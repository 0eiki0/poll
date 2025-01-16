# Poll - Ein Einfaches PHP-basiertes Abstimmungssystem

**Projektname**: StrawPoll  
**Beschreibung**:  
Dieses Repository enthält ein einfaches Abstimmungssystem, mit dem Benutzer Umfragen erstellen und an Abstimmungen teilnehmen können. Die Anwendung ist mit PHP und MySQL entwickelt und ermöglicht eine intuitive und übersichtliche Oberfläche für Umfragen und deren Ergebnisse.

---

## 🚀 **Features**

1. **Erstellen von Umfragen**:
   - Benutzer können eine beliebige Frage eingeben und Antwortmöglichkeiten hinzufügen.
   - Es müssen mindestens zwei Antwortmöglichkeiten definiert sein.

2. **Abstimmungen**:
   - Besucher können über bereitgestellte Antworten abstimmen.
   - Sicherheitsvorkehrungen wie IP-Adresse-Tracking und Cookies verhindern Mehrfachabstimmungen.

3. **Ergebnisanzeige**:
   - Stimmen werden in Echtzeit aktualisiert und als Balkendiagramm angezeigt.
   - Gesamtstimmen und Verhältnisse (prozentual) werden für jede Antwortoption angezeigt.

4. **Umfragenübersicht**:
   - Alle erstellten Umfragen werden in der Datenbank gespeichert und können nach Bedarf aufgerufen werden.

5. **Intuitive Benutzeroberfläche**:
   - Einfaches und klares Design für beste Nutzererfahrung.
   - Unterstützt das Hinzufügen von Optionen durch dynamische Eingabefelder.

6. **Live-Updates**:
   - Ergebnisse der Abstimmung werden per AJAX/Polling alle 2 Sekunden automatisch aktualisiert.

---

## ⚙️ **Installation**

1. **Klonen des Projekts**:
   ```bash
   git clone https://github.com/username/strawpoll.git
   ```
2. **Datenbankeinrichtung**:
   - Importiere die bereitgestellte SQL-Datei (`strawpoll.sql`), die alle notwendigen Tabellen (`poll`, `poll_options`, `poll_votes`) erstellt.
   - Gehe dazu ins MySQL-Terminal oder in ein Tool wie PHPMyAdmin:
     ```sql
     CREATE DATABASE strawpoll;
     USE strawpoll;
     SOURCE strawpoll.sql;
     ```
3. **Konfiguration anpassen**:
   - In der `config`-Datei oder im PHP-Skript (siehe `index.php`) die MySQL-Zugangsdaten anpassen:
     ```php
     $servername = "localhost";
     $username = "your_username";
     $password = "your_password";
     $dbname = "strawpoll";
     ```
4. **Projekt auf einem lokalen oder Server-Host bereitstellen**:
   - Kopiere die Projektdateien in das Root-Verzeichnis des Webservers.  
     Für z.B. XAMPP: `htdocs/strawpoll`.
   - Besuche `http://localhost/strawpoll/`.

---

## 📋 **Nutzung**

### 1. **Umfrage erstellen**
   - Auf der Hauptseite erscheint ein Formular, mit dem der Benutzer eine Frage und Antwortmöglichkeiten eingeben kann.
   - Es können zusätzliche Optionen hinzugefügt oder bestehende entfernt werden.
   - Nach dem Erstellen wird der Benutzer direkt zur neu erstellten Umfrage weitergeleitet.

### 2. **An einer Umfrage teilnehmen**
   - Teilnehmer wählen eine der verfügbaren Optionen aus und klicken auf "Abstimmen".
   - Nach der Abstimmung werden die Ergebnisse der Umfrage in Echtzeit angezeigt.

### 3. **Ergebnisse anzeigen**
   - Jeder Benutzer kann das Abstimmungsergebnis sehen, wenn die Abstimmung abgeschlossen oder eine Teilnahme erfolgt ist.
   - Ergebnisse sind als Balkendiagramm visualisiert und zeigen Stimmenanzahl sowie Prozentanteile.

---

## ❤️ **Contributions**

Dieses Projekt ist ein persönliches Experiment und daher offen für Verbesserungsvorschläge und Pull Requests! 🎉  
Bitte fühle dich frei, Bugs zu melden oder neue Features vorzuschlagen.

---

## 📜 **Lizenz**

Dieses Projekt steht unter der [MIT-Lizenz](LICENSE). Feel free to use, modifizieren und verbessern! 😊
