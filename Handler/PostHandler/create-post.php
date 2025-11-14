<?php
session_start();
require_once '../db.php'; // Lùi 1 cấp về Handler/

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    $_SESSION['error_message'] = "Truy cập không hợp lệ.";
    header("Location: ../../Pages/login.php"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$content = $_POST['content'];
$privacy = (isset($_POST['privacy']) && $_POST['privacy'] === 'public') ? 'public' : 'friends';

$conn->begin_transaction(); 

try {
    // [BƯỚC 1] Lưu bài đăng (chỉ text) vào bảng `posts`
    // [CẬP NHẬT] Thêm PostType = 'status'
    $sql_post = "INSERT INTO posts (UserId, PostType, Content, Privacy) VALUES (?, 'status', ?, ?)";
    $stmt_post = $conn->prepare($sql_post);
    if ($stmt_post === false) {
        throw new Exception("Lỗi CSDL (prepare post): " . $conn->error);
    }
    $stmt_post->bind_param("iss", $user_id, $content, $privacy);
    $stmt_post->execute();
    
    $new_post_id = $conn->insert_id;
    $stmt_post->close();

    // [BƯỚC 2] Xử lý 1 file ảnh (nếu có)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        
        $target_dir = "../../uploads/posts/"; 
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $safe_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['image']['name'];
        $file_tmp_name = $_FILES['image']['tmp_name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_extension, $safe_extensions)) {
            $target_file_name = uniqid('post_') . '_' . time() . '.' . $file_extension;
            $target_file_path = $target_dir . $target_file_name;

            if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                $image_db_path = "uploads/posts/" . $target_file_name; 
                
                // [BƯỚC 3] Chèn ảnh vào bảng `post_images`
                $sql_image = "INSERT INTO post_images (PostId, ImagePath) VALUES (?, ?)";
                $stmt_image = $conn->prepare($sql_image);
                $stmt_image->bind_param("is", $new_post_id, $image_db_path);
                $stmt_image->execute();
                $stmt_image->close();
            } else {
                throw new Exception("Lỗi khi upload file: " . $file_name);
            }
        } else {
            throw new Exception("Định dạng file không hợp lệ: " . $file_name);
        }
    }

    // [BƯỚC 4] Nếu mọi thứ thành công, commit CSDL
    $conn->commit();
    $conn->close();
    header("Location: ../../Pages/PostPages/posts.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    if(isset($stmt_post)) $stmt_post->close();
    if(isset($stmt_image)) $stmt_image->close();
    if($conn) $conn->close();
    header("Location: ../../Pages/PostPages/create_post.php");
    exit();
}
?>