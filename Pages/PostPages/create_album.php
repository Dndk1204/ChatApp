<?php
require_once '../../Handler/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo Album mới - ChatApp</title>
    <link rel="stylesheet" href="./../../css/style.css">
    </head>
<body>
    <main class="form-page-content">
        <div class="form-container">
            <h2 class="form-title">Tạo Album Mới</h2>

            <?php
                if (isset($_SESSION['error_message'])) {
                    echo '<p class="form-error">' . $_SESSION['error_message'] . '</p>';
                    unset($_SESSION['error_message']);
                }
            ?>
            
            <form action="../../Handler/PostHandler/create-album-handler.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title">Tên Album</label>
                    <input type="text" id="title" name="title" required class="comment-input"> </div>

                <div class="form-group">
                    <label for="post-content">Bạn đang nghĩ gì? (Tùy chọn)</label>
                    <textarea id="post-content" name="content"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="post-image">Chọn ảnh (Bắt buộc, chọn nhiều ảnh)</label>
                    <input type="file" id="post-image" name="images[]" accept="image/png, image/jpeg, image/gif" multiple required>
                </div>
                
                <div class="form-group">
                    <label for="privacy">Ai có thể xem Album này?</label>
                    <select id="privacy" name="privacy" style="width: 100%; padding: 10px; border: 1px solid #CCC; border-radius: 5px;">
                        <option value="friends" selected>Chỉ Bạn bè</option>
                        <option value="public">Công khai (Mọi người)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">Tạo Album</button>
            </form>
        </div>
    </main>

     </body>
</html>