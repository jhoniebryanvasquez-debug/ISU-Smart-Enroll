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

$full_name    = $_SESSION["full_name"] ?? "Student";
$applicant_id = $_SESSION["applicant_id"] ?? "";


/* =========================
   GET ANNOUNCEMENTS
========================= */

$announcements = [];

$sql = "
    SELECT
        id,
        title,
        content,
        status,
        publish_date
    FROM announcements
    WHERE status = 'Published'
    ORDER BY publish_date DESC, id DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }

    $result->free();

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
        Announcements - ISU SmartEnroll
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


        .nav-inner a.active {
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
            min-height: 300px;

            background:
                linear-gradient(
                    rgba(0, 65, 37, 0.78),
                    rgba(0, 65, 37, 0.84)
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
            max-width: 1050px;

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


        .page-heading {
            padding: 30px 32px;

            border-bottom: 1px solid #edf1ee;
        }


        .page-heading h2 {
            color: #006b3c;

            font-size: 25px;

            margin-bottom: 8px;
        }


        .page-heading p {
            color: #718078;

            font-size: 14px;

            line-height: 1.7;
        }


        /* =========================
           ANNOUNCEMENTS
        ========================= */

        .announcement-list {
            padding: 30px 32px;

            display: flex;

            flex-direction: column;

            gap: 18px;
        }


        .announcement {
            border: 1px solid #dfe8e2;

            border-radius: 12px;

            padding: 22px;

            background: #ffffff;

            transition: 0.2s ease;
        }


        .announcement:hover {
            border-color: #b8d4c1;

            box-shadow: 0 6px 18px rgba(0,107,60,0.07);

            transform: translateY(-1px);
        }


        .announcement-top {
            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 10px;
        }


        .announcement-title {
            color: #263a30;

            font-size: 19px;

            line-height: 1.35;
        }


        .announcement-date {
            flex-shrink: 0;

            background: #eaf7ef;

            color: #006b3c;

            border-radius: 20px;

            padding: 7px 11px;

            font-size: 11px;

            font-weight: bold;

            white-space: nowrap;
        }


        .announcement-content {
            color: #68766e;

            font-size: 14px;

            line-height: 1.75;

            white-space: pre-line;
        }


        .announcement-label {
            display: inline-block;

            margin-bottom: 10px;

            color: #006b3c;

            font-size: 11px;

            font-weight: bold;

            text-transform: uppercase;

            letter-spacing: 0.8px;
        }


        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {
            padding: 55px 25px;

            text-align: center;
        }


        .empty-icon {
            width: 68px;

            height: 68px;

            margin: 0 auto 16px;

            border-radius: 50%;

            background: #eaf7ef;

            color: #006b3c;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;
        }


        .empty-state h3 {
            color: #263a30;

            font-size: 20px;

            margin-bottom: 8px;
        }


        .empty-state p {
            color: #7a867f;

            font-size: 14px;

            line-height: 1.6;
        }


        /* =========================
           CTA
        ========================= */

        .cta {
            margin: 0 32px 32px;

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


            .page-heading,
            .announcement-list {
                padding-left: 20px;

                padding-right: 20px;
            }


            .announcement-top {
                flex-direction: column;

                gap: 10px;
            }


            .announcement-date {
                align-self: flex-start;
            }


            .cta {
                margin-left: 20px;

                margin-right: 20px;

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


        <a href="procedures.php">
            Procedures
        </a>


        <a
            href="announcements.php"
            class="active"
        >
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
            Announcements
        </h1>


        <p>
            Stay updated with the latest enrollment announcements,
            reminders, schedules, and important notices from
            Isabela State University.
        </p>

    </div>

</section>



<!-- =========================
     MAIN
========================= -->

<main class="main-container">


    <div class="main-card">


        <div class="page-heading">

            <h2>
                Latest Announcements
            </h2>


            <p>
                View published announcements and important information
                related to the enrollment process.
            </p>

        </div>



        <?php if (count($announcements) > 0): ?>


            <div class="announcement-list">


                <?php foreach ($announcements as $announcement): ?>


                    <article class="announcement">


                        <span class="announcement-label">
                            ISU SmartEnroll Notice
                        </span>


                        <div class="announcement-top">

                            <h3 class="announcement-title">

                                <?= htmlspecialchars(
                                    $announcement["title"]
                                ) ?>

                            </h3>


                            <span class="announcement-date">

                                <?= date(
                                    "F j, Y",
                                    strtotime(
                                        $announcement["publish_date"]
                                    )
                                ) ?>

                            </span>

                        </div>


                        <div class="announcement-content">

                            <?= htmlspecialchars(
                                $announcement["content"]
                            ) ?>

                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="empty-state">

                <div class="empty-icon">
                    📢
                </div>


                <h3>
                    No announcements yet
                </h3>


                <p>
                    There are currently no published announcements.
                    Please check again later for new updates.
                </p>

            </div>


        <?php endif; ?>



        <!-- CTA -->

        <div class="cta">

            <div>

                <h3>
                    Need enrollment guidance?
                </h3>


                <p>
                    Check your student type and review the requirements
                    and procedures that may apply to you.
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