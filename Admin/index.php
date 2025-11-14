<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

// Kiểm tra cột CreatedAt
$hasCreatedAt = admin_has_created_at($conn);

// Lấy thống kê
$stats = admin_get_stats($conn, $hasCreatedAt);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Thống kê</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono&display=swap" rel="stylesheet">
    
    <style>
        .stat-grid {
            display: grid;
            /* Tạo 4 cột, tự động xuống hàng trên màn hình nhỏ */
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; /* Khoảng cách giữa các khối */
        }
        .stat-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .stat-card-number {
            font-size: 2.2rem; /* Làm con số to rõ */
            font-weight: 700;
            color: var(--color-accent); /* Dùng màu nhấn */
            margin-bottom: 5px;
        }
        .stat-card-label {
            font-size: 0.9rem;
            color: var(--color-text-muted); /* Dùng màu chữ mờ */
            font-weight: 500;
        }
    </style>
</head>
<body>
	<?php
		admin_render_head('Admin - Thống kê'); 
		admin_render_header('stats'); 
	?>

	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Thống kê</h1>
		</div>
		<section class="section">
			<div class="section-header">
				<h2 class="section-title">Tổng quan</h2>
			</div>
			<div class="section-body">
                
                <div class="stat-grid">
                    
                    <div class="stat-card">
                        <span class="stat-card-number"><?php echo (int)$stats['online']; ?></span>
                        <span class="stat-card-label">Đang trực tuyến</span>
                    </div>

                    <div class="stat-card">
                        <span class="stat-card-number"><?php echo $hasCreatedAt && $stats['today'] !== null ? (int)$stats['today'] : 'N/A'; ?></span>
                        <span class="stat-card-label">Đăng ký hôm nay</span>
                    </div>

                    <div class="stat-card">
                        <span class="stat-card-number"><?php echo $hasCreatedAt && $stats['week'] !== null ? (int)$stats['week'] : 'N/A'; ?></span>
                        <span class="stat-card-label">Đăng ký tuần này</span>
                    </div>

                    <div class="stat-card">
                        <span class="stat-card-number"><?php echo $hasCreatedAt && $stats['month'] !== null ? (int)$stats['month'] : 'N/A'; ?></span>
                        <span class="stat-card-label">Đăng ký tháng này</span>
                    </div>
                    
                </div>
                <?php if (!$hasCreatedAt): ?>
                    <p style="margin-top: 15px; color: var(--color-text-muted); font-size: 0.9em;">
                        Gợi ý: thêm cột <code>CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP</code> vào bảng <code>Users</code> để bật thống kê đăng ký.
                    </p>
                <?php endif; ?>
            </div>
		</section>
	</main>
</body>
</html>


