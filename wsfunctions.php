<?php

require('cryptfunctions.php');
require('mpdfunctions.php');

class ws_wrapper {
    private $mpdcon;
    var $ws_dec;

    function ws_set($setting) {
        if (!function_exists('decrypt_' . $setting)) {
            die ('Bad encryption algorithm');
        }
        
        $this->ws_dec = 'decrypt_' . $setting;

        $this->mpdcon = new mpd_connection();
    }

    /** Configure the MPD connection **/
    function ws_con($configs) {
        $this->check_ws();

        // Do nothing for now 
    }

    /** Run one command via MPD service **/
    function ws_run($inputs) {
        $this->check_ws();

        if (!isset($inputs['argument'])) {
            $inputs['argument'] = NULL;
        }

        $return = $this->mpdcon->mpd_send_com(
            $inputs['command'], $inputs['argument']);

        return $return;
    }

    function check_ws() {
        if (!isset($this->mpdcon)) {
            die ('Bad call order, please set WebService first');
        } else {
            return TRUE;
        }
        
        if (!function_exists('json_decode')) {
            die ('JSON not availble');
        }
    }
}

