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
    $user_id = (int) $_POST["user_id"];
    $balance = $mysqli->real_escape_string($_POST["balance"]);
    $status = 1;

    // Debugging: Output the values
    var_dump($user_id, $balance, $status);

    // Check if the user already has an existing balance record
    $checkBalanceQuery = "SELECT balance FROM user_balance WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    if ($stmt = $mysqli->prepare($checkBalanceQuery)) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($existing_balance);
        $stmt->fetch();
        $stmt->close();

        // If there is an existing balance, sum it with the new balance
        if ($existing_balance !== null) {
            $new_balance = $existing_balance + $balance;

            // Update the balance
            $updateQuery = "UPDATE user_balance SET balance = ?, updated_at = NOW() WHERE user_id = ?";
            if ($stmt = $mysqli->prepare($updateQuery)) {
                $stmt->bind_param('di', $new_balance, $user_id);

                if ($stmt->execute()) {
                    $msgBox = alertBox("Balance has been updated successfully!");
                } else {
                    var_dump($stmt->error);
                    $msgBox = alertBox("Error: Unable to update balance. " . $stmt->error, "error");
                }
                $stmt->close();
            } else {
                var_dump($mysqli->error);
                $msgBox = alertBox("Error: Unable to prepare update SQL query. " . $mysqli->error, "error");
            }
        } else {
            // If no existing balance, insert a new record
            $insertQuery = "INSERT INTO user_balance (balance, user_id, status, created_at, updated_at)
                            VALUES (?, ?, ?, NOW(), NOW())";
            if ($stmt = $mysqli->prepare($insertQuery)) {
                $stmt->bind_param('dii', $balance, $user_id, $status);

                if ($stmt->execute()) {
                    $msgBox = alertBox("Balance has been added successfully!");
                } else {
                    var_dump($stmt->error);
                    $msgBox = alertBox("Error: Unable to add balance. " . $stmt->error, "error");
                }
                $stmt->close();
            } else {
                var_dump($mysqli->error);
                $msgBox = alertBox("Error: Unable to prepare SQL query. " . $mysqli->error, "error");
            }
        }
    } else {
        var_dump($mysqli->error);
        $msgBox = alertBox("Error: Unable to check existing balance. " . $mysqli->error, "error");
    }
}
//Get list category
$GetUserList = "SELECT ub.id, ub.balance, du.FirstName, du.LastName, du.Email 
                FROM user_balance ub
                JOIN department_user du ON ub.user_id = du.UserId
                ORDER BY du.FirstName ASC";
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
            <h1 class="page-header">Balance</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <?php if ($msgBox) {
        echo $msgBox;
    } ?>
    <a href="#new" class="btn white btn-success " data-toggle="modal"><i class="fa fa-plus"></i>
        Give Balance</a>
    <div class="row">

        <div class="col-lg-12">

            <!-- /.panel -->
            <div class="panel panel-red">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart-o fa-fw"></i> List of balance
                </div>
                <div class="panel-body">
                    <div class="">
                        <table class="table table-striped table-bordered table-hover" id="assetsdata">
                            <thead>
                                <tr>
                                    <th class="text-left">User Name</th>
                                    <th class="text-left">Email</th>
                                    <th class="text-left">Balance</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($col = mysqli_fetch_assoc($GetUsers)) { ?>
                                    <tr>
                                        <!-- Display the full name from FirstName and LastName -->
                                        <td><?php echo $col['FirstName'] . ' ' . $col['LastName']; ?></td>
                                        <td><?php echo $col['Email']; ?></td>
                                        <td><?php echo number_format($col['balance'], 2); ?></td>
                                    </tr>
                                </tbody>

                        </div>
                    </div>
                    <!-- /.modal -->
                <?php } ?>

                </table>
            </div>
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
                    <h4 class="modal-title" id="myModalLabel">Add User Balance</h4>
                </div>
                <div class="modal-body">
                    <!-- User Dropdown (Load data from department_user table) -->
                    <div class="form-group">
                        <label for="user_id">Select User</label>
                        <select class="form-control" name="user_id" required>
                            <option value="">Select User</option>
                            <?php
                            $sql = "SELECT UserId, CONCAT(FirstName, ' ', LastName) AS user_name FROM department_user";
                            $result = mysqli_query($mysqli, $sql);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<option value='" . $row['UserId'] . "'>" . $row['user_name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Balance Field -->
                    <div class="form-group">
                        <label for="balance">Balance</label>
                        <input class="form-control" required placeholder="Enter Balance" name="balance" type="number"
                            step="0.01">
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