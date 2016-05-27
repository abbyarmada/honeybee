<?php

/*
 *  Aggregate root node moved test
 */
return [
    'event' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Task\TeamNodeMovedEvent',
        'data' => [
            'parent_node_id' => 'honeybee.fixtures.team-d74d3f93-ceba-4782-95ae-92458b4df34c-de_DE-1'
        ],
        'aggregate_root_identifier' => 'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1',
        'aggregate_root_type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Model\Team\TeamType',
        'embedded_entity_events' => [],
        'seq_number' => 3,
        'uuid' => 'a44955b9-b548-4a16-8cf3-c3eb33b08eed',
        'iso_date' => '2016-04-28T10:54:37.371793+00:00',
        'metadata' => []
    ],
    'aggregate_root' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\Team',
        'identifier' => 'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1',
        'revision' => 3,
        'uuid' => 'd8668418-719e-4c09-886c-c49f45d3ee97',
        'short_id' => 0,
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => '2016-03-26T10:52:37.371793+00:00',
        'modified_at' => '2016-03-26T10:52:37.371793+00:00',
        'workflow_state' => 'edit',
        'workflow_parameters' => [],
        'metadata' => [],
        'parent_node_id' => '',
        'materialized_path' => '',
        'name' => 'Modifying Team'
    ],
    'parent_node' => [
        '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\Team',
        'identifier' => 'honeybee.fixtures.team-d74d3f93-ceba-4782-95ae-92458b4df34c-de_DE-1',
        'revision' => 9,
        'uuid' => 'd74d3f93-ceba-4782-95ae-92458b4df34c',
        'short_id' => 0,
        'language' => 'de_DE',
        'version' => 1,
        'created_at' => '2016-03-26T10:52:37.371793+00:00',
        'modified_at' => '2016-03-26T10:52:37.371793+00:00',
        'workflow_state' => 'edit',
        'workflow_parameters' => [],
        'metadata' => [],
        'parent_node_id' => 'honeybee.fixtures.team-a44955b9-b548-4a16-8cf3-c3eb33b08eed-de_DE-1',
        'materialized_path' =>
            'honeybee.fixtures.team-3e67d616-6d36-4520-b0a6-ab77291584d2-de_DE-1/' .
            'honeybee.fixtures.team-a44955b9-b548-4a16-8cf3-c3eb33b08eed-de_DE-1',
        'name' => 'New Parent Team'
    ],
    'query' => [
        '@type' => 'Honeybee\Infrastructure\DataAccess\Query\Query',
        'search_criteria_list' => [],
        'filter_criteria_list' => [
            [
                '@type' => 'Honeybee\Infrastructure\DataAccess\Query\AttributeCriteria',
                'attribute_path' => 'materialized_path',
                'comparison' => [
                    '@type' => 'Honeybee\Infrastructure\DataAccess\Query\Comparison\Equals',
                    'comparator' => 'eq',
                    'comparand' => 'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1'
                ]
            ]
        ],
        'sort_criteria_list' => [],
        'offset' => 0,
        'limit' => 10000
    ],
    'projections' => [
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\Team',
            'identifier' => 'honeybee.fixtures.team-abeca70c-c0d9-4d6d-a983-1441d7343954-de_DE-1',
            'revision' => 1,
            'uuid' => 'a726301d-dbae-4fb6-91e9-a19188a17e71',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-03-27T10:52:37.371793+00:00',
            'modified_at' => '2016-03-27T10:52:37.371793+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'parent_node_id' => 'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1',
            'materialized_path' =>
                'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1',
            'name' => 'Child Team'
        ],
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\Team',
            'identifier' => 'honeybee.fixtures.team-5ab9c99b-3d69-4cfe-8f06-1d367a02160b-de_DE-1',
            'revision' => 1,
            'uuid' => '5ab9c99b-3d69-4cfe-8f06-1d367a02160b',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-03-27T10:53:37.371793+00:00',
            'modified_at' => '2016-03-27T10:53:37.371793+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'parent_node_id' => 'honeybee.fixtures.team-abeca70c-c0d9-4d6d-a983-1441d7343954-de_DE-1',
            'materialized_path' =>
                'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1/' .
                'honeybee.fixtures.team-abeca70c-c0d9-4d6d-a983-1441d7343954-de_DE-1',
            'name' => 'Grand Child Team'
        ]
    ],
    'expectations' => [
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\Team',
            'identifier' => 'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1',
            'revision' => 3,
            'uuid' => 'd8668418-719e-4c09-886c-c49f45d3ee97',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-03-26T10:52:37.371793+00:00',
            'modified_at' => '2016-04-28T10:54:37.371793+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'parent_node_id' => 'honeybee.fixtures.team-d74d3f93-ceba-4782-95ae-92458b4df34c-de_DE-1',
            'materialized_path' =>
                'honeybee.fixtures.team-3e67d616-6d36-4520-b0a6-ab77291584d2-de_DE-1/' .
                'honeybee.fixtures.team-a44955b9-b548-4a16-8cf3-c3eb33b08eed-de_DE-1/' .
                'honeybee.fixtures.team-d74d3f93-ceba-4782-95ae-92458b4df34c-de_DE-1',
            'name' => 'Modifying Team'
        ],
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\Team',
            'identifier' => 'honeybee.fixtures.team-abeca70c-c0d9-4d6d-a983-1441d7343954-de_DE-1',
            'revision' => 1,
            'uuid' => 'a726301d-dbae-4fb6-91e9-a19188a17e71',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-03-27T10:52:37.371793+00:00',
            'modified_at' => '2016-03-27T10:52:37.371793+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'parent_node_id' => 'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1',
            'materialized_path' =>
                'honeybee.fixtures.team-3e67d616-6d36-4520-b0a6-ab77291584d2-de_DE-1/' .
                'honeybee.fixtures.team-a44955b9-b548-4a16-8cf3-c3eb33b08eed-de_DE-1/' .
                'honeybee.fixtures.team-d74d3f93-ceba-4782-95ae-92458b4df34c-de_DE-1/' .
                'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1',
            'name' => 'Child Team'
        ],
        [
            '@type' => 'Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Team\Team',
            'identifier' => 'honeybee.fixtures.team-5ab9c99b-3d69-4cfe-8f06-1d367a02160b-de_DE-1',
            'revision' => 1,
            'uuid' => '5ab9c99b-3d69-4cfe-8f06-1d367a02160b',
            'short_id' => 0,
            'language' => 'de_DE',
            'version' => 1,
            'created_at' => '2016-03-27T10:53:37.371793+00:00',
            'modified_at' => '2016-03-27T10:53:37.371793+00:00',
            'workflow_state' => 'edit',
            'workflow_parameters' => [],
            'metadata' => [],
            'parent_node_id' => 'honeybee.fixtures.team-abeca70c-c0d9-4d6d-a983-1441d7343954-de_DE-1',
            'materialized_path' =>
                'honeybee.fixtures.team-3e67d616-6d36-4520-b0a6-ab77291584d2-de_DE-1/' .
                'honeybee.fixtures.team-a44955b9-b548-4a16-8cf3-c3eb33b08eed-de_DE-1/' .
                'honeybee.fixtures.team-d74d3f93-ceba-4782-95ae-92458b4df34c-de_DE-1/' .
                'honeybee.fixtures.team-d8668418-719e-4c09-886c-c49f45d3ee97-de_DE-1/' .
                'honeybee.fixtures.team-abeca70c-c0d9-4d6d-a983-1441d7343954-de_DE-1',
            'name' => 'Grand Child Team'
        ]
    ]
];
