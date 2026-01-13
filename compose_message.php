<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$current_user = get_logged_in_user();
$error = '';
$success = '';
$reply_to = null;
$recipient_id = null;

// Check if replying to a message
if (isset($_GET['reply'])) {
    $reply_id = intval($_GET['reply']);
    $reply_query = "SELECT m.*, u.first_name, u.last_name FROM messages m
                    INNER JOIN users u ON m.sender_id = u.id
                    WHERE m.id = $reply_id AND m.receiver_id = {$current_user['id']}";
    $reply_result = mysqli_query($conn, $reply_query);
    if (mysqli_num_rows($reply_result) > 0) {
        $reply_to = mysqli_fetch_assoc($reply_result);
        $recipient_id = $reply_to['sender_id'];
    }
}

// Check if sending to specific user
if (isset($_GET['to'])) {
    $recipient_id = intval($_GET['to']);
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $subject = clean_input($_POST['subject']);
    $message_content = clean_input($_POST['message_content']);
    
    if (empty($message_content)) {
        $error = "Message content cannot be empty.";
    } else {
        $insert_query = "INSERT INTO messages (sender_id, receiver_id, subject, message_content) 
                        VALUES ({$current_user['id']}, $receiver_id, '$subject', '$message_content')";
        
        if (mysqli_query($conn, $insert_query)) {
            $success = "Message sent successfully!";
            header("Location: messages.php?tab=sent");
            exit();
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Get list of friends for recipient dropdown
$friends_query = "SELECT u.id, u.first_name, u.last_name FROM users u 
                  INNER JOIN friends f ON (f.friend_id = u.id OR f.user_id = u.id)
                  WHERE (f.user_id = {$current_user['id']} OR f.friend_id = {$current_user['id']})
                  AND f.status = 'accepted'
                  AND u.id != {$current_user['id']}
                  ORDER BY u.first_name, u.last_name";
$friends_result = mysqli_query($conn, $friends_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Compose Message - thefacebook</title>
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
        .form-section {
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group select,
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 6px;
            font-family: Tahoma, Arial, sans-serif;
            font-size: 11px;
            border: 1px solid #ccc;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 200px;
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
        .error {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 10px;
            margin-bottom: 15px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            margin-bottom: 15px;
        }
        .reply-info {
            background-color: #e8f0fe;
            border: 1px solid #b3d4fc;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 11px;
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
            Compose New Message
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($reply_to): ?>
            <div class="reply-info">
                <strong>Replying to:</strong> <?php echo htmlspecialchars($reply_to['first_name'] . ' ' . $reply_to['last_name']); ?><br>
                <strong>Original Subject:</strong> <?php echo htmlspecialchars($reply_to['subject']); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <form method="POST" action="">
                <div class="form-group">
                    <label>To:</label>
                    <select name="receiver_id" required <?php echo $recipient_id ? 'disabled' : ''; ?>>
                        <option value="">Select a friend...</option>
                        <?php while ($friend = mysqli_fetch_assoc($friends_result)): ?>
                            <option value="<?php echo $friend['id']; ?>" 
                                    <?php echo ($recipient_id == $friend['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if ($recipient_id): ?>
                        <input type="hidden" name="receiver_id" value="<?php echo $recipient_id; ?>">
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" 
                           value="<?php echo $reply_to ? 'Re: ' . htmlspecialchars($reply_to['subject']) : ''; ?>" 
                           placeholder="Enter subject..." 
                           maxlength="255">
                </div>
                
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message_content" required placeholder="Type your message here..."></textarea>
                </div>
                
                <div>
                    <input type="submit" name="send_message" value="Send Message" class="btn">
                    <a href="messages.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
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