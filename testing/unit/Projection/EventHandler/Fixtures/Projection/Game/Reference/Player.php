<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game\Reference;

use Honeybee\Projection\ReferencedEntity;

class Player extends ReferencedEntity
{
    public function getName()
    {
        return $this->getValue('name');
    }
}
