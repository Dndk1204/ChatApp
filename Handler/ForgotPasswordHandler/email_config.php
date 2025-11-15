<?php
// Cấu hình gửi email - TEMPLATE
// Nếu file .local tồn tại, dùng nó; nếu không dùng mặc định
if (file_exists(__DIR__ . '/email_config.local.php')) {
    return require __DIR__ . '/email_config.local.php';
}

// Giá trị mặc định (để test, không có thông tin nhạy cảm)
return [
    'smtp_host' => 'localhost',                // SMTP server
    'smtp_port' => 587,                        // Port (587 for TLS, 465 for SSL)
    'smtp_username' => '',                     // Email gửi
    'smtp_password' => '',                     // Password hoặc App Password
    'from_email' => '',                        // Email gửi đi
    'from_name' => 'ChatApp',                  // Tên người gửi
];
?>