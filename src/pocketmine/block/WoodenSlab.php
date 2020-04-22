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

namespace pocketmine\block;

class WoodenSlab extends Slab{

	protected $id = self::WOODEN_SLAB;

	public function getDoubleSlabId() : int{
		return self::DOUBLE_WOODEN_SLAB;
	}

	public function getHardness() : float{
		return 2;
	}

	public function getName() : string{
		static $names = [
			0 => "Meşe",
			1 => "Ladin",
			2 => "Huş",
			3 => "Orman",
			4 => "Akasya",
			5 => "Koyu Meşe"
		];
		return (($this->meta & 0x08) === 0x08 ? "Üst " : "") . ($names[$this->getVariant()] ?? "") . " Basamak";
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_AXE;
	}

	public function getFuelTime() : int{
		return 300;
	}

	public function getFlameEncouragement() : int{
		return 5;
	}

	public function getFlammability() : int{
		return 20;
	}
}