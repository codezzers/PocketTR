<?php

namespace pocketmine\entity\monster;

use pocketmine\entity\ai\ClimbingTrait;
use pocketmine\entity\ai\CreatureBase;
use pocketmine\entity\ai\InventoryHolder;
use pocketmine\entity\ai\ItemHolderTrait;
use pocketmine\entity\ai\MonsterBase;
use pocketmine\entity\projectile\Arrow;
use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\TakeItemEntityPacket;

class Skeleton extends MonsterBase implements InventoryHolder{
    use ItemHolderTrait, ClimbingTrait;

    public const NETWORK_ID = self::SKELETON;
	public $width = 0.875;
	public $height = 2.0;
	protected $moveTime;
	protected $attackDelay;
    protected $speed = 1.0;
    
    public function initEntity() : void {
		if(!isset($this->mainHand)) {
			$this->mainHand = Item::get(Item::BOW);
		}
		parent::initEntity();
    }
    
    public function onUpdate(int $currentTick) : bool {
		if($this->isFlaggedForDespawn() or $this->closed) {
			return false;
		}
		if($this->attackTime > 0) {
			return parent::onUpdate($currentTick);
		}else {
			if($this->moveTime <= 0 and $this->isTargetValid($this->target) and !$this->target instanceof Entity) {
				$x = $this->target->x - $this->x;
				$y = $this->target->y - $this->y;
				$z = $this->target->z - $this->z;
				$diff = abs($x) + abs($z);
				if($diff > 0) {
					$this->motion->x = $this->speed * 0.15 * ($x / $diff);
					$this->motion->z = $this->speed * 0.15 * ($z / $diff);
					$this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
				}
				$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
				if($this->distance($this->target) <= 0) {
					$this->target = null;
				}
			}elseif($this->target instanceof Entity and $this->isTargetValid($this->target)) {
				$this->moveTime = 0;
				if($this->distance($this->target) <= 16) {
					if($this->attackDelay > 30 and mt_rand(1, 32) < 4) {
						$this->attackDelay = 0;
						$force = 1.2;
						$yaw = $this->yaw + mt_rand(-220, 220) / 10;
						$pitch = $this->pitch + mt_rand(-120, 120) / 10;
						$nbt = Arrow::createBaseNBT(new Vector3($this->x + (-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5), $this->y + $this->eyeHeight, $this->z + (cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * 0.5)), new Vector3(), $yaw, $pitch);
						$arrow = Arrow::createEntity("Arrow", $this->level, $nbt, $this);
						$arrow->setPickupMode(Arrow::PICKUP_NONE);
						$ev = new EntityShootBowEvent($this, Item::get(Item::ARROW, 0, 1), $arrow, $force);
						$ev->call();
						$projectile = $ev->getProjectile();
						if($ev->isCancelled()) {
							$projectile->flagForDespawn();
						}elseif($projectile instanceof Projectile) {
							$launch = new ProjectileLaunchEvent($projectile);
							$launch->call();
							if($launch->isCancelled()) {
								$projectile->flagForDespawn();
							}else {
								$projectile->setMotion(new Vector3(-sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $ev->getForce(), -sin($pitch / 180 * M_PI) * $ev->getForce(), cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI) * $ev->getForce()));
								$projectile->spawnToAll();
								$this->level->addSound(new LaunchSound($this), $projectile->getViewers());
							}
						}
					}
					$target = $this->getSide(self::getRightSide($this->getDirection()));
					$x = $target->x - $this->x;
					$z = $target->z - $this->z;
					$diff = abs($x) + abs($z);
					if($diff > 0) {
						$this->motion->x = $this->speed * 0.15 * ($x / $diff);
						$this->motion->z = $this->speed * 0.15 * ($z / $diff);
					}
					$this->lookAt($this->target->add(0, $this->target->eyeHeight));
				}else {
					$x = $this->target->x - $this->x;
					$y = $this->target->y - $this->y;
					$z = $this->target->z - $this->z;
					$diff = abs($x) + abs($z);
					if($diff > 0) {
						$this->motion->x = $this->speed * 0.15 * ($x / $diff);
						$this->motion->z = $this->speed * 0.15 * ($z / $diff);
						$this->yaw = rad2deg(-atan2($x / $diff, $z / $diff)); 
					}
					$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
				}
			}elseif($this->moveTime <= 0) {
				$this->moveTime = 100;
			}
		}
		return parent::onUpdate($currentTick);
    }
    
    public function entityBaseTick(int $tickDiff = 1) : bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if($this->moveTime > 0) {
			$this->moveTime -= $tickDiff;
		}
		$time = $this->getLevel()->getTime() % Level::TIME_FULL;
		if(!$this->isOnFire() and ($time < Level::TIME_NIGHT or $time > Level::TIME_SUNRISE) and $this->level->getBlockSkyLightAt($this->getFloorX(), $this->getFloorY(), $this->getFloorZ()) >= 15) {
			$this->setOnFire(2);
		}
		if($this->isOnFire() and $this->level->getBlock($this, true, false) instanceof Water) { // TODO: check weather
			$this->extinguish();
		}
		$this->attackDelay += $tickDiff;
		return $hasUpdate;
    }
    
    public function getDrops() : array {
		$drops = parent::getDrops();
		if($this->dropAll) {
			$drops = array_merge($drops, $this->armorInventory->getContents());
		}elseif(mt_rand(1, 100) <= 8.5) {
			if(!empty($this->armorInventory->getContents())) {
				$drops[] = $this->armorInventory->getContents()[array_rand($this->armorInventory->getContents())];
			}
		}
		return $drops;
    }
    
    public function getXpDropAmount() : int {
		$exp = 5;
		foreach($this->getArmorInventory()->getContents() as $piece)
			$exp += mt_rand(1, 3);
		return $exp;
    }
    
    public function canBreathe() : bool{
		return true;
    }
    
    public function getName() : string {
		return "Skeleton";
    }
    
    public static function spawnMob(Position $spawnPos, ?CompoundTag $spawnData = null) : ?CreatureBase {
		//todo
    }
    
    public static function spawnFromSpawner(Position $spawnPos, ?CompoundTag $spawnData = null) : ?CreatureBase {
		//todo
    }
    
    public function onCollideWithEntity(Entity $entity) : void {
		//todo
    }
    
    public function checkItemValueToMainHand(Item $item) : bool {
		return $this->mainHand === null;
    }
    
    public function checkItemValueToOffHand(Item $item) : bool {
		return false;
    }
    
    public function equipRandomItems() : void {
        //todo
    }
    
    public function equipRandomArmour() : void {
		//todo
	}
}