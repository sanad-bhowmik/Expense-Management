<?php
include('db.php');  // Include database connection

// Check if user_id is set
if (isset($_GET['user_id'])) {
    $user_id = (int) $_GET['user_id'];  // Get the user ID

    // Query to get the balance of the selected user
    $sql = "SELECT balance FROM user_balance WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";  // Fetch the most recent balance
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->bind_result($balance);
        $stmt->fetch();
        $stmt->close();

        if (isset($balance)) {
            echo number_format($balance, 2);  // Output the balance with 2 decimal places
        } else {
            echo "error";  // No balance found
        }
    } else {
        echo "error";  // Error with the query
    }
}
?>
