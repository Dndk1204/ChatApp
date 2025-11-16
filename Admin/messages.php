<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

// 1. LẤY TAB HIỆN TẠI
$current_tab = $_GET['tab'] ?? 'private';
$tab_query_string = 'tab=' . htmlspecialchars($current_tab);

// 2. XỬ LÝ POST (Thêm action 'delete_group')
$flash = admin_handle_post(function() use ($conn) {
	$action = $_POST['action'] ?? '';
	
    // Xóa tin nhắn (giữ nguyên)
    if ($action === 'delete_message') {
		$messageId = intval($_POST['message_id'] ?? 0);
		$stmt = $conn->prepare('DELETE FROM Messages WHERE MessageId = ?');
		if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
		$stmt->bind_param('i', $messageId);
		$stmt->execute();
		$affected = $stmt->affected_rows;
		$stmt->close();
		if ($affected > 0) {
			return ['success' => 'Đã xóa tin nhắn.'];
		} else {
			return ['error' => 'Không tìm thấy tin nhắn.'];
		}
	}
    
    // === HÀNH ĐỘNG MỚI: XÓA NHÓM ===
    if ($action === 'delete_group') {
        $groupId = intval($_POST['group_id'] ?? 0);
        if ($groupId === 0) return ['error' => 'Group ID không hợp lệ.'];

        $conn->begin_transaction();
        try {
            // 1. Xóa tất cả tin nhắn trong nhóm
            $stmt_msg = $conn->prepare('DELETE FROM messages WHERE GroupId = ?');
            $stmt_msg->bind_param('i', $groupId);
            $stmt_msg->execute();
            $stmt_msg->close();
            
            // 2. Xóa nhóm (sẽ tự động xóa group_members nhờ Foreign Key)
            $stmt_group = $conn->prepare('DELETE FROM groups WHERE GroupId = ?');
            $stmt_group->bind_param('i', $groupId);
            $stmt_group->execute();
            $affected_rows = $stmt_group->affected_rows;
            $stmt_group->close();
            
            $conn->commit();
            
            if ($affected_rows > 0) {
                return ['success' => 'Đã xóa nhóm và tất cả tin nhắn liên quan.'];
            } else {
                return ['error' => 'Không tìm thấy nhóm để xóa.'];
            }
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception('Lỗi CSDL khi xóa nhóm: ' . $e->getMessage());
        }
    }
	return [];
});

// 3. PHÂN TRANG VÀ LẤY DỮ LIỆU (Đã cập nhật cho tab)
$items_per_page = 5;
$total_messages = 0;
$messages = [];

// Tạo mệnh đề WHERE dựa trên tab
$where_clause = '';
if ($current_tab === 'private') {
    $where_clause = 'WHERE m.GroupId IS NULL';
} else {
    $where_clause = 'WHERE m.GroupId IS NOT NULL';
}

try {
    // Sửa câu lệnh COUNT
	$count_sql = 'SELECT COUNT(*) as total FROM Messages m ' . $where_clause;
	$count_res = $conn->query($count_sql);
	if ($count_res) {
		$total_messages = $count_res->fetch_assoc()['total'];
	}
	
	$pagination = admin_get_pagination($_GET['page'] ?? 1, $total_messages, $items_per_page);
	
    // Sửa câu lệnh SELECT: Thêm LEFT JOIN groups và g.GroupName
    $sql = 'SELECT m.MessageId, m.Content, m.SentAt, 
                   s.Username AS SenderUsername, 
                   r.Username AS ReceiverUsername, 
                   m.GroupId, g.GroupName
		FROM Messages m
		LEFT JOIN Users s ON m.SenderId = s.UserId
		LEFT JOIN Users r ON m.ReceiverId = r.UserId
        LEFT JOIN groups g ON m.GroupId = g.GroupId ' . // <-- JOIN BẢNG GROUPS
        $where_clause . '
		ORDER BY m.MessageId DESC
		LIMIT ? OFFSET ?';
        
	$stmt = $conn->prepare($sql);
	if ($stmt) {
		$stmt->bind_param('ii', $pagination['items_per_page'], $pagination['offset']);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) { $messages[] = $row; }
		$stmt->close();
	}
} catch (Exception $e) { 
	$flash['error'] = 'Lỗi tải tin nhắn: ' . $e->getMessage(); 
	$pagination = admin_get_pagination(1, 0, $items_per_page);
}

admin_render_head('Admin - Tin nhắn');
// Thêm CSS cho tab
echo '
<style>
    .admin-tabs {
        display: flex;
        gap: 5px;
        border-bottom: 2px solid var(--color-border);
        margin-bottom: 20px;
    }
    .admin-tab {
        padding: 10px 15px;
        text-decoration: none;
        color: var(--color-text-muted);
        font-weight: 600;
        border-bottom: 2px solid transparent;
    }
    .admin-tab.active, .admin-tab:hover {
        color: var(--color-accent);
        border-bottom-color: var(--color-accent);
    }
		/* 1. Ép body layout giống các trang khác */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        overflow: hidden; /* Ngăn cả trang cuộn */
    }
    
    /* 2. Ép main container co giãn và chiếm đúng chiều cao 
       (100vh trừ đi 60px của header) */
    main.admin-container {
        flex: 1; 
        display: flex;
        flex-direction: column;
        overflow: hidden; 
        height: calc(100vh - 60px); 
    }
    
    /* 3. Cho section chứa table co giãn */
    .section {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden; /* Ngăn section cuộn */
    }
    
    /* 4. CHO PHÉP .section-body (chứa table) cuộn */
    .section-body {
        flex: 1; /* Chiếm hết không gian còn lại */
        overflow-y: auto; /* Đây là dòng quan trọng nhất */
    }
</style>';
admin_render_header('messages');
?>
	<main class="admin-container">
		<div class="header-bar">
			<h1 class="admin-title">Quản lý tin nhắn</h1>
		</div>

		<?php admin_render_flash($flash['success'], $flash['error']); ?>

        <div class="admin-tabs">
            <a href="?tab=private" class="admin-tab <?php echo $current_tab === 'private' ? 'active' : ''; ?>">
                Tin nhắn riêng
            </a>
            <a href="?tab=group" class="admin-tab <?php echo $current_tab === 'group' ? 'active' : ''; ?>">
                Tin nhắn nhóm
            </a>
        </div>
        <section class="section">
			<div class="section-header">
				<h2 class="section-title">Danh sách <?php echo $current_tab === 'private' ? 'tin nhắn riêng' : 'tin nhắn nhóm'; ?></h2>
			</div>
			<div class="section-body">
				<table>
					<thead>
						<tr>
							<th>ID</th>
							<th>Người gửi</th>
							<th><?php echo $current_tab === 'private' ? 'Người nhận' : 'Nhóm'; ?></th>
							<th>Nội dung</th>
							<th>Thời gian</th>
							<th>Hành động</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($messages as $m): ?>
						<tr>
							<td><?php echo (int)$m['MessageId']; ?></td>
							<td><?php echo htmlspecialchars($m['SenderUsername'] ?? 'Hệ thống'); ?></td>
							<td>
								<?php
                                    // SỬA LỖI HIỂN THỊ TÊN
									if ($current_tab === 'group') { 
                                        // Ưu tiên GroupName, nếu không có (nhóm đã bị xóa) thì hiển thị GroupId
                                        echo '<strong>' . htmlspecialchars($m['GroupName'] ?? ('Group #' . (int)$m['GroupId'])) . '</strong>';
                                    }
									else { 
                                        echo htmlspecialchars($m['ReceiverUsername'] ?? '—'); 
                                    }
								?>
							</td>
							<td><?php echo htmlspecialchars($m['Content']); ?></td>
							<td><?php echo htmlspecialchars($m['SentAt']); ?></td>
							<td>
                                <form method="post" id="delete-msg-form-<?php echo (int)$m['MessageId']; ?>" style="display: inline;">
                                    <?php admin_csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="message_id" value="<?php echo (int)$m['MessageId']; ?>">
                                    
                                    <button class="btn btn-secondary" type="button" 
                                            onclick="adminShowConfirm(
                                                'Bạn có chắc chắn muốn xóa tin nhắn này?', 
                                                () => document.getElementById('delete-msg-form-<?php echo (int)$m['MessageId']; ?>').submit()
                                            )">
                                        Xóa tin nhắn
                                    </button>
                                </form>
                                
                                <?php if ($current_tab === 'group'): ?>
                                    <form method="post" id="delete-group-form-<?php echo (int)$m['GroupId']; ?>" style="display: inline; margin-left: 5px;">
                                        <?php admin_csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_group">
                                        <input type="hidden" name="group_id" value="<?php echo (int)$m['GroupId']; ?>">
                                        <button class="btn btn-danger" type="button"
                                                onclick="adminShowConfirm(
                                                    'XÓA NHÓM?\n\nHành động này sẽ xóa vĩnh viễn nhóm \'<?php echo htmlspecialchars(addslashes($m['GroupName'] ?? 'N/A')); ?>\' và TOÀN BỘ tin nhắn trong đó.\n\nBạn có chắc không?', 
                                                    () => document.getElementById('delete-group-form-<?php echo (int)$m['GroupId']; ?>').submit()
                                                )">
                                            Xóa Nhóm
                                        </button>
                                    </form>
                                <?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<?php 
                // SỬA LỖI PHÂN TRANG: Truyền $tab_query_string vào
                admin_render_pagination(
                    $pagination['current_page'], 
                    $pagination['total_pages'], 
                    $total_messages, 
                    'tin nhắn',
                    $tab_query_string
                ); 
                ?>
			</div>
		</section>
	</main>
	<?php 
		// Thêm dòng này vào cuối file, ngay trước </body>
		admin_render_confirm_modal(); 
	?>
</body>
</html>