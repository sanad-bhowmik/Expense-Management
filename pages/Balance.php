<?php

// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');

// Delete category
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

// Edit User
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

// Add new user balance logic
if (isset($_POST['submit'])) {
    $user_id = (int) $_POST["user_id"];
    $balance = $mysqli->real_escape_string($_POST["balance"]);
    $date = $mysqli->real_escape_string($_POST["date"]);
    $status = 1;

    $add_by = isset($_SESSION['UserId']) ? $_SESSION['UserId'] : 99;

    $checkBalanceQuery = "SELECT balance FROM user_balance WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    if ($stmt = $mysqli->prepare($checkBalanceQuery)) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($existing_balance);
        $stmt->fetch();
        $stmt->close();

        if ($existing_balance !== null) {
            // Calculate new balance by adding the new balance to the existing balance
            $new_balance = $existing_balance + $balance;

            // Update balance in the user_balance table
            $updateQuery = "UPDATE user_balance SET balance = ?, date = ?, updated_at = NOW() WHERE user_id = ?";
            if ($stmt = $mysqli->prepare($updateQuery)) {
                $stmt->bind_param('dsi', $new_balance, $date, $user_id);

                if ($stmt->execute()) {
                    // Insert into ledger table
                    $ledgerInsertQuery = "INSERT INTO ledger (user_id, InBalance, type, amount, total, created_at, updated_at)
                                          VALUES (?, ?, 'in', ?, ?, NOW(), NOW())";
                    if ($ledgerStmt = $mysqli->prepare($ledgerInsertQuery)) {
                        $ledgerStmt->bind_param('iddd', $user_id, $balance, $balance, $new_balance);

                        if ($ledgerStmt->execute()) {
                            // Insert the entered balance (not the total sum) into the history table
                            $historyInsertQuery = "INSERT INTO user_balance_history (user_id, balance, add_by, status, date, created_at, updated_at)
                                                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                            if ($historyStmt = $mysqli->prepare($historyInsertQuery)) {
                                $historyStmt->bind_param('idiss', $user_id, $balance, $add_by, $status, $date);

                                if ($historyStmt->execute()) {
                                    $msgBox = alertBox("Balance has been updated successfully!");
                                } else {
                                    $msgBox = alertBox("Error: Unable to insert into user_balance_history. " . $historyStmt->error, "error");
                                }
                                $historyStmt->close();
                            } else {
                                $msgBox = alertBox("Error: Unable to prepare user_balance_history insert query. " . $mysqli->error, "error");
                            }
                        } else {
                            $msgBox = alertBox("Error: Unable to insert into ledger. " . $ledgerStmt->error, "error");
                        }
                        $ledgerStmt->close();
                    } else {
                        $msgBox = alertBox("Error: Unable to prepare ledger insert query. " . $mysqli->error, "error");
                    }
                } else {
                    $msgBox = alertBox("Error: Unable to update balance. " . $stmt->error, "error");
                }
                $stmt->close();
            } else {
                $msgBox = alertBox("Error: Unable to prepare update SQL query. " . $mysqli->error, "error");
            }
        } else {
            // If no existing balance, insert the new balance directly into user_balance
            $insertQuery = "INSERT INTO user_balance (balance, user_id, date, status, created_at, updated_at)
                            VALUES (?, ?, ?, ?, NOW(), NOW())";
            if ($stmt = $mysqli->prepare($insertQuery)) {
                $stmt->bind_param('diss', $balance, $user_id, $date, $status);

                if ($stmt->execute()) {
                    // Insert into ledger table
                    $ledgerInsertQuery = "INSERT INTO ledger (user_id, InBalance, type, amount, total, created_at, updated_at)
                                          VALUES (?, ?, 'credit', ?, ?, NOW(), NOW())";
                    if ($ledgerStmt = $mysqli->prepare($ledgerInsertQuery)) {
                        $ledgerStmt->bind_param('iddd', $user_id, $balance, $balance, $balance);

                        if ($ledgerStmt->execute()) {
                            // Insert the entered balance (not the total sum) into the history table
                            $historyInsertQuery = "INSERT INTO user_balance_history (user_id, balance, add_by, status, date, created_at, updated_at)
                                                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                            if ($historyStmt = $mysqli->prepare($historyInsertQuery)) {
                                $historyStmt->bind_param('idiss', $user_id, $balance, $add_by, $status, $date);

                                if ($historyStmt->execute()) {
                                    $msgBox = alertBox("Balance has been added successfully!");
                                } else {
                                    $msgBox = alertBox("Error: Unable to insert into user_balance_history. " . $historyStmt->error, "error");
                                }
                                $historyStmt->close();
                            } else {
                                $msgBox = alertBox("Error: Unable to prepare user_balance_history insert query. " . $mysqli->error, "error");
                            }
                        } else {
                            $msgBox = alertBox("Error: Unable to insert into ledger. " . $ledgerStmt->error, "error");
                        }
                        $ledgerStmt->close();
                    } else {
                        $msgBox = alertBox("Error: Unable to prepare ledger insert query. " . $mysqli->error, "error");
                    }
                } else {
                    $msgBox = alertBox("Error: Unable to add balance. " . $stmt->error, "error");
                }
                $stmt->close();
            } else {
                $msgBox = alertBox("Error: Unable to prepare insert SQL query. " . $mysqli->error, "error");
            }
        }
    } else {
        $msgBox = alertBox("Error: Unable to check existing balance. " . $mysqli->error, "error");
    }
}

// Get list of departments for the department dropdown
$GetDepartments = "SELECT id, name FROM department ORDER BY name ASC";
$DepartmentsResult = mysqli_query($mysqli, $GetDepartments);

// Get list of users (for the user dropdown)
$GetUsersQuery = "SELECT du.UserId, du.FirstName, du.LastName FROM department_user du ORDER BY du.FirstName ASC";
$UsersResult = mysqli_query($mysqli, $GetUsersQuery);

// Get list of user balances
$GetUserList = "SELECT ub.id, ub.balance, ub.date, du.UserId, du.FirstName, du.LastName, du.Email, du.department_id, d.name AS department_name
                FROM user_balance ub
                JOIN department_user du ON ub.user_id = du.UserId
                JOIN department d ON du.department_id = d.id
                ORDER BY du.FirstName ASC";
$GetUsers = mysqli_query($mysqli, $GetUserList);

// Search query initialization
$searchQuery = ""; // Default query condition

if (isset($_POST['searchbtn'])) {
    $SearchUser = $mysqli->real_escape_string($_POST['filterUser']);
    $SearchDepartment = $mysqli->real_escape_string($_POST['filterDepartment']);

    // Add user and department filtering conditions
    if ($SearchUser) {
        $searchQuery .= " AND du.UserId = '$SearchUser'";
    }
    if ($SearchDepartment) {
        $searchQuery .= " AND du.department_id = '$SearchDepartment'";
    }
}

// Include Global page
include('includes/global.php');
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Balance</h1>
        </div>
    </div>
    <!-- /.row -->
    <?php if ($msgBox) {
        echo $msgBox;
    } ?>
    <a href="#new" class="btn white btn-success " data-toggle="modal"><i class="fa fa-plus"></i>
        Give Balance</a>
    <div class="row">

        <div class="col-lg-12">

            <div class="panel panel-red">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart-o fa-fw"></i> List of balance
                </div>
                <div class="panel-body">
                    <div class="d-flex justify-content-end mb-3" style="display: flex;margin-bottom: 5vh;gap:10px;">
                        <div class="mr-2">
                            <label for="filterUser" class="form-label">Search by User</label>
                            <select id="filterUser" class="form-control">
                                <option value="">Select User</option>
                                <?php while ($user = mysqli_fetch_assoc($UsersResult)) { ?>
                                    <option value="<?php echo $user['UserId']; ?>">
                                        <?php echo $user['FirstName'] . ' ' . $user['LastName']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mr-2">
                            <label for="filterDepartment" class="form-label">Filter by Department</label>
                            <select id="filterDepartment" class="form-control">
                                <option value="">Select Department</option>
                                <?php while ($department = mysqli_fetch_assoc($DepartmentsResult)) { ?>
                                    <option value="<?php echo $department['id']; ?>">
                                        <?php echo $department['name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div style="margin-top: 26px;">
                            <button class="btn btn-primary" id="filterBtn" name="searchbtn" type="submit">
                                <i class="fa fa-search"></i>
                            </button>
                            <button id="clearBtn" class="btn btn-danger ml-2"><i class="fa fa-times"></i></button>
                        </div>
                    </div>

                    <table class="table table-striped table-bordered table-hover" id="assetsdata">
                        <thead>
                            <tr>
                                <th class="text-left">User Name</th>
                                <th class="text-left">Balance</th>
                                <th class="text-left">Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($col = mysqli_fetch_assoc($GetUsers)) { ?>
                                <tr data-user-id="<?php echo $col['UserId']; ?>"
                                    data-department-id="<?php echo $col['department_id']; ?>"
                                    data-date="<?php echo $col['date']; ?>">
                                    <td><?php echo $col['FirstName'] . ' ' . $col['LastName']; ?></td>
                                    <td><?php echo number_format($col['balance'], 2); ?> Tk</td>
                                    <td><?php echo $col['department_name']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    document.getElementById("filterBtn").addEventListener("click", function () {
                        const filterUser = document.getElementById("filterUser").value;
                        const filterDepartment = document.getElementById("filterDepartment").value;
                        const rows = document.querySelectorAll("#assetsdata tbody tr");

                        rows.forEach(row => {
                            const rowUserId = row.getAttribute("data-user-id");
                            const rowDepartmentId = row.getAttribute("data-department-id");

                            const isUserMatch = filterUser ? rowUserId === filterUser : true;
                            const isDepartmentMatch = filterDepartment ? rowDepartmentId === filterDepartment : true;

                            if (isUserMatch && isDepartmentMatch) {
                                row.style.display = "";
                            } else {
                                row.style.display = "none";
                            }
                        });
                    });

                    document.getElementById("clearBtn").addEventListener("click", function () {
                        document.getElementById("filterUser").value = "";
                        document.getElementById("filterDepartment").value = "";
                        const rows = document.querySelectorAll("#assetsdata tbody tr");
                        rows.forEach(row => {
                            row.style.display = "";
                        });
                    });
                </script>
            </div>

        </div>
    </div>

</div>
<!-- Modal Add Balance -->
<div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Add User Balance</h4>
                </div>
                <div class="modal-body">
                    <!-- Inside the modal-body section of your modal -->
                    <div class="form-group">
                        <label for="user_id">Select User</label>
                        <select class="form-control" name="user_id" id="user_id">
                            <?php
                            $userListQuery = "SELECT UserId, CONCAT(LastName) AS FullName FROM department_user";
                            $userListResult = mysqli_query($mysqli, $userListQuery);

                            while ($user = mysqli_fetch_assoc($userListResult)) {
                                echo '<option value="' . $user['UserId'] . '">' . $user['FullName'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <span id="user_balance" class="form-control-static"
                            style="color: midnightblue;font-weight: 800;font-family: math;"></span>
                    </div>

                    <div class="form-group">
                        <label for="balance">Balance</label>
                        <input type="number" name="balance" id="balance" class="form-control"
                            placeholder="Enter balance">
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" class="form-control"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success" name="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('#user_id').change(function () {
            var userId = $(this).val();

            if (userId) {
                $.ajax({
                    url: 'pages/get_user_balance.php',  // Adjust the path to include the 'pages' folder
                    type: 'POST',
                    data: { user_id: userId },
                    dataType: 'json',  // Expecting JSON response
                    success: function (response) {
                        if (response.balance && response.department) {
                            // Remove any unwanted characters from balance like quotes or commas
                            var cleanedBalance = response.balance.replace(/['"]+/g, '').replace(/,/g, '');

                            // Display balance and department separately
                            $('#user_balance').text('Current Balance: ' + cleanedBalance);
                            $('#user_department').text('Department: ' + response.department);
                        } else {
                            $('#user_balance').text('No balance found for this user.');
                            $('#user_department').text('No department found.');
                        }
                    },
                    error: function () {
                        $('#user_balance').text('Error fetching data.');
                        $('#user_department').text('');
                    }
                });
            } else {
                $('#user_balance').text('');
                $('#user_department').text('');
            }
        });
    });


    $(document).ready(function () {
        // Initialize the date picker
        $('#datepicker').datepicker({
            format: 'yyyy-mm-dd', // Set date format
            autoclose: true,     // Close the picker automatically
            todayHighlight: true // Highlight today's date
        });

        // Trigger the date picker when the calendar icon is clicked
        $('.input-group-addon').on('click', function () {
            $('#datepicker').datepicker('show');
        });
    });

    $(function () {

        $('.notification').tooltip({
            selector: "[data-toggle=tooltip]",
            container: "body"
        })

    });
    $(document).ready(function () {
        $('#user_id').change(function () {
            var userId = $(this).val();

            if (userId) {
                $.ajax({
                    url: 'pages/get_u_balance.php',  // Adjust the path if needed
                    type: 'POST',
                    data: { user_id: userId },
                    success: function (response) {
                        // Assuming response contains the balance in the format: "Balance: 5000 Tk"
                        $('#user_balance').text(response);
                    },
                    error: function (xhr, status, error) {
                        console.error("Error fetching balance: " + error);
                    }
                });
            } else {
                $('#user_balance').text(''); // Clear balance if no user is selected
            }
        });
    });

</script>