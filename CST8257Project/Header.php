<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="header">
    <div class="logo">
        <a href="index.php"><img src="ac_logo.png" alt="Algonquin College Logo" class="header-logo"></a>
    </div>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="<?php echo isset($_SESSION['loggedIn']) ? 'MyFriends.php' : 'Login.php'; ?>">My Friends</a></li>
            <li><a href="<?php echo isset($_SESSION['loggedIn']) ? 'MyAlbums.php' : 'Login.php'; ?>">My Albums</a></li>
            <li><a href="<?php echo isset($_SESSION['loggedIn']) ? 'MyPictures.php' : 'Login.php'; ?>">My Pictures</a></li>
            <li><a href="<?php echo isset($_SESSION['loggedIn']) ? 'UploadPictures.php' : 'Login.php'; ?>">Upload Pictures</a></li>
            <li>
                <?php if (isset($_SESSION['loggedIn'])): ?>
                    <a href="logout.php">Log Out</a>
                <?php else: ?>
                    <a href="Login.php">Log In</a>
                <?php endif; ?>
            </li>
        </ul>
    </nav>
</div>
