<?php
// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');

// Prepare filter values
$fromDate = isset($_POST['from_date']) ? $_POST['from_date'] : '';
$toDate = isset($_POST['to_date']) ? $_POST['to_date'] : '';
$lastName = isset($_POST['last_name']) ? $_POST['last_name'] : '';
$category = isset($_POST['category_name']) ? $_POST['category_name'] : '';
$type = isset($_POST['type']) ? $_POST['type'] : '';

// Build the SQL query based on selected filters
$whereClauses = [];

if (!empty($fromDate)) {
    $whereClauses[] = "ledger.created_at >= '$fromDate'";
}

if (!empty($toDate)) {
    $whereClauses[] = "ledger.created_at <= '$toDate'";
}

if (!empty($lastName)) {
    $whereClauses[] = "department_user.LastName = '$lastName'";
}

if (!empty($category)) {
    $whereClauses[] = "category.CategoryName = '$category'";
}

if (!empty($type)) {
    $whereClauses[] = "ledger.type = '$type'";
}

$whereSql = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : "";

// Get all data from the ledger table with category name from the category table and last name from department_user table
$GetIncomeHistory = "
    SELECT 
        ledger.id, 
        ledger.user_id, 
        ledger.type, 
        ledger.category_id, 
        ledger.amount, 
        ledger.total, 
        ledger.level, 
        ledger.created_at,
        ledger.updated_at,
        department_user.LastName,
        category.CategoryName
    FROM ledger
    LEFT JOIN department_user ON ledger.user_id = department_user.UserId
    LEFT JOIN category ON ledger.category_id = category.CategoryId
    $whereSql
    ORDER BY ledger.created_at DESC
";

$IncomeHistory = mysqli_query($mysqli, $GetIncomeHistory);

include('includes/global.php');
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Incomes</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="glyphicon glyphicon-stats"></i> History of Income
                </div>
                <div class="panel-body">
                    <form action="" method="post">
                        <div class="form-group d-flex align-items-end gap-2 flex-wrap"
                            style="display: flex; gap: 7px; margin-bottom: 35px;">
                            <div class="d-flex flex-column">
                                <label for="from_date" class="fw-bold">From Date</label>
                                <input type="date" name="from_date" id="from_date" class="form-control"
                                    value="<?php echo !empty($fromDate) ? $fromDate : date('Y-m-d'); ?>">
                            </div>

                            <div class="d-flex flex-column">
                                <label for="to_date" class="fw-bold">To Date</label>
                                <input type="date" name="to_date" id="to_date" class="form-control"
                                    value="<?php echo !empty($toDate) ? $toDate : date('Y-m-d'); ?>">
                            </div>


                            <div class="d-flex flex-column">
                                <label for="last_name" class="fw-bold">Name</label>
                                <select name="last_name" id="last_name" class="form-control">
                                    <option value="">Select Name</option>
                                    <?php
                                    // Fetch all users from department_user table
                                    $userQuery = "SELECT UserId, FirstName, LastName FROM department_user";
                                    $userResult = mysqli_query($mysqli, $userQuery);

                                    // Loop through the results and populate the dropdown
                                    while ($user = mysqli_fetch_assoc($userResult)) {
                                        $selected = ($user['UserId'] == $lastName) ? 'selected' : '';
                                        echo "<option value='{$user['UserId']}' {$selected}>{$user['FirstName']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>


                            <div class="d-flex flex-column">
                                <label for="type" class="fw-bold">Type</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="">Select Type</option>
                                    <?php
                                    // Fetch distinct 'type' values from the ledger table
                                    $typeQuery = "SELECT DISTINCT type FROM ledger";
                                    $typeResult = mysqli_query($mysqli, $typeQuery);

                                    while ($typeRow = mysqli_fetch_assoc($typeResult)) {
                                        $selected = ($typeRow['type'] == $type) ? 'selected' : '';
                                        // Displaying the type exactly as stored in the database
                                        echo "<option value='{$typeRow['type']}' {$selected}>{$typeRow['type']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="d-flex align-items-end">
                                <button class="btn btn-primary" name="searchbtn" type="submit"
                                    style="margin-top: 26px;">
                                    <i class="fa fa-search"></i>
                                </button>
                                <button class="btn btn-danger" type="reset" style="margin-top: 26px;"
                                    onclick="window.location='http://localhost/money/index.php?page=Ledger';">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th class="text-left">Name</th>
                                <th class="text-left">Head</th>
                                <th class="text-left">Type</th>
                                <th class="text-left">Amount</th>
                                <th class="text-left">Total</th>
                                <th class="text-left">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($col = mysqli_fetch_assoc($IncomeHistory)) { ?>
                                <tr>
                                    <td><?php echo $col['LastName']; ?></td>
                                    <td><?php echo $col['CategoryName']; ?></td>
                                    <td><?php echo $col['type']; ?></td>
                                    <td><?php echo number_format($col['amount']); ?> Tk</td>
                                    <td><?php echo number_format($col['total']); ?> Tk</td>
                                    <td><?php echo date("M d Y", strtotime($col['created_at'])); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>