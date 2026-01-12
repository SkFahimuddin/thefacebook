<?php
require_once 'config.php';

// If already logged in, redirect to home
if (is_logged_in()) {
    header("Location: home.php");
    exit();
}

$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $gender = clean_input($_POST['gender']);
    
    // Validate email (2004 version required .edu)
    if (!strpos($email, '.edu')) {
        $error = "You must have a .edu email address to register.";
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "This email is already registered.";
        } else {
            // Insert new user
            $hashed_password = md5($password);
            $insert_query = "INSERT INTO users (email, password, first_name, last_name, gender) 
                            VALUES ('$email', '$hashed_password', '$first_name', '$last_name', '$gender')";
            
            if (mysqli_query($conn, $insert_query)) {
                $success = "Registration successful! You can now log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = clean_input($_POST['login_email']);
    $password = md5($_POST['login_password']);
    
    $login_query = "SELECT id, first_name FROM users WHERE email = '$email' AND password = '$password'";
    $login_result = mysqli_query($conn, $login_query);
    
    if (mysqli_num_rows($login_result) == 1) {
        $user = mysqli_fetch_assoc($login_result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        header("Location: home.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Thefacebook</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Tahoma, Verdana, Arial, sans-serif;
            font-size: 11px;
            background-color: #e2e8f4;
        }
        .header-bar {
            background: linear-gradient(to bottom, #5975ba 0%, #3b5998 100%);
            padding: 5px 15px;
            border-bottom: 1px solid #29447e;
            max-width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-icon {
            width: 30px;
            height: 30px;
            background-color: white;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #3B5998;
            font-weight: bold;
        }
        .logo-text {
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        .header-nav {
            display: flex;
            gap: 20px;
        }
        .header-nav a {
            color: white;
            text-decoration: none;
            font-size: 11px;
        }
        .header-nav a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            margin-top: 15vh;
            margin-bottom: 15vh;
            background-color: white;
            border: 1px solid #29447e;
        }
        .main-content {
            display: flex;
        }
        .login-sidebar {
            width: 180px;
            background-color: #d8dfea;
            padding: 15px;
            border-right: 1px solid #c3cde0;
        }
        .login-box {
            background-color: white;
            border: 1px solid #b3b3b3;
            padding: 10px;
            margin-bottom: 15px;
        }
        .login-box label {
            display: block;
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 3px;
            font-size: 10px;
        }
        .login-box input[type="email"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 3px;
            border: 1px solid #999;
            font-size: 11px;
            box-sizing: border-box;
        }
        .btn-row {
            margin-top: 10px;
            display: flex;
            gap: 5px;
        }
        .btn {
            background-color: #5975ba;
            color: white;
            border: 1px solid #29447e;
            padding: 4px 8px;
            cursor: pointer;
            font-size: 10px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #4a6199;
        }
        .content-area {
            flex: 1;
            padding: 20px 25px;
        }
        .welcome-header {
            font-size: 18px;
            font-weight: bold;
            color: #3B5998;
            margin-bottom: 15px;
        }
        .welcome-text {
            line-height: 1.6;
            color: #333;
            margin-bottom: 15px;
        }
        .welcome-text strong {
            font-weight: bold;
        }
        .info-list {
            margin: 15px 0 15px 20px;
            line-height: 1.8;
        }
        .info-list li {
            color: #333;
        }
        .register-text {
            margin: 20px 0;
            color: #333;
            line-height: 1.6;
        }
        .register-buttons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
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
        .error {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    

    <div class="container">
    <div class="header-bar">
        <div class="logo-section">
            <div class="logo-text">thefacebook</div>
        </div>
        <div class="header-nav">
            <a href="#login"  onclick="scrollToLogin()">login</a>
            <a href="#register" onclick="showRegister()" >register</a>
            <a href="#about">about</a>
        </div>
    </div>
        <div class="main-content">
            <div class="login-sidebar">
                <div class="login-box">
                    <form method="POST" action="">
                        <label>Email:</label>
                        <input type="email" name="login_email" required>
                        
                        <label>Password:</label>
                        <input type="password" name="login_password" required>
                        
                        <div class="btn-row">
                            <input type="submit" name="login" value="login" class="btn">
                        </div>
                    </form>
                </div>
            </div>

            <div class="content-area">
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="welcome-header">[ Welcome to Thefacebook ]</div>
                
                <div class="welcome-text">
                    Thefacebook is an online directory that connects people through social networks at colleges.
                </div>

                <div class="welcome-text">
                    We have opened up Thefacebook for popular consumption at <strong>your university</strong>.
                </div>

                <div class="welcome-text">
                    You can use Thefacebook to:
                </div>

                <ul class="info-list">
                    <li>Search for people at your school</li>
                    <li>Find out who are in your classes</li>
                    <li>Look up your friends' friends</li>
                    <li>See a visualization of your social network</li>
                </ul>

                <div class="register-text">
                    To get started, click below to register. If you have already registered, you can log in.
                </div>

                <div class="register-buttons">
                    <button class="btn" onclick="showRegister()">register</button>
                    <button class="btn" onclick="scrollToLogin()">login</button>
                </div>

                <div id="registerForm" style="display:none; margin-top: 20px; padding: 15px; background-color: #f7f7f7; border: 1px solid #ddd;">
                    <h3 style="color: #3B5998; margin-bottom: 10px;">Register for Thefacebook</h3>
                    <p style="margin-bottom: 10px; font-size: 11px;">You must have a .edu email address to register.</p>
                    <form method="POST" action="">
                        <label style="display: block; margin-top: 8px; font-weight: bold;">First Name:</label>
                        <input type="text" name="first_name" required style="padding: 3px; border: 1px solid #999; width: 200px;">
                        
                        <label style="display: block; margin-top: 8px; font-weight: bold;">Last Name:</label>
                        <input type="text" name="last_name" required style="padding: 3px; border: 1px solid #999; width: 200px;">
                        
                        <label style="display: block; margin-top: 8px; font-weight: bold;">Email (.edu required):</label>
                        <input type="email" name="email" required style="padding: 3px; border: 1px solid #999; width: 200px;">
                        
                        <label style="display: block; margin-top: 8px; font-weight: bold;">Password:</label>
                        <input type="password" name="password" required style="padding: 3px; border: 1px solid #999; width: 200px;">
                        
                        <label style="display: block; margin-top: 8px; font-weight: bold;">Gender:</label>
                        <select name="gender" required style="padding: 3px; border: 1px solid #999;">
                            <option value="">Select...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        
                        <div style="margin-top: 15px;">
                            <input type="submit" name="register" value="register" class="btn">
                            <button type="button" class="btn" onclick="hideRegister()">cancel</button>
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
        </div>
    </div>

    <script>
        function showRegister() {
            document.getElementById('registerForm').style.display = 'block';
        }
        
        function hideRegister() {
            document.getElementById('registerForm').style.display = 'none';
        }
        
        function scrollToLogin() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            document.querySelector('.login-sidebar input[name="login_email"]').focus();
        }
    </script>
</body>
</html>