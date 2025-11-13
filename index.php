<?php
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp Home</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<style>
    /* === HERO SECTION === */
.hero-section {
    display: flex;
    align-items: center;       /* căn giữa theo chiều dọc */
    justify-content: center;   /* căn giữa theo chiều ngang */
    min-height: calc(100vh - 70px); /* full height trừ navbar (70px giả định) */
    background-color: #F1FAEE;
    text-align: center;
    padding: 20px;
}

.hero-section .center-content {
    max-width: 800px;
    color: var(--color-text);
}

.hero-section .app-title {
    font-size: 3em;
    font-weight: bold;
    margin-bottom: 10px;
    color: var(--color-accent);
}

.hero-section .tagline {
    font-size: 1.5em;
    margin-bottom: 5px;
    color: var(--color-text);
}

.hero-section .slogan {
    font-size: 1.2em;
    margin-bottom: 20px;
    color: var(--color-text-muted);
}

.hero-section .action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.hero-section .btn {
    padding: 12px 25px;
    border-radius: 25px;
    font-weight: bold;
    text-decoration: none;
    transition: background-color 0.2s, color 0.2s;
}

.hero-section .btn-primary {
    background-color: var(--color-accent);
    color: var(--color-card);
}

.hero-section .btn-primary:hover {
    background-color: var(--color-primary-dark);
}

.hero-section .btn-secondary {
    background-color: var(--color-secondary);
    color: var(--color-text);
}

.hero-section .btn-secondary:hover {
    background-color: var(--color-bg);
}
</style>
<body>
    <?php include 'navbar.php'; ?>

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