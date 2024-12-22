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


            <div class="bg-gray-50 shadow-lg rounded-lg p-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Offers for Your Projects</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse bg-white shadow-sm rounded-lg overflow-hidden">
            <thead class="bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 text-sm uppercase tracking-wide">
                <tr>
                    <th class="border-b px-6 py-4 text-left font-medium">Project Name</th>
                    <th class="border-b px-6 py-4 text-right font-medium">Offer Amount</th>
                    <th class="border-b px-6 py-4 text-left font-medium">Freelancer</th>
                    <th class="border-b px-6 py-4 text-left font-medium">Skills</th>
                    <th class="border-b px-6 py-4 text-center font-medium">Deadline</th>
                    <th class="border-b px-6 py-4 text-center font-medium">Status</th>
                    <th class="border-b px-6 py-4 text-center font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm text-gray-600">
                <?php if (!empty($offersList)): ?>
                    <?php foreach ($offersList as $offer): ?>
                        <tr class="hover:bg-gray-100 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-800"><?php echo htmlspecialchars($offer['project_title']); ?></td>
                            <td class="px-6 py-4 text-right text-green-600 font-medium"><?php echo htmlspecialchars($offer['amount']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($offer['freelancer_name']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($offer['skills']); ?></td>
                            <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($offer['deadline']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="<?php 
                                    echo $offer['status'] === 'pending' ? 'bg-yellow-100 text-yellow-600' : 
                                         ($offer['status'] === 'in progress' ? 'bg-blue-100 text-blue-600' : 
                                         'bg-red-100 text-red-600'); 
                                    ?> px-3 py-1 rounded-full text-xs font-medium">
                                    <?php echo htmlspecialchars(ucfirst($offer['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($offer['status'] === 'pending'): ?>
                                    <form method="post" class="inline-block">
                                        <input type="hidden" name="id_offer" value="<?php echo $offer['id_offer']; ?>">
                                        <button name="accept_offer" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600 transition">
                                            Accept
                                        </button>
                                    </form>
                                    <form method="post" class="inline-block ml-2">
                                        <input type="hidden" name="id_offer" value="<?php echo $offer['id_offer']; ?>">
                                        <button name="reject_offer" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600 transition">
                                            Reject
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-500 font-medium"><?php echo ucfirst($offer['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500 font-medium">No offers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

       

    </body>

</html>