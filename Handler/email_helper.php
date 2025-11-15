<?php
// Helper function để gửi email OTP
// Hỗ trợ cả PHPMailer (SMTP) và mail() function

function sendOtpEmail($recipientEmail, $otpCode, $recipientName = '') {
    // Kiểm tra PHPMailer có được cài đặt không
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        return sendOtpEmailWithPHPMailer($recipientEmail, $otpCode, $recipientName);
    } else {
        return sendOtpEmailWithMailFunction($recipientEmail, $otpCode, $recipientName);
    }
}

/**
 * Gửi OTP sử dụng PHPMailer (khuyên dùng)
 */
function sendOtpEmailWithPHPMailer($recipientEmail, $otpCode, $recipientName = '') {
    try {
        require __DIR__ . '/../vendor/autoload.php';
        
        $emailConfig = require __DIR__ . '/email_config.php';
        
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $emailConfig['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['smtp_username'];
        $mail->Password = $emailConfig['smtp_password'];
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $emailConfig['smtp_port'];
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($recipientEmail, $recipientName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'OTP Reset Password - ChatApp';
        $mail->Body = getEmailHtmlBody($otpCode);
        $mail->AltBody = getEmailPlainTextBody($otpCode);
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (\Exception $e) {
        return ['success' => false, 'message' => 'Email send error: ' . $e->getMessage()];
    }
}

/**
 * Gửi OTP sử dụng hàm mail() của PHP (fallback)
 */
function sendOtpEmailWithMailFunction($recipientEmail, $otpCode, $recipientName = '') {
    try {
        $emailConfig = require __DIR__ . '/email_config.php';
        
        $subject = 'Mã OTP Reset Password - ChatApp';
        $headers = "From: " . $emailConfig['from_email'] . "\r\n";
        $headers .= "Reply-To: " . $emailConfig['from_email'] . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        $body = getEmailHtmlBody($otpCode);
        
        if (mail($recipientEmail, $subject, $body, $headers)) {
            return ['success' => true, 'message' => 'Email sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email using mail() function. Please configure SMTP.'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email send error: ' . $e->getMessage()];
    }
}

/**
 * Lấy nội dung email HTML
 */
function getEmailHtmlBody($otpCode) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; }
            .container { max-width: 500px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            .otp-box { 
                background: #f0f8ff; 
                border: 2px solid #007bff; 
                padding: 20px; 
                text-align: center; 
                margin: 20px 0; 
                border-radius: 5px;
            }
            .otp-code { 
                font-size: 36px; 
                font-weight: bold; 
                color: #007bff; 
                letter-spacing: 5px;
                font-family: 'Courier New', monospace;
            }
            .footer { 
                text-align: center; 
                margin-top: 20px; 
                font-size: 12px; 
                color: #999;
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffc107;
                padding: 10px;
                border-radius: 5px;
                margin: 15px 0;
                color: #856404;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Reset Password - ChatApp</h1>
            </div>
            <div class='content'>
                <p>Xin chào,</p>
                <p>Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản ChatApp của mình. Dưới đây là mã OTP của bạn:</p>
                
                <div class='otp-box'>
                    <div class='otp-code'>" . htmlspecialchars($otpCode) . "</div>
                    <p style='margin: 10px 0; color: #666;'>Mã này sẽ hết hạn sau 15 phút</p>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Lưu ý bảo mật:</strong>
                    <ul style='margin: 10px 0; padding-left: 20px;'>
                        <li>Không bao giờ chia sẻ mã OTP này với bất kỳ ai</li>
                        <li>ChatApp sẽ không bao giờ yêu cầu bạn cung cấp mã này</li>
                        <li>Nếu bạn không yêu cầu reset password, vui lòng bỏ qua email này</li>
                    </ul>
                </div>
                
                <p style='margin-top: 20px;'>
                    <strong>Hướng dẫn:</strong><br>
                    1. Quay lại trang reset password<br>
                    2. Nhập mã OTP ở trên<br>
                    3. Đặt mật khẩu mới
                </p>
                
                <div class='footer'>
                    <p>© 2025 ChatApp. Đây là email tự động, vui lòng không trả lời.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Lấy nội dung email plain text
 */
function getEmailPlainTextBody($otpCode) {
    return "Mã OTP Reset Password: " . $otpCode . "\n\n" .
           "Mã này sẽ hết hạn sau 15 phút.\n\n" .
           "Vui lòng không chia sẻ mã này với bất kỳ ai.\n\n" .
           "Nếu bạn không yêu cầu reset password, vui lòng bỏ qua email này.\n\n" .
           "--- ChatApp";
}
?>
