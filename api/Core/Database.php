<?php
/**
 * It is responsible for managing the connection to the database.
 *
 * @category Core
 * @copyright 2024
 */

namespace Bifrost\Core;

use PDO;
use Bifrost\Core\Settings;

/**
 * It is responsible for managing the connection to the database.
 *
 * @package Bifrost\Core
 */
class Database
{
    /** It is responsible for storaging the connection to the database. */
    private static PDO $conn;
    /** It is responsible for storaging the system settings. */
    private static Settings $settings;

    /**
     * It is responsible for initializing the connection to the database.
     *
     * @uses Settings
     * @uses Database::conn()
     * @return void
     */
    public function __construct()
    {
        if (empty(self::$settings)) {
            self::$settings = new Settings();
        }

        if (empty(self::$conn)) {
            self::$conn = $this->conn();
        }
    }

    /**
     * It is responsible for returning the connection to the database.
     *
     * @uses Settings
     * @uses PDO
     * @uses Database::$conn
     * @return PDO
     */
    private static function conn(): PDO
    {
        $dataConn = self::$settings->database;

        switch ($dataConn["driver"]) {
            case "sqlite":
                return new PDO("sqlite:" . $dataConn["database"]);
            case "mysql":
            default:
                return new PDO(
                    "mysql:host={$dataConn["host"]}:{$dataConn["port"]};dbname={$dataConn["database"]};charset=utf8",
                    $dataConn["username"],
                    $dataConn["password"]
                );
        }
    }

    /**
     * It is responsible for returning the WHERE clause of the SQL query.
     *
     * @param array $conditions Array of conditions where the key is the field name and the value is the field value.
     * @return string
     *
     * @example
     * $conditions = ['id' => 1, 'name' => 'John'];<br>
     * $whereClause = $this->where($conditions);<br>
     * // Result: "id = :id AND name = :name"
     */
    public function where(array $conditions): string
    {
        $where = [];
        foreach (array_keys($conditions) as $field) {
            $where[] = "{$field} = :{$field}";
        }
        return implode(" AND ", $where);
    }

    /**
     * It is responsible for initializing the transaction.
     *
     * @uses PDO
     * @uses Database::$conn
     * @return bool
     */
    public function inicializeTransaction(): bool
    {
        if (
            self::$conn instanceof PDO &&
            !self::$conn->inTransaction()
        ) {
            return self::$conn->beginTransaction();
        }
        return false;
    }

    /**
     * It is responsible for rolling back the transaction.
     *
     * @uses PDO
     * @uses Database::$conn
     * @return bool
     */
    public function rollback(): bool
    {
        if (
            self::$conn instanceof PDO &&
            self::$conn->inTransaction()
        ) {
            return self::$conn->rollBack();
        }
        return false;
    }

    /**
     * It is responsible for saving the transaction.
     *
     * @uses PDO
     * @uses Database::$conn
     * @return bool
     */
    public function save(): bool
    {
        if (
            self::$conn instanceof PDO &&
            self::$conn->inTransaction()
        ) {
            return self::$conn->commit();
        }
        return false;
    }

    /**
     * It is responsible for executing the SQL query.
     *
     * @param string $sql The SQL query to be executed.
     * @param array $params Array of parameters where the key is the parameter name and the value is the parameter value.
     * @uses PDO
     * @return bool return of the execution of PDO::execute.
     */
    public function run(string $sql, array $params = []): bool
    {
        $stmt = self::$conn->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * It is responsible for returning the result of the SQL query.
     *
     * @param string $sql The SQL query to be executed.
     * @param array $params Array of parameters where the key is the parameter name and the value is the parameter value.
     * @uses PDO
     * @return array
     *
     * @example
     * $sql = "SELECT * FROM users WHERE id = :id";<br>
     * $params = ['id' => 1];<br>
     * $result = $this->list($sql, $params);
     */
    public function list(string $sql, array $params = []): array
    {
        $stmt = self::$conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * It is responsible for returning the first result of the SQL query.
     *
     * @param string $sql The SQL query to be executed.
     * @param array $params Array of parameters where the key is the parameter name and the value is the parameter value.
     * @uses PDO
     * @return array
     *
     * @example
     * $sql = "SELECT * FROM users WHERE id = :id";<br>
     * $params = ['id' => 1];<br>
     * $result = $this->listOne($sql, $params);
     */
    public function listOne(string $sql, array $params = []): array
    {
        $stmt = self::$conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * It is responsible for inserting data into the table.
     *
     * @param string $table The name of the table where the data will be inserted.
     * @param array $data Array of data where the key is the field name and the value is the field value.
     * @uses Database::existField()
     * @uses Database::run()
     * @return bool
     *
     * @example
     * <pre>
     * $table = 'users';
     * $data = [
     *     'name' => 'John Doe',
     *     'email' => 'john.doe@test.com',
     *     'password' => 'securepassword'
     * ];
     * $success = $this->insert($table, $data); <br>
     * // Result: true if the insertion was successful, false otherwise
     * </pre>
     */
    public function insert(string $table, array $data): bool
    {
        if ($this->existField($table, "created")) {
            $data["created"] = date("Y-m-d H:i:s");
        }
        if ($this->existField($table, "modified")) {
            $data["modified"] = date("Y-m-d H:i:s");
        }
        $fields = array_keys($data);
        $sql = "INSERT INTO {$table} (" . implode(", ", $fields) . ") VALUES (:" . implode(", :", $fields) . ")";
        return $this->run($sql, $data);
    }

    /**
     * It is responsible for updating data in the table.
     *
     * @param string $table The name of the table where the data will be updated.
     * @param array $data Array of data where the key is the field name and the value is the field value.
     * @param array $where Array of conditions where the key is the field name and the value is the field value.
     * @uses Database::existField()
     * @uses Database::where()
     * @uses Database::run()
     * @return bool
     *
     * @example
     * <pre>
     * $table = 'users';
     * $data = [
     *     'name' => 'Jane Doe',
     *     'email' => 'jane.doe@example.com'
     * ];
     * $where = ['id' => 1];
     * $success = $this->update($table, $data, $where);
     * // Result: true if the update was successful, false otherwise
     * </pre>
     */
    public function update(string $table, array $data, array $where): bool
    {
        if ($this->existField($table, "modified")) {
            $data["modified"] = date("Y-m-d H:i:s");
        }

        $sql = "UPDATE {$table} SET ";

        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = :{$field}";
        }

        $sql .= implode(", ", $fields);
        $where = $this->where($where);
        $sql .= " WHERE {$where}";

        $params = array_merge($data, $where);
        return $this->run($sql, $params);
    }

    /**
     * It is responsible for deleting data from the table.
     *
     * @param string $table The name of the table where the data will be deleted.
     * @param array $where Array of conditions where the key is the field name and the value is the field value.
     * @uses Database::where()
     * @uses Database::run()
     * @return bool
     *
     * @example
     * <pre>
     * $table = 'users';
     * $where = ['id' => 1];
     * $success = $this->delete($table, $where);
     * // Result: true if the deletion was successful, false otherwise
     * </pre>
     */
    public function delete(string $table, array $where): bool
    {
        $whereStr = $this->where($where);
        $sql = "DELETE FROM {$table} WHERE {$whereStr}";
        return $this->run($sql, $where);
    }

    /**
     * It is responsible for returning the fields of the table.
     *
     * @param string $table The name of the table to be returned.
     * @uses Database::list()
     * @return array
     */
    public function getDetTable(string $table): array
    {
        if (!in_array($table, $this->getTables())) {
            return [];
        }

        $fields = [];
        $query = $this->list("DESC {$table}");
        foreach ($query as $field) {
            $fields[] = [
                "name" => $field["Field"],
                "type" => $field["Type"],
                "null" => $field["Null"] == "YES",
                "default" => $field["Default"],
                "pk" => $field["Extra"] == "auto_increment"
            ];
        }

        return $fields;
    }

    /**
     * It is responsible for returning the tables of the database.
     *
     * @uses Database::list()
     * @return array
     */
    public function getTables(): array
    {
        $tables = [];
        $query = $this->list("SHOW TABLES");
        $tables = array_column($query, 'Tables_in_' . self::$settings->database["database"]);
        return $tables;
    }

    /**
     * It is responsible for checking if the table exists.
     *
     * @param string $table The name of the table to be checked.
     * @uses Database::getTables()
     * @return bool
     */
    public function existTable(string $table): bool
    {
        return in_array($table, $this->getTables());
    }

    /**
     * It is responsible for checking if the field exists.
     *
     * @param string $table The name of the table where the field will be checked.
     * @param string $field The name of the field to be checked.
     * @uses Database::getDetTable()
     * @return bool
     */
    public function existField(string $table, string $field): bool
    {
        $fields = array_column($this->getDetTable($table), "name");
        return in_array($field, $fields);
    }
}
