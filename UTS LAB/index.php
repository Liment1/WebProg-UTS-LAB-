<?php
session_start();

if (isset($_SESSION['user_email'])) {
    header("Location: profile.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'signin') {
           
            if (isset($_SESSION['user_email']) && $_SESSION['user_email'] === $_POST['email'] && isset($_SESSION['user_password']) && $_SESSION['user_password'] === $_POST['password']) {
                $_SESSION['user_name'] = explode('@', $_POST['email'])[0]; 
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
                exit();
            }
        } elseif ($_POST['action'] == 'signup') {
           
            $_SESSION['user_email'] = $_POST['email'];
            $_SESSION['user_name'] = $_POST['name'];
            $_SESSION['user_password'] = $_POST['password']; 
            echo json_encode(['success' => true]);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In/Sign Up Page</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: "Outfit", sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url(gambar1.jpg) no-repeat;
            background-size: cover;
            background-position: center;
        }

        .login-container {
            width: 380px;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, .2);
            backdrop-filter: blur(9px);
            color: white;
            border-radius: 14px;
            padding: 30px 40px;
        }

        .login-container h1 {
            font-size: 39px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .social-icons {
            text-align: center;
            margin-bottom: 20px;
        }

        .social-icons .icon {
            display: inline-block;
            font-size: 20px;
            color: white;
            margin: 0 10px;
            border: 2px solid rgba(255, 255, 255, .2);
            border-radius: 16px;
            padding: 7px 10px;
            transition: color 0.3s ease;
        }

        .social-icons .icon:hover {
            color: #c2c2c2;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        .form-container {
            padding: 40px;
            transition: all 0.3s ease;
        }

        .form-message-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1rem;
        }

        input,
        button {
            width: 100%;
            height: 100%;
            height: 50px;
            margin: 10px 0;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, .2);
            border-radius: 60px;
            font-size: 15px;
            color: white;
            text-indent: 15px;
        }

        input::placeholder {
            color: white;
            text-indent: 15px;
        }

        input:focus,
        button:focus {
            border-color: transparent;
        }

        button {
            background-color: #02a152;
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.4s ease ease-in-out;
        }

        button:hover {
            background-color: #45a049;
        }

        a {
            text-align: center;
            text-decoration: underline;
            color: white;
            transition: 0.4s ease-in-out;
        }

        a:hover {
            color: white;
            text-decoration: none;
        }

        .hidden {
            display: none;
        }

    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form" id="loginForm">
            <h1>Sign In</h1>
            <div class="social-icons">
                <a href="#" class="icon"><i class='bx bxl-google-plus'></i></a>
                <a href="#" class="icon"><i class='bx bxl-facebook'></i></a>
                <a href="#" class="icon"><i class='bx bxl-github'></i></a>
                <a href="#" class="icon"><i class='bx bxl-linkedin'></i></a>
            </div>

            <div class="form-message-container">
                <span>Or use your email and password</span>
            </div>

            <form id="signinForm">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Sign In</button>
                <a href="#">Forgot Your Password?</a>
            </form>
            <p>Don't have an account? <a href="#" id="showSignup">Sign Up</a></p>
        </div>

        <div class="signup-form hidden" id="signupForm">
            <h1>Create An Account</h1>
            <div class="social-icons">
                <a href="#" class="icon"><i class='bx bxl-google-plus'></i></a>
                <a href="#" class="icon"><i class='bx bxl-facebook'></i></a>
                <a href="#" class="icon"><i class='bx bxl-github'></i></a>
                <a href="#" class="icon"><i class='bx bxl-linkedin'></i></a>
            </div>
            <div class="form-message-container">
                <span>Or use your email for registration</span>
            </div>

            <form id="signupFormElement">
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Sign Up</button>
            </form>
            <p>Already have an account? <a href="#" id="showLogin">Sign In</a></p>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');
        const showSignupBtn = document.getElementById('showSignup');
        const showLoginBtn = document.getElementById('showLogin');

        showSignupBtn.addEventListener('click', () => {
            loginForm.classList.add('hidden');
            signupForm.classList.remove('hidden');
        });

        showLoginBtn.addEventListener('click', () => {
            loginForm.classList.remove('hidden');
            signupForm.classList.add('hidden');
        });

        document.getElementById('signinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'signin');

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'You have successfully signed in!',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#02a152',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = 'profile.php'; 
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Invalid email or password.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        });

        document.getElementById('signupFormElement').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'signup');

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Your account has been created successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#02a152'
                    }).then(() => {
                        window.location.href = 'dashboard.php'; 
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        });
    </script>
</body>
</html>
