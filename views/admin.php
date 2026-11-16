<?php
// views/admin.php - Tabbed Admin Control & Audit Center
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Junior Admin'])) {
    exit();
}

try {
    $userStmt = $pdo->query("SELECT user_id, staff_id, full_name, role, created_at FROM users ORDER BY created_at DESC");
    $workers = $userStmt->fetchAll();
} catch (Exception $e) {
    $workers = [];
}

try {
    $logStmt = $pdo->query("
        SELECT al.*, u.full_name, u.staff_id, u.role as user_role 
        FROM audit_logs al 
        LEFT JOIN users u ON al.user_id = u.user_id 
        ORDER BY al.timestamp DESC 
        LIMIT 50
    ");
    $logs = $logStmt->fetchAll();
} catch (Exception $e) {
    $logs = [];
}
?>

<!-- Greeting Banner -->
<div class="card" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; margin-bottom: 20px; border-radius: 10px; padding: 25px;">
    <h2 style="margin: 0 0 5px 0; font-size: 24px;">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>! 👋</h2>
    <p style="margin: 0; opacity: 0.9; font-size: 15px;">Administration Center — Manage staff provisioning, account credentials, and review system audit logs.</p>
</div>

<!-- Interactive Navigation Tabs -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
    <button onclick="switchAdminTab('create-user-panel', this)" class="admin-tab-btn active-tab" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
        ➕ Provision Account
    </button>
    <button onclick="switchAdminTab('directory-panel', this)" class="admin-tab-btn" style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
        👥 Personnel Directory & Resets
    </button>
    <button onclick="switchAdminTab('audit-panel', this)" class="admin-tab-btn" style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
        🛡️ Audit Trails
    </button>
</div>

<!-- Panel 1: Create Account -->
<div id="create-user-panel" class="admin-tab-panel card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <h3 style="color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-top: 0;">Create New Staff Account</h3>
    <form action="auth_process.php?action=create_user" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Staff ID</label>
            <input type="text" name="staff_id" placeholder="e.g., DOC-202" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; box-sizing: border-box;">
        </div>
        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Full Name</label>
            <input type="text" name="full_name" placeholder="Dr. Jane Doe" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; box-sizing: border-box;">
        </div>
        <div style="grid-column: span 2;">
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Password</label>
            <input type="password" name="password" placeholder="••••••••" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; box-sizing: border-box;">
        </div>
        <div style="grid-column: span 2;">
            <label style="font-weight: 600; font-size: 13px; color: #475569;">System Role</label>
            <select name="role" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; background: white; box-sizing: border-box;">
                <option value="">-- Select Assigned Role --</option>
                <option value="Doctor">Doctor</option>
                <option value="Pharmacist">Pharmacist</option>
                <option value="Junior Admin">Junior Admin</option>
                <?php if ($_SESSION['role'] === 'Admin'): ?>
                    <option value="Admin">Admin</option>
                <?php endif; ?>
            </select>
        </div>
        <div style="grid-column: span 2;">
            <button type="submit" class="btn-primary" style="padding: 12px 25px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Provision Account</button>
        </div>
    </form>
</div>

<!-- Panel 2: Personnel Directory & Password Reset -->
<div id="directory-panel" class="admin-tab-panel card" style="display: none; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <h3 style="color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-top: 0;">Personnel Directory & Password Management</h3>
    <p style="color: #64748b; font-size: 13px; margin-top: 5px;">Admins can reset passwords for users who forgot their credentials.</p>
    
    <table style="width:100%; border-collapse: collapse; margin-top: 15px; font-size: 14px;">
        <thead>
            <tr style="background: #f8fafc; text-align: left; color: #475569;">
                <th style="padding: 12px;">Staff ID</th>
                <th style="padding: 12px;">Full Name</th>
                <th style="padding: 12px;">Role</th>
                <th style="padding: 12px;">Registered On</th>
                <th style="padding: 12px; text-align: right;">Administrative Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($workers)): ?>
                <tr><td colspan="5" style="padding: 20px; text-align: center; color: #64748b;">No registered staff found.</td></tr>
            <?php else: ?>
                <?php foreach ($workers as $w): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; font-weight: 600;"><?= htmlspecialchars($w['staff_id']) ?></td>
                        <td style="padding: 12px;"><?= htmlspecialchars($w['full_name']) ?></td>
                        <td style="padding: 12px;">
                            <?php 
                                $badgeBg = '#e2e8f0'; $badgeColor = '#334155';
                                if ($w['role'] === 'Admin') { $badgeBg = '#fee2e2'; $badgeColor = '#991b1b'; }
                                elseif ($w['role'] === 'Doctor') { $badgeBg = '#dbeafe'; $badgeColor = '#1e40af'; }
                                elseif ($w['role'] === 'Pharmacist') { $badgeBg = '#dcfce7'; $badgeColor = '#166534'; }
                            ?>
                            <span style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <?= htmlspecialchars($w['role']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px; color: #64748b;"><?= htmlspecialchars($w['created_at']) ?></td>
                        <td style="padding: 12px; text-align: right; display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                            <?php if ($_SESSION['role'] === 'Admin'): ?>
                                <!-- Password Reset Trigger Form -->
                                <button type="button" onclick="openResetModal(<?= $w['user_id'] ?>, '<?= htmlspecialchars($w['staff_id'], ENT_QUOTES) ?>')" style="background: #e0f2fe; color: #0369a1; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                    Reset Password
                                </button>
                                
                                <?php if ((int)$_SESSION['user_id'] !== (int)$w['user_id']): ?>
                                    <a href="auth_process.php?action=delete_user&id=<?= $w['user_id'] ?>" 
                                       onclick="return confirm('⚠️ WARNING: Delete account <?= htmlspecialchars($w['staff_id']) ?>?');" 
                                       style="background: #fee2e2; color: #dc2626; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600;">
                                       Delete
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 12px; font-style: italic;">Admin Only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Panel 3: Audit Trails -->
<div id="audit-panel" class="admin-tab-panel card" style="display: none; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <h3 style="color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-top: 0;">System Audit Trails & Status Logs</h3>
    <table style="width:100%; border-collapse: collapse; margin-top: 15px; font-size: 13px;">
        <thead>
            <tr style="background: #f8fafc; text-align: left; color: #475569;">
                <th style="padding: 10px;">Timestamp</th>
                <th style="padding: 10px;">Staff Member</th>
                <th style="padding: 10px;">Role</th>
                <th style="padding: 10px;">Action Type</th>
                <th style="padding: 10px;">Details & Changes</th>
                <th style="padding: 10px;">IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" style="padding: 20px; text-align: center; color: #64748b;">No security logs recorded yet.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px; white-space: nowrap; color: #64748b;"><?= htmlspecialchars($log['timestamp']) ?></td>
                        <td style="padding: 10px; font-weight: 500;"><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
                        <td style="padding: 10px; color: #475569;"><?= htmlspecialchars($log['user_role'] ?? 'N/A') ?></td>
                        <td style="padding: 10px;">
                            <span style="font-weight: 600; color: #0f172a; background: #f1f5f9; padding: 3px 8px; border-radius: 4px;">
                                <?= htmlspecialchars($log['action_type']) ?>
                            </span>
                        </td>
                        <td style="padding: 10px; color: #334155;"><?= htmlspecialchars($log['details']) ?></td>
                        <td style="padding: 10px; color: #64748b; font-family: monospace;"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog for Password Reset -->
<div id="resetModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div style="background: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: #1e293b;">Reset Password for <span id="modalStaffId" style="color: #2563eb;"></span></h3>
        <form action="auth_process.php" method="POST">
            <!-- Hidden inputs ensure action and user ID are reliably posted -->
            <input type="hidden" name="action" value="admin_reset_password">
            <input type="hidden" name="user_id" id="modalUserId">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; font-size: 13px; color: #475569; margin-bottom: 6px;">New Temporary Password</label>
                <input type="password" name="new_password" placeholder="Enter new password" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeResetModal()" style="background: #e2e8f0; color: #334155; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;">Update Password</button>
            </div>
        </form>
    </div>
</div>
<script>
function switchAdminTab(panelId, btnElement) {
    document.querySelectorAll('.admin-tab-panel').forEach(panel => panel.style.display = 'none');
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.style.background = '#f1f5f9';
        btn.style.color = '#475569';
    });
    document.getElementById(panelId).style.display = 'block';
    btnElement.style.background = '#2563eb';
    btnElement.style.color = 'white';
}

function openResetModal(userId, staffId) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalStaffId').textContent = staffId;
    document.getElementById('resetModal').style.display = 'flex';
}

function closeResetModal() {
    document.getElementById('resetModal').style.display = 'none';
}
</script>