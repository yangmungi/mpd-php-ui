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
        $this->mpd_com($command, $options);

        $this->mpd_close();
    }

    function mpd_com($command, $options) {
        $dstr = '';

        $dstr .= $this->mpd_flush();

        $fcmdl = $command;
        if ($options != NULL) {
            $fcmdl .=' "' . $options . '"';
        }

        fputs($this->mpdrs, $fcmdl);

        $dstr .= $this->mpd_flush();

        return $dstr;
    }

    function mpd_flush() {
        $rstring = '';

        while (!feof($this->mpdrs)) {
            $got = fgets($fp, 1024);

            if (strncmp("OK", $got, $strlen("OK")) == 0) {
                break;
            }

            $rstring .= "$got<br>";
            
            if (strncmp("ACK", $got, strlen("ACK")) == 0) {
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
            error_log('Missing MPD host setting');
        }

        if (!isset($this->port)) {
            $this->port = '6600';
            error_log('Missing MPD port setting');
        }

        if (!isset($this->timeo)) {
            $this->timeo = 10;
            error_log('Missing MPD timeout setting');
        }
    }
}

