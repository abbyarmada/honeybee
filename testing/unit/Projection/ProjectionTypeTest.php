<?php

namespace Honeybee\Tests\Projection;

use Honeybee\Tests\TestCase;
use Workflux\StateMachine\StateMachineInterface;
use Honeybee\Tests\Fixture\GameSchema\Projection\Game\GameType;
use Honeybee\Tests\Fixture\GameSchema\Projection\Team\TeamType;
use Honeybee\Tests\Fixture\GameSchema\Projection\Player\PlayerType;
use Mockery;
use Trellis\Runtime\Attribute\AttributeMap;

class ProjectionTypeTest extends TestCase
{
    public function testGetDefaultAttributesNames()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new GameType($state_machine);

        $expected_attributes = include __DIR__ . '/Fixture/default_attributes.php';
        $attribute_names = array_keys($expected_attributes);

        $this->assertEquals($attribute_names, $test_entity_type->getDefaultAttributeNames());
    }

    public function testGetDefaultAttributesNamesHierarchicalType()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new TeamType($state_machine);

        $expected_attributes = include __DIR__ . '/Fixture/default_attributes_hierarchical.php';
        $attribute_names = array_keys($expected_attributes);

        $this->assertEquals($attribute_names, $test_entity_type->getDefaultAttributeNames());
    }

    public function testGetDefaultAttributes()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new GameType($state_machine);

        $expected_attributes = include __DIR__ . '/Fixture/default_attributes.php';
        $default_attributes = $test_entity_type->getDefaultAttributes();

        $this->assertInstanceOf(AttributeMap::CLASS, $default_attributes);
        $this->assertCount(10, $default_attributes);
        $this->assertEquals($expected_attributes, $default_attributes->toArray());
    }

    public function testGetDefaultAttributesHierarchicalType()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new TeamType($state_machine);

        $expected_attributes = include __DIR__ . '/Fixture/default_attributes_hierarchical.php';
        $default_attributes = $test_entity_type->getDefaultAttributes();

        $this->assertInstanceOf(AttributeMap::CLASS, $default_attributes);
        $this->assertCount(12, $default_attributes);
        $this->assertEquals($expected_attributes, $default_attributes->toArray());
    }

    public function testIsHierarchical()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new GameType($state_machine);

        $this->assertFalse($test_entity_type->isHierarchical());
    }

    public function testIsHierarchicalHierarchicalType()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new TeamType($state_machine);

        $this->assertTrue($test_entity_type->isHierarchical());
    }

    public function testGetMandatoryAttributes()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new PlayerType($state_machine);
        $mandatory_attributes = $test_entity_type->getMandatoryAttributes();

        $this->assertInstanceOf(AttributeMap::CLASS, $mandatory_attributes);
        $this->assertCount(1, $mandatory_attributes);
    }

    public function testGetVendor()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new GameType($state_machine);

        $this->assertEquals('Honeybee-Tests', $test_entity_type->getVendor());
    }

    public function testGetPackage()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new GameType($state_machine);

        $this->assertEquals('GameSchema', $test_entity_type->getPackage());
    }

    public function testGetVariant()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new GameType($state_machine);

        $this->assertEquals('Standard', $test_entity_type->getVariant());
    }

    public function testGetPrefix()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new GameType($state_machine);

        $this->assertEquals('honeybee-tests.game_schema.game', $test_entity_type->getPrefix());
    }

    public function testGetVariantPrefix()
    {
        $state_machine = Mockery::mock(StateMachineInterface::CLASS);
        $test_entity_type = new GameType($state_machine);

        $this->assertEquals(
            'honeybee-tests.game_schema.game::projection.standard',
            $test_entity_type->getVariantPrefix()
        );
    }

    public function testCreateMirroredEntity()
    {
        $this->markTestIncomplete(
            'Tested implicitly in the (Relation)ProjectionUpdater tests, but requires further explicit tests. ' .
            'Additionally the ProjectionUpdater::mirrorReferencedProjection method could be refactored into ' .
            'the Honeybee\Projection\ReferencedEntityType class.'
        );
    }
}
