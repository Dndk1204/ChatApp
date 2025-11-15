<?php
// File: Handler/PostHandler/post_helpers.php

if (!function_exists('renderComments')) {
    /**
     * Hàm PHP đệ quy để render cây bình luận
     */
    function renderComments($post_id, $comments_by_parent, $parent_id = NULL) {
        if (!isset($comments_by_parent[$parent_id])) {
            return; // Không có bình luận con
        }

        $comment_wrapper_class = $parent_id !== NULL ? 'comment-replies' : 'comment-list';
        
        echo "<div class='" . $comment_wrapper_class . "'>";

        foreach ($comments_by_parent[$parent_id] as $comment) {
            $comment_id = $comment['CommentId'];
            
            $js_safe_username = htmlspecialchars($comment['Username'], ENT_QUOTES, 'UTF-8');
            
            $avatar_path = htmlspecialchars($comment['AvatarPath'] ?: 'uploads/default-avatar.jpg');
            $avatar_src = '/ChatApp/' . $avatar_path;
            $profile_link = '/ChatApp/Pages/ProfilePages/Profile.php?id=' . $comment['UserId'];
            
            ?>
            <div class="comment" id="comment-<?php echo $comment_id; ?>">
                
                <a href="<?php echo $profile_link; ?>">
                    <img src="<?php echo $avatar_src; ?>" alt="Avatar" class="comment-avatar">
                </a>

                <div class="comment-bubble">
                    <div class="comment-content">
                        
                        <a href="<?php echo $profile_link; ?>" class="comment-username-link">
                            <span class="comment-username"><?php echo htmlspecialchars($comment['Username']); ?>:</span>
                        </a>
                        
                        <span class="comment-text"><?php echo htmlspecialchars($comment['Content']); ?></span>
                    </div>
                    <div class="comment-meta">
                        <span class="comment-time"><?php echo isset($comment['CommentedAt']) ? date('H:i, d/m/Y', strtotime($comment['CommentedAt'])) : ''; ?></span>
                    </div>
                    <div class="comment-actions">
                        <button class="reply-btn" onclick="setReply(<?php echo $post_id; ?>, <?php echo $comment_id; ?>, '<?php echo $js_safe_username; ?>')">
                            Trả lời
                        </button>
                    </div>
                </div>
            </div>
            <div class="reply-container" id="comment-replies-<?php echo $comment_id; ?>">
                <?php
                // Đệ quy: render các con của bình luận này
                renderComments($post_id, $comments_by_parent, $comment_id);
                ?>
            </div>
            <?php
        }
        echo "</div>";
    }
}
?>