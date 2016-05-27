<?php

namespace Honeybee\Projection\EventHandler;

use Honeybee\EntityInterface;
use Honeybee\EntityTypeInterface;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\DataAccess\Query\AttributeCriteria;
use Honeybee\Infrastructure\DataAccess\Query\CriteriaList;
use Honeybee\Infrastructure\DataAccess\Query\Query;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Infrastructure\DataAccess\Query\Comparison\Equals;
use Honeybee\Infrastructure\DataAccess\Storage\StorageWriterMap;
use Honeybee\Infrastructure\Event\EventHandler;
use Honeybee\Infrastructure\Event\EventInterface;
use Honeybee\Infrastructure\Event\Bus\EventBusInterface;
use Honeybee\Infrastructure\Event\Bus\Channel\ChannelMap;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\Projection\ProjectionInterface;
use Honeybee\Projection\ProjectionUpdatedEvent;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Entity\EntityList;
use Trellis\Runtime\Entity\EntityReferenceInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class RelationProjectionUpdater extends EventHandler
{
    protected $storage_writer_map;

    protected $query_service_map;

    protected $projection_type_map;

    protected $event_bus;

    public function __construct(
        ConfigInterface $config,
        LoggerInterface $logger,
        StorageWriterMap $storage_writer_map,
        QueryServiceMap $query_service_map,
        ProjectionTypeMap $projection_type_map,
        EventBusInterface $event_bus
    ) {
        parent::__construct($config, $logger);

        $this->storage_writer_map = $storage_writer_map;
        $this->query_service_map = $query_service_map;
        $this->projection_type_map = $projection_type_map;
        $this->event_bus = $event_bus;
    }

    public function handleEvent(EventInterface $event)
    {
        return $this->invokeEventHandler($event, 'on');
    }

    protected function onProjectionUpdated(ProjectionUpdatedEvent $event)
    {
        $affected_relatives = $this->loadAffectedRelativesFromProjectionEvent($event);

        // reconstruct complete projection from event data
        $source_projection_type_impl = $event->getProjectionType();
        $source_projection_type = $this->projection_type_map->getByClassName($source_projection_type_impl);
        $source_projection = $source_projection_type->createEntity($event->getData());

        $updated_relatives = $this->updateAffectedRelatives($affected_relatives, $source_projection);

        $this->storeUpdatedProjections($affected_relatives, $updated_relatives);

        return $updated_relatives;
    }

    protected function updateAffectedRelatives(EntityList $affected_relatives, ProjectionInterface $source_projection)
    {
        $referenced_identifier = $source_projection->getIdentifier();
        $updated_relatives = [];
        foreach ($affected_relatives as $affected_relative) {
            // collate the paths and matching entity list attributes from the affected projection
            $updated_state = $affected_relative->toArray();
            $affected_relative_type = $affected_relative->getType();
            $affected_relative_prefix = $affected_relative_type->getPrefix();
            $affected_entities = $affected_relative->collateChildren(
                function (EntityInterface $embedded_entity) use ($referenced_identifier) {
                    return $embedded_entity instanceof EntityReferenceInterface
                        && $embedded_entity->getReferencedIdentifier() === $referenced_identifier;
                }
            );

            // reconstruct related projection state adding the updated mirrored values
            foreach ($affected_entities as $affected_entity_value_path => $affected_entity) {
                $affected_entity_type = $affected_entity->getType();
                $affected_entity_prefix = $affected_entity_type->getPrefix();
                $mirrored_values =
                    $affected_entity_type->createMirroredEntity($source_projection, $affected_entity)->toArray();
                // @todo if the current affected entity type has no mirrored attributes we can cache the
                // mirrored values and improve performance by skipping additional unecessary recursion
                $mirrored_values['@type'] = $affected_entity_prefix;
                $mirrored_values['identifier'] = $affected_entity->getIdentifier();
                $mirrored_values['referenced_identifier'] = $affected_entity->getReferencedIdentifier();
                // insert the mirrored values in the correct position in our updated state
                preg_match_all(
                    '#(?<parent>[a-z]+)\.[a-z]+\[(?<position>\d+)\]\.?#',
                    $affected_entity_value_path,
                    $value_path_parts,
                    PREG_SET_ORDER
                );
                $nested_value = &$updated_state;
                foreach ($value_path_parts as $value_path_part) {
                    $nested_value = &$nested_value[$value_path_part['parent']][$value_path_part['position']];
                }
                $nested_value = $mirrored_values;
            }

            // create the new projection from the updated state
            $updated_relative = $affected_relative_type->createEntity($updated_state);
            $updated_relatives[] = $updated_relative;
        }

        return new EntityList($updated_relatives);
    }

    // @todo investigate possible edge cases where circular dependencies cause endless updates
    protected function storeUpdatedProjections(EntityList $affected_relatives, EntityList $updated_relatives)
    {
        foreach ($affected_relatives as $affected_relative) {
            $updated_relative = $updated_relatives->getEntityByIdentifier($affected_relative->getIdentifier());
            if ($affected_relative->toArray() !== $updated_relative->toArray()) {
                // store updates and distribute events only for changed projections
                $this->getStorageWriter()->write($updated_relative);
                $update_event = new ProjectionUpdatedEvent(
                    [
                        'uuid' => Uuid::uuid4()->toString(),
                        'projection_identifier' => $updated_relative->getIdentifier(),
                        'projection_type' => get_class($updated_relative->getType()),
                        'data' => $updated_relative->toArray()
                    ]
                );
                $this->event_bus->distribute(ChannelMap::CHANNEL_INFRA, $update_event);
            }
        }
    }

    protected function loadAffectedRelativesFromProjectionEvent(ProjectionUpdatedEvent $event)
    {
        // we don't know what exactly has changed in the source projection so first we filter out
        // reference attributes not supporting the matching entity type of the updated projection
        $foreign_projection_type_impl = $event->getProjectionType();
        $referenced_attributes_map = $this->getRelativeProjectionType()->getReferenceAttributes()->filter(
            function ($ref_attribute) use ($foreign_projection_type_impl) {
                foreach ($ref_attribute->getEmbeddedEntityTypeMap() as $ref_embed) {
                    $ref_embed_type_impl = ltrim($ref_embed->getReferencedTypeClass(), '\\');
                    return $ref_embed_type_impl === $foreign_projection_type_impl;
                }
            }
        );

        // build filter criteria to load projections where references may need to be updated
        $reference_filter_list = new CriteriaList([], CriteriaList::OP_OR);
        foreach ($referenced_attributes_map as $ref_attribute) {
            $reference_filter_list->push(
                new AttributeCriteria(
                    $this->buildFieldFilterSpec($ref_attribute),
                    new Equals($event->getProjectionIdentifier())
                )
            );
        }

        return $this->buildQuery($event->getProjectionIdentifier(), $reference_filter_list);
    }

    // finalize query and get results from the query service
    protected function buildQuery($identifier, CriteriaList $reference_filter_list)
    {
        $affected_relatives = [];
        if (!empty($reference_filter_list)) {
            // prevent circular self reference loading
            $filter_criteria_list = new CriteriaList;
            $filter_criteria_list->push(
                new AttributeCriteria('identifier', new Equals('!' . $identifier))
            );
            $filter_criteria_list->push($reference_filter_list);

            // @todo scan and scroll support
            $query_result = $this->getQueryService()->find(
                new Query(
                    new CriteriaList,
                    $filter_criteria_list,
                    new CriteriaList,
                    0,
                    10000
                )
            );
            foreach ($query_result->getResults() as $affected_relative) {
                $affected_relatives[] = $affected_relative;
            }
        }

        return new EntityList($affected_relatives);
    }

    protected function buildFieldFilterSpec(EmbeddedEntityListAttribute $embed_attribute)
    {
        $filter_parts = [];
        $parent_attribute = $embed_attribute->getParent();
        while ($parent_attribute) {
            $filter_parts[] = $parent_attribute->getName();
            $parent_attribute = $parent_attribute->getParent();
        }
        $filter_parts[] = $embed_attribute->getName();
        $filter_parts[] = 'referenced_identifier';

        return implode('.', $filter_parts);
    }

    protected function getRelativeProjectionType()
    {
        $projection_type = $this->config->get('projection_type');
        return $this->projection_type_map->getItem($projection_type);
    }

    protected function getQueryService()
    {
        $query_service_default = sprintf(
            '%s::query_service',
            $this->getRelativeProjectionType()->getPrefix()
        );

        $query_service_key = $this->config->get('query_service', $query_service_default);
        return $this->query_service_map->getItem($query_service_key);
    }

    protected function getStorageWriter()
    {
        $storage_writer_default = sprintf(
            '%s::projection.standard::view_store::writer',
            $this->getRelativeProjectionType()->getPrefix()
        );

        $storage_writer_key = $this->config->get('storage_writer', $storage_writer_default);
        return $this->storage_writer_map->getItem($storage_writer_key);
    }
}
