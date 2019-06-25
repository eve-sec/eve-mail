<?php

require_once('config.php');
require_once('loadclasses.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    if (function_exists('random_int')) {
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
    } else {
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[rand(0, $max)];
        }
    }
    return $str;
}
if (isset($_GET['code'])) {
  $code = $_GET['code'];
  $state = $_GET['state'];
  if ($state != $_SESSION['authstate']) {
    $page = new Page('SSO Login');
    $html = "Error: Invalid state, aborting.";
    session_destroy();
    $page->setError($html);
    $page->display();
    exit;
  }
  $esisso = new ESISSO();
  $esisso->setCode($code);
  if (!$esisso->getError()) {
    $result = $esisso->addToDb();
    if ($result) {
        $page = new Page('SSO Login');
        $_SESSION['characterID'] = $esisso->getCharacterID();
        $_SESSION['characterName'] = $esisso->getCharacterName();
        if (isset($_SESSION['persistent_login']) && $_SESSION['persistent_login']) {
            $authtoken = new AUTHTOKEN(null, $_SESSION['characterID']);
            $authtoken->addToDb();
            $authtoken->storeCookie();
        }
        include_once('auth.php');
        $page = new Page('SSO Login');
        if (isset($_GET['page'])) {
            $fwd = $_GET['page'];
        } else {
            $fwd = 'index.php';
        }
        $page->addHeader('<meta http-equiv="refresh" content="1;url='.URL::url_path().$fwd.'">');
        $page->setInfo($esisso->getMessage());
        $page->display();
        exit;
    }
  } else {
    $page = new Page('SSO Login');
    $page->setError($esisso->getMessage());
    $page->display();
    exit;
  }
}

if (isset($_GET['persistent_login'])) {
  $_SESSION['persistent_login'] = true;
} else {
  $_SESSION['persistent_login'] = false;
}

$authurl = "https://login.eveonline.com/v2/oauth/authorize/";
$state = random_str(32);
if (isset($_GET['scopes'])) {
    $currenturl = rtrim(preg_replace('/(?<=\?|\&)(scopes=[a-zA-Z%0-9.\-\_]*[\&{0-1}])/i', '', URL::full_url()), '?');
    $url = $authurl."?response_type=code&redirect_uri=".rawurlencode($currenturl)."&client_id=".ESI_ID."&scope=".urlencode($_GET['scopes'])."&state=".urlencode($state); 
} else {
    $scopes = unserialize(MINIMAL_SCOPES);
    $url = $authurl."?response_type=code&redirect_uri=".rawurlencode(URL::full_url())."&client_id=".ESI_ID."&scope=".urlencode(implode(' ',$scopes))."&state=".urlencode($state);
}
$_SESSION['authstate'] = $state;
header('Location: '.$url);
exit;
?>
