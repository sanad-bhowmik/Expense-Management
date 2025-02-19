<?php
// Manually adding the connection
$dbuser = "root";           // Your database username
$dbpassword = "";           // Your database password
$dbname = "money";          // Your database name
$dbhost = "localhost";      // Your database host (usually localhost)

$mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (isset($_POST['user_id'])) {
    $userId = (int) $_POST['user_id'];

    // Query to fetch the current balance and the department name for the selected user
    $query = "SELECT ub.balance, du.department_id, d.name AS department_name
              FROM user_balance ub
              JOIN department_user du ON ub.user_id = du.UserId
              JOIN department d ON du.department_id = d.id
              WHERE ub.user_id = ? 
              ORDER BY ub.created_at DESC LIMIT 1";

    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($balance, $departmentId, $departmentName);
        $stmt->fetch();
        $stmt->close();

        if ($balance !== null && $departmentName !== null) {
            // Output as single line (Balance and Department separated by a slash)
            echo "Balance: " . number_format($balance, 2) . " Tk / Department: " . ($departmentName ? $departmentName : "No department found");
        } else {
            // Output plain text if no balance or department found
            echo "Balance: No balance found for this user. / Department: No department found";
        }
    } else {
        // Output plain text error if the query fails
        echo "Balance: Error fetching data. / Department: Error fetching data.";
    }
} else {
    // Output plain text error if 'user_id' is not provided in the POST request
    echo "Balance: User ID not provided. / Department: User ID not provided.";
}

// Close connection
$mysqli->close();
?>
