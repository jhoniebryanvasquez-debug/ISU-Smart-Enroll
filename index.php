<?php
// ISU SmartEnroll Homepage
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ISU SmartEnroll | Isabela State University - Cauayan Campus</title>

    <style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

       html,
body {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
    overflow-x: hidden !important;
}

body {
    min-height: 100vh;
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f7faf9;
    color: #075d43;
}


        /* =====================================================
           HEADER
        ===================================================== */

        .header {
            width: 100%;
            height: 185px;
            background: #ffffff;
            border-top: 3px solid #087b4c;
            overflow: hidden;
        }

        .header-inner {
            width: 100%;
            height: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 45px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }


        /* =====================================================
           UNIVERSITY LOGOS
        ===================================================== */

        .side-logo {
            width: 155px;
            height: 155px;
            object-fit: contain;
            flex: 0 0 auto;
        }


        /* =====================================================
           SMART ENROLL LOGO
        ===================================================== */

        .smart-logo-box {
            width: 700px;
            height: 180px;

            display: flex;
            align-items: center;
            justify-content: center;

            position: relative;
            overflow: hidden;

            flex: 1 1 auto;
        }

        .smart-logo {
            display: block;

            width: 700px;
            max-width: 100%;
            height: auto;

            transform: scale(0.75);
            transform-origin: center center;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {
            width: 100%;
            height: 67px;

            background: linear-gradient(
                90deg,
                #075d3e,
                #087b4d
            );

            box-shadow: 0 3px 10px rgba(0,0,0,0.12);

            overflow: hidden;
        }

        .nav-inner {
    width: 100%;
    max-width: 1600px;
    height: 67px;

    margin: 0 auto;
    padding: 0 25px;

    display: flex;
    align-items: center;

    overflow: hidden;
}

        .nav-links {
            height: 67px;

            display: flex;
            align-items: center;

            min-width: 0;
        }

        .nav-links a {
            height: 67px;

            padding: 0 20px;

            display: flex;
            align-items: center;

            gap: 9px;

            color: #ffffff;

            font-size: 17px;
            font-weight: 600;

            white-space: nowrap;

            transition: background 0.2s ease;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.08);
        }

        .nav-links a.active {
            background: #35a15e;
        }


        /* =====================================================
           NAV ICON
        ===================================================== */

        .nav-icon {
            width: 21px;
            height: 21px;

            stroke: currentColor;
            fill: none;

            stroke-width: 2;

            stroke-linecap: round;
            stroke-linejoin: round;

            flex-shrink: 0;
        }


        /* =====================================================
           ACCOUNT BUTTONS
        ===================================================== */

        .account-links {
            margin-left: auto;

            display: flex;
            align-items: center;

            gap: 12px;

            flex-shrink: 0;
        }

        .login-btn,
        .register-btn {
            height: 46px;

            padding: 0 20px;

            border-radius: 9px;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            color: #ffffff;

            font-size: 16px;
            font-weight: 600;

            white-space: nowrap;
        }

        .login-btn {
            border: 1px solid #ffffff;
            background: transparent;
        }

        .login-btn:hover {
            background: rgba(255,255,255,0.12);
        }

        .register-btn {
            background: #68c638;
        }

        .register-btn:hover {
            background: #55ae2b;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {
            position: relative;

            width: 100%;
            height: 425px;

            overflow: hidden;

            background: #eef8f5;
        }


        /* =====================================================
           CAMPUS IMAGE
        ===================================================== */

        .hero-image {
            position: absolute;

            top: 0;
            right: 0;

            width: 55%;
            height: 100%;

            background-image: url("assets/background.jpg");

            background-size: cover;
            background-position: center center;

            z-index: 1;
        }


        /* =====================================================
           LIGHT LEFT PANEL
        ===================================================== */

        .hero-left {
            position: absolute;

            top: 0;
            left: 0;

            width: 60%;
            height: 100%;

            background: linear-gradient(
                105deg,
                #f7fcfa 0%,
                #eef8f5 72%,
                #e2f2eb 100%
            );

            clip-path: polygon(
                0 0,
                91% 0,
                73% 100%,
                0 100%
            );

            z-index: 2;
        }


        /* =====================================================
           DIAGONAL GREEN DESIGN
        ===================================================== */

        .shape-one {
            position: absolute;

            top: -60px;
            bottom: -60px;

            left: 51%;

            width: 78px;

            background: rgba(7,126,76,0.88);

            transform: skewX(-27deg);

            z-index: 4;
        }

        .shape-two {
            position: absolute;

            top: -60px;
            bottom: -60px;

            left: 48.3%;

            width: 45px;

            background: rgba(103,195,55,0.85);

            transform: skewX(-27deg);

            z-index: 4;
        }

        .shape-three {
            position: absolute;

            top: -60px;
            bottom: -60px;

            left: 53.6%;

            width: 18px;

            background: rgba(255,255,255,0.92);

            transform: skewX(-27deg);

            z-index: 5;
        }


        /* =====================================================
           HERO CONTENT
        ===================================================== */

        .hero-content {
    position: relative;
    z-index: 10;

    width: 100%;
    max-width: 1600px;
    height: 100%;

    margin: 0 auto;
    padding: 72px 65px;

    overflow: hidden;
}
    

        .hero-content-inner {
            width: 670px;
            max-width: 48%;
        }

        .hero-title {
            color: #075d47;

            font-size: 52px;
            line-height: 1.08;

            font-weight: 750;

            margin-bottom: 22px;
        }

        .hero-description {
            color: #527b8d;

            font-size: 20px;
            line-height: 1.5;

            max-width: 650px;

            margin-bottom: 27px;
        }


        /* =====================================================
           GET STARTED
        ===================================================== */

        .get-started {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 12px;

            height: 56px;
            min-width: 230px;

            padding: 0 30px;

            border-radius: 28px;

            background: #078548;

            color: #ffffff;

            font-size: 20px;
            font-weight: 650;

            box-shadow: 0 5px 12px rgba(0,0,0,0.10);

            transition: 0.2s ease;
        }

        .get-started:hover {
            background: #056d3c;
            transform: translateY(-2px);
        }

        .start-icon {
            width: 23px;
            height: 23px;

            stroke: currentColor;
            fill: none;

            stroke-width: 2;

            stroke-linecap: round;
            stroke-linejoin: round;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .services {
            width: 100%;

            padding: 34px 35px 50px;

            background: #f8fbfa;
        }

        .cards {
            width: 100%;
            max-width: 1375px;

            margin: 0 auto;

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 24px;
        }

        .card {
            min-height: 252px;

            padding: 18px 30px 20px;

            background: #ffffff;

            border: 1px solid #e5eeea;

            border-radius: 13px;

            box-shadow: 0 3px 15px rgba(0,0,0,0.06);

            display: flex;
            flex-direction: column;
            align-items: center;

            text-align: center;

            transition: 0.25s ease;
        }

        .card:hover {
            transform: translateY(-5px);

            box-shadow: 0 8px 22px rgba(0,0,0,0.10);
        }


        /* =====================================================
           CARD ICON
        ===================================================== */

        .card-icon {
            width: 76px;
            height: 76px;

            margin-bottom: 12px;

            border-radius: 50%;

            background: #dff1e4;

            color: #087449;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-icon svg {
            width: 38px;
            height: 38px;

            stroke: currentColor;
            fill: none;

            stroke-width: 2;

            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .card h3 {
            color: #086443;

            font-size: 20px;

            margin-bottom: 11px;
        }

        .card p {
            color: #607f91;

            font-size: 16px;
            line-height: 1.45;

            max-width: 270px;
        }

        .card-arrow {
            margin-top: auto;
            padding-top: 12px;

            color: #087449;

            font-size: 31px;
        }


        /* =====================================================
           1400px AND BELOW
        ===================================================== */

        @media (max-width: 1400px) {

            .header-inner {
                padding: 0 30px;
            }

            .side-logo {
                width: 135px;
                height: 135px;
            }

            .smart-logo-box {
                max-width: 650px;
            }

            .smart-logo {
                width: 650px;
            }

            .nav-links a {
                padding: 0 15px;
                font-size: 16px;
            }

            .hero-content {
                padding-left: 50px;
                padding-right: 50px;
            }

            .hero-title {
                font-size: 48px;
            }
        }


        /* =====================================================
           1150px AND BELOW
        ===================================================== */

        @media (max-width: 1150px) {

            .header {
                height: 160px;
            }

            .header-inner {
                padding: 0 20px;
                gap: 10px;
            }

            .side-logo {
                width: 110px;
                height: 110px;
            }

            .smart-logo-box {
                height: 155px;
                max-width: 600px;
            }

            .smart-logo {
                width: 600px;
                transform: scale(0.72);
            }

            .nav-links a {
                padding: 0 11px;
                font-size: 14px;
                gap: 6px;
            }

            .account-links {
                gap: 6px;
            }

            .login-btn,
            .register-btn {
                padding: 0 13px;
                font-size: 14px;
            }

            .hero-content {
                padding: 65px 40px;
            }

            .hero-content-inner {
                max-width: 52%;
            }

            .hero-title {
                font-size: 42px;
            }

            .hero-description {
                font-size: 18px;
            }

            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }


        /* =====================================================
           900px AND BELOW
        ===================================================== */

        @media (max-width: 900px) {

            .header {
                height: 130px;
            }

            .header-inner {
                padding: 0 10px;
            }

            .side-logo {
                width: 80px;
                height: 80px;
            }

            .smart-logo-box {
                width: calc(100% - 180px);
                height: 125px;
            }

            .smart-logo {
                width: 100%;
                transform: scale(0.82);
            }

            .navbar {
                height: auto;
            }

            .nav-inner {
                height: 60px;
                padding: 0;
                overflow-x: auto;
                overflow-y: hidden;
            }

            .nav-links {
                height: 60px;
                flex-shrink: 0;
            }

            .nav-links a {
                height: 60px;
                padding: 0 14px;
            }

            .account-links {
                flex-shrink: 0;
                padding: 0 8px;
            }

            .hero {
                height: 500px;
            }

            .hero-image {
                width: 100%;
                opacity: 0.18;
            }

            .hero-left {
                width: 100%;
                clip-path: none;

                background: rgba(244,251,249,0.93);
            }

            .shape-one,
            .shape-two,
            .shape-three {
                display: none;
            }

            .hero-content {
                padding: 55px 30px;
            }

            .hero-content-inner {
                max-width: 100%;
                width: 100%;
            }

            .hero-title {
                font-size: 40px;
            }

            .hero-description {
                font-size: 18px;
            }
        }


        /* =====================================================
           650px AND BELOW
        ===================================================== */

        @media (max-width: 650px) {

            .header {
                height: 105px;
            }

            .header-inner {
                padding: 0 7px;
                gap: 3px;
            }

            .side-logo {
                width: 60px;
                height: 60px;
            }

            .smart-logo-box {
                width: calc(100% - 126px);
                height: 100px;
            }

            .smart-logo {
                width: 100%;
                transform: scale(0.9);
            }

            .nav-inner {
                height: 56px;
            }

            .nav-links {
                height: 56px;
            }

            .nav-links a {
                height: 56px;
                padding: 0 12px;
                font-size: 13px;
            }

            .account-links {
                height: 56px;
            }

            .login-btn,
            .register-btn {
                height: 40px;
                padding: 0 11px;
                font-size: 13px;
            }

            .hero {
                height: 540px;
            }

            .hero-content {
                padding: 50px 25px;
            }

            .hero-title {
                font-size: 35px;
            }

            .hero-description {
                font-size: 17px;
            }

            .get-started {
                min-width: 210px;
                height: 52px;
                font-size: 18px;
            }

            .services {
                padding: 25px 18px 40px;
            }

            .cards {
                grid-template-columns: 1fr;
                gap: 18px;
            }
        }


        /* =====================================================
           450px AND BELOW
        ===================================================== */

        @media (max-width: 450px) {

            .header {
                height: 90px;
            }

            .side-logo {
                width: 50px;
                height: 50px;
            }

            .smart-logo-box {
                width: calc(100% - 106px);
                height: 85px;
            }

            .smart-logo {
                transform: scale(0.95);
            }

            .nav-links a {
                padding: 0 10px;
                font-size: 12px;
            }

            .nav-icon {
                width: 17px;
                height: 17px;
            }

            .hero {
                height: 500px;
            }

            .hero-content {
                padding: 45px 20px;
            }

            .hero-title {
                font-size: 31px;
            }

            .hero-description {
                font-size: 16px;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">

    <div class="header-inner">

        <!-- ISU LOGO -->

        <img
            src="assets/isu-logo.png"
            alt="Isabela State University"
            class="side-logo"
        >


        <!-- SMART ENROLL LOGO -->

        <div class="smart-logo-box">

            <img
                src="assets/smart-enroll-logo.png?v=7"
                alt="ISU SmartEnroll"
                class="smart-logo"
            >

        </div>


        <!-- CCSICT LOGO -->

        <img
            src="assets/ccsict-logo.png"
            alt="CCSICT"
            class="side-logo"
        >

    </div>

</header>



<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar">

    <div class="nav-inner">


        <div class="nav-links">


            <!-- HOME -->

            <a href="index.php" class="active">

                <svg class="nav-icon" viewBox="0 0 24 24">

                    <path d="M3 10.5L12 3l9 7.5"></path>

                    <path d="M5 9.5V21h14V9.5"></path>

                    <path d="M9 21v-6h6v6"></path>

                </svg>

                Home

            </a>


            <!-- ENROLLMENT GUIDE -->

            <a href="enrollment-guide.php">

                <svg class="nav-icon" viewBox="0 0 24 24">

                    <path d="M4 5.5A3.5 3.5 0 0 1 7.5 2H20v17H7.5A3.5 3.5 0 0 0 4 22z"></path>

                    <path d="M4 5.5v16"></path>

                </svg>

                Enrollment Guide

            </a>


            <!-- REQUIREMENTS -->

            <a href="requirements.php">

                <svg class="nav-icon" viewBox="0 0 24 24">

                    <path d="M6 3h9l4 4v14H6z"></path>

                    <path d="M14 3v5h5"></path>

                    <path d="M9 13h6"></path>

                    <path d="M9 17h6"></path>

                </svg>

                Requirements

            </a>


            <!-- PROCEDURES -->

            <a href="procedures.php">

                <svg class="nav-icon" viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="12"
                        r="3"
                    ></circle>

                    <path d="M19 15a2 2 0 0 0 .4 2.2l.1.1-1.8 1.8-.1-.1A2 2 0 0 0 15.4 19a2 2 0 0 0-1.4 1.9v.1h-4v-.1A2 2 0 0 0 8.6 19a2 2 0 0 0-2.2.4l-.1.1-1.8-1.8.1-.1A2 2 0 0 0 5 15.4 2 2 0 0 0 3.1 14H3v-4h.1A2 2 0 0 0 5 8.6 2 2 0 0 0 4.6 6.4l-.1-.1 1.8-1.8.1.1A2 2 0 0 0 8.6 5 2 2 0 0 0 10 3.1V3h4v.1A2 2 0 0 0 15.4 5a2 2 0 0 0 2.2-.4l.1-.1 1.8 1.8-.1.1a2 2 0 0 0-.4 2.2A2 2 0 0 0 20.9 10h.1v4h-.1A2 2 0 0 0 19 15z"></path>

                </svg>

                Procedures

            </a>


            <!-- ANNOUNCEMENTS -->

            <a href="announcements.php">

                <svg class="nav-icon" viewBox="0 0 24 24">

                    <path d="M3 11v2"></path>

                    <path d="M6 9l11-4v14L6 15z"></path>

                    <path d="M6 9v6"></path>

                    <path d="M17 9c2 0 4 1.5 4 3s-2 3-4 3"></path>

                </svg>

                Announcements

            </a>


        </div>


        <!-- LOGIN / REGISTER -->

        <div class="account-links">


            <a
                href="login.php"
                class="login-btn"
            >

                <svg class="nav-icon" viewBox="0 0 24 24">

                    <path d="M10 17l5-5-5-5"></path>

                    <path d="M15 12H3"></path>

                    <path d="M21 3v18"></path>

                </svg>

                Login

            </a>


            <a
                href="register.php"
                class="register-btn"
            >

                <svg class="nav-icon" viewBox="0 0 24 24">

                    <circle
                        cx="9"
                        cy="8"
                        r="3"
                    ></circle>

                    <path d="M3 20c0-3 2.5-5 6-5s6 2 6 5"></path>

                    <path d="M18 8v6"></path>

                    <path d="M15 11h6"></path>

                </svg>

                Register

            </a>

        </div>

    </div>

</nav>



<!-- =====================================================
     HERO SECTION
===================================================== -->

<section class="hero">


    <!-- CAMPUS IMAGE -->

    <div class="hero-image"></div>


    <!-- LEFT LIGHT AREA -->

    <div class="hero-left"></div>


    <!-- GREEN DIAGONALS -->

    <div class="shape-two"></div>

    <div class="shape-one"></div>

    <div class="shape-three"></div>


    <!-- HERO CONTENT -->

    <div class="hero-content">

        <div class="hero-content-inner">


            <h1 class="hero-title">

                Enrollment Guide and<br>

                Decision Support System

            </h1>


            <p class="hero-description">

                Your gateway to a simpler, smarter, and more
                convenient enrollment process at Isabela State University
                Cauayan Campus.

            </p>


            <a
                href="enrollment-guide.php"
                class="get-started"
            >

                <svg
                    class="start-icon"
                    viewBox="0 0 24 24"
                >

                    <path d="M10 17l5-5-5-5"></path>

                    <path d="M15 12H3"></path>

                    <path d="M21 3v18"></path>

                </svg>

                Get Started

            </a>

        </div>

    </div>

</section>



<!-- =====================================================
     SERVICE CARDS
===================================================== -->

<section class="services">

    <div class="cards">


        <!-- ENROLLMENT GUIDE -->

        <div class="card">

            <div class="card-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M6 3h9l4 4v14H6z"></path>

                    <path d="M14 3v5h5"></path>

                    <path d="M9 12h6"></path>

                    <path d="M9 16h6"></path>

                    <path d="M9 8h2"></path>

                </svg>

            </div>

            <h3>
                Enrollment Guide
            </h3>

            <p>
                Learn the step-by-step process
                of how to enroll at ISU.
            </p>

            <div class="card-arrow">
                →
            </div>

        </div>



        <!-- REQUIREMENTS -->

        <div class="card">

            <div class="card-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M6 3h9l4 4v14H6z"></path>

                    <path d="M14 3v5h5"></path>

                    <path d="M9 12h6"></path>

                    <path d="M9 16h6"></path>

                </svg>

            </div>

            <h3>
                Requirements
            </h3>

            <p>
                View the complete list of
                requirements for enrollment.
            </p>

            <div class="card-arrow">
                →
            </div>

        </div>



        <!-- PROCEDURES -->

        <div class="card">

            <div class="card-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="12"
                        r="3"
                    ></circle>

                    <path d="M19 15a2 2 0 0 0 .4 2.2l.1.1-1.8 1.8-.1-.1A2 2 0 0 0 15.4 19a2 2 0 0 0-1.4 1.9v.1h-4v-.1A2 2 0 0 0 8.6 19a2 2 0 0 0-2.2.4l-.1.1-1.8-1.8-.1-.1A2 2 0 0 0 5 15.4 2 2 0 0 0 3.1 14H3v-4h.1A2 2 0 0 0 5 8.6 2 2 0 0 0 4.6 6.4l-.1-.1 1.8-1.8.1.1A2 2 0 0 0 8.6 5 2 2 0 0 0 10 3.1V3h4v.1A2 2 0 0 0 15.4 5a2 2 0 0 0 2.2-.4l.1-.1 1.8 1.8-.1.1a2 2 0 0 0-.4 2.2A2 2 0 0 0 20.9 10h.1v4h-.1A2 2 0 0 0 19 15z"></path>

                </svg>

            </div>

            <h3>
                Procedures
            </h3>

            <p>
                Check the enrollment procedures
                and important reminders.
            </p>

            <div class="card-arrow">
                →
            </div>

        </div>



        <!-- ANNOUNCEMENTS -->

        <div class="card">

            <div class="card-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 11v2"></path>

                    <path d="M6 9l11-4v14L6 15z"></path>

                    <path d="M6 9v6"></path>

                    <path d="M17 9c2 0 4 1.5 4 3s-2 3-4 3"></path>

                </svg>

            </div>

            <h3>
                Announcements
            </h3>

            <p>
                Stay updated with the latest
                news and updates from the university.
            </p>

            <div class="card-arrow">
                →
            </div>

        </div>


    </div>

</section>


</body>
</html>