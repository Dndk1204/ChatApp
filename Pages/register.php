<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ChatApp</title>
    <link rel="icon" type="image/x-icon" href="/ChatApp/Favicon64x64.ico"> 
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
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
        <a href="../index.php">
            <div class="logo-circle"><img src="/ChatApp/ChatApp_Logo.ico" alt="Logo"></div>
            <span>ChatApp</span>
        </a>
    </div>
    <nav class="main-nav">
        <a href="../index.php">HOME</a>
        <a href="../Pages/PostPages/posts.php">POSTS</a>
        <a href="../Pages/ChatPages/chat.php">CHAT</a>
        <a href="../Pages/FriendPages/friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="../../Handler/admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = $_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'; ?>
                <img src="../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="edit_profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="../Pages/hidden_list.php">Quản lý Ẩn</a>
                	<a href="../Pages/blocked_list.php">Quản lý Chặn</a>
                    <a href="../Handler/logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="./login.php" class="btn-text">Login</a>
            <a href="register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
    </header>

    <main class="form-page-content">
        <?php
        if (isset($_SESSION['success_message'])):
        ?>

            <div class="form-container">
                <h2 class="form-title" style="color: #66ccff;">Success!</h2>
                <p style="text-align: center; color: white;"><?php echo $_SESSION['success_message']; ?></p>
                <p style="text-align: center; color: #aaa;">You will be redirected to the Login page in 5 seconds...</p>
            </div>
            
            <script>
                setTimeout(function() {
                    window.location.href = '../Pages/login.php';
                }, 5000); // 5000 milliseconds = 5 giây
            </script>

        <?php
            unset($_SESSION['success_message']);
        else:
        ?>
        
            <div class="form-container">
                <h2 class="form-title">Register</h2>
                
                <?php
                    if (isset($_SESSION['error_message'])) {
                        echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                        unset($_SESSION['error_message']);
                    }
                ?>

                <form action="../Handler/register.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_code">Admin Code (Optional):</label>
                        <input type="text" id="admin_code" name="admin_code">
                    </div>
                    <button type="submit" class="btn-submit">Register</button>
                    <div style="text-align: center; margin-top: 15px;">
                        <p>Đã có tài khoản? <a href="login.php" style="color: #007bff; text-decoration: none;">Đăng nhập ngay</a></p>
                    </div>
                </form>
            </div>

        <?php
        endif;
        ?>
        
    </main>

</body>
</html>