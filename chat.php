<?php
session_start();
require_once 'db.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = htmlspecialchars($_SESSION['username']);

// Cập nhật trạng thái online ngay khi truy cập trang chat
if ($conn) {
    $sql_online = "UPDATE Users SET IsOnline = 1 WHERE UserId = ?";
    $stmt_online = $conn->prepare($sql_online);
    if ($stmt_online) {
        $stmt_online->bind_param("i", $current_user_id);
        $stmt_online->execute();
        $stmt_online->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat App - <?php echo $current_username; ?></title>
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
        /* CSS bổ sung cho giao diện chat */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #1a1a1a; /* Match hero section */
        }
        main.form-page-content {
             /* Đảm bảo main chiếm không gian còn lại */
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px; /* Thêm padding cho màn hình nhỏ */
        }
        .chat-container {
            display: flex;
            /* height: calc(100vh - 70px); 70px là chiều cao navbar */
            height: 85vh; /* Chiều cao tương đối */
            width: 100%; /* Chiếm toàn bộ chiều rộng main */
            max-width: 1200px;
            margin: 0 auto;
            background-color: #333333;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }
        .user-list {
            width: 30%; /* Sử dụng % cho responsive */
            min-width: 250px; /* Chiều rộng tối thiểu */
            max-width: 350px; /* Chiều rộng tối đa */
            background-color: #2a2a2a;
            padding: 15px;
            overflow-y: auto;
            border-right: 1px solid #444;
            display: flex;
            flex-direction: column;
        }
        .chat-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Ngăn chat area tràn */
        }
        .chat-header {
            padding: 15px;
            background-color: #121212;
            color: #ff6666;
            font-size: 1.2em;
            font-weight: bold;
            border-bottom: 1px solid #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0; /* Không co lại */
        }
        #message-window {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .message-input-area {
            padding: 15px;
            background-color: #2a2a2a;
            border-top: 1px solid #444;
            display: flex;
            flex-shrink: 0; /* Không co lại */
        }
        .message-input-area input {
            flex-grow: 1;
            padding: 10px 15px;
            border-radius: 20px;
            border: none;
            background-color: #333333;
            color: #f0f0f0;
            margin-right: 10px;
            font-family: 'Roboto Mono', monospace;
            font-size: 1em;
        }
        .message-input-area button {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            background-color: #ff6666;
            color: #1a1a1a;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
            font-family: 'Roboto Mono', monospace;
        }
        .message-input-area button:disabled {
            background-color: #555;
            cursor: not-allowed;
        }
        .message-input-area button:hover:not(:disabled) {
            background-color: #ff8080;
        }
        
        /* Message Bubbles */
        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            word-wrap: break-word;
            line-height: 1.4;
        }
        .sent {
            align-self: flex-end;
            background-color: #ff6666;
            color: #1a1a1a;
            border-bottom-right-radius: 2px;
        }
        .received {
            align-self: flex-start;
            background-color: #555555;
            color: #f0f0f0;
            border-bottom-left-radius: 2px;
        }
        .message-info {
            font-size: 0.75em;
            margin-top: 5px;
            opacity: 0.7;
        }
        .message-username {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 0.9em;
        }
        
        /* User List Styling */
         #users-container {
            flex-grow: 1;
            overflow-y: auto;
         }
        .user-list h3 {
            color:#f0f0f0; 
            margin-bottom: 15px; 
            border-bottom: 1px solid #444; 
            padding-bottom: 10px;
            flex-shrink: 0;
        }
        .user-item {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: space-between;
            word-break: break-all;
        }
        .user-item:hover {
            background-color: #3e3e3e;
        }
        .user-item.active {
            background-color: #ff6666;
            color: #1a1a1a;
            font-weight: bold;
        }
        .user-item.active .status-indicator {
            background-color: #1a1a1a !important;
        }
        .user-item.active .user-status-text {
            color: #2a2a2a;
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
        .online { background-color: #4CAF50; }
        .offline { background-color: #888; }

        /* Responsive */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                height: calc(100vh - 55px); /* Điều chỉnh chiều cao navbar mobile */
            }
            .user-list {
                width: 100%;
                max-width: 100%;
                height: 200px; /* Chiều cao cố định cho danh sách user */
                min-height: 150px;
                border-right: none;
                border-bottom: 1px solid #444;
            }
            .chat-area {
                 /* Chiếm phần còn lại */
                 overflow: hidden;
            }
            .navbar {
                padding: 10px 20px;
            }
            .main-nav {
                display: none; /* Ẩn nav chính trên mobile */
            }
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
            <a href="index.php">HOME</a>
            <a href="posts.php">POSTS</a>
            <a href="friend_requests.php">FRIEND REQUESTS</a>
            <a href="friends.php">FRIENDS</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'):?>
                <a href="admin_dashboard.php">ADMIN DASHBOARD</a>
            <?php endif; ?>
        </nav>
        <div class="auth-buttons">
            <span class="logged-in-user">Xin chào, <?php echo $current_username; ?></span>
            <a href="logout.php" class="btn-text">Logout</a>
        </div>
    </header>

    <main class="form-page-content">
        <div class="chat-container">
            
            <!-- Danh sách người dùng -->
            <div class="user-list">
                <h3>Người dùng</h3>
                <div id="users-container">
                    <!-- Users will be loaded here via JS -->
                    <p style="text-align: center; color: #aaa;">Đang tải...</p>
                </div>
            </div>

            <!-- Khu vực chat -->
            <div class="chat-area">
                <div id="chat-header" class="chat-header">
                    Chọn một người dùng để bắt đầu chat
                </div>
                <div id="message-window">
                    <!-- Messages will be loaded here -->
                </div>
                <div class="message-input-area">
                    <input type="text" id="message-input" placeholder="Nhập tin nhắn..." disabled>
                    <button id="send-btn" onclick="sendMessage()" disabled>Gửi</button>
                </div>
            </div>

        </div>
    </main>

    <script>
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        const currentUsername = <?php echo json_encode($current_username); ?>;
        let receiverId = null;
        let receiverUsername = null;
        const messageWindow = document.getElementById('message-window');
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const chatHeader = document.getElementById('chat-header');
        const usersContainer = document.getElementById('users-container');
        
        // lastMessageTimestamp sẽ lưu mốc thời gian (dưới dạng số milliseconds)
        // của tin nhắn *cuối cùng* đã được hiển thị.
        let lastMessageTimestamp = 0; 
        
        let userPollInterval;
        let messagePollInterval;

        // --- HÀM TẢI DANH SÁCH NGƯỜI DÙNG ---
        function loadUsers() {
            const url = 'Handler/php-fetch-users.php';
            // console.log('Fetching users...'); // Debug

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error(`Lỗi ${response.status} khi tải người dùng.`);
                    return response.json();
                })
                .then(users => {
                    usersContainer.innerHTML = '';
                    users.forEach(user => {
                        if (user.UserId != currentUserId) {
                            const userItem = document.createElement('div');
                            userItem.className = 'user-item';
                            userItem.setAttribute('data-user-id', user.UserId);
                            userItem.setAttribute('data-username', user.Username);
                            
                            const statusClass = user.IsOnline == 1 ? 'online' : 'offline';
                            const statusText = user.IsOnline == 1 ? 'Online' : 'Offline';
                            
                            userItem.innerHTML = `
                                <div class="user-details">
                                    <span class="status-indicator ${statusClass}"></span>
                                    <span class="user-name">${htmlspecialchars(user.Username)}</span>
                                </div>
                                <span class="user-status-text">(${statusText})</span>
                            `;
                            
                            userItem.onclick = () => selectUser(user.UserId, user.Username);
                            
                            if (user.UserId == receiverId) {
                                userItem.classList.add('active');
                            }
                            usersContainer.appendChild(userItem);
                        }
                    });
                })
                .catch(error => console.error('Lỗi khi tải danh sách người dùng:', error));
        }
        

        // --- HÀM CHỌN NGƯỜI DÙNG ĐỂ CHAT ---
        function selectUser(id, username) {
            if (receiverId === id) return; // Không chọn lại người đang chat

            receiverId = id;
            receiverUsername = username;
            
            //Thông báo cho loadMessages biết đây là lần tải đầu tiên cho người dùng này
            lastMessageTimestamp = 0; 
            
            //Xóa sạch cửa sổ chat cũ
            messageWindow.innerHTML = ''; 

            chatHeader.innerHTML = `Chat với: ${htmlspecialchars(receiverUsername)}`;
            messageInput.disabled = false;
            sendBtn.disabled = false;
            messageInput.focus();
            
            // Cập nhật trạng thái active trong danh sách
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('active');
            });
            const activeUserItem = document.querySelector(`.user-item[data-user-id="${id}"]`);
            if(activeUserItem) {
                activeUserItem.classList.add('active');
            }

            // Xóa Polling cũ (nếu có) và bắt đầu tải ngay lập tức
            if (messagePollInterval) clearInterval(messagePollInterval);
            loadMessages(); // Tải lần đầu
            
            // Bắt đầu polling mới
            messagePollInterval = setInterval(loadMessages, 2000); 
        }

        // --- HÀM TẢI TIN NHẮN (Polling) ---
        function loadMessages() {
            if (!receiverId) return;

            const url = 'Handler/php-fetch-messages.php';
            // console.log(`Fetching messages since: ${lastMessageTimestamp}`); // Debug

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                // Gửi mốc thời gian (milliseconds) lên server
                body: `receiver_id=${receiverId}&last_timestamp=${lastMessageTimestamp}`
            })
            .then(response => {
                if (!response.ok) throw new Error(`Lỗi ${response.status} khi tải tin nhắn.`);
                return response.json();
            })
            .then(messages => {
                if (messages.length > 0) {
                    
                    // Kiểm tra xem người dùng có đang cuộn ở cuối cửa sổ không
                    const shouldScroll = messageWindow.scrollHeight - messageWindow.clientHeight <= messageWindow.scrollTop + 50;
                    
                    let htmlToAppend = '';
                    let latestTimestampInBatch = lastMessageTimestamp;

                    messages.forEach(msg => {
                        const isSent = msg.SenderId == currentUserId;
                        const messageClass = isSent ? 'sent' : 'received';
                        
                        //Chuyển đổi thời gian YYYY-MM-DD HH:MM:SS (từ MySQL) sang Date object
                        const date = parseMySQLDateTime(msg.SentAt);
                        const newTimestamp = date.getTime(); // Lấy milliseconds

                        // Đảm bảo không xử lý lại tin nhắn cũ
                        if (newTimestamp > lastMessageTimestamp) {
                            const timeString = date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

                            htmlToAppend += `
                                <div class="message ${messageClass}">
                                    <div class="message-username">${isSent ? 'Bạn' : htmlspecialchars(msg.SenderName)}</div>
                                    ${htmlspecialchars(msg.Content)}
                                    <div class="message-info">${timeString}</div>
                                </div>
                            `;
                            
                            // Cập nhật mốc thời gian mới nhất trong loạt tin nhắn này
                            if (newTimestamp > latestTimestampInBatch) {
                                latestTimestampInBatch = newTimestamp;
                            }
                        }
                    });
                    
                    // Cập nhật mốc thời gian toàn cục
                    lastMessageTimestamp = latestTimestampInBatch;

                    // Chỉ *nối* (append) tin nhắn mới vào cửa sổ
                    messageWindow.innerHTML += htmlToAppend;
                    
                    // Cuộn xuống nếu người dùng đang ở cuối
                    // Hoặc nếu đây là lần tải đầu tiên (lastMessageTimestamp vừa bị reset)
                    if (shouldScroll || (messages.length > 0 && lastMessageTimestamp === latestTimestampInBatch && messageWindow.innerHTML === htmlToAppend)) {
                         messageWindow.scrollTop = messageWindow.scrollHeight;
                    }
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải tin nhắn:', error);
                if (messagePollInterval) clearInterval(messagePollInterval);
            });
        }

        // Hàm tiện ích để chuyển đổi chuỗi DateTime của MySQL
        function parseMySQLDateTime(dateTimeStr) {
            // dateTimeStr có dạng "YYYY-MM-DD HH:MM:SS"
            const parts = dateTimeStr.split(/[- :]/);
            // new Date(year, monthIndex (0-11), day, hour, minute, second)
            // Chú ý: parts[1] - 1 vì tháng trong JS bắt đầu từ 0
            return new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]);
        }
        
        // Hàm tiện ích để tránh lỗi XSS
        function htmlspecialchars(str) {
            if (typeof str !== 'string') return '';
            return str.replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
        }


        // --- HÀM GỬI TIN NHẮN ---
        function sendMessage() {
            const content = messageInput.value.trim();
            if (content === '' || receiverId === null) return;

            const tempMessageContent = content; // Giữ lại nội dung
            messageInput.value = ''; // Xóa nội dung ngay lập tức
            messageInput.focus();
            
            const url = 'Handler/php-send-message.php';
            // console.log('Sending message...'); // Debug

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
                // Hoàn lại nội dung tin nhắn nếu có lỗi
                messageInput.value = tempMessageContent; 
            });
        }

        // Bắt sự kiện Enter để gửi tin nhắn
        messageInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Ngăn chặn xuống dòng
                sendMessage();
            }
        });
        
        // --- KHỞI ĐỘNG ỨNG DỤNG ---
        
        // Bắt đầu tải danh sách người dùng và lặp lại
        loadUsers();
        userPollInterval = setInterval(loadUsers, 5000); // 5 giây
    </script>

</body>
</html>