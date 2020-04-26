<?php

namespace pocketmine\scoreboard;

use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Server;
use pocketmine\Player;

class Scoreboard{

    public static $scoreboards = [];

    public static function addScoreboard(Player $player, string $objectiveName, string $displayName){
        if(!$player instanceof Player){return false;}
        if(isset(Scoreboard::$scoreboards[$player->getName()])){
            Scoreboard::removeScoreboard($player);
        }
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $player->sendDataPacket($pk);
		Scoreboard::$scoreboards[$player->getName()] = $objectiveName;
    }

    public static function removeScoreboard(Player $player){
        if(!$player instanceof Player){return false;}
        $objectiveName = Scoreboard::getObjectiveName($player);
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		$player->sendDataPacket($pk);
		unset(Scoreboard::$scoreboards[$player->getName()]);
    }

    public static function setLine(Player $player, int $score, string $message){
        if(!$player instanceof Player){return false;}
        if($score > 15 || $score < 1){
            return;
        }
        $objectiveName = Scoreboard::getObjectiveName($player);
		$entry = new ScorePacketEntry();
		$entry->objectiveName = $objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $message;
		$entry->score = $score;
		$entry->scoreboardId = $score;
		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->sendDataPacket($pk);
    }

    public static function getObjectiveName(Player $player): ?string {
        if(!$player instanceof Player){return false;}
		return isset(Scoreboard::$scoreboards[$player->getName()]) ? Scoreboard::$scoreboards[$player->getName()] : null;
    }
}