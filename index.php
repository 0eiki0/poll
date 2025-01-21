<?php
session_start();

include 'config.php';

date_default_timezone_set('Europe/Berlin'); // Deutsche Zeitzone

// Verbindung zur Datenbank herstellen
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Funktion zum Erzeugen einer zufälligen ID
function generateRandomId($length = 10)
{
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

// Funktion zur Eingabenvalidierung
function validateInput($input, $maxLength = 255)
{
    $input = trim($input);
    if (strlen($input) > $maxLength) {
        return false; // Eingabe ist zu lang
    }
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); // Schützt vor XSS
    if (!preg_match('/^[a-zA-Z0-9äöüÄÖÜß\s.,!?\-]+$/u', $input)) {
        return false; // Ungültige Zeichen
    }
    return $input;
}

function pollExpired($conn, $poll_id)
{
    //Prüfe, ob Umfrage noch aktuell
    $cookie_expired = 'poll_expired_' . $poll_id;
    $stmt = $conn->prepare("SELECT * FROM poll WHERE id = ? AND expires_at < NOW()");
    $stmt->bind_param("s", $poll_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    $numrows = $result->num_rows;
    if (isset($_COOKIE[$cookie_expired]) || $numrows > 0) {
        $message = "Diese Umfrage ist bereits abgelaufen.";
        $cookie_expiration = time() + (10 * 365 * 24 * 60 * 60); // Cookie für 10 Jahre gültig
        setcookie($cookie_expired, 'true', $cookie_expiration, "/", "", isset($_SERVER['HTTPS']), true);
        return true;
    }
    return false;
}

// Umfrage erstellen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_poll'])) {
    $question = validateInput($_POST['question']);
    $options = array_filter($_POST['options'], fn($opt) => validateInput($opt)); // Bereinigt und überprüft alle Optionen
	$expidate = !empty($_POST['expi-date']) ? date('Y-m-d H:i:s', strtotime($_POST['expi-date'])) : null;
    if ($question === false || count($options) < 2) {
        $message = "Bitte füllen Sie die Frage und mindestens 2 Antwortmöglichkeiten korrekt aus.";
    } else {
        $poll_id = generateRandomId(10); // Generiere eine zufällige Umfrage-ID
        $stmt = $conn->prepare("INSERT INTO poll (id, question, created_at, expires_at) VALUES (?, ?, NOW(), ?)");
        $stmt->bind_param("sss", $poll_id, $question, $expidate);
        if ($stmt->execute()) {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
            foreach ($options as $option) {
                $stmt->bind_param("ss", $poll_id, $option);
                $stmt->execute();
            }
            $stmt->close();
            // Weiterleitung zur neu erstellten Umfrage
            header("Location: " . $_SERVER['PHP_SELF'] . "?poll_id=" . $poll_id);
            exit;
        }
    }
}

// Abstimmen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $poll_option_id = intval($_POST['option']);
    $poll_id = validateInput($_POST['poll_id']);
    $cookie_voted = 'poll_voted_' . $poll_id;
    $user_ip = $_SERVER['REMOTE_ADDR'];    
    // Prüfen, ob Benutzer bereits abgestimmt hat
    $stmt = $conn->prepare("SELECT COUNT(*) as vote_count FROM poll_votes WHERE poll_id = ? AND ip_address = ?");
    $stmt->bind_param("ss", $poll_id, $user_ip);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (isset($_COOKIE[$cookie_voted]) || $result['vote_count'] > 0) {
        $message = "Du hast bereits an dieser Umfrage teilgenommen.";
    } else {
        $stmt = $conn->prepare("INSERT INTO poll_votes (poll_option_id, poll_id, ip_address) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $poll_option_id, $poll_id, $user_ip);
        $stmt->execute();
        $stmt->close();
        // Cookie setzen
        $cookie_expiration = time() + (10 * 365 * 24 * 60 * 60); // Cookie für 10 Jahre gültig
        setcookie($cookie_voted, 'true', $cookie_expiration, "/", "", isset($_SERVER['HTTPS']), true);
        header("Location: " . $_SERVER['PHP_SELF'] . "?poll_id=" . $poll_id);
        exit;
    }
}

// Ergebnisse anzeigen (falls Umfrage ausgewählt ist)
$current_poll = null;
$poll_options = [];
$poll_results = [];
if (isset($_GET['poll_id'])) {
    $poll_id = validateInput($_GET['poll_id']);
    $stmt = $conn->prepare("SELECT * FROM poll WHERE id = ?");
    $stmt->bind_param("s", $poll_id);
    $stmt->execute();
    $current_poll = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $stmt = $conn->prepare("SELECT * FROM poll_options WHERE poll_id = ?");
    $stmt->bind_param("s", $poll_id);
    $stmt->execute();
    $poll_options = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $stmt = $conn->prepare("
        SELECT o.option_text, COUNT(v.id) as votes
        FROM poll_options o
        LEFT JOIN poll_votes v ON o.id = v.poll_option_id
        WHERE o.poll_id = ?
        GROUP BY o.id");
    $stmt->bind_param("s", $poll_id);
    $stmt->execute();
    $poll_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Alle Umfragen abrufen
$all_polls = $conn->query("SELECT * FROM poll ORDER BY created_at DESC");

        $remaining_time_seconds = 0;
        $show_timer = false;
        if (!empty($current_poll['expires_at'])) {
            $expires_at = new DateTime($current_poll['expires_at']);
            $now = new DateTime();
            $remaining_time_seconds = max(0, $expires_at->getTimestamp() - $now->getTimestamp());
            $show_timer = true;
        }

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abstimmung</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            gap: 20px;
            padding: 0 20px;
        }
        .main, .sidebar {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .main {
            flex: 2;
        }
        .sidebar {
            flex: 1;
        }
        h1, h2, h3 {
            color: #444;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 10px;
        }
        input[type="text"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .add-option {
            background-color: #28A745;
            color: #fff;
            cursor: pointer;
            padding: 10px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
        }
        .add-option:hover {
            background-color: #218838;
        }
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            color: red;
            font-size: 0.9em;
            margin-top: 10px;
        }
        .dynamic-field {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dynamic-field input {
            flex: 1;
        }
        .dynamic-field .remove-option {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
        }
        .dynamic-field .remove-option:hover {
            background-color: #c82333;
        }
        ul {
            padding-left: 20px;
        }
        ul > li {
            margin: 5px 0;
        }
        .poll-results {
            margin-top: 20px;
        }
        .result-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 15px;
        }
        .option-text {
            flex-basis: 20%;
            font-size: 0.9em;
            font-weight: bold;
        }
        .bar-container {
            flex-grow: 1;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            height: 20px;
            position: relative;
        }
        .bar {
            height: 100%;
            background-color: #007bff;
            transition: width 0.6s ease-in-out;
        }
        .vote-count {
            flex-basis: 20%;
            text-align: right;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Hauptbereich -->
        <div class="main">
			<?php if ($current_poll && !$poll_expired): ?>
				<div id="timer" style="font-weight: bold; margin-top: 10px;"></div>
			<?php endif; ?>
            <?php if ($current_poll): ?>
                <h1><?= htmlspecialchars($current_poll['question']) ?></h1>
                <?php if (isset($_COOKIE['poll_voted_' . $poll_id]) || isset($_COOKIE['poll_expired_' . $poll_id]) || pollExpired($conn, $poll_id)): ?>
                    <h3>Ergebnisse:</h3>
                    <div class="poll-results">
                        <?php 
                        $total_votes = 0;
                        foreach ($poll_results as $result) {
                            $total_votes += $result['votes'];
                        }
                        ?>
                        <?php foreach ($poll_results as $result): 
                            $percentage = $total_votes > 0 ? round(($result['votes'] / $total_votes) * 100, 2) : 0;
                        ?>
                            <div class="result-row">
                                <span class="option-text"><?= htmlspecialchars($result['option_text']) ?></span>
                                <div class="bar-container">
                                    <div class="bar" style="width: <?= $percentage ?>%;"></div>
                                </div>
                                <span class="vote-count"><?= $result['votes'] ?> Stimmen (<?= $percentage ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
					<br>
					<center>
					<p>Gesamtzahl der Stimmen: <?= $total_votes ?></p>
					<form action="index.php" method="GET">
						<button type="submit" name="create_poll">Neue Abstimmung erstellen</button>
					</form>
					</center>
                <?php else: ?>
                    <form method="post">
                        <?php foreach ($poll_options as $option): ?>
                            <label>
                                <input type="radio" class="vote-radio" name="option" value="<?= $option['id'] ?>" required>
                                <?= htmlspecialchars($option['option_text']) ?>
                            </label>
                        <?php endforeach; ?>
                        <input type="hidden" name="poll_id" value="<?= $poll_id ?>">
                        <button type="submit" name="vote">Abstimmen</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <h1>Erstelle eine neue Abstimmung</h1>
                <form method="post" id="poll-form">
                    <input type="text" name="question" placeholder="Gib die Frage ein..." required>
                    <input type="text" name="options[]" placeholder="Antwort 1" required>
                    <input type="text" name="options[]" placeholder="Antwort 2" required>
                    <div id="additional-options"></div>
                    <button type="button" class="add-option" onclick="addOption()">Weitere Antwort hinzufügen</button>
				    <p>Ende der Abstimmung (optional):</p>
                    <input type="datetime-local" name="expi-date" max="2099-12-31" />
                    <button type="submit" name="create_poll">Abstimmung erstellen</button>
                </form>
                <?php if (isset($message)): ?>
                    <p class="message"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            <?php endif; ?>
			<br>
        </div>
        <!-- Seitenleiste -->
        
    </div>
    <script>
	
	const remainingTime = <?= $remaining_time_seconds ?>;
	
    document.addEventListener("DOMContentLoaded", function() {
        const timerElement = document.getElementById("timer");
        if (!timerElement) return; // Kein Timer vorhanden, Funktion beenden

        let timeLeft = remainingTime;

        function updateTimer() {
            if (timeLeft <= 0) {
                timerElement.textContent = "";
                clearInterval(timerInterval);
                return;
            }
			const days = Math.floor(timeLeft / (60 * 60 * 24));
            const hours = Math.floor((timeLeft % (60*60*24)) / (60*60))
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;
            timerElement.textContent = `Verbleibende Zeit: ${days} Tage, ${hours} Stunden, ${minutes} Minuten, ${seconds} Sekunden`;
            timeLeft--;
        }

        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
    });
	
        // JavaScript-Funktion zum Hinzufügen weiterer Antwortfelder
        function addOption() {
            const container = document.getElementById('additional-options');
            const fieldDiv = document.createElement('div');
            fieldDiv.classList.add('dynamic-field');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'options[]';
            input.placeholder = 'Gib eine weitere Antwort ein';
            input.required = false;
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.classList.add('remove-option');
            removeButton.textContent = 'X';
            removeButton.onclick = function() {
                container.removeChild(fieldDiv);
            };
            fieldDiv.appendChild(input);
            fieldDiv.appendChild(removeButton);
            container.appendChild(fieldDiv);
        }
document.addEventListener("DOMContentLoaded", function () {
    const pollId = "<?= isset($poll_id) ? $poll_id : '' ?>";
    const resultsContainer = document.querySelector(".poll-results");
    const totalVotesElement = document.querySelector("p");

    if (pollId && resultsContainer && totalVotesElement) {
        function updateResults() {
            fetch(`poll_results.php?poll_id=${pollId}`)
                .then(response => response.json())
                .then(data => {
                    let totalVotes = data.reduce((sum, option) => sum + parseInt(option.votes), 0);
                    resultsContainer.innerHTML = "";

                    data.forEach(option => {
                        const percentage = totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0;

                        const resultRow = document.createElement("div");
                        resultRow.className = "result-row";

                        const optionText = document.createElement("span");
                        optionText.className = "option-text";
                        optionText.textContent = option.option_text;

                        const barContainer = document.createElement("div");
                        barContainer.className = "bar-container";

                        const bar = document.createElement("div");
                        bar.className = "bar";
                        bar.style.width = `${percentage}%`;
                        barContainer.appendChild(bar);

                        const voteCount = document.createElement("span");
                        voteCount.className = "vote-count";
                        voteCount.textContent = `${option.votes} Stimmen (${percentage}%)`;

                        resultRow.appendChild(optionText);
                        resultRow.appendChild(barContainer);
                        resultRow.appendChild(voteCount);

                        resultsContainer.appendChild(resultRow);
                    });

                    // Gesamtzahl der Stimmen aktualisieren
                    totalVotesElement.textContent = `Gesamtzahl der Stimmen: ${totalVotes}`;
                })
                .catch(error => console.error("Fehler beim Abrufen der Ergebnisse:", error));
        }

        // Alle 2 Sekunden Ergebnisse aktualisieren
        setInterval(updateResults, 2000);

        // Initiales Laden der Ergebnisse
        updateResults();
    }
});
    </script>
</body>
</html>
