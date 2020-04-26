<?php

namespace pocketmine\entity;

class Drowned extends Zombie{

    public const NETWORK_ID = self::DROWNED;

    public function initEntity(): void{
        parent::initEntity();
    }

    protected function applyGravity(): void{
        if(!$this->şsUnderwater()){
            parent::applyGravity();
        }
    }
}