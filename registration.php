<?php

session_start();

require_once "Database.php";

/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"])) {
    header("Location: index.php");
    exit();
}

/* =========================================================
   ALLOW ADMIN AND NURSE
========================================================= */

if (
    $_SESSION["role"] !== "admin" &&
    $_SESSION["role"] !== "nurse"
) {
    header("Location: index.php");
    exit();
}

$message = "";
$error = "";

$registered_student = null;

/* =========================================================
   MANUAL STUDENT REGISTRATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "manual_register"
) {

    $student_id =
        trim($_POST["student_id"] ?? "");

    $full_name =
        trim($_POST["full_name"] ?? "");

    $course =
        trim($_POST["course"] ?? "");

    $year_level =
        trim($_POST["year_level"] ?? "");

    $contact_number =
        trim($_POST["contact_number"] ?? "");

    $address =
        trim($_POST["address"] ?? "");

    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $student_id === "" ||
        $full_name === ""
    ) {

        $error =
            "Student ID and Full Name are required.";

    } else {

        /* =================================================
           CHECK EXISTING STUDENT
        ================================================= */

        $check = $conn->prepare(
            "SELECT id
             FROM students
             WHERE student_id = ?
             LIMIT 1"
        );

        if ($check) {

            $check->bind_param(
                "s",
                $student_id
            );

            $check->execute();

            $result =
                $check->get_result();

            if ($result->num_rows > 0) {

                $error =
                    "This Student ID is already registered.";

            } else {

                /* =========================================
                   INSERT STUDENT
                ========================================= */

                $stmt = $conn->prepare(
                    "INSERT INTO students
                    (
                        student_id,
                        full_name,
                        course,
                        year_level,
                        contact_number,
                        address
                    )
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "ssssss",
                        $student_id,
                        $full_name,
                        $course,
                        $year_level,
                        $contact_number,
                        $address
                    );

                    if ($stmt->execute()) {

                        $message =
                            "Student Successfully Registered.";

                        $registered_student = [

                            "student_id" =>
                                $student_id,

                            "full_name" =>
                                $full_name,

                            "course" =>
                                $course,

                            "year_level" =>
                                $year_level,

                            "contact_number" =>
                                $contact_number,

                            "address" =>
                                $address

                        ];

                    } else {

                        $error =
                            "Unable to Register Student.";

                    }

                    $stmt->close();

                } else {

                    $error =
                        "Unable to Prepare Student Registration.";

                }
            }

            $check->close();

        } else {

            $error =
                "Unable to Check Student Registration.";

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

<title>MEDTRACK - Student Registration</title>

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

    background:
        linear-gradient(
            135deg,
            #eaf7ff 0%,
            #eefbf5 45%,
            #e8f3ff 100%
        );

    color: #17324d;

    overflow-x: hidden;

    position: relative;

}

/* =========================================================
   BACKGROUND SYMBOLS
========================================================= */

.background-symbols {

    position: fixed;

    inset: 0;

    pointer-events: none;

    overflow: hidden;

    z-index: 0;

}

.med-symbol {

    position: absolute;

    color:
        rgba(25, 118, 210, 0.10);

    font-weight: bold;

    user-select: none;

    animation:
        floatSymbol
        12s ease-in-out infinite;

}

.symbol-1 {

    font-size: 90px;

    left: 4%;

    top: 18%;

}

.symbol-2 {

    font-size: 65px;

    right: 8%;

    top: 28%;

    color:
        rgba(46, 125, 50, 0.10);

    animation-delay: -3s;

}

.symbol-3 {

    font-size: 110px;

    left: 15%;

    bottom: 5%;

    animation-delay: -6s;

}

.symbol-4 {

    font-size: 75px;

    right: 18%;

    bottom: 12%;

    color:
        rgba(0, 150, 136, 0.09);

    animation-delay: -2s;

}

.symbol-5 {

    font-size: 55px;

    left: 48%;

    top: 12%;

    animation-delay: -8s;

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
            translateY(-25px)
            rotate(8deg);

    }

}

/* =========================================================
   HEADER
========================================================= */

.header {

    height: 78px;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #087f5b
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 50px;

    box-shadow:
        0 8px 25px
        rgba(13, 110, 253, 0.20);

    position: relative;

    z-index: 20;

}

/* =========================================================
   LOGO
========================================================= */

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
        rgba(255,255,255,0.18);

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
   USER AREA
========================================================= */

.user-area {

    display: flex;

    align-items: center;

    position: relative;

}

.user-menu {

    position: relative;

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        7px 9px 7px 13px;

    border-radius: 14px;

    cursor: pointer;

    user-select: none;

}

.user-menu:hover,
.user-menu.active {

    background:
        rgba(255,255,255,.12);

}

.user-info {

    text-align: right;

}

.user-role {

    font-size: 10px;

    opacity: .80;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-bottom: 2px;

}

.user-name {

    font-size: 15px;

    font-weight: 700;

    text-transform: capitalize;

}

.user-icon {

    width: 44px;

    height: 44px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.18);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    border:
        1px solid
        rgba(255,255,255,.20);

}

/* =========================================================
   USER DROPDOWN
========================================================= */

.user-dropdown {

    position: absolute;

    top:
        calc(100% + 10px);

    right: 0;

    width: 190px;

    padding: 8px;

    border-radius: 15px;

    background:
        rgba(255,255,255,.98);

    box-shadow:
        0 18px 40px
        rgba(25,80,105,.18);

    opacity: 0;

    visibility: hidden;

    transform:
        translateY(-7px);

    pointer-events: none;

    transition:
        .2s ease;

}

.user-menu.active .user-dropdown {

    opacity: 1;

    visibility: visible;

    transform:
        translateY(0);

    pointer-events: auto;

}

.dropdown-user-label {

    padding:
        9px 11px 10px;

    font-size: 11px;

    color: #718096;

    border-bottom:
        1px solid #edf2f7;

    margin-bottom: 5px;

}

.dropdown-user-label strong {

    display: block;

    margin-top: 3px;

    color: #26364a;

    font-size: 13px;

}

.dropdown-link,
.dropdown-logout {

    display: flex;

    align-items: center;

    gap: 10px;

    width: 100%;

    padding:
        10px 11px;

    border-radius: 10px;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

}

.dropdown-link {

    color: #0877c9;

}

.dropdown-link:hover {

    background:
        #eef8ff;

}

.dropdown-logout {

    color: #c62828;

}

.dropdown-logout:hover {

    background:
        #fff1f1;

}

/* =========================================================
   MAIN
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

.page-title .eyebrow {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding:
        7px 12px;

    background:
        rgba(13,110,253,.08);

    color: #0d6efd;

    border-radius: 30px;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 1px;

    margin-bottom: 12px;

}

.page-title h1 {

    font-size: 34px;

    color: #17324d;

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
        rgba(255,255,255,.94);

    backdrop-filter:
        blur(12px);

    border-radius: 22px;

    padding: 34px;

    border:
        1px solid
        rgba(255,255,255,.8);

    box-shadow:
        0 15px 45px
        rgba(31, 78, 121, .10);

    position: relative;

    overflow: hidden;

    margin-bottom: 28px;

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

/* =========================================================
   CARD HEADER
========================================================= */

.card-header {

    display: flex;

    align-items: center;

    gap: 14px;

    margin-bottom: 27px;

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

    font-size: 22px;

}

.card-title {

    font-size: 21px;

    font-weight: 750;

    color: #17324d;

}

.card-subtitle {

    font-size: 12px;

    color: #8795a5;

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

    display: flex;

    align-items: center;

    gap: 10px;

}

.success {

    background:
        linear-gradient(
            135deg,
            #ecfdf3,
            #e5f8ed
        );

    color: #18794e;

    border:
        1px solid #c7f0d8;

}

.error {

    background:
        linear-gradient(
            135deg,
            #fff1f2,
            #ffe8ea
        );

    color: #c62828;

    border:
        1px solid #ffcdd2;

}

/* =========================================================
   FORM
========================================================= */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 23px;

}

.form-group {

    display: flex;

    flex-direction: column;

}

label {

    color: #38536c;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 8px;

}

.required {

    color: #e53935;

}

.input-wrap {

    position: relative;

}

.input-icon {

    position: absolute;

    left: 14px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #8aa1b5;

    font-size: 16px;

    pointer-events: none;

}

input,
select {

    width: 100%;

    padding:
        14px 15px;

    border:
        1px solid #d9e2ec;

    border-radius: 12px;

    background:
        #fafdff;

    color: #263f55;

    font-size: 14px;

    outline: none;

    transition:
        .25s ease;

}

.input-wrap input,
.input-wrap select {

    padding-left: 43px;

}

input::placeholder {

    color: #a5b2bf;

}

input:hover,
select:hover {

    border-color: #b9cbd9;

}

input:focus,
select:focus {

    border-color: #0d6efd;

    background: white;

    box-shadow:
        0 0 0 4px
        rgba(13,110,253,.09);

}

/* =========================================================
   FORM FOOTER
========================================================= */

.form-footer {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-top: 32px;

    padding-top: 25px;

    border-top:
        1px solid #edf1f5;

}

.required-note {

    color: #8795a5;

    font-size: 12px;

}

.buttons {

    display: flex;

    gap: 12px;

    align-items: center;

}

/* =========================================================
   BUTTONS
========================================================= */

.btn {

    border: none;

    border-radius: 12px;

    padding:
        13px 21px;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    transition:
        .25s ease;

}

.btn-register {

    color: white;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #087f5b
        );

    box-shadow:
        0 8px 18px
        rgba(13,110,253,.18);

}

.btn-register:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 25px
        rgba(13,110,253,.25);

}

.btn-back {

    background:
        #f0f4f8;

    color: #506274;

    border:
        1px solid #e0e7ed;

}

.btn-back:hover {

    background:
        #e4ebf1;

}

/* =========================================================
   QR SUCCESS CARD
========================================================= */

.qr-success-card {

    margin-top: 25px;

    padding: 25px;

    border-radius: 18px;

    background:
        linear-gradient(
            135deg,
            #f5fbff,
            #f1fbf6
        );

    border:
        1px solid #d7e9e2;

}

.qr-success-header {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 20px;

}

.qr-success-icon {

    width: 43px;

    height: 43px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #e2f0ff,
            #e1f7ed
        );

    font-size: 20px;

}

.qr-success-title {

    font-size: 18px;

    font-weight: 800;

    color: #17324d;

}

.qr-success-subtitle {

    margin-top: 3px;

    color: #7b8b9b;

    font-size: 12px;

}

.qr-content {

    display: grid;

    grid-template-columns:
        260px
        1fr;

    gap: 28px;

    align-items: center;

}

.qr-code-box {

    background: white;

    border:
        1px solid #dfe8ee;

    border-radius: 16px;

    padding: 18px;

    display: flex;

    align-items: center;

    justify-content: center;

}

#qrcode {

    width: 220px;

    height: 220px;

    display: flex;

    align-items: center;

    justify-content: center;

}

#qrcode img,
#qrcode canvas {

    max-width: 220px;

    max-height: 220px;

}

.student-qr-info {

    display: flex;

    flex-direction: column;

    gap: 12px;

}

.qr-info-row {

    padding:
        12px 14px;

    border-radius: 11px;

    background:
        rgba(255,255,255,.78);

    border:
        1px solid #e4edf2;

}

.qr-info-label {

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .7px;

    font-weight: 800;

    color: #8191a1;

    margin-bottom: 4px;

}

.qr-info-value {

    font-size: 14px;

    font-weight: 700;

    color: #28445b;

}

.qr-student-id {

    color: #0d6efd;

    font-size: 17px;

}

.qr-note {

    padding:
        12px 14px;

    border-radius: 11px;

    background:
        #eef8ff;

    color: #4d6b83;

    font-size: 12px;

    line-height: 1.55;

}

.qr-actions {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 17px;

}

.btn-qr-download,
.btn-qr-print {

    border: none;

    border-radius: 10px;

    padding:
        11px 16px;

    font-size: 12px;

    font-weight: 800;

    cursor: pointer;

}

.btn-qr-download {

    color: white;

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #087f5b
        );

}

.btn-qr-print {

    color: #416176;

    background: #edf3f7;

    border:
        1px solid #dce6ed;

}

/* =========================================================
   RESPONSIVE
========================================================= */

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

        padding: 24px;

    }

    .form-grid {

        grid-template-columns: 1fr;

        gap: 18px;

    }

    .form-footer {

        flex-direction: column;

        align-items: stretch;

        gap: 20px;

    }

    .buttons {

        flex-direction: column;

        width: 100%;

    }

    .btn {

        width: 100%;

    }

    .qr-content {

        grid-template-columns: 1fr;

    }

    .qr-code-box {

        width: 100%;

    }

}

@media (max-width: 450px) {

    .user-info {

        display: none;

    }

}

/* =========================================================
   PRINT QR
========================================================= */

@media print {

    body {

        background: white !important;

    }

    .background-symbols,
    .header,
    .page-title,
    .card-header,
    .success,
    .error,
    form,
    .form-footer {

        display: none !important;

    }

    .container {

        width: 100%;

        max-width: none;

        margin: 0;

    }

    .card {

        padding: 0;

        box-shadow: none;

        border: none;

        background: white;

    }

    .qr-success-card {

        display: block !important;

        margin: 0;

        padding: 30px;

        border: none;

        box-shadow: none;

        background: white;

    }

    .qr-actions {

        display: none !important;

    }

}

</style>

</head>

<body>

<!-- =========================================================
     BACKGROUND
========================================================= -->

<div class="background-symbols">

    <div class="med-symbol symbol-1">✚</div>

    <div class="med-symbol symbol-2">✚</div>

    <div class="med-symbol symbol-3">⚕</div>

    <div class="med-symbol symbol-4">✚</div>

    <div class="med-symbol symbol-5">⚕</div>

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

        <div
            class="user-menu"
            id="userMenu"
            onclick="toggleUserMenu(event)"
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

                    echo htmlspecialchars(
                        $_SESSION["username"]
                    );

                    ?>

                </div>

            </div>

            <div class="user-icon">

                <?php

                if (
                    $_SESSION["role"] === "nurse"
                ) {

                    echo "👩‍⚕️";

                } else {

                    echo "👤";

                }

                ?>

            </div>

            <div
                class="user-dropdown"
                id="userDropdown"
            >

                <div class="dropdown-user-label">

                    Signed In As

                    <strong>

                        <?php

                        if (
                            $_SESSION["role"] === "admin"
                        ) {

                            echo "Administrator";

                        } else {

                            echo "Nurse";

                        }

                        ?>

                    </strong>

                </div>

                <a
                    href="login_history.php"
                    class="dropdown-link"
                    onclick="event.stopPropagation();"
                >

                    <span>◷</span>

                    <span>
                        Login History
                    </span>

                </a>

                <a
                    href="logout.php"
                    class="dropdown-logout"
                    onclick="event.stopPropagation();"
                >

                    <span>↪</span>

                    <span>
                        Logout
                    </span>

                </a>

            </div>

        </div>

    </div>

</header>

<!-- =========================================================
     MAIN
========================================================= -->

<main class="container">

    <div class="page-title">

        <div class="eyebrow">
            ✦ Student Management
        </div>

        <h1>
            Student Registration
        </h1>

        <p>
            Register individual students and create their MEDTRACK QR code.
        </p>

    </div>

    <!-- =====================================================
         REGISTRATION CARD
    ====================================================== -->

    <div class="card">

        <div class="card-header">

            <div class="card-icon">
                🎓
            </div>

            <div>

                <div class="card-title">
                    Register One Student
                </div>

                <div class="card-subtitle">
                    Register an individual student in the MEDTRACK system.
                </div>

            </div>

        </div>

        <?php if ($message !== ""): ?>

            <div class="success">

                <span>✓</span>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $message
                    );

                    ?>

                </span>

            </div>

        <?php endif; ?>

        <?php if ($error !== ""): ?>

            <div class="error">

                <span>⚠</span>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $error
                    );

                    ?>

                </span>

            </div>

        <?php endif; ?>

        <!-- =================================================
             QR CODE AFTER REGISTRATION
        ================================================== -->

        <?php if ($registered_student !== null): ?>

            <div class="qr-success-card">

                <div class="qr-success-header">

                    <div class="qr-success-icon">
                        ▦
                    </div>

                    <div>

                        <div class="qr-success-title">
                            Student QR Code Created
                        </div>

                        <div class="qr-success-subtitle">
                            This QR code is linked to the student's ID.
                        </div>

                    </div>

                </div>

                <div class="qr-content">

                    <div>

                        <div class="qr-code-box">

                            <div id="qrcode"></div>

                        </div>

                        <div class="qr-actions">

                            <button
                                type="button"
                                class="btn-qr-download"
                                onclick="downloadQRCode()"
                            >

                                ↓
                                Download QR

                            </button>

                            <button
                                type="button"
                                class="btn-qr-print"
                                onclick="window.print()"
                            >

                                🖨
                                Print QR

                            </button>

                        </div>

                    </div>

                    <div class="student-qr-info">

                        <div class="qr-info-row">

                            <div class="qr-info-label">
                                Student ID
                            </div>

                            <div class="qr-info-value qr-student-id">

                                <?php

                                echo htmlspecialchars(
                                    $registered_student["student_id"]
                                );

                                ?>

                            </div>

                        </div>

                        <div class="qr-info-row">

                            <div class="qr-info-label">
                                Full Name
                            </div>

                            <div class="qr-info-value">

                                <?php

                                echo htmlspecialchars(
                                    $registered_student["full_name"]
                                );

                                ?>

                            </div>

                        </div>

                        <?php if (
                            $registered_student["course"] !== ""
                        ): ?>

                            <div class="qr-info-row">

                                <div class="qr-info-label">
                                    Course / Program
                                </div>

                                <div class="qr-info-value">

                                    <?php

                                    echo htmlspecialchars(
                                        $registered_student["course"]
                                    );

                                    ?>

                                </div>

                            </div>

                        <?php endif; ?>

                        <?php if (
                            $registered_student["year_level"] !== ""
                        ): ?>

                            <div class="qr-info-row">

                                <div class="qr-info-label">
                                    Year Level
                                </div>

                                <div class="qr-info-value">

                                    <?php

                                    echo htmlspecialchars(
                                        $registered_student["year_level"]
                                    );

                                    ?>

                                </div>

                            </div>

                        <?php endif; ?>

                        <div class="qr-note">

                            📌 <strong>Important:</strong>

                            The QR code contains the Student ID only.
                            The nurse can scan it during consultation
                            to find the student's MEDTRACK record.

                        </div>

                    </div>

                </div>

            </div>

            <div
                class="form-footer"
                style="margin-top:25px;"
            >

                <div class="required-note">

                    ✓
                    Student registration is complete.

                </div>

                <div class="buttons">

                    <a
                        href="registration.php"
                        class="btn btn-back"
                    >

                        +
                        Register Another Student

                    </a>

                    <?php if (
                        $_SESSION["role"] === "admin"
                    ): ?>

                        <a
                            href="admin_dashboard.php"
                            class="btn btn-register"
                        >

                            ←
                            Back to Dashboard

                        </a>

                    <?php else: ?>

                        <a
                            href="nurse_dashboard.php"
                            class="btn btn-register"
                        >

                            ←
                            Back to Dashboard

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php else: ?>

            <form
                method="POST"
                action="registration.php"
            >

                <input
                    type="hidden"
                    name="action"
                    value="manual_register"
                >

                <div class="form-grid">

                    <!-- STUDENT ID -->

                    <div class="form-group">

                        <label for="student_id">

                            Student ID
                            <span class="required">*</span>

                        </label>

                        <div class="input-wrap">

                            <span class="input-icon">
                                🪪
                            </span>

                            <input
                                type="text"
                                id="student_id"
                                name="student_id"
                                placeholder="Enter Student ID"
                                required
                            >

                        </div>

                    </div>

                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label for="full_name">

                            Full Name
                            <span class="required">*</span>

                        </label>

                        <div class="input-wrap">

                            <span class="input-icon">
                                👤
                            </span>

                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                placeholder="Enter student's full name"
                                required
                            >

                        </div>

                    </div>

                    <!-- COURSE -->

                    <div class="form-group">

                        <label for="course">
                            Course / Program
                        </label>

                        <div class="input-wrap">

                            <span class="input-icon">
                                🎓
                            </span>

                            <input
                                type="text"
                                id="course"
                                name="course"
                                placeholder="E.g. BS Information Technology"
                            >

                        </div>

                    </div>

                    <!-- YEAR LEVEL -->

                    <div class="form-group">

                        <label for="year_level">
                            Year Level
                        </label>

                        <div class="input-wrap">

                            <span class="input-icon">
                                📚
                            </span>

                            <select
                                id="year_level"
                                name="year_level"
                            >

                                <option value="">
                                    Select Year Level
                                </option>

                                <option value="Grade 11">
                                    Grade 11
                                </option>

                                <option value="Grade 12">
                                    Grade 12
                                </option>

                                <option value="College - 1st Year">
                                    College - 1st Year
                                </option>

                                <option value="College - 2nd Year">
                                    College - 2nd Year
                                </option>

                                <option value="College - 3rd Year">
                                    College - 3rd Year
                                </option>

                                <option value="College - 4th Year">
                                    College - 4th Year
                                </option>

                                <option value="College - 5th Year">
                                    College - 5th Year
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- CONTACT -->

                    <div class="form-group">

                        <label for="contact_number">
                            Contact Number
                        </label>

                        <div class="input-wrap">

                            <span class="input-icon">
                                ☎
                            </span>

                            <input
                                type="text"
                                id="contact_number"
                                name="contact_number"
                                placeholder="Enter contact number"
                            >

                        </div>

                    </div>

                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label for="address">
                            Address
                        </label>

                        <div class="input-wrap">

                            <span class="input-icon">
                                📍
                            </span>

                            <input
                                type="text"
                                id="address"
                                name="address"
                                placeholder="Enter student's address"
                            >

                        </div>

                    </div>

                </div>

                <div class="form-footer">

                    <div class="required-note">

                        <span class="required">*</span>

                        Required fields

                    </div>

                    <div class="buttons">

                        <?php if (
                            $_SESSION["role"] === "admin"
                        ): ?>

                            <a
                                href="admin_dashboard.php"
                                class="btn btn-back"
                            >

                                ←
                                Back to Dashboard

                            </a>

                        <?php else: ?>

                            <a
                                href="nurse_dashboard.php"
                                class="btn btn-back"
                            >

                                ←
                                Back to Dashboard

                            </a>

                        <?php endif; ?>

                        <button
                            type="submit"
                            class="btn btn-register"
                        >

                            ✓
                            Register Student

                        </button>

                    </div>

                </div>

            </form>

        <?php endif; ?>

    </div>

</main>

<!-- =========================================================
     QR CODE LIBRARY
========================================================= -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>

/* =========================================================
   USER MENU
========================================================= */

function toggleUserMenu(event) {

    event.stopPropagation();

    const menu =
        document.getElementById("userMenu");

    if (menu) {

        menu.classList.toggle("active");

    }

}

document.addEventListener(
    "click",
    function () {

        const menu =
            document.getElementById("userMenu");

        if (menu) {

            menu.classList.remove("active");

        }

    }
);

/* =========================================================
   QR CODE GENERATION
========================================================= */

<?php if ($registered_student !== null): ?>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const qrContainer =
            document.getElementById("qrcode");

        if (!qrContainer) {

            return;

        }

        const studentId =
            <?php

            echo json_encode(
                $registered_student["student_id"]
            );

            ?>;

        qrContainer.innerHTML = "";

        new QRCode(
            qrContainer,
            {

                text: studentId,

                width: 220,

                height: 220,

                colorDark: "#17324d",

                colorLight: "#ffffff",

                correctLevel:
                    QRCode.CorrectLevel.H

            }
        );

    }
);

<?php endif; ?>

/* =========================================================
   DOWNLOAD QR
========================================================= */

function downloadQRCode() {

    const qrContainer =
        document.getElementById("qrcode");

    if (!qrContainer) {

        return;

    }

    const studentId =
        <?php

        if ($registered_student !== null) {

            echo json_encode(
                $registered_student["student_id"]
            );

        } else {

            echo "null";

        }

        ?>;

    if (!studentId) {

        return;

    }

    const canvas =
        qrContainer.querySelector("canvas");

    if (canvas) {

        const link =
            document.createElement("a");

        link.download =
            "MEDTRACK_QR_" +
            studentId +
            ".png";

        link.href =
            canvas.toDataURL("image/png");

        link.click();

        return;

    }

    const image =
        qrContainer.querySelector("img");

    if (image) {

        const link =
            document.createElement("a");

        link.download =
            "MEDTRACK_QR_" +
            studentId +
            ".png";

        link.href =
            image.src;

        link.click();

    }

}

</script>

</body>

</html>