<?php

//Include Functions
include('includes/Functions.php');

//Include Notifications
include('includes/notification.php');

//delete category
if (isset($_POST['delete_user'])) {
    // Sanitize and retrieve the user ID
    $UserId = (int) $_POST['userid'];

    // Debugging: Check the received user ID
    if ($UserId > 0) {
        // Prepare the SQL query to delete the user from admin_hr table
        $DeleteUser = "DELETE FROM admin_hr WHERE id = ?";

        if ($stmt = $mysqli->prepare($DeleteUser)) {
            // Bind the user ID to the query
            $stmt->bind_param('i', $UserId);

            // Execute the delete query
            if ($stmt->execute()) {
                $msgBox = alertBox("User deleted successfully!");
            } else {
                // Get error details
                $msgBox = alertBox("Error: Unable to delete user. SQL Error: " . $stmt->error, "error");
            }

            // Close the statement
            $stmt->close();
        } else {
            $msgBox = alertBox("Error: Unable to prepare SQL query for deletion.", "error");
        }
    } else {
        $msgBox = alertBox("Error: Invalid User ID.", "error");
    }
}


// Edit
if (isset($_POST['edit'])) {
    // Sanitize input
    $UserId = (int) $_POST['user_id'];
    $UserName = $mysqli->real_escape_string($_POST['user_name']);
    $UserEmail = $mysqli->real_escape_string($_POST['user_email']);

    // Update name and email in the admin_hr table
    $sql = "UPDATE admin_hr SET name = ?, email = ? WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        // Bind parameters (s = string, i = integer)
        $stmt->bind_param('ssi', $UserName, $UserEmail, $UserId);

        // Execute the query
        if ($stmt->execute()) {
            $msgBox = alertBox("User details updated successfully!");
        } else {
            $msgBox = alertBox("Error: Unable to update user details.", "error");
        }

        // Close the statement
        $stmt->close();
    } else {
        $msgBox = alertBox("Error: Unable to prepare SQL query.", "error");
    }
}


// Add new user
if (isset($_POST['submit'])) {
    // Sanitize and get form data
    $first_name = $mysqli->real_escape_string($_POST["first_name"]);
    $last_name = $mysqli->real_escape_string($_POST["last_name"]);
    $email = $mysqli->real_escape_string($_POST["email"]);
    $password = $_POST["password"];  // Store password as VARCHAR, no need for encryption here
    $status = 1;
    $added_by = $_SESSION['UserId']; // Assuming the logged-in user’s ID is stored in the session

    // SQL Query
    $sql = "INSERT INTO department_user (FirstName, LastName, Email, Password, status, created_at, updated_at, added_by)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('ssssii', $first_name, $last_name, $email, $password, $status, $added_by);

        if ($stmt->execute()) {
            $msgBox = alertBox("User has been created successfully!");
        } else {
            // Debugging: show error message
            $msgBox = alertBox("Error: Unable to create user. " . $stmt->error, "error");
        }
        $stmt->close();
    } else {
        // Debugging: show error message if SQL query preparation fails
        $msgBox = alertBox("Error: Unable to prepare SQL query. " . $mysqli->error, "error");
    }
}



//Get list category
$GetUserList = "SELECT UserId, FirstName, LastName, Email FROM department_user ORDER BY FirstName ASC";
$GetUsers = mysqli_query($mysqli, $GetUserList);
// Search

// Search
$searchQuery = ""; // Default query condition

if (isset($_POST['searchbtn'])) {
    $SearchTerm = $mysqli->real_escape_string($_POST['search']); // Prevent SQL injection
    $searchQuery = " WHERE name LIKE '%$SearchTerm%' ";
}

//Include Global page
include('includes/global.php');


?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Add New User </h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <?php if ($msgBox) {
        echo $msgBox;
    } ?>
    <a href="#new" class="btn white btn-success " data-toggle="modal"><i class="fa fa-plus"></i>
        Add New User</a>
    <div class="row">

        <div class="col-lg-12">

            <!-- /.panel -->
            <div class="panel panel-red">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart-o fa-fw"></i> List Users
                </div>
                <div class="panel-body">
                    <div class="pull-right">
                        <form action="" method="post">
                            <div class="form-group input-group col-lg-5 pull-right">
                                <input type="text" name="search" placeholder="Search by Name" class="form-control">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" name="searchbtn" type="submit">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </span>
                            </div>
                        </form>


                    </div>
                    <div class="">
                        <table class="table table-striped table-bordered table-hover" id="assetsdata">
                            <thead>
                                <tr>
                                    <th class="text-left">User Name</th>
                                    <th class="text-left">Email</th>
                                    <!-- <th class="text-left"><?php echo $Action; ?></th> -->
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($col = mysqli_fetch_assoc($GetUsers)) { ?>
                                    <tr>
                                        <!-- Display the full name from FirstName and LastName -->
                                        <td><?php echo $col['FirstName'] . ' ' . $col['LastName']; ?></td>
                                        <td><?php echo $col['Email']; ?></td>

                                        <!-- <td colspan="2" class="notification">
                                            <a href="#EditUser<?php echo $col['id']; ?>" data-toggle="modal">
                                                <span class="btn btn-primary btn-xs glyphicon glyphicon-edit"
                                                    data-toggle="tooltip" data-placement="left" title="Edit User"></span>
                                            </a>
                                            <a href="#DeleteUser<?php echo $col['id']; ?>" data-toggle="modal">
                                                <span class="glyphicon glyphicon-trash btn btn-primary btn-xs"
                                                    data-toggle="tooltip" data-placement="right" title="Delete User"></span>
                                            </a>
                                        </td> -->
                                    </tr>
                                </tbody>
                                <div class="modal fade" id="DeleteUser<?php echo $col['id']; ?>" tabindex="-1" role="dialog"
                                    aria-labelledby="myModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="" method="post">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-hidden="true">&times;</button>
                                                    <h4 class="modal-title" id="myModalLabel"><?php echo $AreYouSure; ?>
                                                    </h4>
                                                </div>
                                                <div class="modal-body">
                                                    Sure About this?
                                                </div>
                                                <div class="modal-footer">
                                                    <input type="hidden" name="userid" value="<?php echo $col['id']; ?>" />
                                                    <button type="submit" name="delete_user" class="btn btn-danger">
                                                        <?php echo $Yes; ?>
                                                    </button>
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">
                                                        <?php echo $Cancel; ?>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>



                                <!-- /.modal-dialog -->
                        </div>
                        <!-- /.modal -->
                        <!-- /.edit category -->
                        <div class="modal fade" id="EditUser<?php echo $col['id']; ?>" tabindex="-1" role="dialog"
                            aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="" method="post">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-hidden="true">&times;</button>
                                            <h4 class="modal-title" id="myModalLabel">Edit User</h4>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Name Field -->
                                            <div class="form-group">
                                                <label for="user_name">User Name</label>
                                                <input class="form-control" required name="user_name"
                                                    value="<?php echo $col['name']; ?>" type="text" autofocus>
                                            </div>

                                            <!-- Email Field -->
                                            <div class="form-group">
                                                <label for="user_email">Email</label>
                                                <input class="form-control" required name="user_email"
                                                    value="<?php echo $col['email']; ?>" type="email">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <input type="hidden" name="user_id" value="<?php echo $col['id']; ?>" />
                                            <button type="submit" name="edit" class="btn btn-primary">Save Changes</button>
                                            <button type="button" class="btn btn-default"
                                                data-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>


                        <!-- /.modal-dialog -->
                    </div>
                    <!-- /.modal -->



                <?php } ?>

                </table>
            </div>
            <!-- /.table-responsive -->

        </div>

    </div>

</div>
<!-- /.col-lg-4 -->
</div>
<!-- /.row -->

</div>
<!-- /#page-wrapper -->

<div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Add New User</h4>
                </div>
                <div class="modal-body">
                    <!-- First Name Field -->
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input class="form-control" required placeholder="Enter First Name" name="first_name"
                            type="text" autofocus>
                    </div>

                    <!-- Last Name Field -->
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input class="form-control" required placeholder="Enter Last Name" name="last_name" type="text">
                    </div>

                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input class="form-control" required placeholder="Enter Email" name="email" type="email">
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input class="form-control" required placeholder="Enter Password" name="password"
                            type="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="submit" class="btn btn-success">
                        <?php echo $Save; ?>
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $Cancel; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>


    $(function () {

        $('.notification').tooltip({
            selector: "[data-toggle=tooltip]",
            container: "body"
        })

    });
</script>