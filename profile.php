<?php
session_start();
require 'connection.php'; // Make sure this file establishes the database connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $updatedName = $_POST['name'];
    $updatedEmail = $_POST['email'];
    $newPassword = $_POST['password'];
    $userId = $_SESSION["user_id"]; // Fetch user ID from the session

    // Prepare the SQL query for updating user info
    $sql = "UPDATE users SET name = ?, email = ?";
    $params = [$updatedName, $updatedEmail];
    
    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql .= ", password = ?";
        $params[] = $hashedPassword;
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $userId;

    $stmt = $connection->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        // Update session data with the new values
        $_SESSION['user_name'] = $updatedName;
        $_SESSION['user_email'] = $updatedEmail;

        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated successfully!',
            'user' => [
                'name' => $updatedName,
                'email' => $updatedEmail,
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No changes detected or failed to update profile.']);
    }
    exit();
}

// Fetch user information to display in the profile page
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
<nav class="bg-green-600 shadow-lg">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <!-- Logo and Welcome text -->
        <h1 class="text-2xl font-bold text-white">Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
        
        <!-- Hamburger button (for small screens) -->
        <div class="md:hidden">
            <button id="navbar-toggle" class="text-white focus:outline-none">
                <svg class="h-6 w-6 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M3 5h18a1 1 0 010 2H3a1 1 0 010-2zm0 6h18a1 1 0 010 2H3a1 1 0 010-2zm0 6h18a1 1 0 010 2H3a1 1 0 010-2z" />
                </svg>
            </button>
        </div>

        <!-- Navbar links (hidden on small screens, shown on medium and larger screens) -->
        <div class="hidden md:flex space-x-4">
            <button onclick="showCreateListModal()" 
                    class="bg-white text-green-600 font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-50 transition duration-300 ease-in-out">
                Create New To-Do List
            </button>
            <a href="dashboard.php" 
               class="bg-green-700 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-800 transition duration-300 ease-in-out">
                Dashboard
            </a>
            <a href="profile.php" 
               class="bg-green-800 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-900 transition duration-300 ease-in-out">
                My Profile
            </a>
            <a href="logout.php" 
               class="bg-red-600 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out">
                Logout
            </a>
        </div>
    </div>

    <!-- Collapsible menu for small screens -->
    <div id="navbar-menu" class="hidden md:hidden bg-green-600 px-6 py-4 space-y-2">
        <button onclick="showCreateListModal()" 
                class="block w-full text-left bg-white text-green-600 font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-50 transition duration-300 ease-in-out">
            Create New To-Do List
        </button>
        <a href="dashboard.php" 
           class="block w-full text-left bg-green-700 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-800 transition duration-300 ease-in-out">
            Dashboard
        </a>
        <a href="profile.php" 
           class="block w-full text-left bg-green-800 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-green-900 transition duration-300 ease-in-out">
            My Profile
        </a>
        <a href="logout.php" 
           class="block w-full text-left bg-red-600 text-white font-semibold px-4 py-2 rounded-lg shadow-md hover:bg-red-700 transition duration-300 ease-in-out">
            Logout
        </a>
    </div>
</nav>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">User Profile Management</h1>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">View Profile</h2>
            <div id="profileInfo">
                <p class="mb-2"><strong>Name:</strong> <span id="displayName"><?php echo htmlspecialchars($user_name); ?></span></p>
                <p><strong>Email:</strong> <span id="displayEmail"><?php echo htmlspecialchars($user_email); ?></span></p>
                <p><strong>Password:</strong> <span id="displayPassword">********</span></p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-4">Edit Profile</h2>
            <form id="editProfileForm">
                <div class="mb-4">
                    <label for="inputName" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" id="inputName" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="inputEmail" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" id="inputEmail" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="inputPassword" class="block text-sm font-medium text-gray-700">New Password (leave blank if unchanged)</label>
                    <input type="password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" id="inputPassword" name="password">
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update Profile</button>
            </form>
        </div>
    </div>

    <script>
    // Script to toggle the visibility of the navbar menu on small screens
    document.getElementById('navbar-toggle').addEventListener('click', function() {
        const menu = document.getElementById('navbar-menu');
        menu.classList.toggle('hidden');
    });


    function showCreateListModal() {
        document.getElementById('createListModal').classList.remove('hidden');
    }
    $(document).ready(function() {
    $('#editProfileForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'profile.php',
            type: 'POST',
            data: $(this).serialize() + '&action=update_profile', // Append action parameter
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Update the displayed name and email on the page
                    $('#displayName').text(response.user.name);
                    $('#displayEmail').text(response.user.email);

                    // Update the welcome text in the navbar
                    $('h1:contains("Welcome,")').text('Welcome, ' + response.user.name);
                    
                    // Show success message
                    Swal.fire('Success', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message || 'Failed to update profile. Please try again.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'An error occurred while updating your profile.', 'error');
            }
        });
    });
});
    </script>
</body>
</html>