<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header('Location: index.php');
    exit();
}


$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_password = $_SESSION['user_password'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Update session data
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_password'] = $password;
   
    if ($password) {
       
        $_SESSION['user_password'];
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Profile updated successfully',
        'user' => [
            'name' => $name,
            'email' => $email,
            'password' => $password
        ]
    ]);
    exit();
}
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
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            <div>
                <a href="dashboard.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Dashboard</a>
                <a class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" href="profile.php">My Profile</a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">User Profile Management</h1>

        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">View Profile</h2>
            <div id="profileInfo">
                <p class="mb-2"><strong>Name:</strong> <span id="displayName"><?php echo htmlspecialchars($user_name); ?></span></p>
                <p><strong>Email:</strong> <span id="displayEmail"><?php echo htmlspecialchars($user_email); ?></span></p>
                <p><strong>Password:</strong> <span id="displayPassword"><?php echo htmlspecialchars($user_password); ?></span></p>
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
    $(document).ready(function() {
        $('#editProfileForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'profile.php',
                type: 'POST',
                data: $(this).serialize() + '&action=update_profile',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#displayName').text(response.user.name);
                        $('#displayEmail').text(response.user.email);
                        Swal.fire('Success', response.message, 'success');
                    } else {
                        Swal.fire('Error', 'Failed to update profile. Please try again.', 'error');
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