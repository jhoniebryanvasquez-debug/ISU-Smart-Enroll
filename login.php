<?php

session_start();
require_once "db.php";

if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login === "" || $password === "") {

        $error = "Please enter your login credentials.";

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                applicant_id,
                username,
                password,
                full_name,
                role,
                email,
                status
            FROM users
            WHERE applicant_id = ?
               OR username = ?
               OR email = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "sss",
            $login,
            $login,
            $login
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (strtolower($user["status"]) !== "active") {

                $error = "Your account is currently inactive.";

            } elseif (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["logged_in"] = true;
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["applicant_id"] = $user["applicant_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["login_time"] = time();

                header("Location: index.php");
                exit;

            } else {

                $error = "Incorrect password.";
            }

        } else {

            $error = "Account not found.";
        }

        $stmt->close();
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

<title>Login | ISU SmartEnroll</title>

<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html,
body {
    min-height: 100%;
}

body {

    min-height: 100vh;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        linear-gradient(
            rgba(0, 55, 35, 0.72),
            rgba(0, 35, 25, 0.80)
        ),
        url("assets/background.jpg")
        center / cover no-repeat fixed;

    overflow-x: hidden;
}


/* =========================================================
   ANIMATED BACKGROUND
========================================================= */

body::before,
body::after {

    content: "";

    position: fixed;

    width: 420px;
    height: 420px;

    border-radius: 50%;

    filter: blur(85px);

    opacity: .22;

    pointer-events: none;

    animation:
        floatBlob
        10s
        ease-in-out
        infinite
        alternate;

    z-index: 0;
}

body::before {

    background: #19a463;

    top: -160px;
    left: -160px;
}

body::after {

    background: #0b7045;

    right: -160px;
    bottom: -170px;

    animation-delay: 2s;
}

@keyframes floatBlob {

    from {
        transform:
            translate(0, 0)
            scale(1);
    }

    to {
        transform:
            translate(45px, 35px)
            scale(1.12);
    }
}


/* =========================================================
   HEADER
   SAME STYLE AS INDEX.PHP
========================================================= */

.site-header {

    width: 100%;

    height: 104px;

    background: #ffffff;

    position: relative;

    z-index: 10;

    box-shadow:
        0 2px 10px
        rgba(0, 0, 0, .10);
}


/* LEFT BRAND */

.header-left {

    position: absolute;

    left: 34px;
    top: 50%;

    transform:
        translateY(-50%);

    display: flex;

    align-items: center;

    gap: 14px;
}

.header-left img {

    width: 65px;
    height: 65px;

    object-fit: contain;
}

.header-left-text {

    line-height: 1.2;
}

.header-left-text h2 {

    color: #075b39;

    font-size: 17px;

    font-weight: 700;

    margin-bottom: 5px;
}

.header-left-text p {

    color: #777;

    font-size: 12px;

    margin: 0;
}


/* CENTER SYSTEM LOGO */

.header-center {

    position: absolute;

    left: 50%;
    top: 50%;

    transform:
        translate(-50%, -50%);

    display: flex;

    align-items: center;

    justify-content: center;
}

.header-center img {

    width: 110px;
    height: 110px;

    object-fit: contain;
}


/* RIGHT CCSI(C) BRAND */

.header-right {

    position: absolute;

    right: 34px;
    top: 50%;

    transform:
        translateY(-50%);

    display: flex;

    align-items: center;

    gap: 14px;

    text-align: right;
}

.header-right-text {

    line-height: 1.2;
}

.header-right-text h3 {

    color: #075b39;

    font-size: 14px;

    font-weight: 700;

    margin-bottom: 4px;
}

.header-right-text p {

    color: #777;

    font-size: 10px;

    line-height: 1.3;

    margin: 0;
}

.header-right img {

    width: 68px;
    height: 68px;

    object-fit: contain;
}


/* =========================================================
   MAIN
========================================================= */

.login-area {

    min-height:
        calc(100vh - 104px);

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    padding:
        35px
        20px
        45px;

    position: relative;

    z-index: 2;
}


/* =========================================================
   FLIP SCENE
========================================================= */

.flip-scene {

    width: min(94vw, 470px);

    height: 720px;

    perspective: 1500px;

    position: relative;
}

.flip-card {

    width: 100%;
    height: 100%;

    position: relative;

    transform-style: preserve-3d;

    transition:
        transform
        .85s
        cubic-bezier(.20, .75, .25, 1);
}

.flip-card.flipped {

    transform: rotateY(180deg);
}


/* =========================================================
   CARD FACES
========================================================= */

.card-face {

    position: absolute;

    inset: 0;

    width: 100%;
    height: 100%;

    padding: 34px;

    background:
        rgba(255, 255, 255, .975);

    border-radius: 26px;

    box-shadow:
        0 25px 70px
        rgba(0, 0, 0, .36),

        0 0 0 1px
        rgba(255,255,255,.18);

    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;

    overflow: hidden;

    display: flex;

    flex-direction: column;
}

.login-face {

    transform: rotateY(0deg);
}

.register-face {

    transform: rotateY(180deg);
}


/* =========================================================
   SYSTEM LOGO INSIDE CARD
   ONLY SMARTENROLL LOGO
========================================================= */

.card-logo {

    width: 120px;
    height: 120px;

    object-fit: contain;

    display: block;

    margin:
        0 auto
        8px;
}

.card-brand {

    text-align: center;

    flex-shrink: 0;

    margin-bottom: 20px;
}

.card-brand h1 {

    color: #075b39;

    font-size: 22px;

    line-height: 1.2;

    margin-bottom: 4px;
}

.card-brand p {

    color: #777;

    font-size: 12px;

    line-height: 1.4;
}


/* =========================================================
   FORM TITLE
========================================================= */

.form-title {

    text-align: center;

    flex-shrink: 0;

    margin-bottom: 19px;
}

.form-title h2 {

    color: #075b39;

    font-size: 25px;

    line-height: 1.2;

    margin-bottom: 5px;
}

.form-title span {

    color: #777;

    font-size: 12px;
}


/* =========================================================
   ERROR
========================================================= */

.error {

    background: #fff1f1;

    color: #b42318;

    border:
        1px solid
        #f0c5c5;

    border-radius: 10px;

    padding:
        10px
        12px;

    font-size: 12px;

    line-height: 1.4;

    margin-bottom: 14px;

    flex-shrink: 0;
}


/* =========================================================
   INPUTS
========================================================= */

.input-group {

    margin-bottom: 15px;

    flex-shrink: 0;
}

.input-group label {

    display: block;

    color: #444;

    font-size: 12px;

    font-weight: 600;

    margin-bottom: 6px;
}

.input-group input {

    width: 100%;

    height: 46px;

    padding:
        0
        13px;

    border:
        1px solid
        #d6ddd9;

    border-radius: 10px;

    background: #fafcfb;

    color: #333;

    font-family: inherit;

    font-size: 13px;

    outline: none;

    transition:
        border-color .2s,
        box-shadow .2s,
        background .2s;
}

.input-group input::placeholder {

    color: #a3a3a3;
}

.input-group input:focus {

    border-color: #07804c;

    background: #ffffff;

    box-shadow:
        0 0 0 4px
        rgba(7,128,76,.09);
}


/* =========================================================
   REGISTER SIDE
========================================================= */

.register-face .card-logo {

    width: 90px;
    height: 90px;

    margin-bottom: 5px;
}

.register-face .card-brand {

    margin-bottom: 12px;
}

.register-face .card-brand h1 {

    font-size: 20px;
}

.register-face .card-brand p {

    font-size: 11px;
}

.register-face .form-title {

    margin-bottom: 12px;
}

.register-face .form-title h2 {

    font-size: 23px;

    margin-bottom: 3px;
}

.register-face .form-title span {

    font-size: 11px;
}

.register-face .input-group {

    margin-bottom: 9px;
}

.register-face .input-group label {

    font-size: 11px;

    margin-bottom: 4px;
}

.register-face .input-group input {

    height: 39px;

    font-size: 12px;

    border-radius: 9px;
}


/* =========================================================
   BUTTON
========================================================= */

.primary-btn {

    width: 100%;

    height: 47px;

    margin-top: 2px;

    border: none;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #087b49,
            #075b39
        );

    color: #ffffff;

    font-family: inherit;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    transition:
        transform .2s,
        box-shadow .2s;
}

.primary-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 9px 22px
        rgba(7,91,57,.27);
}

.primary-btn:active {

    transform:
        translateY(0);
}


/* =========================================================
   LINKS
========================================================= */

.secondary-action {

    text-align: center;

    color: #777;

    font-size: 12px;

    margin-top: 16px;

    flex-shrink: 0;
}

.flip-link {

    color: #087b49;

    font-weight: 700;

    text-decoration: none;

    cursor: pointer;

    transition: color .2s;
}

.flip-link:hover {

    color: #075b39;

    text-decoration: underline;
}


/* =========================================================
   FOOTER INSIDE CARD
========================================================= */

.card-footer {

    margin-top: auto;

    padding-top: 15px;

    text-align: center;

    color: #a0a0a0;

    font-size: 10px;

    line-height: 1.4;

    flex-shrink: 0;
}

.register-face .secondary-action {

    margin-top: 12px;
}

.register-face .card-footer {

    padding-top: 10px;
}


/* =========================================================
   MOBILE HEADER
========================================================= */

@media (max-width: 850px) {

    .site-header {

        height: 90px;
    }

    .header-left {

        left: 18px;
    }

    .header-left img {

        width: 53px;
        height: 53px;
    }

    .header-left-text h2 {

        font-size: 14px;
    }

    .header-left-text p {

        font-size: 10px;
    }

    .header-center img {

        width: 110px;
        height: 110px;
    }

    .header-right {

        right: 18px;
    }

    .header-right-text {

        display: none;
    }

    .header-right img {

        width: 54px;
        height: 54px;
    }

    .login-area {

        min-height:
            calc(100vh - 90px);
    }
}


/* =========================================================
   MOBILE CARD
========================================================= */

@media (max-width: 520px) {

    .site-header {

        height: 78px;
    }

    .header-left {

        left: 12px;

        gap: 7px;
    }

    .header-left img {

        width: 43px;
        height: 43px;
    }

    .header-left-text h2 {

        font-size: 11px;

        margin-bottom: 2px;
    }

    .header-left-text p {

        font-size: 8px;
    }

    .header-center img {

        width: 63px;
        height: 63px;
    }

    .header-right {

        right: 12px;
    }

    .header-right img {

        width: 44px;
        height: 44px;
    }

    .login-area {

        min-height:
            calc(100vh - 78px);

        padding:
            25px
            12px
            35px;
    }

    .flip-scene {

        width: 94vw;

        height: 700px;
    }

    .card-face {

        padding:
            27px
            22px;

        border-radius: 22px;
    }

    .card-logo {

        width: 78px;
        height: 78px;
    }

    .card-brand h1 {

        font-size: 20px;
    }

    .form-title h2 {

        font-size: 23px;
    }

    .register-face .card-logo {

        width: 60px;
        height: 60px;
    }

    .register-face .input-group {

        margin-bottom: 8px;
    }
}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-height: 760px) and (max-width: 520px) {

    .flip-scene {

        height: 670px;
    }

    .card-face {

        padding:
            22px
            20px;
    }

    .card-brand {

        margin-bottom: 12px;
    }

    .input-group {

        margin-bottom: 11px;
    }

    .register-face .input-group {

        margin-bottom: 7px;
    }
}

</style>

</head>

<body>


<!-- =======================================================
     HEADER
     SAME STRUCTURE / STYLE AS INDEX.PHP
======================================================== -->

<header class="site-header">


    <!-- LEFT -->
    <div class="header-left">

        <img
            src="assets/isu-logo.png"
            alt="Isabela State University"
        >

        <div class="header-left-text">

            <h2>
                Isabela State University
            </h2>

            <p>
                Cauayan Campus
            </p>

        </div>

    </div>


    <!-- CENTER -->
    <div class="header-center">

        <img
            src="assets/smart-enroll-logo.png?v=8"
            alt="SmartEnroll"
        >

    </div>


    <!-- RIGHT -->
    <div class="header-right">

        <div class="header-right-text">

            <h3>
                College of Computing Studies
            </h3>

            <p>
                Information and Communication Technology<br>
                Isabela State University
            </p>

        </div>

        <img
            src="assets/ccsict-logo.png"
            alt="CCSICT"
        >

    </div>

</header>


<!-- =======================================================
     LOGIN AREA
======================================================== -->

<main class="login-area">

    <div class="flip-scene">

        <div
            class="flip-card"
            id="flipCard"
        >


            <!-- =============================================
                 LOGIN SIDE
            ============================================== -->

            <div class="card-face login-face">


                <!-- SYSTEM LOGO ONLY -->

                <img
                    src="assets/smart-enroll-logo.png?v=8"
                    alt="SmartEnroll"
                    class="card-logo"
                >


                <div class="card-brand">

                    <h1>
                        ISU SmartEnroll
                    </h1>

                    <p>
                        Student Enrollment Guidance System
                    </p>

                </div>


                <div class="form-title">

                    <h2>
                        Welcome Back
                    </h2>

                    <span>
                        Sign in to continue to SmartEnroll
                    </span>

                </div>


                <?php if ($error !== ""): ?>

                    <div class="error">

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    autocomplete="off"
                >


                    <div class="input-group">

                        <label>
                            Applicant ID, Username, or Email
                        </label>

                        <input
                            type="text"
                            name="login"
                            placeholder="Enter your Applicant ID, username, or email"
                            required
                            autofocus
                        >

                    </div>


                    <div class="input-group">

                        <label>
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="primary-btn"
                    >
                        Login
                    </button>

                </form>


                <div class="secondary-action">

                    Don't have an account?

                    <a
                        href="#"
                        class="flip-link"
                        onclick="
                            flipToRegister();
                            return false;
                        "
                    >
                        Create an account
                    </a>

                </div>


                <div class="card-footer">

                    ISU SmartEnroll •
                    Student Enrollment Guidance System

                </div>

            </div>


            <!-- =============================================
                 REGISTER SIDE
            ============================================== -->

            <div class="card-face register-face">


                <!-- SYSTEM LOGO ONLY -->

                <img
                    src="assets/smart-enroll-logo.png?v=8"
                    alt="SmartEnroll"
                    class="card-logo"
                >


                <div class="card-brand">

                    <h1>
                        ISU SmartEnroll
                    </h1>

                    <p>
                        Student Enrollment Guidance System
                    </p>

                </div>


                <div class="form-title">

                    <h2>
                        Create Account
                    </h2>

                    <span>
                        Register to access SmartEnroll
                    </span>

                </div>


                <form
                    method="POST"
                    action="register.php"
                    autocomplete="off"
                >


                    <div class="input-group">

                        <label>
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            placeholder="Enter your full name"
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label>
                            Username
                        </label>

                        <input
                            type="text"
                            name="username"
                            placeholder="Create a username"
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label>

                            Student Number

                            <span
                                style="
                                    font-weight:400;
                                    color:#999;
                                "
                            >
                                (Optional)
                            </span>

                        </label>

                        <input
                            type="text"
                            name="student_number"
                            placeholder="Leave blank if you don't have one"
                        >

                    </div>


                    <div class="input-group">

                        <label>
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            placeholder="Minimum 8 characters"
                            minlength="8"
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label>
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            minlength="8"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="primary-btn"
                    >
                        Create Account
                    </button>

                </form>


                <div class="secondary-action">

                    Already have an account?

                    <a
                        href="#"
                        class="flip-link"
                        onclick="
                            flipToLogin();
                            return false;
                        "
                    >
                        Back to Login
                    </a>

                </div>


                <div class="card-footer">

                    Your Applicant ID will be generated
                    after registration.

                </div>

            </div>

        </div>

    </div>

</main>


<script>

/* =========================================================
   FLIP FUNCTIONS
========================================================= */

const flipCard =
    document.getElementById("flipCard");


function flipToRegister() {

    flipCard.classList.add("flipped");

}


function flipToLogin() {

    flipCard.classList.remove("flipped");

}

</script>

</body>

</html>