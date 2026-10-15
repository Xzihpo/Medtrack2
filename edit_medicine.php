<?php

session_start();

require_once "Database.php";

/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}


/* =========================
   ADMIN ONLY
========================= */

if ($_SESSION["role"] !== "admin") {
    header("Location: nurse_dashboard.php");
    exit();
}


/* =========================
   GET MEDICINE ID
========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: medicine_inventory.php");
    exit();
}

$id = intval($_GET["id"]);

$error = "";


/* =========================
   UPDATE MEDICINE
========================= */

if (isset($_POST["update_medicine"])) {

    $medicine_name = trim($_POST["medicine_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $stock_quantity = intval($_POST["stock_quantity"] ?? 0);
    $minimum_stock = intval($_POST["minimum_stock"] ?? 0);
    $unit = trim($_POST["unit"] ?? "pcs");

    if ($medicine_name === "") {

        $error = "Medicine Name Is Required.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE medicines
             SET
                medicine_name = ?,
                description = ?,
                stock_quantity = ?,
                minimum_stock = ?,
                unit = ?
             WHERE id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "ssiisi",
                $medicine_name,
                $description,
                $stock_quantity,
                $minimum_stock,
                $unit,
                $id
            );

            if ($stmt->execute()) {

                $stmt->close();

                header("Location: medicine_inventory.php");
                exit();

            } else {

                $error = "Unable To Update Medicine.";

            }

            $stmt->close();

        } else {

            $error = "Unable To Prepare Medicine Update.";

        }
    }
}


/* =========================
   GET MEDICINE
========================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM medicines
     WHERE id = ?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    header("Location: medicine_inventory.php");
    exit();
}

$medicine = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>MEDTRACK - Edit Medicine</title>

<style>

/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}


/* =========================================================
   BODY
========================================================= */

body {

    min-height: 100vh;

    color: #1e293b;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(25,118,210,0.13),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 15%,
            rgba(46,125,50,0.12),
            transparent 30%
        ),
        radial-gradient(
            circle at 80% 85%,
            rgba(25,118,210,0.09),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #eef7ff,
            #f4fbf5,
            #edf7ff
        );

    overflow-x: hidden;
}


/* =========================================================
   BACKGROUND DECORATION
========================================================= */

.background-decoration {

    position: fixed;

    inset: 0;

    pointer-events: none;

    overflow: hidden;

    z-index: 0;
}


.bg-circle {

    position: absolute;

    border-radius: 50%;

    filter: blur(1px);

    opacity: 0.5;
}


.circle-one {

    width: 280px;

    height: 280px;

    background:
        rgba(25,118,210,0.10);

    top: 100px;

    left: -120px;
}


.circle-two {

    width: 360px;

    height: 360px;

    background:
        rgba(46,125,50,0.09);

    bottom: -150px;

    right: -120px;
}


.circle-three {

    width: 160px;

    height: 160px;

    background:
        rgba(25,118,210,0.08);

    top: 45%;

    right: 7%;
}


/* =========================================================
   FLOATING MEDICAL SYMBOLS
========================================================= */

.med-symbol {

    position: absolute;

    display: flex;

    align-items: center;

    justify-content: center;

    color: rgba(25,118,210,0.14);

    font-weight: bold;

    user-select: none;

    animation:
        floatingSymbol
        7s
        ease-in-out
        infinite;
}


.symbol-plus-one {

    font-size: 58px;

    top: 18%;

    left: 5%;

    animation-delay: 0s;
}


.symbol-plus-two {

    font-size: 42px;

    bottom: 18%;

    left: 9%;

    color:
        rgba(46,125,50,0.13);

    animation-delay: 2s;
}


.symbol-heart {

    font-size: 38px;

    top: 28%;

    right: 7%;

    color:
        rgba(220,75,105,0.12);

    animation-delay: 1s;
}


.symbol-pill {

    font-size: 42px;

    bottom: 25%;

    right: 6%;

    color:
        rgba(25,118,210,0.13);

    animation-delay: 3s;
}


.symbol-cross {

    font-size: 35px;

    top: 67%;

    right: 17%;

    color:
        rgba(46,125,50,0.11);

    animation-delay: 4s;
}


.symbol-dot {

    width: 12px;

    height: 12px;

    border-radius: 50%;

    background:
        rgba(25,118,210,0.14);

    top: 12%;

    right: 22%;

    animation-delay: 1.5s;
}


.symbol-snake {

    font-size: 28px;

    bottom: 11%;

    right: 26%;

    color:
        rgba(46,125,50,0.10);

    animation-delay: 2.5s;
}


@keyframes floatingSymbol {

    0% {

        transform:
            translateY(0px)
            rotate(0deg);
    }

    50% {

        transform:
            translateY(-14px)
            rotate(3deg);
    }

    100% {

        transform:
            translateY(0px)
            rotate(0deg);
    }
}


/* =========================================================
   HEADER
========================================================= */

.header {

    height: 78px;

    position: relative;

    z-index: 20;

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 0 50px;

    color: white;

    background:
        linear-gradient(
            135deg,
            #1565c0,
            #1976d2 48%,
            #2e7d32
        );

    box-shadow:
        0 8px 25px
        rgba(15,23,42,0.16);
}


/* =========================================================
   LOGO
========================================================= */

.logo-area {

    display: flex;

    align-items: center;

    gap: 13px;
}


.logo-symbol {

    width: 44px;

    height: 44px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 23px;

    background:
        rgba(255,255,255,0.16);

    border:
        1px solid
        rgba(255,255,255,0.25);
}


.logo {

    font-size: 28px;

    font-weight: 800;

    letter-spacing: 3px;
}


.system-name {

    font-size: 11px;

    letter-spacing: 1.5px;

    opacity: 0.82;

    margin-top: 4px;
}


/* =========================================================
   USER AREA
========================================================= */

.user-area {

    position: relative;

    display: flex;

    align-items: center;

    gap: 13px;

    cursor: pointer;

    padding: 7px 10px;

    border-radius: 16px;

    transition: 0.25s;
}


.user-area:hover {

    background:
        rgba(255,255,255,0.10);
}


.user-info {

    text-align: right;
}


.user-role {

    font-size: 11px;

    font-weight: bold;

    letter-spacing: 1px;

    opacity: 0.75;

    text-transform: uppercase;

    margin-bottom: 2px;
}


.user-name {

    font-size: 15px;

    font-weight: bold;

    margin-top: 2px;

    text-transform: capitalize;
}


.user-icon {

    width: 44px;

    height: 44px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    background:
        rgba(255,255,255,0.16);

    border:
        1px solid
        rgba(255,255,255,0.25);

    box-shadow:
        0 4px 12px
        rgba(0,0,0,0.10);
}


/* =========================================================
   PROFILE DROPDOWN
========================================================= */

.profile-menu {

    position: absolute;

    top: 68px;

    right: 0;

    width: 225px;

    padding: 18px;

    background:
        rgba(255,255,255,0.97);

    border-radius: 18px;

    box-shadow:
        0 18px 40px
        rgba(15,23,42,0.16);

    border:
        1px solid
        rgba(255,255,255,0.9);

    display: none;

    color: #1e293b;

    backdrop-filter:
        blur(12px);

    -webkit-backdrop-filter:
        blur(12px);

    animation:
        profileAppear
        0.2s
        ease;
}


.profile-menu.show {

    display: block;
}


@keyframes profileAppear {

    from {

        opacity: 0;

        transform:
            translateY(-7px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


.profile-title {

    font-size: 12px;

    color: #64748b;

    margin-bottom: 4px;
}


.profile-name {

    font-size: 14px;

    font-weight: 700;

    color: #334155;

    padding-bottom: 13px;

    border-bottom:
        1px solid #e2e8f0;

    margin-bottom: 9px;
}


.profile-link {

    display: flex;

    align-items: center;

    gap: 8px;

    width: 100%;

    padding: 10px 4px;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    border-radius: 8px;

    transition: 0.2s;
}


.history-link {

    color: #1976d2;
}


.logout-link {

    color: #d32f2f;

    margin-top: 2px;
}


.profile-link:hover {

    background:
        #f1f5f9;

    padding-left: 8px;
}


/* =========================================================
   MAIN CONTAINER
========================================================= */

.container {

    width: 92%;

    max-width: 880px;

    margin: 42px auto 60px;

    position: relative;

    z-index: 2;
}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title {

    margin-bottom: 28px;

    animation:
        fadeDown
        0.6s
        ease;
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

    border:
        1px solid
        rgba(25,118,210,0.12);

    box-shadow:
        0 8px 20px
        rgba(25,118,210,0.10);
}


.page-title h1 {

    font-size: 31px;

    color: #172033;

    margin-bottom: 6px;

    letter-spacing: -0.5px;
}


.page-title p {

    color: #64748b;

    font-size: 14px;

    line-height: 1.6;
}


/* =========================================================
   ERROR
========================================================= */

.error {

    display: flex;

    align-items: center;

    gap: 10px;

    background:
        linear-gradient(
            135deg,
            #fff1f2,
            #ffebee
        );

    color: #b42318;

    border:
        1px solid #fecdd3;

    border-left:
        5px solid #dc2626;

    padding: 15px 18px;

    border-radius: 12px;

    margin-bottom: 22px;

    font-size: 14px;

    font-weight: 600;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,0.05);
}


/* =========================================================
   FORM CARD
========================================================= */

.form-card {

    position: relative;

    overflow: hidden;

    background:
        rgba(255,255,255,0.94);

    border:
        1px solid
        rgba(255,255,255,0.9);

    border-radius: 20px;

    padding: 30px;

    box-shadow:
        0 14px 38px
        rgba(15,23,42,0.09);

    backdrop-filter:
        blur(14px);

    -webkit-backdrop-filter:
        blur(14px);

    animation:
        fadeUp
        0.7s
        ease;
}


.form-card::before {

    content: "";

    position: absolute;

    top: 0;

    left: 0;

    right: 0;

    height: 4px;

    background:
        linear-gradient(
            90deg,
            #1976d2,
            #2e7d32
        );
}


.form-card::after {

    content: "✚";

    position: absolute;

    right: 22px;

    top: 17px;

    font-size: 28px;

    color:
        rgba(25,118,210,0.08);
}


/* =========================================================
   FORM HEADER
========================================================= */

.form-header {

    display: flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 25px;

    padding-bottom: 20px;

    border-bottom:
        1px solid #edf1f5;
}


.form-icon {

    width: 46px;

    height: 46px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

    background:
        #e8f5e9;
}


.form-header-text h2 {

    font-size: 19px;

    font-weight: 700;

    color: #172033;

    margin-bottom: 4px;
}


.form-header-text p {

    color: #64748b;

    font-size: 12px;
}


/* =========================================================
   FORM GRID
========================================================= */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

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


/* =========================================================
   LABEL
========================================================= */

.form-group label {

    font-size: 13px;

    font-weight: 700;

    color: #334155;

    margin-bottom: 8px;
}


/* =========================================================
   INPUTS
========================================================= */

.form-group input,
.form-group textarea,
.form-group select {

    width: 100%;

    padding: 13px 14px;

    border:
        1px solid #d8e0e8;

    border-radius: 11px;

    background:
        #f8fafc;

    color: #1e293b;

    font-size: 14px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    outline: none;

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease,
        transform 0.2s ease;
}


.form-group input:hover,
.form-group textarea:hover,
.form-group select:hover {

    border-color:
        #b9c9d6;

    background:
        #ffffff;
}


.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {

    border-color:
        #1976d2;

    background:
        #ffffff;

    box-shadow:
        0 0 0 4px
        rgba(25,118,210,0.08);

    transform:
        translateY(-1px);
}


.form-group textarea {

    min-height: 115px;

    resize: vertical;

    line-height: 1.6;
}


.form-group select {

    cursor: pointer;
}


/* =========================================================
   FORM ACTIONS
========================================================= */

.form-actions {

    margin-top: 24px;

    padding-top: 22px;

    border-top:
        1px solid #edf1f5;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 12px;
}


.action-buttons {

    display: flex;

    gap: 10px;
}


/* =========================================================
   UPDATE BUTTON
========================================================= */

.update-btn {

    border: none;

    border-radius: 10px;

    padding: 13px 22px;

    color: white;

    font-size: 14px;

    font-weight: bold;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    cursor: pointer;

    background:
        linear-gradient(
            135deg,
            #1976d2,
            #2e7d32
        );

    box-shadow:
        0 7px 18px
        rgba(25,118,210,0.20);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}


.update-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 10px 22px
        rgba(25,118,210,0.25);
}


/* =========================================================
   CANCEL BUTTON
========================================================= */

.cancel-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 13px 22px;

    border-radius: 10px;

    background:
        #eef2f6;

    color:
        #475569;

    text-decoration: none;

    font-size: 14px;

    font-weight: bold;

    transition:
        all 0.2s ease;
}


.cancel-btn:hover {

    background:
        #e2e8f0;

    transform:
        translateY(-1px);
}


/* =========================================================
   FORM NOTE
========================================================= */

.form-note {

    font-size: 11px;

    color: #94a3b8;
}


.form-note span {

    color: #2e7d32;

    font-weight: bold;
}


/* =========================================================
   ANIMATIONS
========================================================= */

@keyframes fadeUp {

    from {

        opacity: 0;

        transform:
            translateY(18px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


@keyframes fadeDown {

    from {

        opacity: 0;

        transform:
            translateY(-12px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .header {

        height: auto;

        min-height: 78px;

        padding:
            15px 20px;
    }


    .logo {

        font-size: 23px;
    }


    .system-name {

        font-size: 9px;
    }


    .logo-symbol {

        width: 38px;

        height: 38px;

        font-size: 19px;
    }


    .user-role {

        display: none;
    }


    .user-name {

        font-size: 13px;
    }


    .user-icon {

        width: 38px;

        height: 38px;

        font-size: 18px;
    }


    .profile-menu {

        top: 62px;

        right: -5px;

        width: 220px;
    }


    .container {

        width: 94%;

        margin:
            30px auto 45px;
    }


    .page-title h1 {

        font-size: 26px;
    }


    .title-icon {

        width: 50px;

        height: 50px;
    }


    .form-card {

        padding: 22px;

        border-radius: 18px;
    }


    .form-grid {

        grid-template-columns:
            1fr;

        gap: 18px;
    }


    .form-group.full {

        grid-column:
            auto;
    }


    .form-actions {

        flex-direction: column;

        align-items: stretch;
    }


    .action-buttons {

        width: 100%;

        flex-direction: column;
    }


    .update-btn,
    .cancel-btn {

        width: 100%;

        text-align: center;
    }


    .form-note {

        text-align: center;

        order: 2;
    }


    .med-symbol {

        display: none;
    }
}


/* =========================================================
   REDUCED MOTION
========================================================= */

@media (prefers-reduced-motion: reduce) {

    .med-symbol,
    .form-card,
    .page-title {

        animation: none;
    }
}

</style>

</head>

<body>


<!-- =========================================================
     BACKGROUND DECORATION
========================================================= -->

<div class="background-decoration">

    <div class="bg-circle circle-one"></div>

    <div class="bg-circle circle-two"></div>

    <div class="bg-circle circle-three"></div>


    <div class="med-symbol symbol-plus-one">
        ✚
    </div>

    <div class="med-symbol symbol-plus-two">
        ✚
    </div>

    <div class="med-symbol symbol-heart">
        ♡
    </div>

    <div class="med-symbol symbol-pill">
        💊
    </div>

    <div class="med-symbol symbol-cross">
        ✚
    </div>

    <div class="med-symbol symbol-dot">
    </div>

    <div class="med-symbol symbol-snake">
        ⚕
    </div>

</div>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="header">

    <div class="logo-area">

        <div class="logo-symbol">
            ✚
        </div>

        <div>

            <div class="logo">
                MEDTRACK
            </div>

            <div class="system-name">
                CLINIC MANAGEMENT SYSTEM
            </div>

        </div>

    </div>


    <!-- USER PROFILE -->

    <div
        class="user-area"
        onclick="toggleProfileMenu(event)"
    >

        <div class="user-info">

            <div class="user-role">
                ADMINISTRATOR
            </div>

            <div class="user-name">

                <?php

                echo ucfirst(
                    htmlspecialchars(
                        $_SESSION["username"] ?? "Admin"
                    )
                );

                ?>

            </div>

        </div>


        <div class="user-icon">
            👤
        </div>


        <!-- PROFILE DROPDOWN -->

        <div
            class="profile-menu"
            id="profileMenu"
        >

            <div class="profile-title">
                Signed In As
            </div>

            <div class="profile-name">
                Administrator
            </div>


            <a
                href="login_history.php"
                class="profile-link history-link"
                onclick="event.stopPropagation();"
            >
                🕘 Login History
            </a>


            <a
                href="logout.php"
                class="profile-link logout-link"
                onclick="event.stopPropagation();"
            >
                ↪ Logout
            </a>

        </div>

    </div>

</div>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="container">


    <div class="page-title">

        <div class="title-row">

            <div class="title-icon">
                💊
            </div>

            <div>

                <h1>
                    Edit Medicine
                </h1>

                <p>
                    Update the medicine information and stock quantity.
                </p>

            </div>

        </div>

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">

            <span>
                ⚠
            </span>

            <span>

                <?php

                echo htmlspecialchars(
                    $error
                );

                ?>

            </span>

        </div>

    <?php endif; ?>


    <div class="form-card">


        <div class="form-header">

            <div class="form-icon">
                💊
            </div>


            <div class="form-header-text">

                <h2>
                    Medicine Information
                </h2>

                <p>
                    Make the necessary changes to this medicine record.
                </p>

            </div>

        </div>


        <form method="POST">


            <div class="form-grid">


                <!-- MEDICINE NAME -->

                <div class="form-group">

                    <label>
                        Medicine Name
                    </label>

                    <input
                        type="text"
                        name="medicine_name"
                        value="<?php

                        echo htmlspecialchars(
                            $medicine["medicine_name"]
                        );

                        ?>"
                        placeholder="Enter Medicine Name"
                        required
                    >

                </div>


                <!-- UNIT -->

                <div class="form-group">

                    <label>
                        Unit
                    </label>

                    <select name="unit">


                        <option
                            value="pcs"
                            <?php

                            if (
                                $medicine["unit"]
                                ===
                                "pcs"
                            ) {
                                echo "selected";
                            }

                            ?>
                        >
                            Pieces
                        </option>


                        <option
                            value="tablets"
                            <?php

                            if (
                                $medicine["unit"]
                                ===
                                "tablets"
                            ) {
                                echo "selected";
                            }

                            ?>
                        >
                            Tablets
                        </option>


                        <option
                            value="capsules"
                            <?php

                            if (
                                $medicine["unit"]
                                ===
                                "capsules"
                            ) {
                                echo "selected";
                            }

                            ?>
                        >
                            Capsules
                        </option>


                        <option
                            value="bottles"
                            <?php

                            if (
                                $medicine["unit"]
                                ===
                                "bottles"
                            ) {
                                echo "selected";
                            }

                            ?>
                        >
                            Bottles
                        </option>


                        <option
                            value="sachets"
                            <?php

                            if (
                                $medicine["unit"]
                                ===
                                "sachets"
                            ) {
                                echo "selected";
                            }

                            ?>
                        >
                            Sachets
                        </option>


                    </select>

                </div>


                <!-- STOCK QUANTITY -->

                <div class="form-group">

                    <label>
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        name="stock_quantity"
                        min="0"
                        value="<?php

                        echo intval(
                            $medicine["stock_quantity"]
                        );

                        ?>"
                        required
                    >

                </div>


                <!-- MINIMUM STOCK -->

                <div class="form-group">

                    <label>
                        Minimum Stock
                    </label>

                    <input
                        type="number"
                        name="minimum_stock"
                        min="0"
                        value="<?php

                        echo intval(
                            $medicine["minimum_stock"]
                        );

                        ?>"
                        required
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        placeholder="Enter Medicine Description"
                    ><?php

                    echo htmlspecialchars(
                        $medicine["description"]
                    );

                    ?></textarea>

                </div>


            </div>


            <!-- FORM ACTIONS -->

            <div class="form-actions">


                <div class="form-note">

                    <span>●</span>
                    Changes Will Be Saved To The Medicine Inventory.

                </div>


                <div class="action-buttons">


                    <button
                        type="submit"
                        name="update_medicine"
                        class="update-btn"
                    >

                        ✓ Update Medicine

                    </button>


                    <a
                        href="medicine_inventory.php"
                        class="cancel-btn"
                    >

                        Cancel

                    </a>


                </div>

            </div>


        </form>

    </div>

</div>


<script>

/* =========================================================
   PROFILE DROPDOWN
========================================================= */

function toggleProfileMenu(event) {

    event.stopPropagation();

    const menu =
        document.getElementById("profileMenu");

    menu.classList.toggle("show");
}


/* =========================================================
   CLOSE PROFILE DROPDOWN
========================================================= */

document.addEventListener(
    "click",
    function() {

        const menu =
            document.getElementById("profileMenu");

        menu.classList.remove("show");

    }
);


/* =========================================================
   PREVENT DROPDOWN FROM CLOSING
========================================================= */

document
    .getElementById("profileMenu")
    .addEventListener(
        "click",
        function(event) {

            event.stopPropagation();

        }
    );

</script>


</body>

</html>