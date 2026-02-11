<?php
include 'db.php';
$msg = "";
$msg_type = "";

$user = $age = $email = $phone = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username']; 
    $email = $_POST['email'];
    $pass = $_POST['password']; 
    $conf = $_POST['confirm_password'];

    // Validation
    if (strlen($pass) < 6) {
        $msg = "Error: Password must be at least 6 characters!";
        $msg_type = "error";
    } elseif ($pass !== $conf) {
        $msg = "Error: Passwords do not match!";
        $msg_type = "error";
    } else {
        // Prepare statement to check email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $msg = "Error: Email is already registered!";
            $msg_type = "error";
        } else {
            // Insert new user
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, 'user', 1)");
            $stmt->bind_param("sss", $user, $email, $hashed);
            
            if ($stmt->execute()) { 
                header("Location: login.php"); 
                exit();
            } else {
                $msg = "Error creating account. Please try again.";
                $msg_type = "error";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <!-- Bootstrap 5 CSS (LTR) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="bi bi-house-door-fill"></i> </a>
        <div class="navbar-nav ms-auto">
            <a href="index.php" class="nav-link">Home</a>
            <a href="login.php" class="btn btn-info btn-sm ms-2">Login</a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="glass-card p-4">
                <h2 class="text-center mb-4">Create New Account</h2>
                
                <?php if($msg): ?>
                    <div class="alert alert-<?php echo ($msg_type == 'error' ? 'danger' : 'success'); ?> text-center" role="alert" style="display: block;">
                        <i class="bi bi-<?php echo ($msg_type == 'error' ? 'exclamation-triangle-fill' : 'check-circle-fill'); ?> me-2"></i> <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <div id="js-alert" class="alert alert-danger text-center" style="display: none;"></div>

                <form id="regForm" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <div class="custom-input-wrapper">
                            <i class="bi bi-person custom-input-icon"></i>
                            <input type="text" class="form-control" name="username" id="u_name" value="<?php echo htmlspecialchars($user); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="custom-input-wrapper">
                            <i class="bi bi-envelope custom-input-icon"></i>
                            <input type="email" class="form-control" name="email" id="u_email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="custom-input-wrapper">
                            <i class="bi bi-lock custom-input-icon"></i>
                            <input type="password" class="form-control" name="password" id="u_pass" placeholder="At least 6 characters" required style="padding-right: 45px;">
                            <i class="bi bi-eye password-toggle" onclick="togglePassword('u_pass', this)"></i>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <div class="custom-input-wrapper">
                            <i class="bi bi-lock-fill custom-input-icon"></i>
                            <input type="password" class="form-control" name="confirm_password" id="u_conf" required style="padding-right: 45px;">
                            <i class="bi bi-eye password-toggle" onclick="togglePassword('u_conf', this)"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Create Account</button>
                    <div class="text-center mt-4">
                        <a href="login.php" class="text-white-50 text-decoration-none">Already have an account? <span class="text-white fw-bold">Login</span></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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

    document.getElementById('regForm').onsubmit = function(e) {
        const pass = document.getElementById('u_pass').value;
        const conf = document.getElementById('u_conf').value;
        const alertBox = document.getElementById('js-alert');
        
        let error = "";

        if (pass.length < 6) {
            error = "⚠️ Password must be at least 6 characters!";
        } else if (pass !== conf) {
            error = "⚠️ Passwords do not match!";
        }

        if (error) {
            e.preventDefault(); 
            alertBox.innerText = error;
            alertBox.style.display = "block";
            return false;
        }
    };
</script>
</body>
</html>