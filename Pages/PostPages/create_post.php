<?php
require_once '../../Handler/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$userId = $_SESSION['user_id'];
// Lấy username hiện tại nếu đã đăng nhập
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài đăng mới - ChatApp</title>
    <link rel="stylesheet" href="./../../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
        /* Tùy chỉnh textarea cho đẹp hơn */
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            /* Lấy style từ .form-group input trong style.css */
            border: 1px solid var(--color-border);
            background: var(--color-secondary);
            font-size: 1em;
            color: var(--color-text);
            box-sizing: border-box;
            transition: border 0.2s ease, box-shadow 0.2s ease;
            resize: vertical; /* Cho phép thay đổi kích thước theo chiều dọc */
            min-height: 120px;
        }
        .form-group textarea:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 5px rgba(69, 123, 157, 0.2);
            outline: none;
        }
        /* [MỚI] Style cho ô select */
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--color-border);
            background: var(--color-secondary);
            font-size: 1em;
            color: var(--color-text);
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }
        .form-group select:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 5px rgba(69, 123, 157, 0.2);
            outline: none;
        }

        /* ↓↓↓ CSS MỚI CHO TIÊU ĐỀ FLEX ↓↓↓ */
        .form-header-flex {
            display: flex;
            align-items: center;
            margin-bottom: 20px; /* Bù cho margin của h2 */
        }
        .form-header-flex .form-title { /* Sửa lại h2 */
            margin: 0; /* Xóa margin mặc định */
            flex: 1; /* Đẩy nút về bên trái */
            text-align: center; /* Căn giữa tiêu đề (tùy chọn) */
            padding-right: 30px; /* Đảm bảo tiêu đề vẫn ở giữa */
        }
        .back-to-posts-icon {
            display: inline-block;
            font-size: 26px;
            font-weight: bold;
            color: var(--color-accent);
            text-decoration: none;
            line-height: 1;
        }
        .back-to-posts-icon:hover {
            color: var(--color-primary-dark);
        }
    </style>
</head>
<body>

    <header class="navbar">
    <div class="logo">
        <a href="index.php">
            <div class="logo-circle"></div>
            <span>ChatApp</span>
        </a>
    </div>
    <nav class="main-nav">
        <a href="../../index.php">HOME</a>
        <a href="../../Pages/PostPages/posts.php">POSTS</a>
        <a href="../../Pages/ChatPages/chat.php">CHAT</a>
        <a href="../../Pages/FriendPages/friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="../../Handler/admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'), '/'); ?>
                <img src="../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="../ProfilePages/Profile.php?id=<?php echo $current_user_id; ?>">Trang cá nhân của tôi</a>
                    <a href="../ProfilePages/edit_profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="../../Pages/hidden_list.php">Quản lý Ẩn</a>
                    <a href="../../Pages/blocked_list.php">Quản lý Chặn</a> 
                    <a href="../../Handler/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="Pages/login.php" class="btn-text">Login</a>
            <a href="Pages/register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
</header>

    <main class="form-page-content">
        <div class="form-container">
            <div class="form-header-flex">
                <a href="posts.php" class="back-to-posts-icon">←</a>
                <h2 class="form-title">Tạo bài đăng mới</h2>
            </div>

            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>
            
            <form action="../../Handler/PostHandler/create-post.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="post-content">Bạn đang nghĩ gì?</label>
                    <textarea id="post-content" name="content" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="post-image">Chọn ảnh (Tùy chọn)</label>
                    <input type="file" id="post-image" name="image" accept="image/png, image/jpeg, image/gif">
                </div>
                
                <div class="form-group">
                    <label for="privacy">Ai có thể xem bài này?</label>
                    <select id="privacy" name="privacy">
                        <option value="friends" selected>Chỉ Bạn bè</option>
                        <option value="public">Công khai (Mọi người)</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Đăng</button>
            </form>
        </div>
    </main>

    <script>
            // Chờ cho toàn bộ trang được tải xong
    document.addEventListener('DOMContentLoaded', function() {
        
        const avatarBtn = document.getElementById('avatarBtn');
        const avatarDropdown = document.getElementById('avatarDropdown');

        // Kiểm tra xem các phần tử này có tồn tại không
        // (vì khách truy cập sẽ không thấy chúng)
        if (avatarBtn && avatarDropdown) {
            
            // 1. Khi nhấp vào avatar
            avatarBtn.addEventListener('click', function(event) {
                // Ngăn sự kiện click lan ra ngoài
                event.stopPropagation(); 
                
                // Hiển thị hoặc ẩn dropdown
                avatarDropdown.classList.toggle('open');
            });

            // 2. Khi nhấp ra ngoài (bất cứ đâu trên trang)
            document.addEventListener('click', function(event) {
                // Nếu dropdown đang mở và cú click không nằm trong dropdown
                if (avatarDropdown.classList.contains('open') && !avatarDropdown.contains(event.target)) {
                    avatarDropdown.classList.remove('open');
                }
            });
        }
    });
    </script>
</body>
</html>