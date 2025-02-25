<?php

//Include Functions
include('includes/Functions.php');

//Include Notifications
include('includes/notification.php');

//delete category
if (isset($_POST['submitin'])) {
    $CategoryIds = $_POST['categoryid'];
    $Delete = "DELETE FROM category WHERE CategoryId = $CategoryIds";
    $DeleteI = mysqli_query($mysqli, $Delete);

    $msgBox = alertBox($DeleteCategory);
}

//Edit Category
if (isset($_POST['edit'])) {
    $CategoryIds = $_POST['categoryid'];
    $CategoryName = $_POST['categoryedit'];

    $sql = "UPDATE category SET CategoryName = ? WHERE CategoryId = $CategoryIds";
    if ($statement = $mysqli->prepare($sql)) {
        $statement->bind_param('s', $CategoryName);
        $statement->execute();
    }
    $msgBox = alertBox($UpdateMsgCategory);
}

// add new category
if (isset($_POST['submit'])) {

    $category = $mysqli->real_escape_string($_POST["category"]);
    $department_id = $_POST['department_id'];  // Get the department_id from the form
    $level = 1;

    // Add new category with department_id
    $sql = "INSERT INTO category (UserId, CategoryName, Level, department_id) VALUES (?,?,?,?)";
    if ($statement = $mysqli->prepare($sql)) {
        $statement->bind_param('isii', $UserId, $category, $level, $department_id);
        $statement->execute();
    }

    $msgBox = alertBox($SaveMsgCategory);
}

// Get departments from the database
$departmentQuery = "SELECT id, name FROM department"; // Assuming the table is named 'departments' and it has 'id' and 'name' columns
$departmentResult = mysqli_query($mysqli, $departmentQuery);

// Filter form
$departmentId = isset($_POST['department_id']) ? $_POST['department_id'] : '';
$categoryId = isset($_POST['category_id']) ? $_POST['category_id'] : '';

// Build the SQL query with filters
$whereClauses = ["c.Level = 1", "c.UserId = $UserId"];

if (!empty($departmentId)) {
    $whereClauses[] = "c.department_id = $departmentId";
}

if (!empty($categoryId)) {
    $whereClauses[] = "c.CategoryId = $categoryId";
}

$whereSql = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : "";

// Get filtered list of categories
$GetList = "
    SELECT c.CategoryId, c.CategoryName, d.name AS DepartmentName
    FROM category c
    LEFT JOIN department d ON c.department_id = d.id
    $whereSql
    ORDER BY c.CategoryName ASC
";
$GetListCategory = mysqli_query($mysqli, $GetList);
$departmentQuery = "SELECT id, name FROM department";
$departmentResult = mysqli_query($mysqli, $departmentQuery);
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Income Head</h1>
        </div>
    </div>
    <?php if ($msgBox) {
        echo $msgBox;
    } ?>

    <a href="#new" class="btn white btn-success" data-toggle="modal"><i class="fa fa-plus"></i> Add Income Head</a>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart-o fa-fw"></i> Income Head List
                </div>
                <div class="panel-body">
                    <!-- Filter Form -->
                    <div class="">
                        <form action="" method="post">
                            <div class="form-group input-group col-lg-5 pull-right" style="display: flex;">
                                <!-- Department Dropdown Filter -->
                                <select class="form-control" name="department_id" onchange="this.form.submit();">
                                    <option value="">Select Department</option>
                                    <?php while ($row = mysqli_fetch_assoc($departmentResult)) { ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $row['id']) ? 'selected' : ''; ?>>
                                            <?php echo $row['name']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <!-- Category Dropdown Filter -->
                                <select class="form-control" name="category_id" onchange="this.form.submit();">
                                    <option value="">Select Income Head</option>
                                    <?php
                                    // Fetch the categories based on the department filter
                                    if (isset($_POST['department_id']) && !empty($_POST['department_id'])) {
                                        $department_id = $_POST['department_id'];
                                        $categoryQuery = "SELECT CategoryId, CategoryName FROM category WHERE department_id = $department_id";
                                    } else {
                                        $categoryQuery = "SELECT CategoryId, CategoryName FROM category";
                                    }

                                    $categoryResult = mysqli_query($mysqli, $categoryQuery);
                                    while ($category = mysqli_fetch_assoc($categoryResult)) { ?>
                                        <option value="<?php echo $category['CategoryId']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['CategoryId']) ? 'selected' : ''; ?>>
                                            <?php echo $category['CategoryName']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <button class="btn btn-primary" name="searchbtn" type="submit"><i
                                        class="fa fa-search"></i></button>
                                <button id="clearBtn" class="btn btn-danger ml-2" type="button"
                                    onclick="clearFilters()">
                                    <i class="fa fa-times"></i>
                                </button>

                                <script>
                                    function clearFilters() {
                                        window.location.href = "http://36.50.40.147:9099/Twillon/index.php?page=ManageIncomeCategory";
                                    }
                                </script>
                            </div>
                        </form>
                    </div>
                    <!-- Table for Displaying Categories -->
                    <div class="">
                        <table class="table table-striped table-bordered table-hover" id="assetsdata">
                            <thead>
                                <tr>
                                    <th class="text-left"><?php echo $Category; ?></th>
                                    <th class="text-left"><?php echo $Department; ?></th>
                                    <th class="text-left"><?php echo $Action; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($col = mysqli_fetch_assoc($GetListCategory)) { ?>
                                    <tr>
                                        <td><?php echo $col['CategoryName']; ?></td>
                                        <td><?php echo $col['DepartmentName']; ?></td>
                                        <td colspan="2" class="notification">
                                            <!-- Edit & Delete Buttons -->
                                            <a href="#EditCat<?php echo $col['CategoryId']; ?>" data-toggle="modal">
                                                <span class="btn btn-primary btn-xs glyphicon glyphicon-edit"></span>
                                            </a>
                                            <a href="#DeleteCat<?php echo $col['CategoryId']; ?>" data-toggle="modal">
                                                <span class="glyphicon glyphicon-trash btn btn-primary btn-xs"></span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add Income Head -->
<div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Add Income Head</h4>
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
                        <label for="category">Income Head</label>
                        <input class="form-control" required placeholder="Income Head" name="category" type="text"
                            autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="submit" class="btn btn-success"><?php echo $Save; ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $Cancel; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Delete Category -->
<?php while ($col = mysqli_fetch_assoc($GetListCategory)) { ?>
    <div class="modal fade" id="DeleteCat<?php echo $col['CategoryId']; ?>" tabindex="-1" role="dialog"
        aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="myModalLabel"><?php echo $AreYouSure; ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo $ThisItem; ?>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" id="categoryid" name="categoryid" value="<?php echo $col['CategoryId']; ?>" />
                        <button type="input" id="submit" name="submitin"
                            class="btn btn-primary"><?php echo $Yes; ?></button>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $Cancel; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<script>
    $(function () {
        $('.notification').tooltip({
            selector: "[data-toggle=tooltip]",
            container: "body"
        });
    });
</script>