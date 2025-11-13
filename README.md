**Cây Dự Án ChatApp**

```
ChatApp/
├─ admin_dashboard.php
├─ chatapp_db.sql
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
│  ├─ login.php
│  ├─ profile.php
│  ├─ register.php
│  ├─ ChatPages/
│  │  └─ chat.php
│  ├─ FriendPages/
│  │  └─ friends.php
│  └─ PostPages/
│     ├─ create_post.php
│     └─ posts.php
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

