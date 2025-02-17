<?php

error_reporting(0);
ini_set('display_errors', '0');

// Database Connection
$dbuser = "root";	
$dbpassword = ""; 	
$dbname = "money"; 	
$dbhost = "localhost";	

$mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Change character set to utf8
$mysqli->set_charset('utf8');

if (isset($_POST['department_id'])) {
    $department_id = $_POST['department_id'];

    $query = "SELECT CategoryId, CategoryName FROM category WHERE department_id = '$department_id' AND Level = 2";
    $result = $mysqli->query($query);

    if ($result) {
        echo '<option value="">-- Select Head --</option>';
        while ($row = $result->fetch_assoc()) {
            echo "<option value='" . $row['CategoryId'] . "'>" . $row['CategoryName'] . "</option>";
        }
    } else {
        echo '<option value="">No Head found</option>';
    }
}

// Close connection
$mysqli->close();
?>
