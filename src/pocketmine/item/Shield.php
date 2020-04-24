<?php

namespace pocketmine\item;

class Shield extends Tool{

    public function __construct(int $meta = 0){
        parent::__construct(ItemIds::SHIELD, $meta, "Shield");
    }

    public function getMaxDurability(): int{
        return 337;
    }
}