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

// Fetch offers related to projects created by the logged-in user
$offers = $pdo->prepare("
    SELECT 
        o.id_offer, 
        o.amount, 
        o.deadline, 
        o.status, 
        p.project_title, 
        f.freelancer_name, 
        f.skills 
    FROM offers o
    INNER JOIN projects p ON o.id_project = p.id_project
    INNER JOIN freelancers f ON o.id_freelancer = f.id_freelancer
    WHERE p.id_user = ?
");
$offers->execute([$userId]);
$offersList = $offers->fetchAll(PDO::FETCH_ASSOC);

// update statue
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_offer'])) {
        $offerId = $_POST['id_offer'];
        $pdo->prepare("UPDATE offers SET status = 'in progress' WHERE id_offer = ?")->execute([$offerId]);
        $pdo->prepare("
        UPDATE offers o1
        JOIN offers o2 ON o1.id_project = o2.id_project
        SET o1.status = 'rejected'
        WHERE o2.id_offer = ? AND o1.id_offer != ?
    ")->execute([$offerId, $offerId]);
    }

    if (isset($_POST['reject_offer'])) {
        $offerId = $_POST['id_offer'];
        $pdo->prepare("UPDATE offers SET status = 'rejected' WHERE id_offer = ?")->execute([$offerId]);
    }
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
    <h2 class="text-2xl font-bold mb-6">Admin Dashboard</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Total Users -->
        <div class="bg-blue-500 text-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold">Total Users</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total_users FROM utilisateurs");
            $stmt->execute();
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
            ?>
            <p class="text-4xl font-bold mt-4"><?php echo $totalUsers ?: 0; ?></p>
        </div>

        <!-- Total Freelancers -->
        <div class="bg-green-500 text-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold">Total Freelancers</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total_freelancers FROM utilisateurs WHERE role = 'freelancer'");
            $stmt->execute();
            $totalFreelancers = $stmt->fetch(PDO::FETCH_ASSOC)['total_freelancers'];
            ?>
            <p class="text-4xl font-bold mt-4"><?php echo $totalFreelancers ?: 0; ?></p>
        </div>

        <!-- Total Projects -->
        <div class="bg-purple-500 text-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold">Total Projects</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total_projects FROM Projects");
            $stmt->execute();
            $totalProjects = $stmt->fetch(PDO::FETCH_ASSOC)['total_projects'];
            ?>
            <p class="text-4xl font-bold mt-4"><?php echo $totalProjects ?: 0; ?></p>
        </div>

        <!-- Total Offers -->
        <div class="bg-yellow-500 text-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold">Total Offers</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total_offers FROM Offers");
            $stmt->execute();
            $totalOffers = $stmt->fetch(PDO::FETCH_ASSOC)['total_offers'];
            ?>
            <p class="text-4xl font-bold mt-4"><?php echo $totalOffers ?: 0; ?></p>
        </div>

        <!-- Total Categories -->
        <div class="bg-teal-500 text-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold">Total Categories</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total_categories FROM categories");
            $stmt->execute();
            $totalCategories = $stmt->fetch(PDO::FETCH_ASSOC)['total_categories'];
            ?>
            <p class="text-4xl font-bold mt-4"><?php echo $totalCategories ?: 0; ?></p>
        </div>

        <!-- Total Subcategories -->
        <div class="bg-indigo-500 text-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold">Total Subcategories</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total_subcategories FROM subcategories");
            $stmt->execute();
            $totalSubcategories = $stmt->fetch(PDO::FETCH_ASSOC)['total_subcategories'];
            ?>
            <p class="text-4xl font-bold mt-4"><?php echo $totalSubcategories ?: 0; ?></p>
        </div>

        <!-- status -->
                    <div class="bg-orange-500 text-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold">Projects Status</h3>
                <?php
                // Prepare the SQL query to get the counts for each status (in progress, pending, rejected)
                $stmt = $pdo->prepare("
                    SELECT 
                        (SELECT COUNT(DISTINCT p.id_project) 
                        FROM Projects p
                        INNER JOIN Offers o ON p.id_project = o.id_project
                        WHERE o.status = 'in progress') AS in_progress,
                        
                        (SELECT COUNT(DISTINCT p.id_project) 
                        FROM Projects p
                        INNER JOIN Offers o ON p.id_project = o.id_project
                        WHERE o.status = 'pending') AS pending,
                        
                        (SELECT COUNT(DISTINCT p.id_project) 
                        FROM Projects p
                        INNER JOIN Offers o ON p.id_project = o.id_project
                        WHERE o.status = 'rejected') AS rejected
                ");
                $stmt->execute();
                $statusCounts = $stmt->fetch(PDO::FETCH_ASSOC);

                $inProgress = $statusCounts['in_progress'] ?: 0;
                $pending = $statusCounts['pending'] ?: 0;
                $rejected = $statusCounts['rejected'] ?: 0;
                ?>
                <p class="text-4xl font-bold mt-4">In Progress: <?php echo $inProgress; ?></p>
                <p class="text-4xl font-bold mt-4">Pending: <?php echo $pending; ?></p>
                <p class="text-4xl font-bold mt-4">Rejected: <?php echo $rejected; ?></p>
            </div>


         <!-- total number of temo -->

        <div class="bg-red-500 text-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold">Total Testimonials</h3>
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total_testimonials FROM temoignage");
            $stmt->execute();
            $totalTestimonials = $stmt->fetch(PDO::FETCH_ASSOC)['total_testimonials'];
            ?>
            <p class="text-4xl font-bold mt-4"><?php echo $totalTestimonials ?: 0; ?></p>
        </div>
    </div>
</main>

    </div>
</div>

       

    </body>

</html>