<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    http_response_code(401);
    echo json_encode(['error' => 'Chưa đăng nhập hoặc phương thức không hợp lệ.']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0; // LẤY GROUP ID
$last_timestamp_ms = isset($_POST['last_timestamp']) ? (float)$_POST['last_timestamp'] : 0;

// --- SỬA LỖI 1: KIỂM TRA VALIDATION ---
// Chỉ báo lỗi nếu CẢ HAI ID đều bằng 0
if ($receiver_id === 0 && $group_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu ID người nhận hoặc ID nhóm.']);
    exit();
}

$messages = [];

try {
    $sql = "";
    $stmt = null;
    $types = "";
    $params = [];
    
    // SỬA LỖI 2: Đã thêm m.IsRead, m.GroupId
    $select_cols = "m.MessageId, m.SenderId, m.Content, m.SentAt, u.Username AS SenderName, u.AvatarPath as SenderAvatarPath, m.IsRead, m.MessageType, m.GroupId";
    
    $last_timestamp_sql = date('Y-m-d H:i:s', ($last_timestamp_ms / 1000));

    // --- SỬA LỖI 3: TÁCH LOGIC CHAT RIÊNG VÀ CHAT NHÓM ---
    if ($receiver_id > 0) {
        // Đây là CHAT RIÊNG
        $where_conversation = "(m.SenderId = ? AND m.ReceiverId = ?) OR (m.SenderId = ? AND m.ReceiverId = ?)";
        $params = [$sender_id, $receiver_id, $receiver_id, $sender_id];
        $types = "iiii";

        if ($last_timestamp_ms == 0) {
            // Tải toàn bộ lịch sử
            $sql = "SELECT {$select_cols} FROM Messages m LEFT JOIN Users u ON m.SenderId = u.UserId WHERE {$where_conversation} ORDER BY m.SentAt ASC";
        } else {
            // Chỉ tải tin nhắn mới
            $sql = "SELECT {$select_cols} FROM Messages m LEFT JOIN Users u ON m.SenderId = u.UserId WHERE ({$where_conversation}) AND m.SentAt >= ? ORDER BY m.SentAt ASC";
            $params[] = $last_timestamp_sql;
            $types .= "s";
        }

    } else if ($group_id > 0) {
        // Đây là CHAT NHÓM
        $where_conversation = "m.GroupId = ?";
        $params = [$group_id];
        $types = "i";

        if ($last_timestamp_ms == 0) {
            // Tải toàn bộ lịch sử
            $sql = "SELECT {$select_cols} FROM Messages m LEFT JOIN Users u ON m.SenderId = u.UserId WHERE {$where_conversation} ORDER BY m.SentAt ASC";
        } else {
            // Chỉ tải tin nhắn mới
            $sql = "SELECT {$select_cols} FROM Messages m LEFT JOIN Users u ON m.SenderId = u.UserId WHERE {$where_conversation} AND m.SentAt >= ? ORDER BY m.SentAt ASC";
            $params[] = $last_timestamp_sql;
            $types .= "s";
        }
    }

    if (empty($sql)) {
        throw new Exception("Không thể xây dựng câu lệnh SQL.");
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi CSDL (prepare): " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            
            // 1. KIỂM TRA NẾU LÀ TIN NHẮN HỆ THỐNG (TỪ DB)
            if ($row['MessageType'] === 'system') {
                $row['FilePath'] = null; 
                // Cứ giữ nguyên là 'system', không làm gì cả
            } 
            // 2. NẾU KHÔNG PHẢI, THÌ MỚI XỬ LÝ TEXT/IMAGE
            else {
                $content = $row['Content'] ?? '';
                $row['FilePath'] = null;

                if (str_starts_with($content, '[IMG]')) {
                    $row['MessageType'] = 'image';
                    $row['FilePath'] = substr($content, 5); 
                    $row['Content'] = ''; 
                } else {
                    $row['MessageType'] = 'text'; // Chỉ set là text nếu nó không phải [IMG]
                }
            }
            
            $messages[] = $row;
        }
    }
    $stmt->close();
    
    // --- SỬA LỖI 4: CHỈ ĐÁNH DẤU ĐÃ ĐỌC KHI LÀ CHAT RIÊNG ---
    if ($receiver_id > 0) {
        $sql_update = "UPDATE Messages SET IsRead = 1 
                       WHERE ReceiverId = ? AND SenderId = ? AND IsRead = 0 AND IsDeleted = 0";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            $stmt_update->bind_param("ii", $sender_id, $receiver_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }
    // (Lưu ý: Đánh dấu đã đọc cho chat nhóm cần 1 bảng riêng,
    // nhưng code này sẽ cho phép bạn TẢI được tin nhắn nhóm)
    
    $conn->close();
    echo json_encode($messages);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi máy chủ CSDL: ' . $e->getMessage()]);
    if (isset($stmt) && $stmt) $stmt->close();
    if ($conn) $conn->close();
}
?>