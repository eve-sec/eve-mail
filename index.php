<?php

$start_time = microtime(true);
require_once('auth.php');
require_once('config.php');
require_once('loadclasses.php');
require_once('serverstatus.php');

function getMailBoxes($esimail) {
    $labels = $esimail->getMailLabels();
    $table = '<ul id="boxlinks" class="nav nav-pills nav-stacked">
                   <li class="spacer hidden-xs"><em>Mail boxes:</em></li>';
        if (null == URL::getQ('label')) {
            if($labels && isset($labels[1])) {
                $l = array_keys($labels)[1];
            } else {
                $l = null;
            }
        } else {
            $l = URL::getQ('label');
        }
        $ml = URL::getQ('mlist');
        foreach ((array)$labels as $k => $label) {
            $table .= '<li'.($l==$k && null == $ml?' class="active"':'').'><a style="padding: 7px 7px;" href="index.php?p=mail&label='.$k.'" onclick="return loading()">'.str_replace(array("]", "["), "", $label['name']).($label['unread']?'<span class="badge badge-unread">'.$label['unread'].'</span>':'').'</a></li>';
        }
    $mlists =  $esimail->getMailingLists();
    if (count($mlists)) {
        $table .= '<li class="spacer hidden-xs" style="margin-top: 20px;"><em>Mailing lists:</em></li>';
        foreach ($mlists as $id => $mlist) {
            $table .= '<li'.($l==0 && $ml == $id?' class="active"':'').'><a style="padding: 7px 7px;" href="index.php?p=mail&label=0&mlist='.$id.'" onclick="return loading()">'.$mlist.'</a></li>';
        }
    }
    $table .= '</ul>';
    return $table;
}

function mailsPage($esimail) {
    $table = '<div class="row"><div class="col-sm-12 col-md-3 col-lg-2" id="mailboxes">';
    $labels = $esimail->getMailLabels();
    $table .= '<ul id="boxlinks" class="nav nav-pills nav-stacked">
                   <li class="spacer hidden-xs"><em>Mail boxes:</em></li>';
        if (null == URL::getQ('label')) {
            if($labels && isset($labels[1])) {
                $l = array_keys($labels)[1];
            } else {
                $l = null;
            }
        } else {
            $l = URL::getQ('label');
        }
        $ml = URL::getQ('mlist');
        foreach ((array)$labels as $k => $label) {
            $table .= '<li'.($l==$k && null == $ml?' class="active"':'').'><a style="padding: 7px 7px;" href="index.php?p=mail&label='.$k.'" onclick="return loading()">'.str_replace(array("]", "["), "", $label['name']).($label['unread']?'<span class="badge badge-unread">'.$label['unread'].'</span>':'').'</a></li>';
        }
    $mlists =  $esimail->getMailingLists();
    if ($mlists && count($mlists)) {
        $table .= '<li class="spacer hidden-xs" style="margin-top: 20px;"><em>Mailing lists:</em></li>';
        foreach ($mlists as $id => $mlist) {
            $table .= '<li'.($l==0 && $ml == $id?' class="active"':'').'><a style="padding: 7px 7px;" href="index.php?p=mail&label=0&mlist='.$id.'" onclick="return loading()">'.$mlist.'</a></li>';
        }
    } 
    $table .= '</ul>';
    $table .= '</div><div class="col-sm-12 hidden-md hidden-lg" style="height: 20px"></div>
    <div class="col-sm-12 col-md-9 col-lg-10">
    <table id="mailstable" class="jdatatable table responsive table-striped table-hover" cellspacing="0" width="100%">
      <thead>
          <th class="all">Time</th>';
          if ($l == 2) {
            $table .= '<th>To:</th>
            <th>Subject</th>
            <th class="num no-sort min-mobile-l"></th>';
          } else {
            $table .= '<th class="all no-sort"></th>
            <th class="num no-sort min-tablet-l"></th>
            <th>From:</th>
            <th class="subj-col">Subject</th>
            <th class="min-tablet-l to-col">To:</th>
            <th class="num no-sort min-mobile-l"></th>';
          }
          $table .= '<th class="cb"></th>';
      $table .= '</thead></table></div></div>
      <script>
          var label = '.$l.';
          var mlist = '.($ml == null?'null':$ml).';
          function readmail(link, isread) {
              var id = $(link).attr("id");
              if ($(link).parent().hasClass("ellipsis")) {
                  var subject = $(link).parent().attr("title");
              } else {
                  var subject = $(link).text();
              }
              var dialog = new BootstrapDialog(
                  {message: "Fetching mail...</br><center><i class=\"fa fa-spinner fa-pulse fa-3x fa-fw\"></i></center>",
                  title: subject,
                  buttons: [{
                      label: "Close",
                      action: function(dialogRef) {
                          dialogRef.close();
                      }
                  }],});
              dialog.open();
              $.get("readmail.php?mid="+id+"&cid='.$esimail->getCharacterID().'&read="+isread, function(data, status){
                  dialog.setMessage(data);
              });
              isreadcol = $(link.closest("tr")).find("i").first();
              if (isreadcol.hasClass("fa-envelope-o")) {
                  isreadcol.removeClass("fa-envelope-o");
                  isreadcol.addClass("fa-envelope-open-o");
                  box = $("#boxlinks").children(".active");
                  badge = $(box.find(".badge-unread"));
                  if (badge.length) {
                      if (badge.text() == "1") {
                          badge.remove();
                      } else {
                          badge.text(parseInt(badge.text()) - 1);
                      }
                  }
                  allbox = $("#boxlinks").children("li:contains(\'All\')").first();
                  if (!allbox.hasClass("active")) {
                      allbadge = $(allbox.find(".badge-unread"));
                      if (allbadge.length) {
                          if (allbadge.text() == "1") {
                              allbadge.remove();
                          } else {
                              allbadge.text(parseInt(allbadge.text()) - 1);
                          }
                      }
                  }
              }
          }
      </script>
      ';
      return $table;
}

$footer = '<script>
          var lastid;
          var mtable;
          var pages;
          var newest;';
if (isset($_SESSION["reload"])) {
    $reload = $_SESSION["reload"];
} elseif (isset($_COOKIE[COOKIE_ID."reload"])) {
    $reload = $_COOKIE[COOKIE_ID."reload"];
    $_SESSION["reload"] = $reload;
} else {
    $reload = false;
}
if ($reload) {
    $footer .= '          var reload = true;';
} else {
    $footer .= '          var reload = false;';
}
if (isset($_SESSION["notify"])) {
    $notify = $_SESSION["notify"];
} elseif (isset($_COOKIE[COOKIE_ID."notify"])) {
    $notify = $_COOKIE[COOKIE_ID."notify"];
    $_SESSION["notify"] = $notify;
} else {
    $notify = false;
}
if ($notify) {
    $footer .= '          var notify = true;';
} else {
    $footer .= '          var notify = false;';
}

$footer .= '          function getmore() {
              $.ajax({
                  url: "fetchmails.php?label="+label+"&lastid="+lastid+"&pages="+pages+mlstring,
                  success: function(data) {
                      json = JSON.parse(data);
                      mtable.rows.add(json.data).draw();
                      if (json.lastid < lastid) {
                          lastid = json.lastid;
                          if (json.lastid != 0) {
                              getmore();
                          }
                      }
                  }
              });
          }
          $(document).ready(function() {
            if (label == 2) {
                var columns = [{ "data": "date" },{ "data": "to" },{ "data": "subject" }, {"data" : null,"defaultContent": "<a href=\"#\" title=\"Forward mail\" onclick=\"fwdrow(this)\"><i class=\"fa fa-share\" aria-hidden=\"true\"><\/i><\/a>&nbsp;<a href=\"#\" class=\"faa-parent animated-hover\" title=\"Delete mail\" onclick=\"deleterow(this)\"><i class=\"fa fa-trash faa-shake\" aria-hidden=\"true\"><\/i><\/a>", "width": "32px"}, {"defaultContent":""}]
            } else {
                var columns =  [{ "data": "date" },{ "data": "isread" },{ "data": "img" },{ "data": "from" },{ "data": "subject", "class" : "subj-col" },{ "data": "to", "class" : "to-col" },{ "data": null,"defaultContent": "<a href=\"#\" title=\"Reply to\" onclick=\"replyrow(this)\"><i class=\"fa fa-reply\" aria-hidden=\"true\"><\/i><\/a>&nbsp;<a href=\"#\" title=\"Forward mail\" onclick=\"fwdrow(this)\"><i class=\"fa fa-share\" aria-hidden=\"true\"><\/i><\/a>&nbsp;<a href=\"#\" class=\"faa-parent animated-hover\" title=\"Delete mail\" onclick=\"deleterow(this)\"><i class=\"fa fa-trash faa-shake\" aria-hidden=\"true\"><\/i><\/a>", "width": "50px"}, {"defaultContent":""}]
            }
            if (mlist != undefined) {
                pages = 10
                mlstring = "&mlist="+mlist;
            } else {
                pages = 1
                mlstring = "";
            }
            mtable = $(".jdatatable").DataTable(
               {
                   "dom": "<\'row\'<\'col-sm-6\'l><\'col-sm-6\'f>>" +
                   "<\'row\'<\'col-sm-12\'tr>>" +
                   "<\'row\'<\'col-sm-5\'i><\'hidden-xs pull-right col-sm-7\'B>>" +
                   "<<\'col-sm-12\'p>>", 
                   "ajax": {
                       "url": "fetchmails.php?label="+label+"&pages="+pages+mlstring,
                       "dataSrc": function ( json ) {
                           lastid = json.lastid;
                           if (label == 0) {
                               newest = json.firstid;
                           } else if (reload) {
                               $.ajax({
                                   url: "fetchmails.php?label=0",
                                   success: function(data) {
                                       newest = JSON.parse(data).firstid;
                                   }
                               });
                           }
                           getmore();
                           return json.data;
                       },
                  },
                  buttons: [
                   {
                      text: "<i class=\'fa fa-trash\' aria-hidden=\'true\'></i>",
                      className: "btn btn-primary btn-xs",
                      action: function () {
                          selected = mtable.rows(".selected").data();
                          if (selected.length) {
                             ids = [];
                             for (var i = 0; i < selected.length; i++) {
                                 ids.push(parseInt($(selected[i].subject).attr("id")));
                             }
                             massdelete(ids);
                          }
                      }
                   }, {
                      text: "<i class=\'fa fa-envelope-open-o\' aria-hidden=\'true\'></i>",
                      className: "btn btn-primary btn-xs",
                      action: function () {
                          selected = mtable.rows(".selected").data();
                          if (selected.length) {
                             ids = [];
                             for (var i = 0; i < selected.length; i++) {
                                 ids.push(parseInt($(selected[i].subject).attr("id")));
                             }
                             massread(ids);
                          }
                      }
                   }, {
                      text: "<i class=\'fa fa-envelope-o\' aria-hidden=\'true\'></i>",
                      className: "btn btn-primary btn-xs",
                      action: function () {
                          selected = mtable.rows(".selected").data();
                          if (selected.length) {
                             ids = [];
                             for (var i = 0; i < selected.length; i++) {
                                 ids.push(parseInt($(selected[i].subject).attr("id")));
                             }
                             massunread(ids);
                          }
                      }
                   }, {
                      text: "<i class=\'fa fa-check-square-o\' aria-hidden=\'true\'></i>",
                      className: "m-left btn btn-primary btn-xs",
                      action: function () {
                          mtable.rows({page:"current"}).select();
                      }
                  }, {
                      text: "<i class=\'fa fa-square-o\' aria-hidden=\'true\'></i>",
                      className: "btn btn-primary btn-xs",
                      action: function () {
                          mtable.rows().deselect();
                      }
                   }],

                   "columns": columns,
                   "bPaginate": true,
                   "pageLength": 25,
                   "aoColumnDefs" : [ {
                       "bSortable" : false,
                       "aTargets" : [ "no-sort" ]
                   }, {
                       "sClass" : "num-col",
                       "aTargets" : [ "num" ]
                   }, {
                       orderable: false,
                       className: "select-checkbox hidden-xs",
                       "aTargets" : [ "cb" ]
                   }, {
                       "render" : $.fn.dataTable.render.ellipsis( 45, true),
                       "aTargets" : [ "to-col", "subj-col" ]
                   } ], 
                   "order": [[ 0, "desc" ]],
                   fixedHeader: {
                       header: true,
                       footer: true
                   },
                   responsive: {details: false},
                   select: {
                       style:    "multi",
                       selector: "td:last-child"
                   },
               });
               $(mtable.buttons(0).container()).addClass("pull-right");
               $(mtable.buttons(0).nodes()).attr("title", "Delete selected");
               $(mtable.buttons(1).nodes()).attr("title", "Mark selected read");
               $(mtable.buttons(2).nodes()).attr("title", "Mark selected unread");
               $(mtable.buttons(3).nodes()).attr("title", "Select all");
               $(mtable.buttons(4).nodes()).attr("title", "Select none");
          });
         function massunread(ids) {
             var sum = 0;
             var todo = ids.length
             $(ids).each(function() {
                 var id = this;
                 $.ajax({
                     type: "POST",
                     url: "'.URL::url_path().'ajax/aj_mailunread.php",
                     data: {"ajtok" : "'.$_SESSION['ajtoken'].'", "id" : id},
                     success:function(data) {
                         if (data !== "true") {
                             BootstrapDialog.show({message: "Something went wrong..."+data, type: BootstrapDialog.TYPE_WARNING});
                         } else {
                             isreadcol = $( $("#"+id).closest("tr")).find("i").first();
                             if (isreadcol.hasClass("fa-envelope-open-o")) {
                                 isreadcol.removeClass("fa-envelope-open-o");
                                 isreadcol.addClass("fa-envelope-o");
                                 box = $("#boxlinks").children(".active");
                                 badge = $(box.find(".badge-unread"));
                                 if (badge.length == 0) {
                                     box.html(box.html().substr(0, box.html().length-4)+"<span class=\"badge badge-unread\">1</span></a>");
                                 } else {
                                     badge.text(parseInt(badge.text()) + 1);
                                 }
                                 allbox = $("#boxlinks").children("li:contains(\'All\')").first();
                                 if (!allbox.hasClass("active")) {
                                     allbadge = $(allbox.find(".badge-unread"));
                                     if (allbadge.length == 0) {
                                         allbox.html(allbox.html().substr(0, allbox.html().length-4)+"<span class=\"badge badge-unread\">1</span></a>");
                                     } else {
                                         allbadge.text(parseInt(allbadge.text()) + 1);
                                     }
                                 }
                             }
                             mtable.rows().deselect()
                         }
                     }
                 });
             });
         }

         function massread(ids) {
             var sum = 0;
             var todo = ids.length
             $(ids).each(function() {
                 var id = this;
                 $.ajax({
                     type: "POST",
                     url: "'.URL::url_path().'ajax/aj_mailread.php",
                     data: {"ajtok" : "'.$_SESSION['ajtoken'].'", "id" : id},
                     success:function(data) {
                         if (data !== "true") {
                             BootstrapDialog.show({message: "Something went wrong..."+data, type: BootstrapDialog.TYPE_WARNING});
                         } else {
                             isreadcol = $( $("#"+id).closest("tr")).find("i").first();
                             if (isreadcol.hasClass("fa-envelope-o")) {
                                 isreadcol.removeClass("fa-envelope-o");
                                 isreadcol.addClass("fa-envelope-open-o");
                                 box = $("#boxlinks").children(".active");
                                 badge = $(box.find(".badge-unread"));
                                 if (badge.length) {
                                     if (badge.text() == "1") {
                                         badge.remove();
                                     } else {
                                         badge.text(parseInt(badge.text()) - 1);
                                     }
                                 }
                                 allbox = $("#boxlinks").children("li:contains(\'All\')").first();
                                 if (!allbox.hasClass("active")) {
                                     allbadge = $(allbox.find(".badge-unread"));
                                     if (allbadge.length) {
                                         if (allbadge.text() == "1") {
                                             allbadge.remove();
                                         } else {
                                             allbadge.text(parseInt(allbadge.text()) - 1);
                                         }
                                     }
                                 }
                             }
                             mtable.rows().deselect()
                         }
                     }
                 });
             });
         }

         function massdelete(ids) {
             BootstrapDialog.show({
                  message: "You are about to delete "+ids.length+" mails, are you really sure?",
                  type: BootstrapDialog.TYPE_WARNING,
                  buttons: [{
                      label: "Delete mail",
                      action: function(dialogItself){
                          dialogItself.close();
                          var sum = 0;
                          var todo = ids.length
                          $(ids).each(function() {
                              var id = this;
                              $.ajax({
                                  type: "POST",
                                  url: "'.URL::url_path().'ajax/aj_deletemail.php",
                                  data: {"ajtok" : "'.$_SESSION['ajtoken'].'", "id" : id},
                                  success:function(data) {
                                      if (data !== "true") {
                                          BootstrapDialog.show({message: "Something went wrong...", type: BootstrapDialog.TYPE_WARNING});
                                      } else {
                                          isreadcol = $( $("#"+id).closest("tr")).find("i").first();
                                          if (isreadcol.hasClass("fa-envelope-o")) {
                                              box = $("#boxlinks").children(".active");
                                              badge = $(box.find(".badge-unread"));
                                              if (badge.length) {
                                                  if (badge.text() == "1") {
                                                      badge.remove();
                                                  } else {
                                                      badge.text(parseInt(badge.text()) - 1);
                                                  }
                                              }
                                              allbox = $("#boxlinks").children("li:contains(\'All\')").first();
                                              if (!allbox.hasClass("active")) {
                                                  allbadge = $(allbox.find(".badge-unread"));
                                                  if (allbadge.length) {
                                                      if (allbadge.text() == "1") {
                                                          allbadge.remove();
                                                      } else {
                                                          allbadge.text(parseInt(allbadge.text()) - 1);
                                                      }
                                                  }
                                              }
                                          }
                                          var trow = $("#"+id).closest("tr");
                                          mtable.row(trow).remove().draw(false);
                                      }
                                  }
                              });
                          });
             
                      }
                  },{
                      label: "Cancel",
                      action: function(dialogItself){
                          dialogItself.close();
                      }
                  }],
             });

         }

         function deletemail(id) {
             BootstrapDialog.show({
                  message: "Are you sure you want to delete this mail?",
                  type: BootstrapDialog.TYPE_WARNING,
                  buttons: [{
                      label: "Delete mail",
                      action: function(dialogItself){
                          dialogItself.close();
                          $.ajax({
                              type: "POST",
                              url: "'.URL::url_path().'ajax/aj_deletemail.php",
                              data: {"ajtok" : "'.$_SESSION['ajtoken'].'", "id" : id},
                              success:function(data) {
                                  if (data !== "true") {
                                      BootstrapDialog.show({message: "Something went wrong..."+data, type: BootstrapDialog.TYPE_WARNING});
                                  } else {
                                      isreadcol = $( $("#"+id).closest("tr")).find("i").first();
                                      if (isreadcol.hasClass("fa-envelope-o")) {
                                          box = $("#boxlinks").children(".active");
                                          badge = $(box.find(".badge-unread"));
                                          if (badge.length) {
                                              if (badge.text() == "1") {
                                                  badge.remove();
                                              } else {
                                                  badge.text(parseInt(badge.text()) - 1);
                                              }
                                          }
                                          allbox = $("#boxlinks").children("li:contains(\'All\')").first();
                                          if (!allbox.hasClass("active")) {
                                              allbadge = $(allbox.find(".badge-unread"));
                                              if (allbadge.length) {
                                                  if (allbadge.text() == "1") {
                                                      allbadge.remove();
                                                  } else {
                                                      allbadge.text(parseInt(allbadge.text()) - 1);
                                                  }
                                              }
                                          }
                                      }
                                      var trow = $("#"+id).closest("tr");
                                      mtable.row(trow).remove().draw(false);
                                      BootstrapDialog.closeAll();
                                  }
                              }
                          });
                      }
                  },{
                      label: "Cancel",
                      action: function(dialogItself){
                          dialogItself.close();
                      }
                  }],
             });
         }
         function showfit(btn, dna) {
              name = $(btn).text();
              var dialog = new BootstrapDialog(
                  {message: "Parsing Fit...</br><center><i class=\"fa fa-spinner fa-pulse fa-3x fa-fw\"></i></center>",
                  title: name,
                  draggable: true,
                  buttons: [{
                      label: "Close",
                      action: function(dialogRef) {
                          dialogRef.close();
                      }
                  }],});
              dialog.open();
              $.get("fitting.php?dna="+dna, function(data, status){
                  dialog.setMessage(data);
              });
         }
         function fwdrow(btn) {
             var trow = $(btn).closest("tr");
             var id = trow.find("a").first().attr("id");
             window.location = "compose.php?action=fwd&mid="+id;
         }
         function deleterow(btn) {
             var trow = $(btn).closest("tr");
             var id = trow.find("a").first().attr("id");
             deletemail(id);
         }
         function replyrow(btn) {
             var trow = $(btn).closest("tr");
             var id = trow.find("a").first().attr("id");
             window.location = "compose.php?action=re&mid="+id;
         }
         function doReload() {
             if (newest) {
                 $.ajax({
                     url: "fetchmails.php?label=0",
                     success: function(data) {
                         json2 = JSON.parse(data);
                         new_newest = json2.firstid;
                         unread = json2.unread;
                         if (new_newest > newest) {
                             newest = new_newest;
                             if (notify) {
                                 if (Notification.permission !== "granted")
                                     Notification.requestPermission();
                                 else {
                                     var notification = new Notification("You got EVE mail!", {
                                         icon: "https://spacemail.xyz/img/spacemail.png",
                                         body: "You got mail. Currently you have "+unread+" unread mail"+((unread > 1) ? "s." :"."),
                                     });
                                 }
                             }
                             if (reload) {
                                 mtable.ajax.reload()
                                 $.ajax({
                                     url: "index.php?p=mail&label="+label+"&mailboxes_only=1",
                                     success: function(data) {
                                         $("#mailboxes").html(data);
                                     }
                                 });
                             }
                         }
                     }
                 });
                 if (reload || notify) {
                     setTimeout(doReload, 180000);
                 }
             }
         }
         if (reload || notify) {
             setTimeout(doReload, 180000);    
         }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.2.3/css/select.dataTables.min.css">
    <link rel="stylesheet" href="css/dt-custom.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/css/dataTables.bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.datatables.net/responsive/2.1.1/css/responsive.bootstrap.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.13/js/dataTables.bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.1.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.1.1/js/responsive.bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.2.3/js/dataTables.select.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.4.2/js/dataTables.buttons.min.js"></script>
    <script src="js/ellipsis.js"></script>
    <script src="js/typeahead.bundle.min.js"></script>
    <script src="js/esi_autocomplete.js"></script>
    <script src="js/bootstrap-contextmenu.js"></script>
    <script src="js/bootstrap-dialog.min.js"></script>
    <link href="css/bootstrap-dialog.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome-animation/0.0.10/font-awesome-animation.min.css" integrity="sha256-C4J6NW3obn7eEgdECI2D1pMBTve41JFWQs0UTboJSTg=" crossorigin="anonymous" />';

$esimail = new ESIMAIL($_SESSION['characterID']);
$scopesOK = $esimail->checkScopes(['esi-mail.read_mail.v1', 'esi-mail.organize_mail.v1']);
if (!$scopesOK) {
    $scopes = array_unique(array_merge($esimail->getDbScopes(), ['esi-mail.read_mail.v1', 'esi-mail.organize_mail.v1']));
    $url = URL::url_path().'login.php?scopes='.implode(' ',$scopes)."&page=".rawurlencode(URL::relative_url());
    header('Location: '.$url);
}

if (true == URL::getQ('mailboxes_only')) {
    echo getMailBoxes($esimail);
    die;
}

$page = new Page($esimail->getCharacterName().'\'s mailbox');

$page->addBody(mailsPage($esimail));
if ($esimail->getError()) {
    $esistatus = new ESISTATUS();
    if (!$esistatus->getServerStatus()) {
        $page->setError('Failed to get the server status, maybe it\'s downtime?');
    } else {
        $page->setError($esimail->getMessage());
    }
}
$page->addFooter($footer);
$page->setBuildTime(number_format(microtime(true) - $start_time, 3));
$page->display("true");
?>
