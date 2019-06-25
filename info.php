<?php
$start_time = microtime(true);
require_once('auth.php');
require_once('config.php');
require_once('loadclasses.php');

if (session_status() != PHP_SESSION_ACTIVE) {
  header('Location: '.URL::url_path.'index.php');
  die();
}

if (!isset($_SESSION['isRecruiter']) || !$_SESSION['isRecruiter']) {
  header('Location: '.URL::url_path().'index.php');
  die();
}

if (!isset($_SESSION['ajtoken'])) {
    $_SESSION['ajtoken'] = EVEHELPERS::random_str(32);
}

$type = false;
foreach (array('char', 'corp', 'alli') as $t) {
  if (null != URL::getQ($t)) {
    $type = $t;
    $id = URL::getQ($t);
    break;
  }
}

if (!$type || $type=='undefined') {
    $page = new Page('Info');
    $page->setError('No valid ID supplied.');
    $page->display();
}

$page = new Page();
$page->getCached();

if ($type == 'char') {
    if (DBH::getApplication($id) || DBH::getAltOf($id)) {
        header('Location: '.URL::url_path().'application.php?char_id='.$id);
    }  
    $esipilot = new ESIPILOT($id, false, true);
    $page->setTitle($esipilot->getCharacterName());
    if ($esipilot->getError()) {
        $page->setError($esipilot->getMessage());
    }
    $page->addBody(character($esipilot));
}

if ($type == 'corp') {
    $page->addBody(corporation($id, $page));
}

$page->addFooter('<script src="js/clickable.php"></script><script src="js/bootstrap-contextmenu.js"></script><script src="js/bootstrap-dialog.min.js"></script><link href="css/bootstrap-dialog.min.css" rel="stylesheet">');
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display(true);
exit;



function character($esipilot) {
    $corpid = $esipilot->getCorpID();
    $allyid = EVEHELPERS::getAllyForCorp($corpid);
    $html = '<h6>Pilot</h6>
    <div class="row">
      <div class="col-sm-2 col-xs-3">
          <img class="img img-rounded" src="https://imageserver.eveonline.com/Character/'.$esipilot->getCharacterID().'_64.jpg">'.
          ($corpid?'<img class="img img-rounded" src="https://imageserver.eveonline.com/Corporation/'.$corpid.'_64.png">':'').
          ($allyid?'<img class="img img-rounded" src="https://imageserver.eveonline.com/Alliance/'.$allyid.'_64.png">':'').'
      </div>
      <div class="col-md-5 col-xs-6">
      <div class="row"><div class="col-sm-5">Name:</div><div class="col-sm-7">'.$esipilot->getCharacterName().'</div></div>
      <div class="row"><div class="col-sm-5">Birthday:</div><div class="col-sm-7">'.date('Y/m/d', strtotime($esipilot->getBorn())).'</div></div>
      <div class="row"><div class="col-sm-5">Current Corp:</div><div class="col-sm-7">'.($corpid?'<span class="evecorp" eveid="'.$corpid.'">'.EVEHELPERS::getCorpInfo($corpid)->corporation_name.'</span>':'<em>Error</em>').'</div></div>
      <div class="row"><div class="col-sm-5">Alliance:</div><div class="col-sm-7">'.($allyid?EVEHELPERS::getAllyInfo($allyid)->alliance_name:'<em>None</em>').'</div></div>
      </div>
    </div><div class="col-xs-12" style="height: 20px"></div>';
    
    $html .= '<h6>Links</h6>
    <div class="row">
      <div class="col-lg-4 col-md-5 col-xs-8">
          <div class="col-xs-6"><a href="https://zkillboard.com/character/'.$esipilot->getCharacterID().'/" target="_blank">ZKillboard</a></div>
          <div class="col-xs-6"><a href="https://evewho.com/pilot/'.$esipilot->getCharacterName().'" target="_blank">EVE Who</a></div>
          <div class="col-xs-6"><a href="https://gate.eveonline.com/Profile/'.$esipilot->getCharacterName().'" target="_blank">EVE Gate</a></div>
          <div class="col-xs-6"><a href="http://eve-hunt.net/hunt/'.$esipilot->getCharacterName().'" target="_blank">EVE Hunt</a></div>
      </div>
    </div><div class="col-xs-12" style="height: 20px"></div>';
    
    $corphist = $esipilot->getCorpHistory();
    $html .= '<h6>Corp history</h6>
    <div class="row"><div class="col-xs-12 col-md-8 col-lg-6">
      <table id="corptable" class="small table table-striped table-condensed table-hover" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th>Join Date</th>
            <th></th>
            <th>Corporation</th>
          </tr>
        </thead>
        <tbody>';
    foreach ($corphist as $corp) {
        $html.= '<tr><td>'.date('Y/m/d', strtotime($corp['joined'])).'</td>';
        $html.= '<td style="text-align: right;"><img height="24px" src="https://imageserver.eveonline.com/Corporation/'.$corp['id'].'_32.png"></td>';
        $html.= '<td>'.(isset($corp['name'])?'<span class="evecorp" eveid="'.$corp['id'].'">'.$corp['name'].'</span>':'Unknown').'</td></tr>';
    }
    $html .= '</tbody></table></div></div>';
    return $html;
}

function corporation($corpid, $page) {
    $corpinfo = EVEHELPERS::getCorpInfo($corpid);
    $allyid = EVEHELPERS::getAllyForCorp($corpid);
    $ceo = new ESIPILOT($corpinfo->ceo_id, false, true);
    $html = '<h6>Corporation</h6>
    <div class="row">
      <div class="col-sm-2 col-xs-3">
          <img class="img img-rounded" src="https://imageserver.eveonline.com/Corporation/'.$corpid.'_64.png">'.
          ($allyid?'<img class="img img-rounded" src="https://imageserver.eveonline.com/Alliance/'.$allyid.'_64.png">':'').'
      </div>
      <div class="col-md-5 col-xs-6">
      <div class="row"><div class="col-sm-5">Name:</div><div class="col-sm-7">'.$corpinfo->corporation_name.'</div></div>
      <div class="row"><div class="col-sm-5">CEO:</div><div class="col-sm-7"><span class="evechar" eveid="'.$corpinfo->ceo_id.'">'.$ceo->getCharacterName().'<span></div></div>
      <div class="row"><div class="col-sm-5">Member count:</div><div class="col-sm-7">'.$corpinfo->member_count.'</div></div>
      <div class="row"><div class="col-sm-5">Alliance:</div><div class="col-sm-7">'.($allyid?EVEHELPERS::getAllyInfo($allyid)->alliance_name:'<em>None</em>').'</div></div>
      </div>
    </div><div class="col-xs-12" style="height: 20px"></div>';

    $html .= '<h6>Links</h6>
    <div class="row">
      <div class="col-lg-4 col-md-5 col-xs-8">
          <div class="col-xs-6"><a href="https://zkillboard.com/corporation/'.$corpid.'/" target="_blank">ZKillboard</a></div>
          <div class="col-xs-6"><a href="https://evewho.com/corp/'.$corpinfo->corporation_name.'" target="_blank">EVE Who</a></div>
          <div class="col-xs-6"><a href="https://gate.eveonline.com/Corporation/'.$corpinfo->corporation_name.'" target="_blank">EVE Gate</a></div>
          <div class="col-xs-6"><a href="http://eve-hunt.net/hunt/'.$corpinfo->corporation_name.'" target="_blank">EVE Hunt</a></div>
      </div>
    </div><div class="col-xs-12" style="height: 20px"></div>';

    $allyhist = EVEHELPERS::getAllyHistory($corpid);
    $html .= '<h6>Corp history</h6>
    <div class="row"><div class="col-xs-12 col-md-8 col-lg-6">
      <table id="corptable" class="small table table-striped table-condensed table-hover" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th>Join Date</th>
            <th></th>
            <th>Corporation</th>
          </tr>
        </thead>
        <tbody>';
    foreach ($allyhist as $ally) {
        $html.= '<tr><td>'.date('Y/m/d', strtotime($ally['joined'])).'</td>';
        $html.= '<td style="text-align: right;">'.(isset($ally['id'])?'<img height="24px" src="https://imageserver.eveonline.com/Alliance/'.$ally['id'].'_32.png">':'').'</td>';
        $html.= '<td>'.(isset($ally['name'])?$ally['name']:'').'</td></tr>';
    }
    $html .= '</tbody></table></div></div>';

    return $html;

}
?>
