<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$current_user = get_logged_in_user();
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user['id'];

// Get profile user data
$profile_query = "SELECT * FROM users WHERE id = $profile_id";
$profile_result = mysqli_query($conn, $profile_query);

if (mysqli_num_rows($profile_result) == 0) {
    die("User not found.");
}

$profile_user = mysqli_fetch_assoc($profile_result);
$is_own_profile = ($profile_id == $current_user['id']);

// Check friendship status
$friendship_query = "SELECT * FROM friends 
                     WHERE (user_id = {$current_user['id']} AND friend_id = $profile_id)
                     OR (user_id = $profile_id AND friend_id = {$current_user['id']})";
$friendship_result = mysqli_query($conn, $friendship_query);
$friendship = mysqli_fetch_assoc($friendship_result);

// Get friend count
$friend_count_query = "SELECT COUNT(*) as count FROM friends 
                       WHERE (user_id = $profile_id OR friend_id = $profile_id) 
                       AND status = 'accepted'";
$friend_count_result = mysqli_query($conn, $friend_count_query);
$friend_count = mysqli_fetch_assoc($friend_count_result)['count'];

// Get friends list
$friends_query = "SELECT u.id, u.first_name, u.last_name, u.profile_pic FROM users u 
                  INNER JOIN friends f ON (f.friend_id = u.id OR f.user_id = u.id)
                  WHERE (f.user_id = $profile_id OR f.friend_id = $profile_id)
                  AND f.status = 'accepted'
                  AND u.id != $profile_id
                  LIMIT 6";
$friends_result = mysqli_query($conn, $friends_query);

// Handle wall post submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_wall'])) {
    $post_content = clean_input($_POST['post_content']);
    if (!empty($post_content)) {
        $insert_post = "INSERT INTO wall_posts (user_id, wall_owner_id, post_content) 
                       VALUES ({$current_user['id']}, $profile_id, '$post_content')";
        mysqli_query($conn, $insert_post);
        header("Location: profile.php?id=$profile_id");
        exit();
    }
}

// Get wall posts
$wall_query = "SELECT w.*, u.first_name, u.last_name, u.profile_pic FROM wall_posts w
               INNER JOIN users u ON w.user_id = u.id
               WHERE w.wall_owner_id = $profile_id
               ORDER BY w.post_date DESC
               LIMIT 20";
$wall_result = mysqli_query($conn, $wall_query);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile']) && $is_own_profile) {
    $interests = clean_input($_POST['interests']);
    $favorite_music = clean_input($_POST['favorite_music']);
    $favorite_movies = clean_input($_POST['favorite_movies']);
    $favorite_books = clean_input($_POST['favorite_books']);
    $about_me = clean_input($_POST['about_me']);
    $relationship_status = clean_input($_POST['relationship_status']);
    $political_views = clean_input($_POST['political_views']);
    $looking_for = clean_input($_POST['looking_for']);
    $interested_in = clean_input($_POST['interested_in']);
    
    $update_query = "UPDATE users SET 
                     interests = '$interests',
                     favorite_music = '$favorite_music',
                     favorite_movies = '$favorite_movies',
                     favorite_books = '$favorite_books',
                     about_me = '$about_me',
                     relationship_status = '$relationship_status',
                     political_views = '$political_views',
                     looking_for = '$looking_for',
                     interested_in = '$interested_in'
                     WHERE id = {$current_user['id']}";
    
    if (mysqli_query($conn, $update_query)) {
        header("Location: profile.php?id={$current_user['id']}");
        exit();
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_pic']) && $is_own_profile) {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'user_' . $current_user['id'] . '_' . time() . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                $update_pic = "UPDATE users SET profile_pic = '$new_filename' WHERE id = {$current_user['id']}";
                mysqli_query($conn, $update_pic);
                header("Location: profile.php?id={$current_user['id']}");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?>'s Profile</title>
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
            background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==') repeat;
            background-color: #3B5998;
            padding: 5px 10px;
            color: white;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-right: 20px;
        }
        .header-nav {
            display: inline-block;
        }
        .header-nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-size: 11px;
        }
        .header-nav a:hover {
            text-decoration: underline;
        }
        .search-box {
            float: right;
            margin-top: 3px;
        }
        .search-box input[type="text"] {
            padding: 2px 5px;
            font-size: 11px;
            border: 1px solid #ccc;
        }
        .search-box input[type="submit"] {
            padding: 2px 8px;
            font-size: 11px;
            background-color: #6d84b4;
            color: white;
            border: 1px solid #29447e;
            cursor: pointer;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            min-height: 100vh;
        }
        .profile-layout {
            display: flex;
        }
        .sidebar {
            width: 180px;
            background-color: #d8dfea;
            padding: 10px;
            border-right: 1px solid #ccc;
            min-height: 100vh;
        }
        .sidebar-section {
            margin-bottom: 15px;
        }
        .sidebar-section h3 {
            font-size: 11px;
            color: #3B5998;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .sidebar-section a {
            display: block;
            color: #3B5998;
            text-decoration: none;
            padding: 2px 0;
            font-size: 11px;
        }
        .sidebar-section a:hover {
            text-decoration: underline;
        }
        .main-content {
            flex: 1;
            padding: 15px;
        }
        .profile-header {
            display: flex;
            padding: 15px;
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            margin-bottom: 15px;
        }
        .profile-pic-section {
            margin-right: 20px;
        }
        .profile-pic {
            width: 150px;
            height: 150px;
            border: 1px solid #ccc;
            object-fit: cover;
        }
        .upload-pic-form {
            margin-top: 5px;
        }
        .upload-pic-form input[type="file"] {
            font-size: 9px;
        }
        .upload-pic-form input[type="submit"] {
            font-size: 9px;
            padding: 2px 5px;
            margin-top: 3px;
        }
        .profile-info {
            flex: 1;
        }
        .profile-name {
            font-size: 18px;
            font-weight: bold;
            color: #3B5998;
            margin-bottom: 10px;
        }
        .info-table {
            width: 100%;
        }
        .info-table td {
            padding: 3px 5px;
            font-size: 11px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            width: 120px;
            vertical-align: top;
        }
        .info-value {
            color: #3B5998;
        }
        .section {
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            margin-bottom: 15px;
            padding: 15px;
        }
        .section-header {
            background-color: #6d84b4;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11px;
            margin: -15px -15px 10px -15px;
        }
        .blue-link {
            color: #3B5998;
            text-decoration: none;
        }
        .blue-link:hover {
            text-decoration: underline;
        }
        .btn {
            background-color: #3B5998;
            color: white;
            padding: 5px 10px;
            border: 1px solid #29447e;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #2d4373;
        }
        textarea {
            width: 100%;
            padding: 5px;
            font-family: Tahoma, Arial, sans-serif;
            font-size: 11px;
            border: 1px solid #ccc;
        }
        input[type="text"], select {
            padding: 3px;
            font-size: 11px;
            border: 1px solid #ccc;
        }
        .wall-post {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border: 1px solid #ccc;
        }
        .wall-post-author {
            font-weight: bold;
            color: #3B5998;
            margin-bottom: 3px;
        }
        .wall-post-date {
            font-size: 10px;
            color: #999;
            margin-bottom: 5px;
        }
        .wall-post-content {
            margin-top: 5px;
            line-height: 1.4;
        }
        .friends-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .friend-item {
            text-align: center;
        }
        .friend-pic {
            width: 50px;
            height: 50px;
            border: 1px solid #ccc;
            object-fit: cover;
        }
        .friend-name {
            font-size: 10px;
            margin-top: 3px;
        }
        .edit-section {
            margin: 10px 0;
        }
        .edit-section label {
            display: block;
            font-weight: bold;
            margin: 8px 0 3px 0;
        }
        .connection-status {
            background-color: white;
            padding: 8px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="home.php" class="logo">[thefacebook]</a>
            <div class="header-nav">
                <a href="home.php">home</a>
                <a href="search.php">search</a>
                <a href="profile.php?id=<?php echo $current_user['id']; ?>">profile</a>
                <a href="logout.php">logout</a>
            </div>
            <div class="search-box">
                <form method="GET" action="search.php" style="margin: 0;">
                    <input type="text" name="q" placeholder="quick search" size="15">
                    <input type="submit" value="go">
                </form>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>

    <div class="container">
        <div class="profile-layout">
            <div class="sidebar">
                <div class="sidebar-section">
                    <h3>My Profile [ edit ]</h3>
                    <a href="#info">My Friends</a>
                    <a href="#parties">My Parties</a>
                    <a href="#messages">My Messages</a>
                    <a href="#account">My Account</a>
                    <a href="#privacy">My Privacy</a>
                </div>

                <div class="sidebar-section">
                    <h3>Access</h3>
                    <div style="padding: 5px 0; font-size: 10px; color: #666;">
                        <?php echo htmlspecialchars($profile_user['first_name']); ?> is currently logged in from a non-mobile location.
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Friends at Puget Sound</h3>
                    <div class="friends-grid">
                        <?php 
                        $friend_pics = [];
                        while ($friend = mysqli_fetch_assoc($friends_result)) {
                            $friend_pics[] = $friend;
                        }
                        for ($i = 0; $i < 6; $i++):
                            if (isset($friend_pics[$i])):
                        ?>
                            <div class="friend-item">
                                <a href="profile.php?id=<?php echo $friend_pics[$i]['id']; ?>">
                                    <img src="uploads/<?php echo htmlspecialchars($friend_pics[$i]['profile_pic']); ?>" 
                                         alt="<?php echo htmlspecialchars($friend_pics[$i]['first_name']); ?>" 
                                         class="friend-pic"
                                         onerror="this.src='https://via.placeholder.com/50x50/cccccc/666666?text=No+Pic'">
                                </a>
                                <div class="friend-name">
                                    <a href="profile.php?id=<?php echo $friend_pics[$i]['id']; ?>" class="blue-link">
                                        <?php echo htmlspecialchars($friend_pics[$i]['first_name']); ?>
                                    </a>
                                </div>
                            </div>
                        <?php 
                            else:
                        ?>
                            <div class="friend-item">
                                <img src="https://via.placeholder.com/50x50/eeeeee/cccccc?text=?" class="friend-pic">
                            </div>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <div class="profile-header">
                    <div class="profile-pic-section">
                        <img src="uploads/<?php echo htmlspecialchars($profile_user['profile_pic']); ?>" 
                             alt="Profile Picture" 
                             class="profile-pic"
                             onerror="this.src='https://via.placeholder.com/150x150/cccccc/666666?text=No+Photo'">
                        <?php if ($is_own_profile): ?>
                        <form method="POST" enctype="multipart/form-data" class="upload-pic-form">
                            <input type="file" name="profile_pic" accept="image/*">
                            <input type="submit" name="upload_pic" value="Upload" class="btn">
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name">
                            <?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?>'s Profile
                        </div>
                        <table class="info-table">
                            <tr>
                                <td class="info-label">Member Since:</td>
                                <td class="info-value"><?php echo date('F j, Y', strtotime($profile_user['joined_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="info-label">Last Update:</td>
                                <td class="info-value"><?php echo date('F j, Y', strtotime($profile_user['joined_date'])); ?></td>
                            </tr>
                            <tr>
                                <td class="info-label">Email:</td>
                                <td class="info-value"><?php echo htmlspecialchars($profile_user['email']); ?></td>
                            </tr>
                            <tr>
                                <td class="info-label">Screen name:</td>
                                <td class="info-value"><?php echo htmlspecialchars(explode('@', $profile_user['email'])[0]); ?></td>
                            </tr>
                            <tr>
                                <td class="info-label">Mobile:</td>
                                <td class="info-value">+1-xxx-xxx-xxxx</td>
                            </tr>
                            <tr>
                                <td class="info-label">Website:</td>
                                <td class="info-value"><a href="#" class="blue-link">http://www.example.com</a></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if (!$is_own_profile): ?>
                <div class="connection-status">
                    <?php if (!$friendship): ?>
                        You are not in a relationship with <?php echo htmlspecialchars($profile_user['first_name']); ?>.
                        <a href="add_friend.php?id=<?php echo $profile_id; ?>" class="btn">Add as Friend</a>
                    <?php elseif ($friendship['status'] == 'pending'): ?>
                        <?php if ($friendship['user_id'] == $current_user['id']): ?>
                            Friend request pending...
                        <?php else: ?>
                            <?php echo htmlspecialchars($profile_user['first_name']); ?> wants to be friends!
                            <a href="accept_friend.php?id=<?php echo $friendship['id']; ?>" class="btn">Accept</a>
                            <a href="reject_friend.php?id=<?php echo $friendship['id']; ?>" class="btn">Reject</a>
                        <?php endif; ?>
                    <?php else: ?>
                        You are in a relationship with <?php echo htmlspecialchars($profile_user['first_name']); ?>.
                    <?php endif; ?>
                </div>
                
                <div class="section">
                    <div class="section-header">Send <?php echo htmlspecialchars($profile_user['first_name']); ?> a Message</div>
                    <input type="text" placeholder="Subject" style="width: 100%; margin-bottom: 5px;">
                    <textarea rows="3" placeholder="Message"></textarea>
                    <button class="btn" style="margin-top: 5px;">Send</button>
                </div>
                <?php endif; ?>

                <div class="section">
                    <div class="section-header">Information</div>
                    
                    <?php if ($is_own_profile): ?>
                    <form method="POST">
                        <div class="edit-section">
                            <label>Looking For:</label>
                            <select name="looking_for">
                                <option value="">Select...</option>
                                <option value="Friendship" <?php if(isset($profile_user['looking_for']) && $profile_user['looking_for']=='Friendship') echo 'selected'; ?>>Friendship</option>
                                <option value="Dating" <?php if(isset($profile_user['looking_for']) && $profile_user['looking_for']=='Dating') echo 'selected'; ?>>Dating</option>
                                <option value="A Relationship" <?php if(isset($profile_user['looking_for']) && $profile_user['looking_for']=='A Relationship') echo 'selected'; ?>>A Relationship</option>
                                <option value="Whatever" <?php if(isset($profile_user['looking_for']) && $profile_user['looking_for']=='Whatever') echo 'selected'; ?>>Whatever</option>
                            </select>
                        </div>
                        
                        <div class="edit-section">
                            <label>Interested In:</label>
                            <select name="interested_in">
                                <option value="">Select...</option>
                                <option value="Women" <?php if(isset($profile_user['interested_in']) && $profile_user['interested_in']=='Women') echo 'selected'; ?>>Women</option>
                                <option value="Men" <?php if(isset($profile_user['interested_in']) && $profile_user['interested_in']=='Men') echo 'selected'; ?>>Men</option>
                                <option value="Men and Women" <?php if(isset($profile_user['interested_in']) && $profile_user['interested_in']=='Men and Women') echo 'selected'; ?>>Men and Women</option>
                            </select>
                        </div>
                        
                        <div class="edit-section">
                            <label>Relationship Status:</label>
                            <select name="relationship_status">
                                <option value="">Select...</option>
                                <option value="Single" <?php if(isset($profile_user['relationship_status']) && $profile_user['relationship_status']=='Single') echo 'selected'; ?>>Single</option>
                                <option value="In a Relationship" <?php if(isset($profile_user['relationship_status']) && $profile_user['relationship_status']=='In a Relationship') echo 'selected'; ?>>In a Relationship</option>
                                <option value="Married" <?php if(isset($profile_user['relationship_status']) && $profile_user['relationship_status']=='Married') echo 'selected'; ?>>Married</option>
                                <option value="Its Complicated" <?php if(isset($profile_user['relationship_status']) && $profile_user['relationship_status']=='Its Complicated') echo 'selected'; ?>>It's Complicated</option>
                            </select>
                        </div>
                        
                        <div class="edit-section">
                            <label>Political Views:</label>
                            <input type="text" name="political_views" value="<?php echo htmlspecialchars($profile_user['political_views'] ?? ''); ?>" style="width: 300px;">
                        </div>
                        
                        <div class="edit-section">
                            <label>Interests:</label>
                            <textarea name="interests" rows="3"><?php echo htmlspecialchars($profile_user['interests'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="edit-section">
                            <label>Favorite Music:</label>
                            <textarea name="favorite_music" rows="3"><?php echo htmlspecialchars($profile_user['favorite_music'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="edit-section">
                            <label>Favorite Movies:</label>
                            <textarea name="favorite_movies" rows="3"><?php echo htmlspecialchars($profile_user['favorite_movies'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="edit-section">
                            <label>Favorite Books:</label>
                            <textarea name="favorite_books" rows="3"><?php echo htmlspecialchars($profile_user['favorite_books'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="edit-section">
                            <label>About Me:</label>
                            <textarea name="about_me" rows="4"><?php echo htmlspecialchars($profile_user['about_me'] ?? ''); ?></textarea>
                        </div>
                        
                        <input type="submit" name="update_profile" value="Save All Changes" class="btn">
                    </form>
                    <?php else: ?>
                    <table class="info-table">
                        <?php if (isset($profile_user['looking_for']) && $profile_user['looking_for']): ?>
                        <tr>
                            <td class="info-label">Looking For:</td>
                            <td><?php echo htmlspecialchars($profile_user['looking_for']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($profile_user['interested_in']) && $profile_user['interested_in']): ?>
                        <tr>
                            <td class="info-label">Interested In:</td>
                            <td><?php echo htmlspecialchars($profile_user['interested_in']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($profile_user['relationship_status']) && $profile_user['relationship_status']): ?>
                        <tr>
                            <td class="info-label">Relationship Status:</td>
                            <td><?php echo htmlspecialchars($profile_user['relationship_status']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($profile_user['political_views']) && $profile_user['political_views']): ?>
                        <tr>
                            <td class="info-label">Political Views:</td>
                            <td><?php echo htmlspecialchars($profile_user['political_views']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($profile_user['interests']) && $profile_user['interests']): ?>
                        <tr>
                            <td class="info-label">Interests:</td>
                            <td><?php echo nl2br(htmlspecialchars($profile_user['interests'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($profile_user['favorite_music']) && $profile_user['favorite_music']): ?>
                        <tr>
                            <td class="info-label">Favorite Music:</td>
                            <td><?php echo nl2br(htmlspecialchars($profile_user['favorite_music'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($profile_user['favorite_movies']) && $profile_user['favorite_movies']): ?>
                        <tr>
                            <td class="info-label">Favorite Movies:</td>
                            <td><?php echo nl2br(htmlspecialchars($profile_user['favorite_movies'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($profile_user['favorite_books']) && $profile_user['favorite_books']): ?>
                        <tr>
                            <td class="info-label">Favorite Books:</td>
                            <td><?php echo nl2br(htmlspecialchars($profile_user['favorite_books'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (isset($profile_user['about_me']) && $profile_user['about_me']): ?>
                        <tr>
                            <td class="info-label">About Me:</td>
                            <td><?php echo nl2br(htmlspecialchars($profile_user['about_me'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <div class="section-header"><?php echo $is_own_profile ? 'Your' : htmlspecialchars($profile_user['first_name']) . "'s"; ?> Wall</div>
                    
                    <?php if ($friendship && $friendship['status'] == 'accepted' || $is_own_profile): ?>
                    <form method="POST" style="margin-bottom: 15px;">
                        <textarea name="post_content" rows="3" placeholder="Write something on the wall..." required></textarea>
                        <input type="submit" name="post_wall" value="Post" class="btn" style="margin-top: 5px;">
                    </form>
                    <?php endif; ?>
                    
                    <?php 
                    if (mysqli_num_rows($wall_result) > 0) {
                        while ($post = mysqli_fetch_assoc($wall_result)): 
                    ?>
                        <div class="wall-post">
                            <div class="wall-post-author">
                                <a href="profile.php?id=<?php echo $post['user_id']; ?>" class="blue-link">
                                    <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>
                                </a>
                                wrote:
                            </div>
                            <div class="wall-post-date"><?php echo date('F j, Y \a\t g:i a', strtotime($post['post_date'])); ?></div>
                            <div class="wall-post-content">
                                <?php echo nl2br(htmlspecialchars($post['post_content'])); ?>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    } else {
                        echo "<p style='color: #666;'>No wall posts yet.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>