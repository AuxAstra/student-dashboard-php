<?php
// classes/Subject.php
// Model for managing subjects in the database.
// Extends BaseModel for common CRUD operations.

class Subject extends BaseModel {
    protected $table = 'subjects';

    public function __construct($conn) {
        parent::__construct($conn);
    }

    // Get subjects for a specific user with pagination
    public function getByUser($userId, $limit = 10, $offset = 0) {
        return $this->db
                    ->table($this->table)
                    ->select()
                    ->where('user_id', $userId)
                    ->orderBy('id', 'DESC')
                    ->get();
    }

    // Get paginated subjects for a user
    public function getPaginated($userId, $limit = 10, $page = 1) {
        $offset = ($page - 1) * $limit;
        
        // Get the records
        $records = $this->db
                        ->table($this->table)
                        ->select()
                        ->where('user_id', $userId)
                        ->orderBy('id', 'DESC')
                        ->get();
        
        // For Apply manual pagination since QueryBuilder doesn't support limit/offset
        return array_slice($records, $offset, $limit);
    }

    // Count total subjects for a user
    public function countByUser($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    // Count total units for a user
    public function countTotalUnits($userId) {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(units), 0) as total FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    // Insert a new subject
    public function create($data) {
        return $this->db->table($this->table)->insert($data);
    }

    // Update a subject
    public function update($id, $data) {
        return $this->db->table($this->table)->update($data, $id);
    }

    // Delete a subject (inherited from BaseModel)
    // public function delete($id) { return parent::delete($id); }

    // Find a subject by ID and user ID
    public function findByUser($userId, $id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}