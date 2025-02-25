<?php

// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');

$SearchTerm = '';
$DepartmentId = ''; // To store selected department filter
$CategoryName = ''; // To store the selected category filter

// Get the UserId from the session (assuming the user is logged in)
session_start();
if (isset($_SESSION['UserId'])) {
    $UserId = $_SESSION['UserId'];  // Fetch UserId from session
} else {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

if (isset($_POST['submitin'])) {

    $BillsId = $_POST['BillsId'];

    // Delete bills
    $Delete = "DELETE FROM bills WHERE BillsId = $BillsId AND UserId = $UserId";  // Ensure only the user can delete their own bills
    $DeleteI = mysqli_query($mysqli, $Delete);

    $msgBox = alertBox($DeleteExpense);
}

// Fetch department list for dropdown
$GetDepartments = "SELECT * FROM department ORDER BY name ASC";
$Departments = mysqli_query($mysqli, $GetDepartments);

// Build the query for fetching the bills, apply filters if set
$GetExpenseHistory = "
    SELECT 
        bills.*, 
        category.CategoryName, 
        account.AccountName, 
        department.name AS DepartmentName, 
        CONCAT(department_user.FirstName, ' ', department_user.LastName) AS FullName
    FROM bills
    LEFT JOIN category ON bills.CategoryId = category.CategoryId
    LEFT JOIN account ON bills.AccountId = account.AccountId
    LEFT JOIN department ON bills.department_id = department.id
    LEFT JOIN department_user ON bills.UserId = department_user.UserId
    WHERE bills.UserId = $UserId  /* Filter by logged-in user */
";

// Apply department filter if selected
if (!empty($_POST['department_id'])) {
    $DepartmentId = $_POST['department_id'];
    $GetExpenseHistory .= " AND bills.department_id = $DepartmentId";
}

// Apply category filter if provided
if (!empty($_POST['category_name'])) {
    $CategoryName = mysqli_real_escape_string($mysqli, $_POST['category_name']); // Prevent SQL injection
    $GetExpenseHistory .= " AND category.CategoryName LIKE '%$CategoryName%'";
}

// Apply date range filter if provided
if (!empty($_POST['from_date']) && !empty($_POST['to_date'])) {
    $FromDate = $_POST['from_date'];
    $ToDate = $_POST['to_date'];
    $GetExpenseHistory .= " AND bills.Dates BETWEEN '$FromDate' AND '$ToDate'";
} elseif (!empty($_POST['from_date'])) {
    $FromDate = $_POST['from_date'];
    $GetExpenseHistory .= " AND bills.Dates >= '$FromDate'";
} elseif (!empty($_POST['to_date'])) {
    $ToDate = $_POST['to_date'];
    $GetExpenseHistory .= " AND bills.Dates <= '$ToDate'";
}

$GetExpenseHistory .= " ORDER BY bills.Dates DESC";

$ExpenseHistory = mysqli_query($mysqli, $GetExpenseHistory);

// Get total bills by month without UserId filter
$GetAllBillsDate = "SELECT SUM(Amount) AS Amount FROM bills WHERE MONTH(Dates) = MONTH(CURRENT_DATE()) AND UserId = $UserId";
$GetABillsDate = mysqli_query($mysqli, $GetAllBillsDate);
$BillsColDate = mysqli_fetch_assoc($GetABillsDate);

// Get total bills by today without UserId filter
$GetAllBillsToday = "SELECT SUM(Amount) AS Amount FROM bills WHERE Dates = CURRENT_DATE() AND UserId = $UserId";
$GetABillsDateToday = mysqli_query($mysqli, $GetAllBillsToday);
$BillsColDateToday = mysqli_fetch_assoc($GetABillsDateToday);

// Include Global page
include('includes/global.php');

?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?php echo $ExpenseReports; ?></h1>
        </div>
    </div>

    <a href="index.php?page=Transaction" class="btn white btn-success "><i class="fa fa-plus"></i> 
        New Expense</a>
    <a href="pages/ExpenseReportPdf.php?filter=<?php echo $SearchTerm; ?>" class="btn white btn-warning"><i 
            class="glyphicon glyphicon-download-alt"></i> <?php echo $DownloadExpenseReports; ?></a>
    <a href="pages/ExpenseReportCSV.php?filter=<?php echo $SearchTerm; ?>" class="btn white btn-warning"><i 
            class="glyphicon glyphicon-download-alt"></i> <?php echo $DownloadExpenseCSV; ?></a>

    <div class="row">
        <?php if ($msgBox) { echo $msgBox; } ?>

        <div class="col-lg-12">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <i class="glyphicon glyphicon-list-alt"></i> <?php echo $HistoryofExpense; ?>
                </div>

                <div class="panel-body">
                    <form action="" method="post">
                        <div class="form-group d-flex align-items-end gap-2 flex-wrap" style="display: flex; gap: 10px;">
                            <!-- Date Range Filters -->
                            <div class="d-flex flex-column">
                                <label for="from_date" class="fw-bold">From Date</label>
                                <input type="date" name="from_date" id="from_date" class="form-control">
                            </div>

                            <div class="d-flex flex-column">
                                <label for="to_date" class="fw-bold">To Date</label>
                                <input type="date" name="to_date" id="to_date" class="form-control">
                            </div>

                            <!-- Department Dropdown -->
                            <div class="d-flex flex-column">
                                <label for="department_id" class="fw-bold">Department</label>
                                <select name="department_id" id="department_id" class="form-control">
                                    <option value="">-- Select Department --</option>
                                    <?php while ($row = mysqli_fetch_assoc($Departments)) { ?>
                                        <option value="<?php echo $row['id']; ?>" 
                                            <?php echo ($row['id'] == $DepartmentId) ? 'selected' : ''; ?>>
                                            <?php echo $row['name']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- Category Input Filter -->
                            <div class="d-flex flex-column">
                                <label for="category_name" class="fw-bold">Head (Category)</label>
                                <input type="text" name="category_name" id="category_name" class="form-control" placeholder="Enter Category Name">
                            </div>

                            <div class="d-flex align-items-end">
                                <button class="btn btn-primary" name="searchbtn" type="submit" style="margin-top: 26px;">
                                    <i class="fa fa-search"></i>
                                </button>
                                <button class="btn btn-danger" type="reset" style="margin-top: 26px;" 
                                        onclick="window.location='http://localhost/money/index.php?page=AssetReport';">
                                    <i class="fa fa-times"></i> 
                                </button>
                            </div>
                        </div>
                    </form>

                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th class="text-left">Head</th>
                                <th class="text-left"><?php echo $Department; ?></th>
                                <th class="text-left"><?php echo $Amount; ?></th>
                                <th class="text-left"><?php echo $Date; ?></th>
                                <th class="text-left">File</th> 
                                <th class="text-left">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php 
                            $totalAmount = 0; // Variable to store total amount
                            while ($col = mysqli_fetch_assoc($ExpenseHistory)) { 
                                $totalAmount += $col['Amount']; // Add amount to total
                            ?>
                                <tr>
                                    <td><?php echo $col['CategoryName']; ?></td>
                                    <td><?php echo $col['DepartmentName']; ?></td>
                                    <td><?php echo $ColUser['Currency'] . ' ' . number_format($col['Amount']); ?></td>
                                    <td><?php echo date("M d Y", strtotime($col['Dates'])); ?></td>
                                    <td>
                                        <?php if (!empty($col['file_path'])) {
                                            $filePath = 'http://localhost/Money/file/expense/' . basename($col['file_path']);
                                            echo '<a href="' . $filePath . '" target="_blank"><img src="./picture.png" alt="PDF" title="View" width="24" height="24"></a>';
                                        } else {
                                            echo 'No file';
                                        } ?>
                                    </td>

                                    <td>
                                        <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                            <input type="hidden" name="BillsId" value="<?php echo $col['BillsId']; ?>" />
                                            <button type="submit" name="submitin" class="btn btn-danger btn-sm">
                                            <span class="glyphicon glyphicon-trash btn-xs"></span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right"><strong>Total</strong></td>
                                <td class="text-left"><strong><?php echo $ColUser['Currency'] . ' ' . number_format($totalAmount); ?></strong></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
