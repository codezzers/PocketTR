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

class StoneSlab2 extends StoneSlab{
	public const TYPE_RED_SANDSTONE = 0;
	public const TYPE_PURPUR = 1;
	public const TYPE_PRISMARINE = 2;
	public const TYPE_DARK_PRISMARINE = 3;
	public const TYPE_PRISMARINE_BRICKS = 4;
	public const TYPE_MOSSY_COBBLESTONE = 5;
	public const TYPE_SMOOTH_SANDSTONE = 6;
	public const TYPE_RED_NETHER_BRICK = 7;

	protected $id = self::STONE_SLAB2;

	public function getDoubleSlabId() : int{
		return self::DOUBLE_STONE_SLAB2;
	}

	public function getName() : string{
		static $names = [
			self::TYPE_RED_SANDSTONE => "Kırmızı Kumtaşı",
			self::TYPE_PURPUR => "Purpur",
			self::TYPE_PRISMARINE => "Prizmarin",
			self::TYPE_DARK_PRISMARINE => "Koyu Prizmarin",
			self::TYPE_PRISMARINE_BRICKS => "Prizmarin Tuğla",
			self::TYPE_MOSSY_COBBLESTONE => "Yosunlu Kırıktaş",
			self::TYPE_SMOOTH_SANDSTONE => "Pürüssüz Kumtaşı",
			self::TYPE_RED_NETHER_BRICK => "Kırmızı Nether Tuğlası"
		];

		return (($this->meta & 0x08) > 0 ? "Üst " : "") . ($names[$this->getVariant()] ?? "") . " Basamak";
	}
}
