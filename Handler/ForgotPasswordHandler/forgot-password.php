<?php
header('Content-Type: application/json');

// Bắt lỗi PHP output
ob_start();

require_once '../db.php';
require_once 'email_helper.php';

session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $response['message'] = 'Vui lòng nhập email';
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    
    // Kiểm tra email tồn tại
    $stmt = $conn->prepare("SELECT UserId, Username FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Email không tồn tại trong hệ thống';
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $userId = $user['UserId'];
    $username = $user['Username'];
    
    // Xóa OTP cũ chưa sử dụng
    $stmt = $conn->prepare("DELETE FROM password_reset_otp WHERE UserId = ? AND IsUsed = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Tạo OTP 6 số
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Lưu OTP vào database (thời hạn 15 phút)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    $stmt = $conn->prepare("INSERT INTO password_reset_otp (UserId, Email, Otp, ExpiresAt) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $email, $otp, $expiresAt);
    
    if ($stmt->execute()) {
        // Gửi email OTP thực tế
        $emailResult = sendOtpEmail($email, $otp, $username);
        
        if ($emailResult['success']) {
            // Lưu vào session
            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_user_id'] = $userId;
            
            $response['success'] = true;
            $response['message'] = 'OTP đã được gửi đến email: ' . htmlspecialchars($email);
        } else {
            // Nếu lỗi gửi email nhưng OTP đã lưu, có thể xóa OTP hoặc giữ lại cho retry
            // Ở đây ta giữ lại OTP để user có thể thử lại
            
            $response['success'] = false;
            $response['message'] = 'Lỗi gửi email: ' . $emailResult['message'] . '. Vui lòng kiểm tra cấu hình email hoặc thử lại.';
            
            // Xóa OTP vừa tạo nếu gửi email thất bại
            $stmt = $conn->prepare("DELETE FROM password_reset_otp WHERE UserId = ? AND Otp = ?");
            $stmt->bind_param("is", $userId, $otp);
            $stmt->execute();
        }
    } else {
        $response['message'] = 'Lỗi khi tạo OTP. Vui lòng thử lại';
    }
}

// Xóa buffer output bất kỳ (lỗi PHP, warning, v.v)
$debug_output = ob_get_clean();
if (!empty($debug_output)) {
    $response['debug'] = $debug_output;
}

echo json_encode($response);
exit;
?>
