<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header('Location: Login.php');
    exit();
}

require 'db_connection.php';

// Fetch user's name and store it in the session if not already fetched
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

$message = "";
$friendID = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $friendID = trim($_POST['friendID']);

    // Validation
    if (empty($friendID)) {
        $message = "<p class='alert'>Friend ID is required.</p>";
    } elseif ($friendID === $_SESSION['userID']) {
        $message = "<p class='alert'>You cannot send a friend request to yourself.</p>";
    } else {
        try {
            // Check if the friend ID exists
            $stmt = $pdo->prepare("SELECT Name FROM User WHERE UserId = :friendID");
            $stmt->execute([':friendID' => $friendID]);
            $friend = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$friend) {
                $message = "<p class='alert'>User ID does not exist.</p>";
            } else {
                // Check if they are already friends
                $stmt = $pdo->prepare("
                    SELECT * FROM Friendship
                    WHERE (Friend_RequesterId = :userID AND Friend_RequesteeId = :friendID)
                       OR (Friend_RequesterId = :friendID AND Friend_RequesteeId = :userID)
                ");
                $stmt->execute([':userID' => $_SESSION['userID'], ':friendID' => $friendID]);
                $existingRelationship = $stmt->fetch();

                if ($existingRelationship) {
                    if ($existingRelationship['Status'] === 'accepted') {
                        $message = "<p class='alert'>You and {$friend['Name']} ({$friendID}) are already friends.</p>";
                    } elseif ($existingRelationship['Friend_RequesterId'] === $friendID && $existingRelationship['Status'] === 'request') {
                        // Accept the friend request if the other user already sent one
                        $stmt = $pdo->prepare("
                            UPDATE Friendship
                            SET Status = 'accepted'
                            WHERE Friend_RequesterId = :friendID AND Friend_RequesteeId = :userID
                        ");
                        $stmt->execute([':friendID' => $friendID, ':userID' => $_SESSION['userID']]);
                        $message = "<p class='success'>You and {$friend['Name']} ({$friendID}) are now friends.</p>";
                    } else {
                        $message = "<p class='alert'>Friend request already sent.</p>";
                    }
                } else {
                    // Send a new friend request
                    $stmt = $pdo->prepare("
                        INSERT INTO Friendship (Friend_RequesterId, Friend_RequesteeId, Status)
                        VALUES (:userID, :friendID, 'request')
                    ");
                    $stmt->execute([':userID' => $_SESSION['userID'], ':friendID' => $friendID]);
                    $message = "<p class='success'>Your request has been sent to {$friend['Name']} (ID: {$friendID}). Once {$friend['Name']} accepts your request, {$friend['Name']} will be friends and be able to view each other's shared albums.</p>";
                }
            }
        } catch (PDOException $e) {
            $message = "<p class='alert'>Database error: " . $e->getMessage() . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Friend</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'Header.php'; ?>
    <div class="main-content">
        <h2>Add Friend</h2>
        <p>Welcome <?php echo htmlspecialchars($_SESSION['userName']); ?>! (not you? <a href="Logout.php">change user</a>)</p>
        
        <form action="AddFriend.php" method="POST">
            <label for="friendID">Enter the ID of the user you want to befriend:</label>
            <input type="text" id="friendID" name="friendID" value="<?php echo htmlspecialchars($friendID); ?>">
            <button type="submit">Send Friend Request</button>
        </form>

        <?php if (!empty($message)) echo $message; ?>
    </div>
    <?php include 'Footer.php'; ?>
</body>
</html>
