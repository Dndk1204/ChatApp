<?php
header('Content-Type: application/json');
require_once '../Handler/db.php';
session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra session xác nhận OTP
    if (!isset($_SESSION['reset_otp_verified']) || !$_SESSION['reset_otp_verified']) {
        $response['message'] = 'Vui lòng xác nhận OTP trước';
        echo json_encode($response);
        exit;
    }
    
    $userId = $_SESSION['reset_user_id'] ?? null;
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    if (!$userId || empty($newPassword) || empty($confirmPassword)) {
        $response['message'] = 'Vui lòng nhập mật khẩu mới';
        echo json_encode($response);
        exit;
    }
    
    if ($newPassword !== $confirmPassword) {
        $response['message'] = 'Mật khẩu không khớp';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($newPassword) < 6) {
        $response['message'] = 'Mật khẩu phải có ít nhất 6 ký tự';
        echo json_encode($response);
        exit;
    }
    
    // Hash mật khẩu
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Cập nhật mật khẩu trong database
    $stmt = $conn->prepare("UPDATE users SET Password = ? WHERE UserId = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        // Xóa session xác nhận
        unset($_SESSION['reset_otp_verified']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['otp_email']);
        unset($_SESSION['otp_user_id']);
        
        $response['success'] = true;
        $response['message'] = 'Mật khẩu đã được đặt lại thành công';
    } else {
        $response['message'] = 'Lỗi khi cập nhật mật khẩu. Vui lòng thử lại';
    }
}

echo json_encode($response);
exit;
?>
