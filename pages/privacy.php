<?php
require_once __DIR__ . '/../backend/bootstrap.php';
// Public page -- no login required. Reachable from the consent gate
// (pages/consent.php) and should be linked from anywhere a tester/user
// might want to look it up again.
$lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkSync — <?= $lang === 'de' ? 'Datenschutzerklärung' : 'Privacy Policy' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/styles.css">
    <style>
        .privacy-doc { max-width: 760px; margin: 2rem auto; padding: 2rem; line-height: 1.55; }
        .privacy-doc h2 { margin-top: 2rem; }
        .privacy-doc .fill { background: #fff3cd; padding: 0 4px; border-radius: 3px; }
        .privacy-doc ul { padding-left: 1.4rem; }
    </style>
</head>
<body>
<section class="main-section">
<div class="card privacy-doc">

<p><a href="<?= BASE_URL ?>/">&larr; <?= $lang === 'de' ? 'Zurück' : 'Back' ?></a></p>

<?php if ($lang === 'de') : ?>
<h1>Datenschutzerklärung — InkSync</h1>
<p><em>Stand: 23. Juni 2026 (Version 2026-06-23)</em></p>

<p>InkSync ist ein nicht-kommerzielles Studierendenprojekt der FH Salzburg (KI-gestützter Storyboard-Generator), das sich derzeit in einer Testphase mit ausgewählten Testpersonen befindet. Diese Erklärung informiert dich gemäß Art. 13 DSGVO darüber, welche personenbezogenen Daten verarbeitet werden, wozu, und welche Rechte du hast.</p>

<h2>1. Verantwortlicher</h2>
<p>Verantwortlich für die Datenverarbeitung im Sinne der DSGVO ist:</p>
<p>
<span>Georgy Snegur</span><br>
<span>Eduard-Baumgartner-Str. 16 Top 2</span><br>
<span>5020 Salzburg</span><br>
<span>Österreich</span><br>
E-Mail: <span>georgysnegur@gmail.com</span><br>
<em>im Rahmen eines Studienprojekts an der FH Salzburg, Urstein Süd 1, 5412 Puch/Salzburg</em>
</p>

<h2>2. Welche Daten verarbeitet werden</h2>
<ul>
    <li><strong>Konto&shy;daten:</strong> Benutzername, Passwort (als Hash gespeichert, nie im Klartext), Rolle (Nutzer/Admin)</li>
    <li><strong>Inhaltsdaten:</strong> hochgeladene Referenzbilder, Szenenbeschreibungen (Prompts), generierte Storyboard-Panels, gespeicherte Projekte/Storyboards</li>
    <li><strong>Nutzungsdaten:</strong> Zeitpunkt und Anzahl der Generierungen pro Tag (zur Durchsetzung des täglichen Limits), Login-Versuche (zur Account-Sperre nach Fehlversuchen)</li>
    <li><strong>Technische Daten:</strong> IP-Adresse und Standard-Server-Logs des Hosting-Servers (Apache-Standardprotokollierung)</li>
</ul>

<h2>3. Zweck und Rechtsgrundlage der Verarbeitung</h2>
<p>Die Verarbeitung erfolgt zum Zweck des Betriebs und Testens der InkSync-Anwendung im Rahmen dieses Studienprojekts. Rechtsgrundlage ist deine Einwilligung (Art. 6 Abs. 1 lit. a DSGVO), die du vor der ersten Nutzung ausdrücklich erteilst. Für Sicherheitsmaßnahmen (z. B. Account-Sperre nach mehreren Fehlversuchen) ist die Rechtsgrundlage das berechtigte Interesse am Schutz der Anwendung vor Missbrauch (Art. 6 Abs. 1 lit. f DSGVO).</p>

<h2>4. Empfänger und Auftragsverarbeiter</h2>
<p>Folgende Stellen erhalten im Rahmen der Verarbeitung Zugriff auf Daten:</p>
<ul>
    <li><strong>FH Salzburg</strong> — stellt während der Testphase ggf. die Server-Infrastruktur (Hosting, Datenbank) bereit.</li>
    <li><strong>Replicate, Inc.</strong> (San Francisco, USA) — verarbeitet hochgeladene Referenzbilder und Szenenbeschreibungen, um daraus per KI-Modell (Stable Diffusion XL / ControlNet) ein Storyboard-Panel zu erzeugen. Dies stellt eine Datenübermittlung in ein Drittland (USA) ohne allgemeinen EU-Angemessenheitsbeschluss dar. Die Übermittlung erfolgt auf Basis von <span>Standardvertragsklauseln der EU-Kommission </span>.</li>
</ul>
<p>Eine Weitergabe an sonstige Dritte oder eine Nutzung zu Werbezwecken findet nicht statt.</p>

<h2>5. Speicherdauer</h2>
<p>Konto-, Inhalts- und Nutzungsdaten werden für die Dauer der Testphase gespeichert und nach deren Abschluss bzw. auf deine Anfrage gelöscht. Eine konkrete Löschfrist: <span>2 Monate nach Testphase</span>.</p>

<h2>6. Cookies</h2>
<p>InkSync verwendet ausschließlich ein technisch notwendiges Session-Cookie zur Anmeldung (hält dich eingeloggt). Es werden keine Tracking-, Analyse- oder Werbe-Cookies eingesetzt. Für rein technisch notwendige Cookies ist nach § 165 Abs. 3 TKG 2021 keine gesonderte Einwilligung erforderlich.</p>

<h2>7. Deine Rechte</h2>
<p>Du hast nach der DSGVO das Recht auf:</p>
<ul>
    <li>Auskunft über die zu dir gespeicherten Daten (Art. 15 DSGVO)</li>
    <li>Berichtigung unrichtiger Daten (Art. 16 DSGVO)</li>
    <li>Löschung deiner Daten (Art. 17 DSGVO)</li>
    <li>Einschränkung der Verarbeitung (Art. 18 DSGVO)</li>
    <li>Datenübertragbarkeit (Art. 20 DSGVO)</li>
    <li>Widerspruch gegen die Verarbeitung (Art. 21 DSGVO)</li>
    <li>Widerruf deiner Einwilligung mit Wirkung für die Zukunft (Art. 7 Abs. 3 DSGVO)</li>
</ul>
<p>Zur Ausübung dieser Rechte genügt eine E-Mail an <span>georgysnegur@gmail.com</span>.</p>
<p>Außerdem hast du das Recht, dich bei der Aufsichtsbehörde zu beschweren:<br>
Österreichische Datenschutzbehörde, Barichgasse 40–42, 1030 Wien, <a href="mailto:dsb@dsb.gv.at">dsb@dsb.gv.at</a>, <a href="https://www.dsb.gv.at" target="_blank">www.dsb.gv.at</a></p>

<h2>8. Rechte an hochgeladenen Bildern</h2>
<p>Lade nur Referenzbilder hoch, an denen du selbst die nötigen Rechte besitzt — also eigene Fotos/Skizzen, oder Bilder, bei denen die abgebildete Person dem zugestimmt hat. Bilder erkennbarer Dritter ohne deren Einverständnis dürfen nicht hochgeladen werden (Recht am eigenen Bild, § 78 UrhG).</p>

<h2>9. Kommerzielle Nutzung</h2>
<p>Im Rahmen dieser Testphase generierte Panels dürfen <strong>nicht für kommerzielle Zwecke</strong> verwendet werden, sofern dies nicht ausdrücklich schriftlich anders vereinbart wurde.</p>

<h2>10. Datensicherheit</h2>
<p>Passwörter werden ausschließlich als Hash gespeichert (nie im Klartext), Logins werden nach mehreren Fehlversuchen vorübergehend gesperrt, und der Backend-Ordner ist gegen direkten Browserzugriff geschützt.</p>

<h2>11. Minderjährige</h2>
<p>InkSync richtet sich nicht an Personen unter 18 Jahren.</p>

<h2>12. Änderungen dieser Erklärung</h2>
<p>Wird diese Erklärung inhaltlich wesentlich geändert, wirst du beim nächsten Login erneut um Zustimmung gebeten.</p>

<?php else : ?>
<h1>Privacy Policy — InkSync</h1>
<p><em>Last updated: June 23, 2026 (version 2026-06-23)</em></p>

<p>InkSync is a non-commercial student project at FH Salzburg (an AI-assisted storyboard generator), currently in a testing phase with selected test users. This notice informs you, per Art. 13 GDPR, what personal data is processed, why, and what rights you have.</p>

<h2>1. Data Controller</h2>
<p>The controller responsible for data processing under the GDPR is:</p>
<p>
<span>Georgy Snegur</span><br>
<span>Eduard-Baumgartner-Str. 16 Top 2</span><br>
<span>5020 Salzburg</span><br>
<span>Austria</span><br>
Email: <span>georgysnegur@gmail.com</span><br>
<em>as part of a study project at FH Salzburg, Urstein Süd 1, 5412 Puch/Salzburg, Austria</em>
</p>

<h2>2. What data is processed</h2>
<ul>
    <li><strong>Account data:</strong> username, password (stored as a hash, never in plain text), role (user/admin)</li>
    <li><strong>Content data:</strong> uploaded reference images, scene descriptions (prompts), generated storyboard panels, saved projects/storyboards</li>
    <li><strong>Usage data:</strong> timestamp and count of generations per day (to enforce the daily limit), login attempts (for the failed-attempt lockout)</li>
    <li><strong>Technical data:</strong> IP address and standard server logs of the hosting server (default Apache logging)</li>
</ul>

<h2>3. Purpose and legal basis</h2>
<p>Processing serves the purpose of operating and testing the InkSync application as part of this student project. The legal basis is your consent (Art. 6(1)(a) GDPR), given explicitly before first use. For security measures (e.g. account lockout after repeated failed logins), the legal basis is legitimate interest in protecting the application from misuse (Art. 6(1)(f) GDPR).</p>

<h2>4. Recipients and processors</h2>
<p>The following parties have access to data as part of this processing:</p>
<ul>
    <li><strong>FH Salzburg</strong> — may provide the server infrastructure (hosting, database) during the testing phase.</li>
    <li><strong>Replicate, Inc.</strong> (San Francisco, USA) — processes uploaded reference images and scene descriptions to generate a storyboard panel via an AI model (Stable Diffusion XL / ControlNet). This is a transfer of data to a third country (USA) without a general EU adequacy decision. The transfer relies on <span>EU Standard Contractual Clauses]</span>.</li>
</ul>
<p>Data is not shared with any other third parties and is not used for advertising.</p>

<h2>5. Retention period</h2>
<p>Account, content, and usage data is kept for the duration of the testing phase and deleted after it concludes or on your request. Specific deletion timeframe: <span>2 months</span>.</p>

<h2>6. Cookies</h2>
<p>InkSync uses only a technically necessary session cookie for login (keeps you signed in). No tracking, analytics, or advertising cookies are used. Purely technically necessary cookies don't require separate consent under § 165(3) TKG 2021.</p>

<h2>7. Your rights</h2>
<p>Under the GDPR you have the right to:</p>
<ul>
    <li>Access the data held about you (Art. 15 GDPR)</li>
    <li>Rectification of inaccurate data (Art. 16 GDPR)</li>
    <li>Erasure of your data (Art. 17 GDPR)</li>
    <li>Restriction of processing (Art. 18 GDPR)</li>
    <li>Data portability (Art. 20 GDPR)</li>
    <li>Object to processing (Art. 21 GDPR)</li>
    <li>Withdraw your consent with future effect (Art. 7(3) GDPR)</li>
</ul>
<p>To exercise these rights, email <span>georgysnegur@gmail.com</span>.</p>
<p>You also have the right to lodge a complaint with the supervisory authority:<br>
Austrian Data Protection Authority (Österreichische Datenschutzbehörde), Barichgasse 40–42, 1030 Vienna, Austria, <a href="mailto:dsb@dsb.gv.at">dsb@dsb.gv.at</a>, <a href="https://www.dsb.gv.at" target="_blank">www.dsb.gv.at</a></p>

<h2>8. Rights to uploaded images</h2>
<p>Only upload reference images you have the rights to use — your own photos/sketches, or images where the depicted person has consented. Do not upload images of identifiable third parties without their consent (personality rights, § 78 Austrian Copyright Act).</p>

<h2>9. Commercial use</h2>
<p>Panels generated during this testing phase may <strong>not be used for commercial purposes</strong> unless explicitly agreed otherwise in writing.</p>

<h2>10. Data security</h2>
<p>Passwords are stored only as hashes (never in plain text), logins are temporarily locked after repeated failed attempts, and the backend folder is protected against direct browser access.</p>

<h2>11. Minors</h2>
<p>InkSync is not intended for use by anyone under 18.</p>

<h2>12. Changes to this notice</h2>
<p>If this notice changes in a material way, you'll be asked to consent again the next time you log in.</p>

<?php endif; ?>

<p><a href="<?= BASE_URL ?>/">&larr; <?= $lang === 'de' ? 'Zurück' : 'Back' ?></a></p>

</div>
</section>
</body>
</html>
