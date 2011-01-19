<html>
<head>
    <title> Spaceport Level 703 </title>
    <link type="text/css" href="css/custom-theme/jquery-ui-1.8.7.custom.css" rel="stylesheet" />
    <link type="text/css" href="mpd-spacedock.css" rel="stylesheet" />
</head>
<body>
<?php

$playbtn = array();
$sephelp = 0;

$playbtn['prev']['title'] = 'Previous Song';
$playbtn['prev']['icon']  = 'seek-first';

$playbtn['stop']['title'] = 'Stop';
$playbtn['stop']['icon']  = 'stop';

$playbtn['play']['title'] = 'Play Current Song';
$playbtn['play']['icon']  = 'play';

$playbtn['next']['title'] = 'Next Song';
$playbtn['next']['icon']  = 'seek-end';

$sepstr = 'sep' . $sephelp;
$playbtn[$sepstr]['title'] = '';
$playbtn[$sepstr]['icon']  = 'grip-solid-vertical';
$playbtn[$sepstr]['nullvoid'] = TRUE;
$sephelp++;

$playbtn['psclear']['title'] = 'Clear Playlist';
$playbtn['psclear']['icon']  = 'trash';

$sepstr = 'sep' . $sephelp;
$playbtn[$sepstr]['title'] = '';
$playbtn[$sepstr]['icon']  = 'grip-solid-vertical';
$playbtn[$sepstr]['nullvoid'] = TRUE;
$sephelp++;

$playbtn['plmode']['title'] = 'Play Until End of this Song';
$playbtn['plmode']['icon']  = 'arrowstop-1-e';

$sepstr = 'sep' . $sephelp;
$playbtn[$sepstr]['title'] = '';
$playbtn[$sepstr]['icon']  = 'grip-solid-vertical';
$playbtn[$sepstr]['nullvoid'] = TRUE;
$sephelp++;

$conbtn = array();
$conbtn['play']['icon']  = 'play'; 
$conbtn['play']['title'] = 'Play This Song';

$conbtn['remove']['icon']  = 'circle-close';
$conbtn['remove']['title'] = 'Remove From Playlist';

?>
<script type="text/javascript" src="jquery.js"></script>
<script type="text/javascript" src="jquery.ui.js"></script>
<script type="text/javascript">

/** State Variables **/
// Mark to update the playlist
var playListUpdate;
// The currently playing song
var playListCurr;

// When MPD is stopped, volume is reported as -1, so use this
// to keep track of what the volume used to be
var volPrev;

// The play state of MPD
var currState;

// MPD repeat mode on
var currRepeat;
// MPD single mode on
var currSingle;

// Maintains a list of all search results over the search-all-fields calls
var searchCache;

/** Initial Assigns and Event Listeners **/
$(document).ready( function() {
    /** Play Controls **/
<?php

foreach ($playbtn as $id => $btn) {
    if (isset($btn['nullvoid'])) {
        continue;
    }

    echo "    $('#" . $id . "').click( cmd_$id );\n";
}

?>
    assignHover('.play-icons');

    var searchDialog = 'searchDialog';

    /** Search Controls **/
    $('#' + searchDialog).dialog({
        autoOpen: false,
        position: 'top',
        height: 90,
        width: 800,
        close: function() {
            $('#searchresDiag').dialog('close');
        }
    });  

    $('#searchresDiag').dialog({
        autoOpen: false,
        height: 500,
        width: 800,
        close: clearSearches
    });

    $('#opensearch').click( function() {
        // Open modal search window
        $('#' + searchDialog).dialog('open');
        setSearchButtons();
    });
    
    $('#searchbutton').click( function() {
        var searchingArtist = $('#searchfieldartist').val();
        var searchingSong = $('#searchfieldsong').val();

        if (searchingArtist == '' && searchingSong == '') {
            return;
        }

        clearSearches();
        if (searchingArtist != '') {
            sendMPD('search', 'artist ' + searchingArtist, 
                searchResponse, false, false);
        }

        if (searchingSong != '') {
            sendMPD('search', 'title ' + searchingSong, 
                searchResponse, false, false);
        }

        publishSearches();
    });

    /** Move songs from search to playlist **/
    $('#addallsongs').click( function () {
        playListAddSongs('.songinfo');
    });

    $('#addsinglesong').click( function () {
        playListAddSongs('.ui-selected + .songinfo');
    });

    assignHover('.search-icons');

    /** Initial Update **/
    updateAddButtons();
    updateState();
});

function clearSearches() {
    searchCache = new Array();
    $('#searchres').empty();
    setSearchButtons();
}

function setSearchButtons() {
    if (searchCache == undefined || searchCache.length == 0) {
        $('.search-all-icons').fadeOut();
    }

    updateAddButtons();
}

function publishSearches() {
    if (searchCache == undefined || searchCache == null) {
        return false;
    }

    // Shmidty?
    var sortSC = searchCache.sort(
        function(a, b) {
            var sOrder = ["artist", "year", "disc", "track"];
            return searchHelper(a, b, sOrder.shift(), sOrder);
        }
    );

    var sSong;

    var curAlbum = '';
    var preAlbum = '';

    var sDisc = '';
    var sYear = '';

    for (var i in sortSC) {
        sSong = searchCache[i];

        if (sSong.album == undefined) {
            curAlbum = 'No Album';
        } else {
            if (sSong.disc != undefined) {
                sDisc = ' (Disc ' + sSong.disc + ')';
            } else {
                sDisc = '';
            }

            if (sSong.date != undefined) {
                sYear = sSong.date + ' ';
            } else {
                sYear = '';
            }

            curAlbum = sSong.artist 
                + ' - ' + sYear + ' ' + sSong.album + sDisc;
        }

        if (preAlbum != curAlbum) {
            $('#searchres').append(
                  '<div class="search-item">'
                + '<div class="ui-state-default ui-corner-all '
                + 'album-reit album-reit-name">' 
                +  curAlbum + '</div>'
                + '</div>'
            );

            preAlbum = curAlbum;
        }

        addSearchResult(sSong, i);
    }

    $('#searchres').selectable({
        filter: '.search-reit',
        stop: updateAddButtons
    });

    $(this).fadeIn(300, function() {
        $('.search-all-icons').fadeIn();
        updateAddButtons();
        $('#searchresDiag').dialog('open');
    });


}

/** Used as a sort callback **/
function searchHelper(a, b, field, nextarr) {
    if (field.match(/[^a-zA-Z]/)) {
        alert("Danger Match!");
        return false;
    }

    if (eval('a.' + field) == undefined) {
        if (eval('b.' + field) == undefined) {
            eval('a.' + field + ' = 0');
            eval('b.' + field + ' = 0');
        } else {
            eval('a.' + field + ' = b.' + field);
        }
    }

    if (eval('b.' + field) == undefined) {
        eval('b.' + field + ' = a.' + field);
    }

    if (eval('a.' + field + ' > b.' + field)) {
        return 1;
    } else if (eval('a.' + field + ' < b.' + field)) {
        return -1;
    } else {
        if (nextarr.length > 0) {
            var next = nextarr.shift();
            searchHelper(a, b, next, nextarr);
        }
    }
}

/** 
  Add a song from the search results to the playlist.
  Handles cases of multiple selected songs.
  Uses updateState() to actually update the playlist.
 **/
function playListAddSongs(jQCore) {
    $(jQCore).each( function (n, e) {
        sendMPD('addid', $(this).text(), function() { },
            false, true);
    });

    playListUpdate = true;
    updateState();
}

/**
 *  Add all results from a search to global search cache.
 **/
function searchResponse(args) {
    if (args == '[]') {
        return false;
    }
    
    var sres = JSON.parse(args);

    for (var i in sres) {
        searchCache.push(sres[i]);
    }

    return true;
}

function updateAddButtons() {
    var jQR = $('.search-reit.ui-selected');

    var singleBtn = '.search-sel-icons';
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
        var trackStr = '';
        if (song.track != undefined) {
            trackStr = song.track;
        }

        songDisp = trackStr + ' - ' + song.title;
    }

    $('#searchres').append(
              '<div class="search-item">'
            + '<div class="ui-state-default ui-corner-all '
            + 'album-reit album-reit-tl">'
            + '<span class="ui-icon ui-icon-carat-1-e"></span>'
            + '</div>'
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

/** MPD Control Buttons **/
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

function cmd_psclear() {
    playListUpdate = true;
    sendMPD('clear');
}

function cmd_plmode() {
    if (currRepeat == undefined || currSingle == undefined) {
        updateState();          
    }

    if (currSingle == 1) {
        if (currRepeat == 1) {
            currSingle = 0;
            currRepeat = 0;
        } else {
            currRepeat = 1;
        }
    } else {
        if (currRepeat == 1) {
            currSingle = 1;
            currRepeat = 0;
        } else {
            currRepeat = 1;
        }
    }

    sendMPD('repeat', currRepeat, function() {} );
    sendMPD('single', currSingle);
}

function cmd_remove(arg) {
    var playArg = currentTargetExtract(arg.currentTarget.id, playListCurr);

    playListUpdate = true;
    
    // Do animation
    sendMPD('deleteid', playArg);
}

/** Misc Functions **/
function updateDebug(data) {
    $('#debuggah').append(data + "\n");
}

function parseMPDResults(inputs) {
    var jsonObj = JSON.parse(inputs);

    return jsonObj;
}

/** Status Functions **/
function updateState() {
    sendMPD('status', '', updateStatuses); 
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

function divUSDHelper(classStr, infoStr) {
    return '<div class="ui-state-default ui-corner-all '
        + classStr + '">' + infoStr + '</div>' + "\n";
}

function updatePlaylist(arg) {
    $('#songlist').sortable("destroy");
    
    var songInfo = parseMPDResults(arg);

    for (var i in songInfo) {
        var playListId = playlistName(parseInt(songInfo[i]['id']));
        var songInfoStr = '';
        var songInfoOrder = ['artist', 'album', 'track', 'title'];

        for (var sio in songInfoOrder) {
            var songInfoK = '(no album)';
            if (songInfo[i][songInfoOrder[sio]] != undefined) {  
                songInfoK = songInfo[i][songInfoOrder[sio]];
            }

            songInfoStr += divUSDHelper('song-item', songInfoK);
        }

        $('#songlist').append(
              '<div id="'+ playListId + '" class="song-reit">' 
            + songInfoStr
            + '<div class="song-reit-cont">'
<?php

$jsid = '\' + playListId + \'_';

foreach ($conbtn as $id => $btn) {
    echo '+ \'' . build_div_button($jsid . $id, $btn['icon'], 
            'play-icons float-left', $btn['title']) . '\'' . "\n";
}

?>
            + '</div>'
        );
<?php

foreach ($conbtn as $id => $btn) {
    $nid = $jsid . $id;
    echo "$('#$nid').unbind('click').click( cmd_$id );\n";
    echo "assignHover('#$nid');\n";
}

?>
    }

    $('#songlist').sortable();
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
        min: 0,
        max: 100,
        value: args,
        slide: function(event, ui) {
            sendMPD('setvol', ui.value, false);
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
        sendMPD('playlistinfo', '0:' + playListSize, updatePlaylist, false);

        playListUpdate = false;
    }
}

function updateState_state(arg) {
    updateIcon(arg, 'play');

    currState = arg;

    if (currState == 'play') {
        setTimeout('updateState();', 500);
    }
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

function updateState_repeat(arg) {
    currRepeat = arg;

    if (currSingle != undefined) {
        updateRepeatButton();
    }
}

function updateState_single(arg) {
    currSingle = arg;

    if (currRepeat != undefined) {
        updateRepeatButton();
    }
}

function updateRepeatButton() {
    var iconstr = 'arrowthickstop-1-e';
    var icontit = 'Play to End of Playlist';

    if (currSingle == 1 && currRepeat == 1) {
        iconstr = 'arrowreturn-1-w';
        icontit = 'Repeat Song';
    } else if (currSingle == 1) {
        iconstr = 'arrowstop-1-e';
        icontit = 'Play to End of Song';
    } else if (currRepeat == 1) {
        iconstr = 'arrowreturnthick-1-w';
        icontit = 'Repeat Playlist';
    } 

    $('#plmode span').removeClass().addClass('ui-icon ui-icon-' + iconstr);
    $('#plmode').attr('title', icontit);
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

<div id="songlist">
</div>

<div id="play-controls" class="ui-widget ui-widget-content ui-corner-all">

<?php

foreach ($playbtn as $id => $btn) {
    if (isset($btn['nullvoid'])) {
        echo "\n" . '<span class="ui-icon ui-icon-' . $btn['icon']
            . ' separator"></span>';

    } else {
        echo "\n" . build_div_button($id, $btn['icon'], 
            'play-icons float-left', $btn['title']) . "\n";
    }
}

?>
    <div id="voluprogwrap">
        <div id="voluprog"></div>
    </div>

    <div id="playprogwrap">
        <div id="playprog"></div>
    </div>

    <div id="playprogtext">0:00 / 0:00</div>

<?php
    echo build_div_button('opensearch', 'search', 
            'play-icons float-right', 'Open Search Box');
?>
    </div>
</div>

<div id="searchDialog" title="Search">
    <div class="searchDiagText float-left">Artist</div>
    <input id="searchfieldartist" type="text" size=25 />
    <div class="searchDiagText float-left">Song</div>
    <input id="searchfieldsong" type="text" size=25 />

<?php

$searchbtn = array();

$searchbtn['searchbutton']['title'] = 'Search';
$searchbtn['searchbutton']['icon']  = 'search';
$searchbtn['searchbutton']['class'] = '';

$searchbtn['addallsongs']['title'] = 'Add All Songs';
$searchbtn['addallsongs']['icon']  = 'circle-plus';
$searchbtn['addallsongs']['class'] = 'search-all-icons';

$searchbtn['addsinglesong']['title'] = 'Add Selected Song(s)';
$searchbtn['addsinglesong']['icon']  = 'plus';
$searchbtn['addsinglesong']['class'] = 'search-sel-icons';

foreach ($searchbtn as $id => $btn) {
    echo "    " . build_div_button(
            $id, $btn['icon'], 
            'search-icons ' . $btn['class'], $btn['title']) 
        . "\n";
}

?>
</div>

<div id="searchresDiag" title="Search Results">
    <div id="searchres"></div>
</div>

<pre id="debuggah">
</pre>

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
