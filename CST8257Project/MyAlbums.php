<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    // Capture the current URL as the intended redirect target
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];

    // Debugging: Log captured URL
    error_log("Captured redirect URL in MyAlbums.php: " . $_SESSION['redirect_url']);

    // Redirect to Login.php
    header("Location: Login.php");
    exit();
}

require 'db_connection.php'; // Include database connection

// Fetch user's name if not already in session
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

// Fetch user's albums
$albums = [];
try {
    $stmt = $pdo->prepare("
        SELECT Album.Album_Id, Album.Title, Album.Description, Album.Accessibility_Code,
               (SELECT COUNT(*) FROM Picture WHERE Picture.Album_Id = Album.Album_Id) AS PictureCount
        FROM Album
        WHERE Album.Owner_Id = :userID
    ");
    $stmt->execute([':userID' => $_SESSION['userID']]);
    $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch accessibility options
$accessibilityOptions = [];
try {
    $stmt = $pdo->prepare("SELECT Accessibility_Code, Description FROM Accessibility");
    $stmt->execute();
    $accessibilityOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission to save accessibility changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveChanges'])) {
    $updatedAccessibility = $_POST['accessibility'] ?? [];
    try {
        $stmt = $pdo->prepare("UPDATE Album SET Accessibility_Code = :accessibility WHERE Album_Id = :albumId AND Owner_Id = :ownerId");
        foreach ($updatedAccessibility as $albumId => $accessibilityCode) {
            $stmt->execute([
                ':accessibility' => $accessibilityCode,
                ':albumId' => $albumId,
                ':ownerId' => $_SESSION['userID']
            ]);
        }
        // Refresh the page to show updated accessibility
        header('Location: MyAlbums.php');
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Handle album deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteAlbum'])) {
    $albumIdToDelete = $_POST['deleteAlbum'];
    try {
        // Fetch all picture file names for the album
        $stmt = $pdo->prepare("SELECT File_Name FROM Picture WHERE Album_Id = :albumId");
        $stmt->execute([':albumId' => $albumIdToDelete]);
        $filesToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Delete all comments associated with pictures in the album
        $stmt = $pdo->prepare("
            DELETE FROM Comment 
            WHERE Picture_Id IN (SELECT Picture_Id FROM Picture WHERE Album_Id = :albumId)
        ");
        $stmt->execute([':albumId' => $albumIdToDelete]);

        // Delete all pictures associated with the album
        $stmt = $pdo->prepare("DELETE FROM Picture WHERE Album_Id = :albumId");
        $stmt->execute([':albumId' => $albumIdToDelete]);

        // Delete the album itself
        $stmt = $pdo->prepare("DELETE FROM Album WHERE Album_Id = :albumId AND Owner_Id = :ownerId");
        $stmt->execute([
            ':albumId' => $albumIdToDelete,
            ':ownerId' => $_SESSION['userID']
        ]);

        // Remove the files from the uploads folder
        $uploadDir = "uploads/";
        foreach ($filesToDelete as $file) {
            $filePath = $uploadDir . $file['File_Name'];
            if (file_exists($filePath)) {
                unlink($filePath); // Delete the file
            }
        }

        // Refresh the page to reflect the changes
        header('Location: MyAlbums.php');
        exit();
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Albums</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function confirmDeletion() {
            return confirm("Are you sure you want to delete this album? All pictures in the album will also be deleted.");
        }
    </script>
</head>
<body>
    <?php include 'Header.php'; ?>
    <div class="main-content">
        <h2>My Albums</h2>
        <p>Welcome <strong><?php echo htmlspecialchars($_SESSION['userName']); ?></strong>! (Not you? change user <a href="Logout.php">here</a>)</p>
        <a href="AddAlbum.php" class="button">Create a New Album</a>
        <form action="MyAlbums.php" method="post">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Number of Pictures</th>
                        <th>Accessibility</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($albums)): ?>
                        <?php foreach ($albums as $album): ?>
                            <tr>
                                <td>
                                    <a href="MyPictures.php?albumId=<?php echo htmlspecialchars($album['Album_Id']); ?>">
                                        <?php echo htmlspecialchars($album['Title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($album['PictureCount']); ?></td>
                                <td>
                                    <select name="accessibility[<?php echo htmlspecialchars($album['Album_Id']); ?>]" class="custom-dropdown">
                                        <?php foreach ($accessibilityOptions as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option['Accessibility_Code']); ?>"
                                                <?php echo $option['Accessibility_Code'] === $album['Accessibility_Code'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($option['Description']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" name="deleteAlbum" value="<?php echo htmlspecialchars($album['Album_Id']); ?>" onclick="return confirmDeletion();">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No albums found!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <input type="submit" name="saveChanges" value="Save Changes">
        </form>
    </div>
    <?php include 'Footer.php'; ?>
</body>
</html>
