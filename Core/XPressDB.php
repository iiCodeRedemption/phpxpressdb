<?php

require_once __DIR__  . '/Models/Table.php';

class XPressDB {
    private array $config;
    private mysqli $connection;
    private bool $debugMode = false;
    private array $queryLog = [];

    public function __construct(array $config = []) {
        $this->config = $config;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->connection = new mysqli(
            $this->config['host'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database']
        );

        if ($this->connection->connect_error) {
            die('Connection failed: ' . $this->connection->connect_error);
        }
    }

    public function rawQuery(string $query): array {
        $result = $this->connection->query($query);
        $this->logQuery($query);

        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function table(string $table): Table {
        return new Table($table, $this->connection, $this);
    }

    /**
     * @throws Exception
     */
    public function beginTransaction(): void {
        if (!$this->connection->begin_transaction()) {
            throw new Exception("Failed to begin transaction: " . $this->connection->error);
        }
    }

    public function commit(): void {
        $this->connection->commit();
    }

    public function rollback(): void {
        $this->connection->rollback();
    }

    public function debug(bool $debug): void {
        $this->debugMode = $debug;
    }

    public function logQuery(string $query): void {
        if (!$this->debugMode) {
            return;
        }

        $this->queryLog[] = $query;

        echo '<pre><b>DEBUG:</b><br>';
        print_r($query);
        echo '</pre>';
    }

    public function getQueryLog(): array {
        return $this->queryLog;
    }

    public function close(): bool {
        return $this->connection->close();
    }
}
