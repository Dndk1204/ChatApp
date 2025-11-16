<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$group_name = trim($_POST['group_name'] ?? '');
$member_ids = $_POST['member_ids'] ?? [];

if (empty($group_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập tên nhóm.']);
    exit();
}

// Bắt đầu Transaction
$conn->begin_transaction();

try {
    // 1. Tạo nhóm mới trong bảng 'chat_groups'
    // (Tôi giả sử bạn có bảng tên là 'chat_groups')
    $sql_group = "INSERT INTO groups (GroupName, CreatedBy) VALUES (?, ?)";
    $stmt_group = $conn->prepare($sql_group);
    if ($stmt_group === false) { throw new Exception("Lỗi SQL (tạo group): " . $conn->error); }
    
    $stmt_group->bind_param("si", $group_name, $current_user_id);
    $stmt_group->execute();
    $new_group_id = $stmt_group->insert_id; // Lấy ID của nhóm vừa tạo
    $stmt_group->close();

    if ($new_group_id === 0) {
        throw new Exception("Không thể tạo nhóm.");
    }

    // 2. Thêm người tạo nhóm (bản thân) vào làm ADMIN
    $sql_admin = "INSERT INTO group_members (GroupId, UserId, Role) VALUES (?, ?, 'Admin')";
    $stmt_admin = $conn->prepare($sql_admin);
    if ($stmt_admin === false) { throw new Exception("Lỗi SQL (set admin): " . $conn->error); }
    
    $stmt_admin->bind_param("ii", $new_group_id, $current_user_id);
    $stmt_admin->execute();
    $stmt_admin->close();

    // 3. Thêm các thành viên khác vào làm MEMBER
    if (!empty($member_ids)) {
        $sql_members = "INSERT IGNORE INTO group_members (GroupId, UserId, Role) VALUES ";
        $placeholders = [];
        $types = "";
        $params = [];
        
        foreach ($member_ids as $user_id) {
            $placeholders[] = "(?, ?, 'Member')"; // Thêm vai trò 'Member'
            $types .= "ii";
            array_push($params, $new_group_id, (int)$user_id);
        }
        
        $sql_members .= implode(", ", $placeholders);
        $stmt_members = $conn->prepare($sql_members);
        if ($stmt_members === false) { throw new Exception("Lỗi SQL (thêm member): " . $conn->error); }
        
        $stmt_members->bind_param($types, ...$params);
        $stmt_members->execute();
        $stmt_members->close();
    }

    // Hoàn tất
    $conn->commit();
    echo json_encode([
        'status' => 'success', 
        'message' => 'Tạo nhóm thành công!',
        'new_group_id' => $new_group_id
    ]);

} catch (Exception $e) {
    $conn->rollback(); // Hoàn tác nếu có lỗi
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
$conn->close();
?>