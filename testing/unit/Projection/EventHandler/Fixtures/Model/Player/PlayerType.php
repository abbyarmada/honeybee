<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Player;

use Honeybee\Tests\Projection\EventHandler\Fixtures\Model\EntityType;
use Workflux\StateMachine\StateMachineInterface;
use Trellis\Common\Options;
use Trellis\Runtime\Attribute\Text\TextAttribute as Text;
use Trellis\Runtime\Attribute\GeoPoint\GeoPointAttribute;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;

class PlayerType extends EntityType
{
    public function __construct(StateMachineInterface $state_machine)
    {
        parent::__construct('Player', $state_machine);
    }

    public function getDefaultAttributes()
    {
        return array_merge(
            parent::getDefaultAttributes(),
            [
                new Text('name', $this, [ 'mandatory' => true ]),
                new GeoPointAttribute('location', $this, []),
                new EmbeddedEntityListAttribute(
                    'profiles',
                    $this,
                    [
                        'entity_types' => [
                            self::NAMESPACE_PREFIX . 'Player\\Embed\\ProfileType'
                        ]
                    ]
                ),
                new EmbeddedEntityListAttribute(
                    'simple_profiles',
                    $this,
                    [
                        'entity_types' => [
                            self::NAMESPACE_PREFIX . 'Player\\Embed\\ProfileType'
                        ]
                    ]
                )
            ]
        );
    }

    public static function getEntityImplementor()
    {
        return Player::CLASS;
    }
}
