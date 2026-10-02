<?php

session_start();
require_once "db.php";

/*
|--------------------------------------------------------------------------
| LOAD PROGRAMS
|--------------------------------------------------------------------------
*/

$programs = [];

$sqlPrograms = "SELECT id, code, name FROM programs ORDER BY name ASC";
$resultPrograms = $conn->query($sqlPrograms);

if ($resultPrograms) {
    while ($row = $resultPrograms->fetch_assoc()) {
        $programs[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| LOAD ACTIVE REQUIREMENTS
|--------------------------------------------------------------------------
*/

$requirements = [];

$sqlRequirements = "
    SELECT
        id,
        requirement_name,
        description,
        student_type,
        status
    FROM requirements
    WHERE status = 'Active'
    ORDER BY student_type ASC, requirement_name ASC
";

$resultRequirements = $conn->query($sqlRequirements);

if ($resultRequirements) {
    while ($row = $resultRequirements->fetch_assoc()) {
        $requirements[] = $row;
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

    <title>Enrollment Guide - ISU SmartEnroll</title>

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
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f7f5;
            color: #263238;
            min-height: 100vh;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .top-header {
            background: #ffffff;
            border-bottom: 1px solid #e7ece9;
        }

        .top-header-inner {
            width: min(1400px, 94%);
            min-height: 104px;
            margin: 0 auto;

            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 25px;
        }

        /* UNIVERSITY */

        .university-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .university-brand img {
            width: 68px;
            height: 68px;
            object-fit: contain;
        }

        .university-brand h1 {
            font-size: 21px;
            color: #006b3c;
            font-weight: 800;
            line-height: 1.2;
        }

        .university-brand p {
            margin-top: 4px;
            color: #6b756f;
            font-size: 14px;
        }

        /* SMART ENROLL */

        .smart-brand {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .smart-brand img {
            width: 190px;
            max-height: 78px;
            object-fit: contain;
        }

        /* CCSI(C) */

        .ccsict-brand {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            text-align: right;
        }

        .ccsict-brand img {
            width: 62px;
            height: 62px;
            object-fit: contain;
        }

        .ccsict-brand h2 {
            color: #263238;
            font-size: 13px;
            line-height: 1.35;
            font-weight: 700;
        }

        .ccsict-brand p {
            color: #7b8580;
            font-size: 11px;
            margin-top: 3px;
        }

        /* =========================================================
           NAVIGATION
        ========================================================= */

        nav {
            position: sticky;
            top: 0;
            z-index: 1000;

            background: #006b3c;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
        }

        .nav-content {
            width: min(1250px, 94%);
            min-height: 54px;
            margin: 0 auto;

            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        nav a {
            position: relative;

            color: rgba(255, 255, 255, 0.88);
            text-decoration: none;

            padding: 17px 22px;

            font-size: 14px;
            font-weight: 600;

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }

        nav a:hover {
            background: rgba(255, 255, 255, 0.10);
            color: #ffffff;
        }

        nav a.active {
            color: #ffffff;
            background: rgba(0, 0, 0, 0.10);
        }

        nav a.active::after {
            content: "";

            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 0;

            height: 3px;
            background: #ffffff;
            border-radius: 3px 3px 0 0;
        }

        /* =========================================================
           HERO BACKGROUND
        ========================================================= */

        .page-hero {
            position: relative;

            min-height: 315px;

            background-image:
                linear-gradient(
                    135deg,
                    rgba(0, 55, 31, 0.88),
                    rgba(0, 107, 60, 0.72),
                    rgba(0, 39, 24, 0.86)
                ),
                url("assets/background.jpg");

            background-size: cover;
            background-position: center;

            display: flex;
            align-items: center;

            overflow: hidden;
        }

        .page-hero::before {
            content: "";

            position: absolute;
            width: 420px;
            height: 420px;

            right: -150px;
            top: -210px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.07);
        }

        .page-hero::after {
            content: "";

            position: absolute;
            width: 300px;
            height: 300px;

            left: -120px;
            bottom: -180px;

            border-radius: 50%;

            background: rgba(255, 255, 255, 0.05);
        }

        .hero-content {
            position: relative;
            z-index: 2;

            width: min(1180px, 92%);
            margin: 0 auto;

            padding: 65px 0;
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 7px 13px;

            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;

            background: rgba(255, 255, 255, 0.10);

            color: #ffffff;

            font-size: 12px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.8px;

            backdrop-filter: blur(8px);
        }

        .hero-label span {
            width: 7px;
            height: 7px;

            border-radius: 50%;
            background: #8be0b3;

            box-shadow:
                0 0 0 4px rgba(139, 224, 179, 0.12);
        }

        .hero-content h1 {
            margin-top: 18px;

            color: #ffffff;

            font-size: clamp(34px, 5vw, 58px);
            line-height: 1.05;

            max-width: 760px;

            font-weight: 800;
            letter-spacing: -1px;
        }

        .hero-content h1 span {
            color: #a8e8c5;
        }

        .hero-content p {
            max-width: 690px;

            margin-top: 17px;

            color: rgba(255, 255, 255, 0.86);

            font-size: 16px;
            line-height: 1.7;
        }

        /* =========================================================
           PROGRESS
        ========================================================= */

        .progress-wrap {
            position: relative;
            z-index: 5;

            width: min(1050px, 92%);
            margin: -29px auto 0;
        }

        .progress-card {
            background: rgba(255, 255, 255, 0.97);

            border: 1px solid rgba(255, 255, 255, 0.8);

            border-radius: 16px;

            padding: 20px 28px;

            box-shadow:
                0 14px 40px rgba(0, 0, 0, 0.12);
        }

        .progress {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .progress-step {
            display: flex;
            align-items: center;
            gap: 9px;

            color: #8a938e;

            font-size: 12px;
            font-weight: 700;

            white-space: nowrap;
        }

        .progress-number {
            width: 29px;
            height: 29px;

            display: flex;
            justify-content: center;
            align-items: center;

            border-radius: 50%;

            background: #e8efeb;
            color: #77837c;

            font-size: 12px;
        }

        .progress-step.active {
            color: #006b3c;
        }

        .progress-step.active .progress-number {
            background: #006b3c;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(0, 107, 60, 0.20);
        }

        .progress-line {
            flex: 1;
            height: 2px;
            background: #e3e9e5;
        }

        /* =========================================================
           MAIN CONTENT
        ========================================================= */

        .main {
            width: min(1050px, 92%);
            margin: 42px auto 70px;
        }

        .content-card {
            background: #ffffff;

            border: 1px solid #e5ebe7;
            border-radius: 20px;

            box-shadow:
                0 10px 35px rgba(23, 50, 36, 0.07);

            overflow: hidden;
        }

        .content-head {
            padding: 34px 38px 27px;

            border-bottom: 1px solid #edf1ee;
        }

        .section-kicker {
            color: #00804a;

            font-size: 12px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 1px;

            margin-bottom: 8px;
        }

        .content-head h2 {
            color: #1f2c25;

            font-size: 28px;
            line-height: 1.2;
        }

        .content-head p {
            max-width: 720px;

            margin-top: 9px;

            color: #707a75;

            font-size: 14px;
            line-height: 1.65;
        }

        .form-content {
            padding: 34px 38px 38px;
        }

        /* =========================================================
           QUESTIONS
        ========================================================= */

        .question {
            margin-bottom: 32px;
        }

        .question:last-of-type {
            margin-bottom: 0;
        }

        .question-heading {
            display: flex;
            align-items: center;
            gap: 11px;

            margin-bottom: 16px;
        }

        .question-number {
            width: 31px;
            height: 31px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: #e9f6ef;
            color: #006b3c;

            font-size: 13px;
            font-weight: 800;
        }

        .question-heading h3 {
            color: #26342c;
            font-size: 17px;
        }

        /* =========================================================
           STUDENT TYPE
        ========================================================= */

        .student-options {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 13px;
        }

        .student-option {
            position: relative;

            display: flex;
            align-items: flex-start;
            gap: 12px;

            min-height: 86px;

            padding: 17px;

            border: 1px solid #dfe7e2;
            border-radius: 13px;

            background: #ffffff;

            cursor: pointer;

            transition:
                transform 0.18s ease,
                border-color 0.18s ease,
                background 0.18s ease,
                box-shadow 0.18s ease;
        }

        .student-option:hover {
            transform: translateY(-2px);

            border-color: #76b899;

            background: #fbfefc;

            box-shadow:
                0 8px 22px rgba(0, 107, 60, 0.08);
        }

        .student-option.selected {
            border-color: #006b3c;

            background: #f1faf5;

            box-shadow:
                0 0 0 2px rgba(0, 107, 60, 0.07);
        }

        .student-option input {
            position: relative;

            margin-top: 3px;

            width: 17px;
            height: 17px;

            flex-shrink: 0;

            accent-color: #006b3c;
        }

        .student-option-content {
            flex: 1;
        }

        .student-option-title {
            color: #26342c;

            font-size: 14px;
            font-weight: 750;

            line-height: 1.35;
        }

        .student-option-description {
            color: #7b8580;

            font-size: 11px;
            line-height: 1.45;

            margin-top: 5px;
        }

        /* =========================================================
           SELECT
        ========================================================= */

        .field-wrap {
            position: relative;
        }

        select {
            width: 100%;

            padding: 15px 45px 15px 16px;

            border: 1px solid #d6e0da;
            border-radius: 11px;

            background: #ffffff;

            color: #26342c;

            font-family: inherit;
            font-size: 14px;

            outline: none;

            appearance: none;

            cursor: pointer;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .field-wrap::after {
            content: "⌄";

            position: absolute;

            right: 17px;
            top: 50%;

            transform: translateY(-54%);

            color: #006b3c;

            font-size: 20px;

            pointer-events: none;
        }

        select:focus {
            border-color: #006b3c;

            box-shadow:
                0 0 0 4px rgba(0, 107, 60, 0.08);
        }

        .hint {
            margin-top: 8px;

            color: #89928d;

            font-size: 11px;
        }

        /* =========================================================
           REQUIREMENTS
        ========================================================= */

        .requirements-box {
            display: none;

            margin-top: 5px;

            border: 1px solid #dce8e1;
            border-radius: 15px;

            overflow: hidden;

            background: #f9fcfa;
        }

        .requirements-header {
            padding: 20px 22px;

            background:
                linear-gradient(
                    135deg,
                    #006b3c,
                    #008b50
                );

            color: #ffffff;
        }

        .requirements-header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .requirements-header h3 {
            color: #ffffff;
            font-size: 17px;
        }

        .requirements-header p {
            margin-top: 4px;

            color: rgba(255, 255, 255, 0.82);

            font-size: 12px;
        }

        .requirements-badge {
            flex-shrink: 0;

            padding: 7px 11px;

            border-radius: 999px;

            background: rgba(255, 255, 255, 0.13);

            border: 1px solid rgba(255, 255, 255, 0.20);

            font-size: 11px;
            font-weight: 700;
        }

        .requirements-list {
            padding: 16px;
        }

        .requirement-item {
            display: flex;
            align-items: flex-start;
            gap: 13px;

            padding: 15px;

            background: #ffffff;

            border: 1px solid #e2e9e5;
            border-radius: 11px;

            margin-bottom: 10px;

            transition:
                border-color 0.18s ease,
                box-shadow 0.18s ease;
        }

        .requirement-item:last-child {
            margin-bottom: 0;
        }

        .requirement-item:hover {
            border-color: #b9d5c4;

            box-shadow:
                0 5px 16px rgba(0, 107, 60, 0.05);
        }

        .requirement-number {
            min-width: 29px;
            width: 29px;
            height: 29px;

            border-radius: 9px;

            background: #e8f5ed;
            color: #006b3c;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 12px;
            font-weight: 800;
        }

        .requirement-info {
            flex: 1;
        }

        .requirement-name {
            color: #27342d;

            font-size: 13px;
            font-weight: 750;

            margin-bottom: 4px;
        }

        .requirement-description {
            color: #7b8580;

            font-size: 12px;
            line-height: 1.55;
        }

        .no-requirements {
            padding: 25px;

            text-align: center;

            color: #7b8580;

            background: #ffffff;

            border: 1px dashed #d5e0da;
            border-radius: 10px;

            font-size: 13px;
        }

        /* =========================================================
           INFO BOX
        ========================================================= */

        .info-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;

            margin-top: 25px;

            padding: 15px 17px;

            border: 1px solid #dce9e1;
            border-radius: 11px;

            background: #f4faf6;

            color: #59665e;

            font-size: 12px;
            line-height: 1.6;
        }

        .info-icon {
            width: 23px;
            height: 23px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #dcefe4;
            color: #006b3c;

            font-weight: 800;
            font-size: 12px;
        }

        /* =========================================================
           BUTTONS
        ========================================================= */

        .buttons {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-top: 30px;

            padding-top: 25px;

            border-top: 1px solid #edf1ee;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 13px 18px;

            border: 1px solid #dce4df;
            border-radius: 10px;

            color: #52605a;

            text-decoration: none;

            font-size: 13px;
            font-weight: 650;

            transition:
                border-color 0.18s ease,
                background 0.18s ease;
        }

        .back:hover {
            border-color: #b9c9c0;
            background: #f8faf9;
        }

        .continue {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            min-width: 155px;

            padding: 14px 23px;

            border: none;
            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #006b3c,
                    #00864c
                );

            color: #ffffff;

            font-family: inherit;
            font-size: 13px;
            font-weight: 750;

            cursor: pointer;

            box-shadow:
                0 7px 17px rgba(0, 107, 60, 0.18);

            transition:
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }

        .continue:hover {
            transform: translateY(-2px);

            box-shadow:
                0 10px 22px rgba(0, 107, 60, 0.24);
        }

        .continue-arrow {
            font-size: 17px;
            line-height: 1;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        footer {
            background: #004d2b;

            color: rgba(255, 255, 255, 0.75);

            padding: 28px 20px;

            text-align: center;

            font-size: 12px;
        }

        .footer-title {
            color: #ffffff;

            font-size: 14px;
            font-weight: 700;

            margin-bottom: 5px;
        }

        .footer-subtitle {
            color: rgba(255, 255, 255, 0.60);
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {

            .top-header-inner {
                grid-template-columns: 1fr auto;
                padding: 13px 0;
            }

            .smart-brand {
                grid-column: 1 / -1;
                grid-row: 1;
            }

            .university-brand {
                grid-column: 1;
                grid-row: 2;
            }

            .ccsict-brand {
                grid-column: 2;
                grid-row: 2;
            }

            .smart-brand img {
                width: 165px;
            }

            .student-options {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 700px) {

            .top-header-inner {
                display: flex;
                flex-direction: column;

                text-align: center;

                gap: 15px;

                padding: 18px 0;
            }

            .university-brand,
            .ccsict-brand {
                justify-content: center;
            }

            .ccsict-brand {
                text-align: center;
            }

            .university-brand img {
                width: 58px;
                height: 58px;
            }

            .ccsict-brand img {
                width: 52px;
                height: 52px;
            }

            .university-brand h1 {
                font-size: 18px;
            }

            .university-brand p {
                font-size: 12px;
            }

            .nav-content {
                width: 100%;
            }

            nav a {
                padding: 13px 10px;
                font-size: 12px;
            }

            .page-hero {
                min-height: 285px;
            }

            .hero-content {
                padding: 55px 0;
            }

            .hero-content h1 {
                font-size: 38px;
            }

            .hero-content p {
                font-size: 14px;
            }

            .progress-card {
                padding: 17px;
            }

            .progress-step {
                font-size: 10px;
            }

            .progress-number {
                width: 26px;
                height: 26px;
            }

            .progress-line {
                min-width: 10px;
            }

            .main {
                margin-top: 30px;
            }

            .content-head,
            .form-content {
                padding-left: 22px;
                padding-right: 22px;
            }

            .content-head h2 {
                font-size: 24px;
            }

            .student-options {
                grid-template-columns: 1fr;
            }

            .requirements-header-inner {
                align-items: flex-start;
                flex-direction: column;
            }

        }

        @media (max-width: 480px) {

            .progress {
                justify-content: center;
            }

            .progress-step {
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }

            .progress-step span:last-child {
                display: none;
            }

            .progress-line {
                max-width: 25px;
            }

            .hero-content h1 {
                font-size: 33px;
            }

            .hero-content p {
                font-size: 13px;
            }

            .content-head {
                padding: 27px 18px 22px;
            }

            .form-content {
                padding: 25px 18px 28px;
            }

            .buttons {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .continue,
            .back {
                width: 100%;
                justify-content: center;
            }

        }

    </style>

</head>

<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="top-header">

    <div class="top-header-inner">


        <!-- UNIVERSITY -->

        <div class="university-brand">

            <img
                src="assets/isu-logo.png"
                alt="Isabela State University Logo"
            >

            <div>

                <h1>
                    Isabela State University
                </h1>

                <p>
                    Cauayan Campus
                </p>

            </div>

        </div>


        <!-- SMART ENROLL -->

        <div class="smart-brand">

            <img
                src="assets/smart-enroll-logo.png?v=8"
                alt="ISU SmartEnroll"
            >

        </div>


        <!-- CCSI(C) -->

        <div class="ccsict-brand">

            <div>

                <h2>
                    College of Computing Studies<br>
                    Information and Communication Technology
                </h2>

                <p>
                    Isabela State University
                </p>

            </div>

            <img
                src="assets/ccsict-logo.png"
                alt="CCSICT Logo"
            >

        </div>

    </div>

</header>


<!-- =========================================================
     NAVIGATION
========================================================= -->

<nav>

    <div class="nav-content">

        <a href="index.php">
            Home
        </a>

        <a
            href="enrollment.php"
            class="active"
        >
            Enrollment Guide
        </a>

        <a href="requirements.php">
            Requirements
        </a>

        <a href="#">
            Procedures
        </a>

        <a href="#">
            Announcements
        </a>

    </div>

</nav>


<!-- =========================================================
     HERO
========================================================= -->

<section class="page-hero">

    <div class="hero-content">

        <div class="hero-label">

            <span></span>

            ISU SmartEnroll

        </div>


        <h1>

            Start your enrollment
            <span>the simple way.</span>

        </h1>


        <p>

            Tell us what type of student you are, choose your
            program, and select your academic term. SmartEnroll
            will guide you through the next steps.

        </p>

    </div>

</section>


<!-- =========================================================
     PROGRESS
========================================================= -->

<div class="progress-wrap">

    <div class="progress-card">

        <div class="progress">

            <div class="progress-step active">

                <div class="progress-number">
                    1
                </div>

                <span>
                    Selection
                </span>

            </div>


            <div class="progress-line"></div>


            <div class="progress-step">

                <div class="progress-number">
                    2
                </div>

                <span>
                    Guidance
                </span>

            </div>


            <div class="progress-line"></div>


            <div class="progress-step">

                <div class="progress-number">
                    3
                </div>

                <span>
                    Information
                </span>

            </div>


            <div class="progress-line"></div>


            <div class="progress-step">

                <div class="progress-number">
                    4
                </div>

                <span>
                    Requirements
                </span>

            </div>


            <div class="progress-line"></div>


            <div class="progress-step">

                <div class="progress-number">
                    5
                </div>

                <span>
                    Review
                </span>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">

    <div class="content-card">


        <!-- CONTENT HEADER -->

        <div class="content-head">

            <div class="section-kicker">
                Enrollment Application
            </div>

            <h2>
                Let's get started
            </h2>

            <p>
                Select the information below so SmartEnroll can
                provide the appropriate enrollment guidance and
                requirements for your application.
            </p>

        </div>


        <!-- FORM -->

        <div class="form-content">

            <form
                action="result.php"
                method="POST"
            >


                <!-- =================================================
                     STUDENT TYPE
                ================================================= -->

                <div class="question">

                    <div class="question-heading">

                        <div class="question-number">
                            1
                        </div>

                        <h3>
                            What type of student are you?
                        </h3>

                    </div>


                    <div class="student-options">


                        <label class="student-option">

                            <input
                                type="radio"
                                name="student_type"
                                value="Freshman"
                                required
                            >

                            <div class="student-option-content">

                                <div class="student-option-title">
                                    Incoming Freshman
                                </div>

                                <div class="student-option-description">
                                    New student entering college for the first time.
                                </div>

                            </div>

                        </label>


                        <label class="student-option">

                            <input
                                type="radio"
                                name="student_type"
                                value="Transferee"
                            >

                            <div class="student-option-content">

                                <div class="student-option-title">
                                    Transferee
                                </div>

                                <div class="student-option-description">
                                    Student transferring from another institution.
                                </div>

                            </div>

                        </label>


                        <label class="student-option">

                            <input
                                type="radio"
                                name="student_type"
                                value="Continuing"
                            >

                            <div class="student-option-content">

                                <div class="student-option-title">
                                    Continuing / Old Student
                                </div>

                                <div class="student-option-description">
                                    Currently enrolled ISU student continuing studies.
                                </div>

                            </div>

                        </label>


                        <label class="student-option">

                            <input
                                type="radio"
                                name="student_type"
                                value="Returning"
                            >

                            <div class="student-option-content">

                                <div class="student-option-title">
                                    Returning Student
                                </div>

                                <div class="student-option-description">
                                    Former student returning after an absence.
                                </div>

                            </div>

                        </label>


                        <label class="student-option">

                            <input
                                type="radio"
                                name="student_type"
                                value="CPE"
                            >

                            <div class="student-option-content">

                                <div class="student-option-title">
                                    CPE Student
                                </div>

                                <div class="student-option-description">
                                    Student under continuing professional education.
                                </div>

                            </div>

                        </label>


                        <label class="student-option">

                            <input
                                type="radio"
                                name="student_type"
                                value="Foreign Student"
                            >

                            <div class="student-option-content">

                                <div class="student-option-title">
                                    Foreign Student
                                </div>

                                <div class="student-option-description">
                                    International student applying for enrollment.
                                </div>

                            </div>

                        </label>

                    </div>

                </div>


                <!-- =================================================
                     PROGRAM
                ================================================= -->

                <div class="question">

                    <div class="question-heading">

                        <div class="question-number">
                            2
                        </div>

                        <h3>
                            Choose your program or course
                        </h3>

                    </div>


                    <div class="field-wrap">

                        <select
                            name="program_id"
                            id="program"
                            required
                        >

                            <option value="">
                                Select Program / Course
                            </option>

                            <?php foreach ($programs as $program): ?>

                                <option
                                    value="<?= htmlspecialchars($program['id']) ?>"
                                >

                                    <?= htmlspecialchars($program['code']) ?>
                                    -
                                    <?= htmlspecialchars($program['name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <p class="hint">
                        Programs are loaded directly from the SmartEnroll database.
                    </p>

                </div>


                <!-- =================================================
                     TERM
                ================================================= -->

                <div class="question">

                    <div class="question-heading">

                        <div class="question-number">
                            3
                        </div>

                        <h3>
                            Select your academic term
                        </h3>

                    </div>


                    <div class="field-wrap">

                        <select
                            name="term"
                            required
                        >

                            <option value="">
                                Select Academic Term
                            </option>

                            <option value="1st Semester">
                                1st Semester
                            </option>

                            <option value="2nd Semester">
                                2nd Semester
                            </option>

                            <option value="Mid-Year">
                                Mid-Year
                            </option>

                        </select>

                    </div>

                </div>


                <!-- =================================================
                     REQUIREMENTS
                ================================================= -->

                <div
                    class="requirements-box"
                    id="requirementsBox"
                >

                    <div class="requirements-header">

                        <div class="requirements-header-inner">

                            <div>

                                <h3>
                                    Applicable Requirements
                                </h3>

                                <p id="requirementsSubtitle">
                                    Select a student type to view requirements.
                                </p>

                            </div>

                            <div
                                class="requirements-badge"
                                id="requirementsBadge"
                            >
                                Requirements
                            </div>

                        </div>

                    </div>


                    <div
                        class="requirements-list"
                        id="requirementsList"
                    >

                    </div>

                </div>


                <!-- =================================================
                     INFORMATION
                ================================================= -->

                <div class="info-box">

                    <div class="info-icon">
                        i
                    </div>

                    <div>

                        Requirements shown here are based on the
                        active requirement records configured in
                        SmartEnroll. Requirements may vary depending
                        on the student's admission category and
                        applicable university, college, or program
                        requirements.

                    </div>

                </div>


                <!-- =================================================
                     BUTTONS
                ================================================= -->

                <div class="buttons">

                    <a
                        href="index.php"
                        class="back"
                    >
                        <span>←</span>
                        Back to Home
                    </a>


                    <button
                        type="submit"
                        class="continue"
                    >

                        Continue

                        <span class="continue-arrow">
                            →
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</main>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="footer-title">
        ISU SmartEnroll
    </div>

    <div class="footer-subtitle">
        Isabela State University - Cauayan Campus
    </div>

</footer>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/*
|--------------------------------------------------------------------------
| REQUIREMENTS FROM DATABASE
|--------------------------------------------------------------------------
*/

const requirements = <?= json_encode(
    $requirements,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


/*
|--------------------------------------------------------------------------
| STUDENT TYPE LABELS
|--------------------------------------------------------------------------
*/

const studentTypeLabels = {

    "Freshman": "Incoming Freshman",

    "Transferee": "Transferee",

    "Continuing": "Continuing / Old Student",

    "Returning": "Returning Student",

    "CPE": "CPE Student",

    "Foreign Student": "Foreign Student"

};


/*
|--------------------------------------------------------------------------
| STUDENT TYPE SELECTION
|--------------------------------------------------------------------------
*/

const studentOptions =
    document.querySelectorAll(
        '.student-option'
    );


studentOptions.forEach(function(option) {

    const radio =
        option.querySelector(
            'input[type="radio"]'
        );


    radio.addEventListener(
        'change',
        function() {

            studentOptions.forEach(
                function(item) {

                    item.classList.remove(
                        'selected'
                    );

                }
            );


            if (radio.checked) {

                option.classList.add(
                    'selected'
                );

            }


            displayRequirements(
                radio.value
            );

        }
    );

});


/*
|--------------------------------------------------------------------------
| DISPLAY REQUIREMENTS
|--------------------------------------------------------------------------
*/

function displayRequirements(studentType) {

    const box =
        document.getElementById(
            "requirementsBox"
        );

    const list =
        document.getElementById(
            "requirementsList"
        );

    const subtitle =
        document.getElementById(
            "requirementsSubtitle"
        );

    const badge =
        document.getElementById(
            "requirementsBadge"
        );


    list.innerHTML = "";


    const matchingRequirements =
        requirements.filter(
            function(requirement) {

                return (
                    requirement.student_type
                        .trim()
                        .toLowerCase()
                    ===
                    studentType
                        .trim()
                        .toLowerCase()
                );

            }
        );


    box.style.display = "block";


    subtitle.textContent =
        "Requirements for " +
        (
            studentTypeLabels[studentType]
            || studentType
        );


    badge.textContent =
        matchingRequirements.length +
        (
            matchingRequirements.length === 1
                ? " Requirement"
                : " Requirements"
        );


    if (
        matchingRequirements.length === 0
    ) {

        const empty =
            document.createElement(
                "div"
            );

        empty.className =
            "no-requirements";

        empty.textContent =
            "No active requirements are currently configured for this student type.";

        list.appendChild(
            empty
        );

        return;

    }


    matchingRequirements.forEach(
        function(requirement, index) {

            const item =
                document.createElement(
                    "div"
                );

            item.className =
                "requirement-item";


            const number =
                document.createElement(
                    "div"
                );

            number.className =
                "requirement-number";

            number.textContent =
                index + 1;


            const information =
                document.createElement(
                    "div"
                );

            information.className =
                "requirement-info";


            const name =
                document.createElement(
                    "div"
                );

            name.className =
                "requirement-name";

            name.textContent =
                requirement.requirement_name;


            const description =
                document.createElement(
                    "div"
                );

            description.className =
                "requirement-description";

            description.textContent =
                requirement.description
                || "No description provided.";


            information.appendChild(
                name
            );

            information.appendChild(
                description
            );


            item.appendChild(
                number
            );

            item.appendChild(
                information
            );


            list.appendChild(
                item
            );

        }
    );

}

</script>

</body>

</html>