<?php
// File kiểm tra kết nối email
session_start();

// Kiểm tra xem có PHPMailer chưa
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Configuration Test</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; }
            .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
            .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .code { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
            pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>📧 Email Configuration Test</h1>
        
        <div class="error">
            <h3>❌ PHPMailer Library Not Found</h3>
            <p>Bạn chưa cài đặt thư viện PHPMailer. Vui lòng làm theo hướng dẫn dưới đây:</p>
        </div>
        
        <h2>📥 Hướng dẫn cài đặt PHPMailer</h2>
        
        <h3>Cách 1: Sử dụng Composer (Khuyên dùng)</h3>
        <p>Nếu bạn có Composer đã cài đặt, chạy lệnh sau trong thư mục gốc project:</p>
        <pre>composer require phpmailer/phpmailer</pre>
        
        <h3>Cách 2: Tải về thủ công</h3>
        <ol>
            <li>Truy cập: https://github.com/PHPMailer/PHPMailer/releases</li>
            <li>Tải phiên bản mới nhất (VD: v6.8.1)</li>
            <li>Giải nén vào thư mục: <code>ChatApp/vendor/phpmailer/phpmailer/</code></li>
        </ol>
        
        <h3>Cách 3: Nếu không muốn dùng PHPMailer</h3>
        <p>Bạn có thể dùng hàm <code>mail()</code> của PHP (đơn giản hơn nhưng hạn chế hơn):</p>
        <pre><?php echo htmlspecialchars('// Sửa file: Handler/forgot-password.php
// Thay thế đoạn gửi email thành:
$subject = "Mã OTP Reset Password - ChatApp";
$message = "Mã OTP của bạn là: " . $otp . "\n\nMã này sẽ hết hạn sau 15 phút";
$headers = "From: noreply@chatapp.com\r\nContent-Type: text/plain; charset=UTF-8";

if (mail($email, $subject, $message, $headers)) {
    $_SESSION["otp_email"] = $email;
    $_SESSION["otp_user_id"] = $userId;
    $response["success"] = true;
    $response["message"] = "OTP đã được gửi đến email: " . htmlspecialchars($email);
} else {
    $response["message"] = "Lỗi gửi email. Vui lòng thử lại";
}'); ?></pre>
        
        <div class="warning">
            <strong>⚠️ Lưu ý:</strong> Nếu sử dụng hàm mail(), bạn cần cấu hình mail server trên server. Với XAMPP, có thể không hoạt động. Vì vậy khuyên dùng PHPMailer với SMTP.
        </div>
        
        <h2>🔧 Sau khi cài đặt PHPMailer</h2>
        <ol>
            <li>Mở file: <code>Handler/email_config.php</code></li>
            <li>Cập nhật thông tin SMTP của bạn</li>
            <li>Quay lại trang này để test kết nối</li>
        </ol>
    </body>
    </html>
    <?php
    exit;
}

// Nếu PHPMailer đã cài, kiểm tra cấu hình
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Handler/email_helper.php';

$emailConfig = require __DIR__ . '/../Handler/email_config.php';

// Kiểm tra xem cấu hình có hợp lệ không
$configValid = true;
$configIssues = [];

if ($emailConfig['smtp_username'] === 'your-email@gmail.com') {
    $configValid = false;
    $configIssues[] = "SMTP username chưa được cấu hình (vẫn là giá trị mặc định)";
}

if ($emailConfig['smtp_password'] === 'your-app-password') {
    $configValid = false;
    $configIssues[] = "SMTP password chưa được cấu hình (vẫn là giá trị mặc định)";
}

// Kiểm tra kết nối email
$testEmail = $_POST['test_email'] ?? '';
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($testEmail)) {
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $testResult = ['success' => false, 'message' => 'Email không hợp lệ'];
    } else {
        // Gửi email test
        $testResult = sendOtpEmail($testEmail, '123456', 'Test User');
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; font-family: monospace; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h1, h2, h3 { color: #333; }
        .config-list { background: #f9f9f9; padding: 15px; border-radius: 5px; }
        .config-item { padding: 8px 0; border-bottom: 1px solid #eee; }
        .config-item:last-child { border-bottom: none; }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { padding: 8px; width: 100%; max-width: 400px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>📧 Email Configuration Test</h1>
    
    <?php if (!$configValid): ?>
        <div class="error">
            <h3>❌ Cấu hình chưa hoàn thành</h3>
            <p>Vui lòng cập nhật thông tin SMTP trong file: <code>Handler/email_config.php</code></p>
            <h4>Vấn đề:</h4>
            <ul>
                <?php foreach ($configIssues as $issue): ?>
                    <li><?php echo htmlspecialchars($issue); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="success">
            <h3>✅ Cấu hình hợp lệ</h3>
            <p>Các thông tin SMTP đã được cấu hình. Bạn có thể test gửi email bên dưới.</p>
        </div>
    <?php endif; ?>
    
    <h2>📋 Cấu hình hiện tại</h2>
    <div class="config-list">
        <div class="config-item"><strong>SMTP Host:</strong> <?php echo htmlspecialchars($emailConfig['smtp_host']); ?></div>
        <div class="config-item"><strong>SMTP Port:</strong> <?php echo htmlspecialchars($emailConfig['smtp_port']); ?></div>
        <div class="config-item"><strong>SMTP Username:</strong> <?php echo htmlspecialchars(substr($emailConfig['smtp_username'], 0, 5) . '***'); ?></div>
        <div class="config-item"><strong>From Email:</strong> <?php echo htmlspecialchars($emailConfig['from_email']); ?></div>
        <div class="config-item"><strong>From Name:</strong> <?php echo htmlspecialchars($emailConfig['from_name']); ?></div>
    </div>
    
    <h2>✉️ Test gửi email</h2>
    <form method="POST">
        <div class="form-group">
            <label for="test_email">Nhập email để test:</label>
            <input type="email" id="test_email" name="test_email" placeholder="your-email@gmail.com" required>
        </div>
        <button type="submit">Gửi Email Test</button>
    </form>
    
    <?php if ($testResult): ?>
        <?php if ($testResult['success']): ?>
            <div class="success">
                <h3>✅ Gửi email thành công!</h3>
                <p><?php echo htmlspecialchars($testResult['message']); ?></p>
                <p>Hãy kiểm tra hộp thư đến (hoặc spam) của email trên.</p>
            </div>
        <?php else: ?>
            <div class="error">
                <h3>❌ Lỗi gửi email</h3>
                <p><?php echo htmlspecialchars($testResult['message']); ?></p>
                <p>Vui lòng kiểm tra cấu hình SMTP trong file <code>Handler/email_config.php</code></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <h2>🔧 Cách cấu hình cho các nhà cung cấp email phổ biến</h2>
    
    <h3>📧 Gmail</h3>
    <div class="info">
        <ol>
            <li>Vào <a href="https://myaccount.google.com/" target="_blank">https://myaccount.google.com/</a></li>
            <li>Chọn <strong>Bảo mật</strong> (bên trái)</li>
            <li>Kéo xuống tìm <strong>Mật khẩu ứng dụng</strong></li>
            <li>Nếu chưa bật 2FA, phải bật trước</li>
            <li>Chọn <strong>Mail</strong> và <strong>Windows Computer</strong></li>
            <li>Copy mật khẩu ứng dụng (16 ký tự)</li>
            <li>Cập nhật vào file config:
                <pre><?php echo htmlspecialchars("'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@gmail.com',
'smtp_password' => 'xxxx xxxx xxxx xxxx', // Mật khẩu ứng dụng"); ?></pre>
            </li>
        </ol>
    </div>
    
    <h3>💼 Outlook/Hotmail</h3>
    <div class="info">
        <pre><?php echo htmlspecialchars("'smtp_host' => 'smtp.office365.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@outlook.com',
'smtp_password' => 'your-password',"); ?></pre>
    </div>
    
    <h3>🏢 Yahoo</h3>
    <div class="info">
        <pre><?php echo htmlspecialchars("'smtp_host' => 'smtp.mail.yahoo.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@yahoo.com',
'smtp_password' => 'your-password',"); ?></pre>
    </div>
    
    <h3>🔒 Hosting với cPanel (Shared Hosting)</h3>
    <div class="info">
        <p>Thường cấu hình như sau:</p>
        <pre><?php echo htmlspecialchars("'smtp_host' => 'mail.yourdomain.com', // Hoặc localhost
'smtp_port' => 587, // Hoặc 465, 25
'smtp_username' => 'email@yourdomain.com',
'smtp_password' => 'password-của-email-đó',"); ?></pre>
        <p><strong>Liên hệ nhà cung cấp hosting để được hỗ trợ cấu hình SMTP chính xác.</strong></p>
    </div>
    
    <h2>❓ Câu hỏi thường gặp</h2>
    <div class="warning">
        <h3>1. Email không gửi được?</h3>
        <ul>
            <li>Kiểm tra username và password có chính xác không</li>
            <li>Kiểm tra firewall/antivirus có chặn port 587 hoặc 465 không</li>
            <li>Kiểm tra cấu hình SMTP có hợp lệ không (host, port, encryption)</li>
        </ul>
    </div>
    
    <div class="warning">
        <h3>2. Muốn xóa chức năng gửi email?</h3>
        <p>Chỉnh sửa file <code>Handler/forgot-password.php</code> và comment dòng:</p>
        <pre><?php echo htmlspecialchars('require_once "../Handler/email_helper.php";'); ?></pre>
    </div>
    
    <div class="warning">
        <h3>3. Lỗi "Use of unknown class PHPMailer"?</h3>
        <p>PHPMailer chưa được cài. Chạy lệnh: <code>composer require phpmailer/phpmailer</code></p>
    </div>
</body>
</html>
