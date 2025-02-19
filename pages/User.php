<?php
$dbuser = "root";
$dbpassword = "";
$dbname = "money";
$dbhost = "localhost";

$connection = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

// Check connection
if (!$connection) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
//Include Functions
include('includes/Functions.php');

//Include Notifications
include('includes/notification.php');

// delete user
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
// Handle user edit form submission
if (isset($_POST['edit_user'])) {
    $UserId = (int) $_POST['edit_user_id'];
    $UserName = mysqli_real_escape_string($connection, $_POST['edit_user_name']);
    $FullName = mysqli_real_escape_string($connection, $_POST['edit_full_name']);
    $Password = mysqli_real_escape_string($connection, $_POST['edit_password']);
    $Status = (int) $_POST['edit_status'];  // Get the status value from the form

    // Check if a new password is provided
    if (!empty($Password)) {
        $sql = "UPDATE department_user SET FirstName=?, Password=?, LastName=?, status=? WHERE UserId=?";
        if ($stmt = mysqli_prepare($connection, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssii", $UserName, $Password, $FullName, $Status, $UserId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msgBox = alertBox("User updated successfully!");
        } else {
            $msgBox = alertBox("Error: Unable to prepare SQL query.", "error");
        }
    } else {
        $sql = "UPDATE department_user SET FirstName=?, LastName=?, status=? WHERE UserId=?";
        if ($stmt = mysqli_prepare($connection, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssii", $UserName, $FullName, $Status, $UserId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $msgBox = alertBox("User updated successfully!");
        } else {
            $msgBox = alertBox("Error: Unable to prepare SQL query.", "error");
        }
    }
}
// Add new user
if (isset($_POST['submit'])) {
    // Sanitize and get form data
    $first_name = $mysqli->real_escape_string($_POST["first_name"]);
    $last_name = $mysqli->real_escape_string($_POST["last_name"]);
    $email = $mysqli->real_escape_string($_POST["email"]);
    $password = $_POST["password"];
    $status = 1;
    $added_by = $_SESSION['UserId'];
    $department_id = $_POST['department'];

    $sql = "INSERT INTO department_user (FirstName, LastName, Email, Password, status, created_at, updated_at, added_by, department_id)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('ssssiii', $first_name, $last_name, $email, $password, $status, $added_by, $department_id);

        if ($stmt->execute()) {
            $msgBox = alertBox("User has been created successfully!");
        } else {
            $msgBox = alertBox("Error: Unable to create user. " . $stmt->error, "error");
        }
        $stmt->close();
    } else {
        $msgBox = alertBox("Error: Unable to prepare SQL query. " . $mysqli->error, "error");
    }
}

// Get list category
$GetUserList = "
    SELECT du.UserId, du.FirstName, du.LastName, du.Email, du.Password, d.name AS DepartmentName
    FROM department_user du
    LEFT JOIN department d ON du.department_id = d.id
    ORDER BY du.created_at ASC
";

// Search
$searchQuery = ""; // Default query condition

// Check if either filter is set
if (isset($_POST['searchbtn'])) {
    $SearchTerm = $mysqli->real_escape_string($_POST['search']);
    $DepartmentFilter = isset($_POST['department_filter']) ? (int) $_POST['department_filter'] : 0;

    if ($SearchTerm && $DepartmentFilter) {
        // Filter by both name and department
        $searchQuery = " WHERE (FirstName LIKE '%$SearchTerm%' OR LastName LIKE '%$SearchTerm%') AND du.department_id = $DepartmentFilter ";
    } elseif ($SearchTerm) {
        // Filter only by name
        $searchQuery = " WHERE FirstName LIKE '%$SearchTerm%' OR LastName LIKE '%$SearchTerm%' ";
    } elseif ($DepartmentFilter) {
        // Filter only by department
        $searchQuery = " WHERE du.department_id = $DepartmentFilter ";
    }
}

$GetUserListWithSearch = "
    SELECT du.UserId, du.FirstName, du.LastName, du.Email, du.Password,du.status ,d.name AS DepartmentName 
    FROM department_user du
    LEFT JOIN department d ON du.department_id = d.id
    " . $searchQuery . " 
    ORDER BY du.created_at ASC
";

// Execute the search query
$GetUsers = mysqli_query($mysqli, $GetUserListWithSearch);

// Include Global page
include('includes/global.php');
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Add New User</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <?php if ($msgBox) {
        echo $msgBox;
    } ?>
    <a href="#new" class="btn white btn-success" data-toggle="modal"><i class="fa fa-plus"></i> Add New User</a>
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart-o fa-fw"></i> List Users
                </div>
                <div class="panel-body">
                    <div class="pull-left">
                        <form action="" method="post">
                            <div class="form-group input-group col-lg-12" style="display: flex; align-items: center;">
                                <!-- Department Dropdown -->
                                <select class="form-control" name="department_filter" id="department_filter"
                                    style="margin-right: 10px;">
                                    <option value="">Select Department</option>
                                    <?php
                                    // Fetch department data from the database
                                    $sql = "SELECT id, name FROM department WHERE status = 1 ORDER BY name ASC";
                                    $result = mysqli_query($mysqli, $sql);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                                    }
                                    ?>
                                </select>

                                <!-- Search Input -->
                                <!-- <input type="text" name="search" placeholder="Search by Name" class="form-control"
                                    value="<?php echo isset($_POST['search']) ? $_POST['search'] : ''; ?>"
                                    style="margin-right: 10px;"> -->

                                <span class="input-group-btn" style="display: flex; align-items: center;">
                                    <!-- Search Button -->
                                    <button class="btn btn-primary" name="searchbtn" type="submit"
                                        style="margin-right: 10px;">
                                        <i class="fa fa-search"></i>
                                    </button>

                                    <!-- Cross icon button to clear the search input -->
                                    <button class="btn btn-danger" type="button" onclick="clearSearch()">
                                        <i class="fa fa-times"></i>
                                    </button>
                                    <script>
                                        function clearSearch() {
                                            document.querySelector('input[name="search"]').value = '';
                                            document.querySelector('form').submit(); // Resubmit the form to clear the search
                                        }
                                    </script>
                                </span>
                            </div>
                        </form>
                    </div>
                    <div class="">
                        <table class="table table-striped table-bordered table-hover" id="assetsdata">
                            <thead>
                                <tr>
                                    <th class="text-left">User Name</th>
                                    <th class="text-left">Password</th>
                                    <th class="text-left">Full Name</th>
                                    <th class="text-left">Department</th>
                                    <th class="text-left">Status</th> <!-- New column for status -->
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($col = mysqli_fetch_assoc($GetUsers)) { ?>
                                    <tr>
                                        <td><?php echo $col['FirstName']; ?></td>
                                        <td><?php echo $col['Password']; ?></td>
                                        <td><?php echo $col['LastName']; ?></td>
                                        <td><?php echo $col['DepartmentName']; ?></td>

                                        <!-- Status Column with conditional badge -->
                                        <td>
                                            <?php if ($col['status'] == 1) { ?>
                                                <span style="background-color: green; color: white; padding: 5px;border-radius: 10px;">Active</span>
                                            <?php } else { ?>
                                                <span style="background-color: red; color: white; padding: 5px;border-radius: 10px;">Inactive</span>
                                            <?php } ?>
                                        </td>

                                        <!-- Edit Button -->
                                        <td>
                                            <button class="btn btn-warning" data-toggle="modal" data-target="#editModal"
                                                onclick="editUser('<?php echo $col['UserId']; ?>', '<?php echo htmlspecialchars($col['FirstName']); ?>', '<?php echo htmlspecialchars($col['LastName']); ?>', '<?php echo $col['status']; ?>')">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Edit User Modal -->
<!-- Edit User Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit User</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_user_id" id="edit_user_id">
                    <div class="form-group">
                        <label>User Name</label>
                        <input type="text" class="form-control" name="edit_user_name" id="edit_user_name" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="edit_password" id="edit_password">
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="edit_full_name" id="edit_full_name" required>
                    </div>
                    <!-- Status Dropdown -->
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="edit_user_status" id="edit_user_status" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_user" class="btn btn-success">Save Changes</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Add New User</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <select class="form-control" name="department" required>
                            <option value="">Select Department</option>
                            <?php
                            // Fetch departments from the database
                            $query = "SELECT * FROM department"; // Adjust table name if needed
                            $result = mysqli_query($connection, $query); // Adjust connection variable
                            
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <!-- Full Name Field -->
                    <div class="form-group">
                        <label for="last_name">Full Name</label>
                        <input class="form-control" required placeholder="Enter Last Name" name="last_name" type="text">
                    </div>

                    <!-- User Name Field -->
                    <div class="form-group">
                        <label for="first_name">User Name</label>
                        <input class="form-control" required placeholder="Enter First Name" name="first_name"
                            type="text" autofocus>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input class="form-control" required placeholder="Enter Password" name="password"
                            type="password">
                    </div>

                    <!-- Department Dropdown -->

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


<!-- Include Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<script>
    // Initialize Select2 on the department dropdown
    $(document).ready(function () {
        $('#department_filter').select2({
            placeholder: 'Select Department',
            allowClear: true // Allow clearing of the selection
        });

        function clearSearch() {
            document.querySelector("input[name='search']").value = '';
            document.querySelector('form').submit();
        }
    });

    function editUser(id, username, fullname) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_user_name').value = username;
        document.getElementById('edit_full_name').value = fullname;
        document.getElementById('edit_user_status').value = status;
    }
</script>


<?php
include('includes/footer.php');
?>