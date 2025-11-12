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
<style>
/* === MINIMALIST DARK STYLE === */
body {
    font-family: 'Inter', sans-serif;
    margin:0; padding:0;
    background:#1E1E1E;
    color:#ECECEC;
}
.container {
    display:flex;
    flex-direction:column;
    height:100vh;
}
.search-bar {
    padding:10px 20px;
    background:#2A2A2A;
    display:flex;
    justify-content:center;
    position:relative;
}
.search-bar input {
    width:60%;
    padding:10px 15px;
    border-radius:20px;
    border:none;
    background:#3A3A3A;
    color:#ECECEC;
    outline:none;
    font-size:15px;
}
.search-bar input::placeholder { color:#A0A0A0; }
.search-popup {
    position:absolute;
    top:50px;
    width:60%;
    background:#2A2A2A;
    border-radius:12px;
    overflow-y:auto;
    max-height:250px;
    display:none;
}
.search-popup div {
    display:flex;
    align-items:center;
    padding:8px 12px;
    cursor:pointer;
    transition: background 0.15s;
}
.search-popup div:hover { background:#3A3A3A; }
.search-popup img {
    border-radius:50%;
    width:32px;
    height:32px;
    margin-right:10px;
}
.content {
    display:flex;
    flex:1;
    gap:10px;
    padding:10px 20px;
    overflow:hidden;
}
.left, .right {
    background:#2A2A2A;
    border-radius:12px;
    padding:15px;
    overflow-y:auto;
}
.left { flex:2; }
.right { flex:1; }
h2 {
    margin-bottom:12px;
    font-size:16px;
    font-weight:500;
    border-bottom:1px solid #3A3A3A;
    padding-bottom:6px;
}
.friend-item, .request-item {
    display:flex;
    align-items:center;
    margin-bottom:10px;
    padding:6px 10px;
    border-radius:10px;
    transition: background 0.15s;
}
.friend-item:hover, .request-item:hover { background:#3A3A3A; }
.friend-item img, .request-item img {
    width:40px; height:40px; border-radius:50%; margin-right:10px;
}
.friend-item strong, .request-item b { flex:1; font-weight:500; }
.status-dot {
    width:10px; height:10px; border-radius:50%;
    display:inline-block; margin-left:5px;
    border:1px solid #2A2A2A;
}
small { display:block; font-size:12px; color:#A0A0A0; margin-left:50px; }
button {
    padding:5px 10px; border:none; border-radius:8px;
    font-size:13px; cursor:pointer;
    transition: opacity 0.15s;
}
button:hover { opacity:0.85; }
button.accept { background:#4CAF50; color:#fff; }
button.reject { background:#F44336; color:#fff; }
button.message { background:#2196F3; color:#fff; }
button.unfriend { background:#E53935; color:#fff; }

/* Overlay minimal */
.friend-overlay {
    position:fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.7);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:1000;
}
.friend-overlay .box {
    background:#2A2A2A;
    padding:20px;
    border-radius:12px;
    text-align:center;
    width:260px;
}
.friend-overlay .box h3 { margin-bottom:15px; font-size:16px; }
.friend-overlay .box button { margin:5px; }
</style>
</head>
<body>

<div class="container">

    <!-- Thanh tìm kiếm -->
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Tìm bạn bè...">
        <div id="search-results" class="search-popup"></div>
    </div>

    <!-- Nội dung chính -->
    <div class="content">

        <!-- Cột trái: Lời mời kết bạn -->
        <div class="left">
            <h2>Lời mời kết bạn</h2>
            <div id="requests"></div>
        </div>

        <!-- Cột phải: Danh sách bạn bè -->
        <div class="right">
            <h2>Bạn bè của bạn</h2>
            <div id="friends-list"></div>
        </div>

    </div>
</div>

<!-- Overlay -->
<div id="friendOverlay" class="friend-overlay">
    <div class="box">
        <h3 id="overlayName"></h3>
        <button id="msgBtn" class="message">Nhắn tin</button>
        <button id="unfriendBtn" class="unfriend">Hủy kết bạn</button>
        <br><br>
        <button onclick="closeOverlay()">Đóng</button>
    </div>
</div>

<script>
// ====================== Tìm bạn bè realtime ======================
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('search-results');

searchInput.addEventListener('input', function(){
    const q = this.value.trim();
    if(q === '') { searchResults.style.display='none'; searchResults.innerHTML=''; return; }

    fetch(`search_user.php?q=${encodeURIComponent(q)}`)
    .then(res => res.json())
    .then(data => {
        if(data.length === 0) {
            searchResults.innerHTML = '<div>Không tìm thấy người dùng nào</div>';
        } else {
            searchResults.innerHTML = data.map(u => `
                <div onclick="sendFriend(${u.UserId})">
                    <img src="${u.AvatarPath}">${u.Username}
                </div>
            `).join('');
        }
        searchResults.style.display = 'block';
    });
});

document.addEventListener('click', (e)=>{
    if(!searchResults.contains(e.target) && e.target!==searchInput) {
        searchResults.style.display='none';
    }
});

function sendFriend(friend_id) {
    fetch('Handler/php-friend-handler.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=send&friend_id=${friend_id}`
    }).then(res=>res.json())
    .then(data=>{
        alert(data.status==='sent'?"Đã gửi lời mời kết bạn!":"Đã có yêu cầu hoặc đã là bạn!");
    });
}

// ====================== Load lời mời kết bạn ======================
function loadRequests() {
    fetch('Handler/php-friend-handler.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=fetch_requests'
    })
    .then(res => res.json())
    .then(data => {
        let html = '';
        if(data.length === 0) html = '<p>Không có lời mời nào.</p>';
        else {
            data.forEach(r => {
                html += `
                    <div class="request-item">
                        <img src="${r.sender_avatar}" width="40" height="40">
                        <b>${r.sender_name}</b>
                        <button onclick="respond(${r.sender_id}, 'accept')" class="accept">Chấp nhận</button>
                        <button onclick="respond(${r.sender_id}, 'reject')" class="reject">Từ chối</button>
                    </div>
                `;
            });
        }
        document.getElementById('requests').innerHTML = html;
    });
}

// ====================== Chấp nhận / từ chối ======================
function respond(friend_id, type) {
    fetch('Handler/php-friend-handler.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=${type}&friend_id=${friend_id}`
    }).then(() => {
        loadRequests(); // reload lời mời
        loadFriends();  // reload danh sách bạn bè realtime
    });
}

// ====================== Load danh sách bạn bè ======================
let selectedFriendId = null;

function openOverlay(friendId, friendName){
    selectedFriendId = friendId;
    document.getElementById('overlayName').innerText = friendName;
    document.getElementById('friendOverlay').style.display='flex';
}

function closeOverlay(){
    document.getElementById('friendOverlay').style.display='none';
    selectedFriendId = null;
}

function loadFriends() {
    fetch('Handler/php-friend-handler.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=fetch_friends'
    })
    .then(res=>res.json())
    .then(data=>{
        let html='';
        if(data.length===0){
            html='<p>Bạn chưa có bạn bè 😢</p>';
        } else {
            data.forEach(f=>{
                let statusColor=f.IsOnline==1?'green':'gray';
                let lastSeen='';
                if(f.IsOnline==0 && f.LastSeen){
                    let last=new Date(f.LastSeen);
                    let now=new Date();
                    let diff=Math.floor((now-last)/1000);
                    let h=Math.floor(diff/3600), m=Math.floor((diff%3600)/60), s=diff%60;
                    lastSeen=` (Offline ${h}h ${m}m ${s}s)`;
                }
                html+=`
                    <div class="friend-item" onclick="openOverlay(${f.UserId}, '${f.FullName || f.Username}')">
                        <img src="${f.AvatarPath}" width="50" height="50">
                        <strong>${f.FullName || f.Username}</strong>
                        <span class="status-dot" style="background:${statusColor};"></span>
                        <small>${lastSeen}</small>
                    </div>
                `;
            });
        }
        document.getElementById('friends-list').innerHTML = html;
    });
}

document.getElementById('msgBtn').addEventListener('click', ()=>{
    if(selectedFriendId){
        window.location.href = `chat.php?friend_id=${selectedFriendId}`;
    }
});

document.getElementById('unfriendBtn').addEventListener('click', ()=>{
    if(selectedFriendId && confirm("Bạn có chắc chắn muốn hủy kết bạn?")){
        fetch('Handler/php-friend-handler.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body:`action=unfriend&friend_id=${selectedFriendId}`
        }).then(res=>res.json())
        .then(data=>{
            alert(data.status==='success' ? "Đã hủy kết bạn" : "Lỗi!");
            closeOverlay();
            loadFriends();
        });
    }
});

loadRequests();
loadFriends();
setInterval(loadFriends,5000);
</script>

</body>
</html>
