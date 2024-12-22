<?php
// Start session
session_start();

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=itthink', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error_message = "";
$success_message = "";

// Login
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Correct table name to 'utilisateurs'
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user'] = [
            'username' => $user['username'],
            'role' => $user['role'],
            'id_user'=> $user['id_user']
        ];

        // Check if the user is a freelancer
        if ($user['role'] === 'freelancer') {
            // Check if the freelancer already exists in the Freelancers table
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Freelancers WHERE id_user = ?");
            $checkStmt->execute([$user['id_user']]);
            $freelancer_exists = $checkStmt->fetchColumn();

            if (!$freelancer_exists) {
                // Automatically create an entry in the Freelancers table
                $insertStmt = $pdo->prepare("INSERT INTO Freelancers (freelancer_name, skills, id_user) VALUES (?, ?, ?)");
                $insertStmt->execute([$user['username'], $user['skills'], $user['id_user']]);
            }
        }

        // Redirect based on role
        if ($user['role'] == 'admin') {
            header('Location: admin.php');
        } elseif ($user['role'] == 'freelancer') {
            header('Location: freelancer.php');
        } elseif ($user['role'] == 'user') {
            header('Location: user.php');
        }
        exit;
    } else {
        $error_message = "Invalid email or password.";
    }
}

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $skills = ($role === 'freelancer') ? trim($_POST['skills']) : null; // Capture skills only for freelancers

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } 
    // Validate passwords match
    elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } 
    else {
        // Check if the email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        $email_exists = $stmt->fetchColumn() > 0;

        if ($email_exists) {
            $error_message = "Error: Email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert new user into the utilisateurs table
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs (username, password, email, role, skills) 
                VALUES (?, ?, ?, ?, ?)
            ");
            try {
                $stmt->execute([$username, $hashed_password, $email, $role, $skills]);
                $success_message = "Registration successful. Please log in.";
            } catch (Exception $e) {
                $error_message = "An error occurred during registration: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hidden { display: none; }
    </style>
</head>
<body class="flex justify-center items-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-6">
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4 text-center">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div id="success-message" class="bg-green-100 text-green-700 p-2 rounded mb-4 text-center">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div id="login-form" class="">
            <h2 class="text-xl font-bold mb-4 text-center">Login</h2>
            <form method="POST">
                <input type="email" name="email" placeholder="Email" class="w-full mb-4 p-2 border rounded" required>
                <input type="password" name="password" placeholder="Password" class="w-full mb-4 p-2 border rounded" required>
                <button type="submit" name="login" class="w-full bg-blue-500 text-white py-2 rounded">Login</button>
            </form>
            <p class="mt-4 text-center">Don't have an account? <button id="show-register" class="text-blue-500 underline">Sign Up</button></p>
        </div>

        <div id="register-form" class="hidden">
            <h2 class="text-xl font-bold mb-4 text-center">Register</h2>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" class="w-full mb-4 p-2 border rounded" required>
                <input type="email" name="email" placeholder="Email" class="w-full mb-4 p-2 border rounded" required>
                <input type="password" name="password" placeholder="Password" class="w-full mb-4 p-2 border rounded" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" class="w-full mb-4 p-2 border rounded" required>
                <select id="role-select" name="role" class="w-full mb-4 p-2 border rounded" required>
                    <option value="user">User</option>
                    <option value="freelancer">Freelancer</option>
                </select>
                <!-- hide -->
                <div id="skills-input" class="hidden">
            <input type="text" name="skills" placeholder="Skills" class="w-full mb-4 p-2 border rounded">
        </div>

        <!--  -->
                <button type="submit" name="register" class="w-full bg-green-500 text-white py-2 rounded">Register</button>
            </form>
            <p class="mt-4 text-center">Already have an account? <button id="show-login" class="text-blue-500 underline">Log In</button></p>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const showRegister = document.getElementById('show-register');
        const showLogin = document.getElementById('show-login');
        const successMessage = document.getElementById('success-message');

        showRegister.addEventListener('click', () => {
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
            if (successMessage) {
                successMessage.classList.add('hidden');
            }
        });

        showLogin.addEventListener('click', () => {
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            if (successMessage) {
                successMessage.classList.add('hidden');
            }
        });

    // Freelancer
    document.getElementById('role-select').addEventListener('change', function() {
        console.log("Role selection changed to:", this.value);

        const skillsInput = document.getElementById('skills-input');

        if (this.value.toLowerCase() === 'freelancer') {
            skillsInput.classList.remove('hidden');
        } else {
            skillsInput.classList.add('hidden');
        }
    });
    </script>
</body>
</html>
