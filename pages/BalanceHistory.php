<?php
// Include Functions
include('includes/Functions.php');

// Include Notifications
include('includes/notification.php');

// Default query condition: Show only current date's data by default
$currentDate = date('Y-m-d'); // Get the current date
$searchQuery = " WHERE ubh.date = '$currentDate' "; // Default filter for current date

// Process the filters when the form is submitted
if (isset($_POST['searchbtn'])) {

    // Reset the default query condition if filters are applied
    $searchQuery = " WHERE 1=1 "; // Reset to a condition that will always be true

    // Filter by User Name (using dropdown)
    if (!empty($_POST['user_name'])) {
        $userName = $mysqli->real_escape_string($_POST['user_name']);
        $searchQuery .= " AND ubh.user_id = '$userName' ";
    }

    // Filter by From Date and To Date
    if (!empty($_POST['fromDate']) && !empty($_POST['toDate'])) {
        $fromDate = $mysqli->real_escape_string($_POST['fromDate']);
        $toDate = $mysqli->real_escape_string($_POST['toDate']);
        $searchQuery .= " AND ubh.date BETWEEN '$fromDate' AND '$toDate' ";
    } else if (!empty($_POST['fromDate'])) {
        $fromDate = $mysqli->real_escape_string($_POST['fromDate']);
        $searchQuery .= " AND ubh.date >= '$fromDate' ";
    } else if (!empty($_POST['toDate'])) {
        $toDate = $mysqli->real_escape_string($_POST['toDate']);
        $searchQuery .= " AND ubh.date <= '$toDate' ";
    }
}

// Get Balance History with the filters applied and include department name
$GetBalanceHistory = "
    SELECT ubh.id, ubh.user_id, ubh.balance, ubh.add_by, ubh.status, ubh.date,
           u.FirstName AS add_by_first_name, u.LastName AS add_by_last_name,
           du.FirstName AS user_first_name, du.LastName AS user_last_name,
           d.name AS department_name
    FROM user_balance_history ubh
    LEFT JOIN user u ON ubh.add_by = u.UserId
    LEFT JOIN department_user du ON ubh.user_id = du.UserId
    LEFT JOIN department d ON du.department_id = d.id
    $searchQuery
    ORDER BY du.created_at DESC  -- Order by 'created_at' in descending order
";

$BalanceHistory = mysqli_query($mysqli, $GetBalanceHistory);


// Include Global page
include('includes/global.php');
?>

<div id="page-wrapper">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">User Balance History</h1>
        </div>
    </div>

    <!-- Display Notifications -->
    <?php if ($msgBox) {
        echo $msgBox;
    } ?>

    <div class="col-lg-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-stats"></i> History of Balance
            </div>
            <div class="panel-body">
                <div class="d-flex justify-content-end mb-3" style="display: flex;margin-bottom: 5vh;gap:10px;">
                    <form action="" method="post" style="display: flex;gap: 10px;">
                        <!-- User Name Filter (Dropdown) -->
                        <div class="mr-2">
                            <label for="user_name" class="form-label">Search by Name</label>
                            <select name="user_name" id="user_name" class="form-control">
                                <option value="">Select User</option>
                                <?php
                                // Get list of users for the dropdown
                                $getUsers = "SELECT UserId, FirstName, LastName FROM department_user";
                                $usersResult = mysqli_query($mysqli, $getUsers);
                                while ($user = mysqli_fetch_assoc($usersResult)) {
                                    echo "<option value='{$user['UserId']}'" . (isset($_POST['user_name']) && $_POST['user_name'] == $user['UserId'] ? ' selected' : '') . ">{$user['FirstName']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- From Date Filter (Default to today's date) -->
                        <div class="mr-2">
                            <label for="fromDate" class="form-label">From Date</label>
                            <input type="date" id="fromDate" name="fromDate" class="form-control"
                                value="<?php echo isset($_POST['fromDate']) ? $_POST['fromDate'] : date('Y-m-d'); ?>" />
                        </div>

                        <!-- To Date Filter (Default to today's date) -->
                        <div class="mr-2">
                            <label for="toDate" class="form-label">To Date</label>
                            <input type="date" id="toDate" name="toDate" class="form-control"
                                value="<?php echo isset($_POST['toDate']) ? $_POST['toDate'] : date('Y-m-d'); ?>" />
                        </div>

                        <div style="margin-top: 26px;">
                            <!-- Search Button -->
                            <button class="btn btn-primary" id="filterBtn" name="searchbtn" type="submit">
                                <i class="fa fa-search"></i>
                            </button>
                            <!-- Clear Button -->
                            <button id="clearBtn" class="btn btn-danger ml-2" type="button" onclick="clearFilters()">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <table class="table table-bordered table-hover table-striped" id="assetsdata">
                    <thead>
                        <tr>
                            <th class="text-left"><?php echo "SL"; ?></th>
                            <th class="text-left"><?php echo "User Name"; ?></th>
                            <th class="text-left"><?php echo "Department"; ?></th>
                            <th class="text-left"><?php echo "Added By"; ?></th>
                            <th class="text-left"><?php echo "Amount"; ?></th>
                            <th class="text-left"><?php echo "Date"; ?></th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $index = 1; // Initialize index to 1 for the first row
                        while ($col = mysqli_fetch_assoc($BalanceHistory)) { ?>
                            <tr>
                                <td><?php echo $index++; ?></td>
                                <td><?php echo $col['user_first_name']; // Only display First Name ?></td>
                                <td><?php echo $col['department_name']; // Display Department Name ?></td>
                                <td><?php echo $col['add_by_first_name']; ?></td>
                                <td><?php echo number_format($col['balance'], 2); ?></td>
                                <td><?php echo date("M d, Y", strtotime($col['date'])); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function clearFilters() {
        // Clear all filter inputs
        document.querySelector('select[name="user_name"]').value = '';
        document.querySelector('input[name="fromDate"]').value = '';
        document.querySelector('input[name="toDate"]').value = '';
        document.querySelector('form').submit(); // Resubmit the form to clear the search
    }
</script>