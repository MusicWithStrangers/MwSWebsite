<?php
/******************************************************************************
 * Appmidio
 *
 * Erstellt f�r die Appmido-App den QR-Code f�r die Anzeige im Plugin
 *
 * �bergaben:
 *
 * Copyright    : (c) 2013 The Zettem Team
 * Homepage     : https://play.google.com/store/apps/details?id=de.zettem.Appmidio
 * License      : GNU Public License 3 http://www.gnu.org/licenses/gpl-2.0.html
 *
*****************************************************************************/

require_once 'phpqrcode.php';
QRcode::png($_REQUEST['text']);

?>
