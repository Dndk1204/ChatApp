<?php
// Cấu hình gửi email - TEMPLATE
// Nếu file .local tồn tại, dùng nó; nếu không dùng mặc định
if (file_exists(__DIR__ . '/email_config.local.php')) {
    return require __DIR__ . '/email_config.local.php';
}

// Giá trị mặc định (để test, không có thông tin nhạy cảm)
return [
    'smtp_host'     => 'smtp.gmail.com',           // Gmail SMTP host
    'smtp_port'     => 587,                        // TLS port (465 for SSL)
    'smtp_username' => 'deadordie159@gmail.com',   // Your Gmail address
    'smtp_password' => 'uvdn hbxp nmpy djhk',      // App Password or Gmail password
    'from_email'    => 'deadordie159@gmail.com',   // Sender email
    'from_name'     => 'ChatApp',                  // Sender name
];
?>
