<?php
require 'auth.php';
require '../config.php';

// Get user ID from session
$userId = $_SESSION['user']['id'];

// Initialize Grade model
$gradeModel = new Grade($conn);

// Pagination settings
$perPage = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;

// Handle DELETE action
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $deleteId = (int) $_GET['delete'];
    $grade = $gradeModel->findByUser($userId, $deleteId);
    if ($grade) {
        $gradeModel->delete($deleteId);
        $_SESSION['flash'] = "Grade for \"$grade[subject]\" has been deleted.";
    }
    header('Location: grades.php');
    exit;
}

// Handle POST actions (ADD and EDIT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $subject  = trim($_POST['subject']);
    $prelim   = (int) $_POST['prelim'];
    $midterm  = (int) $_POST['midterm'];
    $final    = (int) $_POST['final'];
    $grade    = round(($prelim + $midterm + $final) / 3);
    
    if ($action === 'edit') {
        $id = (int) $_POST['id'];
        $existingGrade = $gradeModel->findByUser($userId, $id);
        if ($existingGrade) {
            $gradeModel->update($id, [
                'subject' => $subject,
                'prelim'  => $prelim,
                'midterm' => $midterm,
                'final'   => $final,
                'grade'   => $grade
            ]);
            $_SESSION['flash'] = "Grade for \"$subject\" has been updated. Final grade: $grade";
        }
    } else {
        // ADD new grade
        $gradeModel->create([
            'user_id' => $userId,
            'subject' => $subject,
            'prelim'  => $prelim,
            'midterm' => $midterm,
            'final'   => $final,
            'grade'   => $grade
        ]);
        $_SESSION['flash'] = "Grade for \"$subject\" added. Final grade: $grade";
    }
    
    header('Location: grades.php');
    exit;
}

// Get flash message
$success_message = '';
if (isset($_SESSION['flash'])) {
    $success_message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Get statistics
$totalGrades = $gradeModel->countByUser($userId);
$avgGrade = $gradeModel->getAverageGrade($userId);
$highestGrade = $gradeModel->getHighestGrade($userId);
$lowestGrade = $gradeModel->getLowestGrade($userId);

// Get paginated grades
$grades = $gradeModel->getPaginated($userId, $perPage, $page);
$totalPages = ceil($totalGrades / $perPage);

$active_page = 'grades';
$page_title  = 'My Grades';
$page_icon   = '<i class="bi bi-trophy-fill"></i>';
include 'header.php';
?>

<!-- Modal for Add/Edit Grade -->
<div id="gradeModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 id="modalTitle">Add Grade Record</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="form-hint">Final Grade is auto-computed: (Prelim + Midterm + Final Exam) ÷ 3</p>
            <form method="POST" action="grades.php" id="gradeForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="subject">Subject Name</label>
                        <input type="text" id="subject" name="subject" placeholder="e.g. Statistics and Probability" required>
                    </div>
                    <div class="form-group">
                        <label for="prelim">Prelim Score</label>
                        <input type="number" id="prelim" name="prelim" min="0" max="100" placeholder="0 – 100" required 
                               oninput="calculateGrade()">
                    </div>
                    <div class="form-group">
                        <label for="midterm">Midterm Score</label>
                        <input type="number" id="midterm" name="midterm" min="0" max="100" placeholder="0 – 100" required 
                               oninput="calculateGrade()">
                    </div>
                    <div class="form-group">
                        <label for="final">Final Exam Score</label>
                        <input type="number" id="final" name="final" min="0" max="100" placeholder="0 – 100" required 
                               oninput="calculateGrade()">
                    </div>
                    <div class="form-group">
                        <label>Computed Final Grade</label>
                        <div id="computedGrade" style="padding: 9px 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: 6px; font-size: 13px; color: var(--accent); font-weight: 600; font-family: var(--mono);">
                            —
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit" id="submitBtn">Add Grade Record</button>
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
            <p>Are you sure you want to delete this grade record? This action cannot be undone.</p>
            <p id="deleteGradeInfo" style="color: var(--accent); font-weight: 600; margin-top: 8px;"></p>
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
            <div class="stat-label">Avg Grade</div>
            <div class="stat-value blue"><?= $avgGrade ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Highest</div>
            <div class="stat-value green"><?= $highestGrade ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Lowest</div>
            <div class="stat-value red"><?= $lowestGrade ?></div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">Grade Report – 1st Semester</div>
            <button class="btn-add" onclick="openAddModal()">
                <i class="bi bi-plus-circle"></i> Add Grade
            </button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Prelim</th>
                    <th>Midterm</th>
                    <th>Final Exam</th>
                    <th>Final Grade</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($grades) === 0): ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:24px; color:var(--text-muted);">
                        No grades yet. Click "Add Grade" to create one.
                    </td>
                </tr>
                <?php endif; ?>

                <?php 
                $counter = ($page - 1) * $perPage + 1;
                foreach ($grades as $g): 
                ?>
                <tr>
                    <td class="id-cell"><?= $counter++ ?></td>
                    <td><?= htmlspecialchars($g['subject']) ?></td>
                    <td class="id-cell"><?= $g['prelim'] ?></td>
                    <td class="id-cell"><?= $g['midterm'] ?></td>
                    <td class="id-cell"><?= $g['final'] ?></td>
                    <td>
                        <?php
                        $fg = $g['grade'];
                        $gc = $fg >= 90 ? 'grade-high' : ($fg >= 85 ? 'grade-mid' : 'grade-low');
                        ?>
                        <span class="<?= $gc ?>"><?= $fg ?></span>
                    </td>
                    <td>
                        <span class="badge <?= $fg >= 75 ? 'badge-active' : 'badge-probation' ?>">
                            <?= $fg >= 75 ? 'Passed' : 'Failed' ?>
                        </span>
                    </td>
                    <td class="actions-cell">
                        <a href="#" class="btn-action btn-edit-action" onclick="openEditModal(<?= htmlspecialchars(json_encode($g)) ?>); return false;">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                        <a href="#" class="btn-action btn-delete-action" onclick="openDeleteModal(<?= $g['id'] ?>, '<?= htmlspecialchars($g['subject']) ?>'); return false;">
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
                Showing <?= max(1, ($page - 1) * $perPage + 1) ?> to <?= min($page * $perPage, $totalGrades) ?> of <?= $totalGrades ?> grades
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
    document.getElementById('modalTitle').textContent = 'Add Grade Record';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('submitBtn').textContent = 'Add Grade Record';
    document.getElementById('gradeForm').reset();
    document.getElementById('computedGrade').textContent = '—';
    document.getElementById('gradeModal').style.display = 'flex';
}

function openEditModal(grade) {
    document.getElementById('modalTitle').textContent = 'Edit Grade Record';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = grade.id;
    document.getElementById('submitBtn').textContent = 'Update Grade Record';
    
    document.getElementById('subject').value = grade.subject;
    document.getElementById('prelim').value = grade.prelim;
    document.getElementById('midterm').value = grade.midterm;
    document.getElementById('final').value = grade.final;
    
    // Calculate and display the grade
    const computed = Math.round((grade.prelim + grade.midterm + grade.final) / 3);
    document.getElementById('computedGrade').textContent = computed;
    
    document.getElementById('gradeModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('gradeModal').style.display = 'none';
}

function openDeleteModal(id, subjectName) {
    document.getElementById('deleteGradeInfo').textContent = subjectName;
    document.getElementById('deleteConfirmBtn').href = '?delete=' + id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function calculateGrade() {
    const prelim = parseInt(document.getElementById('prelim').value) || 0;
    const midterm = parseInt(document.getElementById('midterm').value) || 0;
    const final = parseInt(document.getElementById('final').value) || 0;
    
    if (document.getElementById('prelim').value && document.getElementById('midterm').value && document.getElementById('final').value) {
        const computed = Math.round((prelim + midterm + final) / 3);
        document.getElementById('computedGrade').textContent = computed;
    } else {
        document.getElementById('computedGrade').textContent = '—';
    }
}

// Close modals when clicking outside
document.getElementById('gradeModal').addEventListener('click', function(e) {
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