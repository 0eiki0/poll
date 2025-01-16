
-- Tabelle für Umfragen
CREATE TABLE poll (
    id VARCHAR(10) PRIMARY KEY, -- Umfrage-ID (zufällig generiert)
    question TEXT NOT NULL, -- Die Frage der Umfrage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Erstellungszeit der Umfrage
);

-- Tabelle für die Antwortmöglichkeiten (Options) jeder Umfrage
CREATE TABLE poll_options (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Automatischer eindeutiger Schlüssel für Antwortmöglichkeiten
    poll_id VARCHAR(10) NOT NULL, -- Die ID der zugehörigen Umfrage
    option_text TEXT NOT NULL, -- Text der Antwortmöglichkeit
    FOREIGN KEY (poll_id) REFERENCES poll(id) ON DELETE CASCADE -- Fremdschlüssel zu poll (löscht die Optionen, wenn eine Umfrage gelöscht wird)
);

-- Tabelle für die Stimmen (Votes) jeder Antwortmöglichkeit
CREATE TABLE poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Automatischer eindeutiger Schlüssel für Stimmen
    poll_option_id INT NOT NULL, -- Die ID der zugehörigen Antwortmöglichkeit
    poll_id VARCHAR(10) NOT NULL, -- Die ID der zugehörigen Umfrage
    ip_address VARCHAR(45) NOT NULL, -- Die IP-Adresse des Wählers (max. 45 Zeichen für IPv6)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Zeitstempel der Stimmabgabe
    FOREIGN KEY (poll_option_id) REFERENCES poll_options(id) ON DELETE CASCADE, -- Fremdschlüssel zu poll_options
    FOREIGN KEY (poll_id) REFERENCES poll(id) ON DELETE CASCADE -- Fremdschlüssel zu poll (indirekt)
);


CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

-- User: admin Passwort: admin
INSERT INTO admin_users (username, password_hash) 
VALUES ('admin', '$2y$10$NBQafZAOhXwIM21o5VgqWuOg68mlEqgL88I3nkLoeNTWJzaC0Umiu');

-- Optional: Index hinzufügen, um Abfragen zu beschleunigen
CREATE INDEX idx_poll_votes_poll_id ON poll_votes (poll_id);
CREATE INDEX idx_poll_votes_option_id ON poll_votes (poll_option_id);
