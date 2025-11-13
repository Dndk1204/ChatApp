<?php
session_start();
// (1) Đường dẫn 2 cấp lên thư mục Handler
require_once '../../Handler/db.php'; 

// (2) Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Pages/login.php"); // Sửa: 2 cấp
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

// (3) Lấy Post ID từ URL
if (!isset($_GET['id']) || (int)$_GET['id'] == 0) {
    $_SESSION['error_message'] = "ID bài đăng không hợp lệ.";
    header("Location: ../../Pages/PostPages/posts.php"); // Sửa: 2 cấp
    exit();
}
$post_id = (int)$_GET['id'];

// (4) Lấy thông tin bài đăng VÀ kiểm tra quyền sở hữu
try {
    $sql = "SELECT Content, ImagePath FROM posts WHERE PostId = ? AND UserId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows != 1) {
        // Nếu không tìm thấy (sai ID hoặc không phải chủ) -> đuổi về
        $_SESSION['error_message'] = "Không tìm thấy bài đăng hoặc bạn không có quyền sửa.";
        $stmt->close();
        $conn->close();
        header("Location: ../../Pages/PostPages/posts.php"); // Sửa: 2 cấp
        exit();
    }
    
    $post = $result->fetch_assoc();
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    if ($conn) $conn->close();
    header("Location: ../../Pages/PostPages/posts.php"); // Sửa: 2 cấp
    exit();
}
// Giữ $conn lại để dùng cho navbar
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa bài đăng - ChatApp</title>
    <link rel="stylesheet" href="../../css/style.css"> 
<style>
        /* CSS cho trang edit post (THEO GIAO DIỆN DARK MODE) */
        .page-content {
            flex-grow: 1; display: flex; justify-content: center;
            padding: 50px 20px;
            background-color: #1a1a1a; /* Dark background */
        }
        .form-container {
            width: 100%; max-width: 700px;
            background-color: #2a2a2a; /* Dark card background */
            padding: 25px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); /* Darker shadow */
        }
        .form-container h1 {
            color: #f0f0f0; /* Light text */
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #aaa; /* Muted light text */
        }
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #444; /* Darker border */
            border-radius: 5px;
            background-color: #333; /* Dark input */
            color: #f0f0f0; /* Light text */
            font-size: 1em;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .current-image-preview {
            margin-top: 10px;
        }
        .current-image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 5px;
            border: 1px solid #444; /* Darker border */
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #ff6666; /* Accent color (hồng) */
            color: #1a1a1a; /* Dark text on accent button */
            border: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #ff8080; /* Lighter accent on hover */
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <a href="../../index.php">
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
                <a href="../../admin_dashboard.php">ADMIN</a>
            <?php endif; ?>
        </nav>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
                <div class="avatar-menu">
                    <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'images/default-avatar.jpg'), '/'); ?>
                    <img src="../../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                    <div class="avatar-dropdown" id="avatarDropdown">
                        <a href="../../Pages/profile.php">Chỉnh sửa hồ sơ</a>
                        <a href="../../Handler/logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="../../Pages/login.php" class="btn-text">Login</a>
                <a href="../../Pages/register.php" class="btn-text">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="page-content">
        <div class="form-container">
            <h1>Chỉnh sửa bài đăng</h1>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <p style="color: red; text-align: center;"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            <?php endif; ?>

            <form action="../../Handler/PostHandler/update-post.php" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

                <div class="form-group">
                    <label for="content">Nội dung:</label>
                    <textarea id="content" name="content" rows="6"><?php echo htmlspecialchars($post['Content']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Thay đổi ảnh:</label>
                    <input type="file" id="image" name="image" accept="image/png, image/jpeg, image/gif">
                    
                    <?php if (!empty($post['ImagePath'])): ?>
                        <div class="current-image-preview">
                            <p>Ảnh hiện tại:</p>
                            <img src="../../<?php echo htmlspecialchars($post['ImagePath']); ?>" alt="Ảnh hiện tại">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">Lưu thay đổi</button>
            </form>
        </div>
    </main>

    <script>
        // Script cho Avatar Dropdown
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
<?php
// Đóng kết nối
if ($conn) $conn->close();
?>