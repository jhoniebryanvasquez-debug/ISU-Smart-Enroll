<?php

session_start();
require_once "db.php";

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("Location: index.php");
    exit;
}

$error = "";

$full_name = "";
$username = "";
$email = "";
$student_number = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $student_number = trim($_POST["student_number"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $full_name === "" ||
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        /*
         * Check existing username/email.
         */

        if ($student_number !== "") {

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                   OR email = ?
                   OR student_number = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "sss",
                $username,
                $email,
                $student_number
            );

        } else {

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                   OR email = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "ss",
                $username,
                $email
            );
        }

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $error = "Username, email, or student number is already registered.";

            $stmt->close();

        } else {

            $stmt->close();

            /*
             * Generate Applicant ID.
             */

            do {

                $applicant_id =
                    "APP-" .
                    date("Y") .
                    "-" .
                    str_pad(
                        random_int(1, 99999),
                        5,
                        "0",
                        STR_PAD_LEFT
                    );

                $check = $conn->prepare("
                    SELECT id
                    FROM users
                    WHERE applicant_id = ?
                    LIMIT 1
                ");

                $check->bind_param(
                    "s",
                    $applicant_id
                );

                $check->execute();

                $check_result = $check->get_result();

                $exists = $check_result->num_rows > 0;

                $check->close();

            } while ($exists);


            /*
             * Hash password.
             */

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /*
             * Insert student account.
             */

            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    applicant_id,
                    username,
                    password,
                    full_name,
                    student_number,
                    role,
                    email,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    NULLIF(?, ''),
                    'Student',
                    ?,
                    'Active'
                )
            ");

            $stmt->bind_param(
                "ssssss",
                $applicant_id,
                $username,
                $hashed_password,
                $full_name,
                $student_number,
                $email
            );

            if ($stmt->execute()) {

                $stmt->close();

                ?>

                <!DOCTYPE html>
                <html lang="en">

                <head>

                    <meta charset="UTF-8">

                    <meta
                        name="viewport"
                        content="width=device-width, initial-scale=1.0"
                    >

                    <title>Registration Successful | ISU SmartEnroll</title>

                    <style>

                        * {
                            box-sizing: border-box;
                            margin: 0;
                            padding: 0;
                        }

                        body {

                            min-height: 100vh;

                            display: flex;
                            align-items: center;
                            justify-content: center;

                            font-family:
                                "Segoe UI",
                                Arial,
                                sans-serif;

                            background:
                                linear-gradient(
                                    rgba(0,55,35,.75),
                                    rgba(0,35,25,.80)
                                ),
                                url("assets/background.jpg")
                                center/cover no-repeat fixed;

                            padding: 20px;
                        }

                        .success-card {

                            width: min(94vw, 480px);

                            background: rgba(
                                255,
                                255,
                                255,
                                .97
                            );

                            border-radius: 25px;

                            padding: 40px;

                            text-align: center;

                            box-shadow:
                                0 25px 70px
                                rgba(0,0,0,.35);

                            animation:
                                cardIn .7s
                                cubic-bezier(.2,.8,.2,1);
                        }

                        @keyframes cardIn {

                            from {

                                opacity: 0;

                                transform:
                                    translateY(35px)
                                    scale(.95);
                            }

                            to {

                                opacity: 1;

                                transform:
                                    translateY(0)
                                    scale(1);
                            }
                        }

                        .success-logo {

                            width: 75px;
                            height: 75px;

                            object-fit: contain;

                            margin-bottom: 15px;
                        }

                        .success-card h1 {

                            color: #075b39;

                            font-size: 25px;

                            margin-bottom: 10px;
                        }

                        .success-card p {

                            color: #666;

                            font-size: 14px;

                            line-height: 1.6;

                            margin-bottom: 18px;
                        }

                        .applicant-box {

                            background: #f0f8f4;

                            border:
                                1px solid #cce5d8;

                            border-radius: 13px;

                            padding: 17px;

                            margin: 20px 0;
                        }

                        .applicant-box span {

                            display: block;

                            color: #777;

                            font-size: 12px;

                            margin-bottom: 5px;
                        }

                        .applicant-box strong {

                            color: #075b39;

                            font-size: 23px;

                            letter-spacing: 1px;
                        }

                        .login-btn {

                            display: block;

                            width: 100%;

                            padding: 13px;

                            border-radius: 11px;

                            background:
                                linear-gradient(
                                    135deg,
                                    #087b49,
                                    #075b39
                                );

                            color: white;

                            text-decoration: none;

                            font-weight: 700;

                            transition: .25s;
                        }

                        .login-btn:hover {

                            transform: translateY(-2px);

                            box-shadow:
                                0 9px 20px
                                rgba(7,91,57,.25);
                        }

                    </style>

                </head>

                <body>

                    <div class="success-card">

                        <img
                            src="assets/smart-enroll-logo.png?v=8"
                            alt="SmartEnroll"
                            class="success-logo"
                        >

                        <h1>
                            Registration Successful!
                        </h1>

                        <p>
                            Your SmartEnroll student account
                            has been created successfully.
                        </p>

                        <div class="applicant-box">

                            <span>
                                Your Applicant ID
                            </span>

                            <strong>
                                <?= htmlspecialchars($applicant_id) ?>
                            </strong>

                        </div>

                        <p>
                            Please save your Applicant ID.
                            You can use it together with your
                            password to log in.
                        </p>

                        <a
                            href="login.php"
                            class="login-btn"
                        >
                            Go to Student Login
                        </a>

                    </div>

                </body>

                </html>

                <?php

                exit;

            } else {

                $error =
                    "Registration failed. Please try again.";

                $stmt->close();
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

<title>Register | ISU SmartEnroll</title>

</head>

<body>

<?php if ($error !== ""): ?>

<script>

alert(
    <?= json_encode($error) ?>
);

window.location.href = "login.php";

</script>

<?php else: ?>

<script>

window.location.href = "login.php";

</script>

<?php endif; ?>

</body>

</html>