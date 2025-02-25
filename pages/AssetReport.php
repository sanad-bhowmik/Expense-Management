<?php

// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');
// Check if delete button was pressed
if (isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id']; // Get the ID to be deleted

    // Perform the deletion query
    $DeleteIncomeQuery = "DELETE FROM assets WHERE AssetsId = $deleteId";
    $deleteResult = mysqli_query($mysqli, $DeleteIncomeQuery);

    if ($deleteResult) {
        // Record deleted successfully
        // echo "<script>alert('Income record deleted successfully');</script>";
    } else {
        // Handle error
        echo "<script>alert('Error deleting record: " . mysqli_error($mysqli) . "');</script>";
    }
}

$SearchTerm = '';
$DepartmentId = ''; // Default empty value for department
$CategorySearch = ''; // Default empty value for category search (Head)
$FromDate = '';
$ToDate = '';

if (isset($_POST['searchbtn'])) {
    $SearchTerm = isset($_POST['search']) ? $_POST['search'] : '';
    $FromDate = isset($_POST['from_date']) ? $_POST['from_date'] : '';
    $ToDate = isset($_POST['to_date']) ? $_POST['to_date'] : '';
    $DepartmentId = isset($_POST['department_id']) ? $_POST['department_id'] : '';
    $CategorySearch = isset($_POST['category_search']) ? $_POST['category_search'] : '';

    // Debugging using var_dump
    var_dump($SearchTerm, $FromDate, $ToDate, $DepartmentId, $CategorySearch); // Print the values of these variables

    // Filters
    $dateFilter = "";
    $DepartmentFilter = "";
    $CategoryFilter = "";

    if (!empty($FromDate) && !empty($ToDate)) {
        $dateFilter = " AND assets.Date BETWEEN '$FromDate' AND '$ToDate' ";
    } elseif (!empty($FromDate)) {
        $dateFilter = " AND assets.Date >= '$FromDate' ";
    } elseif (!empty($ToDate)) {
        $dateFilter = " AND assets.Date <= '$ToDate' ";
    }

    if (!empty($DepartmentId)) {
        $DepartmentFilter = " AND assets.department_id = $DepartmentId";
    }

    if (!empty($CategorySearch)) {
        $CategoryFilter = " AND category.CategoryName LIKE '%$CategorySearch%'";
    }

    // Query with all applied filters
    $GetIncomeHistory = "SELECT assets.*, 
                         category.CategoryName, 
                         account.AccountName, 
                         department.name AS DepartmentName,
                         user.FirstName 
                         FROM assets 
                         LEFT JOIN category ON assets.CategoryId = category.CategoryId 
                         LEFT JOIN account ON assets.AccountId = account.AccountId
                         LEFT JOIN department ON assets.department_id = department.id
                         LEFT JOIN user ON assets.UserId = user.UserId
                         AND (assets.Title LIKE '%$SearchTerm%' 
                         OR account.AccountName LIKE '%$SearchTerm%'
                         OR assets.Description LIKE '%$SearchTerm%') 
                         $dateFilter
                         $DepartmentFilter
                         $CategoryFilter
                         ORDER BY assets.Date DESC";

    $IncomeHistory = mysqli_query($mysqli, $GetIncomeHistory);

} else {
    // Default query (No filters applied)
    $GetIncomeHistory = "SELECT assets.*, 
                         category.CategoryName, 
                         account.AccountName, 
                         department.name AS DepartmentName,
                         user.FirstName 
                         FROM assets 
                         LEFT JOIN category ON assets.CategoryId = category.CategoryId 
                         LEFT JOIN account ON assets.AccountId = account.AccountId
                         LEFT JOIN department ON assets.department_id = department.id
                         LEFT JOIN user ON assets.UserId = user.UserId
                         ORDER BY assets.Date DESC";

    $IncomeHistory = mysqli_query($mysqli, $GetIncomeHistory);
}

// Get Department List for dropdown
$GetDepartments = "SELECT id, name FROM department";
$Departments = mysqli_query($mysqli, $GetDepartments);

include('includes/global.php');

?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Incomes</h1>
        </div>
    </div>
    <a href="index.php?page=addIncome" class="btn white btn-success "><i class="fa fa-plus"></i> New Income</a>
    <a href="pages/ExpenseReportPdf.php?filter=<?php echo $SearchTerm; ?>" class="btn white btn-warning"><i class="glyphicon glyphicon-download-alt"></i> <?php echo $DownloadExpenseReports; ?></a>
    <a href="pages/ExpenseReportCSV.php?filter=<?php echo $SearchTerm; ?>" class="btn white btn-warning"><i class="glyphicon glyphicon-download-alt"></i> <?php echo $DownloadExpenseCSV; ?></a>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="glyphicon glyphicon-stats"></i> History of Income
                </div>
                <div class="panel-body">
                    <form action="" method="post">
                        <div class="form-group d-flex align-items-end gap-2 flex-wrap" style="display: flex; gap: 10px; margin-bottom: 35px;">
                            <div class="d-flex flex-column">
                                <label for="from_date" class="fw-bold">From Date</label>
                                <input type="date" name="from_date" id="from_date" class="form-control" value="<?php echo $FromDate; ?>">
                            </div>

                            <div class="d-flex flex-column">
                                <label for="to_date" class="fw-bold">To Date</label>
                                <input type="date" name="to_date" id="to_date" class="form-control" value="<?php echo $ToDate; ?>">
                            </div>

                            <div class="d-flex flex-column">
                                <label for="category_search" class="fw-bold">Search by Head (Category)</label>
                                <input type="text" name="category_search" id="category_search" class="form-control" placeholder="Enter category name" value="<?php echo $CategorySearch; ?>">
                            </div>

                            <div class="d-flex flex-column">
                                <label for="department_id" class="fw-bold">Department</label>
                                <select name="department_id" id="department_id" class="form-control">
                                    <option value="">-- Select Department --</option>
                                    <?php while ($department = mysqli_fetch_assoc($Departments)) { ?>
                                        <option value="<?php echo $department['id']; ?>" <?php echo ($department['id'] == $DepartmentId) ? 'selected' : ''; ?>><?php echo $department['name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="d-flex align-items-end">
                                <button class="btn btn-primary" name="searchbtn" type="submit" style="margin-top: 26px;">
                                    <i class="fa fa-search"></i>
                                </button>
                                <button class="btn btn-danger" type="reset" style="margin-top: 26px;" onclick="window.location='http://localhost/money/index.php?page=AssetReport';">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <script>
                        document.querySelector("button[type='reset']").addEventListener("click", function () {
                            document.querySelector("#from_date").value = "";
                            document.querySelector("#to_date").value = "";
                            document.querySelector("#category_search").value = "";
                            document.querySelector("#department_id").value = "";
                        });
                    </script>

                    <table class="table table-bordered table-hover table-striped" id="assetsdata">
                        <thead>
                            <tr>
                                <th class="text-left">Head</th>
                                <th class="text-left">Department</th>
                                <th class="text-left">Amount</th>
                                <th class="text-left">Date</th>
                                <th class="text-left">File</th>
                                <th class="text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalAmount = 0;
                            while ($col = mysqli_fetch_assoc($IncomeHistory)) { 
                                $totalAmount += $col['Amount'];
                            ?>
                                <tr>
                                    <td><?php echo $col['CategoryName']; ?></td>
                                    <td><?php echo $col['DepartmentName']; ?></td>
                                    <td><?php echo number_format($col['Amount']); ?></td>
                                    <td><?php echo date("M d Y", strtotime($col['Date'])); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($col['file_path'])) {
                                            $baseDir = 'C:/xampp/htdocs/Money/';
                                            $relativeFilePath = str_replace($baseDir, '', $col['file_path']);
                                            $filePath = 'http://localhost/Money/' . $relativeFilePath;

                                            echo '<a href="' . $filePath . '" target="_blank">
                                                     <img src="./picture.png" alt="View File" title="View" width="24" height="24">
                                                  </a>';
                                        } else {
                                            echo 'No file';
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <form action="" method="POST" onsubmit="return confirmDelete()">
                                            <input type="hidden" name="delete_id" value="<?php echo $col['AssetsId']; ?>">
                                            <button type="submit" class="glyphicon glyphicon-trash btn btn-primary btn-xs">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right"><strong>Total Amount</strong></td>
                                <td colspan="4" class="text-left"><strong><?php echo number_format($totalAmount); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete() {
        return confirm("Are you sure you want to delete this income record?");
    }
</script>
