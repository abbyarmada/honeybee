<?php

namespace Honeybee\Tests\Projection\EventHandler\Fixtures\Projection\Game;

use Honeybee\Projection\Projection;

class Game extends Projection
{
    public function getTitle()
    {
        return $this->getValue('title');
    }

    public function getPlayers()
    {
        return $this->getValue('players');
    }
}