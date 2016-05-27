<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Player\Reference;

use Honeybee\Model\Aggregate\ReferencedEntityType;
use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\EntityType;
use Trellis\Common\Options;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;

class ClanType extends ReferencedEntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Clan',
            [
                new Text('name', $this, [], $parent_attribute)
            ],
            new Options(
                [
                    'referenced_type' => EntityType::NAMESPACE_PREFIX . 'Team\\TeamType',
                    'referenced_type_prefix' => 'team',
                    'identifying_attribute' => 'identifier'
                ]
            ),
            $parent,
            $parent_attribute
        );
    }

    public static function getEntityImplementor()
    {
        return Clan::CLASS;
    }
}
