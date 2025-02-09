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
    $iuser = $_SESSION['UserId'];
    $iname = "Hardcoded Name";
    $icategory = $mysqli->real_escape_string($_POST["icategory"]);
    $department_id = isset($_POST["edepartment"]) ? intval($_POST["edepartment"]) : 0;
    $idescription = $mysqli->real_escape_string($_POST["edescription"]);
    $idate = $mysqli->real_escape_string($_POST["edate"]);
    $iamount = $mysqli->real_escape_string(clean($_POST["eamount"]));

    // var_dump($iuser, $icategory, $department_id, $idescription, $idate, $iamount);

    if ($iuser == '' || $iamount == '' || $department_id == 0) {
        $msgBox = alertBox("User, amount, and department are required.");
    } else {
        if ($iamount < 0) {
            $msgBox = alertBox("Amount cannot be negative.");
        } else {
            $sql = "INSERT INTO assets (UserId, Title, Date, CategoryId, department_id, Amount, Description, AccountId) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            // var_dump($sql);

            if ($statement = $mysqli->prepare($sql)) {
                // AccountId will always be 1
                $accountId = 1;

                $statement->bind_param('issiiisi', $iuser, $iname, $idate, $icategory, $department_id, $iamount, $idescription, $accountId);

                // var_dump($iuser, $iname, $idate, $icategory, $department_id, $iamount, $idescription, $accountId);

                if ($statement->execute()) {
                    $msgBox = alertBox("Income saved successfully.");
                } else {
                    $msgBox = alertBox("Error: " . $mysqli->error);
                }
            } else {
                $msgBox = alertBox("Error preparing the query: " . $mysqli->error);
            }
        }
    }
}



// Save Expense Form
if (isset($_POST['expense'])) {
    $euser = $_SESSION['UserId'];
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
        // Handle error if no such account is found
        $msgBox .= alertBox("Cash account not found.");
    }

    $msgBox = '';
    if ($eamount == '') {
        $msgBox .= alertBox("Amount is required.");
    }
    if ($department_id == 0) {
        $msgBox .= alertBox("Department is required.");
    }

    if ($msgBox == '') {
        if ($eamount < 0) {
            $msgBoxExpense = alertBox($NegativeAmount);
        } else {
            // Prepare the SQL query for execution
            $sql = "INSERT INTO bills (UserId, department_id, Title, Dates, CategoryId, AccountId, Amount, Description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            if ($statement = $mysqli->prepare($sql)) {
                if ($statement->bind_param('iissiiis', $euser, $department_id, $ename, $edate, $ecategory, $eaccount, $eamount, $edescription)) {
                    if ($statement->execute()) {
                        $msgBoxExpense = alertBox($SaveMsgExpense);
                    } else {
                        $msgBoxExpense = alertBox("Error executing query: " . $statement->error);
                    }
                } else {
                    $msgBoxExpense = alertBox("Error binding parameters: " . $statement->error);
                }
            } else {
                $msgBoxExpense = alertBox("Error preparing the query: " . $mysqli->error);
            }
        }
    }
}


?>

<!-- Page Content -->
<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?php echo $Transaction; ?></h1>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 ">
            <?php if ($msgBoxExpense) {
                echo $msgBoxExpense;
            } ?>
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <i class="fa fa-minus"></i> <?php echo $Expenses; ?>
                </div>
                <div class="panel-body">
                    <form action="" method="post" role="form">
                        <fieldset>
                            <!-- User Dropdown -->
                            <div class="form-group col-lg-6">
                                <label for="user_id">User</label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">-- Select User --</option>
                                    <?php
                                    // Fetch users from department_user table
                                    $query = "SELECT UserId, FirstName FROM department_user";
                                    $result = mysqli_query($connection, $query);

                                    if ($result) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['UserId'] . "'>" . $row['FirstName'] . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>No users found</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Department Dropdown (Replacing Account Dropdown) -->
                            <div class="form-group col-lg-6">
                                <label for="edepartment"><?php echo $Department; ?></label>
                                <select name="edepartment" class="form-control">
                                    <?php
                                    // Fetch departments from the department table
                                    $query = "SELECT id, name FROM department"; // Modify if needed
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

                            <!-- Category Dropdown (Head) -->
                            <div class="form-group col-lg-6">
                                <label for="ecategory">Head</label>
                                <select name="ecategory" class="form-control">
                                    <?php while ($col = mysqli_fetch_assoc($expense)) { ?>
                                        <option value="<?php echo $col['CategoryId']; ?>">
                                            <?php echo $col['CategoryName']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- Amount Field -->
                            <div class="form-group col-lg-6">
                                <label for="eamount" class="control-label"><?php echo $Amount; ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon"><?php echo $ColUser['Currency']; ?></span>
                                    <input class="form-control" required placeholder="<?php echo $Amount; ?>"
                                        id="iamount" name="eamount" type="text" value="">
                                </div>
                            </div>

                            <!-- Date Field -->
                            <div class="form-group col-lg-6" id="expense">
                                <label for="edate"><?php echo $Date; ?></label>
                                <div class="input-group date">
                                    <input name="edate" class="form-control" type="text"
                                        value="<?php echo date("Y-m-d"); ?>">
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                                </div>
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


        <div class="col-lg-6 ">
            <?php if ($msgBox) {
                echo $msgBox;
            } ?>
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-plus"></i> <?php echo $Incomes; ?>
                </div>
                <div class="panel-body">
                    <form role="form" method="post" action="">
                        <fieldset>
                            <!-- User Dropdown -->
                            <div class="form-group col-lg-6">
                                <label for="user_id">User</label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">-- Select User --</option>
                                    <?php
                                    // Fetch users from department_user table
                                    $query = "SELECT UserId, FirstName FROM department_user";
                                    $result = mysqli_query($connection, $query);

                                    if ($result) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<option value='" . $row['UserId'] . "'>" . $row['FirstName'] . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>No users found</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Department Dropdown (Replacing Account Dropdown) -->
                            <div class="form-group col-lg-6">
                                <label for="edepartment"><?php echo $Department; ?></label>
                                <select name="edepartment" class="form-control">
                                    <?php
                                    // Fetch departments from the department table
                                    $query = "SELECT id, name FROM department"; // Modify if needed
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

                            <!-- Category Dropdown (Head) -->
                            <div class="form-group col-lg-6">
                                <label for="icategory">Head</label>
                                <select name="icategory" class="form-control">
                                    <?php while ($col = mysqli_fetch_assoc($income)) { ?>
                                        <option value="<?php echo $col['CategoryId']; ?>">
                                            <?php echo $col['CategoryName']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- Amount Field -->
                            <div class="form-group col-lg-6">
                                <label for="eamount" class="control-label"><?php echo $Amount; ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon"><?php echo $ColUser['Currency']; ?></span>
                                    <input class="form-control" required placeholder="<?php echo $Amount; ?>"
                                        id="iamount" name="eamount" type="text" value="">
                                </div>
                            </div>

                            <!-- Date Field -->
                            <div class="form-group col-lg-6" id="expense">
                                <label for="edate"><?php echo $Date; ?></label>
                                <div class="input-group date">
                                    <input name="edate" class="form-control" type="text"
                                        value="<?php echo date("Y-m-d"); ?>">
                                    <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                                </div>
                            </div>

                            <!-- Description Field -->
                            <div class="form-group col-lg-12 clearbothh">
                                <label for="edescription"><?php echo $Description; ?></label>
                                <textarea name="edescription" class="form-control"></textarea>
                            </div>
                        </fieldset>


                </div>
                <div class="panel-footer">
                    <button type="submit" name="income" class="btn btn-success btn-block"><span
                            class="glyphicon glyphicon-log-in"></span> <?php echo $SaveIncome; ?></button>
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