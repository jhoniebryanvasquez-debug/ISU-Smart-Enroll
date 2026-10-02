<?php

session_start();
require_once "db.php";

/*
|--------------------------------------------------------------------------
| ISU SmartEnroll - Student Requirements
|--------------------------------------------------------------------------
| Student-facing requirements page.
|
| Flow:
| enrollment.php
|      ↓
| result.php
|      ↓
| student_information.php
|      ↓
| student_requirements.php
|      ↓
| review.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SECURITY / FLOW CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['enrollment_application']) ||
    !is_array($_SESSION['enrollment_application'])
) {
    header("Location: enrollment.php");
    exit;
}

if (
    !isset($_SESSION['student_information']) ||
    !is_array($_SESSION['student_information'])
) {
    header("Location: student_information.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET ENROLLMENT INFORMATION
|--------------------------------------------------------------------------
*/

$application = $_SESSION['enrollment_application'];
$student_info = $_SESSION['student_information'];

$student_type = trim($application['student_type'] ?? '');
$program_id   = intval($application['program_id'] ?? 0);
$program_code = trim($application['program_code'] ?? '');
$program_name = trim($application['program_name'] ?? '');
$term         = trim($application['term'] ?? '');


/*
|--------------------------------------------------------------------------
| VALIDATE ENROLLMENT SESSION
|--------------------------------------------------------------------------
*/

if (
    $student_type === '' ||
    $program_id <= 0 ||
    $program_name === '' ||
    $term === ''
) {
    header("Location: enrollment.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| MAP ENROLLMENT STUDENT TYPE
|--------------------------------------------------------------------------
|
| Enrollment flow:
|
| Freshman
| Transferee
| Continuing
| Returning
| CPE
| Foreign Student
|
| Database requirement categories:
|
| First Year Student
| Regular / Continuing Student
| Transferee Student
| Returning Student
| Irregular Student
| Foreign Student
|
|--------------------------------------------------------------------------
*/

$requirement_student_type = '';

switch ($student_type) {

    case 'Freshman':
        $requirement_student_type = 'First Year Student';
        break;

    case 'Continuing':
        $requirement_student_type = 'Regular / Continuing Student';
        break;

    case 'Transferee':
        $requirement_student_type = 'Transferee Student';
        break;

    case 'Returning':
        $requirement_student_type = 'Returning Student';
        break;

    case 'CPE':
        $requirement_student_type = 'Irregular Student';
        break;

    case 'Foreign Student':
        $requirement_student_type = 'Foreign Student';
        break;

    default:
        $requirement_student_type = '';
        break;
}


/*
|--------------------------------------------------------------------------
| HANDLE REQUIREMENT CONFIRMATION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $confirmed_requirements = $_POST['requirements'] ?? [];

    if (!is_array($confirmed_requirements)) {
        $confirmed_requirements = [];
    }


    /*
    |--------------------------------------------------------------------------
    | Convert requirement IDs to integers
    |--------------------------------------------------------------------------
    */

    $confirmed_requirements = array_map(
        'intval',
        $confirmed_requirements
    );


    $confirmed_requirements = array_values(
        array_unique(
            array_filter(
                $confirmed_requirements,
                function ($id) {
                    return $id > 0;
                }
            )
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Get valid active requirement IDs
    |--------------------------------------------------------------------------
    */

    $valid_requirement_ids = [];

    if ($requirement_student_type !== '') {

        $stmt = $conn->prepare("
            SELECT id
            FROM requirements
            WHERE student_type = ?
            AND status = 'Active'
            ORDER BY id ASC
        ");

        if ($stmt) {

            $stmt->bind_param(
                "s",
                $requirement_student_type
            );

            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {

                $valid_requirement_ids[] = (int)$row['id'];

            }

            $stmt->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Keep only valid IDs
    |--------------------------------------------------------------------------
    */

    $confirmed_requirements = array_values(
        array_intersect(
            $confirmed_requirements,
            $valid_requirement_ids
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Save to session
    |--------------------------------------------------------------------------
    */

    $_SESSION['student_requirements'] = [
        'student_type' => $student_type,
        'requirement_student_type' => $requirement_student_type,
        'confirmed' => $confirmed_requirements
    ];


    /*
    |--------------------------------------------------------------------------
    | Continue to Review
    |--------------------------------------------------------------------------
    */

    header("Location: review.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET ACTIVE REQUIREMENTS
|--------------------------------------------------------------------------
*/

$requirements = [];
$requirements_error = '';

if ($requirement_student_type !== '') {

    $stmt = $conn->prepare("
        SELECT
            id,
            requirement_name,
            description,
            student_type,
            status
        FROM requirements
        WHERE student_type = ?
        AND status = 'Active'
        ORDER BY id ASC
    ");

    if ($stmt) {

        $stmt->bind_param(
            "s",
            $requirement_student_type
        );

        if ($stmt->execute()) {

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {

                $requirements[] = $row;

            }

        } else {

            $requirements_error = "Unable to load requirements.";

        }

        $stmt->close();

    } else {

        $requirements_error = "Unable to load requirements.";

    }

} else {

    $requirements_error =
        "No specific requirements are currently configured for this student type.";

}


/*
|--------------------------------------------------------------------------
| GET PREVIOUSLY CONFIRMED REQUIREMENTS
|--------------------------------------------------------------------------
*/

$previously_confirmed = [];

if (
    isset($_SESSION['student_requirements']) &&
    is_array($_SESSION['student_requirements']) &&
    isset($_SESSION['student_requirements']['confirmed']) &&
    is_array($_SESSION['student_requirements']['confirmed'])
) {

    $previously_confirmed = array_map(
        'intval',
        $_SESSION['student_requirements']['confirmed']
    );

}


/*
|--------------------------------------------------------------------------
| STUDENT NAME
|--------------------------------------------------------------------------
*/

$student_full_name = trim(
    ($student_info['first_name'] ?? '') . ' ' .
    ($student_info['middle_name'] ?? '') . ' ' .
    ($student_info['last_name'] ?? '')
);

if ($student_full_name === '') {
    $student_full_name = 'Student';
}


/*
|--------------------------------------------------------------------------
| CLASSIFY REQUIREMENTS
|--------------------------------------------------------------------------
|
| We keep the database structure unchanged.
| The UI separates documents from process/action items
| based on the requirement name.
|--------------------------------------------------------------------------
*/

$documents = [];
$steps = [];

foreach ($requirements as $requirement) {

    $name = strtolower(
        trim($requirement['requirement_name'] ?? '')
    );


    /*
    |--------------------------------------------------------------------------
    | Process / action keywords
    |--------------------------------------------------------------------------
    */

    $is_step = false;

    $step_keywords = [
        'test',
        'interview',
        'examination',
        'exam',
        'payment',
        'assessment',
        'encoding',
        'advising',
        'interview / advising',
        'approval',
        'evaluation',
        'refresher',
        'screening'
    ];


    foreach ($step_keywords as $keyword) {

        if (strpos($name, $keyword) !== false) {

            $is_step = true;
            break;

        }

    }


    if ($is_step) {

        $steps[] = $requirement;

    } else {

        $documents[] = $requirement;

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

    <title>
        Requirements | ISU SmartEnroll
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7f5;
            color: #1f2937;
        }


        a {
            text-decoration: none;
            color: inherit;
        }


        /* =========================================================
           HEADER
        ========================================================= */

        .top-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 100;
        }


        .top-header-inner {
            max-width: 1250px;
            margin: auto;
            min-height: 92px;
            padding: 12px 24px;

            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 20px;
        }


        .university-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }


        .university-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }


        .university-name h1 {
            font-size: 20px;
            color: #174d2b;
            line-height: 1.1;
        }


        .university-name p {
            margin-top: 4px;
            font-size: 13px;
            color: #6b7280;
        }


        .smart-brand {
            display: flex;
            justify-content: center;
        }


        .smart-brand img {
            width: 190px;
            max-height: 70px;
            object-fit: contain;
        }


        .header-spacer {
            min-width: 1px;
        }


        /* =========================================================
           NAVIGATION
        ========================================================= */

        .navigation {
            background: #174d2b;
        }


        .navigation-inner {
            max-width: 1250px;
            margin: auto;
            padding: 0 24px;

            min-height: 50px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }


        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }


        .nav-links a,
        .account-links a {
            color: #ffffff;
            font-size: 14px;
            padding: 16px 13px;
            transition: 0.2s ease;
        }


        .nav-links a:hover,
        .account-links a:hover {
            background: rgba(255,255,255,0.12);
        }


        /* =========================================================
           PAGE
        ========================================================= */

        .page-wrapper {
            max-width: 1120px;
            margin: auto;
            padding: 38px 20px 60px;
        }


        /* =========================================================
           PROGRESS
        ========================================================= */

        .progress-wrapper {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }


        .progress {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }


        .progress::before {
            content: "";
            position: absolute;
            top: 17px;
            left: 8%;
            right: 8%;
            height: 3px;
            background: #dfe7e2;
            z-index: 0;
        }


        .progress-line {
            position: absolute;
            top: 17px;
            left: 8%;
            width: 63%;
            height: 3px;
            background: #248044;
            z-index: 1;
        }


        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            min-width: 90px;
        }


        .step-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin: auto;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 14px;
            font-weight: bold;

            background: #e5e7eb;
            color: #6b7280;
            border: 4px solid #ffffff;
        }


        .step.completed .step-circle,
        .step.active .step-circle {
            background: #248044;
            color: #ffffff;
        }


        .step-label {
            margin-top: 7px;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
        }


        .step.active .step-label {
            color: #174d2b;
        }


        /* =========================================================
           PAGE TITLE
        ========================================================= */

        .page-title {
            margin-bottom: 24px;
        }


        .page-title .eyebrow {
            display: inline-block;
            color: #248044;
            background: #e9f5ed;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }


        .page-title h2 {
            font-size: 32px;
            color: #174d2b;
            margin-bottom: 8px;
        }


        .page-title p {
            color: #6b7280;
            line-height: 1.6;
            max-width: 760px;
        }


        /* =========================================================
           APPLICATION SUMMARY
        ========================================================= */

        .application-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }


        .summary-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 17px;
        }


        .summary-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #9ca3af;
            margin-bottom: 6px;
            font-weight: bold;
        }


        .summary-value {
            color: #174d2b;
            font-weight: bold;
            font-size: 15px;
            line-height: 1.45;
        }


        /* =========================================================
           MAIN CARD
        ========================================================= */

        .requirements-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 7px 25px rgba(0,0,0,0.05);
            overflow: hidden;
        }


        .requirements-header {
            padding: 24px 28px;
            border-bottom: 1px solid #edf0ee;
            background: #fbfdfb;
        }


        .requirements-header h3 {
            color: #174d2b;
            font-size: 20px;
            margin-bottom: 6px;
        }


        .requirements-header p {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
        }


        .requirements-body {
            padding: 28px;
        }


        /* =========================================================
           INFO BOX
        ========================================================= */

        .info-box {
            display: flex;
            gap: 12px;
            background: #f2f8f4;
            border: 1px solid #d9eadf;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 26px;
        }


        .info-icon {
            width: 29px;
            height: 29px;
            flex-shrink: 0;

            border-radius: 50%;

            background: #248044;
            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: bold;
            font-size: 13px;
        }


        .info-text {
            color: #52605a;
            font-size: 12px;
            line-height: 1.65;
        }


        .info-text strong {
            color: #174d2b;
        }


        /* =========================================================
           ERROR
        ========================================================= */

        .error-box {
            background: #fff1f1;
            border: 1px solid #f2b8b8;
            color: #a52828;
            border-radius: 10px;
            padding: 13px 15px;
            margin-bottom: 22px;
            font-size: 14px;
            line-height: 1.5;
        }


        /* =========================================================
           REQUIREMENT SECTION
        ========================================================= */

        .requirement-section {
            margin-bottom: 30px;
        }


        .section-heading {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 15px;
        }


        .section-icon {
            width: 40px;
            height: 40px;

            border-radius: 10px;

            background: #e9f5ed;
            color: #248044;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: bold;
            font-size: 16px;
        }


        .section-heading h4 {
            color: #174d2b;
            font-size: 18px;
            margin-bottom: 2px;
        }


        .section-heading p {
            color: #7b8490;
            font-size: 11px;
        }


        /* =========================================================
           REQUIREMENT LIST
        ========================================================= */

        .requirement-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }


        .requirement-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;

            padding: 17px;

            border: 1px solid #e1e7e3;
            border-radius: 13px;

            background: #ffffff;

            transition: .2s ease;
        }


        .requirement-item:hover {
            border-color: #b9d7c3;
            background: #fbfdfb;
        }


        .requirement-item:has(
            .requirement-checkbox:checked
        ) {
            border-color: #9bc9a9;
            background: #f8fcf9;
        }


        .requirement-checkbox {
            width: 20px;
            height: 20px;

            margin-top: 2px;

            flex-shrink: 0;

            accent-color: #248044;

            cursor: pointer;
        }


        .requirement-content {
            flex: 1;
            min-width: 0;
        }


        .requirement-name {
            display: block;

            color: #174d2b;

            font-size: 15px;
            font-weight: bold;

            margin-bottom: 5px;

            cursor: pointer;
        }


        .requirement-description {
            color: #6b7280;

            font-size: 12px;

            line-height: 1.6;
        }


        .requirement-status {
            flex-shrink: 0;

            background: #e9f5ed;
            color: #248044;

            border-radius: 20px;

            padding: 5px 9px;

            font-size: 10px;
            font-weight: bold;

            text-transform: uppercase;
        }


        /* =========================================================
           CHECK SUMMARY
        ========================================================= */

        .check-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-top: 20px;

            padding: 13px 15px;

            background: #f7f9f8;
            border: 1px solid #e5e9e6;

            border-radius: 10px;

            font-size: 12px;
            color: #68736d;
        }


        .check-summary strong {
            color: #174d2b;
        }


        /* =========================================================
           NOTICE
        ========================================================= */

        .notice {
            display: flex;
            gap: 12px;

            background: #fffaf0;
            border: 1px solid #eadfbd;

            border-radius: 12px;

            padding: 15px;

            margin-top: 20px;
        }


        .notice-icon {
            width: 28px;
            height: 28px;

            flex-shrink: 0;

            border-radius: 50%;

            background: #b7791f;
            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: bold;
            font-size: 13px;
        }


        .notice-text {
            font-size: 12px;
            color: #665b43;
            line-height: 1.6;
        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-state {
            text-align: center;

            padding: 40px 20px;

            border: 1px dashed #cfd8d2;
            border-radius: 13px;

            background: #fafcfb;
        }


        .empty-icon {
            width: 48px;
            height: 48px;

            margin: 0 auto 14px;

            border-radius: 50%;

            background: #e9f5ed;
            color: #248044;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
            font-weight: bold;
        }


        .empty-state h4 {
            color: #174d2b;
            margin-bottom: 7px;
        }


        .empty-state p {
            color: #7b8490;
            font-size: 13px;
            line-height: 1.6;

            max-width: 560px;

            margin: auto;
        }


        /* =========================================================
           ACTIONS
        ========================================================= */

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;

            padding-top: 24px;
            margin-top: 24px;

            border-top: 1px solid #e5e7eb;
        }


        .btn {
            border: none;
            border-radius: 9px;

            padding: 12px 20px;

            font-size: 14px;
            font-weight: bold;

            cursor: pointer;

            transition: .2s ease;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 7px;
        }


        .btn-back {
            background: #f3f4f6;
            color: #374151;
        }


        .btn-back:hover {
            background: #e5e7eb;
        }


        .btn-next {
            background: #248044;
            color: #ffffff;

            box-shadow: 0 5px 14px rgba(36,128,68,.20);
        }


        .btn-next:hover {
            background: #1d6b38;
            transform: translateY(-1px);
        }


        /* =========================================================
           FOOTER
        ========================================================= */

        footer {
            background: #123c23;
            color: #ffffff;
            padding: 25px 20px;
        }


        .footer-inner {
            max-width: 1120px;
            margin: auto;
            text-align: center;
        }


        .footer-inner strong {
            font-size: 14px;
        }


        .footer-inner p {
            font-size: 12px;
            color: #c8d8ce;
            margin-top: 5px;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 850px) {

            .top-header-inner {
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
            }


            .university-brand {
                justify-content: center;
            }


            .header-spacer {
                display: none;
            }


            .navigation-inner {
                flex-direction: column;
                padding: 0;
            }


            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }


            .nav-links a,
            .account-links a {
                padding: 12px 8px;
            }


            .application-summary {
                grid-template-columns: 1fr;
            }


            .progress-wrapper {
                overflow-x: auto;
            }


            .progress {
                min-width: 600px;
            }


            .requirement-status {
                display: none;
            }

        }


        @media (max-width: 600px) {

            .page-wrapper {
                padding: 25px 12px 45px;
            }


            .page-title h2 {
                font-size: 26px;
            }


            .requirements-body {
                padding: 20px 17px;
            }


            .requirements-header {
                padding: 20px 17px;
            }


            .form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }


            .btn {
                width: 100%;
            }


            .university-name h1 {
                font-size: 17px;
            }


            .smart-brand img {
                width: 165px;
            }


            .requirement-item {
                padding: 14px;
            }


            .check-summary {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
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


        <div class="university-brand">

            <img
                src="assets/isu-logo.png"
                alt="Isabela State University Logo"
                class="university-logo"
            >

            <div class="university-name">

                <h1>
                    Isabela State University
                </h1>

                <p>
                    Cauayan Campus
                </p>

            </div>

        </div>


        <div class="smart-brand">

            <img
                src="assets/smart-enroll-logo.png?v=8"
                alt="ISU SmartEnroll"
            >

        </div>


        <div class="header-spacer"></div>

    </div>

</header>



<!-- =========================================================
     NAVIGATION
========================================================= -->

<nav class="navigation">

    <div class="navigation-inner">


        <div class="nav-links">

            <a href="index.php">
                Home
            </a>

            <a href="enrollment.php">
                Enrollment Guide
            </a>

            <a href="requirements.php">
                Requirements
            </a>

            <a href="procedures.php">
                Procedures
            </a>

            <a href="announcements.php">
                Announcements
            </a>

        </div>


        <div class="account-links">

            <a href="login.php">
                Login
            </a>

            <a href="register.php">
                Register
            </a>

        </div>


    </div>

</nav>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="page-wrapper">


    <!-- =====================================================
         PROGRESS
    ===================================================== -->

    <div class="progress-wrapper">

        <div class="progress">

            <div class="progress-line"></div>


            <div class="step completed">

                <div class="step-circle">
                    ✓
                </div>

                <div class="step-label">
                    Selection
                </div>

            </div>


            <div class="step completed">

                <div class="step-circle">
                    ✓
                </div>

                <div class="step-label">
                    Guidance
                </div>

            </div>


            <div class="step completed">

                <div class="step-circle">
                    ✓
                </div>

                <div class="step-label">
                    Information
                </div>

            </div>


            <div class="step active">

                <div class="step-circle">
                    4
                </div>

                <div class="step-label">
                    Requirements
                </div>

            </div>


            <div class="step">

                <div class="step-circle">
                    5
                </div>

                <div class="step-label">
                    Review
                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         PAGE TITLE
    ===================================================== -->

    <div class="page-title">

        <span class="eyebrow">
            STEP 4 OF 5
        </span>

        <h2>
            Enrollment Requirements
        </h2>

        <p>
            Here are the documents and enrollment steps applicable
            to your selected student type. Review each item carefully
            before continuing.
        </p>

    </div>



    <!-- =====================================================
         APPLICATION SUMMARY
    ===================================================== -->

    <div class="application-summary">


        <div class="summary-card">

            <div class="summary-label">
                Student
            </div>

            <div class="summary-value">
                <?= htmlspecialchars($student_full_name) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Student Type
            </div>

            <div class="summary-value">
                <?= htmlspecialchars($student_type) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Selected Program
            </div>

            <div class="summary-value">

                <?= htmlspecialchars($program_code) ?>

                —

                <?= htmlspecialchars($program_name) ?>

            </div>

        </div>

    </div>



    <!-- =====================================================
         REQUIREMENTS CARD
    ===================================================== -->

    <div class="requirements-card">


        <div class="requirements-header">

            <h3>
                What You Need for Enrollment
            </h3>

            <p>
                Review the applicable documents and procedures below.
                Checking an item only confirms that you have reviewed it;
                it does not mean the document has already been submitted.
            </p>

        </div>


        <div class="requirements-body">


            <!-- =================================================
                 INFO BOX
            ================================================= -->

            <div class="info-box">

                <div class="info-icon">
                    i
                </div>

                <div class="info-text">

                    <strong>
                        Important:
                    </strong>

                    Requirements may include both documents you need
                    to prepare and steps you need to complete.
                    Follow the applicable instructions of the University
                    when you proceed with actual admission or enrollment.

                </div>

            </div>



            <!-- =================================================
                 ERROR
            ================================================= -->

            <?php if ($requirements_error !== ''): ?>

                <div class="error-box">

                    <?= htmlspecialchars($requirements_error) ?>

                </div>

            <?php endif; ?>



            <?php if (count($requirements) > 0): ?>


                <form
                    method="POST"
                    action="student_requirements.php"
                    id="requirementsForm"
                >


                    <!-- =========================================
                         DOCUMENTS
                    ========================================= -->

                    <?php if (count($documents) > 0): ?>

                        <div class="requirement-section">

                            <div class="section-heading">

                                <div class="section-icon">
                                    📄
                                </div>

                                <div>

                                    <h4>
                                        Documents & Requirements to Prepare
                                    </h4>

                                    <p>
                                        Documents or materials you may need to prepare and bring.
                                    </p>

                                </div>

                            </div>


                            <div class="requirement-list">


                                <?php foreach ($documents as $requirement): ?>

                                    <?php

                                    $requirement_id =
                                        (int)$requirement['id'];

                                    $is_checked =
                                        in_array(
                                            $requirement_id,
                                            $previously_confirmed,
                                            true
                                        );

                                    ?>


                                    <div class="requirement-item">


                                        <input
                                            type="checkbox"
                                            class="requirement-checkbox"
                                            id="requirement_<?= $requirement_id ?>"
                                            name="requirements[]"
                                            value="<?= $requirement_id ?>"
                                            <?= $is_checked ? 'checked' : '' ?>
                                        >


                                        <div class="requirement-content">


                                            <label
                                                for="requirement_<?= $requirement_id ?>"
                                                class="requirement-name"
                                            >

                                                <?= htmlspecialchars(
                                                    $requirement['requirement_name']
                                                ) ?>

                                            </label>


                                            <?php if (
                                                trim(
                                                    $requirement['description'] ?? ''
                                                ) !== ''
                                            ): ?>

                                                <div class="requirement-description">

                                                    <?= nl2br(
                                                        htmlspecialchars(
                                                            $requirement['description']
                                                        )
                                                    ) ?>

                                                </div>

                                            <?php endif; ?>


                                        </div>


                                        <div class="requirement-status">
                                            Required
                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </div>

                        </div>

                    <?php endif; ?>



                    <!-- =========================================
                         STEPS
                    ========================================= -->

                    <?php if (count($steps) > 0): ?>

                        <div class="requirement-section">

                            <div class="section-heading">

                                <div class="section-icon">
                                    ✓
                                </div>

                                <div>

                                    <h4>
                                        Steps You Need to Complete
                                    </h4>

                                    <p>
                                        Admission, examination, interview, assessment, or other applicable steps.
                                    </p>

                                </div>

                            </div>


                            <div class="requirement-list">


                                <?php foreach ($steps as $requirement): ?>

                                    <?php

                                    $requirement_id =
                                        (int)$requirement['id'];

                                    $is_checked =
                                        in_array(
                                            $requirement_id,
                                            $previously_confirmed,
                                            true
                                        );

                                    ?>


                                    <div class="requirement-item">


                                        <input
                                            type="checkbox"
                                            class="requirement-checkbox"
                                            id="requirement_<?= $requirement_id ?>"
                                            name="requirements[]"
                                            value="<?= $requirement_id ?>"
                                            <?= $is_checked ? 'checked' : '' ?>
                                        >


                                        <div class="requirement-content">


                                            <label
                                                for="requirement_<?= $requirement_id ?>"
                                                class="requirement-name"
                                            >

                                                <?= htmlspecialchars(
                                                    $requirement['requirement_name']
                                                ) ?>

                                            </label>


                                            <?php if (
                                                trim(
                                                    $requirement['description'] ?? ''
                                                ) !== ''
                                            ): ?>

                                                <div class="requirement-description">

                                                    <?= nl2br(
                                                        htmlspecialchars(
                                                            $requirement['description']
                                                        )
                                                    ) ?>

                                                </div>

                                            <?php endif; ?>


                                        </div>


                                        <div class="requirement-status">
                                            Step
                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </div>

                        </div>

                    <?php endif; ?>



                    <!-- =================================================
                         CHECK SUMMARY
                    ================================================= -->

                    <div class="check-summary">

                        <span>

                            Items reviewed:

                            <strong id="checkedCount">
                                0
                            </strong>

                            /

                            <strong>
                                <?= count($requirements) ?>
                            </strong>

                        </span>


                        <span>

                            <?= count($requirements) ?>

                            applicable item(s)

                        </span>

                    </div>



                    <!-- =================================================
                         NOTICE
                    ================================================= -->

                    <div class="notice">

                        <div class="notice-icon">
                            !
                        </div>

                        <div class="notice-text">

                            Please review all applicable items carefully.
                            A checked item means you have acknowledged
                            that requirement or step. SmartEnroll does not
                            submit or verify your documents through this page.

                        </div>

                    </div>



                    <!-- =================================================
                         ACTIONS
                    ================================================= -->

                    <div class="form-actions">


                        <a
                            href="student_information.php"
                            class="btn btn-back"
                        >
                            ← Back to Information
                        </a>


                        <button
                            type="submit"
                            class="btn btn-next"
                        >
                            Continue to Review →
                        </button>


                    </div>


                </form>


            <?php else: ?>


                <!-- =================================================
                     EMPTY STATE
                ================================================= -->

                <div class="empty-state">

                    <div class="empty-icon">
                        !
                    </div>

                    <h4>
                        No Active Requirements Found
                    </h4>

                    <p>

                        There are currently no active requirements
                        configured for this student type.

                        Please contact the enrollment administrator
                        if you believe requirements should be available.

                    </p>

                </div>


                <div class="form-actions">

                    <a
                        href="student_information.php"
                        class="btn btn-back"
                    >
                        ← Back to Information
                    </a>


                    <a
                        href="review.php"
                        class="btn btn-next"
                    >
                        Continue to Review →
                    </a>

                </div>


            <?php endif; ?>


        </div>

    </div>


</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div class="footer-inner">

        <strong>
            ISU SmartEnroll
        </strong>

        <p>
            Isabela State University – Cauayan Campus
        </p>

        <p>
            © <?= date('Y') ?> ISU SmartEnroll. All rights reserved.
        </p>

    </div>

</footer>



<script>

/*
|--------------------------------------------------------------------------
| Requirement Counter
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    const checkboxes = document.querySelectorAll(
        ".requirement-checkbox"
    );

    const counter = document.getElementById(
        "checkedCount"
    );


    function updateCounter() {

        if (!counter) {
            return;
        }


        let checked = 0;


        checkboxes.forEach(function (checkbox) {

            if (checkbox.checked) {
                checked++;
            }

        });


        counter.textContent = checked;

    }


    checkboxes.forEach(function (checkbox) {

        checkbox.addEventListener(
            "change",
            updateCounter
        );

    });


    updateCounter();

});

</script>


</body>

</html>

<?php

$conn->close();

?>