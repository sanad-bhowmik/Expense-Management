<?php
session_start();

$msgBox = '';

// Include required files
include('includes/notification.php');
include('includes/db.php');
include('includes/Functions.php');

// User Login
if (isset($_POST['login'])) {
    if (empty($_POST['email'])) {
        $msgBox = alertBox($EmailEmpty);
    } elseif (empty($_POST['password'])) {
        $msgBox = alertBox($PasswordEmpty);
    } else {
        // Get User Info
        $Email = $mysqli->real_escape_string($_POST['email']);
        $Password = $mysqli->real_escape_string($_POST['password']);

        // Query to check both tables
        $query = "
            SELECT UserId, FirstName, LastName, Email, Password, Currency, 'user' AS user_type
            FROM user
            WHERE Email = ? AND Password = ?
            UNION
            SELECT UserId, FirstName, LastName, Email, Password, NULL AS Currency, 'department_user' AS user_type
            FROM department_user
            WHERE Email = ? AND Password = ?";

        if ($stmt = $mysqli->prepare($query)) {
            $stmt->bind_param("ssss", $Email, $Password, $Email, $Password);
            $stmt->execute();
            $stmt->bind_result($UserId_, $FirstName_, $LastName_, $Email_, $Password_, $Currency_, $UserType_);
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->fetch();

                // Set session variables
                $_SESSION['UserId'] = $UserId_;
                $_SESSION['FirstName'] = $FirstName_;
                $_SESSION['LastName'] = $LastName_;
                $_SESSION['Email'] = $Email_;
                $_SESSION['UserType'] = $UserType_; // Indicates the source table (user or department_user)
                if ($Currency_) {
                    $_SESSION['Currency'] = $Currency_;
                }

                $UserIds = $_SESSION['UserId'];

                // Generate default Category for New User (if `user` table)
                if ($UserType_ === 'user') {
                    $a = "SELECT CategoryName FROM category WHERE UserId = $UserIds";
                    $b = mysqli_query($mysqli, $a);

                    if (mysqli_num_rows($b) < 1) {
                        $c = "INSERT INTO category(UserId, CategoryName, Level) VALUES 
                            ($UserIds, 'Salary', 1), 
                            ($UserIds, 'Allowance', 1), 
                            ($UserIds, 'Petty Cash', 1), 
                            ($UserIds, 'Bonus', 1), 
                            ($UserIds, 'Food', 2),
                            ($UserIds, 'Social Life', 2), 
                            ($UserIds, 'Self-Development', 2), 
                            ($UserIds, 'Transportation', 2), 
                            ($UserIds, 'Culture', 2), 
                            ($UserIds, 'Household', 2), 
                            ($UserIds, 'Apparel', 2), 
                            ($UserIds, 'Beauty', 2), 
                            ($UserIds, 'Health', 2), 
                            ($UserIds, 'Gift', 2)";
                        mysqli_query($mysqli, $c);
                    }
                }

                // Redirect to index.php
                echo '<META HTTP-EQUIV="Refresh" Content="0; URL=index.php">';
            } else {
                $msgBox = alertBox($LoginError); // Invalid email or password
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

    <style>
        /* Body Background Image with Opacity */
        body {
            background-image: url('https://media.istockphoto.com/id/1462932996/photo/cost-and-quality-control-business-strategy-and-project-management-concept-businessman-working.jpg?s=612x612&w=0&k=20&c=TUVDo4Q6uUpsJCssPxQ05egXtfFoHT0AEd78yMWTW80=');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            position: relative;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgb(0 0 0 / 71%); 
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 2;
        }

        .login-panel {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .panel-heading {
            background-color: #2d3e50;
            color: white;
        }

        .btn-success {
            background-color: #5cb85c;
            border-color: #4cae4c;
        }

        /* Footer Text */
        .footer-text {
            color: white;
            font-size: 12px;
            margin-top: 15px;
        }
    </style>

</head>

<body>

    <!-- Overlay for background opacity -->
    <div class="overlay"></div>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title text-center"><span class="glyphicon glyphicon-lock"></span> <?php echo
                            $UserSign; ?></h3>
                    </div>
                    <div class="panel-body">
                        <?php if ($msgBox) {
                            echo $msgBox;
                        } ?>
                        <form method="post" action="" role="form">
                            <fieldset>
                                <div class="form-group">
                                    <label for="email"><?php echo $Emails; ?></label>
                                    <input class="form-control" placeholder="<?php echo
                                        $Emails; ?>" name="email" type="email" autofocus>
                                </div>
                                <div class="form-group">
                                    <label for="password"><?php echo $Passwords; ?></label>
                                    <input class="form-control" placeholder="<?php echo
                                        $Passwords; ?>" name="password" type="password" value="">
                                </div>

                                <hr>
                                <button type="submit" name="login" class="btn btn-success btn-block"><span
                                        class="glyphicon glyphicon-log-in"></span> <?php echo
                                            $SignIn; ?></button>
                                <hr>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row text-center footer-text">
            <small>copyright © <?php echo Date('Y'); ?> Money Manager | All right Reserved</small><br>
            <small>Develop By PlayOn24</small>
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
