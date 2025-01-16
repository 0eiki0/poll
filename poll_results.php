<?php

include 'config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Umfrage-ID aus GET-Request abrufen
if (isset($_GET['poll_id'])) {
    $poll_id = $_GET['poll_id'];
    $stmt = $conn->prepare("
        SELECT o.option_text, COUNT(v.id) as votes
        FROM poll_options o
        LEFT JOIN poll_votes v ON o.id = v.poll_option_id
        WHERE o.poll_id = ?
        GROUP BY o.id
    ");
    $stmt->bind_param("s", $poll_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $response = [];
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
    $stmt->close();
    echo json_encode($response); // JSON-Antwort senden
} else {
    echo json_encode(["error" => "Poll ID fehlt."]);
}

$conn->close();
?>
