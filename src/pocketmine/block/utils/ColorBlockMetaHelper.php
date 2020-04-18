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

namespace pocketmine\block\utils;

class ColorBlockMetaHelper{

	public static function getColorFromMeta(int $meta) : string{
		static $names = [
			0 => "Beyaz",
			1 => "Turuncu",
			2 => "Eflatun",
			3 => "Açık Mavi",
			4 => "Sarı",
			5 => "Açık Yeşil",
			6 => "Pembe",
			7 => "Gri",
			8 => "Koyu Gri",
			9 => "Cam Göbeği",
			10 => "Mor",
			11 => "Mavi",
			12 => "Kahverengi",
			13 => "Yeşil",
			14 => "Kırmızı",
			15 => "Siyah"
		];

		return $names[$meta] ?? "Bilinmeyen";
	}
}
