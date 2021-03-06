<?php

namespace Honeybee\Tests\Fixture\TopicSchema\Projection\Topic;

use Honeybee\Tests\Fixture\TopicSchema\Projection\ProjectionType;
use Trellis\Runtime\Attribute\EntityReferenceList\EntityReferenceListAttribute;
use Trellis\Runtime\Attribute\Text\TextAttribute;
use Workflux\StateMachine\StateMachineInterface;

class TopicType extends ProjectionType
{
    public function __construct(StateMachineInterface $state_machine)
    {
        $this->workflow_state_machine = $state_machine;

        parent::__construct(
            'Topic',
            [
                new TextAttribute('title', $this),
                new EntityReferenceListAttribute(
                    'Topic_0p-tion',
                    $this,
                    [
                        'min_count' => 0,
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Fixture\\TopicSchema\\Projection\\Topic\\Reference\\TopicOptionType'
                        ]
                    ]
                ),
            ]
        );
    }

    public static function getEntityImplementor()
    {
        return Topic::CLASS;
    }
}
