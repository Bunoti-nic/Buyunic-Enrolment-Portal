<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Security functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^\+?[0-9\s\-\(\)]{10,15}$/', $phone);
}

// Password functions
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iterations
        'threads' => 3,         // 3 threads
    ]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Session management
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['admin', 'sub_admin'])) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function updateUserSession($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW() 
            WHERE user_id = ? AND session_id = ? AND is_active = 1
        ");
        $stmt->execute([$userId, session_id()]);
    } catch (PDOException $e) {
        error_log("Session update error: " . $e->getMessage());
    }
}

function createUserSession($userId) {
    global $pdo;
    
    try {
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $sessionId, $ipAddress, $userAgent, $expiresAt]);
    } catch (PDOException $e) {
        error_log("Session creation error: " . $e->getMessage());
    }
}

function destroyUserSession($userId = null) {
    global $pdo;
    
    if ($userId) {
        try {
            $stmt = $pdo->prepare("
                UPDATE user_sessions 
                SET is_active = 0 
                WHERE user_id = ? AND session_id = ?
            ");
            $stmt->execute([$userId, session_id()]);
        } catch (PDOException $e) {
            error_log("Session destruction error: " . $e->getMessage());
        }
    }
    
    session_destroy();
}

// User management
function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

function getUserByEmail($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get user by email error: " . $e->getMessage());
        return false;
    }
}

function updateLastLogin($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Update last login error: " . $e->getMessage());
    }
}

function incrementLoginAttempts($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET login_attempts = login_attempts + 1,
                locked_until = CASE 
                    WHEN login_attempts >= ? THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                    ELSE locked_until 
                END
            WHERE email = ?
        ");
        $stmt->execute([MAX_LOGIN_ATTEMPTS, LOCKOUT_TIME, $email]);
    } catch (PDOException $e) {
        error_log("Increment login attempts error: " . $e->getMessage());
    }
}

function isAccountLocked($email) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT locked_until 
            FROM users 
            WHERE email = ? AND locked_until > NOW()
        ");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Check account lock error: " . $e->getMessage());
        return false;
    }
}

// Application management
function generateApplicationId() {
    $date = date('dmy');
    $random = str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    return "A-I-{$date}-{$random}";
}

function createApplication($userId) {
    global $pdo;
    
    try {
        $applicationId = generateApplicationId();
        
        $stmt = $pdo->prepare("
            INSERT INTO applications (user_id, application_id, status)
            VALUES (?, ?, 'draft')
        ");
        $stmt->execute([$userId, $applicationId]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Create application error: " . $e->getMessage());
        return false;
    }
}

function getApplicationByUserId($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get application error: " . $e->getMessage());
        return false;
    }
}

function updateApplicationProgress($applicationId, $progress) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE applications SET progress_percentage = ? WHERE id = ?");
        $stmt->execute([$progress, $applicationId]);
    } catch (PDOException $e) {
        error_log("Update application progress error: " . $e->getMessage());
    }
}

// File upload functions
function uploadFile($file, $applicationId, $documentType) {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed'];
    }
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit (1MB)'];
    }
    
    // Validate file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_FILE_TYPES)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $fileExtension;
    $uploadPath = UPLOAD_PATH . $applicationId . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $filePath = $uploadPath . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // Save to database
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO document_uploads 
                (application_id, document_type, original_filename, stored_filename, file_path, file_size, mime_type)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $applicationId,
                $documentType,
                $file['name'],
                $filename,
                $filePath,
                $file['size'],
                $file['type']
            ]);
            
            return ['success' => true, 'message' => 'File uploaded successfully', 'file_id' => $pdo->lastInsertId()];
        } catch (PDOException $e) {
            error_log("File database save error: " . $e->getMessage());
            unlink($filePath); // Remove uploaded file
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
    
    return ['success' => false, 'message' => 'File upload failed'];
}

// Notification functions
function createNotification($userId, $title, $message, $type = 'info', $actionUrl = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, action_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $message, $type, $actionUrl]);
        return true;
    } catch (PDOException $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}

function getUserNotifications($userId, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get notifications error: " . $e->getMessage());
        return [];
    }
}

function markNotificationAsRead($notificationId, $userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        return true;
    } catch (PDOException $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return false;
    }
}

// Admin logging
function logAdminAction($adminId, $actionType, $description, $targetType = null, $targetId = null, $additionalData = null) {
    global $pdo;
    
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $additionalDataJson = $additionalData ? json_encode($additionalData) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs 
            (admin_id, action_type, action_description, target_type, target_id, ip_address, user_agent, additional_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $adminId, $actionType, $description, $targetType, $targetId, $ipAddress, $userAgent, $additionalDataJson
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Admin log error: " . $e->getMessage());
        return false;
    }
}

// Training programs
function getTrainingPrograms($category = null) {
    global $pdo;
    
    try {
        if ($category) {
            $stmt = $pdo->prepare("SELECT * FROM training_programs WHERE category = ? AND status = 'active' ORDER BY program_name");
            $stmt->execute([$category]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM training_programs WHERE status = 'active' ORDER BY category, program_name");
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get training programs error: " . $e->getMessage());
        return [];
    }
}

function getProgramCategories() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT category FROM training_programs WHERE status = 'active' ORDER BY category");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Get program categories error: " . $e->getMessage());
        return [];
    }
}

// Utility functions
function formatCurrency($amount, $currency = 'UGX') {
    return number_format($amount, 0) . ' ' . $currency;
}

function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'M j, Y g:i A') {
    return date($format, strtotime($datetime));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

function redirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit();
}

function showAlert($message, $type = 'info') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Error handling
function handleException($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    
    if (defined('DEBUG') && DEBUG) {
        echo "Error: " . $exception->getMessage();
    } else {
        echo "An error occurred. Please try again later.";
    }
}

set_exception_handler('handleException');
?>