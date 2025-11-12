<?php
// Bắt đầu session nếu chưa bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy username hiện tại nếu đã đăng nhập
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<style>
    header.navbar { display: flex; justify-content: space-between; align-items: center; background: var(--color-primary); padding: 12px 24px; border-bottom: 2px solid var(--color-border); box-shadow: 0 2px 4px rgba(0,0,0,0.05); position: relative; } header.navbar .logo a { text-decoration: none; color: var(--color-text); font-weight: bold; font-size: 1.2em; } header.navbar .main-nav a, header.navbar .auth-buttons a { color: var(--color-text); text-decoration: none; margin-left: 20px; font-size: 0.9em; transition: color 0.2s ease; } header.navbar .main-nav a:hover, header.navbar .auth-buttons a:hover { color: var(--color-accent); } /* Avatar + Username */ .auth-buttons { display: flex; align-items: center; gap: 8px; position: relative; } .logged-in-user { font-weight: 600; color: var(--color-accent); } .avatar-menu { position: relative; } .avatar-thumb { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-primary-dark); cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; } .avatar-thumb:hover { transform: scale(1.05); box-shadow: 0 0 10px rgba(69,123,157,0.3); } /* Dropdown */ .avatar-dropdown { position: absolute; right: 0; top: 50px; /* cách avatar */ background: var(--color-card); border: 1px solid var(--color-border); border-radius: 8px; min-width: 180px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); display: none; z-index: 50; flex-direction: column; overflow: hidden; } .avatar-dropdown.open { display: flex; } .avatar-dropdown a { display: block; padding: 10px 12px; color: var(--color-text); text-decoration: none; border-bottom: 1px solid var(--color-border); font-weight: 500; transition: background 0.2s ease; } .avatar-dropdown a:last-child { border-bottom: none; } .avatar-dropdown a:hover { background: var(--color-secondary); } /* RESPONSIVE */ @media (max-width: 768px) { header.navbar { flex-direction: column; align-items: flex-start; padding: 12px 15px; gap: 8px; } .auth-buttons { width: 100%; justify-content: space-between; } .avatar-dropdown { top: 45px; right: 0; min-width: 150px; } }
</style>
<header class="navbar">
    <div class="logo">
        <a href="index.php">
            <div class="logo-circle"></div>
            <span>ChatApp</span>
        </a>
    </div>
    <nav class="main-nav">
        <a href="index.php">HOME</a>
        <a href="posts.php">POSTS</a>
        <a href="chat.php">CHAT</a>
        <a href="friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'images/default-avatar.jpg'), '/'); ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-text">Login</a>
            <a href="register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
</header>
