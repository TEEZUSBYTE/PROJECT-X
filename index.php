<?php
// Ensure session is started cleanly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Prescription & Tracking System - Hospital Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            display: flex;
            min-height: 100vh;
        }

        /* Split Screen Architecture */
        .portal-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Hero Panel (Hospital Imagery & Branding) */
        .hero-panel {
            flex: 1.2;
            background: linear-gradient(135deg, rgba(2, 132, 199, 0.9), rgba(3, 105, 161, 0.95)), 
                        url('https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 60px;
        }
        .hero-brand {
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.9;
        }
        .hero-content h1 {
            font-size: 42px;
            line-height: 1.2;
            margin-bottom: 20px;
            font-weight: 800;
        }
        .hero-content p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.9;
            max-width: 500px;
        }
        .hero-footer {
            font-size: 13px;
            opacity: 0.8;
        }

        /* Right Authentication Panel */
        .auth-panel {
            flex: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
        .login-card h2 {
            margin-top: 0;
            color: #0f172a;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .login-card p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            transition: all 0.2s ease;
            outline: none;
        }
        .form-group input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #0284c7;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: #0369a1;
        }
        .btn-login:active {
            transform: scale(0.98);
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #fca5a5;
        }

        /* Logged-in Dashboard Layout */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            box-sizing: border-box;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }

        /* Responsive Breakpoint for Mobile Screens */
        @media (max-width: 900px) {
            .portal-container {
                flex-direction: column;
            }
            .hero-panel {
                display: none; /* Hides the side image on mobile devices to optimize UX */
            }
            .auth-panel {
                min-height: 100vh;
            }
        }
    </style>
</head>
<body>
    <div class="<?= !isset($_SESSION['user_id']) ? 'portal-container' : 'container' ?>">
        <?php if (!isset($_SESSION['user_id'])): ?>
            
            <!-- LEFT HERO PANEL (Hospital Visual & Branding) -->
            <div class="hero-panel">
                <div class="hero-brand">🏥 Secure Clinical Gateway</div>
                <div class="hero-content">
                    <h1>Digital Prescription & Tracking System</h1>
                    <p>Advanced medical workflow coordination empowering physicians, administrative personnel, and pharmacists with secure, real-time prescription tracking and patient care logging.</p>
                </div>
                <div class="hero-footer">
                    &copy; <?= date('Y') ?> Hospital Information Systems Directorate. Authorized Personnel Only.
                </div>
            </div>

            <!-- RIGHT AUTHENTICATION PANEL -->
            <div class="auth-panel">
                <div class="login-card">
                    <h2>Staff Portal</h2>
                    <p>Enter your credentials to access your secure workspace.</p>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
                    <?php endif; ?>

                    <form action="auth_process.php?action=login" method="POST" autocomplete="off">
                        <div class="form-group">
                            <label>Staff ID</label>
                            <input type="text" name="staff_id" required autocomplete="off" placeholder="e.g. DOC-101">
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required autocomplete="new-password" placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn-login">Sign In to Dashboard</button>
                    </form>

                    <div style="margin-top: 25px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                        Forgot your password? <br>Please contact your <b>System Administrator</b> for a credential reset.
                    </div>
                </div>
            </div>

        <?php else: ?>
            
            <!-- LOGGED-IN DASHBOARD HEADER -->
            <header class="dashboard-header">
                <div>
                    <h2 style="font-size: 20px; color: #1e293b; margin: 0 0 4px 0;">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?></h2>
                    <span style="font-size: 13px; color: #0284c7; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Role: <?= htmlspecialchars($_SESSION['role']) ?></span>
                </div>
                <a href="auth_process.php?action=logout" style="background-color: #fee2e2; color: #dc2626; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; transition: background 0.2s;">Logout</a>
            </header>

            <?php if (isset($_GET['success'])): ?>
                <div style="background: #dcfce7; color: #166534; padding: 12px 18px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; font-weight: 500; border: 1px solid #bbf7d0;">
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>

            <!-- DYNAMIC ROLE VIEW LOADER -->
            <?php 
            $role = $_SESSION['role'] ?? '';
            $targetPath = "";

            if ($role === 'Doctor') {
                $targetPath = "views/doctor.php";
            } elseif ($role === 'Pharmacist') {
                $targetPath = "views/pharmacist.php";
            } elseif (in_array($role, ['Admin', 'Junior Admin'])) {
                $targetPath = "views/admin.php";
            }

            if (!empty($targetPath) && file_exists($targetPath)) {
                include $targetPath;
            } else {
                echo "<div class='card' style='background: white; padding: 25px; border-radius: 10px; color: #dc2626; border: 1px solid #fca5a5;'>Configuration Error: View file for role <b>" . htmlspecialchars($role) . "</b> could not be located.</div>";
            }
            ?>
        <?php endif; ?>
    </div>
</body>
</html>