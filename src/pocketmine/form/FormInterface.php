<?php

declare(strict_types=1);

namespace pocketmine\form;

use pocketmine\Player;

interface FormInterface extends \JsonSerializable
{

    /**
     * @param mixed $data
     * @throws FormValidationException
     */
    public function handleResponse(Player $player, $data): void;
}