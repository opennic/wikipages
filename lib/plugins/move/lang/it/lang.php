<?php
/**
 * italian language file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Dario Sguassero <>
 */

// settings must be present and set appropriately for the language
$lang['encoding']   = 'utf-8';
$lang['direction']  = 'ltr';

// for admin plugins, the menu prompt to be displayed in the admin menu
// if set here, the plugin doesn't need to override the getMenuText() method
$lang['menu'] = 'Pagina/Namespace Sposta/Rinomina...';
$lang['desc'] = 'Page/Namespace Sposta/Rinomina Plugin';

$lang['notexist']    = 'La pagina %s non esiste';
$lang['medianotexist']    = 'Il file media %s non esiste';
$lang['notwrite']    = 'Non hai permessi sufficienti per modificare questa pagina';
$lang['badns']       = 'Carattere invalido nel namespace.';
$lang['badname']     = 'Carattere invalido nel nome della pagina';
$lang['nochange']    = 'Il nome della pagina ed il namespace non sono stati modificati.';
$lang['nomediachange']    = 'Il nome del file media e il namespace non sono stati modificati.';
$lang['existing']    = 'Una pagina chiamata %s esiste già in %s';
$lang['mediaexisting']    = 'Un file media chiamato %s esiste già in %s';
$lang['root']        = '[Namespace radice]';
$lang['current']     = '(Corrente)';
$lang['renamed']     = 'Il nome della pagina è stato cambiato da %s a %s';
$lang['moved']       = 'Pagina spostata da %s a %s';
$lang['move_rename'] = 'Pagina spostata e rinominata da %s a %s';
$lang['delete']		= 'Cancellato da move (sposta) plugin';
$lang['norights']    = 'Non hai permessi sufficienti per modificare %s.';
$lang['nomediarights']    = 'Non hai permessi sufficienti per cancellare %s.';
$lang['notargetperms'] = 'Non hai permessi sufficienti per creare la pagina %s.';
$lang['nomediatargetperms'] = 'Non hai i permessi per creare il file media %s.';
$lang['filelocked']  = 'La pagina %s è bloccata. Riprova più tardi.';
$lang['linkchange']  = 'Collegamento modificati a causa di un\'operazione di spostamento';

$lang['ns_move_in_progress'] = 'E\' in corso un\'operazione di spostamento di %s pagine e %s file media dal namespace %s al namespace %s.';
$lang['ns_move_continue'] = 'Continua lo spostamento del namespace';
$lang['ns_move_abort'] = 'Interrompi lo spostamento del namespace';
$lang['ns_move_continued'] = 'Lo spostamento del namespace dal namespace %s al namespace %s è ripresa, rimangono ancora %s elementi.';
$lang['ns_move_started'] = 'Lo spostamento di un namespace dal namespace %s al namespace %s è iniziata, %s pagine e %s file media saranno mossi.';
$lang['ns_move_error'] = 'E\' successo un errore mentre era stava continuando lo spostamento del namespace da %s a %s.';
$lang['ns_move_tryagain'] = 'Riprova';
$lang['ns_move_skip'] = 'Salta l\'elemento corrente';
// Form labels
$lang['newname']     = 'Nome della nuova pagina:';
$lang['newnsname']   = 'Nome del nuovo namespace:';
$lang['targetns']    = 'Seleziona il nuovo namespace:';
$lang['newtargetns'] = 'Crea un nuovo namespace:';
$lang['movepage']	= 'Sposta la pagina';
$lang['movens']		= 'Sposta il namespace';
$lang['submit']      = 'Invia';
$lang['content_to_move'] = 'Contenuto da spostare';
$lang['move_pages']  = 'Pagine';
$lang['move_media']  = 'File media';
$lang['move_media_and_pages'] = 'Pagine e file media';
// JavaScript preview
$lang['js']['previewpage'] = 'OLDPAGE saranno spostate nelle NEWPAGE';
$lang['js']['previewns'] = 'Tutte le pagine ed i namespace nel namespace OLDNS saranno mossi verso il namespace NEWNS';
