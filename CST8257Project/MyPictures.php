<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header('Location: Login.php');
    exit();
}

require 'db_connection.php';

// Fetch user's albums for the dropdown
$albums = [];
$pictures = [];
$selectedAlbum = null;
$selectedPicture = null;
$comments = [];
$errors = [];

// Fetch albums for the logged-in user
try {
    $stmt = $pdo->prepare("SELECT Album_Id, Title FROM Album WHERE Owner_Id = :userID");
    $stmt->execute([':userID' => $_SESSION['userID']]);
    $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle album selection
// Determine the selected album from the query string or POST data
if (isset($_GET['albumId']) && is_numeric($_GET['albumId'])) {
    $selectedAlbum = $_GET['albumId'];
} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['album'])) {
    $selectedAlbum = $_POST['album'];
} elseif (!empty($albums)) {
    $selectedAlbum = $albums[0]['Album_Id'];
} else {
    $selectedAlbum = null; // Default to null if no album is available
}

// Fetch pictures for the selected album
if ($selectedAlbum) {
    try {
        $stmt = $pdo->prepare("SELECT Picture_Id, File_Name, Title, Description FROM Picture WHERE Album_Id = :albumId");
        $stmt->execute([':albumId' => $selectedAlbum]);
        $pictures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $selectedPicture = $pictures[0] ?? null;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Handle picture selection
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pictureId'])) {
    foreach ($pictures as $picture) {
        if ($picture['Picture_Id'] == $_POST['pictureId']) {
            $selectedPicture = $picture;
            break;
        }
    }
}

// Fetch comments for the selected picture
if ($selectedPicture) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.Comment_Text, u.Name AS Author
            FROM Comment c
            INNER JOIN User u ON c.Author_Id = u.UserId
            WHERE c.Picture_Id = :pictureId
            ORDER BY c.Comment_Id DESC
        ");
        $stmt->execute([':pictureId' => $selectedPicture['Picture_Id']]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Handle comment submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['comment'])) {
    $commentText = trim($_POST['comment']);
    if (empty($commentText)) {
        $errors[] = "Comment cannot be empty.";
    } elseif ($selectedPicture) {
        try {
            $stmt = $pdo->prepare("INSERT INTO Comment (Author_Id, Picture_Id, Comment_Text) VALUES (:authorId, :pictureId, :commentText)");
            $stmt->execute([
                ':authorId' => $_SESSION['userID'],
                ':pictureId' => $selectedPicture['Picture_Id'],
                ':commentText' => $commentText
            ]);
            header("Location: MyPictures.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error adding comment: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>My Pictures</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <?php include 'Header.php'; ?>
        <div id="my-pictures" class="main-content">
            <h2>My Pictures</h2>

            <!-- Album Dropdown -->
            <form method="POST" action="MyPictures.php" class="album-selector">
                <label for="album">Select Album:</label>
                <select name="album" id="album" class="custom-dropdown" onchange="this.form.submit()">
                    <?php foreach ($albums as $album): ?>
                        <option value="<?php echo htmlspecialchars($album['Album_Id']); ?>" <?php echo $selectedAlbum == $album['Album_Id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($album['Title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if ($selectedPicture): ?>
                <div class="gallery-container">
                    <!-- Image Viewer -->
                    <div class="image-viewer">
                        <img src="uploads/<?php echo htmlspecialchars($selectedPicture['File_Name']); ?>" alt="<?php echo htmlspecialchars($selectedPicture['Title']); ?>" class="main-picture">
                    </div>

                    <!-- Comments Section -->
                    <div class="comments-container">
                        <h3><?php echo htmlspecialchars($selectedPicture['Title']); ?></h3>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($selectedPicture['Description'] ?? 'No description available.'); ?></p>

                        <h4>Comments:</h4>
                        <div class="comments">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <p><strong><?php echo htmlspecialchars($comment['Author']); ?>:</strong> <?php echo htmlspecialchars($comment['Comment_Text']); ?></p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No comments yet. Be the first to comment!</p>
                            <?php endif; ?>
                        </div>

                        <!-- Add Comment Form -->
                        <form method="POST" action="MyPictures.php" class="comment-form">
                            <textarea name="comment" rows="4" placeholder="Leave a comment..." required></textarea>
                            <input type="hidden" name="pictureId" value="<?php echo htmlspecialchars($selectedPicture['Picture_Id']); ?>">
                            <button type="submit">Add Comment</button>
                        </form>
                    </div>
                </div>

                <!-- Thumbnail Bar -->
                <div class="thumbnail-bar">
                    <?php foreach ($pictures as $picture): ?>
                        <form method="POST" action="MyPictures.php" class="thumbnail-form">
                            <input type="hidden" name="pictureId" value="<?php echo htmlspecialchars($picture['Picture_Id']); ?>">
                            <input type="hidden" name="album" value="<?php echo htmlspecialchars($selectedAlbum); ?>">
                            <button type="submit" class="thumbnail-btn <?php echo $selectedPicture['Picture_Id'] == $picture['Picture_Id'] ? 'active' : ''; ?>">
                                <img src="uploads/<?php echo htmlspecialchars($picture['File_Name']); ?>" alt="Thumbnail" class="thumbnail">
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <p>No pictures available in this album.</p>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php include 'Footer.php'; ?>
    </body>
</html>

