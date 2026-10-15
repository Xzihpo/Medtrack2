<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "Database.php";

if ($conn->connect_error) {

    echo "❌ DATABASE CONNECTION FAILED!<br>";
    echo "Error: " . $conn->connect_error;

} else {

    echo "✅ DATABASE CONNECTION SUCCESSFUL!";

}

?>