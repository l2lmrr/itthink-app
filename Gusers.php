<?php
session_start();

// logged in
if (!isset($_SESSION['user'])) {
    header('Location: test.php');
    exit;
}

// Rtv user info 
$username = $_SESSION['user']['username'];
$userId = $_SESSION['user']['id_user'];

// db
try {
    $pdo = new PDO('mysql:host=localhost;dbname=itthink', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$users = $pdo->query(
    "SELECT * FROM utilisateurs"
)->fetchAll(PDO::FETCH_ASSOC);


// Add Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $skills = ($role === 'freelancer') ? $_POST['skills'] : null; 


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

    
    header("Location: Gusers.php");
    exit;
}


// Update Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id_user = $_POST['id_user'];
    $new_email = trim($_POST['newemail']); 
    $new_password = $_POST['newpassword']; 
    $confirm_password = $_POST['newconfirm_password']; 
    $new_role = $_POST['newrole']; 
    $new_skills = ($new_role === 'freelancer') ? $_POST['newskills'] : null; 

    // Validate inputs
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if the new email already exists for another user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE LOWER(email) = LOWER(?) AND id_user != ?");
        $stmt->execute([$new_email, $id_user]);
        $email_exists = $stmt->fetchColumn() > 0;

        if ($email_exists) {
            $error_message = "Error: Email already exists for another user.";
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the user's details in the database
            $stmt = $pdo->prepare("
                UPDATE utilisateurs 
                SET email = ?, password = ?, role = ?, skills = ? 
                WHERE id_user = ?
            ");
            try {
                $stmt->execute([$new_email, $hashed_password, $new_role, $new_skills, $id_user]);
                $success_message = "User updated successfully.";
            } catch (Exception $e) {
                $error_message = "An error occurred during the update: " . $e->getMessage();
            }
        }
    }
    
    header("Location: Gusers.php");
    exit;
}


// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = $_POST['id_user'];

    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_user = ?");
    $stmt->execute([ $userId]);

    header("Location: Gusers.php");
    exit;
}

//  log out
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: test.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/main.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <title>Ithhink Programk</title>
</head>

<body class="bg-gray-100">
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-purple-900 text-white transition-all duration-300">
        <!-- Logo Area -->
        <div class="flex items-center p-4 border-b border-white-700">
            <i class="fas fa-puzzle-piece text-2xl"></i>
            <a href="admin.php"> <span class="ml-2 text-xl font-bold">Ithhink Programk</span> </a>
        </div>

        <!-- Navigation Links -->
        <nav class="mt-8">
        <a href="Gusers.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-purple-700 hover:text-white transition-colors">
                <i class="fas fa-users w-5"></i>
                <span class="ml-3">Gestion Des Utilisateurs</span>
            </a>
        <a href="Gfreelancers.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-purple-700 hover:text-white transition-colors">
                <i class="fas fa-user-tie w-5"></i>
                <span class="ml-3">Gestion Des Freelancers</span>
            </a>
            <a href="Gprojects.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-purple-700 hover:text-white transition-colors">
                <i class="fas fa-tasks w-5"></i>
                <span class="ml-3">Gestion Des Project</span>
            </a>
            <a href="Gtemo.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-purple-700 hover:text-white transition-colors">
                <i class="fas fa-quote-left w-5"></i>
                <span class="ml-3">Gestion Des Temoignage</span>
            </a>
            <a href="Gcat.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-purple-700 hover:text-white transition-colors">
                <i class="fas fa-folder w-5"></i>
                <span class="ml-3">Gestion Des Categorie</span>
                <a href="GsousCat.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-purple-700 hover:text-white transition-colors">
                <i class="fas fa-layer-group w-5"></i>
                <span class="ml-3">Gestion Des SousCategorie</span>
            </a>
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div id="mainContent" class="ml-64 transition-all duration-300">
        <!-- Top Navbar -->
        <header class="bg-white shadow-sm fixed top-0 right-0 left-64 transition-all duration-300">
            <div class="flex justify-end items-center h-16 px-4">
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700" id="admin-name">Welcome, <b><?php echo htmlspecialchars($username); ?></b></span>
                    <form method="post">
            <button
                class="flex items-center px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md transition-colors"
                name="logout" type="submit">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Logout
            </button>
        </form>
                </div>
            </div>
        </header>

        <!-- Main Content Container -->
        <main class="p-6 mt-16">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Filter Row -->
                
        <!-- Action Buttons -->
        <div class="mb-6">
            <button id="newProjectButton" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">New User</button>
            <button id="editProjectButton" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit User</button>
            <button id="deleteProjectButton" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>

        </div>

        <!-- New users Form -->
                        <div id="newProjectForm" style="display: none;" class="mb-6">
                    <form method="post" class="space-y-4">
                        <input type="text" name="username" placeholder="Username" class="w-full p-2 border rounded" required>
                        <input type="email" name="email" placeholder="Email" class="w-full p-2 border rounded" required>
                        <input type="text" name="password" placeholder="Password" class="w-full p-2 border rounded" required>
                        <input type="text" name="confirm_password" placeholder="Confirm Password" class="w-full p-2 border rounded" required>
                        <select id="role-select" name="role" class="w-full mb-4 p-2 border rounded" required>
                            <option value="">Select a Role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                            <option value="freelancer">Freelancer</option>
                        </select>
                        <div id="skills-input" class="hidden">
                            <input type="text" name="skills" placeholder="Skills" class="w-full mb-4 p-2 border rounded">
                        </div>
                        <button type="submit" name="add_user" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add User</button>
                    </form>
                </div>

                <!-- Edit users Selection -->
                <div id="editProjectForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <label for="userSelect">Select a User to edit:</label>
                <select name="id_user" id="userSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select a User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id_user']; ?>">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="newemail" placeholder="New Email" class="w-full p-2 border rounded" required>
                <input type="text" name="newpassword" placeholder="New Password" class="w-full p-2 border rounded" required>
                <input type="text" name="newconfirm_password" placeholder="Confirm the New Password" class="w-full p-2 border rounded" required>
                <select id="updated-role" name="newrole" class="w-full mb-4 p-2 border rounded" required>
                            <option value="">Select a New Role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                            <option value="freelancer">Freelancer</option>
                        </select>
                        <div id="updated-skills" class="hidden">
                            <input type="text" name="newskills" placeholder="Skills" class="w-full mb-4 p-2 border rounded">
                        </div>
                <button type="submit" name="update_user" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update User</button>
            </form>
        </div>

        
                <!-- Delete users Selection -->
                <div id="deleteProjectForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <label for="projectSelect">Select User to Delete</label>
                <select name="id_user" id="userSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select a User</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id_user']; ?>">
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="delete_user" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Delete</button>
            </form>
        </div>

           <!-- Prj Table -->
<table class="w-full border-collapse">
    <thead>
        <tr class="bg-gray-200">
            <th class="py-2 px-12 text-left">Username</th>
            <th class="py-2 px-12 text-left">Email</th>
            <th class="py-2 px-12 text-left">Role</th>
        </tr>
    </thead>
            <tbody>
            <?php
            // Assuming $users has already been fetched
            if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr class="border-b">
                        <td class="py-2 px-10"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="py-2 px-10"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="py-2 px-10"><?php echo htmlspecialchars($user['role'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-2">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>


            </div>
        </main>
    </div>


    <script>
        document.getElementById('newProjectButton').addEventListener('click', function() {
            const form = document.getElementById('newProjectForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });

        function filterSubcategories() {
            const categorySelect = document.getElementById('id_category');
            const subcategorySelect = document.getElementById('id_subcategory');

            const selectedCategory = categorySelect.value;
            const options = subcategorySelect.options;

            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                option.style.display = option.dataset.category === selectedCategory || !selectedCategory ? 'block' : 'none';
            }

            subcategorySelect.value = '';
        }

        // edit
        document.getElementById('editProjectButton').addEventListener('click', function() {
            const form = document.getElementById('editProjectForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });

        function filterSubcategories() {
            const categorySelect = document.getElementById('id_category');
            const subcategorySelect = document.getElementById('id_subcategory');

            const selectedCategory = categorySelect.value;
            const options = subcategorySelect.options;

            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                option.style.display = option.dataset.category === selectedCategory || !selectedCategory ? 'block' : 'none';
            }

            subcategorySelect.value = '';
        }

        // Delete
        document.getElementById('deleteProjectButton').addEventListener('click', function() {
            const form = document.getElementById('deleteProjectForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });

        function filterSubcategories() {
            const categorySelect = document.getElementById('id_category');
            const subcategorySelect = document.getElementById('id_subcategory');

            const selectedCategory = categorySelect.value;
            const options = subcategorySelect.options;

            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                option.style.display = option.dataset.category === selectedCategory || !selectedCategory ? 'block' : 'none';
            }

            subcategorySelect.value = '';
        }

         // user
    document.getElementById('role-select').addEventListener('change', function() {
        console.log("Role selection changed to:", this.value);

        const skillsInput = document.getElementById('skills-input');

        if (this.value.toLowerCase() === 'freelancer') {
            skillsInput.classList.remove('hidden');
        } else {
            skillsInput.classList.add('hidden');
        }
    });

         // update user
         document.getElementById('updated-role').addEventListener('change', function() {
        console.log("Role selection changed to:", this.value);

        const skillsInput = document.getElementById('updated-skills');

        if (this.value.toLowerCase() === 'freelancer') {
            skillsInput.classList.remove('hidden');
        } else {
            skillsInput.classList.add('hidden');
        }
    });

    </script>
    </body>

</html>