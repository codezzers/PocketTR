<?php

declare(strict_types = 1);

namespace pocketmine\form;

use pocketmine\Player;

abstract class Form implements FormInterface
{

    protected $data = [];
    /** @var callable */
    private $callable;

    private $destroyForm;

    /**
     * @param int $id
     * @param callable $callable
     */
    public function __construct(?callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param Player $player
     */
    public function sendToPlayer(Player $player): void
    {
        $player->sendForm($this);
    }

    public function destroy(): void
    {
        $this->destroyForm = true;
    }

    public function setCallable(?callable $callable)
    {
        $this->callable = $callable;
    }

    public function getCallable(): ?callable
    {
        return $this->callable;
    }

    public function processData(&$data): void
    {
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function handleResponse(Player $player, $data): void
    {
        if ($this->destroyForm) return;
        $this->processData($data);
        $callable = $this->getCallable();
        if ($callable !== null) {
            $callable($player, $data);
        }
    }
}