<?php
// auth_process.php - Central Authentication & Request Router
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Optional audit logger integration
if (file_exists('logger.php')) {
    require_once 'logger.php';
}

// Resolve action from either GET or POST request parameters
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ==========================================
// 1. LOGIN HANDLER (Universal & Legacy-Safe)
// ==========================================
if ($action === 'login') {
    $inputStaffId = trim($_POST['staff_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($inputStaffId) || empty($password)) {
        header('Location: index.php?error=Staff ID and Password are required');
        exit();
    }

    try {
        $stmt = $pdo->query("SELECT * FROM users");
        $allUsers = $stmt->fetchAll();

        $user = null;
        foreach ($allUsers as $u) {
            $dbStaffId = trim($u['staff_id'] ?? '');
            $dbUsername = trim($u['username'] ?? '');

            if (strcasecmp($dbStaffId, $inputStaffId) === 0 || strcasecmp($dbUsername, $inputStaffId) === 0) {
                $user = $u;
                break;
            }
        }

        $isAuthenticated = false;

        if ($user) {
            $dbHash = $user['password_hash'] ?? '';

            // 1. Verify against password_hash using bcrypt
            if (!empty($dbHash) && @password_verify($password, $dbHash)) {
                $isAuthenticated = true;
            }
            // 2. Legacy plain-text fallback (if old seed data was unhashed)
            elseif (!empty($dbHash) && $password === $dbHash) {
                $isAuthenticated = true;
                // Upgrade plain-text password to secure bcrypt hash automatically
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $pdo->prepare("UPDATE users SET password_hash = :h WHERE user_id = :id")->execute(['h' => $newHash, 'id' => $user['user_id']]);
                } catch (Exception $ex) {}
            }
        }

        if ($isAuthenticated) {
            $rawRole = trim($user['role'] ?? 'Doctor');
            $normalizedRole = 'Doctor';

            if (strcasecmp($rawRole, 'Admin') === 0 || strcasecmp($rawRole, 'Administrator') === 0) {
                $normalizedRole = 'Admin';
            } elseif (strcasecmp($rawRole, 'Junior Admin') === 0) {
                $normalizedRole = 'Junior Admin';
            } elseif (strcasecmp($rawRole, 'Pharmacist') === 0) {
                $normalizedRole = 'Pharmacist';
            } elseif (strcasecmp($rawRole, 'Doctor') === 0) {
                $normalizedRole = 'Doctor';
            } else {
                $normalizedRole = $rawRole;
            }

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['staff_id'] ?? $user['username'] ?? '';
            $_SESSION['full_name'] = $user['full_name'] ?? 'Staff Member';
            $_SESSION['role'] = $normalizedRole;

            if (function_exists('log_action')) {
                log_action($pdo, $user['user_id'], 'LOGIN', 'User logged in successfully');
            }

            header('Location: index.php');
            exit();
        } else {
            header('Location: index.php?error=Invalid Staff ID or Password');
            exit();
        }
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode('Login Error: ' . $e->getMessage()));
        exit();
    }
}

// ==========================================
// 2. LOGOUT HANDLER
// ==========================================
if ($action === 'logout') {
    if (isset($_SESSION['user_id']) && function_exists('log_action')) {
        log_action($pdo, $_SESSION['user_id'], 'LOGOUT', 'User logged out');
    }
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// ==========================================
// 3. CREATE WORKER ACCOUNT (Admin / Junior Admin)
// ==========================================
if ($action === 'create_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Junior Admin'])) {
        header('Location: index.php?error=Unauthorized action');
        exit();
    }

    $staffId = trim($_POST['staff_id'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if (empty($staffId) || empty($fullName) || empty($password) || empty($role)) {
        header('Location: index.php?error=All fields are required to create an account');
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Insert solely into password_hash matching your actual database schema
        $stmt = $pdo->prepare("INSERT INTO users (staff_id, full_name, password_hash, role) VALUES (:staff_id, :full_name, :hash, :role)");
        $stmt->execute([
            'staff_id' => $staffId,
            'full_name' => $fullName,
            'hash' => $hashedPassword,
            'role' => $role
        ]);

        if (function_exists('log_action')) {
            log_action($pdo, $_SESSION['user_id'], 'CREATE_USER', "Created staff account: {$staffId} ({$role})");
        }

        header('Location: index.php?success=Worker account created successfully');
        exit();
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode('Error creating user: ' . $e->getMessage()));
        exit();
    }
}

// ==========================================
// 4. DELETE USER HANDLER (Admin Only)
// ==========================================
if ($action === 'delete_user' && isset($_GET['id'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        header('Location: index.php?error=Unauthorized action');
        exit();
    }

    $userIdToDelete = (int)$_GET['id'];
    if ($userIdToDelete === (int)$_SESSION['user_id']) {
        header('Location: index.php?error=You cannot delete your own active account.');
        exit();
    }

    try {
        $pdo->prepare("UPDATE audit_logs SET user_id = NULL WHERE user_id = :id")->execute(['id' => $userIdToDelete]);

        $delStmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
        $delStmt->execute(['id' => $userIdToDelete]);

        if (function_exists('log_action')) {
            log_action($pdo, $_SESSION['user_id'], 'DELETE_USER', "Deleted user ID: {$userIdToDelete}");
        }

        header('Location: index.php?success=User account deleted successfully.');
        exit();
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode('Deletion failed: ' . $e->getMessage()));
        exit();
    }
}

// ==========================================
// 5. ADMIN PASSWORD RESET HANDLER (Admin Only)
// ==========================================
if ($action === 'admin_reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        header('Location: index.php?error=Unauthorized action');
        exit();
    }

    $targetUserId = trim($_POST['user_id'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (empty($targetUserId) || empty($newPassword)) {
        header('Location: index.php?error=Invalid user ID or empty password provided.');
        exit();
    }

    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("UPDATE users SET password_hash = :pass WHERE user_id = :id OR staff_id = :id");
        $stmt->execute(['pass' => $hashedPassword, 'id' => $targetUserId]);

        if (function_exists('log_action')) {
            log_action($pdo, $_SESSION['user_id'], 'PASSWORD_RESET', "Reset password for staff user ID: {$targetUserId}");
        }

        header('Location: index.php?success=Staff password successfully updated.');
        exit();
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode('Password reset failed: ' . $e->getMessage()));
        exit();
    }
}

// ==========================================
// 6. CREATE PATIENT HANDLER (Doctor Only)
// ==========================================
if ($action === 'create_patient' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
        header('Location: index.php');
        exit();
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $dob = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? 'Other';
    $contact = trim($_POST['contact_number'] ?? '');
    $patientNumber = 'PAT-' . strtoupper(substr(uniqid(), -6));

    try {
        $stmt = $pdo->prepare("INSERT INTO patients (patient_number, full_name, date_of_birth, gender, contact_number) VALUES (:p_num, :name, :dob, :gender, :contact)");
        $stmt->execute([
            'p_num' => $patientNumber,
            'name' => $fullName,
            'dob' => empty($dob) ? null : $dob,
            'gender' => $gender,
            'contact' => $contact
        ]);

        if (function_exists('log_action')) {
            log_action($pdo, $_SESSION['user_id'], 'CREATE_PATIENT', "Registered patient: {$fullName}");
        }

        header('Location: index.php?success=Patient registered successfully');
        exit();
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode('Patient error: ' . $e->getMessage()));
        exit();
    }
}

// ==========================================
// 7. CREATE PRESCRIPTION HANDLER (Doctor Only)
// ==========================================
if ($action === 'create_prescription' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
        header('Location: index.php');
        exit();
    }

    $patientId = $_POST['patient_id'] ?? '';
    $medication = trim($_POST['medication_details'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');

    try {
        $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_id, doctor_id, medication_details, dosage, status) VALUES (:pid, :did, :med, :dosage, 'Pending')");
        $stmt->execute([
            'pid' => $patientId,
            'did' => $_SESSION['user_id'],
            'med' => $medication,
            'dosage' => $dosage
        ]);

        if (function_exists('log_action')) {
            log_action($pdo, $_SESSION['user_id'], 'CREATE_PRESCRIPTION', "Issued prescription for patient ID: {$patientId}");
        }

        header('Location: index.php?success=Prescription created successfully!');
        exit();
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode('Prescription error: ' . $e->getMessage()));
        exit();
    }
}

// ==========================================
// 8. TOGGLE PRESCRIPTION STATUS (Pharmacist Only)
// ==========================================
if ($action === 'toggle_status' && isset($_GET['id'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Pharmacist') {
        header('Location: index.php');
        exit();
    }

    $rxId = (int)$_GET['id'];
    try {
        $chk = $pdo->prepare("SELECT status FROM prescriptions WHERE prescription_id = :id");
        $chk->execute(['id' => $rxId]);
        $rx = $chk->fetch();

        if ($rx) {
            $newStatus = ($rx['status'] === 'Pending') ? 'Dispensed' : 'Pending';
            $upd = $pdo->prepare("UPDATE prescriptions SET status = :status WHERE prescription_id = :id");
            $upd->execute(['status' => $newStatus, 'id' => $rxId]);

            if (function_exists('log_action')) {
                log_action($pdo, $_SESSION['user_id'], 'UPDATE_STATUS', "Changed prescription #{$rxId} status to {$newStatus}");
            }
        }

        header('Location: index.php?success=Prescription status updated successfully');
        exit();
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode('Status error: ' . $e->getMessage()));
        exit();
    }
}

// Default fallback redirect
header('Location: index.php');
exit();
?>