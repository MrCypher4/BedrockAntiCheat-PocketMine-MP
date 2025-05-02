<?php

namespace UltraAntiCheat\checks;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use UltraAntiCheat\Main;

class MovementCheck implements Listener {

    private array $lastLocations = [];
    private array $lastAirTicks = [];
    private array $speedViolations = [];
    private array $flyTicks = [];
    private array $hoverTicks = [];
    private array $teleportCheck = [];

    public function onMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $from = $event->getFrom();
        $to = $event->getTo();
        $name = $player->getName();

        $dx = abs($to->getX() - $from->getX());
        $dy = abs($to->getY() - $from->getY());
        $dz = abs($to->getZ() - $from->getZ());

        $speed = sqrt($dx ** 2 + $dz ** 2);

        if ($speed > 0.7) {
            $this->speedViolations[$name] = ($this->speedViolations[$name] ?? 0) + 1;
            if ($this->speedViolations[$name] >= 3) {
                Main::getInstance()->getViolationHandler()->flag($player, "Speed", 2);
                $this->speedViolations[$name] = 0;
            }
        }

        if ($player->isOnGround()) {
            $this->lastAirTicks[$name] = 0;
        } else {
            $this->lastAirTicks[$name] = ($this->lastAirTicks[$name] ?? 0) + 1;
            if ($this->lastAirTicks[$name] > 20 && $dy < 0.01) {
                $this->hoverTicks[$name] = ($this->hoverTicks[$name] ?? 0) + 1;
                if ($this->hoverTicks[$name] >= 3) {
                    Main::getInstance()->getViolationHandler()->flag($player, "Hover/Fly", 2);
                    $this->hoverTicks[$name] = 0;
                }
            }
        }

        if ($dy > 1.2 && !$player->isOnGround()) {
            $this->flyTicks[$name] = ($this->flyTicks[$name] ?? 0) + 1;
            if ($this->flyTicks[$name] >= 3) {
                Main::getInstance()->getViolationHandler()->flag($player, "Fly", 2);
                $this->flyTicks[$name] = 0;
            }
        }

        if ($dy > 1.5) {
            Main::getInstance()->getViolationHandler()->flag($player, "HighJump", 1);
        }

        if ($dy < -3.5) {
            Main::getInstance()->getViolationHandler()->flag($player, "FastFall", 1);
        }

        if ($from->distance($to) > 6.0 && !$player->hasPermission("ultraanticheat.bypass")) {
            $this->teleportCheck[$name] = ($this->teleportCheck[$name] ?? 0) + 1;
            if ($this->teleportCheck[$name] >= 2) {
                Main::getInstance()->getViolationHandler()->flag($player, "Teleport/FastTP", 2);
                $this->teleportCheck[$name] = 0;
            }
        }

        if ($dy === 0 && !$player->isOnGround()) {
            Main::getInstance()->getViolationHandler()->flag($player, "NoGravity", 1);
        }

        if ($dx === 0 && $dz === 0 && !$player->isOnGround()) {
            Main::getInstance()->getViolationHandler()->flag($player, "AntiKB/Freeze", 1);
        }

        if ($dy < 0 && $to->getY() === $from->getY() && !$player->isOnGround()) {
            Main::getInstance()->getViolationHandler()->flag($player, "Glide", 1);
        }

        if ($player->getBoundingBox()->getHeight() < 1.5 && !$player->isSneaking()) {
            Main::getInstance()->getViolationHandler()->flag($player, "SneakGlitch", 1);
        }

        if ($to->getFloorY() > 320 || $to->getFloorY() < -64) {
            Main::getInstance()->getViolationHandler()->flag($player, "OutOfBounds", 1);
        }

        if ($player->getMotion()->y > 1.2) {
            Main::getInstance()->getViolationHandler()->flag($player, "JumpBoostHack", 1);
        }

        if ($player->getMotion()->x > 1.0 || $player->getMotion()->z > 1.0) {
            Main::getInstance()->getViolationHandler()->flag($player, "MotionHack", 1);
        }

        if ($player->isGliding() && $player->getMotion()->y >= 0.2) {
            Main::getInstance()->getViolationHandler()->flag($player, "ElytraBoost", 1);
        }

        if ($player->getMovementSpeed() > 0.13) {
            Main::getInstance()->getViolationHandler()->flag($player, "SpeedModifier", 1);
        }

        if (abs($dx) > 3.0 || abs($dz) > 3.0) {
            Main::getInstance()->getViolationHandler()->flag($player, "Lagback/StrafeSpeed", 1);
        }

        if ($to->distanceSquared($from) === 0 && $player->isSprinting()) {
            Main::getInstance()->getViolationHandler()->flag($player, "FakeSprint", 1);
        }

        if (!$player->isOnGround() && $player->getMotion()->y === 0.0) {
            Main::getInstance()->getViolationHandler()->flag($player, "HoverMotionZero", 1);
        }

        if ($player->isFlying() && !$player->hasPermission("ultraanticheat.bypass")) {
            Main::getInstance()->getViolationHandler()->flag($player, "FlyMode", 2);
        }

        $this->lastLocations[$name] = $to;
    }
}