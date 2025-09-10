<?php
require_once 'common/header.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- HANDLE ADMIN PASSWORD UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    if ($new_password === $confirm_password && strlen($new_password) >= 6) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['admin_id']);
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Admin password updated successfully.';
            $_SESSION['message_type'] = 'success';
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = 'Passwords do not match or are too short.';
        $_SESSION['message_type'] = 'error';
    }
    header("Location: setting.php");
    exit();
}

// --- HANDLE UPI SETTINGS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_upi_settings'])) {
    $upi_id = trim($_POST['upi_id']);
    $qr_code_path = $_POST['existing_qr_code']; // Default to existing path

    // Handle file upload
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $target_file = $target_dir . basename($_FILES["qr_code"]["name"]);
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["qr_code"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["qr_code"]["tmp_name"], $target_file)) {
                $qr_code_path = $target_file;
            } else {
                $_SESSION['message'] = 'Sorry, there was an error uploading your file.';
                $_SESSION['message_type'] = 'error';
                header("Location: setting.php"); exit();
            }
        } else {
            $_SESSION['message'] = 'File is not an image.';
            $_SESSION['message_type'] = 'error';
            header("Location: setting.php"); exit();
        }
    }

    $stmt = $conn->prepare("UPDATE settings SET upi_id = ?, qr_code_path = ? WHERE id = 1");
    $stmt->bind_param("ss", $upi_id, $qr_code_path);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'UPI settings updated successfully.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to update UPI settings.';
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();
    header("Location: setting.php");
    exit();
}


// Fetch current settings
$settings = $conn->query("SELECT * FROM settings WHERE id = 1")->fetch_assoc();
?>

<div class="space-y-6">
    <!-- UPI & QR Code Settings -->
    <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
        <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Deposit Settings</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="existing_qr_code" value="<?php echo htmlspecialchars($settings['qr_code_path']); ?>">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Your UPI ID</label>
                <input type="text" name="upi_id" value="<?php echo htmlspecialchars($settings['upi_id']); ?>" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Upload UPI QR Code Image</label>
                <input type="file" name="qr_code" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-500 file:text-gray-900 hover:file:bg-yellow-600">
                <?php if ($settings['qr_code_path']): ?>
                    <p class="text-xs text-gray-400 mt-2">Current: <a href="<?php echo htmlspecialchars($settings['qr_code_path']); ?>" target="_blank" class="underline"><?php echo basename($settings['qr_code_path']); ?></a></p>
                <?php endif; ?>
            </div>
            <button type="submit" name="update_upi_settings" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded">Save UPI Settings</button>
        </form>
    </div>

    <!-- Admin Password Settings -->
    <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
        <h3 class="text-lg font-semibold mb-4 border-b border-gray-700 pb-2">Change Admin Password</h3>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">New Password</label>
                <input type="password" name="new_password" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Confirm New Password</label>
                <input type="password" name="confirm_password" class="w-full bg-gray-700 border border-gray-600 rounded py-2 px-3" required>
            </div>
            <button type="submit" name="update_admin_password" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Update Password</button>
        </form>
    </div>
</div>

<?php require_once 'common/bottom.php'; ?>