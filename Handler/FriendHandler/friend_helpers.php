<?php
/**
 * File này chứa hàm để render thẻ thông tin người dùng (profile modal)
 * Nó sẽ được include ở mọi trang.
 */

/**
 * In ra HTML, CSS, và JS cho thẻ xem thông tin người dùng toàn cục.
 * $api_handler_path: Đường dẫn tuyệt đối đến file friend-handler.php.
 * $base_path: Đường dẫn gốc của web (ví dụ: /ChatApp) để xử lý avatar.
 */
function render_global_profile_modal(
    $api_handler_path = '/ChatApp/Handler/FriendHandler/friend-handler.php', 
    $default_avatar_path = '/ChatApp/uploads/default-avatar.jpg',
    $base_path = '/ChatApp'
) {

    // Đường dẫn đã được chuẩn hóa
    $safe_api_path = htmlspecialchars($api_handler_path);
    $safe_avatar_path = htmlspecialchars($default_avatar_path);
    $safe_base_path = rtrim(htmlspecialchars($base_path), '/');

    // 1. CSS CHO OVERLAY (Sử dụng tiền tố "g-" (global) để tránh xung đột)
    echo '
<style>
/* ... (TOÀN BỘ CSS CŨ CỦA BẠN TỪ DÒNG NÀY...) ... */
#g-profile-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.4);
    display: none; /* Ẩn ban đầu */
    justify-content: center; align-items: center;
    z-index: 1000;
}
#g-profile-overlay.loading .g-profile-loader { display: block; }
#g-profile-overlay.loading .g-profile-box { display: none; }
.g-profile-loader {
    display: none;
    color: #fff;
    font-size: 1.2rem;
    font-family: Arial, sans-serif;
}
.g-profile-box {
    font-family: Arial, sans-serif; /* Đặt font cơ bản */
    background: #FFFFFF;
    padding: 20px 25px; border-radius: 16px;
    width: 380px; display: flex;
    flex-direction: column; gap: 10px;
    border: 1px solid #D0E2E2;
    box-shadow: 0 2px 10px rgba(0,0,0,.1);
    position: relative;
    align-items: stretch;
}
.g-profile-box .close-btn {
    position: absolute; top: 5px; right: 10px; background: none; border: none;
    color: #6C757D; font-size: 18px; cursor: pointer;
}
.g-profile-header {
    text-align: center;
    border-bottom: 1px solid #D0E2E2;
    padding-bottom: 15px;
}
.g-profile-header img {
    width: 80px; height: 80px; border-radius: 50%;
    object-fit: cover; border: 2px solid #D0E2E2;
    margin-bottom: 5px;
}
.g-profile-header h3 { margin: 0; font-size: 18px; color: #2B2D42; }
.g-profile-header small { font-size: 14px; color: #6C757D; }

.g-profile-details h4 {
    margin: 0 0 10px 0; font-size: 14px; color: #457B9D;
    border-bottom: 1px solid #D0E2E2; padding-bottom: 5px;
}
.g-profile-details ul { list-style: none; padding: 0; margin: 0; font-size: 14px; }
.g-profile-details li { display: flex; justify-content: space-between; padding: 6px 0; }
.g-profile-details li strong { color: #6C757D; font-weight: 500; }
.g-profile-details li span { color: #2B2D42; font-weight: 600; text-align: right; }

.g-profile-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.g-profile-actions button {
    flex: 1; padding: 10px; font-size: 14px; border: none; border-radius: 8px;
    cursor: pointer; transition: background .2s;
    font-weight: bold;
}
.g-profile-actions button:disabled { background: #ccc; cursor: not-allowed; }
button.g-accept { background: #81C784; color: #fff; }
button.g-reject { background: #E57373; color: #fff; }
button.g-normal { background: #6F9DE1; color: #fff; }

/* ... (ĐẾN DÒNG NÀY) ... */

/* ↓↓↓ THÊM CSS MỚI CHO POPUP XÁC NHẬN ↓↓↓ */
#g-confirm-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5); /* Lớp nền tối hơn */
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1001; /* Nằm TRÊN cả thẻ thông tin */
    font-family: Arial, sans-serif;
}
.g-confirm-box {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,.2);
    width: 340px;
    text-align: center;
}
.g-confirm-box p {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #2B2D42;
    line-height: 1.5;
}
.g-confirm-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}
.g-confirm-actions button {
    padding: 10px 20px;
    font-size: 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}
/* ↑↑↑ KẾT THÚC CSS MỚI ↑↑↑ */
</style>';

    // 2. HTML CHO OVERLAY (Thẻ thông tin VÀ thẻ xác nhận)
    echo '
<div id="g-profile-overlay" onclick="toggleGlobalProfile(0, false)">
    <div class="g-profile-loader">Đang tải...</div>
    <div class="g-profile-box" onclick="event.stopPropagation()">
        <button class="close-btn" type="button" onclick="toggleGlobalProfile(0, false)">✕</button>
        
        <div class="g-profile-header">
            <img id="g-overlayAvatar" alt="avatar" src="' . $safe_avatar_path . '">
            <h3 id="g-overlayName">---</h3>
            <small id="g-overlayUsername">@---</small>
        </div>

        <div class="g-profile-details">
            <h4>Thông tin chi tiết</h4>
            <ul>
                <li><strong>Họ tên:</strong> <span id="g-infoFullName">---</span></li>
                
                <li><strong>Email:</strong> <span id="g-infoEmail">---</span></li>
                <li><strong>SĐT:</strong> <span id="g-infoPhone">---</span></li>
                <li><strong>Địa chỉ:</strong> <span id="g-infoAddress">---</span></li>
                <li><strong>Giới tính:</strong> <span id="g-infoGender">---</span></li>
                <li><strong>Ngày sinh:</strong> <span id="g-infoDob">---</span></li>
                <li><strong>Tham gia ngày:</strong> <span id="g-infoJoined">---</span></li>
            </ul>
        </div>
        
        <div class="g-profile-actions" id="g-profile-actions"></div>
    </div>
</div>

<div id="g-confirm-overlay" onclick="hideGlobalConfirm()">
    <div class="g-confirm-box" onclick="event.stopPropagation()">
        <p id="g-confirm-message">Bạn có chắc chắn?</p>
        <div class="g-confirm-actions">
            <button id="g-confirm-btn-cancel" class="g-reject">Hủy</button>
            <button id="g-confirm-btn-ok" class="g-normal">Xác nhận</button>
        </div>
    </div>
</div>
';

    // 3. JAVASCRIPT CHO OVERLAY
    echo '
<script>
// Biến toàn cục trỏ đến các thành phần
const G_API_HANDLER = "' . $safe_api_path . '";
const G_DEFAULT_AVATAR = "' . $safe_avatar_path . '";
const G_BASE_PATH = "' . $safe_base_path . '";
const G_PROFILE_OVERLAY = document.getElementById("g-profile-overlay");

// ↓↓↓ THÊM CÁC BIẾN MỚI CHO POPUP XÁC NHẬN ↓↓↓
const G_CONFIRM_OVERLAY = document.getElementById("g-confirm-overlay");
const G_CONFIRM_MESSAGE = document.getElementById("g-confirm-message");
const G_CONFIRM_BTN_OK = document.getElementById("g-confirm-btn-ok");
const G_CONFIRM_BTN_CANCEL = document.getElementById("g-confirm-btn-cancel");
let g_confirmCallback = null; // Biến để lưu hành động (function) sẽ chạy khi bấm OK

// === Các hàm Helper (định dạng) ===
const g_formatInfo = (val) => (val && String(val).trim() !== "" && val !== "null" ? String(val) : "---");
const g_formatDate = (dateString) => {
    if (!dateString) return "---";
    try {
        const date = new Date(dateString);
        if (isNaN(date.getFullYear()) || date.getFullYear() < 1900) return "---";
        const day = String(date.getDate()).padStart(2, "0");
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    } catch (e) { return "---"; }
};

// === Hàm Fetch POST Toàn Cục ===
async function g_fetchPost(data) {
  try {
    const res = await fetch(G_API_HANDLER, {
      method: "POST",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: new URLSearchParams(data)
    });
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
    return await res.json();
  } catch (e) {
    console.error("Lỗi g_fetchPost:", e);
    return { status: "error", message: e.message };
  }
}

// === CÁC HÀM POPUP XÁC NHẬN MỚI ===
function showGlobalConfirm(message, onConfirm) {
    if (!G_CONFIRM_OVERLAY) return;
    
    G_CONFIRM_MESSAGE.textContent = message;
    g_confirmCallback = onConfirm; // Lưu hàm (hành động) lại
    
    G_CONFIRM_OVERLAY.style.display = "flex";
}

function hideGlobalConfirm() {
    if (!G_CONFIRM_OVERLAY) return;
    G_CONFIRM_OVERLAY.style.display = "none";
    g_confirmCallback = null; // Xóa hàm đã lưu
}

// Gắn sự kiện cho các nút của popup xác nhận
if (G_CONFIRM_BTN_CANCEL) {
    G_CONFIRM_BTN_CANCEL.onclick = hideGlobalConfirm;
}
if (G_CONFIRM_BTN_OK) {
    G_CONFIRM_BTN_OK.onclick = () => {
        if (typeof g_confirmCallback === "function") {
            g_confirmCallback(); // Chạy hành động đã lưu
        }
        hideGlobalConfirm(); // Ẩn popup
    };
}
// === KẾT THÚC CÁC HÀM POPUP MỚI ===


// === Hàm Chính: Bật/Tắt Thẻ Thông Tin ===
let g_currentProfileId = null;
let g_isLoading = false;

async function toggleGlobalProfile(userId, show = true) {
    if (!G_PROFILE_OVERLAY || g_isLoading) return;

    if (!show || userId === 0 || userId === g_currentProfileId) {
        G_PROFILE_OVERLAY.style.display = "none";
        g_currentProfileId = null;
        return;
    }
    
    g_currentProfileId = userId;
    g_isLoading = true;
    G_PROFILE_OVERLAY.style.display = "flex";
    G_PROFILE_OVERLAY.classList.add("loading");

    const res = await g_fetchPost({ action: "fetch_user_profile", profile_user_id: userId });
    g_isLoading = false;

    if (res.status === "success" && res.data) {
        const data = res.data;
        // 1. Điền thông tin Header
        const mainName = g_formatInfo(data.FullName) !== "---" ? data.FullName : data.Username;
        document.getElementById("g-overlayName").textContent = mainName;
        document.getElementById("g-overlayUsername").textContent = `@${data.Username}`;
        
        let avatar = G_DEFAULT_AVATAR;
        if (data.AvatarPath) {
             avatar = G_BASE_PATH + "/" + data.AvatarPath.replace(/^\/+/, "");
        }
        document.getElementById("g-overlayAvatar").src = avatar + "?t=" + Date.now();

        // 2. Điền thông tin chi tiết (ĐÃ CẬP NHẬT)
        document.getElementById("g-infoFullName").textContent = g_formatInfo(data.FullName);
        document.getElementById("g-infoEmail").textContent = g_formatInfo(data.Email); // <-- DÒNG MỚI
        document.getElementById("g-infoPhone").textContent = g_formatInfo(data.PhoneNumber); // <-- DÒNG MỚI
        document.getElementById("g-infoAddress").textContent = g_formatInfo(data.Address); // <-- DÒNG MỚI
        document.getElementById("g-infoGender").textContent = g_formatInfo(data.Gender);
        document.getElementById("g-infoDob").textContent = g_formatDate(data.DateOfBirth);
        document.getElementById("g-infoJoined").textContent = g_formatDate(data.CreatedAt);

        // 3. Điền các nút hành động
        g_renderProfileButtons(data.friendship_status, data.UserId);

        G_PROFILE_OVERLAY.classList.remove("loading");
    } else {
        alert("Lỗi: " + (res.message || "Không thể tải thông tin."));
        toggleGlobalProfile(0, false);
    }
}

// === Hàm Render Các Nút Bấm ===
function g_renderProfileButtons(status, userId) {
    const actionsDiv = document.getElementById("g-profile-actions");
    actionsDiv.innerHTML = "";
    const chatPage = `${G_BASE_PATH}/Pages/ChatPages/chat.php?friend_id=${userId}`;
    const profilePage = `${G_BASE_PATH}/Pages/edit_profile.php`;

    switch (status) {
        case "is_self":
            actionsDiv.innerHTML = `<button class="g-normal" onclick="location.href=\'${profilePage}\'">Chỉnh sửa hồ sơ</button>`;
            break;
        case "already_friends":
            actionsDiv.innerHTML = 
                `<button class="g-accept" onclick="location.href=\'${chatPage}\'">Nhắn tin</button>` +
                `<button class="g-reject" onclick="g_unfriend(${userId})">Hủy kết bạn</button>`;
            break;
        case "sent_by_me":
            actionsDiv.innerHTML = `<button class="g-reject" onclick="g_cancelRequest(${userId}, this)">Hủy lời mời</button>`;
            break;
        case "sent_to_me":
            actionsDiv.innerHTML = 
                `<button class="g-accept" onclick="g_respond(${userId}, \'accept\', this)">Chấp nhận</button>` +
                `<button class="g-reject" onclick="g_respond(${userId}, \'reject\', this)">Từ chối</button>`;
            break;
        case "none":
        default:
            actionsDiv.innerHTML = `<button class="g-accept" onclick="g_sendFriend(${userId}, this)">Kết bạn</button>`;
            break;
    }
}

// === Hàm Đồng Bộ (Master) ===
function g_updateAllUserButtons(userId, newStatus) {
    // 1. Cập nhật tất cả các nút BÊN NGOÀI
    const externalButtons = document.querySelectorAll(`[data-user-btn-id="${userId}"]`);
    
    externalButtons.forEach(btn => {
        btn.disabled = false;
        if (newStatus === "sent_by_me") {
            btn.textContent = "Hủy lời mời";
            btn.className = "g-reject";
            btn.onclick = (e) => { e.stopPropagation(); g_cancelRequest(userId, btn); };
        } else if (newStatus === "none") {
            btn.textContent = "Kết bạn";
            btn.className = "g-accept";
            btn.onclick = (e) => { e.stopPropagation(); g_sendFriend(userId, btn); };
        } else if (newStatus === "already_friends") {
            btn.style.display = "none"; // Ẩn nút nếu đã là bạn
        }
    });

    // 2. Cập nhật nút BÊN TRONG modal (nếu đang mở)
    if (g_currentProfileId == userId) {
        g_renderProfileButtons(newStatus, userId);
    }

    // 3. Tải lại các danh sách liên quan
    if (newStatus === "already_friends" || newStatus === "none") {
        if (typeof loadFriends === "function") loadFriends();
        if (typeof loadRequests === "function") loadRequests();
        if (typeof loadSuggestions === "function") loadSuggestions();
    }
}

// === Các Hàm Hành Động (Gửi/Hủy/...) ===
async function g_sendFriend(id, btn) {
    if (btn) { btn.disabled = true; btn.textContent = "Đang gửi..."; }
    const res = await g_fetchPost({ action: "send", friend_id: id });
    if (res.status === "sent") {
        g_updateAllUserButtons(id, "sent_by_me");
    } else {
        alert(res.message || "Đã có lỗi xảy ra.");
        g_updateAllUserButtons(id, "none");
    }
}

async function g_cancelRequest(id, btn) {
    if (btn) { btn.disabled = true; btn.textContent = "Đang hủy..."; }
    const res = await g_fetchPost({ action: "cancel_request", friend_id: id });
    if (res.status === "success") {
        g_updateAllUserButtons(id, "none");
    } else {
        alert("Lỗi: Không thể hủy lời mời.");
        g_updateAllUserButtons(id, "sent_by_me");
    }
}

async function g_respond(id, type, btn) {
    if (btn) btn.disabled = true;
    const res = await g_fetchPost({ action: type, friend_id: id });
    if (res.status === "accepted" || res.status === "rejected") {
        const newStatus = (type === "accept") ? "already_friends" : "none";
        g_updateAllUserButtons(id, newStatus);
    }
}

// ↓↓↓ THAY THẾ HÀM g_unfriend CŨ BẰNG HÀM NÀY ↓↓↓
async function g_unfriend(id) {
    // Lấy tên người dùng từ modal để hiển thị trong popup
    const userName = document.getElementById("g-overlayName").textContent || "người này";
    const message = `Bạn có chắc chắn muốn hủy kết bạn với ${userName}?`;

    // Gọi popup xác nhận mới
    showGlobalConfirm(message, async () => {
        // Hàm này (callback) sẽ chạy khi người dùng bấm "Xác nhận"
        const res = await g_fetchPost({ action: "unfriend", friend_id: id });
        if (res.status === "success") {
            g_updateAllUserButtons(id, "none"); // Đồng bộ!
            // Tự động đóng thẻ thông tin sau khi hủy
            toggleGlobalProfile(0, false); 
        } else {
            alert("Lỗi: Không thể hủy kết bạn.");
            g_updateAllUserButtons(id, "already_friends"); // Trả về trạng thái cũ
        }
    });
}
// ↑↑↑ KẾT THÚC THAY THẾ g_unfriend ↑↑↑

</script>';
}
?>