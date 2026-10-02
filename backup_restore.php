<?php

session_start();

require_once "db.php";
require_once "activity_logger.php";

/* =========================================================
   ADMIN SECURITY
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: admin_dashboard.php");
    exit;
}

/* =========================================================
   DATABASE / BACKUP SETTINGS
========================================================= */

$database_name = "isu_smartenroll";

$backup_dir = __DIR__ . DIRECTORY_SEPARATOR . "backups";

if (!is_dir($backup_dir)) {
    @mkdir($backup_dir, 0755, true);
}

/* Protect backup folder from direct web access */
$htaccess_file = $backup_dir . DIRECTORY_SEPARATOR . ".htaccess";

if (!file_exists($htaccess_file)) {
    $htaccess_content = <<<HTACCESS
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
HTACCESS;

    @file_put_contents($htaccess_file, $htaccess_content);
}

/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION["backup_csrf_token"])) {
    $_SESSION["backup_csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["backup_csrf_token"];

/* =========================================================
   HELPERS
========================================================= */

function formatFileSize($bytes)
{
    if ($bytes <= 0) {
        return "0 Bytes";
    }

    $units = ["Bytes", "KB", "MB", "GB", "TB"];

    $power = floor(log($bytes, 1024));

    $power = min($power, count($units) - 1);

    return number_format(
        $bytes / pow(1024, $power),
        2
    ) . " " . $units[$power];
}


function setBackupMessage($type, $message)
{
    $_SESSION["backup_message_type"] = $type;
    $_SESSION["backup_message"] = $message;
}


function getBackupMessage()
{
    $message = null;

    if (isset($_SESSION["backup_message"])) {
        $message = [
            "type" => $_SESSION["backup_message_type"] ?? "success",
            "message" => $_SESSION["backup_message"]
        ];

        unset($_SESSION["backup_message"]);
        unset($_SESSION["backup_message_type"]);
    }

    return $message;
}


/* =========================================================
   CREATE DATABASE BACKUP
========================================================= */

function createDatabaseBackup($conn, $backup_dir, $database_name, $prefix = "isu_smartenroll_backup")
{
    if (!($conn instanceof mysqli)) {
        return [
            "success" => false,
            "message" => "Database connection is unavailable."
        ];
    }

    $timestamp = date("Y-m-d_H-i-s");

    $filename = $prefix . "_" . $timestamp . ".sql";

    $filepath = $backup_dir . DIRECTORY_SEPARATOR . $filename;

    $sql_output = "";

    $sql_output .= "-- =====================================================\n";
    $sql_output .= "-- ISU SmartEnroll Database Backup\n";
    $sql_output .= "-- Database: " . $database_name . "\n";
    $sql_output .= "-- Created: " . date("Y-m-d H:i:s") . "\n";
    $sql_output .= "-- =====================================================\n\n";

    $sql_output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables_result = $conn->query("SHOW TABLES");

    if (!$tables_result) {
        return [
            "success" => false,
            "message" => "Unable to retrieve database tables: " . $conn->error
        ];
    }

    while ($table_row = $tables_result->fetch_row()) {

        $table = $table_row[0];

        $safe_table = str_replace(
            "`",
            "``",
            $table
        );

        /* ---------------------------------------------
           DROP TABLE
        --------------------------------------------- */

        $sql_output .= "-- -----------------------------------------------------\n";
        $sql_output .= "-- Table: " . $table . "\n";
        $sql_output .= "-- -----------------------------------------------------\n\n";

        $sql_output .= "DROP TABLE IF EXISTS `" . $safe_table . "`;\n\n";

        /* ---------------------------------------------
           CREATE TABLE
        --------------------------------------------- */

        $create_result = $conn->query(
            "SHOW CREATE TABLE `" . $safe_table . "`"
        );

        if (!$create_result) {
            return [
                "success" => false,
                "message" => "Unable to read table structure for " . $table . ": " . $conn->error
            ];
        }

        $create_row = $create_result->fetch_assoc();

        $create_sql = "";

        if (isset($create_row["Create Table"])) {
            $create_sql = $create_row["Create Table"];
        } else {
            $values = array_values($create_row);

            if (isset($values[1])) {
                $create_sql = $values[1];
            }
        }

        if ($create_sql !== "") {
            $sql_output .= $create_sql . ";\n\n";
        }

        $create_result->free();

        /* ---------------------------------------------
           INSERT DATA
        --------------------------------------------- */

        $data_result = $conn->query(
            "SELECT * FROM `" . $safe_table . "`"
        );

        if (!$data_result) {
            return [
                "success" => false,
                "message" => "Unable to read data from table " . $table . ": " . $conn->error
            ];
        }

        if ($data_result->num_rows > 0) {

            $field_names = [];

            $field_count = $data_result->field_count;

            $fields = $data_result->fetch_fields();

            foreach ($fields as $field) {

                $field_name = str_replace(
                    "`",
                    "``",
                    $field->name
                );

                $field_names[] = "`" . $field_name . "`";
            }

            $column_list = implode(", ", $field_names);

            while ($row = $data_result->fetch_assoc()) {

                $value_list = [];

                foreach ($row as $value) {

                    if ($value === null) {

                        $value_list[] = "NULL";

                    } else {

                        $value_list[] = "'" .
                            $conn->real_escape_string($value) .
                            "'";
                    }
                }

                $sql_output .=
                    "INSERT INTO `" .
                    $safe_table .
                    "` (" .
                    $column_list .
                    ") VALUES (" .
                    implode(", ", $value_list) .
                    ");\n";
            }

            $sql_output .= "\n";
        }

        $data_result->free();
    }

    $tables_result->free();

    $sql_output .= "SET FOREIGN_KEY_CHECKS=1;\n";

    if (@file_put_contents($filepath, $sql_output) === false) {

        return [
            "success" => false,
            "message" => "Unable to write the database backup file."
        ];
    }

    return [
        "success" => true,
        "filename" => $filename,
        "filepath" => $filepath
    ];
}


/* =========================================================
   CREATE APPLICATION FILE BACKUP
========================================================= */

function createFilesBackup($project_dir, $backup_dir)
{
    if (!class_exists("ZipArchive")) {
        return [
            "success" => false,
            "message" => "PHP ZipArchive extension is not enabled."
        ];
    }

    $timestamp = date("Y-m-d_H-i-s");

    $filename =
        "ISU_SmartEnroll_files_backup_" .
        $timestamp .
        ".zip";

    $filepath =
        $backup_dir .
        DIRECTORY_SEPARATOR .
        $filename;

    $zip = new ZipArchive();

    if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {

        return [
            "success" => false,
            "message" => "Unable to create application backup ZIP."
        ];
    }

    $project_real = realpath($project_dir);

    if ($project_real === false) {

        $zip->close();

        return [
            "success" => false,
            "message" => "Unable to determine project directory."
        ];
    }

    $project_real = rtrim(
        str_replace("\\", "/", $project_real),
        "/"
    );

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $project_real,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {

        $full_path = str_replace(
            "\\",
            "/",
            $item->getPathname()
        );

        $relative_path = ltrim(
            substr(
                $full_path,
                strlen($project_real)
            ),
            "/"
        );

        if ($relative_path === "") {
            continue;
        }

        /* ---------------------------------------------
           SECURITY EXCLUSIONS
        --------------------------------------------- */

        if (
            $relative_path === "backups" ||
            strpos($relative_path, "backups/") === 0
        ) {
            continue;
        }

        if (
            $relative_path === ".git" ||
            strpos($relative_path, ".git/") === 0
        ) {
            continue;
        }

        /* Never place DB credentials inside ZIP */
        if (
            strtolower($relative_path) === "db.php"
        ) {
            continue;
        }

        /* Temporary admin creation tool should never be backed up */
        if (
            strtolower($relative_path) === "create_admin.php"
        ) {
            continue;
        }

        /* Skip the current ZIP if encountered */
        if (
            basename($relative_path) === $filename
        ) {
            continue;
        }

        if ($item->isDir()) {

            $zip->addEmptyDir(
                $relative_path
            );

        } elseif ($item->isFile()) {

            $zip->addFile(
                $full_path,
                $relative_path
            );
        }
    }

    $zip->close();

    if (!file_exists($filepath)) {

        return [
            "success" => false,
            "message" => "Application backup ZIP was not created."
        ];
    }

    return [
        "success" => true,
        "filename" => $filename,
        "filepath" => $filepath
    ];
}


/* =========================================================
   RESTORE DATABASE
========================================================= */

function restoreDatabase($conn, $filepath)
{
    if (!file_exists($filepath)) {

        return [
            "success" => false,
            "message" => "Database backup file was not found."
        ];
    }

    if (strtolower(pathinfo($filepath, PATHINFO_EXTENSION)) !== "sql") {

        return [
            "success" => false,
            "message" => "Invalid database backup file."
        ];
    }

    $sql = @file_get_contents($filepath);

    if ($sql === false || trim($sql) === "") {

        return [
            "success" => false,
            "message" => "The database backup file is empty or unreadable."
        ];
    }

    /*
     * The generated backup files are normal SQL statements.
     * Using multi_query allows strings containing semicolons
     * to remain intact.
     */

    $sql = "SET FOREIGN_KEY_CHECKS=0;\n" . $sql . "\nSET FOREIGN_KEY_CHECKS=1;";

    if (!$conn->multi_query($sql)) {

        $error = $conn->error;

        /* Clear remaining query results */
        while ($conn->more_results()) {
            $conn->next_result();
        }

        return [
            "success" => false,
            "message" => "Database restore failed: " . $error
        ];
    }

    /* Consume all remaining results */
    while ($conn->more_results()) {

        if (!$conn->next_result()) {

            $error = $conn->error;

            return [
                "success" => false,
                "message" => "Database restore failed: " . $error
            ];
        }
    }

    return [
        "success" => true
    ];
}


/* =========================================================
   RESTORE APPLICATION FILES
========================================================= */

function restoreFiles($project_dir, $filepath)
{
    if (!class_exists("ZipArchive")) {

        return [
            "success" => false,
            "message" => "PHP ZipArchive extension is not enabled."
        ];
    }

    if (!file_exists($filepath)) {

        return [
            "success" => false,
            "message" => "Application backup file was not found."
        ];
    }

    if (strtolower(pathinfo($filepath, PATHINFO_EXTENSION)) !== "zip") {

        return [
            "success" => false,
            "message" => "Invalid application backup file."
        ];
    }

    $zip = new ZipArchive();

    if ($zip->open($filepath) !== true) {

        return [
            "success" => false,
            "message" => "Unable to open the application backup."
        ];
    }

    $project_real = realpath($project_dir);

    if ($project_real === false) {

        $zip->close();

        return [
            "success" => false,
            "message" => "Unable to determine project directory."
        ];
    }

    $project_real = rtrim(
        str_replace("\\", "/", $project_real),
        "/"
    );

    /* ---------------------------------------------
       VALIDATE EVERY ZIP ENTRY FIRST
    --------------------------------------------- */

    for ($i = 0; $i < $zip->numFiles; $i++) {

        $entry = $zip->getNameIndex($i);

        if ($entry === false) {
            $zip->close();

            return [
                "success" => false,
                "message" => "Invalid ZIP entry detected."
            ];
        }

        $entry_normalized = str_replace(
            "\\",
            "/",
            $entry
        );

        if (
            strpos($entry_normalized, "../") !== false ||
            strpos($entry_normalized, "..") === 0 ||
            strpos($entry_normalized, "/..") !== false ||
            preg_match('/^[A-Za-z]:\//', $entry_normalized) ||
            strpos($entry_normalized, "/") === 0
        ) {
            $zip->close();

            return [
                "success" => false,
                "message" => "Unsafe ZIP path detected."
            ];
        }

        /*
         * Never allow a ZIP restore to replace
         * database credentials.
         */
        if (
            strtolower($entry_normalized) === "db.php"
        ) {
            $zip->close();

            return [
                "success" => false,
                "message" => "This backup contains a prohibited db.php file."
            ];
        }

        /*
         * Never allow restoration into the backup folder.
         */
        if (
            $entry_normalized === "backups" ||
            strpos($entry_normalized, "backups/") === 0
        ) {
            $zip->close();

            return [
                "success" => false,
                "message" => "Backup folder restoration is not allowed."
            ];
        }
    }

    /* ---------------------------------------------
       EXTRACT
    --------------------------------------------- */

    if (!$zip->extractTo($project_real)) {

        $zip->close();

        return [
            "success" => false,
            "message" => "Unable to extract the application backup."
        ];
    }

    $zip->close();

    return [
        "success" => true
    ];
}


/* =========================================================
   PROCESS POST ACTIONS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST["csrf_token"] ?? "";

    if (
        !hash_equals(
            $_SESSION["backup_csrf_token"],
            $posted_token
        )
    ) {

        setBackupMessage(
            "error",
            "Security validation failed. Please refresh the page and try again."
        );

        header("Location: backup_restore.php");
        exit;
    }

    $action = $_POST["action"] ?? "";

    /* =====================================================
       DATABASE BACKUP
    ===================================================== */

    if ($action === "database_backup") {

        $result = createDatabaseBackup(
            $conn,
            $backup_dir,
            $database_name
        );

        if ($result["success"]) {

            logActivity(
                $conn,
                "Created database backup: " . $result["filename"]
            );

            setBackupMessage(
                "success",
                "Database backup created successfully."
            );

        } else {

            setBackupMessage(
                "error",
                $result["message"]
            );
        }

        header("Location: backup_restore.php");
        exit;
    }


    /* =====================================================
       APPLICATION FILE BACKUP
    ===================================================== */

    if ($action === "files_backup") {

        $result = createFilesBackup(
            __DIR__,
            $backup_dir
        );

        if ($result["success"]) {

            logActivity(
                $conn,
                "Created application files backup: " . $result["filename"]
            );

            setBackupMessage(
                "success",
                "Application files backup created successfully."
            );

        } else {

            setBackupMessage(
                "error",
                $result["message"]
            );
        }

        header("Location: backup_restore.php");
        exit;
    }


    /* =====================================================
       DATABASE RESTORE
    ===================================================== */

    if ($action === "database_restore") {

        $filename = basename(
            $_POST["filename"] ?? ""
        );

        if (
            $filename === "" ||
            strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== "sql"
        ) {

            setBackupMessage(
                "error",
                "Invalid database backup selected."
            );

            header("Location: backup_restore.php");
            exit;
        }

        $filepath = $backup_dir . DIRECTORY_SEPARATOR . $filename;

        $real_backup_dir = realpath($backup_dir);
        $real_file = realpath($filepath);

        if (
            $real_backup_dir === false ||
            $real_file === false ||
            strpos(
                str_replace("\\", "/", $real_file),
                rtrim(
                    str_replace("\\", "/", $real_backup_dir),
                    "/"
                ) . "/"
            ) !== 0
        ) {

            setBackupMessage(
                "error",
                "Invalid backup file path."
            );

            header("Location: backup_restore.php");
            exit;
        }

        /*
         * Create an automatic backup BEFORE restore.
         * This gives the admin a rollback point.
         */

        $pre_restore_backup = createDatabaseBackup(
            $conn,
            $backup_dir,
            $database_name,
            "pre_restore_" . $database_name . "_backup"
        );

        if (!$pre_restore_backup["success"]) {

            setBackupMessage(
                "error",
                "Restore cancelled because the automatic pre-restore backup could not be created."
            );

            header("Location: backup_restore.php");
            exit;
        }

        $restore_result = restoreDatabase(
            $conn,
            $real_file
        );

        if ($restore_result["success"]) {

            logActivity(
                $conn,
                "Restored database backup: " . $filename .
                " | Pre-restore backup: " .
                $pre_restore_backup["filename"]
            );

            setBackupMessage(
                "success",
                "Database restored successfully. A pre-restore backup was also created."
            );

        } else {

            logActivity(
                $conn,
                "Failed database restore: " . $filename
            );

            setBackupMessage(
                "error",
                $restore_result["message"]
            );
        }

        header("Location: backup_restore.php");
        exit;
    }


    /* =====================================================
       APPLICATION FILE RESTORE
    ===================================================== */

    if ($action === "files_restore") {

        $filename = basename(
            $_POST["filename"] ?? ""
        );

        if (
            $filename === "" ||
            strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== "zip"
        ) {

            setBackupMessage(
                "error",
                "Invalid application backup selected."
            );

            header("Location: backup_restore.php");
            exit;
        }

        $filepath =
            $backup_dir .
            DIRECTORY_SEPARATOR .
            $filename;

        $real_backup_dir = realpath($backup_dir);
        $real_file = realpath($filepath);

        if (
            $real_backup_dir === false ||
            $real_file === false ||
            strpos(
                str_replace("\\", "/", $real_file),
                rtrim(
                    str_replace("\\", "/", $real_backup_dir),
                    "/"
                ) . "/"
            ) !== 0
        ) {

            setBackupMessage(
                "error",
                "Invalid backup file path."
            );

            header("Location: backup_restore.php");
            exit;
        }

        /*
         * Create a current application backup first.
         */

        $pre_restore_files = createFilesBackup(
            __DIR__,
            $backup_dir
        );

        if (!$pre_restore_files["success"]) {

            setBackupMessage(
                "error",
                "Restore cancelled because the automatic pre-restore files backup could not be created."
            );

            header("Location: backup_restore.php");
            exit;
        }

        $restore_result = restoreFiles(
            __DIR__,
            $real_file
        );

        if ($restore_result["success"]) {

            logActivity(
                $conn,
                "Restored application files backup: " . $filename .
                " | Pre-restore backup: " .
                $pre_restore_files["filename"]
            );

            setBackupMessage(
                "success",
                "Application files restored successfully. A pre-restore files backup was also created."
            );

        } else {

            logActivity(
                $conn,
                "Failed application files restore: " . $filename
            );

            setBackupMessage(
                "error",
                $restore_result["message"]
            );
        }

        header("Location: backup_restore.php");
        exit;
    }
}


/* =========================================================
   GET MESSAGE
========================================================= */

$message = getBackupMessage();


/* =========================================================
   LIST BACKUPS
========================================================= */

$database_backups = [];
$file_backups = [];

if (is_dir($backup_dir)) {

    $files = scandir($backup_dir);

    if ($files !== false) {

        foreach ($files as $file) {

            if (
                $file === "." ||
                $file === ".." ||
                $file === ".htaccess"
            ) {
                continue;
            }

            $filepath =
                $backup_dir .
                DIRECTORY_SEPARATOR .
                $file;

            if (!is_file($filepath)) {
                continue;
            }

            $extension =
                strtolower(
                    pathinfo(
                        $file,
                        PATHINFO_EXTENSION
                    )
                );

            $file_info = [
                "name" => $file,
                "size" => formatFileSize(
                    filesize($filepath)
                ),
                "date" => date(
                    "Y-m-d H:i:s",
                    filemtime($filepath)
                )
            ];

            if ($extension === "sql") {
                $database_backups[] = $file_info;
            }

            if ($extension === "zip") {
                $file_backups[] = $file_info;
            }
        }
    }
}


/* Newest first */
usort(
    $database_backups,
    function ($a, $b) {
        return strcmp($b["date"], $a["date"]);
    }
);

usort(
    $file_backups,
    function ($a, $b) {
        return strcmp($b["date"], $a["date"]);
    }
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Backup & Restore - ISU SmartEnroll</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f7f4;
            color: #1f2937;
            min-height: 100vh;
        }

        /* =========================================
           HEADER
        ========================================= */

        .top-header {
            background: #ffffff;
            min-height: 105px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 45px;
            gap: 25px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .header-left img {
            width: 68px;
            height: 68px;
            object-fit: contain;
        }

        .header-left-text h2 {
            color: #166534;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .header-left-text p {
            color: #555;
            font-size: 13px;
        }

        .header-center {
            display: flex;
            justify-content: center;
            flex: 1;
        }

        .header-center img {
            width: 90px;
            height: 90px;
            object-fit: contain;
        }

        .header-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            text-align: right;
        }

        .header-right img {
            width: 62px;
            height: 62px;
            object-fit: contain;
        }

        .header-right-text {
            font-size: 11px;
            line-height: 1.5;
            color: #555;
        }

        .header-right-text strong {
            display: block;
            color: #166534;
            font-size: 13px;
        }

        /* =========================================
           NAVIGATION
        ========================================= */

        .navbar {
            background: #166534;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 17px 20px;
            font-size: 14px;
            transition: 0.2s;
        }

        .navbar a:hover,
        .navbar a.active {
            background: #14532d;
        }

        /* =========================================
           HERO
        ========================================= */

        .hero {
            min-height: 230px;
            position: relative;
            background:
                linear-gradient(
                    rgba(10, 65, 35, 0.80),
                    rgba(10, 65, 35, 0.80)
                ),
                url("assets/background.jpg");

            background-size: cover;
            background-position: center;

            display: flex;
            align-items: center;
            justify-content: center;

            text-align: center;
            color: white;

            padding: 50px 20px;
        }

        .hero-content {
            max-width: 850px;
        }

        .hero h1 {
            font-size: 38px;
            margin-bottom: 12px;
        }

        .hero p {
            font-size: 16px;
            opacity: 0.95;
        }

        /* =========================================
           MAIN
        ========================================= */

        .container {
            width: min(1180px, 92%);
            margin: 40px auto 60px;
        }

        /* =========================================
           MESSAGE
        ========================================= */

        .message {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 600;
        }

        .message.success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .message.error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }

        /* =========================================
           ACTION CARDS
        ========================================= */

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 35px;
        }

        .action-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 28px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.07);
            border: 1px solid #e5e7eb;
        }

        .action-card h2 {
            color: #166534;
            margin-bottom: 10px;
            font-size: 21px;
        }

        .action-card p {
            color: #6b7280;
            line-height: 1.6;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 12px 18px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: 0.2s;
        }

        .btn-green {
            background: #166534;
            color: white;
        }

        .btn-green:hover {
            background: #14532d;
        }

        .btn-blue {
            background: #2563eb;
            color: white;
        }

        .btn-blue:hover {
            background: #1d4ed8;
        }

        .btn-red {
            background: #dc2626;
            color: white;
        }

        .btn-red:hover {
            background: #b91c1c;
        }

        .btn-small {
            padding: 8px 12px;
            font-size: 12px;
        }

        /* =========================================
           SECTION CARD
        ========================================= */

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.07);
            border: 1px solid #e5e7eb;
            margin-bottom: 30px;
        }

        .section-card h2 {
            color: #166534;
            margin-bottom: 18px;
            font-size: 21px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th {
            background: #166534;
            color: white;
            text-align: left;
            padding: 13px;
            font-size: 13px;
        }

        td {
            padding: 13px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            vertical-align: middle;
        }

        tr:hover td {
            background: #f9fafb;
        }

        .filename {
            font-weight: 600;
            color: #374151;
            word-break: break-all;
        }

        .empty {
            text-align: center;
            color: #6b7280;
            padding: 30px;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .action-buttons form {
            margin: 0;
        }

        /* =========================================
           WARNING
        ========================================= */

        .warning {
            background: #fff7ed;
            border: 1px solid #fdba74;
            border-radius: 12px;
            padding: 20px;
            color: #9a3412;
            line-height: 1.7;
            font-size: 14px;
        }

        .warning strong {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .warning ul {
            padding-left: 20px;
        }

        /* =========================================
           FOOTER
        ========================================= */

        footer {
            background: #14532d;
            color: white;
            text-align: center;
            padding: 25px 20px;
            font-size: 13px;
        }

        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 850px) {

            .top-header {
                padding: 15px 20px;
            }

            .header-right-text {
                display: none;
            }

            .header-left-text p {
                display: none;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 30px;
            }
        }

        @media (max-width: 650px) {

            .top-header {
                min-height: auto;
                flex-wrap: wrap;
                justify-content: center;
            }

            .header-left,
            .header-center,
            .header-right {
                flex: none;
            }

            .header-left {
                justify-content: center;
            }

            .header-right {
                justify-content: center;
            }

            .header-left img {
                width: 55px;
                height: 55px;
            }

            .header-center img {
                width: 75px;
                height: 75px;
            }

            .header-right img {
                width: 50px;
                height: 50px;
            }

            .navbar a {
                padding: 13px 10px;
                font-size: 12px;
            }

            .container {
                width: 94%;
                margin-top: 25px;
            }

            .section-card,
            .action-card {
                padding: 20px;
            }

            .hero {
                min-height: 190px;
            }

            .hero h1 {
                font-size: 26px;
            }
        }

    </style>

</head>

<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="top-header">

    <div class="header-left">

        <img
            src="assets/isu-logo.png"
            alt="Isabela State University"
        >

        <div class="header-left-text">

            <h2>Isabela State University</h2>

            <p>Cauayan Campus</p>

        </div>

    </div>


    <div class="header-center">

        <img
            src="assets/smart-enroll-logo.png?v=8"
            alt="SmartEnroll"
        >

    </div>


    <div class="header-right">

        <div class="header-right-text">

            <strong>College of Computing Studies</strong>

            Information and Communication Technology<br>

            Isabela State University

        </div>

        <img
            src="assets/ccsict-logo.png"
            alt="CCSICT"
        >

    </div>

</header>


<!-- =====================================================
     NAVIGATION
===================================================== -->

<nav class="navbar">

    <a href="admin_dashboard.php">
        Dashboard
    </a>

    <a href="users.php">
        Users
    </a>

    <a href="student.php">
        Students
    </a>

    <a href="programs.php">
        Programs
    </a>

    <a href="admin_requirements.php">
        Requirements
    </a>

    <a href="reports.php">
        Reports
    </a>

    <a href="activity_logs.php">
        Activity Logs
    </a>

    <a
        href="backup_restore.php"
        class="active"
    >
        Backup & Restore
    </a>

</nav>


<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">

    <div class="hero-content">

        <h1>Backup & Restore</h1>

        <p>
            Securely back up and restore the ISU SmartEnroll
            database and application files.
        </p>

    </div>

</section>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="container">


<?php if ($message): ?>

    <div
        class="message <?php echo htmlspecialchars($message["type"]); ?>"
    >

        <?php echo htmlspecialchars($message["message"]); ?>

    </div>

<?php endif; ?>


<!-- =====================================================
     BACKUP ACTIONS
===================================================== -->

<div class="action-grid">


    <!-- DATABASE BACKUP -->

    <div class="action-card">

        <h2>Database Backup</h2>

        <p>
            Create a complete SQL backup of the
            <strong>isu_smartenroll</strong> database,
            including table structures and stored records.
        </p>

        <form
            method="POST"
            onsubmit="return confirm('Create a database backup now?');"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($csrf_token); ?>"
            >

            <input
                type="hidden"
                name="action"
                value="database_backup"
            >

            <button
                type="submit"
                class="btn btn-green"
            >
                Create Database Backup
            </button>

        </form>

    </div>


    <!-- FILE BACKUP -->

    <div class="action-card">

        <h2>Application Files Backup</h2>

        <p>
            Create a ZIP backup of the SmartEnroll
            application files. Sensitive database credentials
            are excluded from the backup.
        </p>

        <form
            method="POST"
            onsubmit="return confirm('Create an application files backup now?');"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($csrf_token); ?>"
            >

            <input
                type="hidden"
                name="action"
                value="files_backup"
            >

            <button
                type="submit"
                class="btn btn-green"
            >
                Create Files ZIP Backup
            </button>

        </form>

    </div>

</div>


<!-- =====================================================
     DATABASE BACKUPS
===================================================== -->

<section class="section-card">

    <h2>Database Backups</h2>

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>
                        Backup File
                    </th>

                    <th>
                        Size
                    </th>

                    <th>
                        Date Created
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php if (empty($database_backups)): ?>

                <tr>

                    <td
                        colspan="4"
                        class="empty"
                    >
                        No database backups available.
                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($database_backups as $backup): ?>

                    <tr>

                        <td class="filename">

                            <?php
                            echo htmlspecialchars(
                                $backup["name"]
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $backup["size"]
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $backup["date"]
                            );
                            ?>

                        </td>

                        <td>

                            <div class="action-buttons">

                                <a
                                    href="download_backup.php?type=db&file=<?php echo urlencode($backup["name"]); ?>"
                                    class="btn btn-blue btn-small"
                                >
                                    Download
                                </a>


                                <form
                                    method="POST"
                                    onsubmit="return confirm('WARNING: Restoring this database will replace the current database data. A pre-restore backup will be created automatically. Continue?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars($csrf_token); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="database_restore"
                                    >

                                    <input
                                        type="hidden"
                                        name="filename"
                                        value="<?php echo htmlspecialchars($backup["name"]); ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-red btn-small"
                                    >
                                        Restore
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- =====================================================
     APPLICATION FILE BACKUPS
===================================================== -->

<section class="section-card">

    <h2>Application File Backups</h2>

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>
                        Backup File
                    </th>

                    <th>
                        Size
                    </th>

                    <th>
                        Date Created
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php if (empty($file_backups)): ?>

                <tr>

                    <td
                        colspan="4"
                        class="empty"
                    >
                        No application file backups available.
                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($file_backups as $backup): ?>

                    <tr>

                        <td class="filename">

                            <?php
                            echo htmlspecialchars(
                                $backup["name"]
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $backup["size"]
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $backup["date"]
                            );
                            ?>

                        </td>

                        <td>

                            <div class="action-buttons">

                                <a
                                    href="download_backup.php?type=files&file=<?php echo urlencode($backup["name"]); ?>"
                                    class="btn btn-blue btn-small"
                                >
                                    Download
                                </a>


                                <form
                                    method="POST"
                                    onsubmit="return confirm('WARNING: Restoring application files can overwrite current project files. A pre-restore backup will be created automatically. Continue?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars($csrf_token); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="files_restore"
                                    >

                                    <input
                                        type="hidden"
                                        name="filename"
                                        value="<?php echo htmlspecialchars($backup["name"]); ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-red btn-small"
                                    >
                                        Restore
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- =====================================================
     WARNING
===================================================== -->

<section class="section-card">

    <div class="warning">

        <strong>
            ⚠ Backup & Restore Warning
        </strong>

        <ul>

            <li>
                Create a fresh backup before performing major database changes.
            </li>

            <li>
                Database restore replaces the current database contents.
            </li>

            <li>
                Application file restore may overwrite existing project files.
            </li>

            <li>
                A pre-restore backup is automatically created before a restore operation.
            </li>

            <li>
                Database credentials from <strong>db.php</strong> are excluded from application ZIP backups.
            </li>

            <li>
                Backup files should be kept in a secure location and should not be publicly shared.
            </li>

        </ul>

    </div>

</section>


</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <p>
        &copy; <?php echo date("Y"); ?>
        ISU SmartEnroll |
        Isabela State University - Cauayan Campus
    </p>

</footer>


</body>

</html>