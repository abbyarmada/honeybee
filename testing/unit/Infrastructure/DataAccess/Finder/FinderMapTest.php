<?php

namespace Honeybee\Tests\DataAccess\Finder;

use Honeybee\Infrastructure\DataAccess\Finder\FinderInterface;
use Honeybee\Infrastructure\DataAccess\Finder\FinderMap;
use Honeybee\Tests\TestCase;
use Mockery;
use Trellis\Collection\Map;
use Trellis\Collection\TypedMap;

class FinderMapTest extends TestCase
{
    public function testWithEmpty()
    {
        $finder_map = new FinderMap;

        $this->assertInstanceOf(TypedMap::CLASS, $finder_map);
        $this->assertCount(0, $finder_map);
    }

    public function testWithSingleNumericKey()
    {
        $mock_finder = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ $mock_finder ]);

        $this->assertCount(1, $finder_map);
        $this->assertEquals([ 0 ], $finder_map->getKeys());
        $this->assertEquals($mock_finder, $finder_map->getItem(0));
        $this->assertEquals([ $mock_finder ], $finder_map->getItems());
    }

    public function testWithMultipleItems()
    {
        $mock_finder1 = Mockery::mock(FinderInterface::CLASS);
        $mock_finder2 = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ 'finder1' => $mock_finder1, 'finder2' => $mock_finder2 ]);

        $this->assertCount(2, $finder_map);
        $this->assertEquals([ 'finder1', 'finder2' ], $finder_map->getKeys());
        $this->assertEquals([ 'finder1' => $mock_finder1, 'finder2' => $mock_finder2 ], $finder_map->toArray());
    }

    /**
     * @expectedException Trellis\Exception
     */
    public function testWithInvalidType()
    {
        $finder_map = new FinderMap([ new \stdClass ]);
    }

    /**
     * @expectedException Trellis\Exception
     */
    public function testConstructWithNonUniqueValue()
    {
        $mock_finder = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ 'finder1' => $mock_finder, 'finder2' => $mock_finder ]);
    }

    /**
     * @expectedException Trellis\Exception
     */
    public function testSetItemWithNonUniqueValue()
    {
        $mock_finder = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ 'finder1' => $mock_finder ]);
        $finder_map->withItem('finder2', $mock_finder);
    }

    public function testRemoveItem()
    {
        $mock_finder = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ 'finder1' => $mock_finder ]);

        $map_size = $finder_map->getSize();
        $finder_map = $finder_map->withoutItem($mock_finder);
        $this->assertCount($map_size - 1, $finder_map);
    }

    public function testRemoveItemWithMissingValue()
    {
        $mock_finder1 = Mockery::mock(FinderInterface::CLASS);
        $mock_finder2 = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ 'finder1' => $mock_finder1 ]);

        $map_size = $finder_map->getSize();
        $finder_map = $finder_map->withoutItem($mock_finder2);
        $this->assertCount($map_size, $finder_map);
    }

    public function testAppend()
    {
        $mock_finder1 = Mockery::mock(FinderInterface::CLASS);
        $finder_map1 = new FinderMap([ 'finder1' => $mock_finder1 ]);

        $mock_finder2 = Mockery::mock(FinderInterface::CLASS);
        $finder_map2 = new FinderMap([ 'finder2' => $mock_finder2 ]);

        $map_size1 = $finder_map1->getSize();
        $map_size2 = $finder_map2->getSize();

        $finder_map1 = $finder_map1->append($finder_map2);
        $this->assertCount($map_size1 + $map_size2, $finder_map1);
        $this->assertEquals([ 'finder1' => $mock_finder1, 'finder2' => $mock_finder2 ], $finder_map1->getItems());
        $this->assertEquals([ 'finder2' => $mock_finder2 ], $finder_map2->getItems());
    }

    public function testAppendEmpty()
    {
        $mock_finder1 = Mockery::mock(FinderInterface::CLASS);
        $finder_map1 = new FinderMap([ 'finder1' => $mock_finder1 ]);
        $finder_map2 = new FinderMap;

        $map_size = $finder_map1->getSize();

        $finder_map1 = $finder_map1->append($finder_map2);
        $this->assertCount($map_size, $finder_map1);
        $this->assertEquals([ 'finder1' => $mock_finder1 ], $finder_map1->getItems());
        $this->assertEquals([], $finder_map2->getItems());
    }

    /**
     * @expectedException Trellis\Exception
     */
    public function testAppendWithNonUniqueValue()
    {
        $mock_finder1 = Mockery::mock(FinderInterface::CLASS);
        $finder_map1 = new FinderMap([ 'finder1' => $mock_finder1 ]);
        $finder_map2 = new FinderMap([ 'finder2' => $mock_finder1 ]);

        $finder_map1->append($finder_map2);
    }

    /**
     * @expectedException Trellis\Exception
     */
    public function testAppendInvalidMap()
    {
        $mock_finder = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ 'finder1' => $mock_finder ]);
        $non_matching_map = new Map;

        $finder_map->append($non_matching_map);
    }

    public function testFilterAllowAll()
    {
        $mock_finder1 = Mockery::mock(FinderInterface::CLASS);
        $mock_finder2 = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ 'finder1' => $mock_finder1, 'finder2' => $mock_finder2 ]);

        $map_size = $finder_map->getSize();
        $filtered_map = $finder_map->filter(function () {
            return true;
        });

        $this->assertInstanceOf(FinderMap::CLASS, $filtered_map);
        $this->assertEquals($finder_map, $filtered_map);
        $this->assertCount($map_size, $filtered_map);
    }

    public function testFilterAllowNone()
    {
        $mock_finder1 = Mockery::mock(FinderInterface::CLASS);
        $mock_finder2 = Mockery::mock(FinderInterface::CLASS);
        $finder_map = new FinderMap([ 'finder1' => $mock_finder1, 'finder2' => $mock_finder2 ]);

        $filtered_map = $finder_map->filter(function () {
            return false;
        });

        $this->assertInstanceOf(FinderMap::CLASS, $filtered_map);
        $this->assertCount(0, $filtered_map);
    }
}
