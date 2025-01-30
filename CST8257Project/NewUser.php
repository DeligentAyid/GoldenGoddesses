<?php
session_start();
require 'db_connection.php'; // Connect to the database

// Validation functions
function validateUserID($userID) {
    return empty($userID) ? "User ID is required." : "";
}

function validateName($name) {
    return empty($name) ? "Name is required." : "";
}

function validatePhone($phone) {
    $pattern = "/^[2-9]\d{2}-\d{3}-\d{4}$/";
    if (empty($phone)) {
        return "Phone number is required.";
    }
    if (!preg_match($pattern, $phone)) {
        return "Phone number must be in the format nnn-nnn-nnnn.";
    }
    return "";
}

function validatePassword($password) {
    if (strlen($password) < 6 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        return "Password must be at least 6 characters long, and include at least one uppercase letter, one lowercase letter, and one digit.";
    }
    return "";
}

function checkUserIDExists($userID, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT UserId FROM User WHERE UserId = ?");
        $stmt->execute([$userID]);
        return $stmt->fetch() ? "User ID already exists." : "";
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

// Initialize error messages array and field variables
$errors = [];
$userID = $name = $phone = $password = $passwordConfirm = "";
$registrationSuccess = false;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve user inputs and trim whitespace
    $userID = trim($_POST['userID']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $passwordConfirm = trim($_POST['passwordConfirm']);
    
    // Validate each field
    $errors['userID'] = validateUserID($userID);
    $errors['name'] = validateName($name);
    $errors['phone'] = validatePhone($phone);
    $errors['password'] = validatePassword($password);

    // Check if passwords match
    if ($password !== $passwordConfirm) {
        $errors['passwordConfirm'] = "Passwords do not match.";
    }

    // Check if User ID exists in the database
    if (empty($errors['userID'])) {
        $errors['userID'] = checkUserIDExists($userID, $pdo);
    }

    // Remove empty error messages
    $errors = array_filter($errors);

    // If there are no errors, proceed to insert the new user data
    if (empty($errors)) {
        try {
            // Hash the password for secure storage
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Prepare and execute the insertion query
            $stmt = $pdo->prepare("INSERT INTO User (UserId, Name, Phone, Password) VALUES (:userID, :name, :phone, :password)");
            $stmt->bindParam(':userID', $userID);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password', $hashedPassword);

            if ($stmt->execute()) {
                // Registration successful
                $registrationSuccess = true;

                // Set session variables to log the user in
                $_SESSION['loggedIn'] = true;
                $_SESSION['userID'] = $userID;
            } else {
                $errors['database'] = "Error: Registration failed. Please try again later.";
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
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'Header.php'; ?>
    <div class="main-content">
        <h2>Sign Up</h2>
        
        <?php if ($registrationSuccess): ?>
        <p class="success">Registration successful! Redirecting you to the <b>Home</b> page...</p>
            <script>
                // Redirect after 3 seconds
                setTimeout(function() {
                    window.location.href = "Index.php";
                }, 3000);
            </script>
        <?php else: ?>
            <form action="NewUser.php" method="POST">
                <!-- User ID -->
                <label for="userID">User ID:</label>
                <input type="text" id="userID" name="userID" value="<?php echo htmlspecialchars($userID); ?>">
                <?php if (!empty($errors['userID'])) echo "<p class='alert'>" . $errors['userID'] . "</p>"; ?>

                <!-- Name -->
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
                <?php if (!empty($errors['name'])) echo "<p class='alert'>" . $errors['name'] . "</p>"; ?>

                <!-- Phone Number -->
                <label for="phone">Phone Number: (nnn-nnn-nnnn)</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                <?php if (!empty($errors['phone'])) echo "<p class='alert'>" . $errors['phone'] . "</p>"; ?>

                <!-- Password -->
                <label for="password">Password:</label>
                <input type="password" id="password" name="password">
                <?php if (!empty($errors['password'])) echo "<p class='alert'>" . $errors['password'] . "</p>"; ?>

                <!-- Password Confirm -->
                <label for="passwordConfirm">Password Again:</label>
                <input type="password" id="passwordConfirm" name="passwordConfirm">
                <?php if (!empty($errors['passwordConfirm'])) echo "<p class='alert'>" . $errors['passwordConfirm'] . "</p>"; ?>

                <!-- Display database error, if any -->
                <?php if (!empty($errors['database'])) echo "<p class='alert'>" . $errors['database'] . "</p>"; ?>

                <!-- Submit and Clear Buttons -->
                <br>
                <input type="submit" value="Submit">
                <input type="reset" value="Clear">
            </form>
        <?php endif; ?>
    </div>
    <?php include 'Footer.php'; ?>
</body>
</html>
