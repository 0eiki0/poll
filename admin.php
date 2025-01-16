<?php
session_start();

// Datenbankkonfiguration
$servername = "localhost";
$username = "user";
$password = "passwort";
$dbname = "strawpoll";

// Verbindung zur Datenbank herstellen
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Admin-Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($password_hash);
    $stmt->fetch();
    $stmt->close();
	if ($password_hash && password_verify($password, $password_hash)) {
		$_SESSION['admin_logged_in'] = true;
		$_SESSION['username'] = $username; // Benutzername in die Session speichern
		header("Location: " . $_SERVER['PHP_SELF']);
    exit;
	}
}

// Admin-Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Benutzer hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user']) && isset($_SESSION['admin_logged_in'])) {
    $new_username = $_POST['new_username'];
    $new_password = $_POST['new_password'];
    if ($new_username && $new_password) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $new_username, $password_hash);
        $stmt->execute();
        $stmt->close();
        $message = "Neuer Benutzer wurde erfolgreich hinzugefügt.";
    } else {
        $message = "Fehler: Benutzername und Passwort erforderlich.";
    }
}

// Benutzer löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user']) && isset($_SESSION['admin_logged_in'])) {
    $delete_username = $_POST['delete_username'];
    if ($delete_username) {
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $delete_username);
        $stmt->execute();
        $stmt->close();
        $message = "Benutzer wurde erfolgreich gelöscht.";
    } else {
        $message = "Fehler: Kein Benutzer ausgewählt.";
    }
}

// Umfrage löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_poll']) && isset($_SESSION['admin_logged_in'])) {
    $poll_id = $_POST['poll_id'];
    deletePoll($poll_id, $conn);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Alle Umfragen löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_polls']) && isset($_SESSION['admin_logged_in'])) {
    $all_poll_ids = $conn->query("SELECT id FROM poll");
    while ($row = $all_poll_ids->fetch_assoc()) {
        deletePoll($row['id'], $conn);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Funktion zum Löschen einer Umfrage
function deletePoll($poll_id, $conn) {
    $stmt = $conn->prepare("DELETE FROM poll_options WHERE poll_id = ?");
    $stmt->bind_param("i", $poll_id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM poll_votes WHERE poll_id = ?");
    $stmt->bind_param("i", $poll_id);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM poll WHERE id = ?");
    $stmt->bind_param("i", $poll_id);
    $stmt->execute();
    $stmt->close();
}

// Ajax-Anfrage, um die Umfrage-Details abzurufen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_poll_details']) && isset($_SESSION['admin_logged_in'])) {
    $poll_id = $_POST['poll_id'];
    $poll_query = $conn->prepare("SELECT * FROM poll WHERE id = ?");
    $poll_query->bind_param("i", $poll_id);
    $poll_query->execute();
    $poll_result = $poll_query->get_result()->fetch_assoc();
    $poll_query->close();
    $options_query = $conn->prepare("SELECT * FROM poll_options WHERE poll_id = ?");
    $options_query->bind_param("i", $poll_id);
    $options_query->execute();
    $options_result = $options_query->get_result();
    $options_query->close();
    $poll_details = [
        'poll' => $poll_result,
        'options' => $options_result->fetch_all(MYSQLI_ASSOC)
    ];
    echo json_encode($poll_details);
    exit;
}

// Alle Daten zur Anzeige abrufen
$all_polls = $conn->query("SELECT * FROM poll ORDER BY created_at DESC");
$all_users = $conn->query("SELECT username FROM admin_users");

// Passwort ändern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password']) && isset($_SESSION['admin_logged_in'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Benutzername aus der Session abrufen (als Beispielcode)
    $current_username = $_SESSION['username'] ?? null;

    if ($current_username) {
        // Überprüfen, ob das aktuelle Passwort korrekt ist
        $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $current_username);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($current_password, $password_hash)) {
            // Neues Passwort und Bestätigung prüfen
            if ($new_password === $confirm_password) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
                $stmt->bind_param("ss", $new_password_hash, $current_username);
                if ($stmt->execute()) {
                    $message = "Passwort wurde erfolgreich geändert.";
                } else {
                    $message = "Fehler beim Ändern des Passworts.";
                }
                $stmt->close();
            } else {
                $message = "Neues Passwort und Bestätigung stimmen nicht überein.";
            }
        } else {
            $message = "Das aktuelle Passwort ist falsch.";
        }
    } else {
        $message = "Fehler: Benutzername nicht gefunden.";
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Bereich</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .admin-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
        }
        select, input, button {
            padding: 10px;
            margin-bottom: 10px;
            font-size: 16px;
        }
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        #poll-details {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
        }
    </style>
    <script>
        function showPollDetails(pollId) {
            if (!pollId) {
                document.getElementById('poll-details').innerHTML = "<p>Bitte eine Umfrage auswählen.</p>";
                return;
            }
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'fetch_poll_details=1&poll_id=' + pollId
            })
            .then(res => res.json())
            .then(data => {
                let details = `<h4>Umfrage-ID:</h4><p>${data.poll.id}</p>`;
                details += `<h4>Frage:</h4><p>${data.poll.question}</p>`;
                details += "<h4>Antwortmöglichkeiten:</h4><ul>";
                data.options.forEach(option => {
                    details += `<li>${option.option_text}</li>`;
                });
                details += "</ul>";
                document.getElementById('poll-details').innerHTML = details;
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="admin-section">
            <?php if (isset($_SESSION['admin_logged_in'])): ?>
                <h1>Admin-Bereich</h1>
                <form method="post">
                    <label for="poll_id">Umfragen auswählen:</label>
                    <select name="poll_id" id="poll_id" onchange="showPollDetails(this.value)">
                        <option value="">-- Umfrage auswählen --</option>
                        <?php while ($poll = $all_polls->fetch_assoc()): ?>
                            <option value="<?= $poll['id'] ?>">
                                [ID: <?= $poll['id'] ?>] <?= htmlspecialchars($poll['question']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="delete_poll">Umfrage löschen</button>
                </form>
                <div id="poll-details">
                    <p>Bitte eine Umfrage auswählen, um Details zu sehen.</p>
                </div>
				<h2>Benutzer hinzufügen</h2>
                <form method="post">
                    <input type="text" name="new_username" placeholder="Benutzer hinzufügen" required>
                    <input type="password" name="new_password" placeholder="Passwort" required>
                    <button type="submit" name="add_user">Benutzer hinzufügen</button>
                </form>
				<h2>Benutzer löschen</h2>
                <form method="post">
                    <select name="delete_username">
                        <option value="">Benutzer auswählen</option>
                        <?php while ($user = $all_users->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($user['username']) ?>"><?= htmlspecialchars($user['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="delete_user">Benutzer löschen</button>
                </form>
				<h2>Passwort ändern</h2>
				<form method="post">
					<input type="password" name="current_password" placeholder="Aktuelles Passwort" required>
					<input type="password" name="new_password" placeholder="Neues Passwort" required>
					<input type="password" name="confirm_password" placeholder="Neues Passwort bestätigen" required>
					<button type="submit" name="change_password">Passwort ändern</button>
				</form>
<!-- Anzeige von Nachrichten -->
<?php if (isset($message)): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>
                <a href="?logout=true">Logout</a>
            <?php else: ?>
                <h1>Admin-Login</h1>
                <form method="post">
                    <input type="text" name="username" placeholder="Benutzername" required>
                    <input type="password" name="password" placeholder="Passwort" required>
                    <button type="submit" name="admin_login">Login</button>
                </form>
                <?php if (isset($message)): ?>
                    <p><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
