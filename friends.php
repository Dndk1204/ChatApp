<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Bạn bè & Lời mời</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="./css/style.css">
<style>
/* === 🌿 PASTEL SOCIAL MEDIA THEME === */
:root {
    --color-primary: #A8DADC;
    --color-primary-dark: #74C0C9;
    --color-secondary: #F1FAEE;
    --color-bg: #EAF4F4;
    --color-card: #FFFFFF;
    --color-text: #2B2D42;
    --color-text-muted: #6C757D;
    --color-success: #81C784;
    --color-error: #E57373;
    --color-accent: #457B9D;
    --color-border: #D0E2E2;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--color-bg);
    color: var(--color-text);
    margin: 0;
    padding: 0;
}

/* === CONTAINER === */
.container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 60px);
}

/* === TOP BAR === */
.top-bar {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    background: var(--color-secondary);
    border-bottom: 1px solid var(--color-border);
}
.back-btn {
    background: none;
    border: none;
    color: var(--color-accent);
    font-size: 20px;
    margin-right: 10px;
    cursor: pointer;
    transition: color 0.2s ease;
    text-decoration: none;
}
.back-btn:hover { color: var(--color-primary-dark); }

.search-bar {
    flex: 1;
    display: flex;
    justify-content: center;
    position: relative;
}
.search-bar input {
    width: 60%;
    padding: 10px 15px;
    border-radius: 20px;
    border: 1px solid var(--color-border);
    background: var(--color-card);
    color: var(--color-text);
    font-size: 15px;
    outline: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.search-popup {
    position: absolute;
    top: 45px;
    width: 60%;
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    max-height: 250px;
    overflow-y: auto;
    display: none;
    z-index: 100;
}
.search-popup div {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    cursor: pointer;
    transition: background 0.15s ease;
}
.search-popup div:hover { background: var(--color-secondary); }
.search-popup img {
    border-radius: 50%;
    width: 32px;
    height: 32px;
    margin-right: 10px;
    object-fit: cover;
}

/* === CONTENT === */
.content {
    display: flex;
    flex: 1;
    overflow: hidden;
}
.left, .right {
    padding: 15px;
    overflow-y: auto;
}
.left {
    flex: 2;
    background: var(--color-card);
    border-right: 1px solid var(--color-border);
}
.right {
    flex: 1;
    background: var(--color-card);
}
h2 {
    margin-bottom: 10px;
    font-size: 16px;
    color: var(--color-accent);
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 5px;
}
.friend-item, .request-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: 10px;
    transition: background 0.15s ease;
    border: 1px solid transparent;
}
.friend-item:hover, .request-item:hover { 
    background: var(--color-secondary); 
    border-color: var(--color-border);
}
.avatar-img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid var(--color-border);
}
.friend-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    margin-left: 10px;
}
.friend-info strong { 
    color: var(--color-text); 
    font-weight: 600; 
}
.friend-info small { 
    color: var(--color-text-muted); 
    font-size: 12px; 
}
.status-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    margin-left: 6px;
    border: 1px solid var(--color-border);
}
button {
    padding: 6px 10px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.1s ease;
}
button:hover { transform: translateY(-1px); }
button.accept { background: var(--color-success); color: #fff; }
button.reject { background: var(--color-error); color: #fff; }

/* === OVERLAY === */
.friend-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}
.friend-overlay .box {
    background: var(--color-card);
    padding: 20px 25px;
    border-radius: 16px;
    width: 420px;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
    border: 1px solid var(--color-border);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.friend-overlay .box img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid var(--color-border);
}
.friend-overlay .box h3 {
    flex: 1;
    font-size: 16px;
    color: var(--color-text);
    margin: 0;
}
.close-btn {
    position: absolute;
    top: 5px;
    right: 10px;
    background: none;
    border: none;
    color: var(--color-text-muted);
    font-size: 18px;
    cursor: pointer;
    transition: color 0.2s ease;
}
.close-btn:hover { color: var(--color-accent); }
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="top-bar">
        <a class="back-btn" href="index.php">←</a>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Tìm bạn bè...">
            <div id="search-results" class="search-popup"></div>
        </div>
    </div>
    <div class="content">
        <div class="left">
            <h2>Lời mời kết bạn</h2>
            <div id="requests"></div>
        </div>
        <div class="right">
            <h2>Bạn bè của bạn</h2>
            <div id="friends-list"></div>
        </div>
    </div>
</div>

<!-- Overlay -->
<div id="friendOverlay" class="friend-overlay">
    <div class="box">
        <button class="close-btn" onclick="closeOverlay()">✕</button>
        <img id="overlayAvatar" src="images/default-avatar.jpg" alt="avatar">
        <h3 id="overlayName"></h3>
        <button id="msgBtn" class="accept">Nhắn tin</button>
        <button id="unfriendBtn" class="reject">Hủy kết bạn</button>
    </div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('search-results');
let selectedFriendId = null, cachedFriends = [];

searchInput.addEventListener('input', function(){
    const q = this.value.trim();
    if(!q){ searchResults.style.display='none'; return; }
    fetch(`search_user.php?q=${encodeURIComponent(q)}`)
    .then(r=>r.json())
    .then(data=>{
        searchResults.innerHTML = data.length===0
        ? '<div>Không tìm thấy người dùng nào</div>'
        : data.map(u=>`
            <div onclick="sendFriend(${u.UserId})">
                <img src="${u.AvatarPath || 'images/default-avatar.jpg'}" onerror="this.src='images/default-avatar.jpg'">
                ${u.Username}
            </div>`).join('');
        searchResults.style.display='block';
    });
});
document.addEventListener('click',e=>{
    if(!searchResults.contains(e.target)&&e.target!==searchInput)
        searchResults.style.display='none';
});

function sendFriend(friend_id){
    fetch('Handler/php-friend-handler.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=send&friend_id=${friend_id}`
    }).then(r=>r.json())
    .then(d=>alert(d.status==='sent'?'Đã gửi lời mời kết bạn!':'Đã có yêu cầu hoặc đã là bạn!'));
}

function loadRequests(){
    fetch('Handler/php-friend-handler.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=fetch_requests'
    }).then(r=>r.json()).then(data=>{
        document.getElementById('requests').innerHTML = data.length===0
        ? '<p>Không có lời mời nào.</p>'
        : data.map(r=>`
            <div class="request-item">
                <img src="${r.sender_avatar || 'images/default-avatar.jpg'}" class="avatar-img" onerror="this.src='images/default-avatar.jpg'">
                <b>${r.sender_name}</b>
                <button onclick="respond(${r.sender_id},'accept')" class="accept">Chấp nhận</button>
                <button onclick="respond(${r.sender_id},'reject')" class="reject">Từ chối</button>
            </div>`).join('');
    });
}
function respond(id, type){
    fetch('Handler/php-friend-handler.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=${type}&friend_id=${id}`
    }).then(()=>{loadRequests();loadFriends();});
}

function openOverlay(id, name, avatar){
    selectedFriendId=id;
    document.getElementById('overlayName').innerText=name;
    document.getElementById('overlayAvatar').src=avatar || 'images/default-avatar.jpg';
    document.getElementById('friendOverlay').style.display='flex';
}
function closeOverlay(){
    document.getElementById('friendOverlay').style.display='none';
    selectedFriendId=null;
}

function timeAgo(date){
    const d=new Date(date),n=new Date();
    const diff=Math.floor((n-d)/1000);
    if(diff<60)return `${diff}s ago`;
    if(diff<3600)return `${Math.floor(diff/60)}m ago`;
    if(diff<86400)return `${Math.floor(diff/3600)}h ago`;
    return `${Math.floor(diff/86400)}d ago`;
}

function loadFriends(){
    fetch('Handler/php-friend-handler.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=fetch_friends'
    }).then(r=>r.json()).then(friends=>{
        if(JSON.stringify(friends)===JSON.stringify(cachedFriends)) return;
        cachedFriends=friends;
        document.getElementById('friends-list').innerHTML = friends.length===0
        ? '<p>Bạn chưa có bạn bè 😢</p>'
        : friends.map(f=>{
            const avatar=f.AvatarPath||'images/default-avatar.jpg';
            const color=f.IsOnline==1?'#43A047':'#888';
            const status=f.IsOnline==1?'Online':(f.LastSeen?timeAgo(f.LastSeen):'Offline');
            return `
                <div class="friend-item" onclick="openOverlay(${f.UserId}, '${f.FullName||f.Username}', '${f.AvatarPath || 'images/default-avatar.jpg'}')">
                    <img src="${avatar}" class="avatar-img" onerror="this.src='images/default-avatar.jpg'">
                    <div class="friend-info">
                        <strong>${f.FullName||f.Username}</strong>
                        <small>${status}</small>
                    </div>
                    <span class="status-dot" style="background:${color};"></span>
                </div>`;
        }).join('');
    });
}

document.getElementById('msgBtn').onclick=()=>{ if(selectedFriendId)location=`chat.php?friend_id=${selectedFriendId}`; };
document.getElementById('unfriendBtn').onclick=()=>{ 
    if(selectedFriendId && confirm("Bạn có chắc chắn muốn hủy kết bạn?")){
        fetch('Handler/php-friend-handler.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`action=unfriend&friend_id=${selectedFriendId}`
        }).then(r=>r.json()).then(d=>{
            alert(d.status==='success'?"Đã hủy kết bạn":"Lỗi!");
            closeOverlay();
            loadFriends();
        });
    }
};

loadRequests();
loadFriends();
setInterval(loadFriends,5000);

        // Chờ cho toàn bộ trang được tải xong
        document.addEventListener('DOMContentLoaded', function() {
            
            const avatarBtn = document.getElementById('avatarBtn');
            const avatarDropdown = document.getElementById('avatarDropdown');

            // Kiểm tra xem các phần tử này có tồn tại không
            // (vì khách truy cập sẽ không thấy chúng)
            if (avatarBtn && avatarDropdown) {
                
                // 1. Khi nhấp vào avatar
                avatarBtn.addEventListener('click', function(event) {
                    // Ngăn sự kiện click lan ra ngoài
                    event.stopPropagation(); 
                    
                    // Hiển thị hoặc ẩn dropdown
                    avatarDropdown.classList.toggle('open');
                });

                // 2. Khi nhấp ra ngoài (bất cứ đâu trên trang)
                document.addEventListener('click', function(event) {
                    // Nếu dropdown đang mở và cú click không nằm trong dropdown
                    if (avatarDropdown.classList.contains('open') && !avatarDropdown.contains(event.target)) {
                        avatarDropdown.classList.remove('open');
                    }
                });
            }
        });
</script>
</body>
</html>
