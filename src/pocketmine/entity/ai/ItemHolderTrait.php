<?php

namespace pocketmine\entity\ai;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;

trait ItemHolderTrait{

    protected $mainHand;
	protected $offHand;
    protected $dropAll = false;
    
    public function initEntity() : void {
		if($this->namedtag->hasTag("Mainhand", ListTag::class)) {
			$this->mainHand = Item::nbtDeserialize($this->namedtag->getListTag("Mainhand")->first());
		}
		if($this->namedtag->hasTag("Offhand", ListTag::class)) {
			$this->offHand = Item::nbtDeserialize($this->namedtag->getListTag("Offhand")->first());
		}
		if($this->namedtag->hasTag("Armor", ListTag::class)) {
			foreach($this->namedtag->getListTag("Armor")->getValue() as $tag)
				$items[] = Item::nbtDeserialize($tag);
			$this->getArmorInventory()->setContents($items);
		}
		parent::initEntity();
    }
    
    public function isDropAll() : bool {
		return $this->dropAll;
    }
    
    public function setDropAll(bool $dropAll = true) {
		$this->dropAll = $dropAll;
		return $this;
    }
    
    public function getDrops() : array {
		$drops = parent::getDrops();
		if($this->dropAll) {
			$drops[] = $this->mainHand ?? ItemFactory::get(Item::AIR);
			$drops[] = $this->offHand ?? ItemFactory::get(Item::AIR);
		}elseif(mt_rand(1, 1000) <= 85) {
			$drops[] = $this->mainHand ?? ItemFactory::get(Item::AIR);
		}
		return $drops;
    }
    
    public function getMainHand() : ?Item {
		return $this->mainHand;
    }
    
    public function setMainHandItem(?Item $mainHand) : ItemHolderTrait {
		$this->mainHand = $mainHand;
		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->item = $this->mainHand ?? ItemFactory::get(Item::AIR);
		$pk->inventorySlot = $pk->hotbarSlot = ContainerIds::INVENTORY;
		foreach($this->getViewers() as $player)
			$player->dataPacket($pk);
		return $this;
    }
    
    public function getOffHand() : ?Item {
		return $this->offHand;
    }
    
    public function setOffHandItem(?Item $offHand) : ItemHolderTrait {
		$this->offHand = $offHand;
		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->item = $this->offHand ?? ItemFactory::get(Item::AIR);
		$pk->inventorySlot = $pk->hotbarSlot = ContainerIds::OFFHAND;
		foreach($this->getViewers() as $player)
			$player->dataPacket($pk);
		return $this;
	}

	public function saveNBT() : void {
		parent::saveNBT();
		if(isset($this->mainHand)) {
			$this->namedtag->setTag(new ListTag("Mainhand", [$this->mainHand->nbtSerialize()], NBT::TAG_Compound));
		}
		if(isset($this->offHand)) {
			$this->namedtag->setTag(new ListTag("Offhand", [$this->offHand->nbtSerialize()], NBT::TAG_Compound));
		}
	}

	protected function sendSpawnPacket(Player $player) : void {
		parent::sendSpawnPacket($player);
		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->item = $this->mainHand ?? ItemFactory::get(Item::AIR);
		$pk->inventorySlot = $pk->hotbarSlot = ContainerIds::INVENTORY;
		$player->dataPacket($pk);
		$pk = new MobEquipmentPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->item = $this->offHand ?? ItemFactory::get(Item::AIR);
		$pk->inventorySlot = $pk->hotbarSlot = ContainerIds::OFFHAND;
		$player->dataPacket($pk);
	}
}