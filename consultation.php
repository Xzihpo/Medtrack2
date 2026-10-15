<?php

session_start();

require_once "Database.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"])
) {

    header("Location: index.php");
    exit();

}


/* =========================================================
   ONLY ADMIN AND NURSE
========================================================= */

if (
    $_SESSION["role"] !== "admin" &&
    $_SESSION["role"] !== "nurse"
) {

    header("Location: index.php");
    exit();

}


/* =========================================================
   VARIABLES
========================================================= */

$message = "";

$error = "";

$student = null;

$previous_visits = [];


/* =========================================================
   SEARCH STUDENT
========================================================= */

if (isset($_POST["search_student"])) {

    $student_id =
        trim($_POST["student_id"] ?? "");


    if ($student_id === "") {

        $error =
            "Please Enter A Student ID.";

    } else {

        $stmt = $conn->prepare(
            "SELECT *
             FROM students
             WHERE student_id = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $error =
                "Database Error: Unable To Prepare Student Search.";

        } else {

            $stmt->bind_param(
                "s",
                $student_id
            );

            $stmt->execute();

            $result =
                $stmt->get_result();


            if ($result->num_rows === 1) {

                $student =
                    $result->fetch_assoc();


                /* =================================================
                   PREVIOUS CONSULTATIONS
                ================================================= */

                $history_stmt = $conn->prepare(
                    "SELECT
                        id,
                        student_id,
                        symptoms,
                        diagnosis,
                        result,
                        prescription,
                        medication_instruction
                     FROM consultations
                     WHERE student_id = ?
                     ORDER BY id DESC"
                );


                if ($history_stmt) {

                    $history_stmt->bind_param(
                        "s",
                        $student_id
                    );

                    $history_stmt->execute();

                    $history_result =
                        $history_stmt->get_result();


                    while (
                        $visit =
                        $history_result->fetch_assoc()
                    ) {

                        $previous_visits[] =
                            $visit;

                    }


                    $history_stmt->close();

                } else {

                    $error =
                        "Unable To Load Previous Consultation Records.";

                }


            } else {

                $error =
                    "Student Not Found. Please Register The Student First.";

            }


            $stmt->close();

        }

    }

}


/* =========================================================
   SAVE CONSULTATION
========================================================= */

if (isset($_POST["save_consultation"])) {

    $student_id =
        trim($_POST["student_id"] ?? "");

    $symptoms =
        trim($_POST["symptoms"] ?? "");

    $diagnosis =
        trim($_POST["diagnosis"] ?? "");

    $result_text =
        trim($_POST["result"] ?? "");

    $prescription =
        trim($_POST["prescription"] ?? "");

    $medication_instruction =
        trim($_POST["medication_instruction"] ?? "");


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $student_id === "" ||
        $symptoms === ""
    ) {

        $error =
            "Student ID And Symptoms Are Required.";

    } else {


        /* =================================================
           CHECK STUDENT
        ================================================= */

        $check = $conn->prepare(
            "SELECT id
             FROM students
             WHERE student_id = ?
             LIMIT 1"
        );


        if (!$check) {

            $error =
                "Database Error: Unable To Check Student.";

        } else {

            $check->bind_param(
                "s",
                $student_id
            );

            $check->execute();

            $check_result =
                $check->get_result();


            if (
                $check_result->num_rows === 0
            ) {

                $error =
                    "Student Not Found. Please Register The Student First.";

            } else {


                /* =================================================
                   SAVE
                ================================================= */

                $stmt = $conn->prepare(
                    "INSERT INTO consultations
                    (
                        student_id,
                        symptoms,
                        diagnosis,
                        result,
                        prescription,
                        medication_instruction
                    )
                    VALUES (?, ?, ?, ?, ?, ?)"
                );


                if (!$stmt) {

                    $error =
                        "Database Error: Unable To Prepare Consultation.";

                } else {

                    $stmt->bind_param(
                        "ssssss",
                        $student_id,
                        $symptoms,
                        $diagnosis,
                        $result_text,
                        $prescription,
                        $medication_instruction
                    );


                    if ($stmt->execute()) {

                        $message =
                            "Consultation Successfully Saved.";


                        /* =================================================
                           GET STUDENT AGAIN
                        ================================================= */

                        $student_stmt =
                            $conn->prepare(
                                "SELECT *
                                 FROM students
                                 WHERE student_id = ?
                                 LIMIT 1"
                            );


                        if ($student_stmt) {

                            $student_stmt->bind_param(
                                "s",
                                $student_id
                            );

                            $student_stmt->execute();

                            $student_result =
                                $student_stmt->get_result();


                            if (
                                $student_result->num_rows === 1
                            ) {

                                $student =
                                    $student_result->fetch_assoc();

                            }


                            $student_stmt->close();

                        }


                        /* =================================================
                           CLEAR HISTORY
                        ================================================= */

                        $previous_visits = [];


                        /* =================================================
                           UPDATED HISTORY
                        ================================================= */

                        $history_stmt =
                            $conn->prepare(
                                "SELECT
                                    id,
                                    student_id,
                                    symptoms,
                                    diagnosis,
                                    result,
                                    prescription,
                                    medication_instruction
                                 FROM consultations
                                 WHERE student_id = ?
                                 ORDER BY id DESC"
                            );


                        if ($history_stmt) {

                            $history_stmt->bind_param(
                                "s",
                                $student_id
                            );

                            $history_stmt->execute();

                            $history_result =
                                $history_stmt->get_result();


                            while (
                                $visit =
                                $history_result->fetch_assoc()
                            ) {

                                $previous_visits[] =
                                    $visit;

                            }


                            $history_stmt->close();

                        }


                    } else {

                        $error =
                            "Unable To Save Consultation: " .
                            $stmt->error;

                    }


                    $stmt->close();

                }

            }


            $check->close();

        }

    }

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

<title>MEDTRACK - Consultation</title>


<!-- =========================================================
     QR SCANNER
========================================================= -->

<script
    src="https://unpkg.com/html5-qrcode"
    type="text/javascript">
</script>


<!-- =========================================================
     QR CODE GENERATOR
========================================================= -->

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js">
</script>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;
    padding: 0;

    box-sizing: border-box;

    font-family:
        "Segoe UI",
        Arial,
        Helvetica,
        sans-serif;

}


/* =========================================================
   BODY
========================================================= */

body {

    min-height: 100vh;

    color: #17324d;

    background:
        linear-gradient(
            135deg,
            #eaf7ff,
            #eefbf5 45%,
            #e9f3ff
        );

    overflow-x: hidden;

}


/* =========================================================
   FLOATING SYMBOLS
========================================================= */

.background-symbols {

    position: fixed;

    inset: 0;

    overflow: hidden;

    pointer-events: none;

    z-index: 0;

}


.med-symbol {

    position: absolute;

    color:
        rgba(13,110,253,.10);

    font-weight: bold;

    animation:
        floatSymbol
        13s ease-in-out infinite;

}


.symbol-1 {

    font-size: 100px;

    left: 3%;

    top: 15%;

}


.symbol-2 {

    font-size: 70px;

    right: 6%;

    top: 22%;

    color:
        rgba(32,201,151,.10);

    animation-delay: -3s;

}


.symbol-3 {

    font-size: 115px;

    left: 10%;

    bottom: 3%;

    animation-delay: -7s;

}


.symbol-4 {

    font-size: 80px;

    right: 15%;

    bottom: 8%;

    animation-delay: -4s;

}


.symbol-5 {

    font-size: 55px;

    left: 47%;

    top: 10%;

    animation-delay: -9s;

}


@keyframes floatSymbol {

    0%,
    100% {

        transform:
            translateY(0)
            rotate(0);

    }

    50% {

        transform:
            translateY(-28px)
            rotate(7deg);

    }

}


/* =========================================================
   HEADER
========================================================= */

.header {

    height: 78px;

    padding:
        0 50px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    color: white;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #087f5b
        );

    box-shadow:
        0 8px 25px
        rgba(13,110,253,.20);

    position: relative;

    z-index: 100;

    overflow: visible;

}


.logo-area {

    display: flex;

    align-items: center;

    gap: 13px;

}


.logo-icon {

    width: 45px;

    height: 45px;

    border-radius: 14px;

    background:
        rgba(255,255,255,.18);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 23px;

}


.logo {

    font-size: 27px;

    font-weight: 800;

    letter-spacing: 2px;

}


.system-name {

    font-size: 11px;

    opacity: .85;

    margin-top: 2px;

}


/* =========================================================
   USER MENU
========================================================= */

.user-area {

    position: relative;

    z-index: 1000;

}


.user-menu-button {

    border: none;

    background:
        rgba(255,255,255,.10);

    color: white;

    cursor: pointer;

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 10px;

    width: 168px;

    min-height: 58px;

    padding:
        7px 10px;

    border-radius: 16px;

    transition:
        0.18s ease;

}


.user-menu-button:hover {

    background:
        rgba(255,255,255,.17);

}


.user-info {

    text-align: right;

    line-height: 1.05;

}


.user-role {

    font-size: 10px;

    font-weight: 700;

    opacity: .82;

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

    flex:
        0 0 43px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    background:
        rgba(255,255,255,.16);

    border:
        1px solid
        rgba(255,255,255,.28);

}


/* =========================================================
   USER DROPDOWN
========================================================= */

.user-dropdown {

    position: absolute;

    right: 0;

    top:
        calc(100% + 1px);

    width: 168px;

    box-sizing: border-box;

    padding:
        14px 9px 9px;

    border-radius: 14px;

    background:
        rgba(255,255,255,.98);

    border:
        1px solid
        rgba(255,255,255,.98);

    box-shadow:
        0 15px 35px
        rgba(20,80,110,.22);

    opacity: 0;

    visibility: hidden;

    pointer-events: none;

    transform:
        translateY(-7px);

    transition:
        0.18s ease;

    overflow: hidden;

    z-index: 99999;

}


.user-dropdown.show {

    opacity: 1;

    visibility: visible;

    pointer-events: auto;

    transform:
        translateY(0);

}


.dropdown-label {

    display: block;

    padding:
        0 10px 2px;

    color:
        #718096;

    font-size: 10px;

    font-weight: 500;

}


.dropdown-name {

    display: block;

    padding:
        0 10px 10px;

    color:
        #273444;

    font-size: 13px;

    font-weight: 700;

}


.logout-button,
.login-history-button {

    display: block;

    width: 100%;

    box-sizing: border-box;

    padding:
        9px 12px;

    border-radius: 8px;

    border-top:
        1px solid
        #e7ebef;

    text-decoration: none;

    text-align: left;

    background:
        transparent;

    font-size: 13px;

    font-weight: 700;

}


.logout-button {

    color:
        #d83232;

}


.logout-button:hover {

    background:
        #fff3f3;

    color:
        #bd2525;

}


.login-history-button {

    color:
        #1683d1;

}


.login-history-button:hover {

    background:
        #eef8ff;

    color:
        #0d6fb8;

}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width: 92%;

    max-width: 1100px;

    margin:
        45px auto;

    position: relative;

    z-index: 2;

}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {

    margin-bottom: 28px;

}


.eyebrow {

    display: inline-flex;

    padding:
        7px 12px;

    border-radius: 30px;

    background:
        rgba(13,110,253,.08);

    color: #0d6efd;

    font-size: 11px;

    font-weight: 700;

    letter-spacing: 1px;

    text-transform: uppercase;

    margin-bottom: 12px;

}


.page-title h1 {

    font-size: 34px;

    margin-bottom: 7px;

}


.page-title p {

    color: #718096;

    font-size: 15px;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background:
        rgba(255,255,255,.93);

    backdrop-filter:
        blur(12px);

    -webkit-backdrop-filter:
        blur(12px);

    border-radius: 22px;

    padding: 30px;

    margin-bottom: 25px;

    border:
        1px solid
        rgba(255,255,255,.85);

    box-shadow:
        0 15px 45px
        rgba(31,78,121,.10);

    overflow: hidden;

    position: relative;

}


.card::before {

    content: "";

    position: absolute;

    top: 0;

    left: 0;

    width: 100%;

    height: 4px;

    background:
        linear-gradient(
            90deg,
            #0d6efd,
            #20c997,
            #2e7d32
        );

}


.card-header {

    display: flex;

    align-items: center;

    gap: 14px;

    margin-bottom: 23px;

}


.card-icon {

    width: 48px;

    height: 48px;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #e5f1ff,
            #e5faf2
        );

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

}


.card-title {

    font-size: 20px;

    font-weight: 750;

}


.card-subtitle {

    color: #8a98a8;

    font-size: 12px;

    margin-top: 3px;

}


/* =========================================================
   MESSAGES
========================================================= */

.success,
.error {

    padding:
        15px 18px;

    border-radius: 13px;

    margin-bottom: 20px;

    font-size: 14px;

    font-weight: 600;

}


.success {

    background:
        #ecfdf3;

    color: #18794e;

    border:
        1px solid #c7f0d8;

}


.error {

    background:
        #fff1f2;

    color: #c62828;

    border:
        1px solid #ffcdd2;

}


/* =========================================================
   SEARCH
========================================================= */

.search-row {

    display: flex;

    gap: 12px;

}


.search-input {

    flex: 1;

    position: relative;

}


.search-input span {

    position: absolute;

    left: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #91a3b4;

}


.search-row input {

    width: 100%;

    padding:
        15px 15px 15px 44px;

    border:
        1px solid #d9e2ec;

    border-radius: 12px;

    background:
        #fafdff;

    outline: none;

    font-size: 14px;

    transition: .25s;

}


.search-row input:focus {

    border-color: #0d6efd;

    background: white;

    box-shadow:
        0 0 0 4px
        rgba(13,110,253,.08);

}


.search-button {

    padding:
        14px 23px;

    border: none;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #087f5b
        );

    color: white;

    font-weight: 700;

    cursor: pointer;

    transition: .25s;

}


.search-button:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(13,110,253,.20);

}


/* =========================================================
   QR SCAN BUTTON
========================================================= */

.qr-button {

    width: 100%;

    margin-top: 14px;

    padding: 14px;

    border:
        1px solid #bce8dc;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #effcf8,
            #edf8ff
        );

    color: #087f5b;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    transition: .25s;

}


.qr-button:hover {

    transform:
        translateY(-1px);

    background:
        #e4f8f1;

    box-shadow:
        0 8px 20px
        rgba(8,127,91,.08);

}


/* =========================================================
   QR SCANNER
========================================================= */

#qr-reader {

    width: 100%;

    max-width: 500px;

    margin:
        22px auto 10px;

    display: none;

    border:
        2px solid #0d6efd;

    border-radius: 16px;

    overflow: hidden;

    background: #071b2d;

}


#qr-reader-results {

    text-align: center;

    font-size: 13px;

    color: #6c7d8c;

    margin-top: 10px;

}


.scanner-close {

    display: none;

    width: 100%;

    max-width: 500px;

    margin: 10px auto;

    padding: 12px;

    border: none;

    border-radius: 10px;

    background:
        #ef4444;

    color: white;

    font-weight: 700;

    cursor: pointer;

}


/* =========================================================
   STUDENT INFORMATION
========================================================= */

.student-info {

    display: grid;

    grid-template-columns:
        repeat(2,1fr);

    gap: 15px;

    margin-bottom: 30px;

}


.info-item {

    padding:
        17px;

    border:
        1px solid #e6edf3;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #fafdff,
            #f4fbf8
        );

}


.info-label {

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: .8px;

    color: #8292a2;

    margin-bottom: 5px;

    font-weight: 700;

}


.info-value {

    font-size: 15px;

    font-weight: 700;

    color: #29445c;

}


/* =========================================================
   ELEGANT STUDENT QR CARD
========================================================= */

.student-qr-card {

    position: relative;

    margin:
        0 0 32px;

    padding:
        28px;

    border-radius: 20px;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #f2f9ff 0%,
            #ffffff 48%,
            #effbf5 100%
        );

    border:
        1px solid
        #dceaf3;

    box-shadow:
        0 12px 30px
        rgba(20,80,120,.08);

}


.student-qr-card::before {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    right: -70px;

    top: -75px;

    background:
        rgba(13,110,253,.07);

}


.student-qr-card::after {

    content: "";

    position: absolute;

    width: 130px;

    height: 130px;

    border-radius: 50%;

    left: -65px;

    bottom: -65px;

    background:
        rgba(32,201,151,.07);

}


.qr-card-content {

    position: relative;

    z-index: 2;

    display: grid;

    grid-template-columns:
        1fr
        auto;

    align-items: center;

    gap: 30px;

}


.qr-card-heading {

    display: flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 10px;

}


.qr-card-icon {

    width: 46px;

    height: 46px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #dceeff,
            #dff8ef
        );

    color:
        #0d6efd;

    font-size: 21px;

    box-shadow:
        0 5px 15px
        rgba(13,110,253,.08);

}


.qr-card-title {

    font-size: 20px;

    font-weight: 800;

    color:
        #20384f;

}


.qr-card-description {

    max-width: 560px;

    color:
        #73869a;

    font-size: 13px;

    line-height: 1.65;

    margin-bottom: 17px;

}


.qr-student-details {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

}


.qr-detail {

    padding:
        9px 13px;

    border-radius: 10px;

    background:
        rgba(255,255,255,.82);

    border:
        1px solid
        #e0eaf1;

}


.qr-detail-label {

    display: block;

    color:
        #91a0ae;

    font-size: 9px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .7px;

    margin-bottom: 3px;

}


.qr-detail-value {

    color:
        #29445c;

    font-size: 12px;

    font-weight: 800;

}


/* =========================================================
   QR CODE DISPLAY
========================================================= */

.qr-display-area {

    display: flex;

    flex-direction: column;

    align-items: center;

    min-width: 215px;

}


.qr-frame {

    width: 190px;

    height: 190px;

    padding: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        white;

    border-radius: 18px;

    border:
        1px solid
        #dce7ee;

    box-shadow:
        0 12px 30px
        rgba(20,80,110,.12);

}


#studentQRCode {

    width: 160px;

    height: 160px;

    display: flex;

    align-items: center;

    justify-content: center;

}


#studentQRCode img,
#studentQRCode canvas {

    width: 160px !important;

    height: 160px !important;

    display: block;

}


.qr-label {

    margin-top: 11px;

    font-size: 10px;

    font-weight: 700;

    color:
        #8192a2;

    letter-spacing: .8px;

    text-transform: uppercase;

}


/* =========================================================
   QR ACTION BUTTONS
========================================================= */

.qr-actions {

    display: flex;

    gap: 9px;

    margin-top: 13px;

}


.qr-action {

    border: none;

    padding:
        10px 13px;

    border-radius: 10px;

    cursor: pointer;

    font-size: 11px;

    font-weight: 800;

    transition:
        .22s ease;

}


.qr-download {

    color: white;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #168bd1
        );

}


.qr-print {

    color:
        #087f5b;

    background:
        #e6f8f1;

    border:
        1px solid
        #c8ebdc;

}


.qr-action:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 7px 17px
        rgba(31,78,121,.13);

}


/* =========================================================
   HISTORY
========================================================= */

.history-section {

    margin-top: 10px;

    margin-bottom: 35px;

}


.history-heading {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 17px;

}


.history-heading-icon {

    width: 36px;

    height: 36px;

    border-radius: 10px;

    background:
        #edf5ff;

    display: flex;

    align-items: center;

    justify-content: center;

}


.history-title {

    font-size: 20px;

    font-weight: 750;

}


.visit-card {

    background:
        #fbfdff;

    border:
        1px solid #e1e9f0;

    border-left:
        4px solid #0d6efd;

    border-radius: 14px;

    padding: 21px;

    margin-bottom: 14px;

    transition: .25s;

}


.visit-card:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 22px
        rgba(31,78,121,.08);

}


.visit-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding-bottom: 13px;

    margin-bottom: 17px;

    border-bottom:
        1px solid #edf1f5;

}


.visit-date {

    font-size: 13px;

    font-weight: 700;

    color: #0d6efd;

}


.visit-number {

    padding:
        6px 11px;

    border-radius: 30px;

    background:
        #edf5ff;

    color: #0d6efd;

    font-size: 11px;

    font-weight: 700;

}


.visit-item {

    margin-bottom: 15px;

}


.visit-label {

    display: block;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .6px;

    color: #8292a2;

    margin-bottom: 5px;

}


.visit-value {

    font-size: 14px;

    color: #40566a;

    line-height: 1.65;

}


.no-history {

    text-align: center;

    padding:
        28px;

    border:
        1px dashed #ccd8e2;

    border-radius: 13px;

    color: #8392a1;

    background:
        #fafdff;

    font-size: 13px;

}


/* =========================================================
   CONSULTATION FORM
========================================================= */

.consultation-divider {

    height: 1px;

    background:
        #edf1f5;

    margin:
        5px 0 28px;

}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2,1fr);

    gap: 20px;

}


.form-group {

    display: flex;

    flex-direction: column;

}


.form-group.full {

    grid-column:
        1 / -1;

}


label {

    font-size: 13px;

    font-weight: 700;

    color: #38536c;

    margin-bottom: 8px;

}


.required {

    color: #e53935;

}


textarea {

    width: 100%;

    min-height: 115px;

    resize: vertical;

    padding:
        14px;

    border:
        1px solid #d9e2ec;

    border-radius: 12px;

    background:
        #fafdff;

    color: #29445c;

    font-size: 14px;

    outline: none;

    transition: .25s;

}


textarea:focus {

    border-color: #0d6efd;

    background: white;

    box-shadow:
        0 0 0 4px
        rgba(13,110,253,.08);

}


textarea::placeholder {

    color: #a5b2bf;

}


/* =========================================================
   SAVE AREA
========================================================= */

.save-area {

    display: flex;

    justify-content: flex-end;

    margin-top: 27px;

    padding-top: 22px;

    border-top:
        1px solid #edf1f5;

}


.save-button {

    padding:
        14px 25px;

    border: none;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #087f5b
        );

    color: white;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    transition: .25s;

    box-shadow:
        0 8px 20px
        rgba(13,110,253,.17);

}


.save-button:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(13,110,253,.24);

}


/* =========================================================
   BACK
========================================================= */

.back-area {

    margin-top: 30px;

}


.back-button {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding:
        12px 18px;

    border-radius: 11px;

    background:
        rgba(255,255,255,.80);

    color: #506274;

    border:
        1px solid #dfe7ee;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

    transition: .25s;

}


.back-button:hover {

    background: white;

    transform:
        translateY(-1px);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 800px) {

    .qr-card-content {

        grid-template-columns: 1fr;

    }


    .qr-display-area {

        min-width: 0;

        margin-top: 10px;

    }


    .student-qr-card {

        padding: 23px;

    }

}


@media (max-width: 750px) {

    .header {

        padding:
            0 20px;

    }


    .logo {

        font-size: 22px;

    }


    .system-name {

        display: none;

    }


    .user-role {

        display: none;

    }


    .container {

        width: 94%;

        margin:
            30px auto;

    }


    .page-title h1 {

        font-size: 27px;

    }


    .card {

        padding: 23px;

        border-radius: 18px;

    }


    .search-row {

        flex-direction: column;

    }


    .search-button {

        width: 100%;

    }


    .student-info {

        grid-template-columns: 1fr;

    }


    .form-grid {

        grid-template-columns: 1fr;

    }


    .form-group.full {

        grid-column: auto;

    }


    .save-button {

        width: 100%;

    }


    .save-area {

        justify-content: stretch;

    }


    .med-symbol {

        opacity: .5;

    }


    .qr-student-details {

        flex-direction: column;

    }


    .qr-detail {

        width: 100%;

    }


    .qr-actions {

        width: 100%;

    }


    .qr-action {

        flex: 1;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     FLOATING BACKGROUND
========================================================= -->

<div class="background-symbols">

    <div class="med-symbol symbol-1">✚</div>

    <div class="med-symbol symbol-2">⚕</div>

    <div class="med-symbol symbol-3">✚</div>

    <div class="med-symbol symbol-4">⚕</div>

    <div class="med-symbol symbol-5">✚</div>

</div>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">

    <div class="logo-area">

        <div class="logo-icon">
            ⚕
        </div>

        <div>

            <div class="logo">
                MEDTRACK
            </div>

            <div class="system-name">
                Clinic Management System
            </div>

        </div>

    </div>


    <div class="user-area">

        <button
            type="button"
            class="user-menu-button"
            id="userMenuButton"
            onclick="toggleUserMenu(event)"
            aria-expanded="false"
        >

            <div class="user-info">

                <div class="user-role">

                    <?php
                    echo htmlspecialchars(
                        $_SESSION["role"]
                    );
                    ?>

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

                <?php

                if (
                    strtolower(
                        (string)$_SESSION["role"]
                    ) === "nurse"
                ) {

                    echo "👩‍⚕️";

                } else {

                    echo "👤";

                }

                ?>

            </div>

        </button>


        <div
            class="user-dropdown"
            id="userDropdown"
        >

            <div class="dropdown-label">
                Signed In As
            </div>

            <div class="dropdown-name">

                <?php
                echo ucfirst(
                    htmlspecialchars(
                        $_SESSION["role"]
                    )
                );
                ?>

            </div>


            <a
                href="logout.php"
                class="logout-button"
                onclick="event.stopPropagation();"
            >
                ↪&nbsp;&nbsp;Logout
            </a>


            <a
                href="login_history.php"
                class="login-history-button"
                onclick="event.stopPropagation();"
            >
                🕘&nbsp;&nbsp;Login History
            </a>

        </div>

    </div>

</header>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="container">


    <!-- =====================================================
         TITLE
    ====================================================== -->

    <div class="page-title">

        <div class="eyebrow">
            ✦ Clinical Services
        </div>

        <h1>
            Consultation
        </h1>

        <p>
            Search for a student, review their clinic history,
            and record a new consultation.
        </p>

    </div>


    <!-- =====================================================
         MESSAGES
    ====================================================== -->

    <?php if ($message !== ""): ?>

        <div class="success">

            ✓

            <?php
            echo htmlspecialchars(
                $message
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">

            ⚠

            <?php
            echo htmlspecialchars(
                $error
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FIND STUDENT
    ====================================================== -->

    <div class="card">


        <div class="card-header">

            <div class="card-icon">
                🔎
            </div>

            <div>

                <div class="card-title">
                    Find Student
                </div>

                <div class="card-subtitle">
                    Search by Student ID or scan their QR code.
                </div>

            </div>

        </div>


        <form
            method="POST"
            action="consultation.php"
        >

            <div class="search-row">


                <div class="search-input">

                    <span>
                        🪪
                    </span>

                    <input
                        type="text"
                        id="student_id"
                        name="student_id"
                        placeholder="Enter Student ID"
                        value="<?php

                            echo isset(
                                $_POST["student_id"]
                            )
                                ? htmlspecialchars(
                                    $_POST["student_id"]
                                )
                                : "";

                        ?>"
                        required
                    >

                </div>


                <button
                    type="submit"
                    name="search_student"
                    class="search-button"
                >

                    🔎
                    Search Student

                </button>


            </div>

        </form>


        <!-- =================================================
             QR SCANNER BUTTON
        ================================================== -->

        <button
            type="button"
            class="qr-button"
            id="startScanner"
            onclick="startQRScanner()"
        >

            📷
            Scan Student QR Code

        </button>


        <div id="qr-reader"></div>


        <div id="qr-reader-results"></div>


        <button
            type="button"
            class="scanner-close"
            id="closeScanner"
            onclick="stopQRScanner()"
        >

            ✕
            Close Scanner

        </button>


    </div>


    <!-- =====================================================
         STUDENT FOUND
    ====================================================== -->

    <?php if ($student !== null): ?>


        <div class="card">


            <!-- =================================================
                 STUDENT INFORMATION
            ================================================== -->

            <div class="card-header">

                <div class="card-icon">
                    🎓
                </div>

                <div>

                    <div class="card-title">
                        Student Information
                    </div>

                    <div class="card-subtitle">
                        Confirm the student's information before consultation.
                    </div>

                </div>

            </div>


            <div class="student-info">


                <div class="info-item">

                    <div class="info-label">
                        Student ID
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["student_id"] ?? ""
                        );
                        ?>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Full Name
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["full_name"] ?? ""
                        );
                        ?>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Course / Program
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["course"] ?? ""
                        );
                        ?>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Year Level
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["year_level"] ?? ""
                        );
                        ?>

                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Contact Number
                    </div>

                    <div class="info-value">

                        <?php
                        echo htmlspecialchars(
                            $student["contact_number"] ?? ""
                        );
                        ?>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 ELEGANT QR CODE
            ================================================== -->

            <div class="student-qr-card">

                <div class="qr-card-content">


                    <!-- LEFT SIDE -->

                    <div>

                        <div class="qr-card-heading">

                            <div class="qr-card-icon">
                                ▦
                            </div>

                            <div>

                                <div class="qr-card-title">
                                    Student QR Code
                                </div>

                            </div>

                        </div>


                        <div class="qr-card-description">

                            This QR code is uniquely linked to this
                            student's ID. It can be scanned from the
                            MEDTRACK consultation page for faster
                            student identification.

                        </div>


                        <div class="qr-student-details">


                            <div class="qr-detail">

                                <span class="qr-detail-label">
                                    Student ID
                                </span>

                                <span class="qr-detail-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $student["student_id"] ?? ""
                                    );
                                    ?>

                                </span>

                            </div>


                            <div class="qr-detail">

                                <span class="qr-detail-label">
                                    Student Name
                                </span>

                                <span class="qr-detail-value">

                                    <?php
                                    echo htmlspecialchars(
                                        $student["full_name"] ?? ""
                                    );
                                    ?>

                                </span>

                            </div>


                        </div>

                    </div>


                    <!-- RIGHT SIDE -->

                    <div class="qr-display-area">


                        <div class="qr-frame">

                            <div id="studentQRCode"></div>

                        </div>


                        <div class="qr-label">
                            MEDTRACK Student Identification
                        </div>


                        <div class="qr-actions">


                            <button
                                type="button"
                                class="qr-action qr-download"
                                onclick="downloadStudentQR()"
                            >

                                ⬇
                                Download

                            </button>


                            <button
                                type="button"
                                class="qr-action qr-print"
                                onclick="printStudentQR()"
                            >

                                🖨
                                Print

                            </button>


                        </div>


                    </div>


                </div>

            </div>


            <!-- =================================================
                 PREVIOUS VISITS
            ================================================== -->

            <div class="history-section">


                <div class="history-heading">

                    <div class="history-heading-icon">
                        🕘
                    </div>

                    <div class="history-title">
                        Previous Visits
                    </div>

                </div>


                <?php if (
                    count($previous_visits) > 0
                ): ?>


                    <?php

                    $visit_number = 1;

                    ?>


                    <?php foreach (
                        $previous_visits
                        as $visit
                    ): ?>


                        <div class="visit-card">


                            <div class="visit-header">


                                <div class="visit-date">

                                    Visit Record #

                                    <?php
                                    echo htmlspecialchars(
                                        $visit["id"] ?? ""
                                    );
                                    ?>

                                </div>


                                <div class="visit-number">

                                    Visit #

                                    <?php
                                    echo htmlspecialchars(
                                        $visit_number
                                    );
                                    ?>

                                </div>


                            </div>


                            <div class="visit-item">

                                <span class="visit-label">
                                    Symptoms / Reason For Visit
                                </span>

                                <div class="visit-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $visit["symptoms"] ?? ""
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="visit-item">

                                <span class="visit-label">
                                    Diagnosis / Assessment
                                </span>

                                <div class="visit-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $visit["diagnosis"] ?? ""
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="visit-item">

                                <span class="visit-label">
                                    Result
                                </span>

                                <div class="visit-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $visit["result"] ?? ""
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="visit-item">

                                <span class="visit-label">
                                    Prescription / Medicine
                                </span>

                                <div class="visit-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $visit["prescription"] ?? ""
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                            <div class="visit-item">

                                <span class="visit-label">
                                    Medication Instructions
                                </span>

                                <div class="visit-value">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $visit[
                                                "medication_instruction"
                                            ] ?? ""
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                        </div>


                        <?php

                        $visit_number++;

                        ?>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="no-history">

                        🩺
                        No Previous Visits Found For This Student.

                    </div>


                <?php endif; ?>


            </div>


            <!-- =================================================
                 NEW CONSULTATION
            ================================================== -->

            <div class="consultation-divider"></div>


            <div class="card-header">

                <div class="card-icon">
                    🩺
                </div>

                <div>

                    <div class="card-title">
                        New Consultation
                    </div>

                    <div class="card-subtitle">
                        Record the student's current clinic visit.
                    </div>

                </div>

            </div>


            <form
                method="POST"
                action="consultation.php"
            >


                <input
                    type="hidden"
                    name="student_id"
                    value="<?php

                        echo htmlspecialchars(
                            $student["student_id"] ?? ""
                        );

                    ?>"
                >


                <div class="form-grid">


                    <!-- SYMPTOMS -->

                    <div class="form-group full">

                        <label for="symptoms">

                            Symptoms / Reason For Visit
                            <span class="required">*</span>

                        </label>

                        <textarea
                            id="symptoms"
                            name="symptoms"
                            placeholder="Describe the student's symptoms or reason for today's visit..."
                            required
                        ></textarea>

                    </div>


                    <!-- DIAGNOSIS -->

                    <div class="form-group full">

                        <label for="diagnosis">

                            Diagnosis / Assessment

                        </label>

                        <textarea
                            id="diagnosis"
                            name="diagnosis"
                            placeholder="Enter diagnosis or clinical assessment..."
                        ></textarea>

                    </div>


                    <!-- RESULT -->

                    <div class="form-group full">

                        <label for="result">

                            Consultation Result

                        </label>

                        <textarea
                            id="result"
                            name="result"
                            placeholder="Enter the result or outcome of the consultation..."
                        ></textarea>

                    </div>


                    <!-- PRESCRIPTION -->

                    <div class="form-group">

                        <label for="prescription">

                            Prescription / Medicine

                        </label>

                        <textarea
                            id="prescription"
                            name="prescription"
                            placeholder="Enter prescribed medicine..."
                        ></textarea>

                    </div>


                    <!-- MEDICATION INSTRUCTIONS -->

                    <div class="form-group">

                        <label for="medication_instruction">

                            Medication Instructions

                        </label>

                        <textarea
                            id="medication_instruction"
                            name="medication_instruction"
                            placeholder="E.g. Take after meals and rest..."
                        ></textarea>

                    </div>


                </div>


                <!-- SAVE -->

                <div class="save-area">

                    <button
                        type="submit"
                        name="save_consultation"
                        class="save-button"
                    >

                        ✓
                        Save Consultation

                    </button>

                </div>


            </form>


        </div>


    <?php endif; ?>


    <!-- =====================================================
         BACK
    ====================================================== -->

    <div class="back-area">


        <?php if (
            $_SESSION["role"] === "admin"
        ): ?>

            <a
                href="admin_dashboard.php"
                class="back-button"
            >

                ←
                Back To Dashboard

            </a>

        <?php else: ?>

            <a
                href="nurse_dashboard.php"
                class="back-button"
            >

                ←
                Back To Dashboard

            </a>

        <?php endif; ?>


    </div>


</main>


<!-- =========================================================
     USER MENU JAVASCRIPT
========================================================= -->

<script>

function toggleUserMenu(event) {

    event.stopPropagation();

    const dropdown =
        document.getElementById(
            "userDropdown"
        );

    const button =
        document.getElementById(
            "userMenuButton"
        );


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
            document.querySelector(
                ".user-area"
            );

        const dropdown =
            document.getElementById(
                "userDropdown"
            );

        const button =
            document.getElementById(
                "userMenuButton"
            );


        if (
            menu &&
            !menu.contains(event.target) &&
            dropdown &&
            button
        ) {

            dropdown.classList.remove(
                "show"
            );

            button.setAttribute(
                "aria-expanded",
                "false"
            );

        }

    }
);


document.addEventListener(
    "keydown",
    function(event) {

        if (event.key !== "Escape") {

            return;

        }


        const dropdown =
            document.getElementById(
                "userDropdown"
            );

        const button =
            document.getElementById(
                "userMenuButton"
            );


        if (dropdown && button) {

            dropdown.classList.remove(
                "show"
            );

            button.setAttribute(
                "aria-expanded",
                "false"
            );

        }

    }
);

</script>


<!-- =========================================================
     QR CODE GENERATION / DOWNLOAD / PRINT
========================================================= -->

<script>

/* =========================================================
   STUDENT DATA FOR QR
========================================================= */

const studentQRData = <?php

if ($student !== null) {

    echo json_encode(
        (string)($student["student_id"] ?? "")
    );

} else {

    echo json_encode("");

}

?>;


const studentQRName = <?php

if ($student !== null) {

    echo json_encode(
        (string)($student["full_name"] ?? "Student")
    );

} else {

    echo json_encode("Student");

}

?>;


/* =========================================================
   GENERATE STUDENT QR
========================================================= */

function generateStudentQR() {

    const qrContainer =
        document.getElementById(
            "studentQRCode"
        );


    if (!qrContainer) {

        return;

    }


    if (
        typeof QRCode === "undefined"
    ) {

        qrContainer.innerHTML =
            "<span style='color:#d83232;font-size:12px;text-align:center;'>Unable To Load QR Generator.</span>";

        return;

    }


    qrContainer.innerHTML = "";


    if (
        studentQRData.trim() === ""
    ) {

        return;

    }


    new QRCode(
        qrContainer,
        {

            text:
                studentQRData,

            width:
                160,

            height:
                160,

            colorDark:
                "#17324d",

            colorLight:
                "#ffffff",

            correctLevel:
                QRCode.CorrectLevel.H

        }
    );

}


/* =========================================================
   DOWNLOAD QR
========================================================= */

function downloadStudentQR() {

    const qrContainer =
        document.getElementById(
            "studentQRCode"
        );


    if (!qrContainer) {

        return;

    }


    const canvas =
        qrContainer.querySelector(
            "canvas"
        );


    const image =
        qrContainer.querySelector(
            "img"
        );


    let imageURL = "";


    if (canvas) {

        imageURL =
            canvas.toDataURL(
                "image/png"
            );

    } else if (image) {

        imageURL =
            image.src;

    }


    if (imageURL === "") {

        alert(
            "The QR Code Is Not Ready Yet."
        );

        return;

    }


    const downloadLink =
        document.createElement(
            "a"
        );


    downloadLink.href =
        imageURL;


    downloadLink.download =
        "MEDTRACK_QR_" +
        studentQRData +
        ".png";


    document.body.appendChild(
        downloadLink
    );


    downloadLink.click();


    document.body.removeChild(
        downloadLink
    );

}


/* =========================================================
   PRINT QR
========================================================= */

function printStudentQR() {

    const qrContainer =
        document.getElementById(
            "studentQRCode"
        );


    if (!qrContainer) {

        return;

    }


    const canvas =
        qrContainer.querySelector(
            "canvas"
        );


    const image =
        qrContainer.querySelector(
            "img"
        );


    let imageURL = "";


    if (canvas) {

        imageURL =
            canvas.toDataURL(
                "image/png"
            );

    } else if (image) {

        imageURL =
            image.src;

    }


    if (imageURL === "") {

        alert(
            "The QR Code Is Not Ready Yet."
        );

        return;

    }


    const printWindow =
        window.open(
            "",
            "_blank",
            "width=650,height=750"
        );


    if (!printWindow) {

        alert(
            "Please Allow Pop-Ups In Your Browser To Print The QR Code."
        );

        return;

    }


    const safeStudentID =
        escapeHTML(
            studentQRData
        );


    const safeStudentName =
        escapeHTML(
            studentQRName
        );


    printWindow.document.write(`

        <!DOCTYPE html>

        <html>

        <head>

            <title>
                MEDTRACK Student QR Code
            </title>

            <style>

                * {

                    box-sizing: border-box;

                }


                body {

                    margin: 0;

                    min-height: 100vh;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    background: #f4f7fb;

                    font-family:
                        "Segoe UI",
                        Arial,
                        sans-serif;

                    color: #17324d;

                }


                .print-card {

                    width: 440px;

                    padding: 40px;

                    background: white;

                    border-radius: 24px;

                    text-align: center;

                    border:
                        1px solid #dce7ee;

                    box-shadow:
                        0 15px 40px
                        rgba(20,80,110,.12);

                }


                .logo {

                    font-size: 26px;

                    font-weight: 900;

                    letter-spacing: 2px;

                    color: #0d6efd;

                    margin-bottom: 4px;

                }


                .subtitle {

                    color: #7a8b9b;

                    font-size: 11px;

                    margin-bottom: 25px;

                }


                .title {

                    font-size: 21px;

                    font-weight: 800;

                    margin-bottom: 8px;

                }


                .description {

                    color: #8292a2;

                    font-size: 12px;

                    line-height: 1.6;

                    margin-bottom: 25px;

                }


                .qr {

                    display: flex;

                    justify-content: center;

                    margin-bottom: 22px;

                }


                .qr img {

                    width: 240px;

                    height: 240px;

                }


                .student-id {

                    font-size: 17px;

                    font-weight: 800;

                    color: #17324d;

                    margin-bottom: 5px;

                }


                .student-name {

                    font-size: 13px;

                    color: #65788a;

                    margin-bottom: 20px;

                }


                .footer {

                    padding-top: 15px;

                    border-top:
                        1px solid #e8eef3;

                    color: #94a2af;

                    font-size: 10px;

                }


                @media print {

                    body {

                        background: white;

                    }


                    .print-card {

                        box-shadow: none;

                        border: none;

                    }

                }

            </style>

        </head>


        <body>

            <div class="print-card">

                <div class="logo">
                    MEDTRACK
                </div>

                <div class="subtitle">
                    Clinic Management System
                </div>


                <div class="title">
                    Student Identification QR
                </div>


                <div class="description">

                    Scan this QR code using the
                    MEDTRACK consultation system
                    to quickly identify the student.

                </div>


                <div class="qr">

                    <img
                        src="${imageURL}"
                        alt="Student QR Code"
                    >

                </div>


                <div class="student-id">

                    ${safeStudentID}

                </div>


                <div class="student-name">

                    ${safeStudentName}

                </div>


                <div class="footer">

                    MEDTRACK Clinic Management System

                </div>

            </div>

        </body>

        </html>

    `);


    printWindow.document.close();


    printWindow.focus();


    setTimeout(
        function() {

            printWindow.print();

        },
        500
    );

}


/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHTML(value) {

    return String(value)

        .replace(
            /&/g,
            "&amp;"
        )

        .replace(
            /</g,
            "&lt;"
        )

        .replace(
            />/g,
            "&gt;"
        )

        .replace(
            /"/g,
            "&quot;"
        )

        .replace(
            /'/g,
            "&#039;"
        );

}


/* =========================================================
   GENERATE WHEN PAGE LOADS
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        if (
            studentQRData &&
            studentQRData.trim() !== ""
        ) {

            generateStudentQR();

        }

    }
);

</script>


<!-- =========================================================
     QR SCANNER JAVASCRIPT
========================================================= -->

<script>

let qrScanner = null;


/* =========================================================
   START SCANNER
========================================================= */

function startQRScanner() {

    const reader =
        document.getElementById(
            "qr-reader"
        );

    const closeButton =
        document.getElementById(
            "closeScanner"
        );

    const startButton =
        document.getElementById(
            "startScanner"
        );


    if (!reader) {

        return;

    }


    reader.style.display =
        "block";


    closeButton.style.display =
        "block";


    startButton.style.display =
        "none";


    qrScanner =
        new Html5Qrcode(
            "qr-reader"
        );


    const config = {

        fps: 10,

        qrbox: {

            width: 250,

            height: 250

        }

    };


    qrScanner.start(

        {
            facingMode:
                "environment"
        },

        config,


        function(
            decodedText,
            decodedResult
        ) {

            document.getElementById(
                "student_id"
            ).value =
                decodedText;


            document.getElementById(
                "qr-reader-results"
            ).innerHTML =
                "✓ QR Code Successfully Scanned.";


            stopQRScanner();


            searchStudentAutomatically();

        },


        function(errorMessage) {

            /* Ignore scanning errors */

        }

    ).catch(function(error) {

        document.getElementById(
            "qr-reader-results"
        ).innerHTML =
            "Unable To Access Camera. Please Allow Camera Permission.";

        console.log(error);

    });

}


/* =========================================================
   STOP SCANNER
========================================================= */

function stopQRScanner() {

    const reader =
        document.getElementById(
            "qr-reader"
        );

    const closeButton =
        document.getElementById(
            "closeScanner"
        );

    const startButton =
        document.getElementById(
            "startScanner"
        );


    if (qrScanner) {

        qrScanner.stop()

            .then(function() {

                qrScanner.clear();

                qrScanner = null;

            })

            .catch(function(error) {

                console.log(error);

                qrScanner = null;

            });

    }


    reader.style.display =
        "none";


    closeButton.style.display =
        "none";


    startButton.style.display =
        "block";

}


/* =========================================================
   AUTOMATIC SEARCH
========================================================= */

function searchStudentAutomatically() {

    const studentID =
        document.getElementById(
            "student_id"
        ).value;


    if (
        studentID.trim() === ""
    ) {

        return;

    }


    const form =
        document.createElement(
            "form"
        );


    form.method =
        "POST";


    form.action =
        "consultation.php";


    const input =
        document.createElement(
            "input"
        );


    input.type =
        "hidden";


    input.name =
        "student_id";


    input.value =
        studentID;


    const search =
        document.createElement(
            "input"
        );


    search.type =
        "hidden";


    search.name =
        "search_student";


    search.value =
        "1";


    form.appendChild(
        input
    );


    form.appendChild(
        search
    );


    document.body.appendChild(
        form
    );


    form.submit();

}

</script>


</body>

</html>