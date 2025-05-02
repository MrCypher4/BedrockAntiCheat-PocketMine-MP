<?php

namespace UltraAntiCheat\checks;

use pocketmine\scheduler\Task;
use UltraAntiCheat\Main;

class SystemScanTask extends Task {
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $this->plugin->getSystemCheck()->runFullSystemScan($player);
        }
    }
}