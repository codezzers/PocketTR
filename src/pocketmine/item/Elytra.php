<?php

namespace pocketmine\item;

const SLOT_NUMBER = 1;

class Elytra extends Armor{

    public function __construct($meta = 0, $count = 1){
        parent::__construct(self::ELYTRA, $meta, $count, "Elytra");
    }
}