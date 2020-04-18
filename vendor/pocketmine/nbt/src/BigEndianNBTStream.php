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

namespace pocketmine\nbt;

use function array_values;
use function count;
use function pack;
use function unpack;

use pocketmine\utils\Binary;

class BigEndianNBTStream extends NBTStream{

	public function getShort() : int{
		return (\unpack("n", $this->get(2))[1]);
	}

	public function getSignedShort() : int{
		return (\unpack("n", $this->get(2))[1] << 48 >> 48);
	}

	public function putShort(int $v) : void{
		$this->buffer .= (\pack("n", $v));
	}

	public function getInt() : int{
		return (\unpack("N", $this->get(4))[1] << 32 >> 32);
	}

	public function putInt(int $v) : void{
		$this->buffer .= (\pack("N", $v));
	}

	public function getLong() : int{
		return Binary::readLong($this->get(8));
	}

	public function putLong(int $v) : void{
		$this->buffer .= (\pack("NN", $v >> 32, $v & 0xFFFFFFFF));
	}

	public function getFloat() : float{
		return (\unpack("G", $this->get(4))[1]);
	}

	public function putFloat(float $v) : void{
		$this->buffer .= (\pack("G", $v));
	}

	public function getDouble() : float{
		return (\unpack("E", $this->get(8))[1]);
	}

	public function putDouble(float $v) : void{
		$this->buffer .= (\pack("E", $v));
	}

	public function getIntArray() : array{
		$len = $this->getInt();
		return array_values(unpack("N*", $this->get($len * 4)));
	}

	public function putIntArray(array $array) : void{
		$this->putInt(count($array));
		($this->buffer .= pack("N*", ...$array));
	}
}
