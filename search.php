<?php
require_once 'config.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit();
}

$current_user = get_logged_in_user();
$search_results = [];

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = clean_input($_GET['q']);
    
    $search_query = "SELECT u.*, 
                     (SELECT status FROM friends 
                      WHERE (user_id = {$current_user['id']} AND friend_id = u.id)
                      OR (user_id = u.id AND friend_id = {$current_user['id']})) as friendship_status,
                     (SELECT id FROM friends 
                      WHERE (user_id = {$current_user['id']} AND friend_id = u.id)
                      OR (user_id = u.id AND friend_id = {$current_user['id']})) as friendship_id
                     FROM users u
                     WHERE (u.first_name LIKE '%$search_term%' 
                     OR u.last_name LIKE '%$search_term%'
                     OR u.email LIKE '%$search_term%')
                     AND u.id != {$current_user['id']}
                     LIMIT 50";
    
    $search_result = mysqli_query($conn, $search_query);
    
    while ($user = mysqli_fetch_assoc($search_result)) {
        $search_results[] = $user;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search - thefacebook</title>
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
        .search-box {
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            padding: 15px;
            margin-bottom: 20px;
        }
        .search-box input[type="text"] {
            padding: 5px 8px;
            width: 400px;
            border: 1px solid #ccc;
            font-size: 11px;
            font-family: Tahoma, Arial, sans-serif;
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
        }
        .btn-disabled {
            background-color: #999;
            color: white;
            padding: 6px 12px;
            border: 1px solid #777;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
            cursor: default;
        }
        .btn-pending {
            background-color: #6d84b4;
            color: white;
            padding: 6px 12px;
            border: 1px solid #29447e;
            font-size: 11px;
            text-decoration: none;
            display: inline-block;
            cursor: default;
        }
        .results-header {
            font-size: 13px;
            font-weight: bold;
            color: #3B5998;
            margin: 20px 0 10px 0;
        }
        .result {
            display: flex;
            gap: 15px;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #ccc;
            background-color: #f7f7f7;
            align-items: flex-start;
        }
        .result:hover {
            background-color: #f0f0f0;
        }
        .result-pic {
            width: 80px;
            height: 80px;
            border: 1px solid #ccc;
            object-fit: cover;
            flex-shrink: 0;
        }
        .result-info {
            flex: 1;
        }
        .result-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .result-name a {
            color: #3B5998;
            text-decoration: none;
        }
        .result-name a:hover {
            text-decoration: underline;
        }
        .result-details {
            color: #666;
            margin-top: 3px;
            font-size: 11px;
            line-height: 1.5;
        }
        .result-details span {
            display: block;
        }
        .result-actions {
            margin-top: 8px;
        }
        .no-results {
            color: #666;
            padding: 20px;
            text-align: center;
            background-color: #f7f7f7;
            border: 1px solid #ccc;
        }
        .search-tips {
            background-color: #e8f0fe;
            border: 1px solid #b3d4fc;
            padding: 15px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .search-tips h3 {
            color: #3B5998;
            font-size: 12px;
            margin-bottom: 8px;
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
            Search for People
        </div>
        
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="q" placeholder="Search by name or email..." 
                       value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                       autofocus>
                <input type="submit" value="Search" class="btn">
            </form>
        </div>
        
        <?php if (!isset($_GET['q']) || empty($_GET['q'])): ?>
        <div class="search-tips">
            <h3>Search Tips:</h3>
            • Enter a name, email address, or keyword to find people<br>
            • You can search for friends, classmates, or anyone at your school<br>
            • Click on a profile to view more details and add them as a friend
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['q']) && !empty($_GET['q'])): ?>
            <div class="results-header">
                Search Results for "<?php echo htmlspecialchars($_GET['q']); ?>"
                <?php if (count($search_results) > 0): ?>
                    (<?php echo count($search_results); ?> <?php echo count($search_results) == 1 ? 'person' : 'people'; ?> found)
                <?php endif; ?>
            </div>
            
            <?php if (count($search_results) > 0): ?>
                <?php foreach ($search_results as $user): ?>
                    <div class="result">
                        <div>
                            <img src="uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                 class="result-pic"
                                 onerror="this.src='https://via.placeholder.com/80x80/cccccc/666666?text=No+Photo'">
                        </div>
                        <div class="result-info">
                            <div class="result-name">
                                <a href="profile.php?id=<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </a>
                            </div>
                            <div class="result-details">
                                <span><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></span>
                                <?php if (!empty($user['relationship_status'])): ?>
                                <span><strong>Status:</strong> <?php echo htmlspecialchars($user['relationship_status']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($user['interests'])): ?>
                                <span><strong>Interests:</strong> <?php echo htmlspecialchars(substr($user['interests'], 0, 100)); ?><?php echo strlen($user['interests']) > 100 ? '...' : ''; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="result-actions">
                                <a href="profile.php?id=<?php echo $user['id']; ?>" class="btn">View Profile</a>
                                <?php if (empty($user['friendship_status'])): ?>
                                    <a href="add_friend.php?id=<?php echo $user['id']; ?>" class="btn">Add Friend</a>
                                <?php elseif ($user['friendship_status'] == 'pending'): ?>
                                    <span class="btn-pending">Request Pending</span>
                                <?php elseif ($user['friendship_status'] == 'accepted'): ?>
                                    <span class="btn-disabled">✓ Friends</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <strong>No results found for "<?php echo htmlspecialchars($_GET['q']); ?>"</strong><br><br>
                    Try searching with a different name or email address.
                </div>
            <?php endif; ?>
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