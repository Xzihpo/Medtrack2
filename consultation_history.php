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

$student = null;

$previous_visits = [];

$error = "";


/* =========================================================
   SEARCH STUDENT
========================================================= */

if (isset($_POST["search_student"])) {

    $student_id = trim($_POST["student_id"] ?? "");


    if ($student_id === "") {

        $error = "Please Enter A Student ID.";

    } else {

        /* =====================================================
           FIND STUDENT
        ===================================================== */

        $stmt = $conn->prepare(
            "SELECT
                id,
                student_id,
                full_name,
                course,
                year_level,
                contact_number,
                address,
                created_at
             FROM students
             WHERE student_id = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $error =
                "Database Error: Unable To Search For Student.";

        } else {

            $stmt->bind_param(
                "s",
                $student_id
            );


            $stmt->execute();


            $result = $stmt->get_result();


            if ($result->num_rows === 1) {

                /* =================================================
                   STUDENT FOUND
                ================================================= */

                $student = $result->fetch_assoc();


                /* =================================================
                   GET CONSULTATION HISTORY
                ================================================= */

                $history_stmt = $conn->prepare(
                    "SELECT
                        id,
                        student_id,
                        symptoms,
                        diagnosis,
                        result,
                        prescription,
                        medication_instruction,
                        created_at
                     FROM consultations
                     WHERE student_id = ?
                     ORDER BY created_at DESC, id DESC"
                );


                if (!$history_stmt) {

                    $error =
                        "Database Error: Unable To Load Consultation History.";

                } else {

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
                    "Student Not Found. Please Register The Student First.";

            }


            $stmt->close();

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

<title>MEDTRACK - Consultation History</title>


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


html {

    scroll-behavior: smooth;

}


body {

    min-height: 100vh;

    color: #183247;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(76, 201, 240, 0.18),
            transparent 28%
        ),
        radial-gradient(
            circle at 90% 15%,
            rgba(72, 202, 228, 0.15),
            transparent 28%
        ),
        radial-gradient(
            circle at 80% 90%,
            rgba(67, 170, 139, 0.12),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #eef9fc 0%,
            #f5fbfd 45%,
            #eef8f4 100%
        );

    overflow-x: hidden;

}


/* =========================================================
   FLOATING MEDICAL SYMBOLS
========================================================= */

.medical-symbols {

    position: fixed;

    inset: 0;

    pointer-events: none;

    overflow: hidden;

    z-index: 0;

}


.symbol {

    position: absolute;

    display: flex;

    align-items: center;

    justify-content: center;

    color: rgba(25, 118, 150, 0.10);

    font-size: 34px;

    font-weight: 300;

    animation:
        floatSymbol
        linear
        infinite;

    user-select: none;

}


/* Different positions */

.symbol.one {

    left: 5%;

    top: 22%;

    font-size: 42px;

    animation-duration: 18s;

}


.symbol.two {

    right: 7%;

    top: 35%;

    font-size: 30px;

    animation-duration: 22s;

    animation-delay: -5s;

}


.symbol.three {

    left: 12%;

    bottom: 12%;

    font-size: 28px;

    animation-duration: 20s;

    animation-delay: -8s;

}


.symbol.four {

    right: 14%;

    bottom: 18%;

    font-size: 38px;

    animation-duration: 24s;

    animation-delay: -12s;

}


@keyframes floatSymbol {

    0% {

        transform:
            translateY(30px)
            rotate(0deg);

    }

    50% {

        transform:
            translateY(-35px)
            rotate(8deg);

    }

    100% {

        transform:
            translateY(30px)
            rotate(0deg);

    }

}


/* =========================================================
   HEADER
========================================================= */

.header {

    position: relative;

    z-index: 5;

    min-height: 82px;

    padding: 0 50px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    color: white;

    background:
        linear-gradient(
            120deg,
            #087ea4 0%,
            #1597b8 42%,
            #38a98a 100%
        );

    box-shadow:
        0 8px 30px
        rgba(20, 111, 140, 0.20);

    overflow: hidden;

}


/* Header glow */

.header::after {

    content: "";

    position: absolute;

    width: 280px;

    height: 280px;

    right: -80px;

    top: -180px;

    border-radius: 50%;

    background:
        rgba(255,255,255,0.10);

}


/* =========================================================
   LOGO
========================================================= */

.logo-area {

    position: relative;

    z-index: 2;

}


.logo {

    font-size: 29px;

    font-weight: 800;

    letter-spacing: 3px;

}


.system-name {

    margin-top: 3px;

    font-size: 12px;

    letter-spacing: 0.8px;

    opacity: 0.88;

}


/* =========================================================
   USER AREA
========================================================= */

.user-area {

    position: relative;

    z-index: 2;

    display: flex;

    align-items: center;

    gap: 14px;

}


.user-info {

    text-align: right;

}


.user-role {

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 1px;

    opacity: 0.78;

}


.user-name {

    margin-top: 3px;

    font-size: 15px;

    font-weight: 700;

    text-transform: capitalize;

}


.user-icon {

    width: 46px;

    height: 46px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    background:
        rgba(255,255,255,0.16);

    border:
        1px solid
        rgba(255,255,255,0.28);

    box-shadow:
        inset 0 1px 0
        rgba(255,255,255,0.18),
        0 5px 15px
        rgba(0,0,0,0.10);

    backdrop-filter: blur(8px);

}


/* =========================================================
   MAIN CONTAINER
========================================================= */

.container {

    position: relative;

    z-index: 2;

    width: 90%;

    max-width: 1080px;

    margin: 45px auto 60px;

}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {

    margin-bottom: 28px;

    animation:
        fadeUp
        0.6s
        ease
        both;

}


.page-title h1 {

    color: #153f56;

    font-size: 32px;

    font-weight: 750;

    letter-spacing: -0.6px;

    margin-bottom: 8px;

}


.page-title p {

    color: #66808e;

    font-size: 15px;

    line-height: 1.6;

}


.page-title::after {

    content: "";

    display: block;

    width: 55px;

    height: 4px;

    margin-top: 15px;

    border-radius: 20px;

    background:
        linear-gradient(
            90deg,
            #1198bd,
            #42b883
        );

}


/* =========================================================
   CARD
========================================================= */

.card {

    position: relative;

    background:
        rgba(255,255,255,0.88);

    border:
        1px solid
        rgba(255,255,255,0.90);

    border-radius: 22px;

    padding: 30px;

    margin-bottom: 25px;

    box-shadow:
        0 12px 35px
        rgba(38, 91, 110, 0.09);

    backdrop-filter: blur(14px);

    animation:
        fadeUp
        0.7s
        ease
        both;

}


.card::before {

    content: "";

    position: absolute;

    left: 0;

    top: 25px;

    bottom: 25px;

    width: 4px;

    border-radius: 0 10px 10px 0;

    background:
        linear-gradient(
            180deg,
            #159fbe,
            #42b883
        );

}


.card-title {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #173f54;

    font-size: 20px;

    font-weight: 750;

    margin-bottom: 22px;

}


.card-title::before {

    content: "✦";

    width: 32px;

    height: 32px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    color: #ffffff;

    font-size: 14px;

    background:
        linear-gradient(
            135deg,
            #129bc0,
            #43b789
        );

    box-shadow:
        0 5px 12px
        rgba(18,155,192,0.20);

}


/* =========================================================
   SEARCH
========================================================= */

.search-row {

    display: flex;

    gap: 12px;

}


.search-row input {

    flex: 1;

    min-width: 0;

    padding: 15px 17px;

    border:
        1px solid
        #d5e3e8;

    border-radius: 12px;

    color: #214355;

    background:
        rgba(248,252,253,0.95);

    font-size: 14px;

    outline: none;

    transition:
        0.25s ease;

}


.search-row input::placeholder {

    color: #9aadb6;

}


.search-row input:focus {

    border-color: #159bbd;

    background: #ffffff;

    box-shadow:
        0 0 0 4px
        rgba(21,155,189,0.10),
        0 6px 18px
        rgba(30,100,120,0.06);

}


.search-button {

    min-width: 155px;

    padding: 14px 22px;

    border: none;

    border-radius: 12px;

    color: white;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    background:
        linear-gradient(
            135deg,
            #0786ae,
            #19a1bd
        );

    box-shadow:
        0 7px 18px
        rgba(10,137,174,0.22);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;

}


.search-button:hover {

    transform: translateY(-2px);

    box-shadow:
        0 10px 24px
        rgba(10,137,174,0.28);

}


.search-button:active {

    transform: translateY(0);

}


/* =========================================================
   ERROR
========================================================= */

.error {

    display: flex;

    align-items: center;

    gap: 11px;

    color: #a52d3b;

    background:
        linear-gradient(
            135deg,
            #fff4f5,
            #fffafb
        );

    border:
        1px solid
        #f2c8ce;

    border-left:
        5px solid
        #e25b6b;

    padding: 15px 17px;

    border-radius: 13px;

    margin-bottom: 20px;

    font-size: 14px;

    box-shadow:
        0 6px 18px
        rgba(190,60,80,0.07);

}


/* =========================================================
   STUDENT INFORMATION
========================================================= */

.student-info {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 12px;

    padding: 18px;

    border:
        1px solid
        #dcebef;

    border-radius: 17px;

    margin-bottom: 32px;

    background:
        linear-gradient(
            135deg,
            #f4fbfd,
            #f2faf7
        );

}


.info-item {

    position: relative;

    padding: 17px;

    border-radius: 13px;

    background:
        rgba(255,255,255,0.76);

    border:
        1px solid
        rgba(220,235,239,0.8);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;

}


.info-item:hover {

    transform: translateY(-2px);

    box-shadow:
        0 7px 18px
        rgba(40,100,115,0.08);

}


.info-label {

    color: #7a929d;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.7px;

    margin-bottom: 7px;

}


.info-value {

    color: #24485b;

    font-size: 15px;

    font-weight: 700;

    line-height: 1.5;

    word-break: break-word;

}


/* Student ID special */

.info-item:first-child {

    border-left:
        3px solid
        #20a1bf;

}


/* Name special */

.info-item:nth-child(2) {

    border-left:
        3px solid
        #43b486;

}


/* =========================================================
   HISTORY TITLE
========================================================= */

.history-title {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #173f54;

    font-size: 22px;

    font-weight: 750;

    margin-bottom: 19px;

}


.history-title::before {

    content: "♡";

    width: 34px;

    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    color: #ffffff;

    font-size: 16px;

    background:
        linear-gradient(
            135deg,
            #e36d86,
            #e89a72
        );

}


/* =========================================================
   VISIT CARD
========================================================= */

.visit-card {

    position: relative;

    padding: 23px;

    margin-bottom: 17px;

    border:
        1px solid
        #dce9ed;

    border-left:
        5px solid
        #18a0bd;

    border-radius: 17px;

    background:
        linear-gradient(
            135deg,
            #ffffff,
            #f9fcfd
        );

    box-shadow:
        0 7px 20px
        rgba(38,89,107,0.07);

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;

}


.visit-card:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 12px 28px
        rgba(38,89,107,0.11);

}


/* Alternate visit colors */

.visit-card:nth-of-type(2n) {

    border-left-color: #45ae87;

}


.visit-card:nth-of-type(3n) {

    border-left-color: #8c82d8;

}


/* =========================================================
   VISIT HEADER
========================================================= */

.visit-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    flex-wrap: wrap;

    gap: 12px;

    padding-bottom: 15px;

    margin-bottom: 18px;

    border-bottom:
        1px solid
        #e5eef1;

}


.visit-date {

    color: #246079;

    font-size: 14px;

    font-weight: 700;

}


.visit-date::before {

    content: "◷";

    margin-right: 7px;

    color: #159dbd;

}


.visit-number {

    padding: 6px 12px;

    border-radius: 30px;

    color: #13718c;

    background:
        #e6f7fb;

    border:
        1px solid
        #c6ebf2;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.5px;

}


/* =========================================================
   VISIT ITEMS
========================================================= */

.visit-item {

    position: relative;

    margin-bottom: 16px;

    padding: 14px 16px;

    border-radius: 12px;

    background:
        #f8fbfc;

    border:
        1px solid
        #edf3f5;

}


.visit-item:last-child {

    margin-bottom: 0;

}


/* Different subtle colors */

.visit-item:nth-child(2) {

    background:
        linear-gradient(
            135deg,
            #f3fbfd,
            #f8fcfd
        );

}


.visit-item:nth-child(3) {

    background:
        linear-gradient(
            135deg,
            #f3faf7,
            #f9fcfa
        );

}


.visit-item:nth-child(4) {

    background:
        linear-gradient(
            135deg,
            #f7f5fd,
            #fbfaff
        );

}


.visit-item:nth-child(5) {

    background:
        linear-gradient(
            135deg,
            #fff8f4,
            #fffbf8
        );

}


.visit-label {

    display: block;

    color: #6f8995;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.6px;

    margin-bottom: 6px;

}


.visit-value {

    color: #294957;

    font-size: 14px;

    line-height: 1.65;

    word-break: break-word;

}


/* =========================================================
   NO HISTORY
========================================================= */

.no-history {

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    min-height: 150px;

    padding: 25px;

    text-align: center;

    color: #7c929c;

    font-size: 14px;

    border:
        1px dashed
        #cbdde2;

    border-radius: 16px;

    background:
        linear-gradient(
            135deg,
            #f7fbfc,
            #f5faf8
        );

}


.no-history::before {

    content: "♡";

    display: flex;

    align-items: center;

    justify-content: center;

    width: 43px;

    height: 43px;

    margin-bottom: 10px;

    border-radius: 50%;

    color: #24a0bb;

    background:
        #e8f7fa;

    font-size: 20px;

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

    padding: 12px 19px;

    color: #37606e;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

    border:
        1px solid
        #d5e4e8;

    border-radius: 11px;

    background:
        rgba(255,255,255,0.75);

    box-shadow:
        0 5px 15px
        rgba(50,100,115,0.06);

    transition:
        0.2s ease;

}


.back-button:hover {

    color: #087d9e;

    background: #ffffff;

    border-color: #a9dce7;

    transform:
        translateX(-3px);

}


/* =========================================================
   ANIMATION
========================================================= */

@keyframes fadeUp {

    from {

        opacity: 0;

        transform:
            translateY(15px);

    }

    to {

        opacity: 1;

        transform:
            translateY(0);

    }

}


/* =========================================================
   SCROLLBAR
========================================================= */

::-webkit-scrollbar {

    width: 9px;

}


::-webkit-scrollbar-track {

    background: #edf6f8;

}


::-webkit-scrollbar-thumb {

    border-radius: 20px;

    background:
        linear-gradient(
            180deg,
            #52b8ca,
            #55ad8b
        );

}


::-webkit-scrollbar-thumb:hover {

    background:
        #168ca8;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 800px) {

    .header {

        padding:
            0 25px;

    }


    .container {

        width: 92%;

        margin-top: 32px;

    }


    .student-info {

        grid-template-columns:
            1fr;

    }

}


@media (max-width: 650px) {

    .header {

        min-height: 78px;

        padding:
            12px 18px;

    }


    .logo {

        font-size: 23px;

    }


    .system-name {

        font-size: 10px;

    }


    .user-role {

        display: none;

    }


    .user-name {

        font-size: 13px;

    }


    .user-icon {

        width: 40px;

        height: 40px;

        font-size: 18px;

    }


    .container {

        width: 94%;

        margin:
            28px auto 45px;

    }


    .page-title h1 {

        font-size: 27px;

    }


    .card {

        padding: 22px 18px;

        border-radius: 18px;

    }


    .search-row {

        flex-direction: column;

    }


    .search-button {

        width: 100%;

    }


    .student-info {

        padding: 12px;

    }


    .info-item {

        padding: 14px;

    }


    .visit-card {

        padding: 17px;

    }


    .visit-header {

        align-items: flex-start;

        flex-direction: column;

    }


    .symbol.one {

        left: 2%;

    }


    .symbol.two {

        right: 2%;

    }


    .symbol.three {

        left: 5%;

    }


    .symbol.four {

        right: 5%;

    }

}


/* =========================================================
   REDUCE MOTION FOR ACCESSIBILITY
========================================================= */

@media (prefers-reduced-motion: reduce) {

    * {

        animation-duration: 0.01ms !important;

        animation-iteration-count: 1 !important;

        scroll-behavior: auto !important;

        transition-duration: 0.01ms !important;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     FLOATING MEDICAL SYMBOLS
========================================================= -->

<div class="medical-symbols">

    <div class="symbol one">✚</div>

    <div class="symbol two">✚</div>

    <div class="symbol three">＋</div>

    <div class="symbol four">✦</div>

</div>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="header">


    <div class="logo-area">

        <div class="logo">

            MEDTRACK

        </div>


        <div class="system-name">

            Clinic Management System

        </div>

    </div>


    <div class="user-area">


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
                $_SESSION["role"] === "nurse"
            ) {

                echo "👩‍⚕️";

            } else {

                echo "👤";

            }

            ?>

        </div>


    </div>


</div>



<!-- =========================================================
     MAIN
========================================================= -->

<div class="container">


    <!-- PAGE TITLE -->

    <div class="page-title">

        <h1>

            Consultation History

        </h1>


        <p>

            Search for a student to view their previous clinic visits.

        </p>

    </div>



    <!-- ERROR -->

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



    <!-- =====================================================
         SEARCH STUDENT
    ===================================================== -->

    <div class="card">


        <div class="card-title">

            Find Student

        </div>


        <form
            method="POST"
            action="consultation_history.php"
        >


            <div class="search-row">


                <input
                    type="text"
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


                <button
                    type="submit"
                    name="search_student"
                    class="search-button"
                >

                    🔎 Search Student

                </button>


            </div>


        </form>


    </div>



    <?php if ($student !== null): ?>


    <!-- =====================================================
         STUDENT INFORMATION
    ===================================================== -->

    <div class="card">


        <div class="card-title">

            Student Information

        </div>


        <div class="student-info">


            <!-- STUDENT ID -->

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



            <!-- FULL NAME -->

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



            <!-- COURSE -->

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



            <!-- YEAR LEVEL -->

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



            <!-- CONTACT -->

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



            <!-- ADDRESS -->

            <div class="info-item">

                <div class="info-label">

                    Address

                </div>


                <div class="info-value">

                    <?php

                    echo htmlspecialchars(
                        $student["address"] ?? ""
                    );

                    ?>

                </div>

            </div>


        </div>



        <!-- =================================================
             HISTORY
        ================================================= -->

        <div class="history-title">

            Previous Visits

        </div>



        <?php if (
            count($previous_visits) > 0
        ): ?>


            <?php

            $visit_number =
                count($previous_visits);

            ?>


            <?php foreach (
                $previous_visits
                as $visit
            ): ?>


                <div class="visit-card">


                    <div class="visit-header">


                        <!-- DATE -->

                        <div class="visit-date">

                            <?php

                            if (
                                !empty(
                                    $visit["created_at"]
                                )
                            ) {

                                echo date(
                                    "F d, Y - h:i A",
                                    strtotime(
                                        $visit["created_at"]
                                    )
                                );

                            } else {

                                echo "Date Not Available";

                            }

                            ?>

                        </div>


                        <!-- VISIT NUMBER -->

                        <div class="visit-number">

                            Visit #

                            <?php

                            echo htmlspecialchars(
                                $visit_number
                            );

                            ?>

                        </div>


                    </div>



                    <!-- SYMPTOMS -->

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



                    <!-- DIAGNOSIS -->

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



                    <!-- RESULT -->

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



                    <!-- PRESCRIPTION -->

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



                    <!-- MEDICATION INSTRUCTIONS -->

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

                $visit_number--;

                ?>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="no-history">

                No Previous Visits Found For This Student.

            </div>


        <?php endif; ?>


    </div>


    <?php endif; ?>



    <!-- =====================================================
         BACK TO DASHBOARD
    ===================================================== -->

    <div class="back-area">


        <?php if (
            $_SESSION["role"] === "admin"
        ): ?>


            <a
                href="admin_dashboard.php"
                class="back-button"
            >

                ← Back To Dashboard

            </a>


        <?php else: ?>


            <a
                href="nurse_dashboard.php"
                class="back-button"
            >

                ← Back To Dashboard

            </a>


        <?php endif; ?>


    </div>


</div>


</body>

</html>