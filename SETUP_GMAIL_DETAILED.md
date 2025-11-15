# 📧 Hướng Dẫn Chi Tiết: Cấu Hình Gmail Gửi Email OTP

## 🎯 Tổng Quan
Gmail có bảo mật cao nên không thể sử dụng mật khẩu tài khoản trực tiếp. Thay vào đó, bạn phải sử dụng **Mật khẩu ứng dụng (App Password)**.

---

## 📋 Các Bước Cấu Hình Chi Tiết

### **Bước 1: Bảo Mật Tài Khoản Google (2FA)**

Gmail yêu cầu Xác thực 2 yếu tố (2FA) trước khi tạo mật khẩu ứng dụng.

1. Truy cập: **https://myaccount.google.com/**
2. Chọn **"Bảo mật"** ở menu bên trái
3. Cuộn xuống tìm mục **"Xác thực 2 bước"**
4. Nếu chưa bật:
   - Click **"Bật Xác thực 2 bước"**
   - Làm theo hướng dẫn của Google (nhập số điện thoại hoặc dùng ứng dụng xác thực)

```
Google Account → Bảo mật → Xác thực 2 bước → Bật
```

---

### **Bước 2: Tạo Mật Khẩu Ứng Dụng (App Password)**

1. Sau khi bật 2FA, vào lại **https://myaccount.google.com/apppasswords**
   - Hoặc: **Bảo mật → Mật khẩu ứng dụng**

2. Nếu không thấy "Mật khẩu ứng dụng":
   - Đảm bảo đã bật 2FA
   - Tài khoản phải là tài khoản Google cá nhân (không phải tài khoản công ty)

3. Chọn:
   - **Ứng dụng:** Mail
   - **Thiết bị:** Windows Computer (hoặc loại thiết bị bạn dùng)

```
Ứng dụng: [Mail ▼]
Thiết bị:  [Windows Computer ▼]
```

4. Click **"Tạo"**

5. Google sẽ hiển thị mật khẩu 16 ký tự. **Ví dụ:**
```
abcd efgh ijkl mnop
```

---

### **Bước 3: Copy Mật Khẩu Ứng Dụng**

Google hiển thị cửa sổ với mật khẩu:

```
┌─────────────────────────────────┐
│ Mật khẩu ứng dụng               │
├─────────────────────────────────┤
│ abcd efgh ijkl mnop             │
├─────────────────────────────────┤
│ [Copy] [Xong]                   │
└─────────────────────────────────┘
```

**Copy** mật khẩu này (tất cả 16 ký tự bao gồm khoảng trắng)

---

### **Bước 4: Cấu Hình File PHP**

Mở file: `Handler/email_config.php`

```php
<?php
return [
    'smtp_host' => 'smtp.gmail.com',           // ← KHÔNG THAY ĐỔI
    'smtp_port' => 587,                        // ← KHÔNG THAY ĐỔI (TLS)
    'smtp_username' => 'your-email@gmail.com', // ← THAY BẰNG EMAIL GOOGLE CỦA BẠN
    'smtp_password' => 'your-app-password',    // ← THAY BẰNG MẬT KHẨU ỨNG DỤNG
    'from_email' => 'your-email@gmail.com',    // ← THAY BẰNG EMAIL GOOGLE CỦA BẠN
    'from_name' => 'ChatApp',                  // ← TÙY CHỌN (Tên hiển thị)
];
?>
```

**Ví dụ cấu hình hoàn chỉnh:**

```php
<?php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'deadordie159@gmail.com',    // Email Google
    'smtp_password' => 'abcd efgh ijkl mnop',       // Mật khẩu ứng dụng 16 ký tự
    'from_email' => 'deadordie159@gmail.com',       // Email gửi đi
    'from_name' => 'ChatApp - Reset Password',      // Tên hiển thị
];
?>
```

---

## 🔍 Giải Thích Từng Tham Số

| Tham Số | Giá Trị | Giải Thích |
|---------|---------|-----------|
| `smtp_host` | `smtp.gmail.com` | Server SMTP của Gmail |
| `smtp_port` | `587` | Port TLS (an toàn) - **KHÔNG THAY ĐỔI** |
| `smtp_username` | `your-email@gmail.com` | Email Google của bạn (email gửi) |
| `smtp_password` | `abcd efgh ijkl mnop` | **Mật khẩu ứng dụng** (16 ký tự) |
| `from_email` | `your-email@gmail.com` | Email hiển thị trong "Từ" |
| `from_name` | `ChatApp` | Tên hiển thị trong "Từ" |

### **Port 587 vs 465?**
- **587:** TLS (khuyên dùng, bắt đầu kết nối bình thường rồi nâng cấp lên bảo mật)
- **465:** SSL (kết nối đã bảo mật từ đầu)

Gmail hỗ trợ cả hai, nhưng 587 là tiêu chuẩn.

---

## ⚠️ Lỗi Thường Gặp & Cách Khắc Phục

### **Lỗi 1: "Invalid credentials"**
```
Error: SMTP authentication failed
```

**Nguyên nhân:**
- ❌ Username hoặc password sai
- ❌ Chưa bật 2FA trước khi tạo App Password
- ❌ Sử dụng mật khẩu tài khoản thay vì App Password

**Cách khắc phục:**
1. Kiểm tra email có chính xác không
2. Đảm bảo dùng **Mật khẩu ứng dụng** (16 ký tự), KHÔNG phải mật khẩu tài khoản
3. Thử copy lại mật khẩu từ Google

---

### **Lỗi 2: "Connection timeout"**
```
Error: SMTP connection timeout
```

**Nguyên nhân:**
- ❌ Firewall/Antivirus chặn port 587
- ❌ SMTP host sai (phải là `smtp.gmail.com`)

**Cách khắc phục:**
1. Kiểm tra firewall cho phép port 587
2. Kiểm tra cấu hình:
   ```php
   'smtp_host' => 'smtp.gmail.com',  // ← Phải đúng chính xác
   'smtp_port' => 587,
   ```
3. Thử bằng telnet:
   ```bash
   telnet smtp.gmail.com 587
   ```

---

### **Lỗi 3: "Less secure app access"**
```
Error: Please log in via your web browser
```

**Nguyên nhân:**
- Gmail chặn ứng dụng "kém an toàn" (cách cấu hình cũ)

**Cách khắc phục:**
- ✅ Sử dụng **Mật khẩu ứng dụng** (như hướng dẫn này) sẽ không gặp vấn đề

---

## 🧪 Test Cấu Hình

### **Cách 1: Dùng Trang Test**
Truy cập: `http://localhost/ChatApp/Handler/test_email_config.php`

Nhập email test → Click "Gửi Email Test"

Sẽ nhận thông báo thành công hoặc lỗi cụ thể.

### **Cách 2: Kiểm Tra Log**
Nếu gửi email không thành công, kiểm tra:
```php
// Handler/email_helper.php
// Tìm dòng:
return ['success' => false, 'message' => 'Email send error: ' . $e->getMessage()];
```

Pesan lỗi sẽ cho biết vấn đề chính xác.

---

## 📊 Sơ Đồ Quy Trình Xác Thực Gmail

```
┌─────────────────────────────────────┐
│ Máy tính của bạn                    │
│ (ChatApp PHP Application)           │
└────────────────┬────────────────────┘
                 │
                 │ SMTP Connection
                 │ (Port 587)
                 ↓
┌─────────────────────────────────────┐
│ Gmail SMTP Server                   │
│ (smtp.gmail.com)                    │
└────────────────┬────────────────────┘
                 │
                 │ Xác thực:
                 │ • Username: your-email@gmail.com
                 │ • Password: App Password (16 ký tự)
                 │
                 ↓
        ✅ Kết nối thành công
                 │
                 ↓
        📧 Gửi email OTP
```

---

## 🔐 Security Notes

### **Tại Sao Dùng App Password?**

Google không cho phép ứng dụng bên thứ ba sử dụng mật khẩu tài khoản chính vì:

1. **Bảo mật:** Nếu App Password bị lộ, chỉ email bị ảnh hưởng, mật khẩu tài khoản vẫn an toàn
2. **Kiểm soát:** Bạn có thể xóa App Password bất cứ lúc nào mà không cần đổi mật khẩu tài khoản
3. **Audit:** Google có thể theo dõi hoạt động của mỗi ứng dụng

### **App Password Có Thể Làm Gì?**

App Password chỉ cho phép:
- ✅ Gửi email (SMTP)
- ✅ Nhận email (IMAP/POP3)

**KHÔNG thể:**
- ❌ Đổi mật khẩu tài khoản
- ❌ Bật/tắt 2FA
- ❌ Truy cập dữ liệu khác (Google Drive, Contacts, v.v)

---

## 💾 Lưu Trữ Mật Khẩu Ứng Dụng

### ✅ **Cách làm an toàn:**
1. Cấu hình trong file `email_config.php` trên server của bạn
2. Không commit file `email_config.php` lên GitHub (thêm vào `.gitignore`)
3. Nếu bị lộ, vào Google tài khoản → Mật khẩu ứng dụng → Xóa

### ❌ **Không nên:**
- Commit mật khẩu lên GitHub
- Chia sẻ mật khẩu với người khác
- Sử dụng mật khẩu tài khoản chính

---

## 📞 Hỗ Trợ Thêm

Nếu gặp vấn đề:

1. **Kiểm tra lại các bước** trên đây (đặc biệt bước 2FA)
2. **Xem thông báo lỗi** tại `test_email_config.php`
3. **Thử cấu hình khác** (nếu Gmail không hoạt động, thử Outlook hoặc email khác)

---

## 🎯 Summary - Các Ký Tự Cần Sửa

Chỉ thay **3 dòng** này trong `Handler/email_config.php`:

```php
// Thay 'your-email@gmail.com' bằng EMAIL GOOGLE CỦA BẠN
'smtp_username' => 'deadordie159@gmail.com',

// Thay 'your-app-password' bằng MẬT KHẨU ỨNG DỤNG (16 ký tự)
'smtp_password' => 'abcd efgh ijkl mnop',

// Thay email từ
'from_email' => 'deadordie159@gmail.com',
```

**Các tham số khác KHÔNG THAY ĐỔI!**

---

**Chúc bạn cấu hình thành công! 🎉**
