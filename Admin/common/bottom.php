    </main>
    <?php if (basename($_SERVER['PHP_SELF']) != 'login.php' && isset($_SESSION['admin_id'])):
        $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <nav class="bg-gray-800 p-2 fixed bottom-0 w-full flex justify-around border-t border-gray-700">
        <a href="index.php" class="flex flex-col items-center <?php echo $current_page == 'index.php' ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/5 text-center">
            <i class="fas fa-tachometer-alt fa-lg"></i>
            <span class="text-xs mt-1">Dashboard</span>
        </a>
        <a href="tournament.php" class="flex flex-col items-center <?php echo in_array($current_page, ['tournament.php', 'manage_tournament.php']) ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/5 text-center">
            <i class="fas fa-trophy fa-lg"></i>
            <span class="text-xs mt-1">Tournaments</span>
        </a>
        <a href="user.php" class="flex flex-col items-center <?php echo $current_page == 'user.php' ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/5 text-center">
            <i class="fas fa-users fa-lg"></i>
            <span class="text-xs mt-1">Users</span>
        </a>
         <a href="deposit_requests.php" class="flex flex-col items-center <?php echo $current_page == 'deposit_requests.php' ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/5 text-center">
            <i class="fas fa-money-check-alt fa-lg"></i>
            <span class="text-xs mt-1">Deposits</span>
        </a>
        <a href="setting.php" class="flex flex-col items-center <?php echo $current_page == 'setting.php' ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/5 text-center">
            <i class="fas fa-cog fa-lg"></i>
            <span class="text-xs mt-1">Settings</span>
        </a>
    </nav>
    <?php endif; ?>
</body>
</html>