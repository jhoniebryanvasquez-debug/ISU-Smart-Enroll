<?php

session_start();

require_once "db.php";

/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
| Get the submitted enrollment data first.
| The session is only created after the data is validated.
|--------------------------------------------------------------------------
*/

$student_type = trim(
    $_POST['student_type']
    ?? $_SESSION['enrollment_application']['student_type']
    ?? ''
);

$program_id = intval(
    $_POST['program_id']
    ?? $_SESSION['enrollment_application']['program_id']
    ?? 0
);

$term = trim(
    $_POST['term']
    ?? $_SESSION['enrollment_application']['term']
    ?? ''
);

/*
|--------------------------------------------------------------------------
| VALIDATE BASIC DATA
|--------------------------------------------------------------------------
*/

if (
    $student_type === '' ||
    $program_id <= 0 ||
    $term === ''
) {
    header("Location: enrollment.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET PROGRAM
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        code,
        name
    FROM programs
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Unable to prepare program query.");
}

$stmt->bind_param(
    "i",
    $program_id
);

$stmt->execute();

$program_result = $stmt->get_result();

if ($program_result->num_rows === 0) {
    $stmt->close();

    header("Location: enrollment.php");
    exit;
}

$program = $program_result->fetch_assoc();

$program_code = $program['code'];
$program_name = $program['name'];

$stmt->close();

/*
|--------------------------------------------------------------------------
| SAVE ENROLLMENT APPLICATION
|--------------------------------------------------------------------------
*/

$_SESSION['enrollment_application'] = [
    'student_type' => $student_type,
    'program_id'   => $program_id,
    'program_code' => $program_code,
    'program_name' => $program_name,
    'term'         => $term
];

/*
|--------------------------------------------------------------------------
| GUIDANCE MESSAGE
|--------------------------------------------------------------------------
*/

switch ($student_type) {

    case 'Freshman':

        $recommendation =
            "As an incoming freshman, prepare your admission documents and complete the required screening and medical steps before proceeding with enrollment.";

        $next_step =
            "Complete your student information first, then review the requirements applicable to incoming freshmen.";

        break;


    case 'Transferee':

        $recommendation =
            "As a transferee, prepare your transfer credentials and complete the applicable evaluation, screening, and admission requirements.";

        $next_step =
            "Provide your student information and review the documents required for transferees.";

        break;


    case 'Continuing':

        $recommendation =
            "As a continuing student, review your enrollment information and make sure your required records are ready before continuing your enrollment.";

        $next_step =
            "Provide your student information and continue to the enrollment requirements.";

        break;


    case 'Returning':

        $recommendation =
            "As a returning student, prepare the documents required for your return to the university and complete the applicable evaluation process.";

        $next_step =
            "Provide your student information and review the applicable returning-student requirements.";

        break;


    case 'CPE':

        $recommendation =
            "As a CPE student, prepare the documents applicable to your continuing professional education enrollment.";

        $next_step =
            "Provide your student information and continue to the applicable requirements.";

        break;


    case 'Foreign Student':

        $recommendation =
            "As a foreign student, prepare the applicable international student documents and complete the required admission procedures.";

        $next_step =
            "Provide your student information and review the requirements applicable to foreign students.";

        break;


    default:

        $recommendation =
            "Review the information below and continue with your application.";

        $next_step =
            "Provide your student information to continue.";

        break;
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
        Enrollment Guidance - ISU SmartEnroll
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

            border-bottom:
                1px solid #e7ece9;
        }


        .top-header-inner {
            width:
                min(1400px, 94%);

            min-height:
                104px;

            margin:
                0 auto;

            display:
                grid;

            grid-template-columns:
                1fr auto 1fr;

            align-items:
                center;

            gap:
                25px;
        }


        /* UNIVERSITY */

        .university-brand {
            display:
                flex;

            align-items:
                center;

            gap:
                14px;
        }


        .university-brand img {
            width:
                68px;

            height:
                68px;

            object-fit:
                contain;
        }


        .university-brand h1 {
            font-size:
                21px;

            color:
                #006b3c;

            font-weight:
                800;

            line-height:
                1.2;
        }


        .university-brand p {
            margin-top:
                4px;

            color:
                #6b756f;

            font-size:
                14px;
        }


        /* SMART ENROLL */

        .smart-brand {
            display:
                flex;

            justify-content:
                center;

            align-items:
                center;
        }


        .smart-brand img {
            width:
                190px;

            max-height:
                78px;

            object-fit:
                contain;
        }


        /* CCSI(C) */

        .ccsict-brand {
            display:
                flex;

            justify-content:
                flex-end;

            align-items:
                center;

            gap:
                12px;

            text-align:
                right;
        }


        .ccsict-brand img {
            width:
                62px;

            height:
                62px;

            object-fit:
                contain;
        }


        .ccsict-brand h2 {
            color:
                #263238;

            font-size:
                13px;

            line-height:
                1.35;

            font-weight:
                700;
        }


        .ccsict-brand p {
            color:
                #7b8580;

            font-size:
                11px;

            margin-top:
                3px;
        }


        /* =========================================================
           NAVIGATION
        ========================================================= */

        nav {
            position:
                sticky;

            top:
                0;

            z-index:
                1000;

            background:
                #006b3c;

            box-shadow:
                0 4px 16px rgba(0, 0, 0, 0.10);
        }


        .nav-content {
            width:
                min(1250px, 94%);

            min-height:
                54px;

            margin:
                0 auto;

            display:
                flex;

            justify-content:
                center;

            align-items:
                center;

            flex-wrap:
                wrap;
        }


        nav a {
            position:
                relative;

            color:
                rgba(255, 255, 255, 0.88);

            text-decoration:
                none;

            padding:
                17px 22px;

            font-size:
                14px;

            font-weight:
                600;

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }


        nav a:hover {
            background:
                rgba(255, 255, 255, 0.10);

            color:
                #ffffff;
        }


        nav a.active {
            color:
                #ffffff;

            background:
                rgba(0, 0, 0, 0.10);
        }


        nav a.active::after {
            content:
                "";

            position:
                absolute;

            left:
                18px;

            right:
                18px;

            bottom:
                0;

            height:
                3px;

            background:
                #ffffff;

            border-radius:
                3px 3px 0 0;
        }


        /* =========================================================
           HERO
        ========================================================= */

        .page-hero {
            position:
                relative;

            min-height:
                320px;

            background-image:
                linear-gradient(
                    135deg,
                    rgba(0, 55, 31, 0.90),
                    rgba(0, 107, 60, 0.74),
                    rgba(0, 39, 24, 0.88)
                ),
                url("assets/background.jpg");

            background-size:
                cover;

            background-position:
                center;

            display:
                flex;

            align-items:
                center;

            overflow:
                hidden;
        }


        .page-hero::before {
            content:
                "";

            position:
                absolute;

            width:
                430px;

            height:
                430px;

            right:
                -170px;

            top:
                -220px;

            border-radius:
                50%;

            background:
                rgba(255, 255, 255, 0.07);
        }


        .page-hero::after {
            content:
                "";

            position:
                absolute;

            width:
                310px;

            height:
                310px;

            left:
                -130px;

            bottom:
                -190px;

            border-radius:
                50%;

            background:
                rgba(255, 255, 255, 0.05);
        }


        .hero-content {
            position:
                relative;

            z-index:
                2;

            width:
                min(1100px, 92%);

            margin:
                0 auto;

            padding:
                62px 0;
        }


        .hero-label {
            display:
                inline-flex;

            align-items:
                center;

            gap:
                8px;

            padding:
                7px 13px;

            border:
                1px solid rgba(255, 255, 255, 0.28);

            border-radius:
                999px;

            background:
                rgba(255, 255, 255, 0.10);

            color:
                #ffffff;

            font-size:
                12px;

            font-weight:
                700;

            text-transform:
                uppercase;

            letter-spacing:
                0.8px;

            backdrop-filter:
                blur(8px);
        }


        .hero-label span {
            width:
                7px;

            height:
                7px;

            border-radius:
                50%;

            background:
                #8be0b3;

            box-shadow:
                0 0 0 4px rgba(139, 224, 179, 0.12);
        }


        .hero-content h1 {
            margin-top:
                18px;

            color:
                #ffffff;

            font-size:
                clamp(34px, 5vw, 55px);

            line-height:
                1.05;

            max-width:
                800px;

            font-weight:
                800;

            letter-spacing:
                -1px;
        }


        .hero-content h1 span {
            color:
                #a8e8c5;
        }


        .hero-content p {
            max-width:
                700px;

            margin-top:
                17px;

            color:
                rgba(255, 255, 255, 0.86);

            font-size:
                16px;

            line-height:
                1.7;
        }


        /* =========================================================
           PROGRESS
        ========================================================= */

        .progress-wrap {
            position:
                relative;

            z-index:
                5;

            width:
                min(1050px, 92%);

            margin:
                -29px auto 0;
        }


        .progress-card {
            background:
                rgba(255, 255, 255, 0.97);

            border:
                1px solid rgba(255, 255, 255, 0.8);

            border-radius:
                16px;

            padding:
                20px 28px;

            box-shadow:
                0 14px 40px rgba(0, 0, 0, 0.12);
        }


        .progress {
            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                8px;
        }


        .progress-step {
            display:
                flex;

            align-items:
                center;

            gap:
                9px;

            color:
                #8a938e;

            font-size:
                12px;

            font-weight:
                700;

            white-space:
                nowrap;
        }


        .progress-number {
            width:
                29px;

            height:
                29px;

            display:
                flex;

            justify-content:
                center;

            align-items:
                center;

            border-radius:
                50%;

            background:
                #e8efeb;

            color:
                #77837c;

            font-size:
                12px;
        }


        .progress-step.completed {
            color:
                #006b3c;
        }


        .progress-step.completed .progress-number {
            background:
                #dcefe4;

            color:
                #006b3c;
        }


        .progress-step.active {
            color:
                #006b3c;
        }


        .progress-step.active .progress-number {
            background:
                #006b3c;

            color:
                #ffffff;

            box-shadow:
                0 4px 10px rgba(0, 107, 60, 0.20);
        }


        .progress-line {
            flex:
                1;

            height:
                2px;

            background:
                #e3e9e5;
        }


        /* =========================================================
           MAIN
        ========================================================= */

        .main {
            width:
                min(1050px, 92%);

            margin:
                42px auto 70px;
        }


        .content-card {
            background:
                #ffffff;

            border:
                1px solid #e5ebe7;

            border-radius:
                20px;

            box-shadow:
                0 10px 35px rgba(23, 50, 36, 0.07);

            overflow:
                hidden;
        }


        .content-head {
            padding:
                34px 38px 27px;

            border-bottom:
                1px solid #edf1ee;
        }


        .section-kicker {
            color:
                #00804a;

            font-size:
                12px;

            font-weight:
                800;

            text-transform:
                uppercase;

            letter-spacing:
                1px;

            margin-bottom:
                8px;
        }


        .content-head h2 {
            color:
                #1f2c25;

            font-size:
                28px;

            line-height:
                1.2;
        }


        .content-head p {
            max-width:
                750px;

            margin-top:
                9px;

            color:
                #707a75;

            font-size:
                14px;

            line-height:
                1.65;
        }


        .content-body {
            padding:
                32px 38px 38px;
        }


        /* =========================================================
           APPLICATION SUMMARY
        ========================================================= */

        .summary-grid {
            display:
                grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap:
                14px;
        }


        .summary-item {
            padding:
                18px;

            border:
                1px solid #e1e9e4;

            border-radius:
                13px;

            background:
                #fbfdfc;
        }


        .summary-label {
            color:
                #7d8882;

            font-size:
                11px;

            font-weight:
                700;

            text-transform:
                uppercase;

            letter-spacing:
                0.6px;

            margin-bottom:
                7px;
        }


        .summary-value {
            color:
                #26342c;

            font-size:
                15px;

            font-weight:
                750;

            line-height:
                1.4;
        }


        .summary-code {
            color:
                #006b3c;

            font-size:
                12px;

            font-weight:
                700;

            margin-top:
                4px;
        }


        /* =========================================================
           GUIDANCE
        ========================================================= */

        .guidance {
            margin-top:
                24px;

            padding:
                22px;

            border:
                1px solid #cfe5d8;

            border-radius:
                15px;

            background:
                linear-gradient(
                    135deg,
                    #f1faf5,
                    #f8fcfa
                );
        }


        .guidance-top {
            display:
                flex;

            align-items:
                flex-start;

            gap:
                13px;
        }


        .guidance-icon {
            width:
                40px;

            height:
                40px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                12px;

            background:
                #006b3c;

            color:
                #ffffff;

            font-size:
                18px;

            font-weight:
                800;
        }


        .guidance h3 {
            color:
                #006b3c;

            font-size:
                17px;

            margin-bottom:
                7px;
        }


        .guidance p {
            color:
                #59665e;

            font-size:
                13px;

            line-height:
                1.65;
        }


        /* =========================================================
           NEXT STEP
        ========================================================= */

        .next-section {
            margin-top:
                28px;
        }


        .next-section-title {
            color:
                #26342c;

            font-size:
                17px;

            font-weight:
                750;

            margin-bottom:
                13px;
        }


        .next-card {
            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            padding:
                20px;

            border:
                1px solid #e2e9e5;

            border-radius:
                14px;

            background:
                #ffffff;
        }


        .next-left {
            display:
                flex;

            align-items:
                center;

            gap:
                14px;
        }


        .next-icon {
            width:
                42px;

            height:
                42px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            flex-shrink:
                0;

            border-radius:
                12px;

            background:
                #e8f5ed;

            color:
                #006b3c;

            font-weight:
                800;
        }


        .next-text h4 {
            color:
                #26342c;

            font-size:
                14px;

            margin-bottom:
                4px;
        }


        .next-text p {
            color:
                #7b8580;

            font-size:
                12px;

            line-height:
                1.5;
        }


        /* =========================================================
           NOTICE
        ========================================================= */

        .notice {
            display:
                flex;

            align-items:
                flex-start;

            gap:
                11px;

            margin-top:
                20px;

            padding:
                15px 17px;

            border:
                1px solid #e4e9e6;

            border-radius:
                11px;

            background:
                #fafcfb;

            color:
                #707a75;

            font-size:
                11px;

            line-height:
                1.6;
        }


        .notice-icon {
            width:
                22px;

            height:
                22px;

            flex-shrink:
                0;

            display:
                flex;

            justify-content:
                center;

            align-items:
                center;

            border-radius:
                50%;

            background:
                #edf2ef;

            color:
                #65736b;

            font-size:
                11px;

            font-weight:
                800;
        }


        /* =========================================================
           BUTTONS
        ========================================================= */

        .actions {
            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            margin-top:
                28px;

            padding-top:
                25px;

            border-top:
                1px solid #edf1ee;
        }


        .back-button {
            display:
                inline-flex;

            align-items:
                center;

            gap:
                7px;

            padding:
                13px 18px;

            border:
                1px solid #dce4df;

            border-radius:
                10px;

            color:
                #52605a;

            text-decoration:
                none;

            font-size:
                13px;

            font-weight:
                650;

            transition:
                background 0.18s ease,
                border-color 0.18s ease;
        }


        .back-button:hover {
            background:
                #f8faf9;

            border-color:
                #b9c9c0;
        }


        .primary-button {
            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                9px;

            min-width:
                215px;

            padding:
                14px 23px;

            border-radius:
                10px;

            background:
                linear-gradient(
                    135deg,
                    #006b3c,
                    #00864c
                );

            color:
                #ffffff;

            text-decoration:
                none;

            font-size:
                13px;

            font-weight:
                750;

            box-shadow:
                0 7px 17px rgba(0, 107, 60, 0.18);

            transition:
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }


        .primary-button:hover {
            transform:
                translateY(-2px);

            box-shadow:
                0 10px 22px rgba(0, 107, 60, 0.24);
        }


        .primary-button span {
            font-size:
                17px;

            line-height:
                1;
        }


        /* =========================================================
           FOOTER
        ========================================================= */

        footer {
            background:
                #004d2b;

            color:
                rgba(255, 255, 255, 0.75);

            padding:
                28px 20px;

            text-align:
                center;

            font-size:
                12px;
        }


        .footer-title {
            color:
                #ffffff;

            font-size:
                14px;

            font-weight:
                700;

            margin-bottom:
                5px;
        }


        .footer-subtitle {
            color:
                rgba(255, 255, 255, 0.60);
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {

            .top-header-inner {
                grid-template-columns:
                    1fr auto;

                padding:
                    13px 0;
            }


            .smart-brand {
                grid-column:
                    1 / -1;

                grid-row:
                    1;
            }


            .university-brand {
                grid-column:
                    1;

                grid-row:
                    2;
            }


            .ccsict-brand {
                grid-column:
                    2;

                grid-row:
                    2;
            }


            .smart-brand img {
                width:
                    165px;
            }
        }


        @media (max-width: 700px) {

            .top-header-inner {
                display:
                    flex;

                flex-direction:
                    column;

                text-align:
                    center;

                gap:
                    15px;

                padding:
                    18px 0;
            }


            .university-brand,
            .ccsict-brand {
                justify-content:
                    center;
            }


            .ccsict-brand {
                text-align:
                    center;
            }


            .university-brand img {
                width:
                    58px;

                height:
                    58px;
            }


            .ccsict-brand img {
                width:
                    52px;

                height:
                    52px;
            }


            .university-brand h1 {
                font-size:
                    18px;
            }


            .university-brand p {
                font-size:
                    12px;
            }


            .nav-content {
                width:
                    100%;
            }


            nav a {
                padding:
                    13px 10px;

                font-size:
                    12px;
            }


            .page-hero {
                min-height:
                    285px;
            }


            .hero-content {
                padding:
                    55px 0;
            }


            .hero-content h1 {
                font-size:
                    38px;
            }


            .hero-content p {
                font-size:
                    14px;
            }


            .progress-card {
                padding:
                    17px;
            }


            .progress-step {
                font-size:
                    10px;
            }


            .progress-number {
                width:
                    26px;

                height:
                    26px;
            }


            .progress-line {
                min-width:
                    10px;
            }


            .main {
                margin-top:
                    30px;
            }


            .content-head,
            .content-body {
                padding-left:
                    22px;

                padding-right:
                    22px;
            }


            .summary-grid {
                grid-template-columns:
                    1fr;
            }


            .next-card {
                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .primary-button {
                width:
                    100%;
            }
        }


        @media (max-width: 480px) {

            .progress {
                justify-content:
                    center;
            }


            .progress-step {
                flex-direction:
                    column;

                gap:
                    5px;

                text-align:
                    center;
            }


            .progress-step span:last-child {
                display:
                    none;
            }


            .progress-line {
                max-width:
                    25px;
            }


            .hero-content h1 {
                font-size:
                    33px;
            }


            .hero-content p {
                font-size:
                    13px;
            }


            .content-head {
                padding:
                    27px 18px 22px;
            }


            .content-body {
                padding:
                    25px 18px 28px;
            }


            .actions {
                flex-direction:
                    column-reverse;

                align-items:
                    stretch;
            }


            .primary-button,
            .back-button {
                width:
                    100%;

                justify-content:
                    center;
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


        <div class="smart-brand">

            <img
                src="assets/smart-enroll-logo.png?v=8"
                alt="ISU SmartEnroll"
            >

        </div>


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

            Enrollment Guidance

        </div>


        <h1>

            You're ready for the

            <span>next step.</span>

        </h1>


        <p>

            Review your selected enrollment information and
            follow the guidance below before continuing to
            your student information.

        </p>

    </div>

</section>


<!-- =========================================================
     PROGRESS
========================================================= -->

<div class="progress-wrap">

    <div class="progress-card">

        <div class="progress">

            <div class="progress-step completed">

                <div class="progress-number">
                    ✓
                </div>

                <span>
                    Selection
                </span>

            </div>


            <div class="progress-line"></div>


            <div class="progress-step active">

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


        <div class="content-head">

            <div class="section-kicker">
                Application Summary
            </div>

            <h2>
                Review your selection
            </h2>

            <p>
                These are the details you selected in the previous
                step. Make sure they are correct before continuing
                with your enrollment application.
            </p>

        </div>


        <div class="content-body">


            <!-- =================================================
                 SUMMARY
            ================================================= -->

            <div class="summary-grid">


                <div class="summary-item">

                    <div class="summary-label">
                        Student Type
                    </div>

                    <div class="summary-value">

                        <?= htmlspecialchars(
                            $student_type
                        ) ?>

                    </div>

                </div>


                <div class="summary-item">

                    <div class="summary-label">
                        Academic Term
                    </div>

                    <div class="summary-value">

                        <?= htmlspecialchars(
                            $term
                        ) ?>

                    </div>

                </div>


                <div class="summary-item">

                    <div class="summary-label">
                        Selected Program
                    </div>

                    <div class="summary-value">

                        <?= htmlspecialchars(
                            $program_name
                        ) ?>

                    </div>

                    <div class="summary-code">

                        <?= htmlspecialchars(
                            $program_code
                        ) ?>

                    </div>

                </div>


                <div class="summary-item">

                    <div class="summary-label">
                        Application Status
                    </div>

                    <div class="summary-value">
                        Ready to Continue
                    </div>

                </div>

            </div>


            <!-- =================================================
                 GUIDANCE
            ================================================= -->

            <div class="guidance">

                <div class="guidance-top">

                    <div class="guidance-icon">
                        ✓
                    </div>

                    <div>

                        <h3>
                            Enrollment Guidance
                        </h3>

                        <p>

                            <?= htmlspecialchars(
                                $recommendation
                            ) ?>

                        </p>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 NEXT STEP
            ================================================= -->

            <div class="next-section">

                <div class="next-section-title">
                    What happens next?
                </div>


                <div class="next-card">

                    <div class="next-left">

                        <div class="next-icon">
                            3
                        </div>

                        <div class="next-text">

                            <h4>
                                Student Information
                            </h4>

                            <p>

                                <?= htmlspecialchars(
                                    $next_step
                                ) ?>

                            </p>

                        </div>

                    </div>


                    <a
                        href="student_information.php"
                        class="primary-button"
                    >

                        Continue to Application

                        <span>
                            →
                        </span>

                    </a>

                </div>

            </div>


            <!-- =================================================
                 NOTICE
            ================================================= -->

            <div class="notice">

                <div class="notice-icon">
                    i
                </div>

                <div>

                    Please review your selected program and
                    enrollment category carefully. The information
                    you provide in the next steps will be used to
                    prepare your SmartEnroll application.

                </div>

            </div>


            <!-- =================================================
                 ACTIONS
            ================================================= -->

            <div class="actions">

                <a
                    href="enrollment.php"
                    class="back-button"
                >

                    <span>
                        ←
                    </span>

                    Change Selection

                </a>


                <a
                    href="student_information.php"
                    class="primary-button"
                >

                    Continue to Application

                    <span>
                        →
                    </span>

                </a>

            </div>

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


</body>

</html>