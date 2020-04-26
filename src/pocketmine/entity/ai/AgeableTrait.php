<?php

namespace pocketmine\entity\ai;

trait AgeableTrait{

    public $baby = false;

    public function initEntity(): void{
        if($this->getGenericFlag(self::DATA_FLAG_BABY)){
            $this->setBaby();
        }
        parent::initEntity();
    }

    public function isBaby() : bool {
		return $this->baby;
    }
    
    public function setBaby(bool $baby = true) : self {
		$this->baby = $baby;
		$this->setGenericFlag(self::DATA_FLAG_BABY, $baby);
		$this->setSprinting();
		$this->setScale($baby ? 0.5 : 1);
		return $this;
	}
}