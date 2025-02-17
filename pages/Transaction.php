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
// Save Expense Form
if (isset($_POST['expense'])) {
    $euser = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $ename = "Hardcoded Name"; // Hardcoding the name
    $department_id = $mysqli->real_escape_string($_POST["edepartment"]);
    $ecategory = $mysqli->real_escape_string($_POST["ecategory"]);
    $edescription = isset($_POST["edescription"]) ? $mysqli->real_escape_string($_POST["edescription"]) : ""; // Optional description
    $edate = $mysqli->real_escape_string($_POST["edate"]);
    $eamount = $mysqli->real_escape_string(clean($_POST["eamount"]));

    // Fetch the AccountId for "Cash"
    $query = "SELECT AccountId FROM account WHERE AccountName = 'Cash' LIMIT 1";
    $result = mysqli_query($connection, $query);
    if ($result) {
        $account_row = mysqli_fetch_assoc($result);
        $eaccount = $account_row['AccountId']; // Assign the correct AccountId for 'Cash'
    } else {
        $msgBox .= alertBox("Cash account not found.");
    }

    // Validation checks
    $msgBox = '';
    $msgBoxFile = '';
    $fileUploaded = false;
    $uploadFilePath = ""; // Default file path is empty
    if (isset($_FILES['efile']) && $_FILES['efile']['error'] == 0) {
        $uploadDir = 'C:/xampp/htdocs/Money/file/expense/'; // Directory to save the file
        $fileName = basename($_FILES['efile']['name']);  // Get the file name
        $uploadFilePath = $uploadDir . $fileName;

        // Optionally, validate file (e.g., check for allowed file types or size)
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];  // Example allowed types
        if (in_array($_FILES['efile']['type'], $allowedTypes)) {
            // Move the file to the specified directory
            if (move_uploaded_file($_FILES['efile']['tmp_name'], $uploadFilePath)) {
                $fileUploaded = true;
                $msgBoxFile = alertBox("File uploaded successfully.");
            } else {
                $msgBoxFile = alertBox("Error moving the uploaded file.");
            }
        } else {
            $msgBoxFile = alertBox("Invalid file type. Allowed types: PDF, JPEG, PNG.");
        }
    } else {
        $msgBoxFile = alertBox("No file uploaded or error occurred with file upload.");
    }

    if ($eamount == '') {
        $msgBox .= alertBox("Amount is required.");
    }
    if ($department_id == 0) {
        $msgBox .= alertBox("Department is required.");
    }

    if ($msgBox == '') {
        // Insert the expense into the bills table, including the file_path
        $sql = "INSERT INTO bills (UserId, department_id, Title, Dates, CategoryId, AccountId, Amount, Description, file_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($statement = $mysqli->prepare($sql)) {
            if ($statement->bind_param('iissiiiss', $euser, $department_id, $ename, $edate, $ecategory, $eaccount, $eamount, $edescription, $uploadFilePath)) {
                if ($statement->execute()) {
                    $msgBoxExpense = alertBox("Expense saved successfully.");

                    // Now, update the user balance by subtracting the expense amount (negative amount)
                    $balanceQuery = "SELECT balance FROM user_balance WHERE user_id = ?";
                    if ($balanceStmt = $mysqli->prepare($balanceQuery)) {
                        $balanceStmt->bind_param('i', $euser);
                        $balanceStmt->execute();
                        $balanceStmt->store_result();
                        $balanceStmt->bind_result($current_balance);
                        $balanceStmt->fetch();

                        // Check if balance exists for the user
                        if ($balanceStmt->num_rows > 0) {
                            // Calculate the new balance (subtract the expense amount)
                            $new_balance = $current_balance - abs($eamount);  // Subtract the absolute value of the expense amount

                            // Update the balance in the user_balance table
                            $updateBalanceQuery = "UPDATE user_balance SET balance = ? WHERE user_id = ?";
                            if ($updateStmt = $mysqli->prepare($updateBalanceQuery)) {
                                $updateStmt->bind_param('di', $new_balance, $euser);
                                if ($updateStmt->execute()) {
                                } else {
                                    $msgBoxExpense .= alertBox("Error updating user balance: " . $updateStmt->error);
                                }
                            } else {
                                $msgBoxExpense .= alertBox("Error preparing query to update balance: " . $mysqli->error);
                            }
                        } else {
                            // If no balance record exists for the user, create a new balance entry with a negative amount
                            $createBalanceQuery = "INSERT INTO user_balance (user_id, balance) VALUES (?, ?)";
                            if ($createStmt = $mysqli->prepare($createBalanceQuery)) {
                                $new_balance = 0 - abs($eamount); // Start balance with the negative expense amount
                                $createStmt->bind_param('id', $euser, $new_balance);
                                if ($createStmt->execute()) {
                                    $msgBoxExpense .= alertBox("New balance record created and updated.");
                                } else {
                                    $msgBoxExpense .= alertBox("Error creating balance record: " . $createStmt->error);
                                }
                            } else {
                                $msgBoxExpense .= alertBox("Error preparing query to create balance record: " . $mysqli->error);
                            }
                        }
                        $balanceStmt->close();
                    } else {
                        $msgBoxExpense .= alertBox("Error fetching current balance: " . $mysqli->error);
                    }

                    // Now, handle the ledger entry
                    // Check if there's an existing ledger entry for the user and category.
                    $checkLedgerSql = "SELECT total FROM ledger WHERE user_id = ? AND category_id = ? AND level = 1 ORDER BY created_at DESC LIMIT 1";
                    if ($checkLedgerStmt = $mysqli->prepare($checkLedgerSql)) {
                        $checkLedgerStmt->bind_param('ii', $euser, $ecategory);
                        $checkLedgerStmt->execute();
                        $checkLedgerStmt->store_result();
                        $checkLedgerStmt->bind_result($existing_total);
                        $checkLedgerStmt->fetch();

                        // Check if a previous ledger entry was found
                        if ($checkLedgerStmt->num_rows > 0) {
                            // Subtract the expense amount from the existing total in the ledger
                            $new_total = $existing_total - abs($eamount);  // Subtract the absolute value of the expense amount
                        } else {
                            // If no previous entry, the new total is simply the negative expense amount
                            $new_total = $eamount;
                        }

                        // Insert a new ledger entry with the calculated total and level = 1
                        $ledgerSql = "INSERT INTO ledger (user_id, type, category_id, amount, total, OutBalance, created_at, updated_at, level) 
                        VALUES (?, 'out', ?, ?, ?, ?, NOW(), NOW(), 1)";

                        if ($insertLedgerStmt = $mysqli->prepare($ledgerSql)) {
                            // Bind the parameters: user_id, category_id, amount, total, out_balance
                            $insertLedgerStmt->bind_param('iiidd', $euser, $ecategory, $eamount, $new_total, $eamount);
                            // $new_total is the updated balance after subtracting the expense, and $eamount is the value to post into 'OutBalance'

                            if ($insertLedgerStmt->execute()) {
                                // Successfully inserted into ledger
                            } else {
                                $msgBoxExpense .= alertBox("Error inserting into ledger: " . $insertLedgerStmt->error);
                            }
                        } else {
                            $msgBoxExpense .= alertBox("Error preparing insertLedgerSql: " . $mysqli->error);
                        }

                    } else {
                        $msgBoxExpense .= alertBox("Error preparing checkLedgerSql: " . $mysqli->error);
                    }
                } else {
                    $msgBoxExpense = alertBox("Error executing query to insert expense: " . $statement->error);
                }
            } else {
                $msgBoxExpense = alertBox("Error binding parameters: " . $statement->error);
            }
        } else {
            $msgBoxExpense = alertBox("Error preparing the query to insert expense: " . $mysqli->error);
        }
    }
}

?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Add Expense</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 ">
            <?php if ($msgBoxExpense) {
                echo $msgBoxExpense;
            } ?>

            <div class="panel panel-danger">
                <div class="panel-heading">
                    <i class="fa fa-minus"></i> <?php echo $Expenses; ?>
                </div>
                <div class="panel-body">
                    <form action="" method="post" role="form" enctype="multipart/form-data">
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

                            <!-- Department Dropdown (Replacing Account Dropdown) -->
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
                                                echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                                            }
                                        } else {
                                            echo "<option value=''>No departments found</option>";
                                        }
                                        ?>
                                    </select>

                                </div>
                            <?php endif; ?>

                            <?php if ($isFromDepartmentUser): ?>
                                <!-- If the user is from the department_user table, hide the dropdown completely using display: none -->
                                <input type="hidden" name="user_id" value="<?php echo $loggedInUserId; ?>">
                                <div class="form-group col-lg-6" style="display: none;">
                                    <label for="user_id">User</label>
                                    <input type="text" class="form-control" value="<?php echo $loggedInUserFirstName; ?>"
                                        disabled>
                                </div>
                            <?php else: ?>
                                <!-- If the user is from the user table, show the dropdown -->
                                <div class="form-group col-lg-6">
                                    <label for="user_id">User</label>
                                    <select name="user_id" id="user_id" class="form-control" required>
                                        <option value="">-- Select User --</option>
                                    </select>

                                </div>
                            <?php endif; ?>


                            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                            <script>
                                $(document).ready(function () {
                                    $("#edepartment").change(function () {
                                        var departmentId = $(this).val();

                                        if (departmentId !== "") {
                                            // Fetch Categories
                                            $.ajax({
                                                url: "pages/fetch_categories.php",
                                                type: "POST",
                                                data: { department_id: departmentId },
                                                success: function (response) {
                                                    $("#ecategory").html(response);
                                                }
                                            });

                                            // Fetch Users
                                            $.ajax({
                                                url: "pages/fetch_users.php",
                                                type: "POST",
                                                data: { department_id: departmentId },
                                                success: function (response) {
                                                    $("#user_id").html(response);
                                                }
                                            });

                                        } else {
                                            $("#ecategory").html('<option value="">-- Select Category --</option>');
                                            $("#user_id").html('<option value="">-- Select User --</option>');
                                        }
                                    });
                                });
                            </script>


                            <!-- Category Dropdown (Head) -->
                            <div class="form-group col-lg-6">
                                <label for="ecategory">Head</label>
                                <select name="ecategory" id="ecategory" class="form-control">
                                    <option value="">-- Select Head --</option>
                                </select>

                            </div>
                            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                            <script>
                                $(document).ready(function () {
                                    $("#edepartment").change(function () {
                                        var departmentId = $(this).val();

                                        if (departmentId !== "") {
                                            $.ajax({
                                                url: "pages/fetch_categories.php",
                                                type: "POST",
                                                data: { department_id: departmentId },
                                                success: function (response) {
                                                    $("#ecategory").html(response);
                                                }
                                            });
                                        } else {
                                            $("#ecategory").html('<option value="">-- Select Category --</option>');
                                        }
                                    });
                                });
                            </script>


                            <!-- Amount Field -->
                            <div class="form-group col-lg-6">
                                <label for="eamount" class="control-label"><?php echo $Amount; ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon">৳</span>
                                    <input class="form-control" required placeholder="<?php echo $Amount; ?>"
                                        id="iamount" name="eamount" type="text" value="">
                                </div>
                            </div>
                            <div class="form-group col-lg-6">
                                <label for="efile">Attach File</label>
                                <input type="file" name="efile" id="efile" class="form-control">
                            </div>


                            <!-- Description Field -->
                            <div class="form-group col-lg-12 clearbothh">
                                <label for="edescription"><?php echo $Description; ?></label>
                                <textarea name="edescription" class="form-control"></textarea>
                            </div>
                        </fieldset>


                        <div class="panel-footer">
                            <button type="submit" name="expense" class="btn btn-warning btn-block"><span
                                    class="glyphicon glyphicon-log-in"></span> <?php echo $SaveExpense; ?></button>
                        </div>
                    </form>

                </div>
            </div>
        </div>



    </div>
</div>
</div>
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