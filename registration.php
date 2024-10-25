<?php

$fullName = $email = "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-container">
        <?php
        if (isset($_POST["submit"])) {
            $fullName = $_POST["fullname"];
            $email = $_POST["email"];
            $password = $_POST["password"];
            $passwordRepeat = $_POST["repeat_password"];
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $errors = array();
            
            if (empty($fullName) || empty($email) || empty($password) || empty($passwordRepeat)) {
                array_push($errors, "All fields are required");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "Email is not valid");
            }
            if (strlen($password) < 8) {
                array_push($errors, "Password must be at least 8 characters long");
            }
            if ($password !== $passwordRepeat) {
                array_push($errors, "Passwords do not match");
            }

            require_once "connection.php";

            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$email]);
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                array_push($errors, "Email already exists!");
            }

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    echo "<script>
                        Swal.fire({
                            title: 'Error!',
                            text: '$error',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    </script>";
                }
            } else {
                $sql = "INSERT INTO users (id, name, email, password) VALUES (?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
            
                $checkid = $connection->query("SELECT MAX(id) AS id FROM users");
                $max = $checkid->fetch(PDO::FETCH_ASSOC);
                $nextid= 0;
                if ($max) {
                    $lastId = $max['id']; 
                    $numericPart = (int)substr($lastId, 1); 
                    $nextNumericPart = $numericPart + 1; 
                    $nextId = 'U' . str_pad($nextNumericPart, 4, '0', STR_PAD_LEFT); 
                } else {
                    $nextId = 'U0001';
                }

                $executeStmt = $stmt->execute([$nextId, $fullName, $email, $passwordHash]);

                if ($executeStmt) {
                    echo "<script>
                        Swal.fire({
                            title: 'Success!',
                            text: 'You are registered successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = 'login.php';
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            title: 'Error!',
                            text: 'Something went wrong, please try again.',
                            icon: 'error',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    </script>";
                }
            }
        }

        $emailError = $passwordError = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $emailError = 'Please enter a valid email address';
            }
        
            if (empty($_POST['password'])) {
                $passwordError = 'Please enter a password';
            } elseif (strlen($_POST['password']) < 8) {
                $passwordError = 'Password must be at least 8 characters long';
            }
        
            if (empty($_POST['repeat_password'])) {
                $repeatPasswordError = 'Please confirm your password';
            } elseif ($_POST['password'] !== $_POST['repeat_password']) {
                $repeatPasswordError = 'Passwords do not match';
            }
        }
        
?>
        <h1 class="mb-3">REGISTER</h1>
        <form action="registration.php" method="post" novalidate>
            <div class="form-floating mb-3">
                <input type="text" class="form-control bg-transparent text-light <?php echo !empty($fullNameError) ? 'is-invalid' : ''; ?>" id="fullname" name="fullname" placeholder="Full Name" value="<?php echo htmlspecialchars($fullName); ?>">
                <label for="fullname">Full Name</label>
                <?php if (!empty($fullNameError)): ?>
                    <div class="invalid-feedback">
                        <?php echo $fullNameError; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control bg-transparent text-light <?php echo !empty($emailError) ? 'is-invalid' : ''; ?>" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
                <label for="email">Email</label>
                <?php if (!empty($emailError)): ?>
                    <div class="invalid-feedback">
                        <?php echo $emailError; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control bg-transparent text-light <?php echo !empty($passwordError) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="Password">
                <label for="password">Password</label>
                <?php if (!empty($passwordError)): ?>
                    <div class="invalid-feedback">
                        <?php echo $passwordError; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control bg-transparent text-light <?php echo !empty($repeatPasswordError) ? 'is-invalid' : ''; ?>" id="repeat_password" name="repeat_password" placeholder="Repeat Password">
                <label for="repeat_password">Repeat Password</label>
                <?php if (!empty($repeatPasswordError)): ?>
                    <div class="invalid-feedback">
                        <?php echo $repeatPasswordError; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-grid">
                <input type="submit" class="btn btn-outline-info btn-lg" value="Register" name="submit">
            </div>
        </form>



        <div>
            <p>Already Registered? <a href="login.php">Login Here</a></p>
        </div>
    </div>
</body>
</html>
