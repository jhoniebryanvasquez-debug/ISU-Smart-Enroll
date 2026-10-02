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
   MESSAGE
========================================================= */

$message = "";
$messageType = "";


/* =========================================================
   ADD STUDENT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_student"])
) {

    $submitted_token = $_POST["csrf_token"] ?? "";

    if (
        !hash_equals(
            $_SESSION["csrf_token"],
            $submitted_token
        )
    ) {

        $message = "Invalid security token. Please refresh the page and try again.";
        $messageType = "error";

    } else {

        $student_number = trim($_POST["student_number"] ?? "");
        $first_name = trim($_POST["first_name"] ?? "");
        $middle_name = trim($_POST["middle_name"] ?? "");
        $last_name = trim($_POST["last_name"] ?? "");
        $course = trim($_POST["course"] ?? "");
        $year_level = trim($_POST["year_level"] ?? "");
        $contact_number = trim($_POST["contact_number"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $date_of_birth = trim($_POST["date_of_birth"] ?? "");
        $gender = trim($_POST["gender"] ?? "");


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

            $message = "Please fill in all required fields.";
            $messageType = "error";

        } elseif (strlen($first_name) > 100) {

            $message = "First name is too long.";
            $messageType = "error";

        } elseif (strlen($middle_name) > 100) {

            $message = "Middle name is too long.";
            $messageType = "error";

        } elseif (strlen($last_name) > 100) {

            $message = "Last name is too long.";
            $messageType = "error";

        } elseif (strlen($course) > 100) {

            $message = "Course is too long.";
            $messageType = "error";

        } elseif (strlen($year_level) > 20) {

            $message = "Year level is too long.";
            $messageType = "error";

        } elseif (strlen($contact_number) > 20) {

            $message = "Contact number is too long.";
            $messageType = "error";

        } elseif (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $messageType = "error";

        } elseif (strlen($gender) > 20) {

            $message = "Invalid gender.";
            $messageType = "error";

        } else {

            /* =============================================
               CHECK STUDENT NUMBER DUPLICATE
            ============================================= */

            if ($student_number !== "") {

                $check = $conn->prepare(
                    "SELECT id
                     FROM students
                     WHERE student_number = ?
                     LIMIT 1"
                );

                if (!$check) {

                    $message = "Unable to validate student number.";
                    $messageType = "error";

                } else {

                    $check->bind_param(
                        "s",
                        $student_number
                    );

                    $check->execute();

                    $duplicate = $check->get_result();

                    $studentNumberExists =
                        $duplicate->num_rows > 0;

                    $check->close();


                    if ($studentNumberExists) {

                        $message = "Student number already exists.";
                        $messageType = "error";
                    }
                }
            }


            /* =============================================
               INSERT STUDENT
            ============================================= */

            if ($message === "") {

                $stmt = $conn->prepare(
                    "INSERT INTO students
                    (
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
                    )
                    VALUES
                    (
                        NULLIF(?, ''),
                        ?,
                        NULLIF(?, ''),
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )"
                );


                if (!$stmt) {

                    $message = "Unable to prepare student registration.";
                    $messageType = "error";

                } else {

                    $stmt->bind_param(
                        "sssssssssss",
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
                        $gender
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
                            "Added student: " . $full_name
                        );


                        header(
                            "Location: student.php?added=1"
                        );

                        exit;

                    } else {

                        $message =
                            "Unable to add student. Please check the information.";

                        $messageType = "error";

                        $stmt->close();
                    }
                }
            }
        }
    }
}


/* =========================================================
   DELETE STUDENT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_student"])
) {

    $submitted_token = $_POST["csrf_token"] ?? "";

    if (
        !hash_equals(
            $_SESSION["csrf_token"],
            $submitted_token
        )
    ) {

        $message = "Invalid security token. Please refresh the page and try again.";
        $messageType = "error";

    } else {

        $id = isset($_POST["id"])
            ? (int) $_POST["id"]
            : 0;


        if ($id <= 0) {

            $message = "Invalid student selected.";
            $messageType = "error";

        } else {

            /* =============================================
               GET STUDENT BEFORE DELETE
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

                $message = "Unable to find student.";
                $messageType = "error";

            } else {

                $stmt->bind_param(
                    "i",
                    $id
                );

                $stmt->execute();

                $result = $stmt->get_result();

                $studentToDelete = $result->fetch_assoc();

                $stmt->close();


                if (!$studentToDelete) {

                    $message = "Student not found.";
                    $messageType = "error";

                } else {

                    /* =====================================
                       DELETE
                    ===================================== */

                    $stmt = $conn->prepare(
                        "DELETE FROM students
                         WHERE id = ?"
                    );


                    if (!$stmt) {

                        $message = "Unable to prepare student deletion.";
                        $messageType = "error";

                    } else {

                        $stmt->bind_param(
                            "i",
                            $id
                        );


                        if ($stmt->execute()) {

                            $stmt->close();


                            $full_name = trim(
                                $studentToDelete["first_name"] . " " .
                                $studentToDelete["middle_name"] . " " .
                                $studentToDelete["last_name"]
                            );

                            $full_name = preg_replace(
                                '/\s+/',
                                ' ',
                                $full_name
                            );


                            $identifier =
                                $studentToDelete["student_number"] !== null &&
                                $studentToDelete["student_number"] !== ""
                                    ? " (" . $studentToDelete["student_number"] . ")"
                                    : "";


                            logActivity(
                                $conn,
                                "Deleted student: " .
                                $full_name .
                                $identifier
                            );


                            header(
                                "Location: student.php?deleted=1"
                            );

                            exit;

                        } else {

                            $message =
                                "Unable to delete student. The record may be referenced by another record.";

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
   GET STUDENTS
========================================================= */

$students = $conn->query(
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
        gender,
        created_at
     FROM students
     ORDER BY id DESC"
);


/* =========================================================
   RESULT MESSAGES
========================================================= */

if (isset($_GET["added"])) {

    $message = "Student added successfully.";
    $messageType = "success";
}

if (isset($_GET["updated"])) {

    $message = "Student updated successfully.";
    $messageType = "success";
}

if (isset($_GET["deleted"])) {

    $message = "Student deleted successfully.";
    $messageType = "success";
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

    <title>Student Records - ISU SmartEnroll</title>


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


        .dashboard-link {
            color: white;
            text-decoration: none;

            border: 1px solid white;

            padding: 8px 15px;

            border-radius: 5px;
        }


        .dashboard-link:hover {
            background: white;
            color: #006b3c;
        }


        .container {
            max-width: 1250px;
            margin: 30px auto;
            padding: 0 20px;
        }


        .box {
            background: white;

            border: 1px solid #ddd;

            border-radius: 8px;

            padding: 25px;

            margin-bottom: 25px;
        }


        h1 {
            color: #006b3c;

            margin-bottom: 8px;

            font-size: 27px;
        }


        .description {
            color: #777;

            margin-bottom: 22px;
        }


        .message {
            padding: 11px;

            border-radius: 5px;

            margin-bottom: 20px;
        }


        .message.success {
            background: #eaf7ef;

            border: 1px solid #b8dfc7;

            color: #176b3a;
        }


        .message.error {
            background: #fff1f1;

            border: 1px solid #e0aaaa;

            color: #a10000;
        }


        .form-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 16px;
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


        .required-note {
            color: #777;

            font-size: 12px;

            margin-top: 15px;
        }


        .add-button {

            margin-top: 20px;

            padding: 11px 22px;

            border: none;

            border-radius: 5px;

            background: #006b3c;

            color: white;

            font-weight: bold;

            cursor: pointer;
        }


        .add-button:hover {
            background: #00552f;
        }


        .table-container {
            overflow-x: auto;
        }


        table {
            width: 100%;

            border-collapse: collapse;

            margin-top: 10px;
        }


        th,
        td {

            padding: 12px 10px;

            border-bottom: 1px solid #ddd;

            text-align: left;

            font-size: 14px;

            vertical-align: top;
        }


        th {

            background: #f0f5f2;

            color: #006b3c;

            white-space: nowrap;
        }


        tr:hover {
            background: #fafafa;
        }


        .action-links {
            white-space: nowrap;
        }


        .edit {
            color: #006b3c;

            text-decoration: none;

            font-weight: bold;
        }


        .edit:hover {
            text-decoration: underline;
        }


        .delete {
            color: #b00020;

            text-decoration: none;

            font-weight: bold;
        }


        .delete:hover {
            text-decoration: underline;
        }


        .empty {
            text-align: center;

            color: #777;

            padding: 25px;
        }


        .muted {
            color: #999;
        }


        @media (max-width: 750px) {

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


            .brand img {

                width: 50px;

                height: 50px;
            }


            .brand h2 {

                font-size: 20px;
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

                <p>Student Records</p>

            </div>


        </div>


        <a
            href="admin_dashboard.php"
            class="dashboard-link"
        >
            ← Dashboard
        </a>


    </div>

</header>


<div class="container">


    <!-- ADD STUDENT -->

    <div class="box">


        <h1>Student Records</h1>


        <p class="description">
            Add and manage student information.
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
                        placeholder="Optional"
                        maxlength="50"
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
                        placeholder="Enter first name"
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
                        placeholder="Optional"
                        maxlength="100"
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
                        placeholder="Enter last name"
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
                        placeholder="Example: BSIT"
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
                        placeholder="Example: 1st Year"
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
                        placeholder="Enter contact number"
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
                        placeholder="Enter email address"
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

                        <option value="">
                            Select gender
                        </option>

                        <option value="Male">
                            Male
                        </option>

                        <option value="Female">
                            Female
                        </option>

                        <option value="Other">
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
                        placeholder="Enter complete address"
                        required
                    ></textarea>

                </div>


            </div>


            <p class="required-note">
                * Required fields
            </p>


            <button
                type="submit"
                name="add_student"
                class="add-button"
            >
                Add Student
            </button>


        </form>


    </div>


    <!-- STUDENT LIST -->

    <div class="box">


        <h1>Student List</h1>


        <div class="table-container">


            <table>


                <thead>

                    <tr>

                        <th>Student Number</th>

                        <th>Name</th>

                        <th>Course</th>

                        <th>Year Level</th>

                        <th>Contact</th>

                        <th>Email</th>

                        <th>Gender</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($students && $students->num_rows > 0): ?>


                    <?php while ($student = $students->fetch_assoc()): ?>


                        <?php

                        $full_name = trim(
                            $student["first_name"] . " " .
                            $student["middle_name"] . " " .
                            $student["last_name"]
                        );

                        $full_name = preg_replace(
                            '/\s+/',
                            ' ',
                            $full_name
                        );

                        ?>


                        <tr>


                            <td>

                                <?php if (
                                    $student["student_number"] !== null &&
                                    $student["student_number"] !== ""
                                ): ?>

                                    <?= htmlspecialchars(
                                        $student["student_number"]
                                    ) ?>

                                <?php else: ?>

                                    <span class="muted">
                                        Not assigned
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?= htmlspecialchars($full_name) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $student["course"] ?? ""
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $student["year_level"] ?? ""
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $student["contact_number"] ?? ""
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $student["email"] ?? ""
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $student["gender"] ?? ""
                                ) ?>

                            </td>


                            <td class="action-links">


                                <a
                                    href="edit_student.php?id=<?= (int) $student["id"] ?>"
                                    class="edit"
                                >
                                    Edit
                                </a>


                                <span> | </span>


                                <form
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('Are you sure you want to delete this student?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($csrf_token) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $student["id"] ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="delete_student"
                                        class="delete"
                                        style="
                                            background:none;
                                            border:none;
                                            padding:0;
                                            font-size:14px;
                                            cursor:pointer;
                                        "
                                    >
                                        Delete
                                    </button>

                                </form>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="empty"
                        >
                            No student records yet.
                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>


</body>

</html>