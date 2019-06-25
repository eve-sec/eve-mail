<?php

$start_time = microtime(true);
require_once('auth.php');
require_once('config.php');
require_once('loadclasses.php');
require_once('serverstatus.php');

function calPage() {
    $today_d = (int)gmdate('d');
    $today_m = (int)gmdate('m');
    $today_y = (int)gmdate('Y');
    $cal = '';
    if (null == URL::getQ('m') || null == URL::getQ('y')) {
        $m = gmdate('m');
        $y = gmdate('Y');
    } else {
        $m = URL::getQ('m');
        $y = URL::getQ('y');
    }
    $cal .= '<div id="caldiv" class="row"><div class="col-xs-12 col-sm-5"><h4>'.date('F Y', strtotime($y.'/'.$m.'/01')).'</h4></div><div class="col-xs-12 col-lg-6 col-sm-6 text-right" id="loadingdiv"></div></div>';
    $w = (int)gmdate('W', strtotime($y.'/'.$m.'/01'));
    $dto = new DateTime();
    if ($m == 1 && $w > 50) {
        $dto->setISODate($y-1, $w);
    } else {
        $dto->setISODate($y, $w);
    }
    $next = false;
    do {
        $i = 0;
        $cal .= '<div class="row cal-row">';
        do {
            if ((int)$dto->format('d') == $today_d && (int)$dto->format('m') == $today_m && (int)$dto->format('Y') == $today_y) {
                $style = 'panel-primary';
            } else if ($i > 4) {
                $style = 'panel-success';
            } else {
                $style = 'panel-default';
            }
            $cal .= '<div class="panel small '.$style.' cal-cell" id="'.$dto->format('Y-m-d').'"'.($dto->format('m') != $m?'style=" opacity: 0.4" ':'').'>
                         <div class="panel-heading"><span class="hidden-xs">'.$dto->format('D').'&nbsp;</span>' .$dto->format('j').'</div>
                         <div class="panel-body tiny">';
                $cal .= '</div>
                     </div>';
            $dto->modify('+1 days');
            $i++;
        } while ($i < 7);
        $cal .= '</div>';
        if (((int)$dto->format('m') > $m && (int)$dto->format('Y') == $y) || (int)$dto->format('Y') > $y) {
            $next = true;
        }
    } while (!$next);
    $cal .= '<div class="row"><div class="pull-right">
        <ul class="pager col-xs-12">
            <li><a href="#" onclick="pageBack();"><span class="glyphicon glyphicon-chevron-left"></span>Previous month</a></li>
            <li><a href="#" onclick="pageFwd();">Next month<span class="glyphicon glyphicon-chevron-right"></span></a></li>
        </ul>
    </div></div>';
    return $cal;
}

function getScripts($esicalendar) {
    $scripts = '<script>
        var dialog;
        function viewevent(link) {
            var id = $(link).attr("id");
            var subject = $(link).attr("title");
            dialog = new BootstrapDialog(
                {message: "Fetching event...</br><center><i class=\"fa fa-spinner fa-pulse fa-3x fa-fw\"></i></center>",
                title: subject,
                buttons: [{
                    label: "Close",
                    action: function(dialogRef) {
                        dialogRef.close();
                    }
                }],});
            dialog.open();
            $.get("readevent.php?eid="+id+"&cid='.$esicalendar->getCharacterID().'", function(data, status){
                dialog.setMessage(data);
            });
        }
        function rsvp(id, response) {
            $.ajax({
                url: "readevent.php?eid="+id+"&cid='.$esicalendar->getCharacterID().'&rsvp="+response,
                success: function(data) {
                    dialog.setMessage(data);
                }
            });
        }
        function getFutureEvents(firstrun) {
            if (firstrun) {
                $("#loadingdiv").append("<i class=\"fa fa-spinner fa-pulse fa-2x fa-fw\"></i>");
                $("#loadingdiv").append("<span id=\"loadingtext\"> Loading upcoming events...</span>");
            } 
            $.ajax({
                url: "fetchevents.php?lastid="+lastid,
                success: function(data) {
                    json = JSON.parse(data);
                    if (firstrun && json.data.length) {
                        firstid = json.firstid;
                        timenow = Date.parse(json.data[0].event_date);
                    }
                    if (json.lastid > lastid) {
                        lastid = json.lastid;
                        events.push.apply(events, json.data);
                        parsevents(json.data);
                        getFutureEvents(false);
                    } else {
                        $("#loadingtext").text("Fetching past events, this may take a while");
                        getPastEvents(0);
                    }
                }
            });
        }
        function getPastEvents(fromid) {
            $.ajax({
                url: "fetchevents.php?lastid="+fromid,
                success: function(data) {
                    json = JSON.parse(data);
                    if (json.lastid != 0) {
                        if (Date.parse(json.data[json.data.length-1].event_date) > timenow) {
                            json.data.forEach(function(element) {
                                if (Date.parse(element.event_date) < timenow) {
                                    events.push(element);
                                }
                            });
                            events.sort(function(a,b) {return (a.event_id > b.event_id) ? 1 : ((b.event_id > a.event_id) ? -1 : 0);} );
                            parsevents(events);
                            $("#loadingdiv").fadeOut();
                        } else {
                            events.push.apply(events, json.data);
                            parsevents(json.data);
                            getPastEvents(json.lastid);
                        }
                    } else {
                        parsevents(events);
                        $("#loadingdiv").fadeOut();
                    }
                }
            });
        }
        function parsevents(events) {
            for (var i = 0, len = events.length; i < len; i++) {
                parseevent(events[i]);
            }
        }
        function parseevent(event) {
            evdate = event.event_date.substr(0,10);
            if (document.documentElement.outerHTML.indexOf(evdate) != -1) {
                div = $("#"+evdate);
                iddiv = $("#"+event.event_id);
                if (div.length && !iddiv.length) {
                    html = "<a href=\'#\' id=\'"+event.event_id+"\'  onclick=\'viewevent(this);\' class=\'cal-event\' title=\'"+event.title+"\'>";
                                 if (event.event_response == "accepted") {
                                     html += "<span class=\'glyphicon glyphicon-ok-sign text-success\' title=\'"+event.event_response+"\'>&nbsp;<\/span>";
                                 } else if (event.event_response == "declined") {
                                     html += "<span class=\'glyphicon glyphicon-remove-sign text-danger\' title=\'"+event.event_response+"\'>&nbsp;<\/span>";
                                 } else if (event.event_response == "tentative") {
                                     html += "<span class=\'glyphicon glyphicon-question-sign text-primary\' title=\'"+event.event_response+"\'>&nbsp;<\/span>";
                                 } else {
                                     html += "<span class=\'glyphicon glyphicon-stop\' title=\'"+event.event_response+"\'>&nbsp;<\/span>";
                                 }
                                 if (event.importance) {
                                     html +="<span class=\'text-danger\'><b>!<\/b>&nbsp;<\/span>";
                                 }
                                 html += event.event_date.substr(11, 5)+"<span class=\'hidden-xs\'> "+event.title+"<\/span><\/a><br \/>";
                    div.find(".panel-body").append(html);
                }
            }
        }
        function getUrlVars()
        {
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf("?") + 1).split("&");
            var page = window.location.href.slice(0, window.location.href.indexOf("?"));
            vars["page"] = page;
            for(var i = 0; i < hashes.length; i++)
            {
                hash = hashes[i].split("=");
                vars.push(hash[0]);
                vars[hash[0]] = hash[1];
            }
            return vars;
        }
        function pageFwd() {
            m = parseInt(getUrlVars()["m"]);
            y = parseInt(getUrlVars()["y"]);
            if (m >= 12) {
                m = 1;
                y += 1;
            } else {
                m += 1;
            }
            window.history.replaceState({}, "", getUrlVars()["page"]+"?y="+y+"&m="+m);
            $.ajax({
                url: getUrlVars()["page"]+"?y="+y+"&m="+m+"&calonly=true",
                success: function(data) {
                    if ($("#loadingdiv").is(":visible")) {
                        temp = $("#loadingdiv").html();
                        $("#caldiv").parent().html(data);
                        $("#loadingdiv").html(temp);
                    } else {
                        $("#caldiv").parent().html(data);
                    }
                    parsevents(events);
                }
            });
        }
        function pageBack() {
            m = parseInt(getUrlVars()["m"]);
            y = parseInt(getUrlVars()["y"]);
            if (m <= 1) {
                m = 12;
                y -= 1;
            } else {
                m -= 1;
            }
            window.history.replaceState({}, "", getUrlVars()["page"]+"?y="+y+"&m="+m);
            $.ajax({
                url: getUrlVars()["page"]+"?y="+y+"&m="+m+"&calonly=true",
                success: function(data) {
                    if ($("#loadingdiv").is(":visible")) {
                        temp = $("#loadingdiv").html();
                        $("#caldiv").parent().html(data);
                        $("#loadingdiv").html(temp);
                    } else {
                        $("#caldiv").parent().html(data);
                    }
                    parsevents(events);
                }
            });
        }
    </script>';
    return $scripts;
}

if (null == URL::getQ('m') || null == URL::getQ('y')) {
    $m = gmdate('m');
    $y = gmdate('Y');
    header('location: '.URL::full_url_noq().'?y='.$y.'&m='.$m);
} elseif (null != URL::getQ('calonly')) {
    echo calPage();
    exit;
   
}

$esicalendar = new ESICALENDAR($_SESSION['characterID']);

$scopesOK = $esicalendar->checkScopes(['esi-calendar.read_calendar_events.v1','esi-calendar.respond_calendar_events.v1']);
if (!$scopesOK) {
    $scopes = array_unique(array_merge($esicalendar->getDbScopes(), ['esi-calendar.read_calendar_events.v1','esi-calendar.respond_calendar_events.v1']));
    $url = URL::url_path().'login.php?scopes='.implode(' ',$scopes)."&page=".rawurlencode(URL::relative_url());
    header('Location: '.$url);
}


$footer = '<script>
          var events = [];
          var lastid = -1;
          var firstid;
          var timenow;
          $(document).ready(function() {
              getFutureEvents(true);
          });
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="js/bootstrap-contextmenu.js"></script>
    <script src="js/bootstrap-dialog.min.js"></script>
    <link href="css/bootstrap-dialog.min.css" rel="stylesheet">';

$page = new Page($esicalendar->getCharacterName().'\'s calendar');

$page->addBody(calPage());
$page->addBody(getScripts($esicalendar));
$page->addFooter($footer);
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display("true");
?>
