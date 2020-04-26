<?php

namespace pocketmine\entity\ai;

use pocketmine\Player;

interface Lookable{

    public function onPlayerLook(Player $player) : void;
}