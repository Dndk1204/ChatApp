<?php
// Tải các file cần thiết
require_once __DIR__ . '/../Admin/_helpers.php';
require_once __DIR__ . '/../Handler/db.php';
// Tải file config/db nếu cần, ở đây giả sử admin/_helpers.php đã xử lý việc kết nối CSDL hoặc nó không cần thiết cho trang tĩnh này.

// Kiểm tra quyền admin nếu cần (tuỳ thuộc vào file _auth.php của bạn)
require_once __DIR__ . '/../Admin/_auth.php'; 

// Dữ liệu mẫu cho 5 thành viên
$members = [
    [
        'name' => 'Đặng Nguyễn Đăng Khoa',
        'title' => 'Trưởng Dự Án (Project Lead)',
        'description' => 'Lãnh đạo và định hướng phát triển tổng thể cho ChatApp, đảm bảo dự án đi đúng tiến độ và đạt chất lượng cao.',
        'email' => 'khoa.dnd.64cntt@ntu.edu.vn',
        'social' => 'github.com/Dndk1204',
        'avatar' => '../uploads/members/k.jpg', // Giả sử có ảnh
        'color' => '#f39c12' // Màu thẻ
    ],
    [
        'name' => 'Huỳnh Ngọc Long',
        'title' => 'Nhà Phát Triển Backend (Backend Developer)',
        'description' => 'Chịu trách nhiệm xây dựng và duy trì các API, xử lý logic máy chủ, và quản lý cơ sở dữ liệu (MySQL).',
        'email' => 'long.hn.64cntt@ntu.edu.vn',
        'social' => 'github.com/huynhngoclong',
        'avatar' => '../uploads/members/l2.jpg',
        'color' => '#e74c3c'
    ],
    [
        'name' => 'Nguyễn Đỗ Thiên Luân',
        'title' => 'Nhà Phát Triển Frontend (Frontend Developer)',
        'description' => 'Thiết kế và phát triển giao diện người dùng (UI) trực quan và tối ưu trải nghiệm người dùng (UX) với HTML, CSS, JS.',
        'email' => 'luan.ndt.64cntt@ntu.edu.vn',
        'social' => 'github.com/SilvaHana',
        'avatar' => '../uploads/members/l1.jpg',
        'color' => '#3498db'
    ],
    [
        'name' => 'Lê Việt Hoàng',
        'title' => 'Chuyên Viên Kiểm Thử (QA Specialist)',
        'description' => 'Đảm bảo chất lượng sản phẩm bằng cách lập kế hoạch và thực hiện các quy trình kiểm thử toàn diện, tìm và báo cáo lỗi.',
        'email' => 'hoang.lv.64cntt@ntu.edu.vn',
        'social' => 'github.com/LeVietHoang',
        'avatar' => '../uploads/members/h1.jpg',
        'color' => '#2ecc71'
    ],
    [
        'name' => 'Lê Nhựt Hào',
        'title' => 'Nhà Thiết Kế Đồ Hoạ (Graphic Designer)',
        'description' => 'Thiết kế logo, tài liệu marketing, và các yếu tố hình ảnh khác để đảm bảo ChatApp có nhận diện thương hiệu mạnh mẽ.',
        'email' => 'hao.ln.64cntt@ntu.edu.vn',
        'social' => 'github.com/HaoNetDev',
        'avatar' => '../uploads/members/h2.jpg',
        'color' => '#9b59b6'
    ],
];

// Render HTML head (sử dụng hàm admin_render_head)
admin_render_head('Về Chúng Tôi - ChatApp Admin');
?>

<div class="admin-container full-height-content">
    <?php 
    admin_render_header(''); 
    ?>
    
    <main class="content-wrapper">
        <h2 class="page-title">🌟 Về Dự Án ChatApp và Đội Ngũ Phát Triển</h2>
        <p class="intro-text">Chúng tôi là đội ngũ đam mê công nghệ, cùng nhau xây dựng ChatApp với mong muốn mang lại một trải nghiệm trò chuyện nhanh chóng, bảo mật và thân thiện. Dưới đây là những người đã tạo nên dự án này:</p>

        <section class="team-cards-grid">
            <?php foreach ($members as $member): ?>
                <div class="member-card">
                    <!-- Thẻ bao ngoài cho hiệu ứng lật -->
                    <div class="card-inner">
                        
                        <!-- MẶT TRƯỚC (Chỉ hiện tên và chức danh) -->
                        <div class="card-front" style="border-left-color: <?= htmlspecialchars($member['color']) ?>;">
                            <div class="member-avatar-wrap">
                                <img src="<?= htmlspecialchars($member['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($member['name']) ?>" 
                                     class="member-avatar"
                                     onerror="this.onerror=null;this.src='../uploads/default-avatar.jpg';">
                            </div>
                            <div class="member-info">
                                <h3 class="member-name"><?= htmlspecialchars($member['name']) ?></h3>
                                <p class="member-title" style="color: <?= htmlspecialchars($member['color']) ?>;">
                                    <?= htmlspecialchars($member['title']) ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- MẶT SAU (Hiện full thông tin) -->
                        <div class="card-back" style="background-color: <?= htmlspecialchars($member['color']) ?>;">
                            <div class="member-info-back">
                                <h4 class="member-name-back"><?= htmlspecialchars($member['name']) ?></h4>
                                <p class="member-description-back"><?= htmlspecialchars($member['description']) ?></p>
                                <div class="member-contact-back">
                                    <p>📧 Email: <a href="mailto:<?= htmlspecialchars($member['email']) ?>"><?= htmlspecialchars($member['email']) ?></a></p>
                                    <p>🔗 Social: <a href="http://<?= htmlspecialchars($member['social']) ?>" target="_blank"><?= htmlspecialchars($member['social']) ?></a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        
        <!-- Thêm một khoảng đệm lớn ở cuối để kiểm tra thanh cuộn -->
        <div style="height: 50px;"></div>

    </main>
</div>
