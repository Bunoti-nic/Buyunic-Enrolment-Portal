<?php
require_once '../includes/functions.php';
require_once '../classes/Email.php';

// Check if already logged in
if (isLoggedIn()) {
    $redirectUrl = $_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'sub_admin' 
        ? BASE_URL . 'admin/dashboard.php' 
        : BASE_URL . 'user/dashboard.php';
    redirect($redirectUrl);
}

$errors = [];
$needsOTP = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        if (isset($_POST['email']) && isset($_POST['password'])) {
            // Step 1: Email and Password
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($email) || empty($password)) {
                $errors[] = 'Please enter both email and password.';
            } elseif (!validateEmail($email)) {
                $errors[] = 'Please enter a valid email address.';
            } else {
                // Check if account is locked
                if (isAccountLocked($email)) {
                    $errors[] = 'Account temporarily locked due to multiple failed login attempts. Please try again later.';
                } else {
                    $user = getUserByEmail($email);
                    
                    if ($user && verifyPassword($password, $user['password_hash'])) {
                        if ($user['status'] !== 'active') {
                            if ($user['email_verified']) {
                                $errors[] = 'Your account is suspended. Please contact support.';
                            } else {
                                $errors[] = 'Please verify your email address before logging in.';
                            }
                        } else {
                            // Check if admin user (requires MFA)
                            if ($user['user_type'] === 'admin' || $user['user_type'] === 'sub_admin') {
                                // Generate and send OTP for admin
                                $otpCode = generateOTP(6);
                                $expiresAt = date('Y-m-d H:i:s', time() + OTP_EXPIRY);
                                
                                try {
                                    $stmt = $pdo->prepare("
                                        INSERT INTO otp_tokens (user_id, otp_code, expires_at, purpose)
                                        VALUES (?, ?, ?, 'login')
                                    ");
                                    $stmt->execute([$user['id'], $otpCode, $expiresAt]);
                                    
                                    // Send OTP email
                                    $emailService = new EmailService();
                                    $emailSent = $emailService->sendOTPEmail(
                                        $user['email'],
                                        $user['first_name'] . ' ' . $user['last_name'],
                                        $otpCode,
                                        'login'
                                    );
                                    
                                    if ($emailSent) {
                                        $_SESSION['login_user_id'] = $user['id'];
                                        $_SESSION['login_email'] = $user['email'];
                                        $needsOTP = true;
                                    } else {
                                        $errors[] = 'Failed to send verification code. Please try again.';
                                    }
                                    
                                } catch (PDOException $e) {
                                    error_log("OTP generation error: " . $e->getMessage());
                                    $errors[] = 'Login failed. Please try again.';
                                }
                            } else {
                                // Regular user login
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['user_type'] = $user['user_type'];
                                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                                $_SESSION['last_activity'] = time();
                                
                                updateLastLogin($user['id']);
                                createUserSession($user['id']);
                                
                                // Create login notification
                                createNotification(
                                    $user['id'],
                                    'Login Successful',
                                    'You have successfully logged into your account.',
                                    'success'
                                );
                                
                                redirect(BASE_URL . 'user/dashboard.php');
                            }
                        }
                    } else {
                        incrementLoginAttempts($email);
                        $errors[] = 'Invalid email or password.';
                    }
                }
            }
            
        } elseif (isset($_POST['otp_code'])) {
            // Step 2: OTP Verification (for admins)
            $otpCode = sanitizeInput($_POST['otp_code']);
            $userId = $_SESSION['login_user_id'] ?? null;
            
            if (empty($otpCode) || !$userId) {
                $errors[] = 'Invalid verification code.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        SELECT ot.*, u.first_name, u.last_name, u.user_type
                        FROM otp_tokens ot
                        JOIN users u ON ot.user_id = u.id
                        WHERE ot.user_id = ? AND ot.otp_code = ? AND ot.used = 0 
                        AND ot.expires_at > NOW() AND ot.purpose = 'login'
                        ORDER BY ot.created_at DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$userId, $otpCode]);
                    $otpData = $stmt->fetch();
                    
                    if ($otpData) {
                        // Mark OTP as used
                        $stmt = $pdo->prepare("UPDATE otp_tokens SET used = 1 WHERE id = ?");
                        $stmt->execute([$otpData['id']]);
                        
                        // Complete login
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['user_type'] = $otpData['user_type'];
                        $_SESSION['user_name'] = $otpData['first_name'] . ' ' . $otpData['last_name'];
                        $_SESSION['last_activity'] = time();
                        
                        // Clean up temporary session data
                        unset($_SESSION['login_user_id'], $_SESSION['login_email']);
                        
                        updateLastLogin($userId);
                        createUserSession($userId);
                        
                        // Log admin login
                        logAdminAction($userId, 'login', 'Admin logged in with MFA');
                        
                        redirect(BASE_URL . 'admin/dashboard.php');
                        
                    } else {
                        $errors[] = 'Invalid or expired verification code.';
                        $needsOTP = true;
                        $email = $_SESSION['login_email'] ?? '';
                    }
                    
                } catch (PDOException $e) {
                    error_log("OTP verification error: " . $e->getMessage());
                    $errors[] = 'Verification failed. Please try again.';
                    $needsOTP = true;
                    $email = $_SESSION['login_email'] ?? '';
                }
            }
        }
    }
}

// Set email from session if in OTP step
if ($needsOTP && isset($_SESSION['login_email'])) {
    $email = $_SESSION['login_email'];
}

$pageTitle = 'Login - BUYUNIC';
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
        
        .otp-input {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            font-weight: bold;
        }
        
        .otp-help {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-graduation-cap"></i> BUYUNIC</h1>
                <p><?php echo $needsOTP ? 'Enter Verification Code' : 'Welcome Back!'; ?></p>
            </div>
            
            <div class="auth-body">
                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul style="margin: 0; padding-left: 1.5rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!$needsOTP): ?>
                    <!-- Step 1: Email and Password -->
                    <form method="POST" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   placeholder="Enter your email" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div style="position: relative;">
                                <input type="password" name="password" id="password" class="form-control" 
                                       placeholder="Enter your password" required>
                                <button type="button" id="togglePassword" 
                                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                                <input type="checkbox" name="remember_me">
                                <span>Remember me</span>
                            </label>
                            <a href="forgot_password.php" style="color: var(--primary-color); font-size: 0.9rem;">Forgot Password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Sign In
                        </button>
                    </form>
                    
                <?php else: ?>
                    <!-- Step 2: OTP Verification -->
                    <p>We've sent a 6-digit verification code to:</p>
                    <p><strong><?php echo htmlspecialchars($email); ?></strong></p>
                    
                    <form method="POST" id="otpForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Verification Code</label>
                            <input type="text" name="otp_code" class="form-control otp-input" 
                                   placeholder="000000" maxlength="6" required
                                   pattern="[0-9]{6}" title="Please enter 6 digits">
                        </div>
                        
                        <div class="otp-help">
                            <i class="fas fa-info-circle"></i>
                            The verification code will expire in 5 minutes. Check your email inbox and spam folder.
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                            <i class="fas fa-shield-alt"></i> Verify & Sign In
                        </button>
                    </form>
                    
                    <div class="back-link">
                        <a href="?">← Back to login</a>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                    <p>Don't have an account? <a href="register.php" style="color: var(--primary-color); font-weight: 500;">Register here</a></p>
                    <p><a href="../index.php" style="color: var(--text-secondary); font-size: 0.9rem;">← Back to Homepage</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
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
        
        // OTP input formatting
        const otpInput = document.querySelector('input[name="otp_code"]');
        if (otpInput) {
            otpInput.addEventListener('input', function() {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Auto-submit when 6 digits entered
                if (this.value.length === 6) {
                    setTimeout(() => {
                        document.getElementById('otpForm').submit();
                    }, 500);
                }
            });
            
            // Auto-focus OTP input
            otpInput.focus();
        }
        
        // Form validation
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            const password = this.querySelector('input[name="password"]').value;
            
            if (!email || !password) {
                e.preventDefault();
                showAlert('Please enter both email and password', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            submitBtn.disabled = true;
            
            // Re-enable if form doesn't submit (validation error)
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 5000);
        });
        
        // Clear any existing sessions on load
        if (!<?php echo $needsOTP ? 'true' : 'false'; ?>) {
            // Clear temporary login session data if not in OTP step
            <?php if (!$needsOTP): ?>
                <?php unset($_SESSION['login_user_id'], $_SESSION['login_email']); ?>
            <?php endif; ?>
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>