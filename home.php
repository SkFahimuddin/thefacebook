<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$current_user = get_logged_in_user();

// Get user's friends
$friends_query = "SELECT u.* FROM users u 
                  INNER JOIN friends f ON (f.friend_id = u.id OR f.user_id = u.id)
                  WHERE (f.user_id = {$current_user['id']} OR f.friend_id = {$current_user['id']})
                  AND f.status = 'accepted'
                  AND u.id != {$current_user['id']}
                  LIMIT 5";
$friends_result = mysqli_query($conn, $friends_query);

// Get friend requests
$requests_query = "SELECT u.*, f.id as request_id FROM users u 
                   INNER JOIN friends f ON f.user_id = u.id
                   WHERE f.friend_id = {$current_user['id']} AND f.status = 'pending'";
$requests_result = mysqli_query($conn, $requests_query);

// Get wall posts from friends and own wall (News Feed style)
$wall_query = "SELECT w.*, u.first_name, u.last_name, u.profile_pic, o.first_name as owner_first, o.last_name as owner_last
               FROM wall_posts w
               INNER JOIN users u ON w.user_id = u.id
               INNER JOIN users o ON w.wall_owner_id = o.id
               WHERE w.wall_owner_id IN (
                   SELECT friend_id FROM friends WHERE user_id = {$current_user['id']} AND status = 'accepted'
                   UNION
                   SELECT user_id FROM friends WHERE friend_id = {$current_user['id']} AND status = 'accepted'
                   UNION
                   SELECT {$current_user['id']}
               )
               ORDER BY w.post_date DESC
               LIMIT 20";
$wall_result = mysqli_query($conn, $wall_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>thefacebook | home</title>
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
            display: flex;
            min-height: calc(100vh - 40px);
        }
        .main-content {
            flex: 1;
            padding: 20px 25px;
        }
        .welcome-header {
            font-size: 16px;
            color: #3B5998;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
        }
        .profile-section {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f7f7f7;
            border: 1px solid #ccc;
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border: 1px solid #ccc;
            object-fit: cover;
        }
        .profile-info {
            flex: 1;
        }
        .info-table {
            width: 100%;
        }
        .info-table td {
            padding: 5px 8px;
            font-size: 11px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            width: 150px;
            vertical-align: top;
        }
        .info-value {
            color: #333;
        }
        .section {
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            margin-bottom: 20px;
            padding: 15px;
        }
        .section-header {
            background-color: #6d84b4;
            color: white;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 11px;
            margin: -15px -15px 15px -15px;
        }
        .wall-post {
            margin: 10px 0;
            padding: 12px;
            background-color: white;
            border: 1px solid #ccc;
        }
        .wall-post-author {
            font-weight: bold;
            color: #3B5998;
            margin-bottom: 3px;
        }
        .wall-post-author a {
            color: #3B5998;
            text-decoration: none;
        }
        .wall-post-author a:hover {
            text-decoration: underline;
        }
        .wall-post-date {
            font-size: 10px;
            color: #999;
            margin-bottom: 8px;
        }
        .wall-post-content {
            margin-top: 5px;
            line-height: 1.5;
            color: #333;
        }
        textarea {
            width: 100%;
            padding: 8px;
            font-family: Tahoma, Arial, sans-serif;
            font-size: 11px;
            border: 1px solid #ccc;
            resize: vertical;
        }
        .btn {
            background-color: #3B5998;
            color: white;
            padding: 6px 12px;
            border: 1px solid #29447e;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
            margin-top: 8px;
        }
        .btn:hover {
            background-color: #2d4373;
        }
        .sidebar {
            width: 280px;
            background-color: #d8dfea;
            padding: 15px;
            border-left: 1px solid #ccc;
        }
        .sidebar-section {
            background-color: white;
            border: 1px solid #999;
            margin-bottom: 15px;
        }
        .sidebar-header {
            background-color: #6d84b4;
            color: white;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 11px;
        }
        .sidebar-content {
            padding: 10px;
        }
        .friend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .friend-item:last-child {
            border-bottom: none;
        }
        .friend-pic-small {
            width: 30px;
            height: 30px;
            border: 1px solid #ccc;
            object-fit: cover;
        }
        .friend-name {
            color: #3B5998;
            text-decoration: none;
            font-size: 11px;
        }
        .friend-name:hover {
            text-decoration: underline;
        }
        .view-all-link {
            display: block;
            text-align: right;
            color: #3B5998;
            text-decoration: none;
            font-size: 10px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #f0f0f0;
        }
        .view-all-link:hover {
            text-decoration: underline;
        }
        .group-item, .message-item {
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .group-item:last-child, .message-item:last-child {
            border-bottom: none;
        }
        .group-item a, .message-item a {
            color: #3B5998;
            text-decoration: none;
            font-size: 11px;
        }
        .group-item a:hover, .message-item a:hover {
            text-decoration: underline;
        }
        .group-item::before {
            content: "▸ ";
            color: #666;
        }
        .message-item::before {
            content: "▸ ";
            color: #666;
        }
        .view-inbox-link {
            display: block;
            text-align: right;
            color: #3B5998;
            text-decoration: none;
            font-size: 10px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #f0f0f0;
        }
        .view-inbox-link:hover {
            text-decoration: underline;
        }
        .friend-request {
            padding: 10px;
            background-color: #6d84b4;
            border: 1px solid #162c5b;
            margin-bottom: 15px;
        }
        .friend-request a {
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
        }
        .friend-request a:hover {
            text-decoration: underline;
        }
        .friend-request .btn {
            margin-top: 5px;
            margin-right: 5px;
            font-size: 10px;
            padding: 4px 8px;
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
                <a href="#">Messages</a>
                <a href="#">Groups</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="welcome-header">
                Welcome, <?php echo htmlspecialchars($current_user['first_name']); ?>!
            </div>
            
            <?php if (mysqli_num_rows($requests_result) > 0): ?>
                <?php while ($request = mysqli_fetch_assoc($requests_result)): ?>
                    <div class="friend-request">
                        <strong>Friend Request:</strong>
                        <a href="profile.php?id=<?php echo $request['id']; ?>" style="color: black">
                            <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                        </a>
                        wants to be your friend!
                        <br>
                        <a href="accept_friend.php?id=<?php echo $request['request_id']; ?>" class="btn">Accept</a>
                        <a href="reject_friend.php?id=<?php echo $request['request_id']; ?>" class="btn">Reject</a>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
            
            <div class="profile-section">
                <div>
                    <img src="uploads/<?php echo htmlspecialchars($current_user['profile_pic']); ?>" 
                         alt="Profile Picture" 
                         class="profile-pic"
                         onerror="this.src='https://via.placeholder.com/150x150/cccccc/666666?text=Photo'">
                </div>
                <div class="profile-info">
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Name:</td>
                            <td class="info-value">
                                <a href="profile.php?id=<?php echo $current_user['id']; ?>" style="color: #3B5998; text-decoration: none; font-weight: bold;">
                                    <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="info-label">Member Since:</td>
                            <td class="info-value"><?php echo date('F j, Y', strtotime($current_user['joined_date'])); ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Email:</td>
                            <td class="info-value"><?php echo htmlspecialchars($current_user['email']); ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Gender:</td>
                            <td class="info-value"><?php echo htmlspecialchars($current_user['gender']); ?></td>
                        </tr>
                        <?php if (!empty($current_user['birthday'])): ?>
                        <tr>
                            <td class="info-label">Birthday:</td>
                            <td class="info-value"><?php echo date('F j, Y', strtotime($current_user['birthday'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($current_user['relationship_status'])): ?>
                        <tr>
                            <td class="info-label">Relationship Status:</td>
                            <td class="info-value"><?php echo htmlspecialchars($current_user['relationship_status']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($current_user['political_views'])): ?>
                        <tr>
                            <td class="info-label">Political Views:</td>
                            <td class="info-value"><?php echo htmlspecialchars($current_user['political_views']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($current_user['interests'])): ?>
                        <tr>
                            <td class="info-label">Interests:</td>
                            <td class="info-value"><?php echo htmlspecialchars($current_user['interests']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="section">
                <div class="section-header">News Feed</div>
                
                <div style="margin-top: 10px;">
                    <?php 
                    if (mysqli_num_rows($wall_result) > 0) {
                        while ($post = mysqli_fetch_assoc($wall_result)): 
                    ?>
                        <div class="wall-post">
                            <div class="wall-post-author">
                                <a href="profile.php?id=<?php echo $post['user_id']; ?>">
                                    <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>
                                </a>
                                wrote on
                                <a href="profile.php?id=<?php echo $post['wall_owner_id']; ?>">
                                    <?php echo htmlspecialchars($post['owner_first'] . ' ' . $post['owner_last']); ?>
                                </a>'s wall:
                            </div>
                            <div class="wall-post-date"><?php echo date('F j, Y \a\t g:i a', strtotime($post['post_date'])); ?></div>
                            <div class="wall-post-content">
                                <?php echo nl2br(htmlspecialchars($post['post_content'])); ?>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    } else {
                        echo "<p style='color: #666; padding: 10px;'>No recent updates. Add friends to see their wall posts!</p>";
                    }
                    ?>
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

        <div class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-header">My Friends</div>
                <div class="sidebar-content">
                    <?php 
                    $friends_displayed = 0;
                    while ($friend = mysqli_fetch_assoc($friends_result)): 
                        $friends_displayed++;
                    ?>
                        <div class="friend-item">
                            <img src="uploads/<?php echo htmlspecialchars($friend['profile_pic']); ?>" 
                                 alt="<?php echo htmlspecialchars($friend['first_name']); ?>" 
                                 class="friend-pic-small"
                                 onerror="this.src='https://via.placeholder.com/30x30/cccccc/666666?text=?';">
                            <a href="profile.php?id=<?php echo $friend['id']; ?>" class="friend-name">
                                <?php echo htmlspecialchars($friend['first_name']); ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if ($friends_displayed == 0): ?>
                        <div style="color: #666; font-size: 10px;">You have no friends yet. <a href="search.php" style="color: #3B5998;">Search for people!</a></div>
                    <?php endif; ?>
                    
                    <a href="search.php" class="view-all-link">View All Friends</a>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-header">My Groups</div>
                <div class="sidebar-content">
                    <div class="group-item">
                        <a href="#">CS Majors</a>
                    </div>
                    <div class="group-item">
                        <a href="#">Kirkland House Residents</a>
                    </div>
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-header">Messages</div>
                <div class="sidebar-content">
                    <div class="message-item">
                        <a href="#">Re: Party on Friday</a>
                    </div>
                    <div class="message-item">
                        <a href="#">Meeting Tonight</a>
                    </div>
                    <div class="message-item">
                        <a href="#">Class Notes</a>
                    </div>
                    <div class="message-item">
                        <a href="#">Hey what's up?</a>
                    </div>
                    <a href="#" class="view-inbox-link">View Inbox</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>