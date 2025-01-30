<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header('Location: Login.php');
    exit();
}

require 'db_connection.php';

// Fetch user's albums for the dropdown
$albums = [];
try {
    $stmt = $pdo->prepare("SELECT Album_Id, Title FROM Album WHERE Owner_Id = :userID");
    $stmt->execute([':userID' => $_SESSION['userID']]);
    $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Initialize variables and handle file upload
$errors = [];
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $albumId = $_POST['album'] ?? null;
    $title = trim($_POST['title'] ?? "");
    $description = trim($_POST['description'] ?? "");
    $files = $_FILES['pictures'];

    // Validation
    if (!$albumId || !is_numeric($albumId)) {
        $errors[] = "Please select a valid album.";
    }
    if (empty($files['name'][0])) {
        $errors[] = "Please select at least one picture to upload.";
    }

    // Process file upload if no errors
    if (empty($errors)) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Create the upload directory if it doesn't exist
        }

        foreach ($files['name'] as $index => $fileName) {
            $tmpName = $files['tmp_name'][$index];
            $error = $files['error'][$index];

            // Skip processing if there is an error with this file
            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = "Failed to upload file: $fileName due to an upload error.";
                continue; // Skip this file and move to the next
            }

            $uniqueName = uniqid() . "-" . basename($fileName);
            $targetFilePath = $uploadDir . $uniqueName;

            // Move the uploaded file
            if (move_uploaded_file($tmpName, $targetFilePath)) {
                try {
                    // Insert the file information into the database
                    $stmt = $pdo->prepare("
                    INSERT INTO Picture (Album_Id, File_Name, Title, Description) 
                    VALUES (:albumId, :fileName, :title, :description)
                ");
                    $stmt->execute([
                        ':albumId' => $albumId,
                        ':fileName' => $uniqueName,
                        ':title' => $title ?: basename($fileName),
                        ':description' => $description
                    ]);
                } catch (PDOException $e) {
                    $errors[] = "Error saving picture to the database: " . $e->getMessage();
                    // Delete the file if the database insert fails
                    unlink($targetFilePath);
                }
            } else {
                $errors[] = "Failed to move file: $fileName to the upload directory.";
            }
        }

        if (empty($errors)) {
            $successMessage = "Pictures uploaded successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Upload Pictures</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <?php include 'Header.php'; ?>
        <div class="main-content">
            <h2>Upload Pictures</h2>
            <p>Accepted picture types: JPG (JPEG), GIF, and PNG.</p>
            <p>You can upload multiple pictures at a time by pressing the shift key while selecting pictures.</p> 
            <p>When uploading multiple pictures, the title and description fields will be applied to all pictures.</p>

            <?php if (!empty($errors)): ?>
                <div class="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="success"><?php echo htmlspecialchars($successMessage); ?></div>
                <br>
            <?php endif; ?>

            <form action="UploadPictures.php" method="POST" enctype="multipart/form-data">
                <!-- Select Album -->
                <label for="album">Upload to Album:</label>
                <select name="album" id="album" class="custom-dropdown" required>
                    <option value="">-- Select Album --</option>
                    <?php foreach ($albums as $album): ?>
                        <option value="<?php echo htmlspecialchars($album['Album_Id']); ?>">
                            <?php echo htmlspecialchars($album['Title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Upload File -->
                <label for="pictures">File to Upload:</label>
                <label for="pictures" id="fileLabel" class="custom-file-label">
                    Choose Files
                </label>
                <input type="file" name="pictures[]" id="pictures" multiple accept=".jpg,.jpeg,.png,.gif" onchange="updateFileLabel()">

                <script>
                    function updateFileLabel() {
                        const input = document.getElementById('pictures');
                        const label = document.getElementById('fileLabel');
                        const fileCount = input.files.length;

                        if (fileCount > 0) {
                            label.textContent = `${fileCount} file(s) selected`;
                        } else {
                            label.textContent = 'Choose Files';
                        }
                    }

                    // Reset file input label when form is cleared
                    document.addEventListener('DOMContentLoaded', () => {
                        const form = document.querySelector('form');
                        form.addEventListener('reset', () => {
                            const label = document.getElementById('fileLabel');
                            label.textContent = 'Choose Files'; // Reset label text
                        });
                    });
                </script>

                <!-- Title -->
                <label for="title">Title:</label>
                <input type="text" name="title" id="title">

                <!-- Description -->
                <label for="description">Description:</label>
                <textarea name="description" id="description" rows="4"></textarea>

                <!-- Submit and Clear Buttons -->
                <br>
                <input type="submit" value="Submit">
                <input type="reset" value="Clear">
            </form>
        </div>
        <?php include 'Footer.php'; ?>
    </body>
</html>
