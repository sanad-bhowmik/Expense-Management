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

$msgBoxExpense = '';
//Include Functions
include('includes/db.php');
include('includes/Functions.php');

//Include Notifications
include('includes/notification.php');


//save income form
if (isset($_POST['income'])) {
    $iuser = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $iname = "Hardcoded Name"; // Hardcoding the name
    $icategory = $mysqli->real_escape_string($_POST["icategory"]);
    $department_id = isset($_POST["edepartment"]) ? intval($_POST["edepartment"]) : 0;
    $idescription = $mysqli->real_escape_string($_POST["edescription"]);
    $idate = $mysqli->real_escape_string($_POST["edate"]);
    $iamount = $mysqli->real_escape_string(clean($_POST["eamount"]));

    // Validate the data
    if ($iuser == '' || $iamount == '' || $department_id == 0) {
        $msgBox = alertBox("User, amount, and department are required.");
    } else {
        if ($iamount < 0) {
            $msgBox = alertBox("Amount cannot be negative.");
        } else {
            // Handle file upload for income
            $filePath = null; // Initialize file path to null

            if (isset($_FILES['ifile'])) {
                // var_dump($_FILES['ifile']);  // This will dump the file array for debugging

                if ($_FILES['ifile']['error'] == 0) {
                    $fileTmpPath = $_FILES['ifile']['tmp_name'];
                    $fileName = $_FILES['ifile']['name'];
                    $fileSize = $_FILES['ifile']['size'];
                    $fileType = $_FILES['ifile']['type'];

                    // Define the upload directory
                    $uploadDir = 'C:/xampp/htdocs/Money/file/income/'; // Define the upload directory

                    // Ensure the directory exists and is writable
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0777, true)) {
                            $msgBox = alertBox("Failed to create upload directory.");
                            exit;
                        }
                    }

                    // Generate a unique file name to prevent overwriting
                    $newFileName = time() . '_' . basename($fileName);
                    $destination = $uploadDir . $newFileName;

                    // Move the uploaded file to the destination directory
                    if (move_uploaded_file($fileTmpPath, $destination)) {
                        $filePath = $destination; // Save the file path to insert into the database
                        // $msgBox = alertBox("File uploaded successfully.");
                    } else {
                        // $msgBox = alertBox("Error uploading the file.");
                    }
                } else {
                    $msgBox = alertBox("File upload error. Error code: " . $_FILES['ifile']['error']);
                    // var_dump($_FILES['ifile']);  // Output error details for debugging
                }
            } else {
                $msgBox = alertBox("No file uploaded.");
                // var_dump($_FILES);  // Dump the entire $_FILES array for debugging
            }

            // Insert the income into the assets table, including file path
            $sql = "INSERT INTO assets (UserId, Title, Date, CategoryId, department_id, Amount, Description, AccountId, file_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            if ($statement = $mysqli->prepare($sql)) {
                $accountId = 1;  // Assuming AccountId is 1 for income

                // Bind the parameters to the prepared statement
                $statement->bind_param('issiiisss', $iuser, $iname, $idate, $icategory, $department_id, $iamount, $idescription, $accountId, $filePath);

                if ($statement->execute()) {
                    $msgBox .= alertBox("Income saved successfully.");

                    // Now, handle the ledger entry for the income
                    $checkLedgerSql = "SELECT total FROM ledger WHERE user_id = ? AND category_id = ? AND level = 2 ORDER BY created_at DESC LIMIT 1";
                    if ($checkLedgerStmt = $mysqli->prepare($checkLedgerSql)) {
                        $checkLedgerStmt->bind_param('ii', $iuser, $icategory);
                        $checkLedgerStmt->execute();
                        $checkLedgerStmt->store_result();
                        $checkLedgerStmt->bind_result($existing_total);
                        $checkLedgerStmt->fetch();

                        if ($checkLedgerStmt->num_rows > 0) {
                            $new_total = $existing_total + $iamount;
                        } else {
                            $new_total = $iamount;
                        }

                        $ledgerSql = "INSERT INTO ledger (user_id, type, category_id, amount, total, InBalance, created_at, updated_at, level) 
                                      VALUES (?, 'in', ?, ?, ?, ?, NOW(), NOW(), 2)";

                        if ($insertLedgerStmt = $mysqli->prepare($ledgerSql)) {
                            $insertLedgerStmt->bind_param('iiidd', $iuser, $icategory, $iamount, $new_total, $iamount);

                            if ($insertLedgerStmt->execute()) {
                                // $msgBox .= alertBox("Ledger entry inserted successfully.");
                            } else {
                                $msgBox .= alertBox("Error inserting into ledger: " . $insertLedgerStmt->error);
                            }
                        } else {
                            $msgBox .= alertBox("Error preparing insertLedgerSql: " . $mysqli->error);
                        }
                    } else {
                        $msgBox .= alertBox("Error preparing checkLedgerSql: " . $mysqli->error);
                    }
                } else {
                    $msgBox = alertBox("Error executing query to insert income: " . $statement->error);
                }
            } else {
                $msgBox = alertBox("Error preparing the query: " . $mysqli->error);
            }
        }
    }
}

// Check if the user is logged in by checking session variables
if (isset($_SESSION['UserId']) && isset($_SESSION['FirstName'])) {
    // User is logged in
    $loggedInUserId = $_SESSION['UserId'];
    $loggedInUserFirstName = $_SESSION['FirstName'];

    $query = "SELECT * FROM department_user WHERE UserId = '$loggedInUserId'";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        $isFromDepartmentUser = true;
    } else {
        $isFromDepartmentUser = false;
    }
} else {
    $isFromDepartmentUser = false;
}


if (isset($_SESSION['UserId']) && isset($_SESSION['FirstName'])) {
    // User is logged in
    $loggedInUserId = $_SESSION['UserId'];
    $loggedInUserFirstName = $_SESSION['FirstName'];
    $loggedInUserDepartmentId = $_SESSION['DepartmentId']; // Assuming DepartmentId is stored in the session

    // Check which table the user belongs to (department_user or user)
    $query = "SELECT * FROM department_user WHERE UserId = '$loggedInUserId'";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        // The user is from the department_user table
        $isFromDepartmentUser = true;
    } else {
        // The user is from the user table
        $isFromDepartmentUser = false;
    }
} else {
    $isFromDepartmentUser = false;
}

?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Add Income</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <?php if ($msgBox) {
                echo $msgBox;
            } ?>
            <?php
            if ($ColUser['LastName'] === 'admin') {
                ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <i class="fa fa-plus"></i> <?php echo $Incomes; ?>

                    </div>
                    <div class="panel-body">
                        <form role="form" method="post" action="" enctype="multipart/form-data">
                            <fieldset>
                                <!-- Date Field -->
                                <div class="form-group col-lg-6" id="expense">
                                    <label for="edate"><?php echo $Date; ?></label>
                                    <div class="input-group date">
                                        <input name="edate" class="form-control" type="text"
                                            value="<?php echo date("Y-m-d"); ?>">
                                        <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                                    </div>
                                </div>

                                <!-- Department Dropdown -->
                                <?php if ($isFromDepartmentUser): ?>
                                    <input type="hidden" name="edepartment" value="<?php echo $loggedInUserDepartmentId; ?>">
                                    <div class="form-group col-lg-6" style="display: none;">
                                        <label for="edepartment"><?php echo $Department; ?></label>
                                        <input type="text" class="form-control" value="<?php echo $loggedInUserDepartmentId; ?>"
                                            disabled>
                                    </div>
                                <?php else: ?>
                                    <div class="form-group col-lg-6">
                                        <label for="edepartment"><?php echo $Department; ?></label>
                                        <select name="edepartment" id="edepartment" class="form-control" required>
                                            <option value="">-- Select Department --</option>
                                            <?php
                                            $query = "SELECT id, name FROM department";
                                            $result = mysqli_query($connection, $query);

                                            if ($result) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    $selected = ($row['id'] == $loggedInUserDepartmentId) ? "selected" : "";
                                                    echo "<option value='" . $row['id'] . "' $selected>" . $row['name'] . "</option>";
                                                }
                                            } else {
                                                echo "<option value=''>No departments found</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <!-- User Dropdown -->
                                <div class="form-group col-lg-6" style="display: none;">
                                    <label for="user_id">User</label>
                                    <select name="user_id" id="user_id" class="form-control">
                                        <option value="">-- Select User --</option>
                                    </select>
                                </div>

                                <!-- Category Dropdown (Head) -->
                                <div class="form-group col-lg-6">
                                    <label for="icategory">Head</label>
                                    <select name="icategory" id="icategory" class="form-control">
                                        <option value="">-- Select Head --</option>
                                    </select>
                                </div>

                                <!-- Amount Field -->
                                <div class="form-group col-lg-6">
                                    <label for="eamount" class="control-label"><?php echo $Amount; ?></label>
                                    <div class="input-group">
                                        <span class="input-group-addon">৳</span>
                                        <input class="form-control" required placeholder="<?php echo $Amount; ?>"
                                            id="iamount" name="eamount" type="text" value="">
                                    </div>
                                </div>

                                <!-- Attach File -->
                                <div class="form-group col-lg-6">
                                    <label for="ifile">Attach File</label>
                                    <input type="file" name="ifile" id="ifile" class="form-control">
                                </div>

                                <!-- Description (Aligned with Attach File) -->
                                <div class="form-group col-lg-6">
                                    <label for="edescription"><?php echo $Description; ?></label>
                                    <textarea name="edescription" class="form-control" rows="3"></textarea>
                                </div>

                            </fieldset>
                            <div class="panel-footer">
                                <button type="submit" name="income" class="btn btn-success btn-block"><span
                                        class="glyphicon glyphicon-log-in"></span> <?php echo $SaveIncome; ?></button>
                            </div>
                        </form>


                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
</div>

<!-- jQuery and Ajax to make cascading dropdowns work -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        // When the department dropdown changes, fetch the users for the selected department
        $('#edepartment').change(function () {
            var departmentId = $(this).val();

            if (departmentId != '') {
                // Make an AJAX call to get users for the selected department
                $.ajax({
                    url: 'pages/fetch_users.php', // PHP file to fetch users based on department
                    type: 'POST',
                    data: { department_id: departmentId },
                    success: function (response) {
                        // Populate the user dropdown with the response (list of users)
                        $('#user_id').html(response);
                    }
                });

                // Make an AJAX call to get categories for the selected department (Head dropdown)
                $.ajax({
                    url: 'pages/fetch_income_categories.php ', // PHP file to fetch categories based on department
                    type: 'POST',
                    data: { department_id: departmentId },
                    success: function (response) {
                        // Populate the category dropdown with the response (list of categories)
                        $('#icategory').html(response);
                    }
                });
            } else {
                // Clear the user and category dropdowns if no department is selected
                $('#user_id').html('<option value="">-- Select User --</option>');
                $('#icategory').html('<option value="">-- Select Head --</option>');
            }
        });
    });
</script>
<script>
    $(document).on('keyup', '#iamount', function () {
        var x = $(this).val();
        $(this).val(x.toString().replace(/,/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ","));
    });
</script>

<?php
// Close the database connection
mysqli_close($connection);
?>