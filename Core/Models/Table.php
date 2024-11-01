<?php

require_once __DIR__ . '/../Utils.php';
require_once __DIR__ . '/../Enums/EWhereCond.php';
require_once __DIR__ . '/../Enums/EOrderBy.php';

class Table {
    private string $name;
    private mysqli $connection;
    private XPressDB $db;
    private ?array $selectedColumns = null;
    private ?array $queryConditions = null;

    public function __construct(string $name, mysqli $connection, XPressDB $db) {
        $this->name = $name;
        $this->connection = $connection;
        $this->db = $db;
    }

    public function select(array $columns = ['*']): Table {
        $this->selectedColumns = $columns;
        return $this;
    }

    public function find(mixed $id): ?array {
        $primaryColumn = $this->getPrimaryColumn();

        if (is_null($primaryColumn)) {
            return null;
        }

        $availableColumns = $this->getAvailableColumns();

        $query = "SELECT $availableColumns FROM $this->name WHERE $primaryColumn = $id";
        if (!is_null($this->queryConditions)) {
            $query .= $this->getQueryConditions();
        }

        $this->db->logQuery($query);

        $result = $this->connection->query($query);
        return $result->fetch_assoc();
    }

    public function findMany() {
        $availableColumns = $this->getAvailableColumns();

        $query = "SELECT $availableColumns FROM $this->name";
        if (!is_null($this->queryConditions)) {
            $query .= $this->getQueryConditions();
        }

        $this->db->logQuery($query);

        $result = $this->connection->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function where(string $column, EWhereCond $cond, mixed $value): Table {
        $typedValue = Utils::getTypedValue($value);

        $this->queryConditions[] = ['where', $this->getWhereCondition($column, $cond, $typedValue)];
        return $this;
    }

    public function orderBy(string $column, EOrderBy $order): Table {
        $this->queryConditions[] = ['order', "ORDER BY $column $order->value"];
        return $this;
    }

    public function groupBy(string $column): Table {
        $this->queryConditions[] = ['group', "GROUP BY $column"];
        return $this;
    }

    public function limit(int $limit = 1): Table {
        $this->queryConditions[] = ['limit', "LIMIT $limit"];
        return $this;
    }

    public function insert(array $data): string|int {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(fn($value) => Utils::getTypedValue($value), $data));

        $query = "INSERT INTO $this->name ($columns) VALUES ($values)";

        $this->db->logQuery($query);

        $this->connection->query($query);
        return $this->connection->insert_id;
    }

    public function update(array $data): int {
        $setValues = array_map(fn($key, $value) => "$key = " . Utils::getTypedValue($value), array_keys($data), $data);
        $setValues = implode(', ', $setValues);

        $query = "UPDATE $this->name SET $setValues";
        if (!is_null($this->queryConditions)) {
            $query .= $this->getQueryConditions();
        }

        $this->db->logQuery($query);

        $this->connection->query($query);
        return $this->connection->affected_rows;
    }

    public function delete(): int {
        $query = "DELETE FROM $this->name";
        if (!is_null($this->queryConditions)) {
            $query .= $this->getQueryConditions();
        }

        $this->db->logQuery($query);

        $this->connection->query($query);
        return $this->connection->affected_rows;
    }

    public function max(string $column): float {
        $query = "SELECT MAX($column) FROM $this->name";
        if (!is_null($this->queryConditions)) {
            $query .= $this->getQueryConditions();
        }

        $this->db->logQuery($query);

        $result = $this->connection->query($query);
        return $result->fetch_row()[0] ?? 0;
    }

    public function avg(string $column): float {
        $query = "SELECT AVG($column) FROM $this->name";
        if (!is_null($this->queryConditions)) {
            $query .= $this->getQueryConditions();
        }

        $this->db->logQuery($query);

        $result = $this->connection->query($query);
        return $result->fetch_row()[0] ?? 0;
    }

    public function sum(string $column): float {
        $query = "SELECT SUM($column) FROM $this->name";
        if (!is_null($this->queryConditions)) {
            $query .= $this->getQueryConditions();
        }

        $this->db->logQuery($query);

        $result = $this->connection->query($query);
        return $result->fetch_row()[0] ?? 0;
    }

    public function join(string $table, string $column1, string $column2): Table {
        $this->queryConditions[] = ['join', "JOIN $table ON $column1 = $column2"];
        return $this;
    }

    public function count(): int {
        $availableColumns = $this->getAvailableColumns();

        $query = "SELECT COUNT($availableColumns) FROM $this->name";
        if (!is_null($this->queryConditions)) {
            $query .= $this->getQueryConditions();
        }

        $this->db->logQuery($query);

        $result = $this->connection->query($query);
        return $result->fetch_row()[0];
    }

    private function getWhereCondition(string $column, EWhereCond $cond, mixed $value): string {
        $condition = match ($cond) {
            EWhereCond::EQ => "$column = $value",
            EWhereCond::NEQ => "$column != $value",
            EWhereCond::GT => "$column > $value",
            EWhereCond::GTE => "$column >= $value",
            EWhereCond::LT => "$column < $value",
            EWhereCond::LTE => "$column <= $value",
            default => "$column = $value",
        };

        return $condition;
    }

    private function getQueryConditions(): string {
        $whereConditions = array_filter($this->queryConditions, fn($condition) => $condition[0] === 'where');
        $otherConditions = array_filter($this->queryConditions, fn($condition) => $condition[0] !== 'where');

        $whereConditions = array_map(fn($condition) => $condition[1], $whereConditions);
        $otherConditions = array_map(fn($condition) => $condition[1], $otherConditions);

        $whereConditionsSql = implode(' AND ', $whereConditions);
        if (!empty($whereConditionsSql)) {
            $whereConditionsSql = ' WHERE ' . $whereConditionsSql;
        }

        $otherConditionsSql = implode(' ', $otherConditions);
        return $whereConditionsSql . ' ' . $otherConditionsSql;
    }

    private function getAvailableColumns(): string {
        if ($this->selectedColumns === null) {
            return '*';
        }

        return implode(', ', $this->selectedColumns);
    }

    private function getPrimaryColumn(): string {
        $query = "SHOW KEYS FROM $this->name WHERE Key_name = 'PRIMARY'";

        $result = $this->connection->query($query);
        $row = $result->fetch_assoc();

        return $row['Column_name'];
    }
}