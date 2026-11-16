<?php
// views/pharmacist.php - Tabbed Pharmacist Portal
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Pharmacist') {
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT p.*, pat.full_name as patient_name 
        FROM prescriptions p 
        JOIN patients pat ON p.patient_id = pat.patient_id 
        ORDER BY p.created_at DESC
    ");
    $prescriptions = $stmt->fetchAll();
} catch (Exception $e) {
    $prescriptions = [];
}
?>

<!-- Greeting Banner -->
<div class="card" style="background: linear-gradient(135deg, #059669, #047857); color: white; margin-bottom: 20px; border-radius: 10px; padding: 25px;">
    <h2 style="margin: 0 0 5px 0; font-size: 24px;">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>! 💊</h2>
    <p style="margin: 0; opacity: 0.9; font-size: 15px;">Pharmacy Portal — Track active prescription fulfillment queues and manage status updates.</p>
</div>

<!-- Pharmacist Queue Panel -->
<div class="card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <h3 style="color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-top: 0;">Active Prescription Queue & Fulfillment</h3>
    <table style="width:100%; border-collapse: collapse; margin-top: 15px; font-size: 14px;">
        <thead>
            <tr style="background: #f8fafc; text-align: left; color: #475569;">
                <th style="padding: 12px;">Rx ID</th>
                <th style="padding: 12px;">Patient Name</th>
                <th style="padding: 12px;">Medication & Dosage</th>
                <th style="padding: 12px;">Status</th>
                <th style="padding: 12px;">Issued At</th>
                <th style="padding: 12px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($prescriptions)): ?>
                <tr>
                    <td colspan="6" style="padding: 25px; text-align: center; color: #64748b;">No prescriptions currently found in the queue.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($prescriptions as $rx): ?>
                    <?php 
                        $status = $rx['status'] ?? 'Pending';
                        $isDispensed = ($status === 'Dispensed');
                        $badgeBg = $isDispensed ? '#dcfce7' : '#fef3c7';
                        $badgeColor = $isDispensed ? '#166534' : '#92400e';
                    ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; font-weight: 600;">#<?= htmlspecialchars($rx['prescription_id']) ?></td>
                        <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($rx['patient_name'] ?? 'Unknown') ?></td>
                        <td style="padding: 12px;">
                            <strong><?= htmlspecialchars($rx['medication_details']) ?></strong><br>
                            <small style="color: #64748b;">Dosage: <?= htmlspecialchars($rx['dosage']) ?></small>
                        </td>
                        <td style="padding: 12px;">
                            <span style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>
                        <td style="padding: 12px; color: #64748b;"><?= htmlspecialchars($rx['created_at']) ?></td>
                        <td style="padding: 12px; text-align: right;">
                            <?php if (!$isDispensed): ?>
                                <a href="auth_process.php?action=toggle_status&id=<?= $rx['prescription_id'] ?>" style="background-color: #059669; color: white; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;" onclick="return confirm('Mark as Dispensed?');">Mark Dispensed</a>
                            <?php else: ?>
                                <a href="auth_process.php?action=toggle_status&id=<?= $rx['prescription_id'] ?>" style="background-color: #dc2626; color: white; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;" onclick="return confirm('Revert to Pending?');">Mark Pending</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>