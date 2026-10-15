<?php

session_start();

/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET['logout']) && $_GET['logout'] === '1') {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: index.php');
    exit();
}


/* =========================================================
   CHECK LOGIN
========================================================= */

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role'])
) {

    header('Location: index.php');
    exit();
}


/* =========================================================
   NURSE ONLY
========================================================= */

if (
    strtolower((string)$_SESSION['role']) !== 'nurse'
) {

    header('Location: admin_dashboard.php');
    exit();
}


/* =========================================================
   USERNAME
========================================================= */

$username =
    trim((string)($_SESSION['username'] ?? 'Nurse'));

if ($username === '') {

    $username = 'Nurse';

}

$display_username =
    ucfirst($username);


/* =========================================================
   DATABASE
========================================================= */

require_once 'Database.php';


/* =========================================================
   WEEKLY CLINIC GRAPH
========================================================= */

date_default_timezone_set('Asia/Manila');

$weekly_dates = [];

$weekly_counts =
    array_fill(0, 7, 0);


$week_start =
    new DateTime('monday this week');

$week_start->setTime(0, 0, 0);


for ($i = 0; $i < 7; $i++) {

    $day =
        clone $week_start;

    $day->modify("+{$i} days");

    $weekly_dates[$i] =
        $day;
}


/* =========================================================
   FIND DATE COLUMN
========================================================= */

$date_column = null;

$consultation_columns = [];


$columns_result =
    $conn->query(
        'SHOW COLUMNS FROM consultations'
    );


if ($columns_result) {

    while (
        $column =
        $columns_result->fetch_assoc()
    ) {

        $consultation_columns[] =
            $column['Field'];

    }
}


$date_options = [

    'consultation_date',
    'visit_date',
    'date',
    'created_at',
    'consultation_time',
    'visit_time'

];


foreach (
    $date_options
    as $option
) {

    foreach (
        $consultation_columns
        as $actual_column
    ) {

        if (
            strcasecmp(
                $option,
                $actual_column
            ) === 0
        ) {

            $date_column =
                $actual_column;

            break 2;

        }

    }

}


/* =========================================================
   CHECK STUDENT ID COLUMN
========================================================= */

$has_student_id = false;


foreach (
    $consultation_columns
    as $column
) {

    if (
        strcasecmp(
            $column,
            'student_id'
        ) === 0
    ) {

        $has_student_id = true;

        break;

    }

}


/* =========================================================
   GET WEEKLY VISITS
========================================================= */

if ($date_column !== null) {

    $safe_date_column =
        '`' .
        str_replace(
            '`',
            '``',
            $date_column
        ) .
        '`';


    $next_week =
        clone $week_start;

    $next_week->modify('+7 days');


    if ($has_student_id) {

        $sql = "
            SELECT
                DATE($safe_date_column) AS visit_date,
                COUNT(DISTINCT student_id) AS total
            FROM consultations
            WHERE $safe_date_column >= ?
              AND $safe_date_column < ?
            GROUP BY DATE($safe_date_column)
            ORDER BY DATE($safe_date_column) ASC
        ";

    } else {

        $sql = "
            SELECT
                DATE($safe_date_column) AS visit_date,
                COUNT(*) AS total
            FROM consultations
            WHERE $safe_date_column >= ?
              AND $safe_date_column < ?
            GROUP BY DATE($safe_date_column)
            ORDER BY DATE($safe_date_column) ASC
        ";

    }


    $stmt =
        $conn->prepare($sql);


    if ($stmt) {

        $start_value =
            $week_start->format(
                'Y-m-d H:i:s'
            );


        $end_value =
            $next_week->format(
                'Y-m-d H:i:s'
            );


        $stmt->bind_param(
            'ss',
            $start_value,
            $end_value
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        if ($result) {

            while (
                $row =
                $result->fetch_assoc()
            ) {

                $visit_date =
                    $row['visit_date'] ?? '';

                $total =
                    (int)(
                        $row['total'] ?? 0
                    );


                foreach (
                    $weekly_dates
                    as $index => $day
                ) {

                    if (
                        $visit_date ===
                        $day->format(
                            'Y-m-d'
                        )
                    ) {

                        $weekly_counts[$index] =
                            $total;

                        break;

                    }

                }

            }

        }


        $stmt->close();

    }

}


$total_weekly_visits =
    array_sum(
        $weekly_counts
    );


$max_count =
    max($weekly_counts);


if ($max_count < 1) {

    $max_count = 1;

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
    MEDTRACK - Nurse Dashboard
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;
    padding: 0;

    box-sizing: border-box;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


html,
body {

    min-height: 100%;

}


/* =========================================================
   BODY
========================================================= */

body {

    min-height: 100vh;

    color: #172033;

    overflow-x: hidden;

    background:

        radial-gradient(
            circle at 7% 23%,
            rgba(0, 174, 255, 0.17),
            transparent 25%
        ),

        radial-gradient(
            circle at 92% 16%,
            rgba(42, 184, 112, 0.15),
            transparent 28%
        ),

        radial-gradient(
            circle at 84% 88%,
            rgba(36, 132, 255, 0.12),
            transparent 30%
        ),

        radial-gradient(
            circle at 12% 87%,
            rgba(0, 190, 160, 0.10),
            transparent 28%
        ),

        linear-gradient(
            135deg,
            #f2fbfa 0%,
            #edf7ff 48%,
            #f4fbf7 100%
        );

}


body::before {

    content: "";

    position: fixed;

    inset: 0;

    pointer-events: none;

    z-index: 0;

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

    background-size:
        45px 45px;

}


/* =========================================================
   BACKGROUND GLOWS
========================================================= */

.glow {

    position: fixed;

    border-radius: 50%;

    pointer-events: none;

    z-index: 0;

}


.glow-one {

    width: 310px;

    height: 310px;

    left: -125px;

    top: 85px;

    background:
        rgba(0, 145, 255, 0.10);

    animation:
        glowOne
        10s
        ease-in-out
        infinite;

}


.glow-two {

    width: 380px;

    height: 380px;

    right: -150px;

    bottom: -120px;

    background:
        rgba(0, 180, 125, 0.10);

    animation:
        glowTwo
        12s
        ease-in-out
        infinite;

}


@keyframes glowOne {

    0%,
    100% {

        transform:
            translate(0, 0);

    }

    50% {

        transform:
            translate(25px, 22px);

    }

}


@keyframes glowTwo {

    0%,
    100% {

        transform:
            translate(0, 0);

    }

    50% {

        transform:
            translate(-25px, -25px);

    }

}


/* =========================================================
   FLOATING MEDICAL SYMBOLS
========================================================= */

.medical-symbol {

    position: fixed;

    width: 58px;

    height: 58px;

    border-radius: 17px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,0.25);

    border:
        1px solid
        rgba(255,255,255,0.65);

    color:
        rgba(25,130,180,0.20);

    font-size: 28px;

    backdrop-filter:
        blur(8px);

    box-shadow:
        0 10px 30px
        rgba(30,120,160,0.06);

    pointer-events: none;

    z-index: 1;

}


.symbol-one {

    left: 4%;

    top: 22%;

    animation:
        symbolFloat
        7s
        ease-in-out
        infinite;

}


.symbol-two {

    left: 8%;

    bottom: 13%;

    animation:
        symbolFloat
        9s
        ease-in-out
        infinite reverse;

}


.symbol-three {

    right: 5%;

    top: 24%;

    animation:
        symbolFloat
        8s
        ease-in-out
        infinite;

}


.symbol-four {

    right: 10%;

    bottom: 18%;

    animation:
        symbolFloat
        10s
        ease-in-out
        infinite reverse;

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

    padding:
        0 40px;

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

    border:
        1px solid
        rgba(255,255,255,0.08);

    right: -210px;

    top: -350px;

    pointer-events: none;

}


/* =========================================================
   BRAND
========================================================= */

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

    color:
        rgba(255,255,255,0.82);

}


/* =========================================================
   USER MENU
========================================================= */

.user-menu {

    position: relative;

    z-index: 1000;

}


.user-menu-button {

    border: none;

    background:
        rgba(255,255,255,0.10);

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
        rgba(255,255,255,0.17);

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

    flex:
        0 0 43px;

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

}


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
        rgba(255,255,255,0.98);

    border:
        1px solid
        rgba(255,255,255,0.98);

    box-shadow:
        0 15px 35px
        rgba(20,80,110,0.22);

    opacity: 0;

    visibility: hidden;

    pointer-events: none;

    transform:
        translateY(-7px);

    transition:
        0.18s ease;

    overflow: hidden;

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

    color: #718096;

    font-size: 10px;

    font-weight: 500;

}


.dropdown-name {

    display: block;

    padding:
        0 10px 10px;

    color: #273444;

    font-size: 13px;

    font-weight: 700;

}


.login-history-button {

    display: block;

    width: 100%;

    box-sizing: border-box;

    margin-top: 0;

    padding:
        9px 12px;

    border-radius: 8px;

    border-top:
        1px solid #e7ebef;

    text-decoration: none;

    text-align: left;

    color: #1683d1;

    background: transparent;

    font-size: 13px;

    font-weight: 700;

}


.login-history-button:hover {

    background:
        #eef8ff;

    color:
        #0d6fb8;

}


.logout-button {

    display: block;

    width: 100%;

    box-sizing: border-box;

    margin-top: 0;

    padding:
        9px 12px;

    border-radius: 8px;

    border-top:
        1px solid #e7ebef;

    text-decoration: none;

    text-align: left;

    color: #d83232;

    background: transparent;

    font-size: 13px;

    font-weight: 700;

}


.logout-button:hover {

    background:
        #fff3f3;

    color:
        #bd2525;

}


/* =========================================================
   MAIN
========================================================= */

.container {

    width: 92%;

    max-width: 1080px;

    margin:
        42px auto 50px;

    position: relative;

    z-index: 5;

}


/* =========================================================
   WELCOME
========================================================= */

.welcome {

    position: relative;

    padding:
        27px 30px;

    margin-bottom: 27px;

    border-radius: 20px;

    background:
        rgba(255,255,255,0.68);

    border:
        1px solid
        rgba(255,255,255,0.88);

    backdrop-filter:
        blur(14px);

    box-shadow:
        0 14px 38px
        rgba(30,110,140,0.07);

    overflow: hidden;

}


.welcome::after {

    content: "";

    position: absolute;

    width: 155px;

    height: 155px;

    border-radius: 50%;

    right: -50px;

    top: -75px;

    background:
        rgba(22,132,211,0.07);

}


.welcome-content {

    position: relative;

    z-index: 2;

}


.welcome h1 {

    font-size: 29px;

    margin-bottom: 7px;

}


.welcome p {

    color: #687789;

    font-size: 13px;

    line-height: 1.6;

}


.welcome-line {

    width: 55px;

    height: 4px;

    margin-top: 16px;

    border-radius: 10px;

    background:
        linear-gradient(
            90deg,
            #1684d4,
            #23a99a,
            #2d903e
        );

}


/* =========================================================
   DASHBOARD
========================================================= */

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


/* =========================================================
   CARDS
========================================================= */

.card {

    position: relative;

    min-height: 0;

    padding: 22px;

    border-radius: 19px;

    background:
        rgba(255,255,255,0.90);

    border:
        1px solid
        rgba(255,255,255,0.95);

    box-shadow:
        0 12px 32px
        rgba(35,100,130,0.08);

    overflow: hidden;

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;

}


/* =========================================================
   CARD HOVER
========================================================= */

.card:hover {

    transform:
        translateY(-5px);

    box-shadow:
        0 18px 40px
        rgba(30,110,140,0.13);

}


/* =========================================================
   CARD TOP DECORATION
========================================================= */

.card::before {

    content: "";

    position: absolute;

    width: 125px;

    height: 125px;

    border-radius: 50%;

    right: -55px;

    top: -55px;

    background:
        rgba(22,132,211,0.065);

}


/* =========================================================
   CARD BOTTOM HOVER LINE

   IMPORTANT:
   The line is INVISIBLE before hover.

   When the card is hovered, the line grows
   from left to right.
========================================================= */

.card::after {

    content: "";

    position: absolute;

    left: 0;

    bottom: 0;

    width: 0;

    height: 4px;

    background:
        linear-gradient(
            90deg,
            #1684d4,
            #22a69a,
            #2d903e
        );

    transition:
        width 0.45s ease;

}


/* =========================================================
   SHOW LINE ONLY ON HOVER
========================================================= */

.card:hover::after {

    width: 100%;

}


/* =========================================================
   CARD ICON
========================================================= */

.card-icon {

    position: relative;

    z-index: 2;

    width: 47px;

    height: 47px;

    border-radius: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 23px;

    margin-bottom: 12px;

    background:
        linear-gradient(
            135deg,
            #e3f3ff,
            #e5faf5
        );

    border:
        1px solid
        rgba(30,150,190,0.08);

}


.card h2 {

    position: relative;

    z-index: 2;

    font-size: 17px;

    margin-bottom: 6px;

}


.card p {

    position: relative;

    z-index: 2;

    color: #708094;

    font-size: 11.5px;

    line-height: 1.55;

    margin-bottom: 13px;

    max-width: 420px;

}


/* =========================================================
   BUTTON
========================================================= */

.btn {

    position: relative;

    z-index: 3;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding:
        9px 15px;

    border-radius: 9px;

    text-decoration: none;

    color: white;

    font-size: 11.5px;

    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            #1683d4,
            #198da2
        );

    box-shadow:
        0 7px 17px
        rgba(22,131,212,0.17);

    transition:
        0.2s ease;

}


.btn:hover {

    background:
        linear-gradient(
            135deg,
            #0876c8,
            #168b7b
        );

    transform:
        translateY(-1px);

}


.inventory-btn {

    background:
        linear-gradient(
            135deg,
            #2d8c40,
            #218b75
        );

}


.inventory-btn:hover {

    background:
        linear-gradient(
            135deg,
            #23772f,
            #197c68
        );

}


/* =========================================================
   HEARTBEAT DECORATION
========================================================= */

.heartbeat {

    position: absolute;

    right: 18px;

    bottom: 9px;

    width: 145px;

    height: 30px;

    overflow: hidden;

    opacity: 0.15;

    pointer-events: none;

}


.heartbeat svg {

    width: 290px;

    height: 30px;

    animation:
        heartbeatMove
        3s
        linear
        infinite;

}


.heartbeat path {

    fill: none;

    stroke: #1b9c9a;

    stroke-width: 2;

}


@keyframes heartbeatMove {

    from {

        transform:
            translateX(0);

    }

    to {

        transform:
            translateX(-145px);

    }

}


/* =========================================================
   WEEKLY GRAPH
========================================================= */

.weekly-graph {

    position: relative;

    min-height: 535px;

    padding: 27px;

    border-radius: 20px;

    background:
        rgba(255,255,255,0.89);

    border:
        1px solid
        rgba(255,255,255,0.95);

    box-shadow:
        0 12px 35px
        rgba(35,100,130,0.09);

    overflow: hidden;

}


.weekly-graph::before {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    right: -70px;

    top: -70px;

    background:
        rgba(22,132,211,0.07);

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


.graph-title,
.graph-subtitle,
.chart,
.graph-total {

    position: relative;

    z-index: 2;

}


.graph-title {

    font-size: 20px;

    font-weight: 700;

    margin-bottom: 5px;

}


.graph-subtitle {

    color: #708094;

    font-size: 12px;

    line-height: 1.6;

    margin-bottom: 18px;

}


.week-range {

    display: block;

    color: #1687b7;

    font-weight: 700;

    margin-top: 2px;

}


.chart {

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
            transparent 64px,
            rgba(30,120,160,0.065) 65px
        );

}


.bar-group {

    flex: 1;

    height: 100%;

    display: flex;

    flex-direction: column;

    justify-content: flex-end;

    align-items: center;

    min-width: 45px;

}


.bar-value {

    min-height: 17px;

    font-size: 11px;

    font-weight: 800;

    color: #1687b7;

    margin-bottom: 6px;

}


.bar {

    width:
        min(42px, 72%);

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
        0 7px 16px
        rgba(22,131,212,0.15);

    transition:
        height 0.35s ease,
        transform 0.2s ease;

}


.bar:hover {

    transform:
        translateY(-3px);

}


.bar-day {

    margin-top: 9px;

    text-align: center;

    font-size: 10px;

    font-weight: 800;

    color: #687789;

    white-space: nowrap;

}


.bar-day span {

    display: block;

    margin-top: 2px;

    color: #91a0ae;

    font-size: 9px;

    font-weight: 600;

}


.graph-total {

    margin-top: 18px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding:
        12px 15px;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #eaf6ff,
            #eafaf4
        );

    color: #687789;

    font-size: 11px;

}


.graph-total strong {

    color: #172033;

    font-size: 17px;

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align: center;

    margin-top: 22px;

    color: #91a0ae;

    font-size: 10px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

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

    .header {

        height: 70px;

        padding:
            0 16px;

    }


    .logo {

        font-size: 23px;

    }


    .system-name {

        font-size: 9px;

    }


    .user-name {

        font-size: 11px;

    }


    .user-icon {

        width: 38px;

        height: 38px;

        font-size: 18px;

    }


    .container {

        margin-top: 25px;

    }


    .welcome {

        padding: 22px;

    }


    .welcome h1 {

        font-size: 24px;

    }


    .weekly-graph {

        padding: 21px;

    }


    .chart {

        gap: 5px;

        padding-left: 15px;

    }


    .bar-group {

        min-width: 34px;

    }


    .bar-day {

        font-size: 9px;

    }


    .bar-day span {

        font-size: 8px;

    }


    .medical-symbol {

        display: none;

    }

}

</style>

</head>


<body>


<div class="glow glow-one"></div>

<div class="glow glow-two"></div>


<div class="medical-symbol symbol-one">
    ✚
</div>

<div class="medical-symbol symbol-two">
    ♡
</div>

<div class="medical-symbol symbol-three">
    +
</div>

<div class="medical-symbol symbol-four">
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


    <div class="user-menu">


        <button
            type="button"
            class="user-menu-button"
            onclick="toggleUserMenu(event)"
            aria-expanded="false"
        >


            <div class="user-info">

                <div class="user-role">
                    Nurse
                </div>

                <div class="user-name">

                    <?php
                    echo htmlspecialchars(
                        $display_username
                    );
                    ?>

                </div>

            </div>


            <div class="user-icon">
                👩‍⚕️
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
                echo htmlspecialchars(
                    $display_username
                );
                ?>

            </div>


            <a
                href="nurse_dashboard.php?logout=1"
                class="logout-button"
            >

                ↪&nbsp;&nbsp;Logout

            </a>


            <a
                href="login_history.php"
                class="login-history-button"
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
         WELCOME
    ====================================================== -->

    <section class="welcome">


        <div class="welcome-content">


            <h1>

                Welcome,
                <?php
                echo htmlspecialchars(
                    $display_username
                );
                ?>!

            </h1>


            <p>
                Manage student registration,
                clinic consultations,
                and view medicine inventory.
            </p>


            <div class="welcome-line"></div>


        </div>


    </section>


    <!-- =====================================================
         DASHBOARD
    ====================================================== -->

    <section class="dashboard">


        <!-- =================================================
             LEFT MODULES
        ================================================== -->

        <div class="modules">


            <!-- =================================================
                 STUDENT REGISTRATION
            ================================================== -->

            <div class="card">


                <div class="card-icon">
                    👨‍🎓
                </div>


                <h2>
                    Student Registration
                </h2>


                <p>
                    Register students and manage
                    their clinic information and
                    health records.
                </p>


                <a
                    href="registration.php"
                    class="btn"
                >

                    Open Registration

                </a>


                <div class="heartbeat">

                    <svg
                        viewBox="0 0 290 30"
                        preserveAspectRatio="none"
                    >

                        <path
                            d="
                            M0 15
                            L32 15
                            L39 15
                            L46 5
                            L53 25
                            L60 15
                            L91 15
                            L98 15
                            L105 7
                            L112 23
                            L119 15
                            L150 15
                            L157 15
                            L164 4
                            L171 26
                            L178 15
                            L210 15
                            L217 15
                            L224 6
                            L231 24
                            L238 15
                            L270 15
                            L277 15
                            L284 7
                            L290 15
                            "
                        />

                    </svg>

                </div>


            </div>


            <!-- =================================================
                 CONSULTATION
            ================================================== -->

            <div class="card">


                <div class="card-icon">
                    🩺
                </div>


                <h2>
                    Consultation
                </h2>


                <p>
                    Record consultations, results,
                    prescriptions, and medication
                    instructions for students.
                </p>


                <a
                    href="consultation.php"
                    class="btn"
                >

                    Open Consultation

                </a>


                <div class="heartbeat">

                    <svg
                        viewBox="0 0 290 30"
                        preserveAspectRatio="none"
                    >

                        <path
                            d="
                            M0 15
                            L32 15
                            L39 15
                            L46 5
                            L53 25
                            L60 15
                            L91 15
                            L98 15
                            L105 7
                            L112 23
                            L119 15
                            L150 15
                            L157 15
                            L164 4
                            L171 26
                            L178 15
                            L210 15
                            L217 15
                            L224 6
                            L231 24
                            L238 15
                            L270 15
                            L277 15
                            L284 7
                            L290 15
                            "
                        />

                    </svg>

                </div>


            </div>


            <!-- =================================================
                 MEDICINE INVENTORY
            ================================================== -->

            <div class="card">


                <div class="card-icon">
                    💊
                </div>


                <h2>
                    Medicine Inventory
                </h2>


                <p>
                    View available medicines and stock
                    information. Editing is restricted
                    to administrators.
                </p>


                <a
                    href="Nurse_Inventory.php"
                    class="btn inventory-btn"
                >

                    View Inventory

                </a>


                <div class="heartbeat">

                    <svg
                        viewBox="0 0 290 30"
                        preserveAspectRatio="none"
                    >

                        <path
                            d="
                            M0 15
                            L32 15
                            L39 15
                            L46 5
                            L53 25
                            L60 15
                            L91 15
                            L98 15
                            L105 7
                            L112 23
                            L119 15
                            L150 15
                            L157 15
                            L164 4
                            L171 26
                            L178 15
                            L210 15
                            L217 15
                            L224 6
                            L231 24
                            L238 15
                            L270 15
                            L277 15
                            L284 7
                            L290 15
                            "
                        />

                    </svg>

                </div>


            </div>


        </div>


        <!-- =================================================
             WEEKLY GRAPH
        ================================================== -->

        <div class="weekly-graph">


            <div class="graph-title">
                Students Who Visited the Clinic
            </div>


            <div class="graph-subtitle">

                Clinic visits for the current week

                <span class="week-range">

                    <?php
                    echo $weekly_dates[0]->format(
                        'M j'
                    );
                    ?>

                    -

                    <?php
                    echo $weekly_dates[6]->format(
                        'M j, Y'
                    );
                    ?>

                </span>

            </div>


            <div class="chart">


                <?php
                foreach (
                    $weekly_counts
                    as $index => $count
                ):
                ?>


                    <?php

                    $bar_height =
                        max(
                            4,
                            (
                                $count /
                                $max_count
                            ) * 245
                        );

                    $day =
                        $weekly_dates[$index];

                    ?>


                    <div class="bar-group">


                        <div class="bar-value">

                            <?php
                            echo $count;
                            ?>

                        </div>


                        <div
                            class="bar"
                            style="
                                height:
                                <?php
                                echo $bar_height;
                                ?>px;
                            "
                            title="
                                <?php
                                echo $count;
                                ?>
                                student(s) visited on
                                <?php
                                echo $day->format(
                                    'M j, Y'
                                );
                                ?>
                            "
                        ></div>


                        <div class="bar-day">

                            <?php
                            echo $day->format(
                                'D'
                            );
                            ?>

                            <span>

                                <?php
                                echo $day->format(
                                    'M j'
                                );
                                ?>

                            </span>

                        </div>


                    </div>


                <?php
                endforeach;
                ?>


            </div>


            <div class="graph-total">

                <span>
                    Total students who visited this week
                </span>


                <strong>

                    <?php
                    echo $total_weekly_visits;
                    ?>

                </strong>


            </div>


        </div>


    </section>


    <div class="footer">

        MEDTRACK Clinic Management System

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
            'userDropdown'
        );


    const button =
        document.querySelector(
            '.user-menu-button'
        );


    const isOpen =
        dropdown.classList.toggle(
            'show'
        );


    button.setAttribute(
        'aria-expanded',
        isOpen ? 'true' : 'false'
    );

}


document.addEventListener(
    'click',
    function(event) {

        const menu =
            document.querySelector(
                '.user-menu'
            );


        const dropdown =
            document.getElementById(
                'userDropdown'
            );


        const button =
            document.querySelector(
                '.user-menu-button'
            );


        if (
            menu &&
            !menu.contains(event.target)
        ) {

            dropdown.classList.remove(
                'show'
            );


            button.setAttribute(
                'aria-expanded',
                'false'
            );

        }

    }
);

</script>


</body>

</html>
```