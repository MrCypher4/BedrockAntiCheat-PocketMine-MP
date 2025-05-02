# UltraAntiCheat By VatieraSynth

**UltraAntiCheat** is a comprehensive anti-cheat plugin for Minecraft: Bedrock Edition (PocketMine-MP) designed to detect and block various types of cheats and exploits. It provides advanced protection against movement hacks, combat cheats, block exploits, chat manipulation, and system-level abuse. With real-time violation reporting and automatic handling, this plugin is ideal for competitive servers and public communities.

---

## Key Features

- **Movement Checks**  
  Detects fly, speed, glide, phase, noclip, jesus, bunny hop, spider climb, and other abnormal movement behaviors in real-time.

- **Combat Checks**  
  Detects kill aura, auto clicker, reach hacks, fast attack, criticals, auto aim, and other PvP-related exploits.

- **Block Checks**  
  Prevents scaffold hacks, tower abuse, nuker/farmland destroyers, fast break, instabreak, X-ray behavior, and ghost block placement.

- **System Checks**  
  Detects bot joins, ping spoofing, packet floods, command spam, command spoofing, item usage spam, invalid IPs, malformed UUIDs, and abnormal session durations.

- **Chat Checks**  
  Filters prohibited words, fast message spam, Unicode bypasses, command injections, and fake message exploits.

- **Violation Handling**  
  Each cheat detection increases a player's violation score. Upon reaching a certain threshold, players may be warned, kicked, or further punished automatically.

- **Scheduled Scanning**  
  Periodically scans all online players using low-overhead background tasks to ensure ongoing cheat prevention.

- **Violation Logging**  
  Logs all violations and actions taken in JSON and log files for audit, analysis, and evidence.

- **Staff Notifications**  
  Server operators with permissions receive detailed alerts when a player is flagged for cheating, including cheat type, severity, and violation score.

---

## Anti-Cheat Modules

UltraAntiCheat is built with modular architecture:

- `MovementCheck.php`: Handles illegal motion detections.
- `CombatCheck.php`: Focuses on attack pattern analysis and combat irregularities.
- `BlockCheck.php`: Prevents unnatural block placement and destruction patterns.
- `SystemCheck.php`: Monitors server-level data abuse and abnormal connections.
- `ChatCheck.php`: Secures the chat environment from exploits and inappropriate use.
- `ViolationHandler.php`: Manages score system, punishment logic, and reports.

---

## File Structure

UltraAntiCheat/ ├── plugin.yml ├── src/ │   └── UltraAntiCheat/ │       ├── Main.php │       ├── checks/ │       │   ├── MovementCheck.php │       │   ├── CombatCheck.php │       │   ├── BlockCheck.php │       │   ├── SystemCheck.php │       │   └── ChatCheck.php │       └── utils/ │           └── ViolationHandler.php

---

## Logging

- `violations.json`: Tracks all players' violation scores persistently.
- `punishments.log`: Logs actions like warnings, kicks, and flags with timestamps.

---

## Future Plans

- Web-based GUI dashboard for monitoring.
- Auto-ban integration with customizable thresholds.
- Expanded AI-based detection methods.
- Support for more plugins and third-party APIs.

---

## License

UltraAntiCheat is open-source under the **MIT License**. You are free to use, modify, and distribute this plugin with proper attribution.

---

## Contact & Support

- Discord: `yourdiscord#1234`
- Telegram: `@yourtelegram`
- Email: `you@example.com`

Feel free to report issues, request features, or contribute via pull requests.
