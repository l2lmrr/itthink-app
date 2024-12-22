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

$freelancerOffers = $pdo->prepare("
    SELECT 
        o.status, 
        o.amount, 
        p.project_title, 
        u.username AS user_name
    FROM offers o
    INNER JOIN projects p ON o.id_project = p.id_project
    INNER JOIN utilisateurs u ON p.id_user = u.id_user
    WHERE o.id_freelancer = (
        SELECT id_freelancer FROM freelancers WHERE id_user = ?
    )
");
$freelancerOffers->execute([$userId]);
$offers = $freelancerOffers->fetchAll(PDO::FETCH_ASSOC);

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

            <div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-xl font-semibold mb-6 text-gray-800">Your Offers</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-300 rounded-lg overflow-hidden">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="border px-6 py-3 text-left">Project Name</th>
                    <th class="border px-6 py-3 text-left">Client</th>
                    <th class="border px-6 py-3 text-right">Amount</th>
                    <th class="border px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                <?php if (!empty($offers)): ?>
                    <?php foreach ($offers as $offer): ?>
                        <tr class="hover:bg-gray-100 border-b border-gray-300">
                            <td class="px-6 py-4"><?php echo htmlspecialchars($offer['project_title']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($offer['user_name']); ?></td>
                            <td class="px-6 py-4 text-right"><?php echo htmlspecialchars($offer['amount']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="<?php 
                                    echo $offer['status'] === 'pending' ? 'bg-yellow-200 text-yellow-800' : 
                                         ($offer['status'] === 'in progress' ? 'bg-blue-200 text-blue-800' : 
                                         'bg-red-200 text-red-800'); 
                                    ?> px-3 py-1 rounded-full text-xs font-medium">
                                    <?php echo htmlspecialchars($offer['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-6 text-gray-500">No offers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
</div>

                
        </main>
    </div>



    </body>

</html>