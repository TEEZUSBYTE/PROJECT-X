<?php
// views/doctor.php - Tabbed Doctor Portal
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    exit();
}
?>

<!-- Greeting Banner -->
<div class="card" style="background: linear-gradient(135deg, #0284c7, #0369a1); color: white; margin-bottom: 20px; border-radius: 10px; padding: 25px;">
    <h2 style="margin: 0 0 5px 0; font-size: 24px;">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>! 🩺</h2>
    <p style="margin: 0; opacity: 0.9; font-size: 15px;">Doctor Portal — Use the interactive tabs below to switch between patient registration and digital prescription issuance.</p>
</div>

<!-- Interactive Navigation Tabs -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
    <button onclick="switchTab('register-panel', this)" class="tab-btn active-tab" style="padding: 10px 20px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
        👤 Register Patient
    </button>
    <button onclick="switchTab('prescription-panel', this)" class="tab-btn" style="padding: 10px 20px; background: #f1f5f9; color: #475569; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
        💊 Issue Prescription
    </button>
</div>

<!-- Panel 1: Register Patient -->
<div id="register-panel" class="tab-panel card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <h3 style="color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-top: 0;">Register New Patient</h3>
    <form action="auth_process.php?action=create_patient" method="POST" style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Full Name</label>
            <input type="text" name="full_name" placeholder="Patient Full Name" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; box-sizing: border-box;">
        </div>

        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Date of Birth</label>
            <input type="date" name="date_of_birth" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; box-sizing: border-box;">
        </div>

        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Gender</label>
            <select name="gender" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; background: white; box-sizing: border-box;">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Contact Number</label>
            <input type="text" name="contact_number" placeholder="e.g., +1234567890" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; box-sizing: border-box;">
        </div>

        <div style="grid-column: span 2;">
            <button type="submit" class="btn-primary" style="padding: 12px 25px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Register Patient Record</button>
        </div>
    </form>
</div>

<!-- Panel 2: Issue Prescription -->
<div id="prescription-panel" class="tab-panel card" style="display: none; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <h3 style="color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-top: 0;">Issue Digital Prescription</h3>
    <form action="auth_process.php?action=create_prescription" method="POST" style="margin-top: 20px; display: flex; flex-direction: column; gap: 15px;">
        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Select Patient</label>
            <select name="patient_id" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; background: white; box-sizing: border-box;">
                <option value="">-- Choose Registered Patient --</option>
                <?php
                try {
                    $patients = $pdo->query("SELECT patient_id, full_name, patient_number FROM patients")->fetchAll();
                    foreach ($patients as $p) {
                        $patNum = $p['patient_number'] ?? ('PAT-' . $p['patient_id']);
                        echo "<option value='{$p['patient_id']}'>" . htmlspecialchars($p['full_name']) . " (" . htmlspecialchars($patNum) . ")</option>";
                    }
                } catch (Exception $e) {
                    echo "<option value=''>Error loading patients</option>";
                }
                ?>
            </select>
        </div>

        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Medication Details</label>
            <textarea name="medication_details" rows="3" placeholder="Enter medicine name and clinical instructions..." required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; resize: vertical; box-sizing: border-box;"></textarea>
        </div>

        <div>
            <label style="font-weight: 600; font-size: 13px; color: #475569;">Dosage Instructions</label>
            <input type="text" name="dosage" placeholder="e.g., 1 tablet twice daily after meals" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 6px; box-sizing: border-box;">
        </div>

        <div>
            <button type="submit" class="btn-primary" style="padding: 12px 25px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Issue Digital Prescription</button>
        </div>
    </form>
</div>

<script>
function switchTab(panelId, btnElement) {
    // Hide all panels
    document.querySelectorAll('.tab-panel').forEach(panel => panel.style.display = 'none');
    // Remove active styles from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.background = '#f1f5f9';
        btn.style.color = '#475569';
    });
    // Show selected panel and highlight button
    document.getElementById(panelId).style.display = 'block';
    btnElement.style.background = '#0284c7';
    btnElement.style.color = 'white';
}
</script>