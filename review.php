<?php

session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}

if (
    !isset($_SESSION["enrollment_application"]) ||
    !isset($_SESSION["student_information"])
) {
    header("Location: enrollment.php");
    exit;
}

$application = $_SESSION["enrollment_application"];
$student = $_SESSION["student_information"];

$student_type = $application["student_type"] ?? "";
$program_name = $application["program_name"] ?? "";
$program_code = $application["program_code"] ?? "";
$term = $application["term"] ?? "";

$requirements = $_SESSION["student_requirements"] ?? [];

$full_name = trim(
    ($student["first_name"] ?? "") . " " .
    ($student["middle_name"] ?? "") . " " .
    ($student["last_name"] ?? "")
);

$full_name = preg_replace('/\s+/', ' ', $full_name);

$student_number = $student["student_number"] ?? "";
$year_level = $student["year_level"] ?? "";
$birth_date = $student["birth_date"] ?? "";
$gender = $student["gender"] ?? "";
$contact_number = $student["contact_number"] ?? "";
$email = $student["email"] ?? "";
$address = $student["address"] ?? "";

$applicant_id = $_SESSION["applicant_id"] ?? "";

function formatDate($date)
{
    if (empty($date)) {
        return "Not provided";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return htmlspecialchars($date);
    }

    return date("F d, Y", $timestamp);
}

function formatTerm($term)
{
    $terms = [
        "1st Semester" => "1st Semester",
        "2nd Semester" => "2nd Semester",
        "Summer" => "Summer"
    ];

    return $terms[$term] ?? $term;
}

function formatStudentType($type)
{
    $types = [
        "Freshman" => "Incoming Freshman",
        "Transferee" => "Transferee",
        "Continuing" => "Continuing Student",
        "Returning" => "Returning Student",
        "CPE" => "Cross-Enrollee / CPE",
        "Foreign Student" => "Foreign Student"
    ];

    return $types[$type] ?? $type;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Review Application Guide | SmartEnroll</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7f5;
            color: #183b25;
            min-height: 100vh;
        }

        /* =========================
           HEADER
        ========================= */

        .top-header {
            background: #ffffff;
            min-height: 105px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 45px;
            position: relative;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            z-index: 10;
        }

        .brand-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-left img {
            width: 66px;
            height: 66px;
            object-fit: contain;
        }

        .brand-text h1 {
            font-size: 18px;
            color: #126b36;
            font-weight: 800;
            line-height: 1.2;
        }

        .brand-text p {
            font-size: 13px;
            color: #666;
            margin-top: 3px;
        }

        .brand-center {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .brand-center img {
            width: 110px;
            height: 110px;
            object-fit: contain;
        }

        .brand-right {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: right;
        }

        .brand-right-text {
            line-height: 1.25;
        }

        .brand-right-text strong {
            display: block;
            font-size: 12px;
            color: #176b39;
        }

        .brand-right-text span {
            display: block;
            font-size: 10px;
            color: #555;
        }

        .brand-right img {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        /* =========================
           NAVIGATION
        ========================= */

        .navbar {
            background: #116b36;
            min-height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .nav-inner {
            width: 100%;
            max-width: 1250px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .nav-link {
            color: #ffffff;
            text-decoration: none;
            padding: 11px 15px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.18);
        }

        .nav-account {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-size: 13px;
        }

        .account-info {
            text-align: right;
            line-height: 1.25;
        }

        .account-name {
            font-weight: 700;
        }

        .account-id {
            font-size: 11px;
            opacity: 0.85;
        }

        .logout-btn {
            border: none;
            background: #c62828;
            color: #ffffff;
            padding: 9px 15px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
        }

        .logout-btn:hover {
            background: #a91f1f;
        }

        /* =========================
           PAGE HERO
        ========================= */

        .page-hero {
            min-height: 250px;
            background:
                linear-gradient(
                    rgba(7, 55, 27, 0.84),
                    rgba(7, 55, 27, 0.90)
                ),
                url("assets/background.jpg") center/cover no-repeat;

            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 45px 20px;
        }

        .hero-content {
            max-width: 850px;
            color: #ffffff;
        }

        .hero-content h2 {
            font-size: 38px;
            margin-bottom: 10px;
            font-weight: 800;
        }

        .hero-content p {
            font-size: 16px;
            opacity: 0.92;
        }

        /* =========================
           PROGRESS
        ========================= */

        .progress-wrapper {
            background: #ffffff;
            padding: 24px 20px;
            border-bottom: 1px solid #e6e6e6;
        }

        .progress {
            max-width: 950px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .step {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .step:last-child {
            flex: 0;
        }

        .step-circle {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dce7df;
            color: #52705d;
            font-weight: 800;
            font-size: 13px;
            position: relative;
            z-index: 2;
        }

        .step.completed .step-circle,
        .step.active .step-circle {
            background: #126b36;
            color: #ffffff;
        }

        .step-label {
            margin-left: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #6d7d73;
            white-space: nowrap;
        }

        .step.completed .step-label,
        .step.active .step-label {
            color: #126b36;
        }

        .step-line {
            height: 3px;
            flex: 1;
            background: #dce7df;
            margin: 0 10px;
        }

        .step-line.completed {
            background: #126b36;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            max-width: 1050px;
            margin: 45px auto;
            padding: 0 20px;
        }

        .success-box {
            background: #eaf7ef;
            border: 1px solid #b9dec5;
            border-radius: 15px;
            padding: 26px;
            text-align: center;
            margin-bottom: 25px;
        }

        .success-icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: #126b36;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 28px;
            font-weight: 800;
        }

        .success-box h2 {
            color: #126b36;
            font-size: 25px;
            margin-bottom: 8px;
        }

        .success-box p {
            color: #456050;
            font-size: 14px;
            line-height: 1.6;
        }

        .card {
            background: #ffffff;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 22px;
            box-shadow: 0 8px 30px rgba(16, 60, 32, 0.08);
            border: 1px solid #e5ebe7;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e6ece8;
        }

        .card-title-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: #e8f4ec;
            color: #126b36;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .card-title h3 {
            color: #173e27;
            font-size: 19px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .info-item {
            background: #f8faf9;
            border: 1px solid #e5ebe7;
            border-radius: 10px;
            padding: 15px;
        }

        .info-label {
            font-size: 11px;
            color: #718077;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            color: #193d28;
            font-weight: 700;
            word-break: break-word;
        }

        /* =========================
           REQUIREMENTS
        ========================= */

        .requirements-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .requirement {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #f8faf9;
            border: 1px solid #e2e9e4;
            padding: 14px 16px;
            border-radius: 10px;
        }

        .requirement-check {
            width: 25px;
            height: 25px;
            min-width: 25px;
            border-radius: 50%;
            background: #126b36;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
        }

        .requirement-content {
            flex: 1;
        }

        .requirement-text {
            font-size: 14px;
            color: #294534;
            font-weight: 700;
        }

        .requirement-description {
            margin-top: 5px;
            color: #68786e;
            font-size: 12px;
            line-height: 1.5;
        }

        .requirement-status {
            display: inline-block;
            margin-top: 7px;
            padding: 4px 8px;
            border-radius: 6px;
            background: #e8f4ec;
            color: #126b36;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .no-requirements {
            background: #f7f8f7;
            border: 1px dashed #c8d4cc;
            border-radius: 10px;
            padding: 20px;
            color: #637269;
            font-size: 14px;
            text-align: center;
        }

        /* =========================
           NOTICE
        ========================= */

        .notice {
            background: #fff8e5;
            border: 1px solid #efd58a;
            border-radius: 12px;
            padding: 18px;
            margin-top: 20px;
            color: #664f13;
            font-size: 13px;
            line-height: 1.6;
        }

        .notice strong {
            display: block;
            margin-bottom: 5px;
        }

        /* =========================
           FINAL MESSAGE
        ========================= */

        .final-message {
            background: #ffffff;
            border: 1px solid #dfe8e2;
            border-radius: 15px;
            padding: 22px;
            margin-bottom: 22px;
            text-align: center;
        }

        .final-message h3 {
            color: #126b36;
            margin-bottom: 7px;
            font-size: 18px;
        }

        .final-message p {
            color: #637269;
            font-size: 13px;
            line-height: 1.6;
        }

        /* =========================
           BUTTONS
        ========================= */

        .actions {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: none;
            cursor: pointer;
            border-radius: 9px;
            padding: 13px 22px;
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .btn-primary {
            background: #126b36;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #0d542a;
        }

        .btn-secondary {
            background: #edf2ee;
            color: #28513a;
        }

        .btn-secondary:hover {
            background: #dfe8e2;
        }

        .btn-print {
            background: #ffffff;
            color: #126b36;
            border: 1px solid #126b36;
        }

        .btn-print:hover {
            background: #edf7f0;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            background: #0b4e28;
            color: #dcece1;
            padding: 28px 20px;
            margin-top: 50px;
            text-align: center;
        }

        footer strong {
            display: block;
            color: #ffffff;
            margin-bottom: 5px;
        }

        footer p {
            font-size: 12px;
            opacity: 0.85;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .top-header {
                padding: 12px 20px;
            }

            .brand-right-text {
                display: none;
            }

            .brand-right img {
                width: 48px;
                height: 48px;
            }

            .nav-inner {
                flex-wrap: wrap;
            }

            .nav-link {
                padding: 9px 10px;
                font-size: 12px;
            }

            .nav-account {
                margin-left: 0;
            }

        }

        @media (max-width: 700px) {

            .top-header {
                min-height: 90px;
                padding: 10px 15px;
            }

            .brand-left img {
                width: 50px;
                height: 50px;
            }

            .brand-text h1 {
                font-size: 14px;
            }

            .brand-text p {
                font-size: 10px;
            }

            .brand-center img {
                width: 85px;
                height: 85px;
            }

            .brand-right {
                display: none;
            }

            .navbar {
                padding: 8px 10px;
            }

            .nav-inner {
                gap: 4px;
            }

            .nav-link {
                font-size: 11px;
                padding: 7px 8px;
            }

            .nav-account {
                width: 100%;
                justify-content: center;
                padding-top: 5px;
            }

            .account-info {
                text-align: center;
            }

            .page-hero {
                min-height: 200px;
            }

            .hero-content h2 {
                font-size: 28px;
            }

            .hero-content p {
                font-size: 14px;
            }

            .progress-wrapper {
                overflow-x: auto;
            }

            .progress {
                min-width: 650px;
            }

            .step-label {
                font-size: 10px;
            }

            .main {
                margin: 25px auto;
                padding: 0 12px;
            }

            .card {
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

        }

        /* =========================
           PRINT
        ========================= */

        @media print {

            .top-header,
            .navbar,
            .page-hero,
            .progress-wrapper,
            footer,
            .actions,
            .notice,
            .final-message {
                display: none !important;
            }

            body {
                background: #ffffff;
            }

            .main {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            .card,
            .success-box {
                box-shadow: none;
                border: 1px solid #ccc;
            }

        }

    </style>

</head>

<body>

<header class="top-header">

    <div class="brand-left">

        <img src="assets/isu-logo.png" alt="Isabela State University">

        <div class="brand-text">

            <h1>Isabela State University</h1>

            <p>Cauayan Campus</p>

        </div>

    </div>

    <div class="brand-center">

        <img src="assets/smart-enroll-logo.png?v=8" alt="SmartEnroll">

    </div>

    <div class="brand-right">

        <div class="brand-right-text">

            <strong>College of Computing Studies</strong>

            <span>Information and Communication Technology</span>

            <span>Isabela State University</span>

        </div>

        <img src="assets/ccsict-logo.png" alt="CCSICT">

    </div>

</header>

<nav class="navbar">

    <div class="nav-inner">

        <a href="index.php" class="nav-link">
            Home
        </a>

        <a href="enrollment.php" class="nav-link active">
            Enrollment Guide
        </a>

        <a href="requirements.php" class="nav-link">
            Requirements
        </a>

        <a href="procedures.php" class="nav-link">
            Procedures
        </a>

        <a href="announcements.php" class="nav-link">
            Announcements
        </a>

        <div class="nav-account">

            <div class="account-info">

                <div class="account-name">
                    <?= htmlspecialchars($_SESSION["full_name"] ?? "Student") ?>
                </div>

                <?php if (!empty($applicant_id)): ?>

                    <div class="account-id">
                        <?= htmlspecialchars($applicant_id) ?>
                    </div>

                <?php endif; ?>

            </div>

            <form action="index.php" method="POST">

                <button type="submit" name="logout" class="logout-btn">
                    Logout
                </button>

            </form>

        </div>

    </div>

</nav>

<section class="page-hero">

    <div class="hero-content">

        <h2>Review Your Information</h2>

        <p>
            Review your enrollment guide details and requirements before finishing.
        </p>

    </div>

</section>

<div class="progress-wrapper">

    <div class="progress">

        <div class="step completed">

            <div class="step-circle">✓</div>

            <div class="step-label">
                Selection
            </div>

        </div>

        <div class="step-line completed"></div>

        <div class="step completed">

            <div class="step-circle">✓</div>

            <div class="step-label">
                Guidance
            </div>

        </div>

        <div class="step-line completed"></div>

        <div class="step completed">

            <div class="step-circle">✓</div>

            <div class="step-label">
                Information
            </div>

        </div>

        <div class="step-line completed"></div>

        <div class="step completed">

            <div class="step-circle">✓</div>

            <div class="step-label">
                Requirements
            </div>

        </div>

        <div class="step-line completed"></div>

        <div class="step active">

            <div class="step-circle">
                5
            </div>

            <div class="step-label">
                Review
            </div>

        </div>

    </div>

</div>

<main class="main">

    <div class="success-box">

        <div class="success-icon">
            ✓
        </div>

        <h2>Application Guide Completed</h2>

        <p>
            You have completed the SmartEnroll enrollment guidance process.
            Please review the information below and prepare the listed requirements
            for the actual enrollment process.
        </p>

    </div>

    <!-- =========================
         ENROLLMENT DETAILS
    ========================= -->

    <section class="card">

        <div class="card-title">

            <div class="card-title-icon">
                01
            </div>

            <h3>Enrollment Details</h3>

        </div>

        <div class="info-grid">

            <div class="info-item">

                <div class="info-label">
                    Student Type
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(formatStudentType($student_type)) ?>
                </div>

            </div>

            <div class="info-item">

                <div class="info-label">
                    Academic Program
                </div>

                <div class="info-value">

                    <?= htmlspecialchars($program_name) ?>

                    <?php if (!empty($program_code)): ?>

                        (<?= htmlspecialchars($program_code) ?>)

                    <?php endif; ?>

                </div>

            </div>

            <div class="info-item">

                <div class="info-label">
                    Academic Term
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(formatTerm($term)) ?>
                </div>

            </div>

            <?php if (!empty($applicant_id)): ?>

                <div class="info-item">

                    <div class="info-label">
                        Applicant ID
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars($applicant_id) ?>
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </section>

    <!-- =========================
         STUDENT INFORMATION
    ========================= -->

    <section class="card">

        <div class="card-title">

            <div class="card-title-icon">
                02
            </div>

            <h3>Student Information</h3>

        </div>

        <div class="info-grid">

            <div class="info-item">

                <div class="info-label">
                    Full Name
                </div>

                <div class="info-value">
                    <?= htmlspecialchars($full_name ?: "Not provided") ?>
                </div>

            </div>

            <?php if (!empty($student_number)): ?>

                <div class="info-item">

                    <div class="info-label">
                        Student Number
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars($student_number) ?>
                    </div>

                </div>

            <?php endif; ?>

            <?php if (!empty($year_level)): ?>

                <div class="info-item">

                    <div class="info-label">
                        Year Level
                    </div>

                    <div class="info-value">
                        <?= htmlspecialchars($year_level) ?>
                    </div>

                </div>

            <?php endif; ?>

            <div class="info-item">

                <div class="info-label">
                    Date of Birth
                </div>

                <div class="info-value">
                    <?= htmlspecialchars(formatDate($birth_date)) ?>
                </div>

            </div>

            <div class="info-item">

                <div class="info-label">
                    Gender
                </div>

                <div class="info-value">
                    <?= htmlspecialchars($gender ?: "Not provided") ?>
                </div>

            </div>

            <div class="info-item">

                <div class="info-label">
                    Contact Number
                </div>

                <div class="info-value">
                    <?= htmlspecialchars($contact_number ?: "Not provided") ?>
                </div>

            </div>

            <div class="info-item">

                <div class="info-label">
                    Email Address
                </div>

                <div class="info-value">
                    <?= htmlspecialchars($email ?: "Not provided") ?>
                </div>

            </div>

            <div class="info-item">

                <div class="info-label">
                    Address
                </div>

                <div class="info-value">
                    <?= htmlspecialchars($address ?: "Not provided") ?>
                </div>

            </div>

        </div>

    </section>

    <!-- =========================
         REQUIREMENTS
    ========================= -->

    <section class="card">

        <div class="card-title">

            <div class="card-title-icon">
                03
            </div>

            <h3>Required Documents</h3>

        </div>

        <?php if (!empty($requirements)): ?>

            <div class="requirements-list">

                <?php foreach ($requirements as $requirement): ?>

                    <?php

                    if (is_array($requirement)) {

                        $requirement_name =
                            $requirement["requirement_name"]
                            ?? $requirement["name"]
                            ?? $requirement["title"]
                            ?? "Requirement";

                        $description =
                            $requirement["description"]
                            ?? "";

                        $status =
                            $requirement["status"]
                            ?? "";

                    } else {

                        $requirement_name = $requirement;
                        $description = "";
                        $status = "";

                    }

                    ?>

                    <div class="requirement">

                        <div class="requirement-check">
                            ✓
                        </div>

                        <div class="requirement-content">

                            <div class="requirement-text">
                                <?= htmlspecialchars($requirement_name) ?>
                            </div>

                            <?php if (!empty($description)): ?>

                                <div class="requirement-description">
                                    <?= htmlspecialchars($description) ?>
                                </div>

                            <?php endif; ?>

                            <?php if (!empty($status)): ?>

                                <span class="requirement-status">
                                    <?= htmlspecialchars($status) ?>
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="no-requirements">

                No specific requirements were selected during this session.
                Please check the Requirements page for the applicable documents.

            </div>

        <?php endif; ?>

        <div class="notice">

            <strong>Important Reminder</strong>

            SmartEnroll provides enrollment guidance and helps you identify
            the requirements applicable to your student type. This page does
            not constitute official enrollment or admission confirmation.
            Please follow the official instructions of Isabela State University
            when completing your actual enrollment.

        </div>

    </section>

    <!-- =========================
         FINAL MESSAGE
    ========================= -->

    <div class="final-message">

        <h3>You're All Set!</h3>

        <p>
            Your SmartEnroll guide is complete. Keep this page or print the
            requirements list as a reference when preparing your documents.
            For actual enrollment, admission, submission, and verification,
            follow the official process of Isabela State University.
        </p>

    </div>

    <!-- =========================
         ACTIONS
    ========================= -->

    <div class="actions">

        <a href="student_requirements.php" class="btn btn-secondary">
            ← Back to Requirements
        </a>

        <button
            type="button"
            class="btn btn-print"
            onclick="window.print()"
        >
            🖨 Print Requirements
        </button>

        <a href="index.php" class="btn btn-primary">
            Finish & Return Home →
        </a>

    </div>

</main>

<footer>

    <strong>
        SmartEnroll — Isabela State University Cauayan Campus
    </strong>

    <p>
        Enrollment guidance and requirements information system.
    </p>

</footer>

</body>

</html>