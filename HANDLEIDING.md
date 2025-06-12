# TOP Handleiding
## Trombosedienst Outsource Printing

### Versie 2025.1
### Voor Nederlandse Trombosediensten

---

## Inhoudsopgave

1. [Inleiding](#inleiding)
2. [Inloggen](#inloggen)
3. [Bestandsupload](#bestandsupload)
4. [Uitzonderingenbestand Bewerken](#uitzonderingenbestand-bewerken)
5. [Kalenderoverzicht](#kalenderoverzicht)
6. [Downloads](#downloads)
7. [Wachtwoord Wijzigen](#wachtwoord-wijzigen)
8. [Tweestapsverificatie](#tweestapsverificatie)
9. [Probleemoplossing](#probleemoplossing)
10. [Technische Specificaties](#technische-specificaties)

---

## Inleiding

Het TOP (Trombosedienst Outsource Printing) is een webgebaseerd systeem voor Nederlandse trombosediensten om dagelijks patiëntgegevens te uploaden, doseerkalenders te genereren en deze te laten printen en versturen via externe printservices.

### Belangrijke Kenmerken:
- **Veilige bestandsupload** voor Trodis en Portavita systemen
- **Automatische kalendergeneratie** voor patiënten
- **Uitzonderingenbeheer** voor speciale gevallen
- **Tweestapsverificatie** voor extra beveiliging
- **Outsource printing** via externe printpartners

---

## Inloggen

### Stap 1: Toegang tot het Systeem
1. Ga naar de TOP website
2. Voer uw **gebruikersnaam** in (meestal uw dienst-afkorting)
3. Voer uw **wachtwoord** in
4. Klik op **"Inloggen"**

### Stap 2: Verificatie (indien ingeschakeld)

#### Google Authenticator (Aanbevolen)
Als u Google Authenticator heeft ingeschakeld:
1. Open de Google Authenticator app op uw telefoon
2. Zoek de TOP-code voor uw dienst
3. Voer de 6-cijferige code in
4. Klik op **"Verifiëren"**

#### E-mail Verificatie
Als u e-mailverificatie gebruikt:
1. Controleer uw e-mail voor een verificatiecode
2. Voer de ontvangen code in binnen 10 minuten
3. Klik op **"Verifiëren"**

### Eerste Inlog
Bij uw eerste inlog wordt u mogelijk gevraagd om:
- Google Authenticator in te stellen (sterk aanbevolen)
- Uw wachtwoord te wijzigen
- Contactgegevens te bevestigen

---

## Bestandsupload

### Ondersteunde Bestandsformaten
- **Trodis**: CSV-bestanden met tab als scheidingsteken
- **Portavita**: CSV-bestanden met puntkomma als scheidingsteken

### Bestandsnaam Vereisten
Bestanden moeten de volgende naamconventie volgen:
- **Formaat**: `EDjjmmdd.csv` of `edjjmmdd.csv`
- **Voorbeeld**: `ED250615.csv` voor 15 juni 2025
- **Datum**: Moet overeenkomen met de huidige datum

### Upload Proces

#### Stap 1: Bestand Selecteren
1. Klik op **"Bestand kiezen om te uploaden"**
2. Selecteer uw CSV-bestand
3. Controleer dat de bestandsnaam correct is

#### Stap 2: Upload Uitvoeren
1. Klik op **"Upload Bestand"**
2. Wacht tot de verwerking voltooid is
3. Controleer de statusmeldingen

#### Stap 3: Verificatie
Na succesvolle upload worden automatisch gegenereerd:
- **Noodbestand** (`nood.csv`) - Alle patiënten behalve uitzonderingen
- **Uitzonderingenbestand** (`uitzonderingen.csv`) - Alleen uitzonderingen
- **Printbestand** (`[dienst].dat`) - Voor externe printservice

### Foutmeldingen
- **"Ongeldige bestandsnaamindeling"**: Controleer de naamconventie
- **"Bestandsdatum komt niet overeen"**: Upload alleen bestanden van vandaag
- **"Alleen csv-bestanden toegestaan"**: Gebruik alleen CSV-formaat
- **"Bestand te groot"**: Maximum bestandsgrootte is 10MB

---

## Uitzonderingenbestand Bewerken

Het uitzonderingenbestand bevat patiënten die speciale behandeling nodig hebben (bijvoorbeeld andere postcode of externe printservice).

### Nieuwe Patiënt Toevoegen

#### Stap 1: Formulier Openen
1. Ga naar **"Uitzonderingenbestand bewerken"**
2. Klik op **"Nieuwe patiënt toevoegen"**

#### Stap 2: Gegevens Invoeren
1. **Patientnummer**: Voer het unieke patientnummer in (verplicht)
2. **Postcode**: Voer de postcode in formaat 1234AB in
3. **Extra informatie**: Optionele aanvullende informatie
4. **PGN Checkbox**: Vink aan als printen uitbesteed moet worden

#### Stap 3: Opslaan
1. Controleer alle gegevens
2. Klik op **"Toevoegen"**
3. Bevestig de succesmelding

### Bestaande Patiënt Wijzigen

#### Stap 1: Patiënt Selecteren
1. Klik op **"Bestaande patiënt wijzigen"**
2. Selecteer een patiënt uit de dropdown lijst
3. De gegevens worden automatisch ingevuld

#### Stap 2: Gegevens Aanpassen
1. Wijzig de gewenste velden
2. Klik op **"Bijwerken"** om op te slaan
3. Of klik op **"Verwijderen"** om de patiënt te verwijderen

### Validatieregels
- **Patientnummer**: Alleen letters, cijfers, streepjes en underscores
- **Postcode**: Nederlandse postcode formaat (1234AB)
- **Extra informatie**: Maximum 30 karakters

---

## Kalenderoverzicht

Het kalenderoverzicht toont alle gegenereerde doseerkalenders voor uw dienst.

### Toegang
1. Ga naar **"Kalenderoverzicht"** in het menu
2. Het systeem toont alle beschikbare kalenders

### Functionaliteit
- **Paginering**: 20 kalenders per pagina
- **Filtering**: Alleen kalenders met inhoud worden getoond
- **Detailweergave**: Klik op een patientnummer voor details

### Kalender Bekijken
1. Klik op een patientnummer in de lijst
2. De kalender wordt getoond met:
   - Patiëntgegevens
   - Doseeringsschema voor 2 weken
   - Controledata
   - Speciale instructies

---

## Downloads

### Beschikbare Bestanden
Na elke upload zijn de volgende bestanden beschikbaar:

#### 1. Noodbestand (`nood.csv`)
- **Inhoud**: Alle patiënten behalve uitzonderingen
- **Gebruik**: Backup of alternatieve verwerking
- **Formaat**: CSV met headers

#### 2. Uitzonderingenbestand (`uitzonderingen.csv`)
- **Inhoud**: Alleen patiënten met uitzonderingen
- **Gebruik**: Speciale behandeling
- **Formaat**: CSV met headers

#### 3. Printbestand (`[dienst].dat`)
- **Inhoud**: Geformatteerde data voor printservice
- **Gebruik**: Automatische overdracht naar printpartner
- **Formaat**: Specifiek printformaat

### Download Proces
1. Ga naar **"Download uitzonderingen"** of **"Download noodbestand"**
2. Klik op de gewenste download link
3. Het bestand wordt automatisch gedownload

### Bestandsbeveiliging
- Bestanden zijn alleen toegankelijk voor uw dienst
- Automatische verwijdering na 30 dagen
- Beveiligde overdracht via HTTPS

---

## Wachtwoord Wijzigen

### Vereisten voor Nieuwe Wachtwoorden
- Minimaal 8 karakters
- Minimaal 1 hoofdletter
- Minimaal 1 kleine letter  
- Minimaal 1 cijfer
- Minimaal 1 speciaal teken

### Wijzigingsproces
1. Ga naar **"Wachtwoord wijzigen"**
2. Voer uw **huidige wachtwoord** in
3. Voer het **nieuwe wachtwoord** in
4. **Bevestig** het nieuwe wachtwoord
5. Klik op **"Wachtwoord wijzigen"**

### Na Wijziging
- U ontvangt een bevestigingsmail (indien geconfigureerd)
- U wordt automatisch uitgelogd
- Log opnieuw in met het nieuwe wachtwoord

---

## Tweestapsverificatie

### Google Authenticator (Aanbevolen)

#### Instellen
1. Ga naar **"Google Authenticator instellen"**
2. Download de Google Authenticator app op uw telefoon
3. Scan de QR-code met de app
4. Voer de 6-cijferige code in ter verificatie
5. Klik op **"Activeren"**

#### Gebruik
1. Bij elke inlog wordt een code gevraagd
2. Open Google Authenticator op uw telefoon
3. Voer de actuele 6-cijferige code in
4. Codes zijn 30 seconden geldig

#### Extra Apparaten Toevoegen
1. Ga naar **"Extra 2FA apparaat toevoegen"**
2. Scan dezelfde QR-code op een tweede apparaat
3. Beide apparaten kunnen nu codes genereren

### Voordelen
- **Extra beveiliging** voor uw account
- **Offline werking** - geen internetverbinding nodig
- **Meerdere apparaten** mogelijk
- **Industriestandaard** beveiliging

---

## Probleemoplossing

### Veelvoorkomende Problemen

#### Inlogproblemen
**Probleem**: "Ongeldige gebruikersnaam of wachtwoord"
- **Oplossing**: Controleer uw inloggegevens
- **Tip**: Wachtwoorden zijn hoofdlettergevoelig

**Probleem**: Google Authenticator code wordt niet geaccepteerd
- **Oplossing**: Controleer de tijd op uw telefoon
- **Tip**: Codes zijn slechts 30 seconden geldig

#### Uploadproblemen
**Probleem**: "Bestand te groot"
- **Oplossing**: Controleer bestandsgrootte (max 10MB)
- **Tip**: Neem contact op met uw IT-beheerder als het bestand te groot is

**Probleem**: "Ongeldige bestandsindeling"
- **Oplossing**: Controleer of het bestand echt CSV is
- **Tip**: Open het bestand in een teksteditor om de inhoud te controleren

#### Algemene Problemen
**Probleem**: Pagina laadt niet
- **Oplossing**: Ververs de pagina (F5)
- **Tip**: Controleer uw internetverbinding

**Probleem**: Sessie verlopen
- **Oplossing**: Log opnieuw in
- **Tip**: Sessies verlopen na 1 uur inactiviteit



---

## Technische Specificaties

### Systeemvereisten
- **Browser**: Chrome, Firefox, Safari, Edge (laatste 2 versies)
- **JavaScript**: Moet ingeschakeld zijn
- **Cookies**: Moet ingeschakeld zijn
- **Internetverbinding**: Stabiele verbinding vereist

### Bestandsspecificaties

#### CSV Formaat
- **Encoding**: UTF-8 of Latin-1
- **Scheidingsteken**: Tab voor Trodis, puntkomma voor Portavita
- **Tekstkwalificatie**: Dubbele aanhalingstekens (")
- **Regeleindes**: Windows (CRLF) of Unix (LF)



### Beveiliging
- **HTTPS**: Alle communicatie is versleuteld
- **Sessiebeveiliging**: Automatische uitlog na inactiviteit
- **Bestandsbeveiliging**: Bestanden alleen toegankelijk voor eigen dienst
- **Audit Trail**: Alle acties worden gelogd
- **GDPR Compliant**: Voldoet aan privacywetgeving

### Prestaties
- **Upload snelheid**: Afhankelijk van bestandsgrootte en verbinding
- **Verwerkingstijd**: Gemiddeld 30-60 seconden per 1000 patiënten
- **Beschikbaarheid**: 99.5% uptime garantie
- **Backup**: Dagelijkse backups van alle data

---

## Bijlagen



### A. Foutcodes
- **ERR001**: Ongeldige bestandsnaam
- **ERR002**: Bestand te groot
- **ERR003**: Ongeldige bestandsinhoud
- **ERR004**: Database verbindingsfout
- **ERR005**: Onvoldoende rechten

### B. Versiegeschiedenis
- **v2025.1**: Huidige versie, verbeterde beveiliging en UI
- **v2024.1**: Verbeterde beveiliging, nieuwe UI
- **v2023.2**: Google Authenticator ondersteuning
- **v2023.1**: Portavita ondersteuning toegevoegd
- **v2022.1**: Eerste productieversie

---

*Deze handleiding is geldig voor TOP versie 2025.1*  
*Laatste update: Juni 2025* 