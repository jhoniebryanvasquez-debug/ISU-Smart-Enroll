<?php

session_start();

require_once "db.php";

/* =========================
   SECURITY CHECK
========================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Admin"
) {
    header("Location: admin_login.php");
    exit;
}


/* =========================
   FILTERS
========================= */

$reportType = $_GET["report"] ?? "summary";

$startDate = $_GET["start_date"] ?? "";

$endDate = $_GET["end_date"] ?? "";

$search = trim($_GET["search"] ?? "");


/* =========================
   VALID REPORT TYPES
========================= */

$allowedReports = [
    "summary",
    "users",
    "activities",
    "enrollments"
];

if (!in_array($reportType, $allowedReports, true)) {
    $reportType = "summary";
}


/* =========================
   ADMIN INFORMATION
========================= */

$adminName =
    $_SESSION["full_name"]
    ?? "Administrator";

$adminUsername =
    $_SESSION["username"]
    ?? "admin";


/* =========================
   DATA ARRAYS
========================= */

$users = [];

$activities = [];

$enrollments = [];


/* =========================
   USER REPORT
========================= */

if ($reportType === "users") {

    $sql = "
        SELECT
            id,
            username,
            full_name,
            email,
            role,
            status,
            created_at
        FROM users
        WHERE 1=1
    ";

    $params = [];

    $types = "";

    if ($search !== "") {

        $sql .= "
            AND (
                username LIKE ?
                OR full_name LIKE ?
                OR email LIKE ?
                OR role LIKE ?
                OR status LIKE ?
            )
        ";

        $searchValue = "%" . $search . "%";

        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;

        $types .= "sssss";
    }

    if ($startDate !== "") {

        $sql .= "
            AND DATE(created_at) >= ?
        ";

        $params[] = $startDate;

        $types .= "s";
    }

    if ($endDate !== "") {

        $sql .= "
            AND DATE(created_at) <= ?
        ";

        $params[] = $endDate;

        $types .= "s";
    }

    $sql .= "
        ORDER BY created_at DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        if (!empty($params)) {

            $stmt->bind_param(
                $types,
                ...$params
            );
        }

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $users[] = $row;
        }

        $stmt->close();
    }
}


/* =========================
   ACTIVITY REPORT
========================= */

if ($reportType === "activities") {

    $sql = "
        SELECT
            id,
            user_id,
            full_name,
            username,
            activity,
            activity_date,
            activity_time,
            ip_address
        FROM activity_logs
        WHERE 1=1
    ";

    $params = [];

    $types = "";

    if ($search !== "") {

        $sql .= "
            AND (
                full_name LIKE ?
                OR username LIKE ?
                OR activity LIKE ?
                OR ip_address LIKE ?
            )
        ";

        $searchValue = "%" . $search . "%";

        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;

        $types .= "ssss";
    }

    if ($startDate !== "") {

        $sql .= "
            AND activity_date >= ?
        ";

        $params[] = $startDate;

        $types .= "s";
    }

    if ($endDate !== "") {

        $sql .= "
            AND activity_date <= ?
        ";

        $params[] = $endDate;

        $types .= "s";
    }

    $sql .= "
        ORDER BY
            activity_date DESC,
            activity_time DESC,
            id DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        if (!empty($params)) {

            $stmt->bind_param(
                $types,
                ...$params
            );
        }

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $activities[] = $row;
        }

        $stmt->close();
    }
}


/* =========================
   ENROLLMENT REPORT
========================= */

if ($reportType === "enrollments") {

    $sql = "
        SELECT *
        FROM enrollments
        WHERE 1=1
    ";

    $params = [];

    $types = "";

    /*
       NOTE:
       Since the exact columns of the enrollments table
       may differ, the report safely retrieves all records.
    */

    if ($startDate !== "") {

        $sql .= "
            AND DATE(created_at) >= ?
        ";

        $params[] = $startDate;

        $types .= "s";
    }

    if ($endDate !== "") {

        $sql .= "
            AND DATE(created_at) <= ?
        ";

        $params[] = $endDate;

        $types .= "s";
    }

    $sql .= "
        ORDER BY id DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        if (!empty($params)) {

            $stmt->bind_param(
                $types,
                ...$params
            );
        }

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $enrollments[] = $row;
        }

        $stmt->close();
    }
}


/* =========================
   SUMMARY COUNTS
========================= */

$totalStudents = 0;

$totalEnrollments = 0;

$totalUsers = 0;

$activeUsers = 0;

$totalActivities = 0;


/* =========================
   TOTAL STUDENTS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM students
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalStudents = (int)$row["total"];
}


/* =========================
   TOTAL ENROLLMENTS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM enrollments
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalEnrollments = (int)$row["total"];
}


/* =========================
   TOTAL USERS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalUsers = (int)$row["total"];
}


/* =========================
   ACTIVE USERS
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE status = 'Active'
");

if ($result) {

    $row = $result->fetch_assoc();

    $activeUsers = (int)$row["total"];
}


/* =========================
   TOTAL ACTIVITIES
========================= */

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM activity_logs
");

if ($result) {

    $row = $result->fetch_assoc();

    $totalActivities = (int)$row["total"];
}


/* =========================
   REPORT TITLE
========================= */

$reportTitles = [

    "summary" =>
        "Summary Report",

    "users" =>
        "User Report",

    "activities" =>
        "Activity Report",

    "enrollments" =>
        "Enrollment Report"

];

$currentReportTitle =
    $reportTitles[$reportType]
    ?? "Summary Report";

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Reports | ISU SmartEnroll
</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


body {

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    color: #17221c;

    min-height: 100vh;

    background:

        linear-gradient(
            rgba(245,247,246,0.93),
            rgba(245,247,246,0.93)
        ),

        url("assets/background.jpg");

    background-size: cover;

    background-position: center;

    background-attachment: fixed;
}


a {
    text-decoration: none;
}


/* =========================
   HEADER
========================= */

.topbar {

    background:
        rgba(255,255,255,0.97);

    border-bottom:
        1px solid #dfe8e2;

    padding:
        14px 35px;

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    position: sticky;

    top: 0;

    z-index: 1000;

    backdrop-filter:
        blur(10px);
}


.brand {

    display: flex;

    align-items: center;

    gap: 13px;
}


.logos {

    display: flex;

    align-items: center;

    gap: 8px;
}


.logos img {

    width: 45px;

    height: 45px;

    object-fit: contain;
}


.logos .smart-logo {

    width: 50px;

    height: 50px;
}


.brand-text h1 {

    font-size: 20px;

    color: #006b3c;

    font-weight: 800;
}


.brand-text p {

    font-size: 12px;

    color: #69756e;

    margin-top: 2px;
}


.header-buttons {

    display: flex;

    gap: 9px;

    align-items: center;
}


.header-btn {

    padding:
        9px 14px;

    border:
        1px solid #006b3c;

    border-radius: 8px;

    color: #006b3c;

    background: white;

    font-size: 13px;

    font-weight: 700;

    transition: 0.2s;
}


.header-btn:hover {

    background: #006b3c;

    color: white;
}


/* =========================
   CONTAINER
========================= */

.container {

    width:
        min(
            1250px,
            calc(100% - 40px)
        );

    margin:
        35px auto 50px;
}


/* =========================
   TITLE
========================= */

.page-title {

    background:

        linear-gradient(
            135deg,
            rgba(0,107,60,0.96),
            rgba(0,78,44,0.94)
        );

    color: white;

    border-radius: 18px;

    padding:
        27px 30px;

    margin-bottom: 20px;

    box-shadow:
        0 12px 30px
        rgba(0,0,0,0.12);
}


.page-title h2 {

    font-size: 27px;

    margin-bottom: 7px;
}


.page-title p {

    font-size: 13px;

    opacity: 0.9;
}


/* =========================
   FILTER BOX
========================= */

.filter-box {

    background:
        rgba(255,255,255,0.97);

    border:
        1px solid #e1e8e3;

    border-radius: 15px;

    padding: 20px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.06);

    margin-bottom: 20px;
}


.filter-form {

    display: grid;

    grid-template-columns:
        1.2fr
        1fr
        1fr
        1fr
        auto;

    gap: 10px;

    align-items: end;
}


.form-group label {

    display: block;

    font-size: 12px;

    font-weight: 700;

    color: #56635b;

    margin-bottom: 6px;
}


.form-group input,
.form-group select {

    width: 100%;

    padding:
        10px 11px;

    border:
        1px solid #cbd5ce;

    border-radius: 8px;

    font-size: 13px;

    background: white;
}


.form-group input:focus,
.form-group select:focus {

    outline: none;

    border-color: #006b3c;

    box-shadow:
        0 0 0 3px
        rgba(0,107,60,0.08);
}


.filter-btn {

    border: none;

    background: #006b3c;

    color: white;

    padding:
        10px 17px;

    border-radius: 8px;

    font-weight: 700;

    cursor: pointer;

    font-size: 13px;
}


.filter-btn:hover {

    background: #00532f;
}


.clear-btn {

    display: inline-block;

    margin-top: 10px;

    color: #006b3c;

    font-size: 12px;

    font-weight: 700;
}


/* =========================
   SUMMARY CARDS
========================= */

.summary-grid {

    display: grid;

    grid-template-columns:
        repeat(
            5,
            minmax(0,1fr)
        );

    gap: 15px;

    margin-bottom: 20px;
}


.summary-card {

    background:
        rgba(255,255,255,0.97);

    border:
        1px solid #e2e9e4;

    border-radius: 14px;

    padding: 18px;

    box-shadow:
        0 7px 20px
        rgba(0,0,0,0.06);
}


.summary-card .label {

    color: #6b766f;

    font-size: 12px;

    margin-bottom: 5px;
}


.summary-card .number {

    color: #006b3c;

    font-size: 25px;

    font-weight: 800;
}


/* =========================
   REPORT HEADER
========================= */

.report-header {

    background:
        rgba(255,255,255,0.98);

    border:
        1px solid #dfe7e1;

    border-radius: 15px;

    padding: 22px;

    margin-bottom: 18px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.06);

    text-align: center;
}


.report-header h2 {

    color: #006b3c;

    font-size: 21px;

    margin-bottom: 3px;
}


.report-header h3 {

    font-size: 17px;

    margin-bottom: 5px;
}


.report-header p {

    color: #68736d;

    font-size: 12px;

    margin-top: 3px;
}


.print-row {

    display: flex;

    justify-content:
        flex-end;

    margin-bottom: 10px;
}


.print-btn {

    background: #006b3c;

    color: white;

    border: none;

    padding:
        10px 17px;

    border-radius: 8px;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;
}


.print-btn:hover {

    background: #00532f;
}


/* =========================
   TABLE
========================= */

.table-box {

    background:
        rgba(255,255,255,0.98);

    border:
        1px solid #e1e8e3;

    border-radius: 15px;

    padding: 20px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,0.06);

    overflow-x: auto;

    margin-bottom: 20px;
}


table {

    width: 100%;

    border-collapse:
        collapse;
}


th {

    text-align: left;

    background: #f1f7f3;

    color: #526057;

    padding:
        12px 10px;

    font-size: 12px;

    border-bottom:
        1px solid #dfe7e2;

    white-space: nowrap;
}


td {

    padding:
        12px 10px;

    font-size: 13px;

    color: #344139;

    border-bottom:
        1px solid #edf1ee;
}


tr:last-child td {

    border-bottom: none;
}


.status {

    display: inline-block;

    padding:
        5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;
}


.status.active {

    color: #006b3c;

    background: #e5f5ec;
}


.status.inactive {

    color: #a04400;

    background: #fff1e6;
}


.empty {

    text-align: center;

    padding: 35px;

    color: #777;

    font-size: 13px;
}


/* =========================
   ENROLLMENT INFO
========================= */

.enrollment-note {

    background: #f1f7f3;

    border-left:
        4px solid #006b3c;

    padding: 13px 15px;

    margin-top: 15px;

    color: #526057;

    font-size: 12px;

    border-radius: 6px;
}


/* =========================
   FOOTER
========================= */

.footer {

    text-align: center;

    color: #6d776f;

    font-size: 11px;

    padding: 15px;

}


/* =========================
   PRINT
========================= */

@media print {

    body {

        background: white;

        color: black;
    }


    .topbar,
    .filter-box,
    .print-row,
    .header-buttons {

        display: none !important;
    }


    .container {

        width: 100%;

        margin: 0;
    }


    .page-title {

        background: white;

        color: black;

        box-shadow: none;

        padding:
            10px 0;

        border-bottom:
            2px solid #000;
    }


    .summary-card,
    .report-header,
    .table-box {

        box-shadow: none;

        border:
            1px solid #ccc;
    }


    .summary-grid {

        grid-template-columns:
            repeat(5, 1fr);
    }


    .footer {

        display: none;
    }

}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1000px) {

    .filter-form {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .summary-grid {

        grid-template-columns:
            repeat(3, 1fr);
    }

}


@media (max-width: 700px) {

    .topbar {

        padding:
            13px 18px;
    }


    .brand-text {

        display: none;
    }


    .container {

        width:
            calc(100% - 24px);

        margin-top: 20px;
    }


    .filter-form {

        grid-template-columns: 1fr;
    }


    .summary-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .header-buttons {

        gap: 5px;
    }


    .header-btn {

        padding:
            8px 10px;

        font-size: 11px;
    }

}


@media (max-width: 450px) {

    .summary-grid {

        grid-template-columns: 1fr;
    }

}

</style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header class="topbar">


    <div class="brand">


        <div class="logos">

            <img
                src="assets/isu-logo.png"
                alt="ISU Logo"
            >

            <img
                src="assets/ccsict-logo.png"
                alt="CCSICT Logo"
            >

            <img
                src="assets/smart-enroll-logo.png"
                alt="SmartEnroll Logo"
                class="smart-logo"
            >

        </div>


        <div class="brand-text">

            <h1>
                ISU SmartEnroll
            </h1>

            <p>
                Isabela State University - Cauayan Campus
            </p>

        </div>


    </div>


    <div class="header-buttons">

        <a
            href="admin_dashboard.php"
            class="header-btn"
        >
            ← Dashboard
        </a>


        <a
            href="admin_dashboard.php?logout=1"
            class="header-btn"
            onclick="return confirm('Are you sure you want to logout?');"
        >
            Logout
        </a>

    </div>


</header>


<!-- =========================
     MAIN
========================= -->

<main class="container">


    <!-- PAGE TITLE -->

    <section class="page-title">

        <h2>
            Reports & Summaries
        </h2>

        <p>
            Generate, filter, and print system reports
            for enrollment, users, and activities.
        </p>

    </section>


    <!-- =========================
         FILTERS
    ========================= -->

    <div class="filter-box">


        <form
            method="GET"
            class="filter-form"
        >


            <div class="form-group">

                <label>
                    Report Type
                </label>

                <select name="report">

                    <option
                        value="summary"
                        <?= $reportType === "summary" ? "selected" : "" ?>
                    >
                        Summary Report
                    </option>

                    <option
                        value="users"
                        <?= $reportType === "users" ? "selected" : "" ?>
                    >
                        User Report
                    </option>

                    <option
                        value="activities"
                        <?= $reportType === "activities" ? "selected" : "" ?>
                    >
                        Activity Report
                    </option>

                    <option
                        value="enrollments"
                        <?= $reportType === "enrollments" ? "selected" : "" ?>
                    >
                        Enrollment Report
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Search
                </label>

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search..."
                >

            </div>


            <div class="form-group">

                <label>
                    Start Date
                </label>

                <input
                    type="date"
                    name="start_date"
                    value="<?= htmlspecialchars($startDate) ?>"
                >

            </div>


            <div class="form-group">

                <label>
                    End Date
                </label>

                <input
                    type="date"
                    name="end_date"
                    value="<?= htmlspecialchars($endDate) ?>"
                >

            </div>


            <button
                type="submit"
                class="filter-btn"
            >
                Generate
            </button>


        </form>


        <a
            href="reports.php"
            class="clear-btn"
        >
            Clear Filters
        </a>


    </div>


    <!-- =========================
         SUMMARY CARDS
    ========================= -->

    <div class="summary-grid">


        <div class="summary-card">

            <div class="label">
                Total Students
            </div>

            <div class="number">
                <?= number_format($totalStudents) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="label">
                Total Enrollments
            </div>

            <div class="number">
                <?= number_format($totalEnrollments) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="label">
                Total Users
            </div>

            <div class="number">
                <?= number_format($totalUsers) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="label">
                Active Users
            </div>

            <div class="number">
                <?= number_format($activeUsers) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="label">
                Activity Logs
            </div>

            <div class="number">
                <?= number_format($totalActivities) ?>
            </div>

        </div>


    </div>


    <!-- =========================
         REPORT HEADER
    ========================= -->

    <div class="report-header">

        <h2>
            ISABELA STATE UNIVERSITY
        </h2>

        <h3>
            Cauayan Campus
        </h3>

        <p>
            ISU SmartEnroll — <?= htmlspecialchars($currentReportTitle) ?>
        </p>

        <p>
            Generated:
            <?= date("F d, Y h:i A") ?>
        </p>

        <?php if ($startDate !== "" || $endDate !== ""): ?>

            <p>

                Coverage:

                <?= $startDate !== ""
                    ? date("M d, Y", strtotime($startDate))
                    : "Beginning"
                ?>

                -

                <?= $endDate !== ""
                    ? date("M d, Y", strtotime($endDate))
                    : "Present"
                ?>

            </p>

        <?php endif; ?>

    </div>


    <!-- PRINT BUTTON -->

    <div class="print-row">

        <button
            class="print-btn"
            onclick="window.print()"
        >
            🖨 Print Report
        </button>

    </div>


    <!-- =========================
         SUMMARY REPORT
    ========================= -->

    <?php if ($reportType === "summary"): ?>

        <div class="table-box">

            <table>

                <thead>

                    <tr>

                        <th>
                            Category
                        </th>

                        <th>
                            Total Records
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <td>
                            Students
                        </td>

                        <td>
                            <?= number_format($totalStudents) ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Enrollments
                        </td>

                        <td>
                            <?= number_format($totalEnrollments) ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Total Users
                        </td>

                        <td>
                            <?= number_format($totalUsers) ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Active Users
                        </td>

                        <td>
                            <?= number_format($activeUsers) ?>
                        </td>

                    </tr>


                    <tr>

                        <td>
                            Activity Logs
                        </td>

                        <td>
                            <?= number_format($totalActivities) ?>
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    <?php endif; ?>


    <!-- =========================
         USER REPORT
    ========================= -->

    <?php if ($reportType === "users"): ?>

        <div class="table-box">

            <?php if (count($users) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Username</th>

                            <th>Full Name</th>

                            <th>Email</th>

                            <th>Role</th>

                            <th>Status</th>

                            <th>Created</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($user["id"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user["username"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user["full_name"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user["email"] ?? "N/A") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user["role"]) ?>
                                </td>

                                <td>

                                    <span
                                        class="status <?= strtolower($user["status"]) === "active"
                                            ? "active"
                                            : "inactive" ?>"
                                    >

                                        <?= htmlspecialchars($user["status"]) ?>

                                    </span>

                                </td>

                                <td>

                                    <?php

                                    if (!empty($user["created_at"])) {

                                        echo htmlspecialchars(
                                            date(
                                                "M d, Y",
                                                strtotime($user["created_at"])
                                            )
                                        );

                                    } else {

                                        echo "N/A";

                                    }

                                    ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">

                    No user records found.

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         ACTIVITY REPORT
    ========================= -->

    <?php if ($reportType === "activities"): ?>

        <div class="table-box">

            <?php if (count($activities) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>User</th>

                            <th>Username</th>

                            <th>Activity</th>

                            <th>Date</th>

                            <th>Time</th>

                            <th>IP Address</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($activities as $activity): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $activity["full_name"] ?? "N/A"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $activity["username"] ?? "N/A"
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $activity["activity"]
                                    ) ?>
                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        date(
                                            "M d, Y",
                                            strtotime(
                                                $activity["activity_date"]
                                            )
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        date(
                                            "h:i A",
                                            strtotime(
                                                $activity["activity_time"]
                                            )
                                        )
                                    ) ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars(
                                        $activity["ip_address"]
                                        ?: "N/A"
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">

                    No activity records found
                    for the selected filters.

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         ENROLLMENT REPORT
    ========================= -->

    <?php if ($reportType === "enrollments"): ?>

        <div class="table-box">

            <?php if (count($enrollments) > 0): ?>

                <?php

                /*
                    Dynamically determine columns from
                    the existing enrollments table.
                */

                $columns = array_keys(
                    $enrollments[0]
                );

                ?>

                <table>

                    <thead>

                        <tr>

                            <?php foreach ($columns as $column): ?>

                                <th>

                                    <?= htmlspecialchars(
                                        ucwords(
                                            str_replace(
                                                "_",
                                                " ",
                                                $column
                                            )
                                        )
                                    ) ?>

                                </th>

                            <?php endforeach; ?>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($enrollments as $enrollment): ?>

                            <tr>

                                <?php foreach ($columns as $column): ?>

                                    <td>

                                        <?php

                                        $value =
                                            $enrollment[$column]
                                            ?? "";

                                        if (
                                            $value === null ||
                                            $value === ""
                                        ) {

                                            echo "N/A";

                                        } else {

                                            echo htmlspecialchars(
                                                (string)$value
                                            );

                                        }

                                        ?>

                                    </td>

                                <?php endforeach; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>


                <div class="enrollment-note">

                    Showing
                    <strong>
                        <?= number_format(count($enrollments)) ?>
                    </strong>
                    enrollment record(s).

                </div>

            <?php else: ?>

                <div class="empty">

                    No enrollment records found
                    for the selected filters.

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


</main>


<footer class="footer">

    ISU SmartEnroll —
    Isabela State University - Cauayan Campus

</footer>


</body>

</html>