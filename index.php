<html>
<head>
    <title> Space Port v0.00 </title>

    <link type="text/css" href="css/dark-hive/jquery-ui-1.8.7.custom.css" rel="stylesheet" />
</head>
<body>

<?php

$but = array();
$but['prev'] = 'seek-first';
$but['stop'] = 'stop';
$but['play'] = 'play';
$but['next'] = 'seek-end';
$but['refresh'] = 'arrowrefresh-1-w';

$conbtn = array();
$conbut['play'] = $but['play'];
$conbut['remove'] = 'circle-close';

?>

<style type="text/css">
body {
    min-width: 1024px;
}

#play-controls {
    position: fixed;
    padding: 4px;
    left: 50%;
    width: 49%;
    height: auto;
    background-color: #FFFFFF;
}

.play-icons {
    width: 24px;
    height: 24px;
    padding: 2px;
    margin: 2px;
    float: left;
}

.play-icons span.ui-icon {
    margin: 4px 4px;
}

#voluprogwrap {
    float: left;
    width: 68%;
    margin: 8px 0px 0px 12px;
}

#playprogwrap {
    width: 96%;
    float: left;
    clear: left;
    margin: 12px 0px 0px 12px;
}

#playprogtext {
    margin: 4px;
}

#songlist {
    width: 50%;
}

h3.ui-accordion-header {
    font-size: 10pt;
}

.song-item {
    float: left;
    width: 85%;
    padding: 4px;
    margin: 2px;
}

#searchwrap {
    width: 100%;
}

ul {
    position: relative;
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
    clear: left;
    float: left;
}

.ui-selected {
    background: #FFFF38;
}

.no-display {
    display: none;
}

.mod-title {
    text-align: center;
    padding: 8px;
    margin: auto;
    width: 90%;

}

</style>

<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="jquery.ui.js"></script>
<script type="text/javascript">

/** State Variables **/
var playListSize;
var playListUpdate;
var playListCurr;

var volPrev;

var currState;

/** Initial Assigns and Event Listeners **/
$(document).ready( function() {
    /** Start play controls **/
    assignHover('.play-icons');

<?php
    foreach ($but as $id => $btn) {
        echo "$('#" . $id . "').click( cmd_$id );";
    }
?>

    /** Quick update **/
    updateState();

    /** Search features **/
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
    
    /** AGAIN **/
    updateAddButtons();
    updateState();
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
    if (args !== '[]') {
        $('#searchres').fadeOut(1000, function () {
            $(this).empty();

            if (args == '') {
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

            $(this).fadeIn();
        });
    }
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
//            + '<div id="' + searchId + '_searchres" '
//            + 'class="ui-state-default ui-corner-all search-icons">'
//            + '<span class="ui-icon ui-icon-circle-plus">'
//            + '</span>' 
//            + '</div>'
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

    var playListId = playlistName(parseInt(songInfo[0]['id']));
    $('#songlist').append(
              '<div id="'+ playListId + '" '
            + 'class="float-left">' 
            + '<div class="ui-state-default ui-corner-all song-item">'
            + songInfo[0]['artist'] + ' - ' + songInfo[0]['title'] 
            + '</div>'
            + '<div>'
<?php

$jsid = '\' + playListId + \'_';

foreach ($conbut as $id => $btn) {
    echo '+ \'' . build_div_button($jsid . $id, $btn) . '\'' . "\n";
}

?>
            + '</div>'
    );

<?php

foreach ($conbut as $id => $btn) {
    $nid = $jsid . $id;
    echo "$('#$nid').unbind('click').click( cmd_$id );\n";
    echo "assignHover('#$nid');\n";
}

?>

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
    playListSize = args;
    
    if (playListSize != undefined 
      && (playListUpdate == undefined
       || playListUpdate == true)) {
        $('#songlist').empty();
        for (var i = 0; i < playListSize; i++) {
            sendMPD('playlistinfo', i, updatePlaylist, false);
        }

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

<div id="play-controls" class="ui-widget ui-corner-all">

<div class="ui-widget-header ui-corner-all mod-title">
    Player Controls
</div>
<?php

foreach ($but as $id => $btn) {
    echo build_div_button($id, $btn);
}

function build_div_button($id, $btn) {
    return '<div id="' . $id . '" ' 
       . 'class="ui-state-default ui-corner-all play-icons" '
       . 'title="' . ucwords($btn) . '">' 
       . '<span class="ui-icon ui-icon-' . $btn . '"></span>'
       . '</div>';
}

?>
    <div id="voluprogwrap">
        <div id="voluprog"></div>
    </div>

    <div id="playprogwrap">
        <div id="playprog"></div>
        <div id="playprogtext">Calculating</div>
    </div>

    <div id="searchwrap">
        <input id="searchfield" type="text" size="35" />
        <div id="searchbutton" class="ui-state-default ui-corner-all search-icons" title="Search">
            <span class="ui-icon ui-icon-search"></span>
        </div>
        <div id="addallsongs" class="ui-state-default ui-corner-all search-icons" title="Add All Songs">
            <span class="ui-icon ui-icon-circle-plus"></span>
        </div>
        <div id="addsinglesong" class="ui-state-default ui-corner-all search-icons" title="Add Selected Song">
            <span class="ui-icon ui-icon-plus"></span>
        </div>
        <div id="deselsearch" class="ui-state-default ui-corner-all search-icons" title="Deselect Songs">
            <span class="ui-icon ui-icon-close"></span>
        </div>
    </div>
</div>

<pre id="debuggah"></pre>

</body>

</html>
