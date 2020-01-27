<?php
/******************************************************************************
 * Konfigurationsdatei fuer das Admidio-Plugin Appmidio
 *
 * Copyright    : (c) 2012 The Zettem Team
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 *****************************************************************************/

// Soll der Titel des Plugins in Admidio angezeigt werden?
// 1 = (Default) Titel wird angezeigt
// 0 = Titel wird nicht angezeigt
$plg_show_title = 1;

// Soll die Bearbeitung über Admidio möglich sein?
// 1 = Einträge (z. Bsp. Profil-Daten) können über Admdidio bearbeitet werden
// 0 = (Default) Einträge können nicht über Admidio bearbeitet werden
$plg_enable_admidio_edit = 0;

// Soll die Geburtstagsliste in der App sichtbar sein?
// 1 = (Default) Geburtstagsliste ist sichtbar
// 0 = Geburtstagsliste ist nicht aktiviert
$plg_enable_birthday_module = 1;

// Auflistung der Einträge, die als Jubiläum angezeigt werden
// (mehrere Einträge getrennt durch Komma. z. Bsp: '10,50,100')
$plg_birthday_anniversaries = '10,18,20,30,40,50,60,65,70,75,80,85,90,95,100,105,110,115,120';

// Liste der Rollen, deren Mitglieder für die Geburtstagsliste an die Android-App übermittelt werden
// Es muss die Id der entsprechenden Rolle eingetragen werden
// (mehrere Rollen getrennt durch Komma. z. Bsp: '1,2,3')
$plg_birthday_roles = '';

// Liste der Kategorien, die nicht an die Android-App übermittelt werden
// Es muss die Id der entsprechenden Kategorie eingetragen werden (z. Bsp. für Kontodaten)
// (mehrere Kategorien getrennt durch Komma. z. Bsp: '1,2,3')
$plg_excluded_categories = '';

// Liste der Rollen, die nicht an die Android-App übermittelt werden
// Es muss die Id der entsprechenden Rolle eingetragen werden
// (mehrere Rollen getrennt durch Komma. z. Bsp: '1,2,3')
$plg_excluded_roles = '';

// Liste der Felder, die nicht an die Android-App übermittelt werden
// Es muss die Id der entsprechenden Kategorie eingetragen werden
// (mehrere Felder getrennt durch Komma. z. Bsp: '1,2,3')
$plg_excluded_fields = '';

?>
