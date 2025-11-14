<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !$conn) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập.']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$users = [];

try {
    // Query mới: Chỉ lấy những người đã là bạn (IsConfirmed = 1)
    $sql = "
        SELECT u.UserId, u.Username, u.IsOnline
        FROM users u
        WHERE u.UserId IN (
            -- Lấy những người mà BẠN đã gửi yêu cầu VÀ đã được chấp nhận
            SELECT FriendUserId FROM friends WHERE UserId = ? AND IsConfirmed = 1
            UNION
            -- Lấy những người đã gửi yêu cầu cho BẠN VÀ bạn đã chấp nhận
            SELECT UserId FROM friends WHERE FriendUserId = ? AND IsConfirmed = 1
        )
    ";
    
    // Gán 2 tham số ID của bạn vào
    $params = [$current_user_id, $current_user_id];
    $types = "ii";

    // Thêm điều kiện tìm kiếm (nếu có)
    if (!empty($search_query)) {
        $sql .= " AND u.Username LIKE ?";
        $params[] = "%" . $search_query . "%";
        $types .= "s";
    }

    $sql .= " ORDER BY u.IsOnline DESC, u.Username ASC";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị CSDL: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    $stmt->close();
    $conn->close();

    echo json_encode($users);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>