<?php
session_start();

/* =========================
   SECURITY CHECK
========================= */

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

$full_name    = $_SESSION["full_name"] ?? "Student";
$applicant_id = $_SESSION["applicant_id"] ?? "";

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
        Enrollment Procedures - ISU SmartEnroll
    </title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7f5;
            color: #26372e;
            min-height: 100vh;
        }


        /* =========================
           HEADER
        ========================= */

        .top-header {
            background: #ffffff;
            border-bottom: 1px solid #e5ebe7;
        }


        .header-inner {
            max-width: 1250px;
            margin: auto;

            min-height: 105px;

            padding: 15px 28px;

            display: grid;

            grid-template-columns: 1fr auto 1fr;

            align-items: center;

            gap: 20px;
        }


        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }


        .header-left img {
            width: 68px;
            height: 68px;
            object-fit: contain;
        }


        .isu-name {
            line-height: 1.2;
        }


        .isu-name strong {
            display: block;

            font-size: 20px;

            color: #006b3c;
        }


        .isu-name span {
            display: block;

            font-size: 14px;

            color: #68756e;

            margin-top: 4px;
        }


        .header-center {
            display: flex;

            justify-content: center;

            align-items: center;
        }


        .header-center img {
            width: 110px;
            height: 110px;

            object-fit: contain;
        }


        .college-brand {
            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 12px;

            text-align: right;
        }


        .college-text {
            line-height: 1.25;
        }


        .college-text strong {
            display: block;

            font-size: 14px;

            color: #006b3c;
        }


        .college-text span {
            display: block;

            font-size: 11px;

            color: #69756f;

            margin-top: 2px;
        }


        .college-brand img {
            width: 58px;
            height: 58px;

            object-fit: contain;
        }


        /* =========================
           NAVIGATION
        ========================= */

        .navbar {
            background: #006b3c;

            position: sticky;

            top: 0;

            z-index: 1000;

            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }


        .nav-inner {
            max-width: 1250px;

            margin: auto;

            padding: 0 28px;

            min-height: 52px;

            display: flex;

            align-items: center;

            gap: 4px;
        }


        .nav-inner a {
            color: white;

            text-decoration: none;

            padding: 17px 13px;

            font-size: 14px;

            transition: background 0.2s ease;
        }


        .nav-inner a:hover {
            background: #00552f;
        }


        .nav-inner .active {
            background: #00552f;

            font-weight: bold;
        }


        .nav-spacer {
            flex: 1;
        }


        .account-info {
            color: white;

            font-size: 13px;

            padding: 0 12px;

            text-align: right;
        }


        .account-info strong {
            display: block;

            font-size: 13px;
        }


        .account-info span {
            opacity: 0.85;

            font-size: 11px;
        }


        .logout-button {
            background: #b42318 !important;

            color: white;

            border: none;

            border-radius: 5px;

            margin: 7px 0;

            padding: 10px 15px;

            font-weight: bold;

            cursor: pointer;

            font-size: 13px;
        }


        .logout-button:hover {
            background: #8f1c13 !important;
        }


        /* =========================
           HERO
        ========================= */

        .hero {
            min-height: 310px;

            background:
                linear-gradient(
                    rgba(0, 65, 37, 0.76),
                    rgba(0, 65, 37, 0.82)
                ),
                url("assets/background.jpg");

            background-size: cover;

            background-position: center;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            padding: 55px 20px;
        }


        .hero-content {
            max-width: 850px;

            color: white;
        }


        .eyebrow {
            display: inline-block;

            font-size: 12px;

            font-weight: bold;

            letter-spacing: 1.4px;

            text-transform: uppercase;

            background: rgba(255,255,255,0.12);

            border: 1px solid rgba(255,255,255,0.25);

            padding: 8px 14px;

            border-radius: 30px;

            margin-bottom: 16px;
        }


        .hero-content h1 {
            font-size: 42px;

            margin-bottom: 12px;

            line-height: 1.15;
        }


        .hero-content p {
            font-size: 16px;

            line-height: 1.7;

            max-width: 720px;

            margin: auto;

            color: rgba(255,255,255,0.92);
        }


        /* =========================
           MAIN
        ========================= */

        .main-container {
            max-width: 1150px;

            margin: -35px auto 60px;

            padding: 0 22px;

            position: relative;

            z-index: 2;
        }


        .main-card {
            background: white;

            border-radius: 16px;

            box-shadow: 0 12px 35px rgba(0,0,0,0.10);

            overflow: hidden;
        }


        .intro {
            padding: 30px 32px;

            border-bottom: 1px solid #edf1ee;
        }


        .intro h2 {
            color: #006b3c;

            font-size: 25px;

            margin-bottom: 8px;
        }


        .intro p {
            color: #718078;

            font-size: 14px;

            line-height: 1.7;
        }


        /* =========================
           PROCEDURE NAV
        ========================= */

        .procedure-nav {
            padding: 22px 32px;

            background: #f8faf9;

            border-bottom: 1px solid #e9efeb;

            display: flex;

            gap: 10px;

            flex-wrap: wrap;
        }


        .procedure-nav a {
            text-decoration: none;

            color: #006b3c;

            background: white;

            border: 1px solid #cfe0d5;

            padding: 10px 14px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: bold;

            transition: 0.2s ease;
        }


        .procedure-nav a:hover {
            background: #006b3c;

            color: white;

            border-color: #006b3c;
        }


        /* =========================
           CONTENT
        ========================= */

        .content {
            padding: 32px;
        }


        .section {
            margin-bottom: 38px;

            scroll-margin-top: 85px;
        }


        .section:last-child {
            margin-bottom: 0;
        }


        .section-heading {
            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: 18px;

            padding-bottom: 11px;

            border-bottom: 1px solid #e3ebe6;
        }


        .section-number {
            width: 38px;

            height: 38px;

            border-radius: 10px;

            background: #eaf7ef;

            color: #006b3c;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

            flex: 0 0 38px;
        }


        .section-heading h3 {
            color: #24382e;

            font-size: 20px;
        }


        .section-heading p {
            color: #77847d;

            font-size: 12px;

            margin-top: 3px;
        }


        /* =========================
           STEP LIST
        ========================= */

        .steps {
            display: flex;

            flex-direction: column;

            gap: 12px;
        }


        .step {
            display: flex;

            align-items: flex-start;

            gap: 15px;

            padding: 17px;

            border: 1px solid #e1e9e4;

            border-radius: 11px;

            background: white;

            transition: 0.2s ease;
        }


        .step:hover {
            border-color: #b9d6c4;

            box-shadow: 0 5px 15px rgba(0,107,60,0.06);

            transform: translateY(-1px);
        }


        .step-number {
            width: 36px;

            height: 36px;

            flex: 0 0 36px;

            border-radius: 50%;

            background: #006b3c;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 13px;

            font-weight: bold;
        }


        .step-content {
            flex: 1;
        }


        .step-content h4 {
            color: #263a30;

            font-size: 15px;

            margin-bottom: 5px;
        }


        .step-content p {
            color: #6e7c74;

            font-size: 13px;

            line-height: 1.65;
        }


        /* =========================
           INFO BOX
        ========================= */

        .info-box {
            margin-top: 20px;

            padding: 17px 18px;

            background: #f1f8f4;

            border: 1px solid #cde5d5;

            border-left: 5px solid #006b3c;

            border-radius: 10px;

            color: #42564b;

            font-size: 13px;

            line-height: 1.7;
        }


        .info-box strong {
            color: #006b3c;
        }


        /* =========================
           WARNING
        ========================= */

        .notice-box {
            margin-top: 22px;

            padding: 18px;

            background: #fff8e8;

            border: 1px solid #f0d99b;

            border-left: 5px solid #d79b00;

            border-radius: 10px;

            color: #66521d;

            font-size: 13px;

            line-height: 1.7;
        }


        .notice-box strong {
            color: #8a6400;
        }


        /* =========================
           CTA
        ========================= */

        .cta {
            margin-top: 35px;

            padding: 24px;

            border-radius: 12px;

            background: #006b3c;

            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;
        }


        .cta h3 {
            font-size: 18px;

            margin-bottom: 5px;
        }


        .cta p {
            font-size: 13px;

            color: rgba(255,255,255,0.86);

            line-height: 1.5;
        }


        .cta-button {
            background: white;

            color: #006b3c;

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: bold;

            white-space: nowrap;
        }


        .cta-button:hover {
            background: #edf7f1;
        }


        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #003d25;

            color: rgba(255,255,255,0.82);

            padding: 30px 20px;

            text-align: center;
        }


        footer strong {
            color: white;

            display: block;

            margin-bottom: 6px;
        }


        footer p {
            font-size: 12px;

            line-height: 1.6;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .header-inner {
                grid-template-columns: 1fr auto;

                grid-template-areas:
                    "left center"
                    "right right";
            }


            .header-left {
                grid-area: left;
            }


            .header-center {
                grid-area: center;
            }


            .college-brand {
                grid-area: right;

                justify-content: flex-start;

                text-align: left;

                padding-bottom: 8px;
            }


            .nav-inner {
                overflow-x: auto;

                white-space: nowrap;
            }


            .account-info {
                display: none;
            }

        }


        @media (max-width: 650px) {

            .header-inner {
                display: flex;

                flex-direction: column;

                align-items: center;

                text-align: center;

                gap: 10px;

                padding: 15px 18px;
            }


            .header-left {
                flex-direction: column;

                gap: 6px;
            }


            .isu-name strong {
                font-size: 18px;
            }


            .college-brand {
                justify-content: center;

                text-align: center;
            }


            .college-text {
                text-align: center;
            }


            .nav-inner {
                justify-content: flex-start;

                padding: 0 10px;
            }


            .nav-inner a {
                padding: 15px 10px;

                font-size: 13px;
            }


            .hero {
                min-height: 270px;

                padding: 45px 20px;
            }


            .hero-content h1 {
                font-size: 31px;
            }


            .hero-content p {
                font-size: 14px;
            }


            .main-container {
                margin-top: -25px;

                padding: 0 12px;
            }


            .intro,
            .procedure-nav,
            .content {
                padding-left: 20px;

                padding-right: 20px;
            }


            .cta {
                flex-direction: column;

                align-items: flex-start;
            }


            .cta-button {
                width: 100%;

                text-align: center;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header class="top-header">

    <div class="header-inner">


        <div class="header-left">

            <img
                src="assets/isu-logo.png"
                alt="Isabela State University Logo"
            >

            <div class="isu-name">

                <strong>
                    Isabela State University
                </strong>

                <span>
                    Cauayan Campus
                </span>

            </div>

        </div>


        <div class="header-center">

            <img
                src="assets/smart-enroll-logo.png?v=8"
                alt="SmartEnroll Logo"
            >

        </div>


        <div class="college-brand">

            <div class="college-text">

                <strong>
                    College of Computing Studies
                </strong>

                <span>
                    Information and Communication Technology
                </span>

                <span>
                    Isabela State University
                </span>

            </div>


            <img
                src="assets/ccsict-logo.png"
                alt="CCSICT Logo"
            >

        </div>


    </div>

</header>



<!-- =========================
     NAVIGATION
========================= -->

<nav class="navbar">

    <div class="nav-inner">


        <a href="index.php">
            Home
        </a>


        <a href="enrollment.php">
            Enrollment Guide
        </a>


        <a href="requirements.php">
            Requirements
        </a>


        <a
            href="procedures.php"
            class="active"
        >
            Procedures
        </a>


        <a href="announcements.php">
            Announcements
        </a>


        <div class="nav-spacer"></div>


        <div class="account-info">

            <strong>
                <?= htmlspecialchars($full_name) ?>
            </strong>


            <?php if ($applicant_id !== ""): ?>

                <span>
                    <?= htmlspecialchars($applicant_id) ?>
                </span>

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
                Logout
            </button>

        </form>


    </div>

</nav>



<!-- =========================
     HERO
========================= -->

<section class="hero">

    <div class="hero-content">

        <div class="eyebrow">
            ISU SmartEnroll
        </div>


        <h1>
            Enrollment Procedures
        </h1>


        <p>
            Follow the appropriate enrollment procedure based on
            your student type. This guide summarizes the official
            procedures of Isabela State University for easier reference.
        </p>

    </div>

</section>



<!-- =========================
     MAIN
========================= -->

<main class="main-container">


    <div class="main-card">


        <!-- INTRO -->

        <div class="intro">

            <h2>
                How Enrollment Works
            </h2>


            <p>
                Choose the procedure that matches your student status.
                SmartEnroll provides this page as a guidance tool;
                actual enrollment and approval are still handled by
                the appropriate ISU offices.
            </p>

        </div>



        <!-- QUICK NAV -->

        <div class="procedure-nav">

            <a href="#freshmen">
                Freshmen & Transferees
            </a>


            <a href="#continuing">
                Continuing / Old Students
            </a>


            <a href="#returnee">
                Returnee & CPE
            </a>


            <a href="#important">
                Important Notes
            </a>

        </div>



        <div class="content">


            <!-- =========================
                 FRESHMEN / TRANSFEREES
            ========================= -->

            <section
                class="section"
                id="freshmen"
            >

                <div class="section-heading">

                    <div class="section-number">
                        1
                    </div>


                    <div>

                        <h3>
                            Freshmen and Transferees
                        </h3>


                        <p>
                            For students entering ISU or transferring from another institution
                        </p>

                    </div>

                </div>


                <div class="steps">


                    <div class="step">

                        <div class="step-number">
                            1
                        </div>


                        <div class="step-content">

                            <h4>
                                Proceed to the Program Chair or Dean
                            </h4>


                            <p>
                                Go to the concerned Program Chair or Dean
                                for the required interview and screening.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            2
                        </div>


                        <div class="step-content">

                            <h4>
                                Undergo Medical and Dental Examination
                            </h4>


                            <p>
                                Complete the required medical and dental
                                examination at the University Infirmary.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            3
                        </div>


                        <div class="step-content">

                            <h4>
                                Secure Your Student Number
                            </h4>


                            <p>
                                Secure your student number through the
                                Office of Student Affairs and Services
                                (OSAS) or the Registrar's Office.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            4
                        </div>


                        <div class="step-content">

                            <h4>
                                Submit Admission Requirements
                            </h4>


                            <p>
                                Submit the required admission documents
                                to the Registrar's Office. Your subjects
                                will be encoded and your fees will be assessed.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            5
                        </div>


                        <div class="step-content">

                            <h4>
                                Pay the Assessed Fees
                            </h4>


                            <p>
                                Pay the assessed fees at the Cashier's
                                Office, when applicable.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            6
                        </div>


                        <div class="step-content">

                            <h4>
                                Process Scholarship Approval
                            </h4>


                            <p>
                                Students with applicable scholarships
                                should secure the required scholarship
                                approval from the Office of Student Affairs
                                and Services (OSAS).
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            7
                        </div>


                        <div class="step-content">

                            <h4>
                                Enroll in NSTP
                            </h4>


                            <p>
                                If applicable, complete National Service
                                Training Program (NSTP) enrollment at
                                the NSTP Office.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            8
                        </div>


                        <div class="step-content">

                            <h4>
                                Proceed for ID Processing
                            </h4>


                            <p>
                                Proceed to the Campus Business Affairs
                                Office (CBAO) for ID processing.
                            </p>

                        </div>

                    </div>


                </div>


                <div class="info-box">

                    <strong>
                        Admission note:
                    </strong>

                    Incoming freshmen and transferee students are also
                    required to comply with the applicable entrance/admission
                    test, college screening interview, and medical/dental
                    examination requirements.
                    
                </div>

            </section>



            <!-- =========================
                 CONTINUING / OLD
            ========================= -->

            <section
                class="section"
                id="continuing"
            >

                <div class="section-heading">

                    <div class="section-number">
                        2
                    </div>


                    <div>

                        <h3>
                            Continuing / Old Students
                        </h3>


                        <p>
                            For students continuing their regular enrollment
                        </p>

                    </div>

                </div>


                <div class="steps">


                    <div class="step">

                        <div class="step-number">
                            1
                        </div>


                        <div class="step-content">

                            <h4>
                                Accomplish the Student Cumulative Record
                            </h4>


                            <p>
                                Accomplish the Student Cumulative Record
                                at the Guidance Office.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            2
                        </div>


                        <div class="step-content">

                            <h4>
                                Secure Certification of Grades
                            </h4>


                            <p>
                                Secure the required certification of grades
                                from the college or Registrar's Office.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            3
                        </div>


                        <div class="step-content">

                            <h4>
                                Complete the Pre-registration Form
                            </h4>


                            <p>
                                Accomplish the pre-registration form and
                                have it approved by the registration adviser.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            4
                        </div>


                        <div class="step-content">

                            <h4>
                                Proceed to the Registrar's Office
                            </h4>


                            <p>
                                Proceed to the Registrar's Office for
                                subject encoding and assessment of fees.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            5
                        </div>


                        <div class="step-content">

                            <h4>
                                Process Scholarship Approval
                            </h4>


                            <p>
                                If applicable, secure scholarship approval
                                from the Office of Student Affairs and
                                Services (OSAS).
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            6
                        </div>


                        <div class="step-content">

                            <h4>
                                Pay the Assessed Fees
                            </h4>


                            <p>
                                Pay the assessed fees at the Cashier's
                                Office, when applicable.
                            </p>

                        </div>

                    </div>


                </div>

            </section>



            <!-- =========================
                 RETURNEE / CPE
            ========================= -->

            <section
                class="section"
                id="returnee"
            >

                <div class="section-heading">

                    <div class="section-number">
                        3
                    </div>


                    <div>

                        <h3>
                            Returnee and CPE Students
                        </h3>


                        <p>
                            For returning students and Continuing Professional Education students
                        </p>

                    </div>

                </div>


                <div class="steps">


                    <div class="step">

                        <div class="step-number">
                            1
                        </div>


                        <div class="step-content">

                            <h4>
                                Accomplish the Admission Form
                            </h4>


                            <p>
                                Accomplish the Admission Form and Student
                                Cumulative Record at the Guidance Office.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            2
                        </div>


                        <div class="step-content">

                            <h4>
                                Proceed to the Program Chair
                            </h4>


                            <p>
                                Proceed to the Program Chair for interview
                                and academic advising.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            3
                        </div>


                        <div class="step-content">

                            <h4>
                                Proceed to the Registrar's Office
                            </h4>


                            <p>
                                Proceed to the Registrar's Office for
                                subject encoding and assessment of fees.
                            </p>

                        </div>

                    </div>


                    <div class="step">

                        <div class="step-number">
                            4
                        </div>


                        <div class="step-content">

                            <h4>
                                Pay the Assessed Fees
                            </h4>


                            <p>
                                Pay the assessed fees at the Cashier's
                                Office, when applicable.
                            </p>

                        </div>

                    </div>


                </div>


                <div class="info-box">

                    <strong>
                        Returning student note:
                    </strong>

                    The ISU Student Manual also lists requirements for
                    returning students such as an approved Leave of
                    Absence, re-admission form, certification of grades,
                    and evaluation of grades by the Program Chair or
                    Registration Adviser, when applicable.

                </div>

            </section>



            <!-- =========================
                 IMPORTANT
            ========================= -->

            <section
                class="section"
                id="important"
            >

                <div class="section-heading">

                    <div class="section-number">
                        4
                    </div>


                    <div>

                        <h3>
                            Important Reminders
                        </h3>


                        <p>
                            Before proceeding with actual enrollment
                        </p>

                    </div>

                </div>


                <div class="notice-box">

                    <strong>
                        Please remember:
                    </strong>

                    SmartEnroll is a guidance and information system.
                    Completing the SmartEnroll guide does not mean that
                    your official enrollment has already been completed
                    or approved.

                    <br><br>

                    Actual document submission, subject encoding,
                    assessment, payment, approval, and other official
                    enrollment transactions must still be completed
                    through the appropriate ISU offices and procedures.

                </div>


                <div class="info-box">

                    <strong>
                        Source:
                    </strong>

                    The procedure summaries on this page are based on
                    the enrollment and registration procedures stated
                    in the Isabela State University Student Manual.
                    Procedures may be subject to current university
                    policies and office instructions.

                </div>

            </section>



            <!-- =========================
                 CTA
            ========================= -->

            <div class="cta">

                <div>

                    <h3>
                        Ready to check your requirements?
                    </h3>


                    <p>
                        Start the SmartEnroll guide to identify your
                        student type and review the documents you may need.
                    </p>

                </div>


                <a
                    href="enrollment.php"
                    class="cta-button"
                >
                    Start Enrollment Guide →
                </a>

            </div>


        </div>

    </div>

</main>



<!-- =========================
     FOOTER
========================= -->

<footer>

    <strong>
        ISU SmartEnroll
    </strong>


    <p>
        Isabela State University – Cauayan Campus<br>
        College of Computing Studies
    </p>

</footer>


</body>

</html>