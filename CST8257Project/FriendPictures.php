<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header('Location: Login.php');
    exit();
}

require 'db_connection.php';

if (!isset($_GET['friendID'])) {
    die("Friend ID is required.");
}

$friendID = $_GET['friendID'];
$albums = [];
$selectedAlbum = null;
$pictures = [];
$selectedPictureID = null;
$selectedPicture = null;
$comments = [];

try {
    // Verify the friend relationship and fetch friend's name
    $stmt = $pdo->prepare("
        SELECT U.Name
        FROM User U
        INNER JOIN Friendship F 
            ON (U.UserId = F.Friend_RequesterId AND F.Friend_RequesteeId = :userID)
            OR (U.UserId = F.Friend_RequesteeId AND F.Friend_RequesterId = :userID)
        WHERE U.UserId = :friendID AND F.Status = 'accepted'
    ");
    $stmt->execute([':userID' => $_SESSION['userID'], ':friendID' => $friendID]);
    $friend = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$friend) {
        die("The specified friend is not found or not your friend.");
    }

    $_SESSION['friendName'] = $friend['Name'];

    // Fetch shared albums of the friend
    $stmt = $pdo->prepare("
        SELECT Album_Id, Title
        FROM Album
        WHERE Owner_Id = :friendID AND Accessibility_Code = 'shared'
    ");
    $stmt->execute([':friendID' => $friendID]);
    $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine the selected album
    if (!empty($albums)) {
        $selectedAlbum = $_GET['album'] ?? $albums[0]['Album_Id'];

        // Fetch pictures in the selected album
        $stmt = $pdo->prepare("
            SELECT Picture_Id, File_Name, Title, Description
            FROM Picture
            WHERE Album_Id = :albumID
        ");
        $stmt->execute([':albumID' => $selectedAlbum]);
        $pictures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($pictures)) {
            // Determine the selected picture
            $selectedPictureID = $_GET['picture'] ?? $pictures[0]['Picture_Id'];

            // Fetch the selected picture's details
            $stmt = $pdo->prepare("
                SELECT * 
                FROM Picture
                WHERE Picture_Id = :pictureID
            ");
            $stmt->execute([':pictureID' => $selectedPictureID]);
            $selectedPicture = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fetch comments for the selected picture
            $stmt = $pdo->prepare("
                SELECT U.Name, C.Comment_Text
                FROM Comment C
                INNER JOIN User U ON C.Author_Id = U.UserId
                WHERE C.Picture_Id = :pictureID
                ORDER BY C.Comment_Id DESC
            ");
            $stmt->execute([':pictureID' => $selectedPictureID]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle adding a new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commentText'])) {
    $commentText = trim($_POST['commentText']);
    if (!empty($commentText) && $selectedPictureID) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO Comment (Author_Id, Picture_Id, Comment_Text)
                VALUES (:userID, :pictureID, :commentText)
            ");
            $stmt->execute([
                ':userID' => $_SESSION['userID'],
                ':pictureID' => $selectedPictureID,
                ':commentText' => $commentText,
            ]);
            header("Location: FriendPictures.php?friendID=$friendID&album=$selectedAlbum&picture=$selectedPictureID");
            exit();
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Friend's Shared Pictures</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'Header.php'; ?>
    <div id="friend-pictures" class="main-content">
        <h2><?php echo htmlspecialchars($_SESSION['friendName']); ?>'s Shared Pictures</h2>

        <?php if (empty($albums)): ?>
            <p class="alert">No shared albums are available from this friend.</p>
        <?php else: ?>
            <!-- Album Dropdown -->
            <form method="GET" action="FriendPictures.php" class="album-selector">
                <input type="hidden" name="friendID" value="<?php echo htmlspecialchars($friendID); ?>">
                <label for="album">Select Album:</label>
                <select name="album" id="album" class="custom-dropdown" onchange="this.form.submit()">
                    <?php foreach ($albums as $album): ?>
                        <option value="<?php echo htmlspecialchars($album['Album_Id']); ?>" <?php echo $selectedAlbum == $album['Album_Id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($album['Title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if (!empty($pictures)): ?>
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
                                    <p><strong><?php echo htmlspecialchars($comment['Name']); ?>:</strong> <?php echo htmlspecialchars($comment['Comment_Text']); ?></p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No comments yet. Be the first to comment!</p>
                            <?php endif; ?>
                        </div>

                        <!-- Add Comment Form -->
                        <form method="POST" action="FriendPictures.php?friendID=<?php echo htmlspecialchars($friendID); ?>&album=<?php echo htmlspecialchars($selectedAlbum); ?>&picture=<?php echo htmlspecialchars($selectedPictureID); ?>" class="comment-form">
                            <textarea name="commentText" rows="4" placeholder="Leave a comment..." required></textarea>
                            <button type="submit">Add Comment</button>
                        </form>
                    </div>
                </div>

                <!-- Thumbnail Bar -->
                <div class="thumbnail-bar">
                    <?php foreach ($pictures as $picture): ?>
                        <a href="FriendPictures.php?friendID=<?php echo htmlspecialchars($friendID); ?>&album=<?php echo htmlspecialchars($selectedAlbum); ?>&picture=<?php echo htmlspecialchars($picture['Picture_Id']); ?>" class="thumbnail-link">
                            <img src="uploads/<?php echo htmlspecialchars($picture['File_Name']); ?>" alt="<?php echo htmlspecialchars($picture['Title']); ?>" class="thumbnail <?php echo $picture['Picture_Id'] == $selectedPictureID ? 'active' : ''; ?>">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No pictures found in this album.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php include 'Footer.php'; ?>
</body>
</html>
