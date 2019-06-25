<?php header("Content-type: application/javascript"); ?>

var redflags = [];

$(document).ready(function() {
    $.ajax({
        type: "POST",
        url: "ajax/aj_getredflags.php",
        success:function(data) {
            try {
                redflags = JSON.parse(data);
            } catch (e) {
                redflags = [];
            }
            markup();
        }
    });
});

function markup() {
    $('.evechar').each(function() {
        var id = $(this).attr('eveid');
        if (undefined !== redflags[id]) {
            $(this).css('cursor', 'pointer').css('color', '#cc0000').attr('title',  redflags[id]['reason']+'\n'+redflags[id]['time']+' by '+redflags[id]['byname']+'\n\nLeft-click to open details,\nright-click for more options.');
        } else {
            $(this).css('cursor', 'pointer').attr('title', 'Left-click to open details,\nright-click for more options.');
        }
    });
    $('.evechar').on('click', null, function() {
            var id = $(this).attr('eveid');
            var path = $(location).attr('pathname').split('/');
            if (path[path.length -1] != 'info.php') {
                window.open('info.php?char='+id);
            } else {
                window.location.href = 'info.php?char='+id;
            }
    });

    $('.evecorp').each(function() {
        var id = $(this).attr('eveid');
        if (undefined !== redflags[id]) {
            $(this).css('cursor', 'pointer').css('color', '#cc0000').attr('title',  redflags[id]['reason']+'\n'+redflags[id]['time']+' by '+redflags[id]['byname']+'\n\nLeft-click to open details,\nright-click for more options.');
        } else {
            $(this).css('cursor', 'pointer').attr('title', 'Left-click to open details,\nright-click for more options.');
        }
    });
    $('.evecorp').on('click', null, function() {
            var id = $(this).attr('eveid');
            var path = $(location).attr('pathname').split('/');
            if (path[path.length -1] != 'info.php') {
                window.open('info.php?corp='+id);
            } else {
                window.location.href = 'info.php?corp='+id;
            }
    });
    $('.evealli').each(function() {
        var id = $(this).attr('eveid');
        if (undefined !== redflags[id]) {
            $(this).css('cursor', 'pointer').css('color', '#cc0000').attr('title',  redflags[id]['reason']+'\n'+redflags[id]['time']+' by '+redflags[id]['byname']+'\n\nLeft-click to open details,\nright-click for more options.');
        } else {
            $(this).css('cursor', 'pointer').attr('title', 'Left-click to open details,\nright-click for more options.');
        }
    });
    $('.evealli').on('click', null, function() {
            var id = $(this).attr('eveid');
            var path = $(location).attr('pathname').split('/');
            if (path[path.length -1] != 'info.php') {
                window.open('info.php?alli='+id);
            } else {
                window.location.href = 'info.php?alli='+id;
            }
    });
    
    $('<div id="context-menu"><ul class="dropdown-menu" role="menu"><li><a tabindex="-1" action="mark"><span class="glyphicon glyphicon-warning-sign"></span>&nbsp;Mark red flagged</a></li><li><a tabindex="-1" action="unmark"><span class="glyphicon glyphicon-remove"></span>&nbsp;Unmark</a></li></ul></div>').appendTo(document.body);
}

$(function () {
    $('span[class^="eve"]').contextmenu({
        target: '#context-menu',
        onItem: function (context, e) {
            var action = $(e.target).closest('a').attr('action');
            var name = context.text();
            var id = context.attr('eveid');
            if (action == 'mark') {
                redflag(id, name);
            } else if (action == 'unmark') {
                unflag(id);
            }
        }
    });
});

function redflag(id, name) {
        var id = id;
        BootstrapDialog.show({
            title: 'Red flagging '+name,
            message: '<textarea id="reason'+id+'" class="form-control" placeholder="Please enter a reason."></textarea>',
            draggable: true,
            closable: true,
            cssClass: 'small-dialog',
            buttons: [{
                id: 'btn-cancel',   
                label: 'Cancel',
                autospin: false,
                action: function(dialogRef){    
                    dialogRef.close();
                }
            }, {
                id: 'btn-ok',
                label: 'OK',
                cssClass: 'btn-primary',
                autospin: false,
                action: function(result) {
                    console.log('<?php print_r($_SESSION); ?>')
                    var reason = result.getModalBody().find('textarea').val();
                    $.ajax({
                        type: "POST",
                        url: "ajax/aj_addredflag.php",
                        data: {"id" : id, "reason" : reason },
                        success:function(data) {
                            result.close();
                            if (data !== "true") {
                                BootstrapDialog.show({message: "Uhoh, that didn't work", type: BootstrapDialog.TYPE_WARNING});
                            } else {
                                location.reload();
                            }
                        }
                    });
                }
            }],
        });
}

function unflag(id) {
    var id = id;
    $.ajax({
        type: "POST",
        url: "ajax/aj_removeredflag.php",
        data: {"id" : id},
        success:function(data) {
            if (data !== "true") {
                BootstrapDialog.show({message: "Uhoh, that didn't work", type: BootstrapDialog.TYPE_WARNING});
            } else {
                location.reload();
            }
        }
    });
}
