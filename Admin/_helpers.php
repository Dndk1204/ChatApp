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
	$username = htmlspecialchars($_SESSION['username'] ?? 'Admin');
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
			<a href="./index.php">THỐNG KÊ</a>
			<a href="./users.php">USERS</a>
			<a href="./messages.php">MESSAGES</a>
		</nav>
		<div class="auth-buttons">
			<span class="logged-in-user">Admin: ' . $username . '</span>
			<a href="../logout.php" class="btn-text">Logout</a>
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


