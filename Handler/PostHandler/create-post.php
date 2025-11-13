<?php
session_start();
require_once '../db.php'; // ĐÚNG

// (1) Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST" || !$conn) {
    $_SESSION['error_message'] = "Truy cập không hợp lệ.";
    header("Location: ../../Pages/login.php"); // SỬA
    exit();
}

$user_id = $_SESSION['user_id'];
$content = $_POST['content'];
$image_path = NULL; 

// (2) Xử lý file upload (nếu có)
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

    $target_dir = "../../uploads/posts/"; // ĐÚNG

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $safe_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array(strtolower($file_extension), $safe_extensions)) {
        $target_file_name = uniqid('post_') . '_' . time() . '.' . $file_extension;
        $target_file_path = $target_dir . $target_file_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file_path)) {
            $image_path = "uploads/posts/" . $target_file_name; // ĐÚNG (Vì lưu vào DB)
        } else {
            $_SESSION['error_message'] = "Lỗi khi upload ảnh.";
            header("Location: ../../Pages/PostPages/create_post.php"); // SỬA
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Định dạng ảnh không hợp lệ. Chỉ chấp nhận jpg, jpeg, png, gif.";
        header("Location: ../../Pages/PostPages/create_post.php"); // SỬA
        exit();
    }
}

// (3) Lưu vào CSDL
try {
    $sql = "INSERT INTO posts (UserId, Content, ImagePath) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Lỗi CSDL: " . $conn->error);
    }

    $stmt->bind_param("iss", $user_id, $content, $image_path);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    header("Location: ../../Pages/PostPages/posts.php"); // ĐÚNG
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    if(isset($stmt) && $stmt) $stmt->close();
    if($conn) $conn->close();
    header("Location: ../../Pages/PostPages/create_post.php"); // SỬA
    exit();
}
?>