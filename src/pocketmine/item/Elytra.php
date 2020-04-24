<?php

namespace pocketmine\item;

const SLOT_NUMBER = 1;

class Elytra extends Item{

    public function __construct(int $meta = 0){
        parent::__construct(Item::ELYTRA, $meta, "Elytra");
    }

    public function getMaxStackSize(): int{
        return 1;
    }
}