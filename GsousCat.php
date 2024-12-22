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

$subcategories = $pdo->query("
    SELECT s.id_subcategory, s.subcategory_name, c.category_name 
    FROM subcategories s
    INNER JOIN categories c ON s.id_category = c.id_category
")->fetchAll(PDO::FETCH_ASSOC);



// Fetch Categories and Subcategories
$categories = $pdo->query("SELECT id_category, category_name FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Add 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_souscategory'])) {
    $subcategoryName = $_POST['sousCat_Name'];
    $categoryID = $_POST['id_category'];


    $stmt = $pdo->prepare("INSERT INTO subcategories (subcategory_name, id_category) VALUES (?, ?)");
    $stmt->execute([$subcategoryName, $categoryID,]);
    header("Location: GsousCat.php");
    exit;
}



// Delete 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subcategory'])) {
    $IdsubC = $_POST['id_subcategory'];

    $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id_subcategory = ?");
    $stmt->execute([ $IdsubC]);

    header("Location: GsousCat.php");
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
        <button id="newProjectButton" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">New Subcategory</button>
            <button id="deleteProjectButton" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>

        </div>

        <!-- New Project Form -->
        <div id="newProjectForm" style="display: none;" class="mb-6">
        <form method="post" class="space-y-4">
                <input type="text" name="sousCat_Name" placeholder="SubCategory Name" class="w-full p-2 border rounded" required>
                <select name="id_category" id="TSSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select a Category</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?php echo $categorie['id_category']; ?>">
                            <?php echo htmlspecialchars($categorie['category_name']); ?>
                        </option>
                    <?php endforeach; ?>    
                </select>            
                    <button type="submit" name="add_souscategory" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Subcategory</button>
            </form>
        </div>

        
        <div id="deleteProjectForm" style="display: none;" class="mb-6">
        <form method="post" class="space-y-4">
                <label for="projectSelect">Select a project to edit:</label>
                <select name="id_subcategory" id="TSSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select a Subcategory</option>
                    <?php foreach ($subcategories as $subcategorie): ?>
                        <option value="<?php echo $subcategorie['id_subcategory']; ?>">
                            <?php echo htmlspecialchars($subcategorie['subcategory_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="delete_subcategory" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Delete The Subcategory</button>
            </form>
        </div>

               <!-- Prj Table -->
               <table class="w-full border-collapse">
    <thead>
        <tr class="bg-gray-200">
            <th class="py-2 px-12 text-left">Subcategory</th>
            <th class="py-2 px-12 text-left">Category</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($subcategories)): ?>
            <?php foreach ($subcategories as $subcategory): ?>
                <tr class="border-b">
                    <td class="py-2 px-12"><?php echo htmlspecialchars($subcategory['subcategory_name']); ?></td>
                    
                    <td class="py-2 px-12"><?php echo htmlspecialchars($subcategory['category_name']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" class="text-center py-2">No subcategories found.</td>
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