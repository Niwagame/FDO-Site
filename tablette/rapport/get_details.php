<?php
// get_details.php
$description = $_GET['description'];
$conn = new mysqli("localhost", "username", "password", "database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT article, details FROM amende WHERE description = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $description);
$stmt->execute();
$stmt->bind_result($article, $details);
$stmt->fetch();

echo json_encode(["article" => $article, "details" => $details]);

$stmt->close();
$conn->close();
?>
