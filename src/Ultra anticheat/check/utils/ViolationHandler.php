<?php

namespace UltraAntiCheat\utils;

use pocketmine\player\Player;
use pocketmine\Server;
use UltraAntiCheat\Main;

class ViolationHandler {

    private array $violations = [];
    private array $lastFlagTime = [];
    private array $flagCooldown = [];

    public function flag(Player $player, string $reason, int $severity = 1): void {
        $name = $player->getName();

        if (!$player->isOnline()) return;

        $time = microtime(true);
        $cooldownKey = $name . ":" . $reason;

        if (isset($this->flagCooldown[$cooldownKey]) && ($time - $this->flagCooldown[$cooldownKey]) < 1) {
            return; // Cooldown for duplicate flags
        }

        $this->flagCooldown[$cooldownKey] = $time;
        $this->violations[$name][$reason] = ($this->violations[$name][$reason] ?? 0) + $severity;
        $total = $this->getTotalViolations($player);

        $this->notifyStaff($player, $reason, $severity, $total);
        $this->applyPunishment($player, $total);
    }

    public function getViolations(Player $player): array {
        return $this->violations[$player->getName()] ?? [];
    }

    public function getTotalViolations(Player $player): int {
        $total = 0;
        foreach ($this->getViolations($player) as $count) {
            $total += $count;
        }
        return $total;
    }

    public function clearViolations(Player $player): void {
        unset($this->violations[$player->getName()]);
    }

    private function notifyStaff(Player $player, string $reason, int $severity, int $total): void {
        $msg = "§c[AntiCheat] §f{$player->getName()} flagged for §e$reason §7(severity: $severity, total: $total)";
        foreach (Server::getInstance()->getOnlinePlayers() as $staff) {
            if ($staff->hasPermission("ultraanticheat.notify")) {
                $staff->sendMessage($msg);
            }
        }

        Main::getInstance()->getLogger()->info("[AntiCheat] {$player->getName()} flagged for $reason (severity: $severity, total: $total)");
    }

    private function applyPunishment(Player $player, int $total): void {
        if ($total >= 100) {
            $player->kick("Kicked by UltraAntiCheat: Too many violations", false);
            $this->logPunishment($player, "AutoKick - 100+ violations");
        } elseif ($total >= 50) {
            $player->sendTitle("§cWarning", "§eSuspicious activity detected!");
        }
    }

    public function logPunishment(Player $player, string $reason): void {
        $logPath = Main::getInstance()->getDataFolder() . "punishments.log";
        $entry = "[" . date("Y-m-d H:i:s") . "] {$player->getName()} punished: $reason" . PHP_EOL;
        file_put_contents($logPath, $entry, FILE_APPEND);
    }

    public function saveViolations(): void {
        $path = Main::getInstance()->getDataFolder() . "violations.json";
        file_put_contents($path, json_encode($this->violations));
    }

    public function loadViolations(): void {
        $path = Main::getInstance()->getDataFolder() . "violations.json";
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) {
                $this->violations = $data;
            }
        }
    }

    public function onDisable(): void {
        $this->saveViolations();
    }

    public function exportPlayerViolations(Player $player): string {
        $violations = $this->getViolations($player);
        $export = "Violations for {$player->getName()}:" . PHP_EOL;
        foreach ($violations as $type => $count) {
            $export .= "- $type: $count" . PHP_EOL;
        }
        return $export;
    }
}
