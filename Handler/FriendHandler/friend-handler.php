<?php
require_once('../db.php');
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ====================== XỬ LÝ ======================
switch ($action) {

    // 📨 Gửi lời mời kết bạn
    case 'send':
        $friend_id = intval($_POST['friend_id']);
        if ($friend_id == $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Không thể tự gửi lời mời.']);
            exit;
        }

        // Kiểm tra mối quan hệ đã tồn tại chưa
        $check = $conn->prepare("
            SELECT * FROM friends 
            WHERE (UserId=? AND FriendUserId=?) OR (UserId=? AND FriendUserId=?)
        ");
        $check->bind_param('iiii', $user_id, $friend_id, $friend_id, $user_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            echo json_encode(['status' => 'exists']);
        } else {
            $stmt = $conn->prepare("INSERT INTO friends (UserId, FriendUserId, IsConfirmed) VALUES (?, ?, 0)");
            $stmt->bind_param('ii', $user_id, $friend_id);
            $stmt->execute();
            echo json_encode(['status' => 'sent']);
        }
        break;

    // ✅ Chấp nhận lời mời
    case 'accept':
        $friend_id = intval($_POST['friend_id']);
        $stmt = $conn->prepare("UPDATE friends SET IsConfirmed=1 WHERE UserId=? AND FriendUserId=? LIMIT 1");
        $stmt->bind_param('ii', $friend_id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'accepted']);
        break;

    // ❌ Từ chối lời mời
    case 'reject':
        $friend_id = intval($_POST['friend_id']);
        $stmt = $conn->prepare("DELETE FROM friends WHERE UserId=? AND FriendUserId=? LIMIT 1");
        $stmt->bind_param('ii', $friend_id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'rejected']);
        break;

    // 🔔 Lấy danh sách lời mời kết bạn
    case 'fetch_requests':
        $stmt = $conn->prepare("
            SELECT f.UserId AS sender_id, u.Username AS sender_name, u.AvatarPath AS sender_avatar
            FROM friends f
            JOIN users u ON f.UserId = u.UserId
            WHERE f.FriendUserId=? AND f.IsConfirmed=0
            ORDER BY f.FriendId DESC
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $requests = [];
        while ($row = $res->fetch_assoc()) {
            $requests[] = $row;
        }
        echo json_encode($requests);
        break;

    // 👬 Lấy danh sách bạn bè đã xác nhận
    case 'fetch_friends':
    $stmt = $conn->prepare("
        SELECT 
                u.UserId, u.Username, u.FullName, u.PhoneNumber, u.Address, 
                u.DateOfBirth, u.Gender, u.CreatedAt, u.AvatarPath, u.IsOnline, 
                
                -- YÊU CẦU MỚI:
                -- Yêu cầu MySQL tính toán số giây đã trôi qua
                -- bằng cách so sánh LastSeen với thời gian HIỆN TẠI của server (NOW())
                TIMESTAMPDIFF(SECOND, u.LastSeen, NOW()) AS SecondsAgo
                
            FROM users u
        WHERE u.UserId IN (
            SELECT FriendUserId FROM friends WHERE UserId=? AND IsConfirmed=1
            UNION
            SELECT UserId FROM friends WHERE FriendUserId=? AND IsConfirmed=1
        )
    ");
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $friends = [];
    while ($row = $res->fetch_assoc()) {
        $friends[] = $row;
    }
    echo json_encode($friends);
    break;

    // 🗑️ Hủy kết bạn
    case 'unfriend':
        $friend_id = intval($_POST['friend_id']);
        $stmt = $conn->prepare("DELETE FROM friends WHERE (UserId=? AND FriendUserId=?) OR (UserId=? AND FriendUserId=?)");
        $stmt->bind_param('iiii', $user_id, $friend_id, $friend_id, $user_id);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        break;

    case 'fetch_user_profile':
        if (!isset($_POST['profile_user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Thiếu ID người dùng.']);
            exit;
        }
        $profile_user_id = intval($_POST['profile_user_id']);

        // 1. Lấy thông tin người dùng
        $stmt_user = $conn->prepare("SELECT UserId, Username, Email, FullName, PhoneNumber, Address, DateOfBirth, Gender, CreatedAt, AvatarPath FROM users WHERE UserId = ?");
        $stmt_user->bind_param('i', $profile_user_id);
        $stmt_user->execute();
        $user_data = $stmt_user->get_result()->fetch_assoc();

        if (!$user_data) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng.']);
            exit;
        }
        
        // Chuẩn hóa AvatarPath (ltrim)
        if (!empty($user_data['AvatarPath'])) {
             $user_data['AvatarPath'] = ltrim($user_data['AvatarPath'], '/');
        }

        // 2. Kiểm tra trạng thái bạn bè
        $friendship_status = 'none';
        if ($user_id == $profile_user_id) {
            $friendship_status = 'is_self';
        } else {
            $stmt_friend = $conn->prepare("SELECT * FROM friends WHERE (UserId=? AND FriendUserId=?) OR (UserId=? AND FriendUserId=?)");
            $stmt_friend->bind_param('iiii', $user_id, $profile_user_id, $profile_user_id, $user_id);
            $stmt_friend->execute();
            $friend_res = $stmt_friend->get_result()->fetch_assoc();

            if ($friend_res) {
                if ($friend_res['IsConfirmed'] == 1) {
                    $friendship_status = 'already_friends';
                } elseif ($friend_res['UserId'] == $user_id) { // Bạn là người gửi
                    $friendship_status = 'sent_by_me';
                } else { // Người kia gửi cho bạn
                    $friendship_status = 'sent_to_me';
                }
            }
        }
        
        // 3. Gộp kết quả
        $user_data['friendship_status'] = $friendship_status;

        echo json_encode(['status' => 'success', 'data' => $user_data]);
        break;

    case 'cancel_request':
        $friend_id = intval($_POST['friend_id']);
        // Xóa lời mời mà CHÍNH BẠN đã gửi đi (UserId = $user_id)
        $stmt = $conn->prepare("DELETE FROM friends WHERE UserId = ? AND FriendUserId = ? AND IsConfirmed = 0");
        $stmt->bind_param('ii', $user_id, $friend_id);
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        break;

    case 'fetch_suggestions':
        $sql = "
            SELECT 
                u.UserId, 
                u.Username, 
                u.AvatarPath,
                -- Kiểm tra trạng thái quan hệ một cách chi tiết
                CASE 
                    WHEN f.IsConfirmed = 1 THEN 'already_friends'
                    WHEN f.UserId = ? AND f.IsConfirmed = 0 THEN 'sent_by_me'
                    WHEN f.FriendUserId = ? AND f.IsConfirmed = 0 THEN 'sent_to_me'
                    WHEN f.FriendId IS NULL THEN 'none'
                    ELSE 'none' -- Bất kỳ trường hợp nào khác cũng coi là 'none'
                END AS friendship_status
            FROM users u
            -- Tìm mối quan hệ (nếu có)
            LEFT JOIN friends f ON 
                (f.UserId = ? AND f.FriendUserId = u.UserId) OR 
                (f.FriendUserId = ? AND f.UserId = u.UserId)
            WHERE 
                u.UserId != ? -- Không phải là tôi
            HAVING 
                -- Chỉ hiển thị người 'chưa có gì' (none)
                -- hoặc người 'tôi đã gửi lời mời' (sent_by_me)
                friendship_status = 'none' OR 
                friendship_status = 'sent_by_me'
            ORDER BY RAND()
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($sql);
        // Bây giờ chúng ta có 5 dấu ?, tất cả đều là $user_id
        $stmt->bind_param('iiiii', $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $suggestions = [];
        while ($row = $res->fetch_assoc()) {
            if (empty($row['AvatarPath']) || $row['AvatarPath'] === '/uploads/default-avatar.jpg') {
                $row['AvatarPath'] = 'uploads/default-avatar.jpg';
            }
            $row['AvatarPath'] = ltrim($row['AvatarPath'], '/');
            $suggestions[] = $row;
        }

        echo json_encode($suggestions);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ']);
        break;
}
