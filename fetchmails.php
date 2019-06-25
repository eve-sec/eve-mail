<?php

require_once('auth.php');
require_once('config.php');
require_once('loadclasses.php');

$cachetime = 30;

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

if (isset($_SESSION['characterID'])) {
  $esimail = new ESIMAIL($_SESSION['characterID']);
  $scopesOK = $esimail->getAccessToken('esi-mail.read_mail.v1');
}

$data = array('data' => array(), 'lastid' => 0);

if (!isset($_SESSION['characterID']) || !$scopesOK) {
  echo(json_encode($data));
  exit;
}

$deleted = array();
$qry = DB::getConnection();
$sql = "SELECT mailID FROM deleted_mails WHERE deleted >= '".date("Y-m-d H:i:s", time()-60)."' AND characterID=".$_SESSION['characterID'];
$result = $qry->query($sql);
while ($row = $result->fetch_assoc()) {
    $deleted[] = $row['mailID'];
}

$cachefile = 'cache/mails/'.$_SESSION['characterID'].'_'.preg_replace('/[^A-Za-z0-9\-]/', '', URL::getQueryString()).'.json';
if (file_exists($cachefile) && time() - $cachetime < filemtime($cachefile) && !count($deleted)) {
    $response = file_get_contents($cachefile);
    echo $response;
    die();
}

$labels = $esimail->getMailLabels();
$labels['0'] = array('name' => 'others', 'unread' => 0);

if (null == URL::getQ('label')) {
    if($labels) {
        $l = array_keys($labels)[0];
    } else {
        $l = null;
    }
} else {
    $l = URL::getQ('label');
}

if ($l == 'none' || $l == [0] || $l == 0 || $l == '0') {
    $mails = $esimail->getMails(array(''), URL::getQ('lastid'), URL::getQ('pages'), URL::getQ('mlist'));
} else {
    $mails = $esimail->getMails(array($l), URL::getQ('lastid'), URL::getQ('pages'), URL::getQ('mlist'));
}

$data['firstid'] = 0;
$data['unread'] = 0;
foreach ((array)$mails as $mail) {
    if (in_array($mail['mail_id'], $deleted)) {
        continue;
    }
    $temp = array();
    $temp['date'] = gmdate('y/m/d H:i', strtotime($mail['timestamp']));
    $temp['isread'] = '<i class="fa fa-envelope'.($mail['is_read']?'-open':'').'-o" aria-hidden="true"></i>';
    ($mail['is_read']?'':$data['unread']+=1);
    $temp['img'] = '<img class="img-rounded" height="28px" src="https://imageserver.eveonline.com/Character/'.$mail['from'].'_32.jpg">';
    $temp['from'] = '<span class="evechar" eveid="'.$mail['from'].'">'.$mail['from_name'].'<span>';
    $recarray = array();
    foreach ($mail['recipients'] as $r) {
        $recarray[] =  '<span class="eve'.strtolower(substr($r['recipient_type'], 0, 4)).'" eveid="'.$r['recipient_id'].'">'.$r['recipient_name'].'</span>';
    }
    $temp['to'] = implode(', ', $recarray);
    $temp['subject'] = '<a href="#" id="'.$mail['mail_id'].'" onclick="readmail(this, '.($mail['is_read']?'1':'0').'); return false;">'.$mail['subject'].'</a>';
    $data['data'][] = $temp;
    $data['lastid'] = $mail['mail_id'];
    (($mail['mail_id'] > $data['firstid'] && $mail['from'] != $_SESSION['characterID'])? $data['firstid'] = $mail['mail_id']:'');
}

$response = json_encode($data);
if (count($data['data'])) {
    file_put_contents($cachefile, $response, LOCK_EX);
}

echo($response);

?>
