<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Digital Prescription System</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card login-card">
        <h2>System Login</h2>
        <p style="color: #6c757d; font-size: 0.9rem;">Secure Digital Prescription & Tracking System</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form method="POST" action="auth_process.php?action=login">
            <div class="form-group">
                <label>Staff ID</label>
                <input type="text" name="staff_id" placeholder="e.g., DOC-101" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="password123" required>
            </div>
            <button type="submit" class="btn" style="width: 100%;">Login</button>
        </form>
        <div style="margin-top: 15px; font-size: 0.8rem; color: #6c757d;">
            <strong>Test Accounts:</strong><br>
            Doctor: <code>DOC-101</code> / <code>password123</code><br>
            Pharmacist: <code>PHARM-201</code> / <code>password123</code><br>
            Admin: <code>ADMIN-301</code> / <code>password123</code>
        </div>
    </div>
</body>
</html>