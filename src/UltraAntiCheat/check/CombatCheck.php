<?php

namespace UltraAntiCheat\checks;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use UltraAntiCheat\Main;

class CombatCheck implements Listener {

    private array $lastHits = [];
    private array $hitCounts = [];
    private array $suspiciousYaw = [];
    private array $lastYaw = [];

    public function onAttack(EntityDamageByEntityEvent $event): void {
        $damager = $event->getDamager();
        $entity = $event->getEntity();

        if (!$damager instanceof Player || !$entity instanceof Player) return;

        $name = $damager->getName();
        $now = microtime(true);

        if (isset($this->lastHits[$name])) {
            $interval = $now - $this->lastHits[$name];
            if ($interval < 0.18) {
                $this->hitCounts[$name] = ($this->hitCounts[$name] ?? 0) + 1;
                if ($this->hitCounts[$name] >= 3) {
                    Main::getInstance()->getViolationHandler()->flag($damager, "AutoClicker", 2);
                }
            } else {
                $this->hitCounts[$name] = 0;
            }
        }

        $this->lastHits[$name] = $now;

        $attackerPos = $damager->getPosition()->asVector3();
        $targetPos = $entity->getPosition()->asVector3();
        $distance = $attackerPos->distance($targetPos);

        if ($distance > 4.6) {
            Main::getInstance()->getViolationHandler()->flag($damager, "Reach", 2);
        }

        $dir = $damager->getDirectionVector();
        $hitVec = $targetPos->subtract($attackerPos)->normalize();
        $angle = acos($dir->dot($hitVec));

        if ($angle > 1.2) {
            Main::getInstance()->getViolationHandler()->flag($damager, "AimAssist", 1);
        }

        $yaw = $damager->getLocation()->getYaw();
        if (isset($this->lastYaw[$name])) {
            $diff = abs($yaw - $this->lastYaw[$name]);
            if ($diff === 0.0 || $diff === 360.0) {
                $this->suspiciousYaw[$name] = ($this->suspiciousYaw[$name] ?? 0) + 1;
                if ($this->suspiciousYaw[$name] >= 3) {
                    Main::getInstance()->getViolationHandler()->flag($damager, "KillAura (Static Yaw)", 2);
                    $this->suspiciousYaw[$name] = 0;
                }
            }
        }
        $this->lastYaw[$name] = $yaw;

        $victimEye = $entity->getEyeHeight();
        $attackerEye = $damager->getEyeHeight();
        if (abs($victimEye - $attackerEye) > 2.5) {
            Main::getInstance()->getViolationHandler()->flag($damager, "Hitbox/Hit Through Wall", 1);
        }

        if ($entity->isInvulnerable() && !$entity->isImmobile()) {
            Main::getInstance()->getViolationHandler()->flag($damager, "NoHitDelay Bypass", 1);
        }
    }
}