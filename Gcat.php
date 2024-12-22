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

$categories = $pdo->query("
    SELECT *
        
    FROM categories
    
")->fetchAll(PDO::FETCH_ASSOC);

// Add Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];

    $stmt = $pdo->prepare("INSERT INTO categories (category_name ) VALUES (?)");
    $stmt->execute([$category_name]);
    header("Location: Gcat.php");
    exit;
}

// Delete Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $categoryID = $_POST['id_category'];

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id_category = ?");
    $stmt->execute([ $categoryID]);

    header("Location: Gcat.php");
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
            <button id="newProjectButton" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">New Category</button>
            <button id="deleteProjectButton" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>

        </div>

        <!-- New Project Form -->
        <div id="newProjectForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <input type="text" name="category_name" placeholder="Category Name" class="w-full p-2 border rounded" required>
                <button type="submit" name="add_category" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Project</button>
            </form>
        </div>

        
                <!-- Delete Project Selection -->
                <div id="deleteProjectForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <label for="projectSelect">Select a project to edit:</label>
                <select name="id_category" id="projectSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select Project</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?php echo $categorie['id_category']; ?>">
                            <?php echo htmlspecialchars($categorie['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="delete_category" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Delete</button>
            </form>
        </div>

           <!-- Prj Table -->
<table class="w-full border-collapse">
    <thead>
        <tr class="bg-gray-200">
        <th class="py-2 px-[44.5%] text-left">Category Name</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if (!empty($categories)): ?>
        <?php foreach ($categories as $categorie): ?>
            <tr class="border-b">
                <td class="py-2 px-[44%]"><?php echo htmlspecialchars($categorie['category_name']); ?></td>
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