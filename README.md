# Metzler AntiVPN & Proxy Blocker für WordPress

Ein schlankes und ressourcenschonendes WordPress-Plugin zur zuverlässigen Blockierung von unerwünschtem Traffic durch VPN-Dienste, Proxys und Datacenter-IPs. Entwickelt von Metzler-Webseiten.de mit einem klaren Fokus auf Performance, DSGVO-konformes Logging und den Erhalt der SEO-Rankings.

Das Plugin nutzt die Metzler AntiVPN Premium API zur Echtzeit-Prüfung von IP-Adressen und schützt Formulare, Logins oder die gesamte Webseite vor automatisiertem oder anonymisiertem Spam. **Für den unkomplizierten Einstieg stehen monatlich 1.000 Abfragen dauerhaft kostenlos zur Verfügung.**

## Kernfunktionen

* **Zuverlässige Erkennung:** Serverseitige Blockierung von VPNs, Proxys und Datacenter-Netzwerken.
* **Sicherer SEO-Bot Bypass:** Suchmaschinen-Crawler (wie Googlebot oder Bingbot) werden über einen Reverse-DNS-Check validiert und passieren den Schutz ungehindert. Die Sichtbarkeit in Suchmaschinen bleibt zu 100 % erhalten.
* **Effizientes Caching:** Um die Ladezeiten der WordPress-Instanz nicht zu belasten und das API-Kontingent zu schonen, werden geprüfte IPs für 12 Stunden lokal in WordPress-Transients zwischengespeichert.
* **Zwei Prüf-Modi:**
    * *Global (Jeder Seitenaufruf):* Maximaler Schutz für die gesamte Webseite.
    * *Selektiv (Nur Formular-POSTs):* Ressourcenschonender Schutz, der nur beim Absenden von Daten greift (ideal gegen Spam).
* **Flexible Reaktionen:** Bei einem erkannten VPN greift entweder eine integrierte, saubere 403-Fehlerseite oder eine Weiterleitung auf eine definierte URL.
* **Fail-Open-Architektur:** Sollte die API aufgrund von Netzwerkproblemen nicht erreichbar sein, wird der Traffic standardmäßig durchgelassen. Reguläre Nutzer werden nicht versehentlich ausgesperrt.

## Technische Details

Wir verzichten auf unnötigen Code-Ballast. Das Plugin ist auf Stabilität und Wartbarkeit ausgelegt:
* **DSGVO-konformes Logbuch:** Die letzten 500 Zugriffsversuche (IP, Status, User-Agent) werden lokal in einer eigenen, leichten Datenbanktabelle protokolliert. Die neuesten 100 Einträge sind direkt im WordPress-Backend einsehbar. Nutzer von Premium-Tarifen profitieren zudem von einem zentralen Logbuch im Metzler-Portal, in dem alle Zugriffsdaten nach 7 Tagen vollautomatisch anonymisiert werden.
* **Kein Frontend-Gewicht:** Das Plugin lädt keine unnötigen CSS- oder JavaScript-Dateien im Frontend der Webseite.
* **Sichere IP-Ermittlung:** Das Script prüft diverse Header (`HTTP_CF_CONNECTING_IP`, `HTTP_X_FORWARDED_FOR`, etc.), um auch hinter Reverse Proxys wie Cloudflare die echte Client-IP zuverlässig zu identifizieren.

## Installation

Die aktuellste Version des Plugins wird über GitHub als Release bereitgestellt.

1. Laden Sie die neueste Version als ZIP-Datei herunter:  
   **[Download der aktuellen Version](https://github.com/metzler-webseiten-de/WPMetzlerAntiVPN/releases/tag/RELEASE)**
2. Navigieren Sie im WordPress-Backend zu **Plugins > Installieren** und klicken Sie oben auf **Plugin hochladen**.
3. Wählen Sie die heruntergeladene ZIP-Datei aus und klicken Sie auf **Jetzt installieren**.
4. Aktivieren Sie das Plugin **Metzler AntiVPN & Proxy Blocker**.
5. Nach der Aktivierung steht der neue Menüpunkt **AntiVPN** in der WordPress-Seitenleiste zur Verfügung.

## Konfiguration

Für den Betrieb wird ein API-Key benötigt.

1. **API-Key beziehen:** Im [Metzler Portal](https://portal.metzler-webseiten.de/antivpn/api-keys) einloggen und einen neuen Key generieren (beinhaltet 1.000 kostenlose Abfragen pro Monat).
2. **Key hinterlegen:** Im WordPress-Backend unter **AntiVPN > Einstellungen** den Bearer Token eintragen. Das System prüft die Gültigkeit beim Speichern automatisch.
3. **Modus & Aktion wählen:** Den gewünschten Prüf-Modus (Global oder Formular-POST) festlegen und entscheiden, wie blockierte Besucher behandelt werden sollen.
4. **Dashboard prüfen:** Im Tab "Dashboard" lässt sich jederzeit das aktuelle API-Kontingent (Limit, Genutzt, Verbleibend) sowie der Zeitpunkt des nächsten Resets einsehen.

## Über Metzler-Webseiten.de

Hinter diesem Plugin steht Tiziano Santo Metzler von Metzler-Webseiten.de – einer Agentur für Online-Marketing, SEO und Webentwicklung aus Hoyerswerda. Wir bauen Webseiten und Systeme, die wartbar bleiben, nicht beim kleinsten Update zusammenbrechen und echten geschäftlichen Mehrwert liefern. Technik ist für uns kein Selbstzweck, sondern solides Handwerk.

Mehr Informationen unter: [metzler-webseiten.de/](https://metzler-webseiten.de/)
