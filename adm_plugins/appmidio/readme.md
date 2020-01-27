Appmidio

Dieses Plugin stellt Daten von Admidio der Android-App Appmidio zur Verf�gung.

Einstellungen k�nnen in der Datei config.php vorgenommen werden.


1. Installation
2. Update
3. Versionshistorie

******************************************************************************

1.Installation

Zur Installation sind folgende Schritte durchzuf�hren:

1.1. Im Ordner adm_plugins einen neuen Ordner mit dem Namen appmidio erstellen.

1.2. Alle Dateien des Ordners appmidio aus der entpackten Datei appmidio_1.4.0.zip in diesen Ordner kopieren.

1.3. Die Datei config_sample.php in config.php umbenenen und den eigenen Bed�rfnissen anpassen.

1.4. Zur Kontrolle, ob das Plugin sauber ansprechbar ist, in der Datei my_body_bottom.php folgende Zeile einf�gen:
     include(SERVER_PATH."/adm_plugins/appmidio/appmidio.php");

     Hinweise:
        (1) Die include-Anweisung sollte nicht innerhalb einer if-Abfrage stehen
        (2) Wenn das Plugin am richtigen Ort installiert wurde, funktioniert Appmidio auch ohne die Erg�nzung
            in der Datei my_body_bottom.php

1.5. Admidio starten, wenn der QR-Code von Appmidio auf der Webseite angezeigt wird, ist das Plugin f�r die
     Kommunikation mit der Appmidio-App betriebsbereit.

******************************************************************************

2. Update

F�r einen Update des Plugins sind folgende Schritte durchzuf�hren:

2.1. Alle Dateien im Pluginverzeichnis von appmidio l�schen (ausser config.php).

2.2. Alle Dateien des Ordners appmidio aus der entpackten Datei appmidio_1.4.0.zip in diesen Ordner kopieren.

2.3. Die Datei config.php den eigenen Bed�rfnissen anpassen.
     Als Vorlage kann die Datei config_sample.php verwendet werden.

2.4. Admidio starten, wenn der QR-Code von Appmidio auf der Webseite angezeigt wird, ist das Plugin f�r die
     Kommunikation mit der Appmidio-App betriebsbereit.

******************************************************************************
