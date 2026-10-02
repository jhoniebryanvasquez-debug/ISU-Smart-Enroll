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
   LOGOUT
========================= */

if (isset($_GET["logout"])) {

    session_destroy();

    header("Location: admin_login.php");
    exit;
}


/* =========================
   COUNT FUNCTION
========================= */

function getCount($conn, $table)
{
    $allowedTables = [
        "students",
        "enrollments",
        "users",
        "activity_logs"
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS total FROM `$table`";

    $result = $conn->query($sql);

    if ($result) {
        $row = $result->fetch_assoc();
        return (int)$row["total"];
    }

    return 0;
}


/* =========================
   BASIC COUNTS
========================= */

$totalStudents    = getCount($conn, "students");
$totalEnrollments = getCount($conn, "enrollments");
$totalUsers       = getCount($conn, "users");
$totalActivities  = getCount($conn, "activity_logs");


/* =========================
   ACTIVE USERS
========================= */

$activeUsers = 0;

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
   TODAY'S ACTIVITY
========================= */

$dailyActivity = 0;

$today = date("Y-m-d");

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM activity_logs
    WHERE activity_date = ?
");

if ($stmt) {

    $stmt->bind_param("s", $today);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        $row = $result->fetch_assoc();

        $dailyActivity = (int)$row["total"];
    }

    $stmt->close();
}


/* =========================
   MONTHLY ACTIVITY
   LAST 6 MONTHS
========================= */

$monthlyLabels = [];
$monthlyActivity = [];

for ($i = 5; $i >= 0; $i--) {

    $monthDate = date(
        "Y-m-01",
        strtotime("-" . $i . " months")
    );

    $monthLabel = date(
        "M Y",
        strtotime($monthDate)
    );

    $monthlyLabels[] = $monthLabel;

    $monthlyActivity[$monthLabel] = 0;
}


$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(activity_date, '%b %Y') AS month_label,
        COUNT(*) AS total
    FROM activity_logs
    WHERE activity_date >= DATE_FORMAT(
        DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
        '%Y-%m-01'
    )
    GROUP BY
        YEAR(activity_date),
        MONTH(activity_date)
    ORDER BY
        YEAR(activity_date),
        MONTH(activity_date)
");

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $label = $row["month_label"];

        if (isset($monthlyActivity[$label])) {

            $monthlyActivity[$label] =
                (int)$row["total"];
        }
    }

    $stmt->close();
}

$monthlyValues = array_values(
    $monthlyActivity
);


/* =========================
   DAILY ACTIVITY
   LAST 7 DAYS
========================= */

$dailyLabels = [];
$dailyValues = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date(
        "Y-m-d",
        strtotime("-" . $i . " days")
    );

    $label = date(
        "M d",
        strtotime($date)
    );

    $dailyLabels[] = $label;

    $dailyValues[$date] = 0;
}


$stmt = $conn->prepare("
    SELECT
        activity_date,
        COUNT(*) AS total
    FROM activity_logs
    WHERE activity_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY activity_date
    ORDER BY activity_date
");

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $date = $row["activity_date"];

        if (isset($dailyValues[$date])) {

            $dailyValues[$date] =
                (int)$row["total"];
        }
    }

    $stmt->close();
}

$dailyChartValues = array_values(
    $dailyValues
);


/* =========================
   USER ACTIVITY SUMMARY
========================= */

$userActivityLabels = [];
$userActivityValues = [];

$stmt = $conn->prepare("
    SELECT
        username,
        COUNT(*) AS total
    FROM activity_logs
    WHERE username IS NOT NULL
      AND username != ''
    GROUP BY username
    ORDER BY total DESC
    LIMIT 5
");

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $userActivityLabels[] =
            $row["username"];

        $userActivityValues[] =
            (int)$row["total"];
    }

    $stmt->close();
}


/* =========================
   RECENT ACTIVITIES
========================= */

$recentActivities = [];

$result = $conn->query("
    SELECT
        full_name,
        username,
        activity,
        activity_date,
        activity_time
    FROM activity_logs
    ORDER BY
        activity_date DESC,
        activity_time DESC,
        id DESC
    LIMIT 8
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $recentActivities[] = $row;
    }
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
        Admin Dashboard | ISU SmartEnroll
    </title>


    <!-- DESIGN SYSTEM -->

    <link
        rel="stylesheet"
        href="css/design-system.css"
    >


    <!-- CHART.JS -->

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js"
    ></script>


    <style>

        /* =====================================================
           DASHBOARD-SPECIFIC DESIGN
           ===================================================== */

        body.dashboard-page {

            background:
                linear-gradient(
                    rgba(245, 248, 246, 0.94),
                    rgba(245, 248, 246, 0.94)
                ),
                url("assets/background.jpg");

            background-size: cover;
            background-position: center;
            background-attachment: fixed;

        }


        /* =====================================================
           HEADER
           ===================================================== */

        .dashboard-header {

            position: sticky;

            top: 0;

            z-index: 1000;

            background:
                rgba(255, 255, 255, 0.94);

            border-bottom:
                1px solid rgba(0, 107, 60, 0.10);

            backdrop-filter:
                blur(16px);

            -webkit-backdrop-filter:
                blur(16px);

        }


        .dashboard-header-inner {

            width:
                min(
                    1320px,
                    calc(100% - 40px)
                );

            min-height: 76px;

            margin: 0 auto;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

        }


        .dashboard-brand {

            display: flex;

            align-items: center;

            gap: 13px;

            min-width: 0;

        }


        .dashboard-logos {

            display: flex;

            align-items: center;

            gap: 7px;

            flex-shrink: 0;

        }


        .dashboard-logos img {

            width: 42px;

            height: 42px;

            object-fit: contain;

        }


        .dashboard-logos img.smart-logo {

            width: 48px;

            height: 48px;

        }


        .dashboard-brand-text {

            min-width: 0;

        }


        .dashboard-brand-title {

            font-size: 18px;

            font-weight: 800;

            line-height: 1.2;

            color: #006b3c;

        }


        .dashboard-brand-subtitle {

            margin-top: 3px;

            font-size: 11px;

            color: #6c7771;

            white-space: nowrap;

        }


        /* =====================================================
           ADMIN PROFILE
           ===================================================== */

        .dashboard-profile {

            display: flex;

            align-items: center;

            gap: 12px;

            flex-shrink: 0;

        }


        .dashboard-profile-avatar {

            width: 40px;

            height: 40px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #006b3c,
                    #008c50
                );

            color: white;

            font-weight: 800;

            font-size: 15px;

            box-shadow:
                0 5px 14px
                rgba(0, 107, 60, 0.20);

        }


        .dashboard-profile-info {

            text-align: right;

        }


        .dashboard-profile-name {

            font-size: 13px;

            font-weight: 800;

            color: #17221c;

        }


        .dashboard-profile-role {

            font-size: 11px;

            color: #748078;

            margin-top: 2px;

        }


        .dashboard-logout {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 38px;

            padding: 0 15px;

            border-radius: 9px;

            background: #006b3c;

            color: #ffffff;

            font-size: 12px;

            font-weight: 800;

            transition:
                transform 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;

        }


        .dashboard-logout:hover {

            background: #00532f;

            transform:
                translateY(-1px);

            box-shadow:
                0 7px 15px
                rgba(0, 107, 60, 0.18);

        }


        /* =====================================================
           PAGE CONTAINER
           ===================================================== */

        .dashboard-container {

            width:
                min(
                    1320px,
                    calc(100% - 40px)
                );

            margin:
                28px auto 45px;

        }


        /* =====================================================
           WELCOME HERO
           ===================================================== */

        .dashboard-hero {

            position: relative;

            overflow: hidden;

            border-radius: 20px;

            padding:
                30px 32px;

            background:
                linear-gradient(
                    135deg,
                    #006b3c 0%,
                    #00532f 65%,
                    #003e25 100%
                );

            color: white;

            box-shadow:
                0 14px 32px
                rgba(0, 76, 43, 0.18);

        }


        .dashboard-hero::before {

            content: "";

            position: absolute;

            width: 260px;

            height: 260px;

            border-radius: 50%;

            right: -95px;

            top: -125px;

            background:
                rgba(255,255,255,0.08);

        }


        .dashboard-hero::after {

            content: "";

            position: absolute;

            width: 150px;

            height: 150px;

            border-radius: 50%;

            right: 150px;

            bottom: -100px;

            background:
                rgba(255,255,255,0.05);

        }


        .dashboard-hero-content {

            position: relative;

            z-index: 2;

        }


        .dashboard-hero-eyebrow {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: 0.4px;

            opacity: 0.78;

            margin-bottom: 8px;

        }


        .dashboard-hero-eyebrow::before {

            content: "";

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: #a9e8c6;

            box-shadow:
                0 0 0 4px
                rgba(169,232,198,0.12);

        }


        .dashboard-hero h1 {

            font-size: 28px;

            line-height: 1.2;

            font-weight: 800;

            margin: 0;

        }


        .dashboard-hero p {

            margin-top: 8px;

            max-width: 720px;

            font-size: 13px;

            line-height: 1.65;

            color:
                rgba(255,255,255,0.84);

        }


        /* =====================================================
           STATISTICS
           ===================================================== */

        .dashboard-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 16px;

            margin-top: 18px;

        }


        .dashboard-stat {

            position: relative;

            overflow: hidden;

            background:
                rgba(255,255,255,0.97);

            border:
                1px solid #e3ebe6;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 6px 22px
                rgba(24, 53, 38, 0.06);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;

        }


        .dashboard-stat:hover {

            transform:
                translateY(-3px);

            border-color:
                #c4ded0;

            box-shadow:
                0 12px 28px
                rgba(24, 53, 38, 0.10);

        }


        .dashboard-stat-top {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 10px;

        }


        .dashboard-stat-icon {

            width: 44px;

            height: 44px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                #e9f6ef;

            color:
                #006b3c;

            font-size: 20px;

        }


        .dashboard-stat-label {

            margin-top: 17px;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.55px;

            color: #768179;

        }


        .dashboard-stat-value {

            margin-top: 3px;

            font-size: 29px;

            line-height: 1.15;

            font-weight: 850;

            color: #17221c;

        }


        .dashboard-stat-accent {

            position: absolute;

            width: 70px;

            height: 70px;

            right: -28px;

            bottom: -35px;

            border-radius: 50%;

            background:
                rgba(0,107,60,0.035);

        }


        /* =====================================================
           SECTION HEADER
           ===================================================== */

        .dashboard-section {

            margin-top: 31px;

        }


        .dashboard-section-header {

            display: flex;

            align-items: flex-end;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 13px;

        }


        .dashboard-section-title {

            font-size: 18px;

            font-weight: 800;

            color: #17221c;

        }


        .dashboard-section-subtitle {

            margin-top: 3px;

            font-size: 11px;

            color: #78837c;

        }


        .dashboard-section-link {

            color: #006b3c;

            font-size: 11px;

            font-weight: 800;

        }


        /* =====================================================
           ANALYTICS
           ===================================================== */

        .dashboard-analytics {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 16px;

        }


        .dashboard-chart-card {

            min-height: 340px;

            background:
                rgba(255,255,255,0.97);

            border:
                1px solid #e3ebe6;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 6px 22px
                rgba(24, 53, 38, 0.06);

        }


        .dashboard-chart-head {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 12px;

        }


        .dashboard-chart-title {

            font-size: 14px;

            font-weight: 800;

            color: #17221c;

        }


        .dashboard-chart-description {

            margin-top: 4px;

            font-size: 11px;

            line-height: 1.5;

            color: #7a857e;

        }


        .dashboard-chart-badge {

            flex-shrink: 0;

            padding: 5px 8px;

            border-radius: 7px;

            background: #edf7f1;

            color: #006b3c;

            font-size: 10px;

            font-weight: 800;

        }


        .dashboard-chart-container {

            position: relative;

            width: 100%;

            height: 255px;

        }


        /* =====================================================
           MANAGEMENT CARDS
           ===================================================== */

        .dashboard-management {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );

            gap: 15px;

        }


        .dashboard-management-card {

            position: relative;

            min-height: 174px;

            display: flex;

            flex-direction: column;

            justify-content: space-between;

            overflow: hidden;

            padding: 20px;

            background:
                rgba(255,255,255,0.97);

            border:
                1px solid #e3ebe6;

            border-radius: 16px;

            box-shadow:
                0 6px 22px
                rgba(24, 53, 38, 0.06);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;

        }


        .dashboard-management-card:hover {

            transform:
                translateY(-3px);

            border-color:
                #b9d8c7;

            box-shadow:
                0 12px 28px
                rgba(24, 53, 38, 0.10);

        }


        .dashboard-management-card::after {

            content: "";

            position: absolute;

            width: 90px;

            height: 90px;

            border-radius: 50%;

            right: -42px;

            bottom: -42px;

            background:
                rgba(0,107,60,0.035);

        }


        .dashboard-management-icon {

            width: 44px;

            height: 44px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                #e9f6ef;

            color:
                #006b3c;

            font-size: 19px;

        }


        .dashboard-management-title {

            margin-top: 13px;

            font-size: 14px;

            font-weight: 800;

            color: #17221c;

        }


        .dashboard-management-description {

            margin-top: 5px;

            font-size: 11px;

            line-height: 1.55;

            color: #748078;

            max-width: 330px;

        }


        .dashboard-management-action {

            margin-top: 13px;

            color: #006b3c;

            font-size: 11px;

            font-weight: 800;

        }


        /* =====================================================
           ACTIVITY TABLE
           ===================================================== */

        .dashboard-panel {

            background:
                rgba(255,255,255,0.97);

            border:
                1px solid #e3ebe6;

            border-radius: 16px;

            box-shadow:
                0 6px 22px
                rgba(24, 53, 38, 0.06);

            overflow: hidden;

        }


        .dashboard-panel-header {

            padding:
                18px 20px;

            border-bottom:
                1px solid #edf2ee;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .dashboard-panel-title {

            font-size: 14px;

            font-weight: 800;

            color: #17221c;

        }


        .dashboard-panel-subtitle {

            margin-top: 3px;

            font-size: 11px;

            color: #7b857f;

        }


        .dashboard-table-wrap {

            overflow-x: auto;

        }


        .dashboard-table {

            width: 100%;

            border-collapse: collapse;

        }


        .dashboard-table th {

            padding:
                11px 16px;

            background:
                #f7faf8;

            border-bottom:
                1px solid #e6ede8;

            text-align: left;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 0.45px;

            color: #68746d;

            white-space: nowrap;

        }


        .dashboard-table td {

            padding:
                13px 16px;

            border-bottom:
                1px solid #eef2ef;

            font-size: 12px;

            color: #36423b;

            vertical-align: middle;

        }


        .dashboard-table tbody tr {

            transition:
                background 0.15s ease;

        }


        .dashboard-table tbody tr:hover {

            background:
                #f9fbfa;

        }


        .dashboard-table tbody tr:last-child td {

            border-bottom: none;

        }


        .dashboard-user {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 175px;

        }


        .dashboard-user-avatar {

            width: 31px;

            height: 31px;

            flex-shrink: 0;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                #e9f6ef;

            color:
                #006b3c;

            font-size: 11px;

            font-weight: 800;

        }


        .dashboard-user-name {

            font-weight: 800;

            color: #17221c;

        }


        .dashboard-user-username {

            margin-top: 2px;

            font-size: 10px;

            color: #859088;

        }


        .dashboard-activity {

            color: #455149;

        }


        .dashboard-date {

            white-space: nowrap;

            color: #69756e;

        }


        .dashboard-time {

            white-space: nowrap;

            color: #69756e;

            font-size: 11px;

        }


        .dashboard-empty {

            text-align: center;

            padding: 35px 20px;

            color: #7a857e;

            font-size: 12px;

        }


        /* =====================================================
           SYSTEM STATUS
           ===================================================== */

        .dashboard-status-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0,1fr)
                );

            gap: 13px;

        }


        .dashboard-status-card {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 15px;

            border:
                1px solid #e3ebe6;

            background:
                rgba(255,255,255,0.97);

            border-radius: 13px;

            box-shadow:
                0 5px 18px
                rgba(24, 53, 38, 0.045);

        }


        .dashboard-status-icon {

            width: 38px;

            height: 38px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background:
                #e9f6ef;

            color:
                #006b3c;

            font-size: 15px;

        }


        .dashboard-status-label {

            font-size: 10px;

            color: #7b857f;

        }


        .dashboard-status-value {

            margin-top: 3px;

            font-size: 12px;

            font-weight: 800;

            color: #17221c;

        }


        .dashboard-online {

            color:
                #00864b;

        }


        /* =====================================================
           FOOTER
           ===================================================== */

        .dashboard-footer {

            text-align: center;

            padding:
                26px 10px 10px;

            color: #7a857e;

            font-size: 10px;

        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1050px) {

            .dashboard-stats {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0,1fr)
                    );

            }


            .dashboard-management {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0,1fr)
                    );

            }


            .dashboard-status-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0,1fr)
                    );

            }

        }


        @media (max-width: 850px) {

            .dashboard-analytics {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 700px) {

            .dashboard-header-inner {

                width:
                    calc(100% - 24px);

                min-height: 68px;

            }


            .dashboard-brand-subtitle,
            .dashboard-profile-info {

                display: none;

            }


            .dashboard-brand-title {

                font-size: 16px;

            }


            .dashboard-logos img {

                width: 35px;

                height: 35px;

            }


            .dashboard-logos img.smart-logo {

                width: 40px;

                height: 40px;

            }


            .dashboard-container {

                width:
                    calc(100% - 24px);

                margin-top: 18px;

            }


            .dashboard-hero {

                padding:
                    24px 22px;

                border-radius: 16px;

            }


            .dashboard-hero h1 {

                font-size: 22px;

            }


            .dashboard-hero p {

                font-size: 12px;

            }


            .dashboard-stats {

                grid-template-columns: 1fr;

            }


            .dashboard-management {

                grid-template-columns: 1fr;

            }


            .dashboard-status-grid {

                grid-template-columns: 1fr;

            }


            .dashboard-section-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .dashboard-chart-card {

                min-height: 310px;

            }


            .dashboard-chart-container {

                height: 225px;

            }


            .dashboard-profile-avatar {

                width: 36px;

                height: 36px;

            }


            .dashboard-logout {

                min-height: 36px;

                padding:
                    0 11px;

                font-size: 11px;

            }

        }


        @media (max-width: 450px) {

            .dashboard-logos {

                gap: 4px;

            }


            .dashboard-logos img {

                width: 31px;

                height: 31px;

            }


            .dashboard-logos img.smart-logo {

                width: 36px;

                height: 36px;

            }


            .dashboard-brand-title {

                font-size: 14px;

            }


            .dashboard-hero h1 {

                font-size: 20px;

            }

        }

    </style>

</head>


<body class="dashboard-page">


<!-- =====================================================
     HEADER
===================================================== -->

<header class="dashboard-header">

    <div class="dashboard-header-inner">


        <div class="dashboard-brand">


            <div class="dashboard-logos">

                <img
                    src="assets/isu-logo.png"
                    alt="Isabela State University"
                >

                <img
                    src="assets/ccsict-logo.png"
                    alt="CCSICT"
                >

                <img
                    src="assets/smart-enroll-logo.png"
                    alt="ISU SmartEnroll"
                    class="smart-logo"
                >

            </div>


            <div class="dashboard-brand-text">

                <div class="dashboard-brand-title">
                    ISU SmartEnroll
                </div>

                <div class="dashboard-brand-subtitle">
                    Isabela State University — Cauayan Campus
                </div>

            </div>


        </div>


        <div class="dashboard-profile">


            <div class="dashboard-profile-avatar">

                <?php

                echo strtoupper(
                    substr(
                        trim($adminName),
                        0,
                        1
                    )
                );

                ?>

            </div>


            <div class="dashboard-profile-info">

                <div class="dashboard-profile-name">

                    <?php

                    echo htmlspecialchars(
                        $adminName
                    );

                    ?>

                </div>


                <div class="dashboard-profile-role">

                    @<?php

                    echo htmlspecialchars(
                        $adminUsername
                    );

                    ?>

                    · Administrator

                </div>

            </div>


            <a
                href="admin_dashboard.php?logout=1"
                class="dashboard-logout"
                onclick="
                    return confirm(
                        'Are you sure you want to logout?'
                    );
                "
            >
                Logout
            </a>


        </div>


    </div>

</header>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="dashboard-container">


    <!-- =================================================
         HERO
    ================================================== -->

    <section class="dashboard-hero">

        <div class="dashboard-hero-content">


            <div class="dashboard-hero-eyebrow">
                ADMINISTRATION DASHBOARD
            </div>


            <h1>

                Welcome,

                <?php

                echo htmlspecialchars(
                    $adminName
                );

                ?>!

            </h1>


            <p>

                Manage enrollment records, users,
                academic programs, requirements,
                reports, backups, and system activities
                from one centralized dashboard.

            </p>


        </div>

    </section>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <section class="dashboard-stats">


        <!-- STUDENTS -->

        <div class="dashboard-stat">

            <div class="dashboard-stat-top">

                <div class="dashboard-stat-icon">
                    👨‍🎓
                </div>

            </div>


            <div class="dashboard-stat-label">
                Total Students
            </div>


            <div class="dashboard-stat-value">

                <?php

                echo number_format(
                    $totalStudents
                );

                ?>

            </div>


            <div class="dashboard-stat-accent"></div>

        </div>



        <!-- ENROLLMENTS -->

        <div class="dashboard-stat">

            <div class="dashboard-stat-top">

                <div class="dashboard-stat-icon">
                    📝
                </div>

            </div>


            <div class="dashboard-stat-label">
                Total Enrollments
            </div>


            <div class="dashboard-stat-value">

                <?php

                echo number_format(
                    $totalEnrollments
                );

                ?>

            </div>


            <div class="dashboard-stat-accent"></div>

        </div>



        <!-- ACTIVE USERS -->

        <div class="dashboard-stat">

            <div class="dashboard-stat-top">

                <div class="dashboard-stat-icon">
                    👥
                </div>

            </div>


            <div class="dashboard-stat-label">
                Active Users
            </div>


            <div class="dashboard-stat-value">

                <?php

                echo number_format(
                    $activeUsers
                );

                ?>

            </div>


            <div class="dashboard-stat-accent"></div>

        </div>



        <!-- DAILY ACTIVITY -->

        <div class="dashboard-stat">

            <div class="dashboard-stat-top">

                <div class="dashboard-stat-icon">
                    📊
                </div>

            </div>


            <div class="dashboard-stat-label">
                Today's Activity
            </div>


            <div class="dashboard-stat-value">

                <?php

                echo number_format(
                    $dailyActivity
                );

                ?>

            </div>


            <div class="dashboard-stat-accent"></div>

        </div>


    </section>



    <!-- =================================================
         ANALYTICS
    ================================================== -->

    <section class="dashboard-section">


        <div class="dashboard-section-header">

            <div>

                <div class="dashboard-section-title">
                    Dashboard Analytics
                </div>

                <div class="dashboard-section-subtitle">
                    Monitor system activity and record statistics
                </div>

            </div>

        </div>



        <div class="dashboard-analytics">


            <!-- MONTHLY -->

            <div class="dashboard-chart-card">


                <div class="dashboard-chart-head">

                    <div>

                        <div class="dashboard-chart-title">
                            Monthly Activity
                        </div>

                        <div class="dashboard-chart-description">
                            Activity recorded during the last six months.
                        </div>

                    </div>


                    <div class="dashboard-chart-badge">
                        6 MONTHS
                    </div>

                </div>


                <div class="dashboard-chart-container">

                    <canvas
                        id="monthlyActivityChart"
                    ></canvas>

                </div>


            </div>



            <!-- DAILY -->

            <div class="dashboard-chart-card">


                <div class="dashboard-chart-head">

                    <div>

                        <div class="dashboard-chart-title">
                            Daily Activity
                        </div>

                        <div class="dashboard-chart-description">
                            System activity recorded during the last seven days.
                        </div>

                    </div>


                    <div class="dashboard-chart-badge">
                        7 DAYS
                    </div>

                </div>


                <div class="dashboard-chart-container">

                    <canvas
                        id="dailyActivityChart"
                    ></canvas>

                </div>


            </div>



            <!-- USER ACTIVITY -->

            <div class="dashboard-chart-card">


                <div class="dashboard-chart-head">

                    <div>

                        <div class="dashboard-chart-title">
                            User Activity
                        </div>

                        <div class="dashboard-chart-description">
                            Top users based on recorded system activities.
                        </div>

                    </div>


                    <div class="dashboard-chart-badge">
                        TOP 5
                    </div>

                </div>


                <div class="dashboard-chart-container">

                    <canvas
                        id="userActivityChart"
                    ></canvas>

                </div>


            </div>



            <!-- SYSTEM SUMMARY -->

            <div class="dashboard-chart-card">


                <div class="dashboard-chart-head">

                    <div>

                        <div class="dashboard-chart-title">
                            System Summary
                        </div>

                        <div class="dashboard-chart-description">
                            Current records maintained by the system.
                        </div>

                    </div>


                    <div class="dashboard-chart-badge">
                        LIVE
                    </div>

                </div>


                <div class="dashboard-chart-container">

                    <canvas
                        id="systemSummaryChart"
                    ></canvas>

                </div>


            </div>


        </div>

    </section>



    <!-- =================================================
         MANAGEMENT
    ================================================== -->

    <section class="dashboard-section">


        <div class="dashboard-section-header">

            <div>

                <div class="dashboard-section-title">
                    System Management
                </div>

                <div class="dashboard-section-subtitle">
                    Administration and system control tools
                </div>

            </div>

        </div>



        <div class="dashboard-management">


            <!-- BACKUP -->

            <a
                href="backup_restore.php"
                class="dashboard-management-card"
            >

                <div>

                    <div class="dashboard-management-icon">
                        💾
                    </div>

                    <div class="dashboard-management-title">
                        Backup & Restore
                    </div>

                    <div class="dashboard-management-description">
                        Create database and application
                        backups and restore system data.
                    </div>

                </div>


                <div class="dashboard-management-action">
                    Manage Backups →
                </div>

            </a>



            <!-- USERS -->

            <a
                href="users.php"
                class="dashboard-management-card"
            >

                <div>

                    <div class="dashboard-management-icon">
                        👥
                    </div>

                    <div class="dashboard-management-title">
                        User Management
                    </div>

                    <div class="dashboard-management-description">
                        Manage administrators, registrars,
                        clinic, library, and other users.
                    </div>

                </div>


                <div class="dashboard-management-action">
                    Manage Users →
                </div>

            </a>



            <!-- STUDENTS -->

            <a
                href="student.php"
                class="dashboard-management-card"
            >

                <div>

                    <div class="dashboard-management-icon">
                        🎓
                    </div>

                    <div class="dashboard-management-title">
                        Student Records
                    </div>

                    <div class="dashboard-management-description">
                        View, add, update, search,
                        and manage student information.
                    </div>

                </div>


                <div class="dashboard-management-action">
                    Manage Students →
                </div>

            </a>



            <!-- PROGRAMS -->

            <a
                href="programs.php"
                class="dashboard-management-card"
            >

                <div>

                    <div class="dashboard-management-icon">
                        📚
                    </div>

                    <div class="dashboard-management-title">
                        Academic Programs
                    </div>

                    <div class="dashboard-management-description">
                        Manage available academic programs
                        and enrollment offerings.
                    </div>

                </div>


                <div class="dashboard-management-action">
                    Manage Programs →
                </div>

            </a>



            <!-- REQUIREMENTS -->

            <a
                href="admin_requirements.php"
                class="dashboard-management-card"
            >

                <div>

                    <div class="dashboard-management-icon">
                        📋
                    </div>

                    <div class="dashboard-management-title">
                        Enrollment Requirements
                    </div>

                    <div class="dashboard-management-description">
                        Manage documents and requirements
                        needed for student enrollment.
                    </div>

                </div>


                <div class="dashboard-management-action">
                    Manage Requirements →
                </div>

            </a>



            <!-- REPORTS -->

            <a
                href="reports.php"
                class="dashboard-management-card"
            >

                <div>

                    <div class="dashboard-management-icon">
                        📈
                    </div>

                    <div class="dashboard-management-title">
                        Reports
                    </div>

                    <div class="dashboard-management-description">
                        Generate summaries and reports
                        for enrollment and system records.
                    </div>

                </div>


                <div class="dashboard-management-action">
                    Generate Reports →
                </div>

            </a>



            <!-- ACTIVITY LOGS -->

            <a
                href="activity_logs.php"
                class="dashboard-management-card"
            >

                <div>

                    <div class="dashboard-management-icon">
                        🕒
                    </div>

                    <div class="dashboard-management-title">
                        Activity Logs
                    </div>

                    <div class="dashboard-management-description">
                        Monitor user activities and maintain
                        an audit trail of system actions.
                    </div>

                </div>


                <div class="dashboard-management-action">
                    View Activity Logs →
                </div>

            </a>


        </div>

    </section>



    <!-- =================================================
         RECENT ACTIVITIES
    ================================================== -->

    <section class="dashboard-section">


        <div class="dashboard-section-header">

            <div>

                <div class="dashboard-section-title">
                    Recent Activities
                </div>

                <div class="dashboard-section-subtitle">
                    Latest actions recorded by the system
                </div>

            </div>


            <a
                href="activity_logs.php"
                class="dashboard-section-link"
            >
                View All →
            </a>

        </div>



        <div class="dashboard-panel">


            <div class="dashboard-panel-header">

                <div>

                    <div class="dashboard-panel-title">
                        Activity History
                    </div>

                    <div class="dashboard-panel-subtitle">
                        Showing the latest 8 recorded activities
                    </div>

                </div>

            </div>



            <div class="dashboard-table-wrap">


                <?php if (count($recentActivities) > 0): ?>


                    <table class="dashboard-table">


                        <thead>

                            <tr>

                                <th>
                                    User
                                </th>

                                <th>
                                    Activity
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Time
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $recentActivities
                                as $activity
                            ): ?>


                                <?php

                                $displayName =
                                    $activity["full_name"]
                                    ?: $activity["username"];

                                $initial =
                                    strtoupper(
                                        substr(
                                            trim(
                                                $displayName
                                            ),
                                            0,
                                            1
                                        )
                                    );

                                ?>


                                <tr>


                                    <td>

                                        <div class="dashboard-user">


                                            <div class="dashboard-user-avatar">

                                                <?php

                                                echo htmlspecialchars(
                                                    $initial
                                                );

                                                ?>

                                            </div>


                                            <div>

                                                <div class="dashboard-user-name">

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $displayName
                                                    );

                                                    ?>

                                                </div>


                                                <div class="dashboard-user-username">

                                                    @<?php

                                                    echo htmlspecialchars(
                                                        $activity["username"]
                                                    );

                                                    ?>

                                                </div>

                                            </div>


                                        </div>

                                    </td>


                                    <td class="dashboard-activity">

                                        <?php

                                        echo htmlspecialchars(
                                            $activity["activity"]
                                        );

                                        ?>

                                    </td>


                                    <td class="dashboard-date">

                                        <?php

                                        echo htmlspecialchars(
                                            date(
                                                "M d, Y",
                                                strtotime(
                                                    $activity["activity_date"]
                                                )
                                            )
                                        );

                                        ?>

                                    </td>


                                    <td class="dashboard-time">

                                        <?php

                                        echo htmlspecialchars(
                                            date(
                                                "h:i A",
                                                strtotime(
                                                    $activity["activity_time"]
                                                )
                                            )
                                        );

                                        ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                <?php else: ?>


                    <div class="dashboard-empty">

                        No activity records found.

                    </div>


                <?php endif; ?>


            </div>


        </div>

    </section>



    <!-- =================================================
         SYSTEM STATUS
    ================================================== -->

    <section class="dashboard-section">


        <div class="dashboard-section-header">

            <div>

                <div class="dashboard-section-title">
                    System Status
                </div>

                <div class="dashboard-section-subtitle">
                    Current system information
                </div>

            </div>

        </div>



        <div class="dashboard-status-grid">


            <!-- DATABASE -->

            <div class="dashboard-status-card">


                <div class="dashboard-status-icon">
                    ✓
                </div>


                <div>

                    <div class="dashboard-status-label">
                        Database
                    </div>


                    <div class="dashboard-status-value dashboard-online">
                        ● Connected
                    </div>

                </div>


            </div>



            <!-- ACCOUNT -->

            <div class="dashboard-status-card">


                <div class="dashboard-status-icon">
                    👤
                </div>


                <div>

                    <div class="dashboard-status-label">
                        Logged-in Account
                    </div>


                    <div class="dashboard-status-value">

                        <?php

                        echo htmlspecialchars(
                            $adminUsername
                        );

                        ?>

                    </div>

                </div>


            </div>



            <!-- LOGS -->

            <div class="dashboard-status-card">


                <div class="dashboard-status-icon">
                    📝
                </div>


                <div>

                    <div class="dashboard-status-label">
                        Total Activity Logs
                    </div>


                    <div class="dashboard-status-value">

                        <?php

                        echo number_format(
                            $totalActivities
                        );

                        ?>

                    </div>

                </div>


            </div>


        </div>

    </section>


</main>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="dashboard-footer">

    ISU SmartEnroll ·
    Isabela State University,
    Cauayan Campus

</footer>



<!-- =====================================================
     CHARTS
===================================================== -->

<script>

    /* =====================================================
       CHART DATA
    ===================================================== */

    const monthlyLabels =
        <?php

        echo json_encode(
            $monthlyLabels
        );

        ?>;


    const monthlyValues =
        <?php

        echo json_encode(
            $monthlyValues
        );

        ?>;


    const dailyLabels =
        <?php

        echo json_encode(
            $dailyLabels
        );

        ?>;


    const dailyValues =
        <?php

        echo json_encode(
            $dailyChartValues
        );

        ?>;


    const userLabels =
        <?php

        echo json_encode(
            $userActivityLabels
        );

        ?>;


    const userValues =
        <?php

        echo json_encode(
            $userActivityValues
        );

        ?>;


    const green = "#006b3c";

    const lightGreen = "#8bc9a8";


    /* =====================================================
       MONTHLY ACTIVITY
    ===================================================== */

    new Chart(

        document.getElementById(
            "monthlyActivityChart"
        ),

        {

            type: "bar",

            data: {

                labels:
                    monthlyLabels,

                datasets: [

                    {

                        label:
                            "Activities",

                        data:
                            monthlyValues,

                        backgroundColor:
                            green,

                        borderRadius: 7,

                        borderSkipped: false

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {

                        display: false

                    }

                },

                scales: {

                    y: {

                        beginAtZero: true,

                        grid: {

                            color:
                                "rgba(0,0,0,0.05)"

                        },

                        ticks: {

                            precision: 0,

                            color: "#78837c"

                        }

                    },

                    x: {

                        grid: {

                            display: false

                        },

                        ticks: {

                            color: "#78837c"

                        }

                    }

                }

            }

        }

    );


    /* =====================================================
       DAILY ACTIVITY
    ===================================================== */

    new Chart(

        document.getElementById(
            "dailyActivityChart"
        ),

        {

            type: "line",

            data: {

                labels:
                    dailyLabels,

                datasets: [

                    {

                        label:
                            "Activities",

                        data:
                            dailyValues,

                        borderColor:
                            green,

                        backgroundColor:
                            "rgba(0,107,60,0.10)",

                        fill: true,

                        tension: 0.35,

                        pointRadius: 4,

                        pointHoverRadius: 6,

                        pointBackgroundColor:
                            green,

                        pointBorderColor:
                            "#ffffff",

                        pointBorderWidth: 2

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {

                        display: false

                    }

                },

                scales: {

                    y: {

                        beginAtZero: true,

                        grid: {

                            color:
                                "rgba(0,0,0,0.05)"

                        },

                        ticks: {

                            precision: 0,

                            color: "#78837c"

                        }

                    },

                    x: {

                        grid: {

                            display: false

                        },

                        ticks: {

                            color: "#78837c"

                        }

                    }

                }

            }

        }

    );


    /* =====================================================
       USER ACTIVITY
    ===================================================== */

    new Chart(

        document.getElementById(
            "userActivityChart"
        ),

        {

            type: "doughnut",

            data: {

                labels:
                    userLabels,

                datasets: [

                    {

                        data:
                            userValues,

                        backgroundColor: [

                            "#006b3c",
                            "#198754",
                            "#4aa878",
                            "#78bf98",
                            "#a7d9bb"

                        ],

                        borderWidth: 3,

                        borderColor:
                            "#ffffff"

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                cutout: "66%",

                plugins: {

                    legend: {

                        position: "bottom",

                        labels: {

                            usePointStyle: true,

                            padding: 14,

                            color: "#526058",

                            font: {

                                size: 10

                            }

                        }

                    }

                }

            }

        }

    );


    /* =====================================================
       SYSTEM SUMMARY
    ===================================================== */

    const summaryLabels = [

        "Students",
        "Enrollments",
        "Users",
        "Activities"

    ];


    const summaryValues = [

        <?php

        echo (int)$totalStudents;

        ?>,

        <?php

        echo (int)$totalEnrollments;

        ?>,

        <?php

        echo (int)$totalUsers;

        ?>,

        <?php

        echo (int)$totalActivities;

        ?>

    ];


    new Chart(

        document.getElementById(
            "systemSummaryChart"
        ),

        {

            type: "bar",

            data: {

                labels:
                    summaryLabels,

                datasets: [

                    {

                        label:
                            "Records",

                        data:
                            summaryValues,

                        backgroundColor:
                            lightGreen,

                        borderColor:
                            green,

                        borderWidth: 1,

                        borderRadius: 7

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                indexAxis: "y",

                plugins: {

                    legend: {

                        display: false

                    }

                },

                scales: {

                    x: {

                        beginAtZero: true,

                        grid: {

                            color:
                                "rgba(0,0,0,0.05)"

                        },

                        ticks: {

                            precision: 0,

                            color: "#78837c"

                        }

                    },

                    y: {

                        grid: {

                            display: false

                        },

                        ticks: {

                            color: "#526058"

                        }

                    }

                }

            }

        }

    );

</script>


</body>

</html>