<?php
// get_users.php

// Database connection using your provided credentials
$dbuser = "root";
$dbpassword = "";
$dbname = "money";
$dbhost = "localhost";

// Create a new connection to the database
$connection = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

// Check if the connection was successful
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if (isset($_GET['department_id'])) {
    $departmentId = $_GET['department_id'];

    // Query to fetch users based on department_id
    $query = "SELECT UserId, FirstName FROM department_user WHERE department_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Output the user options
        echo '<option value="">-- Select User --</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['UserId'] . '">' . $row['FirstName'] . '</option>';
        }
    } else {
        echo '<option value="">No users found</option>';
    }
} else {
    echo '<option value="">-- Select User --</option>';
}

// Close the database connection
$connection->close();
?>
<?php
// get_users.php

// Database connection using your provided credentials
$dbuser = "root";
$dbpassword = "";
$dbname = "money";
$dbhost = "localhost";

// Create a new connection to the database
$connection = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

// Check if the connection was successful
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if (isset($_GET['department_id'])) {
    $departmentId = $_GET['department_id'];

    // Query to fetch users based on department_id
    $query = "SELECT UserId, FirstName FROM department_user WHERE department_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('i', $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Output the user options
        echo '<option value="">-- Select User --</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['UserId'] . '">' . $row['FirstName'] . '</option>';
        }
    } else {
        echo '<option value="">No users found</option>';
    }
} else {
    echo '<option value="">-- Select User --</option>';
}

// Close the database connection
$connection->close();
?>
