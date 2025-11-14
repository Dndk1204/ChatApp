<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_helpers.php';

// Xử lý POST (Duyệt hoặc Xóa)
$flash = admin_handle_post(function() use ($conn) {
    $action = $_POST['action'] ?? '';
    
    // HÀNH ĐỘNG 1: DUYỆT (BỎ QUA)
    if ($action === 'resolve_report') {
        $report_id = intval($_POST['report_id'] ?? 0);
        if ($report_id <= 0) {
            throw new Exception('ID báo cáo không hợp lệ.');
        }
        
        // Cập nhật trạng thái từ 'pending' -> 'resolved'
        $stmt = $conn->prepare("UPDATE reports SET Status = 'resolved' WHERE ReportId = ? AND Status = 'pending'");
        if (!$stmt) throw new Exception('Lỗi CSDL: ' . $conn->error);
        
        $stmt->bind_param('i', $report_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected > 0) {
            return ['success' => 'Đã duyệt báo cáo (đánh dấu đã giải quyết).'];
        } else {
            return ['error' => 'Không tìm thấy báo cáo hoặc báo cáo đã được giải quyết.'];
        }
    }
    
    // HÀNH ĐỘNG 2: XÓA BÀI ĐĂNG (VÀ TỰ ĐỘNG XÓA BÁO CÁO)
    if ($action === 'delete_post') {
        $post_id = intval($_POST['post_id'] ?? 0);
        if ($post_id <= 0) {
            throw new Exception('ID bài đăng không hợp lệ.');
        }

        // (A) Lấy đường dẫn ảnh để xóa file
        $image_path = null;
        $stmt_get = $conn->prepare("SELECT ImagePath FROM posts WHERE PostId = ?");
        if ($stmt_get) {
            $stmt_get->bind_param('i', $post_id);
            $stmt_get->execute();
            $res = $stmt_get->get_result();
            if ($res->num_rows > 0) {
                $image_path = $res->fetch_assoc()['ImagePath'];
            }
            $stmt_get->close();
        }

        // (B) Xóa bài đăng (CSDL của bạn đã cài ON DELETE CASCADE, 
        // nên reports, comments, postemotes liên quan sẽ tự động bị xóa)
        $stmt_del = $conn->prepare("DELETE FROM posts WHERE PostId = ?");
        if (!$stmt_del) throw new Exception('Lỗi CSDL: ' . $conn->error);
        
        $stmt_del->bind_param('i', $post_id);
        $stmt_del->execute();
        $affected = $stmt_del->affected_rows;
        $stmt_del->close();
        
        if ($affected > 0) {
            // (C) Xóa file ảnh (nếu có)
            if (!empty($image_path)) {
                // Đường dẫn file ảnh: đi lên 1 cấp (về thư mục gốc) rồi vào ImagePath
                $file_to_delete = __DIR__ . '/../' . $image_path; 
                if (file_exists($file_to_delete)) {
                    @unlink($file_to_delete);
                }
            }
            return ['success' => 'Đã xóa bài đăng. (Các báo cáo liên quan cũng tự động bị xóa).'];
        } else {
            return ['error' => 'Không tìm thấy bài đăng để xóa (có thể đã bị xóa trước đó).'];
        }
    }
    
    return [];
});

// Phân trang (Lấy các báo cáo 'pending')
$items_per_page = 10;
$total_reports = 0;
$reports = [];
try {
    // Chỉ đếm các báo cáo 'pending'
    $count_res = $conn->query("SELECT COUNT(*) as total FROM reports WHERE Status = 'pending'");
    if ($count_res) {
        $total_reports = $count_res->fetch_assoc()['total'];
    }
    
    $pagination = admin_get_pagination($_GET['page'] ?? 1, $total_reports, $items_per_page);
    
    // Lấy thông tin chi tiết của báo cáo
    $sql = "SELECT 
                r.ReportId, r.PostId, r.ReportedAt,
                p.Content AS PostContent,
                reporter.Username AS ReporterUsername,
                author.Username AS AuthorUsername
            FROM reports r
            LEFT JOIN posts p ON r.PostId = p.PostId
            LEFT JOIN users reporter ON r.ReporterId = reporter.UserId
            LEFT JOIN users author ON p.UserId = author.UserId
            WHERE r.Status = 'pending'
            ORDER BY r.ReportedAt DESC
            LIMIT ? OFFSET ?";
            
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ii', $pagination['items_per_page'], $pagination['offset']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { 
            // Nếu bài đăng đã bị xóa (VD: user tự xóa) nhưng báo cáo còn, ta vẫn thấy
            if (empty($row['PostId'])) {
                $row['PostContent'] = '[Bài đăng đã bị xóa]';
                $row['AuthorUsername'] = 'N/A';
            }
            $reports[] = $row; 
        }
        $stmt->close();
    }
} catch (Exception $e) { 
    $flash['error'] = 'Lỗi tải danh sách báo cáo: ' . $e->getMessage(); 
    $pagination = admin_get_pagination(1, 0, $items_per_page);
}

// Render HTML
admin_render_head('Admin - Báo xấu');
admin_render_header('reports');
?>
    <main class="admin-container">
        <div class="header-bar">
            <h1 class="admin-title">Quản lý Báo xấu</h1>
        </div>

        <?php admin_render_flash($flash['success'], $flash['error']); ?>

        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Báo xấu đang chờ xử lý</h2>
            </div>
            <div class="section-body">
                <table>
                    <thead>
                        <tr>
                            <th>ID Báo cáo</th>
                            <th>Người báo cáo</th>
                            <th>Người đăng bài</th>
                            <th>Nội dung bài đăng (tóm tắt)</th>
                            <th>Thời gian</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #888;">Không có báo xấu nào.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><?php echo (int)$r['ReportId']; ?></td>
                            <td><?php echo htmlspecialchars($r['ReporterUsername'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($r['AuthorUsername'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="post-content" title="<?php echo htmlspecialchars($r['PostContent']); ?>" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($r['PostContent']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($r['ReportedAt']); ?></td>
                            <td>
                                <div class="action-row" style="display: flex; gap: 5px;">
                                    <?php if ($r['PostId']): // Chỉ hiển thị nếu bài đăng còn ?>
                                    <a class="btn" href="../Pages/PostPages/posts.php#post-<?php echo (int)$r['PostId']; ?>" target="_blank">Xem</a>
                                    
                                    <form method="post" id="delete-post-form-<?php echo (int)$r['ReportId']; ?>" style="display: inline;">
                                        <?php admin_csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete_post">
                                        <input type="hidden" name="post_id" value="<?php echo (int)$r['PostId']; ?>">
                                        <button class="btn btn-danger" type="button"
                                                onclick="adminShowConfirm(
                                                    'Bạn có chắc XÓA BÀI ĐĂNG này? (Báo cáo sẽ tự động bị xóa theo)', 
                                                    () => document.getElementById('delete-post-form-<?php echo (int)$r['ReportId']; ?>').submit()
                                                )">
                                            Xóa
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <form method="post" id="resolve-report-form-<?php echo (int)$r['ReportId']; ?>" style="display: inline;">
                                        <?php admin_csrf_field(); ?>
                                        <input type="hidden" name="action" value="resolve_report">
                                        <input type="hidden" name="report_id" value="<?php echo (int)$r['ReportId']; ?>">
                                        <button class="btn btn-primary" type="button"
                                                onclick="adminShowConfirm(
                                                    'Bạn có chắc DUYỆT (bỏ qua) báo cáo này?', 
                                                    () => document.getElementById('resolve-report-form-<?php echo (int)$r['ReportId']; ?>').submit()
                                                )">
                                            Duyệt
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php admin_render_pagination($pagination['current_page'], $pagination['total_pages'], $total_reports, 'báo cáo'); ?>
            </div>
        </section>
    </main>

<?php 
    // Thêm dòng này vào cuối file, ngay trước </body>
    admin_render_confirm_modal(); 
?>
</body>
</html>