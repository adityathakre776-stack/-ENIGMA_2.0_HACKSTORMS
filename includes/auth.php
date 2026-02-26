<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Auth {
    private Database $db;

    public function __construct() {
        $this->db = db();
    }

    // ---------- Registration ----------
    public function register(array $data): array {
        $email = strtolower(trim($data['email']));
        $name  = trim($data['name']);
        $pass  = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $role  = $data['role'] ?? 'student';

        // Check existing
        $existing = $this->db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered.'];
        }

        $otp = $this->generateOTP();
        $otpExpiry = date('Y-m-d H:i:s', time() + OTP_EXPIRY);

        $id = $this->db->insert(
            "INSERT INTO users (name, email, password, role, otp, otp_expiry, is_verified, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, 0, NOW())",
            [$name, $email, $pass, $role, $otp, $otpExpiry]
        );

        $this->sendOTPEmail($email, $name, $otp);
        $this->logActivity($id, 'registered', 'New account registration');

        return ['success' => true, 'message' => 'OTP sent to email. Please verify.', 'user_id' => $id];
    }

    // ---------- OTP Verification ----------
    public function verifyOTP(string $email, string $otp): array {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND otp = ? AND otp_expiry > NOW()",
            [strtolower($email), $otp]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired OTP.'];
        }

        $this->db->execute(
            "UPDATE users SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE id = ?",
            [$user['id']]
        );

        $this->logActivity($user['id'], 'verified', 'Email OTP verified');
        return ['success' => true, 'message' => 'Email verified successfully!'];
    }

    // ---------- Login ----------
    public function login(string $email, string $password): array {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [strtolower($email)]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }
        if (!$user['is_verified']) {
            return ['success' => false, 'message' => 'Please verify your email first.'];
        }
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Account suspended. Contact admin.'];
        }
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // Create session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email']= $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Update last login
        $this->db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        $this->logActivity($user['id'], 'login', 'User logged in');

        return ['success' => true, 'message' => 'Login successful!', 'role' => $user['role'], 'name' => $user['name']];
    }

    // ---------- Password Reset ----------
    public function requestPasswordReset(string $email): array {
        $user = $this->db->fetchOne("SELECT id, name FROM users WHERE email = ? AND is_verified = 1", [strtolower($email)]);
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found or not verified.'];
        }

        $otp = $this->generateOTP();
        $expiry = date('Y-m-d H:i:s', time() + OTP_EXPIRY);

        $this->db->execute(
            "UPDATE users SET otp = ?, otp_expiry = ? WHERE id = ?",
            [$otp, $expiry, $user['id']]
        );

        $this->sendOTPEmail($email, $user['name'], $otp, 'reset');
        return ['success' => true, 'message' => 'Password reset OTP sent to your email.'];
    }

    public function resetPassword(string $email, string $otp, string $newPassword): array {
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ? AND otp = ? AND otp_expiry > NOW()",
            [strtolower($email), $otp]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired OTP.'];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $this->db->execute(
            "UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE id = ?",
            [$hash, $user['id']]
        );

        $this->logActivity($user['id'], 'password_reset', 'Password reset successfully');
        return ['success' => true, 'message' => 'Password reset successful! Please login.'];
    }

    // ---------- Logout ----------
    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        session_destroy();
    }

    // ---------- Helpers ----------
    public function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    public function isAdmin(): bool {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header('Location: ' . APP_URL . '/auth/login.php');
            exit;
        }
    }

    public function requireAdmin(): void {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        }
    }

    private function generateOTP(): string {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendOTPEmail(string $to, string $name, string $otp, string $type = 'verify'): void {
        $subject = $type === 'reset' 
            ? 'SmartEdge ML - Password Reset OTP'
            : 'SmartEdge ML - Email Verification OTP';

        $message = $this->buildOTPEmailHTML($name, $otp, $type);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";

        @mail($to, $subject, $message, $headers);
    }

    private function buildOTPEmailHTML(string $name, string $otp, string $type): string {
        $action = $type === 'reset' ? 'reset your password' : 'verify your email';
        return "<!DOCTYPE html><html><body style='font-family:sans-serif;background:#0a0a1a;color:#fff;padding:40px'>
        <div style='max-width:480px;margin:auto;background:#1a1a2e;border-radius:16px;padding:40px;border:1px solid #06d6a0'>
        <h2 style='color:#06d6a0;margin:0 0 8px'>SmartEdge ML Sandbox</h2>
        <p>Hi {$name},</p>
        <p>Your OTP to {$action} is:</p>
        <div style='font-size:42px;font-weight:700;letter-spacing:12px;color:#06d6a0;margin:20px 0;text-align:center'>{$otp}</div>
        <p style='color:#aaa;font-size:13px'>This OTP expires in 5 minutes. Do not share it with anyone.</p>
        </div></body></html>";
    }

    public function logActivity(int $userId, string $action, string $details = ''): void {
        try {
            $this->db->execute(
                "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())",
                [$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']
            );
        } catch (Exception $e) {
            // Silently fail logging
        }
    }
}

$auth = new Auth();
