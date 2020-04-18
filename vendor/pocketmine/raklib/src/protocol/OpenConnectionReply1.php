<?php

/*
 * RakLib network library
 *
 *
 * This project is not affiliated with Jenkins Software LLC nor RakNet.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace raklib\protocol;

use pocketmine\utils\Binary;

class OpenConnectionReply1 extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_OPEN_CONNECTION_REPLY_1;

	/** @var int */
	public $serverID;
	/** @var bool */
	public $serverSecurity = false;
	/** @var int */
	public $mtuSize;

	protected function encodePayload() : void{
		$this->writeMagic();
		($this->buffer .= (\pack("NN", $this->serverID >> 32, $this->serverID & 0xFFFFFFFF)));
		($this->buffer .= \chr($this->serverSecurity ? 1 : 0));
		($this->buffer .= (\pack("n", $this->mtuSize)));
	}

	protected function decodePayload() : void{
		$this->readMagic();
		$this->serverID = (Binary::readLong($this->get(8)));
		$this->serverSecurity = (\ord($this->get(1))) !== 0;
		$this->mtuSize = ((\unpack("n", $this->get(2))[1]));
	}
}
