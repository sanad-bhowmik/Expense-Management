<?php

// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');

// Delete category
if (isset($_POST['delete_department'])) {
    $DepartmentId = $_POST['departmentid'];
    $Delete = "DELETE FROM department WHERE id = $DepartmentId";
    $DeleteI = mysqli_query($mysqli, $Delete);

    $msgBox = alertBox($DeleteDepartment);
}

// Edit Category
if (isset($_POST['edit'])) {
    $DepartmentId = $_POST['departmentid'];
    $DepartmentName = $_POST['departmentedit'];

    $sql = "UPDATE department SET name = ? WHERE id = ?";
    if ($statement = $mysqli->prepare($sql)) {
        // Bind parameters (s = string, i = integer)
        $statement->bind_param('si', $DepartmentName, $DepartmentId);
        $statement->execute();
    }

    $msgBox = alertBox("Department updated successfully!");
}

// Add new category
if (isset($_POST['submit'])) {
    // Sanitize and get form data
    $department_name = $mysqli->real_escape_string($_POST["department_name"]);
    $status = 1; // Set status to 1 (active)
    $added_by = $_SESSION['auth_name']; // Assuming 'auth_name' holds the authenticated user's name

    // Prepare and execute the SQL query to insert the new department
    $sql = "INSERT INTO department (name, status, add_by, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('sis', $department_name, $status, $added_by); // 's' for string, 'i' for integer
        $stmt->execute();

        // Set success message
        $msgBox = alertBox("Department has been saved successfully"); // Assuming this function shows a success message
    } else {
        // Handle error (optional)
        $msgBox = alertBox("Error: Unable to add department.", "error");
    }
}

// Get list category (modified to check search filter)
$GetDepartments = "SELECT id, name FROM department WHERE status = 1 ORDER BY name ASC";
$GetDepartmentList = mysqli_query($mysqli, $GetDepartments);

// Search category (if search term is provided)
if (isset($_POST['searchbtn'])) {
    $SearchTerm = $mysqli->real_escape_string($_POST['search']); // Prevent SQL injection
    $GetDepartments = "SELECT id, name FROM department WHERE status = 1 AND name LIKE '%$SearchTerm%' ORDER BY name ASC";
    $GetDepartmentList = mysqli_query($mysqli, $GetDepartments);
}


// Include Global page
include('includes/global.php');

?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header"><?php echo $NewDepartment; ?> </h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->

    <?php if ($msgBox) {
        echo $msgBox;
    } ?>

    <a href="#new" class="btn white btn-success" data-toggle="modal"><i class="fa fa-plus"></i> Add New Department</a>

    <div class="row">
        <div class="col-lg-12">
            <!-- /.panel -->
            <div class="panel panel-red">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart-o fa-fw"></i> List Department
                </div>
                <div class="panel-body">
                    <div class="pull-right">
                        <form action="" method="post">
                            <div class="form-group input-group col-lg-5 pull-right">
                                <input type="text" name="search" placeholder="<?php echo $Search; ?>"
                                    class="form-control">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" name="searchbtn" type="submit"><i
                                            class="fa fa-search"></i></button>
                                    <button class="btn btn-danger" type="button" onclick="clearSearch()">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </span>
                                <script>
                                    function clearSearch() {
                                        document.querySelector('input[name="search"]').value = '';
                                        document.querySelector('form').submit(); // Resubmit the form to clear the search
                                    }
                                </script>
                            </div>
                        </form>
                    </div>

                    <table class="table table-striped table-bordered table-hover" id="assetsdata">
                        <thead>
                            <tr>
                                <th class="text-left">Department</th>
                                <th class="text-left"><?php echo $Action; ?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while ($col = mysqli_fetch_assoc($GetDepartmentList)) { ?>
                                <tr>
                                    <td><?php echo $col['name']; ?></td>
                                    <td colspan="2" class="notification">
                                        <a href="#EditDept<?php echo $col['id']; ?>" class="" data-toggle="modal">
                                            <span class="btn btn-primary btn-xs glyphicon glyphicon-edit"
                                                data-toggle="tooltip" data-placement="left" title="Edit Department"></span>
                                        </a>
                                        <!-- <a href="#DeleteDept<?php echo $col['id']; ?>" data-toggle="modal">
                                            <span class="glyphicon glyphicon-trash btn btn-primary btn-xs"
                                                data-toggle="tooltip" data-placement="right"
                                                title="Delete Department"></span>
                                        </a> -->
                                    </td>
                                </tr>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="DeleteDept<?php echo $col['id']; ?>" tabindex="-1" role="dialog"
                                    aria-labelledby="myModalLabel" aria-hidden="true">
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
                                                    <input type="hidden" name="departmentid"
                                                        value="<?php echo $col['id']; ?>" />
                                                    <button type="submit" name="delete_department"
                                                        class="btn btn-primary"><?php echo $Yes; ?></button>
                                                    <button type="button" class="btn btn-default"
                                                        data-dismiss="modal"><?php echo $Cancel; ?></button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="EditDept<?php echo $col['id']; ?>" tabindex="-1" role="dialog"
                                    aria-labelledby="myModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="" method="post">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-hidden="true">&times;</button>
                                                    <h4 class="modal-title" id="myModalLabel">Edit Department</h4>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="department_name">Department Name</label>
                                                        <input class="form-control" required name="departmentedit"
                                                            value="<?php echo $col['name']; ?>" type="text" autofocus>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <input type="hidden" name="departmentid"
                                                        value="<?php echo $col['id']; ?>" />
                                                    <button type="submit" name="edit" class="btn btn-primary">Save
                                                        Changes</button>
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
<!-- /.col-lg-4 -->

<!-- Add New Department Modal -->
<div class="modal fade" id="new" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">Add New Department</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="department_name">New Department</label>
                        <input class="form-control" required placeholder="New Department" name="department_name"
                            type="text" autofocus>
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

<!-- Include Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<script>
    $(function () {
        $('.notification').tooltip({
            selector: "[data-toggle=tooltip]",
            container: "body"
        });
    });
</script>