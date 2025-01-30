<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header('Location: Login.php');
    exit();
}

require 'db_connection.php'; // Include database connection
// Fetch user's friends
try {
    $stmt = $pdo->prepare("
        SELECT U.UserId AS FriendID, U.Name, 
               (SELECT COUNT(*) FROM Album 
                WHERE Owner_Id = U.UserId AND Accessibility_Code = 'shared') AS SharedAlbums
        FROM User U
        INNER JOIN Friendship F 
            ON (U.UserId = F.Friend_RequesterId AND F.Friend_RequesteeId = :userID)
            OR (U.UserId = F.Friend_RequesteeId AND F.Friend_RequesterId = :userID)
        WHERE F.Status = 'accepted'
    ");
    $stmt->execute([':userID' => $_SESSION['userID']]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch pending friend requests
try {
    $stmt = $pdo->prepare("
        SELECT U.UserId AS RequesterID, U.Name
        FROM User U
        INNER JOIN Friendship F 
            ON U.UserId = F.Friend_RequesterId
        WHERE F.Friend_RequesteeId = :userID AND F.Status = 'request'
    ");
    $stmt->execute([':userID' => $_SESSION['userID']]);
    $friendRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle defriend and accept/deny actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['defriendSelected']) && isset($_POST['defriend'])) {
            $stmt = $pdo->prepare("
                DELETE FROM Friendship
                WHERE (Friend_RequesterId = :userID AND Friend_RequesteeId = :friendID)
                   OR (Friend_RequesterId = :friendID AND Friend_RequesteeId = :userID)
            ");
            foreach ($_POST['defriend'] as $friendID) {
                $stmt->execute([':userID' => $_SESSION['userID'], ':friendID' => $friendID]);
            }
        }

        if (isset($_POST['acceptSelected']) && isset($_POST['requests'])) {
            $stmt = $pdo->prepare("
                UPDATE Friendship
                SET Status = 'accepted'
                WHERE Friend_RequesterId = :requesterID AND Friend_RequesteeId = :userID
            ");
            foreach ($_POST['requests'] as $requesterID) {
                $stmt->execute([':userID' => $_SESSION['userID'], ':requesterID' => $requesterID]);
            }
        }

        if (isset($_POST['denySelected']) && isset($_POST['requests'])) {
            $stmt = $pdo->prepare("
                DELETE FROM Friendship
                WHERE Friend_RequesterId = :requesterID AND Friend_RequesteeId = :userID
            ");
            foreach ($_POST['requests'] as $requesterID) {
                $stmt->execute([':userID' => $_SESSION['userID'], ':requesterID' => $requesterID]);
            }
        }
        header('Location: MyFriends.php');
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
        <title>My Friends</title>
        <link rel="stylesheet" href="styles.css">
        <script>
            function confirmDefriend() {
                return confirm("The selected friends will be defriended!");
            }
        </script>
    </head>
    <body>
        <?php include 'Header.php'; ?>
        <div class="main-content">
            <h2>My Friends</h2>
            <p>Welcome <strong><?php echo htmlspecialchars($_SESSION['userName']); ?></strong>! (Not you? change user <a href="Logout.php">here</a>)</p>

            <!-- Add Friends Button -->
            <a href="AddFriend.php" class="button">Add New Friends</a> <!-- Add this button -->

            <form action="MyFriends.php" method="post">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Shared Albums</th>
                            <th>Defriend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($friends)): ?>
                            <?php foreach ($friends as $friend): ?>
                                <tr>
                                    <td>
                                        <a href="FriendPictures.php?friendID=<?php echo htmlspecialchars($friend['FriendID']); ?>">
                                            <?php echo htmlspecialchars($friend['Name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($friend['SharedAlbums']); ?></td>
                                    <td>
                                        <input type="checkbox" name="defriend[]" value="<?php echo htmlspecialchars($friend['FriendID']); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">You have no friends.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <input type="submit" name="defriendSelected" value="Defriend Selected" onclick="return confirmDefriend();">
            </form>

            <h3>Friend Requests</h3>
            <form action="MyFriends.php" method="post">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Accept or Deny</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($friendRequests)): ?>
                            <?php foreach ($friendRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['Name']); ?></td>
                                    <td>
                                        <input type="checkbox" name="requests[]" value="<?php echo htmlspecialchars($request['RequesterID']); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No pending friend requests.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <input type="submit" name="acceptSelected" value="Accept Selected">
                <input type="submit" name="denySelected" value="Deny Selected">
            </form>
        </div>

        <?php include 'Footer.php'; ?>
    </body>
</html>