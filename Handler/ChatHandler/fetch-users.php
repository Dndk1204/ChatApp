<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$conn) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$search_query = $_GET['search'] ?? '';
$search_param = "%" . $search_query . "%";

try {
    
    // ----------- QUERY 1: LẤY BẠN BÈ (CHAT 1-1) -----------
    // Lấy bạn bè đã `IsConfirmed = 1`
    $sql_friends = "
        SELECT 
            u.UserId AS ConversationId, 
            u.Username AS ConversationName, 
            u.AvatarPath, 
            u.IsOnline,
            'user' AS ConversationType, -- Đánh dấu đây là 'user'
            (SELECT COUNT(*) FROM messages m 
             WHERE m.ReceiverId = ? AND m.SenderId = u.UserId AND m.IsRead = 0) AS UnreadCount
        FROM users u
        WHERE u.UserId IN (
            SELECT FriendUserId FROM friends WHERE UserId = ? AND IsConfirmed = 1
            UNION
            SELECT UserId FROM friends WHERE FriendUserId = ? AND IsConfirmed = 1
        )
        AND u.Username LIKE ?
    ";
    
    // ----------- QUERY 2: LẤY NHÓM CHAT -----------
    // Lấy các nhóm mà bạn là thành viên
    $sql_groups = "
        SELECT 
            g.GroupId AS ConversationId, 
            g.GroupName AS ConversationName, 
            COALESCE(g.AvatarPath, '/uploads/default-group-avatar.jpg') AS AvatarPath, -- Lấy avatar thật, nếu NULL thì dùng default
            0 AS IsOnline, -- (Nhóm không có trạng thái online)
            'group' AS ConversationType, -- Đánh dấu đây là 'group'
            
            -- Logic đếm tin chưa đọc cho nhóm (Tạm thời dùng IsRead = 0 và không phải do mình gửi)
            (SELECT COUNT(*) FROM messages m 
             WHERE m.GroupId = g.GroupId AND m.SenderId != ? AND m.IsRead = 0) AS UnreadCount
        FROM groups g
        JOIN group_members gm ON g.GroupId = gm.GroupId
        WHERE gm.UserId = ?
        AND g.GroupName LIKE ?
    ";

    // ----------- GỘP 2 QUERY LẠI BẰNG UNION -----------
    $sql = "($sql_friends) UNION ($sql_groups) ORDER BY UnreadCount DESC, ConversationName ASC";

    $stmt = $conn->prepare($sql);
    
    // Gán 7 tham số theo đúng thứ tự
    $stmt->bind_param(
        "iiisiss", 
        $current_user_id, // (cho UnreadCount của bạn bè)
        $current_user_id, // (cho danh sách bạn bè 1)
        $current_user_id, // (cho danh sách bạn bè 2)
        $search_param,    // (cho tìm kiếm tên bạn bè)
        $current_user_id, // (cho UnreadCount của nhóm)
        $current_user_id, // (cho danh sách nhóm)
        $search_param     // (cho tìm kiếm tên nhóm)
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    $conversations = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    $conn->close();

    echo json_encode($conversations);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>