<?php
require 'auth.php';
require '../config.php';

// Get user ID from session
$userId = $_SESSION['user']['id'];

// Initialize Subject model
$subjectModel = new Subject($conn);

// Pagination settings
$perPage = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;

// Handle DELETE action
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $deleteId = (int) $_GET['delete'];
    $subject = $subjectModel->findByUser($userId, $deleteId);
    if ($subject) {
        $subjectModel->delete($deleteId);
        $_SESSION['flash'] = "Subject \"$subject[code]: $subject[name]\" has been deleted.";
    }
    header('Location: subjects.php');
    exit;
}

// Handle POST actions (ADD and EDIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $code     = strtoupper(trim($_POST['code']));
    $name     = trim($_POST['name']);
    $teacher  = trim($_POST['teacher']);
    $units    = (int) $_POST['units'];
    $schedule = trim($_POST['schedule']);
    
    if ($action === 'edit') {
        $id = (int) $_POST['id'];
        $subject = $subjectModel->findByUser($userId, $id);
        if ($subject) {
            $subjectModel->update($id, [
                'code'     => $code,
                'name'     => $name,
                'teacher'  => $teacher,
                'units'    => $units,
                'schedule' => $schedule
            ]);
            $_SESSION['flash'] = "Subject \"$name\" has been updated.";
        }
    } else {
        // ADD new subject
        $subjectModel->create([
            'user_id'  => $userId,
            'code'     => $code,
            'name'     => $name,
            'teacher'  => $teacher,
            'units'    => $units,
            'schedule' => $schedule
        ]);
        $_SESSION['flash'] = "Subject \"$name\" has been added.";
    }
    
    header('Location: subjects.php');
    exit;
}

// Get flash message
$success_message = '';
if (isset($_SESSION['flash'])) {
    $success_message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Get statistics
$totalSubjects = $subjectModel->countByUser($userId);
$totalUnits = $subjectModel->countTotalUnits($userId);

// Get paginated subjects
$subjects = $subjectModel->getPaginated($userId, $perPage, $page);
$totalPages = ceil($totalSubjects / $perPage);

// Get edit subject if editing
$editSubject = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editSubject = $subjectModel->findByUser($userId, $editId);
}

$active_page = 'subjects';
$page_title  = 'Subjects';
$page_icon   = '<i class="bi bi-journal-text"></i>';
include 'header.php';
?>

<!-- Modal for Add/Edit Subject -->
<div id="subjectModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Subject</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="subjects.php" id="subjectForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="code">Subject Code</label>
                        <input type="text" id="code" name="code" placeholder="e.g. MATH102" required maxlength="10">
                    </div>
                    <div class="form-group">
                        <label for="name">Subject Name</label>
                        <input type="text" id="name" name="name" placeholder="e.g. Statistics and Probability" required>
                    </div>
                    <div class="form-group">
                        <label for="teacher">Teacher</label>
                        <input type="text" id="teacher" name="teacher" placeholder="e.g. Ms. Cruz" required>
                    </div>
                    <div class="form-group">
                        <label for="units">Units</label>
                        <select id="units" name="units" required>
                            <option value="">— Select —</option>
                            <option value="1">1 unit</option>
                            <option value="2">2 units</option>
                            <option value="3">3 units</option>
                            <option value="4">4 units</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="schedule">Schedule</label>
                        <input type="text" id="schedule" name="schedule" placeholder="e.g. MWF 7:30–8:30" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3>Confirm Delete</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this subject? This action cannot be undone.</p>
            <p id="deleteSubjectInfo" style="color: var(--accent); font-weight: 600; margin-top: 8px;"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <a href="#" id="deleteConfirmBtn" class="btn-delete">Delete</a>
        </div>
    </div>
</div>

<main class="content">
    <?php if ($success_message): ?>
        <div class="alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-label">Total Subjects</div>
            <div class="stat-value blue"><?= $totalSubjects ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Units</div>
            <div class="stat-value green"><?= $totalUnits ?></div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">Enrolled Subjects</div>
            <button class="btn-add" onclick="openAddModal()">
                <i class="bi bi-plus-circle"></i> Add Subject
            </button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Subject Name</th>
                    <th>Teacher</th>
                    <th>Units</th>
                    <th>Schedule</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($subjects) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">
                        No subjects yet. Click "Add Subject" to create one.
                    </td>
                </tr>
                <?php endif; ?>

                <?php 
                $counter = ($page - 1) * $perPage + 1;
                foreach ($subjects as $subject): 
                ?>
                <tr>
                    <td class="id-cell"><?= $counter++ ?></td>
                    <td class="code-cell"><?= htmlspecialchars($subject['code']) ?></td>
                    <td><?= htmlspecialchars($subject['name']) ?></td>
                    <td><?= htmlspecialchars($subject['teacher']) ?></td>
                    <td class="id-cell"><?= $subject['units'] ?> units</td>
                    <td class="schedule-tag"><?= htmlspecialchars($subject['schedule']) ?></td>
                    <td class="actions-cell">
                        <a href="?edit=<?= $subject['id'] ?>" class="btn-action btn-edit-action" onclick="openEditModal(<?= htmlspecialchars(json_encode($subject)) ?>); return false;">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                        <a href="#" class="btn-action btn-delete-action" onclick="openDeleteModal(<?= $subject['id'] ?>, '<?= htmlspecialchars($subject['code'] . ': ' . $subject['name']) ?>'); return false;">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <div class="pagination-info">
                Showing <?= max(1, ($page - 1) * $perPage + 1) ?> to <?= min($page * $perPage, $totalSubjects) ?> of <?= $totalSubjects ?> subjects
            </div>
            <div class="pagination-controls">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="pagination-btn">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="pagination-btn">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(2px);
}

.modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    width: 90%;
    max-width: 520px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.modal-sm {
    max-width: 400px;
}

.modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    transition: color 0.15s;
}

.modal-close:hover {
    color: var(--text);
}

.modal-body {
    padding: 20px;
}

.modal-body p {
    font-size: 14px;
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0;
}

.modal-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-cancel {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-muted);
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-cancel:hover {
    background: var(--surface2);
    color: var(--text);
}

.btn-delete {
    background: var(--accent4);
    color: #fff;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    transition: opacity 0.15s;
}

.btn-delete:hover {
    opacity: 0.85;
}

/* Add button in table header */
.btn-add {
    background: var(--accent);
    color: #0d1117;
    border: none;
    padding: 7px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: opacity 0.15s;
}

.btn-add:hover {
    opacity: 0.85;
}

/* Action buttons in table */
.actions-cell {
    display: flex;
    gap: 6px;
}

.btn-action {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s;
    border: none;
    cursor: pointer;
}

.btn-edit-action {
    background: rgba(88, 166, 255, 0.15);
    color: var(--accent);
}

.btn-edit-action:hover {
    background: rgba(88, 166, 255, 0.25);
}

.btn-delete-action {
    background: rgba(248, 81, 73, 0.15);
    color: var(--accent4);
}

.btn-delete-action:hover {
    background: rgba(248, 81, 73, 0.25);
}

/* Pagination */
.pagination {
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.pagination-info {
    font-size: 12px;
    color: var(--text-muted);
    font-family: var(--mono);
}

.pagination-controls {
    display: flex;
    gap: 4px;
}

.pagination-btn {
    padding: 6px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 12px;
    color: var(--text-muted);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s;
    background: transparent;
}

.pagination-btn:hover {
    background: var(--surface2);
    color: var(--text);
    border-color: var(--accent);
}

.pagination-btn.active {
    background: var(--accent);
    color: #0d1117;
    border-color: var(--accent);
    font-weight: 600;
}
</style>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Subject';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('submitBtn').textContent = 'Add Subject';
    document.getElementById('subjectForm').reset();
    document.getElementById('subjectModal').style.display = 'flex';
}

function openEditModal(subject) {
    document.getElementById('modalTitle').textContent = 'Edit Subject';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = subject.id;
    document.getElementById('submitBtn').textContent = 'Update Subject';
    
    document.getElementById('code').value = subject.code;
    document.getElementById('name').value = subject.name;
    document.getElementById('teacher').value = subject.teacher;
    document.getElementById('units').value = subject.units;
    document.getElementById('schedule').value = subject.schedule;
    
    document.getElementById('subjectModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('subjectModal').style.display = 'none';
}

function openDeleteModal(id, subjectInfo) {
    document.getElementById('deleteSubjectInfo').textContent = subjectInfo;
    document.getElementById('deleteConfirmBtn').href = '?delete=' + id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modals when clicking outside
document.getElementById('subjectModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
    }
});
</script>

<?php include 'footer.php'; ?>