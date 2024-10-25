<?php

try {
    $connection = new PDO("mysql:host=localhost;dbname=evef9533_TodoList", "evef9533_admin123", "_z9K7Nrih7acMEu");
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

?>