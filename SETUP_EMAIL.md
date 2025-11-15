# 📧 Hướng Dẫn Cài Đặt Gửi Email OTP

## 📝 Tổng Quan
Chức năng quên mật khẩu sẽ gửi mã OTP 6 chữ số qua email thực tế của người dùng.

## 🚀 Cài Đặt Nhanh

### Bước 1: Cài đặt PHPMailer (Khuyên dùng)
```bash
composer require phpmailer/phpmailer
```

**Hoặc** tải thủ công từ: https://github.com/PHPMailer/PHPMailer/releases

### Bước 2: Cấu Hình Email
Mở file `Handler/email_config.php` và cập nhật thông tin SMTP:

```php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => 'your-app-password',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'ChatApp',
];
```

### Bước 3: Test Email
Truy cập: `http://localhost/ChatApp/Handler/test_email_config.php`

Nhập email test và click "Gửi Email Test"

## 🔧 Cấu Hình Cho Các Nhà Cung Cấp Email

### 📧 Gmail
1. Vào https://myaccount.google.com/
2. **Bảo mật** → **Mật khẩu ứng dụng**
3. Chọn **Mail** và **Windows Computer**
4. Copy mật khẩu (16 ký tự)

```php
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@gmail.com',
'smtp_password' => 'xxxx xxxx xxxx xxxx',
```

### 💼 Outlook/Hotmail
```php
'smtp_host' => 'smtp.office365.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@outlook.com',
'smtp_password' => 'your-password',
```

### 🟠 Yahoo
```php
'smtp_host' => 'smtp.mail.yahoo.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@yahoo.com',
'smtp_password' => 'your-password',
```

### 🏢 Hosting Riêng / cPanel
```php
'smtp_host' => 'mail.yourdomain.com', // hoặc localhost
'smtp_port' => 587, // hoặc 465, 25
'smtp_username' => 'email@yourdomain.com',
'smtp_password' => 'email-password',
```

## 📱 Các File Liên Quan

| File | Mô tả |
|------|-------|
| `Handler/email_config.php` | Cấu hình SMTP (bạn phải sửa) |
| `Handler/email_helper.php` | Hàm gửi email |
| `Handler/forgot-password.php` | API tạo OTP |
| `Handler/verify-otp.php` | API xác nhận OTP |
| `Handler/reset-password.php` | API đặt mật khẩu mới |
| `Pages/forgot-password.php` | Giao diện quên mật khẩu |
| `Handler/test_email_config.php` | Trang test email |

## 🔗 API Documentation

### 1. Gửi OTP - `Handler/forgot-password.php`
```
POST /Handler/forgot-password.php
Body: { email: "user@gmail.com" }

Response (Success):
{
  "success": true,
  "message": "OTP đã được gửi đến email: user@gmail.com"
}

Response (Error):
{
  "success": false,
  "message": "Email không tồn tại..."
}
```

### 2. Xác Nhận OTP - `Handler/verify-otp.php`
```
POST /Handler/verify-otp.php
Body: { email: "user@gmail.com", otp: "123456" }

Response (Success):
{
  "success": true,
  "message": "OTP xác nhận thành công",
  "user_id": 10
}
```

### 3. Reset Password - `Handler/reset-password.php`
```
POST /Handler/reset-password.php
Body: { new_password: "newpass123", confirm_password: "newpass123" }

Response (Success):
{
  "success": true,
  "message": "Mật khẩu đã được đặt lại thành công"
}
```

## ⚙️ Fallback - Nếu PHPMailer Không Có

File `email_helper.php` sẽ tự động sử dụng hàm `mail()` của PHP nếu PHPMailer không có sẵn.

**Lưu ý:** Hàm `mail()` cần cấu hình mail server trên server.

## ❓ Troubleshooting

### Email không gửi được?
1. ✅ Kiểm tra username/password có chính xác?
2. ✅ Kiểm tra firewall có chặn port 587?
3. ✅ Kiểm tra SMTP host có chính xác?
4. ✅ Truy cập `test_email_config.php` để xem chi tiết lỗi

### Lỗi "Use of unknown class PHPMailer"?
- Chạy: `composer require phpmailer/phpmailer`
- Hoặc tải thủ công và giải nén vào `vendor/phpmailer/phpmailer/`

### Muốn dùng hàm mail() của PHP?
- Không cần làm gì, nó sẽ tự động fallback nếu PHPMailer không có

## 🔒 Security Notes
- ✅ OTP có thời hạn 15 phút
- ✅ OTP chỉ dùng được 1 lần
- ✅ Mật khẩu được hash với BCRYPT
- ✅ Session xác nhận ngăn chặn bypass
- ✅ Sử dụng prepared statements để tránh SQL injection

## 📚 Công Thức Database
```sql
CREATE TABLE `password_reset_otp` (
  `OtpId` int(11) NOT NULL AUTO_INCREMENT,
  `UserId` int(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Otp` varchar(6) NOT NULL,
  `IsUsed` tinyint(1) NOT NULL DEFAULT 0,
  `ExpiresAt` datetime NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`OtpId`),
  KEY `UserId` (`UserId`),
  KEY `Email` (`Email`),
  CONSTRAINT `fk_otp_user` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🎯 User Flow
```
1. User vào Pages/forgot-password.php
   ↓
2. Nhập email
   ↓
3. API forgot-password.php tạo OTP + gửi email
   ↓
4. User nhập OTP
   ↓
5. API verify-otp.php xác nhận OTP
   ↓
6. User nhập mật khẩu mới
   ↓
7. API reset-password.php cập nhật password
   ↓
8. Tự động redirect về login sau 3 giây
```

Chúc bạn thành công! 🎉
