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
$action = $_POST['action'] ?? '';
$group_id = (int)($_POST['group_id'] ?? 0);

if ($group_id === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu Group ID.']);
    exit();
}

// HÀM MỚI: Tự động tạo tin nhắn hệ thống
function createSystemMessage($conn, $groupId, $content) {
    // Chúng ta dùng SenderId = 0 (hoặc 1 user hệ thống) để đánh dấu
    // Nhưng để đơn giản, ta sẽ dùng SenderId của Admin (người thực hiện hành động)
    // Sửa: Dùng một SenderId không có thật (ví dụ 0) để JS biết đây là tin nhắn hệ thống
    
    // Tốt nhất là dùng SenderId của người thực hiện hành động
    // và thêm MessageType = 'system'
    
    $sql = "INSERT INTO messages (SenderId, GroupId, Content, MessageType)
            VALUES (?, ?, ?, 'system')";
    
    try {
        $stmt = $conn->prepare($sql);
        // Dùng SenderId = 0 để đánh dấu là HỆ THỐNG
        $systemSenderId = 0; 
        $stmt->bind_param("iis", $systemSenderId, $groupId, $content);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        // Ghi log lỗi nhưng không làm dừng tiến trình chính
        error_log("Lỗi tạo system message: " . $e->getMessage());
        return false;
    }
}

// --- HÀM KIỂM TRA ADMIN ---
function isGroupAdmin($conn, $group_id, $user_id) {
    $sql = "SELECT Role FROM group_members WHERE GroupId = ? AND UserId = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { return false; }
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['Role'] === 'Admin';
    }
    return false;
}

// --- HÀM KIỂM TRA THÀNH VIÊN (MỚI) ---
function isGroupMember($conn, $group_id, $user_id) {
    $sql = "SELECT 1 FROM group_members WHERE GroupId = ? AND UserId = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { return false; }
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
// -------------------------

try {
    switch ($action) {
        
        // --- LẤY DANH SÁCH THÀNH VIÊN ---
        case 'fetch_members':
            // SỬA LỖI: Lấy Role thật từ bảng group_members
            $sql = "
                SELECT u.UserId, u.Username, u.AvatarPath, u.IsOnline, 
                       gm.Role  -- Lấy Role thật
                FROM users u 
                JOIN group_members gm ON u.UserId = gm.UserId 
                WHERE gm.GroupId = ?
                ORDER BY gm.Role DESC, u.Username
            ";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) { throw new Exception("Lỗi SQL (fetch_members): " . $conn->error); }
            
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Lấy vai trò của người dùng hiện tại
            $currentUserRole = isGroupAdmin($conn, $group_id, $current_user_id) ? 'Admin' : 'Member';

            echo json_encode([
                'status' => 'success', 
                'members' => $members,
                'currentUserRole' => $currentUserRole // Gửi vai trò về cho JS
            ]);
            break;

        // --- LẤY DANH SÁCH BẠN BÈ ĐỂ MỜI ---
        // (Giữ nguyên như file cũ của bạn, không cần sửa)
        case 'fetch_invite_list':
            $sql = "
                SELECT u.UserId, u.Username, u.AvatarPath 
                FROM users u
                WHERE u.UserId IN (
                    SELECT FriendUserId FROM friends WHERE UserId = ? AND IsConfirmed = 1
                    UNION
                    SELECT UserId FROM friends WHERE FriendUserId = ? AND IsConfirmed = 1
                ) 
                AND u.UserId NOT IN (
                    SELECT UserId FROM group_members WHERE GroupId = ?
                )
                ORDER BY u.Username
            ";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) { throw new Exception("Lỗi SQL (fetch_invite_list): " . $conn->error); }
            
            $stmt->bind_param("iii", $current_user_id, $current_user_id, $group_id);
            $stmt->execute();
            $friends_to_invite = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['status' => 'success', 'friends' => $friends_to_invite]);
            break;

        // --- MỜI THÀNH VIÊN ---
        // (Giữ nguyên như file cũ của bạn, chỉ thêm Role='Member' khi INSERT)
        case 'invite_members':
            // Chỉ Admin mới được mời
            if (!isGroupMember($conn, $group_id, $current_user_id)) {
                throw new Exception("Bạn phải là thành viên của nhóm để mời người khác.");
            }

            $member_ids = $_POST['member_ids'] ?? [];
            if (empty($member_ids)) throw new Exception("Không có ai để mời.");

            $sql_insert = "INSERT IGNORE INTO group_members (GroupId, UserId, Role) VALUES ";
            $placeholders = []; $types = ""; $params = [];
            
            foreach ($member_ids as $user_id) {
                $placeholders[] = "(?, ?, 'Member')"; // Mặc định là Member
                $types .= "ii";
                array_push($params, $group_id, (int)$user_id);
            }
            
            $sql_insert .= implode(", ", $placeholders);
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert === false) { throw new Exception("Lỗi SQL (invite_members): " . $conn->error); }
            
            $stmt_insert->bind_param($types, ...$params);
            $stmt_insert->execute();
            $count = count($member_ids);
            $invited_content = $_SESSION['username'] . " đã mời " . $count . " thành viên mới vào nhóm.";
            createSystemMessage($conn, $group_id, $invited_content);
            
            echo json_encode(['status' => 'success', 'message' => 'Đã mời thành viên.']);
            break;

        // --- XÓA THÀNH VIÊN ---
        case 'remove_member':
            // KIỂM TRA QUYỀN ADMIN
            if (!isGroupAdmin($conn, $group_id, $current_user_id)) {
                throw new Exception("Bạn không có quyền xóa thành viên.");
            }

            $user_id_to_remove = (int)($_POST['user_id_to_remove'] ?? 0);
            if ($user_id_to_remove === 0) throw new Exception("Thiếu User ID.");
            
            // Admin không thể tự xóa mình (phải chuyển quyền trước)
            if ($user_id_to_remove === $current_user_id) throw new Exception("Bạn không thể tự xóa mình. Hãy chuyển quyền Admin trước.");

            $stmt_name = $conn->prepare("SELECT Username FROM users WHERE UserId = ?");
            $stmt_name->bind_param("i", $user_id_to_remove);
            $stmt_name->execute();
            $removed_username = $stmt_name->get_result()->fetch_assoc()['Username'] ?? 'Ai đó';
            $stmt_name->close();

            $stmt = $conn->prepare("DELETE FROM group_members WHERE GroupId = ? AND UserId = ?");
            if ($stmt === false) { throw new Exception("Lỗi SQL (remove_member): " . $conn->error); }

            $stmt->bind_param("ii", $group_id, $user_id_to_remove);
            $stmt->execute();

            $removed_content = $_SESSION['username'] . " đã xóa " . $removed_username . " khỏi nhóm.";
            createSystemMessage($conn, $group_id, $removed_content);
            
            echo json_encode(['status' => 'success', 'message' => 'Đã xóa thành viên.']);
            break;

        // --- (MỚI) CHUYỂN QUYỀN ADMIN ---
        case 'transfer_admin':
            // KIỂM TRA QUYỀN ADMIN
            if (!isGroupAdmin($conn, $group_id, $current_user_id)) {
                throw new Exception("Bạn không có quyền chuyển quyền Admin.");
            }

            $user_id_to_promote = (int)($_POST['user_id_to_promote'] ?? 0);
            if ($user_id_to_promote === 0) throw new Exception("Thiếu User ID người nhận.");
            if ($user_id_to_promote === $current_user_id) throw new Exception("Bạn đã là Admin rồi.");

            $stmt_name = $conn->prepare("SELECT Username FROM users WHERE UserId = ?");
            $stmt_name->bind_param("i", $user_id_to_promote);
            $stmt_name->execute();
            $promoted_username = $stmt_name->get_result()->fetch_assoc()['Username'] ?? 'Ai đó';
            $stmt_name->close();

            // Bắt đầu transaction để đảm bảo an toàn
            $conn->begin_transaction();
            try {
                // 1. Hạ Admin cũ (bản thân) xuống làm Member
                $sql_demote = "UPDATE group_members SET Role = 'Member' WHERE GroupId = ? AND UserId = ?";
                $stmt_demote = $conn->prepare($sql_demote);
                $stmt_demote->bind_param("ii", $group_id, $current_user_id);
                $stmt_demote->execute();
                $stmt_demote->close();

                // 2. Nâng Member mới lên làm Admin
                $sql_promote = "UPDATE group_members SET Role = 'Admin' WHERE GroupId = ? AND UserId = ?";
                $stmt_promote = $conn->prepare($sql_promote);
                $stmt_promote->bind_param("ii", $group_id, $user_id_to_promote);
                $stmt_promote->execute();
                $stmt_promote->close();

                $transfer_content = $_SESSION['username'] . " đã chuyển quyền Admin cho " . $promoted_username . ".";
                createSystemMessage($conn, $group_id, $transfer_content);

                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Đã chuyển quyền Admin.']);
            
            } catch (Exception $e_trans) {
                $conn->rollback();
                throw new Exception("Lỗi khi chuyển quyền: " . $e_trans->getMessage());
            }
            break;

        case 'delete_group':
            // 1. Kiểm tra phải Admin không
            if (!isGroupAdmin($conn, $group_id, $current_user_id)) {
                throw new Exception("Chỉ Admin mới có quyền xóa nhóm.");
            }
            
            // 2. Bắt đầu transaction để đảm bảo an toàn
            $conn->begin_transaction();
            try {
                // 3. Xóa tất cả tin nhắn thuộc nhóm này
                // (Bắt buộc vì bảng 'messages' của bạn không có ON DELETE CASCADE)
                $stmt_msg = $conn->prepare("DELETE FROM messages WHERE GroupId = ?");
                if ($stmt_msg === false) { throw new Exception("Lỗi SQL (xóa messages): " . $conn->error); }
                $stmt_msg->bind_param("i", $group_id);
                $stmt_msg->execute();
                $stmt_msg->close();
                
                // 4. Xóa nhóm khỏi bảng 'groups'
                // (Bảng 'group_members' sẽ tự động xóa theo nhờ ON DELETE CASCADE)
                $stmt_group = $conn->prepare("DELETE FROM groups WHERE GroupId = ?");
                if ($stmt_group === false) { throw new Exception("Lỗi SQL (xóa group): " . $conn->error); }
                $stmt_group->bind_param("i", $group_id);
                $stmt_group->execute();
                $stmt_group->close();
                
                // 5. Hoàn tất
                $conn->commit();
                echo json_encode(['status' => 'success', 'message' => 'Đã xóa nhóm thành công.']);
                
            } catch (Exception $e_trans) {
                // 6. Hoàn tác nếu có lỗi
                $conn->rollback();
                throw new Exception("Lỗi trong quá trình xóa nhóm: " . $e_trans->getMessage());
            }
            break;

        case 'change_group_avatar':
            if (!isset($_FILES['group_avatar'])) {
                throw new Exception("Không có file nào được tải lên.");
            }
            
            $file = $_FILES['group_avatar'];
            
            // Kiểm tra lỗi file
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Lỗi tải file: " . $file['error']);
            }
            
            // Kiểm tra loại file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("Loại file không hợp lệ. Chỉ chấp nhận JPG, PNG, GIF.");
            }
            
            // Tạo thư mục nếu chưa có
            $upload_dir = '../../uploads/group_avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Tạo tên file mới
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'group_avatar_' . $group_id . '_' . time() . '.' . $extension;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Cập nhật CSDL
                $db_path = 'uploads/group_avatars/' . $new_filename;
                $stmt_update = $conn->prepare("UPDATE groups SET AvatarPath = ? WHERE GroupId = ?");
                $stmt_update->bind_param("si", $db_path, $group_id);
                $stmt_update->execute();
                
                // Tạo tin nhắn hệ thống
                $avatar_content = $_SESSION['username'] . " đã thay đổi ảnh đại diện nhóm.";
                createSystemMessage($conn, $group_id, $avatar_content);
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Đổi ảnh đại diện thành công.',
                    'newPath' => $db_path
                ]);
            } else {
                throw new Exception("Không thể di chuyển file đã tải lên.");
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
$conn->close();
?>