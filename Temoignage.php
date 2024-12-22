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

$Temoignages = $pdo->query(
    "SELECT 
         t.id_testimonial,
         t.Testimonial_Title,
         t.comment,
         u.username 
    FROM temoignage t
    INNER JOIN utilisateurs u ON t.id_user = u.id_user"
)->fetchAll(PDO::FETCH_ASSOC);


// Fetch Categories and Subcategories
$categories = $pdo->query("SELECT id_category, category_name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $pdo->query("SELECT id_subcategory, subcategory_name, id_category FROM subcategories")->fetchAll(PDO::FETCH_ASSOC);

// Add 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_temoignage'])) {
    $TestimonialTitle = $_POST['Testimonial_Title'];
    $comment = $_POST['comment'];


    $stmt = $pdo->prepare("INSERT INTO temoignage (comment, id_user,Testimonial_Title) VALUES (?, ?, ?)");
    $stmt->execute([$comment, $userId,$TestimonialTitle]);
    header("Location: Temoignage.php");
    exit;
}

// Update 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_temoignage'])) {
    $IdTemoignage = $_POST['id_testimonial'];
    $comment = $_POST['comment'];

    $stmt = $pdo->prepare("UPDATE temoignage SET comment = ? WHERE id_testimonial = ?");
    $stmt->execute([$comment, $IdTemoignage]);

    header("Location: Temoignage.php");
    exit;
}

// Delete 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_temoignage'])) {
    $IdTemoignage = $_POST['id_testimonial'];

    $stmt = $pdo->prepare("DELETE FROM temoignage WHERE id_testimonial = ?");
    $stmt->execute([ $IdTemoignage]);

    header("Location: Temoignage.php");
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
            <button id="newTSButton" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">New Testimonial</button>
            <button id="editTStButton" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit a Testimonial</button>
            <button id="deleteTStButton" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>

        </div>

        <!-- New Project Form -->
        <div id="newTSForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <input type="text" name="Testimonial_Title" placeholder="Testimonial Title" class="w-full p-2 border rounded" required>
                <textarea name="comment" placeholder="Testimonial Comment" class="w-full p-2 border rounded" required></textarea>
                <button type="submit" name="add_temoignage" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add The Testimonial</button>
            </form>
        </div>

                <!-- Edit Project Selection -->
                <div id="editTSForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <label for="TestimonialSelect">Select a project to edit:</label>
                <select name="id_testimonial" id="TSSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select a Testimonial</option>
                    <?php foreach ($Temoignages as $Temoignage): ?>
                        <option value="<?php echo $Temoignage['id_testimonial']; ?>">
                            <?php echo htmlspecialchars($Temoignage['Testimonial_Title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <textarea name="comment" placeholder="New Testimonial Comment" class="w-full p-2 border rounded" required></textarea>

                <button type="submit" name="update_temoignage" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Edit The Testimonial</button>
            </form>
        </div>

        
                <!-- Delete Project Selection -->
                <div id="deleteFSForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <label for="projectSelect">Select a project to edit:</label>
                <select name="id_testimonial" id="TSSelect" class="w-full p-2 border rounded" required>
                    <option value="">Select Project</option>
                    <?php foreach ($Temoignages as $Temoignage): ?>
                        <option value="<?php echo $Temoignage['id_testimonial']; ?>">
                            <?php echo htmlspecialchars($Temoignage['Testimonial_Title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="delete_temoignage" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Delete The Testimonial</button>
            </form>
        </div>

               <!-- Prj Table -->
<table class="w-full border-collapse">
    <thead>
        <tr class="bg-gray-200">
        <th class="py-2 px-12 text-left">User</th>
            <th class="py-2 px-12 text-left">Testimonial Title</th>
            <th class="py-2 px-12 text-left">Comment</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Fetch testimonials created by the current user
        $stmt = $pdo->prepare("SELECT * FROM temoignage WHERE id_user = ?");
        $stmt->execute([$userId]);
        $Temoignages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($Temoignages)): ?>
            <?php foreach ($Temoignages as $Temoignage): ?>
                <tr class="border-b">
                <td class="py-2 px-12"><?php echo htmlspecialchars($Temoignage['username']); ?></td>
                    <td class="py-2 px-10"><?php echo htmlspecialchars($Temoignage['Testimonial_Title']); ?></td>
                    <td class="py-2 px-10"><?php echo htmlspecialchars($Temoignage['comment']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" class="text-center py-2">No testimonials found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
    </table>

            </div>
        </main>
    </div>


    <script>
        document.getElementById('newTSButton').addEventListener('click', function() {
            const form = document.getElementById('newTSForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });


        // edit
        document.getElementById('editTStButton').addEventListener('click', function() {
            const form = document.getElementById('editTSForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });


        // Delete
        document.getElementById('deleteTStButton').addEventListener('click', function() {
            const form = document.getElementById('deleteFSForm');
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