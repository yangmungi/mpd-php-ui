<?php

class mpd_connection {
    private $host;
    private $port;

    private $errno;
    private $errms;
    private $timeo;    

    private $mpdrs;

    // Sends a single command to MPD
    function mpd_send_com($command, $options = NULL) {
        $this->mpd_open();

        // Do the stuff
        $ret = $this->mpd_com($command, $options);
        $this->mpd_close();
        
        return $ret;
    }

    function mpd_com($command, $options) {
        $dstr = '';
       
        // Connect response
        $dstr .= $this->mpd_flush();

        $fcmdl = $command;
        if ($options != NULL) {
            $eopt = explode(' ', $options);
            $fcmdl .=' "' . implode('" "', $eopt) . '"';
        }

        $dstr .= "> " . $fcmdl . "\n";

        $stat = fwrite($this->mpdrs, $fcmdl . "\n");

        if (!$stat) {
            die ("Write error");
        }

        $dstr .= $this->mpd_flush();

        return $dstr;
    }

    function mpd_flush() {
        $rstring = '';

        $fgetsl = 2048;

        while (!feof($this->mpdrs)) {
            $got = fread($this->mpdrs, $fgetsl);
            //error_log($got);

            $rstring .= $got;

            $cleanput = FALSE;
            if ($rstring[strlen($rstring) - 1] == "\n") {
                $cleanput = TRUE;
            }

            $ers = explode("\n", $got);
           
            $brakes = FALSE;
            foreach ($ers as $line) {
                if ($cleanput 
                 && (preg_match('/^OK/', $line)
                  || preg_match('/^ACK/', $line))) {
                    $brakes = TRUE;
                }
            }

            if ($brakes) {
                break;
            }

        }

        return $rstring;
    }

    function mpd_open() {
        $this->check_cfg();

        $mpdr = fsockopen($this->host, $this->port, 
            $this->errno, $this->errms, $this->timeo);

        if (!$mpdr) {
            die ('Could not connect to MPD server: (' . $this->errno 
                . ') ' . $this->errms);
        }

        $this->mpdrs = $mpdr;
    }

    function mpd_close() {
        if (isset($this->mpdrs)) {
            fclose($this->mpdrs);
        }
    }

    function check_cfg() {
        if (!isset($this->host)) {
            $this->host = 'localhost';
            //error_log('Missing MPD host setting; setting to ' . $this->host);
        }

        if (!isset($this->port)) {
            $this->port = '6600';
            //error_log('Missing MPD port setting; setting to ' . $this->port);
        }

        if (!isset($this->timeo)) {
            $this->timeo = 22;
            //error_log('Missing MPD timeout setting; setting to ' . $this->timeo);
        }
    }
}

