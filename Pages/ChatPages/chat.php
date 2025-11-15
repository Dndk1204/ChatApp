<?php
session_start();
require_once '../../Handler/db.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = htmlspecialchars($_SESSION['username']);

// Cập nhật trạng thái online khi truy cập trang chat
if ($conn) {
    $sql_online = "UPDATE Users SET IsOnline = 1 WHERE UserId = ?";
    $stmt_online = $conn->prepare($sql_online);
    if ($stmt_online) {
        $stmt_online->bind_param("i", $current_user_id);
        $stmt_online->execute();
        $stmt_online->close();
    }

    // Đếm số tin nhắn chưa đọc
    $sql_unread = "SELECT COUNT(*) as UnreadCount FROM messages WHERE ReceiverId = ? AND IsRead = 0 AND IsDeleted = 0";
    $stmt_unread = $conn->prepare($sql_unread);
    $unread_count = 0;
    if ($stmt_unread) {
        $stmt_unread->bind_param("i", $current_user_id);
        $stmt_unread->execute();
        $result_unread = $stmt_unread->get_result();
        if ($result_unread && $row = $result_unread->fetch_assoc()) {
            $unread_count = $row['UnreadCount'];
        }
        $stmt_unread->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $unread_count > 0 ? "($unread_count) Bạn có $unread_count chưa đọc" : "Chat App - " . $current_username; ?></title>
    <link rel="stylesheet" href="./../../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
        main.form-page-content {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .chat-container {
            display: flex;
            height: 85vh;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--color-card);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .user-list {
            width: 30%;
            min-width: 250px;
            max-width: 350px;
            background-color: var(--color-primary);
            padding: 15px;
            overflow-y: auto;
            border-right: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
        }

        .user-list h3 {
            color: #FFFFFF;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 10px;
            flex-shrink: 0;
        }

        #search-user-input {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid var(--color-border);
            background-color: var(--color-secondary);
            color: var(--color-text);
            font-family: 'Roboto Mono', monospace;
            box-sizing: border-box;
        }
        #users-container {
            flex-grow: 1;
            overflow-y: auto;
        }
        .user-item {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s, border 0.2s;
            display: flex;
            align-items: center;
            justify-content: space-between;
            word-break: break-all;
            border: 1px solid transparent;
        }
        .user-item:hover {
            background-color: var(--color-primary-dark);
        }

        .user-item.active {
            font-weight: bold;
            border: 1px solid var(--color-accent);
            background-color: var(--color-secondary);
        }
        .user-item.active .status-indicator {
            border: 1px solid var(--color-accent);
        }

        .user-item.active .user-status-text {
            color: var(--color-text-muted);
        }

        .unread-badge {
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75em;
            font-weight: bold;
            min-width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-left: 5px;
        }

        .user-details {
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        .user-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .user-status-text {
            font-size: 0.8em;
            color: #aaa;
            flex-shrink: 0;
            margin-left: 10px;
        }
        .status-indicator.online { background-color: var(--color-success); }
        .status-indicator.offline { background-color: var(--color-text-muted); }

        /* === KHU VỰC CHAT === */
        .chat-area-wrapper {
            flex-grow: 1;
            display: flex;
            overflow: hidden;
        }

        .chat-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            width: 100%;
            transition: width 0.3s ease;
        }
        .chat-area.with-media-viewer { width: 70%; }

        /* Header */
        .chat-header {
            padding: 15px;
            background-color: var(--color-primary);
            color: #FFFFFFFF;
            font-size: 1.2em;
            font-weight: bold;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        /* Tin nhắn */
        #message-window {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            word-wrap: break-word;
            line-height: 1.4;
        }
        .sent {
            align-self: flex-end;
            background-color: var(--color-accent);
            color: var(--color-card);
            border-bottom-right-radius: 2px;
        }

        .received {
            align-self: flex-start;
            background-color: #EFF3E1FF !important;
            color: var(--color-text);
        }
        .message-text-content { white-space: pre-wrap; }
        .message-info {
            font-size: 0.75em;
            margin-top: 5px;
            opacity: 0.7;
            text-align: right;
        }
        .message-image {
            max-width: 100%;
            height: auto;
            max-height: 300px;
            border-radius: 8px;
            cursor: zoom-in;
            margin-top: 5px;
        }

        .message-username {
            color: #457B9D;
            font-weight: bold;
        }

        /* === NHẬP TIN NHẮN === */
        .message-input-area {
            padding: 10px 15px;
            background-color: var(--color-primary);
            border-top: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            flex-shrink: 0;
            position: relative; 
        }

        #emoji-picker {
            display: none; /* 1. Ẩn ban đầu */
            position: absolute;
            bottom: 100%; /* 2. Hiển thị ngay trên thanh input */
            left: 0;
            
            width: 300px; /* Độ rộng của bảng */
            background: #ffffff;
            border: 1px solid #D0E2E2; /* Dùng màu border của bạn */
            border-radius: 8px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10;
            margin-bottom: 5px; /* Khoảng cách đến thanh input */
        }

        #emoji-picker.open {
            display: flex; /* 3. Hiện ra khi có class 'open' */
            flex-wrap: wrap; /* Cho phép các emoji xuống dòng */
            gap: 5px; /* Khoảng cách giữa các emoji */
        }

        .emoji-item {
            font-size: 1.5rem; /* Kích cỡ emoji */
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.1s;
        }

        .emoji-item:hover {
            background: #f0f0f0; /* Màu khi di chuột qua */
        }
        
        .input-group {
            display: flex;
            flex-grow: 1;
            border-radius: 20px;
            background-color: var(--color-secondary);
            margin-right: 10px;
        }

        .message-input-area input[type="text"] {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 20px;
            border: none;
            background-color: transparent;
            color: var(--color-text);
            font-family: 'Roboto Mono', monospace;
            font-size: 1em;
            outline: none;
        }
        .input-button {
            background: none;
            border: none;
            color: var(--color-accent);
            font-size: 1.5em;
            cursor: pointer;
            padding: 0 10px;
            transition: color 0.2s;
            line-height: 1;
        }

        .input-button:hover { color: var(--color-text); }

        #send-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            background-color: var(--color-accent);
            color: var(--color-card);
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
            font-family: 'Roboto Mono', monospace;
        }

        #send-btn:disabled { background-color: var(--color-text-muted); cursor: not-allowed; }
        #send-btn:hover:not(:disabled) { background-color: var(--color-primary-dark); }

        /* === EMOJI PICKER === */
        #emoji-picker {
            position: absolute;
            bottom: 100%;
            left: 0;
            background: #1f1f1f;
            border: 1px solid #444;
            border-radius: 8px 8px 0 0;
            padding: 10px;
            display: none;
            z-index: 10;
            max-width: 300px;
        }
        #emoji-picker.open { display: block; }
        .emoji-item {
            cursor: pointer;
            font-size: 1.5em;
            padding: 5px;
            display: inline-block;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .emoji-item:hover { background: #333; }

        /* === MEDIA VIEWER === */
        #media-viewer {
            width: 30%;
            min-width: 200px;
            background-color: #F7F7F7FF;
            border-left: 1px solid #444;
            display: none;
            flex-direction: column;
            padding: 15px;
            overflow-y: auto;
            flex-shrink: 0;
        }
        #media-viewer.open { display: flex; }
        .media-viewer-title {
            color: #282525FF;
            font-size: 1.1em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #444;
            text-align: center;
        }
        .media-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .media-item {
            width: calc(50% - 5px);
            height: 100px;
            overflow: hidden;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid #444;
        }
        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                height: calc(100vh - 55px);
            }
            .user-list {
                width: 100%;
                max-width: 100%;
                height: 200px;
                min-height: 150px;
                border-right: none;
                border-bottom: 1px solid #444;
            }
            .chat-area-wrapper { flex-direction: column; }
            .chat-area, .chat-area.with-media-viewer { width: 100%; }
            #media-viewer {
                width: 100%;
                height: 200px;
                border-left: none;
                border-top: 1px solid #444;
            }
            .navbar { padding: 10px 20px; }
            .main-nav { display: none; }
        }

        p {
            padding: 0;
            margin: 0;
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
            <a href="../../Handler/admin_dashboard.php">ADMIN</a>
        <?php endif; ?>
    </nav>
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
            <div class="avatar-menu">
                <?php $avatar = ltrim(($_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'), '/'); ?>
                <img src="../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn" onerror="this.src='../../uploads/default-avatar.jpg'">
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

    <main class="form-page-content">
        <div class="chat-container">
            
            <div class="user-list">
                <h3>Người dùng</h3>
                <input type="text" id="search-user-input" placeholder="Tìm kiếm Username...">
                
                <div id="users-container">
                    <p style="text-align: center; color: #aaa;">Đang tải...</p>
                </div>
            </div>

            <div class="chat-area-wrapper">
                <div class="chat-area" id="chat-area">
                    <div id="chat-header" class="chat-header">
                        <p>Chọn một người dùng để bắt đầu chat</p>
                    </div>

                    <div id="message-window">
                        </div>
                    
                    <div class="message-input-area">
                        <div id="emoji-picker"></div>
                        
                        <div class="input-group">
                                                    
                        <button id="toggle-media-viewer-btn" type="button" class="input-button" style="font-size:1em;color:#f0f0f0;padding:5px 10px;border-radius:4px;" onclick="toggleMediaViewer()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="#75B7DDFF" class="bi bi-backpack4-fill" viewBox="0 0 16 16" stroke="#2B2D42" stroke-width="1">
                            <path d="M8 0a2 2 0 0 0-2 2H3.5a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h4v.5a.5.5 0 0 0 1 0V7h4a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H10a2 2 0 0 0-2-2m1 2a1 1 0 0 0-2 0zm-4 9v2h6v-2h-1v.5a.5.5 0 0 1-1 0V11z"/>
                            <path d="M14 7.599A3 3 0 0 1 12.5 8H9.415a1.5 1.5 0 0 1-2.83 0H3.5A3 3 0 0 1 2 7.599V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM4 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                        </button>
                    
                            <button id="emoji-btn" class="input-button" onclick="toggleEmojiPicker()" disabled>😀</button>
                            
                            <input type="file" id="file-input" accept="image/*" style="display:none;">
                            <button id="file-input-btn" class="input-button" onclick="document.getElementById('file-input').click()" disabled>🖼️</button>
                            
                            <input type="text" id="message-input" placeholder="Nhập tin nhắn..." disabled>
                        </div>
                        <button id="send-btn" onclick="sendMessage()" disabled>Gửi</button>

                    </div>
                </div>

                <div id="media-viewer">
                    <h4 class="media-viewer-title">Ảnh đã chia sẻ</h4>
                    <div id="media-grid" class="media-grid">
                        <p style="text-align: center; color: #aaa; font-size: 0.9em;">Chưa có ảnh nào được chia sẻ trong cuộc hội thoại này.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

<script>
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        const currentUsername = <?php echo json_encode($current_username); ?>;
        let receiverId = null;
        let receiverUsername = null;
        const chatArea = document.getElementById('chat-area');
        const messageWindow = document.getElementById('message-window');
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const chatHeader = document.getElementById('chat-header');
        const usersContainer = document.getElementById('users-container');
        const searchInput = document.getElementById('search-user-input');
        const fileInput = document.getElementById('file-input');
        const toggleMediaViewerBtn = document.getElementById('toggle-media-viewer-btn');
        const mediaViewer = document.getElementById('media-viewer');
        const mediaGrid = document.getElementById('media-grid');
        const emojiPicker = document.getElementById('emoji-picker');
        const emojiButton = document.getElementById('emoji-btn');
        const fileInputBtn = document.getElementById('file-input-btn');
        
        let lastMessageTimestamp = 0; 
        let userPollInterval;
        let messagePollInterval;
        let mediaPollInterval;

        // Emoji list (có thể mở rộng)
        const emojis = ['😀', '😂', '😍', '🤔', '😎', '😭', '🥺', '👍', '❤️', '🔥', '🥳', '🤯'];
        
        // Khởi tạo emoji picker
        function initEmojiPicker() {
            emojiPicker.innerHTML = emojis.map(e => `<span class="emoji-item" data-emoji="${e}">${e}</span>`).join('');
            document.querySelectorAll('.emoji-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    messageInput.value += e.target.getAttribute('data-emoji');
                    messageInput.focus();
                    emojiPicker.classList.remove('open');
                });
            });
        }
        initEmojiPicker();

        // Bật/tắt emoji picker
        function toggleEmojiPicker() {
             if (receiverId === null) return;
             emojiPicker.classList.toggle('open');
        }

        // Tải danh sách người dùng và tìm kiếm
        function loadUsers(search_query = '') {
            const url = `./../../Handler/ChatHandler/fetch-users.php?search=${encodeURIComponent(search_query)}`;
            
            return fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error(`Lỗi ${response.status} khi tải người dùng.`);
                    return response.json();
                })
                .then(users => {
                    usersContainer.innerHTML = '';
                    if (users.length === 0 && search_query !== '') {
                        usersContainer.innerHTML = '<p style="text-align: center; color: #aaa; margin-top: 10px;">Không tìm thấy người dùng.</p>';
                        return users;
                    }
                    users.forEach(user => {
                        if (user.UserId != currentUserId) {
                            const userItem = document.createElement('div');
                            userItem.className = 'user-item';
                            userItem.setAttribute('data-user-id', user.UserId);
                            userItem.setAttribute('data-username', user.Username);
                            
                            const statusClass = user.IsOnline == 1 ? 'online' : 'offline';
                            const unreadCount = user.UnreadCount || 0;
                            
                            const unreadBadge = unreadCount > 0 
                                ? `<span class="unread-badge">${unreadCount}</span>` 
                                : '';
                            
                            userItem.innerHTML = `
                                <div class="user-details">
                                    <span class="status-indicator ${statusClass}"></span>
                                    <span class="user-name">${htmlspecialchars(user.Username)}</span>
                                </div>
                                ${unreadBadge}
                            `;
                            
                            userItem.onclick = () => selectUser(user.UserId, user.Username);
                            
                            if (user.UserId == receiverId) {
                                userItem.classList.add('active');
                            }
                            usersContainer.appendChild(userItem);
                        }
                    });
                    return users;
                })
                .catch(error => {
                    console.error('Lỗi khi tải danh sách người dùng:', error);
                    return [];
                });
        }
        
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim();
            loadUsers(query);
        });

        // Chọn người dùng để chat
        function selectUser(id, username) {
            if (receiverId === id) return;

            receiverId = id;
            receiverUsername = username;
            
            lastMessageTimestamp = 0; 
            messageWindow.innerHTML = ''; 

            chatHeader.innerHTML = `Chat với: ${htmlspecialchars(receiverUsername)}`;
            toggleMediaViewerBtn.style.display = 'block'; // Hiển thị nút Media

            messageInput.disabled = false;
            sendBtn.disabled = false;
            emojiButton.disabled = false;
            fileInputBtn.disabled = false;
            messageInput.focus();
            
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('active');
            });
            const activeUserItem = document.querySelector(`.user-item[data-user-id="${id}"]`);
            if(activeUserItem) {
                activeUserItem.classList.add('active');
            }

            if (messagePollInterval) clearInterval(messagePollInterval);
            if (mediaPollInterval) clearInterval(mediaPollInterval);
            
            loadMessages();
            loadMediaViewer();
            
            messagePollInterval = setInterval(loadMessages, 2000); 
            mediaPollInterval = setInterval(loadMediaViewer, 10000); 
        }

        // Tải và hiển thị tin nhắn
        function loadMessages() {
            if (!receiverId) return;

            const url = './../../Handler/ChatHandler/fetch-messages.php';

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=${receiverId}&last_timestamp=${lastMessageTimestamp}`
            })
            .then(response => {
                if (!response.ok) throw new Error(`Lỗi ${response.status} khi tải tin nhắn.`);
                return response.json();
            })
            .then(messages => {
                if (messages.length > 0) {
                    
                    const shouldScroll = messageWindow.scrollHeight - messageWindow.clientHeight <= messageWindow.scrollTop + 50;
                    
                    let htmlToAppend = '';
                    let latestTimestampInBatch = lastMessageTimestamp;

                    messages.forEach(msg => {
                        const isSent = msg.SenderId == currentUserId;
                        const messageClass = isSent ? 'sent' : 'received';
                        
                        const date = parseMySQLDateTime(msg.SentAt);
                        const newTimestamp = date.getTime();

                        if (newTimestamp > lastMessageTimestamp) {
                            const timeString = date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                            let contentHTML = '';

                            if (msg.MessageType === 'image' && msg.FilePath) {
                                const imagePath = msg.FilePath.startsWith('/') ? msg.FilePath.substring(1) : msg.FilePath;
                                contentHTML = `<img src="./../../${htmlspecialchars(imagePath)}" alt="Image" class="message-image" onclick="viewImage(this.src)">`;
                            } else {
                                contentHTML = `<div class="message-text-content">${linkify(htmlspecialchars(msg.Content))}</div>`;
                            }

                            htmlToAppend += `
                                <div class="message ${messageClass}">
                                    <div class="message-username">${isSent ? '' : htmlspecialchars(msg.SenderName)}</div>
                                    ${contentHTML}
                                    <div class="message-info">${timeString}</div>
                                </div>
                            `;
                            
                            if (newTimestamp > latestTimestampInBatch) {
                                latestTimestampInBatch = newTimestamp;
                            }
                        }
                    });
                    
                    lastMessageTimestamp = latestTimestampInBatch;

                    messageWindow.innerHTML += htmlToAppend;
                    
                    if (shouldScroll || (messages.length > 0 && lastMessageTimestamp === latestTimestampInBatch && messageWindow.innerHTML === htmlToAppend)) {
                         messageWindow.scrollTop = messageWindow.scrollHeight;
                    }
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải tin nhắn:', error);
            });
        }
        
        // Tải media cho media viewer
        function loadMediaViewer() {
            if (!receiverId) return;
            
            const url = './../../Handler/ChatHandler/fetch-messages.php';
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=${receiverId}&last_timestamp=0` 
            })
            .then(response => response.json())
            .then(messages => {
                mediaGrid.innerHTML = '';
                let mediaCount = 0;
                
                messages.reverse().forEach(msg => {
                    if (msg.MessageType === 'image' && msg.FilePath) {
                        const imagePath = msg.FilePath.startsWith('/') ? msg.FilePath.substring(1) : msg.FilePath;
                        const mediaItem = document.createElement('div');
                        mediaItem.className = 'media-item';
                        mediaItem.innerHTML = `<img src="./../../${htmlspecialchars(imagePath)}" alt="Shared Image" onclick="viewImage(this.src)">`;
                        mediaGrid.appendChild(mediaItem);
                        mediaCount++;
                    }
                });

                if (mediaCount === 0) {
                    mediaGrid.innerHTML = '<p style="text-align: center; color: #aaa; font-size: 0.9em;">Chưa có ảnh nào được chia sẻ trong cuộc hội thoại này.</p>';
                }
            })
            .catch(error => console.error('Lỗi khi tải media:', error));
        }


        // Gửi tin nhắn text
        function sendMessage() {
            const content = messageInput.value.trim();
            if (content === '' || receiverId === null) return;

            const tempMessageContent = content;
            messageInput.value = ''; 
            messageInput.focus();
            
            const url = './../../Handler/ChatHandler/send-message.php';

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=${receiverId}&content=${encodeURIComponent(tempMessageContent)}` 
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `Lỗi HTTP ${response.status}`);
                    }).catch(() => {
                        throw new Error(`Lỗi Server không xác định (Mã: ${response.status})`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    loadMessages(); 
                } else {
                    throw new Error(data.message || 'Lỗi gửi tin nhắn không rõ.');
                }
            })
            .catch(error => {
                console.error('Lỗi khi gửi tin nhắn:', error);
                alert('Lỗi mạng. Không thể gửi tin nhắn. Chi tiết: ' + error.message);
                messageInput.value = tempMessageContent; 
            });
        }
        
        // Gửi ảnh/media
        fileInput.addEventListener('change', sendMedia);

        function sendMedia() {
            if (!receiverId || fileInput.files.length === 0) return;

            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('receiver_id', receiverId);
            formData.append('image', file);
            fileInput.value = '';
            
            const url = './../../Handler/ChatHandler/send-media.php';

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || `Lỗi HTTP ${response.status}`);
                    }).catch(() => {
                        throw new Error(`Lỗi Server không xác định (Mã: ${response.status})`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    loadMessages(); 
                    loadMediaViewer(); 
                } else {
                    throw new Error(data.message || 'Lỗi gửi ảnh không rõ.');
                }
            })
            .catch(error => {
                console.error('Lỗi khi gửi ảnh:', error);
                alert('Lỗi: ' + error.message);
            });
        }
        
        // Chuyển đổi media viewer
        function toggleMediaViewer() {
            const isOpen = mediaViewer.classList.toggle('open');
            if (isOpen) {
                chatArea.classList.add('with-media-viewer');
                loadMediaViewer();
            } else {
                chatArea.classList.remove('with-media-viewer');
            }
        }
        
        // Xem ảnh
        function viewImage(src) {
            window.open(src, '_blank');
        }

        function parseMySQLDateTime(dateTimeStr) {
            const parts = dateTimeStr.split(/[- :]/);
            return new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]);
        }
        
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return '';
            return str.replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
        }
        
        function linkify(inputText) {
            let replacedText;
            const replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
            replacedText = inputText.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');
            const replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
            replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" target="_blank">$2</a>');
            return replacedText;
        }
        
        messageInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); 
                sendMessage();
            }
        });
        
        document.addEventListener('click', (e) => {
             if (emojiPicker.classList.contains('open') && !emojiPicker.contains(e.target) && e.target !== emojiButton && !messageInput.contains(e.target)) {
                emojiPicker.classList.remove('open');
            }
        });
        
        const urlParams = new URLSearchParams(window.location.search);
        const friendIdFromUrl = urlParams.get('friend_id');
        
        loadUsers().then(users => {
            if (friendIdFromUrl) {
                const friendId = parseInt(friendIdFromUrl);
                const friendUser = users.find(u => u.UserId == friendId);
                if (friendUser && friendUser.UserId != currentUserId) {
                    selectUser(friendUser.UserId, friendUser.Username);
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, '', newUrl);
                }
            }
        });
        
        userPollInterval = setInterval(loadUsers, 5000);
        (function(){
            const avatarBtn = document.getElementById('avatarBtn');
            const avatarDropdown = document.getElementById('avatarDropdown');
            document.addEventListener('click', (e) => {
                if (avatarBtn && (e.target === avatarBtn || avatarBtn.contains(e.target))) {
                    avatarDropdown.classList.toggle('open');
                } else if (avatarDropdown && !avatarDropdown.contains(e.target)) {
                    avatarDropdown.classList.remove('open');
                }
            });
        })();
</script>
</body>
</html>