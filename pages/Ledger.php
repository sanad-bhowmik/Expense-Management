<?php
// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');

$currentDate = date('Y-m-d');

// Prepare filter values
$fromDate = isset($_POST['from_date']) ? $_POST['from_date'] : '';
$toDate = isset($_POST['to_date']) ? $_POST['to_date'] : '';
$userId = isset($_POST['user_id']) ? $_POST['user_id'] : ''; // User Filter

// Build the SQL query based on selected filters
$whereClauses = [];

if (!empty($fromDate)) {
    // Only consider date part for accurate filtering
    $whereClauses[] = "DATE(ledger.created_at) >= '$fromDate'";
}

if (!empty($toDate)) {
    $whereClauses[] = "DATE(ledger.created_at) <= '$toDate'";
}

if (!empty($userId)) {
    $whereClauses[] = "ledger.user_id = '$userId'";
}

$whereSql = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : "";

// Initialize variables
$IncomeHistory = null;
$openingBalance = 0; // Default Opening Balance
$closingBalance = 0; // Default Closing Balance

// Fetch Opening Balance (sum of all transactions before the selected From Date)
if (!empty($fromDate)) {
    $GetOpeningBalance = "
        SELECT 
            SUM(InBalance) - SUM(OutBalance) AS OpeningBalance
        FROM ledger
        WHERE DATE(created_at) < '$fromDate'
    ";

    $OpeningBalanceResult = mysqli_query($mysqli, $GetOpeningBalance);
    if ($OpeningBalanceResult) {
        $openingData = mysqli_fetch_assoc($OpeningBalanceResult);
        $openingBalance = $openingData['OpeningBalance'] ?? 0;
    }
}

// Fetch filtered data
if (!empty($fromDate) || !empty($toDate) || !empty($userId)) {
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
            department_user.LastName,
            category.CategoryName,
            assets.Description AS Remarks
        FROM ledger
        LEFT JOIN department_user ON ledger.user_id = department_user.UserId
        LEFT JOIN category ON ledger.category_id = category.CategoryId
        LEFT JOIN assets ON ledger.user_id = assets.UserId
        $whereSql
        ORDER BY ledger.created_at ASC
    ";

    $IncomeHistory = mysqli_query($mysqli, $GetIncomeHistory);
}

// Calculate Closing Balance
$closingBalance = $openingBalance; // Start from Opening Balance
if ($IncomeHistory) {
    while ($col = mysqli_fetch_assoc($IncomeHistory)) {
        $closingBalance += $col['InBalance'] - $col['OutBalance'];
    }
}

// Reset query pointer for displaying the table
if ($IncomeHistory) {
    mysqli_data_seek($IncomeHistory, 0);
}

// Load Users for Dropdown
$GetUsers = "SELECT UserId, FirstName, LastName FROM department_user ORDER BY FirstName ASC";
$Users = mysqli_query($mysqli, $GetUsers);

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
                                    value="<?php echo $fromDate ? $fromDate : $currentDate; ?>">
                            </div>

                            <div class="d-flex flex-column">
                                <label for="to_date" class="fw-bold">To Date</label>
                                <input type="date" name="to_date" id="to_date" class="form-control"
                                    value="<?php echo $toDate ? $toDate : $currentDate; ?>">
                            </div>

                            <div class="d-flex flex-column">
                                <label for="user_id" class="fw-bold">User</label>
                                <select name="user_id" id="user_id" class="form-control">
                                    <option value="">All Users</option>
                                    <?php while ($row = mysqli_fetch_assoc($Users)) { ?>
                                        <option value="<?php echo $row['UserId']; ?>" <?php echo ($userId == $row['UserId']) ? 'selected' : ''; ?>>
                                            <?php echo $row['FirstName']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="d-flex align-items-end">
                                <button class="btn btn-primary" name="searchbtn" type="submit"
                                    style="margin-top: 26px;">
                                    <i class="fa fa-search"></i>
                                </button>
                                <button class="btn btn-danger" type="reset" style="margin-top: 26px;"
                                    onclick="window.location='index.php?page=Ledger';">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Display Opening and Closing Balance -->
                    <p style="color: green;"><strong>Opening Balance:</strong>
                        <?php echo number_format($openingBalance, 2); ?> Tk</p>
                    <p style="color: red;"><strong>Closing Balance:</strong>
                        <?php echo number_format($closingBalance, 2); ?> Tk</p>

                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Head</th>
                                <th>Remarks</th>
                                <th>InBalance</th>
                                <th>OutBalance</th>
                                <th>Type</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalInBalance = 0;
                            $totalOutBalance = 0;

                            if ($IncomeHistory && mysqli_num_rows($IncomeHistory) > 0) {
                                while ($col = mysqli_fetch_assoc($IncomeHistory)) {
                                    $totalInBalance += $col['InBalance']; // Sum up InBalance
                                    $totalOutBalance += $col['OutBalance']; // Sum up OutBalance
                                    ?>
                                    <tr>
                                        <td><?php echo date("M d, Y", strtotime($col['created_at'])); ?></td>
                                        <td>
                                            <?php
                                            if (!empty($col['InBalance']) && $col['InBalance'] > 0) {
                                                echo "Given Balance by Admin";
                                            } else {
                                                echo $col['CategoryName'];
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo !empty($col['Remarks']) ? $col['Remarks'] : 'No Remarks'; ?></td>
                                        <td><?php echo number_format($col['InBalance'], 2); ?> Tk</td>
                                        <td><?php echo number_format($col['OutBalance'], 2); ?> Tk</td>
                                        <td><?php echo $col['type']; ?></td>
                                        <td><?php echo number_format($col['total'], 2); ?> Tk</td>
                                    </tr>
                                <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">No data available</td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>

                        <!-- Table Footer Showing Totals -->
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Total:</th>
                                <th style="color: navy;"><strong><?php echo number_format($totalInBalance, 2); ?>
                                        Tk</strong></th>
                                <th style="color: navy;"><strong><?php echo number_format($totalOutBalance, 2); ?>
                                        Tk</strong></th>
                                <th colspan="2"></th> <!-- Empty columns to align structure -->
                            </tr>
                        </tfoot>

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>