<?php
session_start();
require_once "pdo.php";
date_default_timezone_set('UTC');

if (!isset($_SESSION["email"])) {
    echo "<p class='die-msg'>PLEASE LOGIN</p>";
    echo '<link rel="stylesheet" href="./style.css?v=<?php echo time(); ?>">';
    echo "<br />";
    echo "<p class='die-msg'>Redirecting in 3 seconds</p>";
    header("refresh:3;url=index.php");
    die();
}
if ($_SESSION['email'] == 'guest@guest.com') {
    echo "<p class='die-msg'>LOGGED IN AS GUEST ACCOUNT</p>";
    echo "<p class='die-msg'>EDIT ACCOUNT DETAILS NOT ALLOWED</p>";
    echo '<link rel="stylesheet" href="./style.css?v=<?php echo time(); ?>">';
    echo "<br />";
    echo "<p class='die-msg'>Redirecting in 3 seconds</p>";
    header("refresh:3;url=index.php");
    die();
}
if (isset($_SESSION["email"])) {
    $statement = $pdo->prepare("SELECT * FROM account where user_Id = :usr");
    $statement->execute(array(':usr' => $_SESSION['user_id']));
    $response = $statement->fetch();
    $pfpsrc_default = './default-pfp.png';

    if ($response['pfp'] != null) {
        $userpfp = $response['pfp'];
    } else {
        $userpfp = $pfpsrc_default;
    }
    $pfp = "<a class='pfp-link' href='./profile.php?user={$_SESSION['user_id']}'><img class='profile-img-large' src='$userpfp'></a>";
    $main = "<p id='profile-name'>{$_SESSION['name']}</p><p id='profile-email'>{$_SESSION['email']}</p>";
    $profileLink = "<a href='./profile.php?user={$_SESSION['user_id']}'>Your public profile</a>";
    $actions = '<a href="edit-account.php">Edit Account</a> | <a href="logout.php">Logout</a>';
    echo "<div id='profile'><button id='close-btn' onclick='closeProfile()'>&times;</button>{$pfp}{$main}{$actions}<br />{$profileLink}</div>";
    echo "<button id='close-btn-two' onclick='openProfile()'><img class='user-pfp' alt='user-logo' src='{$userpfp}'></button>";
}

if (isset($_POST["submit"])) {
    if (!file_exists($_FILES['fileToUpload']['tmp_name']) || !is_uploaded_file($_FILES['fileToUpload']['tmp_name'])) {
        $stmta = $pdo->prepare("SELECT pfp FROM account WHERE name=?");
        $stmta->execute([$_SESSION['name']]);
        $pfptemp = $stmta->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pfptemp as $test) {
            if ($test['pfp'] != null) {
                $base64 = $test['pfp'];
            }
        }
    } else {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        $uploadOk = 1;
        $path = $_FILES["fileToUpload"]["tmp_name"];
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    if ($check !== false) {
        $statement = $pdo->prepare("SELECT user_id FROM account where email = :em");
        $statement->execute(array(':em' => $_POST['email']));
        $checkEmail = $statement->fetch();

        if ($checkEmail['user_id'] == $_SESSION['user_id'] || $checkEmail['user_id'] == "") {
            $emailCheck = true;
        } else {
            $emailCheck = false;
        }

        if ($emailCheck != false) {
            if (isset($_POST['password'])) {
                $salt = 'XyZzy12*_';
                $newPassword = $_POST['password'];
                $hash = hash("md5", $salt . $newPassword);
            }
            if ($_POST["show_email"] == "on") {
                $show_email = "True";
            } else {
                $show_email = "False";
            }

            $sql = "UPDATE account SET pfp = :pfp, 
            name = :newName,
            email = :email,
            password = :password,
            about = :about,
            show_email = :showEmail
            WHERE user_id = :usrid";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(
                ':pfp' => $base64,
                ':usrid' => $_SESSION['user_id'],
                ':newName' => $_POST['name'],
                ':email' => $_POST['email'],
                ':password' => $hash,
                ':about' => $_POST['about'],
                ':showEmail' => $show_email
            ));
            $_SESSION['success'] = 'Account details updated.';
        } else {
            $_SESSION['error'] = 'Email taken';
        }
    } else {
        $_SESSION['error'] = "File is not an image.";
        $uploadOk = 0;
    }
    header("Location: ./index.php");
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Account</title>
    <link rel="stylesheet" href="./css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/edit-account.css?v=<?php echo time(); ?>">
    <script src="./js/jquery-3.6.0.js"></script>
    <script src="./particles/particles.js"></script>
    <script src="./js/index.js"></script>
</head>

<header class="navbar-header">
    <div class="navbar-container">
        <div>
            <a href="./index.php">
                <img class="logo" alt="logo" src="./favicon.ico">
            </a>
        </div>
        <nav>
            <ul>
                <li><a href="#">Test</a></li>
                <li><a href="#">Test</a></li>
                <li><a href="#">Test</a></li>
            </ul>
        </nav>
    </div>
</header>
<div id="particles-js"></div>

<div class="login-box">
    <form id="form" action="edit-account.php" method="post" enctype="multipart/form-data">
        Select image to upload for <?= $_SESSION['name'] ?>
        <div class="user-box">
            <input type="file" name="fileToUpload" id="fileToUpload">
        </div>
        <div class="user-box">
            <input required type="text" name="name" value="<?= $response['name'] ?>">
            <label>Name:</label>
        </div>
        <div class="user-box">
            <input required type="text" name="email" value="<?= $response['email'] ?>">
            <label>Email:</label>
        </div>
        <div class="user-box">
            <input type="text" name="about" value="<?= $response['about'] ?>">
            <label>About:</label>
        </div>
        <div class="user-box">
            <input required size='21' type="password" name="password">
            <label>New Password:</label>
        </div>
        <div class="user-box">
            <label>Show email:</label>
            <input type="checkbox" name="show_email" <?php echo ($response['show_email'] == 'True') ? 'checked' : '' ?>>
        </div>
        <br />
        <div style="float:left;">
            <input type="submit" value="Submit Changes" class="btn" name="submit">
            <a href="./index.php" class="btn">Cancel</a>
        </div>
    </form>
</div>