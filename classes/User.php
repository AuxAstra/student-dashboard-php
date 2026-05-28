<?php
// ============================================================
//  classes/User.php
//
//  Handles all database operations related to the users table.
//  Extends BaseModel so it inherits getAll(), delete(), find().
// ============================================================

class User extends BaseModel {
    protected $table = "users";

    // Find a user by username (used for login)
    public function findByUsername($username) {
        return $this->db
                    ->table($this->table)
                    ->select()
                    ->where('username', $username)
                    ->first();
    }

    // Update profile fields for a given user ID
    public function update($id, $data) {
        return $this->db->table($this->table)->update($data, $id);
    }

    // Create a new user record
    public function create($data) {
        return $this->db->table($this->table)->insert($data);
    }
}
