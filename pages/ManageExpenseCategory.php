<?php

// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');
// Get departments from the database
$departmentQuery = "SELECT id, name FROM department"; // Assuming the table is named 'departments' and it has 'id' and 'name' columns
$departmentResult = mysqli_query($mysqli, $departmentQuery);

// Fetch all categories for the dropdown
$categoryQuery = "SELECT CategoryId, CategoryName FROM category";
$categoryResult = mysqli_query($mysqli, $categoryQuery);

// Prepare filter values
$searchTerm = isset($_POST['search']) ? $_POST['search'] : '';
$departmentId = isset($_POST['department_id']) ? $_POST['department_id'] : '';
$categoryId = isset($_POST['category_id']) ? $_POST['category_id'] : ''; // For filtering by category

// Build the SQL query with filters
$whereClauses = ["category.Level = 2", "category.UserId = $UserId"];

if (!empty($searchTerm)) {
    $whereClauses[] = "category.CategoryName LIKE '%$searchTerm%'";
}

if (!empty($departmentId)) {
    $whereClauses[] = "category.department_id = '$departmentId'";
}

if (!empty($categoryId)) {
    $whereClauses[] = "category.CategoryId = '$categoryId'"; // Filter by selected category
}

$whereSql = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : "";

// Get list of filtered categories
$GetList = "
    SELECT category.CategoryId, category.CategoryName, department.name AS DepartmentName
    FROM category
    LEFT JOIN department ON category.department_id = department.id
    $whereSql
    ORDER BY category.CategoryName ASC
";
$GetListCategory = mysqli_query($mysqli, $GetList);

// Include Global page
include('includes/global.php');
// Handle New Category Submission
if (isset($_POST['submit'])) {
    session_start(); // Start session if not already started
    $categoryName = $_POST['category'];
    $departmentId = $_POST['department_id'];
    $userId = $_SESSION['UserId']; // Get UserId from session
    $level = 2; // Set Level to 2

    $sql = "INSERT INTO category (CategoryName, department_id, UserId, Level) VALUES (?, ?, ?, ?)";
    if ($statement = $mysqli->prepare($sql)) {
        $statement->bind_param('siii', $categoryName, $departmentId, $userId, $level);
        if ($statement->execute()) {
            echo "<script>
                // alert('Category added successfully!');
                window.location.href = 'http://localhost/money/index.php?page=ManageExpenseCategory';
            </script>";
        } else {
            echo "<script>alert('Error adding category!');</script>";
        }
        $statement->close();
    }
}


// Handle category edit update
if (isset($_POST['edit'])) {
    $CategoryIds = $_POST['categoryid'];
    $CategoryName = $_POST['categoryedit'];

    $sql = "UPDATE category SET CategoryName = ? WHERE CategoryId = ?";
    if ($statement = $mysqli->prepare($sql)) {
        $statement->bind_param('si', $CategoryName, $CategoryIds);
        if ($statement->execute()) {
            $msgBox = "<div class='alert alert-success'>Head updated successfully.</div>";
            echo "<script type='text/javascript'>
                    window.location.href = 'http://localhost/money/index.php?page=ManageExpenseCategory';
                  </script>";
        } else {
            $msgBox = "<div class='alert alert-danger'>Error updating category.</div>";
        }
        $statement->close();
    }
}

?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Expense Head</h1>
        </div>
    </div>

    <?php if (isset($msgBox))
        echo $msgBox; ?>

    <a href="#new" class="btn btn-success" data-toggle="modal"><i class="fa fa-plus"></i> Add Expense Head</a>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart-o fa-fw"></i> List of Expense Heads
                </div>
                <div class="panel-body">

                    <!-- Search & Filter Form -->
                    <form action="" method="post" style="margin-bottom: 24px;">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="department_id">Filter by Department:</label>
                                <select class="form-control" name="department_id" onchange="this.form.submit();">
                                    <option value="">All Departments</option>
                                    <?php
                                    $departmentResult = mysqli_query($mysqli, $departmentQuery);
                                    while ($row = mysqli_fetch_assoc($departmentResult)) { ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo ($departmentId == $row['id']) ? 'selected' : ''; ?>>
                                            <?php echo $row['name']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="category_id">Filter by Category:</label>
                                <select class="form-control" name="category_id" onchange="this.form.submit();">
                                    <option value="">Select Category</option>
                                    <?php
                                    while ($row = mysqli_fetch_assoc($categoryResult)) { ?>
                                        <option value="<?php echo $row['CategoryId']; ?>" <?php echo ($categoryId == $row['CategoryId']) ? 'selected' : ''; ?>>
                                            <?php echo $row['CategoryName']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-btn">

                                        <button id="clearBtn" class="btn btn-danger ml-2" type="button"
                                            onclick="clearFilters()" style="margin-top: 25px;margin-left: -20px;">
                                            <i class="fa fa-times"></i>
                                        </button>

                                        <script>
                                            function clearFilters() {
                                                window.location.href = "http://36.50.40.147:9099/Twillon/index.php?page=ManageExpenseCategory";
                                            }
                                        </script>

                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Head</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($col = mysqli_fetch_assoc($GetListCategory)) { ?>
                                <tr>
                                    <td><?php echo $col['CategoryName']; ?></td>
                                    <td><?php echo $col['DepartmentName']; ?></td>
                                    <td>
                                        <a href="#EditCat<?php echo $col['CategoryId']; ?>" data-toggle="modal">
                                            <button class="btn btn-primary btn-xs"><i class="fa fa-edit"></i></button>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Edit Category Modal -->
                                <div class="modal fade" id="EditCat<?php echo $col['CategoryId']; ?>" tabindex="-1"
                                    role="dialog">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="" method="post">
                                                <div class="modal-header">
                                                    <button type="button" class="close"
                                                        data-dismiss="modal">&times;</button>
                                                    <h4 class="modal-title">Edit Expense Head</h4>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="categoryedit">Category Name</label>
                                                        <input type="text" class="form-control" name="categoryedit"
                                                            value="<?php echo $col['CategoryName']; ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <input type="hidden" name="categoryid"
                                                        value="<?php echo $col['CategoryId']; ?>">
                                                    <button type="submit" name="edit" class="btn btn-primary">Save</button>
                                                    <button type="button" class="btn btn-default"
                                                        data-dismiss="modal">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="new" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add New Expense Head</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="department_id">Select Department</label>
                        <select class="form-control" name="department_id" required>
                            <option value="" disabled selected>Select Department</option>
                            <?php
                            $departmentResult = mysqli_query($mysqli, $departmentQuery);
                            while ($row = mysqli_fetch_assoc($departmentResult)) { ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">Expense Head</label>
                        <input type="text" class="form-control" name="category" placeholder="Enter expense head"
                            required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>