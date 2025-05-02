<?php

namespace UltraAntiCheat\checks;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\player\Player;
use UltraAntiCheat\Main;

class SystemCheck implements Listener {

    private array $loginTimes = [];
    private array $interactCounts = [];
    private array $packetCounts = [];
    private array $lastCommandTimes = [];
    private array $itemUseTimestamps = [];

    public function onLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        $ip = $player->getNetworkSession()->getIp();
        if (substr_count($ip, ".") !== 3) {
            Main::getInstance()->getViolationHandler()->flag($player, "Invalid IP Format", 2);
        }
        $this->loginTimes[$player->getName()] = microtime(true);
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $name = $event->getPlayer()->getName();
        unset($this->loginTimes[$name], $this->interactCounts[$name], $this->packetCounts[$name], $this->itemUseTimestamps[$name]);
    }

    public function onItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $time = microtime(true);

        if (isset($this->itemUseTimestamps[$name])) {
            $delay = $time - $this->itemUseTimestamps[$name];
            if ($delay < 0.2) {
                Main::getInstance()->getViolationHandler()->flag($player, "FastUse/AutoUse", 1);
            }
        }

        $this->itemUseTimestamps[$name] = $time;
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $this->interactCounts[$name] = ($this->interactCounts[$name] ?? 0) + 1;
        if ($this->interactCounts[$name] >= 15) {
            Main::getInstance()->getViolationHandler()->flag($player, "FastInteract/AutoClick", 1);
            $this->interactCounts[$name] = 0;
        }
    }

    public function onCommand(PlayerCommandPreprocessEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $now = microtime(true);

        if (isset($this->lastCommandTimes[$name])) {
            $interval = $now - $this->lastCommandTimes[$name];
            if ($interval < 0.5) {
                Main::getInstance()->getViolationHandler()->flag($player, "FastCommand/Spam", 1);
            }
        }

        $this->lastCommandTimes[$name] = $now;
    }

    public function onPacketReceive(DataPacketReceiveEvent $event): void {
        $player = $event->getOrigin()->getPlayer();
        if (!$player instanceof Player) return;

        $name = $player->getName();
        $pkName = $event->getPacket()::class;

        $this->packetCounts[$name][$pkName] = ($this->packetCounts[$name][$pkName] ?? 0) + 1;

        if ($this->packetCounts[$name][$pkName] > 50) {
            Main::getInstance()->getViolationHandler()->flag($player, "PacketFlood: $pkName", 1);
            $this->packetCounts[$name][$pkName] = 0;
        }
    }

    public function monitorAbnormalPackets(Player $player): void {
        $name = $player->getName();
        $counts = $this->packetCounts[$name] ?? [];

        foreach ($counts as $packetName => $count) {
            if ($count > 80) {
                Main::getInstance()->getViolationHandler()->flag($player, "Suspicious Packet Activity: {$packetName}", 2);
                $this->packetCounts[$name][$packetName] = 0;
            }
        }
    }

    public function detectSessionTime(Player $player): void {
        $name = $player->getName();
        $loginTime = $this->loginTimes[$name] ?? null;
        if ($loginTime !== null) {
            $sessionLength = microtime(true) - $loginTime;
            if ($sessionLength < 1) {
                Main::getInstance()->getViolationHandler()->flag($player, "SessionTooShort (Bot Join)", 2);
            }
        }
    }

    public function checkItemSpam(Player $player): void {
        $name = $player->getName();
        $now = microtime(true);

        if (isset($this->itemUseTimestamps[$name])) {
            $delay = $now - $this->itemUseTimestamps[$name];
            if ($delay < 0.15) {
                Main::getInstance()->getViolationHandler()->flag($player, "ItemSpam", 1);
            }
        }

        $this->itemUseTimestamps[$name] = $now;
    }

    public function runFullSystemScan(Player $player): void {
        $this->monitorAbnormalPackets($player);
        $this->detectSessionTime($player);
        $this->checkItemSpam($player);

        $inv = $player->getInventory();
        $slot = $inv->getHeldItemIndex();
        if ($slot > 8 || $slot < 0) {
            Main::getInstance()->getViolationHandler()->flag($player, "Invalid Hotbar Slot", 1);
        }

        $network = $player->getNetworkSession();
        if ($network->getPing() < 0 || $network->getPing() > 1000) {
            Main::getInstance()->getViolationHandler()->flag($player, "Invalid Ping", 1);
        }

        $uuid = $player->getUniqueId()->toString();
        if (strlen($uuid) !== 36) {
            Main::getInstance()->getViolationHandler()->flag($player, "Invalid UUID Format", 1);
        }

        if (strlen($player->getName()) > 20 || preg_match("/[^a-zA-Z0-9_]/", $player->getName())) {
            Main::getInstance()->getViolationHandler()->flag($player, "Invalid Username", 1);
        }
    }

    public function runScheduledChecks(): void {
        foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
            $this->runFullSystemScan($player);
        }
    }
}