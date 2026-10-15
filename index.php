<?php

session_start();

require_once "Database.php";

$error = "";
$register_error = "";
$register_success = "";

/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {

    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    }

    if ($_SESSION['role'] === 'nurse') {
        header("Location: nurse_dashboard.php");
        exit();
    }
}


/*
|--------------------------------------------------------------------------
| PROCESS LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {

    $register_username = trim($_POST["register_username"] ?? "");
    $register_password = $_POST["register_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $register_role = $_POST["register_role"] ?? "";

    if (empty($register_username) || empty($register_password) || empty($confirm_password) || empty($register_role)) {

        $register_error = "Please complete all registration fields.";

    } elseif ($register_password !== $confirm_password) {

        $register_error = "Passwords do not match.";

    } elseif (!in_array($register_role, ["admin", "nurse"], true)) {

        $register_error = "Please select a valid role.";

    } else {

        $check_stmt = $conn->prepare(
            "SELECT id FROM users WHERE username = ? LIMIT 1"
        );

        if ($check_stmt) {

            $check_stmt->bind_param("s", $register_username);
            $check_stmt->execute();

            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {

                $register_error = "Username already exists. Please choose another username.";

            } else {

                $hashed_password = password_hash($register_password, PASSWORD_DEFAULT);

                $insert_stmt = $conn->prepare(
                    "INSERT INTO users (username, password, role) VALUES (?, ?, ?)"
                );

                if ($insert_stmt) {

                    $insert_stmt->bind_param(
                        "sss",
                        $register_username,
                        $hashed_password,
                        $register_role
                    );

                    if ($insert_stmt->execute()) {

                        $register_success = "Account created successfully. You can now log in.";

                    } else {

                        $register_error = "Unable to create the account. Please try again.";
                    }

                    $insert_stmt->close();

                } else {

                    $register_error = "Unable to process registration. Please try again.";
                }
            }

            $check_stmt->close();

        } else {

            $register_error = "Unable to process registration. Please try again.";
        }
    }

} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {

        $error = "Please enter your username and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, username, password, role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        if ($stmt) {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] === 'admin') {

                        header("Location: admin_dashboard.php");
                        exit();

                    } elseif ($user['role'] === 'nurse') {

                        header("Location: nurse_dashboard.php");
                        exit();

                    } else {

                        $error = "Invalid user role.";
                    }

                } else {

                    $error = "Incorrect username or password.";
                }

            } else {

                $error = "Incorrect username or password.";
            }

            $stmt->close();

        } else {

            $error = "Unable to process login. Please try again.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MEDTRACK - Welcome Back</title>


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
           BODY
        ========================================================= */

        body {

            font-family: Arial, Helvetica, sans-serif;

            min-height: 100vh;

            overflow: hidden;

            position: relative;

            display: flex;

            align-items: center;

            justify-content: center;

            background:

                radial-gradient(
                    circle at 8% 15%,
                    rgba(75, 220, 190, 0.28),
                    transparent 25%
                ),

                radial-gradient(
                    circle at 92% 20%,
                    rgba(50, 145, 255, 0.28),
                    transparent 28%
                ),

                radial-gradient(
                    circle at 15% 90%,
                    rgba(0, 190, 220, 0.20),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 90% 90%,
                    rgba(65, 140, 255, 0.20),
                    transparent 30%
                ),

                linear-gradient(
                    135deg,
                    #e8faf7 0%,
                    #edf8ff 50%,
                    #eaf4ff 100%
                );
        }


        /* =========================================================
           BACKGROUND GRID
        ========================================================= */

        body::before {

            content: "";

            position: absolute;

            inset: 0;

            background-image:

                linear-gradient(
                    rgba(25, 150, 190, 0.055) 1px,
                    transparent 1px
                ),

                linear-gradient(
                    90deg,
                    rgba(25, 150, 190, 0.055) 1px,
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

            pointer-events: none;
        }


        /* =========================================================
           BACKGROUND GLOWS
        ========================================================= */

        .glow {

            position: absolute;

            border-radius: 50%;

            pointer-events: none;

            filter: blur(4px);
        }


        .glow-one {

            width: 330px;

            height: 330px;

            left: -130px;

            top: -100px;

            background: rgba(0, 185, 210, 0.16);

            animation: glowOne 9s ease-in-out infinite;
        }


        .glow-two {

            width: 400px;

            height: 400px;

            right: -160px;

            bottom: -160px;

            background: rgba(40, 120, 255, 0.15);

            animation: glowTwo 11s ease-in-out infinite;
        }


        .glow-three {

            width: 210px;

            height: 210px;

            right: 20%;

            top: -80px;

            background: rgba(0, 210, 170, 0.10);

            animation: glowThree 8s ease-in-out infinite;
        }


        @keyframes glowOne {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(30px, 25px);
            }
        }


        @keyframes glowTwo {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(-25px, -30px);
            }
        }


        @keyframes glowThree {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(-25px, 20px);
            }
        }


        /* =========================================================
           MEDICAL BACKGROUND SYMBOLS
        ========================================================= */

        .medical-symbol {

            position: absolute;

            width: 60px;

            height: 60px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 30px;

            color: rgba(35, 140, 205, 0.28);

            background: rgba(255,255,255,0.28);

            border: 1px solid rgba(255,255,255,0.65);

            backdrop-filter: blur(8px);

            box-shadow:
                0 12px 35px rgba(50, 130, 170, 0.08);

            pointer-events: none;

            z-index: 1;
        }


        .symbol-one {

            top: 10%;

            left: 6%;

            animation: symbolFloat 7s ease-in-out infinite;
        }


        .symbol-two {

            top: 15%;

            right: 8%;

            animation: symbolFloat 8s ease-in-out infinite reverse;
        }


        .symbol-three {

            bottom: 12%;

            left: 10%;

            animation: symbolFloat 9s ease-in-out infinite;
        }


        .symbol-four {

            bottom: 15%;

            right: 10%;

            animation: symbolFloat 7s ease-in-out infinite reverse;
        }


        @keyframes symbolFloat {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-16px) rotate(3deg);
            }
        }


        /* =========================================================
           DECORATIVE DOTS
        ========================================================= */

        .dots {

            position: absolute;

            width: 90px;

            height: 90px;

            background-image:
                radial-gradient(
                    circle,
                    rgba(40, 175, 205, 0.25) 3px,
                    transparent 3px
                );

            background-size: 22px 22px;

            pointer-events: none;

            z-index: 1;
        }


        .dots-one {

            top: 8%;

            left: 31%;
        }


        .dots-two {

            bottom: 7%;

            right: 29%;
        }


        /* =========================================================
           MAIN PAGE
        ========================================================= */

        .page {

            position: relative;

            z-index: 5;

            width: 100%;

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px;
        }


        /* =========================================================
           LOGIN CARD
        ========================================================= */

        .login-container {

            width: 100%;

            max-width: 980px;

            min-height: 560px;

            display: flex;

            overflow: hidden;

            border-radius: 28px;

            background: rgba(255,255,255,0.91);

            border: 1px solid rgba(255,255,255,0.9);

            box-shadow:

                0 35px 80px rgba(40, 100, 140, 0.20),

                0 10px 30px rgba(40, 100, 140, 0.10);

            backdrop-filter: blur(20px);

            animation: cardAppear 0.8s ease;
        }


        @keyframes cardAppear {

            from {

                opacity: 0;

                transform:
                    translateY(25px)
                    scale(0.97);
            }

            to {

                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);
            }
        }


        /* =========================================================
           LEFT PANEL
        ========================================================= */

        .left-side {

            width: 50%;

            position: relative;

            overflow: hidden;

            padding: 55px 48px;

            color: white;

            display: flex;

            flex-direction: column;

            justify-content: center;

            background:

                linear-gradient(
                    145deg,
                    #087bd5 0%,
                    #078b9b 48%,
                    #178d45 100%
                );
        }


        /* Decorative circles */

        .left-side::before {

            content: "";

            position: absolute;

            width: 330px;

            height: 330px;

            border-radius: 50%;

            background:
                rgba(255,255,255,0.07);

            top: -160px;

            right: -130px;
        }


        .left-side::after {

            content: "";

            position: absolute;

            width: 280px;

            height: 280px;

            border-radius: 50%;

            border:
                1px solid rgba(255,255,255,0.12);

            bottom: -150px;

            left: -100px;
        }


        /* =========================================================
           LOGO
        ========================================================= */

        .logo {

            position: relative;

            z-index: 3;

            font-size: 40px;

            font-weight: 800;

            letter-spacing: 3px;

            margin-bottom: 10px;
        }


        .logo-line {

            position: relative;

            z-index: 3;

            width: 60px;

            height: 4px;

            border-radius: 10px;

            margin-bottom: 30px;

            background:
                linear-gradient(
                    90deg,
                    #ffffff,
                    #62e3c1
                );
        }


        /* =========================================================
           LEFT HEADING
        ========================================================= */

        .left-side h1 {

            position: relative;

            z-index: 3;

            font-size: 30px;

            line-height: 1.2;

            margin-bottom: 18px;
        }


        .left-side p {

            position: relative;

            z-index: 3;

            max-width: 370px;

            font-size: 15px;

            line-height: 1.8;

            color: rgba(255,255,255,0.91);

            margin-bottom: 30px;
        }


        /* =========================================================
           FEATURES
        ========================================================= */

        .features {

            position: relative;

            z-index: 3;

            display: flex;

            flex-direction: column;

            gap: 14px;
        }


        .feature {

            display: flex;

            align-items: center;

            gap: 13px;

            font-size: 14px;

            font-weight: 600;
        }


        .feature-icon {

            width: 38px;

            height: 38px;

            flex-shrink: 0;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(255,255,255,0.16);

            border:
                1px solid rgba(255,255,255,0.18);

            font-size: 17px;

            box-shadow:
                0 5px 15px rgba(0,0,0,0.08);
        }


        /* =========================================================
           HEART RATE MONITOR
        ========================================================= */
        .heartbeat-box {

            position: relative;

            z-index: 4;

            width: 100%;

            height: 46px;

            margin-top: 15px;

            overflow: hidden;

            background: transparent;

            border: none;

            border-radius: 0;

            opacity: 0.95;
        }


        /*
         * The SVG is wider than the visible box.
         * It continuously moves from right to left.
         */

        .heartbeat-track {

            position: absolute;

            top: 0;

            left: 0;

            width: 200%;

            height: 46px;

            display: flex;

            animation:
                heartbeatMove 5.5s linear infinite;
        }


        .heartbeat-svg {

            width: 50%;

            height: 46px;

            flex-shrink: 0;

            overflow: visible;
        }


        .heartbeat-line {

            fill: none;

            stroke: #54efbd;

            stroke-width: 2;

            stroke-linecap: round;

            stroke-linejoin: round;

            filter:
                drop-shadow(
                    0 0 4px rgba(60,255,190,0.65)
                );
        }


        @keyframes heartbeatMove {

            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(-50%);
            }
        }


        /* =========================================================
           RIGHT SIDE
        ========================================================= */

        .right-side {

            width: 50%;

            padding: 60px 55px;

            display: flex;

            flex-direction: column;

            justify-content: center;

            background:
                rgba(255,255,255,0.94);
        }


        /* =========================================================
           WELCOME BACK
        ========================================================= */

        .login-title {

            color: #14213d;

            font-size: 32px;

            font-weight: 700;

            margin-bottom: 8px;
        }


        .login-subtitle {

            color: #718096;

            font-size: 14px;

            margin-bottom: 32px;
        }


        /* =========================================================
           ERROR
        ========================================================= */

        .error-message {

            background: #fff1f2;

            color: #be123c;

            border:
                1px solid #fecdd3;

            border-left:
                4px solid #e11d48;

            padding: 12px 14px;

            border-radius: 8px;

            margin-bottom: 18px;

            font-size: 13px;
        }


        /* =========================================================
           FORM
        ========================================================= */

        .form-group {

            margin-bottom: 20px;
        }


        .form-group label {

            display: block;

            font-size: 13px;

            font-weight: 700;

            color: #273444;

            margin-bottom: 8px;
        }


        .input-container {

            position: relative;
        }


        .input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 17px;

            color: #3d6b91;

            z-index: 2;
        }


        .input-container input {

            width: 100%;

            height: 52px;

            padding:
                0 45px;

            border:
                1px solid #d4dce6;

            border-radius: 10px;

            background: #ffffff;

            color: #172033;

            font-size: 14px;

            outline: none;

            transition: 0.25s;
        }


        .input-container input::placeholder {

            color: #98a2b3;
        }


        .input-container input:focus {

            border-color: #168ac4;

            box-shadow:
                0 0 0 4px
                rgba(22,138,196,0.10);
        }


        /* =========================================================
           SHOW PASSWORD
        ========================================================= */

        .show-password {

            position: absolute;

            right: 14px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: none;

            cursor: pointer;

            font-size: 16px;

            color: #667085;

            transition: 0.2s;
        }


        .show-password:hover {

            color: #1677bd;
        }


        /* =========================================================
           LOGIN BUTTON
        ========================================================= */

        .login-button {

            width: 100%;

            height: 52px;

            margin-top: 8px;

            border: none;

            border-radius: 10px;

            cursor: pointer;

            color: white;

            font-size: 15px;

            font-weight: 700;

            letter-spacing: 0.5px;

            background:
                linear-gradient(
                    90deg,
                    #087bd5,
                    #168c99,
                    #218b43
                );

            background-size: 200% 100%;

            transition: 0.3s;

            box-shadow:
                0 8px 20px
                rgba(20,130,160,0.18);
        }


        .login-button:hover {

            background-position: right center;

            transform: translateY(-2px);

            box-shadow:
                0 12px 25px
                rgba(20,130,160,0.25);
        }


        .login-button:active {

            transform: translateY(0);
        }


        /* =========================================================
           REGISTRATION BUTTON
        ========================================================= */

        .register-button {

            width: 100%;

            height: 52px;

            margin-top: 12px;

            border: 1px solid #168c99;

            border-radius: 10px;

            cursor: pointer;

            color: #168c99;

            background: #ffffff;

            font-size: 15px;

            font-weight: 700;

            letter-spacing: 0.5px;

            transition: 0.3s;
        }


        .register-button:hover {

            color: white;

            background: #168c99;

            transform: translateY(-2px);

            box-shadow:
                0 10px 22px
                rgba(20,130,160,0.18);
        }


        .register-button:active {

            transform: translateY(0);
        }


        /* =========================================================
           REGISTRATION MODAL
        ========================================================= */

        .registration-overlay {

            position: fixed;

            inset: 0;

            display: none;

            align-items: center;

            justify-content: center;

            padding: 25px;

            background: rgba(10, 35, 55, 0.42);

            backdrop-filter: blur(8px);

            z-index: 100;
        }


        .registration-overlay.show {

            display: flex;
        }


        .registration-card {

            width: 100%;

            max-width: 480px;

            max-height: 90vh;

            overflow-y: auto;

            padding: 38px 40px;

            border-radius: 22px;

            background: rgba(255,255,255,0.98);

            border: 1px solid rgba(255,255,255,0.9);

            box-shadow:
                0 30px 70px rgba(20,70,100,0.28);

            animation: registrationAppear 0.25s ease;
        }


        @keyframes registrationAppear {

            from {

                opacity: 0;

                transform: translateY(15px) scale(0.98);
            }

            to {

                opacity: 1;

                transform: translateY(0) scale(1);
            }
        }


        .registration-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }


        .registration-header h2 {

            color: #14213d;

            font-size: 28px;

            margin-bottom: 6px;
        }


        .registration-header p {

            color: #718096;

            font-size: 13px;

            line-height: 1.5;
        }


        .close-registration {

            width: 36px;

            height: 36px;

            flex-shrink: 0;

            border: none;

            border-radius: 50%;

            background: #eef4f8;

            color: #536273;

            cursor: pointer;

            font-size: 20px;

            transition: 0.2s;
        }


        .close-registration:hover {

            background: #dce8ef;

            color: #14213d;
        }


        .registration-card .form-group {

            margin-bottom: 17px;
        }


        .registration-card .input-container input {

            height: 50px;

            padding: 0 16px;
        }


        .registration-card .input-container select {

            width: 100%;

            height: 50px;

            padding: 0 16px;

            border: 1px solid #d4dce6;

            border-radius: 10px;

            background: #ffffff;

            color: #172033;

            font-size: 14px;

            outline: none;

            cursor: pointer;

            transition: 0.25s;
        }


        .registration-card .input-container select:focus {

            border-color: #168ac4;

            box-shadow:
                0 0 0 4px
                rgba(22,138,196,0.10);
        }


        .registration-card .input-container .register-input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            font-size: 17px;

            color: #3d6b91;

            z-index: 2;
        }


        .registration-card .input-container input.with-icon,
        .registration-card .input-container select.with-icon {

            padding-left: 45px;
        }


        .registration-submit {

            width: 100%;

            height: 50px;

            margin-top: 5px;

            border: none;

            border-radius: 10px;

            cursor: pointer;

            color: white;

            background:
                linear-gradient(
                    90deg,
                    #087bd5,
                    #168c99,
                    #218b43
                );

            background-size: 200% 100%;

            font-size: 15px;

            font-weight: 700;

            letter-spacing: 0.4px;

            transition: 0.3s;

            box-shadow:
                0 8px 20px
                rgba(20,130,160,0.16);
        }


        .registration-submit:hover {

            background-position: right center;

            transform: translateY(-2px);
        }


        .registration-note {

            text-align: center;

            margin-top: 15px;

            color: #8a94a6;

            font-size: 11px;

            line-height: 1.5;
        }


        .registration-success {

            background: #ecfdf3;

            color: #087443;

            border: 1px solid #bbf7d0;

            border-left: 4px solid #16a34a;

            padding: 12px 14px;

            border-radius: 8px;

            margin-bottom: 18px;

            font-size: 13px;
        }


        /* =========================================================
           SECURITY TEXT
        ========================================================= */

        .secure-text {

            text-align: center;

            color: #8a94a6;

            font-size: 12px;

            margin-top: 25px;
        }


        .secure-text span {

            color: #1677bd;

            margin-right: 4px;
        }



        /* =========================================================
           NTC LOGO - TOP RIGHT
        ========================================================= */

        .ntc-logo-top {
            position: fixed;
            top: 18px;
            right: 22px;
            width: 78px;
            height: 78px;
            object-fit: contain;
            z-index: 200;
            border-radius: 50%;
            background: rgba(255,255,255,0.96);
            padding: 4px;
            box-shadow:
                0 8px 22px rgba(30, 80, 110, 0.18);
            border: 1px solid rgba(255,255,255,0.9);
        }

        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 750px) {

            body {

                overflow-y: auto;
            }


            .page {

                padding: 20px;
            }


            .login-container {

                flex-direction: column;

                max-width: 500px;
            }


            .left-side {

                width: 100%;

                padding: 40px 35px;

                min-height: 390px;
            }


            .right-side {

                width: 100%;

                padding: 40px 35px;
            }


            .left-side h1 {

                font-size: 27px;
            }


            .medical-symbol {

                display: none;
            }
        }


        @media (max-width: 450px) {

            .left-side,
            .right-side {

                padding: 30px 25px;
            }


            .logo {

                font-size: 30px;
            }


            .login-title {

                font-size: 27px;
            }


            .heartbeat-box {

                margin-top: 12px;
            }
        }


        @media (max-width: 750px) {
            .ntc-logo-top {
                width: 64px;
                height: 64px;
                top: 12px;
                right: 12px;
            }
        }

        @media (max-width: 450px) {
            .ntc-logo-top {
                width: 54px;
                height: 54px;
                top: 10px;
                right: 10px;
            }
        }

    </style>

</head>


<body>

    <!-- NTC LOGO -->
    <img
        src="data:image/png;base64,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VAisyRa8tKSm3vaaLe997RU96RGdtdtXrPjf1IEvPfdtn5oFGYw4ajan+1bs3rKlnE8auiDV4tL9Fws4X3bkXEfqNMhzUyJhGJIMxiBLKQkobiFqIW5J0EBrHmGDwkCFLZu1TZpAdJx4hybtvcf0d074o+1apbvykd9621tfvwUACymtmOEaNASl83l1UZqki5+RnbEI/SyKF1WH4ikQqnn4j+971eTEwl9+4dEmBuDvPJnK3z98tOyXb718czFNSQJNmkgDitkIshyKUEpIQZEo1trLQzyW88vOK8Ylf3HKycQKELGUtNNgfjqIj49HwJCgCyhAiFzd4/ls04pOlDm5qnTg1t28au36f3pw6thct9IrSBXFesxKkaepYiUtllQY1AvaEnS8MBM1wuPjSQODjFWWuhhFwBP2FYSRFEu6t5cuu2xI+eDJqdlDz48eHj06lVq9NaN+sAYZ8qTcle29buvv/uHe9UML0YGs/szjPdb+/M1DZyafOjz9zwkGSqXaXfe8/oMfedtQfxmSQX4wVyu3Xi2SDN2Fs1AKViCGrBFnTpuGFLcRBAgDBE1p1yVs6qCFqC5Zi9MY0IH1D07YB04UHhgtn4pqUvH/3a/d9e537tFONEvodHHReaUfXPV7yWCFjooZAlnRpT72obtmp4Ovff25Fq32tX2hZf/uvsMlr7x314hKY1BLmMHs8ldBoonEpVXDkjbERehinrr+ohdargARCGCItSgFAZFpz07iYAgAACAASURBVKft+kQjGVsA06C1PoQIPshzDuGkqKuL19eif3/P1Tdtak206bsPzPrYmJKAUwBQ2kITewRVq0hRxyt7GWkUzk23wuzwWJxhu9AKsTXAR+7nIgLu6vL7+2XnZaths3DseHN8YvTUdIg+0f1sCgQRSUVnazb23vPTw+t39wgK5etubjUbwXPPbB3p/vgvv2F0RjVVZeut1916z13V3gLEAMRKX+De/eLfEQZpWuo+SI6OEMOklGXIEslaSFoStlUrRlBHOCdRXaKmxA2YlrUBxAtN8fCkuf+kvv9k8UijO/LwCz938/t/8+auCphdbEqnL3spjhiXFuhyW4O1rNYNlj/9ibdGaXzv/UcaslZ7xUP1A//9u88KzA2XrSqnkSdGIOw0ucxgqCwVTgg18YriE0mBPNflYuTr778AtQqYnF2JEMRG87Np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"
        alt="NTC Logo"
        class="ntc-logo-top"
    >


    <!-- =========================================================
         BACKGROUND EFFECTS
    ========================================================= -->

    <div class="glow glow-one"></div>

    <div class="glow glow-two"></div>

    <div class="glow glow-three"></div>


    <div class="medical-symbol symbol-one">
        ✚
    </div>


    <div class="medical-symbol symbol-two">
        +
    </div>


    <div class="medical-symbol symbol-three">
        ♡
    </div>


    <div class="medical-symbol symbol-four">
        ✚
    </div>


    <div class="dots dots-one"></div>

    <div class="dots dots-two"></div>



    <!-- =========================================================
         MAIN PAGE
    ========================================================= -->

    <main class="page">


        <div class="login-container">


            <!-- =================================================
                 LEFT SIDE
            ================================================= -->

            <div class="left-side">


                <div class="logo">
                    MEDTRACK
                </div>


                <div class="logo-line"></div>


                <h1>
                    Clinic Management
                    <br>
                    System
                </h1>


                <p>
                    A secure and organized system for managing
                    student registration, consultations,
                    medicines, prescriptions, and clinic records.
                </p>


                <div class="features">


                    <!-- FEATURE 1 -->

                    <div class="feature">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <span>
                            Secure Staff Access
                        </span>

                    </div>


                    <!-- FEATURE 2 -->

                    <div class="feature">

                        <div class="feature-icon">
                            +
                        </div>

                        <span>
                            Student Health Records
                        </span>

                    </div>


                    <!-- FEATURE 3 -->

                    <div class="feature">

                        <div class="feature-icon">
                            ▣
                        </div>

                        <span>
                            Medicine & Inventory Management
                        </span>

                    </div>


                </div>


                <!-- =================================================
                     SMALL CONTINUOUS HEART RATE MONITOR
                ================================================= -->

                <div class="heartbeat-box">

                    <div class="heartbeat-track">


                        <!-- FIRST WAVE -->

                        <svg
                            class="heartbeat-svg"
                            viewBox="0 0 500 42"
                            preserveAspectRatio="none"
                        >

                            <polyline
                                class="heartbeat-line"
                                points="
                                
0,23 35,23 48,22 55,20 62,23 72,23 80,23 86,25 92,23 98,23
108,23 112,8 118,39 124,23 136,23 150,23 165,23 180,23
194,23 205,23 214,21 220,17 226,23 238,23 246,23
252,6 258,40 264,23 278,23 300,23 320,23 340,23 360,23
375,23 388,23 398,19 404,23 415,23 430,23 445,23 460,23
475,23 500,23
                                "
                            />

                        </svg>


                        <!-- SECOND WAVE
                             Identical to first wave so the
                             animation loops continuously.
                        -->

                        <svg
                            class="heartbeat-svg"
                            viewBox="0 0 500 42"
                            preserveAspectRatio="none"
                        >

                            <polyline
                                class="heartbeat-line"
                                points="
                                
0,23 28,23 42,23 50,21 57,23 68,23 78,23
88,23 94,7 100,40 106,23 118,23 132,23 150,23 168,23
182,23 190,19 196,23 207,23 218,23 228,23 236,5 242,41 248,23
260,23 280,23 300,23 315,23 330,23 344,23 354,23 362,20 368,23
380,23 392,23 404,23 414,23 422,9 428,38 434,23 448,23 465,23 500,23
                                "
                            />

                        </svg>

                    </div>

                </div>


            </div>



            <!-- =================================================
                 RIGHT SIDE
            ================================================= -->

            <div class="right-side">


                <h2 class="login-title">
                    Welcome Back
                </h2>


                <p class="login-subtitle">
                    Sign in to access the MEDTRACK system.
                </p>



                <!-- =================================================
                     ERROR MESSAGE
                ================================================= -->

                <?php if (!empty($error)): ?>

                    <div class="error-message">

                        <?php echo htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>



                <!-- =================================================
                     LOGIN FORM
                ================================================= -->

                <form
                    method="POST"
                    action="login.php"
                >


                    <!-- USERNAME -->

                    <div class="form-group">

                        <label for="username">
                            Username
                        </label>


                        <div class="input-container">

                            <span class="input-icon">
                                👤
                            </span>


                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Enter your username"
                                autocomplete="username"
                                required
                            >

                        </div>

                    </div>



                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>


                        <div class="input-container">

                            <span class="input-icon">
                                🔒
                            </span>


                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >


                            <button
                                type="button"
                                class="show-password"
                                onclick="togglePassword()"
                                title="Show password"
                            >
                                👁
                            </button>

                        </div>

                    </div>



                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        name="login"
                        class="login-button"
                    >
                        LOGIN
                    </button>


                </form>


                <!-- =================================================
                     REGISTRATION BUTTON
                ================================================= -->

                <button
                    type="button"
                    class="register-button"
                    onclick="openRegistration()"
                >
                    CREATE AN ACCOUNT
                </button>



                <!-- =================================================
                     SECURITY MESSAGE
                ================================================= -->

                <div class="secure-text">

                    <span>
                        🔒
                    </span>

                    Authorized clinic personnel only

                </div>


            </div>


        </div>


    </main>



    <!-- =========================================================
         REGISTRATION MODAL
    ========================================================= -->

    <div
        class="registration-overlay<?php echo (!empty($register_error) || !empty($register_success)) ? ' show' : ''; ?>"
        id="registrationOverlay"
    >

        <div class="registration-card">

            <div class="registration-header">

                <div>
                    <h2>Create Account</h2>
                    <p>Register a new MEDTRACK staff account.</p>
                </div>

                <button
                    type="button"
                    class="close-registration"
                    onclick="closeRegistration()"
                    title="Close"
                >
                    ×
                </button>

            </div>


            <?php if (!empty($register_error)): ?>

                <div class="error-message">
                    <?php echo htmlspecialchars($register_error); ?>
                </div>

            <?php endif; ?>


            <?php if (!empty($register_success)): ?>

                <div class="registration-success">
                    <?php echo htmlspecialchars($register_success); ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="login.php">

                <!-- REGISTRATION USERNAME -->

                <div class="form-group">

                    <label for="register_username">
                        Username
                    </label>

                    <div class="input-container">

                        <span class="register-input-icon">
                            👤
                        </span>

                        <input
                            class="with-icon"
                            type="text"
                            id="register_username"
                            name="register_username"
                            placeholder="Enter your username"
                            autocomplete="username"
                            value="<?php echo htmlspecialchars($_POST['register_username'] ?? ''); ?>"
                            required
                        >

                    </div>

                </div>


                <!-- REGISTRATION PASSWORD -->

                <div class="form-group">

                    <label for="register_password">
                        Password
                    </label>

                    <div class="input-container">

                        <span class="register-input-icon">
                            🔒
                        </span>

                        <input
                            class="with-icon"
                            type="password"
                            id="register_password"
                            name="register_password"
                            placeholder="Enter your password"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <div class="input-container">

                        <span class="register-input-icon">
                            🔐
                        </span>

                        <input
                            class="with-icon"
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <!-- ROLE -->

                <div class="form-group">

                    <label for="register_role">
                        Account Role
                    </label>

                    <div class="input-container">

                        <span class="register-input-icon">
                            🏥
                        </span>

                        <select
                            class="with-icon"
                            id="register_role"
                            name="register_role"
                            required
                        >
                            <option value="" disabled <?php echo empty($_POST['register_role']) ? 'selected' : ''; ?>>
                                Select a role
                            </option>

                            <option
                                value="admin"
                                <?php echo (($_POST['register_role'] ?? '') === 'admin') ? 'selected' : ''; ?>
                            >
                                Administrator
                            </option>

                            <option
                                value="nurse"
                                <?php echo (($_POST['register_role'] ?? '') === 'nurse') ? 'selected' : ''; ?>
                            >
                                Nurse
                            </option>
                        </select>

                    </div>

                </div>


                <button
                    type="submit"
                    name="register"
                    class="registration-submit"
                >
                    REGISTER ACCOUNT
                </button>

            </form>


            <div class="registration-note">
                Choose the role that matches the staff member's access level.
            </div>

        </div>

    </div>



    <!-- =========================================================
         PASSWORD SCRIPT
    ========================================================= -->

    <script>

        function openRegistration() {

            document
                .getElementById("registrationOverlay")
                .classList.add("show");

        }


        function closeRegistration() {

            document
                .getElementById("registrationOverlay")
                .classList.remove("show");

        }


        document
            .getElementById("registrationOverlay")
            .addEventListener("click", function(event) {

                if (event.target === this) {

                    closeRegistration();

                }

            });


        function togglePassword() {

            const password =
                document.getElementById("password");

            const button =
                document.querySelector(".show-password");


            if (password.type === "password") {

                password.type = "text";

                button.textContent = "🙈";

                button.title = "Hide password";

            } else {

                password.type = "password";

                button.textContent = "👁";

                button.title = "Show password";
            }

        }

    </script>


</body>

</html>