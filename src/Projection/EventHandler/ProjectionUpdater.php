<?php

namespace Honeybee\Projection\EventHandler;

use Honeybee\Common\Error\RuntimeError;
use Honeybee\EntityTypeInterface;
use Honeybee\Infrastructure\Config\ConfigInterface;
use Honeybee\Infrastructure\DataAccess\DataAccessServiceInterface;
use Honeybee\Infrastructure\DataAccess\Query\QueryServiceMap;
use Honeybee\Infrastructure\DataAccess\Query\AttributeCriteria;
use Honeybee\Infrastructure\DataAccess\Query\CriteriaList;
use Honeybee\Infrastructure\DataAccess\Query\Query;
use Honeybee\Infrastructure\DataAccess\Query\Comparison\Equals;
use Honeybee\Infrastructure\Event\EventHandler;
use Honeybee\Infrastructure\Event\EventInterface;
use Honeybee\Infrastructure\Event\Bus\EventBusInterface;
use Honeybee\Infrastructure\Event\Bus\Channel\ChannelMap;
use Honeybee\Model\Aggregate\AggregateRootTypeMap;
use Honeybee\Model\Event\AggregateRootEventInterface;
use Honeybee\Model\Event\EmbeddedEntityEventInterface;
use Honeybee\Model\Event\EmbeddedEntityEventList;
use Honeybee\Model\Task\CreateAggregateRoot\AggregateRootCreatedEvent;
use Honeybee\Model\Task\ModifyAggregateRoot\AggregateRootModifiedEvent;
use Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\EmbeddedEntityAddedEvent;
use Honeybee\Model\Task\ModifyAggregateRoot\ModifyEmbeddedEntity\EmbeddedEntityModifiedEvent;
use Honeybee\Model\Task\ModifyAggregateRoot\RemoveEmbeddedEntity\EmbeddedEntityRemovedEvent;
use Honeybee\Model\Task\MoveAggregateRootNode\AggregateRootNodeMovedEvent;
use Honeybee\Model\Task\ProceedWorkflow\WorkflowProceededEvent;
use Honeybee\Projection\ProjectionTypeInterface;
use Honeybee\Projection\ProjectionTypeMap;
use Honeybee\Projection\ProjectionUpdatedEvent;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Entity\EntityInterface;
use Trellis\Runtime\Entity\EntityList;
use Trellis\Runtime\Entity\EntityReferenceInterface;
use Trellis\Runtime\ReferencedEntityTypeInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class ProjectionUpdater extends EventHandler
{
    protected $data_access_service;

    protected $query_service_map;

    protected $projection_type_map;

    protected $aggregate_root_type_map;

    protected $event_bus;

    public function __construct(
        ConfigInterface $config,
        LoggerInterface $logger,
        DataAccessServiceInterface $data_access_service,
        QueryServiceMap $query_service_map,
        ProjectionTypeMap $projection_type_map,
        AggregateRootTypeMap $aggregate_root_type_map,
        EventBusInterface $event_bus
    ) {
        parent::__construct($config, $logger);

        $this->data_access_service = $data_access_service;
        $this->query_service_map = $query_service_map;
        $this->projection_type_map = $projection_type_map;
        $this->aggregate_root_type_map = $aggregate_root_type_map;
        $this->event_bus = $event_bus;
    }

    public function handleEvent(EventInterface $event)
    {
        $updated_projections = $this->invokeEventHandler($event, 'on');

        // store updates and distribute projection update events
        foreach ($updated_projections as $updated_projection) {
            // @todo introduce a writeMany method to the storageWriter to save requests
            $this->getStorageWriter($event)->write($updated_projection);
            $update_event = new ProjectionUpdatedEvent(
                [
                    'uuid' => Uuid::uuid4()->toString(),
                    'projection_identifier' => $updated_projection->getIdentifier(),
                    'projection_type' => get_class($updated_projection->getType()),
                    'data' => $updated_projection->toArray()
                ]
            );
            $this->event_bus->distribute(ChannelMap::CHANNEL_INFRA, $update_event);
        }

        // Could possible return multiple projections on AggregateRootNodeMoved
        return $updated_projections->getFirst();
    }

    protected function onAggregateRootCreated(AggregateRootCreatedEvent $event)
    {
        $projection_data = $event->getData();
        $projection_data['identifier'] = $event->getAggregateRootIdentifier();
        $projection_data['revision'] = $event->getSeqNumber();
        $projection_data['created_at'] = $event->getDateTime();
        $projection_data['modified_at'] = $event->getDateTime();
        $projection_data['metadata'] = $event->getMetaData();

        $new_projection = $this->getProjectionType($event)->createEntity($projection_data);
        $this->handleEmbeddedEntityEvents($new_projection, $event->getEmbeddedEntityEvents());

        return new EntityList([ $new_projection ]);
    }

    protected function onAggregateRootModified(AggregateRootModifiedEvent $event)
    {
        $updated_data = $this->loadProjection($event)->toArray();

        foreach ($event->getData() as $attribute_name => $new_value) {
            $updated_data[$attribute_name] = $new_value;
        }
        $updated_data['revision'] = $event->getSeqNumber();
        $updated_data['modified_at'] = $event->getDateTime();
        $updated_data['metadata'] = array_merge($updated_data['metadata'], $event->getMetaData());

        $projection = $this->getProjectionType($event)->createEntity($updated_data);

        $this->handleEmbeddedEntityEvents($projection, $event->getEmbeddedEntityEvents());

        return new EntityList([ $projection ]);
    }

    protected function onWorkflowProceeded(WorkflowProceededEvent $event)
    {
        $updated_data = $this->loadProjection($event)->toArray();
        $updated_data['revision'] = $event->getSeqNumber();
        $updated_data['modified_at'] = $event->getDateTime();
        $updated_data['metadata'] = array_merge($updated_data['metadata'], $event->getMetaData());
        $updated_data['workflow_state'] = $event->getWorkflowState();
        $workflow_parameters = $event->getWorkflowParameters();
        if ($workflow_parameters !== null) {
            $updated_data['workflow_parameters'] = $workflow_parameters;
        }

        $projection = $this->getProjectionType($event)->createEntity($updated_data);

        return new EntityList([ $projection ]);
    }

    protected function onAggregateRootNodeMoved(AggregateRootNodeMovedEvent $event)
    {
        $updated_projection = $this->loadProjection($event);
        $new_parent_node_id = $event->getParentNodeId();
        $current_path_parts = explode('/', $updated_projection->getValue('materialized_path'));
        $new_path_parts = [];

        if (!empty($new_parent_node_id)) {
            $new_parent_projection = $this->loadProjection($event, $new_parent_node_id);
            $new_parent_path = $new_parent_projection->getMaterializedPath();
            if (!empty($new_parent_path)) {
                $new_path_parts = explode('/', $new_parent_path);
            }
            $new_path_parts[] = $new_parent_node_id;
        }

        // create the updated node projection
        $updated_data = $updated_projection->toArray();
        $updated_data['revision'] = $event->getSeqNumber();
        $updated_data['modified_at'] = $event->getDateTime();
        $updated_data['metadata'] = array_merge($updated_data['metadata'], $event->getMetaData());
        $updated_data['parent_node_id'] = $new_parent_node_id;
        $updated_data['materialized_path'] = implode('/', $new_path_parts);
        $projection_type = $this->getProjectionType($event);
        $updated_projections[] = $projection_type->createEntity($updated_data);

        // find all existing children of the updated node
        $affected_children = $this->getQueryService($projection_type)->find(
            // @todo scan and scroll support
            new Query(
                new CriteriaList,
                new CriteriaList(
                    [ new AttributeCriteria('materialized_path', new Equals($updated_projection->getIdentifier())) ]
                ),
                new CriteriaList,
                0,
                10000
            )
        );

        // updated the affected children
        $parent_identifier = $updated_projection->getIdentifier();
        $new_path_parts[] = $parent_identifier;
        $new_child_path_root = implode('/', $new_path_parts);
        foreach ($affected_children->getResults() as $affected_child) {
            $new_child_path = preg_replace(
                '#.*' . $parent_identifier . '#',
                $new_child_path_root,
                $affected_child->getMaterializedPath()
            );
            $child_data = $affected_child->toArray();
            $child_data['materialized_path'] = $new_child_path;
            $updated_projections[] = $projection_type->createEntity($child_data);
        }

        return new EntityList($updated_projections);
    }

    protected function handleEmbeddedEntityEvents(EntityInterface $projection, EmbeddedEntityEventList $events)
    {
        foreach ($events as $event) {
            if ($event instanceof EmbeddedEntityAddedEvent) {
                $this->onEmbeddedEntityAdded($projection, $event);
            } elseif ($event instanceof EmbeddedEntityModifiedEvent) {
                $this->onEmbeddedEntityModified($projection, $event);
            } elseif ($event instanceof EmbeddedEntityRemovedEvent) {
                $this->onEmbeddedEntityRemoved($projection, $event);
            } else {
                throw new RuntimeError(
                    sprintf(
                        'Unsupported domain event-type given. Supported default event-types are: %s.',
                        implode(
                            ', ',
                            [
                                EmbeddedEntityAddedEvent::CLASS,
                                EmbeddedEntityModifiedEvent::CLASS,
                                EmbeddedEntityRemovedEvent::CLASS
                            ]
                        )
                    )
                );
            }
        }
    }

    protected function onEmbeddedEntityAdded(EntityInterface $projection, EmbeddedEntityAddedEvent $event)
    {
        $embedded_projection_attr = $projection->getType()->getAttribute($event->getParentAttributeName());
        $embedded_projection_type = $this->getEmbeddedEntityTypeFor($projection, $event);
        $embedded_projection = $embedded_projection_type->createEntity($event->getData(), $projection);
        $projection_list = $projection->getValue($embedded_projection_attr->getName());
        if ($embedded_projection_type instanceof ReferencedEntityTypeInterface) {
            $embedded_projection = $this->mirrorReferencedProjection($embedded_projection);
        }

        $projection_list->insertAt($event->getPosition(), $embedded_projection);

        $this->handleEmbeddedEntityEvents($embedded_projection, $event->getEmbeddedEntityEvents());
    }

    protected function onEmbeddedEntityModified(EntityInterface $projection, EmbeddedEntityModifiedEvent $event)
    {
        $embedded_projection_attr = $projection->getType()->getAttribute($event->getParentAttributeName());
        $embedded_projection_type = $this->getEmbeddedEntityTypeFor($projection, $event);

        $embedded_projections = $projection->getValue($embedded_projection_attr->getName());
        $projection_to_modify = null;
        foreach ($embedded_projections as $embedded_projection) {
            if ($embedded_projection->getIdentifier() === $event->getEmbeddedEntityIdentifier()) {
                $projection_to_modify = $embedded_projection;
            }
        }

        if ($projection_to_modify) {
            $embedded_projections->removeItem($projection_to_modify);
            $projection_to_modify = $embedded_projection_type->createEntity(
                array_merge($projection_to_modify->toArray(), $event->getData()),
                $projection
            );

            if ($embedded_projection_type instanceof ReferencedEntityTypeInterface) {
                $projection_to_modify = $this->mirrorReferencedProjection($projection_to_modify);
            }

            $embedded_projections->insertAt($event->getPosition(), $projection_to_modify);
            $this->handleEmbeddedEntityEvents($projection_to_modify, $event->getEmbeddedEntityEvents());
        }
    }

    protected function onEmbeddedEntityRemoved(EntityInterface $projection, EmbeddedEntityRemovedEvent $event)
    {
        $projection_list = $projection->getValue($event->getParentAttributeName());
        $projection_to_remove = null;

        foreach ($projection_list as $embedded_projection) {
            if ($embedded_projection->getIdentifier() === $event->getEmbeddedEntityIdentifier()) {
                $projection_to_remove = $embedded_projection;
            }
        }

        if ($projection_to_remove) {
            $projection_list->removeItem($projection_to_remove);
        }
    }

    /**
     * Evaluate and updated mirrored values from a loaded referenced projection
     */
    protected function mirrorReferencedProjection(EntityReferenceInterface $projection)
    {
        $projection_type = $projection->getType();
        $mirrored_attribute_map = $projection_type->getAttributes()->filter(
            function (AttributeInterface $attribute) {
                return (bool)$attribute->getOption('mirrored', false) === true;
            }
        );

        // Don't need to load a referenced entity if the mirrored attribute map is empty
        if ($mirrored_attribute_map->isEmpty()) {
            return $projection;
        }

        // Load the referenced projection to mirror values from
        $referenced_type = $this->projection_type_map->getByClassName(
            $projection_type->getReferencedTypeClass()
        );
        $referenced_identifier = $projection->getReferencedIdentifier();

        if ($referenced_identifier === $projection->getRoot()->getIdentifier()) {
            $referenced_projection = $projection->getRoot(); // self reference, no need to load
        } else {
            $referenced_projection = $this->loadReferencedProjection($referenced_type, $referenced_identifier);
            if (!$referenced_projection) {
                $this->logger->debug('[Zombie Alarm] Unable to load referenced projection: ' . $referenced_identifier);
                return $projection;
            }
        }

        // Add default attribute values
        $mirrored_values['@type'] = $projection_type->getPrefix();
        $mirrored_values['identifier'] = $projection->getIdentifier();
        $mirrored_values['referenced_identifier'] = $projection->getReferencedIdentifier();
        $mirrored_values = array_merge(
            $projection->createMirrorFrom($referenced_projection)->toArray(),
            $mirrored_values
        );

        return $projection_type->createEntity($mirrored_values, $projection->getParent());
    }

    protected function loadProjection(AggregateRootEventInterface $event, $identifier = null)
    {
        return $this->getStorageReader($event)->read($identifier ?: $event->getAggregateRootIdentifier());
    }

    protected function loadReferencedProjection(EntityTypeInterface $referenced_type, $identifier)
    {
        $search_result = $this->getFinder($referenced_type)->getByIdentifier($identifier);
        if (!$search_result->hasResults()) {
            return null;
        }
        return $search_result->getFirstResult();
    }

    protected function getEmbeddedEntityTypeFor(EntityInterface $projection, EmbeddedEntityEventInterface $event)
    {
        $embed_attribute = $projection->getType()->getAttribute($event->getParentAttributeName());
        return $embed_attribute->getEmbeddedTypeByPrefix($event->getEmbeddedEntityType());
    }

    protected function getProjectionType(AggregateRootEventInterface $event)
    {
        $ar_type = $this->aggregate_root_type_map->getByClassName($event->getAggregateRootType());
        return $this->projection_type_map->getItem($ar_type->getPrefix());
    }

    protected function getQueryService(ProjectionTypeInterface $projection_type)
    {
        $query_service_default = $projection_type->getPrefix() . '::query_service';
        $query_service_key = $this->config->get('query_service', $query_service_default);
        return $this->query_service_map->getItem($query_service_key);
    }

    protected function getStorageWriter(AggregateRootEventInterface $event)
    {
        $projection_type = $this->getProjectionType($event);
        return $this->getDataAccessComponent($this->getProjectionType($event), 'writer');
    }

    protected function getStorageReader(AggregateRootEventInterface $event)
    {
        return $this->getDataAccessComponent($this->getProjectionType($event), 'reader');
    }

    protected function getFinder(EntityTypeInterface $entity_type)
    {
        return $this->getDataAccessComponent($entity_type, 'finder');
    }

    protected function getDataAccessComponent(ProjectionTypeInterface $projection_type, $component = 'reader')
    {
        $default_component_name = sprintf(
            '%s::projection.standard::view_store::%s',
            $projection_type->getPrefix(),
            $component
        );
        $custom_component_option = $projection_type->getPrefix() . '.' . $component;

        switch ($component) {
            case 'finder':
                return $this->data_access_service->getFinder(
                    $this->config->get($custom_component_option, $default_component_name)
                );
                break;
            case 'reader':
                return $this->data_access_service->getStorageReader(
                    $this->config->get($custom_component_option, $default_component_name)
                );
                break;
            case 'writer':
                return $this->data_access_service->getStorageWriter(
                    $this->config->get($custom_component_option, $default_component_name)
                );
                break;
        }

        throw new RuntimeError('Invalid data access component name given: ' . $component);
    }
}
