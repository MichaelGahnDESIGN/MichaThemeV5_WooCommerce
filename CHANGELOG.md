# Änderungen

Hier steht, was sich von Version zu Version geändert hat. Das Theme ist noch in einer frühen Phase (Pre-Alpha). Bitte nicht in einem echten Shop einsetzen, ohne es vorher selbst zu testen.

## 0.4.1 (28.10.2026, Pre-Alpha)

Kleine Korrektur zu 0.4.0 und bessere Anleitung.

**Korrigiert**

- Korrigiert: Zähl-Texte für Screenreader stimmen jetzt in der Einzahl. Bei genau einem Favoriten heißt es „Favoriten, 1 Eintrag“, bei mehreren „Favoriten, 2 Einträge“ (vorher stand bei einem Favoriten „1 Einträge“).

**Doku**

- Anleitung für die Symbol-Leiste jetzt auch in `docs/INSTALL.md` und in der `README.md`.
- Neuer Abschnitt „Farben und Colorpicker“. Ehrlich: Der Colorpicker ist fertig, aber noch nicht im Theme sichtbar. Er kommt mit den Plugin-Einstellungsseiten in Version 0.5.0.

## 0.4.0 (28.10.2026, Pre-Alpha)

Neue Symbol-Leiste im Kopfbereich.

**Neu**

- Die Leiste im Kopfbereich zeigt von links nach rechts: Hell/Dunkel-Schalter, Benachrichtigungen (Glocke), Merkliste, Favoriten, Konto, Warenkorb. Die Suche bleibt in der Mitte.
- Der Hell/Dunkel-Schalter ist im Kopfbereich nur noch ein Symbol (kein Text mehr). Welcher Modus gerade gilt, steht im Tooltip und für Screenreader im Label.
- Neue Einstellung im Customizer (Farben): „Ort des Hell/Dunkel-Schalters“. Zur Wahl stehen Header, Footer und Benutzerkonto-Einstellungen. Der Schalter erscheint nur am gewählten Ort. Im Konto steht er unter dem Konto-Menü und ist nur für eingeloggte Kunden sichtbar.
- Favoriten: Auf Produktkarten (Shop, Suche, verwandte Produkte) und auf der Artikelseite gibt es ein Herz. Die Liste wird nur im Browser des Besuchers gespeichert und nicht an den Shop gesendet. Das Favoriten-Symbol im Kopfbereich erscheint erst, wenn mindestens ein Favorit da ist. Abschalten geht im Customizer (Kopfbereich, „Favoriten“).
- Die Glocke erscheint nur, wenn das Plugin MGD Notification Center wirklich Einträge liefert. Sie sieht jetzt wie die anderen Symbole aus (Linien-Glocke statt Emoji, neutraler Zähler).
- Zähler an Merkliste und Favoriten sind neutral grau. Nur der Warenkorb-Zähler bleibt in der Akzentfarbe.

**Gut zu wissen**

- WooCommerce hat von sich aus keine Merkliste. Das Merkliste-Symbol erscheint nur, wenn „YITH WooCommerce Wishlist“ oder „TI WooCommerce Wishlist“ aktiv ist. Sonst fehlt es einfach. Die Zahl wird beim Laden der Seite ermittelt (bei Seiten-Zwischenspeichern kann sie kurz alt sein).
- Die Glocke bleibt bei 0 Einträgen auf allen Plätzen (Kopfbereich, Footer, schwebend) unsichtbar.

## 0.3.1 (28.10.2026, Pre-Alpha)

Kleine Korrektur zu 0.3.0.

**Geändert**

- Die Ankündigungsleiste hat keinen vorausgefüllten Text mehr. Wer sie einschaltet, schreibt den Text selbst.
- Prozent-Siegel und Countdown sind im Standard aus.
- Textlinks ändern beim Darüberfahren ihre Farbe auf die Link-Farbe der Akzentfarbe (gut lesbar im Hell- und Dunkelmodus).

## 0.3.0 (28.10.2026, Pre-Alpha)

Das Theme hat ein neues, einheitliches Aussehen. Es passt jetzt zu den Themes für Shopware, JTL und Shopify.

**Neu im Aussehen**

- Dunkelmodus in ruhigem Anthrazit, Hellmodus in angenehmem Weiß und Hellgrau.
- Standard-Akzent ist jetzt Rot. Die anderen Farben und die eigene Akzentfarbe bleiben in den Theme-Einstellungen (Customizer) wählbar.
- Kopfbereich: Logo links, Suche in der Mitte, Konto, Warenkorb und Hell/Dunkel-Schalter rechts. Das Menü steht in einer zweiten Zeile. Der aktive Menüpunkt ist rot unterstrichen.
- Der Warenkorb hat ein Symbol statt eines Emojis. Die Zahl steht in einer roten Pille und aktualisiert sich nach „In den Warenkorb“.
- Produktkarten haben einen feinen Rahmen und springen beim Darüberfahren nicht mehr.
- Rot ist nur noch für Hauptaktionen da (In den Warenkorb, Zur Kasse, Zahlungspflichtig bestellen, Anmelden, aktuelle Seitenzahl, Warenkorb-Zähler). Alle anderen Buttons, zum Beispiel „Weiter einkaufen“, sind neutral umrandet.
- Sonderpreis-Siegel sind hell getönt mit Text („Sonderpreis“) und nie mehr weiße Schrift auf Rot. Ausverkaufte Artikel zeigen nur das Siegel „Ausverkauft“.
- Hinweise und Fehlermeldungen haben Symbol, Text und Rahmen. Fehler beginnen mit „Fehler:“ und sind nie rot gefüllt.
- Preise sind fett mit gleich breiten Ziffern, der Aktionspreis steht in der Textfarbe, der alte Preis ist durchgestrichen.
- Warenkorb, Kasse, Konto, Tabellen, Formulare und Seitenzahlen nutzen die Theme-Farben (auch im Dunkelmodus).
- Auf dem Handy bleibt der Kopfbereich nicht mehr oben haften (drei Zeilen würden zu viel Platz brauchen). Ab Tablet-Breite bleibt er fixiert, wenn die Option an ist.

**Geändert**

- Die Lieferzeit hat keinen vorausgefüllten Text mehr. Wer sie nutzt, trägt sie selbst ein. Bestehende Shops, die den alten Standardtext nie gespeichert hatten, zeigen die Lieferzeit nicht mehr an.
- Über „Zur Kasse“ steht ein Satz: „Versandkosten und Gesamtbetrag sehen Sie, bevor Sie bestellen.“
- Der Hell/Dunkel-Schalter speichert die Wahl erst, wenn der Besucher selbst umschaltet.

**Hinweis**

- Shops, die schon eine Akzentfarbe gespeichert haben, behalten ihre Wahl. Nur neue Installationen starten mit Rot.

## 0.2.1 (27.10.2026, Pre-Alpha)

Diese Version bringt alles zusammen, was seit 0.2.0 dazugekommen ist, und Korrekturen aus den Tests im Demo-Shop.

**Neu**

- Recht, Teil 1 (Einstellungen im Customizer, Angaben pro Artikel in Zusatzfeldern; Details in `docs/INSTALL.md`):
  - Lieferzeit am Artikel
  - Grundpreis am Artikel
  - niedrigster Preis der letzten 30 Tage, wenn ein Streichpreis angezeigt wird
  - Hinweis zur gesetzlichen Gewährleistung auf der Artikelseite und im Warenkorb
  - Reiter „Produktsicherheit“ (GPSR) auf der Artikelseite
  - Ausverkaufte Artikel kennzeichnen oder in Listen ausblenden
  - Link „Vertrag widerrufen“ im Footer
  - Link zur Barrierefreiheitserklärung im Footer
  - Button „Cookie-Einstellungen“ im Footer (er erscheint nur, wenn ein Consent-Manager vorhanden ist)
- Videos von YouTube und Vimeo laden erst nach einem Klick (2-Klick-Lösung).
- Wenn das Plugin Germanized oder German Market aktiv ist, übernimmt das Plugin Preishinweis, Grundpreis und Lieferzeit.
- Notification Center: Wenn das Plugin „MGD Notification Center“ aktiv ist, zeigt das Theme die Glocke an. Die Platzierung stellst du im Customizer ein.

**Behoben**

- Produktkarten in Listen waren nur etwa 100 Pixel breit.
- Das Video-Fenster hat jetzt einen Titel, damit Screenreader es vorlesen können.
- Ein Hilfetext nannte ein Pro-Merkmal. Er ist jetzt neutral.

**Wichtig zu wissen**

- Alle Rechts-Funktionen sind technische Hilfen und keine Rechtsberatung. Texte und Einsatz bitte von einer Anwältin oder einem Anwalt prüfen lassen.
- Das Theme liefert keinen eigenen Cookie-Banner. Den Bestellbutton beschriftet die Kasse oder ein Rechts-Plugin.
- Die Funktionen sind bisher im Demo-Shop getestet, aber nicht in vielen verschiedenen Shops.

## 0.2.0 (21.10.2026, Pre-Alpha)

Erste Vorab-Version der kostenlosen Free-Edition: Hell- und Dunkelmodus, Farben und Schriften als Einstellungen, lokale Schriften ohne Google, Grundgerüst für WooCommerce.
