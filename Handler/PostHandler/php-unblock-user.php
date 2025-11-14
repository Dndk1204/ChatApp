<?php
session_start();
require_once '../db.php'; // Lùi 1 cấp về thư mục Handler
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$blocker_id = $_SESSION['user_id']; // Tôi
$blocked_id_to_unblock = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0; // Người tôi muốn gỡ chặn

if ($blocked_id_to_unblock === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID người dùng không hợp lệ.']);
    exit();
}

try {
    // Logic gỡ chặn: Xóa hàng khỏi bảng blocked_users
    $sql = "DELETE FROM blocked_users WHERE BlockerId = ? AND BlockedId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $blocker_id, $blocked_id_to_unblock);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Đã gỡ chặn.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người này trong danh sách chặn.']);
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>