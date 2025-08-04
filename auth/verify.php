<?php
require_once '../includes/functions.php';

$token = sanitizeInput($_GET['token'] ?? '');
$errors = [];
$success = false;

if (empty($token)) {
    $errors[] = 'Invalid verification link.';
} else {
    try {
        // Check if token exists and is valid
        $stmt = $pdo->prepare("
            SELECT vt.*, u.id as user_id, u.first_name, u.last_name, u.email
            FROM email_verification_tokens vt
            JOIN users u ON vt.user_id = u.id
            WHERE vt.token = ? AND vt.used = 0 AND vt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch();
        
        if (!$tokenData) {
            $errors[] = 'Invalid or expired verification link. Please request a new verification email.';
        } else {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE email_verification_tokens SET used = 1 WHERE id = ?");
            $stmt->execute([$tokenData['id']]);
            
            // Activate user account
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, status = 'active' WHERE id = ?");
            $stmt->execute([$tokenData['user_id']]);
            
            // Create welcome notification
            createNotification(
                $tokenData['user_id'],
                'Welcome to BUYUNIC!',
                'Your email has been verified successfully. You can now complete your application.',
                'success',
                BASE_URL . 'user/dashboard.php'
            );
            
            $pdo->commit();
            $success = true;
            
            // Auto-login the user
            $_SESSION['user_id'] = $tokenData['user_id'];
            $_SESSION['user_type'] = 'applicant';
            $_SESSION['last_activity'] = time();
            
            createUserSession($tokenData['user_id']);
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Email verification error: " . $e->getMessage());
        $errors[] = 'Verification failed. Please try again.';
    }
}

$pageTitle = 'Email Verification - BUYUNIC';
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
            text-align: center;
        }
        
        .auth-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
        }
        
        .auth-body {
            padding: 3rem 2rem;
        }
        
        .result-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        
        .success-icon {
            color: var(--success-color);
        }
        
        .error-icon {
            color: var(--danger-color);
        }
        
        .redirect-timer {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><i class="fas fa-graduation-cap"></i> BUYUNIC</h1>
                <p>Email Verification</p>
            </div>
            
            <div class="auth-body">
                <?php if ($success): ?>
                    <div class="result-icon success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Email Verified Successfully!</h2>
                    <p>Welcome to BUYUNIC! Your email has been verified and your account is now active.</p>
                    
                    <div class="redirect-timer">
                        <i class="fas fa-clock"></i> Redirecting to your dashboard in <span id="countdown">5</span> seconds...
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <a href="../user/dashboard.php" class="btn btn-primary btn-large">
                            <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                        </a>
                    </div>
                    
                <?php else: ?>
                    <div class="result-icon error-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h2>Verification Failed</h2>
                    
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
                        <a href="resend_verification.php" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> Resend Verification Email
                        </a>
                        <a href="login.php" class="btn btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-sign-in-alt"></i> Back to Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($success): ?>
    <script>
        // Countdown timer and auto-redirect
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = '../user/dashboard.php';
            }
        }, 1000);
    </script>
    <?php endif; ?>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>