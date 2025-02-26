<?php
// Database credentials
$dbuser = "root";
$dbpassword = "";
$dbname = "money";
$dbhost = "localhost";

// Create a connection
$conn = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize date variables with default current date
$fromDate = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-d');
$toDate = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');

// Prepare SQL query conditions based on date filter
$dateCondition = "";
if (!empty($fromDate) && !empty($toDate)) {
    $dateCondition = " WHERE Dates BETWEEN '$fromDate' AND '$toDate' ";
}

// Fetch income (bills) data including Description with date filter
$incomeQuery = "SELECT Dates AS Date, CategoryId, Amount, Description FROM bills" . $dateCondition;
$incomeResult = $conn->query($incomeQuery);

// Fetch expense (assets) data including Description with date filter
$expenseQuery = "SELECT Date, CategoryId, Amount, Description FROM assets" . str_replace("Dates", "Date", $dateCondition);
$expenseResult = $conn->query($expenseQuery);

// Check if the queries executed correctly
if (!$incomeResult) {
    die("Error in income query: " . $conn->error);
}
if (!$expenseResult) {
    die("Error in expense query: " . $conn->error);
}

// Calculate totals
$totalIncome = 0;
$totalExpense = 0;
$profitExpense = 0; // Profit for expense
$lossIncome = 0;    // Loss for income

// Loop to calculate total income
while ($incomeRow = $incomeResult->fetch_assoc()) {
    $totalIncome += $incomeRow['Amount'];
}

// Loop to calculate total expense
while ($expenseRow = $expenseResult->fetch_assoc()) {
    $totalExpense += $expenseRow['Amount'];
}

// Reset the result pointers
$incomeResult->data_seek(0);
$expenseResult->data_seek(0);

// Determine profit or loss
if ($totalExpense > $totalIncome) {
    // If expense is more, calculate profit in expense
    $profitExpense = $totalExpense - $totalIncome;
} elseif ($totalIncome > $totalExpense) {
    // If income is more, calculate loss in income
    $lossIncome = $totalIncome - $totalExpense;
}
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Ledger Report</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="glyphicon glyphicon-stats"></i> Table of Ledger Report
                </div>
                <form method="POST" action="index.php?page=LedgerReport" style="margin-top: 10px; margin-bottom: 10px;">
                    <div class="col-lg-3">
                        <label for="from_date">From Date</label>
                        <input type="date" id="from_date" name="from_date" class="form-control"
                            value="<?php echo $fromDate; ?>" />
                    </div>
                    <div class="col-lg-3">
                        <label for="to_date">To Date</label>
                        <input type="date" id="to_date" name="to_date" class="form-control"
                            value="<?php echo $toDate; ?>" />
                    </div>
                    <div class="col-lg-3">
                        <button type="submit" class="btn btn-primary" style="margin-top: 23px;">Apply Filter</button>
                        <a href="index.php?page=LedgerReport" class="btn btn-danger" style="margin-top: 23px;">Clear</a>
                    </div>
                </form>

                <div class="panel-body">
                    <table class="table table-bordered table-hover table-striped" style="margin-top: 81px;">
                        <thead>
                            <tr>
                                <th colspan="4" style="text-align:center;">Expense</th>
                                <th colspan="4" style="text-align:center;">Income</th>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <th>Category ID</th>
                                <th>Amount</th>
                                <th>Remarks</th>
                                <th>Date</th>
                                <th>Category ID</th>
                                <th>Amount</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxRows = max($expenseResult->num_rows, $incomeResult->num_rows);
                            for ($i = 0; $i < $maxRows; $i++) {
                                $expenseRow = $expenseResult->fetch_assoc();
                                $incomeRow = $incomeResult->fetch_assoc();
                                ?>
                                <tr>
                                    <td><?php echo $expenseRow ? $expenseRow['Date'] : '0'; ?></td>
                                    <td><?php echo $expenseRow ? $expenseRow['CategoryId'] : '0'; ?></td>
                                    <td><?php echo $expenseRow ? number_format($expenseRow['Amount'], 2) . ' ৳' : '0.00 ৳'; ?>
                                    </td>
                                    <td><?php echo $expenseRow ? $expenseRow['Description'] : '0'; ?></td>

                                    <td><?php echo $incomeRow ? $incomeRow['Date'] : '0'; ?></td>
                                    <td><?php echo $incomeRow ? $incomeRow['CategoryId'] : '0'; ?></td>
                                    <td><?php echo $incomeRow ? number_format($incomeRow['Amount'], 2) . ' ৳' : '0.00 ৳'; ?>
                                    </td>
                                    <td><?php echo $incomeRow ? $incomeRow['Description'] : '0'; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="2" style="text-align:right;">Total:</th>
                                <th><?php echo number_format($totalExpense, 2); ?> ৳</th>
                                <th></th>
                                <th colspan="2" style="text-align:right;">Total:</th>
                                <th><?php echo number_format($totalIncome, 2); ?> ৳</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="2" style="text-align:right;">Profit :</th>
                                <th><?php echo $profitExpense > 0 ? number_format($profitExpense, 2) . ' ৳' : '0.00 ৳'; ?>
                                </th>
                                <th></th>
                                <th colspan="2" style="text-align:right;">Loss :</th>
                                <th><?php echo $lossIncome > 0 ? number_format($lossIncome, 2) . ' ৳' : '0.00 ৳'; ?>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
?>