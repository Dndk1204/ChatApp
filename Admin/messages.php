<?php
require_once __DIR__ . '/_auth.php';

$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!validate_csrf($_POST['csrf_token'] ?? '')) {
		$flash_error = 'CSRF token không hợp lệ.';
	} else {
		try {
			$action = $_POST['action'] ?? '';
			if ($action === 'delete_message') {
				$messageId = intval($_POST['message_id'] ?? 0);
				$stmt = $conn->prepare('DELETE FROM Messages WHERE MessageId = ?');
				if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
				$stmt->bind_param('i', $messageId);
				$stmt->execute();
				$affected = $stmt->affected_rows;
				$stmt->close();
				if ($affected > 0) $flash_success = 'Đã xóa tin nhắn.';
				else $flash_error = 'Không tìm thấy tin nhắn.';
			}
		} catch (Exception $ex) {
			$flash_error = $ex->getMessage();
		}
	}
}

$messages = [];
try {
	$sql = 'SELECT m.MessageId, m.Content, m.SentAt, s.Username AS SenderUsername, r.Username AS ReceiverUsername, m.GroupId
		FROM Messages m
		LEFT JOIN Users s ON m.SenderId = s.UserId
		LEFT JOIN Users r ON m.ReceiverId = r.UserId
		ORDER BY m.MessageId DESC
		LIMIT 100';
	$res = $conn->query($sql);
	if ($res) { while ($row = $res->fetch_assoc()) { $messages[] = $row; } }
} catch (Exception $e) { $flash_error = 'Lỗi tải tin nhắn.'; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Tin nhắn</title>
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
			<h1 class="admin-title">Tin nhắn gần đây</h1>
		</div>

		<?php if (!empty($flash_success)): ?><div class="flash flash-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>
		<?php if (!empty($flash_error)): ?><div class="flash flash-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>

		<section class="section">
			<div class="section-header">
				<h2 class="section-title">Danh sách</h2>
			</div>
			<div class="section-body">
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Người gửi</th>
							<th>Người nhận</th>
							<th>Nội dung</th>
							<th>Thời gian</th>
							<th>Hành động</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($messages as $m): ?>
						<tr>
							<td><?php echo (int)$m['MessageId']; ?></td>
							<td><?php echo htmlspecialchars($m['SenderUsername'] ?? '—'); ?></td>
							<td>
								<?php
									if (!empty($m['GroupId'])) { echo 'Group #' . (int)$m['GroupId']; }
									else { echo htmlspecialchars($m['ReceiverUsername'] ?? '—'); }
								?>
							</td>
							<td><?php echo htmlspecialchars($m['Content']); ?></td>
							<td><?php echo htmlspecialchars($m['SentAt']); ?></td>
							<td>
								<form method="post" onsubmit="return confirm('Xác nhận xóa tin nhắn?');">
									<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
									<input type="hidden" name="action" value="delete_message">
									<input type="hidden" name="message_id" value="<?php echo (int)$m['MessageId']; ?>">
									<button class="btn btn-danger" type="submit">Xóa</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
	</main>
</body>
</html>


