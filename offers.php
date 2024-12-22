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

// Fetch Categories and Subcategories
$projects = $pdo->query("SELECT id_project, project_title FROM projects")->fetchAll(PDO::FETCH_ASSOC);


// Add Offer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_offer'])) {
    // Ensure required POST data is set
    if (isset($_POST['id_project'], $_POST['amount'], $_POST['deadline']) && !empty($_POST['id_project'])) {
        $idProject = $_POST['id_project'];
        $amount = $_POST['amount'];
        $deadline = $_POST['deadline'];

        $currentDate = date('Y-m-d');
        if (DateTime::createFromFormat('Y-m-d', $deadline) === false || $deadline < $currentDate) {
            echo "Error: The deadline must be today or a future date.";
            exit;
        }

        if ($amount <= 0) {
            echo "Error: Offer price must be greater than zero.";
            exit;
        }

        $stmt = $pdo->prepare("SELECT id_freelancer FROM freelancers WHERE id_user = ?");
        $stmt->execute([$userId]);
        $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($freelancer) {
            $idFreelancer = $freelancer['id_freelancer'];

            // Insert the new offer into the database
            $stmt = $pdo->prepare("INSERT INTO offers (amount, deadline, id_freelancer, id_project) VALUES (?, ?, ?, ?)");
            $stmt->execute([$amount, $deadline, $idFreelancer, $idProject]);

            // Redirect to prevent form resubmission
            header("Location: offers.php");
            exit;
        } else {
            echo "Error: Freelancer not found.";
        }
    } else {
        echo "Error: Please fill all required fields.";
    }
}

// Update offer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_offer'])) {
    $offerId = $_POST['id_offer'];
    $amount = $_POST['amount'];
    $deadline = $_POST['deadline'];

    // Validate the deadline
    $currentDate = date('Y-m-d');
    if (DateTime::createFromFormat('Y-m-d', $deadline) === false || $deadline < $currentDate) {
        echo "Error: The deadline must be today or a future date.";
        exit;
    }

    // Validate the amount
    if ($amount <= 0) {
        echo "Error: The amount must be greater than zero.";
        exit;
    }

    // Update the offer in the database
    $stmt = $pdo->prepare("UPDATE offers SET amount = ?, deadline = ? WHERE id_offer = ?");
    $stmt->execute([$amount, $deadline, $offerId]);

    // Redirect to prevent form resubmission
    header("Location: offers.php");
    exit;
}


// Delete Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_offer'])) {
    $offerId = $_POST['id_offer'];

    $stmt = $pdo->prepare("DELETE FROM offers WHERE id_offer = ?");
    $stmt->execute([ $offerId]);

    header("Location: offers.php");
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
                <!-- Filter Row -->
                
        <div class="mb-6">
            <button id="newOffertButton" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">New Project</button>
            <button id="editOffertButton" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Edit Project</button>
            <button id="deleteOfferButton" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Delete</button>

        </div>

        <!-- New offer Form -->
        <div id="newOfferForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
            <select name="id_project" id="id_project" class="w-full p-2 border rounded" onchange="filterSubcategories()" required>
                    <option value="">Select a Project</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id_project']; ?>">
                            <?php echo htmlspecialchars($project['project_title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="amount" placeholder="Offer Price" class="w-full p-2 border rounded" required>
                <input type="date" name="deadline" placeholder="Work Deadline" class="w-full p-2 border rounded" required>
                <button type="submit" name="add_offer" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Make an Offer</button>
            </form>
        </div>

            <!-- Edit Offer Form -->
            <div id="editOfferForm" style="display: none;" class="mb-6">
                <form method="post" class="space-y-4">
                    <label for="offerSelect">Select an Offer to edit:</label>
                    <select name="id_offer" id="offerSelect" class="w-full p-2 border rounded" required>
                        <option value="">Select Offer</option>
                        <?php foreach ($offers as $offer): ?>
                            <option value="<?php echo $offer['id_offer']; ?>">
                                <?php echo htmlspecialchars($offer['project_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="amount" placeholder="New Price of the Offer" class="w-full p-2 border rounded" required>
                    <input type="date" name="deadline" placeholder="New Deadline of the Offer" class="w-full p-2 border rounded" required>
                    <button type="submit" name="update_offer" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update Offer</button>
                </form>
            </div>


        
                <!-- Delete Project Selection -->
                <div id="deleteOfferForm" style="display: none;" class="mb-6">
            <form method="post" class="space-y-4">
                <label for="projectSelect">Select a project to edit:</label>
                <select name="id_offer" id="offerSelect" class="w-full p-2 border rounded" required>
                        <option value="">Select Offer</option>
                        <?php foreach ($offers as $offer): ?>
                            <option value="<?php echo $offer['id_offer']; ?>">
                                <?php echo htmlspecialchars($offer['project_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <button type="submit" name="delete_offer" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Delete</button>
            </form>
        </div>

                <!-- Prj Table -->
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                           
                            <th class="py-2 px-12 text-left">Project Title</th>
                            <th class="py-2 px-12 text-left">Offer Price</th>
                            <th class="py-2 px-12 text-left">Deadline</th>
                        </tr>
                    </thead>
                    </thead>
                    <tbody>
                <?php if (!empty($offers)): ?>
                    <?php foreach ($offers as $offer): ?>
                        <tr class="border-b">
                            <td class="py-2 px-10"><?php echo htmlspecialchars($offer['project_title']); ?></td>
                            <td class="py-2 px-10"><?php echo htmlspecialchars($offer['offer_price']); ?></td>
                            <td class="py-2 px-10"><?php echo htmlspecialchars($offer['deadline']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-2">No offers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
                    
                </table>
            </div>
        </main>
    </div>


    <script>
        document.getElementById('newOffertButton').addEventListener('click', function() {
            const form = document.getElementById('newOfferForm');
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
        document.getElementById('editOffertButton').addEventListener('click', function() {
            const form = document.getElementById('editOfferForm');
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
        document.getElementById('deleteOfferButton').addEventListener('click', function() {
            const form = document.getElementById('deleteOfferForm');
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