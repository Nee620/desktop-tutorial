<?php
require_once 'common/header.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['message'] = 'All fields are required.';
        $_SESSION['message_type'] = 'error';
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($admin = $result->fetch_assoc()) {
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                header("Location: index.php");
                exit();
            }
        }
        $_SESSION['message'] = 'Invalid credentials.';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: login.php");
    exit();
}
?>
<div class="min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm bg-gray-800 rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold text-center text-yellow-400 mb-6">Admin Login</h2>
        <form method="POST" action="login.php">
            <div class="mb-4">
                <label for="username" class="block text-gray-300 text-sm font-bold mb-2">Username</label>
                <input type="text" id="username" name="username" class="w-full bg-gray-700 text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-yellow-500" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-300 text-sm font-bold mb-2">Password</label>
                <input type="password" id="password" name="password" class="w-full bg-gray-700 text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-yellow-500" required>
            </div>
            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded focus:outline-none">
                Login
            </button>
        </form>
    </div>
</div>
<?php require_once 'common/bottom.php'; ?>