<?php
session_start();
require_once '../../Handler/db.php'; // Đi lên 2 cấp
require_once '../../Handler/FriendHandler/friend_helpers.php'; // Đi lên 2 cấp
require_once '../../Handler/PostHandler/post_helpers.php';

// === 1. LẤY DỮ LIỆU ===

// Lấy ID của người xem (bạn)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$current_user_id = (int)$_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? 'Guest';
$current_user_avatar = $_SESSION['avatar'] ?? 'uploads/default-avatar.jpg';

// Lấy ID của chủ nhân trang cá nhân (từ URL)
$profile_user_id = (int)($_GET['id'] ?? 0);
if ($profile_user_id <= 0) {
    header("Location: posts.php"); // Nếu ID không hợp lệ, về trang posts
    exit;
}

// === 2. LẤY THÔNG TIN CHỦ TRANG VÀ KIỂM TRA QUAN HỆ ===
$user_info = null;
$is_self = false;
$is_friend = false;

// Lấy thông tin user
$stmt_user = $conn->prepare("SELECT Username, AvatarPath, FullName, CreatedAt FROM users WHERE UserId = ?");
$stmt_user->bind_param("i", $profile_user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows == 0) {
    // Không tìm thấy user
    $_SESSION['error_message'] = "Không tìm thấy người dùng này.";
    header("Location: posts.php");
    exit;
}
$user_info = $result_user->fetch_assoc();
$stmt_user->close();

// Kiểm tra quan hệ
if ($current_user_id === $profile_user_id) {
    $is_self = true;
} else {
    // Kiểm tra xem có phải là bạn bè không
    $stmt_friend = $conn->prepare("SELECT FriendId FROM friends WHERE IsConfirmed = 1 AND 
                                    ((UserId = ? AND FriendUserId = ?) OR (UserId = ? AND FriendUserId = ?))");
    $stmt_friend->bind_param("iiii", $current_user_id, $profile_user_id, $profile_user_id, $current_user_id);
    $stmt_friend->execute();
    $is_friend = $stmt_friend->get_result()->num_rows > 0;
    $stmt_friend->close();
}

// === 3. XÂY DỰNG CÂU QUERY CHO BÀI ĐĂNG (DỰA TRÊN QUYỀN RIÊNG TƯ) ===
$privacy_sql = " AND (p.Privacy = 'public'"; // Mọi người luôn thấy public
if ($is_self || $is_friend) {
    // Nếu là chủ nhân HOẶC là bạn bè, thì thấy cả bài 'friends'
    $privacy_sql .= " OR p.Privacy = 'friends'";
}
$privacy_sql .= ")";

// Lấy tất cả bài đăng CỦA NGƯỜI NÀY, tuân thủ quyền riêng tư
$sql_posts = "SELECT p.PostId, p.UserId, p.Content, p.Title, p.PostType, p.PostedAt, 
                     p.Privacy, 
                     u.Username, u.AvatarPath 
              FROM posts p
              JOIN users u ON p.UserId = u.UserId
              WHERE 
                  p.UserId = ?  -- CHỈ lấy bài của người này
                  $privacy_sql  -- Áp dụng điều kiện riêng tư
              ORDER BY p.PostedAt DESC";

$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $profile_user_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

// Lấy tất cả emotes (cho phần reactions)
$emotes_map = [];
$result_emotes = $conn->query("SELECT * FROM emotes");
while ($row = $result_emotes->fetch_assoc()) {
    $emotes_map[$row['EmoteId']] = ['unicode' => $row['EmoteUnicode'], 'name' => $row['EmoteName']];
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang cá nhân của <?php echo htmlspecialchars($user_info['Username']); ?></title>
    <link rel="icon" type="image/x-icon" href="/ChatApp/Favicon64x64.ico"> 
    <link rel="stylesheet" href="../../css/style.css"> 
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
        }
        .profile-header {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--color-border);
        }
        .profile-info h1 {
            margin: 0 0 5px 0;
            color: var(--color-text);
        }
        .profile-info span {
            font-size: 1rem;
            color: var(--color-text-muted);
        }
        .profile-actions {
            margin-left: auto; /* Đẩy nút về cuối */
        }
        .profile-actions .btn-edit {
            background-color: var(--color-secondary);
            color: var(--color-text);
            border: 1px solid var(--color-border);
            padding: 8px 15px;
            font-weight: bold;
        }
        .profile-actions .btn-action {
            background-color: var(--color-accent);
            color: white;
            border: none;
            padding: 8px 15px;
            font-weight: bold;
        }
        
        .profile-tabs {
            display: flex;
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-bottom: none;
        }
        .profile-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            color: var(--color-text-muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        .profile-tab.active {
            color: var(--color-accent);
            border-bottom-color: var(--color-accent);
        }
        
        /* Ẩn tab content ban đầu */
        .profile-content { display: none; }
        .profile-content.active { display: block; }
        
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 5px;
            background: var(--color-card);
            padding: 5px;
            border: 1px solid var(--color-border);
        }
        .photo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            cursor: pointer;
        }
        .page-content {
            flex-grow: 1; display: flex; justify-content: center;
            padding: 50px 20px;
            background-color: var(--color-bg); 
        }
        .post-feed { width: 100%; max-width: 700px; }
        .post-feed-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px;
        }
        .post-feed-header h1 { 
            color: var(--color-text);
            letter-spacing: 2px; 
        }
        .btn-create-post {
            padding: 10px 20px;
            background-color: var(--color-accent); 
            color: var(--color-card);
            text-decoration: none; border-radius: 5px; font-weight: bold;
            transition: background-color 0.3s ease;
            margin-left: 10px; /* Thêm khoảng cách */
        }
        .btn-create-post:hover { background-color: #4A88B8; }

        /* Card bài đăng */
        .post-card {
            background-color: var(--color-card); 
            border-radius: 8px; margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); 
            overflow: hidden;
            border: 1px solid var(--color-border);
        }
        .post-header { display: flex; align-items: top; padding: 15px 20px; }
        .post-avatar {
            width: 45px; height: 45px; border-radius: 50%;
            margin-right: 15px; border: 2px solid #EEE;
        }
        .post-user-info { display: flex; flex-direction: column; flex-grow: 1; }
        .post-username { 
            font-weight: bold; 
            color: var(--color-accent); 
            font-size: 1.1em; 
        }
        .post-time { font-size: 0.8em; color: var(--color-text-muted); }
        
        .post-privacy-icon {
            font-size: 0.75em;
            color: var(--color-text-muted);
            margin-top: 3px;
            font-weight: bold;
        }
        
        .post-content {
            padding: 0 20px 15px 40px;
            line-height: 1.6;
            word-wrap: break-word;
            color: var(--color-text);
        }
        
        /* CSS CHO ALBUM GRID */
        .post-album-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr); 
            gap: 5px; 
            margin: 0 20px 15px 20px; 
        }
        .post-album-grid img {
            width: 100%;
            height: 250px; 
            object-fit: cover;
            border-radius: 5px;
            background-color: #EEE;
        }
        /* CSS đặc biệt nếu chỉ có 1 ảnh */
        .post-album-grid.single-image {
            grid-template-columns: 1fr; /* 1 cột */
        }
        .post-album-grid.single-image img {
            height: auto; /* Chiều cao tự động */
            max-height: 500px;
        }
        /* CSS đặc biệt nếu có 3 ảnh */
        .post-album-grid.three-images {
             grid-template-columns: repeat(2, 1fr);
        }
        .post-album-grid.three-images img:first-child {
            grid-column: 1 / 3; /* Ảnh đầu tiên chiếm 2 cột */
            height: 300px; /* Cao hơn 1 chút */
        }

        /* Tương tác: Reactions (AJAX) */
        .post-interactions {
            padding: 10px 20px; border-top: 1px solid #EEE;
            display: flex; justify-content: space-between; align-items: center;
        }
        .reaction-buttons-wrapper { display: flex; gap: 5px; }
        .reaction-btn {
            background: var(--color-secondary); 
            border: 1px solid #DDD;
            border-radius: 5px;
            font-size: 1.2em;
            cursor: pointer;
            padding: 5px 8px;
            transition: transform 0.1s;
        }
        .reaction-btn:hover {
            transform: scale(1.1);
            background: #DDD;
        }
        .reaction-btn.active { 
            background: #DDD;
            border-color: var(--color-accent);
            transform: scale(1.1);
        }

        .post-stats { font-size: 0.9em; color: var(--color-text-muted); }
        .top-emotes-display { margin-right: 5px; }

        /* Khu vực bình luận */
        .comment-section {
            padding: 10px 20px 15px 20px;
            border-top: 1px solid #EEE;
            background-color: #F9F9F9;
        }
        .comments-list { display: flex; flex-direction: column; gap: 10px; }
        .comment { display: flex; gap: 10px; font-size: 0.9em; }
        .comment-avatar {
            width: 30px; height: 30px; border-radius: 50%;
            flex-shrink: 0;
        }
        .comment-bubble {
            display: flex; flex-direction: column;
            width: 100%;
        }
        .comment-content {
            background-color: var(--received-bubble-bg, #E9E9E9);
            padding: 8px 12px;
            border-radius: 10px; display: inline-block; max-width: fit-content;
        }
        .comment-username { 
            font-weight: bold; 
            color: var(--color-accent);
            margin-right: 5px; 
        }
        .comment-text { color: var(--color-text); }
        .comment-meta {
            margin-top: 4px;
            font-size: 0.75em;
            color: var(--color-text-muted);
        }
        .comment-time {
            display: inline-block;
        }
        
        /* Bình luận trả lời */
        .comment-actions { margin-top: 3px; }
        .reply-btn {
            background: none; border: none; color: var(--color-text-muted);
            font-size: 0.8em; cursor: pointer; padding: 0;
            text-decoration: none;
        }
        .reply-btn:hover { color: var(--color-text); }
        .comment-replies { 
            margin-left: 40px; 
            padding-top: 10px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .reply-container { 
             margin-left: 40px; 
             display: flex; flex-direction: column; gap: 10px;
             padding-top: 10px;
        }

        /* Form đăng bình luận */
        .comment-form-container { padding-top: 15px; border-top: 1px solid #EEE; margin-top: 15px; }
        .comment-form { display: flex; gap: 10px; }
        .comment-input {
            flex-grow: 1; padding: 8px 12px; border-radius: 15px;
            border: 1px solid #CCC; 
            background-color: #FFFFFF; 
            color: var(--color-text);
            font-family: 'Roboto Mono', monospace;
        }
        .comment-submit-btn {
            background-color: var(--color-accent);
            border: none;
            color: var(--color-card);
            padding: 8px 15px; border-radius: 15px;
            font-weight: bold; cursor: pointer;
        }
        .reply-info {
            font-size: 0.8em; color: var(--color-text-muted);
            margin-bottom: 5px;
        }
        .cancel-reply-btn {
            background: none; border: none; color: var(--color-danger);
            cursor: pointer; margin-left: 5px;
        }

        /* CSS CHO MENU TÙY CHỌN (Sửa/Xóa) */
        .post-options { position: relative; }
        .options-btn {
            background: none; border: none; color: var(--color-text-muted);
            font-size: 1.5em; cursor: pointer; padding: 5px; line-height: 1;
        }
        .options-btn:hover { color: var(--color-text); }
        .options-dropdown {
            display: none; position: absolute; right: 0; top: 30px;
            background-color: var(--color-card);
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            overflow: hidden; z-index: 10;
            border: 1px solid #EEE;
            width: 150px;
        }
        .options-dropdown a, .options-dropdown button {
            display: block; padding: 10px 15px; color: var(--color-text);
            text-decoration: none; font-size: 0.9em; background: none;
            border: none; width: 100%; text-align: left; cursor: pointer;
        }
        .options-dropdown a:hover, .options-dropdown button:hover { 
            background-color: var(--color-bg); 
            color: black;
        }
        .options-dropdown .delete-btn:hover,
        .options-dropdown .unfriend-btn:hover,
        .options-dropdown .report-btn:hover {
            background-color: var(--color-bg); /* Giữ màu đỏ */
            color: black;
        }
        .options-dropdown.show { display: block; }

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
<body class="font-inter light-theme">

<header class="navbar">
    <div class="logo">
        <div class="logo">
        <a href="../../index.php">
            <div class="logo-circle"><img src="/ChatApp/ChatApp_Logo.ico" alt="Logo"></div>
            <span>ChatApp</span>
        </a>
    </div>
    </div>
    <nav class="main-nav">
        <a href="../../index.php">HOME</a> 
        <a href="../PostPages/posts.php">POSTS</a> 
        <a href="../ChatPages/chat.php">CHAT</a> 
        <a href="../FriendPages/friends.php">FRIENDS</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <a href="../../Admin/index.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'), '/'); ?>
                <img src="../../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn" onerror="this.src='../../uploads/default-avatar.jpg'">
            <div class="avatar-dropdown" id="avatarDropdown">
                <a href="../ProfilePages/Profile.php?id=<?php echo $current_user_id; ?>">Trang cá nhân của tôi</a>
                <a href="../ProfilePages/edit_profile.php">Chỉnh sửa hồ sơ</a>
                <a href="../hidden_list.php">Quản lý Ẩn</a>
                <a href="../blocked_list.php">Quản lý Chặn</a>
                <a href="../../Handler/logout.php">Logout</a>
            </div>
            </div>
        <?php else: ?>
            <a href="Pages/login.php" class="btn-text">Login</a>
            <a href="Pages/register.php" class="btn-text">Register</a>
        <?php endif; ?>
    </div>
</header>

<main class="page-content">
    <div class="profile-container">
        
        <div class="profile-header">
            <img src="../../<?php echo htmlspecialchars($user_info['AvatarPath'] ?: 'uploads/default-avatar.jpg'); ?>" alt="Avatar" class="profile-avatar">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user_info['FullName'] ?: $user_info['Username']); ?></h1>
                <span>@<?php echo htmlspecialchars($user_info['Username']); ?></span>
            </div>
            <div class="profile-actions">
                <?php if ($is_self): ?>
                    <a href="../ProfilePages/edit_profile.php" class="btn-edit">Chỉnh sửa hồ sơ</a>
                <?php else: ?>
                    <button class="btn-action" onclick="toggleGlobalProfile(<?php echo $profile_user_id; ?>)">
                        Hành động
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-tabs">
            <div class="profile-tab active" data-tab="posts">Bài đăng</div>
            <div class="profile-tab" data-tab="photos">Ảnh</div>
        </div>

        <div id="tab-content-posts" class="profile-content active">
            <div class="post-feed">
                <?php
                if ($result_posts->num_rows > 0):
                    while($post = $result_posts->fetch_assoc()):
                        $post_id = $post['PostId'];
                ?>
                
                <div class="post-card" id="post-<?php echo $post_id; ?>" data-user-id="<?php echo $post['UserId']; ?>">
                    <div class="post-header">
                        <img src="../../<?php echo htmlspecialchars($post['AvatarPath'] ?: 'uploads/default-avatar.jpg'); ?>" alt="Avatar" class="post-avatar">
                        <div class="post-user-info">
                            <a href="profile.php?id=<?php echo $post['UserId']; ?>" class="post-username-link">
                                <span class="post-username"><?php echo htmlspecialchars($post['Username']); ?></span>
                            </a>
                            <span class="post-time"><?php echo date('H:i, d/m/Y', strtotime($post['PostedAt'])); ?></span>
                            <span class="post-privacy-icon">
                                <?php echo ($post['Privacy'] == 'public') ? '🌍 Công khai' : '👥 Bạn bè'; ?>
                            </span>
                        </div>
                        
                        <div class="post-options">
                            <button class="options-btn" onclick="toggleOptions(<?php echo $post_id; ?>)">&#8942;</button>
                            <div class="options-dropdown" id="options-<?php echo $post_id; ?>">
                                <?php if ($post['UserId'] == $current_user_id): // Nếu là bài của TÔI ?>
                                    <?php if ($post['PostType'] == 'status'): ?>
                                        <a href="../../Pages/PostPages/edit_post.php?id=<?php echo $post_id; ?>">Chỉnh sửa</a>
                                    <?php endif; ?>
                                    <button class="delete-btn" onclick="deletePost(<?php echo $post_id; ?>)">Xóa bài đăng</button>
                                <?php else: // Nếu là bài của NGƯỜI KHÁC ?>
                                    <button onclick="hideFeed(<?php echo $post['UserId']; ?>)">Ẩn nhật ký của <?php echo htmlspecialchars($post['Username']); ?></button>
                                    <button onclick="blockUser(<?php echo $post['UserId']; ?>)">Chặn <?php echo htmlspecialchars($post['Username']); ?> xem nhật ký</button>
                                    <button class="report-btn" onclick="reportPost(<?php echo $post_id; ?>)">Báo xấu</button>
                                    <button class="unfriend-btn" onclick="unfriendUser(<?php echo $post['UserId']; ?>)">Xóa bạn</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div> 
                    
                        <?php if ($post['PostType'] == 'album' && !empty($post['Title'])): ?>
                            <h3 class="post-content" style="font-weight:bold;"><?php echo htmlspecialchars($post['Title']); ?></h3>
                        <?php endif; ?>
                        
                        <?php if (!empty($post['Content'])): ?>
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                        </div>
                        <?php endif; ?>
                
                    <?php
                    // (Code PHP lấy ảnh của bạn...)
                    $sql_images = "SELECT ImagePath FROM post_images WHERE PostId = ? ORDER BY ImageId ASC";
                    $stmt_images = $conn->prepare($sql_images);
                    $stmt_images->bind_param("i", $post_id);
                    $stmt_images->execute();
                    $result_images = $stmt_images->get_result();
                    $images = [];
                    while ($img = $result_images->fetch_assoc()) { $images[] = $img['ImagePath']; }
                    $stmt_images->close();
                    $image_count = count($images);

                    if ($image_count > 0):
                        $grid_class = '';
                        if ($image_count == 1) { $grid_class = 'single-image'; }
                        elseif ($image_count == 3) { $grid_class = 'three-images'; }
                    ?>
                        <div class="post-album-grid <?php echo $grid_class; ?>">
                            <?php foreach ($images as $image_path): ?>
                                <img src="../../<?php echo htmlspecialchars($image_path); ?>" alt="Ảnh bài đăng">
                            <?php endforeach; ?>
                        </div>
                    <?php 
                    endif; 
                    ?>

                <div class="post-interactions">
                        <div class="reaction-buttons-wrapper" id="reaction-wrapper-<?php echo $post_id; ?>">
                            <?php
                            $sql_user_emote = "SELECT EmoteId FROM postemotes WHERE PostId = ? AND UserId = ?";
                            $stmt_user_emote = $conn->prepare($sql_user_emote);
                            $stmt_user_emote->bind_param("ii", $post_id, $current_user_id);
                            $stmt_user_emote->execute();
                            $user_emote_result = $stmt_user_emote->get_result();
                            $user_emote_id = ($user_emote_result->num_rows > 0) ? $user_emote_result->fetch_assoc()['EmoteId'] : 0;
                            $stmt_user_emote->close();
                            
                            foreach ($emotes_map as $emote_id => $emote):
                                $is_active = ($user_emote_id == $emote_id) ? 'active' : '';
                            ?>
                                <button class="reaction-btn <?php echo $is_active; ?>" 
                                        data-emote-id="<?php echo $emote_id; ?>"
                                        onclick="handleReaction(<?php echo $post_id; ?>, <?php echo $emote_id; ?>)">
                                    <?php echo $emote['unicode']; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="post-stats" id="post-stats-<?php echo $post_id; ?>">
                            <?php
                            $sql_stats = "SELECT EmoteId, COUNT(*) as Count 
                                          FROM postemotes 
                                          WHERE PostId = ? 
                                          GROUP BY EmoteId 
                                          ORDER BY Count DESC";
                            $stmt_stats = $conn->prepare($sql_stats);
                            $stmt_stats->bind_param("i", $post_id);
                            $stmt_stats->execute();
                            $stats_result = $stmt_stats->get_result();
                            $total_reactions = 0;
                            $top_emotes_html = '';
                            while($row = $stats_result->fetch_assoc()) {
                                $total_reactions += $row['Count'];
                                $top_emotes_html .= $emotes_map[$row['EmoteId']]['unicode'];
                            }
                            $stmt_stats->close();
                            ?>
                            <span class="top-emotes-display"><?php echo $top_emotes_html; ?></span>
                            <span class="total-reactions-count"><?php echo $total_reactions > 0 ? $total_reactions : ''; ?></span>
                        </div>
                    </div>

                    <div class="comment-section">
                        <?php
                        // Lấy và sắp xếp TẤT CẢ bình luận
                        $sql_comments = "SELECT c.CommentId, c.Content, c.ParentCommentId, c.CommentedAt, u.UserId, u.Username, u.AvatarPath
                                         FROM comments c
                                         JOIN users u ON c.UserId = u.UserId
                                         WHERE c.PostId = ?
                                         ORDER BY c.CommentedAt ASC";
                        $stmt_comments = $conn->prepare($sql_comments);
                        $stmt_comments->bind_param("i", $post_id);
                        $stmt_comments->execute();
                        $result_comments = $stmt_comments->get_result();
                        
                        $comments_by_parent = [];
                        while($comment = $result_comments->fetch_assoc()) {
                            $comments_by_parent[$comment['ParentCommentId']][] = $comment;
                        }
                        $stmt_comments->close();

                        // Gọi hàm đệ quy để render bình luận (chỉ render gốc)
                        renderComments($post_id, $comments_by_parent, NULL);
                        ?>

                        <div class="comment-form-container" id="comment-form-<?php echo $post_id; ?>">
                            
                            <div class="reply-info" id="reply-info-<?php echo $post_id; ?>" style="display: none;">
                                Đang trả lời <span id="reply-username-<?php echo $post_id; ?>"></span>
                                <button class="cancel-reply-btn" onclick="cancelReply(<?php echo $post_id; ?>)">[Hủy]</button>
                            </div>

                            <form class="comment-form" onsubmit="submitComment(event, <?php echo $post_id; ?>)">
                                <input type="hidden" id="parent-id-input-<?php echo $post_id; ?>" value="0">
                                <input type="text" id="comment-input-<?php echo $post_id; ?>" class="comment-input" placeholder="Viết bình luận..." required>
                                <button type="submit" class="comment-submit-btn">Gửi</button>
                            </form>
                        </div>
                    </div>

                    </div>
                <?php
                    endwhile;
                else:
                    echo "<p style='text-align: center; color: var(--color-text-muted); padding: 20px; background: var(--color-card);'>Không có bài đăng nào để hiển thị.</p>";
                endif;
                ?>
            </div>
        </div>
        
        <div id="tab-content-photos" class="profile-content">
            <div class="photo-gallery">
                <?php
                // Query để lấy TẤT CẢ ảnh (tuân thủ privacy)
                $sql_gallery = "SELECT pi.ImagePath 
                                FROM post_images pi
                                JOIN posts p ON pi.PostId = p.PostId
                                WHERE p.UserId = ?
                                $privacy_sql
                                ORDER BY p.PostedAt DESC";
                                
                $stmt_gallery = $conn->prepare($sql_gallery);
                $stmt_gallery->bind_param("i", $profile_user_id);
                $stmt_gallery->execute();
                $result_gallery = $stmt_gallery->get_result();
                
                if ($result_gallery->num_rows > 0):
                    while($img = $result_gallery->fetch_assoc()):
                ?>
                        <div class="photo-item">
                            <a href="../../<?php echo htmlspecialchars($img['ImagePath']); ?>" target="_blank">
                                <img src="../../<?php echo htmlspecialchars($img['ImagePath']); ?>" alt="Ảnh trong bộ sưu tập">
                            </a>
                        </div>
                <?php
                    endwhile;
                else:
                    echo "<p style='color: var(--color-text-muted); text-align: center; grid-column: 1 / -1;'>Không có ảnh nào.</p>";
                endif;
                $stmt_gallery->close();
                $conn->close();
                ?>
            </div>
        </div>

    </div>
</main>

<script>
    document.querySelectorAll('.profile-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            // Lấy data-tab (ví dụ: "posts")
            const tabName = tab.getAttribute('data-tab');

            // 1. Tắt active ở tất cả các tab
            document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
            // 2. Bật active cho tab vừa bấm
            tab.classList.add('active');

            // 3. Ẩn tất cả nội dung
            document.querySelectorAll('.profile-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // 4. Hiển thị nội dung của tab vừa bấm
            document.getElementById(`tab-content-${tabName}`).classList.add('active');
        });
    });
</script>

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

<script>
    // Script riêng của trang POSTS (cho like, comment, v.v.)
    
    // Truyền dữ liệu từ PHP sang JS
    const emotesMap = <?php echo json_encode($emotes_map); ?>;
    const currentUsername = <?php echo json_encode($current_username); ?>;
    const currentUserAvatar = '../../' + <?php echo json_encode($current_user_avatar); ?>;
    
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // -----------------------------
    // XỬ LÝ REACTION (AJAX)
    // -----------------------------
    function handleReaction(postId, emoteId) {
        // ... (Code này giữ nguyên) ...
        const statsContainer = document.getElementById(`post-stats-${postId}`);
        const topEmotesSpan = statsContainer.querySelector('.top-emotes-display');
        const totalCountSpan = statsContainer.querySelector('.total-reactions-count');
        const buttonWrapper = document.getElementById(`reaction-wrapper-${postId}`);
        const allButtons = buttonWrapper.querySelectorAll('.reaction-btn');

        fetch('../../Handler/PostHandler/handle-reaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${postId}&emote_id=${emoteId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                totalCountSpan.textContent = data.reactionCount > 0 ? data.reactionCount : '';
                let topHtml = '';
                data.topEmotes.forEach(id => {
                    topHtml += emotesMap[id]['unicode'];
                });
                topEmotesSpan.textContent = topHtml;
                allButtons.forEach(btn => btn.classList.remove('active'));
                if (data.currentUserEmote > 0) {
                    const activeButton = buttonWrapper.querySelector(`.reaction-btn[data-emote-id="${data.currentUserEmote}"]`);
                    if(activeButton) activeButton.classList.add('active');
                }
            } else {
                alert('Lỗi Reaction: ' + data.message);
            }
        })
        .catch(error => console.error('Lỗi khi reaction:', error));
    }

    // -----------------------
    // XỬ LÝ COMMENT (AJAX)
    // -----------------------
    function setReply(postId, commentId, username) {
        // ... (Code này giữ nguyên) ...
        document.getElementById(`parent-id-input-${postId}`).value = commentId;
        document.getElementById(`reply-username-${postId}`).textContent = username;
        document.getElementById(`reply-info-${postId}`).style.display = 'block';
        document.getElementById(`comment-input-${postId}`).focus();
    }

    function cancelReply(postId) {
        // ... (Code này giữ nguyên) ...
        document.getElementById(`parent-id-input-${postId}`).value = '0';
        document.getElementById(`reply-info-${postId}`).style.display = 'none';
    }

    function createCommentHtml(comment, postId) {
         // Giả định 'comment' object từ AJAX trả về có:
        // comment.UserId, comment.Username, comment.AvatarPath, 
        // comment.CommentId, comment.Content, comment.CommentedAt
        
         const avatarPath = comment.AvatarPath ? htmlspecialchars(comment.AvatarPath) : htmlspecialchars(currentUserAvatar);
        
        // SỬA LỖI 1: Đường dẫn tuyệt đối cho Avatar
        // (currentUserAvatar đã có ../../ rồi, nên chỉ cần chuẩn hóa)
         const avatar = avatarPath.startsWith('/ChatApp/') ? avatarPath : '/ChatApp/' + avatarPath.replace(/^\/+/, '');
        
        const username = comment.Username ? htmlspecialchars(comment.Username) : htmlspecialchars(currentUsername);
        
        // SỬA LỖI 2: Đường dẫn tuyệt đối cho Profile Link
        const profileLink = `/ChatApp/Pages/ProfilePages/Profile.php?id=${comment.UserId}`;
        
         let commentTime = '';
         if (comment.CommentedAt) {
            // (code định dạng thời gian của bạn giữ nguyên)
            const date = new Date(comment.CommentedAt);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            commentTime = `${day}/${month}/${year}, ${hours}:${minutes}`;
         }

         return `
            <div class="comment" id="comment-${comment.CommentId}">
                <a href="${profileLink}">
                    <img src="${avatar}" alt="Avatar" class="comment-avatar">
                </a>
                <div class="comment-bubble">
                    <div class="comment-content">
                        <a href="${profileLink}" class="comment-username-link">
                            <span class="comment-username">${username}:</span>
                        </a>
                        <span class="comment-text">${htmlspecialchars(comment.Content)}</span>
                    </div>
                    <div class="comment-meta">
                        <span class="comment-time">${commentTime}</span>
                    </div>
                    <div class="comment-actions">
                        <button class="reply-btn" onclick="setReply(${postId}, ${comment.CommentId}, '${username}')">Trả lời</button>
                    </div>
                </div>
            </div>
            <div class="reply-container" id="comment-replies-${comment.CommentId}"></div>
         `;
     }

    function submitComment(event, postId) {
        // ... (Code này giữ nguyên) ...
        event.preventDefault(); 
        const input = document.getElementById(`comment-input-${postId}`);
        const parentIdInput = document.getElementById(`parent-id-input-${postId}`);
        const content = input.value.trim();
        const parentId = parentIdInput.value;
        
        if (content === '') return;

        fetch('../../Handler/PostHandler/add-comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${postId}&content=${encodeURIComponent(content)}&parent_id=${parentId}`
        })
        .then(response => {
            if (!response.ok) { throw new Error(`Lỗi mạng: ${response.statusText}`); }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                input.value = '';
                cancelReply(postId);
                const comment = data.comment;
                const newCommentHtml = createCommentHtml(comment, postId);

                if (comment.ParentCommentId === null || comment.ParentCommentId == 0) {
                    let list = document.querySelector(`#post-${postId} .comments-list`);
                    if (!list) {
                        const commentSection = document.querySelector(`#post-${postId} .comment-section`);
                        const formContainer = document.querySelector(`#post-${postId} .comment-form-container`);
                        list = document.createElement('div');
                        list.className = 'comments-list';
                        commentSection.insertBefore(list, formContainer);
                    }
                    list.innerHTML += newCommentHtml;
                } else {
                    const replyContainer = document.getElementById(`comment-replies-${comment.ParentCommentId}`);
                    if (replyContainer) {
                        replyContainer.innerHTML += newCommentHtml;
                    } else {
                        document.querySelector(`#post-${postId} .comments-list`).innerHTML += newCommentHtml;
                    }
                }
            } else {
                alert('Lỗi từ Server: ' + data.message);
            }
        })
        .catch(error => {
            alert(`LỖI JAVASCRIPT:\n${error.message}`);
            console.error('Lỗi khi bình luận:', error);
        });
    }

    // -----------------------
    // XỬ LÝ MENU TÙY CHỌN (Sửa/Xóa)
    // -----------------------
    function toggleOptions(postId) {
        // ... (Code này giữ nguyên) ...
        document.getElementById(`options-${postId}`).classList.toggle("show");
    }

    window.onclick = function(event) {
        // ... (Code này giữ nguyên) ...
        if (!event.target.matches('.options-btn')) {
            var dropdowns = document.getElementsByClassName("options-dropdown");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
    
    // ↓↓↓ THAY ĐỔI BẮT ĐẦU TỪ ĐÂY ↓↓↓

    function deletePost(postId) {
        // Gọi popup xác nhận toàn cục (thay vì confirm)
        showGlobalConfirm('Bạn có chắc chắn muốn xóa bài đăng này không?', () => {
            // Logic fetch được đưa vào trong callback
            fetch('../../Handler/PostHandler/delete-post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const postElement = document.getElementById(`post-${postId}`);
                    if (postElement) { postElement.remove(); }
                } else {
                    alert('Lỗi: ' + data.message); // Vẫn dùng alert cho thông báo lỗi
                }
            })
            .catch(error => console.error('Lỗi khi xóa bài đăng:', error));
        });
    }
    
    // -----------------------
    // 4 HÀM CHO CÁC TÍNH NĂNG MỚI (ĐÃ SỬA)
    // -----------------------

    function unfriendUser(userId) {
        // Gọi popup xác nhận toàn cục
        showGlobalConfirm('Bạn có chắc chắn muốn hủy kết bạn với người này?', () => {
            fetch('../../Handler/PostHandler/unfriend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message); // Dùng alert cho thông báo (thành công/thất bại)
                if (data.status === 'success') {
                    // Tải lại trang để cập nhật các nút "Xóa bạn" khác
                    window.location.reload(); 
                }
            })
            .catch(error => console.error('Lỗi khi hủy kết bạn:', error));
        });
    }

    function hideFeed(userId, postId) {
        // Gọi popup xác nhận toàn cục
        showGlobalConfirm('Bạn có muốn ẩn tất cả bài đăng từ người này?', () => {
            fetch('../../Handler/PostHandler/hide-feed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    // Xóa tất cả bài đăng của người đó khỏi DOM
                    document.querySelectorAll(`.post-card[data-user-id="${userId}"]`).forEach(post => post.remove());
                }
            })
            .catch(error => console.error('Lỗi khi ẩn feed:', error));
        });
    }

    function blockUser(userId) {
        // Gọi popup xác nhận toàn cục
        showGlobalConfirm('Người này sẽ không thấy bài đăng của bạn nữa. Bạn chắc chứ?', () => {
            fetch('../../Handler/PostHandler/block-user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            })
            .catch(error => console.error('Lỗi khi chặn:', error));
        });
    }

    function reportPost(postId) {
        // Gọi popup xác nhận toàn cục
        showGlobalConfirm('Bạn có chắc chắn muốn báo xấu bài đăng này?', () => {
            fetch('../../Handler/PostHandler/report-post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
            })
            .catch(error => console.error('Lỗi khi báo xấu:', error));
        });
    }
</script>

<?php 
    // Vẫn gọi các modal toàn cục
    render_global_profile_modal(
        '/ChatApp/Handler/FriendHandler/friend-handler.php',
        '/ChatApp/uploads/default-avatar.jpg',
        '/ChatApp'
    ); 
?>
</body>
</html>