

## Recht: Felder und Hinweise

Das Theme liest diese Custom Fields am Produkt (Bearbeiten, Zusatzfelder einblenden): `_mt_delivery_time` (Lieferzeit), `_mt_unit_price` (Grundpreis als Text, z. B. 12,50 € / kg), `_mt_lowest_price_30` (niedrigster Preis der letzten 30 Tage als Zahl, nur bei Streichpreis), `_mt_gpsr_manufacturer`, `_mt_gpsr_responsible_person`, `_mt_gpsr_contact`, `_mt_gpsr_safety_info` (Produktsicherheit). Ist Germanized oder German Market aktiv, übernimmt das Plugin Preishinweis, Grundpreis und Lieferzeit. Für GPSR-Felder der Plugins den Filter `mt_gpsr_data` nutzen. Widerrufs-Link: Seite wählen oder den Endpunkt von WooCommerce Order withdrawal (ab 11.1, unter Einstellungen, Erweitert, Funktionen einschalten) verwenden. YouTube- und Vimeo-Einbettungen werden erst nach Klick geladen; Consent-Manager können die Ereignisse `mt:consent-ready`, `mt:consent-open` und `mt:video-before-load` nutzen. Technische Hinweise, keine Rechtsberatung. Texte und Einsatz anwaltlich prüfen lassen. Eigene Cookie-Banner liefert das Theme nicht; den Bestellbutton beschriftet die Kasse bzw. ein Rechtsplugin (Button-Lösung § 312j BGB).

## Symbol-Leiste im Kopfbereich (ab 0.4.0)

Rechts oben stehen kleine Symbole: Hell/Dunkel-Schalter, Glocke, Merkliste, Favoriten, Konto, Warenkorb. So stellst du sie ein:

1. Öffne im WordPress-Menü Design, dann Customizer.
2. Gruppe **Farben**: Bei „Ort des Hell/Dunkel-Schalters“ wählst du Header, Footer oder Benutzerkonto-Einstellungen.
3. Gruppe **Kopfbereich**: „Favoriten (Herz-Liste) anzeigen“ schaltet die Herzen an oder aus.
4. Klicke auf **Veröffentlichen**.

Gut zu wissen: Glocke, Merkliste und Favoriten sind unsichtbar, solange nichts drin ist. Die Merkliste gibt es nur mit dem Plugin „YITH WooCommerce Wishlist“ oder „TI WooCommerce Wishlist“. Die Glocke braucht das Plugin „MGD Notification Center“. Alle Einzelheiten stehen in [`ICON-LEISTE.md`](ICON-LEISTE.md).

## Aussehen und Farben (ab 0.3.0)

Im WordPress-Menü unter Design, Customizer, Farben stellst du alles ein.

1. Farbmodus: „Wie das Gerät“, „Hell“ oder „Dunkel“.
2. Akzentfarbe: Standard ist Rot. Du kannst eine andere Farbe wählen oder eine eigene Farbe eintragen.
3. Rot (oder deine Farbe) erscheint nur bei den wichtigen Buttons, beim aktiven Menüpunkt, bei der aktuellen Seitenzahl und beim Warenkorb-Zähler.
4. Lieferzeit: Im Customizer, Gruppe Recht, steht das Feld „Lieferzeit“. Es ist leer. Trage nur etwas ein, was für deinen Shop stimmt.

## Farben und Colorpicker

Der Colorpicker ist ein Farbwähler mit Farbfläche, Farbton-Regler, HEX-Feld (zum Beispiel `#c8161a`) mit Kopieren-Knopf, einer Palette für bis zu 12 gespeicherte Farben und einer Warnung bei schlecht lesbarem Text auf der Akzentfarbe.

Wichtig: Er ist fertig und getestet, aber in diesem Theme noch nicht zu sehen. Er kommt mit den Einstellungsseiten der Plugins in Version 0.5.0. Bis dahin gilt:

1. Öffne die Theme-Einstellungen (WordPress-Menü, Design, Customizer, Farben).
2. Wähle bei „Akzentfarbe“ eine Farbe aus der Liste.
3. Oder wähle „Eigene Akzentfarbe“ und trage den HEX-Wert ein, zum Beispiel `#c8161a`.
4. Prüfe die Seite hell und dunkel. Text auf der Farbe muss gut lesbar bleiben.
