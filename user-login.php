<?php
include 'db.php';
include 'navbar.php';

$error = '';
$success = '';
$email = '';
$signupName = '';
$signupEmail = '';
$activeMode = $_GET['mode'] ?? 'login';
$activeMode = in_array($activeMode, ['login', 'signup', 'admin'], true) ? $activeMode : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? 'login';

    if ($formType === 'signup') {
        $activeMode = 'signup';
        $signupName = trim($_POST['name'] ?? '');
        $signupEmail = trim($_POST['signup_email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($signupName === '' || $signupEmail === '' || $password === '' || $confirmPassword === '') {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($signupEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Password confirmation does not match.';
        } else {
            $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $checkStmt->bind_param('s', $signupEmail);
            $checkStmt->execute();
            $existing = $checkStmt->get_result();

            if ($existing && $existing->num_rows > 0) {
                $error = 'An account with this email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user';
                $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param('ssss', $signupName, $signupEmail, $hashedPassword, $role);

                if ($insertStmt->execute()) {
                    $success = 'Account created successfully. You can now log in.';
                    $activeMode = 'login';
                    $email = $signupEmail;
                    $signupName = '';
                    $signupEmail = '';
                } else {
                    $error = 'Failed to create account. Please try again.';
                }
            }
        }
    } elseif ($formType === 'admin') {
        $activeMode = 'admin';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $conn->prepare("SELECT user_id, name, email, password FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['admin_id'] = (int) $user['user_id'];
                    $_SESSION['admin_name'] = $user['name'];
                    header('Location: admin/dashboard.php');
                    exit;
                }
            }

            $error = 'Invalid admin email or password.';
        }
    } else {
        $activeMode = 'login';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ? AND role = 'user' LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = (int) $user['user_id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];

                    header('Location: planner.php');
                    exit;
                }
            }

            $error = 'Invalid email or password.';
        }
    }
}
?>

<div class="container auth-shell">
    <div class="auth-card content-box mx-auto">
        <div class="auth-grid">
            <div class="auth-showcase">
                <span class="auth-kicker">Plan better trips</span>
                <h1>Sign in once and keep every getaway in one place.</h1>
                <p>Save trip plans, revisit favorite destinations, and build a smoother tourist experience with TripZo.</p>
                <div class="auth-benefits">
                    <div><i class="bi bi-check2-circle"></i><span>Save your custom trip planner</span></div>
                    <div><i class="bi bi-check2-circle"></i><span>Track attractions you want to visit</span></div>
                    <div><i class="bi bi-check2-circle"></i><span>Return to your itinerary anytime</span></div>
                </div>
            </div>

            <div class="auth-panel">
                <div class="auth-panel-header">
                    <span class="badge-soft"><?php echo $activeMode === 'admin' ? 'Admin Access' : 'Member Access'; ?></span>
                    <div class="auth-mode-switch" role="tablist" aria-label="Choose account type">
                        <a class="auth-mode-pill<?php echo $activeMode === 'login' ? ' active' : ''; ?>" href="user-login.php">User Login</a>
                        <a class="auth-mode-pill<?php echo $activeMode === 'signup' ? ' active' : ''; ?>" href="user-login.php?mode=signup">Create Account</a>
                        <a class="auth-mode-pill<?php echo $activeMode === 'admin' ? ' active' : ''; ?>" href="user-login.php?mode=admin">Admin Login</a>
                    </div>
                    <h2><?php echo $activeMode === 'signup' ? 'Create your account' : ($activeMode === 'admin' ? 'Control panel access' : 'Welcome back'); ?></h2>
                    <p class="login-subtitle mb-0">
                        <?php echo $activeMode === 'signup'
                            ? 'Create your TripZo account to unlock saved trips and faster planning.'
                            : ($activeMode === 'admin'
                                ? 'Sign in to manage attractions, reviews, and content across the platform.'
                                : 'Log in to manage your saved places and continue planning your trip.'); ?>
                    </p>
                </div>

                <?php if ($error !== '') { ?>
                    <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>

                <?php if ($success !== '') { ?>
                    <div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
                <?php } ?>

                <?php if ($activeMode === 'signup') { ?>
                    <form method="POST" action="user-login.php?mode=signup" class="auth-form">
                        <input type="hidden" name="form_type" value="signup">

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($signupName); ?>" placeholder="Enter your full name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="signup_email" class="form-control" required value="<?php echo htmlspecialchars($signupEmail); ?>" autocomplete="email" placeholder="Enter your email">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required autocomplete="new-password" placeholder="Minimum 6 characters">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password" placeholder="Re-enter your password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Create Account</button>
                    </form>

                    <p class="text-center mt-4 mb-0 auth-switch-copy">
                        Already have an account?
                        <a href="user-login.php">Login here</a>
                    </p>
                <?php } elseif ($activeMode === 'admin') { ?>
                    <form method="POST" action="user-login.php?mode=admin" class="auth-form">
                        <input type="hidden" name="form_type" value="admin">

                        <div class="mb-3">
                            <label class="form-label">Admin Email</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>" autocomplete="username" placeholder="Enter admin email">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Enter your password">
                        </div>

                        <button type="submit" class="btn btn-dark w-100">Login to Admin Panel</button>
                    </form>

                    <p class="text-center mt-4 mb-0 auth-switch-copy">
                        Looking for your traveller account?
                        <a href="user-login.php">Back to user login</a>
                    </p>
                <?php } else { ?>
                    <form method="POST" action="user-login.php" class="auth-form">
                        <input type="hidden" name="form_type" value="login">

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>" autocomplete="username" placeholder="Enter your email">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Enter your password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <p class="text-center mt-4 mb-0 auth-switch-copy">
                        New to TripZo?
                        <a href="user-login.php?mode=signup">Create new account</a>
                    </p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
