<?php

namespace UltraAntiCheat\checks;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use UltraAntiCheat\Main;

class ChatCheck implements Listener {

    private array $lastMessages = [];
    private array $messageTimes = [];
    private array $joinTimes = [];
    private array $capsCounter = [];
    private array $messageHistory = [];

    private array $bannedWords = [
        "hack", "hacker", "cheat", "killaura", "xray", "nuker", "fly", "speed", "regen", "scaffold", "autoclick", "aimbot",
        "client", "injek", "injector", "bypass", "antiknock", "autorespawn", "tp", "blink", "reach", "auto", "ff", "antikick"
    ];

    public function onChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $msg = $event->getMessage();
        $time = microtime(true);
        $msgLower = strtolower($msg);

        if (isset($this->messageTimes[$name])) {
            $delay = $time - $this->messageTimes[$name];
            if ($delay < 0.8) {
                Main::getInstance()->getViolationHandler()->flag($player, "Chat Spam", 1);
            }
        }

        if (isset($this->lastMessages[$name]) && $this->lastMessages[$name] === $msg) {
            Main::getInstance()->getViolationHandler()->flag($player, "Repeated Chat", 1);
        }

        if (!isset($this->messageHistory[$name])) $this->messageHistory[$name] = [];
        $this->messageHistory[$name][] = $msg;
        if (count($this->messageHistory[$name]) > 5) array_shift($this->messageHistory[$name]);

        $repeatCount = 0;
        foreach (array_count_values($this->messageHistory[$name]) as $count) {
            if ($count >= 3) $repeatCount++;
        }
        if ($repeatCount >= 1) {
            Main::getInstance()->getViolationHandler()->flag($player, "Chat Flood", 2);
        }

        $caps = preg_match_all("/[A-Z]/", $msg);
        $letters = preg_match_all("/[A-Za-z]/", $msg);
        if ($letters > 4 && $caps / $letters > 0.7) {
            $this->capsCounter[$name] = ($this->capsCounter[$name] ?? 0) + 1;
            if ($this->capsCounter[$name] >= 3) {
                Main::getInstance()->getViolationHandler()->flag($player, "Caps Spam", 1);
                $this->capsCounter[$name] = 0;
            }
        }

        foreach ($this->bannedWords as $word) {
            if (str_contains($msgLower, $word)) {
                Main::getInstance()->getViolationHandler()->flag($player, "Bypass Word: $word", 1);
            }
        }

        $this->messageTimes[$name] = $time;
        $this->lastMessages[$name] = $msg;
    }

    public function onCommand(PlayerCommandPreprocessEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $time = microtime(true);

        if (isset($this->messageTimes[$name]) && ($time - $this->messageTimes[$name]) < 0.5) {
            Main::getInstance()->getViolationHandler()->flag($player, "Command Spam", 1);
        }

        $this->messageTimes[$name] = $time;
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $time = microtime(true);

        $this->joinTimes[] = $time;
        $recent = array_filter($this->joinTimes, fn($t) => $time - $t < 3);
        if (count($recent) >= 5) {
            Main::getInstance()->getViolationHandler()->flag($player, "JoinBot/Flood", 2);
        }

        $this->joinTimes = array_filter($this->joinTimes, fn($t) => $time - $t < 5);
    }
}