<?php

namespace pocketmine\inventory;

use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;
use pocketmine\Server;

class LeftInventory extends BaseInventory{

    protected $holder;

    public function __construct(Player $holder){
        parent::__construct([], 1);
        $this->holder = $holder;
    }

    public function getPlayer(): Player{
        return $this->holder;
    }

    public function setSize(int $size): void{
        throw new \BadMethodCallException("Cannot call size on left inventory");
    }

    public function getName(): string{
        return "LeftInventory";
    }

    public function getDefaultSize(): int{
        return 1;
    }

    public function setItemInLeftHand(Item $item): void{
        $this->setItem(0, $item);

        $pk = new MobEquipmentPacket();
        $pk->windowId = ContainerIds::OFFHAND;
        $pk->hotbarSlot = $pk->inventorySlot = 0;
        $pk->item = $this->getItem(0);
        $pk->entityRuntimeId = $this->holder->getId();
        foreach(Server::getInstance()->getOnlinePlayers() as $pl) $pl->dataPacket($pk);

        $pk = new InventorySlotPacket();
		$pk->windowId = ContainerIds::OFFHAND;
		$pk->inventorySlot = 0;
		$pk->item = $this->getItemInLeftHand();
		foreach(Server::getInstance()->getOnlinePlayers() as $pl) $pl->dataPacket($pk);
    }

    public function getItemInLeftHand(): Item{
        return $this->getItem(0);
    }
}