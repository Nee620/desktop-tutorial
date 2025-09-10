# Welcome to GitHub Desktop!

This is your README. READMEs are where you can communicate what your project is and how to use it.

Write your name on line 6, save it, and then head back to GitHub Desktop.
Gods Arena
You are an expert full-stack developer. Your task is to generate a fully working Tournament Web App called "Gods Arena", using ONLY the following technologies:
✅ HTML
✅ Tailwind CSS
✅ JavaScript (for basic UI/UX only, NO AJAX)
✅ PHP
✅ MySQL DB
✅ Font Awesome (only for icons)

NO external frameworks like React, Vue, Laravel, Bootstrap, jQuery, or AJAX should be used. All actions should be handled through traditional PHP form submissions and page reloads. The design should resemble a real mobile application—user-friendly, responsive, and clean UI. All text selection, right-click, and zoom in/out must be disabled using JavaScript.

✅ APP STRUCTURE OVERVIEW:
📁 Root Folder
┣ 📁 common
┃ ┣ header.php
┃ ┣ bottom.php
┃ ┗ config.php
┣ 📁 admin
┃ ┣ login.php
┃ ┣ index.php
┃ ┣ tournament.php
┃ ┣ manage_tournament.php
┃ ┣ user.php
┃ ┣ setting.php
┃ ┣ 📁 common
┃ ┃ ┣ header.php
┃ ┃ ┗ bottom.php
┣ login.php
┣ index.php
┣ my_tournaments.php
┣ wallet.php
┣ profile.php
┣ install.php
┣ update_database.php (for safe schema migration)
┗ .htaccess (optional)

✅ COMMON INCLUSIONS (User & Admin Panel)
config.php – one centralized DB connection (host: 127.0.0.1, user: root, pass: root).

header.php – contains the top navigation, "Gods Arena" logo, and user's wallet balance.

bottom.php – fixed bottom navigation with icons (Home, My Tournaments, Wallet, Profile).
All UI components must use Tailwind classes only, and Font Awesome for icons. Use a modern dark theme.

USER PANEL – Pages Design & Functionality
1. login.php
Design: Two tabs – Login and Sign Up in the same file.

Fields: Username, Password | Username, Email, Password (for signup).

Functionality: Use standard PHP form submission. Show errors/success messages on the same page after reload. On success, redirect to index.php.

2. index.php (Homepage)
Display: Show a list of all "Upcoming" tournaments in a card grid format.

Card Details: Tournament Title, Game Name, Type, Match Time, Entry Fee, Prize Pool.

Joining Logic: Each card must have a "Join Now" button.

A "Slots Filled" bar will show the progress (e.g., 12/48 Slots Filled).

If all slots are filled, the button will be replaced with the text "Tournament Full".

Clicking "Join Now" opens a modal to handle the registration process.

Modal Functionality:

The modal displays a grid of all available and booked slots for the tournament. Users can click on an available slot number to select it.

After selecting a slot, new fields appear based on the tournament type:

Solo: A form to enter the user's In-game Name and UID.

Duo/Squad: A form to enter a Team Name.

A "Confirm & Pay" button submits the form. The PHP logic at the top of index.php will handle the joining process: check balance, deduct fee, and add the user to the participants and teams tables with the selected slot, team name, and in-game details. Show a success/error message after the page reloads.

3. my_tournaments.php
Display: Use a two-tab layout.

"Upcoming/Live" tab: Shows tournaments the user has joined that are not yet completed, along with their assigned Slot Number and Team Name. If a tournament is live, it displays the Room ID and Password.

"Completed" tab: Shows a history of all tournaments the user has played, along with their results (e.g., "Winner", "Participated").

4. wallet.php
Display: Show current wallet balance in a large, prominent card.

Functionality: Show a transaction history list. Include "Add Money" and "Withdraw" buttons. These buttons will open modals for manual UPI deposits and withdrawals.

5. profile.php
Display: Show and Edit: Username, Email, and UPI ID (new field).

Functionality: Change password section. Logout button.

⚙ ADMIN PANEL – Pages Design & Functions
1. login.php
Functionality: Simple, secure login page for the admin using a standard PHP form.

2. index.php (Admin Dashboard)
Design: Mobile-app style Admin Dashboard with a clean, modern UI.

Stats: Show quick stats using small Tailwind grid cards: Total Users, Total Tournaments, Total Prize Distributed, Total Revenue (Commission).

Actions: Include a quick-action button: "Create New Tournament".

3. tournament.php
Form: A standard PHP form to add a new tournament.

Fields: Title, Game Name, Type (Solo/Duo/Squad), Number of Slots (up to 48), Entry Fee, Prize Pool, Match Time, Commission Percentage, and a "Details Space" (text area) for rules and info.

Functionality: On submission, the page reloads and shows a success/error message. Below the form, show a list of all created tournaments with options to Edit/Delete.

4. manage_tournament.php
Functionality: This is the core management page for a single tournament.

Display a list of all users who have joined, including their Team Name and Slot Number.

A form to enter/update the Room ID and Room Password.

A form with a dropdown menu of all participants to select the winner.

A button "Declare Winner & Distribute Prize". When submitted, the PHP logic will add the prize_pool amount to the winner's wallet, change the tournament status to 'Completed', and show a success message after reload.

5. user.php
Display: List all registered users with their current wallet balance and saved UPI ID.

Functionality: Option to view a user's match history or block the user.

6. settings.php
Functionality: Update admin info/password using a standard PHP form. Add a new section to allow the admin to upload a QR code image and set their UPI ID for deposits.

7. deposit_requests.php
Functionality: A new page for the admin to view all pending deposit requests. Each request shows the username, amount, and the user's UPI Transaction ID.

Actions: The admin can "Approve" or "Reject" each request. Approving a request adds the amount to the user's wallet.

8. withdrawal_requests.php
Functionality: A new page for the admin to view all pending withdrawal requests. Each request shows the username, amount, and the user's saved UPI ID.

Actions: The admin can mark a request as "Completed" after manually sending the money to the user.

✨ FEATURES TO IMPLEMENT:
All actions are handled by traditional PHP form submissions (POST/GET).

install.php must:

Create the database and all required tables.

Insert default admin credentials (admin/admin123).

Redirect to login.php after successful installation.

update_database.php must:

Safely add new tables (teams, deposits, withdrawals, settings) and columns (type, total_slots, details, upi_id) to the existing database using CREATE TABLE IF NOT EXISTS and ALTER TABLE.

Display a confirmation message on success and a clear error message on failure.

📊 DATABASE SCHEMA
users (id, username, email, password, wallet_balance, upi_id, created_at)

admin (id, username, password)

settings (id, upi_id, qr_code_path)

tournaments (id, title, game_name, type, total_slots, entry_fee, prize_pool, match_time, details, room_id, room_password, status, created_at)

teams (id, tournament_id, team_name, slot_number)

participants (id, user_id, tournament_id, team_id, in_game_name, in_game_uid)

deposits (id, user_id, amount, transaction_id, status ENUM('Pending', 'Completed', 'Rejected'))

withdrawals (id, user_id, amount, status ENUM('Pending', 'Completed', 'Rejected'))

transactions (id, user_id, amount, type, description, created_at)

🔒 SECURITY & USER EXPERIENCE
Disable right-click, text selection, and zoom using JS.

Use overflow-hidden, select-none, and custom JS for a native app feel.

Validate all forms on the server-side (PHP).

Use PHP sessions for login tracking.

Use prepared statements in PHP to prevent SQL injection.

Consistent dark theme, color scheme, and typography across the app.

All pricing/wallet values should be displayed in Indian Rupees (₹) format.

✅ FINAL DELIVERABLE:
Generate all required code files and folders as described above. For each file, clearly mention:

The filename

The folder/directory where it should be saved

The complete source code/content of the file

All PHP files with integrated logic.

The complete SQL schema inside install.php and the safe migration script in update_database.php.

The project should be a single folder, ready to be uploaded and run on a localhost or hosting server.