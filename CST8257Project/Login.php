<?php
// Ensure session is active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Session timeout handling
if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} elseif (time() - $_SESSION['LAST_ACTIVITY'] > 1800) { // 30-minute timeout
    session_unset();
    session_destroy();
    header("Location: Login.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

require 'db_connection.php'; // Include database connection
// Initialize error messages array
$errors = [];
$userID = $password = "";

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Debugging: Log redirect_url session variable for verification
error_log("Session 'redirect_url' before POST: " . ($_SESSION['redirect_url'] ?? 'None'));

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and trim user inputs
    $userID = trim($_POST['userID']);
    $password = trim($_POST['password']);

    // Validation: Check if fields are empty
    if (empty($userID)) {
        $errors['userID'] = "User ID is required.";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required.";
    }

    // CSRF token verification
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors['csrf'] = "Invalid CSRF token.";
    }

    // If no validation errors, proceed with login check
    if (empty($errors)) {
        try {
            // Prepare and execute a query to retrieve user with matching User ID
            $stmt = $pdo->prepare("SELECT Password, Name FROM User WHERE UserId = :userID");
            $stmt->bindParam(':userID', $userID);
            $stmt->execute();
            $user = $stmt->fetch();

            // Check if user exists and password matches
            if ($user && password_verify($password, $user['Password'])) {
                // Successful login: Set session variables
                session_regenerate_id(true); // Regenerate session ID for security
                $_SESSION['loggedIn'] = true;
                $_SESSION['userID'] = $userID;
                $_SESSION['userName'] = $user['Name'];

                // Debugging: Log redirect URL session variable before redirection
                error_log("Session 'redirect_url' on Login.php POST: " . ($_SESSION['redirect_url'] ?? 'None'));

                // Redirect to the intended page or fallback to Index.php
                $redirectUrl = $_SESSION['redirect_url'] ?? 'Index.php';
                error_log("Final redirect URL after login: $redirectUrl"); // Debugging
                unset($_SESSION['redirect_url']); // Clear the redirect URL after use

                header("Location: $redirectUrl");
                exit();
            } else {
                // Invalid credentials error
                $errors['credentials'] = "Invalid User ID or Password.";
            }
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
        <title>Log In</title>
        <link rel="stylesheet" href="styles.css">
        <script>
            // Autofocus on User ID field
            window.onload = function () {
                document.getElementById('userID').focus();
            };
        </script>
    </head>
    <body>
        <?php include 'Header.php'; ?>
        <div class="main-content">
            <h2>Log In</h2>
            <p>You need to <a href="NewUser.php">sign up</a> if you are a new user</p>

            <form action="Login.php" method="POST">
                <!-- User ID -->
                <label for="userID">User ID:</label>
                <input type="text" id="userID" name="userID" value="<?php echo htmlspecialchars($userID); ?>">
                <?php if (!empty($errors['userID'])) echo "<p class='alert'>" . $errors['userID'] . "</p>"; ?>

                <!-- Password -->
                <label for="password">Password:</label>
                <input type="password" id="password" name="password">
                <?php if (!empty($errors['password'])) echo "<p class='alert'>" . $errors['password'] . "</p>"; ?>

                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Display error message for invalid credentials -->
                <?php if (!empty($errors['credentials'])) echo "<p class='alert'>" . $errors['credentials'] . "</p>"; ?>

                <!-- Display CSRF token error -->
                <?php if (!empty($errors['csrf'])) echo "<p class='alert'>" . $errors['csrf'] . "</p>"; ?>

                <!-- Display database error if any -->
                <?php if (!empty($errors['database'])) echo "<p class='alert'>" . $errors['database'] . "</p>"; ?>

                <!-- Submit and Clear Buttons -->
                <br>
                <input type="submit" value="Submit">
                <input type="reset" value="Clear">
            </form>
        </div>
        <?php include 'Footer.php'; ?>
    </body>
</html>
