<?php
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Home</title>

    <link rel="stylesheet" href="./css/style.css">
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
            <?php if (isset($_SESSION['user_id'])):?>
                <a href="index.php">HOME</a>
                <a href="posts.php">POSTS</a>
                <a href="friend_requests.php">FRIEND REQUESTS</a>
                <a href="friends.php">FRIENDS</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'):?>
                    <a href="admin_dashboard.php">ADMIN DASHBOARD</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])):?>
                <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn-text">Logout</a>
            <?php else:?>
                <a href="login.php" class="btn-text">Login</a>
                <a href="register.php" class="btn-text">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="hero-section">
        <div class="center-content">
            <h1 class="app-title">CHATAPP</h1>
            <p class="tagline">Connect. Share. Inspire.</p>
            <p class="slogan">WELCOME TO THE FUTURE OF COMMUNICATION</p>
            <div class="action-buttons">
                <?php if (!isset($_SESSION['user_id'])):?>
                    <a href="login.php" class="btn btn-primary">SIGN IN</a>
                    <a href="register.php" class="btn btn-secondary">SIGN UP</a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>