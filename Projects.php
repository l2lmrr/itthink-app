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

$projects = $pdo->query(
    "SELECT 
         p.id_project,
        p.project_title, 
        p.description, 
        c.category_name, 
        s.subcategory_name, 
        p.date_creation
    FROM projects p 
    INNER JOIN categories c ON p.id_category = c.id_category
    LEFT JOIN subcategories s ON p.id_subcategory = s.id_subcategory"
)->fetchAll(PDO::FETCH_ASSOC);

// Fetch Categories and Subcategories
$categories = $pdo->query("SELECT id_category, category_name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $pdo->query("SELECT id_subcategory, subcategory_name, id_category FROM subcategories")->fetchAll(PDO::FETCH_ASSOC);

// Add Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    $projectTitle = $_POST['project_title'];
    $description = $_POST['description'];
    $categoryId = $_POST['id_category'];
    $subcategoryId = $_POST['id_subcategory'];
    $dateCreation = date('Y-m-d H:i:s');
    

    $stmt = $pdo->prepare("INSERT INTO projects (project_title, description, id_category, id_subcategory, id_user, date_creation) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$projectTitle, $description, $categoryId, $subcategoryId, $userId, $dateCreation]);
    header("Location: Projects.php");
    exit;
}

// Update Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_project'])) {
    $projectId = $_POST['id_project'];
    $projectTitle = $_POST['project_title'];
    $description = $_POST['description'];
    $categoryId = $_POST['id_category'];
    $subcategoryId = $_POST['id_subcategory'];

    $stmt = $pdo->prepare("UPDATE projects SET project_title = ?, description = ?, id_category = ?, id_subcategory = ? WHERE id_project = ?");
    $stmt->execute([$projectTitle, $description, $categoryId, $subcategoryId, $projectId]);

    header("Location: Projects.php");
    exit;
}

// Delete Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $projectId = $_POST['id_project'];

    $stmt = $pdo->prepare("DELETE FROM projects WHERE id_project = ?");
    $stmt->execute([ $projectId]);

    header("Location: Projects.php");
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
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-green-800 text-white transition-all duration-300">
        <!-- Logo Area -->
        <div class="flex items-center p-4 border-b border-white-700">
            <i class="fas fa-puzzle-piece text-2xl"></i>
            <a href="user.php"> <span class="ml-2 text-xl font-bold">Ithhink Programk</span> </a>
        </div>

        <!-- Navigation Links -->
        <nav class="mt-8">
            <a href="Projects.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-green-700 hover:text-white transition-colors">
                <i class="fas fa-clipboard-list w-5"></i>
                <span class="ml-3">Gestion Des Project</span>
            </a>
            <a href="Temoignage.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-green-700 hover:text-white transition-colors">
                <i class="fas fa-comments w-5"></i>
                <span class="ml-3">Gestion Des Temoignage</span>
            </a>
            <a href="useroffers.php"
                class="flex items-center px-4 py-3 text-white-300 hover:bg-green-700 hover:text-white transition-colors">
                <i class="fas fa-handshake w-5"></i>
                <span class="ml-3">Gestion Offre</span>
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
            <button id="newProjectButton" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">New Project</button>
            <button id="editProjectButton" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit Project</button>
            <button id="deleteProjectButton" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>

        </div>

        <!-- New Project Form -->
        <div id="newProjectForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <input type="text" name="project_title" placeholder="Project Title" class="w-full p-2 border rounded" required>
                <textarea name="description" placeholder="Project Description" class="w-full p-2 border rounded" required></textarea>
                <select name="id_category" id="id_category" class="w-full p-2 border rounded" onchange="filterSubcategories()" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id_category']; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="id_subcategory" id="id_subcategory" class="w-full p-2 border rounded" required>
                    <option value="">Select Subcategory</option>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?php echo $subcategory['id_subcategory']; ?>" data-category="<?php echo $subcategory['id_category']; ?>">
                            <?php echo htmlspecialchars($subcategory['subcategory_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_project" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Project</button>
            </form>
        </div>

                <!-- Edit Project Selection -->
                <div id="editProjectForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <label for="projectSelect">Select a project to edit:</label>
                <select name="id_project" id="projectSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id_project']; ?>">
                            <?php echo htmlspecialchars($project['project_title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="project_title" placeholder="New Project Title" class="w-full p-2 border rounded" required>
                <textarea name="description" placeholder="New Project Description" class="w-full p-2 border rounded" required></textarea>
                <select name="id_category" id="edit_id_category" class="w-full p-2 border rounded" onchange="filterSubcategoriesEdit()" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id_category']; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="id_subcategory" id="edit_id_subcategory" class="w-full p-2 border rounded" required>
                    <option value="">Select Subcategory</option>
                    <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?php echo $subcategory['id_subcategory']; ?>" data-category="<?php echo $subcategory['id_category']; ?>">
                            <?php echo htmlspecialchars($subcategory['subcategory_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="update_project" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update Project</button>
            </form>
        </div>

        
                <!-- Delete Project Selection -->
                <div id="deleteProjectForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <label for="projectSelect">Select a project to edit:</label>
                <select name="id_project" id="projectSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id_project']; ?>">
                            <?php echo htmlspecialchars($project['project_title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="delete_project" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Delete</button>
            </form>
        </div>

           <!-- Prj Table -->
<table class="w-full border-collapse">
    <thead>
        <tr class="bg-gray-200">
            <th class="py-2 px-12 text-left">Project Title</th>
            <th class="py-2 px-12 text-left">Description</th>
            <th class="py-2 px-12 text-left">Category</th>
            <th class="py-2 px-12 text-left">Subcategory</th>
            <th class="py-2 px-10 text-left">Date Creation</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Fetch projects created by the current user
        $stmt = $pdo->prepare("
            SELECT p.project_title, p.description, c.category_name, s.subcategory_name, p.date_creation 
            FROM Projects p
            INNER JOIN Categories c ON p.id_category = c.id_category
            LEFT JOIN Subcategories s ON p.id_subcategory = s.id_subcategory
            WHERE p.id_user = ?
        ");
        $stmt->execute([$userId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($projects)): ?>
            <?php foreach ($projects as $project): ?>
                <tr class="border-b">
                    <td class="py-2 px-10"><?php echo htmlspecialchars($project['project_title']); ?></td>
                    <td class="py-2 px-10"><?php echo htmlspecialchars($project['description']); ?></td>
                    <td class="py-2 px-10"><?php echo htmlspecialchars($project['category_name']); ?></td>
                    <td class="py-2 px-10"><?php echo htmlspecialchars($project['subcategory_name'] ?? 'N/A'); ?></td>
                    <td class="py-2 px-12"><?php echo htmlspecialchars($project['date_creation']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center py-2">No projects found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

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
    </script>
    </body>

</html>