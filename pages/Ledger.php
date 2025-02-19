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
        ledger.InBalance,
        ledger.OutBalance,
        department_user.FirstName,
        category.CategoryName
    FROM ledger
    LEFT JOIN department_user ON ledger.user_id = department_user.UserId
    LEFT JOIN category ON ledger.category_id = category.CategoryId
    $whereSql
    ORDER BY ledger.created_at DESC
";

$IncomeHistory = mysqli_query($mysqli, $GetIncomeHistory);

// Calculate Opening Balance (before the from_date)
$openingBalance = 0;

if (!empty($fromDate)) {
    $openingBalanceQuery = "
        SELECT 
            IFNULL(SUM(InBalance), 0) AS total_inbalance,
            IFNULL(SUM(OutBalance), 0) AS total_outbalance
        FROM ledger
        WHERE created_at < '$fromDate'
    ";

    $openingBalanceResult = mysqli_query($mysqli, $openingBalanceQuery);
    $openingBalanceRow = mysqli_fetch_assoc($openingBalanceResult);

    $openingBalance = $openingBalanceRow['total_inbalance'] - $openingBalanceRow['total_outbalance'];
}

// Calculate Closing Balance (within the filtered date range)
$closingBalance = $openingBalance;
while ($col = mysqli_fetch_assoc($IncomeHistory)) {
    $closingBalance += $col['InBalance'] - $col['OutBalance'];
}

// Reset the pointer of the result set to the beginning
mysqli_data_seek($IncomeHistory, 0);

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

                    <!-- Display Opening Balance -->
                    <p><strong>Opening Balance:</strong> <?php echo number_format($openingBalance, 2); ?> Tk</p>

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
                                    <td><?php echo $col['FirstName']; ?></td>
                                    <td><?php echo $col['CategoryName']; ?></td>
                                    <td><?php echo $col['type']; ?></td>
                                    <td><?php echo number_format($col['amount'], 2); ?> Tk</td>
                                    <td><?php echo number_format($col['total'], 2); ?> Tk</td>
                                    <td><?php echo date("M d Y", strtotime($col['created_at'])); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                    <!-- Display Closing Balance -->
                    <p><strong>Closing Balance:</strong> <?php echo number_format($closingBalance, 2); ?> Tk</p>
                </div>
            </div>
        </div>
    </div>
</div>
