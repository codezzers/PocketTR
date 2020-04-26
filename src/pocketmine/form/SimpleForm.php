<?php

/**
 * 
 * PocketTR team
 * 
 */

declare(strict_types = 1);

namespace pocketmine\form;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;

class SimpleForm extends API {

	const IMAGE_TYPE_PATH = 0;
	const IMAGE_TYPE_URL = 1;

	public $id;
	private $data = [];
	private $content = "";
	public $playerName;

	public function __construct(int $id, ?callable $callable) {
		parent::__construct($id, $callable);
		$this->data["type"] = "form";
		$this->data["title"] = "";
		$this->data["content"] = $this->content;
	}

	public function getId() : int {
		return $this->id;
	}

	public function sendToPlayer(Player $player) : void {
		$pk = new ModalFormRequestPacket();
		$pk->formId = $this->id;
		$pk->formData = json_encode($this->data);
		$player->dataPacket($pk);
		$this->playerName = $player->getName();
	}

	public function setTitle(string $title) : void {
		$this->data["title"] = $title;
	}

	public function getTitle() : string {
		return $this->data["title"];
	}

	public function getContent() : string {
		return $this->data["content"];
	}

	public function setContent(string $content) : void {
		$this->data["content"] = $content;
	}

	public function addButton(string $text, int $imageType = -1, string $imagePath = "") : void {
		$content = ["text" => $text];
		if($imageType !== -1){
			$content["image"]["type"] = $imageType === 0 ? "path" : "url";
			$content["image"]["data"] = $imagePath;
		}
		$this->data["buttons"][] = $content;
	}

}