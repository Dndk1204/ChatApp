<?php
session_start();
require_once '../db.php'; // ĐÚNG

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

if ($post_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Bài đăng không hợp lệ.']);
    exit();
}

try {
    $sql_get_image = "SELECT ImagePath FROM posts WHERE PostId = ? AND UserId = ?";
    $stmt_get_image = $conn->prepare($sql_get_image);
    $stmt_get_image->bind_param("ii", $post_id, $user_id);
    $stmt_get_image->execute();
    $result = $stmt_get_image->get_result();
    
    if ($result->num_rows == 1) {
        $post = $result->fetch_assoc();
        $image_path = $post['ImagePath']; // VD: "uploads/posts/pic.jpg"

        $sql_delete = "DELETE FROM posts WHERE PostId = ? AND UserId = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $post_id, $user_id);
        $stmt_delete->execute();

        if (!empty($image_path)) {
            $image_path_on_server = "../../" . $image_path; 
            if (file_exists($image_path_on_server)) {
                unlink($image_path_on_server);
            }
        }
        
        $stmt_delete->close();
        $stmt_get_image->close();
        $conn->close();
        echo json_encode(['status' => 'success']);

    } else {
        $stmt_get_image->close();
        $conn->close();
        echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xóa bài đăng này.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    if ($conn) $conn->close();
}
?>