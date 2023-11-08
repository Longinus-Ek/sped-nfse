<?php

namespace NFSePHP\NFSe\Interfaces;

use NFSePHP\NFSe\Util\CertificadoException;

interface VerificationInterface
{
    const SIGNATURE_CORRECT = 1;

    const SIGNATURE_INCORRECT = 0;

    const SIGNATURE_ERROR = -1;

    /**
     * Verify signature
     * @link http://php.net/manual/en/function.openssl-verify.php
     * @param string $data
     * @param string $signature
     * @param int $algorithm [optional] For more information see the list of Signature Algorithms.
     * @return bool Returns true if the signature is correct, false if it is incorrect
     * @throws CertificadoException An error has occurred when verify signature
     */
    public function verify($data, $signature, $algorithm = OPENSSL_ALGO_SHA1);
}
