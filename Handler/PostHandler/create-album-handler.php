<?php
session_start();
require_once '../db.php'; // Lùi 1 cấp về Handler/

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    $_SESSION['error_message'] = "Truy cập không hợp lệ.";
    header("Location: ../../Pages/login.php"); 
    exit();
}

// Lấy dữ liệu
$user_id = $_SESSION['user_id'];
$title = $_POST['title']; // Tên Album (bắt buộc)
$content = $_POST['content'] ?? ''; // Mô tả (tùy chọn)
$privacy = (isset($_POST['privacy']) && $_POST['privacy'] === 'public') ? 'public' : 'friends';

// Kiểm tra xem có ảnh được upload không
if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
    $_SESSION['error_message'] = "Bạn phải chọn ít nhất 1 ảnh cho Album.";
    header("Location: ../../Pages/PostPages/create_album.php");
    exit();
}

$conn->begin_transaction(); 

try {
    // [BƯỚC 1] Lưu bài đăng (với Title, PostType = 'album')
    $sql_post = "INSERT INTO posts (UserId, PostType, Title, Content, Privacy) VALUES (?, 'album', ?, ?, ?)";
    $stmt_post = $conn->prepare($sql_post);
    if ($stmt_post === false) {
        throw new Exception("Lỗi CSDL (prepare post): " . $conn->error);
    }
    $stmt_post->bind_param("isss", $user_id, $title, $content, $privacy);
    $stmt_post->execute();
    
    $new_post_id = $conn->insert_id;
    $stmt_post->close();

    // [BƯỚC 2] Xử lý và lặp qua các file ảnh (GIỐNG HỆT FILE CŨ CỦA BẠN)
    $target_dir = "../../uploads/posts/"; 
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $safe_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $sql_image = "INSERT INTO post_images (PostId, ImagePath) VALUES (?, ?)";
    $stmt_image = $conn->prepare($sql_image);
    if ($stmt_image === false) {
        throw new Exception("Lỗi CSDL (prepare image): " . $conn->error);
    }

    $file_count = count($_FILES['images']['name']);
    $success_upload_count = 0;

    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['images']['error'][$i] == 0) {
            
            $file_name = $_FILES['images']['name'][$i];
            $file_tmp_name = $_FILES['images']['tmp_name'][$i];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($file_extension, $safe_extensions)) {
                $target_file_name = uniqid('post_') . '_' . time() . '_' . $i . '.' . $file_extension;
                $target_file_path = $target_dir . $target_file_name;

                if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                    $image_db_path = "uploads/posts/" . $target_file_name; 
                    
                    $stmt_image->bind_param("is", $new_post_id, $image_db_path);
                    $stmt_image->execute();
                    $success_upload_count++;
                }
            } 
        }
    }
    $stmt_image->close();
    
    // Nếu không upload thành công cái nào, báo lỗi
    if ($success_upload_count == 0) {
        throw new Exception("Không thể upload ảnh, vui lòng kiểm tra lại.");
    }

    // [BƯỚC 3] Commit CSDL
    $conn->commit();
    $conn->close();
    header("Location: ../../Pages/PostPages/posts.php");
    exit();

} catch (Exception $e) {
    // Nếu có lỗi, rollback (hủy) tất cả thay đổi
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
    
    // [ĐÃ SỬA] Xóa 2 dòng close() vì chúng đã được close ở trên
    // Chỉ cần đóng kết nối chính
    if($conn) $conn->close(); 
    
    header("Location: ../../Pages/PostPages/create_album.php");
    exit();
}
?>