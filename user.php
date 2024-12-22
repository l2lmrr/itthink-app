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

        <main class="p-6 mt-16">
            <div class="bg-white rounded-lg shadow-md p-6">
        
        <!--  dash -->
        <h2 class="text-2xl font-bold mb-6">User Dashboard</h2>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Total Projects Created Card -->
    <div class="bg-blue-500 text-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold">Total Projects Created</h3>
        <?php
        // Fetch total number of projects created by the user
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total_projects FROM Projects WHERE id_user = ?");
        $stmt->execute([$userId]);
        $totalProjects = $stmt->fetch(PDO::FETCH_ASSOC)['total_projects'];
        ?>
        <p class="text-4xl font-bold mt-4"><?php echo $totalProjects ?: 0; ?></p>
    </div>

    <!-- Total Projects Accepted Card -->
    <div class="bg-green-500 text-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold">Total Projects Accepted</h3>
        <?php
        // Fetch total number of accepted projects by the user (Assuming status in 'Offers' table defines accepted projects)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id_project) AS total_accepted_projects
            FROM Projects p
            INNER JOIN Offers o ON p.id_project = o.id_project
            WHERE o.status = 'in progress' AND p.id_user = ?
        ");
        $stmt->execute([$userId]);
        $totalAcceptedProjects = $stmt->fetch(PDO::FETCH_ASSOC)['total_accepted_projects'];
        ?>
        <p class="text-4xl font-bold mt-4"><?php echo $totalAcceptedProjects ?: 0; ?></p>
    </div>

    <!-- Total Offers Received Card -->
    <div class="bg-purple-500 text-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold">Total Offers Received</h3>
        <?php
        // Fetch total number of offers received by the user
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total_offers_received FROM Offers WHERE id_project IN (SELECT id_project FROM Projects WHERE id_user = ?)");
        $stmt->execute([$userId]);
        $totalOffersReceived = $stmt->fetch(PDO::FETCH_ASSOC)['total_offers_received'];
        ?>
        <p class="text-4xl font-bold mt-4"><?php echo $totalOffersReceived ?: 0; ?></p>
    </div>

    <!-- Average Offer Acceptance Rate Card -->
    <div class="bg-yellow-500 text-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold">Offer Acceptance Rate</h3>
        <?php
        // Fetch total and accepted offers for the user to calculate acceptance rate
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM Offers o WHERE o.status = 'in progress' AND o.id_project IN (SELECT id_project FROM Projects WHERE id_user = ?)) AS accepted_offers,
                (SELECT COUNT(*) FROM Offers o WHERE o.id_project IN (SELECT id_project FROM Projects WHERE id_user = ?)) AS total_offers
        ");
        $stmt->execute([$userId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $acceptedOffers = $result['accepted_offers'] ?: 0;
        $totalOffers = $result['total_offers'] ?: 1; // Avoid division by zero
        $acceptanceRate = ($acceptedOffers / $totalOffers) * 100;
        ?>
        <p class="text-4xl font-bold mt-4"><?php echo number_format($acceptanceRate, 2) . "%"; ?></p>
    </div>

    <!-- Total Testimonials Created Card -->
    <div class="bg-red-500 text-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold">Total Testimonials Created</h3>
        <?php
        // Fetch total number of testimonials created by the user
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total_testimonials FROM temoignage WHERE id_user = ?");
        $stmt->execute([$userId]);
        $totalTestimonials = $stmt->fetch(PDO::FETCH_ASSOC)['total_testimonials'];
        ?>
        <p class="text-4xl font-bold mt-4"><?php echo $totalTestimonials ?: 0; ?></p>
    </div>
</div>


        </main>
    </div>
    
   
    </body>

</html>