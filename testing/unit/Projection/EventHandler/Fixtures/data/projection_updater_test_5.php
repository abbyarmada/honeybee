<?php

/*
 *  Test cases are described by related flow chart images
 */
return [
    'event' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Task\GameModifiedEvent',
        'data' => [
            'title' => 'Doom 3'
        ],
        'aggregate_root_identifier' => 'honeybee.fixtures.game-a7cec777-d932-4bbd-8156-261138d3fe39-de_DE-1',
        'aggregate_root_type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Game\GameType',
        'embedded_entity_events' => [],
        'seq_number' => 3,
        'uuid' => 'a7cec777-d932-4bbd-8156-261138d3fe39',
        'iso_date' => '2016-04-28T10:53:53.530472+00:00',
        'metadata' => []
    ],
    'aggregate_root' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\GameType',
        'identifier' => 'honeybee.fixtures.game-a7cec777-d932-4bbd-8156-261138d3fe39-de_DE-1',
        'revision' => 3,
        'uuid' => 'a7cec777-d932-4bbd-8156-261138d3fe39',
        'short_id' => 0,
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => '2016-04-28T10:53:53.530472+00:00',
        'modified_at' => '2016-04-28T10:53:53.530472+00:00',
        'workflow_state' => 'edit',
        'workflow_parameters' => [],
        'metadata' => [],
        'title' => 'Doom 3',
        'challenges' => [],
        'players' => []
    ],
    'references' => [
    ],
    'expected' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Game',
        'identifier' => 'honeybee.fixtures.game-a7cec777-d932-4bbd-8156-261138d3fe39-de_DE-1',
        'revision' => 3,
        'uuid' => 'a7cec777-d932-4bbd-8156-261138d3fe39',
        'short_id' => 0,
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => '2016-04-28T10:53:53.530472+00:00',
        'modified_at' => '2016-04-28T10:53:53.530472+00:00',
        'workflow_state' => 'edit',
        'workflow_parameters' => [],
        'metadata' => [],
        'title' => 'Doom 3',
        'challenges' => [],
        'players' => []
    ]
];
