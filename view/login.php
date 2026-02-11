<?php
session_start();
include 'db.php';
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; 
    $pass = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($pass, $user['password'])) {
        if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
            $msg = "This account is currently inactive.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            header("Location: index.php");
            exit();
        }
    } else { 
        $msg = "Invalid email or password!"; 
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/style.css">
</head>
<body class="d-flex align-items-center justify-content-center">
    
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-house-door-fill"></i></a>
            <div class="navbar-nav ms-auto">
                <a href="register.php" class="btn btn-info btn-sm">Sign Up</a>
            </div>
        </div>
    </nav>

    <div class="glass-card p-4 mx-3" style="width: 100%; max-width: 400px; margin-top: 80px;">
        <h2 class="text-center mb-4">Login</h2>
        
        <?php if($msg): ?>
            <div class="alert alert-danger text-center" role="alert" style="display: block;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="form-label">Email Address</label>
                <div class="custom-input-wrapper">
                    <i class="bi bi-envelope custom-input-icon"></i>
                    <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="custom-input-wrapper">
                    <i class="bi bi-lock custom-input-icon"></i>
                    <input type="password" class="form-control" name="password" id="u_pass" placeholder="••••••••" required style="padding-right: 45px;">
                    <i class="bi bi-eye password-toggle" onclick="togglePassword('u_pass', this)"></i>
                </div>
                <?php if (isset($_GET['error']) && $_GET['error'] == 'please_login'): ?>
    <div class="alert alert-warning text-center">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        Please login first to access this page!
    </div>
<?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
            <p class="text-center mt-4 mb-0 text-muted">Don't have an account? <a href="register.php" class="fw-bold">Register now</a></p>
        </form>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>