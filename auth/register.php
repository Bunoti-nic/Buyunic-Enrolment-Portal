<?php
require_once '../includes/functions.php';
require_once '../classes/Email.php';

// Check if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'user/dashboard.php');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        if ($step === 1) {
            // Step 1: Basic Information
            $firstName = sanitizeInput($_POST['first_name']);
            $lastName = sanitizeInput($_POST['last_name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validation
            if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
                $errors[] = 'All fields are required.';
            }
            
            if (!validateEmail($email)) {
                $errors[] = 'Please enter a valid email address.';
            }
            
            if (!validatePhone($phone)) {
                $errors[] = 'Please enter a valid phone number.';
            }
            
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long.';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }
            
            // Check if user already exists
            if (empty($errors)) {
                $existingUser = getUserByEmail($email);
                if ($existingUser) {
                    $errors[] = 'An account with this email already exists.';
                }
            }
            
            if (empty($errors)) {
                // Store step 1 data in session
                $_SESSION['registration_step1'] = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password
                ];
                
                redirect('register.php?step=2');
            }
            
        } elseif ($step === 2) {
            // Step 2: Terms and Conditions
            $termsAccepted = isset($_POST['terms_conditions']);
            $privacyAccepted = isset($_POST['privacy_policy']);
            $dataProcessingAccepted = isset($_POST['data_processing']);
            $marketingConsent = isset($_POST['marketing_communications']);
            
            if (!$termsAccepted || !$privacyAccepted || !$dataProcessingAccepted) {
                $errors[] = 'You must accept the Terms & Conditions, Privacy Policy, and Data Processing agreement to continue.';
            }
            
            if (empty($errors) && isset($_SESSION['registration_step1'])) {
                $step1Data = $_SESSION['registration_step1'];
                
                try {
                    $pdo->beginTransaction();
                    
                    // Create user account
                    $hashedPassword = hashPassword($step1Data['password']);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, status)
                        VALUES (?, ?, ?, ?, ?, 'applicant', 'pending')
                    ");
                    $stmt->execute([
                        $step1Data['email'],
                        $hashedPassword,
                        $step1Data['first_name'],
                        $step1Data['last_name'],
                        $step1Data['phone']
                    ]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    // Generate verification token
                    $verificationToken = generateSecureToken(64);
                    $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO email_verification_tokens (user_id, token, expires_at)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$userId, $verificationToken, $expiresAt]);
                    
                    // Create initial application
                    $applicationId = createApplication($userId);
                    
                    // Store consent records
                    $stmt = $pdo->prepare("
                        INSERT INTO consents (application_id, terms_conditions, privacy_policy, data_processing, marketing_communications, ip_address, user_agent)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $applicationId,
                        $termsAccepted,
                        $privacyAccepted,
                        $dataProcessingAccepted,
                        $marketingConsent,
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    $pdo->commit();
                    
                    // Send verification email
                    $emailService = new EmailService();
                    $emailSent = $emailService->sendVerificationEmail(
                        $step1Data['email'],
                        $step1Data['first_name'] . ' ' . $step1Data['last_name'],
                        $verificationToken
                    );
                    
                    if ($emailSent) {
                        // Clean up session data
                        unset($_SESSION['registration_step1']);
                        
                        $_SESSION['registration_email'] = $step1Data['email'];
                        $success = true;
                    } else {
                        $errors[] = 'Account created but failed to send verification email. Please contact support.';
                    }
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Registration error: " . $e->getMessage());
                    $errors[] = 'Registration failed. Please try again.';
                }
            } else {
                $errors[] = 'Session expired. Please start registration again.';
                redirect('register.php?step=1');
            }
        }
    }
}

// Get form data for current step
$formData = [];
if ($step === 1) {
    $formData = $_SESSION['registration_step1'] ?? [];
} elseif ($step === 2 && !isset($_SESSION['registration_step1'])) {
    redirect('register.php?step=1');
}

$pageTitle = 'Register - BUYUNIC';
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
            max-width: 500px;
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
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: var(--primary-color);
            color: white;
        }
        
        .step.completed {
            background: var(--success-color);
            color: white;
        }
        
        .step.inactive {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .step-line {
            width: 60px;
            height: 2px;
            background: #e5e7eb;
            margin-top: 19px;
        }
        
        .step-line.completed {
            background: var(--success-color);
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
        
        .terms-section {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f9fafb;
        }
        
        .success-message {
            text-align: center;
            padding: 2rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-graduation-cap"></i> BUYUNIC</h1>
                <p>Create your account to start your IT training journey</p>
            </div>
            
            <div class="auth-body">
                <?php if (!$success): ?>
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'inactive'; ?>">1</div>
                        <div class="step-line <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
                        <div class="step <?php echo $step >= 2 ? 'active' : 'inactive'; ?>">2</div>
                    </div>
                    
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
                    
                    <?php if ($step === 1): ?>
                        <!-- Step 1: Basic Information -->
                        <h2>Personal Information</h2>
                        <p class="text-secondary">Please provide your basic information to create your account.</p>
                        
                        <form method="POST" id="registerForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-group">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" 
                                       placeholder="+256 XXX XXX XXX" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Password *</label>
                                <div style="position: relative;">
                                    <input type="password" name="password" id="password" class="form-control" 
                                           required minlength="8">
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
                                <label class="form-label">Confirm Password *</label>
                                <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required>
                                <small id="passwordMatch" class="text-secondary"></small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                                Continue <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                        
                    <?php elseif ($step === 2): ?>
                        <!-- Step 2: Terms and Conditions -->
                        <h2>Terms & Privacy</h2>
                        <p class="text-secondary">Please review and accept our terms to complete your registration.</p>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="terms-section">
                                <h4>Terms & Conditions</h4>
                                <p>By creating an account with BUYUNIC, you agree to the following terms:</p>
                                <ul>
                                    <li>You will provide accurate and complete information</li>
                                    <li>You are responsible for maintaining the security of your account</li>
                                    <li>You agree to comply with all program requirements and deadlines</li>
                                    <li>Payment terms and refund policies as outlined in our fee structure</li>
                                    <li>Respectful conduct towards staff and fellow students</li>
                                </ul>
                                
                                <h4>Privacy Policy</h4>
                                <p>Your privacy is important to us. We collect and use your information to:</p>
                                <ul>
                                    <li>Process your application and enrollment</li>
                                    <li>Communicate with you about your training</li>
                                    <li>Provide support and respond to inquiries</li>
                                    <li>Improve our services and programs</li>
                                </ul>
                                
                                <h4>Data Processing</h4>
                                <p>We process your personal data in accordance with applicable data protection laws. Your data will be stored securely and only accessed by authorized personnel.</p>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                    <input type="checkbox" name="terms_conditions" required>
                                    <span>I accept the <strong>Terms & Conditions</strong> *</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                    <input type="checkbox" name="privacy_policy" required>
                                    <span>I accept the <strong>Privacy Policy</strong> *</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                    <input type="checkbox" name="data_processing" required>
                                    <span>I consent to <strong>Data Processing</strong> *</span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: flex-start; gap: 0.5rem;">
                                    <input type="checkbox" name="marketing_communications">
                                    <span>I consent to receive marketing communications (optional)</span>
                                </label>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <a href="register.php?step=1" class="btn btn-secondary" style="width: 100%;">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                                <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                                    Create Account <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- Success Message -->
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2>Registration Successful!</h2>
                        <p>Thank you for registering with BUYUNIC. We've sent a verification email to:</p>
                        <p><strong><?php echo htmlspecialchars($_SESSION['registration_email'] ?? ''); ?></strong></p>
                        <p>Please check your email and click the verification link to activate your account.</p>
                        
                        <div style="margin-top: 2rem;">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Go to Login
                            </a>
                        </div>
                        
                        <p style="margin-top: 1rem; font-size: 0.9rem; color: #6b7280;">
                            Didn't receive the email? Check your spam folder or 
                            <a href="resend_verification.php" style="color: var(--primary-color);">resend verification email</a>
                        </p>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                    <p>Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: 500;">Sign in here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
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
        document.getElementById('togglePassword').addEventListener('click', function() {
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
        document.getElementById('confirmPassword').addEventListener('input', function() {
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
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
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
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>

<?php
// Clean up session data if success
if ($success) {
    unset($_SESSION['registration_email']);
}
?>