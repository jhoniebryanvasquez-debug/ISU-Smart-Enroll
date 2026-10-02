<?php

session_start();

require_once "db.php";
require_once "activity_logger.php";

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
   CSRF TOKEN
========================= */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];


/* =========================
   VARIABLES
========================= */

$message = "";
$messageType = "success";


$allowedRoles = [
    "Admin",
    "Registrar",
    "Clinic",
    "Library"
];

$allowedStatus = [
    "Active",
    "Inactive"
];


/* =========================
   ADD USER
========================= */

if (isset($_POST["add_user"])) {

    /* CSRF CHECK */

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"]
        )
    ) {

        $message = "Security verification failed. Please try again.";
        $messageType = "error";

    } else {

        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $full_name = trim($_POST["full_name"] ?? "");
        $role = trim($_POST["role"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $status = trim($_POST["status"] ?? "Active");


        /* Basic validation */

        if (
            $username === "" ||
            $password === "" ||
            $full_name === "" ||
            $role === ""
        ) {

            $message = "Please fill in all required fields.";
            $messageType = "error";

        } elseif (!in_array($role, $allowedRoles, true)) {

            $message = "Invalid role.";
            $messageType = "error";

        } elseif (!in_array($status, $allowedStatus, true)) {

            $message = "Invalid account status.";
            $messageType = "error";

        } elseif (strlen($username) < 3) {

            $message = "Username must be at least 3 characters.";
            $messageType = "error";

        } elseif (strlen($password) < 8) {

            $message = "Password must be at least 8 characters.";
            $messageType = "error";

        } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $messageType = "error";

        } else {

            /* =========================
               CHECK USERNAME
            ========================= */

            $check = $conn->prepare(
                "SELECT id
                 FROM users
                 WHERE username = ?
                 LIMIT 1"
            );

            if (!$check) {

                $message = "Unable to validate username.";
                $messageType = "error";

            } else {

                $check->bind_param(
                    "s",
                    $username
                );

                $check->execute();

                $existing = $check->get_result();

                if ($existing->num_rows > 0) {

                    $message = "Username already exists.";
                    $messageType = "error";

                } else {

                    /* =========================
                       CHECK EMAIL
                    ========================= */

                    if ($email !== "") {

                        $emailCheck = $conn->prepare(
                            "SELECT id
                             FROM users
                             WHERE email = ?
                             LIMIT 1"
                        );

                        if (!$emailCheck) {

                            $message = "Unable to validate email.";
                            $messageType = "error";

                        } else {

                            $emailCheck->bind_param(
                                "s",
                                $email
                            );

                            $emailCheck->execute();

                            $emailResult =
                                $emailCheck->get_result();

                            if ($emailResult->num_rows > 0) {

                                $message =
                                    "Email address already exists.";

                                $messageType = "error";

                            }

                            $emailCheck->close();
                        }
                    }


                    /* =========================
                       CREATE USER
                    ========================= */

                    if ($message === "") {

                        $hashedPassword = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $emailValue =
                            ($email === "")
                                ? null
                                : $email;


                        $stmt = $conn->prepare(
                            "INSERT INTO users
                            (
                                username,
                                password,
                                full_name,
                                role,
                                email,
                                status
                            )
                            VALUES (?, ?, ?, ?, ?, ?)"
                        );


                        if (!$stmt) {

                            $message =
                                "Unable to prepare user creation.";

                            $messageType = "error";

                        } else {

                            $stmt->bind_param(
                                "ssssss",
                                $username,
                                $hashedPassword,
                                $full_name,
                                $role,
                                $emailValue,
                                $status
                            );


                            if ($stmt->execute()) {

                                logActivity(
                                    $conn,
                                    "Added new user: " .
                                    $username .
                                    " (" .
                                    $role .
                                    ")"
                                );


                                $message =
                                    "User added successfully.";

                                $messageType = "success";


                                /* Prevent duplicate POST */

                                $_POST = [];

                            } else {

                                $message =
                                    "Unable to add user.";

                                $messageType = "error";
                            }


                            $stmt->close();
                        }
                    }
                }


                $check->close();
            }
        }
    }
}


/* =========================
   ACTIVATE / DEACTIVATE USER
========================= */

if (isset($_POST["toggle_status"])) {

    /* CSRF CHECK */

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"]
        )
    ) {

        $message =
            "Security verification failed. Please try again.";

        $messageType = "error";

    } else {

        $id = (int) ($_POST["user_id"] ?? 0);


        if ($id <= 0) {

            $message = "Invalid user account.";
            $messageType = "error";

        }

        /* Prevent changing your own status */

        elseif (
            isset($_SESSION["user_id"]) &&
            $id === (int) $_SESSION["user_id"]
        ) {

            $message =
                "You cannot deactivate or change the status of your own account.";

            $messageType = "error";

        } else {

            /* =========================
               GET TARGET USER
            ========================= */

            $stmt = $conn->prepare(
                "SELECT
                    username,
                    full_name,
                    role,
                    status
                 FROM users
                 WHERE id = ?
                 LIMIT 1"
            );


            if (!$stmt) {

                $message =
                    "Unable to retrieve user account.";

                $messageType = "error";

            } else {

                $stmt->bind_param(
                    "i",
                    $id
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();

                $targetUser =
                    $result->fetch_assoc();

                $stmt->close();


                if (!$targetUser) {

                    $message =
                        "User account not found.";

                    $messageType = "error";

                } else {

                    /* =========================
                       TOGGLE STATUS
                    ========================= */

                    $newStatus =
                        ($targetUser["status"] === "Active")
                            ? "Inactive"
                            : "Active";


                    $update = $conn->prepare(
                        "UPDATE users
                         SET status = ?
                         WHERE id = ?"
                    );


                    if (!$update) {

                        $message =
                            "Unable to update account status.";

                        $messageType = "error";

                    } else {

                        $update->bind_param(
                            "si",
                            $newStatus,
                            $id
                        );


                        if ($update->execute()) {

                            if ($newStatus === "Active") {

                                $message =
                                    "User account activated successfully.";

                                logActivity(
                                    $conn,
                                    "Activated user account: " .
                                    $targetUser["username"]
                                );

                            } else {

                                $message =
                                    "User account deactivated successfully.";

                                logActivity(
                                    $conn,
                                    "Deactivated user account: " .
                                    $targetUser["username"]
                                );
                            }


                            $messageType = "success";

                        } else {

                            $message =
                                "Unable to update account status.";

                            $messageType = "error";
                        }


                        $update->close();
                    }
                }
            }
        }
    }
}


/* =========================
   SEARCH
========================= */

$search = trim(
    $_GET["search"] ?? ""
);


if ($search !== "") {

    $searchValue =
        "%" . $search . "%";


    $stmt = $conn->prepare(
        "SELECT
            id,
            username,
            full_name,
            role,
            email,
            status,
            created_at
         FROM users
         WHERE
            username LIKE ?
            OR full_name LIKE ?
            OR role LIKE ?
            OR email LIKE ?
         ORDER BY id DESC"
    );


    $stmt->bind_param(
        "ssss",
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    );


    $stmt->execute();


    $users =
        $stmt->get_result();

} else {

    $users = $conn->query(
        "SELECT
            id,
            username,
            full_name,
            role,
            email,
            status,
            created_at
         FROM users
         ORDER BY id DESC"
    );
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>User Management - ISU SmartEnroll</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            font-family:
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            color: #17221c;

            min-height: 100vh;

            background:
                linear-gradient(
                    rgba(245, 247, 246, 0.90),
                    rgba(245, 247, 246, 0.90)
                ),
                url("assets/background.jpg");

            background-position: center;

            background-size: cover;

            background-repeat: no-repeat;

            background-attachment: fixed;
        }


        a {
            text-decoration: none;
        }


        /* =========================
           HEADER
        ========================= */

        header {

            width: 100%;

            background:
                rgba(255, 255, 255, 0.96);

            border-bottom:
                1px solid #dfe8e2;

            box-shadow:
                0 4px 18px rgba(0, 0, 0, 0.06);

            position: sticky;

            top: 0;

            z-index: 1000;

            backdrop-filter: blur(10px);
        }


        .header-content {

            width:
                min(1200px, calc(100% - 40px));

            min-height: 82px;

            margin: auto;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .logos {

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .logos img {

            width: 45px;

            height: 45px;

            object-fit: contain;
        }


        .logos img.smart-logo {

            width: 50px;

            height: 50px;
        }


        .brand-text h2 {

            color: #006b3c;

            font-size: 19px;

            font-weight: 800;

            margin-bottom: 2px;
        }


        .brand-text p {

            color: #6a746e;

            font-size: 12px;
        }


        .back-link {

            color: #006b3c;

            border:
                1px solid #006b3c;

            padding:
                9px 15px;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 700;

            transition: 0.2s;

            white-space: nowrap;
        }


        .back-link:hover {

            background: #006b3c;

            color: white;

            transform: translateY(-1px);
        }


        /* =========================
           CONTAINER
        ========================= */

        .container {

            width:
                min(1200px, calc(100% - 40px));

            margin:
                30px auto 50px;
        }


        /* =========================
           BOX
        ========================= */

        .box {

            background:
                rgba(255, 255, 255, 0.96);

            border:
                1px solid #e1e8e3;

            border-radius: 18px;

            padding: 28px;

            margin-bottom: 22px;

            box-shadow:
                0 9px 28px rgba(0, 0, 0, 0.07);
        }


        h1 {

            color: #006b3c;

            margin-bottom: 6px;

            font-size: 25px;
        }


        .description {

            color: #6c766f;

            margin-bottom: 23px;

            font-size: 13px;
        }


        /* =========================
           MESSAGE
        ========================= */

        .message {

            padding: 12px 14px;

            border-radius: 9px;

            margin-bottom: 20px;

            font-size: 13px;

            line-height: 1.4;
        }


        .message.success {

            background: #eaf7ef;

            border:
                1px solid #b8dfc7;

            color: #176b3a;
        }


        .message.error {

            background: #fff0f0;

            border:
                1px solid #e0aaaa;

            color: #a33;
        }


        /* =========================
           FORM
        ========================= */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 17px;
        }


        .form-group label {

            display: block;

            font-weight: 700;

            font-size: 13px;

            margin-bottom: 7px;

            color: #26342c;
        }


        .form-group input,
        .form-group select {

            width: 100%;

            padding: 12px 13px;

            border:
                1px solid #cbd5ce;

            border-radius: 9px;

            font-size: 14px;

            background: white;

            color: #17221c;

            transition: 0.2s;
        }


        .form-group input:hover,
        .form-group select:hover {

            border-color: #9eb8a8;
        }


        .form-group input:focus,
        .form-group select:focus {

            outline: none;

            border-color: #006b3c;

            box-shadow:
                0 0 0 3px
                rgba(0, 107, 60, 0.10);
        }


        .add-button {

            margin-top: 20px;

            padding: 12px 22px;

            border: none;

            border-radius: 9px;

            background:
                linear-gradient(
                    135deg,
                    #00834a,
                    #006b3c
                );

            color: white;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s;

            box-shadow:
                0 6px 15px
                rgba(0, 107, 60, 0.20);
        }


        .add-button:hover {

            background:
                linear-gradient(
                    135deg,
                    #007441,
                    #00552f
                );

            transform: translateY(-1px);
        }


        /* =========================
           USER LIST HEADER
        ========================= */

        .list-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 18px;
        }


        .list-header h1 {

            margin-bottom: 0;
        }


        /* =========================
           SEARCH
        ========================= */

        .search-form {

            display: flex;

            gap: 9px;

            margin-bottom: 20px;
        }


        .search-form input {

            flex: 1;

            padding: 12px 13px;

            border:
                1px solid #cbd5ce;

            border-radius: 9px;

            font-size: 14px;

            background: white;
        }


        .search-form input:focus {

            outline: none;

            border-color: #006b3c;

            box-shadow:
                0 0 0 3px
                rgba(0, 107, 60, 0.10);
        }


        .search-button {

            padding:
                11px 19px;

            border: none;

            border-radius: 9px;

            background: #006b3c;

            color: white;

            font-weight: 700;

            cursor: pointer;
        }


        .search-button:hover {

            background: #00552f;
        }


        .clear-button {

            display: flex;

            align-items: center;

            justify-content: center;

            padding:
                0 16px;

            border:
                1px solid #cbd5ce;

            border-radius: 9px;

            color: #59645d;

            font-size: 13px;

            font-weight: 600;

            background: white;
        }


        .clear-button:hover {

            background: #f4f7f5;
        }


        /* =========================
           TABLE
        ========================= */

        .table-container {

            overflow-x: auto;

            border:
                1px solid #e2e8e4;

            border-radius: 12px;
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 950px;

            background: white;
        }


        th,
        td {

            padding:
                13px 12px;

            border-bottom:
                1px solid #e5ebe7;

            text-align: left;

            font-size: 13px;
        }


        th {

            background: #f0f5f2;

            color: #006b3c;

            font-size: 12px;

            font-weight: 800;

            white-space: nowrap;
        }


        tr:last-child td {

            border-bottom: none;
        }


        tbody tr {

            transition: 0.15s;
        }


        tbody tr:hover {

            background: #f8fbf9;
        }


        /* =========================
           ROLE BADGES
        ========================= */

        .role-badge {

            display: inline-block;

            padding:
                5px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;
        }


        .role-admin {

            background: #e8f5ee;

            color: #006b3c;
        }


        .role-registrar {

            background: #edf3ff;

            color: #315a9b;
        }


        .role-clinic {

            background: #fff4e5;

            color: #9a5b00;
        }


        .role-library {

            background: #f1eafa;

            color: #68439a;
        }


        /* =========================
           STATUS
        ========================= */

        .status {

            display: inline-block;

            padding:
                5px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;
        }


        .status-active {

            background: #eaf7ef;

            color: #176b3a;
        }


        .status-inactive {

            background: #fff0f0;

            color: #a33;
        }


        /* =========================
           ACTIONS
        ========================= */

        .update {

            color: #006b3c;

            font-weight: 700;
        }


        .toggle {

            color: #9a5b00;

            font-weight: 700;

            background: none;

            border: none;

            padding: 0;

            margin: 0;

            font: inherit;

            cursor: pointer;
        }


        .update:hover,
        .toggle:hover {

            text-decoration: underline;
        }


        .current-account {

            color: #999;

            font-size: 12px;

            white-space: nowrap;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {

            text-align: center;

            color: #777;

            padding: 30px;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 850px) {

            .form-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 650px) {

            .header-content {

                width:
                    calc(100% - 25px);

                min-height: 75px;
            }


            .brand-text {

                display: none;
            }


            .logos img {

                width: 40px;

                height: 40px;
            }


            .logos img.smart-logo {

                width: 45px;

                height: 45px;
            }


            .container {

                width:
                    calc(100% - 24px);

                margin-top: 20px;
            }


            .box {

                padding: 21px;

                border-radius: 15px;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .search-form {

                flex-direction: column;
            }


            .search-button,
            .clear-button {

                width: 100%;

                padding: 12px;
            }


            .list-header {

                align-items: flex-start;

                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header>

    <div class="header-content">


        <div class="brand">


            <div class="logos">

                <img
                    src="assets/isu-logo.png"
                    alt="Isabela State University Logo"
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

                <h2>
                    ISU SmartEnroll
                </h2>

                <p>
                    Isabela State University - Cauayan Campus
                </p>

            </div>


        </div>


        <a
            href="admin_dashboard.php"
            class="back-link"
        >
            ← Dashboard
        </a>


    </div>

</header>



<!-- =========================
     MAIN
========================= -->

<div class="container">


    <!-- =========================
         ADD USER
    ========================= -->

    <div class="box">


        <h1>
            User Management
        </h1>


        <p class="description">
            Create and manage system user accounts.
        </p>


        <?php if ($message !== ""): ?>

            <div
                class="message <?= htmlspecialchars($messageType) ?>"
            >
                <?= htmlspecialchars($message) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken) ?>"
            >


            <div class="form-grid">


                <div class="form-group">

                    <label for="username">
                        Username *
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter username"
                        minlength="3"
                        maxlength="50"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Password *
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        minlength="8"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="full_name">
                        Full Name *
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        placeholder="Enter full name"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="role">
                        Role *
                    </label>

                    <select
                        id="role"
                        name="role"
                        required
                    >

                        <option value="">
                            Select role
                        </option>

                        <option value="Admin">
                            Admin
                        </option>

                        <option value="Registrar">
                            Registrar
                        </option>

                        <option value="Clinic">
                            Clinic
                        </option>

                        <option value="Library">
                            Library
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter email"
                        maxlength="150"
                    >

                </div>


                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>


            </div>


            <button
                type="submit"
                name="add_user"
                class="add-button"
            >
                + Add User
            </button>


        </form>

    </div>



    <!-- =========================
         USER LIST
    ========================= -->

    <div class="box">


        <div class="list-header">

            <div>

                <h1>
                    User List
                </h1>

            </div>

        </div>


        <!-- SEARCH -->

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Search username, name, role, or email..."
                value="<?= htmlspecialchars($search) ?>"
            >


            <button
                type="submit"
                class="search-button"
            >
                Search
            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="users.php"
                    class="clear-button"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>



        <!-- TABLE -->

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Username</th>

                        <th>Full Name</th>

                        <th>Role</th>

                        <th>Email</th>

                        <th>Status</th>

                        <th>Created</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($users && $users->num_rows > 0): ?>


                    <?php while ($user = $users->fetch_assoc()): ?>


                        <tr>


                            <td>
                                <?= (int) $user["id"] ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $user["username"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars(
                                    $user["full_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </td>


                            <td>


                                <?php

                                $roleClass = "role-admin";

                                if ($user["role"] === "Registrar") {

                                    $roleClass =
                                        "role-registrar";

                                } elseif ($user["role"] === "Clinic") {

                                    $roleClass =
                                        "role-clinic";

                                } elseif ($user["role"] === "Library") {

                                    $roleClass =
                                        "role-library";
                                }

                                ?>


                                <span
                                    class="role-badge <?= $roleClass ?>"
                                >

                                    <?= htmlspecialchars(
                                        $user["role"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </span>


                            </td>


                            <td>

                                <?php if (!empty($user["email"])): ?>

                                    <?= htmlspecialchars(
                                        $user["email"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                <?php else: ?>

                                    <span style="color:#999;">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>


                                <?php if (
                                    $user["status"] === "Active"
                                ): ?>

                                    <span
                                        class="status status-active"
                                    >
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status status-inactive"
                                    >
                                        Inactive
                                    </span>

                                <?php endif; ?>


                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    date(
                                        "M d, Y",
                                        strtotime(
                                            $user["created_at"]
                                        )
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </td>


                            <td>


                                <?php

                                $isCurrentAccount =
                                    isset($_SESSION["user_id"]) &&
                                    (int) $user["id"] ===
                                    (int) $_SESSION["user_id"];

                                ?>


                                <?php if (!$isCurrentAccount): ?>


                                    <a
                                        href="edit_user.php?id=<?= (int) $user["id"] ?>"
                                        class="update"
                                    >
                                        Update
                                    </a>


                                    <span>
                                        |
                                    </span>


                                    <form
                                        method="POST"
                                        style="display:inline;"
                                        onsubmit="return confirm(
                                            'Are you sure you want to <?= $user["status"] === "Active" ? "deactivate" : "activate" ?> this user account?'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars($csrfToken) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $user["id"] ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="toggle_status"
                                            class="toggle"
                                        >

                                            <?= $user["status"] === "Active"
                                                ? "Deactivate"
                                                : "Activate"
                                            ?>

                                        </button>

                                    </form>


                                <?php else: ?>


                                    <span class="current-account">
                                        Current Account
                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="empty"
                        >
                            No users found.
                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


    </div>


</div>


</body>

</html>