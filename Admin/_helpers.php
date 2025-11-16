<?php
require_once __DIR__ . '/_auth.php';

function admin_csrf_token() {
	return get_csrf_token();
}

function admin_csrf_field() {
	echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token()) . '">';
}

function admin_render_flash($flash_success, $flash_error) {
	if (!empty($flash_success)) {
		echo '<div class="flash flash-success">' . htmlspecialchars($flash_success) . '</div>';
	}
	if (!empty($flash_error)) {
		echo '<div class="flash flash-error">' . htmlspecialchars($flash_error) . '</div>';
	}
}

function admin_render_header($active = '') {
    // Tách biến $active (tên trang hiện tại)
    $username = htmlspecialchars($_SESSION['username'] ?? 'Admin');
    
    // ↓↓↓ SỬA LỖI Ở ĐÂY: Lấy user_id từ SESSION ↓↓↓
    $current_user_id = (int)($_SESSION['user_id'] ?? 0); 
    // ↑↑↑ KẾT THÚC SỬA LỖI ↑↑↑

    $stats_active = ($active === 'stats') ? 'class="active"' : '';
    $users_active = ($active === 'users') ? 'class="active"' : '';
    $messages_active = ($active === 'messages') ? 'class="active"' : '';
    $reports_active = ($active === 'reports') ? 'class="active"' : '';

    $avatar_path = $_SESSION['avatar'] ?? 'uploads/default-avatar.jpg';
    $avatar_src = '../' . htmlspecialchars($avatar_path);

    echo '
    <header class="navbar">
        <div class="logo">
            <a href="../index.php">
                <div class="logo-circle"></div>
                <span>ChatApp</span>
            </a>
        </div>
        <nav class="main-nav">
            <a href="../index.php">HOME</a>
            <a href="./index.php" ' . $stats_active . '>STATISTICS</a>
            <a href="./users.php" ' . $users_active . '>USERS</a>
            <a href="./messages.php" ' . $messages_active . '>MESSAGES</a>
            <a href="./manage_reports.php" ' . $reports_active . '>REPORT</a> 
        </nav>
        
        <div class="auth-buttons">
            <span class="logged-in-user">Admin: ' . $username . '</span>
            
            <div class="avatar-menu">
                <img src="' . $avatar_src . '" alt="avatar" class="avatar-thumb" id="adminAvatarBtn" onerror="this.src=\'../../uploads/default-avatar.jpg\'">
                
                <div class="avatar-dropdown" id="adminAvatarDropdown">
                    <a href="../Pages/ProfilePages/Profile.php?id=' . $current_user_id . '">Trang cá nhân của tôi</a>
                    <a href="../Pages/ProfilePages/edit_profile.php">Chỉnh sửa hồ sơ</a>
                    <a href="../Pages/hidden_list.php">Quản lý Ẩn</a>
                    <a href="../Pages/blocked_list.php">Quản lý Chặn</a>
                    <a href="../Handler/logout.php">Logout</a>
                </div>
            </div>
            </div>
    </header>';
}

function admin_has_created_at($conn) {
	try {
		$colRes = $conn->query("SHOW COLUMNS FROM Users LIKE 'CreatedAt'");
		return ($colRes && $colRes->num_rows === 1);
	} catch (Exception $e) {
		return false;
	}
}

function admin_get_stats($conn, $hasCreatedAt) {
	$stats = [
		'online' => 0,
		'today' => null,
		'week' => null,
		'month' => null
	];
	try {
		$res = $conn->query('SELECT COUNT(*) AS c FROM Users WHERE IsOnline = 1');
		if ($res) { $row = $res->fetch_assoc(); $stats['online'] = intval($row['c'] ?? 0); }
		if ($hasCreatedAt) {
			$res = $conn->query('SELECT COUNT(*) AS c FROM Users WHERE DATE(CreatedAt) = CURDATE()');
			if ($res) { $row = $res->fetch_assoc(); $stats['today'] = intval($row['c'] ?? 0); }
			$res = $conn->query("SELECT COUNT(*) AS c FROM Users WHERE YEARWEEK(CreatedAt, 1) = YEARWEEK(CURDATE(), 1)");
			if ($res) { $row = $res->fetch_assoc(); $stats['week'] = intval($row['c'] ?? 0); }
			$res = $conn->query("SELECT COUNT(*) AS c FROM Users WHERE YEAR(CreatedAt) = YEAR(CURDATE()) AND MONTH(CreatedAt) = MONTH(CURDATE())");
			if ($res) { $row = $res->fetch_assoc(); $stats['month'] = intval($row['c'] ?? 0); }
		}
	} catch (Exception $e) {
		// ignore
	}
	return $stats;
}

/**
 * Render HTML head section cho admin pages
 */
function admin_render_head($title) {
    echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Lấy đúng ID mà chúng ta đã đặt trong admin_render_header
        const avatarBtn = document.getElementById("adminAvatarBtn"); 
        const avatarDropdown = document.getElementById("adminAvatarDropdown");

        if (avatarBtn && avatarDropdown) {
            // 1. Khi bấm vào avatar
            avatarBtn.addEventListener("click", function(event) {
                event.stopPropagation(); // Ngăn sự kiện lan ra ngoài
                avatarDropdown.classList.toggle("open");
            });
            
            // 2. Khi bấm ra ngoài
            document.addEventListener("click", function(event) {
                if (avatarDropdown.classList.contains("open") && !avatarDropdown.contains(event.target)) {
                    avatarDropdown.classList.remove("open"); // Tắt menu
                }
            });
        }
    });
    </script>
    </head>
<body>';
}

/**
 * Tính toán phân trang
 */
function admin_get_pagination($current_page, $total_items, $items_per_page) {
	$current_page = max(1, intval($current_page));
	$total_pages = ceil($total_items / $items_per_page);
	$offset = ($current_page - 1) * $items_per_page;
	
	return [
		'current_page' => $current_page,
		'total_pages' => $total_pages,
		'offset' => $offset,
		'items_per_page' => $items_per_page
	];
}

/**
 * Render phân trang
 */
function admin_render_pagination($current_page, $total_pages, $total_items, $item_label = 'mục', $extra_params = '') {
    if ($total_pages <= 1) return;
    
    // Xử lý tham số URL (ví dụ: 'tab=group')
    $query_string = $extra_params ? '&' . ltrim(htmlspecialchars($extra_params), '&') : '';
    
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    echo '<div class="pagination">';
    
    // Trang đầu tiên
    if ($start_page > 1) {
        echo '<a href="?page=1' . $query_string . '" class="pagination-btn">1</a>';
        if ($start_page > 2) {
            echo '<span class="pagination-ellipsis">...</span>';
        }
    }
    
    // Các trang xung quanh
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            echo '<span class="pagination-btn active">' . $i . '</span>';
        } else {
            echo '<a href="?page=' . $i . $query_string . '" class="pagination-btn">' . $i . '</a>';
        }
    }
    
    // Trang cuối cùng
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            echo '<span class="pagination-ellipsis">...</span>';
        }
        echo '<a href="?page=' . $total_pages . $query_string . '" class="pagination-btn">' . $total_pages . '</a>';
    }
    
    echo '</div>';
    echo '<div class="pagination-info">';
    echo 'Trang ' . $current_page . ' / ' . $total_pages . ' (' . $total_items . ' ' . $item_label . ')';
    echo '</div>';
}

/**
 * Xử lý POST request với CSRF validation
 */
function admin_handle_post($callback) {
	$flash_success = '';
	$flash_error = '';
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!validate_csrf($_POST['csrf_token'] ?? '')) {
			$flash_error = 'CSRF token không hợp lệ.';
		} else {
			try {
				$result = $callback();
				if (isset($result['success'])) {
					$flash_success = $result['success'];
				}
				if (isset($result['error'])) {
					$flash_error = $result['error'];
				}
			} catch (Exception $ex) {
				$flash_error = $ex->getMessage();
			}
		}
	}
	
	return ['success' => $flash_success, 'error' => $flash_error];
}

function admin_render_confirm_modal() {
    
    // 1. CSS cho modal
    echo '
<style>
#admin-confirm-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    display: none; /* Ẩn ban đầu */
    justify-content: center;
    align-items: center;
    z-index: 10001; /* Nằm trên mọi thứ */
    font-family: Arial, sans-serif;
}
.admin-confirm-box {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,.2);
    width: 360px;
    text-align: center;
}
.admin-confirm-box p {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #333;
    line-height: 1.5;
}
.admin-confirm-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}
.admin-confirm-actions button {
    padding: 10px 20px;
    font-size: 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}
/* Sử dụng lại class .btn từ admin.css của bạn */
.admin-confirm-actions .btn-secondary {
    background: #6F9DE1; color: #fff;
}
.admin-confirm-actions .btn-danger {
    background: #dc3545; color: #fff;
}
</style>';
    
    // 2. HTML cho modal
    echo '
<div id="admin-confirm-overlay" onclick="adminHideConfirm()">
    <div class="admin-confirm-box" onclick="event.stopPropagation()">
        <p id="admin-confirm-message">Bạn có chắc chắn?</p>
        <div class="admin-confirm-actions">
            <button id="admin-confirm-btn-cancel" class="btn btn-danger">Hủy</button>
            <button id="admin-confirm-btn-ok" class="btn btn-secondary">Xác nhận</button>
        </div>
    </div>
</div>';
    
    // 3. JavaScript cho modal
    echo '
<script>
// Lấy các phần tử DOM
const ADMIN_CONFIRM_OVERLAY = document.getElementById("admin-confirm-overlay");
const ADMIN_CONFIRM_MESSAGE = document.getElementById("admin-confirm-message");
const ADMIN_CONFIRM_BTN_OK = document.getElementById("admin-confirm-btn-ok");
const ADMIN_CONFIRM_BTN_CANCEL = document.getElementById("admin-confirm-btn-cancel");

// Biến toàn cục để lưu hành động (callback)
let admin_confirmCallback = null;

/**
 * Hiển thị popup với một tin nhắn và một hành động
 * @param {string} message Tin nhắn cần hiển thị
 * @param {function} onConfirm Hàm sẽ chạy khi bấm "Xác nhận"
 */
function adminShowConfirm(message, onConfirm) {
    if (!ADMIN_CONFIRM_OVERLAY) return;
    ADMIN_CONFIRM_MESSAGE.textContent = message;
    admin_confirmCallback = onConfirm; // Lưu lại hành động
    ADMIN_CONFIRM_OVERLAY.style.display = "flex";
}

/**
 * Ẩn popup
 */
function adminHideConfirm() {
    if (!ADMIN_CONFIRM_OVERLAY) return;
    ADMIN_CONFIRM_OVERLAY.style.display = "none";
    admin_confirmCallback = null; // Xóa hành động
}

// Gán sự kiện cho các nút
if (ADMIN_CONFIRM_BTN_CANCEL) {
    ADMIN_CONFIRM_BTN_CANCEL.onclick = adminHideConfirm;
}
if (ADMIN_CONFIRM_BTN_OK) {
    ADMIN_CONFIRM_BTN_OK.onclick = () => {
        if (typeof admin_confirmCallback === "function") {
            admin_confirmCallback(); // Chạy hành động
        }
        adminHideConfirm(); // Ẩn popup
    };
}
</script>';
}
