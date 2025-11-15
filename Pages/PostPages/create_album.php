<?php
require_once '../../Handler/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Album mới - ChatApp</title>
    <link rel="stylesheet" href="./../../css/style.css">
    <style>
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
        
        /* Style cho các form trong file này (lấy từ create_post.php) */
         .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--color-border);
            background: var(--color-secondary);
            font-size: 1em;
            color: var(--color-text);
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }
         .form-group textarea {
            min-height: 120px;
            resize: vertical;
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
                    <?php $avatar = $_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'; ?>
                    <img src="../../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn" onerror="this.src='../../uploads/default-avatar.jpg'">
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
                <h2 class="form-title">Tạo Album Mới</h2>
            </div>

            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>
            
            <form action="../../Handler/PostHandler/create-album-handler.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title">Tên Album</label>
                    <input type="text" id="title" name="title" required class="comment-input"> </div>

                <div class="form-group">
                    <label for="post-content">Bạn đang nghĩ gì? (Tùy chọn)</label>
                    <textarea id="post-content" name="content"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="post-image">Chọn ảnh (Bắt buộc, chọn nhiều ảnh)</label>
                    <input type="file" id="post-image" name="images[]" accept="image/png, image/jpeg, image/gif" multiple required>
                </div>
                
                <div class="form-group">
                    <label for="privacy">Ai có thể xem Album này?</label>
                    <select id="privacy" name="privacy" style="width: 100%; padding: 10px; border: 1px solid #CCC; border-radius: 5px;">
                        <option value="friends" selected>Chỉ Bạn bè</option>
                        <option value="public">Công khai (Mọi người)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Tạo Album</button>
            </form>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const avatarBtn = document.getElementById('avatarBtn');
            const avatarDropdown = document.getElementById('avatarDropdown');
            if (avatarBtn && avatarDropdown) {
                avatarBtn.addEventListener('click', function(event) {
                    event.stopPropagation(); 
                    avatarDropdown.classList.toggle('open');
                });
                document.addEventListener('click', function(event) {
                    if (avatarDropdown.classList.contains('open') && !avatarDropdown.contains(event.target)) {
                        avatarDropdown.classList.remove('open');
                    }
                });
            }
        });
    </script>

     </body>
</html>