<?php
$dbuser = "root";
$dbpassword = "";
$dbname = "money";
$dbhost = "localhost";

$connection = mysqli_connect($dbhost, $dbuser, $dbpassword, $dbname);

if (!$connection) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = (int) $_POST["userId"];
    $status = (int) $_POST["status"];

    $query = "UPDATE department_user SET status = ? WHERE UserId = ?";
    if ($stmt = mysqli_prepare($connection, $query)) {
        mysqli_stmt_bind_param($stmt, "ii", $status, $userId);
        if (mysqli_stmt_execute($stmt)) {
            echo "success";
        } else {
            echo "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "error";
    }
}
mysqli_close($connection);
?>