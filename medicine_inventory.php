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

$message = "";
$error = "";

/* =========================
   DELETE MEDICINE
========================= */

if (isset($_GET["delete"])) {

    $id = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM medicines WHERE id = ?"
    );

    if ($stmt) {

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "Medicine Successfully Deleted.";
        } else {
            $error = "Unable To Delete Medicine.";
        }

        $stmt->close();

    } else {
        $error = "Unable To Process Delete Request.";
    }
}

/* =========================
   ADD MEDICINE
========================= */

if (isset($_POST["add_medicine"])) {

    $medicine_name = trim($_POST["medicine_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $stock_quantity = intval($_POST["stock_quantity"] ?? 0);
    $minimum_stock = intval($_POST["minimum_stock"] ?? 10);
    $unit = trim($_POST["unit"] ?? "pcs");

    if ($medicine_name === "") {

        $error = "Medicine Name Is Required.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO medicines
            (
                medicine_name,
                description,
                stock_quantity,
                minimum_stock,
                unit
            )
            VALUES (?, ?, ?, ?, ?)"
        );

        if ($stmt) {

            $stmt->bind_param(
                "ssiis",
                $medicine_name,
                $description,
                $stock_quantity,
                $minimum_stock,
                $unit
            );

            if ($stmt->execute()) {
                $message = "Medicine Successfully Added.";
            } else {
                $error = "Unable To Add Medicine.";
            }

            $stmt->close();

        } else {
            $error = "Unable To Prepare Medicine Insert.";
        }
    }
}

/* =========================
   EDIT MEDICINE
========================= */

if (isset($_POST["edit_medicine"])) {

    $id = intval($_POST["medicine_id"] ?? 0);

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
                $message = "Medicine Successfully Updated.";
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
   SEARCH
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

        $stmt->execute();

        $medicines = $stmt->get_result();

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

/* =========================
   HEADER
========================= */

.header {
    height: 78px;
    position: relative;
    z-index: 10;

    background:
        linear-gradient(
            135deg,
            #1565c0,
            #1976d2 48%,
            #2e7d32
        );

    color: white;

    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 0 50px;

    box-shadow:
        0 8px 25px
        rgba(15, 23, 42, 0.16);
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

.user-area {
    position: relative;

    display: flex;
    align-items: center;
    gap: 13px;

    cursor: pointer;
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
}

.user-name {
    font-size: 15px;
    font-weight: bold;
    margin-top: 2px;
}

.user-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 21px;

    background: rgba(255,255,255,0.16);

    border:
        1px solid
        rgba(255,255,255,0.25);

    box-shadow:
        0 4px 12px
        rgba(0,0,0,0.10);
}

/* =========================
   USER DROPDOWN
========================= */

.user-dropdown {

    position: absolute;

    top: 58px;
    right: 0;

    width: 190px;

    background: white;

    border-radius: 18px;

    padding: 17px;

    box-shadow:
        0 18px 40px
        rgba(15,23,42,0.16);

    display: none;

    color: #1e293b;
}

.user-dropdown.show {
    display: block;

    animation:
        dropdownAppear
        0.20s ease;
}

@keyframes dropdownAppear {

    from {
        opacity: 0;
        transform: translateY(-7px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }

}

.dropdown-title {

    font-size: 12px;

    color: #64748b;

    margin-bottom: 4px;
}

.dropdown-role {

    font-size: 14px;

    font-weight: 700;

    color: #334155;

    padding-bottom: 12px;

    border-bottom:
        1px solid #e2e8f0;

}

.dropdown-link {

    display: flex;

    align-items: center;

    gap: 8px;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    padding: 10px 2px;

    transition: 0.20s;

}

.history-link {
    color: #006dcc;
}

.logout-link {
    color: #d62828;
}

.dropdown-link:hover {
    transform: translateX(2px);
}

.dropdown-divider {

    height: 1px;

    background: #e2e8f0;

    margin: 2px 0;
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

.success,
.error {
    padding: 15px 18px;
    border-radius: 12px;
    margin-bottom: 22px;
    font-size: 14px;
    font-weight: 600;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,0.05);
}

.success {
    background:
        linear-gradient(
            135deg,
            #ecfdf3,
            #e8f5e9
        );

    color: #18703a;

    border:
        1px solid #b7e4c7;
}

.error {
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

.search-btn,
.add-btn {

    border: none;

    border-radius: 11px;

    padding: 13px 21px;

    color: white;

    font-size: 14px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.25s;
}

.search-btn {

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

.add-btn {

    background:
        linear-gradient(
            135deg,
            #2e7d32,
            #388e3c
        );
}

.add-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 18px
        rgba(46,125,50,0.22);
}

/* =========================
   ADD FORM
========================= */

.form-card {

    background:
        rgba(255,255,255,0.94);

    backdrop-filter:
        blur(14px);

    -webkit-backdrop-filter:
        blur(14px);

    padding: 30px;

    border-radius: 20px;

    margin-bottom: 25px;

    border:
        1px solid
        rgba(255,255,255,0.9);

    box-shadow:
        0 14px 38px
        rgba(15,23,42,0.09);

    display: none;
}

.form-card.show {

    display: block;

    animation:
        formAppear
        0.3s ease;
}

@keyframes formAppear {

    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }

}

.form-heading {

    display: flex;

    align-items: center;

    gap: 13px;

    margin-bottom: 25px;
}

.form-heading-icon {

    width: 46px;

    height: 46px;

    border-radius: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e8f5e9;

    font-size: 22px;
}

.form-title {

    font-size: 21px;

    font-weight: 700;

    color: #172033;
}

.form-subtitle {

    color: #64748b;

    font-size: 12px;

    margin-top: 3px;
}

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

    grid-column: 1 / -1;
}

.form-group label {

    font-size: 13px;

    font-weight: 700;

    color: #334155;

    margin-bottom: 8px;
}

.form-group input,
.form-group textarea,
.form-group select {

    padding: 13px 14px;

    border:
        1px solid #d8e0e8;

    border-radius: 11px;

    font-size: 14px;

    outline: none;

    background: #f8fafc;

    color: #1e293b;

    transition: 0.25s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {

    background: white;

    border-color: #1976d2;

    box-shadow:
        0 0 0 4px
        rgba(25,118,210,0.08);
}

.form-group textarea {

    min-height: 95px;

    resize: vertical;
}

.form-actions {

    margin-top: 24px;

    display: flex;

    gap: 10px;
}

.save-btn,
.cancel-btn {

    border: none;

    border-radius: 10px;

    padding: 13px 22px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.25s;
}

.save-btn {

    background:
        linear-gradient(
            135deg,
            #1976d2,
            #2e7d32
        );

    color: white;
}

.save-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 8px 18px
        rgba(25,118,210,0.20);
}

.cancel-btn {

    background: #eef2f6;

    color: #475569;
}

.cancel-btn:hover {

    background: #e2e8f0;
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

.edit-btn,
.delete-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 8px 12px;

    border-radius: 8px;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;

    transition: 0.2s;
}

.edit-btn {

    background: #eaf4ff;

    color: #1976d2;
}

.delete-btn {

    background: #fff0f1;

    color: #c62828;
}

.edit-btn:hover {

    background: #d8ebff;

    transform: translateY(-1px);
}

.delete-btn:hover {

    background: #ffdfe3;

    transform: translateY(-1px);
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

    .add-btn {
        width: 100%;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .table-card {
        border-radius: 15px;
    }

}

@media (max-width: 600px) {

    .header {

        height: auto;

        min-height: 78px;

        padding: 15px 18px;
    }

    .logo {
        font-size: 23px;
    }

    .system-name {
        font-size: 9px;
    }

    .user-name {
        font-size: 13px;
    }

    .user-role {
        font-size: 9px;
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

    .form-card {
        padding: 22px;
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


<!-- =========================
     HEADER
========================= -->

<div class="header">

    <div>

        <div class="logo">
            MEDTRACK
        </div>

        <div class="system-name">
            CLINIC MANAGEMENT SYSTEM
        </div>

    </div>


    <div
        class="user-area"
        onclick="toggleUserMenu(event)"
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


        <!-- =========================
             USER DROPDOWN
        ========================== -->

        <div
            class="user-dropdown"
            id="userDropdown"
            onclick="event.stopPropagation()"
        >

            <div class="dropdown-title">
                Signed In As
            </div>

            <div class="dropdown-role">
                Administrator
            </div>


            <a
                href="login_history.php"
                class="dropdown-link history-link"
            >

                🕘
                Login History

            </a>


            <div class="dropdown-divider"></div>


            <a
                href="logout.php"
                class="dropdown-link logout-link"
            >

                ↪
                Logout

            </a>

        </div>

    </div>

</div>


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
                    Manage medicines, monitor available stock,
                    and identify low-stock items.
                </p>

            </div>

        </div>

    </div>


    <!-- SUCCESS -->

    <?php if ($message !== ""): ?>

        <div class="success">

            ✓

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


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
         ADD MEDICINE FORM
    ========================== -->

    <div
        class="form-card"
        id="addForm"
    >

        <div class="form-heading">

            <div class="form-heading-icon">
                💊
            </div>

            <div>

                <div class="form-title">
                    Add New Medicine
                </div>

                <div class="form-subtitle">
                    Enter the medicine information below.
                </div>

            </div>

        </div>


        <form method="POST">

            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Medicine Name
                    </label>

                    <input
                        type="text"
                        name="medicine_name"
                        placeholder="Enter Medicine Name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Unit
                    </label>

                    <select name="unit">

                        <option value="pcs">
                            Pieces
                        </option>

                        <option value="tablets">
                            Tablets
                        </option>

                        <option value="capsules">
                            Capsules
                        </option>

                        <option value="bottles">
                            Bottles
                        </option>

                        <option value="sachets">
                            Sachets
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        name="stock_quantity"
                        min="0"
                        value="0"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Minimum Stock
                    </label>

                    <input
                        type="number"
                        name="minimum_stock"
                        min="0"
                        value="10"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        placeholder="Enter Medicine Description"
                    ></textarea>

                </div>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    name="add_medicine"
                    class="save-btn"
                >
                    ✓ Save Medicine
                </button>


                <button
                    type="button"
                    class="cancel-btn"
                    onclick="closeAddForm()"
                >
                    Cancel
                </button>

            </div>

        </form>

    </div>


    <!-- =========================
         SEARCH / ADD CONTROLS
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


        <button
            type="button"
            class="add-btn"
            onclick="openAddForm()"
        >
            ＋ Add Medicine
        </button>

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
                            Actions
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


                                <!--
                                    UNIT IS ALWAYS DISPLAYED
                                    WITH THE FIRST LETTER
                                    CAPITALIZED.
                                -->

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


                            <!-- ACTIONS -->

                            <td>

                                <div class="actions">


                                    <a
                                        href="edit_medicine.php?id=<?php
                                            echo intval(
                                                $medicine["id"]
                                            );
                                        ?>"
                                        class="edit-btn"
                                    >
                                        ✎ Edit
                                    </a>


                                    <a
                                        href="inventory.php?delete=<?php
                                            echo intval(
                                                $medicine["id"]
                                            );
                                        ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are You Sure You Want To Delete This Medicine?');"
                                    >
                                        🗑 Delete
                                    </a>


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
            href="admin_dashboard.php"
            class="back-btn"
        >
            ← Back To Dashboard
        </a>

    </div>

</div>


<script>

/* =========================
   ADD FORM
========================= */

function openAddForm() {

    document
        .getElementById("addForm")
        .classList
        .add("show");


    setTimeout(function() {

        document
            .getElementById("addForm")
            .scrollIntoView({
                behavior: "smooth",
                block: "start"
            });

    }, 50);

}


function closeAddForm() {

    document
        .getElementById("addForm")
        .classList
        .remove("show");

}


/* =========================
   USER MENU
========================= */

function toggleUserMenu(event) {

    event.stopPropagation();

    document
        .getElementById("userDropdown")
        .classList
        .toggle("show");

}


/* =========================
   CLOSE USER MENU
========================= */

document.addEventListener(
    "click",
    function() {

        document
            .getElementById("userDropdown")
            .classList
            .remove("show");

    }
);

</script>

</body>

</html>
```