<?php

declare(strict_types=1);

namespace Shreyansh\FastTP;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use Shreyansh\FastTP\commands\TeleportAcceptCommand;
use Shreyansh\FastTP\commands\TeleportDenyCommand;
use Shreyansh\FastTP\commands\TeleportRequestCommand;
use Shreyansh\FastTP\managers\DataManager;
use Shreyansh\FastTP\managers\TeleportRequestManager;
use SQLite3;
use SQLite3Result;
use SQLite3Stmt;
use Throwable;

class FastTP extends PluginBase {

    private static FastTP $instance;
    /** @var SQLite3Stmt */
    public SQLite3Stmt $prepare;
    /** @var SQLite3Result */
    public SQLite3Result $result;
    /** @var SQLite3 */
    public SQLite3 $db2;
    /** @var TeleportRequestManager */
    private TeleportRequestManager $teleportRequestManager;

    /**
     * FastTP
     */
    public static function getInstance(): FastTP {
        return self::$instance;
    }



    /**
     * @return TeleportRequestManager
     */
    public function getTeleportRequestManager(): TeleportRequestManager {
        return $this->teleportRequestManager;
    }

    public function onEnable(): void {
        self::$instance = $this;

        $this->teleportRequestManager = new TeleportRequestManager();

        $authors = $this->getDescription()->getAuthors();
        $version = $this->getDescription()->getVersion();
        $configVer = $this->getConfig()->get("version");

        if(array_diff($authors, ["Shreyansh"])) {
            $this->getLogger()->error("Plugin author does not match 'Shreyansh'");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        if(version_compare($version, $configVer, "<>")) {
            $this->getLogger()->warning("Plugin version does not match config version. Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }


        try {
            if(!file_exists($this->getDataFolder() . "FastTP.db")) {
                $this->db2 = new SQLite3($this->getDataFolder() . "FastTP.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            } else {
                $this->db2 = new SQLite3($this->getDataFolder() . "FastTP.db", SQLITE3_OPEN_READWRITE);
            }
        } catch (Throwable $error) {
            $this->getLogger()->critical($error->getMessage());
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        DataManager::executeQuery("PRAGMA journal_mode=WAL");
        DataManager::init();
        $this->registerCommands();
    }

    private function registerCommands(): void {
        $commandMap = Server::getInstance()->getCommandMap();
        $commandMap->register("FastTP", new TeleportRequestCommand());
        $commandMap->register("FastTP", new TeleportAcceptCommand());
        $commandMap->register("FastTP", new TeleportDenyCommand());
    }

    public function onDisable(): void {
        if (isset($this->prepare)) {
            $this->prepare->close();
        }
}

}
