<?php
require_once 'common/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- HANDLE PROFILE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $upi_id = trim($_POST['upi_id']);

    if (empty($username) || empty($email)) {
        $_SESSION['message'] = 'Username and Email cannot be empty.';
        $_SESSION['message_type'] = 'error';
    } else {
        // Check for uniqueness of username/email if changed
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['message'] = 'Username or Email is already taken by another user.';
            $_SESSION['message_type'] = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, upi_id = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $upi_id, $user_id);
            if ($stmt->execute()) {
                $_SESSION['username'] = $username; // Update session username
                $_SESSION['message'] = 'Profile updated successfully.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to update profile.';
                $_SESSION['message_type'] = 'error';
            }
        }
        $stmt->close();
    }
    header("Location: profile.php");
    exit();
}

// --- HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['message'] = 'Incorrect current password.';
        $_SESSION['message_type'] = 'error';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['message'] = 'New passwords do not match.';
        $_SESSION['message_type'] = 'error';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['message'] = 'New password must be at least 6 characters long.';
        $_SESSION['message_type'] = 'error';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Password changed successfully.';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Failed to change password.';
            $_SESSION['message_type'] = 'error';
        }
        $stmt->close();
    }
    header("Location: profile.php");
    exit();
}


// --- HANDLE LOGOUT ---
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// --- FETCH USER DATA ---
$stmt = $conn->prepare("SELECT username, email, upi_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<?php require 'common/header.php'; ?>

<div class="space-y-6">
    <!-- Edit Profile Section -->
    <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
        <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Edit Profile</h3>
        <form method="POST" action="profile.php">
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-bold mb-2">UPI ID</label>
                <input type="text" name="upi_id" value="<?php echo htmlspecialchars($user['upi_id']); ?>" placeholder="your-upi@oksbi" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3">
            </div>
            <button type="submit" name="update_profile" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded">Save Changes</button>
        </form>
    </div>

    <!-- Change Password Section -->
    <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
        <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Change Password</h3>
        <form method="POST" action="profile.php">
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-bold mb-2">Current Password</label>
                <input type="password" name="current_password" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-bold mb-2">New Password</label>
                <input type="password" name="new_password" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-bold mb-2">Confirm New Password</label>
                <input type="password" name="confirm_password" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            </div>
            <button type="submit" name="change_password" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Update Password</button>
        </form>
    </div>

    <!-- Logout Button -->
    <div class="text-center">
        <a href="?action=logout" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
    </div>
</div>

<?php require 'common/bottom.php'; ?>