<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\Embed;

use Honeybee\EntityType;
use Trellis\Common\Options;
use Trellis\Runtime\EntityTypeInterface;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;

class BadgeType extends EntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Badge',
            [
                new Text('award', $this, [], $parent_attribute)
            ],
            new Options,
            $parent,
            $parent_attribute
        );
    }

    public static function getEntityImplementor()
    {
        return Badge::CLASS;
    }
}
