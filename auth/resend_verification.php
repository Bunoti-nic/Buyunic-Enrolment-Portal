<?php
require_once '../includes/functions.php';
require_once '../classes/Email.php';

// Check if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'user/dashboard.php');
}

$errors = [];
$success = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email']);
        
        if (empty($email)) {
            $errors[] = 'Please enter your email address.';
        } elseif (!validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if user exists and is unverified
            $user = getUserByEmail($email);
            
            if ($user && !$user['email_verified']) {
                try {
                    // Check if there's already a recent verification token
                    $stmt = $pdo->prepare("
                        SELECT * FROM email_verification_tokens 
                        WHERE user_id = ? AND used = 0 AND expires_at > NOW()
                        ORDER BY created_at DESC LIMIT 1
                    ");
                    $stmt->execute([$user['id']]);
                    $existingToken = $stmt->fetch();
                    
                    if ($existingToken && (time() - strtotime($existingToken['created_at'])) < 300) {
                        // Less than 5 minutes since last token
                        $errors[] = 'A verification email was recently sent. Please wait 5 minutes before requesting another.';
                    } else {
                        // Generate new verification token
                        $verificationToken = generateSecureToken(64);
                        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
                        
                        // Invalidate old tokens first
                        $stmt = $pdo->prepare("UPDATE email_verification_tokens SET used = 1 WHERE user_id = ?");
                        $stmt->execute([$user['id']]);
                        
                        // Insert new token
                        $stmt = $pdo->prepare("
                            INSERT INTO email_verification_tokens (user_id, token, expires_at)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$user['id'], $verificationToken, $expiresAt]);
                        
                        // Send verification email
                        $emailService = new EmailService();
                        $emailSent = $emailService->sendVerificationEmail(
                            $user['email'],
                            $user['first_name'] . ' ' . $user['last_name'],
                            $verificationToken
                        );
                        
                        if ($emailSent) {
                            $success = true;
                            
                            // Create notification
                            createNotification(
                                $user['id'],
                                'Verification Email Sent',
                                'A new verification email has been sent to your email address.',
                                'info'
                            );
                        } else {
                            $errors[] = 'Failed to send verification email. Please try again.';
                        }
                    }
                    
                } catch (PDOException $e) {
                    error_log("Resend verification error: " . $e->getMessage());
                    $errors[] = 'System error. Please try again.';
                }
            } else {
                // Don't reveal if email exists or is already verified (security measure)
                $success = true;
            }
        }
    }
}

$pageTitle = 'Resend Verification - BUYUNIC';
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
        
        .success-message {
            text-align: center;
            padding: 1rem 0;
        }
        
        .success-icon {
            font-size: 3rem;
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
                <p>Resend Verification Email</p>
            </div>
            
            <div class="auth-body">
                <?php if (!$success): ?>
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
                    
                    <p style="text-align: center; margin-bottom: 2rem; color: #6b7280;">
                        Enter your email address and we'll send you a new verification link.
                    </p>
                    
                    <form method="POST" id="resendForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   placeholder="Enter your registered email" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Resend Verification Email
                        </button>
                    </form>
                    
                <?php else: ?>
                    <!-- Success Message -->
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Verification Email Sent!</h3>
                        <p>If an unverified account with that email exists, we've sent a new verification link to:</p>
                        <p><strong><?php echo htmlspecialchars($email); ?></strong></p>
                        <p>Please check your email and follow the instructions to verify your account.</p>
                        
                        <div style="background: #f3f4f6; padding: 1rem; border-radius: 0.5rem; margin: 1.5rem 0; font-size: 0.9rem; color: #6b7280;">
                            <i class="fas fa-info-circle"></i>
                            The verification link will expire in 24 hours. If you don't see the email, check your spam folder.
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                    <p><a href="login.php" style="color: var(--primary-color); font-weight: 500;">← Back to Login</a></p>
                    <p><a href="../index.php" style="color: var(--text-secondary); font-size: 0.9rem;">Back to Homepage</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation and loading state
        document.getElementById('resendForm')?.addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            
            if (!email) {
                e.preventDefault();
                showAlert('Please enter your email address', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Re-enable if form doesn't submit (validation error)
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 5000);
        });
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>