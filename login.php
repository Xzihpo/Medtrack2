```php
<?php

session_start();

require_once "Database.php";

$error = "";
$register_error = "";
$register_success = "";


/*
|--------------------------------------------------------------------------
| CREATE LOGIN HISTORY TABLE
|--------------------------------------------------------------------------
|
| This makes sure the table exists before a login is recorded.
|
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS login_history (

        id INT AUTO_INCREMENT PRIMARY KEY,

        username VARCHAR(255) NOT NULL,

        role VARCHAR(50) NOT NULL,

        login_time DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP

    )
");


/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['user_id']) &&
    isset($_SESSION['role'])
) {

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
| PROCESS LOGIN / REGISTRATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["register"])
) {


    /*
    |--------------------------------------------------------------------------
    | PROCESS REGISTRATION
    |--------------------------------------------------------------------------
    */

    $register_username =
        trim(
            $_POST["register_username"] ?? ""
        );

    $register_password =
        $_POST["register_password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";

    $register_role =
        $_POST["register_role"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATE REGISTRATION
    |--------------------------------------------------------------------------
    */

    if (
        empty($register_username) ||
        empty($register_password) ||
        empty($confirm_password) ||
        empty($register_role)
    ) {

        $register_error =
            "Please Complete All Registration Fields.";

    }

    elseif (
        $register_password !==
        $confirm_password
    ) {

        $register_error =
            "Passwords Do Not Match.";

    }

    elseif (
        !in_array(
            $register_role,
            ["admin", "nurse"],
            true
        )
    ) {

        $register_error =
            "Please Select A Valid Role.";

    }

    else {


        /*
        |--------------------------------------------------------------------------
        | CHECK IF USERNAME ALREADY EXISTS
        |--------------------------------------------------------------------------
        */

        $check_stmt =
            $conn->prepare(
                "SELECT id
                 FROM users
                 WHERE username = ?
                 LIMIT 1"
            );


        if ($check_stmt) {

            $check_stmt->bind_param(
                "s",
                $register_username
            );

            $check_stmt->execute();

            $check_result =
                $check_stmt->get_result();


            if (
                $check_result->num_rows > 0
            ) {

                $register_error =
                    "Username Already Exists. Please Choose Another Username.";

            }

            else {


                /*
                |--------------------------------------------------------------------------
                | HASH PASSWORD
                |--------------------------------------------------------------------------
                */

                $hashed_password =
                    password_hash(
                        $register_password,
                        PASSWORD_DEFAULT
                    );


                /*
                |--------------------------------------------------------------------------
                | CREATE ACCOUNT
                |--------------------------------------------------------------------------
                */

                $insert_stmt =
                    $conn->prepare(
                        "INSERT INTO users
                        (username, password, role)
                        VALUES (?, ?, ?)"
                    );


                if ($insert_stmt) {

                    $insert_stmt->bind_param(
                        "sss",
                        $register_username,
                        $hashed_password,
                        $register_role
                    );


                    if (
                        $insert_stmt->execute()
                    ) {

                        $register_success =
                            "Account Created Successfully. You Can Now Log In.";

                    }

                    else {

                        $register_error =
                            "Unable To Create The Account. Please Try Again.";

                    }


                    $insert_stmt->close();

                }

                else {

                    $register_error =
                        "Unable To Process Registration. Please Try Again.";

                }

            }


            $check_stmt->close();

        }

        else {

            $register_error =
                "Unable To Process Registration. Please Try Again.";

        }

    }


}


/*
|--------------------------------------------------------------------------
| PROCESS LOGIN
|--------------------------------------------------------------------------
*/

elseif (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    /*
    |--------------------------------------------------------------------------
    | GET LOGIN DATA
    |--------------------------------------------------------------------------
    */

    $username =
        trim(
            $_POST['username'] ?? ''
        );

    $password =
        $_POST['password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATE LOGIN
    |--------------------------------------------------------------------------
    */

    if (
        empty($username) ||
        empty($password)
    ) {

        $error =
            "Please Enter Your Username And Password.";

    }

    else {


        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $stmt =
            $conn->prepare(
                "SELECT
                    id,
                    username,
                    password,
                    role
                 FROM users
                 WHERE username = ?
                 LIMIT 1"
            );


        if ($stmt) {

            $stmt->bind_param(
                "s",
                $username
            );

            $stmt->execute();

            $result =
                $stmt->get_result();


            /*
            |--------------------------------------------------------------------------
            | USER FOUND
            |--------------------------------------------------------------------------
            */

            if (
                $result->num_rows === 1
            ) {

                $user =
                    $result->fetch_assoc();


                /*
                |--------------------------------------------------------------------------
                | CHECK PASSWORD
                |--------------------------------------------------------------------------
                */

                if (
                    password_verify(
                        $password,
                        $user['password']
                    )
                ) {


                    /*
                    |--------------------------------------------------------------------------
                    | CHECK VALID ROLE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $user['role'] !== 'admin' &&
                        $user['role'] !== 'nurse'
                    ) {

                        $error =
                            "Invalid User Role.";

                    }

                    else {


                        /*
                        |--------------------------------------------------------------------------
                        | CREATE SESSION
                        |--------------------------------------------------------------------------
                        */

                        $_SESSION['user_id'] =
                            $user['id'];

                        $_SESSION['username'] =
                            $user['username'];

                        $_SESSION['role'] =
                            $user['role'];


                        /*
                        |--------------------------------------------------------------------------
                        | SAVE LOGIN HISTORY
                        |--------------------------------------------------------------------------
                        |
                        | IMPORTANT:
                        |
                        | The login_history table contains:
                        |
                        | id
                        | username
                        | role
                        | login_time
                        |
                        | There is NO user_id column.
                        |
                        */

                        $history_stmt =
                            $conn->prepare(
                                "INSERT INTO login_history
                                (username, role)
                                VALUES (?, ?)"
                            );


                        if ($history_stmt) {

                            $history_stmt->bind_param(
                                "ss",
                                $user['username'],
                                $user['role']
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | EXECUTE LOGIN HISTORY INSERT
                            |--------------------------------------------------------------------------
                            */

                            $history_stmt->execute();

                            $history_stmt->close();

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | CLOSE USER QUERY
                        |--------------------------------------------------------------------------
                        */

                        $stmt->close();


                        /*
                        |--------------------------------------------------------------------------
                        | REDIRECT BASED ON ROLE
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $user['role'] === 'admin'
                        ) {

                            header(
                                "Location: admin_dashboard.php"
                            );

                            exit();

                        }


                        if (
                            $user['role'] === 'nurse'
                        ) {

                            header(
                                "Location: nurse_dashboard.php"
                            );

                            exit();

                        }

                    }

                }

                else {

                    $error =
                        "Incorrect Username Or Password.";

                }

            }

            else {

                $error =
                    "Incorrect Username Or Password.";

            }


            /*
            |--------------------------------------------------------------------------
            | CLOSE STATEMENT
            |--------------------------------------------------------------------------
            */

            if ($stmt) {

                $stmt->close();

            }

        }

        else {

            $error =
                "Unable To Process Login. Please Try Again.";

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

    <title>
        MEDTRACK - Welcome Back
    </title>


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

            font-family:
                Arial,
                Helvetica,
                sans-serif;

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

            background-size:
                45px 45px;

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

            background:
                rgba(0, 185, 210, 0.16);

            animation:
                glowOne 9s ease-in-out infinite;

        }


        .glow-two {

            width: 400px;

            height: 400px;

            right: -160px;

            bottom: -160px;

            background:
                rgba(40, 120, 255, 0.15);

            animation:
                glowTwo 11s ease-in-out infinite;

        }


        .glow-three {

            width: 210px;

            height: 210px;

            right: 20%;

            top: -80px;

            background:
                rgba(0, 210, 170, 0.10);

            animation:
                glowThree 8s ease-in-out infinite;

        }


        @keyframes glowOne {

            0%,
            100% {

                transform:
                    translate(0, 0);

            }

            50% {

                transform:
                    translate(30px, 25px);

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
                    translate(-25px, -30px);

            }

        }


        @keyframes glowThree {

            0%,
            100% {

                transform:
                    translate(0, 0);

            }

            50% {

                transform:
                    translate(-25px, 20px);

            }

        }


        /* =========================================================
           MEDICAL SYMBOLS
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

            color:
                rgba(35, 140, 205, 0.28);

            background:
                rgba(255,255,255,0.28);

            border:
                1px solid
                rgba(255,255,255,0.65);

            backdrop-filter:
                blur(8px);

            box-shadow:
                0 12px 35px
                rgba(50, 130, 170, 0.08);

            pointer-events: none;

            z-index: 1;

        }


        .symbol-one {

            top: 10%;

            left: 6%;

            animation:
                symbolFloat 7s ease-in-out infinite;

        }


        .symbol-two {

            top: 15%;

            right: 8%;

            animation:
                symbolFloat 8s ease-in-out infinite reverse;

        }


        .symbol-three {

            bottom: 12%;

            left: 10%;

            animation:
                symbolFloat 9s ease-in-out infinite;

        }


        .symbol-four {

            bottom: 15%;

            right: 10%;

            animation:
                symbolFloat 7s ease-in-out infinite reverse;

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
                    rotate(3deg);

            }

        }


        /* =========================================================
           DOTS
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

            background-size:
                22px 22px;

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

            background:
                rgba(255,255,255,0.91);

            border:
                1px solid
                rgba(255,255,255,0.9);

            box-shadow:

                0 35px 80px
                rgba(40, 100, 140, 0.20),

                0 10px 30px
                rgba(40, 100, 140, 0.10);

            backdrop-filter:
                blur(20px);

            animation:
                cardAppear 0.8s ease;

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
                1px solid
                rgba(255,255,255,0.12);

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

            color:
                rgba(255,255,255,0.91);

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
                1px solid
                rgba(255,255,255,0.18);

            font-size: 17px;

            box-shadow:
                0 5px 15px
                rgba(0,0,0,0.08);

        }


        /* =========================================================
           HEARTBEAT
        ========================================================= */

        .heartbeat-box {

            position: relative;

            z-index: 4;

            width: 100%;

            height: 42px;

            margin-top: 15px;

            overflow: hidden;

            border-radius: 8px;

            background:

                linear-gradient(
                    90deg,
                    rgba(0, 180, 190, 0.18),
                    rgba(0, 255, 150, 0.08)
                );

            border-top:
                1px solid
                rgba(255,255,255,0.08);

            border-bottom:
                1px solid
                rgba(255,255,255,0.08);

            opacity: 0.9;

        }


        .heartbeat-track {

            position: absolute;

            top: 0;

            left: 0;

            width: 200%;

            height: 42px;

            display: flex;

            animation:
                heartbeatMove 5s linear infinite;

        }


        .heartbeat-svg {

            width: 50%;

            height: 42px;

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
                    0 0 4px
                    rgba(60,255,190,0.65)
                );

        }


        @keyframes heartbeatMove {

            from {

                transform:
                    translateX(0);

            }

            to {

                transform:
                    translateX(-50%);

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
                1px solid
                #fecdd3;

            border-left:
                4px solid
                #e11d48;

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

            transform:
                translateY(-50%);

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
                1px solid
                #d4dce6;

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

            border-color:
                #168ac4;

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

            transform:
                translateY(-50%);

            border: none;

            background: none;

            cursor: pointer;

            font-size: 16px;

            color: #667085;

            transition: 0.2s;

        }


        .show-password:hover {

            color:
                #1677bd;

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

            background-size:
                200% 100%;

            transition: 0.3s;

            box-shadow:

                0 8px 20px
                rgba(20,130,160,0.18);

        }


        .login-button:hover {

            background-position:
                right center;

            transform:
                translateY(-2px);

            box-shadow:

                0 12px 25px
                rgba(20,130,160,0.25);

        }


        .login-button:active {

            transform:
                translateY(0);

        }


        /* =========================================================
           REGISTRATION BUTTON
        ========================================================= */

        .register-button {

            width: 100%;

            height: 52px;

            margin-top: 12px;

            border:
                1px solid
                #168c99;

            border-radius: 10px;

            cursor: pointer;

            color:
                #168c99;

            background: #ffffff;

            font-size: 15px;

            font-weight: 700;

            letter-spacing: 0.5px;

            transition: 0.3s;

        }


        .register-button:hover {

            color: white;

            background:
                #168c99;

            transform:
                translateY(-2px);

            box-shadow:

                0 10px 22px
                rgba(20,130,160,0.18);

        }


        .register-button:active {

            transform:
                translateY(0);

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

            background:
                rgba(10, 35, 55, 0.42);

            backdrop-filter:
                blur(8px);

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

            background:
                rgba(255,255,255,0.98);

            border:
                1px solid
                rgba(255,255,255,0.9);

            box-shadow:
                0 30px 70px
                rgba(20,70,100,0.28);

            animation:
                registrationAppear 0.25s ease;

        }


        @keyframes registrationAppear {

            from {

                opacity: 0;

                transform:
                    translateY(15px)
                    scale(0.98);

            }

            to {

                opacity: 1;

                transform:
                    translateY(0)
                    scale(1);

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

            color:
                #14213d;

            font-size: 28px;

            margin-bottom: 6px;

        }


        .registration-header p {

            color:
                #718096;

            font-size: 13px;

            line-height: 1.5;

        }


        .close-registration {

            width: 36px;

            height: 36px;

            flex-shrink: 0;

            border: none;

            border-radius: 50%;

            background:
                #eef4f8;

            color:
                #536273;

            cursor: pointer;

            font-size: 20px;

            transition: 0.2s;

        }


        .close-registration:hover {

            background:
                #dce8ef;

            color:
                #14213d;

        }


        .registration-card .form-group {

            margin-bottom: 17px;

        }


        .registration-card .input-container input {

            height: 50px;

            padding:
                0 16px;

        }


        .registration-card .input-container select {

            width: 100%;

            height: 50px;

            padding:
                0 16px;

            border:
                1px solid
                #d4dce6;

            border-radius: 10px;

            background:
                #ffffff;

            color:
                #172033;

            font-size: 14px;

            outline: none;

            cursor: pointer;

            transition: 0.25s;

        }


        .registration-card .input-container select:focus {

            border-color:
                #168ac4;

            box-shadow:

                0 0 0 4px
                rgba(22,138,196,0.10);

        }


        .registration-card
        .input-container
        .register-input-icon {

            position: absolute;

            left: 15px;

            top: 50%;

            transform:
                translateY(-50%);

            font-size: 17px;

            color:
                #3d6b91;

            z-index: 2;

        }


        .registration-card
        .input-container
        input.with-icon,
        .registration-card
        .input-container
        select.with-icon {

            padding-left:
                45px;

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

            background-size:
                200% 100%;

            font-size: 15px;

            font-weight: 700;

            letter-spacing: 0.4px;

            transition: 0.3s;

            box-shadow:

                0 8px 20px
                rgba(20,130,160,0.16);

        }


        .registration-submit:hover {

            background-position:
                right center;

            transform:
                translateY(-2px);

        }


        .registration-note {

            text-align: center;

            margin-top: 15px;

            color:
                #8a94a6;

            font-size: 11px;

            line-height: 1.5;

        }


        .registration-success {

            background:
                #ecfdf3;

            color:
                #087443;

            border:
                1px solid
                #bbf7d0;

            border-left:
                4px solid
                #16a34a;

            padding:
                12px 14px;

            border-radius: 8px;

            margin-bottom: 18px;

            font-size: 13px;

        }


        /* =========================================================
           SECURITY TEXT
        ========================================================= */

        .secure-text {

            text-align: center;

            color:
                #8a94a6;

            font-size: 12px;

            margin-top: 25px;

        }


        .secure-text span {

            color:
                #1677bd;

            margin-right: 4px;

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

                padding:
                    40px 35px;

                min-height: 390px;

            }


            .right-side {

                width: 100%;

                padding:
                    40px 35px;

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

                padding:
                    30px 25px;

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


    </style>

</head>


<body>


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


                    <div class="feature">

                        <div class="feature-icon">
                            ✓
                        </div>

                        <span>
                            Secure Staff Access
                        </span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            +
                        </div>

                        <span>
                            Student Health Records
                        </span>

                    </div>


                    <div class="feature">

                        <div class="feature-icon">
                            ▣
                        </div>

                        <span>
                            Medicine &amp; Inventory Management
                        </span>

                    </div>


                </div>


                <!-- =================================================
                     HEART RATE MONITOR
                ================================================= -->

                <div class="heartbeat-box">

                    <div class="heartbeat-track">


                        <svg
                            class="heartbeat-svg"
                            viewBox="0 0 500 42"
                            preserveAspectRatio="none"
                        >

                            <polyline
                                class="heartbeat-line"
                                points="
                                0,22
                                25,22
                                35,21
                                42,23
                                50,22
                                58,22
                                68,22
                                76,21
                                84,22
                                92,22
                                100,22
                                108,20
                                114,20
                                120,22
                                130,22
                                140,22
                                150,22
                                160,22
                                170,22
                                178,22
                                185,22
                                192,22
                                198,20
                                202,4
                                207,34
                                213,22
                                220,22
                                230,22
                                240,22
                                250,22
                                260,22
                                270,22
                                278,21
                                285,22
                                295,22
                                305,22
                                312,22
                                318,20
                                323,4
                                328,35
                                334,22
                                342,22
                                350,22
                                360,22
                                370,22
                                380,22
                                390,22
                                400,22
                                410,22
                                418,21
                                425,22
                                435,22
                                445,22
                                455,22
                                465,22
                                475,22
                                485,22
                                500,22
                                "
                            />

                        </svg>


                        <svg
                            class="heartbeat-svg"
                            viewBox="0 0 500 42"
                            preserveAspectRatio="none"
                        >

                            <polyline
                                class="heartbeat-line"
                                points="
                                0,22
                                25,22
                                35,21
                                42,23
                                50,22
                                58,22
                                68,22
                                76,21
                                84,22
                                92,22
                                100,22
                                108,20
                                114,20
                                120,22
                                130,22
                                140,22
                                150,22
                                160,22
                                170,22
                                178,22
                                185,22
                                192,22
                                198,20
                                202,4
                                207,34
                                213,22
                                220,22
                                230,22
                                240,22
                                250,22
                                260,22
                                270,22
                                278,21
                                285,22
                                295,22
                                305,22
                                312,22
                                318,20
                                323,4
                                328,35
                                334,22
                                342,22
                                350,22
                                360,22
                                370,22
                                380,22
                                390,22
                                400,22
                                410,22
                                418,21
                                425,22
                                435,22
                                445,22
                                455,22
                                465,22
                                475,22
                                485,22
                                500,22
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



                <!-- ERROR -->

                <?php if (!empty($error)): ?>

                    <div class="error-message">

                        <?php

                        echo htmlspecialchars(
                            $error
                        );

                        ?>

                    </div>

                <?php endif; ?>



                <!-- LOGIN FORM -->

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



                <!-- REGISTRATION BUTTON -->

                <button
                    type="button"
                    class="register-button"
                    onclick="openRegistration()"
                >
                    CREATE AN ACCOUNT
                </button>



                <!-- SECURITY -->

                <div class="secure-text">

                    <span>
                        🔒
                    </span>

                    Authorized Clinic Personnel Only

                </div>


            </div>


        </div>


    </main>



    <!-- =========================================================
         REGISTRATION MODAL
    ========================================================= -->

    <div
        class="registration-overlay<?php

            echo (
                !empty($register_error) ||
                !empty($register_success)
            )
                ? ' show'
                : '';

        ?>"
        id="registrationOverlay"
    >


        <div class="registration-card">


            <div class="registration-header">


                <div>

                    <h2>
                        Create Account
                    </h2>

                    <p>
                        Register A New MEDTRACK Staff Account.
                    </p>

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

                    <?php

                    echo htmlspecialchars(
                        $register_error
                    );

                    ?>

                </div>

            <?php endif; ?>



            <?php if (!empty($register_success)): ?>

                <div class="registration-success">

                    <?php

                    echo htmlspecialchars(
                        $register_success
                    );

                    ?>

                </div>

            <?php endif; ?>



            <form
                method="POST"
                action="login.php"
            >


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
                            value="<?php

                                echo htmlspecialchars(
                                    $_POST[
                                        'register_username'
                                    ] ?? ''
                                );

                            ?>"
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


                            <option
                                value=""
                                disabled
                                <?php

                                echo empty(
                                    $_POST['register_role']
                                )
                                    ? 'selected'
                                    : '';

                                ?>
                            >
                                Select A Role
                            </option>


                            <option
                                value="admin"
                                <?php

                                echo (
                                    (
                                        $_POST[
                                            'register_role'
                                        ] ?? ''
                                    )
                                    ===
                                    'admin'
                                )
                                    ? 'selected'
                                    : '';

                                ?>
                            >
                                Administrator
                            </option>


                            <option
                                value="nurse"
                                <?php

                                echo (
                                    (
                                        $_POST[
                                            'register_role'
                                        ] ?? ''
                                    )
                                    ===
                                    'nurse'
                                )
                                    ? 'selected'
                                    : '';

                                ?>
                            >
                                Nurse
                            </option>


                        </select>

                    </div>

                </div>



                <!-- REGISTER -->

                <button
                    type="submit"
                    name="register"
                    class="registration-submit"
                >
                    REGISTER ACCOUNT
                </button>


            </form>



            <div class="registration-note">

                Choose The Role That Matches The Staff Member's
                Access Level.

            </div>


        </div>

    </div>



    <!-- =========================================================
         JAVASCRIPT
    ========================================================= -->

    <script>


        function openRegistration() {

            document
                .getElementById(
                    "registrationOverlay"
                )
                .classList
                .add("show");

        }


        function closeRegistration() {

            document
                .getElementById(
                    "registrationOverlay"
                )
                .classList
                .remove("show");

        }


        document
            .getElementById(
                "registrationOverlay"
            )
            .addEventListener(
                "click",
                function(event) {

                    if (
                        event.target === this
                    ) {

                        closeRegistration();

                    }

                }
            );


        function togglePassword() {

            const password =
                document.getElementById(
                    "password"
                );

            const button =
                document.querySelector(
                    ".show-password"
                );


            if (
                password.type ===
                "password"
            ) {

                password.type =
                    "text";

                button.textContent =
                    "🙈";

                button.title =
                    "Hide Password";

            }

            else {

                password.type =
                    "password";

                button.textContent =
                    "👁";

                button.title =
                    "Show Password";

            }

        }


    </script>


</body>

</html>
```