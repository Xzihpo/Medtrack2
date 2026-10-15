<?php

session_start();

require_once "Database.php";

/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}


/* =========================================================
   ADMIN + NURSE ONLY
========================================================= */

if (
    !isset($_SESSION["role"]) ||
    (
        $_SESSION["role"] !== "admin" &&
        $_SESSION["role"] !== "nurse"
    )
) {
    header("Location: index.php");
    exit();
}


/* =========================================================
   CREATE LOGIN HISTORY TABLE
========================================================= */

$conn->query("
    CREATE TABLE IF NOT EXISTS login_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");


/* =========================================================
   CREATE CSRF TOKEN
========================================================= */

if (!isset($_SESSION["login_history_csrf"])) {

    $_SESSION["login_history_csrf"] =
        bin2hex(random_bytes(32));

}


/* =========================================================
   CLEAR ALL LOGIN HISTORY
========================================================= */

$clearMessage = "";
$clearError = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["clear_history"])
) {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["login_history_csrf"],
            $_POST["csrf_token"]
        )
    ) {

        $clearError =
            "Invalid Security Request.";

    } else {

        $deleteResult = $conn->query(
            "DELETE FROM login_history"
        );

        if ($deleteResult) {

            $clearMessage =
                "Every Login Record Has Been Deleted.";

        } else {

            $clearError =
                "Unable To Clear Login History.";

        }

    }

}


/* =========================================================
   DATE SEARCH
========================================================= */

$searchDate = "";
$dateError = "";

if (isset($_GET["search_date"])) {

    $searchDate =
        trim($_GET["search_date"]);

}


/* =========================================================
   CONVERT MM/DD/YYYY TO YYYY-MM-DD
========================================================= */

$sqlDate = "";

if ($searchDate !== "") {

    /*
       Accept ONLY:
       MM/DD/YYYY
    */

    if (
        preg_match(
            '/^(\d{2})\/(\d{2})\/(\d{4})$/',
            $searchDate,
            $matches
        )
    ) {

        $month = (int)$matches[1];
        $day   = (int)$matches[2];
        $year  = (int)$matches[3];

        if (
            checkdate(
                $month,
                $day,
                $year
            )
        ) {

            $sqlDate =
                sprintf(
                    "%04d-%02d-%02d",
                    $year,
                    $month,
                    $day
                );

        } else {

            $dateError =
                "Please Enter A Valid Date In MM/DD/YYYY Format.";

        }

    } else {

        $dateError =
            "Please Enter The Date In MM/DD/YYYY Format.";

    }

}


/* =========================================================
   GET LOGIN HISTORY
========================================================= */

$history = false;


/*
   IF VALID DATE WAS SEARCHED
*/

if ($sqlDate !== "") {

    $stmt = $conn->prepare("
        SELECT
            id,
            username,
            role,
            login_time
        FROM login_history
        WHERE DATE(login_time) = ?
        ORDER BY login_time DESC
    ");

    $stmt->bind_param(
        "s",
        $sqlDate
    );

    $stmt->execute();

    $history =
        $stmt->get_result();


/*
   OTHERWISE SHOW ALL HISTORY
*/

} else {

    $history = $conn->query("
        SELECT
            id,
            username,
            role,
            login_time
        FROM login_history
        ORDER BY login_time DESC
    ");

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

<title>MEDTRACK - Login History</title>

<style>

/* =========================================================
   GLOBAL
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    min-height: 100vh;
    color: #1e293b;

    background:
        radial-gradient(
            circle at 5% 20%,
            rgba(25,118,210,0.18),
            transparent 28%
        ),
        radial-gradient(
            circle at 95% 15%,
            rgba(46,125,50,0.15),
            transparent 28%
        ),
        radial-gradient(
            circle at 85% 90%,
            rgba(25,118,210,0.13),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #eef8ff,
            #f2fbf5,
            #edf7ff
        );

    overflow-x: hidden;
}


/* =========================================================
   FLOATING MEDICAL SYMBOLS
========================================================= */

.medical-symbol {
    position: fixed;
    pointer-events: none;
    user-select: none;
    z-index: 0;
    opacity: 0.13;
    animation: floatSymbol 8s ease-in-out infinite;
}

.symbol-1 {
    top: 110px;
    left: 4%;
    font-size: 65px;
    color: #1976d2;
}

.symbol-2 {
    top: 200px;
    right: 5%;
    font-size: 75px;
    color: #2e7d32;
    animation-delay: 2s;
}

.symbol-3 {
    bottom: 130px;
    left: 8%;
    font-size: 55px;
    color: #1976d2;
    animation-delay: 4s;
}

.symbol-4 {
    bottom: 70px;
    right: 10%;
    font-size: 65px;
    color: #2e7d32;
    animation-delay: 1s;
}

@keyframes floatSymbol {

    0%,
    100% {
        transform:
            translateY(0)
            rotate(0deg);
    }

    50% {
        transform:
            translateY(-18px)
            rotate(6deg);
    }
}


/* =========================================================
   BACKGROUND DECORATION
========================================================= */

.background-circle {
    position: fixed;
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}

.circle-one {
    width: 270px;
    height: 270px;
    left: -100px;
    top: 70px;
    background: rgba(25,118,210,0.08);
}

.circle-two {
    width: 300px;
    height: 300px;
    right: -110px;
    bottom: -80px;
    background: rgba(46,125,50,0.08);
}


/* =========================================================
   HEADER
========================================================= */

.header {
    height: 73px;
    position: relative;
    z-index: 100;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 40px;

    color: white;

    background:
        linear-gradient(
            110deg,
            #1683d1,
            #168ca5 48%,
            #27833f
        );

    box-shadow:
        0 5px 25px
        rgba(20,100,130,0.18);
}

.header::after {
    content: "";
    position: absolute;
    width: 500px;
    height: 500px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.08);
    right: -210px;
    top: -350px;
    pointer-events: none;
}

.brand {
    position: relative;
    z-index: 2;
}

.logo {
    font-size: 29px;
    font-weight: 800;
    letter-spacing: 3px;
    line-height: 1;
}

.system-name {
    font-size: 10px;
    margin-top: 5px;
    color: rgba(255,255,255,0.82);
}


/* =========================================================
   USER DROPDOWN
========================================================= */

.user-menu {
    position: relative;
    z-index: 1000;
}

.user-menu-button {
    border: none;
    background: rgba(255,255,255,0.10);
    color: white;
    cursor: pointer;

    display: flex;
    align-items: center;
    justify-content: flex-end;

    gap: 10px;

    width: 168px;
    min-height: 58px;

    padding: 7px 10px;

    border-radius: 16px;

    transition: 0.18s ease;
}

.user-menu-button:hover {
    background: rgba(255,255,255,0.17);
}

.user-info {
    text-align: right;
    line-height: 1.05;
}

.user-role {
    font-size: 10px;
    font-weight: 700;
    opacity: 0.82;
    margin-bottom: 4px;
    text-transform: uppercase;
}

.user-name {
    font-size: 13px;
    font-weight: 800;
}

.user-icon {
    width: 43px;
    height: 43px;
    flex: 0 0 43px;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 21px;

    background: rgba(255,255,255,0.16);

    border:
        1px solid
        rgba(255,255,255,0.28);
}

.user-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 1px);

    width: 168px;
    box-sizing: border-box;

    padding: 14px 9px 9px;

    border-radius: 14px;

    background: rgba(255,255,255,0.98);

    border:
        1px solid
        rgba(255,255,255,0.98);

    box-shadow:
        0 15px 35px
        rgba(20,80,110,0.22);

    opacity: 0;
    visibility: hidden;
    pointer-events: none;

    transform: translateY(-7px);

    transition: 0.18s ease;

    overflow: hidden;
}

.user-dropdown.show {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transform: translateY(0);
}

.dropdown-label {
    display: block;
    padding: 0 10px 2px;
    color: #718096;
    font-size: 10px;
    font-weight: 500;
}

.dropdown-name {
    display: block;
    padding: 0 10px 10px;
    color: #273444;
    font-size: 13px;
    font-weight: 700;
}

.login-history-button {
    display: block;
    width: 100%;
    box-sizing: border-box;

    margin-top: 0;
    padding: 9px 12px;

    border-radius: 8px;
    border-top: 1px solid #e7ebef;

    text-decoration: none;
    text-align: left;

    color: #1683d1;
    background: transparent;

    font-size: 13px;
    font-weight: 700;
}

.login-history-button:hover {
    background: #eef8ff;
    color: #0d6fb8;
}

.logout-button {
    display: block;
    width: 100%;
    box-sizing: border-box;

    margin-top: 0;
    padding: 9px 12px;

    border-radius: 8px;
    border-top: 1px solid #e7ebef;

    text-decoration: none;
    text-align: left;

    color: #d83232;
    background: transparent;

    font-size: 13px;
    font-weight: 700;
}

.logout-button:hover {
    background: #fff3f3;
    color: #bd2525;
}


/* =========================================================
   MAIN
========================================================= */

.container {
    width: 92%;
    max-width: 1200px;
    margin: 42px auto;
    position: relative;
    z-index: 2;
}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {
    background:
        rgba(255,255,255,0.92);

    backdrop-filter:
        blur(15px);

    -webkit-backdrop-filter:
        blur(15px);

    border:
        1px solid
        rgba(255,255,255,0.85);

    border-radius: 22px;

    padding: 27px 30px;

    margin-bottom: 25px;

    box-shadow:
        0 14px 38px
        rgba(15,23,42,0.08);

    position: relative;
    overflow: hidden;
}

.page-title::after {
    content: "";
    position: absolute;

    width: 150px;
    height: 150px;

    border-radius: 50%;

    background:
        rgba(25,118,210,0.06);

    right: -45px;
    top: -70px;
}

.title-row {
    display: flex;
    align-items: center;
    gap: 16px;
}

.title-icon {
    width: 58px;
    height: 58px;

    border-radius: 17px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 28px;

    background:
        linear-gradient(
            135deg,
            #e3f2fd,
            #e8f5e9
        );

    box-shadow:
        0 8px 20px
        rgba(25,118,210,0.10);
}

.page-title h1 {
    font-size: 29px;
    color: #172033;
    margin-bottom: 6px;
}

.page-title p {
    color: #64748b;
    font-size: 14px;
}


/* =========================================================
   SEARCH CARD
========================================================= */

.search-card {
    background:
        rgba(255,255,255,0.94);

    backdrop-filter:
        blur(15px);

    -webkit-backdrop-filter:
        blur(15px);

    border:
        1px solid
        rgba(255,255,255,0.9);

    border-radius: 22px;

    padding: 22px 27px;

    margin-bottom: 25px;

    box-shadow:
        0 14px 38px
        rgba(15,23,42,0.08);
}

.search-title {
    display: flex;
    align-items: center;
    gap: 10px;

    color: #172033;
    font-size: 17px;
    font-weight: 800;

    margin-bottom: 5px;
}

.search-title-icon {
    width: 38px;
    height: 38px;

    border-radius: 11px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #e3f2fd;

    font-size: 18px;
}

.search-description {
    color: #94a3b8;
    font-size: 12px;
    margin-bottom: 16px;
}

.search-row {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.date-field {
    flex: 1;
    min-width: 240px;
}

.date-label {
    display: block;

    color: #475569;

    font-size: 12px;
    font-weight: 800;

    margin-bottom: 7px;
}

.date-input-wrapper {
    position: relative;
}

.date-input {
    width: 100%;

    height: 45px;

    border:
        1px solid
        #dbe3ea;

    border-radius: 10px;

    background: white;

    color: #172033;

    padding:
        0 43px
        0 14px;

    font-size: 14px;

    outline: none;

    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.date-input::placeholder {
    color: #a0aec0;
}

.date-input:focus {
    border-color: #1683d1;

    box-shadow:
        0 0 0 3px
        rgba(22,131,209,0.10);
}

.calendar-icon {
    position: absolute;

    right: 14px;
    top: 50%;

    transform:
        translateY(-50%);

    color: #64748b;

    pointer-events: none;

    font-size: 18px;
}

.search-button {
    height: 45px;

    padding:
        0 18px;

    border: none;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #1976d2,
            #168ca5
        );

    color: white;

    font-size: 13px;
    font-weight: 800;

    cursor: pointer;

    box-shadow:
        0 6px 16px
        rgba(25,118,210,0.20);

    transition: 0.2s;
}

.search-button:hover {
    transform:
        translateY(-2px);

    box-shadow:
        0 9px 20px
        rgba(25,118,210,0.28);
}

.clear-date-button {
    height: 45px;

    padding:
        0 17px;

    border:
        1px solid
        #dbe3ea;

    border-radius: 10px;

    background: #f8fafc;

    color: #64748b;

    font-size: 13px;
    font-weight: 800;

    cursor: pointer;

    text-decoration: none;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    transition: 0.2s;
}

.clear-date-button:hover {
    background: white;

    color: #334155;

    border-color: #cbd5e1;
}


/* =========================================================
   SEARCH RESULT MESSAGE
========================================================= */

.search-result {
    margin-top: 15px;

    padding: 11px 14px;

    border-radius: 10px;

    background: #eff6ff;

    border:
        1px solid
        #bfdbfe;

    color: #1d4ed8;

    font-size: 12px;
    font-weight: 700;
}

.search-error {
    margin-top: 15px;

    padding: 11px 14px;

    border-radius: 10px;

    background: #fef2f2;

    border:
        1px solid
        #fecaca;

    color: #b91c1c;

    font-size: 12px;
    font-weight: 700;
}


/* =========================================================
   HISTORY CARD
========================================================= */

.history-card {
    background:
        rgba(255,255,255,0.94);

    backdrop-filter:
        blur(15px);

    -webkit-backdrop-filter:
        blur(15px);

    border:
        1px solid
        rgba(255,255,255,0.9);

    border-radius: 22px;

    box-shadow:
        0 14px 38px
        rgba(15,23,42,0.08);

    overflow: hidden;
}


/* =========================================================
   CARD HEADER
========================================================= */

.card-header {
    padding: 23px 27px;

    border-bottom:
        1px solid
        #edf1f5;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 12px;
}

.card-heading {
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-icon {
    width: 43px;
    height: 43px;

    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    background:
        #e3f2fd;

    font-size: 20px;
}

.card-title {
    font-size: 19px;
    font-weight: 700;
    color: #172033;
}

.card-subtitle {
    color: #94a3b8;
    font-size: 12px;
    margin-top: 3px;
}


/* =========================================================
   CLEAR HISTORY BUTTON
========================================================= */

.clear-history-btn {
    border: none;

    background:
        linear-gradient(
            135deg,
            #dc2626,
            #ef4444
        );

    color: white;

    padding: 11px 17px;

    border-radius: 10px;

    font-size: 12px;
    font-weight: 800;

    cursor: pointer;

    display: inline-flex;
    align-items: center;

    gap: 7px;

    box-shadow:
        0 6px 16px
        rgba(220,38,38,0.22);

    transition:
        transform 0.2s,
        box-shadow 0.2s;
}

.clear-history-btn:hover {
    transform:
        translateY(-2px);

    box-shadow:
        0 9px 20px
        rgba(220,38,38,0.30);
}

.clear-history-btn:active {
    transform:
        translateY(0);
}


/* =========================================================
   TABLE
========================================================= */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

th {
    background: #f8fafc;
    color: #64748b;

    font-size: 11px;
    font-weight: 800;

    letter-spacing: 0.7px;
    text-transform: uppercase;

    text-align: left;

    padding: 15px 20px;

    border-bottom:
        1px solid
        #e8edf2;
}

td {
    padding: 17px 20px;
    font-size: 14px;
    color: #475569;

    border-bottom:
        1px solid
        #edf1f5;
}

tbody tr {
    transition: 0.2s;
}

tbody tr:hover {
    background:
        linear-gradient(
            90deg,
            rgba(227,242,253,0.45),
            rgba(232,245,233,0.25)
        );
}

tbody tr:last-child td {
    border-bottom: none;
}


/* =========================================================
   USER
========================================================= */

.username {
    font-weight: 700;
    color: #172033;
}


/* =========================================================
   ROLE
========================================================= */

.role {
    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 11px;
    font-weight: 800;
}

.role-admin {
    background: #e3f2fd;
    color: #1565c0;
}

.role-nurse {
    background: #e8f5e9;
    color: #2e7d32;
}


/* =========================================================
   TIME
========================================================= */

.login-time {
    color: #475569;
    font-weight: 600;
}

.date-text {
    color: #64748b;
    font-size: 12px;
    margin-top: 3px;
}


/* =========================================================
   EMPTY
========================================================= */

.empty {
    text-align: center;
    padding: 60px 20px !important;
    color: #94a3b8;
}

.empty-icon {
    font-size: 42px;
    margin-bottom: 12px;
}

.empty-text {
    font-size: 14px;
}


/* =========================================================
   MESSAGE
========================================================= */

.message-area {
    margin-bottom: 18px;
}

.success-message {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;

    padding: 13px 17px;

    border-radius: 12px;

    font-size: 13px;
    font-weight: 700;
}

.error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;

    padding: 13px 17px;

    border-radius: 12px;

    font-size: 13px;
    font-weight: 700;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-area {
    margin-top: 25px;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;

    padding: 12px 18px;

    background:
        rgba(255,255,255,0.88);

    border:
        1px solid
        #e2e8f0;

    color: #475569;

    text-decoration: none;

    border-radius: 10px;

    font-size: 13px;
    font-weight: 700;

    transition: 0.2s;
}

.back-btn:hover {
    background: white;

    transform:
        translateX(-2px);

    box-shadow:
        0 5px 15px
        rgba(15,23,42,0.08);
}


/* =========================================================
   FOOTER
========================================================= */

.footer {
    text-align: center;
    color: #94a3b8;
    font-size: 11px;
    margin-top: 25px;
}


/* =========================================================
   WARNING MODAL
========================================================= */

.modal-overlay {
    position: fixed;
    inset: 0;

    background:
        rgba(15,23,42,0.52);

    backdrop-filter:
        blur(5px);

    -webkit-backdrop-filter:
        blur(5px);

    display: none;

    align-items: center;
    justify-content: center;

    padding: 20px;

    z-index: 9999;
}

.modal-overlay.show {
    display: flex;
}

.warning-modal {
    width: 100%;
    max-width: 480px;

    background:
        rgba(255,255,255,0.98);

    border-radius: 22px;

    padding: 30px;

    box-shadow:
        0 25px 70px
        rgba(15,23,42,0.28);

    text-align: center;

    animation:
        modalAppear 0.2s ease;
}

@keyframes modalAppear {

    from {
        opacity: 0;

        transform:
            translateY(12px)
            scale(0.97);
    }

    to {
        opacity: 1;

        transform:
            translateY(0)
            scale(1);
    }
}

.warning-icon {
    width: 70px;
    height: 70px;

    margin: 0 auto 18px;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 34px;

    background:
        #fef2f2;

    border:
        1px solid
        #fecaca;
}

.warning-modal h2 {
    color: #172033;
    font-size: 23px;
    margin-bottom: 13px;
}

.warning-text {
    color: #475569;
    font-size: 14px;
    line-height: 1.7;
    margin-bottom: 7px;
}

.warning-text strong {
    color: #b91c1c;
}

.warning-subtext {
    color: #94a3b8;
    font-size: 12px;
    margin-bottom: 25px;
}

.modal-buttons {
    display: flex;
    justify-content: center;
    gap: 12px;
}

.modal-btn {
    border: none;
    border-radius: 10px;

    padding: 12px 18px;

    font-size: 13px;
    font-weight: 800;

    cursor: pointer;

    transition:
        transform 0.2s,
        box-shadow 0.2s;
}

.modal-btn:hover {
    transform:
        translateY(-2px);
}

.cancel-btn {
    background:
        #f1f5f9;

    color:
        #475569;

    border:
        1px solid
        #e2e8f0;
}

.cancel-btn:hover {
    box-shadow:
        0 6px 15px
        rgba(15,23,42,0.08);
}

.delete-btn {
    background:
        linear-gradient(
            135deg,
            #b91c1c,
            #ef4444
        );

    color: white;

    box-shadow:
        0 6px 16px
        rgba(185,28,28,0.24);
}

.delete-btn:hover {
    box-shadow:
        0 9px 20px
        rgba(185,28,28,0.32);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 700px) {

    .card-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .clear-history-btn {
        width: 100%;
        justify-content: center;
    }

    .search-row {
        flex-direction: column;
        align-items: stretch;
    }

    .date-field {
        min-width: 100%;
    }

    .search-button,
    .clear-date-button {
        width: 100%;
    }
}


@media (max-width: 650px) {

    .header {
        padding: 0 18px;
    }

    .logo {
        font-size: 23px;
    }

    .system-name {
        font-size: 8px;
    }

    .user-menu-button {
        width: 156px;
    }

    .user-name {
        font-size: 12px;
    }

    .user-icon {
        width: 38px;
        height: 38px;
        flex-basis: 38px;
        font-size: 18px;
    }

    .user-dropdown {
        right: -5px;
    }

    .container {
        width: 94%;
        margin-top: 25px;
    }

    .page-title {
        padding: 22px;
    }

    .page-title h1 {
        font-size: 25px;
    }

    .medical-symbol {
        display: none;
    }

    .warning-modal {
        padding: 25px 20px;
    }

    .modal-buttons {
        flex-direction: column;
    }

    .modal-btn {
        width: 100%;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     BACKGROUND
========================================================= -->

<div class="background-circle circle-one"></div>
<div class="background-circle circle-two"></div>

<div class="medical-symbol symbol-1">
    ✚
</div>

<div class="medical-symbol symbol-2">
    ✚
</div>

<div class="medical-symbol symbol-3">
    ♡
</div>

<div class="medical-symbol symbol-4">
    ✚
</div>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">

    <div class="brand">

        <div class="logo">
            MEDTRACK
        </div>

        <div class="system-name">
            Clinic Management System
        </div>

    </div>


    <div class="user-menu" id="userMenu">

        <button
            type="button"
            class="user-menu-button"
            onclick="toggleUserMenu(event)"
            aria-expanded="false"
        >

            <div class="user-info">

                <div class="user-role">

                    <?php

                    echo strtoupper(
                        htmlspecialchars(
                            $_SESSION["role"] ?? ""
                        )
                    );

                    ?>

                </div>


                <div class="user-name">

                    <?php

                    echo htmlspecialchars(
                        ucfirst(
                            strtolower(
                                $_SESSION["username"] ?? "User"
                            )
                        )
                    );

                    ?>

                </div>

            </div>


            <div class="user-icon">

                <?php

                if (
                    isset($_SESSION["role"]) &&
                    $_SESSION["role"] === "admin"
                ) {

                    echo "👨‍💼";

                } else {

                    echo "👩‍⚕️";

                }

                ?>

            </div>

        </button>


        <div
            class="user-dropdown"
            id="userDropdown"
            onclick="event.stopPropagation()"
        >

            <div class="dropdown-label">
                Signed In As
            </div>

            <div class="dropdown-name">

                <?php

                if (
                    isset($_SESSION["role"]) &&
                    $_SESSION["role"] === "admin"
                ) {

                    echo "Administrator";

                } else {

                    echo "Nurse";

                }

                ?>

            </div>


            <a
                href="login_history.php"
                class="login-history-button"
            >
                🕘&nbsp;&nbsp;Login History
            </a>


            <a
                href="logout.php"
                class="logout-button"
            >
                ↪&nbsp;&nbsp;Logout
            </a>

        </div>

    </div>

</header>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="container">


    <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

    <section class="page-title">

        <div class="title-row">

            <div class="title-icon">
                📋
            </div>

            <div>

                <h1>
                    Login History
                </h1>

                <p>
                    View The Login Activity Of MEDTRACK Clinic Personnel.
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================================
         MESSAGES
    ====================================================== -->

    <?php if ($clearMessage !== ""): ?>

        <div class="message-area">

            <div class="success-message">

                ✓

                <?php

                echo htmlspecialchars(
                    $clearMessage
                );

                ?>

            </div>

        </div>

    <?php endif; ?>


    <?php if ($clearError !== ""): ?>

        <div class="message-area">

            <div class="error-message">

                ⚠

                <?php

                echo htmlspecialchars(
                    $clearError
                );

                ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         DATE SEARCH
    ====================================================== -->

    <section class="search-card">

        <div class="search-title">

            <div class="search-title-icon">
                🔎
            </div>

            Search Login History By Date

        </div>


        <div class="search-description">

            Enter A Specific Date To See Who Logged In On That Day.

        </div>


        <form
            method="GET"
            action="login_history.php"
            autocomplete="off"
        >

            <div class="search-row">


                <div class="date-field">

                    <label
                        class="date-label"
                        for="search_date"
                    >
                        Date
                    </label>


                    <div class="date-input-wrapper">

                        <input
                            type="text"
                            id="search_date"
                            name="search_date"
                            class="date-input"
                            placeholder="MM/DD/YYYY"
                            maxlength="10"
                            value="<?php
                                echo htmlspecialchars(
                                    $searchDate
                                );
                            ?>"
                        >

                        <span class="calendar-icon">
                            📅
                        </span>

                    </div>

                </div>


                <button
                    type="submit"
                    class="search-button"
                >
                    🔍 Search Date
                </button>


                <a
                    href="login_history.php"
                    class="clear-date-button"
                >
                    ✕ Clear Date
                </a>


            </div>

        </form>


        <?php if ($dateError !== ""): ?>

            <div class="search-error">

                ⚠️

                <?php

                echo htmlspecialchars(
                    $dateError
                );

                ?>

            </div>

        <?php elseif ($searchDate !== "" && $sqlDate !== ""): ?>

            <div class="search-result">

                🔎 Showing Login Records For:

                <strong>
                    <?php

                    echo htmlspecialchars(
                        $searchDate
                    );

                    ?>
                </strong>

            </div>

        <?php endif; ?>


    </section>


    <!-- =====================================================
         HISTORY TABLE
    ====================================================== -->

    <section class="history-card">


        <div class="card-header">


            <div class="card-heading">

                <div class="card-icon">
                    🕒
                </div>

                <div>

                    <div class="card-title">
                        Staff Login Records
                    </div>

                    <div class="card-subtitle">

                        <?php

                        if (
                            $searchDate !== "" &&
                            $sqlDate !== ""
                        ) {

                            echo
                                "Login Activity For " .
                                htmlspecialchars(
                                    $searchDate
                                ) .
                                ".";

                        } else {

                            echo
                                "Most Recent Login Activity Appears First.";

                        }

                        ?>

                    </div>

                </div>

            </div>


            <button
                type="button"
                class="clear-history-btn"
                onclick="openClearModal()"
            >

                🗑️ Clear History

            </button>


        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Username
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Login Date & Time
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if ($history && $history->num_rows > 0): ?>


                    <?php while ($row = $history->fetch_assoc()): ?>


                        <tr>


                            <td>

                                <div class="username">

                                    <?php

                                    $historyUsername =
                                        trim(
                                            (string)
                                            ($row["username"] ?? "")
                                        );

                                    echo htmlspecialchars(
                                        ucfirst(
                                            $historyUsername
                                        )
                                    );

                                    ?>

                                </div>

                            </td>


                            <td>

                                <?php

                                if (
                                    strtolower(
                                        $row["role"]
                                    ) === "admin"
                                ) {

                                    echo '
                                    <span class="role role-admin">
                                        Administrator
                                    </span>';

                                } else {

                                    echo '
                                    <span class="role role-nurse">
                                        Nurse
                                    </span>';

                                }

                                ?>

                            </td>


                            <td>

                                <div class="login-time">

                                    <?php

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $row["login_time"]
                                        )
                                    );

                                    ?>

                                </div>


                                <div class="date-text">

                                    <?php

                                    echo date(
                                        "F d, Y",
                                        strtotime(
                                            $row["login_time"]
                                        )
                                    );

                                    ?>

                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="3"
                            class="empty"
                        >

                            <div class="empty-icon">
                                📋
                            </div>

                            <div class="empty-text">

                                <?php

                                if (
                                    $searchDate !== "" &&
                                    $sqlDate !== ""
                                ) {

                                    echo
                                        "No Login History Found For " .
                                        htmlspecialchars(
                                            $searchDate
                                        ) .
                                        ".";

                                } else {

                                    echo
                                        "No Login History Found.";

                                }

                                ?>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>


    <!-- =====================================================
         BACK
    ====================================================== -->

    <div class="back-area">

        <?php

        if (
            isset($_SESSION["role"]) &&
            $_SESSION["role"] === "admin"
        ) {

            $backPage =
                "admin_dashboard.php";

        } else {

            $backPage =
                "nurse_dashboard.php";

        }

        ?>


        <a
            href="<?php echo $backPage; ?>"
            class="back-btn"
        >

            ← Back To Dashboard

        </a>

    </div>


    <div class="footer">

        MEDTRACK Clinic Management System

    </div>


</main>


<!-- =========================================================
     CLEAR HISTORY WARNING MODAL
========================================================= -->

<div
    class="modal-overlay"
    id="clearModal"
    onclick="closeClearModal(event)"
>


    <div
        class="warning-modal"
        onclick="event.stopPropagation()"
    >


        <div class="warning-icon">
            ⚠️
        </div>


        <h2>
            Warning
        </h2>


        <div class="warning-text">

            Pressing <strong>Yes</strong> Will Lead To
            <strong>Every Login Record To Be Deleted.</strong>

        </div>


        <div class="warning-subtext">

            This Action Cannot Be Undone.

        </div>


        <div class="modal-buttons">


            <button
                type="button"
                class="modal-btn cancel-btn"
                onclick="closeClearModal()"
            >

                No, Keep History

            </button>


            <form
                method="POST"
                action=""
            >

                <input
                    type="hidden"
                    name="clear_history"
                    value="1"
                >


                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php
                    echo htmlspecialchars(
                        $_SESSION["login_history_csrf"]
                    );
                    ?>"
                >


                <button
                    type="submit"
                    class="modal-btn delete-btn"
                >

                    Yes, Delete Everything

                </button>

            </form>


        </div>


    </div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   USER MENU
========================================================= */

function toggleUserMenu(event) {

    event.stopPropagation();

    const dropdown =
        document.getElementById("userDropdown");

    const button =
        document.querySelector(".user-menu-button");

    const isOpen =
        dropdown.classList.toggle("show");

    button.setAttribute(
        "aria-expanded",
        isOpen ? "true" : "false"
    );
}


/* =========================================================
   CLOSE USER MENU
========================================================= */

document.addEventListener(
    "click",
    function(event) {

        const menu =
            document.getElementById("userMenu");

        const dropdown =
            document.getElementById("userDropdown");

        const button =
            document.querySelector(".user-menu-button");

        if (
            menu &&
            !menu.contains(event.target)
        ) {

            dropdown.classList.remove("show");

            button.setAttribute(
                "aria-expanded",
                "false"
            );

        }

    }
);


/* =========================================================
   DATE INPUT
   AUTOMATICALLY ADDS /
========================================================= */

const dateInput =
    document.getElementById("search_date");

if (dateInput) {

    dateInput.addEventListener(
        "input",
        function() {

            let value =
                this.value.replace(
                    /[^0-9]/g,
                    ""
                );

            if (value.length > 8) {

                value =
                    value.substring(
                        0,
                        8
                    );

            }


            if (value.length >= 5) {

                value =
                    value.substring(0, 2) +
                    "/" +
                    value.substring(2, 4) +
                    "/" +
                    value.substring(4);

            } else if (value.length >= 3) {

                value =
                    value.substring(0, 2) +
                    "/" +
                    value.substring(2);

            }


            this.value =
                value;

        }
    );

}


/* =========================================================
   CLEAR HISTORY MODAL
========================================================= */

function openClearModal() {

    const modal =
        document.getElementById("clearModal");

    modal.classList.add("show");

}


function closeClearModal(event) {

    if (
        event &&
        event.target !==
        document.getElementById("clearModal")
    ) {

        return;

    }

    const modal =
        document.getElementById("clearModal");

    modal.classList.remove("show");

}


/* =========================================================
   ESCAPE KEY
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closeClearModal();

        }

    }
);

</script>


</body>

</html>