<?php

session_start();

require_once "db.php";
require_once "activity_logger.php";


/* =========================================================
   SECURITY CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Admin"
) {
    header("Location: admin_login.php");
    exit;
}


/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];


/* =========================================================
   GET USER ID
========================================================= */

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    header("Location: users.php");
    exit;
}


$message = "";
$messageType = "";


/* =========================================================
   GET USER
========================================================= */

$stmt = $conn->prepare(
    "SELECT id, username, full_name, email, role, status
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to prepare user query.");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();


if (!$user) {
    header("Location: users.php");
    exit;
}


/* =========================================================
   UPDATE USER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_user"])) {

    /* =====================================================
       VERIFY CSRF TOKEN
    ===================================================== */

    $submitted_token = $_POST["csrf_token"] ?? "";

    if (
        !hash_equals(
            $_SESSION["csrf_token"],
            $submitted_token
        )
    ) {
        $message = "Invalid security token. Please refresh the page and try again.";
        $messageType = "error";

    } else {

        /* =================================================
           GET FORM DATA
        ================================================= */

        $username  = trim($_POST["username"] ?? "");
        $full_name = trim($_POST["full_name"] ?? "");
        $email     = trim($_POST["email"] ?? "");
        $role      = trim($_POST["role"] ?? "");
        $status    = trim($_POST["status"] ?? "");
        $password  = $_POST["password"] ?? "";


        /* =================================================
           VALIDATION
        ================================================= */

        if (
            $username === "" ||
            $full_name === "" ||
            $email === "" ||
            $role === "" ||
            $status === ""
        ) {

            $message = "Please fill in all required fields.";
            $messageType = "error";

        } elseif (strlen($username) < 3) {

            $message = "Username must be at least 3 characters.";
            $messageType = "error";

        } elseif (strlen($username) > 50) {

            $message = "Username must not exceed 50 characters.";
            $messageType = "error";

        } elseif (strlen($full_name) > 150) {

            $message = "Full name is too long.";
            $messageType = "error";

        } elseif (
            !in_array(
                $role,
                ["Admin", "Registrar", "Clinic", "Library"],
                true
            )
        ) {

            $message = "Invalid role selected.";
            $messageType = "error";

        } elseif (
            !in_array(
                $status,
                ["Active", "Inactive"],
                true
            )
        ) {

            $message = "Invalid status selected.";
            $messageType = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $messageType = "error";

        } elseif (strlen($email) > 150) {

            $message = "Email address is too long.";
            $messageType = "error";

        } elseif ($password !== "" && strlen($password) < 8) {

            $message = "Password must be at least 8 characters.";
            $messageType = "error";

        } else {

            /* =============================================
               CHECK DUPLICATE USERNAME
            ============================================= */

            $check = $conn->prepare(
                "SELECT id
                 FROM users
                 WHERE username = ?
                 AND id != ?
                 LIMIT 1"
            );

            if (!$check) {

                $message = "Unable to validate username.";
                $messageType = "error";

            } else {

                $check->bind_param(
                    "si",
                    $username,
                    $id
                );

                $check->execute();

                $duplicateUsername = $check->get_result();

                $usernameExists = $duplicateUsername->num_rows > 0;

                $check->close();


                if ($usernameExists) {

                    $message = "Username already exists.";
                    $messageType = "error";

                } else {

                    /* =====================================
                       CHECK DUPLICATE EMAIL
                    ===================================== */

                    $checkEmail = $conn->prepare(
                        "SELECT id
                         FROM users
                         WHERE email = ?
                         AND id != ?
                         LIMIT 1"
                    );

                    if (!$checkEmail) {

                        $message = "Unable to validate email.";
                        $messageType = "error";

                    } else {

                        $checkEmail->bind_param(
                            "si",
                            $email,
                            $id
                        );

                        $checkEmail->execute();

                        $duplicateEmail = $checkEmail->get_result();

                        $emailExists = $duplicateEmail->num_rows > 0;

                        $checkEmail->close();


                        if ($emailExists) {

                            $message = "Email address already exists.";
                            $messageType = "error";

                        } else {

                            /* =================================
                               CURRENT ADMIN ID
                            ================================= */

                            $currentAdminId = isset($_SESSION["user_id"])
                                ? (int) $_SESSION["user_id"]
                                : 0;


                            /* =================================
                               PREVENT SELF LOCKOUT
                            ================================= */

                            if (
                                $id === $currentAdminId &&
                                (
                                    $role !== "Admin" ||
                                    $status !== "Active"
                                )
                            ) {

                                $message =
                                    "You cannot remove Admin access or deactivate your own account.";

                                $messageType = "error";

                            } else {

                                /* =================================
                                   UPDATE WITH PASSWORD
                                ================================= */

                                if ($password !== "") {

                                    $hashed_password = password_hash(
                                        $password,
                                        PASSWORD_DEFAULT
                                    );

                                    $update = $conn->prepare(
                                        "UPDATE users
                                         SET username = ?,
                                             full_name = ?,
                                             email = ?,
                                             role = ?,
                                             status = ?,
                                             password = ?
                                         WHERE id = ?"
                                    );

                                    if (!$update) {

                                        $message = "Unable to prepare user update.";
                                        $messageType = "error";

                                    } else {

                                        $update->bind_param(
                                            "ssssssi",
                                            $username,
                                            $full_name,
                                            $email,
                                            $role,
                                            $status,
                                            $hashed_password,
                                            $id
                                        );
                                    }

                                } else {

                                    /* =================================
                                       UPDATE WITHOUT PASSWORD
                                    ================================= */

                                    $update = $conn->prepare(
                                        "UPDATE users
                                         SET username = ?,
                                             full_name = ?,
                                             email = ?,
                                             role = ?,
                                             status = ?
                                         WHERE id = ?"
                                    );

                                    if (!$update) {

                                        $message = "Unable to prepare user update.";
                                        $messageType = "error";

                                    } else {

                                        $update->bind_param(
                                            "sssssi",
                                            $username,
                                            $full_name,
                                            $email,
                                            $role,
                                            $status,
                                            $id
                                        );
                                    }
                                }


                                /* =================================
                                   EXECUTE UPDATE
                                ================================= */

                                if (
                                    isset($update) &&
                                    $update instanceof mysqli_stmt
                                ) {

                                    if ($update->execute()) {

                                        $update->close();


                                        /* =============================
                                           UPDATE CURRENT SESSION
                                        ============================= */

                                        if ($id === $currentAdminId) {

                                            $_SESSION["admin_username"] = $username;
                                            $_SESSION["admin_full_name"] = $full_name;
                                            $_SESSION["admin_role"] = $role;

                                            $_SESSION["username"] = $username;
                                            $_SESSION["full_name"] = $full_name;
                                            $_SESSION["role"] = $role;
                                            $_SESSION["email"] = $email;
                                        }


                                        /* =============================
                                           ACTIVITY LOG
                                        ============================= */

                                        $activityText =
                                            "Updated user account: " . $username;

                                        if ($password !== "") {
                                            $activityText .= " (password changed)";
                                        }

                                        logActivity(
                                            $conn,
                                            $activityText
                                        );


                                        /* =============================
                                           REDIRECT
                                        ============================= */

                                        header(
                                            "Location: users.php?updated=1"
                                        );
                                        exit;

                                    } else {

                                        $message =
                                            "Unable to update user.";

                                        $messageType = "error";

                                        $update->close();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
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

    <title>Edit User - ISU SmartEnroll</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            min-height: 100vh;

            background:
                linear-gradient(
                    rgba(0, 70, 40, 0.88),
                    rgba(0, 70, 40, 0.88)
                ),
                url("assets/background.jpg");

            background-size: cover;
            background-position: center;
            background-attachment: fixed;

            color: #333;

        }


        /* =========================
           HEADER
        ========================= */

        header {

            background: rgba(255, 255, 255, 0.97);

            border-bottom:
                3px solid #006b3c;

            padding:
                12px 5%;

            box-shadow:
                0 3px 15px rgba(0, 0, 0, 0.15);

        }


        .header-content {

            max-width: 1100px;

            margin: auto;

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .brand-logos {

            display: flex;

            align-items: center;

            gap: 8px;

        }


        .brand-logos img {

            width: 52px;

            height: 52px;

            object-fit: contain;

        }


        .brand-info h2 {

            color: #006b3c;

            font-size: 21px;

            margin-bottom: 3px;

        }


        .brand-info p {

            color: #555;

            font-size: 13px;

        }


        .back-link {

            display: inline-block;

            padding:
                9px 16px;

            border:
                1px solid #006b3c;

            border-radius: 7px;

            color: #006b3c;

            background: white;

            text-decoration: none;

            font-weight: bold;

            transition: 0.2s;

            white-space: nowrap;

        }


        .back-link:hover {

            background: #006b3c;

            color: white;

        }


        /* =========================
           MAIN CONTAINER
        ========================= */

        .container {

            width: 100%;

            max-width: 760px;

            margin:
                40px auto;

            padding:
                0 20px;

        }


        /* =========================
           CARD
        ========================= */

        .box {

            background:
                rgba(255, 255, 255, 0.98);

            border-radius: 15px;

            padding: 32px;

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.25);

        }


        .title-section {

            margin-bottom: 28px;

        }


        .title-section h1 {

            color: #006b3c;

            font-size: 28px;

            margin-bottom: 7px;

        }


        .description {

            color: #666;

            font-size: 14px;

            line-height: 1.5;

        }


        /* =========================
           MESSAGE
        ========================= */

        .message {

            padding:
                12px 15px;

            border-radius: 8px;

            margin-bottom: 20px;

            font-size: 14px;

            font-weight: 600;

        }


        .message.error {

            background: #fff1f1;

            border:
                1px solid #f1b5b5;

            color: #a10000;

        }


        /* =========================
           FORM
        ========================= */

        .form-group {

            margin-bottom: 19px;

        }


        .form-group label {

            display: block;

            font-size: 14px;

            font-weight: bold;

            color: #333;

            margin-bottom: 7px;

        }


        .form-group input,
        .form-group select {

            width: 100%;

            padding:
                12px 13px;

            border:
                1px solid #c8c8c8;

            border-radius: 8px;

            background: #fff;

            font-size: 14px;

            transition: 0.2s;

        }


        .form-group input:focus,
        .form-group select:focus {

            outline: none;

            border-color: #006b3c;

            box-shadow:
                0 0 0 3px
                rgba(0, 107, 60, 0.10);

        }


        .password-note {

            margin-top: 6px;

            font-size: 12px;

            color: #777;

        }


        /* =========================
           BUTTONS
        ========================= */

        .buttons {

            display: flex;

            gap: 10px;

            margin-top: 28px;

        }


        .save-button {

            flex: 1;

            border: none;

            border-radius: 8px;

            padding:
                12px 20px;

            background: #006b3c;

            color: white;

            font-size: 14px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.2s;

        }


        .save-button:hover {

            background: #00552f;

            transform:
                translateY(-1px);

        }


        .cancel-button {

            flex: 1;

            border:
                1px solid #aaa;

            border-radius: 8px;

            padding:
                12px 20px;

            background: white;

            color: #555;

            text-decoration: none;

            text-align: center;

            font-size: 14px;

            font-weight: bold;

            transition: 0.2s;

        }


        .cancel-button:hover {

            background: #f1f1f1;

        }


        /* =========================
           USER ID INFO
        ========================= */

        .user-id {

            margin-top: 22px;

            padding-top: 16px;

            border-top:
                1px solid #eee;

            color: #888;

            font-size: 12px;

        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 700px) {

            .header-content {

                flex-direction: column;

                align-items: stretch;

            }


            .brand {

                justify-content: center;

            }


            .back-link {

                width: 100%;

                text-align: center;

            }


            .container {

                margin-top: 25px;

            }


            .box {

                padding: 24px 20px;

            }

        }


        @media (max-width: 480px) {

            .brand-logos img {

                width: 42px;

                height: 42px;

            }


            .brand-info h2 {

                font-size: 18px;

            }


            .buttons {

                flex-direction: column;

            }


            .save-button,
            .cancel-button {

                width: 100%;

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


            <div class="brand-logos">

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
                >

            </div>


            <div class="brand-info">

                <h2>
                    ISU SmartEnroll
                </h2>

                <p>
                    User Management
                </p>

            </div>


        </div>


        <a
            href="users.php"
            class="back-link"
        >
            ← User Management
        </a>


    </div>

</header>



<!-- =========================
     MAIN
========================= -->

<div class="container">


    <div class="box">


        <div class="title-section">

            <h1>
                Edit User
            </h1>

            <p class="description">

                Update the account information,
                role, status, or password.

            </p>

        </div>



        <?php if ($message !== ""): ?>

            <div
                class="message <?= htmlspecialchars($messageType) ?>"
            >

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>



        <form
            method="POST"
            autocomplete="off"
        >

            <!-- CSRF TOKEN -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >


            <!-- USERNAME -->

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= htmlspecialchars($user["username"]) ?>"
                    maxlength="50"
                    required
                >

            </div>



            <!-- FULL NAME -->

            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= htmlspecialchars($user["full_name"]) ?>"
                    maxlength="150"
                    required
                >

            </div>



            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($user["email"]) ?>"
                    maxlength="150"
                    required
                >

            </div>



            <!-- ROLE -->

            <div class="form-group">

                <label for="role">
                    Role
                </label>

                <select
                    id="role"
                    name="role"
                    required
                >

                    <option
                        value="Admin"
                        <?= $user["role"] === "Admin" ? "selected" : "" ?>
                    >
                        Admin
                    </option>


                    <option
                        value="Registrar"
                        <?= $user["role"] === "Registrar" ? "selected" : "" ?>
                    >
                        Registrar
                    </option>


                    <option
                        value="Clinic"
                        <?= $user["role"] === "Clinic" ? "selected" : "" ?>
                    >
                        Clinic
                    </option>


                    <option
                        value="Library"
                        <?= $user["role"] === "Library" ? "selected" : "" ?>
                    >
                        Library
                    </option>

                </select>

            </div>



            <!-- STATUS -->

            <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    required
                >

                    <option
                        value="Active"
                        <?= $user["status"] === "Active" ? "selected" : "" ?>
                    >
                        Active
                    </option>


                    <option
                        value="Inactive"
                        <?= $user["status"] === "Inactive" ? "selected" : "" ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>



            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    New Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Leave blank to keep current password"
                    autocomplete="new-password"
                    minlength="8"
                >

                <p class="password-note">

                    Leave blank if you do not want to change
                    the current password.
                    Minimum 8 characters if changing.

                </p>

            </div>



            <!-- BUTTONS -->

            <div class="buttons">


                <button
                    type="submit"
                    name="update_user"
                    class="save-button"
                >
                    Save Changes
                </button>


                <a
                    href="users.php"
                    class="cancel-button"
                >
                    Cancel
                </a>


            </div>



            <div class="user-id">

                User ID:
                <?= htmlspecialchars($user["id"]) ?>

            </div>


        </form>


    </div>


</div>


</body>

</html>