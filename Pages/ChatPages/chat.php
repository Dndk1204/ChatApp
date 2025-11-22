<?php
session_start();
require_once '../../Handler/db.php';
require_once __DIR__ . '/../../Handler/FriendHandler/friend_helpers.php';

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
    <link rel="icon" type="image/x-icon" href="/ChatApp/favicon64x64.ico"> 
    <link rel="stylesheet" href="./../../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    <style>
        main.form-page-content {
            flex-grow: 1;
            display: flex;
            padding: 0;
            overflow: hidden; /* Ngăn main bị cuộn */
            height: calc(100vh - 60px); /* Chiều cao đầy đủ trừ header */
        }

        .chat-container {
            display: flex;
            height: 100%; /* Sửa thành 100% để lấp đầy main */
            width: 100%;
            max-width: none; /* Bỏ giới hạn chiều rộng */
            margin: 0;
            background-color: var(--color-card);
            border-radius: 0; /* Bỏ bo góc */
            overflow: hidden;
            box-shadow: none; /* Bỏ đổ bóng */
        }

        /* === CỘT 1: DANH SÁCH BẠN BÈ === */
        .user-list {
            /* ↓↓↓ THAY ĐỔI: Chuyển từ width sang flex: 1 ↓↓↓ */
            flex: 1;
            min-width: 250px; /* Giữ lại min-width */
            position: relative;
            background-color: var(--color-primary);
            padding: 15px; overflow-y: auto;
            border-right: 1px solid var(--color-border);
            display: flex; flex-direction: column;
        }
        /* (CSS .user-list h3, #search-user-input, #users-container... giữ nguyên) */
        .user-list h3 {
             color: #FFFFFF; margin-bottom: 15px;
             border-bottom: 1px solid var(--color-border);
             padding-bottom: 10px; flex-shrink: 0;
        }
        #search-user-input {
             width: 100%; padding: 8px 10px; margin-bottom: 15px; margin-top: 15px;
             border-radius: 5px; border: 1px solid var(--color-border);
             background-color: var(--color-secondary); color: var(--color-text);
             font-family: 'Roboto Mono', monospace; box-sizing: border-box;
        }
        #users-container { flex-grow: 1; overflow-y: auto; }
        .user-item {
             padding: 10px; margin-bottom: 5px; border-radius: 5px;
             cursor: pointer; transition: background-color 0.2s, border 0.2s;
             display: flex; align-items: center; justify-content: space-between;
             word-break: break-all; border: 1px solid transparent;
        }
        .user-item:hover { background-color: var(--color-primary-dark); }
        .user-item.active {
             font-weight: bold; border: 1px solid var(--color-accent);
             background-color: var(--color-secondary);
        }
        .user-item.active .status-indicator { border: 1px solid var(--color-accent); }
        .user-item.active .user-status-text { color: var(--color-text-muted); }
        .unread-badge {
             background-color: #ff4444; color: white; border-radius: 50%;
             padding: 2px 6px; font-size: 0.75em; font-weight: bold;
             min-width: 20px; display: flex; align-items: center;
             justify-content: center; flex-shrink: 0; margin-left: 5px;
        }
        .user-details {
             display: flex; align-items: center;
             overflow: hidden; gap: 8px;
        }
        .user-avatar {
             width: 32px; height: 32px; min-width: 32px; border-radius: 50%;
             object-fit: cover; background-color: var(--color-secondary);
        }
        .user-avatar.group-avatar {
            border-radius: 25%; /* Bo góc vuông thay vì tròn */
        }
        .user-name {
             white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .status-indicator {
             width: 10px; height: 10px; border-radius: 50%;
             margin-right: 10px; flex-shrink: 0;
        }
        .user-status-text {
             font-size: 0.8em; color: #aaa;
             flex-shrink: 0; margin-left: 10px;
        }
        .status-indicator.online { background-color: var(--color-success); }
        .status-indicator.offline { background-color: var(--color-text-muted); }

        .user-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative; /* Neo cho nút + */
        }
        .user-list-header h3 {
            margin-bottom: 0; /* Ghi đè CSS cũ */
            border-bottom: none;
            padding-bottom: 0;
        }

        .chat-tabs {
            display: flex;
            background-color: var(--color-primary-dark);
            border-radius: 5px;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .chat-tab {
            flex: 1;
            padding: 8px;
            background: none;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        .chat-tab.active {
            opacity: 1;
            border-bottom: 3px solid var(--color-secondary);
        }

        .users-container-wrapper {
            flex-grow: 1;
            position: relative; /* Cho phép 2 pane chồng lên nhau */
        }
        .user-list-pane {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            overflow-y: auto;
            display: none; /* Ẩn cả 2 pane */
        }
        .user-list-pane.active {
            display: block; /* Chỉ hiện pane active */
        }

        /* === KHU VỰC CHAT === */
        .chat-area {
            flex: 4; /* Tỷ lệ 2 */
            border-right: 1px solid var(--color-border);
            display: flex; 
            flex-direction: column;
            overflow: hidden;
            width: 100%; /* (Width 100% vẫn OK) */
        }

        /* Header */
        .chat-header {
             padding: 15px; background-color: var(--color-primary);
             color: #FFFFFFFF; font-size: 1.2em; font-weight: bold;
             border-bottom: 1px solid var(--color-border);
             display: flex; justify-content: space-between;
             align-items: center; flex-shrink: 0;
        }
        #message-window {
             flex-grow: 1; padding: 20px; overflow-y: auto;
             display: flex; flex-direction: column; gap: 10px;
        }
        .message {
             max-width: 70%; padding: 10px 15px; border-radius: 15px;
             word-wrap: break-word; line-height: 1.4;
        }
        .sent {
             align-self: flex-end; background-color: var(--color-accent);
             color: var(--color-card); border-bottom-right-radius: 2px;
        }
        .received {
             align-self: flex-start; background-color: #EFF3E1FF !important;
             color: var(--color-text);
             border-bottom-right-radius: 2px;
        }
        .message-text-content { white-space: pre-wrap; }
        .message-info {
             font-size: 0.75em; margin-top: 5px;
             opacity: 0.7; text-align: right;
        }
        .message-image {
             max-width: 100%; height: auto; max-height: 300px;
             border-radius: 8px; cursor: zoom-in; margin-top: 5px;
        }
        .unread-divider {
             display: flex; align-items: center; text-align: center;
             margin: 15px 0; color: var(--color-error, #E57373);
        }
        .unread-divider::before, .unread-divider::after {
             content: ''; flex: 1; border-bottom: 1px solid var(--color-border);
        }
        .unread-divider span {
             padding: 0 10px; font-size: 0.8em; font-weight: bold;
             text-transform: uppercase;
        }
        .message-username { color: #457B9D; font-weight: bold; }

        /* === NHẬP TIN NHẮN === */
        .message-input-area {
             padding: 10px 15px; background-color: var(--color-primary);
             border-top: 1px solid var(--color-border);
             display: flex; align-items: center;
             flex-shrink: 0; position: relative; 
        }
        #emoji-picker {
             display: none; position: absolute; bottom: 100%; left: 0;
             width: 300px; background: #ffffff;
             border: 1px solid #D0E2E2; border-radius: 8px;
             padding: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
             z-index: 10; margin-bottom: 5px;
        }
        #emoji-picker.open {
             display: flex; flex-wrap: wrap; gap: 5px;
        }
        .emoji-item {
             font-size: 1.5rem; cursor: pointer; padding: 4px;
             border-radius: 4px; transition: background 0.1s;
        }
        .emoji-item:hover { background: #f0f0f0; }
        .input-group {
             display: flex; flex-grow: 1; border-radius: 20px;
             background-color: var(--color-secondary); margin-right: 10px;
        }
        .message-input-area input[type="text"] {
             flex-grow: 1; padding: 10px 15px; border-radius: 20px;
             border: none; background-color: transparent;
             color: var(--color-text); font-family: 'Roboto Mono', monospace;
             font-size: 1em; outline: none;
        }
        .input-button {
             background: none; border: none; color: var(--color-accent);
             font-size: 1.5em; cursor: pointer; padding: 0 10px;
             transition: color 0.2s; line-height: 1;
        }
        .input-button:hover { color: var(--color-text); }
        #send-btn {
             padding: 10px 20px; border: none; border-radius: 20px;
             background-color: var(--color-accent); color: var(--color-card);
             font-weight: bold; cursor: pointer;
             transition: background-color 0.2s;
             font-family: 'Roboto Mono', monospace;
        }
        #send-btn:disabled { background-color: var(--color-text-muted); cursor: not-allowed; }
        #send-btn:hover:not(:disabled) { background-color: var(--color-primary-dark); }

        /* === MEDIA VIEWER === */
        #media-viewer {
            flex: 1; /* Tỷ lệ 1 */
            display: flex; /* Luôn hiển thị */
            min-width: 200px; /* Giữ lại min-width */
            background-color: #F7F7F7FF;
            border-left: 1px solid var(--color-border); /* Đổi màu border */
            flex-direction: column;
            padding: 15px;
            overflow-y: auto;
            flex-shrink: 0;
        }
        .media-viewer-title {
             color: #282525FF; font-size: 1.1em;
             margin-bottom: 15px; padding-bottom: 10px;
             border-bottom: 1px solid var(--color-border); /* Đổi màu border */
             text-align: center;
        }
        .media-grid {
             display: flex; flex-wrap: wrap; gap: 10px;
        }
        .media-item {
             width: calc(50% - 5px); height: 100px;
             overflow: hidden; border-radius: 4px;
             cursor: pointer; border: 1px solid var(--color-border); /* Đổi màu border */
        }
        .media-item img {
             width: 100%; height: 100%; object-fit: cover;
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

        /* Nút tạo nhóm (dấu +) */
        .btn-create-group {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: none;
            background-color: var(--color-secondary);
            color: var(--color-accent);
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-create-group:hover {
            background-color: #fff;
            transform: scale(1.1);
        }

        /* CSS cho Modal (Giống với popup của bạn) */
        /* Lớp phủ nền */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6); /* Tăng độ tối */
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1001;
            font-family: Arial, sans-serif;
            backdrop-filter: blur(5px); /* Thêm hiệu ứng mờ nền */
        }

        /* Khung modal */
        .modal-box {
            background: var(--color-card, #FFFFFF);
            padding: 0; /* Xóa padding cũ */
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 480px; /* Tăng nhẹ độ rộng */
            position: relative;
            overflow: hidden; /* Giúp bo góc header */
        }

        /* Header của modal */
        .modal-box h2 {
            margin: 0;
            padding: 20px 25px;
            background-color: var(--color-secondary, #F1FAEE);
            border-bottom: 1px solid var(--color-border, #D0E2E2);
            color: var(--color-accent, #457B9D);
            font-size: 1.25rem; /* Tăng cỡ chữ */
            text-align: center;
        }

        /* Nút đóng modal */
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem; /* Tăng cỡ chữ */
            color: var(--color-text-muted, #6C757D);
            cursor: pointer;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        .modal-close:hover {
            background-color: rgba(0,0,0,0.1);
        }

        /* Phần nội dung (form) */
        .modal-content {
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 20px; /* Tăng khoảng cách các mục */
        }

        /* Các nhóm form */
        .modal-box .form-group {
            margin-bottom: 0; /* Bỏ margin cũ */
        }
        .modal-box .form-group label {
            display: block;
            margin-bottom: 8px; /* Tăng K/C với input */
            font-weight: bold;
            color: var(--color-text, #333); /* Đổi màu chữ */
            font-size: 0.9rem;
        }
        .modal-box .form-group input[type="text"] {
            width: 100%;
            padding: 12px 15px; /* Tăng padding */
            border: 1px solid var(--color-border, #D0E2E2);
            background: #FFF; /* Nền trắng */
            border-radius: 5px;
            box-sizing: border-box; 
            font-size: 1rem;
            transition: all 0.2s;
        }
        .modal-box .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(69, 123, 157, 0.2); /* Hiệu ứng focus */
        }

        /* Nút submit */
        .modal-box .btn-submit {
            width: 100%;
            padding: 14px; /* Tăng padding */
            font-size: 1rem;
            font-weight: bold; /* In đậm */
            background: var(--color-accent, #457B9D);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }
        .modal-box .btn-submit:hover {
            background: #3a6885; /* Màu tối hơn khi hover */
        }
        .modal-box .btn-submit:active {
            transform: scale(0.98); /* Hiệu ứng nhấn */
        }

        /* Nút trên Header */
        .chat-header-actions {
            display: flex;
            gap: 10px;
        }
        .header-icon-btn {
            background: var(--color-secondary);
            border: 1px solid var(--color-border);
            border-radius: 5px;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px 8px;
            transition: all 0.2s;
        }
        .header-icon-btn:hover {
            background-color: #fff;
            transform: scale(1.1);
        }

        /* 3 Panel nội dung trong Cột 3 */
        .media-panel {
            display: none; /* Ẩn tất cả panel */
            flex-direction: column;
            height: 100%;
        }
        .media-panel.active {
            display: flex; /* Chỉ hiện panel có class .active */
        }
        /* Đảm bảo grid ảnh co giãn */
        #media-panel-media {
            overflow: hidden;
        }
        /* Đảm bảo panel thành viên là gốc để định vị */
        #media-panel-members {
            position: relative;
        }

        /* Style cho chính Popover */
        .member-action-popover {
            display: none; /* Ẩn mặc định */
            position: absolute;
            right: 15px; /* Căn phải */
            width: 180px;
            background: var(--color-card, #fff);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1010; /* Nổi lên trên */
            overflow: hidden; /* Bo góc các nút bên trong */
            flex-direction: column;
            padding: 5px; /* Thêm đệm */
        }

        /* Style cho các nút bên trong popover */
        .popover-action-btn {
            background: none;
            border: none;
            padding: 10px 12px;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 0.9rem;
            border-radius: 5px; /* Bo góc từng nút */
            transition: background-color 0.2s;
            color: var(--color-text);
        }
        .popover-action-btn:hover {
            background-color: var(--color-secondary, #f1faee);
        }

        /* Nút xóa màu đỏ */
        .popover-action-btn.remove {
            color: var(--color-error, #E57373);
        }
        .popover-action-btn.remove:hover {
            background-color: #ffebee; /* Màu nền đỏ nhạt */
            color: #d32f2f;
        }

        /* Cập nhật .member-item để có con trỏ khi là admin */
        .member-item.admin-clickable {
            cursor: pointer;
        }
        .member-item.admin-clickable:hover {
            background-color: var(--color-secondary);
        }
        #media-grid {
            flex-grow: 1;
            overflow-y: auto;
        }

        /* Style cho danh sách thành viên */
        .member-list-container {
            flex-grow: 1;
            overflow-y: auto;
        }
        .member-item {
            display: flex;
            align-items: center;
            padding: 8px 5px;
            border-bottom: 1px solid var(--color-border);
        }
        .member-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .member-item-info {
            flex-grow: 1;
        }
        .member-item-info span {
            font-weight: bold;
        }
        .member-item-info small {
            display: block;
            color: var(--color-text-muted);
            font-size: 0.8em;
        }
        .member-item-actions {
            display: flex;
            gap: 5px;
            flex-shrink: 0; /* Ngăn nút bị co lại */
        }
        .member-action-btn {
            background: none;
            border: 1px solid var(--color-border);
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            padding: 3px 6px;
            transition: all 0.2s;
        }
        .member-action-btn.remove {
            color: var(--color-error);
            border-color: var(--color-error);
        }
        .member-action-btn.remove:hover {
            background: var(--color-error);
            color: white;
        }
        .member-action-btn.promote {
            color: var(--color-accent);
            border-color: var(--color-accent);
        }
        .member-action-btn.promote:hover {
            background: var(--color-accent);
            color: white;
        }
        .remove-member-btn {
            background: none;
            border: none;
            color: var(--color-error);
            font-size: 1.2rem;
            cursor: pointer;
            display: none; /* Chỉ admin mới thấy (chúng ta sẽ thêm logic sau) */
        }

        /* 1. KHUNG CHỨA (Giữ nguyên max-height) */
        .friend-list-container {
            flex-grow: 1;
            max-height: 400px; /* Giữ chiều cao và thanh cuộn */
            overflow-y: auto;
            border: 1px solid var(--color-border);
            padding: 10px;
            background: var(--color-bg);
            border-radius: 5px;
        }

        /* 2. MỖI HÀNG (Sửa padding) */
        .friend-list-container .friend-invite-item {
            display: flex !important;
            flex-direction: row;
            align-items: center; /* Đây là dòng căn giữa DỌC */
            justify-content: space-between; /* Đây là dòng căn NGANG (đẩy 2 bên) */
            height: 48px;
            
            /* SỬA LỖI LỀ: 8px trên/dưới, 0px trái/phải */
            padding: 8px 10px; 
            
            border: 2px solid var(--color-border);
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }

        /* 3. KHỐI TRÁI (Avatar + Tên) */
        .friend-list-container .friend-invite-item .friend-invite-info {
            display: flex;
            align-items: center; /* Căn giữa avatar và tên với nhau */
            margin-right: auto;
            padding: 0;
            text-align: left;
            height: 32px;
        }

        /* 4. AVATAR (Giữ vertical-align) */
        .friend-list-container .friend-invite-item img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: auto;
            vertical-align: middle; /* Sửa lỗi căn dọc của img */
        }

        /* 5. CHECKBOX BÊN PHẢI (Sửa margin) */
        .friend-list-container .friend-invite-item input[type="checkbox"] {
            /* Reset về checkbox gốc (fix lỗi vỡ hình) */
            appearance: checkbox !important;
            -webkit-appearance: checkbox !important;
            -moz-appearance: checkbox !important;
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
            background: none !important;
            border: none !important;
            padding: 0 !important;
            transform: none !important;

            /* SỬA LỖI LỀ: Xóa bỏ mọi margin (kể cả margin-left: 10px) */
            margin-left: auto; 
            
            flex-shrink: 0;
        }
        /* 1. Định nghĩa hàng tin nhắn nhận (bao gồm avatar + bong bóng chat) */
        .message-row.received {
            display: flex;
            gap: 10px;                  /* Khoảng cách giữa avatar và tin nhắn */
            max-width: 80%;             /* Giới hạn chiều rộng tổng thể */
            align-self: flex-start;     /* Căn cả hàng sang trái */
            align-items: flex-start;    /* Căn avatar và tin nhắn theo đỉnh */
            padding: 4px 8px; border-radius: 5px;
        }

        /* 2. Định nghĩa avatar bên trong hàng */
        .message-row.received .chat-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid var(--color-border);
            margin-top: 5px; /* Căn chỉnh với bong bóng chat */
        }

        /* 3. Ghi đè CSS .received cũ KHI nó nằm trong .message-row */
        .message.received {
            align-self: auto;     /* Bỏ align-self: flex-start cũ đi */
            max-width: 100%;    /* Cho phép nó lấp đầy .message-row */
            /* Các thuộc tính khác như background, color... sẽ được kế thừa */
        }

        .modal-box .form-group .form-group-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--color-text, #333);
            font-size: 0.9rem;
        }

        /* === CSS CHO TIN NHẮN HỆ THỐNG === */
        .message.system {
            align-self: center; /* Tự căn giữa */
            background-color: var(--color-secondary);
            color: var(--color-text-muted);
            font-size: 0.8em;
            font-style: italic;
            padding: 5px 10px;
            border-radius: 10px;
            max-width: 80%;
            text-align: center;
            word-wrap: break-word;
        }
        .message.system .message-info {
            font-size: 0.9em;
            text-align: center;
            opacity: 0.8;
        }

        /* === CSS CHO NÚT ĐỔI AVATAR NHÓM === */
        .group-avatar-upload {
            padding: 15px 5px 5px 5px;
            border-top: 1px solid var(--color-border);
            margin-top: 10px;
        }
        .group-avatar-upload label {
            display: block;
            width: 100%;
            text-align: center;
            font-weight: bold;
            padding: 10px;
            border: 1px dashed var(--color-accent);
            color: var(--color-accent);
            background: var(--color-secondary);
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .group-avatar-upload label:hover {
            background: var(--color-card);
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
                <?php $avatar = $_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'; ?>
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

    <main class="form-page-content">
        <div class="chat-container">
            
            <div class="user-list">
                <div class="user-list-header">
                    <h3>Chat</h3>                     
                    <button id="create-group-btn" class="btn-create-group" title="Tạo nhóm mới">+</button>
                </div>
                <input type="text" id="search-user-input" placeholder="Tìm kiếm bạn bè, nhóm...">
            
                <div class="chat-tabs">
                    <button class="chat-tab active" data-tab="friends">Bạn bè</button>
                    <button class="chat-tab" data-tab="groups">Nhóm</button>
                </div>

                <div class="users-container-wrapper">
                    <div id="users-container-friends" class="user-list-pane active">
                        <p style="text-align: center; color: #aaa;">Đang tải...</p>
                    </div>
                    <div id="users-container-groups" class="user-list-pane">
                        <p style="text-align: center; color: #aaa;">Đang tải...</p>
                    </div>
                </div>
            </div>

            <div class="chat-area" id="chat-area">
                <div id="chat-header" class="chat-header">
                    <p id="chat-header-name">Chọn một người dùng để bắt đầu chat</p>
                    
                    <div class="chat-header-actions" id="group-actions-container" style="display: none;">
                        <button class="header-icon-btn" onclick="handleShowMedia()" title="Ảnh đã gửi">🖼️</button>
                        <button class="header-icon-btn" onclick="handleShowMembers()" title="Thành viên">👥</button>
                        <button class="header-icon-btn" onclick="handleShowInvite()" title="Mời bạn bè">+</button>
                    </div>
                </div>
                <div id="message-window">
                    </div>
                <div class="message-input-area">
                    <div id="emoji-picker"></div>
                    <div class="input-group">
                        
                        <button id="emoji-btn" class="input-button" onclick="toggleEmojiPicker()" disabled>😀</button>
                        <input type="file" id="file-input" accept="image/*" style="display:none;">
                        <button id="file-input-btn" class="input-button" onclick="document.getElementById('file-input').click()" disabled>🖼️</button>
                        <input type="text" id="message-input" placeholder="Nhập tin nhắn..." disabled>
                    </div>
                    <button id="send-btn" onclick="sendMessage()" disabled>Gửi</button>
                </div>
            </div>

            <div id="media-viewer">             
                <div id="media-panel-media" class="media-panel active">
                    <h4 class="media-viewer-title">Ảnh đã chia sẻ</h4>
                    <div id="media-grid" class="media-grid">
                        <p style="text-align: center; color: #aaa; font-size: 0.9em;">Chưa có ảnh nào được chia sẻ.</p>
                    </div>
                </div>

                <div id="media-panel-members" class="media-panel">
                    <h4 class="media-viewer-title">Thành viên nhóm</h4>
                    <div id="member-action-popover" class="member-action-popover">
                        <button type="button" class="popover-action-btn promote" id="popover-btn-promote">
                            Chuyển quyền Admin
                        </button>
                        <button type="button" class="popover-action-btn remove" id="popover-btn-remove">
                            Xóa khỏi nhóm
                        </button>
                    </div>
                    <div id="member-list" class="member-list-container">
                    </div>
                    <div class="group-avatar-upload" id="group-avatar-upload-container" style="display: none;">
                        <label for="group-avatar-input">
                            🖼️ Đổi ảnh đại diện nhóm
                        </label>
                        <input type="file" id="group-avatar-input" accept="image/png, image/jpeg, image/gif" style="display: none;">
                    </div>
                    <div id="delete-group-container" style="display: none; padding: 15px 5px 5px 5px; border-top: 1px solid var(--color-border); margin-top: 10px;">
                        <button type="button" id="delete-group-btn" class="member-action-btn remove" style="width: 100%; text-align: center; font-weight: bold;">
                            XÓA NHÓM NÀY
                        </button>
                    </div>
                </div>

                <div id="media-panel-invite" class="media-panel">
                    <h4 class="media-viewer-title">Mời bạn bè vào nhóm</h4>
                    <div id="invite-list" class="friend-list-container">
                        </div>
                    <button id="invite-btn" class="btn-submit" style="width: 100%; margin-top: 10px;">Mời</button>
                </div>

            </div>
            
        </div>
    </main>
    <div id="create-group-overlay" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeCreateGroupModal()">✕</button>
            <h2>Tạo nhóm chat mới</h2>
            
            <div class="modal-content">
                <div class="form-group">
                    <label for="group-name-input">Tên nhóm:</label>
                    <input type="text" id="group-name-input" placeholder="Nhập tên nhóm...">
                </div>
                
                <div class="form-group">
                    <p>Mời bạn bè (chọn bạn bè để thêm):</p>
                    <div id="invite-friend-list" class="friend-list-container">
                        </div>
                </div>
                
                <button id="submit-create-group" class="btn-submit">Tạo Nhóm</button>
            </div>
        </div>
    </div>

<script>
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        const currentUsername = <?php echo json_encode($current_username); ?>;
        let selectedReceiverId = null; // Đổi tên để rõ ràng
        let selectedGroupId = null; // ID của nhóm đang chat
        let selectedName = null; // Tên của người hoặc nhóm
        const chatArea = document.getElementById('chat-area');
        const messageWindow = document.getElementById('message-window');
        const messageInput = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-btn');
        const chatHeader = document.getElementById('chat-header');
        const usersContainerFriends = document.getElementById('users-container-friends');
        const usersContainerGroups = document.getElementById('users-container-groups');
        const chatTabs = document.querySelectorAll('.chat-tab');
        const searchInput = document.getElementById('search-user-input');
        const fileInput = document.getElementById('file-input');
        const mediaViewer = document.getElementById('media-viewer');
        const mediaGrid = document.getElementById('media-grid');
        const emojiPicker = document.getElementById('emoji-picker');
        const emojiButton = document.getElementById('emoji-btn');
        const fileInputBtn = document.getElementById('file-input-btn');
        const groupActionsContainer = document.getElementById('group-actions-container');
        const allMediaPanels = document.querySelectorAll('.media-panel');
        
        let lastMessageTimestamp = 0; 
        let userPollInterval;
        let messagePollInterval;
        let mediaPollInterval;
        let hasShownUnreadDivider = false;
        let allConversations = []; // <-- THÊM BIẾN CACHE MỚI NÀY

        // === LOGIC TẠO GROUP CHAT ===
        const createGroupOverlay = document.getElementById('create-group-overlay');
        const createGroupBtn = document.getElementById('create-group-btn');
        const submitCreateGroupBtn = document.getElementById('submit-create-group');
        const groupNameInput = document.getElementById('group-name-input');
        const inviteFriendList = document.getElementById('invite-friend-list');

        // Mở Modal
        async function openCreateGroupModal() {
            inviteFriendList.innerHTML = '<p>Đang tải danh sách bạn bè...</p>';
            createGroupOverlay.style.display = 'flex';
            
            try {
                // 1. Lấy danh sách bạn bè từ friend-handler.php
                // (Chúng ta dùng API của trang Friends, vì nó đã có sẵn)
                const friends = await fetch('../../Handler/FriendHandler/friend-handler.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ action: 'fetch_friends' })
                }).then(res => res.json());

                if (friends.length === 0) {
                    inviteFriendList.innerHTML = '<p>Bạn chưa có bạn bè nào để mời.</p>';
                    return;
                }

                // 2. Hiển thị bạn bè dưới dạng checkbox
                inviteFriendList.innerHTML = friends.map(friend => `
                    <div class="friend-invite-item" onclick="toggleInviteCheckbox(event)">
                        <div class="friend-invite-info">
                            <img src="../../${friend.AvatarPath || 'uploads/default-avatar.jpg'}" alt="avt" onerror="this.src='../../uploads/default-avatar.jpg'">
                            <span>${htmlspecialchars(friend.Username)}</span>
                        </div>
                        <input type="checkbox" name="member_ids[]" value="${friend.UserId}">
                    </div>
                `).join('');
                
            } catch (e) {
                console.error('Lỗi tải danh sách bạn bè:', e);
                inviteFriendList.innerHTML = '<p>Lỗi khi tải danh sách bạn bè.</p>';
            }
        }

        // Đóng Modal
        function closeCreateGroupModal() {
            createGroupOverlay.style.display = 'none';
            groupNameInput.value = '';
            inviteFriendList.innerHTML = '';
        }

        // Gửi dữ liệu (Tạo nhóm)
        async function handleCreateGroup() {
            const groupName = groupNameInput.value.trim();
            if (!groupName) {
                showGlobalAlert('Vui lòng nhập tên nhóm.');
                return;
            }
            
            // Lấy ID của các bạn bè được chọn
            const selectedMembers = [];
            document.querySelectorAll('#invite-friend-list input[type="checkbox"]:checked').forEach(cb => {
                selectedMembers.push(cb.value);
            });

            try {
                const formData = new URLSearchParams();
                formData.append('group_name', groupName);
                selectedMembers.forEach(id => {
                    formData.append('member_ids[]', id);
                });

                // Gửi đến handler mới
                const response = await fetch('../../Handler/ChatHandler/create_group.php', {
                    method: 'POST',
                    body: formData
                }).then(res => res.json());

                if (response.status === 'success') {
                    showGlobalAlert(response.message);
                    closeCreateGroupModal();
                    loadUsers(); // <-- THÊM DÒNG NÀY ĐỂ TẢI LẠI DANH SÁCH
                } else {
                    throw new Error(response.message);
                }
            } catch (e) {
                console.error('Lỗi tạo nhóm:', e);
                showGlobalAlert('Lỗi: ' + e.message);
            }
        }

        // Gán sự kiện cho các nút
        createGroupBtn.addEventListener('click', openCreateGroupModal);
        submitCreateGroupBtn.addEventListener('click', handleCreateGroup);

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
             if (!selectedReceiverId && !selectedGroupId) return; 
                emojiPicker.classList.toggle('open');
        }

        // Hàm render chính (MỚI)
        // Hàm này sẽ lọc và render từ cache `allConversations`
        function renderConversationLists() {
            const query = searchInput.value.toLowerCase();
            // Xác định xem tab nào đang active
            const activeTab = document.querySelector('.chat-tab.active').getAttribute('data-tab'); // 'friends' hoặc 'groups'

            const targetType = (activeTab === 'friends') ? 'user' : 'group';
            const targetContainer = (activeTab === 'friends') ? usersContainerFriends : usersContainerGroups;

            // Lọc danh sách đầy đủ
            const filteredList = allConversations.filter(convo => {
                const typeMatch = convo.ConversationType === targetType;
                const nameMatch = convo.ConversationName.toLowerCase().includes(query);
                return typeMatch && nameMatch;
            });

            targetContainer.innerHTML = ''; // Xóa nội dung cũ
            
            if (filteredList.length === 0) {
                targetContainer.innerHTML = `<p style="text-align: center; color: #aaa; margin-top: 10px;">Không tìm thấy ${activeTab === 'friends' ? 'bạn bè' : 'nhóm'}.</p>`;
                return;
            }
            
            filteredList.forEach(convo => {
                const userItem = document.createElement('div');
                userItem.className = 'user-item';
                userItem.setAttribute('data-id', convo.ConversationId);
                userItem.setAttribute('data-name', convo.ConversationName);
                userItem.setAttribute('data-type', convo.ConversationType);
                
                let avatarClass = 'user-avatar';
                let statusIndicator = '';
                let isActive = false;

                if (convo.ConversationType === 'user') {
                    const statusClass = convo.IsOnline == 1 ? 'online' : 'offline';
                    statusIndicator = `<span class="status-indicator ${statusClass}"></span>`;
                    isActive = (convo.ConversationId == selectedReceiverId);
                } else {
                    avatarClass += ' group-avatar';
                    isActive = (convo.ConversationId == selectedGroupId);
                }

                const unreadCount = convo.UnreadCount || 0;
                const unreadBadge = unreadCount > 0 
                    ? `<span class="unread-badge">${unreadCount}</span>` 
                    : '';
                
                userItem.innerHTML = `
                    <div class="user-details">
                        <img src="../../${convo.AvatarPath.replace(/^\/+/, '')}" alt="avatar" class="${avatarClass}" onerror="this.src='../../uploads/default-avatar.jpg'">
                        ${statusIndicator}
                        <span class="user-name">${htmlspecialchars(convo.ConversationName)}</span>
                    </div>
                    ${unreadBadge}
                `;
                
                userItem.onclick = () => selectConversation(convo.ConversationId, convo.ConversationName, convo.ConversationType);
                
                if (isActive) {
                    userItem.classList.add('active');
                }
                targetContainer.appendChild(userItem);
            });
        }
        
        // Hàm TẢI DỮ LIỆU (MỚI)
        // Hàm này chỉ tải dữ liệu, không render
        async function loadUsers() {
            const url = `./../../Handler/ChatHandler/fetch-users.php?search=`; // Tải tất cả
            
            try {
                const conversations = await fetch(url).then(response => response.json());
                allConversations = conversations; // Cập nhật cache
                renderConversationLists(); // Render
                return conversations;
            } catch (error) {
                console.error('Lỗi khi tải danh sách người dùng:', error);
                usersContainerFriends.innerHTML = "<p>Lỗi tải danh sách.</p>";
                usersContainerGroups.innerHTML = "<p>Lỗi tải danh sách.</p>";
                return [];
            }
        }
        
        // Sửa lại listener TÌM KIẾM (MỚI)
        // Nó sẽ chỉ lọc và render lại, không fetch
        searchInput.addEventListener('input', () => {
            renderConversationLists();
        });

        // Chọn người dùng để chat
        // Chọn 1 cuộc trò chuyện (bạn bè hoặc nhóm)
        function selectConversation(id, name, type) {
            if ((type === 'user' && selectedReceiverId === id) || (type === 'group' && selectedGroupId === id)) {
                return;
            }

            hasShownUnreadDivider = false;
            selectedName = name;

            if (type === 'user') {
                selectedReceiverId = id;
                selectedGroupId = null;
                groupActionsContainer.style.display = 'none'; // Ẩn nút quản lý nhóm
            } else if (type === 'group') {
                selectedReceiverId = null;
                selectedGroupId = id;
                groupActionsContainer.style.display = 'flex'; // Hiện nút quản lý nhóm
            }
            
            lastMessageTimestamp = 0; 
            messageWindow.innerHTML = ''; 

            document.getElementById('chat-header-name').textContent = `Chat với: ${htmlspecialchars(selectedName)}`;
            messageInput.disabled = false;
            sendBtn.disabled = false;
            emojiButton.disabled = false;
            fileInputBtn.disabled = false;
            messageInput.focus();
            
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('active');
            });
            const activeUserItem = document.querySelector(`.user-item[data-id="${id}"][data-type="${type}"]`);
            if(activeUserItem) {
                activeUserItem.classList.add('active');
            }

            if (messagePollInterval) clearInterval(messagePollInterval);
            if (mediaPollInterval) clearInterval(mediaPollInterval);
            
            loadMessages();
            // Mặc định hiển thị panel Media
            handleShowMedia(); 
            
            messagePollInterval = setInterval(loadMessages, 2000); 
            mediaPollInterval = setInterval(loadMediaViewer, 10000); 
        }

        // Tải và hiển thị tin nhắn
        function loadMessages() {
            if (!selectedReceiverId && !selectedGroupId) return;

            const url = './../../Handler/ChatHandler/fetch-messages.php';

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=${selectedReceiverId || 0}&group_id=${selectedGroupId || 0}&last_timestamp=${lastMessageTimestamp}`
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
                        // Nếu tin nhắn này là tin NHẬN, VÀ CHƯA ĐỌC, VÀ ta chưa hiển thị vạch
                        if (!isSent && msg.IsRead == 0 && !hasShownUnreadDivider) {
                            htmlToAppend += `
                                <div class="unread-divider">
                                    <span>Tin nhắn chưa đọc</span>
                                </div>
                            `;
                            // Đánh dấu là đã hiển thị, để không lặp lại
                            hasShownUnreadDivider = true; 
                        }
                        const messageClass = isSent ? 'sent' : 'received';
                        
                        const date = parseMySQLDateTime(msg.SentAt);
                        const newTimestamp = date.getTime();

                        if (newTimestamp > lastMessageTimestamp) {
                            const timeString = date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                            if (msg.MessageType === 'system') {
                                htmlToAppend += `
                                    <div class="message system">
                                        <span>${htmlspecialchars(msg.Content)}</span>
                                        <div class="message-info">${timeString}</div>
                                    </div>
                                `;
                                if (newTimestamp > latestTimestampInBatch) {
                                    latestTimestampInBatch = newTimestamp;
                                }
                                return; // (Quan trọng) Bỏ qua phần còn lại của vòng lặp
                            }
                            let contentHTML = '';

                            if (msg.MessageType === 'image' && msg.FilePath) {
                                const imagePath = msg.FilePath.startsWith('/') ? msg.FilePath.substring(1) : msg.FilePath;
                                contentHTML = `<img src="./../../${htmlspecialchars(imagePath)}" alt="Image" class="message-image" onclick="viewImage(this.src)">`;
                            } else {
                                contentHTML = `<div class="message-text-content">${linkify(htmlspecialchars(msg.Content))}</div>`;
                            }

                            if (isSent) {
                                // Tin nhắn GỬI (sent) - Giữ nguyên cấu trúc cũ
                                htmlToAppend += `
                                    <div class="message ${messageClass}">
                                        <div class="message-username"></div> ${contentHTML}
                                        <div class="message-info">${timeString}</div>
                                    </div>
                                `;
                            } else {
                                // Tin nhắn NHẬN (received) - Dùng cấu trúc .message-row mới
                                
                                // LƯU Ý: Bạn CẦN đảm bảo file 'fetch-messages.php' của bạn
                                // trả về 'SenderAvatarPath' trong đối tượng msg.
                                const avatarPath = msg.SenderAvatarPath 
                                    ? `../../${htmlspecialchars(msg.SenderAvatarPath.replace(/^\/+/, ''))}` 
                                    : '../../uploads/default-avatar.jpg';

                                htmlToAppend += `
                                    <div class="message-row received">
                                        <img src="${avatarPath}" alt="avatar" class="chat-avatar" onerror="this.src='../../uploads/default-avatar.jpg'">
                                        <div class="message ${messageClass}">
                                            <div class="message-username">${htmlspecialchars(msg.SenderName)}</div>
                                            ${contentHTML}
                                            <div class="message-info">${timeString}</div>
                                        </div>
                                    </div>
                                `;
                            }
                            
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
            const url = './../../Handler/ChatHandler/fetch-messages.php';
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=${selectedReceiverId || 0}&group_id=${selectedGroupId || 0}&last_timestamp=0`
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
            // SỬA LỖI: Kiểm tra cả 2 biến mới
            if (content === '' || (!selectedReceiverId && !selectedGroupId)) return;

            const tempMessageContent = content;
            messageInput.value = ''; 
            messageInput.focus();
            
            const url = './../../Handler/ChatHandler/send-message.php';

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                // Code body này đã đúng
                body: `receiver_id=${selectedReceiverId || 0}&group_id=${selectedGroupId || 0}&content=${encodeURIComponent(tempMessageContent)}` 
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
                showGlobalAlert('Lỗi mạng. Không thể gửi tin nhắn. Chi tiết: ' + error.message);
                messageInput.value = tempMessageContent; 
            });
        }
        
        // Gửi ảnh/media
        fileInput.addEventListener('change', sendMedia);

        function sendMedia() {
            // SỬA LỖI: Kiểm tra cả 2 biến mới
            if ((!selectedReceiverId && !selectedGroupId) || fileInput.files.length === 0) return;

            const file = fileInput.files[0];
            const formData = new FormData();
            // Code này đã đúng
            formData.append('receiver_id', selectedReceiverId || 0);
            formData.append('group_id', selectedGroupId || 0);
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
                showGlobalAlert('Lỗi: ' + error.message);
            });
        }
        
        // Xem ảnh
        function viewImage(src) {
            window.open(src, '_blank');
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

        chatTabs.forEach(tabButton => {
            tabButton.addEventListener('click', () => {
                // 1. Bỏ active ở tất cả các tab
                chatTabs.forEach(t => t.classList.remove('active'));
                // 2. Thêm active cho tab vừa bấm
                tabButton.classList.add('active');

                // 3. Ẩn/hiện pane tương ứng
                const tabName = tabButton.getAttribute('data-tab'); // "friends" hoặc "groups"
                document.querySelectorAll('.user-list-pane').forEach(pane => pane.classList.remove('active'));
                document.getElementById(`users-container-${tabName}`).classList.add('active');

                // 4. Render lại danh sách
                renderConversationLists();
            });
        });
        
        const urlParams = new URLSearchParams(window.location.search);
        const friendIdFromUrl = urlParams.get('friend_id');
        
        // Sửa tên biến `users` thành `allConversations` cho rõ nghĩa
        loadUsers().then(allConversations => {
            if (friendIdFromUrl) {
                const friendId = parseInt(friendIdFromUrl);
                
                // Sửa lỗi: Tìm đúng 'ConversationId' và 'ConversationType'
                const friendUser = allConversations.find(c => 
                    c.ConversationId == friendId && c.ConversationType === 'user'
                );
                
                // Sửa lỗi: Gọi đúng hàm 'selectConversation'
                if (friendUser) {
                    selectConversation(
                        friendUser.ConversationId, 
                        friendUser.ConversationName, 
                        'user'
                    );
                    
                    // Xóa ID khỏi URL
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, '', newUrl);
                }
            }
        });

        // --- CÁC HÀM XỬ LÝ PANEL CỘT 3 ---

        function showGroupPanel(panelName) {
            // 1. Ẩn tất cả các panel
            allMediaPanels.forEach(panel => panel.classList.remove('active'));
            // 2. Hiển thị panel được yêu cầu
            const panelToShow = document.getElementById(`media-panel-${panelName}`);
            if (panelToShow) {
                panelToShow.classList.add('active');
            }
        }

        function handleShowMedia() {
            showGroupPanel('media');
            loadMediaViewer(); // Tải lại ảnh
        }

        async function handleShowMembers() {
            if (!selectedGroupId) return;
            showGroupPanel('members');
            hideMemberActions(); 
            
            const memberList = document.getElementById('member-list');
            const deleteContainer = document.getElementById('delete-group-container'); // <-- Thêm dòng này
            const avatarUploadContainer = document.getElementById('group-avatar-upload-container');
            memberList.innerHTML = "<p>Đang tải thành viên...</p>";
            // --- HIỆN NÚT ĐỔI AVATAR ---
            avatarUploadContainer.style.display = 'block';

            try {
                const res = await fetch('../../Handler/ChatHandler/group_manager.php', { 
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ action: 'fetch_members', group_id: selectedGroupId })
                }).then(r => r.json());

                if (res.status !== 'success') throw new Error(res.message);

                if (res.members.length === 0) {
                    memberList.innerHTML = "<p>Lỗi: Không tìm thấy thành viên nào.</p>";
                    deleteContainer.style.display = 'none'; // <-- Thêm dòng này
                    return;
                }

                const amAdmin = (res.currentUserRole === 'Admin');

                // === THÊM LOGIC HIỆN NÚT XÓA ===
                if (amAdmin) {
                    deleteContainer.style.display = 'block';
                } else {
                    deleteContainer.style.display = 'none';
                }
                // === KẾT THÚC THÊM LOGIC ===

                memberList.innerHTML = res.members.map(member => {
                    let roleText = (member.Role === 'Admin') ? 'Quản trị viên' : 'Thành viên';
                    let clickHandler = '';
                    let itemClass = 'member-item';

                    if (amAdmin && member.UserId != currentUserId) {
                        const memberUserId = member.UserId;
                        const memberUsername = htmlspecialchars(member.Username);
                        const memberRole = member.Role;
                        itemClass += ' admin-clickable'; 
                        clickHandler = `onclick="showMemberActions(event, ${memberUserId}, '${memberUsername}', '${memberRole}')"`;
                    }

                    return `
                        <div class="${itemClass}" ${clickHandler}>
                            <img src="../../${member.AvatarPath.replace(/^\/+/, '')}" alt="avt" onerror="this.src='../../uploads/default-avatar.jpg'">
                            <div class="member-item-info">
                                <span>${htmlspecialchars(member.Username)}</span>
                                <small>${roleText}</small>
                            </div>
                        </div>
                    `;
                }).join('');
                
            } catch(e) {
                memberList.innerHTML = `<p>Lỗi: ${e.message}</p>`;
                deleteContainer.style.display = 'none'; // <-- Thêm dòng này
                avatarUploadContainer.style.display = 'none';
            }
        }

        // --- (MỚI) HÀM ẨN POPOVER ---
        function hideMemberActions() {
            const popover = document.getElementById('member-action-popover');
            if (popover) {
                popover.style.display = 'none';
            }
        }

        // --- (MỚI) HÀM HIỆN POPOVER VÀ GÁN SỰ KIỆN ---
        function showMemberActions(event, userId, username, role) {
            event.stopPropagation(); // Ngăn sự kiện click lan ra ngoài
            const popover = document.getElementById('member-action-popover');
            
            // Lấy các nút
            const promoteBtn = document.getElementById('popover-btn-promote');
            const removeBtn = document.getElementById('popover-btn-remove');

            // Gán lại sự kiện onclick cho đúng user
            removeBtn.onclick = (e) => { 
                e.stopPropagation(); 
                hideMemberActions(); 
                handleRemoveMember(userId, username); 
            };
            promoteBtn.onclick = (e) => { 
                e.stopPropagation(); 
                hideMemberActions(); 
                handleTransferAdmin(userId, username); 
            };

            // Ẩn/hiện nút chuyển quyền (Admin thì không cần chuyển nữa)
            if (role === 'Admin') {
                promoteBtn.style.display = 'none';
            } else {
                promoteBtn.style.display = 'block';
            }

            // Định vị Popover
            // Lấy vị trí của item được click (ví dụ: .member-item)
            const clickedItem = event.currentTarget;
            // Lấy vị trí của panel chứa (ví dụ: #media-panel-members)
            const panel = document.getElementById('media-panel-members');

            // Tính toán vị trí top: Vị trí item (so với panel) - độ cuộn của panel + chiều cao item
            let topPosition = clickedItem.offsetTop - panel.scrollTop + clickedItem.offsetHeight;
            
            popover.style.top = topPosition + 'px';
            popover.style.display = 'flex'; // Hiển thị popover
        }

        function resetChatUI() {
            selectedReceiverId = null;
            selectedGroupId = null;
            selectedName = null;
            
            messageWindow.innerHTML = '';
            document.getElementById('chat-header-name').textContent = 'Chọn một người dùng để bắt đầu chat';
            messageInput.disabled = true;
            sendBtn.disabled = true;
            emojiButton.disabled = true;
            fileInputBtn.disabled = true;
            groupActionsContainer.style.display = 'none';
            
            // Ẩn các panel cột 3 và container nút xóa
            showGroupPanel('media'); // Quay về panel media mặc định
            document.getElementById('media-grid').innerHTML = '<p style="text-align: center; color: #aaa; font-size: 0.9em;">Hãy chọn một cuộc hội thoại.</p>';
            document.getElementById('member-list').innerHTML = '';
            document.getElementById('delete-group-container').style.display = 'none';
        }

        // --- (MỚI) HÀM XỬ LÝ NÚT XÓA GROUP ---
        async function handleDeleteGroup() {
            if (!selectedGroupId) return;
            // Lấy tên nhóm hiện tại
            const groupName = selectedName || "nhóm này"; 
            
            const message = `Bạn có chắc chắn muốn XÓA VĨNH VIỄN nhóm "${groupName}"?\n\nCẢNH BÁO: Hành động này không thể hoàn tác. Toàn bộ tin nhắn sẽ bị mất.`;

            // Gọi popup xác nhận
            showGlobalConfirm(message, async () => {
                // Hàm này chạy khi admin bấm "Xác nhận"
                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'delete_group');
                    formData.append('group_id', selectedGroupId);

                    const res = await fetch('../../Handler/ChatHandler/group_manager.php', {
                        method: 'POST',
                        body: formData
                    }).then(r => r.json());

                    if (res.status !== 'success') throw new Error(res.message);

                    // Thông báo thành công và dọn dẹp
                    showGlobalAlert(res.message);
                    resetChatUI(); // Dọn dẹp giao diện
                    loadUsers();   // Tải lại danh sách nhóm (sẽ thấy nhóm bị xóa)
                    
                } catch(e) {
                    showGlobalAlert('Lỗi khi xóa: ' + e.message);
                }
            });
        }

        async function handleRemoveMember(userId, username) {
            if (!selectedGroupId) return;
            
            const message = `Bạn có chắc chắn muốn xóa "${username}" khỏi nhóm?`;

            // THAY THẾ CONFIRM BẰNG POPUP MỚI
            showGlobalConfirm(message, async () => {
                // Chỉ chạy code này khi người dùng bấm "Xác nhận"
                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'remove_member');
                    formData.append('group_id', selectedGroupId);
                    formData.append('user_id_to_remove', userId);

                    const res = await fetch('../../Handler/ChatHandler/group_manager.php', {
                        method: 'POST',
                        body: formData
                    }).then(r => r.json());

                    if (res.status !== 'success') throw new Error(res.message);

                    // THAY THẾ ALERT
                    showGlobalAlert(res.message);
                    handleShowMembers(); // Tải lại danh sách thành viên
                    loadMessages();

                } catch(e) {
                    // THAY THẾ ALERT
                    showGlobalAlert('Lỗi: ' + e.message);
                }
            });
        }

        // --- (MỚI) HÀM XỬ LÝ NÚT CHUYỂN QUYỀN ADMIN ---
        async function handleTransferAdmin(userId, username) {
            if (!selectedGroupId) return;

            const message = `Bạn có chắc muốn chuyển quyền Admin cho "${username}"?\n\nCẢNH BÁO: Bạn sẽ mất quyền Admin của mình sau khi chuyển.`;

            // THAY THẾ CONFIRM BẰNG POPUP MỚI
            showGlobalConfirm(message, async () => {
                // Chỉ chạy code này khi người dùng bấm "Xác nhận"
                try {
                    const formData = new URLSearchParams();
                    formData.append('action', 'transfer_admin');
                    formData.append('group_id', selectedGroupId);
                    formData.append('user_id_to_promote', userId);

                    const res = await fetch('../../Handler/ChatHandler/group_manager.php', {
                        method: 'POST',
                        body: formData
                    }).then(r => r.json());

                    if (res.status !== 'success') throw new Error(res.message);

                    // THAY THẾ ALERT
                    showGlobalAlert(res.message);
                    handleShowMembers(); // Tải lại danh sách thành viên
                    loadMessages();

                } catch(e) {
                    // THAY THẾ ALERT
                    showGlobalAlert('Lỗi: ' + e.message);
                }
            });
        }

        async function handleShowInvite() {
            if (!selectedGroupId) return;
            showGroupPanel('invite');
            const inviteList = document.getElementById('invite-list');
            inviteList.innerHTML = "<p>Đang tải danh sách bạn bè...</p>";

            try {
                const res = await fetch('../../Handler/ChatHandler/group_manager.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ action: 'fetch_invite_list', group_id: selectedGroupId })
                }).then(r => r.json());
                
                if (res.status !== 'success') throw new Error(res.message);
                
                if (res.friends.length === 0) {
                    inviteList.innerHTML = "<p>Tất cả bạn bè của bạn đã ở trong nhóm này.</p>";
                    return;
                }

                inviteList.innerHTML = res.friends.map(friend => `
                    <div class="friend-invite-item" onclick="toggleInviteCheckbox(event)">
                        <div class="friend-invite-info">
                            <img src="../../${friend.AvatarPath.replace(/^\/+/, '')}" alt="avt" onerror="this.src='../../uploads/default-avatar.jpg'">
                            <span>${htmlspecialchars(friend.Username)}</span>
                        </div>
                        <input type="checkbox" name="invite_ids[]" value="${friend.UserId}">
                     </div>
                `).join('');
                
            } catch(e) {
                inviteList.innerHTML = `<p>Lỗi: ${e.message}</p>`;
            }
        }

        // --- (MỚI) HÀM XỬ LÝ ĐỔI AVATAR NHÓM ---
        async function handleGroupAvatarChange(event) {
            if (!selectedGroupId) return;
            
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('action', 'change_group_avatar');
            formData.append('group_id', selectedGroupId);
            formData.append('group_avatar', file);

            try {
                const res = await fetch('../../Handler/ChatHandler/group_manager.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());

                if (res.status !== 'success') throw new Error(res.message);

                showGlobalAlert(res.message);
                loadUsers(); // Tải lại danh sách bên trái để cập nhật avatar
                loadMessages(); // Tải lại tin nhắn để thấy thông báo
            
            } catch (e) {
                showGlobalAlert('Lỗi: ' + e.message);
            }
            
            // Reset input file để có thể tải lại cùng 1 ảnh
            event.target.value = null;
        }

        function toggleInviteCheckbox(event) {
            // Không chạy nếu người dùng bấm chính xác vào checkbox
            if (event.target.type === 'checkbox') {
                return;
            }
            
            // Lấy hàng được click và tìm checkbox bên trong nó
            const row = event.currentTarget;
            const checkbox = row.querySelector('input[type="checkbox"]');
            
            if (checkbox) {
                // Đảo ngược trạng thái checked
                checkbox.checked = !checkbox.checked;
            }
        }

        // Gán sự kiện cho nút "Mời" trong panel
        document.getElementById('invite-btn').addEventListener('click', async () => {
            if (!selectedGroupId) return;

            const selectedMembers = [];
            document.querySelectorAll('#invite-list input[type="checkbox"]:checked').forEach(cb => {
                selectedMembers.push(cb.value);
            });

            if (selectedMembers.length === 0) {
                showGlobalAlert("Bạn chưa chọn ai để mời.");
                return;
            }

            try {
                const formData = new URLSearchParams();
                formData.append('action', 'invite_members');
                formData.append('group_id', selectedGroupId);
                selectedMembers.forEach(id => {
                    formData.append('member_ids[]', id);
                });

                const res = await fetch('../../Handler/ChatHandler/group_manager.php', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json());
                
                if (res.status !== 'success') throw new Error(res.message);

                showGlobalAlert("Đã mời thành công!");
                handleShowMembers(); // Chuyển sang tab thành viên để xem ds mới

            } catch(e) {
                showGlobalAlert("Lỗi: " + e.message);
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

        document.getElementById('delete-group-btn').addEventListener('click', handleDeleteGroup);
        document.addEventListener('click', (e) => {
            const popover = document.getElementById('member-action-popover');
            
            // Nếu popover đang hiện, VÀ
            // Nơi click không phải là popover, VÀ
            // Nơi click cũng không phải là 1 item thành viên
            if (popover && popover.style.display === 'flex' && 
                !popover.contains(e.target) && 
                !e.target.closest('.member-item')) 
            {
                hideMemberActions();
            }
        });

        document.getElementById('group-avatar-input').addEventListener('change', handleGroupAvatarChange);
</script>
<?php render_global_profile_modal(); ?>
</body>
</html>