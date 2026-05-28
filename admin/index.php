<?php
require 'auth.php';
require_once '../config.php';

$userModel = new User($conn);

// Fetch the full user row from the database using the session ID
$student = $userModel->find($logged_in_user['id']);

// ----------------------------------------------------------
// PAGE TITLES
// ----------------------------------------------------------
$active_page = 'profile';
$page_title = 'Student Profile';
$page_icon = '<i class="bi bi-person-fill"></i>';

// Include header
include 'header.php'; 
?>
<main class="content">
    <div class="profile-header table-card" style="margin-bottom: 24px;">
        <div class="profile-banner">
            <img src="../src/assets/images/hiro-avatar.png" alt="Avatar">
        </div>
        <div class="profile-info-header">
            <div>
                <div class="profile-name"><?= htmlspecialchars($student['name']) ?></div>
                <div class="profile-id"><?= htmlspecialchars($student['student_no']) ?></div>
                <span class="badge badge-active"><?= htmlspecialchars($student['status']) ?></span>
            </div>
        </div>
    </div>
     <div class="table-card" style="margin-bottom: 24px;">
        <div class="table-card-header">
            <div class="table-card-title">Personal Information</div>
        </div>
        <?php
        $personal = [
            "Full Name"  => $student['name'],
            "Birthdate"  => date('F d, Y', strtotime($student['birthdate'])),
            "Age"        => $student['age'] . " years old",
            "Gender"     => $student['gender'],
            "Email"      => $student['email'],
            "Phone"      => $student['phone'],
            "Address"    => $student['address'],
        ];
        foreach ($personal as $label => $value): ?>
        <div class="info-row">
            <div class="info-row-label"><?= htmlspecialchars($label) ?></div>
            <div class="info-row-value"><?= htmlspecialchars($value) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-card-header">
            <div class="table-card-title">Guardian Information</div>
        </div>
        <?php
        $guardian = [
            "Guardian"     => $student['guardian'],
            "Relationship" => $student['guardian_rel'],
            "Contact No."  => $student['guardian_contact'],
        ];
        foreach ($guardian as $label => $value): ?>
        <div class="info-row">
            <div class="info-row-label"><?= htmlspecialchars($label) ?></div>
            <div class="info-row-value"><?= htmlspecialchars($value) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</main>
<?php include 'footer.php'; ?>