<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	if (!validate_csrf($_POST['csrf_token'] ?? '')) {
		$flash_error = 'CSRF token không hợp lệ.';
	} else {
		try {
			if ($action === 'update_user_role') {
				$userId = intval($_POST['user_id'] ?? 0);
				$newRole = ($_POST['role'] ?? 'User') === 'Admin' ? 'Admin' : 'User';
				if ($userId === intval($_SESSION['user_id'])) {
					throw new Exception('Không thể thay đổi quyền của chính bạn.');
				}
				$stmt = $conn->prepare('UPDATE Users SET Role = ? WHERE UserId = ?');
				if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
				$stmt->bind_param('si', $newRole, $userId);
				$stmt->execute();
				$stmt->close();
				$flash_success = 'Cập nhật quyền người dùng thành công.';
			}
		} catch (Exception $ex) {
			$flash_error = $ex->getMessage();
		}
	}
}

$users = [];
try {
	$res = $conn->query('SELECT UserId, Username, Email, Role FROM Users ORDER BY UserId ASC');
	if ($res) {
		while ($row = $res->fetch_assoc()) { $users[] = $row; }
	}
} catch (Exception $e) { $flash_error = 'Lỗi tải danh sách người dùng.'; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin - Người dùng</title>
	<link rel="stylesheet" href="../css/style.css">
	<link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
</head>
<body>
	<?php admin_render_header('users'); ?>

	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Quản lý người dùng</h1>
			<div class="header-actions">
				<a class="btn btn-outline" href="./user_create.php">Tạo người dùng</a>
			</div>
		</div>

		<?php admin_render_flash($flash_success, $flash_error); ?>

		<section class="section">
			<div class="section-header">
				<h2 class="section-title">Danh sách</h2>
			</div>
			<div class="section-body">
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Username</th>
							<th>Email</th>
							<th>Quyền</th>
							<th>Hành động</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($users as $u): ?>
						<tr>
							<td><?php echo (int)$u['UserId']; ?></td>
							<td><?php echo htmlspecialchars($u['Username']); ?></td>
							<td><?php echo htmlspecialchars($u['Email']); ?></td>
							<td>
								<span class="badge <?php echo $u['Role']==='Admin'?'badge-admin':''; ?>">
									<?php echo htmlspecialchars($u['Role']); ?>
								</span>
							</td>
							<td>
								<div class="action-row">
									<form method="post" onsubmit="return confirm('Xác nhận cập nhật quyền?');">
										<?php admin_csrf_field(); ?>
										<input type="hidden" name="action" value="update_user_role">
										<input type="hidden" name="user_id" value="<?php echo (int)$u['UserId']; ?>">
										<select name="role">
											<option value="User" <?php echo $u['Role']==='User'?'selected':''; ?>>User</option>
											<option value="Admin" <?php echo $u['Role']==='Admin'?'selected':''; ?>>Admin</option>
										</select>
										<button class="btn btn-primary" type="submit">Lưu quyền</button>
									</form>
									<a class="btn" href="./user_edit.php?id=<?php echo (int)$u['UserId']; ?>">Sửa</a>
								</div>
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


