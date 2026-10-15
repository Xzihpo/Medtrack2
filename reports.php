<?php

session_start();

require_once "Database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {

    header("Location: index.php");
    exit();

}


/* =========================================================
   ADMIN ONLY
========================================================= */

if ($_SESSION["role"] !== "admin") {

    header("Location: index.php");
    exit();

}


/* =========================================================
   EDIT / DELETE REPORTS
========================================================= */

$message = "";
$error = "";

if (isset($_POST["update_report"])) {

    $report_id = intval($_POST["report_id"] ?? 0);

    $symptoms = trim($_POST["symptoms"] ?? "");
    $diagnosis = trim($_POST["diagnosis"] ?? "");
    $result_value = trim($_POST["result"] ?? "");
    $prescription = trim($_POST["prescription"] ?? "");
    $medication_instruction = trim(
        $_POST["medication_instruction"] ?? ""
    );

    if ($report_id <= 0) {

        $error = "Invalid Report ID.";

    } elseif ($symptoms === "") {

        $error = "Symptoms / Reason For Visit Is Required.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE consultations
             SET
                symptoms = ?,
                diagnosis = ?,
                result = ?,
                prescription = ?,
                medication_instruction = ?
             WHERE id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "sssssi",
                $symptoms,
                $diagnosis,
                $result_value,
                $prescription,
                $medication_instruction,
                $report_id
            );

            if ($stmt->execute()) {

                $message = "Report Successfully Updated.";

            } else {

                $error = "Unable To Update Report.";

            }

            $stmt->close();

        } else {

            $error = "Unable To Prepare Report Update.";

        }

    }

}


if (isset($_POST["delete_report"])) {

    $report_id = intval($_POST["report_id"] ?? 0);

    if ($report_id <= 0) {

        $error = "Invalid Report ID.";

    } else {

        $stmt = $conn->prepare(
            "DELETE FROM consultations
             WHERE id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $report_id
            );

            if ($stmt->execute()) {

                $message = "Report Successfully Deleted.";

            } else {

                $error = "Unable To Delete Report.";

            }

            $stmt->close();

        } else {

            $error = "Unable To Prepare Report Delete.";

        }

    }

}


/* =========================================================
   GET DATABASE COLUMN NAMES
========================================================= */

function getColumns($conn, $table)
{

    $columns = [];

    $result = $conn->query(
        "SHOW COLUMNS FROM `$table`"
    );

    if ($result) {

        while ($row = $result->fetch_assoc()) {

            $columns[] = $row["Field"];

        }

    }

    return $columns;

}


$consultation_columns = getColumns(
    $conn,
    "consultations"
);

$student_columns = getColumns(
    $conn,
    "students"
);

$medicine_columns = getColumns(
    $conn,
    "medicines"
);


/* =========================================================
   FIND AVAILABLE DATE COLUMN
========================================================= */

$date_column = null;

$date_options = [

    "created_at",
    "consultation_date",
    "date",
    "visit_date",
    "consultation_time",
    "visit_time"

];


foreach ($date_options as $column) {

    if (
        in_array(
            $column,
            $consultation_columns
        )
    ) {

        $date_column = $column;

        break;

    }

}


/* =========================================================
   FIND AVAILABLE NURSE COLUMN
========================================================= */

$nurse_column = null;

$nurse_options = [

    "nurse_id",
    "nurse",
    "nurse_name",
    "user_id",
    "staff_id"

];


foreach ($nurse_options as $column) {

    if (
        in_array(
            $column,
            $consultation_columns
        )
    ) {

        $nurse_column = $column;

        break;

    }

}


/* =========================================================
   SEARCH
========================================================= */

$search = trim(
    $_GET["search"] ?? ""
);


/* =========================================================
   STATISTICS
========================================================= */

$total_students = 0;

$total_consultations = 0;

$total_medicines = 0;

$low_stock = 0;


/* =========================================================
   TOTAL STUDENTS
========================================================= */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM students"
);

if ($result) {

    $row = $result->fetch_assoc();

    $total_students =
        (int)$row["total"];

}


/* =========================================================
   TOTAL CONSULTATIONS
========================================================= */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM consultations"
);

if ($result) {

    $row = $result->fetch_assoc();

    $total_consultations =
        (int)$row["total"];

}


/* =========================================================
   TOTAL MEDICINES
========================================================= */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM medicines"
);

if ($result) {

    $row = $result->fetch_assoc();

    $total_medicines =
        (int)$row["total"];

}


/* =========================================================
   LOW STOCK
========================================================= */

if (
    in_array(
        "stock_quantity",
        $medicine_columns
    )
    &&
    in_array(
        "minimum_stock",
        $medicine_columns
    )
) {

    $result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM medicines
         WHERE stock_quantity <= minimum_stock"
    );

    if ($result) {

        $row =
            $result->fetch_assoc();

        $low_stock =
            (int)$row["total"];

    }

}


/* =========================================================
   GET CONSULTATIONS
========================================================= */

$consultations = [];


$sql = "

    SELECT

        c.*,

        s.full_name

    FROM consultations c

    LEFT JOIN students s

        ON c.student_id = s.student_id

";


/* =========================================================
   SEARCH BY STUDENT ID OR NAME
========================================================= */

if ($search !== "") {

    $sql .= "

        WHERE c.student_id LIKE ?

        OR s.full_name LIKE ?

    ";

}


/* =========================================================
   ORDER BY DATE
========================================================= */

if ($date_column !== null) {

    $sql .=
        " ORDER BY c.`$date_column` DESC";

} else {

    $sql .=
        " ORDER BY c.id DESC";

}


/* =========================================================
   LIMIT
========================================================= */

$sql .= " LIMIT 100";


$stmt = $conn->prepare($sql);


if ($stmt) {

    if ($search !== "") {

        $search_value =
            "%" . $search . "%";

        $stmt->bind_param(
            "ss",
            $search_value,
            $search_value
        );

    }

    $stmt->execute();

    $result =
        $stmt->get_result();

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $consultations[] =
            $row;

    }

    $stmt->close();

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
    MEDTRACK - Reports
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

}


/* =========================================================
   IMPORTANT:
   SAME FONT AS MEDTRACK DASHBOARD
========================================================= */

html,
body,
button,
input,
textarea,
select {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


html {

    scroll-behavior: smooth;

}


body {

    min-height: 100vh;

    color: #1e293b;

    background:

        radial-gradient(
            circle at 10% 10%,
            rgba(33,150,243,0.16),
            transparent 28%
        ),

        radial-gradient(
            circle at 90% 20%,
            rgba(76,175,80,0.15),
            transparent 30%
        ),

        radial-gradient(
            circle at 50% 100%,
            rgba(156,39,176,0.10),
            transparent 30%
        ),

        #f4f7fb;

    overflow-x: hidden;

}


/* =========================================================
   FLOATING BACKGROUND SYMBOLS
========================================================= */

.floating-symbol {

    position: fixed;

    pointer-events: none;

    user-select: none;

    z-index: 0;

    opacity: 0.08;

    animation:
        floatSymbol
        linear
        infinite;

}


.symbol-1 {

    top: 13%;

    left: 3%;

    font-size: 65px;

    animation-duration: 15s;

}


.symbol-2 {

    top: 45%;

    right: 4%;

    font-size: 75px;

    animation-duration: 18s;

    animation-delay: -4s;

}


.symbol-3 {

    bottom: 10%;

    left: 9%;

    font-size: 55px;

    animation-duration: 17s;

    animation-delay: -7s;

}


.symbol-4 {

    top: 25%;

    right: 20%;

    font-size: 42px;

    animation-duration: 13s;

    animation-delay: -3s;

}


.symbol-5 {

    bottom: 25%;

    right: 25%;

    font-size: 60px;

    animation-duration: 20s;

    animation-delay: -8s;

}


@keyframes floatSymbol {

    0% {

        transform:
            translateY(0)
            rotate(0deg);

    }

    25% {

        transform:
            translateY(-25px)
            rotate(8deg);

    }

    50% {

        transform:
            translateY(0)
            rotate(-8deg);

    }

    75% {

        transform:
            translateY(25px)
            rotate(5deg);

    }

    100% {

        transform:
            translateY(0)
            rotate(0deg);

    }

}


/* =========================================================
   HEADER
========================================================= */

.header {

    position: relative;

    z-index: 1000;

    min-height: 82px;

    padding: 0 48px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    color: white;

    background:

        linear-gradient(
            135deg,
            #1565c0 0%,
            #1976d2 38%,
            #00897b 72%,
            #2e7d32 100%
        );

    box-shadow:

        0 8px 30px
        rgba(21,101,192,0.25);

    overflow: visible;

}


.header::before {

    content: "";

    position: absolute;

    width: 250px;

    height: 250px;

    border-radius: 50%;

    background:
        rgba(255,255,255,0.08);

    pointer-events: none;

    z-index: 0;

    top: -170px;

    right: 10%;

}


.header::after {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    background:
        rgba(255,255,255,0.06);

    pointer-events: none;

    z-index: 0;

    bottom: -140px;

    left: 35%;

}


/* =========================================================
   BRAND
========================================================= */

.brand-area {

    position: relative;

    z-index: 2;

}


.logo-row {

    display: flex;

    align-items: center;

    gap: 13px;

}


.logo-icon {

    width: 45px;

    height: 45px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    background:
        rgba(255,255,255,0.18);

    border:
        1px solid
        rgba(255,255,255,0.25);

    box-shadow:
        0 5px 15px
        rgba(0,0,0,0.12);

}


.logo {

    font-size: 28px;

    font-weight: 800;

    letter-spacing: 3px;

}


.system-name {

    font-size: 12px;

    margin-top: 3px;

    margin-left: 58px;

    opacity: 0.88;

    letter-spacing: 0.5px;

}


/* =========================================================
   USER AREA
========================================================= */

.user-area {

    position: relative;

    z-index: 2;

    display: flex;

    align-items: center;

    gap: 12px;

}


.user-info {

    text-align: right;

}


.user-role {

    font-size: 11px;

    opacity: 0.8;

    text-transform: uppercase;

    letter-spacing: 1px;

}


.user-name {

    margin-top: 3px;

    font-size: 15px;

    font-weight: 700;

}


.user-icon {

    width: 45px;

    height: 45px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    background:
        rgba(255,255,255,0.18);

    border:
        1px solid
        rgba(255,255,255,0.3);

}


/* =========================================================
   USER MENU / DROPDOWN
========================================================= */

.user-menu {

    position: relative;

    z-index: 2000;

}


.user-menu-button {

    border: none;

    background:
        rgba(255,255,255,0.10);

    color: white;

    cursor: pointer;

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        6px 8px;

    border-radius: 13px;

    transition:
        0.2s ease;

}


.user-menu-button:hover {

    background:
        rgba(255,255,255,0.17);

}


.user-menu-button:focus {

    outline: none;

    background:
        rgba(255,255,255,0.17);

}


.user-info {

    text-align: right;

}


.user-role {

    font-size: 11px;

    color:
        rgba(255,255,255,0.78);

    margin-bottom: 2px;

    text-transform: uppercase;

    letter-spacing: 0.5px;

}


.user-name {

    font-size: 14px;

    font-weight: 700;

    text-transform: capitalize;

}


.user-icon {

    width: 45px;

    height: 45px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    background:
        rgba(255,255,255,0.18);

    border:
        1px solid
        rgba(255,255,255,0.30);

    box-shadow:
        inset 0 0 15px
        rgba(255,255,255,0.08);

}


.user-dropdown {

    position: absolute;

    right: 0;

    top:
        calc(100% + 9px);

    width: 172px;

    padding:
        14px;

    border-radius: 16px;

    background:
        rgba(255,255,255,0.98);

    border:
        1px solid
        rgba(255,255,255,0.95);

    box-shadow:
        0 14px 35px
        rgba(20,80,110,0.22);

    z-index: 100000;

    opacity: 0;

    visibility: hidden;

    transform:
        translateY(-6px);

    transition:
        opacity 0.18s ease,
        transform 0.18s ease,
        visibility 0.18s ease;

}


.user-dropdown.show {

    opacity: 1;

    visibility: visible;

    transform:
        translateY(0);

}


.dropdown-signed-label {

    color: #78909c;

    font-size: 10px;

    font-weight: 700;

    margin-bottom: 3px;

}


.dropdown-signed-role {

    color: #263238;

    font-size: 13px;

    font-weight: 800;

    margin-bottom: 11px;

}


.dropdown-divider {

    height: 1px;

    background: #edf1f5;

    margin:
        8px 0;

}


.dropdown-link {

    display: flex;

    align-items: center;

    gap: 8px;

    width: 100%;

    padding:
        9px 8px;

    border-radius: 8px;

    text-decoration: none;

    color: #1976d2;

    font-size: 12px;

    font-weight: 700;

    transition:
        0.18s ease;

}


.dropdown-link:hover {

    background: #eef6ff;

}


.dropdown-logout {

    display: flex;

    align-items: center;

    gap: 8px;

    width: 100%;

    padding:
        9px 8px;

    border-radius: 8px;

    text-decoration: none;

    color: #c62828;

    font-size: 12px;

    font-weight: 700;

    transition:
        0.18s ease;

}


.dropdown-logout:hover {

    background: #fff0f1;

}


/* =========================================================
   MAIN
========================================================= */

.container {

    position: relative;

    z-index: 100;

    width: 92%;

    max-width: 1250px;

    margin: 42px auto 60px;

}


.container button,
.container a,
.container input,
.container select,
.container textarea {

    position: relative;

    z-index: 5;

}


/* Keep dashboard controls above decorative background */

.container .search-button,
.container .clear-button,
.container .print-button,
.container .edit-report-button,
.container .delete-report-button,
.container .save-report-button,
.container .cancel-edit-button {

    position: relative;

    z-index: 20;

}


/* =========================================================
   ALERTS
========================================================= */

.alert {

    padding:
        14px 17px;

    border-radius: 12px;

    margin-bottom: 22px;

    font-size: 13px;

    font-weight: 700;

}


.alert-success {

    background: #e8f5e9;

    color: #2e7d32;

    border:
        1px solid #c8e6c9;

}


.alert-error {

    background: #ffebee;

    color: #c62828;

    border:
        1px solid #ffcdd2;

}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {

    margin-bottom: 28px;

}


.page-title h1 {

    font-size: 34px;

    font-weight: 800;

    letter-spacing: -0.5px;

    color: #172033;

}


.title-highlight {

    display: inline-block;

    width: 55px;

    height: 5px;

    border-radius: 10px;

    margin-top: 10px;

    background:

        linear-gradient(
            90deg,
            #1976d2,
            #00a896,
            #2e7d32
        );

}


.page-title p {

    margin-top: 10px;

    color: #718096;

    font-size: 15px;

}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 28px;

}


.stat-card {

    position: relative;

    min-height: 145px;

    padding: 23px;

    border-radius: 18px;

    color: white;

    overflow: hidden;

    box-shadow:

        0 12px 28px
        rgba(0,0,0,0.10);

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;

}


.stat-card:hover {

    transform:
        translateY(-6px);

    box-shadow:

        0 18px 35px
        rgba(0,0,0,0.16);

}


.stat-card::after {

    content: "";

    position: absolute;

    width: 110px;

    height: 110px;

    border-radius: 50%;

    right: -35px;

    bottom: -45px;

    background:
        rgba(255,255,255,0.13);

}


.stat-blue {

    background:

        linear-gradient(
            135deg,
            #1565c0,
            #42a5f5
        );

}


.stat-green {

    background:

        linear-gradient(
            135deg,
            #2e7d32,
            #66bb6a
        );

}


.stat-purple {

    background:

        linear-gradient(
            135deg,
            #6a1b9a,
            #ab47bc
        );

}


.stat-red {

    background:

        linear-gradient(
            135deg,
            #c62828,
            #ef5350
        );

}


.stat-icon {

    position: absolute;

    top: 20px;

    right: 20px;

    width: 45px;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;

    font-size: 23px;

    background:
        rgba(255,255,255,0.18);

}


.stat-title {

    font-size: 13px;

    opacity: 0.9;

    margin-bottom: 8px;

}


.stat-number {

    font-size: 34px;

    font-weight: 800;

    line-height: 1;

}


.stat-subtitle {

    margin-top: 14px;

    font-size: 11px;

    opacity: 0.78;

}


/* =========================================================
   GENERAL CARD
========================================================= */

.card {

    position: relative;

    background:
        rgba(255,255,255,0.94);

    border:
        1px solid
        rgba(255,255,255,0.8);

    border-radius: 20px;

    padding: 30px;

    margin-bottom: 26px;

    box-shadow:

        0 12px 35px
        rgba(31,41,55,0.08);

    backdrop-filter:
        blur(12px);

}


.card-title-row {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 22px;

}


.card-title {

    font-size: 21px;

    font-weight: 800;

    color: #1e293b;

}


.card-description {

    color: #8792a2;

    font-size: 13px;

    margin-top: 5px;

}


/* =========================================================
   SEARCH
========================================================= */

.search-card {

    border-top:
        4px solid #1976d2;

}


.search-row {

    display: flex;

    gap: 12px;

}


.search-input-wrapper {

    position: relative;

    flex: 1;

}


.search-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    font-size: 17px;

    color: #94a3b8;

}


.search-row input {

    width: 100%;

    padding:
        15px
        15px
        15px
        45px;

    border:
        1px solid #dbe2ea;

    border-radius: 11px;

    font-size: 14px;

    color: #334155;

    background: #f8fafc;

    outline: none;

    transition:
        0.2s ease;

}


.search-row input:focus {

    border-color: #1976d2;

    background: white;

    box-shadow:

        0 0 0 4px
        rgba(25,118,210,0.09);

}


.search-button {

    border: none;

    padding:
        0 25px;

    border-radius: 11px;

    background:

        linear-gradient(
            135deg,
            #1976d2,
            #1565c0
        );

    color: white;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    box-shadow:

        0 6px 15px
        rgba(25,118,210,0.22);

    transition:
        0.2s ease;

}


.search-button:hover {

    transform:
        translateY(-2px);

}


.clear-button {

    display: flex;

    align-items: center;

    justify-content: center;

    padding:
        0 20px;

    background: #f1f5f9;

    color: #475569;

    text-decoration: none;

    border-radius: 11px;

    font-size: 14px;

    font-weight: 700;

    transition:
        0.2s ease;

}


.clear-button:hover {

    background: #e2e8f0;

    transform:
        translateY(-2px);

}


/* =========================================================
   REPORT HEADER
========================================================= */

.report-card {

    border-top:
        4px solid #2e7d32;

}


.report-heading {

    display: flex;

    align-items: center;

    gap: 12px;

}


.report-heading-icon {

    width: 44px;

    height: 44px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    font-size: 21px;

    color: #2e7d32;

    background:
        #e8f5e9;

}


.report-actions {

    display: flex;

    align-items: center;

    gap: 10px;

}


.record-count {

    padding:
        7px
        12px;

    border-radius: 20px;

    background: #eef6ff;

    color: #1976d2;

    font-size: 12px;

    font-weight: 700;

}


@media (max-width: 500px) {

    .user-dropdown {

        right: -5px;

        width: 165px;

    }

}


/* =========================================================
   PRINT BUTTON
========================================================= */

.print-button {

    border: none;

    padding:
        11px
        17px;

    border-radius: 10px;

    background:

        linear-gradient(
            135deg,
            #2e7d32,
            #43a047
        );

    color: white;

    font-weight: 700;

    font-size: 13px;

    cursor: pointer;

    box-shadow:

        0 5px 14px
        rgba(46,125,50,0.20);

    transition:
        0.2s ease;

}


.print-button:hover {

    transform:
        translateY(-2px);

}


/* =========================================================
   CONSULTATION RECORD
========================================================= */

.report-record {

    position: relative;

    background:
        linear-gradient(
            135deg,
            #ffffff,
            #f9fbfd
        );

    border:
        1px solid #e4e9ef;

    border-left:
        5px solid #1976d2;

    border-radius: 15px;

    padding: 22px;

    margin-bottom: 18px;

    box-shadow:

        0 5px 17px
        rgba(0,0,0,0.045);

    transition:
        transform 0.22s ease,
        box-shadow 0.22s ease;

}


.report-record:hover {

    transform:
        translateY(-3px);

    box-shadow:

        0 10px 25px
        rgba(25,118,210,0.10);

}


.report-record::before {

    content: "";

    position: absolute;

    top: 0;

    left: 25px;

    width: 80px;

    height: 3px;

    border-radius:
        0 0 8px 8px;

    background:

        linear-gradient(
            90deg,
            #1976d2,
            #00a896
        );

}


/* =========================================================
   RECORD HEADER
========================================================= */

.record-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    flex-wrap: wrap;

    padding-bottom: 15px;

    margin-bottom: 18px;

    border-bottom:
        1px solid #edf0f4;

}


.student-section {

    display: flex;

    align-items: center;

    gap: 13px;

}


.student-avatar {

    width: 46px;

    height: 46px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: white;

    font-size: 20px;

    font-weight: 800;

    background:

        linear-gradient(
            135deg,
            #1976d2,
            #26a69a
        );

    box-shadow:

        0 5px 13px
        rgba(25,118,210,0.20);

}


.student-name {

    font-size: 18px;

    font-weight: 800;

    color: #1976d2;

}


.student-id {

    margin-top: 3px;

    font-size: 12px;

    color: #7b8794;

}


.record-date {

    display: flex;

    align-items: center;

    gap: 7px;

    padding:
        8px
        12px;

    border-radius: 9px;

    background: #f1f5f9;

    color: #64748b;

    font-size: 12px;

    font-weight: 700;

}


/* =========================================================
   RECORD GRID
========================================================= */

.record-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 13px;

}


.record-item {

    padding: 15px;

    border-radius: 11px;

    background: #f8fafc;

    border:
        1px solid #edf1f5;

    transition:
        background 0.2s ease;

}


.record-item:hover {

    background: #f1f7fd;

}


.record-label {

    display: flex;

    align-items: center;

    gap: 6px;

    font-size: 11px;

    color: #718096;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.45px;

    margin-bottom: 7px;

}


.record-value {

    font-size: 14px;

    line-height: 1.65;

    color: #334155;

    word-wrap: break-word;

}


/* =========================================================
   RECORD COLORS
========================================================= */

.record-item:nth-child(1) {

    border-left:
        3px solid #42a5f5;

}


.record-item:nth-child(2) {

    border-left:
        3px solid #ab47bc;

}


.record-item:nth-child(3) {

    border-left:
        3px solid #26a69a;

}


.record-item:nth-child(4) {

    border-left:
        3px solid #ffa726;

}


.record-item:nth-child(5) {

    border-left:
        3px solid #66bb6a;

}


.record-item:nth-child(6) {

    border-left:
        3px solid #ef5350;

}


/* =========================================================
   REPORT ACTIONS
========================================================= */

.record-manage-actions {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-top: 18px;

    padding-top: 16px;

    border-top:
        1px solid #edf0f4;

}


.edit-report-button,
.delete-report-button {

    border: none;

    border-radius: 9px;

    padding: 9px 14px;

    font-size: 12px;

    font-weight: 800;

    cursor: pointer;

    text-decoration: none;

    transition:
        0.2s ease;

}


.edit-report-button {

    background: #eaf4ff;

    color: #1976d2;

}


.edit-report-button:hover {

    background: #d8ebff;

    transform:
        translateY(-1px);

}


.delete-report-button {

    background: #fff0f1;

    color: #c62828;

}


.delete-report-button:hover {

    background: #ffdfe3;

    transform:
        translateY(-1px);

}


/* =========================================================
   EDIT REPORT FORM
========================================================= */

.edit-report-form {

    display: none;

    margin-top: 18px;

    padding: 20px;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #f8fbff,
            #f3faf5
        );

    border:
        1px solid #dbe7ef;

}


.edit-report-form.show {

    display: block;

    animation:
        editFormAppear 0.25s ease;

}


@keyframes editFormAppear {

    from {

        opacity: 0;

        transform:
            translateY(-6px);

    }

    to {

        opacity: 1;

        transform:
            translateY(0);

    }

}


.edit-form-title {

    font-size: 16px;

    font-weight: 800;

    color: #1e293b;

    margin-bottom: 16px;

}


.edit-form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 14px;

}


.edit-form-group {

    display: flex;

    flex-direction: column;

}


.edit-form-group.full {

    grid-column:
        1 / -1;

}


.edit-form-group label {

    font-size: 11px;

    font-weight: 800;

    color: #64748b;

    text-transform: uppercase;

    letter-spacing: 0.45px;

    margin-bottom: 7px;

}


.edit-form-group textarea {

    width: 100%;

    min-height: 90px;

    padding: 12px 13px;

    border:
        1px solid #d8e0e8;

    border-radius: 10px;

    outline: none;

    resize: vertical;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size: 13px;

    line-height: 1.5;

    color: #334155;

    background: white;

    transition:
        0.2s ease;

}


.edit-form-group textarea:focus {

    border-color: #1976d2;

    box-shadow:
        0 0 0 4px
        rgba(25,118,210,0.08);

}


.edit-form-actions {

    display: flex;

    gap: 9px;

    margin-top: 16px;

}


.save-report-button,
.cancel-edit-button {

    border: none;

    border-radius: 9px;

    padding: 10px 16px;

    font-size: 12px;

    font-weight: 800;

    cursor: pointer;

    transition:
        0.2s ease;

}


.save-report-button {

    color: white;

    background:
        linear-gradient(
            135deg,
            #1976d2,
            #2e7d32
        );

}


.cancel-edit-button {

    color: #475569;

    background: #e9eef3;

}


/* =========================================================
   NO RECORDS
========================================================= */

.no-records {

    padding: 45px 25px;

    text-align: center;

    color: #7b8794;

    background:

        linear-gradient(
            135deg,
            #f8fafc,
            #eef5f9
        );

    border:
        1px dashed #cbd5e1;

    border-radius: 15px;

}


.no-records-icon {

    font-size: 42px;

    margin-bottom: 12px;

    opacity: 0.55;

}


.no-records-title {

    font-size: 16px;

    font-weight: 700;

    color: #64748b;

}


.no-records-text {

    margin-top: 5px;

    font-size: 13px;

}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-area {

    margin-top: 28px;

}


.back-button {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding:
        13px
        20px;

    border-radius: 11px;

    background: white;

    border:
        1px solid #dce3ea;

    color: #475569;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,0.05);

    transition:
        0.2s ease;

}


.back-button:hover {

    background: #1976d2;

    color: white;

    border-color: #1976d2;

    transform:
        translateX(-3px);

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align: center;

    color: #94a3b8;

    font-size: 11px;

    margin-top: 35px;

}


.footer span {

    color: #1976d2;

    font-weight: 700;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1000px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 750px) {

    .header {

        padding:
            0 20px;

    }


    .logo {

        font-size: 23px;

    }


    .system-name {

        margin-left: 0;

    }


    .user-name {

        font-size: 13px;

    }


    .user-icon {

        width: 40px;

        height: 40px;

    }


    .logout-button {

        padding:
            0 10px;

    }


    .logout-text {

        display: none;

    }


    .container {

        width: 94%;

        margin-top: 30px;

    }


    .page-title h1 {

        font-size: 28px;

    }


    .card {

        padding: 21px;

    }


    .search-row {

        flex-direction: column;

    }


    .search-button {

        min-height: 48px;

    }


    .clear-button {

        min-height: 48px;

    }


    .record-grid {

        grid-template-columns: 1fr;

    }


    .card-title-row {

        align-items: flex-start;

        flex-direction: column;

    }


    .report-actions {

        width: 100%;

        justify-content: space-between;

    }


    .edit-form-grid {

        grid-template-columns: 1fr;

    }


    .edit-form-group.full {

        grid-column: auto;

    }

}


@media (max-width: 500px) {

    .stats {

        grid-template-columns: 1fr;

    }


    .header {

        min-height: 75px;

    }


    .logo-icon {

        width: 38px;

        height: 38px;

        font-size: 20px;

    }


    .logo {

        font-size: 21px;

    }


    .system-name {

        font-size: 10px;

    }


    .user-role {

        font-size: 9px;

    }


    .user-name {

        font-size: 12px;

    }


    .user-icon {

        display: none;

    }


    .report-record {

        padding: 18px;

    }


    .student-avatar {

        width: 40px;

        height: 40px;

    }


    .student-name {

        font-size: 16px;

    }

}


@media (max-width: 500px) {

    .user-dropdown {

        right: -5px;

        width: 165px;

    }

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    body {

        background: white !important;

    }


    .floating-symbol {

        display: none !important;

    }


    .header,
    .search-card,
    .back-area,
    .print-button,
    .footer,
    .record-manage-actions,
    .edit-report-form {

        display: none !important;

    }


    .container {

        width: 100%;

        max-width: none;

        margin: 0;

    }


    .page-title {

        margin-bottom: 15px;

    }


    .stats {

        grid-template-columns:
            repeat(4, 1fr);

    }


    .stat-card {

        color: #222 !important;

        background: white !important;

        border:
            1px solid #ccc;

        box-shadow: none;

    }


    .stat-number {

        color: #222 !important;

    }


    .card {

        box-shadow: none;

        border:
            1px solid #ccc;

        break-inside: avoid;

    }


    .report-record {

        box-shadow: none;

        break-inside: avoid;

    }


}

</style>

</head>


<body>


<!-- =========================================================
     FLOATING MEDICAL SYMBOLS
========================================================= -->

<div class="floating-symbol symbol-1">
    ⚕
</div>

<div class="floating-symbol symbol-2">
    ⚕
</div>

<div class="floating-symbol symbol-3">
    ✚
</div>

<div class="floating-symbol symbol-4">
    💊
</div>

<div class="floating-symbol symbol-5">
    🩺
</div>



<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">


    <div class="brand-area">

        <div class="logo-row">

            <div class="logo-icon">
                ⚕
            </div>

            <div class="logo">
                MEDTRACK
            </div>

        </div>


        <div class="system-name">
            Clinic Management System
        </div>

    </div>



    <div class="user-menu">

        <button
            type="button"
            class="user-menu-button"
            onclick="toggleUserMenu(event)"
            aria-expanded="false"
        >

            <div class="user-info">

                <div class="user-role">
                    Administrator
                </div>

                <div class="user-name">

                    <?php

                    echo ucfirst(
                        htmlspecialchars(
                            $_SESSION["username"] ?? ""
                        )
                    );

                    ?>

                </div>

            </div>


            <div class="user-icon">
                👨‍💼
            </div>

        </button>


        <div
            class="user-dropdown"
            id="userDropdown"
        >

            <div class="dropdown-signed-label">
                Signed In As
            </div>

            <div class="dropdown-signed-role">
                Administrator
            </div>

            <div class="dropdown-divider"></div>

            <a
                href="login_history.php"
                class="dropdown-link"
            >
                🕘
                <span>Login History</span>
            </a>

            <a
                href="logout.php"
                class="dropdown-logout"
                onclick="return confirm('Are You Sure You Want To Logout?');"
            >
                ↪
                <span>Logout</span>
            </a>

        </div>

    </div>


</header>



<!-- =========================================================
     MAIN CONTAINER
========================================================= -->

<main class="container">


    <!-- ALERTS -->

    <?php if ($message !== ""): ?>

        <div class="alert alert-success">

            ✓
            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="alert alert-error">

            ⚠
            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

    <section class="page-title">

        <h1>
            Reports
        </h1>

        <div class="title-highlight"></div>

        <p>
            View, search, and generate clinic consultation records.
        </p>

    </section>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="stats">


        <div class="stat-card stat-blue">

            <div class="stat-icon">
                🎓
            </div>

            <div class="stat-title">
                Total Students
            </div>

            <div class="stat-number">

                <?php
                echo $total_students;
                ?>

            </div>

            <div class="stat-subtitle">
                Registered students
            </div>

        </div>



        <div class="stat-card stat-green">

            <div class="stat-icon">
                🩺
            </div>

            <div class="stat-title">
                Total Consultations
            </div>

            <div class="stat-number">

                <?php
                echo $total_consultations;
                ?>

            </div>

            <div class="stat-subtitle">
                Clinic consultation records
            </div>

        </div>



        <div class="stat-card stat-purple">

            <div class="stat-icon">
                💊
            </div>

            <div class="stat-title">
                Medicines
            </div>

            <div class="stat-number">

                <?php
                echo $total_medicines;
                ?>

            </div>

            <div class="stat-subtitle">
                Medicine inventory items
            </div>

        </div>



        <div class="stat-card stat-red">

            <div class="stat-icon">
                ⚠️
            </div>

            <div class="stat-title">
                Low Stock
            </div>

            <div class="stat-number">

                <?php
                echo $low_stock;
                ?>

            </div>

            <div class="stat-subtitle">
                Items needing attention
            </div>

        </div>


    </section>



    <!-- =====================================================
         SEARCH CARD
    ====================================================== -->

    <section class="card search-card">


        <div class="card-title-row">

            <div>

                <div class="card-title">
                    🔎 Search Consultation Records
                </div>

                <div class="card-description">
                    Search by student ID or student name.
                </div>

            </div>

        </div>



        <form method="GET">

            <div class="search-row">


                <div class="search-input-wrapper">

                    <span class="search-icon">
                        🔍
                    </span>

                    <input
                        type="text"
                        name="search"
                        placeholder="Enter Student ID or Student Name"
                        value="<?php
                            echo htmlspecialchars($search);
                        ?>"
                    >

                </div>



                <button
                    type="submit"
                    class="search-button"
                >

                    🔎 Search

                </button>



                <?php if ($search !== ""): ?>

                    <a
                        href="reports.php"
                        class="clear-button"
                    >

                        ✕ Clear

                    </a>

                <?php endif; ?>


            </div>

        </form>


    </section>



    <!-- =====================================================
         CONSULTATION REPORT
    ====================================================== -->

    <section class="card report-card">


        <div class="card-title-row">


            <div class="report-heading">

                <div class="report-heading-icon">
                    📋
                </div>

                <div>

                    <div class="card-title">
                        Patient Consultation Records
                    </div>

                    <div class="card-description">
                        Detailed history of clinic consultations.
                    </div>

                </div>

            </div>



            <div class="report-actions">


                <div class="record-count">

                    <?php
                    echo count($consultations);
                    ?>

                    Records

                </div>


                <button
                    type="button"
                    class="print-button"
                    onclick="window.print()"
                >

                    🖨 Print

                </button>


            </div>


        </div>



        <?php if (
            count($consultations) > 0
        ): ?>


            <?php foreach (
                $consultations
                as $record
            ): ?>


                <article class="report-record">


                    <div class="record-header">


                        <div class="student-section">


                            <div class="student-avatar">

                                <?php

                                $student_name =
                                    $record["full_name"]
                                    ??
                                    "Unknown";

                                echo strtoupper(
                                    substr(
                                        $student_name,
                                        0,
                                        1
                                    )
                                );

                                ?>

                            </div>


                            <div>


                                <div class="student-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $record["full_name"]
                                        ??
                                        "Unknown Student"
                                    );

                                    ?>

                                </div>


                                <div class="student-id">

                                    Student ID:

                                    <?php

                                    echo htmlspecialchars(
                                        $record["student_id"]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </div>


                            </div>


                        </div>



                        <?php

                        if (
                            $date_column !== null
                            &&
                            !empty(
                                $record[
                                    $date_column
                                ]
                            )
                        ):

                        ?>

                            <div class="record-date">

                                📅

                                <?php

                                echo htmlspecialchars(
                                    $record[
                                        $date_column
                                    ]
                                );

                                ?>

                            </div>

                        <?php endif; ?>


                    </div>



                    <div class="record-grid">


                        <?php if (
                            isset(
                                $record["symptoms"]
                            )
                        ): ?>


                            <div class="record-item">

                                <span class="record-label">

                                    🩺
                                    Illness / Symptoms

                                </span>

                                <div class="record-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $record[
                                                "symptoms"
                                            ]
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                        <?php endif; ?>



                        <?php if (
                            isset(
                                $record["diagnosis"]
                            )
                        ): ?>


                            <div class="record-item">

                                <span class="record-label">

                                    🔬
                                    Diagnosis / Assessment

                                </span>

                                <div class="record-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $record[
                                                "diagnosis"
                                            ]
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                        <?php endif; ?>



                        <?php if (
                            isset(
                                $record["result"]
                            )
                        ): ?>


                            <div class="record-item">

                                <span class="record-label">

                                    📋
                                    Result

                                </span>

                                <div class="record-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $record[
                                                "result"
                                            ]
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                        <?php endif; ?>



                        <?php if (
                            isset(
                                $record["prescription"]
                            )
                        ): ?>


                            <div class="record-item">

                                <span class="record-label">

                                    💊
                                    Prescription

                                </span>

                                <div class="record-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $record[
                                                "prescription"
                                            ]
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                        <?php endif; ?>



                        <?php if (
                            isset(
                                $record[
                                    "medication_instruction"
                                ]
                            )
                        ): ?>


                            <div class="record-item">

                                <span class="record-label">

                                    📝
                                    Medication Instructions

                                </span>

                                <div class="record-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $record[
                                                "medication_instruction"
                                            ]
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                        <?php endif; ?>



                        <?php if (
                            $nurse_column !== null
                        ): ?>


                            <div class="record-item">

                                <span class="record-label">

                                    👩‍⚕️
                                    Nurse

                                </span>

                                <div class="record-value">

                                    <?php

                                    echo htmlspecialchars(
                                        $record[
                                            $nurse_column
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </div>

                            </div>


                        <?php endif; ?>


                    </div>



                    <!-- ADMIN ACTIONS -->

                    <div class="record-manage-actions">


                        <button
                            type="button"
                            class="edit-report-button"
                            onclick="toggleEditForm(<?php echo intval($record["id"]); ?>)"
                        >

                            ✎ Edit Report

                        </button>



                        <form
                            method="POST"
                            style="display:inline;"
                            onsubmit="return confirm('Are You Sure You Want To Delete This Report? This Cannot Be Undone.');"
                        >

                            <input
                                type="hidden"
                                name="report_id"
                                value="<?php echo intval($record["id"]); ?>"
                            >

                            <button
                                type="submit"
                                name="delete_report"
                                class="delete-report-button"
                            >

                                🗑 Delete Report

                            </button>

                        </form>


                    </div>



                    <!-- EDIT FORM -->

                    <form
                        method="POST"
                        class="edit-report-form"
                        id="editForm<?php echo intval($record["id"]); ?>"
                    >

                        <input
                            type="hidden"
                            name="report_id"
                            value="<?php echo intval($record["id"]); ?>"
                        >


                        <div class="edit-form-title">

                            ✎ Edit Consultation Report

                        </div>


                        <div class="edit-form-grid">


                            <div class="edit-form-group full">

                                <label>
                                    Symptoms / Reason For Visit
                                </label>

                                <textarea
                                    name="symptoms"
                                    required
                                ><?php
                                    echo htmlspecialchars(
                                        $record["symptoms"] ?? ""
                                    );
                                ?></textarea>

                            </div>


                            <div class="edit-form-group full">

                                <label>
                                    Diagnosis / Assessment
                                </label>

                                <textarea
                                    name="diagnosis"
                                ><?php
                                    echo htmlspecialchars(
                                        $record["diagnosis"] ?? ""
                                    );
                                ?></textarea>

                            </div>


                            <div class="edit-form-group full">

                                <label>
                                    Result
                                </label>

                                <textarea
                                    name="result"
                                ><?php
                                    echo htmlspecialchars(
                                        $record["result"] ?? ""
                                    );
                                ?></textarea>

                            </div>


                            <div class="edit-form-group full">

                                <label>
                                    Prescription
                                </label>

                                <textarea
                                    name="prescription"
                                ><?php
                                    echo htmlspecialchars(
                                        $record["prescription"] ?? ""
                                    );
                                ?></textarea>

                            </div>


                            <div class="edit-form-group full">

                                <label>
                                    Medication Instructions
                                </label>

                                <textarea
                                    name="medication_instruction"
                                ><?php
                                    echo htmlspecialchars(
                                        $record[
                                            "medication_instruction"
                                        ] ?? ""
                                    );
                                ?></textarea>

                            </div>


                        </div>


                        <div class="edit-form-actions">


                            <button
                                type="submit"
                                name="update_report"
                                class="save-report-button"
                            >

                                ✓ Save Changes

                            </button>


                            <button
                                type="button"
                                class="cancel-edit-button"
                                onclick="toggleEditForm(<?php echo intval($record["id"]); ?>)"
                            >

                                Cancel

                            </button>


                        </div>


                    </form>


                </article>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="no-records">

                <div class="no-records-icon">
                    📂
                </div>

                <div class="no-records-title">

                    No Consultation Records Found

                </div>

                <div class="no-records-text">

                    Try searching with another student ID or name.

                </div>

            </div>


        <?php endif; ?>


    </section>



    <!-- =====================================================
         BACK TO DASHBOARD
    ====================================================== -->

    <div class="back-area">


        <a
            href="admin_dashboard.php"
            class="back-button"
        >

            ←
            Back To Dashboard

        </a>


    </div>



    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <div class="footer">

        MEDTRACK

        <span>•</span>

        Clinic Management System

        <span>•</span>

        Reports

    </div>


</main>



<script>

/* =========================================================
   USER DROPDOWN
========================================================= */

function toggleUserMenu(event) {

    if (event) {

        event.stopPropagation();

    }


    const dropdown =
        document.getElementById("userDropdown");

    const button =
        document.querySelector(".user-menu-button");

    if (!dropdown || !button) {

        return;

    }


    const isOpen =
        dropdown.classList.toggle("show");

    button.setAttribute(
        "aria-expanded",
        isOpen ? "true" : "false"
    );

}


document.addEventListener(
    "click",
    function(event) {

        const menu =
            document.querySelector(".user-menu");

        const dropdown =
            document.getElementById("userDropdown");

        const button =
            document.querySelector(".user-menu-button");

        if (
            menu &&
            dropdown &&
            !menu.contains(event.target)
        ) {

            dropdown.classList.remove("show");

            if (button) {

                button.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        }

    }
);


document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            const dropdown =
                document.getElementById("userDropdown");

            const button =
                document.querySelector(".user-menu-button");

            if (dropdown) {

                dropdown.classList.remove("show");

            }

            if (button) {

                button.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        }

    }
);

</script>


<script>

/* =========================================================
   EDIT REPORT
========================================================= */

function toggleEditForm(id) {

    const form = document.getElementById(
        "editForm" + id
    );

    if (!form) {

        return;

    }


    form.classList.toggle("show");


    if (
        form.classList.contains("show")
    ) {

        setTimeout(function() {

            form.scrollIntoView({

                behavior: "smooth",

                block: "center"

            });

        }, 50);

    }

}

</script>


</body>

</html>