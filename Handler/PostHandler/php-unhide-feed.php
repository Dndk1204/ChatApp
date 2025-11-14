<?php
session_start();
require_once '../db.php'; // Lùi 1 cấp về thư mục Handler
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$hider_id = $_SESSION['user_id']; // Tôi
$hidden_id_to_unhide = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0; // Người tôi muốn gỡ ẩn

if ($hidden_id_to_unhide === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID người dùng không hợp lệ.']);
    exit();
}

try {
    // Logic gỡ ẩn: Xóa hàng khỏi bảng hidden_feeds
    $sql = "DELETE FROM hidden_feeds WHERE HiderId = ? AND HiddenId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $hider_id, $hidden_id_to_unhide);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Đã gỡ ẩn.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người này trong danh sách ẩn.']);
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>