

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

## Plugin MichaThemeV5 installieren (ab 0.5.0)

Das Plugin ist die bequeme Einstellungsseite. Das Theme läuft auch ohne.

1. Lade das Plugin-ZIP herunter: `michatheme-v5-plugin-0.5.0.zip` (Release-Seite, gleiche Version wie das Theme). Lade auch die Datei `.sha256` und prüfe die Prüfsumme (Terminal: `shasum -a 256 michatheme-v5-plugin-0.5.0.zip`, die Zahl muss gleich sein).
2. WordPress: Plugins, Installieren, Plugin hochladen, ZIP wählen, installieren, aktivieren.
3. Nach dem Aktivieren öffnet sich die Seite „Info“. Dort steht die Karte „Theme einrichten“. Es wird **nichts automatisch kopiert**.
4. Hast du das Theme schon? Dann ist hier nichts zu tun. Sonst: Häkchen „Ich habe ein Backup“ setzen, „Theme kopieren“ klicken, auf der Rückfrage-Seite bestätigen. Danach „Theme aktivieren“ und wieder bestätigen. Ein vorhandener Theme-Ordner wird vorher gesichert (`uploads/michatheme-v5-backup`). Kind-Themes werden nie verändert.
5. Menü „MichaThemeV5“, Einstellungen: Hier stellst du alles ein.
6. Etwas schiefgegangen? Info, „Einstellungen wiederherstellen“ (Backup-Häkchen setzen). Oder das Preset „Standard“ anwenden.

Wichtig: Beim Entfernen des Plugins bleiben alle Einstellungen im Theme erhalten.

## Eigene Farben und Presets

1. Menü MichaThemeV5, Einstellungen, oben stehen die Presets: Standard, Glas, Kompakt. „Anwenden“ klicken. Vorher wird automatisch gesichert.
2. Eigene Farben: Ansicht „Experte“ wählen, Gruppe „Eigene Farben“ öffnen. Jede Farbe gibt es für Hell und Dunkel. Leer heißt Standard.
3. „Farbwähler öffnen“ zeigt den Colorpicker. Mit „Aktuelle Farbe speichern“ legst du bis zu 12 Farben in die Palette. „Standard nutzen (Farbe leeren)“ setzt das Feld zurück.
4. Unter der Überschrift siehst du die Vorschau (Hell und Dunkel) und Warnungen zum Kontrast. Warnung heißt: Text wäre schwer zu lesen. Du kannst trotzdem speichern.
5. Erlaubte Farben: `#rgb`, `#rrggbb` oder `#rrggbbaa`. Alles andere wird beim Speichern geleert.

Verträglichkeit: Das Plugin lädt nichts in deinem Shop und nichts im Seitenbaukasten (Divi, Elementor). Es nutzt keine externen Dienste für Schriften oder Skripte.
