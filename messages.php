<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$current_user = get_logged_in_user();

// Get inbox messages (received)
$inbox_query = "SELECT m.*, u.first_name, u.last_name, u.profile_pic 
                FROM messages m
                INNER JOIN users u ON m.sender_id = u.id
                WHERE m.receiver_id = {$current_user['id']}
                ORDER BY m.sent_date DESC";
$inbox_result = mysqli_query($conn, $inbox_query);

// Get sent messages
$sent_query = "SELECT m.*, u.first_name, u.last_name, u.profile_pic 
               FROM messages m
               INNER JOIN users u ON m.receiver_id = u.id
               WHERE m.sender_id = {$current_user['id']}
               ORDER BY m.sent_date DESC";
$sent_result = mysqli_query($conn, $sent_query);

// Count unread messages
$unread_query = "SELECT COUNT(*) as unread_count FROM messages 
                 WHERE receiver_id = {$current_user['id']} AND is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_count = mysqli_fetch_assoc($unread_result)['unread_count'];

// Get active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'inbox';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Messages - thefacebook</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Tahoma, Verdana, Arial, sans-serif;
            font-size: 11px;
            background-color: #3B5998;
        }
        .header {
            background-color: #3B5998;
            padding: 8px 15px;
            color: white;
            border-bottom: 1px solid #29447e;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 22px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .header-nav a {
            color: white;
            text-decoration: none;
            margin: 0 12px;
            font-size: 11px;
        }
        .header-nav a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            min-height: calc(100vh - 40px);
            padding: 20px;
        }
        .page-header {
            font-size: 16px;
            color: #3B5998;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
        }
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ccc;
        }
        .tab {
            padding: 8px 20px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-bottom: none;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            font-size: 11px;
            font-weight: bold;
        }
        .tab:hover {
            background-color: #e0e0e0;
        }
        .tab.active {
            background-color: white;
            color: #3B5998;
            border-bottom: 2px solid white;
            margin-bottom: -2px;
        }
        .compose-btn {
            background-color: #3B5998;
            color: white;
            padding: 8px 15px;
            border: 1px solid #29447e;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 15px;
        }
        .compose-btn:hover {
            background-color: #2d4373;
        }
        .message-list {
            border: 1px solid #ccc;
        }
        .message-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            align-items: flex-start;
            background-color: white;
        }
        .message-item:hover {
            background-color: #f7f7f7;
        }
        .message-item.unread {
            background-color: #e8f0fe;
            font-weight: bold;
        }
        .message-item.unread:hover {
            background-color: #d6e4f9;
        }
        .message-pic {
            width: 40px;
            height: 40px;
            border: 1px solid #ccc;
            object-fit: cover;
            flex-shrink: 0;
        }
        .message-content {
            flex: 1;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .message-from {
            font-size: 11px;
            color: #3B5998;
        }
        .message-from a {
            color: #3B5998;
            text-decoration: none;
            font-weight: bold;
        }
        .message-from a:hover {
            text-decoration: underline;
        }
        .message-date {
            font-size: 10px;
            color: #999;
        }
        .message-subject {
            font-size: 11px;
            margin-bottom: 3px;
            color: #333;
        }
        .message-preview {
            font-size: 10px;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .message-actions {
            margin-top: 5px;
        }
        .message-actions a {
            font-size: 10px;
            color: #3B5998;
            text-decoration: none;
            margin-right: 10px;
        }
        .message-actions a:hover {
            text-decoration: underline;
        }
        .no-messages {
            padding: 30px;
            text-align: center;
            color: #666;
            background-color: #f7f7f7;
            border: 1px solid #ccc;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .footer a {
            color: #3B5998;
            text-decoration: none;
            margin: 0 8px;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="home.php" class="logo">thefacebook</a>
            <div class="header-nav">
                <a href="profile.php?id=<?php echo $current_user['id']; ?>">My Profile</a>
                <a href="search.php">My Friends</a>
                <a href="search.php">Search</a>
                <a href="messages.php">Messages <?php if ($unread_count > 0) echo "($unread_count)"; ?></a>
                <a href="#">Groups</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            My Messages
        </div>
        
        <a href="compose_message.php" class="compose-btn">+ Compose New Message</a>
        
        <div class="tabs">
            <a href="messages.php?tab=inbox" class="tab <?php echo $active_tab == 'inbox' ? 'active' : ''; ?>">
                Inbox <?php if ($unread_count > 0) echo "($unread_count)"; ?>
            </a>
            <a href="messages.php?tab=sent" class="tab <?php echo $active_tab == 'sent' ? 'active' : ''; ?>">
                Sent Messages
            </a>
        </div>
        
        <?php if ($active_tab == 'inbox'): ?>
            <div class="message-list">
                <?php if (mysqli_num_rows($inbox_result) > 0): ?>
                    <?php while ($message = mysqli_fetch_assoc($inbox_result)): ?>
                        <div class="message-item <?php echo $message['is_read'] == 0 ? 'unread' : ''; ?>">
                            <img src="uploads/<?php echo htmlspecialchars($message['profile_pic']); ?>" 
                                 alt="Profile" 
                                 class="message-pic"
                                 onerror="this.src='https://via.placeholder.com/40x40/cccccc/666666?text=?'">
                            <div class="message-content">
                                <div class="message-header">
                                    <div class="message-from">
                                        From: <a href="profile.php?id=<?php echo $message['sender_id']; ?>">
                                            <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                                        </a>
                                    </div>
                                    <div class="message-date">
                                        <?php echo date('M j, Y \a\t g:i a', strtotime($message['sent_date'])); ?>
                                    </div>
                                </div>
                                <div class="message-subject">
                                    <strong><?php echo htmlspecialchars($message['subject']); ?></strong>
                                </div>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(substr($message['message_content'], 0, 100)); ?>...
                                </div>
                                <div class="message-actions">
                                    <a href="view_message.php?id=<?php echo $message['id']; ?>">Read Message</a>
                                    <a href="compose_message.php?reply=<?php echo $message['id']; ?>">Reply</a>
                                    <a href="delete_message.php?id=<?php echo $message['id']; ?>" 
                                       onclick="return confirm('Delete this message?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <strong>Your inbox is empty</strong><br><br>
                        You have no messages. <a href="search.php" style="color: #3B5998;">Find friends</a> and start messaging!
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="message-list">
                <?php if (mysqli_num_rows($sent_result) > 0): ?>
                    <?php while ($message = mysqli_fetch_assoc($sent_result)): ?>
                        <div class="message-item">
                            <img src="uploads/<?php echo htmlspecialchars($message['profile_pic']); ?>" 
                                 alt="Profile" 
                                 class="message-pic"
                                 onerror="this.src='https://via.placeholder.com/40x40/cccccc/666666?text=?'">
                            <div class="message-content">
                                <div class="message-header">
                                    <div class="message-from">
                                        To: <a href="profile.php?id=<?php echo $message['receiver_id']; ?>">
                                            <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                                        </a>
                                    </div>
                                    <div class="message-date">
                                        <?php echo date('M j, Y \a\t g:i a', strtotime($message['sent_date'])); ?>
                                    </div>
                                </div>
                                <div class="message-subject">
                                    <strong><?php echo htmlspecialchars($message['subject']); ?></strong>
                                </div>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(substr($message['message_content'], 0, 100)); ?>...
                                </div>
                                <div class="message-actions">
                                    <a href="view_message.php?id=<?php echo $message['id']; ?>">View Message</a>
                                    <a href="delete_message.php?id=<?php echo $message['id']; ?>" 
                                       onclick="return confirm('Delete this message?')">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <strong>You haven't sent any messages yet</strong><br><br>
                        <a href="compose_message.php" style="color: #3B5998;">Send your first message!</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <a href="#">about</a>
            <a href="#">contact</a>
            <a href="#">faq</a>
            <a href="#">terms</a>
            <a href="#">privacy</a>
            <br>
            <div style="margin-top: 8px;">a Sk Fahimuddin production</div>
            <div style="margin-top: 3px;">Thefacebook © 2004</div>
        </div>
    </div>
</body>
</html>