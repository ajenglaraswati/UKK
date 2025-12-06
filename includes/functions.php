<?php
// Include config first
include_once __DIR__ . '/config.php';

/**
 * Check if user is authenticated
 */
function checkAuth() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        header('Location: ../login.php');
        exit();
    }
    return true;
}

/**
 * Check if user is a buyer
 */
function isBuyer() {
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'buyer') {
        return true;
    }
    return false;
}

/**
 * Check if user is an admin
 */
function isAdmin() {
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
        return true;
    }
    return false;
}

/**
 * Check if user is a seller
 */
function isSeller() {
    if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'seller') {
        return true;
    }
    return false;
}

/**
 * Log user activity
 */
function logActivity($user_id, $action, $description = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity (user_id, action, description, ip_address, user_agent) 
            VALUES (:user_id, :action, :description, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Activity log error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    header('Location: ' . $url);
    exit();
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Check password strength
 */
function checkPasswordStrength($password) {
    $strength = 0;
    $messages = [];
    
    // Check length
    if (strlen($password) >= 8) {
        $strength += 25;
    } else {
        $messages[] = 'Password must be at least 8 characters';
    }
    
    // Check uppercase
    if (preg_match('/[A-Z]/', $password)) {
        $strength += 25;
    } else {
        $messages[] = 'Password must contain at least one uppercase letter';
    }
    
    // Check lowercase
    if (preg_match('/[a-z]/', $password)) {
        $strength += 25;
    } else {
        $messages[] = 'Password must contain at least one lowercase letter';
    }
    
    // Check number
    if (preg_match('/[0-9]/', $password)) {
        $strength += 25;
    } else {
        $messages[] = 'Password must contain at least one number';
    }
    
    return [
        'strength' => $strength,
        'messages' => $messages,
        'is_strong' => $strength >= 80
    ];
}

/**
 * Send email notification for password change
 */
function sendPasswordChangeNotification($email, $name) {
    // In production, implement email sending
    // For now, just log it
    error_log("Password change notification would be sent to: $email ($name)");
    return true;
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'USD') {
    $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    return $formatter->formatCurrency($amount, $currency);
}

/**
 * Get user by ID
 */
function getUserById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role, avatar, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Get user error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Check if email exists
 */
function emailExists($email, $exclude_id = null) {
    global $pdo;
    
    try {
        if ($exclude_id) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :exclude_id");
            $stmt->execute([
                ':email' => $email,
                ':exclude_id' => $exclude_id
            ]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
        }
        
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log('Email exists check error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}
?>