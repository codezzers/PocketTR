<?php

namespace pocketmine\entity\ai;

trait CollisionCheckingTrait{

    public function entityBaseTick(int $tickDiff = 1) : bool {
		$this->checkNearEntities();
		return parent::entityBaseTick($tickDiff);
    }
    
    protected function checkNearEntities() {
		foreach($this->level->getNearbyEntities($this->boundingBox, $this) as $entity) {
			if(!$entity->isAlive() or $entity->isFlaggedForDespawn()) {
				continue;
			}
			$entity->scheduleUpdate();
			if($entity instanceof Collidable and $this instanceof Collidable) {
				if($this->getBoundingBox()->intersectsWith($entity->getBoundingBox())) {
					$entity->push($this);
				}
				$entity->onCollideWithEntity($this);
				$this->onCollideWithEntity($entity);
			}
		}
    }
    
    public function onUpdate(int $currentTick) : bool {
		return parent::onUpdate($currentTick);
	}

	protected function checkBlockCollision() : void {
		$vector = $this->temporalVector->setComponents(0, 0, 0);
		foreach($this->getBlocksAround() as $block) {
			$block->onEntityCollide($this);
			$this->onCollideWithBlock($block);
			$block->addVelocityToEntity($this, $vector);
		}
		if($vector->lengthSquared() > 0) {
			$vector = $vector->normalize();
			$d = 0.014;
			$this->motion->x += $vector->x * $d;
			$this->motion->y += $vector->y * $d;
			$this->motion->z += $vector->z * $d;
		}
	}
}