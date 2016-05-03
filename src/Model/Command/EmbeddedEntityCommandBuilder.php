<?php

namespace Honeybee\Model\Command;

use Honeybee\EntityInterface;
use Honeybee\EntityTypeInterface;
use Honeybee\Infrastructure\Command\CommandBuilder;
use Honeybee\Infrastructure\Command\CommandBuilderList;
use Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\AddEmbeddedEntityCommand;
use Honeybee\Model\Task\ModifyAggregateRoot\ModifyEmbeddedEntity\ModifyEmbeddedEntityCommand;
use Honeybee\Model\Task\ModifyAggregateRoot\RemoveEmbeddedEntity\RemoveEmbeddedEntityCommand;
use Trellis\Runtime\Entity\EntityList;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Validator\Result\IncidentInterface;
use Shrink0r\Monatic\Error;
use Shrink0r\Monatic\Success;
use Shrink0r\Monatic\Result;

class EmbeddedEntityCommandBuilder extends CommandBuilder
{
    protected $entity_type;

    public function __construct(EntityTypeInterface $entity_type, $command_class)
    {
        parent::__construct($command_class);

        $this->entity_type = $entity_type;
        $this->command_state['embedded_entity_type'] = $entity_type->getPrefix();
        $this->command_state['embedded_entity_commands'] = new EmbeddedEntityTypeCommandList;
    }

    /**
     * @return array
     */
    protected function getCommandProperties($command_class)
    {
        $properties = parent::getCommandProperties($command_class);

        // sort properties to ensure embedded entity commands are validated last
        usort(
            $properties,
            function ($a, $b) {
                return $b === 'embedded_entity_commands' ? -1 : 1;
            }
        );

        return $properties;
    }

    public function getParentAttributeName()
    {
        return $this->command_state['parent_attribute_name'];
    }

    /**
     * @return array
     */
    protected function getEmbeddedCommands(EmbeddedEntityListAttribute $attribute, array $values)
    {
        $affected_identifiers = [];
        $attribute_name = $attribute->getName();
        $embedded_entity_list = $this->projection ? $this->projection->getValue($attribute_name) : new EntityList;
        $builder_list = new CommandBuilderList;

        foreach ($values as $position => $embedded_values) {
            $embed_type_prefix = $embedded_values['@type'];
            unset($embedded_values['@type']);
            $embed_type = $attribute->getEmbeddedTypeByPrefix($embed_type_prefix);

            /*
             * Filter entities from the projection by incoming payload identifiers. If the
             * identifier is not matched then prepare an 'add' command, otherwise a 'modify'.
             */
            $affected_entity = $embedded_entity_list->filter(
                function ($embedded_entity) use ($embedded_values) {
                    return $embedded_entity instanceof EntityInterface
                    && isset($embedded_values['identifier'])
                    && $embedded_entity->getIdentifier() === $embedded_values['identifier'];
                }
            )->getFirst();

            if (!$affected_entity) {
                $builder_list->push(
                    (new self($embed_type, AddEmbeddedEntityCommand::CLASS))
                    ->withParentAttributeName($attribute_name)
                    ->withPosition($position)
                    ->withValues($embedded_values)
                );
            } else {
                $affected_identifiers[] = $affected_entity->getIdentifier();
                $modified_values = $this->filterUnmodifiedValues($affected_entity, $embedded_values);
                // prepare a modify command if values or position has changed
                if (!empty($modified_values)
                    || $embedded_entity_list->getKey($affected_entity) != $position
                ) {
                    $builder_list->push(
                        (new self($embed_type, ModifyEmbeddedEntityCommand::CLASS))
                        ->withParentAttributeName($attribute_name)
                        ->withEmbeddedEntityIdentifier($affected_entity->getIdentifier())
                        ->withPosition($position)
                        ->withValues($modified_values)
                    );
                }
            }
        }

        /*
         * Iterate the projection attribute entity list and prepare remove commands for
         * any embedded entities with no incoming payload, or compensate for commands
         * which already exist.
         */
        foreach ($embedded_entity_list as $embedded_entity) {
            // if an entity is not found in the payload then we do compensation checks to make
            // sure we don't inadvertently add an existing entity
            if (!in_array($embedded_entity->getIdentifier(), $affected_identifiers)) {
                // look for any add command which has no difference in values for the current entity
                $command_key = null;
                foreach ($builder_list as $key => $command_builder) {
                    if ($command_builder->getCommandClass() === AddEmbeddedEntityCommand::CLASS
                        && empty($this->filterUnmodifiedValues($embedded_entity, $command_builder->getValues()))
                    ) {
                        $command_key = $key;
                        break;
                    }
                }
                if (!is_null($command_key)) {
                    // remove the unnecessary add command if the entity already exists in the projection
                    $builder_list->splice($command_key);
                } else {
                    // the projection entity was not found in the payload so we can prepare removal
                    $builder_list->push(
                        (new self($embedded_entity->getType(), RemoveEmbeddedEntityCommand::CLASS))
                        ->withParentAttributeName($attribute_name)
                        ->withEmbeddedEntityIdentifier($embedded_entity->getIdentifier())
                    );
                }
            }
        }

        return $builder_list->build();
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return isset($this->command_state['values']) ? $this->command_state['values'] : [];
    }

    /**
     * @return Result
     */
    protected function validateValues(array $values)
    {
        $errors = [];
        $sanitized_values = [];

        foreach ($this->entity_type->getAttributes() as $attribute_name => $attribute) {
            if (isset($values[$attribute_name])) {
                if ($attribute instanceof EmbeddedEntityListAttribute) {
                    /*
                     * prepare and build embedded commands on the fly and then add them to the global
                     * scope list which is validated later
                     */
                    $result = $this->getEmbeddedCommands($attribute, $values[$attribute_name]);
                    if ($result instanceof Success) {
                        $this->command_state['embedded_entity_commands']->addItems($result->get());
                        continue;
                    }
                } else {
                    $result = $this->sanitizeAttributeValue($attribute, $values[$attribute_name]);
                }

                if ($result instanceof Success) {
                    $sanitized_values[$attribute_name] = $result->get();
                } elseif ($result instanceof Error) {
                    $errors[] = $result->get();
                }
            }
        }

        return empty($errors) ? Success::unit($sanitized_values) : Error::unit($errors);
    }

    /**
     * @return Result
     */
    protected function sanitizeAttributeValue(AttributeInterface $attribute, $value)
    {
        $errors = [];
        $sanitized_value = null;

        $value_holder = $attribute->createValueHolder();
        $result = $value_holder->setValue($value);

        if ($result->getSeverity() > IncidentInterface::NOTICE) {
            foreach ($result->getViolatedRules() as $rule) {
                foreach ($rule->getIncidents() as $name => $incident) {
                    $incident_params = $incident->getParameters();
                    $errors[$attribute->getName()]['@incidents'][] = [ $name => $incident_params ];
                }
            }
        }

        return empty($errors) ? Success::unit($value_holder->toNative()) : Error::unit($errors);
    }

    protected function filterUnmodifiedValues(EntityInterface $entity, array $payload)
    {
        $modified_values = [];
        foreach ($entity->getType()->getAttributes() as $attribute_name => $attribute) {
            if (!array_key_exists($attribute_name, $payload)) {
                continue;
            }
            $value_holder = $attribute->createValueHolder();
            $payload_value = $payload[$attribute_name];
            $attribute_value = $entity->getValue($attribute_name);
            $result = $value_holder->setValue($payload_value, $entity);
            if ($result->getSeverity() <= IncidentInterface::NOTICE) {
                if (!$value_holder->sameValueAs($attribute_value)) {
                    $modified_values[$attribute_name] = $value_holder->toNative();
                }
            } else {
                error_log(
                    sprintf(
                        '[%s] Invalid values given for "%s": %s',
                        __METHOD__,
                        $attribute->getPath(),
                        var_export($payload_value, true)
                    )
                );
            }
        }

        return $modified_values;
    }
}
