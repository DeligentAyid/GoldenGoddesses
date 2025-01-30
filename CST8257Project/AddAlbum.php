<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header('Location: Login.php');
    exit();
}

require 'db_connection.php'; // Include database connection

// Fetch user's name and store it in the session
if (!isset($_SESSION['userName'])) {
    try {
        $stmt = $pdo->prepare("SELECT Name FROM User WHERE UserId = :userID");
        $stmt->execute([':userID' => $_SESSION['userID']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['userName'] = $user['Name'];
        } else {
            die("User not found in the database.");
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Fetch accessibility options from the database
$accessibilityOptions = [];
try {
    $stmt = $pdo->query("SELECT Accessibility_Code, Description FROM Accessibility");
    $accessibilityOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
$errors = [];
$title = $description = $accessibilityCode = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and trim user inputs
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $accessibilityCode = trim($_POST['accessibility']);

    // Validate inputs
    if (empty($title)) {
        $errors['title'] = "Title is required.";
    }
    if (empty($accessibilityCode)) {
        $errors['accessibility'] = "Accessibility option is required.";
    } elseif (!in_array($accessibilityCode, array_column($accessibilityOptions, 'Accessibility_Code'))) {
        $errors['accessibility'] = "Invalid accessibility option.";
    }

    // If no errors, insert the album into the database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO Album (Title, Description, Owner_Id, Accessibility_Code)
                VALUES (:title, :description, :ownerId, :accessibility)
            ");
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':ownerId' => $_SESSION['userID'],
                ':accessibility' => $accessibilityCode
            ]);
            $successMessage = "Album successfully created!";
            $title = $description = $accessibilityCode = ""; // Clear the form fields
        } catch (PDOException $e) {
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Add Album</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <?php include 'Header.php'; ?>
        
        <div class="main-content">
            <h2>Create New Album</h2>
            <p>Welcome <strong><?php echo htmlspecialchars($_SESSION['userName']); ?></strong>! (Not you? change user <a href="Logout.php">here</a>)</p>
            
            <?php if (!empty($successMessage)): ?>
                <p class="success"><?php echo $successMessage; ?></p>
            <?php endif; ?>

            <form action="AddAlbum.php" method="POST">
                <!-- Title -->
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>">
                <?php if (!empty($errors['title'])) echo "<p class='alert'>" . $errors['title'] . "</p>"; ?>

                <!-- Accessibility -->
                <label for="accessibility">Accessibility:</label>
                <select id="accessibility" name="accessibility" class="custom-dropdown">
                    <option value="">Select Accessibility</option>
                    <?php foreach ($accessibilityOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option['Accessibility_Code']); ?>"
                            <?php if ($option['Accessibility_Code'] == $accessibilityCode) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($option['Description']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['accessibility'])) echo "<p class='alert'>" . $errors['accessibility'] . "</p>"; ?>

                <!-- Description -->
                <label for="description">Description:</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>

                <!-- Submit and Clear Buttons -->
                <br>
                <input type="submit" value="Submit">
                <input type="reset" value="Clear">
            </form>
        </div>
        <?php include 'Footer.php'; ?>
    </body>
</html>
