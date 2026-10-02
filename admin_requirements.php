<?php

session_start();

require_once "db.php";
require_once "activity_logger.php";


/* =========================================================
   SECURITY CHECK
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Admin"
) {
    header("Location: admin_login.php");
    exit;
}


$message = "";
$message_type = "success";


/* =========================================================
   ADMIN INFORMATION
========================================================= */

$adminName =
    $_SESSION["full_name"]
    ?? "Administrator";

$adminUsername =
    $_SESSION["username"]
    ?? "admin";


/* =========================================================
   ADD REQUIREMENT
========================================================= */

if (isset($_POST["add_requirement"])) {

    $requirement_name = trim(
        $_POST["requirement_name"] ?? ""
    );

    $description = trim(
        $_POST["description"] ?? ""
    );

    $student_type = trim(
        $_POST["student_type"] ?? ""
    );

    $status = trim(
        $_POST["status"] ?? "Active"
    );


    if (
        $requirement_name === "" ||
        $student_type === ""
    ) {

        $message =
            "Please fill in all required fields.";

        $message_type = "error";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO requirements
            (
                requirement_name,
                description,
                student_type,
                status
            )
            VALUES (?, ?, ?, ?)
        ");


        if ($stmt) {

            $stmt->bind_param(
                "ssss",
                $requirement_name,
                $description,
                $student_type,
                $status
            );


            if ($stmt->execute()) {

                $message =
                    "Requirement added successfully.";


                /* ACTIVITY LOG */

                logActivity(
                    $conn,
                    "Added requirement: " .
                    $requirement_name .
                    " (" .
                    $student_type .
                    ")"
                );

            } else {

                $message =
                    "Unable to add requirement.";

                $message_type = "error";
            }


            $stmt->close();

        } else {

            $message =
                "Database error.";

            $message_type = "error";
        }
    }
}


/* =========================================================
   DELETE REQUIREMENT
========================================================= */

if (isset($_POST["delete_requirement"])) {

    $id = (int) (
        $_POST["id"] ?? 0
    );


    if ($id > 0) {

        /*
         * Get requirement information first
         * so it can be recorded in the audit log.
         */

        $requirement_name = "";

        $stmt = $conn->prepare("
            SELECT
                requirement_name,
                student_type
            FROM requirements
            WHERE id = ?
            LIMIT 1
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if ($result) {

                $row =
                    $result->fetch_assoc();

                if ($row) {

                    $requirement_name =
                        $row["requirement_name"];

                    $requirement_type =
                        $row["student_type"];
                }
            }

            $stmt->close();
        }


        /*
         * Delete requirement
         */

        $stmt = $conn->prepare("
            DELETE FROM requirements
            WHERE id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "Requirement deleted successfully.";


                /*
                 * ACTIVITY LOG
                 */

                logActivity(
                    $conn,
                    "Deleted requirement: " .
                    $requirement_name .
                    " (" .
                    ($requirement_type ?? "") .
                    ")"
                );

            } else {

                $message =
                    "Unable to delete requirement.";

                $message_type = "error";
            }


            $stmt->close();

        } else {

            $message =
                "Database error.";

            $message_type = "error";
        }

    } else {

        $message =
            "Invalid requirement.";

        $message_type = "error";
    }
}


/* =========================================================
   UPDATE REQUIREMENT
========================================================= */

if (isset($_POST["update_requirement"])) {

    $id = (int) (
        $_POST["id"] ?? 0
    );


    $requirement_name = trim(
        $_POST["requirement_name"] ?? ""
    );


    $description = trim(
        $_POST["description"] ?? ""
    );


    $student_type = trim(
        $_POST["student_type"] ?? ""
    );


    $status = trim(
        $_POST["status"] ?? "Active"
    );


    if (
        $id <= 0 ||
        $requirement_name === "" ||
        $student_type === ""
    ) {

        $message =
            "Please fill in all required fields.";

        $message_type = "error";

    } else {

        /*
         * Get old requirement name
         * for audit logging.
         */

        $oldRequirementName = "";

        $stmt = $conn->prepare("
            SELECT
                requirement_name
            FROM requirements
            WHERE id = ?
            LIMIT 1
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if ($result) {

                $row =
                    $result->fetch_assoc();

                if ($row) {

                    $oldRequirementName =
                        $row["requirement_name"];
                }
            }

            $stmt->close();
        }


        /*
         * Update requirement
         */

        $stmt = $conn->prepare("
            UPDATE requirements
            SET
                requirement_name = ?,
                description = ?,
                student_type = ?,
                status = ?
            WHERE id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "ssssi",
                $requirement_name,
                $description,
                $student_type,
                $status,
                $id
            );


            if ($stmt->execute()) {

                $message =
                    "Requirement updated successfully.";


                /*
                 * ACTIVITY LOG
                 */

                logActivity(
                    $conn,
                    "Updated requirement: " .
                    $requirement_name .
                    " (" .
                    $student_type .
                    ")"
                );

            } else {

                $message =
                    "Unable to update requirement.";

                $message_type = "error";
            }


            $stmt->close();

        } else {

            $message =
                "Database error.";

            $message_type = "error";
        }
    }
}


/* =========================================================
   GET REQUIREMENT FOR EDIT
========================================================= */

$edit_requirement = null;


if (isset($_GET["edit"])) {

    $id = (int) (
        $_GET["edit"] ?? 0
    );


    if ($id > 0) {

        $stmt = $conn->prepare("
            SELECT
                id,
                requirement_name,
                description,
                student_type,
                status
            FROM requirements
            WHERE id = ?
            LIMIT 1
        ");


        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();


            if ($result) {

                $edit_requirement =
                    $result->fetch_assoc();
            }


            $stmt->close();
        }
    }
}


/* =========================================================
   GET ALL REQUIREMENTS
========================================================= */

$requirements = $conn->query("
    SELECT
        id,
        requirement_name,
        description,
        student_type,
        status
    FROM requirements
    ORDER BY id DESC
");

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


        body {
            font-family: Arial, sans-serif;
            background: #f3f6f4;
            color: #333;
        }


        /* =========================
           HEADER
        ========================= */

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


        /* =========================
           MAIN
        ========================= */

        .container {
            max-width: 1100px;
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


        /* =========================
           MESSAGE
        ========================= */

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
            background: #fff0f0;

            border: 1px solid #e0b5b5;

            color: #b00020;
        }


        /* =========================
           FORM
        ========================= */

        .form-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 16px;
        }


        .form-group label {
            display: block;

            font-weight: bold;

            font-size: 14px;

            margin-bottom: 7px;
        }


        .form-group input,
        .form-group textarea,
        .form-group select {

            width: 100%;

            padding: 11px;

            border: 1px solid #bbb;

            border-radius: 5px;

            font-size: 14px;

            background: white;
        }


        .form-group textarea {

            min-height: 100px;

            resize: vertical;
        }


        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {

            outline: none;

            border-color: #006b3c;
        }


        .full-width {
            grid-column: 1 / -1;
        }


        .button-area {
            margin-top: 20px;
        }


        .button {
            padding: 11px 22px;

            border: none;

            border-radius: 5px;

            background: #006b3c;

            color: white;

            font-weight: bold;

            cursor: pointer;

            text-decoration: none;

            display: inline-block;
        }


        .button:hover {
            background: #00552f;
        }


        .cancel-button {
            background: #777;

            margin-left: 8px;
        }


        .cancel-button:hover {
            background: #555;
        }


        /* =========================
           TABLE
        ========================= */

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


        /* =========================
           ACTIONS
        ========================= */

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


        /*
         * DELETE FORM
         */

        .delete-form {
            display: inline;
        }


        .delete-button {
            background: none;

            border: none;

            padding: 0;

            color: #b00020;

            font-size: 14px;

            font-family: inherit;

            font-weight: bold;

            cursor: pointer;
        }


        .delete-button:hover {
            text-decoration: underline;
        }


        /* =========================
           STATUS
        ========================= */

        .active {
            color: #176b3a;

            font-weight: bold;
        }


        .inactive {
            color: #b00020;

            font-weight: bold;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;

            color: #777;

            padding: 25px;
        }


        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 750px) {

            .header-content {
                flex-direction: column;

                align-items: flex-start;

                gap: 15px;
            }


            .form-grid {
                grid-template-columns: 1fr;
            }


            .full-width {
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


<!-- =========================
     HEADER
========================= -->

<header>

    <div class="header-content">


        <div class="brand">

            <img
                src="assets/isu-logo.png"
                alt="Isabela State University Logo"
            >

            <div>

                <h2>
                    ISU SmartEnroll
                </h2>

                <p>
                    Requirements Management
                </p>

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



<!-- =========================
     MAIN
========================= -->

<div class="container">


    <!-- ADD / EDIT -->

    <div class="box">

        <?php if ($edit_requirement): ?>

            <h1>
                Edit Requirement
            </h1>

            <p class="description">
                Update the selected enrollment requirement.
            </p>


            <?php if ($message !== ""): ?>

                <div class="message <?= $message_type ?>">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <form method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $edit_requirement["id"] ?>"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label for="requirement_name">
                            Requirement Name
                        </label>

                        <input
                            type="text"
                            id="requirement_name"
                            name="requirement_name"
                            value="<?= htmlspecialchars($edit_requirement["requirement_name"]) ?>"
                            placeholder="Enter requirement name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="student_type">
                            Student Type
                        </label>

                        <select
                            id="student_type"
                            name="student_type"
                            required
                        >

                            <option value="">
                                Select student type
                            </option>

                            <option
                                value="First Year Student"
                                <?= $edit_requirement["student_type"] === "First Year Student" ? "selected" : "" ?>
                            >
                                First Year Student
                            </option>

                            <option
                                value="Regular / Continuing Student"
                                <?= $edit_requirement["student_type"] === "Regular / Continuing Student" ? "selected" : "" ?>
                            >
                                Regular / Continuing Student
                            </option>

                            <option
                                value="Irregular Student"
                                <?= $edit_requirement["student_type"] === "Irregular Student" ? "selected" : "" ?>
                            >
                                Irregular Student
                            </option>

                            <option
                                value="Transferee Student"
                                <?= $edit_requirement["student_type"] === "Transferee Student" ? "selected" : "" ?>
                            >
                                Transferee Student
                            </option>

                            <option
                                value="Returning Student"
                                <?= $edit_requirement["student_type"] === "Returning Student" ? "selected" : "" ?>
                            >
                                Returning Student
                            </option>

                        </select>

                    </div>


                    <div class="form-group full-width">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Enter requirement description"
                        ><?= htmlspecialchars($edit_requirement["description"] ?? "") ?></textarea>

                    </div>


                    <div class="form-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                        >

                            <option
                                value="Active"
                                <?= $edit_requirement["status"] === "Active" ? "selected" : "" ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?= $edit_requirement["status"] === "Inactive" ? "selected" : "" ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        name="update_requirement"
                        class="button"
                    >
                        Update Requirement
                    </button>


                    <a
                        href="admin_requirements.php"
                        class="button cancel-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>


        <?php else: ?>


            <h1>
                Requirements
            </h1>

            <p class="description">
                Add and manage enrollment requirements for different student types.
            </p>


            <?php if ($message !== ""): ?>

                <div class="message <?= $message_type ?>">

                    <?= htmlspecialchars($message) ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <div class="form-grid">


                    <div class="form-group">

                        <label for="requirement_name">
                            Requirement Name
                        </label>

                        <input
                            type="text"
                            id="requirement_name"
                            name="requirement_name"
                            placeholder="Example: PSA Birth Certificate"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="student_type">
                            Student Type
                        </label>

                        <select
                            id="student_type"
                            name="student_type"
                            required
                        >

                            <option value="">
                                Select student type
                            </option>

                            <option value="First Year Student">
                                First Year Student
                            </option>

                            <option value="Regular / Continuing Student">
                                Regular / Continuing Student
                            </option>

                            <option value="Irregular Student">
                                Irregular Student
                            </option>

                            <option value="Transferee Student">
                                Transferee Student
                            </option>

                            <option value="Returning Student">
                                Returning Student
                            </option>

                        </select>

                    </div>


                    <div class="form-group full-width">

                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            placeholder="Enter details about this requirement"
                        ></textarea>

                    </div>


                    <div class="form-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                        >

                            <option value="Active">
                                Active
                            </option>

                            <option value="Inactive">
                                Inactive
                            </option>

                        </select>

                    </div>


                </div>


                <div class="button-area">

                    <button
                        type="submit"
                        name="add_requirement"
                        class="button"
                    >
                        Add Requirement
                    </button>

                </div>

            </form>


        <?php endif; ?>

    </div>



    <!-- REQUIREMENT LIST -->

    <div class="box">

        <h1>
            Requirement List
        </h1>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            Requirement
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Student Type
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($requirements && $requirements->num_rows > 0): ?>


                    <?php while ($requirement = $requirements->fetch_assoc()): ?>


                        <tr>


                            <td>
                                <?= htmlspecialchars($requirement["requirement_name"]) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($requirement["description"] ?? "") ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($requirement["student_type"]) ?>
                            </td>


                            <td>

                                <?php if ($requirement["status"] === "Active"): ?>

                                    <span class="active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="inactive">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td class="action-links">


                                <!-- EDIT -->

                                <a
                                    href="admin_requirements.php?edit=<?= (int) $requirement["id"] ?>"
                                    class="edit"
                                >
                                    Edit
                                </a>


                                <span>
                                    |
                                </span>


                                <!-- DELETE -->

                                <form
                                    method="POST"
                                    class="delete-form"
                                    onsubmit="return confirm('Are you sure you want to delete this requirement?');"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $requirement["id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_requirement"
                                        class="delete-button"
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
                            colspan="5"
                            class="empty"
                        >
                            No requirements added yet.
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

<?php

$conn->close();

?>