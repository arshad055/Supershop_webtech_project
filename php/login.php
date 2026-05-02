<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require __DIR__ . '/config.php'; 

$username = $password = "";
$usernameErr = $passwordErr = "";
$flashMsg = "";

if (isset($_GET['registered'])) {
    if ($_GET['registered'] === '1') {
        $flashMsg = "Registration complete. Please log in.";
    } elseif ($_GET['registered'] === 'employee') {
        $flashMsg = "Employee registration complete. Please log in.";
    }
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function clean($s){ return h(stripslashes(trim((string)$s))); }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
   
    if (empty($_POST["username"])) {
        $usernameErr = "Username is required";
    } else {
        $username = clean($_POST["username"]);
    }

  
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = trim((string)$_POST["password"]);
    }

    if ($usernameErr === "" && $passwordErr === "") {
        
        if ($username === 'admin' && $password === '123456') {
            session_regenerate_id(true);
            $_SESSION['user_id']  = 0;          
            $_SESSION['username'] = 'admin';

            $base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            header('Location: ' . $base . '/home.php');
            exit;
        }
       

        
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = (int)$row['id'];
                $_SESSION['username'] = (string)$row['username'];

               
                $empStmt = $conn->prepare("SELECT 1 FROM employees WHERE user_id=? LIMIT 1");
                $empStmt->bind_param("i", $row['id']);
                $empStmt->execute();
                $isEmployee = $empStmt->get_result()->num_rows > 0;

                $base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $next = $base . ($isEmployee ? '/employee_home.php' : '/dashboard.php');
                header('Location: ' . $next);
                exit;
            }
        }


        $passwordErr = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Page</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>

<div class="wrapper">
    <div class="login_box">
        <div class="login-header">
            <span>Login</span>
        </div>

        <?php if ($flashMsg): ?>
            <div style="margin:8px 0; padding:8px 10px; border:1px solid #bfe6cc; background:#e8fff0; color:#0a7a2f; border-radius:6px;">
                <?= h($flashMsg) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= h($_SERVER["PHP_SELF"]) ?>">
            <div class="input_box">
                <input type="text" name="username" class="input-field" value="<?= h($username) ?>">
                <label for="user" class="label">Username</label>
                <span style="color:red;"><?= h($usernameErr) ?></span>
            </div>

            <div class="input_box">
                <input type="password" name="password" class="input-field">
                <label for="pass" class="label">Password</label>
                <span style="color:red;"><?= h($passwordErr) ?></span>
            </div>

            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember">
                    <label for="remember"> Remember me</label>
                </div>
                <div class="forgot">
                    <a href="#">Forgot password?</a>
                </div>
            </div>

            <div class="input_box">
                <input type="submit" class="input-submit" value="Login">
            </div>
        </form>

        <div class="register">
           
            <span>Don't have an account? <a href="regustration.php">Register as customer</a></span>
            <span>Don't have an account? <a href="registrationem.php">Register as employee</a></span>
        </div>
    </div>
</div>

</body>
</html>
