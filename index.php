<?php

session_start();


/*
|--------------------------------------------------------------------------
| AUTHENTICATION CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["logged_in"]) ||
    $_SESSION["logged_in"] !== true
) {

    header("Location: login.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["logout"])
) {

    $_SESSION = [];


    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );

    }


    session_destroy();

    header("Location: login.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/

$full_name = $_SESSION["full_name"] ?? "Student";

$applicant_id = $_SESSION["applicant_id"] ?? "";


$display_name = htmlspecialchars(
    $full_name,
    ENT_QUOTES,
    "UTF-8"
);


$display_applicant_id = htmlspecialchars(
    $applicant_id,
    ENT_QUOTES,
    "UTF-8"
);

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
        ISU SmartEnroll | Cauayan Campus
    </title>


    <link
        rel="icon"
        href="assets/isu-logo.png"
    >


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                #f5f9f7;

            color:
                #164f3a;

            overflow-x:
                hidden;

        }


        /* =========================================================
           HEADER
        ========================================================= */

        .top-header {

            width: 100%;

            background: #ffffff;

            border-top:
                4px solid #0a7747;

            min-height:
                165px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            padding:
                25px 40px;

            gap:
                25px;

        }


        /* =========================================================
           UNIVERSITY BRAND
        ========================================================= */

        .university-brand {

            display:
                flex;

            align-items:
                center;

            gap:
                20px;

            flex:
                1;

        }


        .university-brand img {

            width:
                92px;

            height:
                92px;

            object-fit:
                contain;

        }


        .university-text h1 {

            font-size:
                30px;

            line-height:
                1.2;

            color:
                #075f3c;

            font-weight:
                800;

        }


        .university-text p {

            margin-top:
                8px;

            font-size:
                17px;

            color:
                #6c7d75;

        }


        /* =========================================================
           SMART ENROLL LOGO
        ========================================================= */

        .smart-enroll-brand {

            display:
                flex;

            justify-content:
                center;

            align-items:
                center;

            flex:
                1;

        }


        .smart-enroll-brand img {

            width:
                270px;

            max-width:
                100%;

            height:
                auto;

            object-fit:
                contain;

        }


        /* =========================================================
           CCSI(C) / COLLEGE BRAND
        ========================================================= */

        .college-brand {

            display:
                flex;

            align-items:
                center;

            justify-content:
                flex-end;

            gap:
                22px;

            flex:
                1;

        }


        .college-text {

            text-align:
                left;

        }


        .college-text h2 {

            color:
                #075f3c;

            font-size:
                23px;

            line-height:
                1.25;

            font-weight:
                800;

        }


        .college-text p {

            color:
                #6d7c76;

            font-size:
                15px;

            line-height:
                1.45;

            margin-top:
                6px;

        }


        .college-brand img {

            width:
                88px;

            height:
                88px;

            object-fit:
                contain;

        }


        /* =========================================================
           NAVBAR
        ========================================================= */

        .navbar {

            position:
                sticky;

            top:
                0;

            z-index:
                1000;

            width:
                100%;

            min-height:
                79px;

            background:
                #056344;

            display:
                flex;

            align-items:
                center;

            padding:
                0 20px;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.08);

        }


        .nav-left {

            display:
                flex;

            align-items:
                stretch;

            height:
                79px;

        }


        .nav-link {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            padding:
                0 25px;

            color:
                #ffffff;

            text-decoration:
                none;

            font-size:
                17px;

            font-weight:
                700;

            position:
                relative;

            transition:
                0.2s;

        }


        .nav-link:hover {

            background:
                rgba(255,255,255,0.08);

        }


        .nav-link.active {

            background:
                rgba(255,255,255,0.08);

        }


        .nav-link.active::after {

            content:
                "";

            position:
                absolute;

            left:
                0;

            right:
                0;

            bottom:
                0;

            height:
                4px;

            background:
                #72df36;

        }


        .nav-icon {

            font-size:
                22px;

            line-height:
                1;

        }


        /* =========================================================
           ACCOUNT AREA
        ========================================================= */

        .nav-account {

            margin-left:
                auto;

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

        }


        .account-info {

            color:
                #ffffff;

            text-align:
                right;

            line-height:
                1.25;

            margin-right:
                4px;

        }


        .account-name {

            font-size:
                14px;

            font-weight:
                800;

            max-width:
                210px;

            white-space:
                nowrap;

            overflow:
                hidden;

            text-overflow:
                ellipsis;

        }


        .account-id {

            font-size:
                11px;

            color:
                rgba(255,255,255,0.72);

            margin-top:
                3px;

        }


        /* =========================================================
           RED LOGOUT
        ========================================================= */

        .logout-button {

            height:
                50px;

            padding:
                0 22px;

            border:
                1px solid #dc3545;

            border-radius:
                11px;

            background:
                #dc3545;

            color:
                #ffffff;

            font-size:
                15px;

            font-weight:
                700;

            cursor:
                pointer;

            transition:
                0.2s;

        }


        .logout-button:hover {

            background:
                #b02a37;

            border-color:
                #b02a37;

            transform:
                translateY(-1px);

        }


        .logout-button:active {

            transform:
                translateY(0);

        }


        /* =========================================================
           HERO
        ========================================================= */

        .hero {

            min-height:
                610px;

            position:
                relative;

            display:
                flex;

            align-items:
                center;

            background:

                linear-gradient(
                    90deg,
                    rgba(250,255,253,0.99) 0%,
                    rgba(248,253,251,0.96) 37%,
                    rgba(248,253,251,0.76) 52%,
                    rgba(248,253,251,0.10) 75%,
                    rgba(248,253,251,0) 100%
                ),

                url("assets/background.jpg");

            background-size:
                cover;

            background-position:
                center;

            background-repeat:
                no-repeat;

        }


        .hero-content {

            width:
                100%;

            max-width:
                1500px;

            margin:
                0 auto;

            padding:
                70px 30px;

        }


        .campus-badge {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                10px;

            padding:
                12px 18px;

            border-radius:
                30px;

            background:
                #e3f6ed;

            border:
                1px solid #ccebdc;

            color:
                #087547;

            font-size:
                14px;

            font-weight:
                800;

            margin-bottom:
                28px;

        }


        .campus-badge span {

            width:
                9px;

            height:
                9px;

            border-radius:
                50%;

            background:
                #67d437;

        }


        .hero h1 {

            max-width:
                850px;

            font-size:
                clamp(48px, 5vw, 72px);

            line-height:
                1.08;

            letter-spacing:
                -2px;

            color:
                #056343;

            font-weight:
                850;

        }


        .hero h1 span {

            color:
                #3da634;

        }


        .hero-description {

            max-width:
                800px;

            margin-top:
                25px;

            color:
                #52746a;

            font-size:
                20px;

            line-height:
                1.8;

        }


        .hero-actions {

            display:
                flex;

            gap:
                16px;

            margin-top:
                36px;

            flex-wrap:
                wrap;

        }


        .primary-button,
        .secondary-button {

            min-width:
                193px;

            height:
                67px;

            border-radius:
                12px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            padding:
                0 28px;

            font-size:
                16px;

            font-weight:
                800;

            text-decoration:
                none;

            transition:
                0.2s;

        }


        .primary-button {

            background:
                #07834b;

            color:
                #ffffff;

            box-shadow:
                0 12px 25px rgba(0,131,75,0.18);

        }


        .primary-button:hover {

            background:
                #056c3d;

            transform:
                translateY(-2px);

        }


        .secondary-button {

            background:
                #ffffff;

            color:
                #075f3c;

            border:
                1px solid #d2dfd9;

        }


        .secondary-button:hover {

            border-color:
                #07834b;

            transform:
                translateY(-2px);

        }


        /* =========================================================
           QUICK INFORMATION
        ========================================================= */

        .quick-section {

            background:
                #f5f9f7;

            padding:
                70px 30px;

        }


        .quick-grid {

            max-width:
                1500px;

            margin:
                0 auto;

            display:
                grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap:
                24px;

        }


        .info-card {

            background:
                #ffffff;

            border:
                1px solid #e0ebe5;

            border-radius:
                18px;

            padding:
                32px 29px;

            min-height:
                260px;

            box-shadow:
                0 10px 25px rgba(0,0,0,0.04);

            transition:
                0.2s;

        }


        .info-card:hover {

            transform:
                translateY(-4px);

            box-shadow:
                0 15px 30px rgba(0,0,0,0.07);

        }


        .info-icon {

            width:
                58px;

            height:
                58px;

            border-radius:
                15px;

            background:
                #e7f6ee;

            color:
                #087547;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                25px;

            margin-bottom:
                28px;

        }


        .info-card h3 {

            color:
                #075f3c;

            font-size:
                21px;

            margin-bottom:
                14px;

        }


        .info-card p {

            color:
                #6c8078;

            font-size:
                16px;

            line-height:
                1.7;

            margin-bottom:
                26px;

        }


        .info-link {

            color:
                #087547;

            text-decoration:
                none;

            font-size:
                15px;

            font-weight:
                800;

        }


        .info-link:hover {

            text-decoration:
                underline;

        }


        /* =========================================================
           CTA
        ========================================================= */

        .cta {

            background:
                #056344;

            color:
                #ffffff;

            padding:
                75px 30px;

        }


        .cta-inner {

            max-width:
                1500px;

            margin:
                0 auto;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                40px;

        }


        .cta h2 {

            font-size:
                38px;

            line-height:
                1.25;

            margin-bottom:
                16px;

            color:
                #ffffff;

        }


        .cta p {

            color:
                rgba(255,255,255,0.78);

            font-size:
                17px;

            line-height:
                1.6;

        }


        .cta-actions {

            display:
                flex;

            gap:
                13px;

            flex-shrink:
                0;

            align-items:
                center;

        }


        .cta-button {

            min-width:
                170px;

            height:
                60px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            padding:
                0 24px;

            border-radius:
                11px;

            text-decoration:
                none;

            font-weight:
                800;

            font-size:
                15px;

            transition:
                0.2s;

        }


        .cta-primary {

            background:
                #ffffff;

            color:
                #056344;

        }


        .cta-primary:hover {

            transform:
                translateY(-2px);

        }


        .cta-logout {

            border:
                1px solid #dc3545;

            background:
                #dc3545;

            color:
                #ffffff;

            cursor:
                pointer;

        }


        .cta-logout:hover {

            background:
                #b02a37;

            border-color:
                #b02a37;

            transform:
                translateY(-2px);

        }


        /* =========================================================
           FOOTER
        ========================================================= */

        .footer {

            background:
                #034a32;

            color:
                #ffffff;

            padding:
                36px 30px;

        }


        .footer-inner {

            max-width:
                1500px;

            margin:
                0 auto;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                30px;

        }


        .footer-brand {

            display:
                flex;

            align-items:
                center;

            gap:
                15px;

        }


        .footer-brand img {

            width:
                54px;

            height:
                54px;

            object-fit:
                contain;

        }


        .footer-brand strong {

            display:
                block;

            font-size:
                15px;

            margin-bottom:
                4px;

        }


        .footer-brand span {

            color:
                rgba(255,255,255,0.62);

            font-size:
                13px;

        }


        .footer-copy {

            color:
                rgba(255,255,255,0.58);

            font-size:
                13px;

        }


        /* =========================================================
           RESPONSIVE - 1200
        ========================================================= */

        @media (max-width: 1200px) {

            .top-header {

                padding:
                    22px 25px;

            }


            .university-text h1 {

                font-size:
                    24px;

            }


            .university-text p {

                font-size:
                    15px;

            }


            .college-text h2 {

                font-size:
                    18px;

            }


            .college-text p {

                font-size:
                    13px;

            }


            .nav-link {

                padding:
                    0 17px;

                font-size:
                    15px;

            }


            .quick-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        /* =========================================================
           RESPONSIVE - 900
        ========================================================= */

        @media (max-width: 900px) {

            .top-header {

                min-height:
                    auto;

                flex-wrap:
                    wrap;

                justify-content:
                    center;

            }


            .university-brand,
            .smart-enroll-brand,
            .college-brand {

                flex:
                    none;

            }


            .university-brand {

                justify-content:
                    center;

            }


            .college-brand {

                justify-content:
                    center;

            }


            .nav-left {

                overflow-x:
                    auto;

                width:
                    100%;

            }


            .nav-account {

                position:
                    absolute;

                right:
                    15px;

            }


            .account-info {

                display:
                    none;

            }


            .hero {

                background:

                    linear-gradient(
                        rgba(248,253,251,0.92),
                        rgba(248,253,251,0.92)
                    ),

                    url("assets/background.jpg")
                    center / cover;

            }


            .cta-inner {

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }

        }


        /* =========================================================
           RESPONSIVE - 650
        ========================================================= */

        @media (max-width: 650px) {

            .top-header {

                padding:
                    18px 15px;

                gap:
                    15px;

            }


            .university-brand {

                width:
                    100%;

                justify-content:
                    center;

            }


            .university-brand img {

                width:
                    65px;

                height:
                    65px;

            }


            .university-text h1 {

                font-size:
                    20px;

            }


            .university-text p {

                font-size:
                    13px;

            }


            .smart-enroll-brand {

                width:
                    100%;

            }


            .smart-enroll-brand img {

                width:
                    220px;

            }


            /*
            |--------------------------------------------------------------------------
            | KEEP CCSI(C) LOGO VISIBLE
            |--------------------------------------------------------------------------
            */

            .college-brand {

                display:
                    flex;

                width:
                    100%;

                justify-content:
                    center;

                align-items:
                    center;

                gap:
                    12px;

            }


            .college-text {

                text-align:
                    right;

            }


            .college-text h2 {

                font-size:
                    15px;

            }


            .college-text p {

                font-size:
                    11px;

            }


            .college-brand img {

                width:
                    60px;

                height:
                    60px;

            }


            .navbar {

                padding:
                    0 8px;

            }


            .nav-left {

                height:
                    67px;

            }


            .navbar {

                min-height:
                    67px;

            }


            .nav-link {

                height:
                    67px;

                padding:
                    0 14px;

                font-size:
                    13px;

                gap:
                    7px;

                white-space:
                    nowrap;

            }


            .nav-icon {

                font-size:
                    18px;

            }


            .logout-button {

                height:
                    43px;

                padding:
                    0 13px;

                font-size:
                    13px;

            }


            .hero {

                min-height:
                    570px;

            }


            .hero-content {

                padding:
                    55px 22px;

            }


            .campus-badge {

                font-size:
                    12px;

                padding:
                    10px 14px;

            }


            .hero h1 {

                font-size:
                    44px;

                letter-spacing:
                    -1px;

            }


            .hero-description {

                font-size:
                    16px;

                line-height:
                    1.65;

            }


            .hero-actions {

                flex-direction:
                    column;

            }


            .primary-button,
            .secondary-button {

                width:
                    100%;

            }


            .quick-section {

                padding:
                    45px 18px;

            }


            .quick-grid {

                grid-template-columns:
                    1fr;

            }


            .cta {

                padding:
                    55px 22px;

            }


            .cta h2 {

                font-size:
                    29px;

            }


            .cta-actions {

                width:
                    100%;

                flex-direction:
                    column;

                align-items:
                    stretch;

            }


            .cta-actions form {

                width:
                    100%;

            }


            .cta-button {

                width:
                    100%;

            }


            .footer-inner {

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }

        }

    </style>

</head>


<body>


<!-- ============================================================
     HEADER
============================================================= -->

<header class="top-header">


    <!-- UNIVERSITY -->

    <div class="university-brand">


        <img
            src="assets/isu-logo.png"
            alt="Isabela State University"
        >


        <div class="university-text">

            <h1>
                Isabela State University
            </h1>

            <p>
                Cauayan Campus
            </p>

        </div>


    </div>


    <!-- SMART ENROLL -->

    <div class="smart-enroll-brand">

        <img
            src="assets/smart-enroll-logo.png?v=8"
            alt="ISU SmartEnroll"
        >

    </div>


    <!-- CCSI(C) -->

    <div class="college-brand">


        <div class="college-text">

            <h2>
                College of Computing Studies
            </h2>

            <p>
                Information and Communication Technology<br>
                Isabela State University
            </p>

        </div>


        <img
            src="assets/ccsict-logo.png"
            alt="College of Computing Studies"
        >


    </div>


</header>



<!-- ============================================================
     NAVIGATION
============================================================= -->

<nav class="navbar">


    <div class="nav-left">


        <a
            href="index.php"
            class="nav-link active"
        >

            <span class="nav-icon">
                ⌂
            </span>

            Home

        </a>


        <a
            href="enrollment.php"
            class="nav-link"
        >

            <span class="nav-icon">
                □
            </span>

            Enrollment Guide

        </a>


        <a
            href="requirements.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ▤
            </span>

            Requirements

        </a>


        <a
            href="procedures.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ⚙
            </span>

            Procedures

        </a>


        <a
            href="announcements.php"
            class="nav-link"
        >

            <span class="nav-icon">
                ◁
            </span>

            Announcements

        </a>


    </div>


    <!-- =========================================================
         LOGGED-IN ACCOUNT
    ========================================================== -->

    <div class="nav-account">


        <div class="account-info">


            <div class="account-name">

                Hi,
                <?= $display_name ?>

            </div>


            <?php if ($applicant_id !== ""): ?>

                <div class="account-id">

                    <?= $display_applicant_id ?>

                </div>

            <?php endif; ?>


        </div>


        <form
            method="POST"
            action="index.php"
        >

            <input
                type="hidden"
                name="logout"
                value="1"
            >


            <button
                type="submit"
                class="logout-button"
            >

                ⇥ &nbsp; Logout

            </button>

        </form>


    </div>


</nav>



<!-- ============================================================
     HERO
============================================================= -->

<section class="hero">


    <div class="hero-content">


        <div class="campus-badge">

            <span></span>

            ISU CAUAYAN CAMPUS

        </div>


        <h1>

            Enrollment made

            <span>
                simpler.
            </span>

        </h1>


        <p class="hero-description">

            Your gateway to a simpler, smarter, and more
            convenient enrollment experience at Isabela State
            University – Cauayan Campus.

        </p>


        <div class="hero-actions">


            <a
                href="enrollment.php"
                class="primary-button"
            >

                Get Started
                &nbsp; →

            </a>


            <a
                href="requirements.php"
                class="secondary-button"
            >

                View Requirements

            </a>


        </div>


    </div>


</section>



<!-- ============================================================
     QUICK INFORMATION
============================================================= -->

<section class="quick-section">


    <div class="quick-grid">


        <!-- ENROLLMENT GUIDE -->

        <div class="info-card">


            <div class="info-icon">
                ▣
            </div>


            <h3>
                Enrollment Guide
            </h3>


            <p>

                Follow the step-by-step enrollment
                process and understand what you need
                to do before, during, and after
                enrollment.

            </p>


            <a
                href="enrollment.php"
                class="info-link"
            >

                View Guide →

            </a>


        </div>



        <!-- REQUIREMENTS -->

        <div class="info-card">


            <div class="info-icon">
                ▤
            </div>


            <h3>
                Requirements
            </h3>


            <p>

                View the documents and requirements
                needed for your enrollment at Isabela
                State University.

            </p>


            <a
                href="requirements.php"
                class="info-link"
            >

                View Requirements →

            </a>


        </div>



        <!-- PROCEDURES -->

        <div class="info-card">


            <div class="info-icon">
                ⚙
            </div>


            <h3>
                Procedures
            </h3>


            <p>

                Learn the enrollment procedures,
                important steps, and reminders to
                help you prepare.

            </p>


            <a
                href="procedures.php"
                class="info-link"
            >

                View Procedures →

            </a>


        </div>



        <!-- ANNOUNCEMENTS -->

        <div class="info-card">


            <div class="info-icon">
                ◇
            </div>


            <h3>
                Announcements
            </h3>


            <p>

                Stay informed about the latest
                announcements, updates, schedules,
                and university reminders.

            </p>


            <a
                href="announcements.php"
                class="info-link"
            >

                View Announcements →

            </a>


        </div>


    </div>


</section>



<!-- ============================================================
     CTA
============================================================= -->

<section class="cta">


    <div class="cta-inner">


        <div>


            <h2>

                Ready to begin your enrollment journey?

            </h2>


            <p>

                Continue to SmartEnroll to view your
                enrollment guide, requirements, and
                procedures.

            </p>


        </div>


        <div class="cta-actions">


            <a
                href="enrollment.php"
                class="cta-button cta-primary"
            >

                Continue to Enrollment

            </a>


            <form
                method="POST"
                action="index.php"
            >

                <input
                    type="hidden"
                    name="logout"
                    value="1"
                >


                <button
                    type="submit"
                    class="cta-button cta-logout"
                >

                    Logout

                </button>

            </form>


        </div>


    </div>


</section>



<!-- ============================================================
     FOOTER
============================================================= -->

<footer class="footer">


    <div class="footer-inner">


        <div class="footer-brand">


            <img
                src="assets/isu-logo.png"
                alt="ISU"
            >


            <div>


                <strong>
                    ISU SmartEnroll
                </strong>


                <span>
                    Isabela State University – Cauayan Campus
                </span>


            </div>


        </div>


        <div class="footer-copy">

            © <?= date("Y") ?>
            ISU SmartEnroll.
            All rights reserved.

        </div>


    </div>


</footer>


</body>

</html>