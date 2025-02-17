<?php

// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');

// Delete category
if (isset($_POST['submitin'])) {
    $CategoryIds = $_POST['categoryid'];
    $Delete = "DELETE FROM category WHERE CategoryId = $CategoryIds";
    $DeleteI = mysqli_query($mysqli, $Delete);

    $msgBox = alertBox($DeleteCategory);
}

// Edit Category
if (isset($_POST['edit'])) {
    $CategoryIds = $_POST['categoryid'];
    $CategoryName = $_POST['categoryedit'];

    $sql = "UPDATE category SET CategoryName = ? WHERE CategoryId = $CategoryIds";
    if ($statement = $mysqli->prepare($sql)) {
        // Bind parameters for markers, where (s = string, i = integer, d = double, b = blob)
        $statement->bind_param('s', $CategoryName);
        $statement->execute();
    }
    $msgBox = alertBox($UpdateMsgCategory);
}

// Add new category
if (isset($_POST['submit'])) {

    // Sanitize inputs
    $category = $mysqli->real_escape_string($_POST["category"]);
    $department_id = $_POST['department_id'];  // Get the department_id
    $level = 2;

    // Check if department_id is selected
    if (empty($department_id)) {
        $msgBox = alertBox("Please select a department.");
    } else {
        // Add new category with department_id
        $sql = "INSERT INTO category (UserId, CategoryName, Level, department_id) VALUES (?,?,?,?)";
        if ($statement = $mysqli->prepare($sql)) {
            // Bind parameters for markers, where (s = string, i = integer)
            $statement->bind_param('isii', $UserId, $category, $level, $department_id);
            $statement->execute();
        }
        $msgBox = alertBox($SaveMsgCategory);
    }
}

// Get list of categories
$GetList = "
    SELECT category.CategoryId, category.CategoryName, department.name AS DepartmentName
    FROM category
    LEFT JOIN department ON category.department_id = department.id
    WHERE category.Level = 2 AND category.UserId = $UserId
    ORDER BY category.CategoryName ASC
";
$GetListCategory = mysqli_query($mysqli, $GetList);


// Search category
if (isset($_POST['searchbtn'])) {
    $SearchTerm = $_POST['search'];
    $GetList = "SELECT CategoryId, CategoryName FROM category WHERE Level = 2 AND UserId = $UserId  AND CategoryName
                like '%$SearchTerm%' ORDER BY CategoryName ASC";
    $GetListCategory = mysqli_query($mysqli, $GetList);
}

// Get departments from the database
$departmentQuery = "SELECT id, name FROM department"; // Assuming the table is named 'departments' and it has 'id' and 'name' columns
$departmentResult = mysqli_query($mysqli, $departmentQuery);

// Include Global page
include('includes/global.php');
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Expense Head </h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <?php if ($msgBox) {
        echo $msgBox;
    } ?>
    <a href="#new" class="btn white btn-success" data-toggle="modal"><i class="fa fa-plus"></i>
        Add Expense Head</a>
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-red">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart-o fa-fw"></i> List of Expense
                </div>
                <div class="panel-body">
                    <div class="pull-right">
                        <form action="" method="post">
                            <div class="form-group input-group col-lg-5 pull-right">
                                <input type="text" name="search" placeholder="<?php echo $Search; ?>"
                                    class="form-control">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" name="searchbtn" type="input"><i
                                            class="fa fa-search"></i></button>
                                </span>
                            </div>
                        </form>
                    </div>
                    <div class="">
                        <table class="table table-striped table-bordered table-hover" id="assetsdata">
                            <thead>
                                <tr>
                                    <th class="text-left"><?php echo $Category; ?></th>
                                    <th class="text-left"><?php echo $Department; ?></th>
                                    <!-- Added column for Department -->
                                    <th class="text-left"><?php echo $Action; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($col = mysqli_fetch_assoc($GetListCategory)) { ?>
                                    <tr>
                                        <td><?php echo $col['CategoryName']; ?></td>
                                        <td><?php echo $col['DepartmentName']; ?></td> <!-- Displaying department name -->
                                        <td colspan="2" class="notification">
                                            <a href="#EditCat<?php echo $col['CategoryId']; ?>" data-toggle="modal">
                                                <span class="btn btn-primary btn-xs glyphicon glyphicon-edit"
                                                    data-toggle="tooltip" data-placement="left" title=""
                                                    data-original-title="<?php echo $EditCategory; ?>"></span>
                                            </a>
                                            <a href="#DeleteCat<?php echo $col['CategoryId']; ?>" data-toggle="modal">
                                                <span class="glyphicon glyphicon-trash btn btn-primary btn-xs"
                                                    data-toggle="tooltip" data-placement="right" title=""
                                                    data-original-title="<?php echo $DeleteCategories; ?>"></span>
                                            </a>
                                        </td>
                                    </tr>
                                    <div class="modal fade" id="DeleteCat<?php echo $col['CategoryId']; ?>" tabindex="-1"
                                        role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="" method="post">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-hidden="true">&times;</button>
                                                        <h4 class="modal-title" id="myModalLabel"><?php echo $AreYouSure; ?>
                                                        </h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php echo $ThisItem; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <input type="hidden" id="categoryid" name="categoryid"
                                                            value="<?php echo $col['CategoryId']; ?>" />
                                                        <button type="input" id="submit" name="submitin"
                                                            class="btn btn-primary"><?php echo $Yes; ?></button>
                                                        <button type="button" class="btn btn-default"
                                                            data-dismiss="modal"><?php echo $Cancel; ?></button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /.modal -->
                                    <!-- /.edit category -->
                                    <div class="modal fade" id="EditCat<?php echo $col['CategoryId']; ?>" tabindex="-1"
                                        role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="" method="post">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-hidden="true">&times;</button>
                                                        <h4 class="modal-title" id="myModalLabel">
                                                            <?php echo $EditCategory; ?>
                                                        </h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label for="category"><?php echo $Category; ?></label>
                                                            <input class="form-control" required name="categoryedit"
                                                                value="<?php echo $col['CategoryName']; ?>" type="text"
                                                                autofocus>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <input type="hidden" id="categoryid" name="categoryid"
                                                            value="<?php echo $col['CategoryId']; ?>" />
                                                        <button type="input" id="submit" name="edit"
                                                            class="btn btn-primary"><?php echo $Yes; ?></button>
                                                        <button type="button" class="btn btn-default"
                                                            data-dismiss="modal"><?php echo $Cancel; ?></button>
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

</div>

<!-- /.col-lg-4 -->
</div>
<!-- /.row -->

</div>
<!-- /#page-wrapper -->

<!-- New Category Modal -->
<div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Add New Head</h4>
                </div>
                <div class="modal-body">


                    <div class="form-group">
                        <label for="department"><?php echo $Department; ?></label>
                        <select class="form-control" name="department_id" required>
                            <option value="" disabled selected><?php echo $SelectDepartment; ?></option>
                            <?php while ($row = mysqli_fetch_assoc($departmentResult)) { ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <!-- Category Input Field -->
                    <div class="form-group">
                        <label for="category">Head</label>
                        <input class="form-control" required placeholder="Expense Head" name="category"
                            type="text" autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="submit" class="btn btn-success"><span
                            class=""></span><?php echo $Save; ?></button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $Cancel; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('.notification').tooltip({
            selector: "[data-toggle=tooltip]",
            container: "body"
        });
    });
</script>