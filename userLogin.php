<?php
session_start();

$msgBox = '';

// Include necessary files
include('includes/notification.php');
include('includes/db.php');
include('includes/Functions.php');

// User Login
if (isset($_POST['login'])) {
    if (empty($_POST['email'])) {
        $msgBox = alertBox("Email field is required.");
    } elseif (empty($_POST['password'])) {
        $msgBox = alertBox("Password field is required.");
    } else {
        $Email = $mysqli->real_escape_string($_POST['email']);
        $Password = $mysqli->real_escape_string($_POST['password']);

        // Check User in department_user table
        if ($stmt = $mysqli->prepare("SELECT UserId, Email, Password FROM department_user WHERE Email = ?")) {
            $stmt->bind_param("s", $Email);
            $stmt->execute();
            $stmt->bind_result($UserId, $Email_, $PasswordHash);
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->fetch();

                // Compare passwords directly for plain text
                if ($Password === $PasswordHash) {
                    $_SESSION['UserId'] = $UserId;
                    $_SESSION['Email'] = $Email_;

                    echo '<META HTTP-EQUIV="Refresh" Content="0; URL=index.php">';
                    exit;
                } else {
                    $msgBox = alertBox("Invalid email or password.");
                }
            } else {
                $msgBox = alertBox("User not found.");
            }

            $stmt->close();
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Money Manager Login</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- MetisMenu CSS -->
    <link href="css/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/custom.css" rel="stylesheet">
    <!-- Custom Fonts -->
    <link href="font-awesome-4.1.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
</head>

<body>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title text-center"><span class="glyphicon glyphicon-lock"></span> User Login</h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($msgBox) { echo $msgBox; } ?>
                        <form method="post" action="" role="form">
                            <fieldset>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input class="form-control" placeholder="Enter Email" name="email" type="email" autofocus required>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input class="form-control" placeholder="Enter Password" name="password" type="password" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-success btn-block">
                                    <span class="glyphicon glyphicon-log-in"></span> Sign In
                                </button>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row text-center">
            <small>copyright © <?php echo date('Y'); ?> Money Manager | All Rights Reserved</small><br>
            <small>Developed By PlayOn24</small>
        </div>
    </div>

    <!-- jQuery Version 1.11.0 -->
    <script src="js/jquery-1.11.0.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="js/plugins/metisMenu/metisMenu.min.js"></script>
    <!-- Custom Theme JavaScript -->
    <script src="js/sb-admin-2.js"></script>

</body>

</html>
