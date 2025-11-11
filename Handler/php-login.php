<?php
session_start();
require_once '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT UserId, Username, Password, Role FROM Users WHERE Username = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Lỗi CSDL: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['Password'])) {
                $_SESSION['user_id'] = $user['UserId'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['Role'];

                $stmt->close();
                $conn->close();
                header("Location: ../index.php");
                exit();

            } else {
                throw new Exception("Tên đăng nhập hoặc mật khẩu không đúng.");
            }
        } else {
            throw new Exception("Tên đăng nhập hoặc mật khẩu không đúng.");
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        
        if (isset($stmt) && $stmt) $stmt->close();
        $conn->close();
        header("Location: ../login.php");
        exit();
    }

} else {
    $conn->close();
    header("Location: ../index.php");
    exit();
}
?>