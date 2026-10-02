<?php

/*
|--------------------------------------------------------------------------
| ISU SmartEnroll - Activity Logger
|--------------------------------------------------------------------------
| Centralized activity/audit logging for the system.
|--------------------------------------------------------------------------
*/

function logActivity($conn, $activity)
{
    // Make sure database connection exists
    if (!$conn || !($conn instanceof mysqli)) {
        return false;
    }

    // Make sure user is logged in
    if (!isset($_SESSION["user_id"])) {
        return false;
    }

    // Get session information
    $user_id = (int) $_SESSION["user_id"];
    $full_name = trim($_SESSION["full_name"] ?? "");
    $username = trim($_SESSION["username"] ?? "");

    // Activity information
    $activity = trim($activity);

    if ($activity === "") {
        return false;
    }

    // Prevent excessively long activity descriptions
    $activity = mb_substr($activity, 0, 255);

    // Date and time
    $activity_date = date("Y-m-d");
    $activity_time = date("H:i:s");

    // Get IP address
    $ip_address = $_SERVER["REMOTE_ADDR"] ?? null;

    // Limit IP address to the database field size
    if ($ip_address !== null) {
        $ip_address = mb_substr($ip_address, 0, 45);
    }

    /*
    |--------------------------------------------------------------------------
    | Insert Activity Log
    |--------------------------------------------------------------------------
    */

    $sql = "
        INSERT INTO activity_logs
        (
            user_id,
            full_name,
            username,
            activity,
            activity_date,
            activity_time,
            ip_address
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "issssss",
        $user_id,
        $full_name,
        $username,
        $activity,
        $activity_date,
        $activity_time,
        $ip_address
    );

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}

?>