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
    $date = $mysqli->real_escape_string($_POST["date"]);
    $status = 1;

    // Debugging: Output the values
    // var_dump($user_id, $balance, $date, $status);

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
            $updateQuery = "UPDATE user_balance SET balance = ?, date = ?, updated_at = NOW() WHERE user_id = ?";
            if ($stmt = $mysqli->prepare($updateQuery)) {
                $stmt->bind_param('dsi', $new_balance, $date, $user_id);

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
            $insertQuery = "INSERT INTO user_balance (balance, user_id, date, status, created_at, updated_at)
                            VALUES (?, ?, ?, ?, NOW(), NOW())";
            if ($stmt = $mysqli->prepare($insertQuery)) {
                $stmt->bind_param('diss', $balance, $user_id, $date, $status);

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
$GetUserList = "SELECT ub.id, ub.balance, ub.date, du.FirstName, du.LastName, du.Email 
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
                    <!-- Flex container for the date filter and buttons, aligned to the right -->
                    <div class="d-flex justify-content-end mb-3" style="margin-bottom: 5vh;">
                        <div class="mr-2">
                            <!-- Date filter input -->
                            <input type="date" id="filterDate" class="form-control" />
                        </div>
                        <div>
                            <!-- Filter button -->
                            <button id="filterBtn" class="btn"
                                style="padding: 7px; background-color: transparent;">
                                <svg fill="#000" height="20px" width="20px" version="1.1" id="Capa_1"
                                    xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    viewBox="0 0 488.4 488.4" xml:space="preserve" stroke="#000">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier">
                                        <g>
                                            <g>
                                                <path
                                                    d="M0,203.25c0,112.1,91.2,203.2,203.2,203.2c51.6,0,98.8-19.4,134.7-51.2l129.5,129.5c2.4,2.4,5.5,3.6,8.7,3.6 s6.3-1.2,8.7-3.6c4.8-4.8,4.8-12.5,0-17.3l-129.6-129.5c31.8-35.9,51.2-83,51.2-134.7c0-112.1-91.2-203.2-203.2-203.2 S0,91.15,0,203.25z M381.9,203.25c0,98.5-80.2,178.7-178.7,178.7s-178.7-80.2-178.7-178.7s80.2-178.7,178.7-178.7 S381.9,104.65,381.9,203.25z">
                                                </path>
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                            </button>
                            <!-- Clear button -->
                            <button id="clearBtn" class="btn btn-secondary ml-2"
                                style="">Clear</button>
                        </div>
                    </div>

                    <table class="table table-striped table-bordered table-hover" id="assetsdata">
                        <thead>
                            <tr>
                                <th class="text-left">User Name</th>
                                <th class="text-left">Date</th>
                                <th class="text-left">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($col = mysqli_fetch_assoc($GetUsers)) { ?>
                                <tr data-date="<?php echo $col['date']; ?>">
                                    <!-- Display the full name from FirstName and LastName -->
                                    <td><?php echo $col['FirstName'] . ' ' . $col['LastName']; ?></td>
                                    <!-- Display the date -->
                                    <td><?php echo $col['date']; ?></td>
                                    <td><?php echo number_format($col['balance'], 2); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    // JavaScript for date filter
                    document.getElementById("filterBtn").addEventListener("click", function () {
                        const filterDate = document.getElementById("filterDate").value;

                        const rows = document.querySelectorAll("#assetsdata tbody tr");

                        rows.forEach(row => {
                            const rowDate = row.getAttribute("data-date");

                            // If the filter date is selected, compare with the row date
                            if (filterDate) {
                                if (rowDate === filterDate) {
                                    row.style.display = "";
                                } else {
                                    row.style.display = "none";
                                }
                            } else {
                                // If no filter is selected, show all rows
                                row.style.display = "";
                            }
                        });
                    });

                    document.getElementById("clearBtn").addEventListener("click", function () {
                        document.getElementById("filterDate").value = "";

                        // Show all rows
                        const rows = document.querySelectorAll("#assetsdata tbody tr");
                        rows.forEach(row => {
                            row.style.display = "";
                        });
                    });
                </script>

                <style>
                    .d-flex {
                        display: flex;
                        align-items: center;
                        justify-content: flex-end;
                    }

                    .d-flex>div {
                        margin-left: 10px;
                    }

                    input[type="date"] {
                        max-width: 200px;
                    }

                    .ml-2 {
                        margin-left: 10px;
                    }
                </style>


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

                    <!-- Date Picker with Clickable Calendar Icon -->
                    <div class="form-group">
                        <label for="date">Date</label>
                        <div class="input-group">
                            <input type="text" name="date" id="datepicker" class="form-control"
                                placeholder="Select Date" required>
                            <span class="input-group-addon" style="cursor: pointer;">
                                <i class="glyphicon glyphicon-calendar"></i>
                            </span>
                        </div>
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
<link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>


<script>
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
</script>