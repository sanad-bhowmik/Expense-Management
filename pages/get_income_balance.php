<?php
// Manual Database Connection
$dbuser = "root";
$dbpassword = "";
$dbname = "money";
$dbhost = "localhost";

// Create connection
$mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
// Get the user_id from the GET request
if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];

    // Query to get the user balance from the user_balance table
    $query = "SELECT balance FROM user_balance WHERE user_id = '$userId' LIMIT 1";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo $row['balance'];
    } else {
        echo '0'; // Return 0 if no balance found
    }
}
?>