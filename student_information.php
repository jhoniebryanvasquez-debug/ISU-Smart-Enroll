<?php
session_start();
require_once "db.php";

/*
|--------------------------------------------------------------------------
| Check if the student came from the enrollment selection flow
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['enrollment_application'])) {
    header("Location: enrollment.php");
    exit;
}

$application = $_SESSION['enrollment_application'];

$student_type = $application['student_type'] ?? '';
$program_id   = intval($application['program_id'] ?? 0);
$program_code = $application['program_code'] ?? '';
$program_name = $application['program_name'] ?? '';
$term         = $application['term'] ?? '';

if ($student_type === '' || $program_id <= 0 || $program_name === '' || $term === '') {
    header("Location: enrollment.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Preserve previously entered information if the user returns to this page
|--------------------------------------------------------------------------
*/
$student_info = $_SESSION['student_information'] ?? [];

$first_name      = $student_info['first_name'] ?? '';
$middle_name     = $student_info['middle_name'] ?? '';
$last_name       = $student_info['last_name'] ?? '';
$student_number  = $student_info['student_number'] ?? '';
$date_of_birth   = $student_info['date_of_birth'] ?? '';
$gender          = $student_info['gender'] ?? '';
$contact_number  = $student_info['contact_number'] ?? '';
$email           = $student_info['email'] ?? '';
$address         = $student_info['address'] ?? '';
$year_level      = $student_info['year_level'] ?? '';

$error = '';

/*
|--------------------------------------------------------------------------
| Handle form submission
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name     = trim($_POST['first_name'] ?? '');
    $middle_name    = trim($_POST['middle_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $date_of_birth  = trim($_POST['date_of_birth'] ?? '');
    $gender         = trim($_POST['gender'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $year_level     = trim($_POST['year_level'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Basic validation
    |--------------------------------------------------------------------------
    */

    if ($first_name === '') {
        $error = "Please enter your first name.";
    } elseif ($last_name === '') {
        $error = "Please enter your last name.";
    } elseif ($date_of_birth === '') {
        $error = "Please enter your date of birth.";
    } elseif ($gender === '') {
        $error = "Please select your gender.";
    } elseif ($contact_number === '') {
        $error = "Please enter your contact number.";
    } elseif ($email === '') {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($address === '') {
        $error = "Please enter your address.";
    }

    /*
    |--------------------------------------------------------------------------
    | Year level is only required for selected student types
    |--------------------------------------------------------------------------
    */

    if (
        $error === '' &&
        in_array($student_type, ['Freshman', 'Transferee', 'Continuing', 'Returning'], true) &&
        $year_level === ''
    ) {
        $error = "Please select your year level.";
    }

    /*
    |--------------------------------------------------------------------------
    | Save information into session
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $_SESSION['student_information'] = [
            'first_name'     => $first_name,
            'middle_name'    => $middle_name,
            'last_name'      => $last_name,
            'student_number' => $student_number,
            'date_of_birth'  => $date_of_birth,
            'gender'         => $gender,
            'contact_number' => $contact_number,
            'email'          => $email,
            'address'        => $address,
            'year_level'     => $year_level
        ];

        header("Location: student_requirements.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Determine whether year level should be displayed
|--------------------------------------------------------------------------
*/

$show_year_level = in_array(
    $student_type,
    ['Freshman', 'Transferee', 'Continuing', 'Returning'],
    true
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Information | ISU SmartEnroll</title>

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

        /* =========================
           HEADER
        ========================= */

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

        /* =========================
           NAVIGATION
        ========================= */

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

        /* =========================
           PAGE
        ========================= */

        .page-wrapper {
            max-width: 1120px;
            margin: auto;
            padding: 38px 20px 60px;
        }

        /* =========================
           PROGRESS
        ========================= */

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
            width: 42%;
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

        /* =========================
           PAGE TITLE
        ========================= */

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
            max-width: 720px;
        }

        /* =========================
           APPLICATION SUMMARY
        ========================= */

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
        }

        /* =========================
           FORM CARD
        ========================= */

        .form-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 7px 25px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .form-header {
            padding: 24px 28px;
            border-bottom: 1px solid #edf0ee;
            background: #fbfdfb;
        }

        .form-header h3 {
            color: #174d2b;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .form-header p {
            color: #6b7280;
            font-size: 13px;
        }

        .form-body {
            padding: 28px;
        }

        /* =========================
           ERROR
        ========================= */

        .error-box {
            background: #fff1f1;
            border: 1px solid #f2b8b8;
            color: #a52828;
            border-radius: 10px;
            padding: 13px 15px;
            margin-bottom: 22px;
            font-size: 14px;
        }

        /* =========================
           FORM
        ========================= */

        .section-title {
            font-size: 16px;
            color: #174d2b;
            margin-bottom: 17px;
            padding-bottom: 9px;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
        }

        .required {
            color: #c53030;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            padding: 12px 13px;
            font-size: 14px;
            font-family: inherit;
            color: #1f2937;
            background: #ffffff;
            outline: none;
            transition: .2s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #248044;
            box-shadow: 0 0 0 3px rgba(36,128,68,.10);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .field-note {
            margin-top: 5px;
            font-size: 11px;
            color: #8a929c;
        }

        /* =========================
           NOTICE
        ========================= */

        .notice {
            display: flex;
            gap: 12px;
            background: #f2f8f4;
            border: 1px solid #d9eadf;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .notice-icon {
            width: 28px;
            height: 28px;
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

        .notice-text {
            font-size: 12px;
            color: #52605a;
            line-height: 1.6;
        }

        /* =========================
           BUTTONS
        ========================= */

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding-top: 22px;
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

        /* =========================
           FOOTER
        ========================= */

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

        .footer-inner p {
            font-size: 12px;
            color: #c8d8ce;
            margin-top: 5px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .progress-wrapper {
                overflow-x: auto;
            }

            .progress {
                min-width: 600px;
            }
        }

        @media (max-width: 600px) {

            .page-wrapper {
                padding: 25px 12px 45px;
            }

            .page-title h2 {
                font-size: 26px;
            }

            .form-body {
                padding: 20px 17px;
            }

            .form-header {
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
        }
    </style>
</head>

<body>

<header class="top-header">
    <div class="top-header-inner">

        <div class="university-brand">
            <img
                src="assets/isu-logo.png"
                alt="Isabela State University Logo"
                class="university-logo"
            >

            <div class="university-name">
                <h1>Isabela State University</h1>
                <p>Cauayan Campus</p>
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

<nav class="navigation">
    <div class="navigation-inner">

        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="enrollment-guide.php">Enrollment Guide</a>
            <a href="requirements.php">Requirements</a>
            <a href="procedures.php">Procedures</a>
            <a href="announcements.php">Announcements</a>
        </div>

        <div class="account-links">
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>

    </div>
</nav>


<main class="page-wrapper">

    <!-- Progress -->

    <div class="progress-wrapper">

        <div class="progress">

            <div class="progress-line"></div>

            <div class="step completed">
                <div class="step-circle">✓</div>
                <div class="step-label">Selection</div>
            </div>

            <div class="step completed">
                <div class="step-circle">✓</div>
                <div class="step-label">Guidance</div>
            </div>

            <div class="step active">
                <div class="step-circle">3</div>
                <div class="step-label">Information</div>
            </div>

            <div class="step">
                <div class="step-circle">4</div>
                <div class="step-label">Requirements</div>
            </div>

            <div class="step">
                <div class="step-circle">5</div>
                <div class="step-label">Review</div>
            </div>

        </div>

    </div>


    <!-- Title -->

    <div class="page-title">

        <span class="eyebrow">STEP 3 OF 5</span>

        <h2>Student Information</h2>

        <p>
            Please provide your personal information accurately.
            This information will be used for your SmartEnroll application.
        </p>

    </div>


    <!-- Application Summary -->

    <div class="application-summary">

        <div class="summary-card">
            <div class="summary-label">Student Type</div>
            <div class="summary-value">
                <?= htmlspecialchars($student_type) ?>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Selected Program</div>
            <div class="summary-value">
                <?= htmlspecialchars($program_code) ?> —
                <?= htmlspecialchars($program_name) ?>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Term</div>
            <div class="summary-value">
                <?= htmlspecialchars($term) ?>
            </div>
        </div>

    </div>


    <!-- Form -->

    <div class="form-card">

        <div class="form-header">

            <h3>Personal Details</h3>

            <p>
                Fields marked with <span class="required">*</span> are required.
            </p>

        </div>


        <div class="form-body">

            <?php if ($error !== ''): ?>

                <div class="error-box">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="student_information.php">

                <h4 class="section-title">
                    Basic Information
                </h4>


                <div class="form-grid">

                    <div class="form-group">

                        <label for="first_name">
                            First Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?= htmlspecialchars($first_name) ?>"
                            placeholder="Enter your first name"
                            autocomplete="given-name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="middle_name">
                            Middle Name
                        </label>

                        <input
                            type="text"
                            id="middle_name"
                            name="middle_name"
                            value="<?= htmlspecialchars($middle_name) ?>"
                            placeholder="Enter your middle name"
                            autocomplete="additional-name"
                        >

                        <span class="field-note">
                            Leave blank if not applicable.
                        </span>

                    </div>


                    <div class="form-group">

                        <label for="last_name">
                            Last Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?= htmlspecialchars($last_name) ?>"
                            placeholder="Enter your last name"
                            autocomplete="family-name"
                            required
                        >

                    </div>


                    <?php if ($show_year_level): ?>

                        <div class="form-group">

                            <label for="year_level">
                                Year Level <span class="required">*</span>
                            </label>

                            <select
                                id="year_level"
                                name="year_level"
                                required
                            >

                                <option value="">
                                    Select year level
                                </option>

                                <option
                                    value="1st Year"
                                    <?= $year_level === '1st Year' ? 'selected' : '' ?>
                                >
                                    1st Year
                                </option>

                                <option
                                    value="2nd Year"
                                    <?= $year_level === '2nd Year' ? 'selected' : '' ?>
                                >
                                    2nd Year
                                </option>

                                <option
                                    value="3rd Year"
                                    <?= $year_level === '3rd Year' ? 'selected' : '' ?>
                                >
                                    3rd Year
                                </option>

                                <option
                                    value="4th Year"
                                    <?= $year_level === '4th Year' ? 'selected' : '' ?>
                                >
                                    4th Year
                                </option>

                                <option
                                    value="5th Year"
                                    <?= $year_level === '5th Year' ? 'selected' : '' ?>
                                >
                                    5th Year
                                </option>

                            </select>

                        </div>

                    <?php endif; ?>


                    <div class="form-group">

                        <label for="date_of_birth">
                            Date of Birth <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            value="<?= htmlspecialchars($date_of_birth) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="gender">
                            Gender <span class="required">*</span>
                        </label>

                        <select
                            id="gender"
                            name="gender"
                            required
                        >

                            <option value="">
                                Select gender
                            </option>

                            <option
                                value="Male"
                                <?= $gender === 'Male' ? 'selected' : '' ?>
                            >
                                Male
                            </option>

                            <option
                                value="Female"
                                <?= $gender === 'Female' ? 'selected' : '' ?>
                            >
                                Female
                            </option>

                            <option
                                value="Other"
                                <?= $gender === 'Other' ? 'selected' : '' ?>
                            >
                                Other
                            </option>

                        </select>

                    </div>

                </div>


                <h4 class="section-title">
                    Contact Information
                </h4>


                <div class="form-grid">

                    <div class="form-group">

                        <label for="contact_number">
                            Contact Number <span class="required">*</span>
                        </label>

                        <input
                            type="tel"
                            id="contact_number"
                            name="contact_number"
                            value="<?= htmlspecialchars($contact_number) ?>"
                            placeholder="09XXXXXXXXX"
                            autocomplete="tel"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email Address <span class="required">*</span>
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="example@email.com"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label for="address">
                            Complete Address <span class="required">*</span>
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            placeholder="House/Building No., Street, Barangay, Municipality/City, Province"
                            autocomplete="street-address"
                            required
                        ><?= htmlspecialchars($address) ?></textarea>

                    </div>

                </div>


                <?php if ($student_type !== 'Freshman'): ?>

                    <h4 class="section-title">
                        Student Number
                    </h4>

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="student_number">
                                Existing Student Number
                            </label>

                            <input
                                type="text"
                                id="student_number"
                                name="student_number"
                                value="<?= htmlspecialchars($student_number) ?>"
                                placeholder="Enter student number if available"
                            >

                            <span class="field-note">
                                If you do not have one yet, you may leave this blank.
                            </span>

                        </div>

                    </div>

                <?php endif; ?>


                <div class="notice">

                    <div class="notice-icon">
                        i
                    </div>

                    <div class="notice-text">
                        Please make sure that all information you enter is accurate.
                        You will have an opportunity to review your application before
                        final submission.
                    </div>

                </div>


                <div class="form-actions">

                    <a
                        href="result.php"
                        class="btn btn-back"
                        onclick="history.back(); return false;"
                    >
                        ← Back
                    </a>

                    <button
                        type="submit"
                        class="btn btn-next"
                    >
                        Continue to Requirements →
                    </button>

                </div>

            </form>

        </div>

    </div>

</main>


<footer>

    <div class="footer-inner">

        <strong>ISU SmartEnroll</strong>

        <p>
            Isabela State University – Cauayan Campus
        </p>

        <p>
            © <?= date('Y') ?> ISU SmartEnroll. All rights reserved.
        </p>

    </div>

</footer>

</body>
</html>