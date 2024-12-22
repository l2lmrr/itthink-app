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

$offers = $pdo->query(
    "SELECT 
         o.id_offer,
        p.project_title, 
        o.amount AS offer_price, 
        o.deadline,
        o.id_freelancer,
        o.id_project
    FROM offers o 
    INNER JOIN projects p ON o.id_project = p.id_project"
)->fetchAll(PDO::FETCH_ASSOC);

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
 <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-blue-800 text-white transition-all duration-300">
        <!-- Logo Area -->
        <div class="flex items-center p-4 border-b border-blue-1000">
            <i class="fas fa-puzzle-piece text-2xl"></i>
            <a href="freelancer.php"> <span class="ml-2 text-xl font-bold">Ithhink Programk</span> </a>
        </div>

        <!-- Navigation Links -->
        <nav class="mt-8">
            <a href="offers.php"
                class="flex items-center px-4 py-3 text-gray-300 hover:bg-blue-700 hover:text-white transition-colors">
                <i class="fas fa-question-circle w-5"></i>
                <span class="ml-3"> Gestion Des Offres</span>
            </a>
            <a href="freelanceroffers.php"
                class="flex items-center px-4 py-3 text-gray-300 hover:bg-blue-700 hover:text-white transition-colors">
                <i class="fas fa-users w-5"></i>
                <span class="ml-3">Status Des Offers</span>
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
        
        <!--  dash -->

                            <h2 class="text-2xl font-bold mb-6">Freelancer Dashboard</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Total Offers Card -->
                        <div class="bg-blue-500 text-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold">Total Offers Made</h3>
                            <?php
                            // Fetch total number of offers by the freelancer
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) AS total_offers 
                                FROM offers 
                                WHERE id_freelancer = (
                                    SELECT id_freelancer 
                                    FROM freelancers 
                                    WHERE id_user = ?
                                )
                            ");
                            $stmt->execute([$userId]);
                            $totalOffers = $stmt->fetch(PDO::FETCH_ASSOC)['total_offers'];
                            ?>
                            <p class="text-4xl font-bold mt-4"><?php echo $totalOffers ?: 0; ?></p>
                        </div>

                        <!-- Average Offer Price Card -->
                        <div class="bg-green-500 text-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold">Average Offer Price</h3>
                            <?php
                            // Fetch average offer price by the freelancer
                            $stmt = $pdo->prepare("
                                SELECT AVG(amount) AS average_price 
                                FROM offers 
                                WHERE id_freelancer = (
                                    SELECT id_freelancer 
                                    FROM freelancers 
                                    WHERE id_user = ?
                                )
                            ");
                            $stmt->execute([$userId]);
                            $averagePrice = $stmt->fetch(PDO::FETCH_ASSOC)['average_price'];
                            ?>
                            <p class="text-4xl font-bold mt-4">
                                <?php echo $averagePrice ? number_format($averagePrice, 2) . " USD" : "0.00 USD"; ?>
                            </p>
                        </div>

                        <!-- Average Accepted Offer Price Card -->
                        <div class="bg-purple-500 text-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold">Average Accepted Offer Price</h3>
                            <?php
                            // Fetch average price of accepted offers by the freelancer
                            $stmt = $pdo->prepare("
                                SELECT AVG(amount) AS avg_accepted_price 
                                FROM offers 
                                WHERE status = 'in progress' 
                                AND id_freelancer = (
                                    SELECT id_freelancer 
                                    FROM freelancers 
                                    WHERE id_user = ?
                                )
                            ");
                            $stmt->execute([$userId]);
                            $averageAcceptedPrice = $stmt->fetch(PDO::FETCH_ASSOC)['avg_accepted_price'];
                            ?>
                            <p class="text-4xl font-bold mt-4">
                                <?php echo $averageAcceptedPrice ? number_format($averageAcceptedPrice, 2) . " USD" : "0.00 USD"; ?>
                            </p>
                        </div>

                        <!-- Acceptance Rate Card -->
                        <div class="bg-yellow-500 text-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold">Offer Acceptance Rate</h3>
                            <?php
                            // Fetch total and accepted offers for the freelancer
                            $stmt = $pdo->prepare("
                                SELECT 
                                    (SELECT COUNT(*) FROM offers WHERE status = 'in progress' AND id_freelancer = (
                                        SELECT id_freelancer 
                                        FROM freelancers 
                                        WHERE id_user = ?
                                    )) AS accepted_offers,
                                    (SELECT COUNT(*) FROM offers WHERE id_freelancer = (
                                        SELECT id_freelancer 
                                        FROM freelancers 
                                        WHERE id_user = ?
                                    )) AS total_offers
                            ");
                            $stmt->execute([$userId, $userId]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);

                            $acceptedOffers = $result['accepted_offers'] ?: 0;
                            $totalOffers = $result['total_offers'] ?: 1; // Avoid division by zero
                            $acceptanceRate = ($acceptedOffers / $totalOffers) * 100;
                            ?>
                            <p class="text-4xl font-bold mt-4">
                                <?php echo number_format($acceptanceRate, 2) . "%"; ?>
                            </p>
                        </div>
                    </div>

                
            </div>
        </main>
    </div>



    </body>

</html>