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
    $role_name = $mysqli->real_escape_string($_POST["role_name"]);
    $status = 1;  // Set status to 1 (active)

    // SQL query to insert into role_ehrer table
    $sql = "INSERT INTO role (name, status, created_at, updated_at) 
            VALUES (?, ?, NOW(), NOW())";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('si', $role_name, $status);

        if ($stmt->execute()) {
            $msgBox = alertBox("Role has been created successfully!");
        } else {
            $msgBox = alertBox("Error: Unable to create role.", "error");
        }
        $stmt->close();
    } else {
        $msgBox = alertBox("Error: Unable to prepare SQL query.", "error");
    }
}

//Get list category

$GetUserList = "SELECT id, name, email FROM admin_hr ORDER BY name ASC";
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
                                    <th class="text-left"><?php echo $Action; ?></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($col = mysqli_fetch_assoc($GetUsers)) { ?>
                                    <tr>
                                        <td><?php echo $col['name']; ?></td>
                                        <td><?php echo $col['email']; ?></td>
                                    </tr>
                                </tbody>
                        </div>

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
                    <h4 class="modal-title" id="myModalLabel">Add New Role</h4>
                </div>
                <div class="modal-body">
                    <!-- Role Name Field -->
                    <div class="form-group">
                        <label for="role_name">Role Name</label>
                        <input class="form-control" required placeholder="Enter Role Name" name="role_name" type="text"
                            autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="submit" class="btn btn-success">
                        Save
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
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