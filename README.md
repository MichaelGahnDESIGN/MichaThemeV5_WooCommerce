# MichaThemeV5 für WooCommerce

Theme von Michael Gahn DESIGN mit Hell-/Dunkelmodus, vielen Optionen und rechtssicherer Ausrichtung für DE und EU.

**Version:** 1.5.1

⚠️ Bitte vor jedem Update eine Sicherung von Shop und Datenbank machen. Neue Versionen am besten zuerst in einem Test-Shop ausprobieren.

## Lizenzprüfung (ab Version 0.6.1)

Die Lizenzprüfung läuft nur noch per HTTPS. Der Lizenzcode steht nicht in Adressen (URLs), sondern im Kopfteil der Anfrage. Theme und Plugin immer gemeinsam aktualisieren. [Details und Grenzen](docs/LIZENZTRANSPORT.md).

## Funktionen

35 Free-Funktionen sind aufgeführt (fertig oder noch in Arbeit, den Stand zeigt die Liste). Dieses Repo enthält ausschließlich die kostenlosen Funktionen.
Die vollständige Liste mit Status steht in [`docs/FEATURES.md`](docs/FEATURES.md), offene Punkte in [`TODO.md`](TODO.md).

## Aussehen (ab Version 0.3.0)

Hell: Weiß und Hellgrau. Dunkel: Anthrazit. Der Standard-Akzent ist Rot und lässt sich in den Theme-Einstellungen ändern. Rot steht nur bei bedienbaren Dingen (Kaufen-Button, Menüpunkt, Seitenzahl). Alle anderen Buttons sind neutral. Der Hell/Dunkel-Umschalter sitzt standardmäßig in der Kopfzeile.

## Farben und Colorpicker

Der Colorpicker ist ein Farbwähler. Er hat eine Farbfläche, einen Farbton-Regler und ein Feld für den HEX-Wert (zum Beispiel `#c8161a`) mit Kopieren-Knopf. Dazu kommt eine Palette für bis zu 12 gespeicherte Farben und eine Warnung, wenn Text auf deinen Farben schlecht lesbar wäre.

Eingebunden ist er in der Einstellungsseite des Plugins MichaThemeV5: bei WooCommerce ab Plugin 0.5.0, bei JTL-Shop 5 ab Plugin 0.5.1 und bei Shopware 6 im Admin-Modul ab 0.5.1. Dort gibt es 16 Farbfelder je Hell und Dunkel. Ohne Plugin wählst du die Akzentfarbe wie bisher aus der Liste oder als eigene Farbe (HEX-Wert) in den Theme-Einstellungen. Bei Shopify gibt es den Colorpicker nicht: Der Theme-Editor hat einen eigenen Farbwähler, und gespeicherte Paletten sind dort nicht möglich.

Ehrlich gesagt: Die Plugins sind bisher in Test- und Demo-Umgebungen geprüft. Mach vor dem Einsatz in deinem Shop eine Sicherung.

## Icon-Leiste in der Kopfzeile (ab Version 0.4.0)

Von links nach rechts: Hell/Dunkel-Schalter, Glocke, Merkliste, Favoriten, Konto, Warenkorb. Die Suche bleibt in der Mitte. Glocke, Merkliste und Favoriten sind unsichtbar, solange nichts drin ist.

- **Hell/Dunkel-Schalter:** In der Kopfzeile nur ein Symbol (ohne Text). Den Ort ändern: WordPress-Menü, Design, Customizer, Farben, „Ort des Hell/Dunkel-Schalters“ (Header, Footer oder Benutzerkonto).
- **Glocke:** Kommt vom Plugin „MGD Notification Center“ und erscheint erst, wenn es mindestens einen Eintrag liefert.
- **Merkliste:** WooCommerce hat von sich aus keine. Das Symbol erscheint nur, wenn „YITH WooCommerce Wishlist“ oder „TI WooCommerce Wishlist“ aktiv ist.
- **Favoriten:** Eine eigene Herz-Liste des Themes. Das Herz sitzt auf den Produktkarten und auf der Artikelseite. Die Liste steht nur im Browser des Besuchers. Abschaltbar im Customizer („Favoriten (Herz-Liste) anzeigen“).

Mehr dazu: [`docs/ICON-LEISTE.md`](docs/ICON-LEISTE.md).

## Plugin MichaThemeV5 (ab Version 0.5.0)

Das Plugin ist die bequeme Einstellungsseite für das Theme. Das Theme läuft auch ohne Plugin, dann stellst du alles wie bisher im Customizer ein.

Das Plugin bringt vier Seiten mit (WordPress-Menü „MichaThemeV5“):

1. **Einstellungen**: alle Optionen, gruppiert, mit Suche, Ansicht „Einfach“ oder „Experte“, Filter und Live-Hinweisen. Oben stehen die **Design-Presets** (Standard, Glas, Kompakt): ein Klick setzt mehrere Einstellungen. Vorher wird automatisch gesichert. Dazu kommen drei Layout-Einstellungen: **Kartenstil** (flach, mit Rahmen, mit Schatten), **Flächenstil** (einfarbig oder Glas) und **Inhaltsbreite** (schmal, normal, breit).
2. **Eigene Farben** (Gruppe in den Einstellungen): 16 Farben, je für Hell und Dunkel einzeln. Leer heißt: Standard des Themes. Mit Colorpicker, einer Palette für 12 Farben (gilt für den ganzen Shop) und einer Vorschau. Die Warnung zeigt dir, wenn Text zu schwach zu lesen wäre (Text mindestens 4,5 zu 1, Rahmen und Fokus mindestens 3 zu 1). Gespeichert wird trotzdem.
3. **Lizenzen**: Lizenzcode einfügen, Status sehen, Link zum Kauf. Ohne Code läuft alles in der Free-Version.
4. **Plugin-Empfehlungen** und **Info** (Versionen, Theme einrichten, Einstellungen wiederherstellen).

Datenschutz: Für die Lizenzprüfung gehen nur Lizenzcode und Domain an https://theme.michael-gahn.de. Die Empfehlungen werden nur im Admin geladen. Besucher deines Shops sind nicht betroffen.

Verträglichkeit: Das Plugin lädt Skripte und Stile nur auf seinen eigenen Admin-Seiten. Im Shop und in Seitenbaukästen wie Divi oder Elementor tut es nichts.

## Favicon (ab Version 1.5.1)

Das Favicon ist das kleine Bild im Browser-Tab.

- Ab Werk zeigt der Shop das Michael-Gahn-DESIGN-Logo.
- Eigenes Favicon:
  1. WordPress-Admin öffnen.
  2. Design > Customizer > Website-Identität.
  3. Bei „Website-Icon“ ein Bild wählen (quadratisch, mindestens 512 × 512 Pixel).
  4. Veröffentlichen.
- Entfernst du dein Icon wieder, erscheint automatisch das Logo.

## Datenschutz: Aktiv-Meldung (ab Version 1.5.0)

Kurz gesagt: Das **Plugin** MichaThemeV5 meldet sich höchstens einmal am Tag bei theme.michael-gahn.de. So wissen wir, wie viele Shops es nutzen, und können Updates bereitstellen.

**Was wird gesendet?** Nur diese fünf Dinge:

1. Plugin-Name (`michatheme-v5-woocommerce`)
2. Version (z. B. 1.5.0)
3. Shopsystem (`woocommerce`)
4. Die Domain deines Shops (z. B. `mein-shop.de`)
5. Eine zufällige Installations-ID (eine lange Zufallsnummer, nichts Persönliches)

**Was wird nicht gesendet?** Keine Kundendaten, keine E-Mail-Adressen, keine Bestellungen, keine Shop-Inhalte, kein Lizenzcode.

**Wann?**

- Nur wenn du als Admin im WordPress-Adminbereich bist. Besucher deines Shops lösen nie etwas aus.
- Beim ersten Mal bekommt das Plugin einen eigenen Schlüssel. Danach höchstens einmal am Tag „Ich bin noch aktiv“.
- Ist die Plattform nicht erreichbar, passiert nichts Schlimmes. Das Plugin versucht es später noch einmal.

**So schaltest du es ab:**

1. Öffne im WordPress-Menü **MichaThemeV5**, dann **Einstellungen**.
2. Ganz unten steht der Kasten **Datenschutz: Aktiv-Meldung**.
3. Nimm den Haken bei „Installation bei Michael Gahn DESIGN melden“ weg.
4. Klicke auf **Speichern**.

Danach wird nichts mehr gesendet, und dein Eintrag auf der Plattform wird gelöscht. Plugin und Theme funktionieren genauso weiter wie vorher.

**Gut zu wissen:**

- Der Schalter ist ab Werk an.
- Das **Theme allein** (ohne Plugin) meldet sich nie. Das macht nur das Plugin.
- Updates: Das Plugin fragt die Update-Datei höchstens einmal am Tag über dieselbe Meldung ab. Sonst und bei einer Störung nimmt es wie bisher die feste Datei. Die Sicherheitsprüfungen (Prüfsumme, Größe, Pfade, kein automatisches Update) bleiben gleich.
- Löschst du das Plugin, werden auch die gespeicherten Daten der Aktiv-Meldung entfernt. Einträge ohne Meldung seit 12 Monaten löscht die Plattform selbst.

## Demo-Artikel importieren und entfernen (ab Version 0.6.0)

Mit einem Klick bekommt dein Test-Shop 16 Beispielartikel in 4 Kategorien (je 3 Platzhalterbilder, ohne Internetzugriff). Voraussetzung: WooCommerce ist aktiv.

1. WordPress: Werkzeuge, Demo-Inhalte, „Importieren“. Ein zweiter Import legt nichts doppelt an.
2. Zum Aufräumen dort „Entfernen“ wählen und bestätigen.
3. Per WP-CLI: `wp michatheme demo import`, `wp michatheme demo status`, `wp michatheme demo remove` (mit `--yes` ohne Rückfrage).

Entfernen löscht nur markierte Demo-Einträge. Eigene Artikel bleiben. Bitte vorher eine Sicherung machen und zuerst in einem Test-Shop ausprobieren.

## Weitere Funktionen (Pro)

Dieses Repo enthält nur die kostenlosen Funktionen (Free). Pro-Funktionen und Erweiterungen werden separat und privat ausgeliefert und sind nicht Teil dieses Repos; Informationen, Kauf und Verwaltung unter https://theme.michael-gahn.de (Konto erforderlich).

## Lizenz

Das Theme ist proprietär (so steht es auch in `style.css`): Copyright (c) 2026 Michael Gahn DESIGN, alle Rechte vorbehalten. Der Text steht in der Datei [`LICENSE`](LICENSE).

- Die mitgelieferten Schriften stehen unter der SIL Open Font License. Die Lizenztexte liegen in `assets/mt/fonts/`.
- Fremdcode und Plugins behalten ihre eigene Lizenz.

Das ist keine Rechtsberatung; die Lizenzfrage wird noch rechtlich geprüft.

## Rechtliches

Alle rechtlichen Angaben sind technische Hinweise zum Stand der Erstellung und keine Rechtsberatung. Vor Veröffentlichung von einer Anwältin oder einem Anwalt prüfen lassen.

## Paketprüfung für Katalogfreigaben

Der GitHub-Check `packages` baut das uploadfertige Begleitplugin einschließlich Theme und prüft Versionen, die vollständige Theme-Prüfliste und ausgeschlossene Entwicklungsdateien. Er läuft bei Pull Requests, auf `main` und bei neuen `v*`-Tags. Neue Katalogfreigaben benötigen zusätzlich die serverseitige Prüfung des veröffentlichten ZIPs gegen Git und SHA-256. Die bestehende Version 0.6.0 bleibt als bereits abgenommene Bestandsfreigabe erhalten; dieser CI-Nachweis wird nicht rückwirkend behauptet.
