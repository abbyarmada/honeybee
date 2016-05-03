<?php

namespace Honeybee\Model\Aggregate;

use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Attribute\Integer\IntegerAttribute;
use Trellis\Runtime\Attribute\KeyValueList\KeyValueListAttribute;
use Trellis\Runtime\Attribute\Text\TextAttribute;
use Trellis\Runtime\Attribute\Timestamp\TimestampAttribute;
use Trellis\Runtime\Attribute\Uuid\UuidAttribute;
use Trellis\Runtime\Entity\EntityInterface;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Common\ScopeKeyInterface;
use Honeybee\Common\Util\StringToolkit;
use Honeybee\EntityType;
use Workflux\StateMachine\StateMachineInterface;

abstract class AggregateRootType extends EntityType implements AggregateRootTypeInterface
{
    public function getVendor()
    {
        return $this->getOption('vendor', '');
    }

    public function getPackage()
    {
        return $this->getOption('package', '');
    }

    public function getPackagePrefix()
    {
        return sprintf(
            '%s.%s',
            strtolower($this->getVendor()),
            StringToolkit::asSnakeCase($this->getPackage())
        );
    }

    public function getPrefix()
    {
        return sprintf(
            '%s.%s',
            $this->getPackagePrefix(),
            StringToolkit::asSnakeCase($this->getName())
        );
    }

    /**
     * Creates a new AggregateRoot instance.
     * The parent (EntityType) method is overriden to adhere the rules for a new aggregate-root:
     * no initial state and being the root-entity also no parent.
     *
     * @param array $data Optional data for initial hydration (is dropped by this class).
     * @param EntityInterface $parent_entity (also dropped)
     *
     * @return EntityInterface
     *
     * @throws InvalidTypeException
     */
    public function createEntity(array $data = [], EntityInterface $parent_entity = null)
    {
        $implementor = $this->getEntityImplementor();
        if (!class_exists($implementor, true)) {
            throw new RuntimeError(
                sprintf(
                    'Unable to resolve the given aggregate-root implementor "%s" to an existing class.',
                    $implementor
                )
            );
        }

        return new $implementor($this, $this->getWorkflowStateMachine());
    }

    /**
     * Returns the default attributes that are initially added to a aggregate_root_type upon creation.
     *
     * @return array A list of AttributeInterface implementations.
     */
    public function getDefaultAttributes()
    {
        $attributes = array_merge(
            parent::getDefaultAttributes(),
            [
                new TextAttribute('identifier', $this),
                new IntegerAttribute('revision', $this, [ 'default_value' => 0 ]),
                new UuidAttribute('uuid', $this, [ 'default_value' => 'auto_gen' ]),
                new IntegerAttribute('short_id', $this),
                new TextAttribute('language', $this, [ 'default_value' => 'de_DE' ]),
                new IntegerAttribute('version', $this, [ 'default_value' => 1 ]),
                new TimestampAttribute('created_at', $this, [ 'default_value' => 'now' ]),
                new TimestampAttribute('modified_at', $this, [ 'default_value' => 'now' ]),
                new TextAttribute('workflow_state', $this),
                new KeyValueListAttribute('workflow_parameters', $this)
            ]
        );

        if ($this->isHierarchical()) {
            $attributes[] = new TextAttribute('parent_node_id', $this);
        }

        return $attributes;
    }
}
