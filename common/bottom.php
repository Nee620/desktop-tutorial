    </main>

    <?php if (basename($_SERVER['PHP_SELF']) != 'login.php' && isset($_SESSION['user_id'])):
        $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <nav class="bg-gray-800 p-2 fixed bottom-0 w-full flex justify-around border-t border-gray-700">
        <a href="index.php" class="flex flex-col items-center <?php echo $current_page == 'index.php' ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/4">
            <i class="fas fa-home fa-lg"></i>
            <span class="text-xs mt-1">Home</span>
        </a>
        <a href="my_tournaments.php" class="flex flex-col items-center <?php echo $current_page == 'my_tournaments.php' ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/4">
            <i class="fas fa-trophy fa-lg"></i>
            <span class="text-xs mt-1">My Tournaments</span>
        </a>
        <a href="wallet.php" class="flex flex-col items-center <?php echo $current_page == 'wallet.php' ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/4">
            <i class="fas fa-wallet fa-lg"></i>
            <span class="text-xs mt-1">Wallet</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center <?php echo $current_page == 'profile.php' ? 'text-yellow-400' : 'text-gray-400'; ?> hover:text-yellow-400 w-1/4">
            <i class="fas fa-user fa-lg"></i>
            <span class="text-xs mt-1">Profile</span>
        </a>
    </nav>
    <?php endif; ?>

    <script>
        document.addEventListener('contextmenu', event => event.preventDefault());
        document.addEventListener('keydown', function (event) {
          if (event.ctrlKey === true && (event.key === '+' || event.key === '-' || event.key === '0')) {
            event.preventDefault();
          }
        });
        window.addEventListener('wheel', function(event){
          if(event.ctrlKey === true){
            event.preventDefault();
          }
        }, { passive: false });
    </script>
</body>
</html>