<?php

require_once "Database.php";

header("Content-Type: application/json");

if (!isset($_GET["student_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "No student ID provided."
    ]);
    exit();
}

$student_id = trim($_GET["student_id"]);

$sql = "SELECT * FROM students WHERE student_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $student_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $student = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "student" => $student
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Student not found."
    ]);
}

$stmt->close();
$conn->close();

?>

