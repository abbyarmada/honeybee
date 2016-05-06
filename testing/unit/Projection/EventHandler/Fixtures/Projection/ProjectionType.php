<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection;

use Honeybee\Projection\ProjectionType as BaseProjectionType;
use Workflux\StateMachine\StateMachineInterface;

abstract class ProjectionType extends BaseProjectionType
{
    const VENDOR = 'Honeybee-CMF';

    const PACKAGE = 'ProjectionFixtures';

    protected $workflow_state_machine;

    public function __construct($name, StateMachineInterface $state_machine)
    {
        $this->workflow_state_machine = $state_machine;

        parent::__construct($name);
    }

    public function getPackage()
    {
        return self::PACKAGE;
    }

    public function getVendor()
    {
        return self::VENDOR;
    }

    public function getWorkflowStateMachine()
    {
        return $this->workflow_state_machine;
    }
}