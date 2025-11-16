<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$sender_id = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$group_id = (int)($_POST['group_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

// --- SỬA LỖI 400 ---
// Nếu không có nội dung, HOẶC không có cả ID người nhận VÀ ID nhóm -> Lỗi
if (empty($content) || ($receiver_id === 0 && $group_id === 0)) {
    http_response_code(400); // Đây chính là lỗi 400
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu gửi lên không hợp lệ.']);
    exit();
}

try {
    $sql = "";
    $stmt = null;

    if ($receiver_id > 0) {
        // Đây là CHAT RIÊNG (1-với-1)
        $sql = "INSERT INTO messages (SenderId, ReceiverId, Content, MessageType) VALUES (?, ?, ?, 'text')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $sender_id, $receiver_id, $content);
        
    } else if ($group_id > 0) {
        // Đây là CHAT NHÓM
        $sql = "INSERT INTO messages (SenderId, GroupId, Content, MessageType) VALUES (?, ?, ?, 'text')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $sender_id, $group_id, $content);
        
    } else {
        // Trường hợp này đã bị chặn ở trên, nhưng để an toàn
        throw new Exception("Không xác định được người nhận.");
    }

    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Gửi thành công.']);
    } else {
        throw new Exception("Không thể gửi tin nhắn.");
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi server: ' . $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>