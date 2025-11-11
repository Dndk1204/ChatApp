<?php
require_once __DIR__ . '/_auth.php';

// Kiểm tra cột CreatedAt
$hasCreatedAt = false;
try {
	$colRes = $conn->query("SHOW COLUMNS FROM Users LIKE 'CreatedAt'");
	if ($colRes && $colRes->num_rows === 1) {
		$hasCreatedAt = true;
	}
} catch (Exception $e) {
	$hasCreatedAt = false;
}

// Lấy thống kê
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
} catch (Exception $e) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Thống kê</title>
	<link rel="stylesheet" href="../css/style.css">
	<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
</head>
<body>
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
			<span class="logged-in-user">Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
			<a href="../logout.php" class="btn-text">Logout</a>
		</div>
	</header>

	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Thống kê</h1>
		</div>
		<section class="section">
			<div class="section-header">
				<h2 class="section-title">Tổng quan</h2>
			</div>
			<div class="section-body">
				<table>
					<thead>
						<tr>
							<th>Đang trực tuyến</th>
							<th>Đăng ký hôm nay</th>
							<th>Đăng ký tuần này</th>
							<th>Đăng ký tháng này</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php echo (int)$stats['online']; ?></td>
							<td><?php echo $hasCreatedAt && $stats['today'] !== null ? (int)$stats['today'] : 'N/A'; ?></td>
							<td><?php echo $hasCreatedAt && $stats['week'] !== null ? (int)$stats['week'] : 'N/A'; ?></td>
							<td><?php echo $hasCreatedAt && $stats['month'] !== null ? (int)$stats['month'] : 'N/A'; ?></td>
						</tr>
					</tbody>
				</table>
				<?php if (!$hasCreatedAt): ?>
					<p style="margin-top:8px;color:#bbb;">Gợi ý: thêm cột <code>CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP</code> vào bảng <code>Users</code> để bật thống kê đăng ký.</p>
				<?php endif; ?>
			</div>
		</section>
	</main>
</body>
</html>


