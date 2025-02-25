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

// Fetch income (bills) data including Description
$incomeQuery = "SELECT Dates AS Date, CategoryId, Amount, Description FROM bills";
$incomeResult = $conn->query($incomeQuery);

// Fetch expense (assets) data including Description
$expenseQuery = "SELECT Date, CategoryId, Amount, Description FROM assets";
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
                <form method="POST" action="" style="margin-top: 10px;margin-bottom: 10px;">
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
                    </div>
                </form>
                <div class="panel-body">
                    <table class="table table-bordered table-hover table-striped" style="margin-top: 81px;">
                        <thead>
                            <tr>
                                <!-- Header for Expense and Income -->
                                <th colspan="4" style="text-align:center;">Expense</th>
                                <th colspan="4" style="text-align:center;">Income</th>
                            </tr>
                            <tr>
                                <!-- Column headers for both Expense and Income -->
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
                            // Fetch and display both Expense and Income data side by side
                            $maxRows = max($expenseResult->num_rows, $incomeResult->num_rows);
                            for ($i = 0; $i < $maxRows; $i++) {
                                // Fetch expense row if available
                                $expenseRow = $expenseResult->fetch_assoc();
                                // Fetch income row if available
                                $incomeRow = $incomeResult->fetch_assoc();
                                ?>
                                <tr>
                                    <!-- Display Expense data -->
                                    <td><?php echo $expenseRow ? $expenseRow['Date'] : ''; ?></td>
                                    <td><?php echo $expenseRow ? $expenseRow['CategoryId'] : ''; ?></td>
                                    <td><?php echo $expenseRow ? number_format($expenseRow['Amount'], 2) . ' ৳' : ''; ?>
                                    </td>
                                    <td><?php echo $expenseRow ? $expenseRow['Description'] : ''; ?></td>

                                    <!-- Display Income data -->
                                    <td><?php echo $incomeRow ? $incomeRow['Date'] : ''; ?></td>
                                    <td><?php echo $incomeRow ? $incomeRow['CategoryId'] : ''; ?></td>
                                    <td><?php echo $incomeRow ? number_format($incomeRow['Amount'], 2) . ' ৳' : ''; ?></td>
                                    <td><?php echo $incomeRow ? $incomeRow['Description'] : ''; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>

                        <tfoot>
                            <tr>
                                <!-- Total Expense and Income row -->
                                <th colspan="2" style="text-align:right;">Total:</th>
                                <th><?php echo number_format($totalExpense, 2); ?> ৳</th>
                                <th></th>

                                <th colspan="2" style="text-align:right;">Total:</th>
                                <th><?php echo number_format($totalIncome, 2); ?> ৳</th>
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
// Close the connection
$conn->close();
?>