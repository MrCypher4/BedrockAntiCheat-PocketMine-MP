<?php

namespace UltraAntiCheat\checks;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;
use UltraAntiCheat\Main;

class BlockCheck implements Listener {

    private array $lastPlaceTime = [];
    private array $lastBreakTime = [];
    private array $blockDigStats = [];

    private array $valuableBlocks = [
        "diamond_ore", "gold_ore", "ancient_debris", "iron_ore", "emerald_ore", "nether_quartz_ore"
    ];

    public function onPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $name = $player->getName();

        if (!$player->isSneaking() && $player->getDirectionVector()->y == 0 && $block->getPosition()->y < $player->getLocation()->y) {
            Main::getInstance()->getViolationHandler()->flag($player, "Scaffold", 1);
        }

        if ($block->getPosition()->y > $player->getLocation()->y + 1.5) {
            Main::getInstance()->getViolationHandler()->flag($player, "Tower", 1);
        }

        $now = microtime(true);
        if (isset($this->lastPlaceTime[$name]) && ($now - $this->lastPlaceTime[$name]) < 0.1) {
            Main::getInstance()->getViolationHandler()->flag($player, "FastPlace", 1);
        }
        $this->lastPlaceTime[$name] = $now;
    }

    public function onBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $name = $player->getName();

        $now = microtime(true);
        if (isset($this->lastBreakTime[$name]) && ($now - $this->lastBreakTime[$name]) < 0.1) {
            Main::getInstance()->getViolationHandler()->flag($player, "FastBreak", 1);
        }
        $this->lastBreakTime[$name] = $now;

        $blockId = strtolower($block->getName());
        if (in_array($blockId, $this->valuableBlocks)) {
            $this->blockDigStats[$name][$blockId] = ($this->blockDigStats[$name][$blockId] ?? 0) + 1;
            $valuableCount = array_sum($this->blockDigStats[$name]);
            if ($valuableCount >= 5 && count($this->blockDigStats[$name]) <= 2) {
                Main::getInstance()->getViolationHandler()->flag($player, "Xray", 2);
            }
        }
    }
}