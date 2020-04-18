<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol;

use pocketmine\utils\Binary;

use pocketmine\network\mcpe\NetworkSession;

class CommandBlockUpdatePacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::COMMAND_BLOCK_UPDATE_PACKET;

	/** @var bool */
	public $isBlock;

	/** @var int */
	public $x;
	/** @var int */
	public $y;
	/** @var int */
	public $z;
	/** @var int */
	public $commandBlockMode;
	/** @var bool */
	public $isRedstoneMode;
	/** @var bool */
	public $isConditional;

	/** @var int */
	public $minecartEid;

	/** @var string */
	public $command;
	/** @var string */
	public $lastOutput;
	/** @var string */
	public $name;
	/** @var bool */
	public $shouldTrackOutput;
	/** @var int */
	public $tickDelay;
	/** @var bool */
	public $executeOnFirstTick;

	protected function decodePayload(){
		$this->isBlock = (($this->get(1) !== "\x00"));

		if($this->isBlock){
			$this->getBlockPosition($this->x, $this->y, $this->z);
			$this->commandBlockMode = $this->getUnsignedVarInt();
			$this->isRedstoneMode = (($this->get(1) !== "\x00"));
			$this->isConditional = (($this->get(1) !== "\x00"));
		}else{
			//Minecart with command block
			$this->minecartEid = $this->getEntityRuntimeId();
		}

		$this->command = $this->getString();
		$this->lastOutput = $this->getString();
		$this->name = $this->getString();

		$this->shouldTrackOutput = (($this->get(1) !== "\x00"));
		$this->tickDelay = ((\unpack("V", $this->get(4))[1] << 32 >> 32));
		$this->executeOnFirstTick = (($this->get(1) !== "\x00"));
	}

	protected function encodePayload(){
		($this->buffer .= ($this->isBlock ? "\x01" : "\x00"));

		if($this->isBlock){
			$this->putBlockPosition($this->x, $this->y, $this->z);
			$this->putUnsignedVarInt($this->commandBlockMode);
			($this->buffer .= ($this->isRedstoneMode ? "\x01" : "\x00"));
			($this->buffer .= ($this->isConditional ? "\x01" : "\x00"));
		}else{
			$this->putEntityRuntimeId($this->minecartEid);
		}

		$this->putString($this->command);
		$this->putString($this->lastOutput);
		$this->putString($this->name);

		($this->buffer .= ($this->shouldTrackOutput ? "\x01" : "\x00"));
		($this->buffer .= (\pack("V", $this->tickDelay)));
		($this->buffer .= ($this->executeOnFirstTick ? "\x01" : "\x00"));
	}

	public function handle(NetworkSession $session) : bool{
		return $session->handleCommandBlockUpdate($this);
	}
}
