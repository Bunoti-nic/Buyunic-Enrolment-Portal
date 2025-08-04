<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->setupSMTP();
    }
    
    private function setupSMTP() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            
            // Default sender
            $this->mailer->setFrom(FROM_EMAIL, FROM_NAME);
            
            // Enable HTML
            $this->mailer->isHTML(true);
            
        } catch (Exception $e) {
            error_log("Email setup error: " . $e->getMessage());
        }
    }
    
    public function sendVerificationEmail($userEmail, $userName, $verificationToken) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            
            $verificationLink = BASE_URL . "auth/verify.php?token=" . $verificationToken;
            
            $this->mailer->Subject = 'BUYUNIC - Email Verification Required';
            $this->mailer->Body = $this->getVerificationEmailTemplate($userName, $verificationLink);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendOTPEmail($userEmail, $userName, $otpCode, $purpose = 'verification') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            
            $subject = '';
            $template = '';
            
            switch ($purpose) {
                case 'login':
                    $subject = 'BUYUNIC - Login Verification Code';
                    $template = $this->getLoginOTPTemplate($userName, $otpCode);
                    break;
                case 'password_reset':
                    $subject = 'BUYUNIC - Password Reset Code';
                    $template = $this->getPasswordResetOTPTemplate($userName, $otpCode);
                    break;
                default:
                    $subject = 'BUYUNIC - Verification Code';
                    $template = $this->getVerificationOTPTemplate($userName, $otpCode);
            }
            
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $template;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("OTP email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendPasswordResetEmail($userEmail, $userName, $resetToken) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            
            $resetLink = BASE_URL . "auth/reset_password.php?token=" . $resetToken;
            
            $this->mailer->Subject = 'BUYUNIC - Password Reset Request';
            $this->mailer->Body = $this->getPasswordResetEmailTemplate($userName, $resetLink);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Password reset email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendNotificationEmail($userEmail, $userName, $subject, $message, $actionUrl = null) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            
            $this->mailer->Subject = 'BUYUNIC - ' . $subject;
            $this->mailer->Body = $this->getNotificationEmailTemplate($userName, $subject, $message, $actionUrl);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Notification email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendApplicationStatusEmail($userEmail, $userName, $applicationId, $status, $notes = null) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            
            $statusMessages = [
                'submitted' => 'Application Submitted Successfully',
                'under_review' => 'Application Under Review',
                'approved' => 'Application Approved - Congratulations!',
                'rejected' => 'Application Update Required',
                'returned_for_edit' => 'Application Returned for Editing'
            ];
            
            $subject = $statusMessages[$status] ?? 'Application Status Update';
            
            $this->mailer->Subject = 'BUYUNIC - ' . $subject;
            $this->mailer->Body = $this->getApplicationStatusEmailTemplate($userName, $applicationId, $status, $subject, $notes);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Application status email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendPaymentConfirmationEmail($userEmail, $userName, $paymentDetails) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($userEmail, $userName);
            
            $this->mailer->Subject = 'BUYUNIC - Payment Confirmation';
            $this->mailer->Body = $this->getPaymentConfirmationTemplate($userName, $paymentDetails);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Payment confirmation email error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getVerificationEmailTemplate($userName, $verificationLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Email Verification - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f9fafb; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #111827; color: white; padding: 20px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUYUNIC Enrollment Portal</h1>
                </div>
                <div class='content'>
                    <h2>Welcome, {$userName}!</h2>
                    <p>Thank you for registering with BUYUNIC. To complete your registration and start your application process, please verify your email address by clicking the button below:</p>
                    
                    <a href='{$verificationLink}' class='button'>Verify Email Address</a>
                    
                    <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #666;'>{$verificationLink}</p>
                    
                    <p>This verification link will expire in 24 hours for security reasons.</p>
                    
                    <p>If you didn't create an account with BUYUNIC, please ignore this email.</p>
                    
                    <p>Best regards,<br>BUYUNIC Team</p>
                </div>
                <div class='footer'>
                    <p>BUYUNIC - Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | Phone: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getLoginOTPTemplate($userName, $otpCode) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Login Verification - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f9fafb; }
                .otp-code { background: #e2e8f0; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; margin: 20px 0; border-radius: 5px; }
                .footer { background: #111827; color: white; padding: 20px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUYUNIC Login Verification</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$userName}!</h2>
                    <p>You've requested to log in to your BUYUNIC account. Please use the following verification code:</p>
                    
                    <div class='otp-code'>{$otpCode}</div>
                    
                    <p>This code will expire in 5 minutes for security reasons.</p>
                    
                    <p>If you didn't attempt to log in, please contact us immediately.</p>
                    
                    <p>Best regards,<br>BUYUNIC Team</p>
                </div>
                <div class='footer'>
                    <p>BUYUNIC - Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | Phone: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getVerificationOTPTemplate($userName, $otpCode) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Verification Code - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f9fafb; }
                .otp-code { background: #e2e8f0; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; margin: 20px 0; border-radius: 5px; }
                .footer { background: #111827; color: white; padding: 20px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUYUNIC Verification</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$userName}!</h2>
                    <p>Please use the following verification code to continue:</p>
                    
                    <div class='otp-code'>{$otpCode}</div>
                    
                    <p>This code will expire in 5 minutes.</p>
                    
                    <p>Best regards,<br>BUYUNIC Team</p>
                </div>
                <div class='footer'>
                    <p>BUYUNIC - Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | Phone: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getPasswordResetOTPTemplate($userName, $otpCode) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f9fafb; }
                .otp-code { background: #e2e8f0; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 3px; margin: 20px 0; border-radius: 5px; }
                .footer { background: #111827; color: white; padding: 20px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUYUNIC Password Reset</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$userName}!</h2>
                    <p>You've requested to reset your password. Please use the following verification code:</p>
                    
                    <div class='otp-code'>{$otpCode}</div>
                    
                    <p>This code will expire in 5 minutes for security reasons.</p>
                    
                    <p>If you didn't request a password reset, please ignore this email.</p>
                    
                    <p>Best regards,<br>BUYUNIC Team</p>
                </div>
                <div class='footer'>
                    <p>BUYUNIC - Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | Phone: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getPasswordResetEmailTemplate($userName, $resetLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f9fafb; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #111827; color: white; padding: 20px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUYUNIC Password Reset</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$userName}!</h2>
                    <p>You've requested to reset your password. Click the button below to create a new password:</p>
                    
                    <a href='{$resetLink}' class='button'>Reset Password</a>
                    
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; color: #666;'>{$resetLink}</p>
                    
                    <p>This link will expire in 1 hour for security reasons.</p>
                    
                    <p>If you didn't request a password reset, please ignore this email.</p>
                    
                    <p>Best regards,<br>BUYUNIC Team</p>
                </div>
                <div class='footer'>
                    <p>BUYUNIC - Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | Phone: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getNotificationEmailTemplate($userName, $subject, $message, $actionUrl = null) {
        $actionButton = '';
        if ($actionUrl) {
            $actionButton = "<a href='{$actionUrl}' class='button'>View Details</a>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject} - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f9fafb; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #111827; color: white; padding: 20px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUYUNIC Notification</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$userName}!</h2>
                    <h3>{$subject}</h3>
                    <p>{$message}</p>
                    
                    {$actionButton}
                    
                    <p>Best regards,<br>BUYUNIC Team</p>
                </div>
                <div class='footer'>
                    <p>BUYUNIC - Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | Phone: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getApplicationStatusEmailTemplate($userName, $applicationId, $status, $subject, $notes = null) {
        $statusContent = '';
        $actionUrl = BASE_URL . 'user/dashboard.php';
        
        switch ($status) {
            case 'submitted':
                $statusContent = "
                    <p>Your application has been successfully submitted and is now in our system.</p>
                    <p><strong>Application ID:</strong> {$applicationId}</p>
                    <p>Our admissions team will review your application and get back to you soon.</p>
                ";
                break;
                
            case 'under_review':
                $statusContent = "
                    <p>Your application is currently under review by our admissions team.</p>
                    <p><strong>Application ID:</strong> {$applicationId}</p>
                    <p>We will notify you once the review process is complete.</p>
                ";
                break;
                
            case 'approved':
                $statusContent = "
                    <p>Congratulations! Your application has been approved.</p>
                    <p><strong>Application ID:</strong> {$applicationId}</p>
                    <p>You can now proceed with the enrollment process. Please log in to your dashboard for next steps.</p>
                ";
                break;
                
            case 'rejected':
                $statusContent = "
                    <p>We regret to inform you that your application requires updates before it can be processed.</p>
                    <p><strong>Application ID:</strong> {$applicationId}</p>
                    <p>Please review the feedback provided and resubmit your application.</p>
                ";
                if ($notes) {
                    $statusContent .= "<p><strong>Admin Notes:</strong> {$notes}</p>";
                }
                break;
                
            case 'returned_for_edit':
                $statusContent = "
                    <p>Your application has been returned for editing.</p>
                    <p><strong>Application ID:</strong> {$applicationId}</p>
                    <p>Please make the necessary changes and resubmit your application.</p>
                ";
                if ($notes) {
                    $statusContent .= "<p><strong>Required Changes:</strong> {$notes}</p>";
                }
                break;
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject} - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f9fafb; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #111827; color: white; padding: 20px; text-align: center; font-size: 14px; }
                .status-box { background: #e2e8f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUYUNIC Application Update</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$userName}!</h2>
                    <h3>{$subject}</h3>
                    
                    <div class='status-box'>
                        {$statusContent}
                    </div>
                    
                    <a href='{$actionUrl}' class='button'>View Dashboard</a>
                    
                    <p>If you have any questions, please don't hesitate to contact us.</p>
                    
                    <p>Best regards,<br>BUYUNIC Admissions Team</p>
                </div>
                <div class='footer'>
                    <p>BUYUNIC - Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | Phone: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getPaymentConfirmationTemplate($userName, $paymentDetails) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Payment Confirmation - BUYUNIC</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #059669; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background: #f9fafb; }
                .payment-details { background: #e2e8f0; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #111827; color: white; padding: 20px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✓ Payment Confirmed</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$userName}!</h2>
                    <p>We have successfully received your payment. Thank you!</p>
                    
                    <div class='payment-details'>
                        <h3>Payment Details:</h3>
                        <p><strong>Amount:</strong> {$paymentDetails['amount']} {$paymentDetails['currency']}</p>
                        <p><strong>Reference:</strong> {$paymentDetails['reference']}</p>
                        <p><strong>Method:</strong> {$paymentDetails['method']}</p>
                        <p><strong>Date:</strong> {$paymentDetails['date']}</p>
                        <p><strong>Application ID:</strong> {$paymentDetails['application_id']}</p>
                    </div>
                    
                    <a href='" . BASE_URL . "user/dashboard.php' class='button'>View Dashboard</a>
                    
                    <p>You can download your receipt from your dashboard.</p>
                    
                    <p>Best regards,<br>BUYUNIC Finance Team</p>
                </div>
                <div class='footer'>
                    <p>BUYUNIC - Plot 28, North Road, Northern City Division, Mbale City</p>
                    <p>Email: info@buyunic.ug | Phone: +256 207 901 434</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>