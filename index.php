<html>
<head>
    <title> Space Port v0.00 </title>

    <link type="text/css" href="css/hot-sneaks/jquery-ui-1.8.6.custom.css" rel="stylesheet" />
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
    left: 55%;
}

.play-icons {
    width: 24px;
    height: 24px;
    padding: 4px;
    margin: 2px;
    float: left;
}

.play-icons span.ui-icon {
    margin: 4px 4px;
}

#voluprogwrap {
    width: 300px;
    float: left;
    margin: 12px 0px 0px 12px;
}

#playprogwrap {
    width: 80%;
    float: left;
    margin: 12px 0px 0px 12px;
}

#playprogtext {
    margin: 4px;
}

#songlist {
    clear: both;
    width: 50%;
}

h3.ui-accordion-header {
    font-size: 10pt;
}

.float-left {
    float: left;
    clear: both;
    width: 100%;
}

.song-item {
    float: left;
    width: 85%;
    padding: 4px;
    height: 24px;
    margin: 2px;
}

#searchwrap {
    width: 100%;
}

ul {
    position: relative;
}

#searchres {
    clear: left;
}

#searchfield {
    float: left;
}

#searchbutton {
    float: left;
    margin: 4px;
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
    $('#runcomm').click( function() {
        var comm = $('#commander').val();
    });

    assignHover('.play-icons');

<?php
    foreach ($but as $id => $btn) {
        echo "$('#" . $id . "').click( cmd_$id );";
    }
?>

    updateState();

    //setInterval('sendMPD(\'status\');', 1000);

    $('#searchres').selectable();

    $('#searchbutton').click( function() {
        var searching = $('#searchfield').val();

        sendMPD('search', 'artist ' + searching, updateDebug);
    });
});

function assignHover(jqCore) {
    $(jqCore).hover(
        function() { $(this).addClass('ui-state-hover'); },
        function() { $(this).removeClass('ui-state-hover'); }
    );
    
}

/** MPD Buttons **/
function cmd_play(arg) {
    var playArg = '';

    if (arg.toElement.id == undefined) {
        updateDebug("undefined");
    } else {
        var tArg = arg.currentTarget.id.match(/([0-9]*)/);
        if (tArg[1] != undefined && parseInt(tArg[1])) {
            playArg = parseInt(tArg[1]) - 1;
        }
    };

    //updateDebug(playArg);

    sendMPD('play', playArg);
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

function cmd_remove() {

}

/** Misc Functions **/
function updateDebug(data) {
    $('#debuggah').append(data);
}

/** Status Functions **/
function updateState() {
    sendMPD('status', '', updateStatuses); 
}

function parseMPDResults(inputs) {
    var aSplits = inputs.split("\n");
    var statSplitter = /([a-zA-Z]*: )(.*)/;
    var statSpliClean = /[a-zA-Z]*/;

    var currTag;
    var currMatch;

    var currArgs;
    var currResults = [];

    // Get all status information
    var grouper = 0;
    for (var i in aSplits) {
        currMatch = aSplits[i].match(statSplitter);

        if (currMatch == null) {
            continue;
        }

        // Clean the command parse
        currArgs = currMatch[2];
        currTag = currMatch[1].match(statSpliClean);
        currTag = currTag + grouper;
        currTag = currTag.toLowerCase();

        while (currResults[currTag] != undefined) {
            grouper++;
        }

        currResults[currTag] = currArgs;
    }

    return currResults;
}

function updateStatuses(arg) {
    //updateDebug(arg);
    var returners = parseMPDResults(arg);

    var currMatch = '';
    var currArgs = '';

    for (var i in returners) {
        currMatch = 'updateState_' + i.substring(0, i.length - 1);
        currArgs = returners[i];

        if (window[currMatch] != undefined) {
            eval(currMatch + '(currArgs)');
        } else {
            //updateDebug(currMatch + " does not exist.\n");
        }
    }
}

function playlistName(id) {
    return 'pls_' + id;
}

function updatePlaylist(arg) {
    var songInfo = parseMPDResults(arg);

    var playListId = playlistName(songInfo['id0']);
    $('#songlist').append(
              '<div id="'+ playListId + '" '
            + 'class="float-left">' 
            + '<div class="ui-state-default ui-corner-all song-item">'
            + songInfo['artist0'] + ' - ' + songInfo['title0'] 
            + '</div>'
            + '<div>'
<?php
$jsid = '\' + songInfo[\'id0\'] + \'_';

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
        max: 101,
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
    //updateDebug('Playlist Length: ' + args + "\n");
    playListSize = args;
    
    if (playListSize != undefined 
      && (playListUpdate == undefined
       || playListUpdate == true)) {
        $('#songlist').empty();
        for (var i = 0; i < playListSize; i++) {
            sendMPD('playlistinfo', i, updatePlaylist, false);
        }

        var icons = {
            header: "ui-icon-circle-plus",
            headerSelected: "ui-icon-circle-minus"
        };

        playListUpdate = false;
    }
}

function updateState_state(arg) {
    updateIcon(arg, 'play');

    currState = arg;
    //updateDebug('State: ' + arg + "\n");
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

function updateState_song(arg) {
    arg = parseInt(arg) + 1;
    //arg = parseInt(arg);

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
    var progress = parseInt(100 * playtime[0] / playtime[1]);

    //updateDebug(progress);

    var minDisp = toMinutes(playtime);

    $('#playprog').slider({
        min: 0,
        max: parseInt(playtime[1]),
        value: parseInt(playtime[0]),
        stop: function(event, ui) {
            sendMPD('seek', (parseInt(playListCurr) - 1) 
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
function sendMPD(cmd, args, customcallback, asyncOpt) {
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

    $.ajax({
        url: 'mpdws.php',
        type: 'POST',
        data: {
            salt: 'gen',
            command: cmd,
            argument: args
        },
        success: customcallback,
        async: asyncOpt
   });
}
</script>

<div id="play-controls">
<?php

foreach ($but as $id => $btn) {
    echo build_div_button($id, $btn);
}

function build_div_button($id, $btn) {
    return '<div id="' . $id . '" ' 
       . 'class="ui-state-default ui-corner-all play-icons" '
       . 'title=".ui-icon-' . $btn . '">' 
       . '<span class="ui-icon ui-icon-' . $btn . '"></span>'
       . '</div>';
}

?>
    <div id="voluprogwrap">
        <div id="voluprog"></div>
    </div>

    <div id="playprogwrap">
        <div id="playprog"></div>
        <div id="playprogtext">00:00</div>
    </div>

    <div id="searchwrap">
    <input id="searchfield" type="text" size="35" />
    <div id="searchbutton" class="ui-state-default ui-corner-all" title="ui-icon-search">
        <span class="ui-icon ui-icon-search"></span>
    </div>
    <ul id="searchres">
    </ul>
    </div>
</div>

<div id="songlistwrap">
    <div id="songlist"></div>
</div>

<pre id="debuggah"></pre>

</body>

</html>
