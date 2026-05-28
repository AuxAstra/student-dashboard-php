<?php
// classes/Grade.php
// Model for managing grades in the database.
// Extends BaseModel for common CRUD operations.

class Grade extends BaseModel {
    protected $table = 'grades';

    public function __construct($conn) {
        parent::__construct($conn);
    }

    // Get paginated grades for a user
    public function getPaginated($userId, $limit = 10, $page = 1) {
        $offset = ($page - 1) * $limit;
        
        // Get the records
        $records = $this->db
                        ->table($this->table)
                        ->select()
                        ->where('user_id', $userId)
                        ->orderBy('id', 'DESC')
                        ->get();
        
        // Apply manual pagination since QueryBuilder doesn't support limit/offset
        return array_slice($records, $offset, $limit);
    }

    // Count total grades for a user
    public function countByUser($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    // Calculate average grade for a user
    public function getAverageGrade($userId) {
        $stmt = $this->conn->prepare("SELECT COALESCE(ROUND(AVG(grade), 1), 0) as avg_grade FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) $result['avg_grade'];
    }

    // Get highest grade for a user
    public function getHighestGrade($userId) {
        $stmt = $this->conn->prepare("SELECT COALESCE(MAX(grade), 0) as highest FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['highest'];
    }

    // Get lowest grade for a user
    public function getLowestGrade($userId) {
        $stmt = $this->conn->prepare("SELECT COALESCE(MIN(grade), 0) as lowest FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['lowest'];
    }

    // Insert a new grade
    public function create($data) {
        return $this->db->table($this->table)->insert($data);
    }

    // Update a grade
    public function update($id, $data) {
        return $this->db->table($this->table)->update($data, $id);
    }

    // Delete a grade (inherited from BaseModel)
    // public function delete($id) { return parent::delete($id); }

    // Find a grade by ID and user ID
    public function findByUser($userId, $id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}