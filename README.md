**Cây Dự Án ChatApp**

```
ChatApp/
├─ admin_dashboard.php
├─ chatappsql.sql
├─ index.php
├─ README.md
├─ Admin/
│  ├─ _auth.php
│  ├─ _helpers.php
│  ├─ index.php
│  ├─ messages.php
│  ├─ user_create.php
│  ├─ user_edit.php
│  └─ users.php
├─ css/
│  ├─ admin.css
│  └─ style.css
├─ Handler/
│  ├─ db.php
│  ├─ login.php
│  ├─ logout.php
│  ├─ register.php
│  ├─ email_config.php                 [MỚI - Cấu hình SMTP]
│  ├─ email_helper.php                 [MỚI - Hàm gửi email]
│  ├─ forgot-password.php              [MỚI - API tạo OTP]
│  ├─ verify-otp.php                   [MỚI - API verify OTP]
│  ├─ reset-password.php               [MỚI - API reset password]
│  ├─ test_email_config.php            [MỚI - Trang test email]
│  ├─ ChatHandler/
│  │  ├─ fetch-messages.php
│  │  ├─ fetch-users.php
│  │  ├─ send-media.php
│  │  └─ send-message.php
│  ├─ FriendHandler/
│  │  ├─ friend-handler.php
│  │  └─ search_user.php
│  └─ PostHandler/
│     ├─ add-comment.php
│     ├─ block-user.php
│     ├─ create-post.php
│     ├─ delete-post.php
│     ├─ get-posts.php
│     ├─ handle-reaction.php
│     ├─ hide-feed.php
│     ├─ report-post.php
│     ├─ toggle-like.php
│     ├─ unfriend.php
│     └─ update-post.php
├─ Pages/
│  ├─ blocked_list.php
│  ├─ hidden_list.php
│  ├─ login.php                        [CẬP NHẬT - Thêm link "Quên mật khẩu?"]
│  ├─ profile.php
│  ├─ register.php
│  ├─ forgot-password.php              [MỚI - Trang reset password 3 bước]
│  ├─ ChatPages/
│  │  └─ chat.php
│  ├─ FriendPages/
│  │  └─ friends.php
│  └─ PostPages/
│     ├─ create_album.php
│     ├─ create_post.php
│     ├─ edit_post.php
│     └─ posts.php
├─ vendor/                             [MỚI - Composer dependencies]
│  ├─ autoload.php
│  ├─ composer/
│  └─ phpmailer/
│     └─ phpmailer/src/
│        ├─ Exception.php
│        ├── PHPMailer.php
│        ├─ SMTP.php
│        └─ ... (các file khác)
└─ uploads/
	├─ avatars/
	├─ messages/
	└─ posts/
```

- **Ghi chú:** Đây là sơ đồ tĩnh lấy theo cấu trúc workspace hiện tại; nếu bạn thêm/xóa file hoặc thư mục, tôi có thể cập nhật lại sơ đồ.

- **Muốn thêm:** tôi có thể thêm hướng dẫn chạy dự án, lệnh `tree` để sinh tự động, hoặc tạo script để export cây thư mục nếu bạn muốn.

**Chú thích chức năng các thư mục chính**

- **root files:**
	- `index.php`: Trang chính / entrypoint của ứng dụng (giao diện người dùng).
	- `admin_dashboard.php`: Bảng điều khiển quản trị viên.
	- `chatapp_db.sql`: Tập tin dump/structure database mẫu.

- **Admin/**: Các trang và helper dành cho quản trị viên — xác thực, quản lý người dùng và tin nhắn.
	- `_auth.php`: Xử lý xác thực / kiểm tra quyền truy cập admin.
	- `_helpers.php`: Hàm tiện ích dùng trong phần admin.
	- `messages.php`: Giao diện/logic quản lý tin nhắn từ admin.
	- `users.php`, `user_create.php`, `user_edit.php`: Quản lý người dùng (danh sách, tạo, sửa).

- **css/**: Các file stylesheet cho giao diện (toàn site và admin).

- **Handler/**: API/handler phía server — xử lý form, AJAX, tương tác DB.
	- `db.php`: Kết nối và các tiện ích DB.
	- `login.php`, `logout.php`, `register.php`: Xử lý xác thực người dùng.
	- **ChatHandler/**: Xử lý liên quan đến chat (gửi, nhận tin nhắn, media).
	- **FriendHandler/**: Xử lý danh sách bạn bè và tìm kiếm người dùng.
	- **PostHandler/**: Xử lý bài viết, bình luận, like, báo cáo, ẩn, xóa.

- **Pages/**: Các trang front-end mà người dùng truy cập.
	- `login.php`, `register.php`, `profile.php`: Trang xác thực và hồ sơ người dùng.
	- **ChatPages/**: Trang chat (giao diện nhắn tin).
	- **Components/**: Thành phần dùng lại (ví dụ: `navbar.php`).
	- **FriendPages/**, **PostPages/**: Giao diện quản lý bạn bè và bài viết.

- **uploads/**: Nơi lưu trữ file tải lên (avatar, media tin nhắn, ảnh bài viết).
	- `avatars/`: Thư mục avatar của người dùng, chia theo user id.
	- `messages/`: File media gửi kèm tin nhắn, chia theo user id.
	- `posts/`: Ảnh/đính kèm của bài viết.

---

## 🔐 Chức Năng Quên Mật Khẩu Với OTP

### 📌 Mô Tả

Chức năng cho phép người dùng reset mật khẩu thông qua email OTP 6 chữ số:
1. Nhập email để yêu cầu reset password
2. Nhận mã OTP qua email (thời hạn 15 phút)
3. Xác nhận OTP để verify danh tính
4. Đặt mật khẩu mới
5. Tự động quay về login sau 3 giây

#### **Dependencies**
- `vendor/` - Thư mục Composer (PHPMailer & dependencies)
- `vendor/autoload.php` - Composer autoload
### 🚀 Hướng Dẫn Cài Đặt Nhanh
#### **1. Cài Đặt Composer**
```powershell
# Tải và cài từ: https://getcomposer.org/download/
# Chạy Composer-Setup.exe
# Kiểm tra
composer --version
```
### Bước 2: Kiểm Tra Cài Đặt
**Quan trọng:** Mở PowerShell/Terminal **MỚI** (không phải cửa sổ cũ)
```powershell
composer --version
```
**Kết quả mong đợi:**
```
Composer version 2.6.5 2023-10-06 10:11:52
```
---

### Bước 3: Thêm Composer Vào PATH (Nếu Cần)

Nếu `composer --version` hiển thị lỗi, bạn cần thêm Composer vào PATH.

1. **Mở PowerShell as Administrator**
   - Nhấn `Win + X` → Chọn **"Windows PowerShell (Admin)"**

2. **Tìm đường dẫn Composer:**
   ```powershell
   Get-Command composer
   # Hoặc
   where.exe composer
   ```
   
   Nếu tìm thấy, ghi lại đường dẫn (ví dụ: `C:\ProgramData\ComposerSetup\bin`)

3. **Thêm vào PATH:**
   ```powershell
   setx PATH "$($env:PATH);C:\ProgramData\ComposerSetup\bin"
   ```
   
   > **Lưu ý:** Thay `C:\ProgramData\ComposerSetup\bin` bằng đường dẫn thực tế nếu khác

4. **Mở PowerShell mới và kiểm tra:**
   ```powershell
   composer --version
   ```

---

## 📦 Cài Đặt PHPMailer

### Cách 1: Dùng Composer (Khuyên Dùng) ⭐

```powershell
cd d:\Study\XAMPP\htdocs\MaNguonMo\thiCK\ChatApp
composer require phpmailer/phpmailer
```

**Kết quả mong đợi:**
```
Using version ^6.8 for phpmailer/phpmailer
./composer.json has been updated
Loading composer repositories with package information
...
Installing phpmailer/phpmailer (v6.8.1)
```

✅ **Hoàn tất! PHPMailer đã được cài.**

---

#### **3. Cấu Hình Email**

> **⚠️ Bảo mật:** Không bao giờ commit email/password thực vào git!

**Cách An Toàn (Khuyên dùng):**

File `email_config.php` là **template công khai** (không chứa thông tin nhạy cảm).
File `email_config.local.php` là **file cấu hình thực** (được ignore bởi git).

1. **Mở file:** `Handler/ForgotPasswordHandler/email_config.local.php`
2. **Thay đổi thông tin SMTP của bạn:**
   ```php
   return [
       'smtp_host' => 'smtp.gmail.com',
       'smtp_port' => 587,
       'smtp_username' => 'your-email@gmail.com',
       'smtp_password' => 'your-app-password',  // 16 ký tự từ Google
       'from_email' => 'your-email@gmail.com',
       'from_name' => 'ChatApp',
   ];
   ```
3. **Lưu file** - Không cần sửa `email_config.php` nữa!

**Cách Lấy App Password Gmail:**
1. Vào: https://myaccount.google.com/
2. Bảo mật → Xác thực 2 bước (bật nếu chưa)
3. Mật khẩu ứng dụng → Chọn Mail + Windows Computer
4. Copy mật khẩu 16 ký tự vào `email_config.local.php`

**Lưu ý về .gitignore:**
```
email_config.local.php  ← File này KHÔNG được commit vào git
.env                    ← Các file config cục bộ khác cũng được ignore
```

Khi clone repo ở máy khác, bạn chỉ cần tạo file `email_config.local.php` của riêng mình.

#### **4. Test Email**

```
http://localhost/ChatApp/Handler/test_email_config.php
```

Nhập email → Click "Gửi Email Test" → Kiểm tra email nhận được
