<?php
$role = $_POST['role'] ?? 'user';  

if ($role === 'admin') {
    echo "Selamat datang Admin!";
} else {
    echo "Selamat datang User!";
}
?>
