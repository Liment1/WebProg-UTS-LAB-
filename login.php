<?php
session_start();
require_once "connection.php";

$email ='';
$name = '';

if (isset($_POST["login"])) {
    $email = $_POST["email"];  
    $password = $_POST["password"];
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $connection->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_email"] = $email;
                header("Location: dashboard.php");
                exit();
        } else {
            $error = "Password does not match."; 
        }
    } else {
        $error = "Email does not match."; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-container">
        <h1>LOGIN</h1>
       <form action="login.php" method="post" novalidate>
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

            <div class="d-grid">
                <input type="submit" value="Login" name="login" class="btn btn-outline-info btn-lg">
            </div>
        </form>

        <div><p>Not registered yet? <a href="registration.php">Register Here</a></p></div>
    </div>

    <?php if (!empty($error)): ?>
    <script>
        Swal.fire({
            title: 'Error!',
            text: '<?php echo $error; ?>',
            icon: 'error',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
    <?php endif; ?>

</body>
</html>

