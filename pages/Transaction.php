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
    // Get the user ID from the session
    $euser = isset($_SESSION['UserId']) ? $_SESSION['UserId'] : 0; // Fetch the UserId from session

    $ename = "Hardcoded Name"; // Hardcoding the name
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

    // Get the department_id of the logged-in user from the department_user table
    $department_id = 0; // Default value
    if ($euser != 0) {
        $departmentQuery = "SELECT department_id FROM department_user WHERE UserId = ?";
        if ($stmt = $mysqli->prepare($departmentQuery)) {
            $stmt->bind_param('i', $euser);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($department_id);
            $stmt->fetch();
            $stmt->close();
        } else {
            $msgBox .= alertBox("Error fetching department_id: " . $mysqli->error);
        }
    }

    // If no department_id found or eamount is empty, display an error message
    if ($department_id == 0) {
        $msgBox .= alertBox("Department is required.");
    }

    if ($eamount == '') {
        $msgBox .= alertBox("Amount is required.");
    }

    if ($msgBox == '') {
        // Insert the expense into the bills table, including the department_id
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

$query = "SELECT CategoryId, CategoryName FROM category";
$result = mysqli_query($mysqli, $query);

if ($result) {
    $categories = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $categories = [];
}
$loggedInUserId = $_SESSION['UserId']; // Assuming you have UserId in the session

// Fetch department_id from department_user table based on logged-in UserId
$query = "SELECT department_id FROM department_user WHERE UserId = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
$departmentRow = $result->fetch_assoc();
$departmentId = $departmentRow['department_id']; // Get the department_id for the logged-in user
$stmt->close();

// Fetch categories based on the department_id of the logged-in user
$query = "SELECT CategoryId, CategoryName FROM category WHERE department_id = ? AND Level = 2";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $departmentId);
$stmt->execute();
$categoryResult = $stmt->get_result();
$stmt->close();
$userId = $_SESSION['UserId'];

// Fetch the current balance for the logged-in user from the 'user_balance' table
$getBalanceQuery = "SELECT balance FROM user_balance WHERE user_id = '$userId' LIMIT 1";
$result = mysqli_query($mysqli, $getBalanceQuery);

// Check if the balance exists
$currentBalance = 0;
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $currentBalance = $row['balance'];
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
                    <span class="pull-right">Current Balance:<strong>
                        <?php echo number_format($currentBalance, 2); ?> Tk</span></strong>
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


                            <div class="form-group col-lg-6">
                                <label for="ecategory">Head</label>
                                <select name="ecategory" id="ecategory" class="form-control">
                                    <option value="">-- Select Head --</option>
                                    <?php
                                    // Check if any categories are returned and populate the dropdown
                                    if ($categoryResult->num_rows > 0) {
                                        while ($row = $categoryResult->fetch_assoc()) {
                                            echo "<option value='" . $row['CategoryId'] . "'>" . $row['CategoryName'] . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>No categories available for your department</option>";
                                    }
                                    ?>
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