<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$current_user = get_logged_in_user();

if (!isset($_GET['id'])) {
    header("Location: messages.php");
    exit();
}

$message_id = intval($_GET['id']);

// Get message details
$message_query = "SELECT m.*, 
                  sender.first_name as sender_first, sender.last_name as sender_last, sender.profile_pic as sender_pic,
                  receiver.first_name as receiver_first, receiver.last_name as receiver_last
                  FROM messages m
                  INNER JOIN users sender ON m.sender_id = sender.id
                  INNER JOIN users receiver ON m.receiver_id = receiver.id
                  WHERE m.id = $message_id 
                  AND (m.sender_id = {$current_user['id']} OR m.receiver_id = {$current_user['id']})";

$message_result = mysqli_query($conn, $message_query);

if (mysqli_num_rows($message_result) == 0) {
    header("Location: messages.php");
    exit();
}

$message = mysqli_fetch_assoc($message_result);

// Mark as read if current user is receiver
if ($message['receiver_id'] == $current_user['id'] && $message['is_read'] == 0) {
    $update_query = "UPDATE messages SET is_read = 1 WHERE id = $message_id";
    mysqli_query($conn, $update_query);
}

$is_sender = ($message['sender_id'] == $current_user['id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Message - thefacebook</title>
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
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
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
        .message-container {
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            padding: 20px;
        }
        .message-header {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ccc;
        }
        .sender-pic {
            width: 60px;
            height: 60px;
            border: 1px solid #ccc;
            object-fit: cover;
        }
        .message-info {
            flex: 1;
        }
        .message-meta {
            margin-bottom: 5px;
        }
        .message-meta strong {
            color: #333;
        }
        .message-meta a {
            color: #3B5998;
            text-decoration: none;
            font-weight: bold;
        }
        .message-meta a:hover {
            text-decoration: underline;
        }
        .message-subject {
            font-size: 14px;
            font-weight: bold;
            color: #3B5998;
            margin: 10px 0;
        }
        .message-date {
            font-size: 10px;
            color: #999;
        }
        .message-body {
            background-color: white;
            padding: 15px;
            border: 1px solid #ccc;
            line-height: 1.6;
            color: #333;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .message-actions {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
        }
        .btn {
            background-color: #3B5998;
            color: white;
            padding: 8px 15px;
            border: 1px solid #29447e;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2d4373;
        }
        .btn-secondary {
            background-color: #999;
            border: 1px solid #777;
        }
        .btn-secondary:hover {
            background-color: #888;
        }
        .btn-danger {
            background-color: #d9534f;
            border: 1px solid #c9302c;
        }
        .btn-danger:hover {
            background-color: #c9302c;
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
                <a href="messages.php">Messages</a>
                <a href="#">Groups</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            View Message
        </div>
        
        <div class="message-container">
            <div class="message-header">
                <img src="uploads/<?php echo htmlspecialchars($message['sender_pic']); ?>" 
                     alt="Profile" 
                     class="sender-pic"
                     onerror="this.src='https://via.placeholder.com/60x60/cccccc/666666?text=?'">
                <div class="message-info">
                    <div class="message-meta">
                        <strong>From:</strong> 
                        <a href="profile.php?id=<?php echo $message['sender_id']; ?>">
                            <?php echo htmlspecialchars($message['sender_first'] . ' ' . $message['sender_last']); ?>
                        </a>
                    </div>
                    <div class="message-meta">
                        <strong>To:</strong> 
                        <a href="profile.php?id=<?php echo $message['receiver_id']; ?>">
                            <?php echo htmlspecialchars($message['receiver_first'] . ' ' . $message['receiver_last']); ?>
                        </a>
                    </div>
                    <div class="message-date">
                        Sent: <?php echo date('F j, Y \a\t g:i a', strtotime($message['sent_date'])); ?>
                    </div>
                </div>
            </div>
            
            <div class="message-subject">
                Subject: <?php echo htmlspecialchars($message['subject']); ?>
            </div>
            
            <div class="message-body">
                <?php echo nl2br(htmlspecialchars($message['message_content'])); ?>
            </div>
            
            <div class="message-actions">
                <a href="messages.php" class="btn btn-secondary">Back to Messages</a>
                <?php if (!$is_sender): ?>
                    <a href="compose_message.php?reply=<?php echo $message['id']; ?>" class="btn">Reply</a>
                <?php endif; ?>
                <a href="delete_message.php?id=<?php echo $message['id']; ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this message?')">Delete</a>
            </div>
        </div>
        
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