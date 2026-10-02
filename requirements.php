<?php
session_start();
require_once "db.php";

/* =========================
   SECURITY CHECK
========================= */

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit;
}


/* =========================
   GET CURRENT USER
========================= */

$full_name   = $_SESSION["full_name"] ?? "Student";
$applicant_id = $_SESSION["applicant_id"] ?? "";


/* =========================
   GET FILTER
========================= */

$selected_type = trim($_GET["type"] ?? "");


/* =========================
   ALLOWED STUDENT TYPES
========================= */

$student_types = [
    "First Year Student",
    "Regular / Continuing Student",
    "Irregular Student",
    "Transferee Student",
    "Returning Student"
];


/* =========================
   GET REQUIREMENTS
========================= */

$requirements = [];

if ($selected_type !== "" && in_array($selected_type, $student_types, true)) {

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

        $stmt->bind_param("s", $selected_type);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $requirements[] = $row;
        }

        $stmt->close();
    }

} else {

    $result = $conn->query("
        SELECT
            id,
            requirement_name,
            description,
            student_type,
            status
        FROM requirements
        WHERE status = 'Active'
        ORDER BY student_type ASC, id ASC
    ");

    if ($result) {

        while ($row = $result->fetch_assoc()) {
            $requirements[] = $row;
        }
    }
}


/* =========================
   GROUP REQUIREMENTS
========================= */

$grouped_requirements = [];

foreach ($requirements as $requirement) {

    $type = $requirement["student_type"];

    if (!isset($grouped_requirements[$type])) {
        $grouped_requirements[$type] = [];
    }

    $grouped_requirements[$type][] = $requirement;
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
        Requirements - ISU SmartEnroll
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
            color: #25352d;
            min-height: 100vh;
        }


        /* =========================
           TOP HEADER
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

            border-radius: 5px;

            margin: 7px 0;

            padding: 10px 15px !important;

            font-weight: bold;
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


        .hero-content .eyebrow {
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

            max-width: 700px;

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


        .card-header {
            padding: 30px 32px 20px;

            border-bottom: 1px solid #edf1ee;
        }


        .card-header h2 {
            color: #006b3c;

            font-size: 25px;

            margin-bottom: 8px;
        }


        .card-header p {
            color: #718078;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =========================
           FILTER
        ========================= */

        .filter-section {
            padding: 22px 32px;

            background: #f8faf9;

            border-bottom: 1px solid #e9efeb;
        }


        .filter-label {
            display: block;

            font-size: 13px;

            font-weight: bold;

            color: #30443a;

            margin-bottom: 8px;
        }


        .filter-form {
            display: flex;

            gap: 12px;

            align-items: center;

            flex-wrap: wrap;
        }


        .filter-form select {
            width: min(500px, 100%);

            padding: 12px 14px;

            border: 1px solid #cbd7d0;

            border-radius: 9px;

            background: white;

            color: #27372f;

            font-size: 14px;

            cursor: pointer;
        }


        .filter-form select:focus {
            outline: none;

            border-color: #006b3c;

            box-shadow: 0 0 0 3px rgba(0,107,60,0.10);
        }


        .filter-button {
            border: none;

            background: #006b3c;

            color: white;

            padding: 12px 20px;

            border-radius: 9px;

            font-weight: bold;

            cursor: pointer;

            font-size: 14px;
        }


        .filter-button:hover {
            background: #00552f;
        }


        .clear-button {
            text-decoration: none;

            color: #006b3c;

            font-size: 14px;

            font-weight: bold;

            padding: 10px;
        }


        /* =========================
           REQUIREMENTS CONTENT
        ========================= */

        .requirements-content {
            padding: 30px 32px 35px;
        }


        .selected-title {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 20px;
        }


        .selected-title h3 {
            color: #24382e;

            font-size: 20px;
        }


        .count-badge {
            background: #e9f6ef;

            color: #006b3c;

            border: 1px solid #c9e7d5;

            border-radius: 30px;

            padding: 7px 12px;

            font-size: 12px;

            font-weight: bold;

            white-space: nowrap;
        }


        /* =========================
           REQUIREMENT GROUP
        ========================= */

        .requirement-group {
            margin-bottom: 32px;
        }


        .requirement-group:last-child {
            margin-bottom: 0;
        }


        .group-title {
            display: flex;

            align-items: center;

            gap: 10px;

            color: #006b3c;

            font-size: 17px;

            margin-bottom: 13px;

            padding-bottom: 9px;

            border-bottom: 1px solid #e3ebe6;
        }


        .group-title::before {
            content: "";

            width: 5px;

            height: 20px;

            background: #006b3c;

            border-radius: 5px;
        }


        .requirement-item {
            display: flex;

            align-items: flex-start;

            gap: 15px;

            padding: 17px;

            border: 1px solid #e1e9e4;

            border-radius: 11px;

            margin-bottom: 11px;

            background: white;

            transition: 0.2s ease;
        }


        .requirement-item:hover {
            border-color: #b9d6c4;

            box-shadow: 0 5px 15px rgba(0,107,60,0.06);

            transform: translateY(-1px);
        }


        .requirement-icon {
            flex: 0 0 40px;

            width: 40px;

            height: 40px;

            border-radius: 10px;

            background: #eaf7ef;

            color: #006b3c;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            font-weight: bold;
        }


        .requirement-info {
            flex: 1;
        }


        .requirement-info h4 {
            color: #263a30;

            font-size: 15px;

            margin-bottom: 6px;
        }


        .requirement-info p {
            color: #6e7c74;

            font-size: 13px;

            line-height: 1.6;
        }


        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {
            text-align: center;

            padding: 55px 25px;
        }


        .empty-icon {
            width: 65px;

            height: 65px;

            margin: 0 auto 16px;

            border-radius: 50%;

            background: #edf7f1;

            color: #006b3c;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;
        }


        .empty-state h3 {
            color: #304239;

            font-size: 20px;

            margin-bottom: 8px;
        }


        .empty-state p {
            color: #78857e;

            font-size: 14px;

            line-height: 1.6;

            max-width: 550px;

            margin: auto;
        }


        /* =========================
           INFO BOX
        ========================= */

        .info-box {
            margin-top: 28px;

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
           CTA
        ========================= */

        .cta {
            margin-top: 24px;

            padding: 22px;

            border-radius: 12px;

            background: #006b3c;

            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;
        }


        .cta h3 {
            font-size: 17px;

            margin-bottom: 5px;
        }


        .cta p {
            font-size: 13px;

            color: rgba(255,255,255,0.85);

            line-height: 1.5;
        }


        .cta-button {
            background: white;

            color: #006b3c;

            text-decoration: none;

            padding: 11px 17px;

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
                display: flex;

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


            .card-header,
            .filter-section,
            .requirements-content {
                padding-left: 20px;

                padding-right: 20px;
            }


            .selected-title {
                align-items: flex-start;

                flex-direction: column;
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


        <a
            href="requirements.php"
            class="active"
        >
            Requirements
        </a>


        <a href="procedures.php">
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


        <form method="POST" action="index.php">

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
            Enrollment Requirements
        </h1>

        <p>
            Review the documents and requirements you may need
            before proceeding with your enrollment at Isabela State University
            – Cauayan Campus.
        </p>

    </div>

</section>



<!-- =========================
     MAIN
========================= -->

<main class="main-container">


    <div class="main-card">


        <!-- HEADER -->

        <div class="card-header">

            <h2>
                Required Documents
            </h2>

            <p>
                Select your student type to view the corresponding
                enrollment requirements.
            </p>

        </div>



        <!-- FILTER -->

        <div class="filter-section">

            <span class="filter-label">
                Student Type
            </span>


            <form
                method="GET"
                class="filter-form"
            >


                <select
                    name="type"
                    id="studentType"
                >

                    <option value="">
                        View all student types
                    </option>


                    <?php foreach ($student_types as $type): ?>

                        <option
                            value="<?= htmlspecialchars($type) ?>"
                            <?= $selected_type === $type ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars($type) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="filter-button"
                >
                    View Requirements
                </button>


                <?php if ($selected_type !== ""): ?>

                    <a
                        href="requirements.php"
                        class="clear-button"
                    >
                        Clear
                    </a>

                <?php endif; ?>


            </form>

        </div>



        <!-- REQUIREMENTS -->

        <div class="requirements-content">


            <?php if ($selected_type !== ""): ?>


                <div class="selected-title">

                    <h3>
                        <?= htmlspecialchars($selected_type) ?>
                    </h3>


                    <span class="count-badge">

                        <?= count($requirements) ?>

                        requirement<?= count($requirements) !== 1 ? "s" : "" ?>

                    </span>

                </div>


            <?php endif; ?>



            <?php if (count($requirements) > 0): ?>


                <?php foreach ($grouped_requirements as $type => $items): ?>


                    <div class="requirement-group">


                        <?php if ($selected_type === ""): ?>

                            <h3 class="group-title">

                                <?= htmlspecialchars($type) ?>

                            </h3>

                        <?php endif; ?>


                        <?php foreach ($items as $requirement): ?>


                            <div class="requirement-item">


                                <div class="requirement-icon">
                                    ✓
                                </div>


                                <div class="requirement-info">

                                    <h4>
                                        <?= htmlspecialchars(
                                            $requirement["requirement_name"]
                                        ) ?>
                                    </h4>


                                    <p>

                                        <?php

                                        $description =
                                            trim(
                                                $requirement["description"] ?? ""
                                            );

                                        if ($description !== "") {

                                            echo htmlspecialchars(
                                                $description
                                            );

                                        } else {

                                            echo "Please prepare this document as part of your enrollment requirements.";

                                        }

                                        ?>

                                    </p>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-state">


                    <div class="empty-icon">
                        ✓
                    </div>


                    <h3>
                        No Requirements Found
                    </h3>


                    <p>
                        There are currently no active requirements
                        available for the selected student type.
                        Please check again later or contact the
                        appropriate ISU office for assistance.
                    </p>


                </div>


            <?php endif; ?>



            <!-- INFO -->

            <div class="info-box">

                <strong>
                    Important:
                </strong>

                The requirements displayed here are for guidance
                purposes. Please prepare the required documents
                and follow the official enrollment instructions
                provided by Isabela State University.

            </div>



            <!-- CTA -->

            <div class="cta">

                <div>

                    <h3>
                        Ready to continue?
                    </h3>

                    <p>
                        Start the SmartEnroll guide and let the system
                        show you the requirements based on your student type.
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

<?php
$conn->close();
?>