# 📦 Cài Đặt PHPMailer - Không Cần Composer

Bạn không có Composer? Không vấn đề! Dưới đây là 2 cách cài PHPMailer mà không cần Composer.

---

## ✅ Cách 1: Tải File PHP Đã Dịch (Dễ Nhất)

### **Bước 1: Tải PHPMailer**
1. Vào: https://github.com/PHPMailer/PHPMailer/releases
2. Tìm phiên bản mới nhất (ví dụ: v6.8.1)
3. Click **"Source code (zip)"** để tải file ZIP

### **Bước 2: Giải Nén**
1. Giải nén file ZIP vừa tải
2. Tìm thư mục `src` bên trong
3. Copy thư mục `src` vào: `ChatApp/vendor/phpmailer/phpmailer/`

### **Bước 3: Tạo Cấu Trúc Thư Mục**

Dùng Windows Explorer hoặc lệnh PowerShell:

```powershell
# Vào thư mục ChatApp
cd d:\Study\XAMPP\htdocs\MaNguonMo\thiCK\ChatApp

# Tạo cấu trúc thư mục
mkdir -Path vendor\phpmailer\phpmailer\src -Force
```

### **Bước 4: Copy File**

Sau khi giải nén, bạn sẽ thấy thư mục có cấu trúc này:

```
PHPMailer-master/
├── src/
│   ├── Exception.php
│   ├── OAuth.php
│   ├── PHPMailer.php
│   ├── POP3.php
│   └── SMTP.php
├── ...
```

Copy tất cả file từ thư mục `src/` vào: `ChatApp/vendor/phpmailer/phpmailer/src/`

**Kết quả cuối cùng:**
```
ChatApp/vendor/phpmailer/phpmailer/src/
├── Exception.php
├── OAuth.php
├── PHPMailer.php
├── POP3.php
└── SMTP.php
```

### **Bước 5: Tạo File Autoload**

Tạo file: `ChatApp/vendor/autoload.php`

```php
<?php
// Autoload file cho PHPMailer

$composer_autoload = __DIR__ . '/composer/autoload_real.php';

// Check if running under Composer
if (file_exists($composer_autoload)) {
    return require $composer_autoload;
}

// Manual autoload for PHPMailer
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    $relative_class = substr($class, strlen($prefix));
    $file = __DIR__ . '/phpmailer/phpmailer/src/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// If Composer's autoloader exists, use it
if (file_exists($composer_autoload)) {
    return require $composer_autoload;
}
?>
```

**Xong! Bây giờ PHPMailer đã sẵn sàng sử dụng.**

---

## ✅ Cách 2: Cài Composer (Lâu Hơn Nhưng Chuẩn)

### **Bước 1: Tải Composer**

1. Vào: https://getcomposer.org/download/
2. Click **"Windows Installer"** để tải `Composer-Setup.exe`
3. Chạy file setup và cài đặt (Next → Next → Finish)

### **Bước 2: Kiểm Tra Cài Đặt**

Mở PowerShell và chạy:

```powershell
composer --version
```

Nếu thấy phiên bản, cài đặt thành công!

### **Bước 3: Cài PHPMailer**

```powershell
cd d:\Study\XAMPP\htdocs\MaNguonMo\thiCK\ChatApp
composer require phpmailer/phpmailer
```

Composer sẽ tự động tải và cài đặt PHPMailer.

---

## 🧪 Kiểm Tra PHPMailer Đã Sẵn Sàng

### **Cách 1: Dùng Test Page**

Truy cập: `http://localhost/ChatApp/Handler/test_email_config.php`

Nếu không thấy lỗi "PHPMailer Library Not Found", PHPMailer đã sẵn sàng!

### **Cách 2: Kiểm Tra File**

Đảm bảo các file này tồn tại:

```
ChatApp/vendor/phpmailer/phpmailer/src/
├── Exception.php       ✅
├── PHPMailer.php       ✅
├── SMTP.php            ✅
└── ... (các file khác)
```

---

## 🆘 Nếu Vẫn Gặp Lỗi

### **Lỗi 1: "Use of unknown class PHPMailer"**

**Nguyên nhân:** File `vendor/autoload.php` không tồn tại hoặc cấu trúc thư mục sai

**Cách khắc phục:**
1. Kiểm tra cấu trúc thư mục (xem bước trên)
2. Kiểm tra file `vendor/autoload.php` tồn tại
3. Xóa cache browser (Ctrl+Shift+Delete) và thử lại

---

### **Lỗi 2: "Cannot open file SMTP.php"**

**Nguyên nhân:** Đường dẫn thư mục sai

**Cách khắc phục:**
```
ChatApp/vendor/phpmailer/phpmailer/src/SMTP.php
                       ↑                    ↑
           Phải là 'phpmailer'      Phải có thư mục 'src'
```

---

## 📊 So Sánh 2 Cách

| Tiêu Chí | Cách 1 (Tải File) | Cách 2 (Composer) |
|---------|------------------|-----------------|
| **Tốc độ** | ⚡ Nhanh (10 phút) | 🐢 Lâu (30 phút) |
| **Độ khó** | ✅ Dễ | ❌ Khó |
| **Kích thước** | 💾 Nhẹ (~2MB) | 📦 Nặng (~50MB) |
| **Quản lý package** | ❌ Thủ công | ✅ Tự động |
| **Cập nhật** | ⚠️ Thủ công | ✅ Tự động |

---

## 🎯 Khuyến Nghị

**Sử dụng Cách 1 (Tải File)** nếu:
- ✅ Bạn không muốn cài thêm Composer
- ✅ Bạn chỉ cần PHPMailer
- ✅ Bạn muốn cài nhanh

**Sử dụng Cách 2 (Composer)** nếu:
- ✅ Bạn sẽ làm project lớn với nhiều library
- ✅ Bạn muốn dễ quản lý package
- ✅ Bạn muốn cập nhật package tự động

---

## ✨ Sau Khi Cài Xong

1. ✅ Cấu hình `Handler/email_config.php`
2. ✅ Truy cập `Handler/test_email_config.php` để test
3. ✅ Gửi email OTP thành công! 🎉

---

**Hãy chọn cách nào phù hợp với bạn!** 😊
