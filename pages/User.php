<?php

//Include Functions
include('includes/Functions.php');

//Include Notifications
include('includes/notification.php');

//delete category

if (isset($_POST['delete_user'])) {
    // Sanitize and retrieve the user id
    $UserId = (int) $_POST['userid'];

    // Debugging: Check the received user id
    var_dump($UserId);

    // Prepare the SQL query to delete the user from department_wise_users table
    $DeleteUser = "DELETE FROM department_wise_users WHERE id = ?";

    if ($stmt = $mysqli->prepare($DeleteUser)) {
        // Bind the user id to the query
        $stmt->bind_param('i', $UserId);

        // Execute the delete query
        if ($stmt->execute()) {
            $msgBox = alertBox("User deleted successfully!");
        } else {
            // Get error details
            $error = $stmt->error;
            $msgBox = alertBox("Error: Unable to delete user. SQL Error: " . $error, "error");
        }

        // Close the statement
        $stmt->close();
    } else {
        $msgBox = alertBox("Error: Unable to prepare SQL query for deletion.", "error");
    }
}


//Edit Category
if (isset($_POST['edit'])) {
    $UserId = $_POST['user_id'];
    $UserName = $_POST['user_name'];

    // Update user name in department_wise_users table
    $sql = "UPDATE department_wise_users SET user_name = ? WHERE id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        // Bind parameters (s = string, i = integer)
        $stmt->bind_param('si', $UserName, $UserId);

        // Execute the query
        if ($stmt->execute()) {
            $msgBox = alertBox("User name updated successfully!");
        } else {
            $msgBox = alertBox("Error: Unable to update user name.", "error");
        }
    }

    // Close the statement
    $stmt->close();
}



// Add new user
if (isset($_POST['submit'])) {
    // Sanitize and get form data
    $user_name = $mysqli->real_escape_string($_POST["user_name"]);
    $department_id = (int) $_POST["department"];
    $balance = (float) $_POST["balance"];

    if (empty($department_id)) {
        $msgBox = alertBox("Please select a department.", "error");
    } else {
        $status = 1;
        $added_by = $_SESSION['auth_name'];
        $sql = "INSERT INTO department_wise_users (user_name, department_id, balance, status, added_by, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

        // var_dump($sql);

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param('siiss', $user_name, $department_id, $balance, $status, $added_by);

            if ($stmt->execute()) {
                $msgBox = alertBox("User has been created successfully!");
            } else {
                // var_dump($stmt->error);
                $msgBox = alertBox("Error: Unable to create user.", "error");
            }
            $stmt->close();
        } else {
            // var_dump($mysqli->error); // Display SQL preparation error
            $msgBox = alertBox("Error: Unable to prepare SQL query.", "error");
        }
    }
}


//Get list category

$GetDepartmentList = "SELECT du.*, d.name AS department_name 
                      FROM department_wise_users du
                      INNER JOIN department d ON du.department_id = d.id
                      WHERE d.status = 1
                      ORDER BY d.name ASC";
$GetDepartments = mysqli_query($mysqli, $GetDepartmentList);

// Search category
if (isset($_POST['searchbtn'])) {
    $SearchTerm = $mysqli->real_escape_string($_POST['search']); // Prevent SQL injection
    $GetList = "SELECT id, name FROM department WHERE status = 1 AND name LIKE '%$SearchTerm%' ORDER BY name ASC";
    $GetListDepartment = mysqli_query($mysqli, $GetList);
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
                            <div class="form-group input-group col-lg-5	pull-right">
                                <input type="text" name="search" placeholder="<?php echo $Search; ?>"
                                    class="form-control">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" name="searchbtn" type="input"><i
                                            class="fa fa-search"></i>
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
                                    <th class="text-left">Department</th>
                                    <th class="text-left">Balance</th>
                                    <th class="text-left"><?php echo $Action; ?></th>

                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($col = mysqli_fetch_assoc($GetDepartments)) { ?>
                                    <tr>
                                        <td><?php echo $col['user_name']; ?></td>
                                        <td><?php echo $col['department_name']; ?></td>
                                        <td><?php echo $col['balance']; ?></td>

                                        <td colspan="2" class="notification">
                                            <a href="#EditDept<?php echo $col['id']; ?>" data-toggle="modal">
                                                <span class="btn btn-primary btn-xs glyphicon glyphicon-edit"
                                                    data-toggle="tooltip" data-placement="left" title="Edit User"></span>
                                            </a>
                                            <a href="#DeleteUser<?php echo $col['id']; ?>" data-toggle="modal">
                                                <span class="glyphicon glyphicon-trash btn btn-primary btn-xs"
                                                    data-toggle="tooltip" data-placement="right" title="Delete User"></span>
                                            </a>
                                        </td>
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
                                                    <?php echo $ThisItem; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <input type="hidden" name="userid" value="<?php echo $col['id']; ?>" />
                                                    <button type="submit" name="delete_user"
                                                        class="btn btn-danger"><?php echo $Yes; ?></button>
                                                    <button type="button" class="btn btn-default"
                                                        data-dismiss="modal"><?php echo $Cancel; ?></button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>


                                <!-- /.modal-dialog -->
                        </div>
                        <!-- /.modal -->
                        <!-- /.edit category -->
                        <div class="modal fade" id="EditDept<?php echo $col['id']; ?>" tabindex="-1" role="dialog"
                            aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="" method="post">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-hidden="true">&times;</button>
                                            <h4 class="modal-title" id="myModalLabel">Edit User Name</h4>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="user_name">User Name</label>
                                                <input class="form-control" required name="user_name"
                                                    value="<?php echo $col['user_name']; ?>" type="text" autofocus>
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
                    <!-- Name Field -->
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input class="form-control" required placeholder="Enter Name" name="user_name" type="text"
                            autofocus>
                    </div>

                    <!-- Department Dropdown -->
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select class="form-control" required name="department">
                            <option value="">Select Department</option>
                            <?php
                            // Fetch departments from the department table
                            $GetDepartments = "SELECT id, name FROM department ORDER BY name ASC";
                            $GetDepartmentsResult = mysqli_query($mysqli, $GetDepartments);
                            while ($department = mysqli_fetch_assoc($GetDepartmentsResult)) {
                                echo "<option value='" . $department['id'] . "'>" . $department['name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Balance Field -->
                    <div class="form-group">
                        <label for="balance">Balance</label>
                        <input class="form-control" required placeholder="Enter Balance" name="balance" type="number"
                            min="0" step="any">
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