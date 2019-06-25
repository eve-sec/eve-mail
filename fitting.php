<?php
$start_time = microtime(true);
require_once('config.php');
require_once('loadclasses.php');
require_once('auth.php');

$html = '';

if (isset($_GET['dna'])) {
    $fit = new FITTING($_GET['dna']);
} else {
    $fit = Null;
}

$html .= '<div class="row">';

if ($fit) {
  $names = $fit->getNames();
  $fitting = $fit->getFitArray();
  $html .= '<link rel="stylesheet" href="css/fitting.css" type="text/css" />';
  $html .= '<div class="fitting col-xs-12">
  <ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#fit">Fitting</a></li>
    <li><a data-toggle="tab" href="#export">Text based</a></li>
  </ul>
  <div class="tab-content">
  <div id="fit" class="tab-pane fade in active">
  <h5>'.$names[$fitting['ship']].'</h5>
  <div class="fitting-container">
  <img class="fitting-render" src=https://imageserver.eveonline.com/Render/'.$fitting['ship'].'_256.png>
  <img class="fitting-overlay" src=img/fittingbase_256.png>';
  foreach ($fitting['highs'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-high-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  foreach ($fitting['meds'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-med-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  foreach ($fitting['lows'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-low-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  foreach ($fitting['rigs'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-rig-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  foreach ($fitting['subsys'] as $i => $mod) {
    $html .= '<img class="fit-mod fit-sub-'.$i.'" src="https://imageserver.eveonline.com/Type/'.$mod.'_32.png" title="'.$names[$mod].'">';
  }
  $drones = array_count_values($fitting['drones']);
  $i = 0;
  $html .= '<img class="fitting-drones" src="img/fitting_drones.png">';
  foreach ($drones as $drone => $qty) {
    $html .= '<img class="fit-drone" src="https://imageserver.eveonline.com/Type/'.$drone.'_32.png" title="'.$names[$drone].' x'.$qty.'" style="top: '.(45 + 28*$i).'px;">';
    $i++;
  }
  $charges = array_count_values($fitting['charges']);
  $i = 0;
  $html .= '<img class="fitting-charges" src="img/fitting_charges.png">';
  foreach ($charges as $charge => $qty) {
    $html .= '<img class="fit-charge" src="https://imageserver.eveonline.com/Type/'.$charge.'_32.png" title="'.$names[$charge].' x'.$qty.'" style="top: '.(45 + 28*$i).'px;">';
    $i++;
  }
  $html .= '</div></div>
  <div id="export" class="tab-pane fade">
  <blockquote class="small">
  ['.$names[$fitting['ship']].' fit]<br/>';
  foreach ($fitting['lows'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['meds'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['highs'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['rigs'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($fitting['subsys'] as $mod) {
    $html .= $names[$mod].'<br/>';
  }
  $html .= '<br/>';
  foreach ($drones as $drone => $qty) {
    $html .= $names[$drone].' x'.$qty.'<br/>';
  }
  $html .= '<br/>';
  foreach ($charges as $charge => $qty) {
    $html .= $names[$charge].' x'.$qty.'<br/>';
  }
  $html .= '</blockquote></div></div></div></div>';
}
$html = str_replace("\n", "", $html);
echo $html;
exit;
?>
