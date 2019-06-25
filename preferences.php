<?php
$start_time = microtime(true);
require_once('auth.php');
require_once('config.php');
require_once('loadclasses.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  header('Location: '.URL::url_path.'index.php');
  die();
}

if (isset($_POST["submit"])) {
  $path = URL::path_only();
  $server = URL::server();
  if (!isset($_POST["reload"])) {
      $_POST["reload"] = 0;
  }
  if (!isset($_POST["notify"])) {
      $_POST["notify"] = 0;
  }
  setcookie(COOKIE_ID.'style', $_POST["style"], strtotime("now")+3600*24*365, $path, $server, 0);
  $_SESSION['style'] = $_POST["style"];
  setcookie(COOKIE_ID.'reload', $_POST["reload"], strtotime("now")+3600*24*365, $path, $server, 0);
  $_SESSION['reload'] = $_POST["reload"];
  setcookie(COOKIE_ID.'notify', $_POST["notify"], strtotime("now")+3600*24*365, $path, $server, 0);
  $_SESSION['notify'] = $_POST["notify"];

}

if (isset($_SESSION["style"])) {
    $style = $_SESSION["style"];
} elseif (isset($_COOKIE[COOKIE_ID."style"])) {
    $style = $_COOKIE[COOKIE_ID."style"];
    $_SESSION["style"] = $style;
} else {
    $style = "dark";
}

if (isset($_SESSION["reload"])) {
    $reload = $_SESSION["reload"];
} elseif (isset($_COOKIE[COOKIE_ID."reload"])) {
    $reload = $_COOKIE[COOKIE_ID."reload"];
    $_SESSION["reload"] = $reload;
} else {
    $reload = False;
}

if (isset($_SESSION["notify"])) {
    $notify = $_SESSION["notify"];
} elseif (isset($_COOKIE[COOKIE_ID."notify"])) {
    $notify = $_COOKIE[COOKIE_ID."notify"];
    $_SESSION["notify"] = $notify;
} else {
    $notify = False;
}


$html = '<div class="col-xs-12">
           <form id="prefs" role="form" action="" method="post">
             <div class="form-group col-xs-12">
               <label for="style" class="control-label">Please select your preferred site Layout:</label>
               <div class="radio">
                 <label><input type="radio" name="style" value="dark" '.($style == "dark"?'checked ':'').'>Dark</label>
               </div>
               <div class="radio">
                 <label><input type="radio" name="style" value="light" '.($style == "light"?'checked ':'').'>Light</label>
               </div>
               <div class="radio">
                 <label><input type="radio" name="style" value="colors" '.($style == "colors"?'checked ':'').'>I really like colors...</label>
               </div>
             </div>
             <div class="form-group col-xs-12">
                 <label class="checkbox-inline"><input type="checkbox" name="reload" value="1" '.($reload?'checked ':'').'>Auto reload mails</label>
             </div>
             <div class="form-group col-xs-12">
                 <label class="checkbox-inline"><input type="checkbox" name="notify" value="1" '.($notify?'checked ':'').'>Recieve Desktop notifications</label>
             </div>
             <div class="form-group col-xs-12">
                 <button type="submit" id="submit" class="btn btn-primary" value="submit" name="submit">Submit</button>
             </div>
        </form></div>';

$page = new Page('My Preferences');

$page->addBody($html);
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display();
exit;
?>
