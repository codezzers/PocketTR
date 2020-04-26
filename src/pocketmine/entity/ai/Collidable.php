<?php

namespace pocketmine\entity\ai;

use pocketmine\block\Block;
use pocketmine\entity\Entity;

interface Collidable{

    public function onCollideWithEntity(Entity $entity) : void;

    public function onCollideWithBlock(Block $block) : void;

    public function push(CreatureBase $source) : void;
}