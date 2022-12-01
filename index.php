<?php
session_start();
require_once "pdo.php";
date_default_timezone_set('UTC');

if (isset($_POST['logout'])) {
    header('Location: logout.php');
    return;
}

if (isset($_SESSION['email'])) {
    $stmt = $pdo->query("SELECT * FROM account");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT * FROM account WHERE user_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT * FROM user_status_log where user_Id = :usr");
    $stmt->execute(array(':usr' => $_SESSION['user_id']));
    $user_status_log = $stmt->fetch();
    $pfpsrc_default = './default-pfp.png';

    if ($user[0]['pfp'] != null) {
        $userpfp = $user[0]['pfp'];
    } else {
        $userpfp = $pfpsrc_default;
    }

    if ($user_status_log != null) {
        $stmt = $pdo->prepare("UPDATE user_status_log SET account=?, last_active_date_time=? WHERE user_id=?");
        $stmt->execute([$_SESSION['name'], date(DATE_RFC2822), $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO user_status_log (user_id, account, last_active_date_time) VALUES (:usr, :acc, :date)');
        $stmt->execute(
            array(
                ':usr' => $_SESSION['user_id'],
                ':acc' => $_SESSION['name'],
                ':date' => date(DATE_RFC2822)
            )
        );
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>G4o2 Chat</title>
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
    <link rel="stylesheet" href="./css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/index.css?=<?php echo time(); ?>">
    <script type="text/javascript" src="./js/jquery-3.6.0.js"></script>
    <script src="./particles/particles.js"></script>
    <script src="./js/index.js"></script>
    <script>
        $.getJSON('https://g4o2-chat-api.maxhu787.repl.co/accounts', function(data) {
            console.log(data)
        });
    </script>
</head>

<body>
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
    <div class="container">
        <h1 class='rainbow_text_animated'>Welcome to g4o2</h1>
        <p>Still in development</p>
        <?php
        if (isset($_SESSION["success"])) {
            echo ('<p class="success popup-msg">' . htmlentities($_SESSION["success"]) . "</p>");
            unset($_SESSION["success"]);
            echo "";
        }
        if (isset($_SESSION["error"])) {
            echo ('<p class="error popup-msg">' . htmlentities($_SESSION["error"]) . "</p>");
            unset($_SESSION["error"]);
            echo "";
        }
        if (isset($_SESSION['email'])) {
            echo '<a class="chat-btn btn" href="chat.php"><p class="rainbow_text_animated">CHAT</p></a></p>
            <table border="5">
            <thead>
                <tr>
                    <th>user_id</th>
                    <th>Profile</th>
                    <th>Email</th>
                    <th>Last online</th>
                </tr>
            </thead>';

            foreach ($accounts as $account) {
                if ($account['pfp'] != null) {
                    $pfpsrc = $account['pfp'];
                } else {
                    $pfpsrc = $pfpsrc_default;
                }

                $pfp = "<a class='pfp-link' href='./profile.php?user={$account['user_id']}'><img style='margin-left: 10px;' class='profile-img' src='$pfpsrc'></a>";

                $statement = $pdo->prepare("SELECT * FROM user_status_log where user_Id = :usr");
                $statement->execute(array(':usr' => $account['user_id']));
                $user_status_log = $statement->fetch();

                $userStatus = "Undefined";
                if ($user_status_log != null) {
                    $userStatus = $user_status_log['last_active_date_time'];
                }


                if ($userStatus === "Undefined") {
                    $diff = "<p style='color:red;'>Undefined</p>";
                } else {
                    $last_online    = $userStatus;
                    $current_date_time = date(DATE_RFC2822);
                    $last_online     = new DateTime($last_online);
                    $current_date_time = new DateTime($current_date_time);

                    $diff = $current_date_time->diff($last_online)->format("last online %a days %h hours and %i minutes ago");
                    $exploded = explode(" ", $diff);

                    if ($exploded[2] == "1") {
                        $diff = "<p style='color:#ffc200;'>Last online $exploded[2] day ago</p>";
                    } elseif ($exploded[4] == "1") {
                        $diff = "<p style='color:#ffc200;'>Last online $exploded[4] hour ago</p>";
                    } elseif ($exploded[7] == "1") {
                        $diff = "<p style='color:#ffc200;'>Last online $exploded[7] minute ago</p>";
                    } elseif ($exploded[2] !== "0") {
                        $diff = "<p style='color:#ffc200;'>Last online $exploded[2] days ago</p>";
                    } elseif ($exploded[4] !== "0") {
                        $diff = "<p style='color:#ffc200;'>Last online $exploded[4] hours ago</p>";
                    } elseif ($exploded[7] !== "0") {
                        $diff = "<p style='color:#ffc200;'>Last online $exploded[7] minutes ago</p>";
                    } else {
                        $diff = "<p style='color: green;'>Online</p>";
                    }
                }
                echo "<tr><td>";
                echo ($account['user_id']);
                echo $pfp;
                echo ("</td><td>");
                echo "<a href='./profile.php?user={$account['user_id']}' >" . $account['name'] . "</a>";
                echo "<td>";
                if ($account['show_email'] === "False") {
                    echo "Hidden";
                } else {
                    echo ($account['email']);
                }
                echo ("</td><td>");
                echo $diff;
                echo ("</td></tr>\n");
                echo ("</td></tr>\n");
            }
            echo "</table>";

            $pfp = "<a class='pfp-link' href='./profile.php?user={$_SESSION['user_id']}'><img class='profile-img-large' src='$userpfp'></a>";
            $main = "<p id='profile-name'>{$_SESSION['name']}</p><p id='profile-email'>{$_SESSION['email']}</p>";
            $profileLink = "<a href='./profile.php?user={$_SESSION['user_id']}'>Your public profile</a>";
            $actions = '<a href="edit-account.php">Edit Account</a> | <a href="logout.php">Logout</a>';
            echo "<div id='profile'><button id='close-btn' onclick='closeProfile()'>&times;</button>{$pfp}{$main}{$actions}<br />{$profileLink}</div>";
            echo "<button id='close-btn-two' onclick='openProfile()'><img class='user-pfp' alt='user-logo' src='{$userpfp}'></button>";
        } else {
            echo '<h4><a style="text-decoration: underline" href="login.php">Please log in</a></h4>';
            echo '<a style="user-select: none;" class="pfp-link" href="https://github.com/maxhu787" target="_blank"><img style="animation-name: g4o2-breath; animation-iteration-count: infinite; animation-duration: 2.5s; position: fixed; height: 50px; width: 50px; border-radius: 120px; top: 20px; right: 20px;z-index: 100;" src="./g4o2.jpeg"></a>';
        }
        ?>
        <div style='margin-top: 20px;'>
            <a href="https://github.com/g4o2-chat/PHP-SQL-Chat">
                <img src="https://github-readme-stats.vercel.app/api/pin/?username=g4o2-chat&repo=PHP-SQL-Chat&theme=react&bg_color=0D1117" />
            </a>
        </div>
    </div>
    <div id="announcements">
        <h3 style="font-family: orbitron;">Announcements</h3><br />
        <h4>Site creation &#127881; <code>2022/8/23</code></h4>
        <h4>Login system working! 8 created accounts <code>2022/8/24</code></h4>
        <h4>Chat now available&#128526; <code>2022/8/25</code></h4>
        <h4>Profile pictures are now available <code>2022/8/26</code></h4>
        <h4>Profile system working and still being worked on for new look <code>2022/8/28</code></h4>
        <h4>New website theme/style & added user 👤 profile link table on index page <code>2022/9/11</code></h4>
        <h4>Show / Hide email 📧 feature implemented check it out now in the <a href="./edit-account.php">account settings</a> <code>2022/9/17</code></h4>
        <h4>You can now <span class='announcement-highlight'>edit messages</span>! <code>2022/9/18</code></h4>
        <h4>You can now edit / change your passwords 🔑 via <a href="./edit-account.php">account settings</a> <code>2022/9/23</code></h4>
        <h4>Chat now only runs a query to get msgs when a new msg is sent <code>2022/9/24</code></h4>
        <h4>The broken message editing is now fixed 😀 <code>2022/9/24</code></h4>
        <h4>New <a href="./login.php">login</a> &amp; <a href="./edit-account.php">edit account</a> pages <code>2022/9/25</code></h4>
        <h4>Update edit account page css <code>2022/9/30</code></h4>
        <h4>New <a href="./edit-account.php">edit account</a> page same style as login page <code>2022/10/2</code></h4>
        <h4>Now hosting php page on <a href="https://php-sql-chat.maxhu787.repl.co/index.php">repl</a> mysql database hosted with <a href="https://www.db4free.net">db4free</a> <code>2022/10/2</code></h4>
        <h4>Added console message stuff <code>2022/10/8</code></h4>
        <h4>New website message styling <code>2022/10/8</code></h4>
        <h4>Index page update <code>2022/10/9</code></h4>
        <h4><a href="https://github.com/g4o2-chat/PHP-SQL-Chat/releases/tag/1.0.0" target="_blank">v1.0.0</a> out now <code>2022/10/16</code></h4>
        <h4>Database migration: we have switched our database host to <a href="https://www.freemysqlhosting.net">freemysqlhosting.net</a> <code>2022/11/3</code></h4>
        <h4>You can now <a href="create-account.php">create accounts</a> <code>2022/11/3</code></h4>
        <h4>New features added include <span class='announcement-highlight'>ip loggin on login fail</span>, and <span class='announcement-highlight'>guest accounts</span> <code>2022/11/4</code></h4>
        <h4>Several exploits fixed <code>2022/11/4</code></h4>
        <p style="color: rgb(153, 157, 162)">Copyright © <?= date("Y") ?> g4o2. All Rights Reserved.</p>
    </div>
</body>