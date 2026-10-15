<?php

session_start();

/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");

    exit();

}


/* =========================================================
   ONLY ADMIN
========================================================= */

if ($_SESSION['role'] !== 'admin') {

    header("Location: nurse_dashboard.php");

    exit();

}


/* =========================================================
   DISPLAY USERNAME
   Preserve existing uppercase/lowercase characters and
   capitalize only the first character.
========================================================= */

$displayUsername = trim((string)($_SESSION['username'] ?? 'Admin'));

if ($displayUsername === '') {

    $displayUsername = 'Admin';

}

$displayUsername =
    strtoupper(substr($displayUsername, 0, 1)) .
    substr($displayUsername, 1);


require_once "Database.php";


/* =========================================================
   WEEKLY CLINIC VISIT DATA
========================================================= */

$weekStart = new DateTime('monday this week');
$weekEnd = new DateTime('sunday this week');

$weekStartSql = $weekStart->format('Y-m-d 00:00:00');
$weekEndSql = $weekEnd->format('Y-m-d 23:59:59');

$dailyVisits = array_fill(0, 7, 0);


/* =========================================================
   DAILY VISITS
========================================================= */

$chartStmt = $conn->prepare(
    "SELECT DATE(consultation_date) AS visit_date,
            COUNT(DISTINCT student_id) AS total_visits
     FROM consultations
     WHERE consultation_date BETWEEN ? AND ?
     GROUP BY DATE(consultation_date)
     ORDER BY visit_date ASC"
);

if ($chartStmt) {

    $chartStmt->bind_param(
        "ss",
        $weekStartSql,
        $weekEndSql
    );

    $chartStmt->execute();

    $chartResult = $chartStmt->get_result();

    while ($chartRow = $chartResult->fetch_assoc()) {

        $visitDate = new DateTime(
            $chartRow['visit_date']
        );

        $dayIndex =
            (int)$visitDate->format('N') - 1;

        $dailyVisits[$dayIndex] =
            (int)$chartRow['total_visits'];

    }

    $chartStmt->close();

}


/* =========================================================
   TOTAL UNIQUE STUDENTS
========================================================= */

$totalUniqueStudentsStmt = $conn->prepare(
    "SELECT COUNT(DISTINCT student_id) AS total_students
     FROM consultations
     WHERE consultation_date BETWEEN ? AND ?"
);

$totalWeeklyStudents = 0;

if ($totalUniqueStudentsStmt) {

    $totalUniqueStudentsStmt->bind_param(
        "ss",
        $weekStartSql,
        $weekEndSql
    );

    $totalUniqueStudentsStmt->execute();

    $totalResult =
        $totalUniqueStudentsStmt->get_result();

    $totalRow =
        $totalResult->fetch_assoc();

    $totalWeeklyStudents =
        (int)($totalRow['total_students'] ?? 0);

    $totalUniqueStudentsStmt->close();

}


$maxVisits = max($dailyVisits);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MEDTRACK - Admin Dashboard</title>
<style>

        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }


        /* =====================================================
           BODY
        ===================================================== */

        body {

            min-height: 100vh;

            color: #172033;

            overflow-x: hidden;

            background:

                radial-gradient(
                    circle at 8% 15%,
                    rgba(0, 174, 255, 0.17),
                    transparent 28%
                ),

                radial-gradient(
                    circle at 92% 12%,
                    rgba(42, 184, 112, 0.16),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 85% 85%,
                    rgba(36, 132, 255, 0.14),
                    transparent 32%
                ),

                radial-gradient(
                    circle at 12% 88%,
                    rgba(0, 190, 160, 0.12),
                    transparent 30%
                ),

                linear-gradient(
                    135deg,
                    #f3fbfa 0%,
                    #edf7ff 48%,
                    #f4fbf7 100%
                );

            position: relative;
        }


        /* =====================================================
           BACKGROUND GRID
        ===================================================== */

        body::before {

            content: "";

            position: fixed;

            inset: 0;

            pointer-events: none;

            background-image:

                linear-gradient(
                    rgba(20, 130, 180, 0.035) 1px,
                    transparent 1px
                ),

                linear-gradient(
                    90deg,
                    rgba(20, 130, 180, 0.035) 1px,
                    transparent 1px
                );

            background-size: 45px 45px;

            mask-image:
                linear-gradient(
                    to bottom,
                    transparent,
                    black 20%,
                    black 80%,
                    transparent
                );

            z-index: 0;
        }


        /* =====================================================
           BACKGROUND GLOWS
        ===================================================== */

        .background-glow {

            position: fixed;

            border-radius: 50%;

            pointer-events: none;

            filter: blur(4px);

            z-index: 0;
        }


        .glow-one {

            width: 300px;
            height: 300px;

            left: -120px;
            top: 90px;

            background:
                rgba(0, 145, 255, 0.12);

            animation:
                floatGlowOne 10s ease-in-out infinite;
        }


        .glow-two {

            width: 380px;
            height: 380px;

            right: -160px;
            bottom: -130px;

            background:
                rgba(0, 180, 125, 0.11);

            animation:
                floatGlowTwo 12s ease-in-out infinite;
        }


        .glow-three {

            width: 200px;
            height: 200px;

            right: 25%;
            top: 15%;

            background:
                rgba(0, 160, 220, 0.07);

            animation:
                floatGlowThree 8s ease-in-out infinite;
        }


        @keyframes floatGlowOne {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(30px, 25px);
            }
        }


        @keyframes floatGlowTwo {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(-25px, -30px);
            }
        }


        @keyframes floatGlowThree {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(-20px, 25px);
            }
        }


        /* =====================================================
           FLOATING MEDICAL SYMBOLS
        ===================================================== */

        .medical-symbol {

            position: fixed;

            width: 58px;
            height: 58px;

            border-radius: 18px;

            display: flex;

            align-items: center;
            justify-content: center;

            color:
                rgba(25, 130, 180, 0.20);

            font-size: 28px;

            background:
                rgba(255,255,255,0.28);

            border:
                1px solid
                rgba(255,255,255,0.65);

            backdrop-filter:
                blur(10px);

            box-shadow:
                0 12px 35px
                rgba(30, 120, 160, 0.07);

            pointer-events: none;

            z-index: 1;
        }


        .symbol-one {

            left: 4%;
            top: 23%;

            animation:
                symbolFloat 7s ease-in-out infinite;
        }


        .symbol-two {

            left: 8%;
            bottom: 13%;

            animation:
                symbolFloat 9s ease-in-out infinite reverse;
        }


        .symbol-three {

            right: 5%;
            top: 25%;

            animation:
                symbolFloat 8s ease-in-out infinite;
        }


        .symbol-four {

            right: 10%;
            bottom: 18%;

            animation:
                symbolFloat 10s ease-in-out infinite reverse;
        }


        @keyframes symbolFloat {

            0%,
            100% {
                transform:
                    translateY(0)
                    rotate(0deg);
            }

            50% {
                transform:
                    translateY(-16px)
                    rotate(4deg);
            }
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .header {

            height: 73px;

            position: relative;

            z-index: 10;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 40px;

            color: white;

            background:

                linear-gradient(
                    110deg,
                    #167fd1,
                    #168ca4 48%,
                    #27833f
                );

            box-shadow:
                0 5px 25px
                rgba(20, 100, 130, 0.20);

            overflow: visible;
        }


        /* Header shine */

        .header::after {

            content: "";

            position: absolute;

            width: 500px;
            height: 500px;

            border-radius: 50%;

            border:
                1px solid
                rgba(255,255,255,0.08);

            right: -200px;
            top: -350px;

            pointer-events: none;
        }


        /* =====================================================
           HEADER LOGO
        ===================================================== */

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

            font-size: 11px;

            margin-top: 6px;

            color:
                rgba(255,255,255,0.82);

            letter-spacing: 0.2px;
        }


        /* =====================================================
           USER AREA
        ===================================================== */

        .user-area {

            display: flex;

            align-items: center;

            gap: 13px;

            position: relative;

            z-index: 2;
        }


        /* =====================================================
           TOP-RIGHT USER DROPDOWN
        ===================================================== */

        .user-menu {

            position: relative;

            z-index: 20;
        }


        .user-menu-button {

            border: none;

            color: white;

            background: transparent;

            cursor: pointer;

            padding: 6px 8px;

            border-radius: 12px;

            transition:
                background 0.2s ease;
        }


        .user-menu-button {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            width: 168px;
            min-height: 58px;
            padding: 7px 10px;
            border-radius: 16px;
        }


        .user-menu-button:hover {

            background:
                rgba(255,255,255,0.17);
        }


        .user-menu-button:focus-visible {

            outline:
                2px solid
                rgba(255,255,255,0.75);

            outline-offset: 3px;
        }


        .user-dropdown {

            position: absolute;

            right: 0;

            top: calc(100% + 9px);

            min-width: 150px;

            padding: 7px;

            border-radius: 12px;

            background:
                rgba(255,255,255,0.97);

            border:
                1px solid
                rgba(255,255,255,0.95);

            box-shadow:
                0 14px 35px
                rgba(20, 80, 110, 0.20);

            opacity: 0;

            visibility: hidden;

            transform:
                translateY(-7px);

            pointer-events: none;

            z-index: 99999;

            transition:
                opacity 0.18s ease,
                transform 0.18s ease,
                visibility 0.18s ease;
        }


        /* OPEN DROPDOWN WHEN THE USER AREA IS CLICKED */

        .user-menu.active .user-dropdown,
        .user-dropdown.show {

            opacity: 1;

            visibility: visible;

            transform:
                translateY(0);

            pointer-events: auto;

            z-index: 99999;
        }


        .user-dropdown.show {

            opacity: 1;

            visibility: visible;

            transform:
                translateY(0);

            pointer-events: auto;

            z-index: 99999;
        }


        .dropdown-logout {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 100%;

            padding: 10px 13px;

            border-radius: 8px;

            text-decoration: none;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #d93636,
                    #c62828
                );

            font-size: 11px;

            font-weight: 700;

            box-shadow:
                0 6px 14px
                rgba(198,40,40,0.15);

            transition:
                transform 0.15s ease,
                background 0.15s ease;
        }


        .dropdown-logout:hover {

            background:
                linear-gradient(
                    135deg,
                    #c62828,
                    #a71919
                );

            transform:
                translateY(-1px);
        }


        .user-info {

            text-align: right;
        }


        .user-role {

            font-size: 11px;

            color:
                rgba(255,255,255,0.75);

            margin-bottom: 2px;
        }


        .user-name {

            font-size: 13px;

            font-weight: 700;

            text-transform: capitalize;
        }


        .user-icon {

            width: 43px;
            height: 43px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 20px;

            background:
                rgba(255,255,255,0.17);

            border:
                1px solid
                rgba(255,255,255,0.20);

            box-shadow:
                inset 0 0 15px
                rgba(255,255,255,0.08);
        }


        /* =====================================================
           MAIN CONTAINER
        ===================================================== */

        .container {

            width: 92%;

            max-width: 1080px;

            margin:
                42px auto 50px;

            position: relative;

            z-index: 5;
        }


        /* =====================================================
           WELCOME AREA
        ===================================================== */

        .welcome {

            position: relative;

            margin-bottom: 27px;

            padding:
                27px 30px;

            border-radius: 20px;

            background:
                rgba(255,255,255,0.63);

            border:
                1px solid
                rgba(255,255,255,0.85);

            backdrop-filter:
                blur(16px);

            box-shadow:
                0 15px 40px
                rgba(30, 110, 140, 0.07);

            overflow: hidden;
        }


        .welcome::before {

            content: "";

            position: absolute;

            width: 150px;
            height: 150px;

            border-radius: 50%;

            background:
                rgba(26, 137, 209, 0.07);

            right: -50px;
            top: -70px;
        }


        .welcome-content {

            position: relative;

            z-index: 2;
        }


        .welcome h1 {

            font-size: 29px;

            color: #172033;

            margin-bottom: 8px;
        }


        .welcome p {

            color: #687789;

            font-size: 13px;

            line-height: 1.6;
        }


        .welcome-line {

            width: 60px;

            height: 4px;

            border-radius: 10px;

            margin-top: 17px;

            background:
                linear-gradient(
                    90deg,
                    #1684d4,
                    #23a99a,
                    #2d903e
                );
        }


        /* =====================================================
           DASHBOARD LAYOUT
        ===================================================== */

        .dashboard {

            display: grid;

            grid-template-columns:
                0.92fr 1.42fr;

            gap: 22px;

            align-items: stretch;
        }


        .modules {

            display: grid;

            grid-template-rows:
                repeat(3, 1fr);

            gap: 18px;

            min-height: 535px;
        }


        /* =====================================================
           WEEKLY GRAPH
        ===================================================== */

        .weekly-graph {

            position: relative;

            min-height: 535px;

            padding: 27px;

            border-radius: 20px;

            background:
                rgba(255,255,255,0.88);

            border:
                1px solid
                rgba(255,255,255,0.95);

            box-shadow:
                0 12px 35px
                rgba(35, 100, 130, 0.09);

            overflow: hidden;
        }


        .weekly-graph::before {

            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            border-radius: 50%;

            background:
                rgba(22, 132, 211, 0.07);

            right: -70px;
            top: -70px;
        }


        .weekly-graph::after {

            content: "";

            position: absolute;

            left: 0;
            bottom: 0;

            width: 100%;
            height: 4px;

            background:
                linear-gradient(
                    90deg,
                    #1684d4,
                    #22a69a,
                    #2d903e
                );
        }


        .graph-title {

            position: relative;

            z-index: 2;

            font-size: 21px;

            font-weight: 700;

            color: #172033;

            margin-bottom: 6px;
        }


        .graph-subtitle {

            position: relative;

            z-index: 2;

            color: #708094;

            font-size: 12px;

            margin-bottom: 18px;
        }


        .chart {

            position: relative;

            z-index: 2;

            height: 345px;

            display: flex;

            align-items: flex-end;

            gap: 11px;

            padding:
                16px 8px 43px 30px;

            border-left:
                1px solid
                rgba(50,100,130,0.12);

            border-bottom:
                1px solid
                rgba(50,100,130,0.12);

            background:
                repeating-linear-gradient(
                    to top,
                    transparent 0,
                    transparent 71px,
                    rgba(30,120,160,0.07) 72px
                );
        }


        .bar-group {

            flex: 1;

            height: 100%;

            display: flex;

            flex-direction: column;

            justify-content: flex-end;

            align-items: center;

            min-width: 34px;
        }


        .bar-value {

            font-size: 12px;

            font-weight: 700;

            color: #1687b7;

            margin-bottom: 7px;
        }


        .bar {

            width: min(42px, 72%);

            min-height: 4px;

            border-radius:
                8px 8px 3px 3px;

            background:
                linear-gradient(
                    to top,
                    #1684d4,
                    #23a99a
                );

            box-shadow:
                0 8px 18px
                rgba(22,131,212,0.16);

            transition:
                height 0.3s ease,
                transform 0.2s ease;
        }


        .bar:hover {

            transform:
                translateY(-4px);
        }


        .bar-day {

            margin-top: 9px;

            font-size: 10px;

            color: #708094;

            font-weight: 700;
        }


        .graph-total {

            position: relative;

            z-index: 2;

            margin-top: 18px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding:
                12px 15px;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #eaf6ff,
                    #eafaf4
                );

            color: #687789;

            font-size: 12px;
        }


        .graph-total strong {

            color: #172033;

            font-size: 17px;
        }


        /* =====================================================
           SMALLER MODULE CARDS
        ===================================================== */

        .modules .card {

            min-height: 0;

            padding: 22px;
        }


        .modules .card-icon {

            width: 47px;
            height: 47px;

            font-size: 23px;

            margin-bottom: 12px;
        }


        .modules .card h2 {

            font-size: 17px;

            margin-bottom: 6px;
        }


        .modules .card p {

            font-size: 11.5px;

            min-height: 0;

            margin-bottom: 13px;
        }


        .modules .btn {

            padding:
                9px 15px;

            font-size: 11.5px;
        }


        .inventory-btn {

            background:
                linear-gradient(
                    135deg,
                    #2d8c40,
                    #218b75
                );

            box-shadow:
                0 7px 18px
                rgba(45, 140, 64, 0.18);
        }


        .inventory-btn:hover {

            background:
                linear-gradient(
                    135deg,
                    #23772f,
                    #197c68
                );
        }


        /* =====================================================
           CARD
        ===================================================== */

        .card {

            position: relative;

            min-height: 255px;

            padding: 29px;

            border-radius: 20px;

            background:
                rgba(255,255,255,0.88);

            border:
                1px solid
                rgba(255,255,255,0.95);

            box-shadow:
                0 12px 35px
                rgba(35, 100, 130, 0.09);

            overflow: hidden;

            transition:
                transform 0.3s ease,
                box-shadow 0.3s ease;

        }


        /* Card colored glow */

        .card::before {

            content: "";

            position: absolute;

            width: 130px;
            height: 130px;

            border-radius: 50%;

            background:
                rgba(22, 132, 211, 0.07);

            right: -55px;
            top: -55px;

            transition:
                transform 0.4s ease;
        }


        .card::after {

            content: "";

            position: absolute;

            left: 0;
            bottom: 0;

            width: 100%;
            height: 4px;

            background:
                linear-gradient(
                    90deg,
                    #1684d4,
                    #22a69a,
                    #2d903e
                );

            transform:
                scaleX(0.15);

            transform-origin: left;

            transition:
                transform 0.35s ease;
        }


        .card:hover {

            transform:
                translateY(-7px);

            box-shadow:
                0 20px 45px
                rgba(30, 110, 140, 0.14);
        }


        .card:hover::before {

            transform:
                scale(1.5);
        }


        .card:hover::after {

            transform:
                scaleX(1);
        }


        /* =====================================================
           CARD ICON
        ===================================================== */

        .card-icon {

            position: relative;

            z-index: 2;

            width: 55px;
            height: 55px;

            border-radius: 15px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 27px;

            margin-bottom: 19px;

            background:
                linear-gradient(
                    135deg,
                    #e3f3ff,
                    #e5faf5
                );

            border:
                1px solid
                rgba(30,150,190,0.08);

            box-shadow:
                0 7px 18px
                rgba(25, 130, 170, 0.08);
        }


        /* =====================================================
           CARD TEXT
        ===================================================== */

        .card h2 {

            position: relative;

            z-index: 2;

            font-size: 19px;

            color: #172033;

            margin-bottom: 9px;
        }


        .card p {

            position: relative;

            z-index: 2;

            color: #708094;

            font-size: 13px;

            line-height: 1.65;

            min-height: 55px;

            max-width: 470px;

            margin-bottom: 20px;
        }


        /* =====================================================
           CARD BUTTON
        ===================================================== */

        .btn {

            position: relative;

            z-index: 3;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                11px 19px;

            border-radius: 9px;

            text-decoration: none;

            color: white;

            font-size: 13px;

            font-weight: 700;

            background:
                linear-gradient(
                    135deg,
                    #1683d4,
                    #198da2
                );

            box-shadow:
                0 7px 18px
                rgba(22, 131, 212, 0.18);

            transition:
                all 0.25s ease;
        }


        .btn:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 10px 22px
                rgba(22, 131, 212, 0.25);

            background:
                linear-gradient(
                    135deg,
                    #0876c8,
                    #168b7b
                );
        }


        /* =====================================================
           HISTORY CARD
        ===================================================== */

        .history-card {

            grid-column: 1 / 2;
        }


        .history-card .card-icon {

            background:
                linear-gradient(
                    135deg,
                    #e5f9ef,
                    #e7f5ff
                );
        }


        .history-btn {

            background:
                linear-gradient(
                    135deg,
                    #2d8c40,
                    #218b75
                );

            box-shadow:
                0 7px 18px
                rgba(45, 140, 64, 0.18);
        }


        .history-btn:hover {

            background:
                linear-gradient(
                    135deg,
                    #23772f,
                    #197c68
                );
        }


        /* =====================================================
           HEARTBEAT DECORATION
        ===================================================== */

        .heartbeat {

            position: absolute;

            bottom: 9px;

            right: 18px;

            width: 145px;

            height: 30px;

            opacity: 0.16;

            overflow: hidden;

            pointer-events: none;
        }


        .heartbeat svg {

            width: 290px;

            height: 30px;

            animation:
                heartbeatMove 3s linear infinite;
        }


        .heartbeat path {

            fill: none;

            stroke: #1b9c9a;

            stroke-width: 2;

        }


        @keyframes heartbeatMove {

            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-145px);
            }
        }


        /* =====================================================
           LOGOUT
        ===================================================== */

        .logout-area {

            display: flex;

            justify-content: center;

            margin-top: 34px;
        }


        .logout {

            min-width: 110px;

            background:
                linear-gradient(
                    135deg,
                    #d93636,
                    #c62828
                );

            box-shadow:
                0 7px 18px
                rgba(198,40,40,0.16);
        }


        .logout:hover {

            background:
                linear-gradient(
                    135deg,
                    #c62828,
                    #a71919
                );

            box-shadow:
                0 10px 22px
                rgba(198,40,40,0.23);
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .footer {

            text-align: center;

            margin-top: 22px;

            color: #91a0ae;

            font-size: 10px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (min-width: 851px) {
            body {
                overflow-y: hidden;
            }
        }


        @media (max-width: 850px) {

            .dashboard {

                grid-template-columns: 1fr;
            }


            .modules {

                min-height: auto;

                grid-template-rows:
                    repeat(3, auto);
            }


            .weekly-graph {

                min-height: 500px;
            }


            .header {

                padding:
                    0 25px;
            }


            .container {

                width: 92%;
            }
        }


        @media (max-width: 600px) {

            body {

                overflow-y: auto;
            }


            .header {

                height: 70px;

                padding:
                    0 18px;
            }


            .logo {

                font-size: 23px;
            }


            .system-name {

                font-size: 9px;
            }


            .user-role {

                display: none;
            }


            .user-name {

                font-size: 12px;
            }


            .user-icon {

                width: 39px;
                height: 39px;

                font-size: 18px;
            }


            .container {

                width: 92%;

                margin:
                    25px auto 35px;
            }


            .welcome {

                padding:
                    22px;
            }


            .welcome h1 {

                font-size: 24px;
            }


            .card {

                min-height: auto;

                padding: 24px;
            }


            .medical-symbol {

                display: none;
            }


            .heartbeat {

                display: none;
            }
        }


        @media (max-width: 400px) {

            .user-info {

                display: none;
            }


            .welcome h1 {

                font-size: 23px;
            }


            .card h2 {

                font-size: 18px;
            }


            .card p {

                font-size: 12px;
            }
        }

    

        /* =====================================================
           ADMIN DROPDOWN - LOGIN HISTORY
        ===================================================== */

        .dropdown-admin-label {
            padding: 8px 10px 9px;
            font-size: 11px;
            color: #718096;
            border-bottom: 1px solid #edf2f7;
            margin-bottom: 6px;
            line-height: 1.25;
        }

        .dropdown-admin-label strong {
            display: block;
            margin-top: 3px;
            color: #1f2937;
            font-size: 13px;
        }

        .dropdown-link {
            display: flex;
            align-items: center;
            width: 100%;
            min-height: 38px;
            padding: 9px 10px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: background 0.18s ease, color 0.18s ease;
            box-sizing: border-box;
        }

        .dropdown-icon {
            width: 22px;
            min-width: 22px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            font-size: 15px;
            line-height: 1;
        }

        .dropdown-history {
            color: #0077c8;
        }

        .dropdown-history:hover {
            background: #eef8ff;
            color: #0064a8;
        }



        /* =====================================================
           NURSE DASHBOARD SIZE MATCH - DESKTOP
           Keeps the admin dashboard at the same compact size
           as the nurse dashboard so the full layout fits.
        ===================================================== */

</style>
</head>
<body>

<div class="background-glow glow-one"></div>
<div class="background-glow glow-two"></div>
<div class="background-glow glow-three"></div>

<div class="medical-symbol symbol-one">✚</div>
<div class="medical-symbol symbol-two">♡</div>
<div class="medical-symbol symbol-three">+</div>
<div class="medical-symbol symbol-four">✚</div>

<header class="header">
    <div class="brand">
        <div class="logo">MEDTRACK</div>
        <div class="system-name">Clinic Management System</div>
    </div>

    <div class="user-menu" id="userMenu">

        <button
            type="button"
            class="user-menu-button"
            onclick="toggleUserMenu(event)"
            aria-expanded="false"
        >

            <div class="user-info">
                <div class="user-role">Administrator</div>
                <div class="user-name"><?php echo htmlspecialchars($displayUsername); ?></div>
            </div>

            <div class="user-icon">👨‍💼</div>

        </button>

        <div class="user-dropdown" id="userDropdown">
            <div class="dropdown-admin-label">
                Signed In As
                <strong>Administrator</strong>
            </div>

            <a
                href="login_history.php"
                class="dropdown-link dropdown-history"
                onclick="event.stopPropagation();"
            >
                <span class="dropdown-icon">🕒</span>
                <span>Login History</span>
            </a>

            <a
                href="logout.php"
                class="dropdown-logout"
                onclick="event.stopPropagation(); return confirm('Are you sure you want to logout?');"
            >
                ↪ Logout
            </a>
        </div>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <div class="welcome-content">
            <h1>Welcome, <span><?php echo htmlspecialchars($displayUsername); ?>!</span></h1>
            <p>
                Manage the MEDTRACK clinic system
                and access all available administrative modules.
            </p>
            <div class="welcome-line"></div>
        </div>
    </section>

    <section class="dashboard">

        <div class="modules">

            <div class="card">
                <div class="card-icon">👨‍🎓</div>
                <h2>Student Registration</h2>
                <p>
                    Register students and manage their clinic information,
                    records, and basic health details.
                </p>
                <a href="registration.php" class="btn">
                    Open Registration →
                </a>
                <div class="heartbeat">
                    <svg viewBox="0 0 320 35" preserveAspectRatio="none">
                        <path d="M0 18 L35 18 L42 18 L48 7 L55 28 L62 18 L92 18 L99 18 L106 10 L113 25 L120 18 L155 18 L162 18 L170 5 L177 30 L185 18 L220 18 L228 18 L235 9 L242 27 L250 18 L285 18 L292 18 L300 7 L307 28 L314 18 L320 18" />
                    </svg>
                </div>
            </div>

            <div class="card">
                <div class="card-icon">💊</div>
                <h2>Medicine Inventory</h2>
                <p>
                    Manage medicines, monitor available stock, and identify
                    items that are running low.
                </p>
                <a href="medicine_inventory.php" class="btn inventory-btn">
                    Open Inventory →
                </a>
                <div class="heartbeat">
                    <svg viewBox="0 0 320 35" preserveAspectRatio="none">
                        <path d="M0 18 L35 18 L42 18 L48 7 L55 28 L62 18 L92 18 L99 18 L106 10 L113 25 L120 18 L155 18 L162 18 L170 5 L177 30 L185 18 L220 18 L228 18 L235 9 L242 27 L250 18 L285 18 L292 18 L300 7 L307 28 L314 18 L320 18" />
                    </svg>
                </div>
            </div>

            <div class="card">
                <div class="card-icon">📊</div>
                <h2>Reports</h2>
                <p>
                    View and generate clinic consultation and medicine
                    reports for administrative monitoring.
                </p>
                <a href="reports.php" class="btn">
                    Open Reports →
                </a>
                <div class="heartbeat">
                    <svg viewBox="0 0 320 35" preserveAspectRatio="none">
                        <path d="M0 18 L35 18 L42 18 L48 7 L55 28 L62 18 L92 18 L99 18 L106 10 L113 25 L120 18 L155 18 L162 18 L170 5 L177 30 L185 18 L220 18 L228 18 L235 9 L242 27 L250 18 L285 18 L292 18 L300 7 L307 28 L314 18 L320 18" />
                    </svg>
                </div>
            </div>

        </div>

        <div class="weekly-graph">
            <div class="graph-title">Students Who Visited the Clinic</div>
            <div class="graph-subtitle">Clinic visits for this week</div>

            <div class="chart">
                <?php
                $day_labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                $chartMax = max($dailyVisits);
                if ($chartMax < 1) {
                    $chartMax = 1;
                }
                foreach ($dailyVisits as $index => $count):
                    $barHeight = max(4, ($count / $chartMax) * 245);
                ?>
                    <div class="bar-group">
                        <div class="bar-value"><?php echo $count; ?></div>
                        <div
                            class="bar"
                            style="height: <?php echo $barHeight; ?>px;"
                            title="<?php echo $count; ?> student visit(s)"
                        ></div>
                        <div class="bar-day">
                            <?php echo $day_labels[$index]; ?>
                            <span>
                                <?php
                                $dayDate = clone $weekStart;
                                $dayDate->modify("+{$index} day");
                                echo $dayDate->format('M j');
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="graph-total">
                <span>Total students who visited this week</span>
                <strong><?php echo $totalWeeklyStudents; ?></strong>
            </div>
        </div>

    </section>

    <div class="footer">
        MEDTRACK Clinic Management System
    </div>

</main>

<script>
function toggleUserMenu(event) {
    if (event) {
        event.stopPropagation();
    }

    const menu = document.getElementById("userMenu");
    const button = document.querySelector(".user-menu-button");

    if (!menu) {
        return;
    }

    const isOpen = menu.classList.toggle("active");

    if (button) {
        button.setAttribute("aria-expanded", isOpen ? "true" : "false");
    }
}

document.addEventListener("click", function () {
    const menu = document.getElementById("userMenu");
    const button = document.querySelector(".user-menu-button");

    if (menu) {
        menu.classList.remove("active");
    }

    if (button) {
        button.setAttribute("aria-expanded", "false");
    }
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        const menu = document.getElementById("userMenu");
        const button = document.querySelector(".user-menu-button");

        if (menu) {
            menu.classList.remove("active");
        }

        if (button) {
            button.setAttribute("aria-expanded", "false");
        }
    }
});
</script>

</body>
</html>
