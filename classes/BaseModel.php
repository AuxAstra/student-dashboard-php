<?php
// classes/BaseModel.php
// Every model (User, Subject, Grade) extends this class.
// It provides shared getAll() and delete() so subclasses
// only need to define their own specific queries.

class BaseModel {
    protected $conn;    // raw PDO connection
    protected $db;      // QueryBuilder instance
    protected $table;   // set by each subclass

    public function __construct($conn) {
        $this->conn = $conn;
        $this->db   = new QueryBuilder($conn);
    }

    // Return every row in the table
    public function getAll() {
        return $this->db->table($this->table)->select()->get();
    }

    // Delete a row by its primary key
    public function delete($id) {
        return $this->db->table($this->table)->delete($id);
    }

    // Find a single row by ID
    public function find($id) {
        return $this->db
                    ->table($this->table)
                    ->select()
                    ->where('id', $id)
                    ->first();
    }
}
