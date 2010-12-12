<?php

class mpd_connection {
    private $host;
    private $port;

    private $errno;
    private $errms;
    private $timeo;    

    private $mpdrs;

    // Sends a single command to MPD
    function mpd_send_com($command, $options = NULL, $no_quote = FALSE) {
        $this->mpd_open();

        // Do the stuff
        $ret = $this->mpd_com($command, $options, $no_quote);

        $this->mpd_close();

        $fullret = $this->mpd_parse($ret);

        if (is_array($fullret)) {
            $fullret = json_encode($fullret);
        }
        
        return $fullret;
    }

    function mpd_parse($retstr) {
        $inputs = explode("\n", $retstr);

        $facts = array();

        //error_log(print_r($inputs, TRUE));

        foreach ($inputs as $input) {
            if (!preg_match("/^OK/", $input) && trim($input) != '') {
                if (preg_match("/^ACK.*/", $input)) {
                    return FALSE; 
                }

                preg_match('/([a-zA-Z0-9]*):\s*(.*)/', $input, $matches);
               
                $field = strtolower(trim($matches[1]));

                if (isset($matches[2])) {
                    $datum = trim($matches[2]);
                }

                if (!isset($theobj)) {
                    $theobj = array();
                } else if (isset($theobj[$field]) 
                        && $field != 'composer') {
                    $facts[] = $theobj;
                    $theobj = array();
                }

                if (isset($datum)) {
                    $theobj[$field] = $datum;
                }
            }
        }

        if (isset($theobj)) {
            $facts[] = $theobj;
        }

        return $facts;
    }

    function mpd_com($command, $options, $no_quote = FALSE) {
        $dstr = '';
       
        // Connect response
        $dstr .= $this->mpd_flush();

        $fcmdl = $command;

        if ($options != NULL) {
            if ($no_quote) {
                $fcmdl .= ' "' . $options . '"';
            } else {
                $eopt = explode(' ', $options);
                $fcmdl .= ' "' . implode('" "', $eopt) . '"';
            }
        }

        //$dstr .= "> " . $fcmdl . "\n";

        $stat = fwrite($this->mpdrs, $fcmdl . "\n");

        if (!$stat) {
            die ("Write error");
        }

        $dstr .= $this->mpd_flush();

        return $dstr;
    }

    function mpd_flush() {
        $rstring = '';

        $fgetsl = 8192;

        while (!feof($this->mpdrs)) {
            $got = fgets($this->mpdrs, $fgetsl);

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
        }

        if (!isset($this->port)) {
            $this->port = '6600';
        }

        if (!isset($this->timeo)) {
            $this->timeo = 22;
        }
    }
}

