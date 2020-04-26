<?php

namespace pocketmine\entity\ai;

use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;

trait SpawnableTrait{

    protected $spawnLight = 7;

    public static function spawnFromSpawner(Position $spawnPos, ?CompoundTag $spawnData = null) : ?CreatureBase {
		$nbt = self::createBaseNBT($spawnPos);
		if(isset($spawnData)) {
			$nbt = $spawnData->merge($nbt);
			$nbt->setInt("id", self::NETWORK_ID);
		}
		$entity = self::createEntity(self::NETWORK_ID, $spawnPos->level, $nbt);
		if(!$spawnPos->isValid() or count($entity->getBlocksAround()) > 1 or (($entity instanceof MonsterBase and $entity->level->getFullLight($entity) > $entity->spawnLight) or ($entity instanceof AnimalBase and $entity->level->getFullLight($entity) < $entity->spawnLight))) {
			$entity->flagForDespawn();
			return null;
		}else {
			$entity->spawnToAll();
			return $entity;
		}
    }
    
    public function getSpawnLight() : int {
		return $this->spawnLight;
    }
    
    public function setSpawnLight(int $spawnLight) : SpawnableTrait {
		$this->spawnLight = $spawnLight;
		return $this;
	}
}