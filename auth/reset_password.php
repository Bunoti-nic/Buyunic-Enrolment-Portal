<?php
require_once '../includes/functions.php';

// Check if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'user/dashboard.php');
}

$token = sanitizeInput($_GET['token'] ?? '');
$errors = [];
$success = false;
$tokenValid = false;
$userData = null;

// Validate token first
if (empty($token)) {
    $errors[] = 'Invalid reset link.';
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT rt.*, u.id as user_id, u.first_name, u.last_name, u.email
            FROM password_reset_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.used = 0 AND rt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $userData = $stmt->fetch();
        
        if ($userData) {
            $tokenValid = true;
        } else {
            $errors[] = 'Invalid or expired reset link. Please request a new password reset.';
        }
    } catch (PDOException $e) {
        error_log("Token validation error: " . $e->getMessage());
        $errors[] = 'System error. Please try again.';
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($password) || empty($confirmPassword)) {
            $errors[] = 'Please enter and confirm your new password.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Update password
                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userData['user_id']]);
                
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
                $stmt->execute([$userData['id']]);
                
                // Invalidate all user sessions (force re-login)
                $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE user_id = ?");
                $stmt->execute([$userData['user_id']]);
                
                // Create notification
                createNotification(
                    $userData['user_id'],
                    'Password Reset Successful',
                    'Your password has been successfully reset. Please log in with your new password.',
                    'success'
                );
                
                $pdo->commit();
                $success = true;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Password reset error: " . $e->getMessage());
                $errors[] = 'Password reset failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Reset Password - BUYUNIC';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 1rem;
        }
        
        .auth-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .auth-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .auth-body {
            padding: 2rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .success-message {
            text-align: center;
            padding: 1rem 0;
        }
        
        .success-icon {
            font-size: 3rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .error-icon {
            font-size: 3rem;
            color: var(--danger-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-graduation-cap"></i> BUYUNIC</h1>
                <p>Reset Your Password</p>
            </div>
            
            <div class="auth-body">
                <?php if (!$tokenValid): ?>
                    <!-- Invalid Token -->
                    <div style="text-align: center;">
                        <div class="error-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h3>Invalid Reset Link</h3>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error" style="text-align: left; margin: 1.5rem 0;">
                                <ul style="margin: 0; padding-left: 1.5rem;">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 2rem;">
                            <a href="forgot_password.php" class="btn btn-primary">
                                <i class="fas fa-redo"></i> Request New Reset Link
                            </a>
                        </div>
                    </div>
                    
                <?php elseif ($success): ?>
                    <!-- Success Message -->
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Password Reset Successful!</h3>
                        <p>Your password has been successfully reset.</p>
                        <p>You can now log in with your new password.</p>
                        
                        <div style="margin-top: 2rem;">
                            <a href="login.php" class="btn btn-primary btn-large">
                                <i class="fas fa-sign-in-alt"></i> Login Now
                            </a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Password Reset Form -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul style="margin: 0; padding-left: 1.5rem;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <p style="text-align: center; margin-bottom: 2rem; color: #6b7280;">
                        Resetting password for: <strong><?php echo htmlspecialchars($userData['email']); ?></strong>
                    </p>
                    
                    <form method="POST" id="resetForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <div style="position: relative;">
                                <input type="password" name="password" id="password" class="form-control" 
                                       placeholder="Enter new password" required minlength="8">
                                <button type="button" id="togglePassword" 
                                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthBar"></div>
                                </div>
                                <small id="strengthText" class="text-secondary">Enter a password to see strength</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" 
                                   placeholder="Confirm new password" required>
                            <small id="passwordMatch" class="text-secondary"></small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                            <i class="fas fa-shield-alt"></i> Reset Password
                        </button>
                    </form>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                    <p><a href="login.php" style="color: var(--primary-color); font-weight: 500;">← Back to Login</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength checker
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            if (strength <= 25) {
                strengthBar.style.width = '25%';
                strengthBar.style.background = '#dc2626';
                feedback = 'Weak';
            } else if (strength <= 50) {
                strengthBar.style.width = '50%';
                strengthBar.style.background = '#d97706';
                feedback = 'Fair';
            } else if (strength <= 75) {
                strengthBar.style.width = '75%';
                strengthBar.style.background = '#059669';
                feedback = 'Good';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.style.background = '#059669';
                feedback = 'Strong';
            }
            
            strengthText.textContent = feedback;
        });
        
        // Password visibility toggle
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        // Password confirmation check
        document.getElementById('confirmPassword')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('passwordMatch');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    matchText.textContent = '✓ Passwords match';
                    matchText.style.color = '#059669';
                } else {
                    matchText.textContent = '✗ Passwords do not match';
                    matchText.style.color = '#dc2626';
                }
            } else {
                matchText.textContent = '';
            }
        });
        
        // Form validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showAlert('Passwords do not match', 'error');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showAlert('Password must be at least 8 characters long', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
            submitBtn.disabled = true;
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>