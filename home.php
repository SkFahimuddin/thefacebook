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
        .profile-link {
            display: block;
            color: #3B5998;
            text-decoration: none;
            font-size: 12px;
            padding: 4px 0;
        }
        .profile-link:hover {
            text-decoration: underline;
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
                Welcome, Fahim!
            </div>
            
            <div class="profile-section">
                <div>
                    <img src="uploads/<?php echo htmlspecialchars($current_user['profile_pic']); ?>" 
                         alt="Profile Picture" 
                         class="profile-pic"
                         onerror="this.src='https://via.placeholder.com/150x150/cccccc/666666?text=Photo'">
                </div>
                <div class="profile-info">
                    <a href="#" class="profile-link"><strong>Harvard University</strong></a>
                    <a href="#" class="profile-link"><strong>Kirkland House</strong></a>
                    <a href="#" class="profile-link"><strong>Computer Science</strong></a>
                </div>
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
                        <div style="color: #666; font-size: 10px;">You have no friends yet.</div>
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