<?php

namespace pocketmine\entity\ai;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;

trait LinkableTrait{

    public $link;

    public function getLink() : ?Linkable {
		return $this->link;
    }
    
    public function setLink(?Linkable $entity) : Linkable {
		$this->link = $entity;
		$entity->setLink($this);
		$viewers = $this->getViewers();
		if($entity !== null) {
			$link = new EntityLink();
			$link->fromEntityUniqueId = $this->getId();
			$link->type = EntityLink::TYPE_RIDER;
			$link->toEntityUniqueId = $entity->getId();
			$link->immediate = true;
			if($entity instanceof Player) {
				$pk = new SetEntityLinkPacket();
				$pk->link = $link;
				$entity->dataPacket($pk);
				$link_2 = new EntityLink();
				$link_2->fromEntityUniqueId = $entity->getId();
				$link_2->type = EntityLink::TYPE_RIDER;
				$link_2->toEntityUniqueId = 0;
				$link_2->immediate = true;
				$pk = new SetEntityLinkPacket();
				$pk->link = $link_2;
				$entity->dataPacket($pk);
				unset($viewers[$entity->getLoaderId()]);
			}
		}else{
			$link = new EntityLink();
			$link->fromEntityUniqueId = $this->getId();
			$link->type = EntityLink::TYPE_RIDER;
			$link->toEntityUniqueId = $entity->getId();
			$link->immediate = true;
			if($entity instanceof Player) {
				$pk = new SetEntityLinkPacket();
				$pk->link = $link;
				$entity->dataPacket($pk);
				$link_2 = new EntityLink();
				$link_2->fromEntityUniqueId = $entity->getId();
				$link_2->type = EntityLink::TYPE_RIDER;
				$link_2->toEntityUniqueId = 0;
				$link_2->immediate = true;
				$pk = new SetEntityLinkPacket();
				$pk->link = $link_2;
				$entity->dataPacket($pk);
				unset($viewers[$entity->getLoaderId()]);
			}
		}
		return $this;
	}

	public function unlink() : bool {
		$this->link->setLink(null);
		$this->link = null;

		$viewers = $this->getViewers();
		$entity = $this->link;
		$link = new EntityLink();
		$link->fromEntityUniqueId = $this->getId();
		$link->type = EntityLink::TYPE_RIDER;
		$link->toEntityUniqueId = $entity->getId();
		$link->immediate = true;
		if($entity instanceof Player) {
			$pk = new SetEntityLinkPacket();
			$pk->link = $link;
			$entity->dataPacket($pk);
			$link_2 = new EntityLink();
			$link_2->fromEntityUniqueId = $entity->getId();
			$link_2->type = EntityLink::TYPE_RIDER;
			$link_2->toEntityUniqueId = 0;
			$link_2->immediate = true;
			$pk = new SetEntityLinkPacket();
			$pk->link = $link_2;
			$entity->dataPacket($pk);
			unset($viewers[$entity->getLoaderId()]);
		}
		return true;
	}
}