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
$wall_query = "SELECT w.*, u.first_name, u.last_name FROM wall_posts w
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
    
    $update_query = "UPDATE users SET 
                     interests = '$interests',
                     favorite_music = '$favorite_music',
                     favorite_movies = '$favorite_movies',
                     favorite_books = '$favorite_books',
                     about_me = '$about_me',
                     relationship_status = '$relationship_status',
                     political_views = '$political_views'
                     WHERE id = {$current_user['id']}";
    
    if (mysqli_query($conn, $update_query)) {
        header("Location: profile.php?id={$current_user['id']}");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?> - TheFacebook</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            background-color: #3B5998;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #3B5998;
            color: white;
            padding: 10px 20px;
            font-size: 24px;
            font-weight: bold;
        }
        .nav {
            background-color: #6d84b4;
            padding: 10px 20px;
            color: white;
        }
        .nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
        }
        .profile-header {
            border-bottom: 2px solid #3B5998;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .profile-name {
            font-size: 24px;
            color: #3B5998;
            font-weight: bold;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f7f7f7;
            border: 1px solid #ddd;
        }
        h2 {
            color: #3B5998;
            font-size: 16px;
            margin-top: 0;
        }
        .info-row {
            margin: 8px 0;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        textarea {
            width: 95%;
            padding: 8px;
            border: 1px solid #ccc;
            font-family: Tahoma, Arial, sans-serif;
        }
        input[type="text"] {
            width: 95%;
            padding: 5px;
            border: 1px solid #ccc;
        }
        select {
            padding: 5px;
            border: 1px solid #ccc;
        }
        .btn {
            background-color: #3B5998;
            color: white;
            padding: 8px 15px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px 5px 5px 0;
        }
        .btn:hover {
            background-color: #2d4373;
        }
        .post {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border: 1px solid #ddd;
        }
        .post-author {
            font-weight: bold;
            color: #3B5998;
        }
        .post-date {
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">TheFacebook</div>
    <div class="nav">
        <a href="home.php">Home</a>
        <a href="profile.php?id=<?php echo $current_user['id']; ?>">My Profile</a>
        <a href="search.php">Search</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="container">
        <div class="profile-header">
            <div class="profile-name">
                <?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?>
            </div>
            <div><?php echo htmlspecialchars($profile_user['email']); ?></div>
        </div>
        
        <?php if (!$is_own_profile): ?>
            <div class="section">
                <?php if (!$friendship): ?>
                    <a href="add_friend.php?id=<?php echo $profile_id; ?>" class="btn">Add as Friend</a>
                <?php elseif ($friendship['status'] == 'pending'): ?>
                    <?php if ($friendship['user_id'] == $current_user['id']): ?>
                        <p>Friend request pending...</p>
                    <?php else: ?>
                        <a href="accept_friend.php?id=<?php echo $friendship['id']; ?>" class="btn">Accept Friend Request</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p>You are friends</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Basic Information</h2>
            <div class="info-row">
                <span class="info-label">Gender:</span> <?php echo htmlspecialchars($profile_user['gender']); ?>
            </div>
            <?php if ($profile_user['birthday']): ?>
            <div class="info-row">
                <span class="info-label">Birthday:</span> <?php echo date('F j, Y', strtotime($profile_user['birthday'])); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($profile_user['relationship_status']) && $profile_user['relationship_status']): ?>
            <div class="info-row">
                <span class="info-label">Relationship:</span> <?php echo htmlspecialchars($profile_user['relationship_status']); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($profile_user['political_views']) && $profile_user['political_views']): ?>
            <div class="info-row">
                <span class="info-label">Political Views:</span> <?php echo htmlspecialchars($profile_user['political_views']); ?>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Member Since:</span> <?php echo date('F Y', strtotime($profile_user['joined_date'])); ?>
            </div>
        </div>
        
        <?php if ($is_own_profile): ?>
        <div class="section">
            <h2>Edit Your Profile</h2>
            <form method="POST">
                <div class="info-row">
                    <label class="info-label">Relationship Status:</label><br>
                    <select name="relationship_status">
                        <option value="">Select...</option>
                        <option value="Single" <?php if(isset($profile_user['relationship_status']) && $profile_user['relationship_status']=='Single') echo 'selected'; ?>>Single</option>
                        <option value="In a Relationship" <?php if(isset($profile_user['relationship_status']) && $profile_user['relationship_status']=='In a Relationship') echo 'selected'; ?>>In a Relationship</option>
                        <option value="Married" <?php if(isset($profile_user['relationship_status']) && $profile_user['relationship_status']=='Married') echo 'selected'; ?>>Married</option>
                        <option value="Its Complicated" <?php if(isset($profile_user['relationship_status']) && $profile_user['relationship_status']=='Its Complicated') echo 'selected'; ?>>It's Complicated</option>
                    </select>
                </div>
                <div class="info-row">
                    <label class="info-label">Political Views:</label><br>
                    <input type="text" name="political_views" value="<?php echo htmlspecialchars($profile_user['political_views'] ?? ''); ?>">
                </div>
                <div class="info-row">
                    <label class="info-label">Interests:</label><br>
                    <textarea name="interests" rows="3"><?php echo htmlspecialchars($profile_user['interests'] ?? ''); ?></textarea>
                </div>
                <div class="info-row">
                    <label class="info-label">Favorite Music:</label><br>
                    <textarea name="favorite_music" rows="2"><?php echo htmlspecialchars($profile_user['favorite_music'] ?? ''); ?></textarea>
                </div>
                <div class="info-row">
                    <label class="info-label">Favorite Movies:</label><br>
                    <textarea name="favorite_movies" rows="2"><?php echo htmlspecialchars($profile_user['favorite_movies'] ?? ''); ?></textarea>
                </div>
                <div class="info-row">
                    <label class="info-label">Favorite Books:</label><br>
                    <textarea name="favorite_books" rows="2"><?php echo htmlspecialchars($profile_user['favorite_books'] ?? ''); ?></textarea>
                </div>
                <div class="info-row">
                    <label class="info-label">About Me:</label><br>
                    <textarea name="about_me" rows="4"><?php echo htmlspecialchars($profile_user['about_me'] ?? ''); ?></textarea>
                </div>
                <input type="submit" name="update_profile" value="Save Changes" class="btn">
            </form>
        </div>
        <?php else: ?>
        <div class="section">
            <h2>Personal Information</h2>
            <?php if (isset($profile_user['interests']) && $profile_user['interests']): ?>
            <div class="info-row">
                <span class="info-label">Interests:</span><br>
                <?php echo nl2br(htmlspecialchars($profile_user['interests'])); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($profile_user['favorite_music']) && $profile_user['favorite_music']): ?>
            <div class="info-row">
                <span class="info-label">Favorite Music:</span><br>
                <?php echo nl2br(htmlspecialchars($profile_user['favorite_music'])); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($profile_user['favorite_movies']) && $profile_user['favorite_movies']): ?>
            <div class="info-row">
                <span class="info-label">Favorite Movies:</span><br>
                <?php echo nl2br(htmlspecialchars($profile_user['favorite_movies'])); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($profile_user['favorite_books']) && $profile_user['favorite_books']): ?>
            <div class="info-row">
                <span class="info-label">Favorite Books:</span><br>
                <?php echo nl2br(htmlspecialchars($profile_user['favorite_books'])); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($profile_user['about_me']) && $profile_user['about_me']): ?>
            <div class="info-row">
                <span class="info-label">About Me:</span><br>
                <?php echo nl2br(htmlspecialchars($profile_user['about_me'])); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2><?php echo $is_own_profile ? 'Your' : htmlspecialchars($profile_user['first_name']) . "'s"; ?> Wall</h2>
            
            <?php if ($friendship && $friendship['status'] == 'accepted' || $is_own_profile): ?>
            <form method="POST">
                <textarea name="post_content" rows="3" placeholder="Write something..." required></textarea><br>
                <input type="submit" name="post_wall" value="Post" class="btn">
            </form>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <?php 
                if (mysqli_num_rows($wall_result) > 0) {
                    while ($post = mysqli_fetch_assoc($wall_result)): 
                ?>
                    <div class="post">
                        <div class="post-author">
                            <a href="profile.php?id=<?php echo $post['user_id']; ?>">
                                <?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?>
                            </a>
                        </div>
                        <div class="post-date"><?php echo date('F j, Y g:i a', strtotime($post['post_date'])); ?></div>
                        <div style="margin-top: 10px;">
                            <?php echo nl2br(htmlspecialchars($post['post_content'])); ?>
                        </div>
                    </div>
                <?php 
                    endwhile;
                } else {
                    echo "<p>No wall posts yet.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>