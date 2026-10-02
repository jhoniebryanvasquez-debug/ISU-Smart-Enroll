<?php

session_start();

require_once "db.php";
require_once "activity_logger.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $sql = "
            SELECT
                id,
                username,
                password,
                full_name,
                role,
                status
            FROM users
            WHERE username = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error = "Unable to process login. Please try again.";

        } else {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            if (!$user) {

                $error = "Incorrect username or password.";

            } elseif (strtolower(trim($user["status"])) !== "active") {

                $error = "This account is inactive.";

            } elseif (empty($user["password"])) {

                $error = "This account does not have a valid password.";

            } elseif (!password_verify($password, $user["password"])) {

                $error = "Incorrect username or password.";

            } elseif (strtolower(trim($user["role"])) !== "admin") {

                $error = "You do not have administrator access.";

            } else {

                /*
                 * Successful Admin Login
                 */

                session_regenerate_id(true);

                // Admin session
                $_SESSION["admin_logged_in"] = true;
                $_SESSION["admin_id"] = $user["id"];
                $_SESSION["admin_username"] = $user["username"];
                $_SESSION["admin_full_name"] = $user["full_name"];
                $_SESSION["admin_role"] = $user["role"];

                // General session
                $_SESSION["logged_in"] = true;
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];

                // Login time
                $_SESSION["login_time"] = time();

                // Activity log
                logActivity(
                    $conn,
                    "Logged in to the administration system"
                );

                header("Location: admin_dashboard.php");
                exit;
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

    <title>Admin Login - ISU SmartEnroll</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;

            font-family:
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            color: #1f2a24;

            background: #00552f;
        }

        header {
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #e5e9e6;
        }

        .header-content {
            width: min(1100px, calc(100% - 30px));
            min-height: 76px;
            margin: auto;

            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-logos {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-shrink: 0;
        }

        .header-logos img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .header-logos .smart-logo {
            width: 46px;
            height: 46px;
        }

        .header-text h2 {
            color: #006b3c;
            font-size: 18px;
            font-weight: 700;
        }

        .header-text p {
            color: #68736d;
            font-size: 12px;
            margin-top: 2px;
        }

        .login-area {
            flex: 1;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px 15px;
        }

        .login-box {
            width: 100%;
            max-width: 390px;

            background: #ffffff;

            padding: 32px;

            border-radius: 14px;

            box-shadow:
                0 15px 40px rgba(0, 0, 0, 0.22);
        }

        .login-brand {
            text-align: center;
            margin-bottom: 24px;
        }

        .login-brand img {
            width: 130px;
            height: 130px;

            object-fit: contain;

            margin-bottom: 10px;
        }

        .login-brand h1 {
            color: #006b3c;

            font-size: 24px;
            font-weight: 700;
        }

        .login-brand p {
            color: #68736d;

            font-size: 12px;

            margin-top: 5px;
        }

        .error {
            background: #fff3f3;

            border: 1px solid #e4b4b4;

            color: #a52d2d;

            border-radius: 7px;

            padding: 10px 12px;

            margin-bottom: 17px;

            font-size: 13px;

            text-align: center;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;

            color: #26342c;

            font-size: 13px;
            font-weight: 600;

            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;

            padding: 12px 13px;

            border: 1px solid #cbd5ce;

            border-radius: 7px;

            background: #ffffff;

            color: #1f2a24;

            font-size: 14px;

            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }

        .form-group input:hover {
            border-color: #9eb8a8;
        }

        .form-group input:focus {
            outline: none;

            border-color: #006b3c;

            box-shadow:
                0 0 0 3px rgba(0, 107, 60, 0.10);
        }

        .login-button {
            width: 100%;

            padding: 12px;

            margin-top: 4px;

            border: none;

            border-radius: 7px;

            background: #006b3c;

            color: #ffffff;

            font-size: 14px;
            font-weight: 600;

            cursor: pointer;

            transition:
                background 0.2s,
                transform 0.1s;
        }

        .login-button:hover {
            background: #00552f;
        }

        .login-button:active {
            transform: translateY(1px);
        }

        .back-home {
            display: block;

            text-align: center;

            margin-top: 18px;

            color: #006b3c;

            font-size: 12px;
            font-weight: 600;

            text-decoration: none;
        }

        .back-home:hover {
            text-decoration: underline;
        }

        footer {
            padding: 14px;

            text-align: center;

            color: rgba(255, 255, 255, 0.9);

            font-size: 11px;
        }

        @media (max-width: 600px) {

            .header-content {
                width: calc(100% - 20px);
                min-height: 68px;
            }

            .header-logos {
                gap: 5px;
            }

            .header-logos img {
                width: 34px;
                height: 34px;
            }

            .header-logos .smart-logo {
                width: 38px;
                height: 38px;
            }

            .header-text h2 {
                font-size: 15px;
            }

            .header-text p {
                font-size: 10px;
            }

            .login-area {
                padding: 20px 12px;
            }

            .login-box {
                padding: 26px 21px;
                border-radius: 12px;
            }

            .login-brand img {
                width: 52px;
                height: 52px;
            }

            .login-brand h1 {
                font-size: 22px;
            }
        }

    </style>

</head>

<body>

<header>

    <div class="header-content">

        <div class="header-logos">

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
                alt="ISU SmartEnroll Logo"
                class="smart-logo"
            >

        </div>

        <div class="header-text">

            <h2>
                Isabela State University
            </h2>

            <p>
                Cauayan Campus
            </p>

        </div>

    </div>

</header>

<main class="login-area">

    <div class="login-box">

        <div class="login-brand">

            <img
                src="assets/smart-enroll-logo.png"
                alt="ISU SmartEnroll Logo"
            >

            <h1>
                Admin Login
            </h1>

            <p>
                ISU SmartEnroll Administration
            </p>

        </div>

        <?php if ($error !== ""): ?>

            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autocomplete="username"
                    placeholder="Enter your username"
                    value="<?= htmlspecialchars($_POST["username"] ?? "") ?>"
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                >

            </div>

            <button
                type="submit"
                class="login-button"
            >
                Login
            </button>

        </form>

        <a
            href="index.php"
            class="back-home"
        >
            ← Back to Home
        </a>

    </div>

</main>

<footer>

    ISU SmartEnroll •
    Isabela State University - Cauayan Campus

</footer>

</body>

</html>