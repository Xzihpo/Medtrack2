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
   NURSE ONLY
========================= */

if ($_SESSION["role"] !== "nurse") {
    header("Location: admin_dashboard.php");
    exit();
}

$error = "";

/* =========================
   SEARCH
   READ-ONLY CONNECTION
   TO THE SAME MEDICINES TABLE
   USED BY ADMIN INVENTORY
========================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search !== "") {

    $stmt = $conn->prepare(
        "SELECT *
         FROM medicines
         WHERE medicine_name LIKE ?
         ORDER BY medicine_name ASC"
    );

    if ($stmt) {

        $searchTerm = "%" . $search . "%";

        $stmt->bind_param("s", $searchTerm);

        if ($stmt->execute()) {
            $medicines = $stmt->get_result();
        } else {
            $error = "Unable To Search Medicines.";
            $medicines = false;
        }

    } else {

        $error = "Unable To Search Medicines.";

        $medicines = false;
    }

} else {

    $medicines = $conn->query(
        "SELECT *
         FROM medicines
         ORDER BY medicine_name ASC"
    );

    if (!$medicines) {
        $error = "Unable To Load Medicines.";
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

<title>MEDTRACK - Medicine Inventory</title>

<style>

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

/* =========================
   MEDICAL SYMBOLS
========================= */

.medical-symbol {
    position: fixed;
    pointer-events: none;
    user-select: none;
    z-index: 0;
    opacity: 0.10;
    filter: blur(0.2px);
    animation: floatSymbol 8s ease-in-out infinite;
}

.symbol-1 {
    top: 120px;
    left: 4%;
    font-size: 65px;
    color: #1976d2;
}

.symbol-2 {
    top: 280px;
    right: 5%;
    font-size: 75px;
    color: #2e7d32;
    animation-delay: 2s;
}

.symbol-3 {
    bottom: 150px;
    left: 8%;
    font-size: 55px;
    color: #1976d2;
    animation-delay: 4s;
}

.symbol-4 {
    bottom: 60px;
    right: 10%;
    font-size: 65px;
    color: #2e7d32;
    animation-delay: 1s;
}

.symbol-5 {
    top: 520px;
    left: 47%;
    font-size: 40px;
    color: #1976d2;
    animation-delay: 3s;
}

@keyframes floatSymbol {

    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }

    50% {
        transform: translateY(-22px) rotate(8deg);
    }

}

/* =========================================================
   HEADER
   Matches the Nurse Dashboard header exactly
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
    background: linear-gradient(110deg, #1683d1, #168ca5 48%, #27833f);
    box-shadow: 0 5px 25px rgba(20,100,130,0.18);
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
   Matches the Nurse Dashboard top-right area
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
    border: 1px solid rgba(255,255,255,0.28);
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
    border: 1px solid rgba(255,255,255,0.98);
    box-shadow: 0 15px 35px rgba(20,80,110,0.22);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateY(-7px);
    transition: 0.18s ease;
    overflow: hidden;
    color: #1e293b;
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

/* =========================
   CONTAINER
========================= */

.container {
    width: 92%;
    max-width: 1250px;
    margin: 42px auto;
    position: relative;
    z-index: 2;
}

/* =========================
   PAGE TITLE
========================= */

.page-title {
    margin-bottom: 28px;
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

/* =========================
   MESSAGES
========================= */

.error {
    padding: 15px 18px;
    border-radius: 12px;
    margin-bottom: 22px;
    font-size: 14px;
    font-weight: 600;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,0.05);

    background:
        linear-gradient(
            135deg,
            #fff1f2,
            #ffebee
        );

    color: #b42318;

    border:
        1px solid #fecdd3;
}

/* =========================
   CONTROLS
========================= */

.controls {
    background: rgba(255,255,255,0.88);

    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);

    padding: 18px;

    border-radius: 18px;

    border:
        1px solid
        rgba(255,255,255,0.8);

    box-shadow:
        0 12px 35px
        rgba(15,23,42,0.08);

    margin-bottom: 24px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;
}

.search-form {
    display: flex;
    gap: 10px;
    flex: 1;
}

.search-form input {
    width: 100%;
    max-width: 430px;

    padding: 14px 17px;

    border:
        1px solid #dbe3ec;

    border-radius: 11px;

    outline: none;

    font-size: 14px;

    background: #f8fafc;

    color: #1e293b;

    transition: 0.25s;
}

.search-form input::placeholder {
    color: #94a3b8;
}

.search-form input:focus {

    background: white;

    border-color: #1976d2;

    box-shadow:
        0 0 0 4px
        rgba(25,118,210,0.09);
}

.search-btn {

    border: none;

    border-radius: 11px;

    padding: 13px 21px;

    color: white;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.25s;

    background:
        linear-gradient(
            135deg,
            #1976d2,
            #1565c0
        );
}

.search-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 18px
        rgba(25,118,210,0.22);
}

.read-only-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    padding: 12px 17px;

    border-radius: 11px;

    background: #eef4ff;
    color: #2563eb;

    font-size: 13px;
    font-weight: 700;

    white-space: nowrap;
}

/* =========================
   TABLE
========================= */

.table-card {

    background:
        rgba(255,255,255,0.94);

    backdrop-filter:
        blur(14px);

    -webkit-backdrop-filter:
        blur(14px);

    border-radius: 20px;

    border:
        1px solid
        rgba(255,255,255,0.9);

    box-shadow:
        0 14px 38px
        rgba(15,23,42,0.08);

    overflow: hidden;
}

.table-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 23px 26px;

    border-bottom:
        1px solid #edf1f5;
}

.table-title-area {

    display: flex;

    align-items: center;

    gap: 12px;
}

.table-icon {

    width: 42px;

    height: 42px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e3f2fd;

    font-size: 20px;
}

.table-title {

    font-size: 19px;

    font-weight: 700;

    color: #172033;
}

.table-subtitle {

    color: #94a3b8;

    font-size: 12px;

    margin-top: 3px;
}

.table-wrapper {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 850px;
}

th {

    background: #f8fafc;

    color: #64748b;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: 0.7px;

    text-transform: uppercase;

    text-align: left;

    padding: 15px 18px;

    border-bottom:
        1px solid #e8edf2;
}

td {

    padding: 17px 18px;

    font-size: 14px;

    color: #475569;

    border-bottom:
        1px solid #edf1f5;

    vertical-align: middle;
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

.medicine-name {

    color: #172033;

    font-weight: 700;

    margin-bottom: 4px;
}

/* =========================
   UNIT DISPLAY
   ALWAYS TITLE CASE
========================= */

.unit {

    display: inline-block;

    color: #64748b;

    font-size: 11px;

    padding: 3px 8px;

    border-radius: 20px;

    background: #f1f5f9;

    text-transform: capitalize;
}

.description {

    max-width: 250px;

    line-height: 1.5;

    color: #64748b;
}

.stock-number {

    font-size: 17px;

    font-weight: 800;

    color: #172033;
}

/* =========================
   STATUS
========================= */

.status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 7px 12px;

    border-radius: 30px;

    font-size: 11px;

    font-weight: 800;
}

.status::before {

    content: "";

    width: 6px;

    height: 6px;

    border-radius: 50%;
}

.low {

    background: #ffedf0;

    color: #b42318;
}

.low::before {

    background: #dc2626;
}

.minimum {

    background: #fff7df;

    color: #a16207;
}

.minimum::before {

    background: #f59e0b;
}

.high {

    background: #eaf7ee;

    color: #237a3b;
}

.high::before {

    background: #2e7d32;
}

.normal {

    background: #eef4ff;

    color: #2563eb;
}

.normal::before {

    background: #3b82f6;
}

.out {

    background: #ffedf0;

    color: #b42318;
}

.out::before {

    background: #dc2626;
}

/* =========================
   ACTIONS
========================= */

.actions {

    display: flex;

    gap: 7px;
}

.view-only-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 8px 12px;

    border-radius: 8px;

    font-size: 11px;

    font-weight: 700;

    background: #eef2f6;

    color: #64748b;

    cursor: default;

    white-space: nowrap;
}

/* =========================
   EMPTY
========================= */

.empty {

    text-align: center;

    padding: 55px 20px !important;

    color: #94a3b8;
}

.empty-icon {

    font-size: 40px;

    margin-bottom: 10px;

    opacity: 0.7;
}

.empty-text {

    font-size: 14px;
}

/* =========================
   BACK BUTTON
========================= */

.back-area {

    margin-top: 28px;
}

.back-btn {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 12px 18px;

    background:
        rgba(255,255,255,0.85);

    border:
        1px solid #e2e8f0;

    color: #475569;

    text-decoration: none;

    border-radius: 10px;

    font-size: 13px;

    font-weight: 700;

    transition: 0.2s;
}

.back-btn:hover {

    background: white;

    transform: translateX(-2px);

    box-shadow:
        0 5px 15px
        rgba(15,23,42,0.08);
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 850px) {

    .header {
        padding: 0 25px;
    }

    .container {
        width: 94%;
        margin-top: 30px;
    }

    .controls {
        flex-direction: column;
        align-items: stretch;
    }

    .search-form {
        width: 100%;
    }

    .search-form input {
        max-width: none;
    }

    .read-only-badge {
        width: 100%;
    }

}

@media (max-width: 600px) {

    .header {
        height: 70px;
        padding: 0 16px;
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

    .user-dropdown {
        right: -5px;
    }

    .page-title h1 {
        font-size: 26px;
    }

    .title-icon {
        width: 50px;
        height: 50px;
    }

    .controls {
        padding: 14px;
    }

    .search-form {
        flex-direction: column;
    }

    .search-btn {
        width: 100%;
    }

    .medical-symbol {
        display: none;
    }

}

</style>

</head>

<body>

<!-- =========================
     MEDICAL SYMBOLS
========================= -->

<div class="medical-symbol symbol-1">💊</div>
<div class="medical-symbol symbol-2">✚</div>
<div class="medical-symbol symbol-3">🩺</div>
<div class="medical-symbol symbol-4">💉</div>
<div class="medical-symbol symbol-5">⚕</div>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="header">

    <div class="brand">
        <div class="logo">MEDTRACK</div>
        <div class="system-name">Clinic Management System</div>
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
                        ucfirst(
                            strtolower(
                                $_SESSION["username"] ?? "Nurse"
                            )
                        )
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
            onclick="event.stopPropagation()"
        >

            <div class="dropdown-label">
                Signed In As
            </div>

            <div class="dropdown-name">
                Nurse
            </div>

            <a
                href="logout.php"
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

<!-- =========================
     MAIN CONTAINER
========================= -->

<div class="container">


    <!-- PAGE TITLE -->

    <div class="page-title">

        <div class="title-row">

            <div class="title-icon">
                💊
            </div>

            <div>

                <h1>
                    Medicine Inventory
                </h1>

                <p>
                    View current clinic medicines, monitor available stock,
                    and identify low-stock items.
                </p>

            </div>

        </div>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ""): ?>

        <div class="error">

            ⚠

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         SEARCH / READ-ONLY CONTROLS
    ========================== -->

    <div class="controls">

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Search medicine by name..."
                value="<?php
                    echo htmlspecialchars($search);
                ?>"
            >


            <button
                type="submit"
                class="search-btn"
            >
                🔎 Search
            </button>

        </form>


        <div class="read-only-badge">
            👁 View Only
        </div>

    </div>


    <!-- =========================
         MEDICINE TABLE
    ========================== -->

    <div class="table-card">


        <div class="table-header">

            <div class="table-title-area">

                <div class="table-icon">
                    💊
                </div>

                <div>

                    <div class="table-title">
                        Medicine List
                    </div>

                    <div class="table-subtitle">
                        Current clinic medicine inventory
                    </div>

                </div>

            </div>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Medicine
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Stock
                        </th>

                        <th>
                            Minimum Stock
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Access
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    $medicines &&
                    $medicines->num_rows > 0
                ): ?>


                    <?php while (
                        $medicine =
                        $medicines->fetch_assoc()
                    ): ?>


                        <tr>


                            <!-- MEDICINE -->

                            <td>

                                <div class="medicine-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $medicine["medicine_name"]
                                    );

                                    ?>

                                </div>


                                <span class="unit">

                                    <?php

                                    echo htmlspecialchars(
                                        ucwords(
                                            strtolower(
                                                $medicine["unit"]
                                            )
                                        )
                                    );

                                    ?>

                                </span>

                            </td>


                            <!-- DESCRIPTION -->

                            <td>

                                <div class="description">

                                    <?php

                                    echo htmlspecialchars(
                                        $medicine["description"]
                                    );

                                    ?>

                                </div>

                            </td>


                            <!-- STOCK -->

                            <td>

                                <div class="stock-number">

                                    <?php

                                    echo intval(
                                        $medicine["stock_quantity"]
                                    );

                                    ?>

                                </div>

                            </td>


                            <!-- MINIMUM STOCK -->

                            <td>

                                <?php

                                echo intval(
                                    $medicine["minimum_stock"]
                                );

                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php

                                $stock =
                                    intval(
                                        $medicine["stock_quantity"]
                                    );

                                $minimum =
                                    intval(
                                        $medicine["minimum_stock"]
                                    );


                                if ($stock <= 0) {

                                    echo '
                                    <span class="status out">
                                        Out Of Stock
                                    </span>';

                                } elseif ($stock < $minimum) {

                                    echo '
                                    <span class="status low">
                                        Low Stock
                                    </span>';

                                } elseif ($stock == $minimum) {

                                    echo '
                                    <span class="status minimum">
                                        Minimum
                                    </span>';

                                } elseif (
                                    $minimum > 0 &&
                                    $stock > ($minimum * 1.60)
                                ) {

                                    echo '
                                    <span class="status high">
                                        High Stock
                                    </span>';

                                } else {

                                    echo '
                                    <span class="status normal">
                                        Normal
                                    </span>';

                                }

                                ?>

                            </td>


                            <!-- ACCESS -->

                            <td>

                                <div class="actions">

                                    <span class="view-only-btn">
                                        👁 View Only
                                    </span>

                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty"
                        >

                            <div class="empty-icon">
                                💊
                            </div>

                            <div class="empty-text">
                                No Medicines Found.
                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


    <!-- BACK -->

    <div class="back-area">

        <a
            href="nurse_dashboard.php"
            class="back-btn"
        >
            ← Back To Dashboard
        </a>

    </div>

</div>


<script>

/* =========================
   USER MENU
========================= */

function toggleUserMenu(event) {

    event.stopPropagation();

    const dropdown = document.getElementById("userDropdown");
    const button = document.querySelector(".user-menu-button");

    const isOpen = dropdown.classList.toggle("show");

    button.setAttribute(
        "aria-expanded",
        isOpen ? "true" : "false"
    );

}


/* =========================
   CLOSE USER MENU
========================= */

document.addEventListener(
    "click",
    function(event) {

        const menu = document.querySelector(".user-menu");
        const dropdown = document.getElementById("userDropdown");
        const button = document.querySelector(".user-menu-button");

        if (menu && !menu.contains(event.target)) {

            dropdown.classList.remove("show");

            button.setAttribute(
                "aria-expanded",
                "false"
            );
        }
    }
);

</script>

</body>

</html>
