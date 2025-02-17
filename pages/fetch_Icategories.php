<?php

// Include your manual database connection
error_reporting(0);
ini_set('display_errors', '0');

// Connection Database
$dbuser = "root";    
$dbpassword = "";     
$dbname = "money";    
$dbhost = "localhost";  

// Connect
$mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);
if (mysqli_connect_errno()) {
    printf("MySQLi connection failed: %s\n", mysqli_connect_error());
    exit();
}

// Change character set to utf8
if (!$mysqli->set_charset('utf8')) {
    printf('Error loading character set utf8: %s\n', $mysqli->error);
}

// Get the department_id from the URL
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';

if ($department_id) {
    // Prepare and execute the query to fetch categories based on the department_id
    $query = "SELECT CategoryId, CategoryName FROM category WHERE department_id = ?";
    $stmt = $mysqli->prepare($query);
    
    // Bind parameters and execute
    $stmt->bind_param('i', $department_id);
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();

    // Store categories in an array
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    // Return the categories as JSON
    echo json_encode($categories);
} else {
    echo json_encode([]);
}

// Close the connection
$mysqli->close();

?>
