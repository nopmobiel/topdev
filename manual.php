<?php
// Public manual page - no authentication required
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOP Handleiding</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/site.css">
    <style>
        .manual-content {
            padding: 20px;
            background: #ffffff;
            border-radius: 4px;
            color: #333333;
            border: 1px solid #ddd;
            line-height: 1.7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .manual-content h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 12px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .manual-content h2 {
            color: #2c3e50;
            margin-top: 35px;
            margin-bottom: 18px;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 8px;
            font-weight: 600;
        }
        .manual-content h3 {
            color: #34495e;
            margin-top: 28px;
            margin-bottom: 12px;
            font-weight: 500;
            background: none;
            padding: 0;
        }
        .manual-content h4 {
            color: #5d6d7e;
            margin-top: 22px;
            margin-bottom: 10px;
            font-weight: 500;
            background: none;
            padding: 0;
        }
        .manual-content code {
            background-color: #f8f9fa;
            color: #495057;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.9em;
            border: 1px solid #e9ecef;
        }
        .manual-content ul, .manual-content ol {
            margin-bottom: 18px;
            padding-left: 25px;
        }
        .manual-content li {
            margin-bottom: 6px;
            line-height: 1.6;
        }
        .manual-content strong {
            color: #2c3e50;
            font-weight: 600;
            background: none;
        }
        .manual-content p {
            margin-bottom: 15px;
            text-align: justify;
            background: none;
        }
        .manual-container {
            background: #ffffff;
            color: #333333;
            padding: 0;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 1200px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .manual-header {
            background-color: #2c3e50;
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        .manual-header h1 {
            margin: 0;
            color: white;
            font-size: 2.2rem;
            font-weight: 400;
            border: none;
            padding: 0;
        }
        .manual-header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .toc {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin: 25px 0 35px 0;
        }
        .toc h2 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 15px;
            border: none;
            font-size: 1.4rem;
            font-weight: 600;
        }
        .toc ol {
            margin-bottom: 0;
            columns: 2;
            column-gap: 30px;
        }
        .toc li {
            break-inside: avoid;
            margin-bottom: 8px;
        }
        .toc a {
            text-decoration: none;
            color: #495057;
            transition: color 0.2s ease;
        }
        .toc a:hover {
            color: #007bff;
            text-decoration: underline;
        }
        .manual-content {
            padding: 30px;
        }
        
        /* Better spacing for lists */
        .manual-content ul ul, .manual-content ol ol {
            margin-top: 8px;
            margin-bottom: 8px;
        }
        
        /* Override any external red styling */
        .manual-content * {
            background-color: transparent !important;
        }
        
        .manual-content h1, .manual-content h2, .manual-content h3, .manual-content h4, .manual-content h5, .manual-content h6 {
            background: none !important;
            background-color: transparent !important;
        }
        
        /* Footer styling */
        .manual-content hr {
            margin: 40px 0 20px 0;
            border: none;
            height: 1px;
            background-color: #dee2e6;
        }
        
        .manual-content hr + p {
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="manual-container">
            <div class="manual-header">
                <h1>TOP Handleiding</h1>
                <p>Trombosedienst Outsource Printing</p>
            </div>
            <div class="manual-content">
                <div class="toc">
                    <h2>Inhoudsopgave</h2>
                    <ol>
                        <li><a href="#inleiding">Inleiding</a></li>
                        <li><a href="#inloggen">Inloggen</a></li>
                        <li><a href="#bestandsupload">Bestandsupload</a></li>
                        <li><a href="#uitzonderingenbestand-bewerken">Uitzonderingenbestand bewerken</a></li>
                        <li><a href="#downloads">Downloads</a></li>
                        <li><a href="#wachtwoord-wijzigen">Wachtwoord wijzigen</a></li>
                        <li><a href="#tweestapsverificatie">Tweestapsverificatie</a></li>
                        <li><a href="#probleemoplossing">Probleemoplossing</a></li>
                        <li><a href="#technische-specificaties">Technische specificaties</a></li>
                    </ol>
                </div>

                <h2 id="inleiding">1. Inleiding</h2>
                <p>TOP (Trombosedienst Outsource Printing) is een webgebaseerd systeem voor trombosediensten om dagelijks doseerkalenders te uploaden en zo nodig te verwerken.</p>
                
                <h3>Belangrijke kenmerken:</h3>
                <ul>
                    <li><strong>Veilige bestandsupload</strong> voor Trodis en Portavita systemen</li>
                    <li><strong>Automatische kalendergeneratie</strong> voor patiënten</li>
                    <li><strong>Uitzonderingenbeheer</strong> voor speciale gevallen</li>
                    <li><strong>Tweestapsverificatie</strong> voor extra beveiliging</li>
                    <li><strong>Outsource printing</strong> via externe printpartners</li>
                </ul>

                <h2 id="inloggen">2. Inloggen</h2>
                
                <h3>Stap 1: Toegang tot het systeem</h3>
                <ol>
                    <li>Ga naar de TOP website</li>
                    <li>Voer uw <strong>gebruikersnaam</strong> in (meestal uw dienst-afkorting)</li>
                    <li>Voer uw <strong>wachtwoord</strong> in</li>
                    <li>Klik op <strong>"Inloggen"</strong></li>
                </ol>

                <h3>Stap 2: Verificatie (indien ingeschakeld)</h3>
                
                <h4>Google Authenticator of Microsoft Authenticator (verplicht)</h4>
                <p>Download de app van uw keuze via:</p>
                <ul>
                    <li>Voor Android telefoons:
                        <ul>
                            <li><a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Google Authenticator in Play Store</a></li>
                            <li><a href="https://play.google.com/store/apps/details?id=com.azure.authenticator">Microsoft Authenticator in Play Store</a></li>
                        </ul>
                    </li>
                    <li>Voor iPhones:
                        <ul>
                            <li><a href="https://apps.apple.com/app/google-authenticator/id388497605">Google Authenticator in App Store</a></li>
                            <li><a href="https://apps.apple.com/app/microsoft-authenticator/id983156458">Microsoft Authenticator in App Store</a></li>
                        </ul>
                    </li>
                </ul>
                
                <p>Als u de Authenticator heeft ingeschakeld:</p>
                <ol>
                    <li>Open de Authenticator app op uw telefoon</li>
                    <li>Zoek de TOP-code voor uw dienst</li>
                    <li>Voer de 6-cijferige code in</li>
                    <li>Klik op <strong>"Verifiëren"</strong></li>
                </ol>

                <h4>E-mail verificatie</h4>
                <p>Als u e-mailverificatie gebruikt:</p>
                <ol>
                    <li>Controleer uw e-mail voor een verificatiecode</li>
                    <li>Voer de ontvangen code in binnen 10 minuten</li>
                    <li>Klik op <strong>"Verifiëren"</strong></li>
                </ol>

                <h3>Eerste inlog</h3>
                <p>Bij uw eerste inlog wordt u gevraagd om:</p>
                <ul>
                    <li>De Authenticator in te stellen (verplicht)</li>
                    <li>Contactgegevens te bevestigen</li>
                </ul>

                <h2 id="bestandsupload">3. Bestandsupload</h2>
                
                <h3>Ondersteunde bestandsformaten</h3>
                <ul>
                    <li><strong>Trodis</strong>: Tab-gescheiden bestanden (TSV)</li>
                    <li><strong>Portavita</strong>: CSV-bestanden met puntkomma als scheidingsteken</li>
                </ul>

                <h3>Bestandsnaam vereisten</h3>
                <p>Bestanden moeten de volgende naamconventie volgen:</p>
                <ul>
                    <li><strong>Formaat</strong>: <code>EDjjmmdd.csv</code> of <code>edjjmmdd.csv</code></li>
                    <li><strong>Voorbeeld</strong>: <code>ED250615.csv</code> voor 15 juni 2025</li>
                    <li><strong>Datum</strong>: Moet overeenkomen met de huidige datum</li>
                </ul>

                <h3>Upload proces</h3>
                
                <h4>Stap 1: Bestand selecteren</h4>
                <ol>
                    <li>Klik op <strong>"Bestand kiezen om te uploaden"</strong></li>
                    <li>Selecteer uw CSV-bestand</li>
                    <li>Controleer dat de bestandsnaam correct is</li>
                </ol>

                <h4>Stap 2: Upload uitvoeren</h4>
                <ol>
                    <li>Klik op <strong>"Upload Bestand"</strong></li>
                    <li>Wacht tot de verwerking voltooid is</li>
                    <li>Controleer de statusmeldingen</li>
                </ol>

                <h4>Stap 3: Verificatie</h4>
                <p>Na succesvolle upload worden automatisch gegenereerd:</p>
                <ul>
                    <li><strong>Noodbestand</strong> (<code>nood.csv</code>) - Alle patiënten behalve uitzonderingen</li>
                    <li><strong>Uitzonderingenbestand</strong> (<code>uitzonderingen.csv</code>) - Alleen uitzonderingen</li>
                    <li><strong>Printbestand</strong> (<code>[dienst].dat</code>) - Voor externe printservice</li>
                </ul>

                <h3>Foutmeldingen</h3>
                <ul>
                    <li><strong>"Ongeldige bestandsnaamindeling"</strong>: Controleer de naamconventie</li>
                    <li><strong>"Bestandsdatum komt niet overeen"</strong>: Upload alleen bestanden van vandaag</li>
                    <li><strong>"Alleen csv-bestanden toegestaan"</strong>: Gebruik alleen CSV-formaat</li>
                    <li><strong>"Bestand te groot"</strong>: Maximum bestandsgrootte is 10MB</li>
                </ul>

                <h2 id="uitzonderingenbestand-bewerken">4. Uitzonderingenbestand bewerken</h2>
                <p>Het uitzonderingenbestand bevat patiënten die speciale behandeling nodig hebben (bijvoorbeeld andere postcode of externe printservice).</p>

                <h3>Nieuwe patiënt toevoegen</h3>
                
                <h4>Stap 1: Formulier openen</h4>
                <ol>
                    <li>Ga naar <strong>"Uitzonderingenbestand bewerken"</strong></li>
                    <li>Klik op <strong>"Nieuwe patiënt toevoegen"</strong></li>
                </ol>

                <h4>Stap 2: Gegevens invoeren</h4>
                <ol>
                    <li><strong>Patientnummer</strong>: Voer het unieke patientnummer in (verplicht)</li>
                    <li><strong>Postcode</strong>: Voer de postcode in formaat 1234AB in</li>
                    <li><strong>Extra informatie</strong>: Optionele aanvullende informatie</li>
                    <li><strong>PGN Checkbox</strong>: Vink aan als printen uitbesteed moet worden</li>
                </ol>

                <h4>Stap 3: Opslaan</h4>
                <ol>
                    <li>Controleer alle gegevens</li>
                    <li>Klik op <strong>"Toevoegen"</strong></li>
                    <li>Bevestig de succesmelding</li>
                </ol>

                <h3>Bestaande patiënt wijzigen</h3>
                
                <h4>Stap 1: Patiënt selecteren</h4>
                <ol>
                    <li>Klik op <strong>"Bestaande patiënt wijzigen"</strong></li>
                    <li>Selecteer een patiënt uit de dropdown lijst</li>
                    <li>De gegevens worden automatisch ingevuld</li>
                </ol>

                <h4>Stap 2: Gegevens aanpassen</h4>
                <ol>
                    <li>Wijzig de gewenste velden</li>
                    <li>Klik op <strong>"Bijwerken"</strong> om op te slaan</li>
                    <li>Of klik op <strong>"Verwijderen"</strong> om de patiënt te verwijderen</li>
                </ol>

                <h3>Validatieregels</h3>
                <ul>
                    <li><strong>Patientnummer</strong>: Alleen letters, cijfers, streepjes en underscores</li>
                    <li><strong>Postcode</strong>: Nederlandse postcode formaat (1234AB)</li>
                    <li><strong>Extra informatie</strong>: Maximum 30 karakters</li>
                </ul>

                <h2 id="downloads">5. Downloads</h2>
                
                <h3>Beschikbare bestanden</h3>
                <p>Na elke upload zijn de volgende bestanden beschikbaar:</p>

                <h4>1. Noodbestand (<code>nood.csv</code>)</h4>
                <ul>
                    <li><strong>Inhoud</strong>: Alle patiënten behalve uitzonderingen</li>
                    <li><strong>Gebruik</strong>: Backup of alternatieve verwerking</li>
                    <li><strong>Formaat</strong>: CSV met headers</li>
                </ul>

                <h4>2. Uitzonderingenbestand (<code>uitzonderingen.csv</code>)</h4>
                <ul>
                    <li><strong>Inhoud</strong>: Alleen patiënten met uitzonderingen</li>
                    <li><strong>Gebruik</strong>: Speciale behandeling</li>
                    <li><strong>Formaat</strong>: CSV met headers</li>
                </ul>

                <h4>3. Printbestand (<code>[dienst].dat</code>)</h4>
                <ul>
                    <li><strong>Inhoud</strong>: Geformatteerde data voor printservice</li>
                    <li><strong>Gebruik</strong>: Automatische overdracht naar printpartner</li>
                    <li><strong>Formaat</strong>: Specifiek printformaat</li>
                </ul>

                <h3>Download proces</h3>
                <ol>
                    <li>Ga naar <strong>"Download uitzonderingen"</strong> of <strong>"Download noodbestand"</strong></li>
                    <li>Klik op de gewenste download link</li>
                    <li>Het bestand wordt automatisch gedownload</li>
                </ol>

                <h3>Bestandsbeveiliging</h3>
                <ul>
                    <li>Bestanden zijn alleen toegankelijk voor uw dienst</li>
                    <li>Automatische verwijdering na 4 uur</li>
                    <li>Beveiligde overdracht via HTTPS</li>
                </ul>

                <h2 id="wachtwoord-wijzigen">6. Wachtwoord wijzigen</h2>
                
                <h3>Vereisten voor nieuwe wachtwoorden</h3>
                <ul>
                    <li>Minimaal 8 karakters</li>
                    <li>Minimaal 1 hoofdletter</li>
                    <li>Minimaal 1 kleine letter</li>
                    <li>Minimaal 1 cijfer</li>
                    <li>Minimaal 1 speciaal teken</li>
                </ul>

                <h3>Wijzigingsproces</h3>
                <ol>
                    <li>Ga naar <strong>"Wachtwoord wijzigen"</strong></li>
                    <li>Voer uw <strong>huidige wachtwoord</strong> in</li>
                    <li>Voer het <strong>nieuwe wachtwoord</strong> in</li>
                    <li><strong>Bevestig</strong> het nieuwe wachtwoord</li>
                    <li>Klik op <strong>"Wachtwoord wijzigen"</strong></li>
                </ol>

                <h3>Na wijziging</h3>
                <ul>
                    <li>U ontvangt een bevestigingsmail (indien geconfigureerd)</li>
                    <li>U wordt automatisch uitgelogd</li>
                    <li>Log opnieuw in met het nieuwe wachtwoord</li>
                </ul>

                <h2 id="tweestapsverificatie">7. Tweestapsverificatie</h2>
                
                <h3>Authenticator App (verplicht)</h3>
                
                <h4>Instellen</h4>
                <ol>
                    <li>Ga naar <strong>"Authenticator instellen"</strong></li>
                    <li>Download de Google Authenticator of Microsoft Authenticator app op uw telefoon</li>
                    <li>Scan de QR-code met de app</li>
                    <li>Voer de 6-cijferige code in ter verificatie</li>
                    <li>Klik op <strong>"Activeren"</strong></li>
                </ol>

                <h4>Gebruik</h4>
                <ol>
                    <li>Bij elke inlog wordt een code gevraagd</li>
                    <li>Open de Authenticator app op uw telefoon</li>
                    <li>Voer de actuele 6-cijferige code in</li>
                    <li>Codes zijn 30 seconden geldig</li>
                </ol>

                <h4>Extra apparaten toevoegen</h4>
                <ol>
                    <li>Ga naar <strong>"Extra 2FA apparaat toevoegen"</strong></li>
                    <li>Scan dezelfde QR-code op een tweede apparaat</li>
                    <li>Beide apparaten kunnen nu codes genereren</li>
                </ol>

                <h3>Voordelen</h3>
                <ul>
                    <li><strong>Extra beveiliging</strong> voor uw account</li>
                    <li><strong>Meerdere apparaten</strong> mogelijk</li>
                    <li><strong>Industriestandaard</strong> beveiliging</li>
                </ul>

                <h2 id="probleemoplossing">8. Probleemoplossing</h2>
                
                <h3>Veelvoorkomende problemen</h3>
                
                <h4>Inlogproblemen</h4>
                <p><strong>Probleem</strong>: "Ongeldige gebruikersnaam of wachtwoord"</p>
                <ul>
                    <li><strong>Oplossing</strong>: Controleer uw inloggegevens</li>
                    <li><strong>Tip</strong>: Wachtwoorden zijn hoofdlettergevoelig</li>
                </ul>

                <p><strong>Probleem</strong>: Google Authenticator code wordt niet geaccepteerd</p>
                <ul>
                    <li><strong>Oplossing</strong>: Controleer de tijd op uw telefoon</li>
                    <li><strong>Tip</strong>: Codes zijn slechts 30 seconden geldig</li>
                </ul>

                <h4>Uploadproblemen</h4>
                <p><strong>Probleem</strong>: "Bestand te groot"</p>
                <ul>
                    <li><strong>Oplossing</strong>: Controleer bestandsgrootte (max 10MB)</li>
                    <li><strong>Tip</strong>: Neem contact op met PGN als het bestand te groot is</li>
                </ul>

                <p><strong>Probleem</strong>: "Ongeldige bestandsindeling"</p>
                <ul>
                    <li><strong>Oplossing</strong>: Controleer of het bestand echt CSV is</li>
                    <li><strong>Tip</strong>: Open het bestand in een teksteditor om de inhoud te controleren</li>
                </ul>

                <h4>Algemene problemen</h4>
                <p><strong>Probleem</strong>: Pagina laadt niet</p>
                <ul>
                    <li><strong>Oplossing</strong>: Ververs de pagina (F5)</li>
                    <li><strong>Tip</strong>: Controleer uw internetverbinding</li>
                </ul>

                <p><strong>Probleem</strong>: Sessie verlopen</p>
                <ul>
                    <li><strong>Oplossing</strong>: Log opnieuw in</li>
                    <li><strong>Tip</strong>: Sessies verlopen na 1 uur inactiviteit</li>
                </ul>

                <h2 id="technische-specificaties">9. Technische specificaties</h2>
                
                <h3>Systeemvereisten</h3>
                <ul>
                    <li><strong>Browser</strong>: Chrome, Firefox, Safari, Edge (laatste 2 versies)</li>
                    <li><strong>JavaScript</strong>: Moet ingeschakeld zijn</li>
                    <li><strong>Cookies</strong>: Moet ingeschakeld zijn</li>
                    <li><strong>Internetverbinding</strong>: Stabiele verbinding vereist</li>
                </ul>

                <h3>Bestandsspecificaties</h3>
                
                <h4>CSV formaat</h4>
                <ul>
                    <li><strong>Encoding</strong>: UTF-8 of Latin-1</li>
                    <li><strong>Scheidingsteken</strong>: Tab voor Trodis, puntkomma voor Portavita</li>
                    <li><strong>Tekstkwalificatie</strong>: Dubbele aanhalingstekens (")</li>
                    <li><strong>Regeleindes</strong>: Windows (CRLF) of Unix (LF)</li>
                </ul>

                <h3>Beveiliging</h3>
                <ul>
                    <li><strong>HTTPS</strong>: Alle communicatie is versleuteld</li>
                    <li><strong>Sessiebeveiliging</strong>: Automatische uitlog na inactiviteit</li>
                    <li><strong>Bestandsbeveiliging</strong>: Bestanden alleen toegankelijk voor eigen dienst</li>
                    <li><strong>Audit Trail</strong>: Alle acties worden gelogd</li>
                    <li><strong>GDPR Compliant</strong>: Voldoet aan privacywetgeving</li>
                </ul>

                <h3>Prestaties</h3>
                <ul>
                    <li><strong>Upload snelheid</strong>: Afhankelijk van bestandsgrootte en verbinding</li>
                    <li><strong>Verwerkingstijd</strong>: Gemiddeld 30-60 seconden per 1000 patiënten</li>
                    <li><strong>Beschikbaarheid</strong>: 99.5% uptime garantie</li>
                    <li><strong>Backup</strong>: Dagelijkse backups van alle data</li>
                </ul>

                <h3>A. Foutcodes</h3>
                <ul>
                    <li><strong>ERR001</strong>: Ongeldige bestandsnaam</li>
                    <li><strong>ERR002</strong>: Bestand te groot</li>
                    <li><strong>ERR003</strong>: Ongeldige bestandsinhoud</li>
                    <li><strong>ERR004</strong>: Database verbindingsfout</li>
                    <li><strong>ERR005</strong>: Onvoldoende rechten</li>
                </ul>

                <h3>B. Versiegeschiedenis</h3>
                <ul>
                    <li><strong>v2025.1</strong>: Huidige versie, verbeterde beveiliging en UI</li>
                    <li><strong>v2024.1</strong>: Verbeterde beveiliging, nieuwe UI</li>
                </ul>

                <hr>
                <p><em>Deze handleiding is geldig voor TOP versie 2025.1<br>
                Laatste update: Juni 2025</em></p>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 