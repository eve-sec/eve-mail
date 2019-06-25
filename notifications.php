<?php

$start_time = microtime(true);
require_once('auth.php');
require_once('config.php');
require_once('loadclasses.php');
require_once('serverstatus.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

$html = '';

if (!isset($_SESSION['ajtoken'])) {
  $_SESSION['ajtoken'] = EVEHELPERS::random_str(32);
}

$footer = '<script>$(document).ready(function() {
            var table = $("#nottable").dataTable(
               {
                   "bPaginate": true,
                   "pageLength": 25,
                   "aoColumnDefs" : [ {
                       "bSortable" : false,
                       "aTargets" : [ "no-sort" ]
                   }, {
                       "sClass" : "num-col",
                       "aTargets" : [ "num" ]
                   } ],
                   fixedHeader: {
                       header: true,
                       footer: false
                   },
                   "order": [[ 0, "desc" ]],
               });
             });
         </script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/css/dataTables.bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.datatables.net/responsive/2.1.1/css/responsive.bootstrap.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/dataTables.bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.1.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.1.1/js/responsive.bootstrap.min.js"></script>
    <script src="js/typeahead.bundle.min.js"></script>
    <script src="js/esi_autocomplete.js"></script>
    <script src="js/bootstrap-contextmenu.js"></script>
    <script src="js/bootstrap-dialog.min.js"></script>
    <link href="css/bootstrap-dialog.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome-animation/0.0.10/font-awesome-animation.min.css" integrity="sha256-C4J6NW3obn7eEgdECI2D1pMBTve41JFWQs0UTboJSTg=" crossorigin="anonymous" />';

$esinot = new ESINOTIFICATIONS($_SESSION['characterID']);

$scopesOK = $esinot->checkScopes(['esi-characters.read_notifications.v1']);
if (!$scopesOK) {
    $scopes = array_unique(array_merge($esinot->getDbScopes(), ['esi-characters.read_notifications.v1']));
    $url = URL::url_path().'login.php?scopes='.implode(' ',$scopes)."&page=".rawurlencode(URL::relative_url());
    header('Location: '.$url);
}


$page = new Page($esinot->getCharacterName().'\'s notifications');

$notifications = $esinot->getNotifications();

$lookup = array();
foreach ($notifications as $n) {
    if ($n['sender_type'] == 'character' || $n['sender_type'] == 'corporation' || $n['sender_type'] == 'alliance') {
        $lookup[] = $n['sender_id'];
    }
}
$dict = EVEHELPERS::esiIdsToNames($lookup);

$html = '<table class="table table-striped small" id="nottable">
             <thead>
                 <th>Date</th>
                 <th class="no-sort"></th>
                 <th>From</th>
                 <th>type</th>
                 <th>Message</th>
             </thead>
             <tbody>';
foreach ($notifications as $n) {
    $html .= '<tr><td>'.gmdate('y/m/d H:i', strtotime($n['timestamp'])).'</td>';
    if (isset($dict[$n['sender_id']])) {
        $html .= '<td><img src="https://imageserver.eveonline.com/'.$n['sender_type'].'/'.$n['sender_id'].'_32.'.($n['sender_type'] == 'character'?'jpg':'png').'" height="24px"></td><td>'.$dict[$n['sender_id']].'</td>';
    } else {
        $html .= '<td></td><td>Unknown</td>';
    }
    $html .= '<td>'.$n['type'].'</td><td class="wrap">'.$n['text'].'</td></tr>';
}

$html.=     '</tbody>
         </table>';

$page->addBody($html);
$page->addFooter($footer);
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display("true");
?>
