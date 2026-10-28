# MichaThemeV5 für WooCommerce

Theme von Michael Gahn DESIGN mit Hell-/Dunkelmodus, vielen Optionen und rechtssicherer Ausrichtung für DE und EU.

**Status:** in Entwicklung (Pre-Alpha).

## Funktionen

30 Free-Funktionen sind aufgeführt (fertig oder noch in Arbeit, den Stand zeigt die Liste). Dieses Repo enthält ausschließlich die kostenlosen Funktionen.
Die vollständige Liste mit Status steht in [`docs/FEATURES.md`](docs/FEATURES.md), offene Punkte in [`TODO.md`](TODO.md).

## Aussehen (ab Version 0.3.0)

Hell: Weiß und Hellgrau. Dunkel: Anthrazit. Der Standard-Akzent ist Rot und lässt sich in den Theme-Einstellungen ändern. Rot steht nur bei bedienbaren Dingen (Kaufen-Button, Menüpunkt, Seitenzahl). Alle anderen Buttons sind neutral. Der Hell/Dunkel-Umschalter sitzt standardmäßig in der Kopfzeile.

## Farben und Colorpicker

Der Colorpicker ist ein Farbwähler. Er hat eine Farbfläche, einen Farbton-Regler und ein Feld für den HEX-Wert (zum Beispiel `#c8161a`) mit Kopieren-Knopf. Dazu kommt eine Palette für bis zu 12 gespeicherte Farben und eine Warnung, wenn Text auf deiner Akzentfarbe schlecht lesbar wäre.

Ehrlich gesagt: Der Colorpicker ist fertig und getestet, aber in diesem Theme noch nicht zu sehen. Er wird mit den Einstellungsseiten der Plugins in Version 0.5.0 eingebunden. Bis dahin wählst du die Akzentfarbe wie bisher: aus der Liste oder als eigene Farbe (HEX-Wert) in den Theme-Einstellungen.

## Icon-Leiste in der Kopfzeile (ab Version 0.4.0)

Von links nach rechts: Hell/Dunkel-Schalter, Glocke, Merkliste, Favoriten, Konto, Warenkorb. Die Suche bleibt in der Mitte. Glocke, Merkliste und Favoriten sind unsichtbar, solange nichts drin ist.

- **Hell/Dunkel-Schalter:** In der Kopfzeile nur ein Symbol (ohne Text). Den Ort ändern: WordPress-Menü, Design, Customizer, Farben, „Ort des Hell/Dunkel-Schalters“ (Header, Footer oder Benutzerkonto).
- **Glocke:** Kommt vom Plugin „MGD Notification Center“ und erscheint erst, wenn es mindestens einen Eintrag liefert.
- **Merkliste:** WooCommerce hat von sich aus keine. Das Symbol erscheint nur, wenn „YITH WooCommerce Wishlist“ oder „TI WooCommerce Wishlist“ aktiv ist.
- **Favoriten:** Eine eigene Herz-Liste des Themes. Das Herz sitzt auf den Produktkarten und auf der Artikelseite. Die Liste steht nur im Browser des Besuchers. Abschaltbar im Customizer („Favoriten (Herz-Liste) anzeigen“).

Mehr dazu: [`docs/ICON-LEISTE.md`](docs/ICON-LEISTE.md).

## Weitere Funktionen (Pro)

Dieses Repo enthält nur die kostenlosen Funktionen (Free). Pro-Funktionen und Erweiterungen werden separat und privat ausgeliefert und sind nicht Teil dieses Repos; Informationen, Kauf und Verwaltung unter https://theme.michael-gahn.de (Konto erforderlich).

## Lizenz

Das Theme ist proprietär (so steht es auch in `style.css`): Copyright (c) 2026 Michael Gahn DESIGN, alle Rechte vorbehalten. Der Text steht in der Datei [`LICENSE`](LICENSE).

- Die mitgelieferten Schriften stehen unter der SIL Open Font License. Die Lizenztexte liegen in `assets/mt/fonts/`.
- Fremdcode und Plugins behalten ihre eigene Lizenz.

Das ist keine Rechtsberatung; die Lizenzfrage wird noch rechtlich geprüft.

## Rechtliches

Alle rechtlichen Angaben sind technische Hinweise zum Stand der Erstellung und keine Rechtsberatung. Vor Veröffentlichung von einer Anwältin oder einem Anwalt prüfen lassen.
