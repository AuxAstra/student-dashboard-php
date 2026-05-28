<?php
// classes/QueryBuilder.php
// A fluent interface for building SQL queries without
// writing raw SQL strings in every page.
//
// Usage example:
//   $qb = new QueryBuilder($conn);
//   $rows = $qb->table('subjects')->select()->where('user_id', 1)->get();

class QueryBuilder {
    private $conn;
    private $table;
    private $sql    = "";
    private $params = [];
    private $limitValue  = null;
    private $offsetValue = null;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Set the target table and reset state for a fresh query
    public function table($table) {
        $this->table  = $table;
        $this->sql    = "";
        $this->params = [];
        return $this;
    }

    // Build a SELECT statement
    public function select($columns = "*") {
        $this->sql = "SELECT $columns FROM {$this->table}";
        return $this;
    }

    // Append a WHERE clause
    public function where($column, $value) {
        // Check if WHERE already exists to support chaining
        if (strpos($this->sql, 'WHERE') === false) {
            $this->sql .= " WHERE $column = :$column";
        } else {
            $this->sql .= " AND $column = :$column";
        }
        $this->params[":$column"] = $value;
        return $this;
    }

    // ORDER BY clause
    public function orderBy($column, $direction = 'ASC') {
        $this->sql .= " ORDER BY $column $direction";
        return $this;
    }

    // LIMIT clause
    public function limit($value) {
        $this->limitValue = (int) $value;
        return $this;
    }

    // OFFSET clause
    public function offset($value) {
        $this->offsetValue = (int) $value;
        return $this;
    }

    // Execute and return all matching rows
    public function get() {
        // Append LIMIT and OFFSET if set
        if ($this->limitValue !== null) {
            $this->sql .= " LIMIT " . $this->limitValue;
        }
        if ($this->offsetValue !== null) {
            $this->sql .= " OFFSET " . $this->offsetValue;
        }
        
        $stmt = $this->conn->prepare($this->sql);
        $stmt->execute($this->params);
        
        // Reset limit/offset for next query
        $this->limitValue  = null;
        $this->offsetValue = null;
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Execute and return only the first matching row
    public function first() {
        $this->sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($this->sql);
        $stmt->execute($this->params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Build and execute an INSERT statement
    public function insert(array $data) {
        $columns      = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));

        $this->sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        $stmt = $this->conn->prepare($this->sql);
        return $stmt->execute($data);
    }

    // Build and execute an UPDATE statement
    public function update(array $data, $id) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
        }
        $set = implode(", ", $setParts);

        $this->sql = "UPDATE {$this->table} SET $set WHERE id = :id";
        $data['id'] = $id;

        $stmt = $this->conn->prepare($this->sql);
        return $stmt->execute($data);
    }

    // Build and execute a DELETE statement
    public function delete($id) {
        $this->sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($this->sql);
        return $stmt->execute(['id' => $id]);
    }

    // Return the last inserted ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
}
