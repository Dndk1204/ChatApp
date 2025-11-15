<?php
session_start();
// [ĐÃ SỬA] Lùi 1 cấp về root, vào Handler
require_once '../Handler/db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Cùng thư mục Pages/
    exit();
}
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Ẩn Nhật ký</title>
    <link rel="stylesheet" href="../css/style.css"> 
    
    <style>
        /* CSS cho trang Cài đặt (Theme sáng) */
        .settings-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: var(--color-card);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .settings-container h1 {
            color: var(--color-accent);
            border-bottom: 2px solid #EEE;
            padding-bottom: 10px;
        }
        .hidden-user-list {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        .hidden-user-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .hidden-user-item:nth-child(odd) {
            background-color: #F9F9F9;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        .user-info span {
            font-weight: bold;
            font-size: 1.1em;
        }
        .unhide-btn {
            padding: 8px 15px;
            background-color: var(--color-danger);
            color: black; /* Sửa thành màu đen theo yêu cầu của bạn */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .unhide-btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <a href="../index.php"> <div class="logo-circle"></div>
                <span>ChatApp</span>
            </a>
        </div>
        <nav class="main-nav">
            <a href="../index.php">HOME</a>
            <a href="PostPages/posts.php">POSTS</a> <a href="ChatPages/chat.php">CHAT</a> <a href="FriendPages/friends.php">FRIENDS</a> <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                <a href="../Handler/admin_dashboard.php">ADMIN</a> <?php endif; ?>
        </nav>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
                <div class="avatar-menu">
                    <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'), '/'); ?>
                    <img src="../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn" onerror="this.src='../uploads/default-avatar.jpg'">
                    <div class="avatar-dropdown" id="avatarDropdown">
                        <a href="ProfilePages/Profile.php?id=<?php echo $current_user_id; ?>">Trang cá nhân của tôi</a>
                        <a href="ProfilePages/edit_profile.php">Chỉnh sửa hồ sơ</a> 
                        <a href="hidden_list.php">Quản lý Ẩn</a>
                        <a href="blocked_list.php">Quản lý Chặn</a> 
                        <a href="../Handler/logout.php">Logout</a> </div>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="settings-container">
        <h1>Những người bạn đã ẩn nhật ký</h1>
        
        <ul class="hidden-user-list">
            <?php
            // Truy vấn tất cả những người mà user hiện tại đã ẩn
            $sql = "SELECT u.UserId, u.Username, u.AvatarPath 
                    FROM hidden_feeds h
                    JOIN users u ON h.HiddenId = u.UserId
                    WHERE h.HiderId = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0):
                while($user = $result->fetch_assoc()):
            ?>
                <li class="hidden-user-item" id="hidden-user-<?php echo $user['UserId']; ?>">
                    <div class="user-info">
                        <img src="../<?php echo htmlspecialchars($user['AvatarPath'] ?: 'uploads/default-avatar.jpg'); ?>" alt="Avatar">
                        <span><?php echo htmlspecialchars($user['Username']); ?></span>
                    </div>
                    <button class="unhide-btn" onclick="unhideFeed(<?php echo $user['UserId']; ?>)">
                        Gỡ ẩn
                    </button>
                </li>
            <?php
                endwhile;
            else:
                echo "<p style='color: var(--color-text-muted);'>Bạn chưa ẩn nhật ký của ai.</p>";
            endif;
            $stmt->close();
            $conn->close();
            ?>
        </ul>
    </main>
    
    <script>
        function unhideFeed(userId) {
            if (!confirm('Bạn có muốn xem lại bài đăng của người này?')) { return; }
            
            // [ĐÃ SỬA] Đường dẫn AJAX lùi 1 cấp
            fetch('../Handler/PostHandler/php-unhide-feed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    // Xóa người đó khỏi danh sách trên giao diện
                    const itemToRemove = document.getElementById(`hidden-user-${userId}`);
                    if (itemToRemove) {
                        itemToRemove.remove();
                    }
                }
            })
            .catch(error => console.error('Lỗi khi gỡ ẩn:', error));
        }
    </script>
    
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