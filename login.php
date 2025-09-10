<?php
require_once 'common/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$active_tab = 'login'; // 'login' or 'signup'

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- SIGN UP LOGIC ---
    if (isset($_POST['signup'])) {
        $active_tab = 'signup';
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['message'] = 'All fields are required.';
            $_SESSION['message_type'] = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = 'Invalid email format.';
            $_SESSION['message_type'] = 'error';
        } else {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $_SESSION['message'] = 'Username or email already taken.';
                $_SESSION['message_type'] = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Registration successful! Please login.';
                    $_SESSION['message_type'] = 'success';
                    $active_tab = 'login'; // Switch to login tab on success
                } else {
                    $_SESSION['message'] = 'An error occurred. Please try again.';
                    $_SESSION['message_type'] = 'error';
                }
            }
            $stmt->close();
        }
    }

    // --- LOGIN LOGIC ---
    if (isset($_POST['login'])) {
        $active_tab = 'login';
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $_SESSION['message'] = 'Username and password are required.';
            $_SESSION['message_type'] = 'error';
        } else {
            $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    header("Location: index.php");
                    exit();
                } else {
                    $_SESSION['message'] = 'Invalid credentials.';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Invalid credentials.';
                $_SESSION['message_type'] = 'error';
            }
            $stmt->close();
        }
    }
    // Redirect to self to prevent form resubmission
    header("Location: login.php?tab=" . $active_tab);
    exit();
}

// Set active tab from GET parameter
if (isset($_GET['tab']) && $_GET['tab'] === 'signup') {
    $active_tab = 'signup';
}
?>
<?php require 'common/header.php'; ?>

<div class="min-h-screen flex flex-col items-center justify-center">
    <h1 class="text-4xl font-bold text-yellow-400 mb-8">Gods Arena</h1>

    <div class="w-full max-w-md bg-gray-800 rounded-lg shadow-lg p-2">
        <!-- Tabs -->
        <div class="flex border-b border-gray-700">
            <button id="loginTab" onclick="switchTab('login')" class="flex-1 py-2 text-center font-semibold <?php echo $active_tab == 'login' ? 'text-yellow-400 border-b-2 border-yellow-400' : 'text-gray-400'; ?>">
                Login
            </button>
            <button id="signupTab" onclick="switchTab('signup')" class="flex-1 py-2 text-center font-semibold <?php echo $active_tab == 'signup' ? 'text-yellow-400 border-b-2 border-yellow-400' : 'text-gray-400'; ?>">
                Sign Up
            </button>
        </div>

        <!-- Login Form -->
        <div id="loginContent" class="p-6 <?php echo $active_tab == 'login' ? '' : 'hidden'; ?>">
            <form method="POST" action="login.php">
                <div class="mb-4">
                    <label for="login-username" class="block text-gray-300 text-sm font-bold mb-2">Username</label>
                    <input type="text" id="login-username" name="username" class="w-full bg-gray-700 text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-yellow-500" required>
                </div>
                <div class="mb-6">
                    <label for="login-password" class="block text-gray-300 text-sm font-bold mb-2">Password</label>
                    <input type="password" id="login-password" name="password" class="w-full bg-gray-700 text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-yellow-500" required>
                </div>
                <button type="submit" name="login" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded focus:outline-none">
                    Login
                </button>
            </form>
        </div>

        <!-- Sign Up Form -->
        <div id="signupContent" class="p-6 <?php echo $active_tab == 'signup' ? '' : 'hidden'; ?>">
            <form method="POST" action="login.php">
                <div class="mb-4">
                    <label for="signup-username" class="block text-gray-300 text-sm font-bold mb-2">Username</label>
                    <input type="text" id="signup-username" name="username" class="w-full bg-gray-700 text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-yellow-500" required>
                </div>
                <div class="mb-4">
                    <label for="signup-email" class="block text-gray-300 text-sm font-bold mb-2">Email</label>
                    <input type="email" id="signup-email" name="email" class="w-full bg-gray-700 text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-yellow-500" required>
                </div>
                <div class="mb-6">
                    <label for="signup-password" class="block text-gray-300 text-sm font-bold mb-2">Password</label>
                    <input type="password" id="signup-password" name="password" class="w-full bg-gray-700 text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-yellow-500" required>
                </div>
                <button type="submit" name="signup" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none">
                    Sign Up
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function switchTab(tab) {
        document.getElementById('loginContent').classList.toggle('hidden', tab !== 'login');
        document.getElementById('signupContent').classList.toggle('hidden', tab !== 'signup');
        document.getElementById('loginTab').classList.toggle('text-yellow-400', tab === 'login');
        document.getElementById('loginTab').classList.toggle('border-yellow-400', tab === 'login');
        document.getElementById('loginTab').classList.toggle('text-gray-400', tab !== 'login');
        document.getElementById('signupTab').classList.toggle('text-yellow-400', tab === 'signup');
        document.getElementById('signupTab').classList.toggle('border-yellow-400', tab === 'signup');
        document.getElementById('signupTab').classList.toggle('text-gray-400', tab !== 'signup');
    }
</script>

<?php require 'common/bottom.php'; ?>