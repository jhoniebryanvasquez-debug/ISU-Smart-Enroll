<?php

session_start();

require_once "db.php";
require_once "activity_logger.php";


/* =========================================================
   ADMIN SECURITY
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Admin"
) {
    header("Location: admin_login.php");
    exit;
}


/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];


/* =========================================================
   GET STUDENT ID
========================================================= */

$id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($id <= 0) {

    header("Location: student.php");
    exit;
}


$message = "";
$messageType = "";


/* =========================================================
   UPDATE STUDENT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_student"])
) {

    $submitted_token = $_POST["csrf_token"] ?? "";

    if (
        !hash_equals(
            $_SESSION["csrf_token"],
            $submitted_token
        )
    ) {

        $message =
            "Invalid security token. Please refresh the page and try again.";

        $messageType = "error";

    } else {

        $student_number = trim(
            $_POST["student_number"] ?? ""
        );

        $first_name = trim(
            $_POST["first_name"] ?? ""
        );

        $middle_name = trim(
            $_POST["middle_name"] ?? ""
        );

        $last_name = trim(
            $_POST["last_name"] ?? ""
        );

        $course = trim(
            $_POST["course"] ?? ""
        );

        $year_level = trim(
            $_POST["year_level"] ?? ""
        );

        $contact_number = trim(
            $_POST["contact_number"] ?? ""
        );

        $email = trim(
            $_POST["email"] ?? ""
        );

        $address = trim(
            $_POST["address"] ?? ""
        );

        $date_of_birth = trim(
            $_POST["date_of_birth"] ?? ""
        );

        $gender = trim(
            $_POST["gender"] ?? ""
        );


        /* =================================================
           VALIDATION
        ================================================= */

        if (
            $first_name === "" ||
            $last_name === "" ||
            $course === "" ||
            $year_level === "" ||
            $contact_number === "" ||
            $email === "" ||
            $address === "" ||
            $date_of_birth === "" ||
            $gender === ""
        ) {

            $message =
                "Please fill in all required fields.";

            $messageType = "error";

        } elseif (strlen($first_name) > 100) {

            $message =
                "First name is too long.";

            $messageType = "error";

        } elseif (strlen($middle_name) > 100) {

            $message =
                "Middle name is too long.";

            $messageType = "error";

        } elseif (strlen($last_name) > 100) {

            $message =
                "Last name is too long.";

            $messageType = "error";

        } elseif (strlen($course) > 100) {

            $message =
                "Course is too long.";

            $messageType = "error";

        } elseif (strlen($year_level) > 20) {

            $message =
                "Year level is too long.";

            $messageType = "error";

        } elseif (strlen($contact_number) > 20) {

            $message =
                "Contact number is too long.";

            $messageType = "error";

        } elseif (
            strlen($email) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            $message =
                "Please enter a valid email address.";

            $messageType = "error";

        } elseif (
            !in_array(
                $gender,
                ["Male", "Female", "Other"],
                true
            )
        ) {

            $message =
                "Invalid gender selected.";

            $messageType = "error";

        } else {

            /* =============================================
               CHECK STUDENT EXISTS
            ============================================= */

            $stmt = $conn->prepare(
                "SELECT
                    first_name,
                    middle_name,
                    last_name,
                    student_number
                 FROM students
                 WHERE id = ?
                 LIMIT 1"
            );


            if (!$stmt) {

                $message =
                    "Unable to find student.";

                $messageType = "error";

            } else {

                $stmt->bind_param(
                    "i",
                    $id
                );

                $stmt->execute();

                $result = $stmt->get_result();

                $oldStudent = $result->fetch_assoc();

                $stmt->close();


                if (!$oldStudent) {

                    header("Location: student.php");
                    exit;

                }


                /* =========================================
                   CHECK DUPLICATE STUDENT NUMBER
                ========================================= */

                if ($student_number !== "") {

                    $check = $conn->prepare(
                        "SELECT id
                         FROM students
                         WHERE student_number = ?
                         AND id != ?
                         LIMIT 1"
                    );


                    if (!$check) {

                        $message =
                            "Unable to validate student number.";

                        $messageType = "error";

                    } else {

                        $check->bind_param(
                            "si",
                            $student_number,
                            $id
                        );

                        $check->execute();

                        $duplicate =
                            $check->get_result();

                        $studentNumberExists =
                            $duplicate->num_rows > 0;

                        $check->close();


                        if ($studentNumberExists) {

                            $message =
                                "Student number already exists.";

                            $messageType = "error";
                        }
                    }
                }


                /* =========================================
                   UPDATE
                ========================================= */

                if ($message === "") {

                    $stmt = $conn->prepare(
                        "UPDATE students
                         SET
                            student_number = NULLIF(?, ''),
                            first_name = ?,
                            middle_name = NULLIF(?, ''),
                            last_name = ?,
                            course = ?,
                            year_level = ?,
                            contact_number = ?,
                            email = ?,
                            address = ?,
                            date_of_birth = ?,
                            gender = ?
                         WHERE id = ?"
                    );


                    if (!$stmt) {

                        $message =
                            "Unable to prepare student update.";

                        $messageType = "error";

                    } else {

                        $stmt->bind_param(
                            "sssssssssssi",
                            $student_number,
                            $first_name,
                            $middle_name,
                            $last_name,
                            $course,
                            $year_level,
                            $contact_number,
                            $email,
                            $address,
                            $date_of_birth,
                            $gender,
                            $id
                        );


                        if ($stmt->execute()) {

                            $stmt->close();


                            $full_name = trim(
                                $first_name . " " .
                                $middle_name . " " .
                                $last_name
                            );

                            $full_name = preg_replace(
                                '/\s+/',
                                ' ',
                                $full_name
                            );


                            logActivity(
                                $conn,
                                "Updated student: " .
                                $full_name
                            );


                            header(
                                "Location: student.php?updated=1"
                            );

                            exit;

                        } else {

                            $message =
                                "Unable to update student.";

                            $messageType = "error";

                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   GET CURRENT STUDENT
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        id,
        student_number,
        first_name,
        middle_name,
        last_name,
        course,
        year_level,
        contact_number,
        email,
        address,
        date_of_birth,
        gender
     FROM students
     WHERE id = ?
     LIMIT 1"
);


if (!$stmt) {

    header("Location: student.php");
    exit;
}


$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();

$result = $stmt->get_result();

$student = $result->fetch_assoc();

$stmt->close();


if (!$student) {

    header("Location: student.php");
    exit;
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

    <title>Edit Student - ISU SmartEnroll</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: Arial, sans-serif;
            background: #f3f6f4;
            color: #333;
        }


        header {
            background: #006b3c;
            color: white;
            padding: 15px 5%;
        }


        .header-content {
            max-width: 1100px;
            margin: auto;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }


        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }


        .brand img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }


        .brand h2 {
            font-size: 23px;
            margin-bottom: 4px;
        }


        .brand p {
            font-size: 14px;
        }


        .back-link {
            color: white;
            text-decoration: none;

            border: 1px solid white;

            padding: 8px 15px;

            border-radius: 5px;
        }


        .back-link:hover {
            background: white;
            color: #006b3c;
        }


        .container {
            max-width: 950px;
            margin: 35px auto;
            padding: 0 20px;
        }


        .box {
            background: white;

            border: 1px solid #ddd;

            border-radius: 8px;

            padding: 30px;
        }


        h1 {
            color: #006b3c;

            margin-bottom: 8px;

            font-size: 27px;
        }


        .description {
            color: #777;

            margin-bottom: 25px;
        }


        .message {
            padding: 11px;

            border-radius: 5px;

            margin-bottom: 20px;
        }


        .message.error {
            background: #fff1f1;

            border: 1px solid #e0aaaa;

            color: #a10000;
        }


        .form-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 18px;
        }


        .form-group.full {
            grid-column: 1 / -1;
        }


        .form-group label {
            display: block;

            font-weight: bold;

            font-size: 14px;

            margin-bottom: 7px;
        }


        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            padding: 11px;

            border: 1px solid #bbb;

            border-radius: 5px;

            font-size: 14px;

            background: white;

            font-family: Arial, sans-serif;
        }


        .form-group textarea {
            min-height: 90px;
            resize: vertical;
        }


        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            outline: none;

            border-color: #006b3c;
        }


        .buttons {
            display: flex;

            gap: 10px;

            margin-top: 25px;
        }


        .update-button {

            padding: 11px 22px;

            border: none;

            border-radius: 5px;

            background: #006b3c;

            color: white;

            font-weight: bold;

            cursor: pointer;
        }


        .update-button:hover {
            background: #00552f;
        }


        .cancel-button {

            padding: 11px 22px;

            border: 1px solid #999;

            border-radius: 5px;

            background: white;

            color: #555;

            text-decoration: none;

            font-weight: bold;
        }


        .cancel-button:hover {
            background: #f2f2f2;
        }


        .required-note {
            color: #777;

            font-size: 12px;

            margin-top: 18px;
        }


        @media (max-width: 700px) {

            .header-content {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .form-group.full {
                grid-column: auto;
            }


            .box {

                padding: 20px;
            }

        }

    </style>

</head>


<body>


<header>

    <div class="header-content">


        <div class="brand">


            <img
                src="assets/isu-logo.png"
                alt="Isabela State University Logo"
            >


            <div>

                <h2>ISU SmartEnroll</h2>

                <p>Edit Student</p>

            </div>


        </div>


        <a
            href="student.php"
            class="back-link"
        >
            ← Student Records
        </a>


    </div>

</header>


<div class="container">


    <div class="box">


        <h1>
            Edit Student
        </h1>


        <p class="description">
            Update the student's information below.
        </p>


        <?php if ($message !== ""): ?>

            <div class="message <?= htmlspecialchars($messageType) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrf_token) ?>"
            >


            <div class="form-grid">


                <div class="form-group">

                    <label for="student_number">
                        Student Number
                    </label>

                    <input
                        type="text"
                        id="student_number"
                        name="student_number"
                        value="<?= htmlspecialchars($student["student_number"] ?? "") ?>"
                        maxlength="50"
                        placeholder="Optional"
                    >

                </div>


                <div class="form-group">

                    <label for="first_name">
                        First Name *
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?= htmlspecialchars($student["first_name"]) ?>"
                        maxlength="100"
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
                        value="<?= htmlspecialchars($student["middle_name"] ?? "") ?>"
                        maxlength="100"
                        placeholder="Optional"
                    >

                </div>


                <div class="form-group">

                    <label for="last_name">
                        Last Name *
                    </label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?= htmlspecialchars($student["last_name"]) ?>"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="course">
                        Course *
                    </label>

                    <input
                        type="text"
                        id="course"
                        name="course"
                        value="<?= htmlspecialchars($student["course"] ?? "") ?>"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="year_level">
                        Year Level *
                    </label>

                    <input
                        type="text"
                        id="year_level"
                        name="year_level"
                        value="<?= htmlspecialchars($student["year_level"] ?? "") ?>"
                        maxlength="20"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="contact_number">
                        Contact Number *
                    </label>

                    <input
                        type="text"
                        id="contact_number"
                        name="contact_number"
                        value="<?= htmlspecialchars($student["contact_number"] ?? "") ?>"
                        maxlength="20"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email *
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($student["email"] ?? "") ?>"
                        maxlength="100"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="date_of_birth">
                        Date of Birth *
                    </label>

                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        value="<?= htmlspecialchars($student["date_of_birth"] ?? "") ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="gender">
                        Gender *
                    </label>

                    <select
                        id="gender"
                        name="gender"
                        required
                    >

                        <option value="Male"
                            <?= ($student["gender"] ?? "") === "Male" ? "selected" : "" ?>>
                            Male
                        </option>

                        <option value="Female"
                            <?= ($student["gender"] ?? "") === "Female" ? "selected" : "" ?>>
                            Female
                        </option>

                        <option value="Other"
                            <?= ($student["gender"] ?? "") === "Other" ? "selected" : "" ?>>
                            Other
                        </option>

                    </select>

                </div>


                <div class="form-group full">

                    <label for="address">
                        Address *
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        required
                    ><?= htmlspecialchars($student["address"] ?? "") ?></textarea>

                </div>


            </div>


            <p class="required-note">
                * Required fields
            </p>


            <div class="buttons">


                <button
                    type="submit"
                    name="update_student"
                    class="update-button"
                >
                    Save Changes
                </button>


                <a
                    href="student.php"
                    class="cancel-button"
                >
                    Cancel
                </a>


            </div>


        </form>


    </div>


</div>


</body>

</html>