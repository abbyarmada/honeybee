<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\Reference;

use Honeybee\Projection\ReferencedEntityType;
use Trellis\Common\Options;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;

class TeamType extends ReferencedEntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Team',
            [
                new Text('name', $this, [ 'mirrored' => true ]),
            ],
            new Options(
                [
                    'referenced_type' => '\\Honeybee\\Tests\\Projection\\EventHandler\\Fixtures\\Projection\\Team\\TeamType',
                    'identifying_attribute' => 'identifier',
                ]
            ),
            $parent,
            $parent_attribute
        );
    }

    public static function getEntityImplementor()
    {
        return Team::CLASS;
    }
}
