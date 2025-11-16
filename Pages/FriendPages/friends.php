<?php
require_once '../../Handler/db.php';
require_once __DIR__ . '/../../Handler/FriendHandler/friend_helpers.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$userId = $_SESSION['user_id'];
// Lấy username hiện tại nếu đã đăng nhập
$current_username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Bạn bè & Lời mời</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="./../../css/style.css">
<style>
  body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      overflow: hidden;
  }

  /* Layout */
  .container { display: flex; flex-direction: column; height: calc(100vh - 60px); }
  .top-bar {
      display: flex; align-items: center; padding: 12px 20px;
      background: var(--color-secondary);
      border-bottom: 1px solid var(--color-border);
  }

  /* Search bar */
  .search-bar { flex: 1; display: flex; justify-content: center; position: relative; }
  .search-bar input {
      width: 60%; padding: 10px 15px; border-radius: 20px;
      border: 1px solid var(--color-border); background: var(--color-card);
      color: var(--color-text); font-size: 15px; outline: none;
      box-shadow: 0 1px 3px rgba(0,0,0,.05);
  }
  .search-popup {
      position: absolute; top: 45px; width: 60%;
      background: var(--color-card); border: 1px solid var(--color-border);
      border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.08);
      max-height: 250px; overflow-y: auto; display: none; z-index: 100;
  }
  .search-popup div {
      display: flex; align-items: center; padding: 8px 12px; cursor: pointer;
      transition: background .15s;
  }
  .search-popup div:hover { background: var(--color-secondary); }
  .search-popup img {
      border-radius: 50%; width: 32px; height: 32px; margin-right: 10px; object-fit: cover;
  }

  /* Content */
  .content { display: flex; flex: 1; overflow: hidden; }
  .left, .right, .middle { padding: 15px; overflow-y: auto; background: var(--color-card); }
  .left { flex: 1; border-right: 1px solid var(--color-border); }
  .middle { flex: 2; border-right: 1px solid var(--color-border);  }
  .right { flex: 1; }
  h2 {
      margin-bottom: 10px; font-size: 16px; color: var(--color-accent);
      border-bottom: 1px solid var(--color-border); padding-bottom: 5px;
  }
  .friend-item, .request-item {
      display: flex; align-items: center; padding: 10px 12px;
      border-radius: 10px; transition: background .15s, border .15s;
      border: 1px solid transparent;
  }
  .friend-item:hover, .request-item:hover {
      background: var(--color-secondary); border-color: var(--color-border);
  }
  .avatar-img {
      width: 45px; height: 45px; border-radius: 50%;
      object-fit: cover; border: 1px solid var(--color-border);
  }
  .friend-info { flex: 1; display: flex; flex-direction: column; margin-left: 10px; }
  .friend-info strong { font-weight: 600; color: var(--color-text); }
  .friend-info small { color: var(--color-text-muted); font-size: 12px; }
  .status-dot { width: 10px; height: 10px; border-radius: 50%; margin-left: 6px; border: 1px solid var(--color-border); }

  /* Buttons */
  button {
      padding: 6px 10px; border: none; border-radius: 8px; font-size: 13px;
      cursor: pointer; transition: background .2s, transform .1s;
  }
  button:hover { transform: translateY(-1px); }
  button.accept { background: var(--color-success); color: #fff; }
  button.reject { background: var(--color-error); color: #fff; }
</style>
</head>
<body>
  <header class="navbar">
      <div class="logo">
          <a href="../../index.php">
              <div class="logo-circle"></div>
              <span>ChatApp</span>
          </a>
      </div>
      <nav class="main-nav">
          <a href="../../index.php">HOME</a>
          <a href="../../Pages/PostPages/posts.php">POSTS</a>
          <a href="../../Pages/ChatPages/chat.php">CHAT</a>
          <a href="../../Pages/FriendPages/friends.php">FRIENDS</a>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
              <a href="../../Handler/admin_dashboard.php">ADMIN</a>
          <?php endif; ?>
      </nav>
      <div class="auth-buttons">
          <?php if (isset($_SESSION['user_id'])): ?>
              <span class="logged-in-user">Xin chào, <?php echo htmlspecialchars($current_username); ?></span>
              <div class="avatar-menu">
                  <?php $avatar = $_SESSION['avatar'] ?? 'uploads/default-avatar.jpg'; ?>
                  <img src="../../<?php echo htmlspecialchars($avatar); ?>" alt="avatar" class="avatar-thumb" id="avatarBtn" onerror="this.src='../../uploads/default-avatar.jpg'">
              <div class="avatar-dropdown" id="avatarDropdown">
                <a href="../ProfilePages/Profile.php?id=<?php echo $current_user_id; ?>">Trang cá nhân của tôi</a>
                <a href="../ProfilePages/edit_profile.php">Chỉnh sửa hồ sơ</a>
                <a href="../hidden_list.php">Quản lý Ẩn</a>
                <a href="../blocked_list.php">Quản lý Chặn</a>
                <a href="../../Handler/logout.php">Logout</a>
              </div>
              </div>
          <?php else: ?>
              <a href="Pages/login.php" class="btn-text">Login</a>
              <a href="Pages/register.php" class="btn-text">Register</a>
          <?php endif; ?>
      </div>
  </header>

  <div class="container">
      <div class="top-bar">
          <div class="search-bar">
              <input type="text" id="searchInput" placeholder="Tìm bạn bè...">
              <div id="search-results" class="search-popup"></div>
          </div>
      </div>
      <div class="content">
          <div class="left">
              <h2>Lời mời kết bạn</h2>
              <div id="friend-requests"></div>
          </div>
          <div class="middle">
              <h2>Gợi ý kết bạn</h2>
              <div id="friend-suggests"></div>
          </div>
          <div class="right">
              <h2>Bạn bè của bạn</h2>
              <div id="friends-list"></div>
          </div>
      </div>
  </div>

<script>
  // === CÁC BIẾN VÀ HÀM CỐT LÕI CỦA TRANG NÀY ===
  const api = '../../Handler/FriendHandler/friend-handler.php';
  const searchInput = document.getElementById('searchInput');
  const searchResults = document.getElementById('search-results');
  let cachedFriends = []; // Cache cho loadFriends

  // Hàm fetchPost cục bộ (chỉ dùng cho trang này)
  const fetchPost = async (data) =>
    (await fetch(api, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams(data)})).json();

  // Hàm renderList cục bộ
  const renderList = (selector, data, template, emptyMsg) => {
    const element = document.querySelector(selector);
    if (element) {
        element.innerHTML = data.length ? data.map(template).join('') : `<p>${emptyMsg}</p>`;
    }
  };

  // === TÌM KIẾM BẠN BÈ (HEADER) ===
  searchInput.addEventListener('input', async e => {
    const q = e.target.value.trim();
    if (!q) {
      searchResults.style.display = 'none';
      return;
    }
    
    try {
      const users = await (await fetch(`../../Handler/FriendHandler/search_user.php?q=${encodeURIComponent(q)}`)).json();
      
      renderList('#search-results', users, u => {
        const avatar = `../../${u.AvatarPath || 'uploads/default-avatar.jpg'}`;
        const displayName = (u.Username || 'Unknown').replace(/'/g, "\\");
        
        let buttonHtml = '';
        const btnStyle = 'style="font-size: 12px; padding: 4px 8px;"';

        // ↓↓↓ THÊM "data-user-btn-id" VÀO ĐÂY ↓↓↓
        if (u.friendship_status === 'sent_by_me') {
          buttonHtml = `<button data-user-btn-id="${u.UserId}" onclick="event.stopPropagation(); g_cancelRequest(${u.UserId}, this)" class="g-reject" ${btnStyle}>Hủy lời mời</button>`;
        } else if (u.friendship_status === 'none') {
          buttonHtml = `<button data-user-btn-id="${u.UserId}" onclick="event.stopPropagation(); g_sendFriend(${u.UserId}, this)" class="g-accept" ${btnStyle}>Kết bạn</button>`;
        }
        
        return `
        <div onclick="toggleGlobalProfile(${u.UserId})" style="cursor: pointer;"> 
          <img src="${avatar}" onerror="this.src='${G_DEFAULT_AVATAR || '../../uploads/default-avatar.jpg'}'">
          <span style="flex: 1; margin-left: 10px;">${displayName}</span>
          <div style="margin-left: 10px;">
              ${buttonHtml}
          </div>
        </div>`;
      }, '<p style="padding: 12px 6px; border-radius: 5px;">Không tìm thấy người dùng nào</p>');
      
      searchResults.style.display = 'block';
      
    } catch (e) {
      console.error('Lỗi tìm kiếm:', e);
      searchResults.style.display = 'none';
    }
  });

  // Ẩn kết quả tìm kiếm khi bấm ra ngoài
  document.addEventListener('click', e => {
    if (searchResults && !searchResults.contains(e.target) && e.target !== searchInput)
      searchResults.style.display = 'none';
  });

  // === TẢI LỜI MỜI KẾT BẠN (CỘT TRÁI) ===
async function loadRequests() {
  try {
    const data = await fetchPost({action:'fetch_requests'});
    
    // Sửa lại template string ở dòng dưới
    renderList('#friend-requests', data, r => `
    <div class="request-item" onclick="toggleGlobalProfile(${r.sender_id})" style="cursor: pointer;">
      <img src="../../${r.sender_avatar || 'uploads/default-avatar.jpg'}" class="avatar-img"">
      <b style="margin-left: 10px;">${r.sender_name}</b>
      
      <div style="margin-left: auto; display: flex; gap: 5px;">
        <button onclick="event.stopPropagation(); respond(${r.sender_id},'accept')" class="accept">Chấp nhận</button>
        <button onclick="event.stopPropagation(); respond(${r.sender_id},'reject')" class="reject">Từ chối</button>
      </div>

    </div>`, 'Không có lời mời nào.');
  } catch (e) {
    console.error('Lỗi tải lời mời:', e);
  }
}

  // Hàm xử lý khi bấm Chấp nhận/Từ chối
  async function respond(id, type) {
    try {
      // Dùng hàm g_fetchPost toàn cục
      const fetchFunc = typeof g_fetchPost === 'function' ? g_fetchPost : fetchPost;
      await fetchFunc({action:type, friend_id:id});
      
      // Tải lại cả 3 cột
      loadRequests(); 
      loadFriends();
      loadSuggestions();
    } catch (e) {
      alert('Có lỗi xảy ra: ' + e.message);
    }
  }

  // === TẢI GỢI Ý KẾT BẠN (CỘT GIỮA) ===
  async function loadSuggestions() {
    try {
      const data = await fetchPost({ action: 'fetch_suggestions' });
      
      renderList('#friend-suggests', data, s => {
        const avatar = `../../${s.AvatarPath || 'uploads/default-avatar.jpg'}`;
        const displayName = (s.Username || 'Unknown').replace(/'/g, "\\");

        let buttonHtml = '';
        
        // ↓↓↓ THÊM "data-user-btn-id" VÀO ĐÂY ↓↓↓
        if (s.friendship_status === 'sent_by_me') {
          buttonHtml = `<button data-user-btn-id="${s.UserId}" onclick="event.stopPropagation(); g_cancelRequest(${s.UserId}, this)" class="g-reject">Hủy lời mời</button>`;
        } else {
          buttonHtml = `<button data-user-btn-id="${s.UserId}" onclick="event.stopPropagation(); g_sendFriend(${s.UserId}, this)" class="g-accept">Kết bạn</button>`;
        }

        return `
        <div class="request-item" onclick="toggleGlobalProfile(${s.UserId})" style="cursor: pointer;">
          <img src="${avatar}" class="avatar-img" onerror="this.src='${G_DEFAULT_AVATAR || '../../uploads/default-avatar.jpg'}'">
          <b style="margin-left: 10px;">${displayName}</b>
          <div style="margin-left: auto; display: flex; gap: 5px;">
              ${buttonHtml}
          </div>
        </div>`;
      }, 'Không có gợi ý nào.');
    } catch (e) {
      console.error('Lỗi tải gợi ý bạn bè:', e);
    }
  }

  // === TẢI DANH SÁCH BẠN BÈ (CỘT PHẢI) ===
  function timeAgo(diffInSeconds) {
      // diffInSeconds là một con số (số giây) từ PHP/MySQL
      // (Giá trị có thể là null nếu LastSeen là null)
      if (diffInSeconds === null || typeof diffInSeconds === 'undefined' || diffInSeconds < 0) {
          return 'Offline';
      }

      // Nếu dưới 1 phút
      if (diffInSeconds < 60) {
          return 'Vài giây trước';
      }

      // Định nghĩa các khoảng thời gian bằng giây
      const intervals = [
          { label: 'năm', seconds: 31536000 },
          { label: 'tháng', seconds: 2592000 },
          { label: 'tuần', seconds: 604800 },
          { label: 'ngày', seconds: 86400 },
          { label: 'giờ', seconds: 3600 },
          { label: 'phút', seconds: 60 }
      ];

      // Tìm khoảng thời gian phù hợp
      for (const interval of intervals) {
          const count = Math.floor(diffInSeconds / interval.seconds);
          if (count >= 1) {
              return `${count} ${interval.label} trước`;
          }
      }

      return 'Vừa xong'; // Fallback
  }

  async function loadFriends() {
    try {
      const friends = await fetchPost({action:'fetch_friends'});
      
      if (JSON.stringify(friends) === JSON.stringify(cachedFriends)) return;
      cachedFriends = friends;
      
      renderList('#friends-list', friends, f => {
        const color = f.IsOnline ? '#43A047' : '#888';
        const status = f.IsOnline ? 'Online' : timeAgo(f.SecondsAgo);
        const avatar = (f.AvatarPath ? '../../' + f.AvatarPath : '../../uploads/default-avatar.jpg');
        const displayName = (f.Username || 'Unknown').replace(/'/g, "\\");

        return `
        <div class="friend-item" onclick="toggleGlobalProfile(${f.UserId})" style="cursor: pointer;">
          <img src="${avatar}?t=${Date.now()}" class="avatar-img" onerror="this.src='${G_DEFAULT_AVATAR || '../../uploads/default-avatar.jpg'}'">
          <div class="friend-info">
            <strong>${displayName}</strong>
            <small>${status}</small>
          </div>
          <span class="status-dot" style="background:${color};"></span>
        </div>`;
      }, 'Bạn chưa có bạn bè 😢');
    } catch (e) {
      console.error('Lỗi tải bạn bè:', e);
    }
  }


  // === SỬA LỖI Ở ĐÂY ===
  // Chờ cho toàn bộ trang (bao gồm cả script helper ở cuối) được tải xong
  document.addEventListener('DOMContentLoaded', function() {
      
      // === KHỞI CHẠY ===
      // Bây giờ các hàm này mới được gọi
      loadRequests();
      loadFriends();
      loadSuggestions();

      // Tự động làm mới danh sách bạn bè (cho trạng thái online)
      setInterval(loadFriends, 5000);

      // === CODE CHO AVATAR DROPDOWN (Giữ nguyên) ===
      const avatarBtn = document.getElementById('avatarBtn');
      const avatarDropdown = document.getElementById('avatarDropdown');

      if (avatarBtn && avatarDropdown) {
          avatarBtn.addEventListener('click', function(event) {
              event.stopPropagation(); 
              avatarDropdown.classList.toggle('open');
          });

          document.addEventListener('click', function(event) {
              if (avatarDropdown.classList.contains('open') && !avatarDropdown.contains(event.target)) {
                  avatarDropdown.classList.remove('open');
              }
          });
      }
  });
</script>
<?php 
  render_global_profile_modal(
      '/ChatApp/Handler/FriendHandler/friend-handler.php',
      '/ChatApp/uploads/default-avatar.jpg',
      '/ChatApp'
  ); 
?>
</body>
</html>
