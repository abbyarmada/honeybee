<?php

/*
 *  Test cases are described by related flow chart images
 */
return [
    'event' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Task\GameCreatedEvent',
        'data' => [
            'identifier' => 'honeybee.fixtures.game-49c5a3b7-8127-4169-8676-a9ebb5229142-de_DE-1',
            'uuid' => '49c5a3b7-8127-4169-8676-a9ebb5229142',
            'language' => 'de_DE',
            'version' => 1,
            'workflow_state' => 'edit',
            'title' => 'Doom 7'
        ],
        'aggregate_root_identifier' => 'honeybee.fixtures.game-49c5a3b7-8127-4169-8676-a9ebb5229142-de_DE-1',
        'aggregate_root_type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Game\GameType',
        'embedded_entity_events' => [
            [
                '@type' => 'Honeybee\Model\Task\ModifyAggregateRoot\AddEmbeddedEntity\EmbeddedEntityAddedEvent',
                'data' => [
                    'identifier' => 'ca8a5117-927a-4f94-8b0d-7b0be6196acf',
                    'referenced_identifier' =>
                        'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1'
                ],
                'position' => 0,
                'embedded_entity_identifier' => 'ca8a5117-927a-4f94-8b0d-7b0be6196acf',
                'embedded_entity_type' => 'player',
                'parent_attribute_name' => 'players',
                'embedded_entity_events' => []
            ]
        ],
        'seq_number' => 1,
        'uuid' => '44c4597c-f463-4916-a330-2db87ef36547',
        'iso_date' => '2016-04-28T10:52:37.371793+00:00',
        'metadata' => []
    ],
    'aggregate_root' => [
    ],
    'references' => [
        'player' => [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Player\Player',
            'identifier' => 'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
            'revision' => 1,
            'uuid' => 'a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-03-27T10:52:37.371793+00:00',
            'modified_at' => '2016-03-27T10:52:37.371793+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'name' => 'Mock Player',
            'profiles' => [
                [
                    '@type' => 'profile',
                    'identifier' => '7b446909-9e43-42fb-a043-969463747e2a',
                    'alias' => 'mockprofile1',
                    'tags' => [ 'mock', 'player', 'profile', 'one' ],
                    'badges' => [
                        [
                            '@type' => 'badge',
                            'identifier' => '3c642c81-dc8b-485c-9b63-3eaade13c7de',
                            'award' => 'High Score'
                        ]
                    ],
                    'unmirrored_badges' => [
                        [
                            '@type' => 'badge',
                            'identifier' => '30efa6d8-792b-4fc8-95af-6fb2a048bcac',
                            'award' => 'Low Score'
                        ]
                    ],
                    'teams' => [
                        [
                            '@type' => 'team',
                            'identifier' => '704856e1-28a3-4069-8055-4ff4fd2f3b83',
                            'referenced_identifier' =>
                                'honeybee.fixtures.team-5cf33cf1-554b-40be-98e7-ef7b4e98ec8c-de_DE-1',
                            'name' => 'Super Clan'
                        ]
                    ]
                ]
            ],
            'unmirrored_profiles' => [
                [
                    '@type' => 'profile',
                    'identifier' => '94a03a00-8420-4ee2-a4f7-0e0ff1989592',
                    'alias' => 'hiddenprofile2',
                    'tags' => [ 'hidden', 'player', 'profile', 'two' ],
                    'badges' => [],
                    'unmirrored_badges' => []
                ]
            ]
        ],
    ],
    'expected' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Game',
        'identifier' => 'honeybee.fixtures.game-49c5a3b7-8127-4169-8676-a9ebb5229142-de_DE-1',
        'revision' => 1,
        'uuid' => '49c5a3b7-8127-4169-8676-a9ebb5229142',
        'short_id' => 0,
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => '2016-04-28T10:52:37.371793+00:00',
        'modified_at' => '2016-04-28T10:52:37.371793+00:00',
        'workflow_state' => 'edit',
        'workflow_parameters' => [],
        'metadata' => [],
        'title' => 'Doom 7',
        'challenges' => [],
        'players' => [
            [
                '@type' => 'player',
                'identifier' => 'ca8a5117-927a-4f94-8b0d-7b0be6196acf',
                'referenced_identifier' => 'honeybee.fixtures.player-a726301d-dbae-4fb6-91e9-a19188a17e71-de_DE-1',
                'name' => 'Mock Player',
                'profiles' => [
                    [
                        '@type' => 'profile',
                        'identifier' => '7b446909-9e43-42fb-a043-969463747e2a',
                        'alias' => 'mockprofile1',
                        'tags' => [ 'mock', 'player', 'profile', 'one' ],
                        'teams' => [
                            [
                                '@type' => 'team',
                                'identifier' => '704856e1-28a3-4069-8055-4ff4fd2f3b83',
                                'referenced_identifier' =>
                                    'honeybee.fixtures.team-5cf33cf1-554b-40be-98e7-ef7b4e98ec8c-de_DE-1',
                                'name' => 'Super Clan'
                            ]
                        ],
                        'badges' => [
                            [
                                '@type' => 'badge',
                                'identifier' => '3c642c81-dc8b-485c-9b63-3eaade13c7de',
                                'award' => 'High Score'
                            ]
                        ],
                        'unmirrored_badges' => []
                    ]
                ],
                'unmirrored_profiles' => []
            ]
        ]
    ]
];
