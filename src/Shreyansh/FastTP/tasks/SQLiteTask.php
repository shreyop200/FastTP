<?php

declare(strict_types=1);

namespace Shreyansh\FastTP\tasks;

use pocketmine\scheduler\AsyncTask;
use SQLite3;

class SQLiteTask extends AsyncTask {

    private string $query;

    public function __construct(string $query) {
        $this->query = $query;
    }

    public function onRun(): void {
        $db = new SQLite3($this->getDataFolder() . "FastTP.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $db->exec($this->query);
        $db->close();
    }
}
