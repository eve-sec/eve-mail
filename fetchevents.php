<?php

require_once('auth.php');
require_once('config.php');
require_once('loadclasses.php');

$cachetime = 30;

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

if (isset($_SESSION['characterID'])) {
  $esical = new ESICALENDAR($_SESSION['characterID']);
  $scopesOK = $esical->getAccessToken('esi-calendar.read_calendar_events.v1');
}

$data = array('data' => array(), 'firstid' => 0, 'lastid' => 0);

if (!isset($_SESSION['characterID']) || !$scopesOK) {
  echo(json_encode($data));
  exit;
}

$cachefile = 'cache/events/'.$_SESSION['characterID'].'_'.preg_replace('/[^A-Za-z0-9\-]/', '', URL::getQueryString()).'.json';
if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile)) {
    $response = file_get_contents($cachefile);
    echo $response;
    die();
}

$events = $esical->getEvents(URL::getQ('lastid'), 1);

$data['firstid'] = 0;
$data['lastid'] = 0;
foreach ((array)$events as $event) {
    $data['data'][] = $event;
    if ($event["event_id"] > $data['lastid']) {
        $data['lastid'] = $event["event_id"];
    }
    if ($data['firstid'] == 0) {
        $data['firstid'] = $event["event_id"];
    }
}

$response = json_encode($data);
if (count($data['data'])) {
    file_put_contents($cachefile, $response, LOCK_EX);
}

echo($response);

?>
