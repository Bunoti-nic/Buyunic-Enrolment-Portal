<?php
/**
 * Fallback Email Service for BUYUNIC Enrollment Portal
 * Uses PHP's built-in mail() function when PHPMailer is not available
 */

class EmailFallbackService {
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->fromEmail = defined('FROM_EMAIL') ? FROM_EMAIL : 'noreply@buyunic.ug';
        $this->fromName = defined('FROM_NAME') ? FROM_NAME : 'BUYUNIC Enrollment System';
    }
    
    private function sendMail($to, $subject, $htmlBody) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }
    
    public function sendVerificationEmail($userEmail, $userName, $verificationToken) {
        $verificationLink = BASE_URL . 'auth/verify.php?token=' . $verificationToken;
        $subject = 'Verify Your Email - BUYUNIC Enrollment';
        $htmlBody = $this->getVerificationEmailTemplate($userName, $verificationLink);
        return $this->sendMail($userEmail, $subject, $htmlBody);
    }
    
    public function sendOTPEmail($userEmail, $userName, $otpCode, $purpose = 'verification') {
        $subject = 'Your OTP Code - BUYUNIC Enrollment';
        $htmlBody = $this->getLoginOTPTemplate($userName, $otpCode);
        return $this->sendMail($userEmail, $subject, $htmlBody);
    }
    
    public function sendPasswordResetEmail($userEmail, $userName, $resetToken) {
        $resetLink = BASE_URL . 'auth/reset_password.php?token=' . $resetToken;
        $subject = 'Password Reset Request - BUYUNIC Enrollment';
        $htmlBody = $this->getPasswordResetTemplate($userName, $resetLink);
        return $this->sendMail($userEmail, $subject, $htmlBody);
    }
    
    public function sendNotificationEmail($userEmail, $userName, $subject, $message, $actionUrl = null) {
        $htmlBody = $this->getNotificationTemplate($userName, $message, $actionUrl);
        return $this->sendMail($userEmail, $subject, $htmlBody);
    }
    
    public function sendApplicationStatusEmail($userEmail, $userName, $applicationId, $status, $notes = null) {
        $subject = 'Application Status Update - BUYUNIC Enrollment';
        $htmlBody = $this->getApplicationStatusTemplate($userName, $applicationId, $status, $notes);
        return $this->sendMail($userEmail, $subject, $htmlBody);
    }
    
    public function sendPaymentConfirmationEmail($userEmail, $userName, $paymentDetails) {
        $subject = 'Payment Confirmation - BUYUNIC Enrollment';
        $htmlBody = $this->getPaymentConfirmationTemplate($userName, $paymentDetails);
        return $this->sendMail($userEmail, $subject, $htmlBody);
    }
    
    private function getVerificationEmailTemplate($userName, $verificationLink) {
        return "
        <html>
        <head>
            <title>Email Verification - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9fafb; }
                .button { background-color: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; }
                .footer { padding: 20px; text-align: center; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUYUNIC Training Center</h1>
                    <p>Welcome to Our Enrollment Portal</p>
                </div>
                <div class='content'>
                    <h2>Email Verification Required</h2>
                    <p>Dear {$userName},</p>
                    <p>Thank you for registering with BUYUNIC Enrollment Portal. To complete your registration, please verify your email address by clicking the button below:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$verificationLink}' class='button'>Verify My Email</a>
                    </div>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #2563eb; background: #f3f4f6; padding: 10px; border-radius: 4px;'>{$verificationLink}</p>
                    <p><strong>Note:</strong> This verification link will expire in 24 hours for security reasons.</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br><strong>BUYUNIC Team</strong></p>
                    <p>Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | WhatsApp: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getLoginOTPTemplate($userName, $otpCode) {
        return "
        <html>
        <head>
            <title>Login Verification - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .otp-code { background-color: #f3f4f6; border: 2px solid #2563eb; padding: 20px; font-size: 28px; font-weight: bold; letter-spacing: 4px; border-radius: 8px; text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 style='color: #2563eb;'>Login Verification Code</h2>
                <p>Dear {$userName},</p>
                <p>Your verification code for admin login is:</p>
                <div class='otp-code'>{$otpCode}</div>
                <p><strong>Important:</strong> This code will expire in 5 minutes.</p>
                <p>If you didn't request this code, please contact our support team immediately.</p>
                <p>Best regards,<br>BUYUNIC Team</p>
            </div>
        </body>
        </html>";
    }
    
    private function getPasswordResetTemplate($userName, $resetLink) {
        return "
        <html>
        <head>
            <title>Password Reset - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { background-color: #dc2626; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; }
                .warning { background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 style='color: #dc2626;'>Password Reset Request</h2>
                <p>Dear {$userName},</p>
                <p>You requested to reset your password for your BUYUNIC account. Click the button below to create a new password:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetLink}' class='button'>Reset My Password</a>
                </div>
                <div class='warning'>
                    <strong>Security Notice:</strong>
                    <ul>
                        <li>This link will expire in 1 hour</li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>Your password will remain unchanged unless you click the link above</li>
                    </ul>
                </div>
                <p>If you need assistance, please contact our support team.</p>
                <p>Best regards,<br>BUYUNIC Team</p>
            </div>
        </body>
        </html>";
    }
    
    private function getNotificationTemplate($userName, $message, $actionUrl) {
        $actionButton = '';
        if ($actionUrl) {
            $actionButton = "<div style='text-align: center; margin: 30px 0;'><a href='{$actionUrl}' style='background-color: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>View Details</a></div>";
        }
        
        return "
        <html>
        <head>
            <title>Notification - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 style='color: #2563eb;'>BUYUNIC Notification</h2>
                <p>Dear {$userName},</p>
                <div style='background-color: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    {$message}
                </div>
                {$actionButton}
                <p>Best regards,<br>BUYUNIC Team</p>
            </div>
        </body>
        </html>";
    }
    
    private function getApplicationStatusTemplate($userName, $applicationId, $status, $notes) {
        $statusColor = $status === 'approved' ? '#059669' : ($status === 'rejected' ? '#dc2626' : '#2563eb');
        $notesSection = $notes ? "<div style='background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'><strong>Admin Notes:</strong><br>{$notes}</div>" : "";
        
        return "
        <html>
        <head>
            <title>Application Status Update - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .status { color: {$statusColor}; font-weight: bold; font-size: 18px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 style='color: #2563eb;'>Application Status Update</h2>
                <p>Dear {$userName},</p>
                <p>Your application <strong>{$applicationId}</strong> status has been updated to:</p>
                <p class='status'>" . strtoupper($status) . "</p>
                {$notesSection}
                <p>Please log into your account to view more details and take any required actions.</p>
                <p>Best regards,<br>BUYUNIC Team</p>
            </div>
        </body>
        </html>";
    }
    
    private function getPaymentConfirmationTemplate($userName, $paymentDetails) {
        return "
        <html>
        <head>
            <title>Payment Confirmation - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .payment-details { background-color: #f0f9ff; border: 1px solid #2563eb; padding: 20px; border-radius: 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 style='color: #059669;'>Payment Confirmation</h2>
                <p>Dear {$userName},</p>
                <p>Your payment has been successfully confirmed! Here are the details:</p>
                <div class='payment-details'>
                    <h3>Payment Details</h3>
                    <ul>
                        <li><strong>Amount:</strong> UGX " . number_format($paymentDetails['amount']) . "</li>
                        <li><strong>Reference:</strong> {$paymentDetails['reference']}</li>
                        <li><strong>Method:</strong> {$paymentDetails['method']}</li>
                        <li><strong>Status:</strong> Confirmed</li>
                    </ul>
                </div>
                <p>Your application will now proceed to the next stage. You will receive further updates via email.</p>
                <p>Best regards,<br>BUYUNIC Team</p>
            </div>
        </body>
        </html>";
    }
}