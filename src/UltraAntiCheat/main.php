private SystemCheck $systemCheck;

public function onEnable(): void {
    $this->systemCheck = new SystemCheck();
    $this->getServer()->getPluginManager()->registerEvents($this->systemCheck, $this);
    $this->getScheduler()->scheduleRepeatingTask(new \UltraAntiCheat\checks\SystemScanTask($this), 20 * 5);
}

public function getSystemCheck(): SystemCheck {
    return $this->systemCheck;
}
