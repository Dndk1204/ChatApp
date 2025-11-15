<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu - ChatApp</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .auth-form-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .auth-form-title {
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.5);
        }
        
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .btn-submit:hover {
            background: #0056b3;
        }
        
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-links {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .form-links a {
            color: #007bff;
            text-decoration: none;
        }
        
        .form-links a:hover {
            text-decoration: underline;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50px;
            right: 50px;
            height: 2px;
            background: #ddd;
            z-index: -1;
        }
        
        .step {
            width: 30px;
            height: 30px;
            background: #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        
        .step.active {
            background: #007bff;
        }
        
        .step.completed {
            background: #28a745;
        }
    </style>
</head>
<body>
    <main class="form-page-content">
        <div class="auth-form-container">
            <h2 class="auth-form-title">Quên Mật Khẩu</h2>
            
            <div class="step-indicator">
                <div class="step active" id="step1">1</div>
                <div class="step" id="step2">2</div>
                <div class="step" id="step3">3</div>
            </div>

            <!-- BƯỚC 1: Nhập Email -->
            <div id="step-1-content">
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Nhập email của bạn để nhận mã OTP
                </p>
                
                <form id="forgotPasswordForm">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
                    </div>
                    <button type="submit" class="btn-submit">Gửi OTP</button>
                </form>
                
                <div class="form-links">
                    <a href="login.php">← Quay lại Login</a>
                </div>
            </div>

            <!-- BƯỚC 2: Nhập OTP -->
            <div id="step-2-content" style="display: none;">
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Mã OTP đã được gửi đến <strong id="emailDisplay"></strong>
                </p>
                
                <form id="verifyOtpForm">
                    <div class="form-group">
                        <label for="otp">Mã OTP (6 chữ số)</label>
                        <input type="text" id="otp" name="otp" placeholder="000000" maxlength="6" required>
                    </div>
                    <button type="submit" class="btn-submit">Xác Nhận OTP</button>
                </form>
                
                <div class="form-links">
                    <a href="javascript:void(0)" onclick="backToStep1()">← Quay lại</a>
                    <span style="margin: 0 10px;">|</span>
                    <a href="javascript:void(0)" onclick="resendOtp()">Gửi lại OTP</a>
                </div>
            </div>

            <!-- BƯỚC 3: Đặt Mật Khẩu Mới -->
            <div id="step-3-content" style="display: none;">
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Đặt mật khẩu mới cho tài khoản của bạn
                </p>
                
                <form id="resetPasswordForm">
                    <div class="form-group">
                        <label for="newPassword">Mật Khẩu Mới</label>
                        <input type="password" id="newPassword" name="new_password" placeholder="Nhập mật khẩu mới" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Xác Nhận Mật Khẩu</label>
                        <input type="password" id="confirmPassword" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
                    </div>
                    <button type="submit" class="btn-submit">Đặt Lại Mật Khẩu</button>
                </form>
            </div>

            <div id="messageBox" class="message" style="display: none;"></div>
        </div>
    </main>

    <script>
        let currentEmail = '';
        let otpResendCount = 0;
        const MAX_RESEND = 3;

        // BƯỚC 1: Gửi OTP
        document.getElementById('forgotPasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const messageBox = document.getElementById('messageBox');
            
            try {
                const response = await fetch('../Handler/forgot-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'email=' + encodeURIComponent(email)
                });
                
                const data = await response.json();
                messageBox.style.display = 'block';
                
                if (data.success) {
                    currentEmail = email;
                    otpResendCount = 0;
                    
                    messageBox.className = 'message success';
                    messageBox.textContent = data.message;
                    
                    // Chuyển sang bước 2
                    setTimeout(() => {
                        document.getElementById('step-1-content').style.display = 'none';
                        document.getElementById('step-2-content').style.display = 'block';
                        document.getElementById('emailDisplay').textContent = email;
                        
                        // Cập nhật step indicator
                        document.getElementById('step1').classList.add('completed');
                        document.getElementById('step2').classList.add('active');
                        
                        messageBox.style.display = 'none';
                    }, 1500);
                } else {
                    messageBox.className = 'message error';
                    messageBox.textContent = data.message;
                }
            } catch (error) {
                messageBox.className = 'message error';
                messageBox.textContent = 'Lỗi: ' + error.message;
                messageBox.style.display = 'block';
            }
        });

        // BƯỚC 2: Xác nhận OTP
        document.getElementById('verifyOtpForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const otp = document.getElementById('otp').value.trim();
            const messageBox = document.getElementById('messageBox');
            
            if (!/^\d{6}$/.test(otp)) {
                messageBox.className = 'message error';
                messageBox.textContent = 'OTP phải là 6 chữ số';
                messageBox.style.display = 'block';
                return;
            }
            
            try {
                const response = await fetch('../Handler/verify-otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'email=' + encodeURIComponent(currentEmail) + '&otp=' + encodeURIComponent(otp)
                });
                
                const data = await response.json();
                messageBox.style.display = 'block';
                
                if (data.success) {
                    messageBox.className = 'message success';
                    messageBox.textContent = data.message;
                    
                    // Chuyển sang bước 3
                    setTimeout(() => {
                        document.getElementById('step-2-content').style.display = 'none';
                        document.getElementById('step-3-content').style.display = 'block';
                        
                        // Cập nhật step indicator
                        document.getElementById('step2').classList.add('completed');
                        document.getElementById('step2').classList.remove('active');
                        document.getElementById('step3').classList.add('active');
                        
                        messageBox.style.display = 'none';
                    }, 1500);
                } else {
                    messageBox.className = 'message error';
                    messageBox.textContent = data.message;
                }
            } catch (error) {
                messageBox.className = 'message error';
                messageBox.textContent = 'Lỗi: ' + error.message;
                messageBox.style.display = 'block';
            }
        });

        // BƯỚC 3: Đặt lại mật khẩu
        document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const newPassword = document.getElementById('newPassword').value.trim();
            const confirmPassword = document.getElementById('confirmPassword').value.trim();
            const messageBox = document.getElementById('messageBox');
            
            if (newPassword !== confirmPassword) {
                messageBox.className = 'message error';
                messageBox.textContent = 'Mật khẩu không khớp';
                messageBox.style.display = 'block';
                return;
            }
            
            if (newPassword.length < 6) {
                messageBox.className = 'message error';
                messageBox.textContent = 'Mật khẩu phải có ít nhất 6 ký tự';
                messageBox.style.display = 'block';
                return;
            }
            
            try {
                const response = await fetch('../Handler/reset-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'new_password=' + encodeURIComponent(newPassword) + 
                          '&confirm_password=' + encodeURIComponent(confirmPassword)
                });
                
                const data = await response.json();
                messageBox.style.display = 'block';
                
                if (data.success) {
                    messageBox.className = 'message success';
                    messageBox.textContent = data.message + ' - Đang chuyển hướng...';
                    
                    // Cập nhật step indicator
                    document.getElementById('step3').classList.add('completed');
                    document.getElementById('step3').classList.remove('active');
                    
                    // Chuyển hướng sau 3 giây
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 3000);
                } else {
                    messageBox.className = 'message error';
                    messageBox.textContent = data.message;
                }
            } catch (error) {
                messageBox.className = 'message error';
                messageBox.textContent = 'Lỗi: ' + error.message;
                messageBox.style.display = 'block';
            }
        });

        // Quay lại bước 1
        function backToStep1() {
            document.getElementById('step-2-content').style.display = 'none';
            document.getElementById('step-1-content').style.display = 'block';
            
            document.getElementById('step1').classList.remove('completed');
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step2').classList.add('completed');
            document.getElementById('step1').classList.add('active');
            
            document.getElementById('otp').value = '';
            document.getElementById('messageBox').style.display = 'none';
            otpResendCount = 0;
        }

        // Gửi lại OTP
        async function resendOtp() {
            if (otpResendCount >= MAX_RESEND) {
                alert('Bạn đã hết lượt gửi lại OTP. Vui lòng bắt đầu lại từ đầu');
                return;
            }
            
            const messageBox = document.getElementById('messageBox');
            
            try {
                const response = await fetch('../Handler/forgot-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'email=' + encodeURIComponent(currentEmail)
                });
                
                const data = await response.json();
                messageBox.style.display = 'block';
                
                if (data.success) {
                    otpResendCount++;
                    messageBox.className = 'message success';
                    messageBox.textContent = data.message + ' (Lần ' + (otpResendCount + 1) + '/' + (MAX_RESEND + 1) + ')';
                    document.getElementById('otp').value = '';
                    document.getElementById('otp').focus();
                } else {
                    messageBox.className = 'message error';
                    messageBox.textContent = data.message;
                }
            } catch (error) {
                messageBox.className = 'message error';
                messageBox.textContent = 'Lỗi: ' + error.message;
                messageBox.style.display = 'block';
            }
        }

        // Cho phép chỉ nhập số cho OTP
        document.getElementById('otp').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>
</html>
