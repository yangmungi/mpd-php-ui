<html>
<head>
    <title> Space Port v0.00 </title>
    <link type="text/css" href="css/custom-theme/jquery-ui-1.8.7.custom.css" rel="stylesheet" />
</head>
<body>

<style type="text/css">

/** Global **/
body {
    min-width: 1024px;
}

/** Play Controls **/
#play-controls {
    position: fixed;
    padding: 4px;
    width: 98%;
    height: auto;
    background-color: #FFFFFF;
}

.mod-title {
    text-align: center;
    margin: 2px;
    padding: 0px 8px;
    float: left;
}

.play-icons {
    padding: 4px;
    margin: 2px;
    float: left;
}

#voluprogwrap {
    float: left;
    margin: 8px;
    width: 40px;
}

#playprogwrap {
    width: 400px;
    float: left;
    margin: 8px;
}

#playprogtext {
    margin: 4px;
    float: left;
}

#songlist {
    width: 50%;
}

.song-item {
    float: left;
    width: 85%;
    padding: 2px;
    margin: 2px;
}

#searchwrap {
    width: 100%;
}

#searchres {
    left: 50%;
    width: 50%;
    position: absolute;
}

.search-reit {
    padding: 4px;
    margin: 2px 2px;
    width: 95%;
    float: left;
}

.search-icons {
    float: left;
    padding: 4px;
    margin: 2px 2px;
}

.search-item {

}

#searchfield {
    float: left;
}

.ui-selected {
    background: #DB4865 url(http://localhost/mpd/css/custom-theme/images/ui-bg_glass_40_db4865_1x400.png) repeat-x 50% 50%;
}

.no-display {
    display: none;
}

</style>

<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="jquery.ui.js"></script>
<script type="text/javascript">
<?php

$playbtn = array();
$playbtn['prev']['title'] = 'Previous Song';
$playbtn['prev']['icon']  = 'seek-first';

$playbtn['stop']['title'] = 'Stop';
$playbtn['stop']['icon']  = 'stop';

$playbtn['play']['title'] = 'Play Current Song';
$playbtn['play']['icon']  = 'play';

$playbtn['next']['title'] = 'Next Song';
$playbtn['next']['icon']  = 'seek-end';

$playbtn['refresh']['title'] = 'Quick Refresh';
$playbtn['refresh']['icon']  = 'arrowrefresh-1-w';

$conbtn = array();
$conbtn['play']['icon']  = 'play'; 
$conbtn['play']['title'] = 'Play This Song';

$conbtn['remove']['icon']  = 'circle-close';
$conbtn['remove']['title'] = 'Remove From Playlist';

?>

/** State Variables **/
var playListUpdate;
var playListCurr;

var volPrev;

var currState;

/** Initial Assigns and Event Listeners **/
$(document).ready( function() {
    /** Play Controls **/
<?php
    foreach ($playbtn as $id => $btn) {
        echo "\t$('#" . $id . "').click( cmd_$id );";
    }
?>
    assignHover('.play-icons');

    /** Search Controls **/
    $('#searchbutton').click( function() {
        var searching = $('#searchfield').val();
        sendMPD('search', 'artist ' + searching, searchResponse, 
            true, false);
    });

    $('#addallsongs').click( function () {
        playListAddSongs('.songinfo');
    });

    $('#addsinglesong').click( function () {
        playListAddSongs('.ui-selected + .songinfo');
    });

    $('#deselsearch').click( function () {
        $('.ui-selected').removeClass('ui-selected');
        updateAddButtons();
    });

    assignHover('.search-icons');

    /** Entire Play Controls **/
    $('#play-controls').draggable();
    
    /** Initial Update **/
    updateAddButtons();
    updateState();
    searchResponse('[]');
});

function playListAddSongs(jQCore) {
    $(jQCore).each( function (n, e) {
        sendMPD('addid', $(this).text(), function() { },
            false, true);
    });

    playListUpdate = true;
    updateState();
}

function searchResponse(args) {
    var fadeTime = 300;
    $('#addallsongs').fadeOut();
    $('#searchres').fadeOut(fadeTime, function () {
        $(this).empty();

        if (args == '[]') {
            return;
        }

        var sres = JSON.parse(args);
        var sSong;

        for (var i in sres) {
            sSong = sres[i];

            addSearchResult(sSong, i);
        }

        $('#searchres').selectable({
            filter: '.search-reit',
            stop: updateAddButtons
        });

        $(this).fadeIn(fadeTime, function() {
            $('#addallsongs').fadeIn();
        });
    });
}

function updateAddButtons() {
    var jQR = $('.search-reit.ui-selected');

    var singleBtn = '#addsinglesong, #deselsearch';
    if (jQR.length == 0) {
        if ($(singleBtn).css('display') != 'none') {
            $(singleBtn).fadeOut();
        }
    } else {
        if ($(singleBtn).css('display') == 'none') {
            $(singleBtn).fadeIn();
        }
    }
}

function addSearchResult(song, searchId) {
    var songDisp;

    if (song.artist == undefined || song.title == undefined) {
        songDisp = song.file;
    } else {
        songDisp = song.artist + ' - ' + song.title;
    }

    $('#searchres').append(
              '<div class="search-item">'
            + '<div class="ui-state-default ui-corner-all search-reit">' 
            + songDisp + '</div>'
            + '<div class="no-display songinfo">'
            + song.file
            + '</div>'
            + '</div>'
    );
}

function assignHover(jqCore) {
    $(jqCore).unbind('hover').hover(
        function() { $(this).addClass('ui-state-hover'); },
        function() { $(this).removeClass('ui-state-hover'); }
    );
}

function currentTargetExtract(cti, alt) {
    var playArg = cti;

    if (cti == undefined) {
        updateDebug("undefined");
    } else {
        var tArg = playArg.match(/([0123456789]+)/);

        if (!tArg) {
            playArg = alt;
        } else {
            if (tArg[1] != undefined) {
                playArg = parseInt(tArg[1]);
            }
        }
    };

    return playArg;
}

/** MPD Buttons **/
function cmd_play(arg) {
    var playArg = currentTargetExtract(arg.currentTarget.id, playListCurr);

    sendMPD('playid', playArg);
}

function cmd_stop() {
    sendMPD('stop', '');
}

function cmd_prev() {
    sendMPD('previous', '');
}

function cmd_next() {
    sendMPD('next', '');
}

function cmd_refresh() {
    updateState();
}

function cmd_pause() {
    sendMPD('pause', '1');
}

function cmd_resume() {
    sendMPD('pause', '0');
}

function cmd_remove(arg) {
    var playArg = currentTargetExtract(arg.currentTarget.id, playListCurr);

    playListUpdate = true;
    sendMPD('deleteid', playArg);
}

function cmd_addSong(arg) {
    var ctid = arg.currentTarget.id;
    if (ctid == '' || ctid == 'undefined') {
        updateDebug('null');
        return;
    }

    var playArg = $('#' + ctid + ' .songinfo').text();

    return;

    playListUpdate = true;
    sendMPD('addid', playArg);
}

/** Misc Functions **/
function updateDebug(data) {
    $('#debuggah').append(data + "\n");
}

/** Status Functions **/
function updateState() {
    sendMPD('status', '', updateStatuses); 
}

function parseMPDResults(inputs) {
    var jsonObj = JSON.parse(inputs);

    return jsonObj;
}

function updateStatuses(arg) {
    //updateDebug(arg);
    var returners = parseMPDResults(arg);

    var currMatch = '';
    var currArgs = '';

    for (var i in returners) {
        for (var j in returners[i]) {
            currMatch = 'updateState_' + j;
            currArgs = returners[i][j];

            if (window[currMatch] != undefined) {
                eval(currMatch + '(currArgs)');
            } else {
                //updateDebug(currMatch + " does not exist.\n");
            }
        }
    }
}

function playlistName(id) {
    return 'pls_' + id;
}

function updatePlaylist(arg) {
    var songInfo = parseMPDResults(arg);

    for (var i in songInfo) {
        var playListId = playlistName(parseInt(songInfo[i]['id']));
        $('#songlist').append(
              '<div id="'+ playListId + '" '
            + 'class="float-left">' 
            + '<div class="ui-state-default ui-corner-all song-item">'
            + songInfo[i]['artist'] + ' - ' + songInfo[i]['title'] 
            + '</div>'
            + '<div>'
<?php

$jsid = '\' + playListId + \'_';

foreach ($conbtn as $id => $btn) {
    echo "\t\t\t" . '+ \'' 
        . build_div_button($jsid . $id, $btn['icon'], 
            'play-icons', $btn['title']) 
        . '\'' . "\n";
}

?>
            + '</div>'
        );
<?php

foreach ($conbtn as $id => $btn) {
    $nid = $jsid . $id;
    echo "\t\t$('#$nid').unbind('click').click( cmd_$id );\n";
    echo "\t\tassignHover('#$nid');\n";
}

?>
    }
}

/** State Update Functions **/
function updateState_volume(args) {
    //updateDebug('Volume: ' + args + "\n");
    if (args == -1) {
        if (volPrev != undefined) {
            args = volPrev;
        }
    }

    volPrev = args;

    $('#voluprog').slider({
        orientation: 'horizontal',
        range: 'min',
        min: -1,
        max: 100,
        value: args,
        slide: function(event, ui) {
            sendMPD('setvol', ui.value - 1, false);
        }
    });

    $('#playprog').slider({
        range: 'none'
    });
}

function updateState_random(args) {
    //updateDebug('Random: ' + args + "\n");
}

function updateState_playlist(args) {
    //updateDebug('Playlist: ' + args + "\n");
}

function updateState_playlistlength(args) {
    var playListSize = args;
   
    if (playListSize != undefined 
      && (playListUpdate == undefined
       || playListUpdate == true)) {
        $('#songlist').empty();
        sendMPD('playlistinfo', '0:' + playListSize, updatePlaylist, 
                false);

        playListUpdate = false;
    }
}

function updateState_state(arg) {
    updateIcon(arg, 'play');

    currState = arg;
}

function updateIcon(state, ident) {
    $('#' + ident).unbind('click');

    var bindFunc;
    if (state == 'stop') {
        // Play button
        $('#' + ident + ' .ui-icon-pause')
            .removeClass('ui-icon-pause')
            .addClass('ui-icon-play');
        bindFunc = cmd_play;
    } else if (state == 'pause') {
        $('#' + ident + ' .ui-icon-pause')
            .removeClass('ui-icon-pause')
            .addClass('ui-icon-play');
        bindFunc = cmd_resume;
    } else if (state == 'play') {
        // Pause button
        $('#' + ident + ' .ui-icon-play')
            .removeClass('ui-icon-play')
            .addClass('ui-icon-pause');
        bindFunc = cmd_pause;
    }
    
    $('#' + ident).click(bindFunc);
}

function updateState_songid(arg) {
    arg = parseInt(arg);

    if (playListCurr != undefined) {
        $('#' + playlistName(playListCurr) + ' .song-item')
            .removeClass('ui-state-active').addClass('ui-state-default');
    }

    playListCurr = arg;

    $('#' + playlistName(arg) + ' .song-item')
        .removeClass('ui-state-default').addClass('ui-state-active');
}

function updateState_time(arg) {
    var playtime = arg.split(':');
    var minDisp = toMinutes(playtime);

    $('#playprog').slider({
        min: 0,
        max: parseInt(playtime[1]),
        value: parseInt(playtime[0]),
        stop: function(event, ui) {
            sendMPD('seekid', parseInt(playListCurr) 
                + ' ' + ui.value);
        },
        change: function(event, ui) {
            $('#playprogtext').text(minDisp[0] + ' / ' + minDisp[1]);
        }
    });
}

function toMinutes(splits) {
    var rem = parseInt(splits[1]);
    var remMin = parseInt(rem / 60);
    var remSec = rem % 60;
    if (remSec < 10) {
        remSec = '0' + remSec;
    }

    var cur = parseInt(splits[0]);
    var curMin = parseInt(cur / 60);
    var curSec = cur % 60;
    if (curSec < 10) {
        curSec = '0' + curSec;
    }

    return [curMin + ':' + curSec,
            remMin + ':' + remSec];
}

/** Background Helper Services **/
function sendMPD(cmd, args, customcallback, asyncOpt, forceQuote) {
    if (cmd == undefined) {
        updateDebug("No command was given.");
    }

    if (args == undefined) {
        args = '';
    }

    if (customcallback == undefined) {
        customcallback = updateState;
    }

    if (asyncOpt == undefined) {
        asyncOpt = true;
    }

    if (forceQuote == undefined) {
        forceQuote = false;
    }

    $.ajax({
        url: 'mpdws.php',
        type: 'POST',
        data: {
            salt: 'gen',
            command: cmd,
            argument: args,
            force: forceQuote
        },
        success: customcallback,
        async: asyncOpt
   });
}
</script>

<div id="searchres"></div>
<div id="songlist"></div>

<div id="play-controls" class="ui-widget ui-widget-content ui-corner-all">
<!--
    <div class="ui-widget-header ui-corner-all mod-title">
        Player Controls
    </div>
-->

<?php

foreach ($playbtn as $id => $btn) {
    echo "\n" . build_div_button($id, $btn['icon'], 
            'play-icons', $btn['title']) . "\n";
}

?>
    <div id="voluprogwrap">
        <div id="voluprog"></div>
    </div>

    <div id="playprogwrap">
        <div id="playprog"></div>
    </div>
    <div id="playprogtext">0:00 / 0:00</div>

    <div id="searchwrap">
        <input id="searchfield" type="text" size="25" />
<?php

$searchbtn = array();

$searchbtn['searchbutton']['title'] = 'Search';
$searchbtn['searchbutton']['icon']  = 'search';

$searchbtn['addallsongs']['title'] = 'Add All Songs';
$searchbtn['addallsongs']['icon']  = 'circle-plus';

$searchbtn['addsinglesong']['title'] = 'Add Selected Song(s)';
$searchbtn['addsinglesong']['icon']  = 'plus';

$searchbtn['deselsearch']['title'] = 'Deselect Songs';
$searchbtn['deselsearch']['icon']  = 'close';

foreach ($searchbtn as $id => $btn) {
    echo build_div_button($id, $btn['icon'], 'search-icons', $btn['title'])
        . "\n";
}

?>

</div>

<pre id="debuggah"></pre>

</body>

<?php

function build_div_button($id, $icon, $class_str, $title) {
    return '<div id="' . $id . '" ' 
       . 'class="ui-state-default ui-corner-all '
       . $class_str . '" ' 
       . 'title="' . $title . '">' 
       . '<span class="ui-icon ui-icon-' . $icon . '"></span>'
       . '</div>';
}

?>

</html>
