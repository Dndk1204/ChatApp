<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Chưa đăng nhập hoặc phương thức không hợp lệ.']);
    exit();
}

$sender_id = $_SESSION['user_id'];//ID người gửi
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;// ID người nhận

// Lấy mốc thời gian (dưới dạng milliseconds từ 1970)
$last_timestamp_ms = isset($_POST['last_timestamp']) ? (float)$_POST['last_timestamp'] : 0;

if ($receiver_id === 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Thiếu ID người nhận.']);
    exit();
}

$messages = [];

try {
    
    $sql = "";
    $stmt = null;

    // Truy vấn chỉ lấy tin nhắn mới hơn mốc thời gian
    // và liên quan đến cuộc hội thoại này
    
    // Nếu last_timestamp = 0 (lần tải đầu tiên), lấy TẤT CẢ tin nhắn
    if ($last_timestamp_ms == 0) {
        $sql = "SELECT m.MessageId, m.SenderId, m.Content, m.SentAt, u.Username AS SenderName 
                FROM Messages m
                JOIN Users u ON m.SenderId = u.UserId
                WHERE (m.SenderId = ? AND m.ReceiverId = ?) 
                   OR (m.SenderId = ? AND m.ReceiverId = ?)
                ORDER BY m.SentAt ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);

    } else {
        // Nếu last_timestamp > 0, chỉ lấy tin nhắn MỚI
        // Chuyển đổi milliseconds (từ JS) sang giây và định dạng cho MySQL
        // Thêm 0.001 giây (1ms) để tránh lấy lại tin nhắn cuối cùng
        $last_timestamp_sql = date('Y-m-d H:i:s', ($last_timestamp_ms / 1000) + 0.001);

        $sql = "SELECT m.MessageId, m.SenderId, m.Content, m.SentAt, u.Username AS SenderName 
                FROM Messages m
                JOIN Users u ON m.SenderId = u.UserId
                WHERE ((m.SenderId = ? AND m.ReceiverId = ?) 
                   OR (m.SenderId = ? AND m.ReceiverId = ?))
                AND m.SentAt > ?
                ORDER BY m.SentAt ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiis", $sender_id, $receiver_id, $receiver_id, $sender_id, $last_timestamp_sql);
    }


    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();

    echo json_encode($messages);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi máy chủ CSDL: ' . $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>