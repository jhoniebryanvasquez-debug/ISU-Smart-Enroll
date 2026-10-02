<?php

session_start();

require_once "db.php";

/* =========================================================
   ADMIN SECURITY
========================================================= */

if (!isset($_SESSION["user_id"])) {
    http_response_code(403);
    exit("Access denied.");
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    http_response_code(403);
    exit("Access denied.");
}


/* =========================================================
   BACKUP DIRECTORY
========================================================= */

$backup_dir = __DIR__ . DIRECTORY_SEPARATOR . "backups";

if (!is_dir($backup_dir)) {
    http_response_code(404);
    exit("Backup directory not found.");
}


/* =========================================================
   GET PARAMETERS
========================================================= */

$type = $_GET["type"] ?? "";
$filename = $_GET["file"] ?? "";


/* =========================================================
   VALIDATE TYPE
========================================================= */

$allowed_types = [
    "db",
    "files"
];

if (!in_array($type, $allowed_types, true)) {
    http_response_code(400);
    exit("Invalid backup type.");
}


/* =========================================================
   VALIDATE FILENAME
========================================================= */

$filename = basename($filename);

if ($filename === "") {
    http_response_code(400);
    exit("Invalid backup filename.");
}


/* =========================================================
   VALIDATE EXTENSION
========================================================= */

$extension = strtolower(
    pathinfo($filename, PATHINFO_EXTENSION)
);

if ($type === "db" && $extension !== "sql") {
    http_response_code(400);
    exit("Invalid database backup file.");
}

if ($type === "files" && $extension !== "zip") {
    http_response_code(400);
    exit("Invalid application backup file.");
}


/* =========================================================
   BUILD FILE PATH
========================================================= */

$file_path =
    $backup_dir .
    DIRECTORY_SEPARATOR .
    $filename;


/* =========================================================
   SECURITY CHECK
========================================================= */

$real_backup_dir = realpath($backup_dir);
$real_file = realpath($file_path);

if (
    $real_backup_dir === false ||
    $real_file === false
) {
    http_response_code(404);
    exit("Backup file not found.");
}

$real_backup_dir = rtrim(
    str_replace("\\", "/", $real_backup_dir),
    "/"
);

$real_file = str_replace(
    "\\",
    "/",
    $real_file
);


/*
 * Make sure the requested file is actually
 * inside the backups directory.
 */

if (
    strpos(
        $real_file,
        $real_backup_dir . "/"
    ) !== 0
) {
    http_response_code(403);
    exit("Invalid backup location.");
}


/* =========================================================
   FILE CHECK
========================================================= */

if (!is_file($real_file) || !is_readable($real_file)) {
    http_response_code(404);
    exit("Backup file is unavailable.");
}


/* =========================================================
   CONTENT TYPE
========================================================= */

if ($extension === "sql") {

    $content_type = "application/sql";

} else {

    $content_type = "application/zip";
}


/* =========================================================
   DOWNLOAD HEADERS
========================================================= */

$file_size = filesize($real_file);

if ($file_size === false) {
    http_response_code(500);
    exit("Unable to determine file size.");
}


/*
 * Clear any output that may have been generated
 * before the download.
 */

while (ob_get_level() > 0) {
    ob_end_clean();
}


header(
    "Content-Type: " . $content_type
);

header(
    "Content-Disposition: attachment; filename=\"" .
    basename($filename) .
    "\""
);

header(
    "Content-Length: " . $file_size
);

header(
    "Content-Transfer-Encoding: binary"
);

header(
    "Cache-Control: private, no-store, no-cache, must-revalidate"
);

header(
    "Pragma: no-cache"
);

header(
    "Expires: 0"
);


/* =========================================================
   SEND FILE
========================================================= */

readfile($real_file);

exit;
?>