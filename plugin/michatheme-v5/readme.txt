=== MichaThemeV5 ===
Contributors: michaelgahndesign
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Übersichtliche Einstellungsseite, Lizenzverwaltung und Plugin-Empfehlungen für das Theme MichaThemeV5.

== Description ==

Das Plugin ergänzt das Theme MichaThemeV5 um eine Einstellungsseite (durchsuchbar, mit Einfach- und Experten-Ansicht), eine Seite für den Lizenzcode, Plugin-Empfehlungen und eine Info-Seite. Das Theme funktioniert auch ohne das Plugin.

Die Aktiv-Meldung (höchstens einmal am Tag an theme.michael-gahn.de: Plugin, Version, Shopsystem, Domain, zufällige ID) lässt sich unter MGD Tools abschalten.

Datenschutz: Zur Lizenzprüfung gehen nur Lizenzcode und Domain an https://theme.michael-gahn.de. Die Plugin-Empfehlungen werden nur im Admin geladen. Besucher Ihres Shops sind nicht betroffen.

== Changelog ==

= 2.1.1 =
* Gemeinsame Verwaltung unter MGD Tools: Einstellungen, Lizenzen und Info als Reiter, Updates, Weitere Plugins und der Schalter „Installation melden“ gemeinsam mit den anderen MGD-Plugins.
* Einheitlicher Plugin-Kopf und Icons.

= 1.6.0 =
* Neu: Menüpunkt „Updates und Weitere Plugins“ (Info, Updates, Weitere Plugins, Installation melden) über den gemeinsamen Baukasten.
* Der Schalter „Installation melden“ steht jetzt nur noch dort, nicht mehr unten in den Einstellungen. Zustand und Installations-ID bleiben.
* Update-Prüfung und Aktiv-Meldung laufen über den Baukasten. Prüfsumme, Größe und Pfade werden weiter geprüft, automatische Updates bleiben aus.
* Beim Löschen des Plugins wird die Installation abgemeldet.
* Einstellungen, Farben und Lizenzcode bleiben erhalten. Vor dem Update bitte eine Sicherung machen.

= 1.5.1 =
* Favicon: Ab Werk zeigt der Shop das Michael-Gahn-DESIGN-Logo. Eigenes Icon unter Design > Customizer > Website-Identität > Website-Icon. Ohne eigenes Icon erscheint wieder das Logo.

= 1.5.0 =
* Neu: Aktiv-Meldung. Das Plugin meldet sich höchstens einmal am Tag bei theme.michael-gahn.de (Plugin, Version, Shopsystem, Domain, zufällige ID). Nur im Admin.
* Neu: Schalter unter MichaThemeV5, Einstellungen, Kasten „Datenschutz: Aktiv-Meldung“. Ab Werk an, jederzeit abschaltbar. Das Plugin funktioniert dann genauso weiter.
* Update-Prüfung höchstens einmal am Tag über die Aktiv-Meldung, sonst wie bisher über die feste Datei. Alle Sicherheitsprüfungen bleiben gleich.
* Beim Löschen des Plugins werden auch die Daten der Aktiv-Meldung entfernt.

= 1.4.0 =
* Erste fertige Version (keine Vorabversion mehr).
* Einheitliche Versionsnummer 1.4.0 für alle Produkte von Michael Gahn DESIGN.
* Keine Änderungen an Funktionen seit 0.6.1. Vor dem Update bitte eine Sicherung machen.

= 0.6.1 =
* Lizenzprüfung nur noch per HTTPS-Header (kein Code in Adressen).
* Updates nur mit Prüfsumme von theme.michael-gahn.de, keine automatischen WordPress-Updates für dieses Plugin.
* "Lizenzcode entfernen" entfernt den Code überall.

= 0.6.0 =
* Neu: Demo-Artikel importieren und entfernen (Werkzeuge, Demo-Inhalte, oder WP-CLI).

= 0.5.1 =
* Korrigiert: Absturz, wenn das Plugin zusammen mit einem Theme vor 0.5.0 aktiviert wird.

= 0.5.0 =
* Neu: Einstellungsseite, Lizenzen, Plugin-Empfehlungen, Info.
