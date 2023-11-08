<?php

namespace NFSePHP\NFSe\Util;

class SoapLogger
{
    private $log = '';

    public function logData($data) {
        $this->log .= $data;
    }

    public function getLog() {
        return $this->log;
    }
}
