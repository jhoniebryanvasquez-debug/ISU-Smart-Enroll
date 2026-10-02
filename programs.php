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
   ADD PROGRAM
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_program"])
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

        $code = trim($_POST["code"] ?? "");
        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");


        /* =================================================
           VALIDATION
        ================================================= */

        if ($code === "" || $name === "") {

            $message = "Please fill in all required fields.";
            $messageType = "error";

        } elseif (strlen($code) > 20) {

            $message = "Program code is too long.";
            $messageType = "error";

        } elseif (strlen($name) > 200) {

            $message = "Program name is too long.";
            $messageType = "error";

        } else {

            /* =============================================
               CHECK DUPLICATE PROGRAM CODE
            ============================================= */

            $check = $conn->prepare(
                "SELECT id
                 FROM programs
                 WHERE code = ?
                 LIMIT 1"
            );

            if (!$check) {

                $message = "Unable to validate program code.";
                $messageType = "error";

            } else {

                $check->bind_param(
                    "s",
                    $code
                );

                $check->execute();

                $duplicate = $check->get_result();

                $codeExists = $duplicate->num_rows > 0;

                $check->close();


                if ($codeExists) {

                    $message = "Program code already exists.";
                    $messageType = "error";

                } else {

                    /* =====================================
                       INSERT PROGRAM
                    ===================================== */

                    $stmt = $conn->prepare(
                        "INSERT INTO programs
                        (
                            code,
                            name,
                            description
                        )
                        VALUES (?, ?, ?)"
                    );

                    if (!$stmt) {

                        $message = "Unable to prepare program creation.";
                        $messageType = "error";

                    } else {

                        $stmt->bind_param(
                            "sss",
                            $code,
                            $name,
                            $description
                        );


                        if ($stmt->execute()) {

                            $stmt->close();


                            /* =============================
                               ACTIVITY LOG
                            ============================= */

                            logActivity(
                                $conn,
                                "Added program: " .
                                $code .
                                " - " .
                                $name
                            );


                            header(
                                "Location: programs.php?added=1"
                            );

                            exit;

                        } else {

                            $message = "Unable to add program.";
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
   DELETE PROGRAM
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_program"])
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

            $message = "Invalid program selected.";
            $messageType = "error";

        } else {

            /* =============================================
               GET PROGRAM BEFORE DELETE
            ============================================= */

            $stmt = $conn->prepare(
                "SELECT code, name
                 FROM programs
                 WHERE id = ?
                 LIMIT 1"
            );

            if (!$stmt) {

                $message = "Unable to find program.";
                $messageType = "error";

            } else {

                $stmt->bind_param(
                    "i",
                    $id
                );

                $stmt->execute();

                $result = $stmt->get_result();

                $programToDelete = $result->fetch_assoc();

                $stmt->close();


                if (!$programToDelete) {

                    $message = "Program not found.";
                    $messageType = "error";

                } else {

                    /* =====================================
                       DELETE PROGRAM
                    ===================================== */

                    $stmt = $conn->prepare(
                        "DELETE FROM programs
                         WHERE id = ?"
                    );

                    if (!$stmt) {

                        $message = "Unable to prepare program deletion.";
                        $messageType = "error";

                    } else {

                        $stmt->bind_param(
                            "i",
                            $id
                        );


                        if ($stmt->execute()) {

                            $stmt->close();


                            /* =============================
                               ACTIVITY LOG
                            ============================= */

                            logActivity(
                                $conn,
                                "Deleted program: " .
                                $programToDelete["code"] .
                                " - " .
                                $programToDelete["name"]
                            );


                            header(
                                "Location: programs.php?deleted=1"
                            );

                            exit;

                        } else {

                            $message =
                                "Unable to delete program. It may be referenced by other records.";

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
   UPDATE PROGRAM
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_program"])
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

        $code = trim($_POST["code"] ?? "");
        $name = trim($_POST["name"] ?? "");
        $description = trim($_POST["description"] ?? "");


        /* =================================================
           VALIDATION
        ================================================= */

        if ($id <= 0) {

            $message = "Invalid program selected.";
            $messageType = "error";

        } elseif ($code === "" || $name === "") {

            $message = "Please fill in all required fields.";
            $messageType = "error";

        } elseif (strlen($code) > 20) {

            $message = "Program code is too long.";
            $messageType = "error";

        } elseif (strlen($name) > 200) {

            $message = "Program name is too long.";
            $messageType = "error";

        } else {

            /* =============================================
               GET CURRENT PROGRAM
            ============================================= */

            $stmt = $conn->prepare(
                "SELECT code, name
                 FROM programs
                 WHERE id = ?
                 LIMIT 1"
            );

            if (!$stmt) {

                $message = "Unable to find current program.";
                $messageType = "error";

            } else {

                $stmt->bind_param(
                    "i",
                    $id
                );

                $stmt->execute();

                $result = $stmt->get_result();

                $currentProgram = $result->fetch_assoc();

                $stmt->close();


                if (!$currentProgram) {

                    $message = "Program not found.";
                    $messageType = "error";

                } else {

                    /* =====================================
                       CHECK DUPLICATE PROGRAM CODE
                    ===================================== */

                    $check = $conn->prepare(
                        "SELECT id
                         FROM programs
                         WHERE code = ?
                         AND id != ?
                         LIMIT 1"
                    );

                    if (!$check) {

                        $message = "Unable to validate program code.";
                        $messageType = "error";

                    } else {

                        $check->bind_param(
                            "si",
                            $code,
                            $id
                        );

                        $check->execute();

                        $duplicate = $check->get_result();

                        $codeExists =
                            $duplicate->num_rows > 0;

                        $check->close();


                        if ($codeExists) {

                            $message =
                                "Program code already exists.";

                            $messageType = "error";

                        } else {

                            /* =================================
                               UPDATE PROGRAM
                            ================================= */

                            $stmt = $conn->prepare(
                                "UPDATE programs
                                 SET code = ?,
                                     name = ?,
                                     description = ?
                                 WHERE id = ?"
                            );

                            if (!$stmt) {

                                $message =
                                    "Unable to prepare program update.";

                                $messageType = "error";

                            } else {

                                $stmt->bind_param(
                                    "sssi",
                                    $code,
                                    $name,
                                    $description,
                                    $id
                                );


                                if ($stmt->execute()) {

                                    $stmt->close();


                                    /* =========================
                                       ACTIVITY LOG
                                    ========================= */

                                    logActivity(
                                        $conn,
                                        "Updated program: " .
                                        $code .
                                        " - " .
                                        $name
                                    );


                                    header(
                                        "Location: programs.php?updated=1"
                                    );

                                    exit;

                                } else {

                                    $message =
                                        "Unable to update program.";

                                    $messageType = "error";

                                    $stmt->close();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   GET PROGRAM FOR EDIT
========================================================= */

$edit_program = null;

if (isset($_GET["edit"])) {

    $id = (int) $_GET["edit"];

    if ($id > 0) {

        $stmt = $conn->prepare(
            "SELECT *
             FROM programs
             WHERE id = ?
             LIMIT 1"
        );

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $edit_program = $result->fetch_assoc();

            $stmt->close();
        }
    }
}


/* =========================================================
   GET ALL PROGRAMS
========================================================= */

$result = $conn->query(
    "SELECT
        id,
        code,
        name,
        description,
        created_at
     FROM programs
     ORDER BY id DESC"
);


/* =========================================================
   RESULT MESSAGE
========================================================= */

if (isset($_GET["added"])) {

    $message = "Program added successfully.";
    $messageType = "success";
}

if (isset($_GET["updated"])) {

    $message = "Program updated successfully.";
    $messageType = "success";
}

if (isset($_GET["deleted"])) {

    $message = "Program deleted successfully.";
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

    <title>Programs - ISU SmartEnroll</title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f3;
            color: #222;
        }


        .header {
            background: #006b3c;
            color: white;
            padding: 25px 10%;
        }


        .header h1 {
            margin: 0;
            font-size: 30px;
        }


        .header p {
            margin: 5px 0 0;
            font-size: 16px;
        }


        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
        }


        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }


        .card h2 {
            color: #006b3c;
            margin-top: 0;
        }


        .message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }


        .message.success {
            background: #e8f5ed;
            border: 1px solid #9ed0b2;
            color: #006b3c;
        }


        .message.error {
            background: #fff1f1;
            border: 1px solid #e0aaaa;
            color: #a10000;
        }


        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }


        .form-group {
            display: flex;
            flex-direction: column;
        }


        .form-group.full {
            grid-column: 1 / -1;
        }


        .form-group label {
            font-weight: bold;
            margin-bottom: 8px;
        }


        input,
        textarea {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            font-family: Arial, sans-serif;
        }


        textarea {
            min-height: 110px;
            resize: vertical;
        }


        input:focus,
        textarea:focus {
            outline: none;
            border-color: #006b3c;
        }


        .buttons {
            margin-top: 20px;
        }


        button {
            background: #006b3c;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }


        button:hover {
            background: #00552f;
        }


        .cancel {
            display: inline-block;
            margin-left: 10px;
            padding: 12px 22px;
            background: #777;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }


        .table-wrapper {
            overflow-x: auto;
        }


        table {
            width: 100%;
            border-collapse: collapse;
        }


        th {
            background: #e8f1ec;
            color: #006b3c;
            text-align: left;
            padding: 14px;
        }


        td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }


        .description-cell {
            max-width: 350px;
            white-space: normal;
            line-height: 1.5;
        }


        .date-cell {
            white-space: nowrap;
        }


        .edit {
            color: #006b3c;
            font-weight: bold;
            text-decoration: none;
            margin-right: 15px;
        }


        .delete {
            color: #b00020;
            font-weight: bold;
            text-decoration: none;
        }


        .delete:hover,
        .edit:hover {
            text-decoration: underline;
        }


        .empty-state {
            text-align: center;
            color: #777;
            padding: 25px !important;
        }


        @media (max-width: 700px) {

            .form-grid {
                grid-template-columns: 1fr;
            }


            .form-group.full {
                grid-column: auto;
            }


            .header {
                padding: 20px;
            }


            .container {
                width: 95%;
            }


            .card {
                padding: 20px;
            }

        }

    </style>

</head>


<body>


<div class="header">

    <h1>
        ISU SmartEnroll
    </h1>

    <p>
        Programs Management
    </p>

</div>


<div class="container">


    <!-- MESSAGE -->

    <?php if ($message !== ""): ?>

        <div class="message <?= htmlspecialchars($messageType) ?>">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <!-- ADD / EDIT FORM -->

    <div class="card">


        <?php if ($edit_program): ?>


            <h2>
                Edit Program
            </h2>


            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrf_token) ?>"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int) $edit_program["id"] ?>"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Program Code
                        </label>

                        <input
                            type="text"
                            name="code"
                            value="<?= htmlspecialchars($edit_program["code"]) ?>"
                            maxlength="20"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Program Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($edit_program["name"]) ?>"
                            maxlength="200"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            maxlength="5000"
                            placeholder="Enter program description..."
                        ><?= htmlspecialchars($edit_program["description"] ?? "") ?></textarea>

                    </div>


                </div>


                <div class="buttons">

                    <button
                        type="submit"
                        name="update_program"
                    >
                        Update Program
                    </button>


                    <a
                        href="programs.php"
                        class="cancel"
                    >
                        Cancel
                    </a>

                </div>


            </form>


        <?php else: ?>


            <h2>
                Add Program
            </h2>


            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrf_token) ?>"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Program Code
                        </label>

                        <input
                            type="text"
                            name="code"
                            placeholder="Example: BSIT"
                            maxlength="20"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Program Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            placeholder="Example: Bachelor of Science in Information Technology"
                            maxlength="200"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label>
                            Description
                        </label>

                        <textarea
                            name="description"
                            maxlength="5000"
                            placeholder="Enter program description..."
                        ></textarea>

                    </div>


                </div>


                <div class="buttons">

                    <button
                        type="submit"
                        name="add_program"
                    >
                        Add Program
                    </button>

                </div>


            </form>


        <?php endif; ?>


    </div>


    <!-- PROGRAM LIST -->

    <div class="card">


        <h2>
            Program List
        </h2>


        <div class="table-wrapper">


            <table>

                <thead>

                    <tr>

                        <th>
                            Program Code
                        </th>

                        <th>
                            Program Name
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Date Added
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($result && $result->num_rows > 0): ?>


                    <?php while ($program = $result->fetch_assoc()): ?>


                        <tr>


                            <td>
                                <?= htmlspecialchars($program["code"]) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($program["name"]) ?>
                            </td>


                            <td class="description-cell">

                                <?php

                                $description =
                                    trim($program["description"] ?? "");

                                if ($description === ""):

                                ?>

                                    <span style="color:#999;">
                                        No description
                                    </span>

                                <?php else: ?>

                                    <?= nl2br(htmlspecialchars($description)) ?>

                                <?php endif; ?>

                            </td>


                            <td class="date-cell">

                                <?= htmlspecialchars(
                                    date(
                                        "M d, Y",
                                        strtotime($program["created_at"])
                                    )
                                ) ?>

                            </td>


                            <td>


                                <a
                                    class="edit"
                                    href="programs.php?edit=<?= (int) $program["id"] ?>"
                                >
                                    Edit
                                </a>


                                <form
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('Delete this program?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($csrf_token) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $program["id"] ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="delete_program"
                                        class="delete"
                                        style="
                                            background:none;
                                            border:none;
                                            padding:0;
                                            font-size:inherit;
                                            font-weight:bold;
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
                            colspan="5"
                            class="empty-state"
                        >
                            No programs added yet.
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