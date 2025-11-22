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
    // [CẬP NHẬT 1] Lấy thêm cột `Privacy`
    $sql = "SELECT Content, ImagePath, Privacy FROM posts WHERE PostId = ? AND UserId = ?";
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
    <link rel="icon" type="image/x-icon" href="/ChatApp/Favicon64x64.ico"> 
    <link rel="stylesheet" href="../../css/style.css"> 
<style>
        /* [CẬP NHẬT 3] CSS cho trang edit post (ĐÃ DỊCH SANG LIGHT THEME) */
        .page-content {
            flex-grow: 1; display: flex; justify-content: center;
            padding: 50px 20px;
            background-color: var(--color-bg); /* Light background */
        }
        .form-container {
            width: 100%; max-width: 700px;
            background-color: var(--color-card); /* Light card background */
            padding: 25px 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Lighter shadow */
            border: 1px solid var(--color-border);
        }
        .form-container h1 {
            color: var(--color-accent); /* Dark text */
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
            color: var(--color-text); /* Muted dark text */
        }
        .form-group textarea,
        .form-group select, /* Thêm select */
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--color-border);
            background: var(--color-secondary);
            font-size: 1em;
            color: var(--color-text);
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }
        .form-group textarea:focus,
        .form-group select:focus {
             border-color: var(--color-accent);
            box-shadow: 0 0 5px rgba(69, 123, 157, 0.2);
            outline: none;
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
            border: 1px solid var(--color-border);
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: var(--color-accent); /* Accent color (xanh) */
            color: var(--color-card); /* Light text on accent button */
            border: none;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: var(--color-primary-dark); /* Lighter accent on hover */
        }

        /* ↓↓↓ THÊM CSS CHO NÚT QUAY LẠI ↓↓↓ */
        .back-to-posts {
            display: inline-block;
            margin-bottom: 15px;
            font-size: 14px;
            color: var(--color-accent);
            text-decoration: none;
            font-weight: bold;
        }
        .back-to-posts:hover {
            text-decoration: underline;
            color: var(--color-primary-dark);
        }

        /* ↓↓↓ CSS MỚI CHO TIÊU ĐỀ FLEX ↓↓↓ */
        .form-header-flex {
            display: flex;
            align-items: center;
            margin-bottom: 20px; /* Bù cho margin của h1 */
        }
        .form-header-flex h1 {
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
        .logo>a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--color-text);
            font-weight: bold;
            font-size: 1.5em;
            gap: 10px;
        }
        .logo-circle>img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">
            <a href="../../index.php">
                <div class="logo-circle"><img src="/ChatApp/ChatApp_Logo.ico" alt="Logo"></div>
                <span>ChatApp</span>
            </a>
        </div>
        <nav class="main-nav">
            <a href="../../index.php">HOME</a>
            <a href="posts.php">POSTS</a> <a href="../ChatPages/chat.php">CHAT</a> <a href="../FriendPages/friends.php">FRIENDS</a> <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
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
                <a href="../../Pages/login.php" class="btn-text">Login</a>
                <a href="../../Pages/register.php" class="btn-text">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="page-content">
        <div class="form-container">
            <div class="form-header-flex">
                <a href="posts.php" class="back-to-posts-icon">←</a>
                <h1>Chỉnh sửa bài đăng</h1>
            </div>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <p class="form-error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
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

                <div class="form-group">
                    <label for="privacy">Ai có thể xem bài này?</label>
                    <select id="privacy" name="privacy">
                        <option value="friends" <?php echo ($post['Privacy'] == 'friends') ? 'selected' : ''; ?>>
                            Chỉ Bạn bè
                        </option>
                        <option value="public" <?php echo ($post['Privacy'] == 'public') ? 'selected' : ''; ?>>
                            Công khai (Mọi người)
                        </option>
                    </select>
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