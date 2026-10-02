<?php
session_start();

require_once "db.php";

/* =========================
   ADMIN SECURITY CHECK
========================= */
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_SESSION["admin_role"]) || strtolower($_SESSION["admin_role"]) !== "admin") {
    die("Access denied.");
}

/* =========================
   SEARCH & FILTER
========================= */
$search = trim($_GET["search"] ?? "");
$date = trim($_GET["date"] ?? "");

/* =========================
   PAGINATION
========================= */
$per_page = 10;
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $per_page;

/* =========================
   BUILD CONDITIONS
========================= */
$conditions = [];
$params = [];
$types = "";

if ($search !== "") {
    $conditions[] = "(
        full_name LIKE ?
        OR username LIKE ?
        OR activity LIKE ?
        OR ip_address LIKE ?
    )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ssss";
}

if ($date !== "") {
    $conditions[] = "activity_date = ?";
    $params[] = $date;
    $types .= "s";
}

$where = "";

if (!empty($conditions)) {
    $where = "WHERE " . implode(" AND ", $conditions);
}

/* =========================
   TOTAL RECORDS
========================= */
$count_sql = "SELECT COUNT(*) AS total
              FROM activity_logs
              $where";

$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();

$count_result = $count_stmt->get_result();
$total_records = (int)$count_result->fetch_assoc()["total"];

$count_stmt->close();

$total_pages = max(1, ceil($total_records / $per_page));

if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

/* =========================
   GET ACTIVITY LOGS
========================= */
$sql = "SELECT
            id,
            user_id,
            full_name,
            username,
            activity,
            activity_date,
            activity_time,
            ip_address
        FROM activity_logs
        $where
        ORDER BY activity_date DESC, activity_time DESC, id DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

$data_params = $params;
$data_types = $types . "ii";

$data_params[] = $per_page;
$data_params[] = $offset;

$stmt->bind_param($data_types, ...$data_params);

$stmt->execute();

$result = $stmt->get_result();

/* =========================
   HELPER FOR URL
========================= */
function pageUrl($page, $search, $date)
{
    return "activity_logs.php?page=" . $page
        . "&search=" . urlencode($search)
        . "&date=" . urlencode($date);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activity Logs - ISU SmartEnroll</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7f5;
            color: #26332b;
        }

        /* =========================
           HEADER
        ========================= */
        .topbar {
            background: #0b6b3a;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            background: white;
            border-radius: 50%;
            padding: 3px;
        }

        .brand-text h1 {
            font-size: 20px;
            margin-bottom: 3px;
        }

        .brand-text p {
            font-size: 12px;
            opacity: 0.9;
        }

        .back-btn {
            text-decoration: none;
            color: #0b6b3a;
            background: white;
            padding: 9px 15px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: bold;
        }

        .back-btn:hover {
            background: #e9f5ee;
        }

        /* =========================
           MAIN
        ========================= */
        .container {
            width: 94%;
            max-width: 1400px;
            margin: 28px auto;
        }

        .page-title {
            margin-bottom: 20px;
        }

        .page-title h2 {
            font-size: 26px;
            color: #174b30;
            margin-bottom: 5px;
        }

        .page-title p {
            color: #6d7771;
            font-size: 14px;
        }

        /* =========================
           SUMMARY
        ========================= */
        .summary {
            background: white;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 18px;
            border: 1px solid #e1e8e3;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .summary strong {
            color: #0b6b3a;
            font-size: 20px;
        }

        .summary span {
            color: #777;
            font-size: 13px;
        }

        /* =========================
           FILTER BOX
        ========================= */
        .filter-box {
            background: white;
            border: 1px solid #e1e8e3;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 18px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 190px auto auto;
            gap: 10px;
            align-items: end;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 12px;
            font-weight: bold;
            color: #53615a;
        }

        .field input {
            height: 40px;
            border: 1px solid #ccd7d0;
            border-radius: 7px;
            padding: 0 12px;
            font-size: 14px;
            outline: none;
        }

        .field input:focus {
            border-color: #0b6b3a;
        }

        .btn {
            height: 40px;
            border: none;
            border-radius: 7px;
            padding: 0 17px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-search {
            background: #0b6b3a;
            color: white;
        }

        .btn-search:hover {
            background: #09592f;
        }

        .btn-clear {
            background: #edf1ee;
            color: #425048;
        }

        .btn-clear:hover {
            background: #dfe7e2;
        }

        /* =========================
           TABLE
        ========================= */
        .table-card {
            background: white;
            border: 1px solid #e1e8e3;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th {
            background: #0b6b3a;
            color: white;
            text-align: left;
            padding: 13px 12px;
            font-size: 12px;
            white-space: nowrap;
        }

        td {
            padding: 13px 12px;
            border-bottom: 1px solid #edf0ee;
            font-size: 13px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #f7faf8;
        }

        .activity {
            max-width: 330px;
            line-height: 1.4;
        }

        .user-name {
            font-weight: bold;
            color: #26332b;
        }

        .username {
            color: #777;
            font-size: 12px;
        }

        .date {
            white-space: nowrap;
        }

        .time {
            white-space: nowrap;
        }

        .ip {
            font-family: Consolas, monospace;
            font-size: 12px;
            color: #59645e;
        }

        /* =========================
           EMPTY
        ========================= */
        .empty {
            text-align: center;
            padding: 45px 20px;
            color: #777;
        }

        .empty strong {
            display: block;
            color: #4d5a52;
            margin-bottom: 6px;
        }

        /* =========================
           PAGINATION
        ========================= */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 20px;
            flex-wrap: wrap;
        }

        .page-btn {
            min-width: 35px;
            height: 35px;
            padding: 0 10px;
            border-radius: 6px;
            border: 1px solid #d4ddd7;
            background: white;
            color: #365040;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        .page-btn:hover {
            background: #edf5ef;
        }

        .page-btn.active {
            background: #0b6b3a;
            color: white;
            border-color: #0b6b3a;
            font-weight: bold;
        }

        .page-info {
            color: #777;
            font-size: 12px;
            margin: 0 8px;
        }

        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 850px) {

            .topbar {
                padding: 12px 16px;
            }

            .brand-text h1 {
                font-size: 17px;
            }

            .brand-text p {
                display: none;
            }

            .back-btn {
                padding: 8px 11px;
                font-size: 12px;
            }

            .container {
                width: 95%;
                margin: 20px auto;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
            }

            .summary {
                align-items: flex-start;
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>

<body>

<!-- =========================
     HEADER
========================= -->
<div class="topbar">

    <div class="brand">

        <img src="assets/isu-logo.png" alt="ISU Logo">

        <div class="brand-text">
            <h1>ISU SmartEnroll</h1>
            <p>Activity Logs & Audit Trail</p>
        </div>

    </div>

    <a href="admin_dashboard.php" class="back-btn">
        ← Dashboard
    </a>

</div>


<!-- =========================
     MAIN
========================= -->
<div class="container">

    <div class="page-title">
        <h2>Activity Logs</h2>
        <p>Monitor user activities and system actions.</p>
    </div>


    <!-- SUMMARY -->
    <div class="summary">

        <div>
            <strong><?php echo number_format($total_records); ?></strong>
            <span>Total Activity Records</span>
        </div>

        <div>
            <span>
                <?php
                if ($search !== "" || $date !== "") {
                    echo "Filtered results";
                } else {
                    echo "All recorded activities";
                }
                ?>
            </span>
        </div>

    </div>


    <!-- FILTER -->
    <div class="filter-box">

        <form method="GET" action="activity_logs.php" class="filter-form">

            <div class="field">
                <label>Search</label>

                <input
                    type="text"
                    name="search"
                    placeholder="Search name, username, activity or IP..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>


            <div class="field">
                <label>Activity Date</label>

                <input
                    type="date"
                    name="date"
                    value="<?php echo htmlspecialchars($date); ?>"
                >
            </div>


            <button type="submit" class="btn btn-search">
                Search
            </button>


            <a href="activity_logs.php" class="btn btn-clear">
                Clear
            </a>

        </form>

    </div>


    <!-- TABLE -->
    <div class="table-card">

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Activity</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>IP Address</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo (int)$row["id"]; ?>
                            </td>

                            <td>
                                <?php
                                echo $row["user_id"] !== null
                                    ? (int)$row["user_id"]
                                    : "—";
                                ?>
                            </td>

                            <td>
                                <div class="user-name">
                                    <?php
                                    echo htmlspecialchars(
                                        $row["full_name"] ?? "—"
                                    );
                                    ?>
                                </div>
                            </td>

                            <td>
                                <div class="username">
                                    <?php
                                    echo htmlspecialchars(
                                        $row["username"] ?? "—"
                                    );
                                    ?>
                                </div>
                            </td>

                            <td class="activity">
                                <?php
                                echo htmlspecialchars(
                                    $row["activity"]
                                );
                                ?>
                            </td>

                            <td class="date">
                                <?php
                                echo htmlspecialchars(
                                    $row["activity_date"]
                                );
                                ?>
                            </td>

                            <td class="time">
                                <?php
                                echo htmlspecialchars(
                                    $row["activity_time"]
                                );
                                ?>
                            </td>

                            <td class="ip">
                                <?php
                                echo htmlspecialchars(
                                    $row["ip_address"] ?? "—"
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8">

                            <div class="empty">

                                <strong>No activity records found.</strong>

                                <?php if ($search !== "" || $date !== ""): ?>
                                    Try changing your search or date filter.
                                <?php else: ?>
                                    Activity logs will appear here once system actions are recorded.
                                <?php endif; ?>

                            </div>

                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


        <!-- PAGINATION -->
        <?php if ($total_records > 0): ?>

            <div class="pagination">

                <?php if ($page > 1): ?>

                    <a
                        class="page-btn"
                        href="<?php echo pageUrl($page - 1, $search, $date); ?>"
                    >
                        ‹
                    </a>

                <?php endif; ?>


                <?php

                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>

                    <a
                        class="page-btn <?php echo ($i == $page) ? "active" : ""; ?>"
                        href="<?php echo pageUrl($i, $search, $date); ?>"
                    >
                        <?php echo $i; ?>
                    </a>

                <?php endfor; ?>


                <span class="page-info">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </span>


                <?php if ($page < $total_pages): ?>

                    <a
                        class="page-btn"
                        href="<?php echo pageUrl($page + 1, $search, $date); ?>"
                    >
                        ›
                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>