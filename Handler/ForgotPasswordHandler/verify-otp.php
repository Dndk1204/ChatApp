<?php
header('Content-Type: application/json');
require_once '../db.php';
session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($otp) || empty($email)) {
        $response['message'] = 'Vui lòng nhập OTP và email';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        $response['message'] = 'OTP phải là 6 chữ số';
        echo json_encode($response);
        exit;
    }
    
    // Kiểm tra OTP có tồn tại và chưa hết hạn
    $stmt = $conn->prepare("
        SELECT OtpId, UserId, Otp, IsUsed, ExpiresAt 
        FROM password_reset_otp 
        WHERE Email = ? AND Otp = ? AND IsUsed = 0
        ORDER BY CreatedAt DESC 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'OTP không tồn tại hoặc đã sử dụng';
        echo json_encode($response);
        exit;
    }
    
    $otpRecord = $result->fetch_assoc();
    
    // Kiểm tra OTP có hết hạn không
    if (strtotime($otpRecord['ExpiresAt']) < time()) {
        $response['message'] = 'OTP đã hết hạn. Vui lòng yêu cầu OTP mới';
        echo json_encode($response);
        exit;
    }
    
    // Đánh dấu OTP đã sử dụng
    $stmt = $conn->prepare("UPDATE password_reset_otp SET IsUsed = 1 WHERE OtpId = ?");
    $stmt->bind_param("i", $otpRecord['OtpId']);
    $stmt->execute();
    
    // Lưu vào session để xác nhận người dùng có thể reset password
    $_SESSION['reset_otp_verified'] = true;
    $_SESSION['reset_user_id'] = $otpRecord['UserId'];
    $_SESSION['reset_email'] = $email;
    
    $response['success'] = true;
    $response['message'] = 'OTP xác nhận thành công';
    $response['user_id'] = $otpRecord['UserId'];
}

echo json_encode($response);
exit;
?>
