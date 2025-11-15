<?php
require_once('../db.php'); // Đảm bảo đường dẫn này đúng
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$q = $_GET['q'] ?? '';

if (empty($q)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$search_term = '%' . $q . '%';

/* * Query này sao chép logic từ 'fetch_suggestions'
 * nhưng thêm điều kiện 'WHERE u.Username LIKE ?' để tìm kiếm
 */
$sql = "
    SELECT 
        u.UserId, 
        u.Username, 
        u.AvatarPath,
        -- Kiểm tra trạng thái quan hệ
        CASE 
            WHEN f.IsConfirmed = 1 THEN 'already_friends'
            WHEN f.UserId = ? AND f.IsConfirmed = 0 THEN 'sent_by_me'
            WHEN f.FriendUserId = ? AND f.IsConfirmed = 0 THEN 'sent_to_me'
            WHEN f.FriendId IS NULL THEN 'none'
            ELSE 'none'
        END AS friendship_status
    FROM users u
    LEFT JOIN friends f ON 
        (f.UserId = ? AND f.FriendUserId = u.UserId) OR 
        (f.FriendUserId = ? AND f.UserId = u.UserId)
    WHERE 
        u.UserId != ? AND u.Username LIKE ?
    HAVING 
        -- Lọc ra những người đã là bạn hoặc đã gửi lời mời cho BẠN
        friendship_status = 'none' OR 
        friendship_status = 'sent_by_me'
    LIMIT 10
";

$stmt = $conn->prepare($sql);
// 5 cái $user_id, 1 cái $search_term
$stmt->bind_param('iiisss', $user_id, $user_id, $user_id, $user_id, $user_id, $search_term);
$stmt->execute();
$res = $stmt->get_result();

$users = [];
while ($row = $res->fetch_assoc()) {
    // Chuẩn hóa AvatarPath
    if (empty($row['AvatarPath']) || $row['AvatarPath'] === '/uploads/default-avatar.jpg') {
        $row['AvatarPath'] = 'uploads/default-avatar.jpg';
    }
    $users[] = $row;
}

header('Content-Type: application/json');
echo json_encode($users);
?>