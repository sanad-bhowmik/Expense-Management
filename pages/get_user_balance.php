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

if (isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // Updated query to select from the user_balance table
    $query = "SELECT balance FROM user_balance WHERE user_id = $user_id ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($mysqli, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        echo $row['balance'];
    } else {
        echo "0.00"; // Default if no balance found
    }
}
?>
