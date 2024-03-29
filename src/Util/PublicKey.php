<?php

namespace NFSePHP\NFSe\Util;

use NFSePHP\NFSe\Interfaces\VerificationInterface;

class PublicKey implements VerificationInterface
{
    /**
     * @var string
     */
    private $rawKey;
    /**
     * @var string
     */
    public $commonName;
    /**
     * @var string
     */
    public $icp;
    /**
     * @var string
     */
    public $caurl;
    /**
     * @var \DateTime
     */
    public $validFrom;
    /**
     * @var \DateTime
     */
    public $validTo;
    /**
     * @var string
     */
    public $emailAddress;
    /**
     * @var string Cryptographic Service Provider
     */
    public $cspName;
    /**
     * @var string
     */
    public $serialNumber;
    /**
     * @var string
     */
    public $subjectNameValue;

    /**
     * PublicKey constructor.
     * @param string $publicKey
     */
    public function __construct($publicKey)
    {
        $this->rawKey = $publicKey;
        $this->read();
    }

    /**
     * Load class with certificate content
     * @param string $content
     * @return PublicKey
     */
    public static function createFromContent($content)
    {
        $content = rtrim(chunk_split(preg_replace('/[\r\n]/', '', $content), 64, PHP_EOL));
        $certificate = <<<CONTENT
-----BEGIN CERTIFICATE-----
{$content}
-----END CERTIFICATE-----

CONTENT;

        return new static($certificate);
    }

    /**
     * Parse an X509 certificate and define the information in object
     * @link http://php.net/manual/en/function.openssl-x509-read.php
     * @link http://php.net/manual/en/function.openssl-x509-parse.php
     * @return void
     * @throws CertificadoException Unable to open certificate
     */
    protected function read()
    {
        if (!$resource = openssl_x509_read($this->rawKey)) {
            throw CertificadoException::unableToOpen();
        }
        $detail = openssl_x509_parse($resource, false);
        $this->commonName = $detail['subject']['commonName'];
        if (isset($detail['subject']['emailAddress'])) {
            $this->emailAddress = $detail['subject']['emailAddress'];
        }
        if (isset($detail['issuer']['organizationalUnitName'])) {
            $this->cspName = is_array($detail['issuer']['organizationalUnitName'])
                ? implode(', ', $detail['issuer']['organizationalUnitName']) . ' - ' . $detail['issuer']['commonName']
                : $detail['issuer']['organizationalUnitName'] . ' - ' . $detail['issuer']['commonName'];
        }
        $this->serialNumber = $detail['serialNumber'];
        $this->icp = $detail['subject']['organizationName'] ?? '';
        $authority = $detail['extensions']['authorityInfoAccess'] ?? '';
        if (!empty($authority)) {
            $txt = explode("\n", $authority);
            $this->caurl = $this->between($txt[0], 'http', ['.p7b', '.p7c']);
        }
        $this->validFrom = \DateTime::createFromFormat('ymdHis\Z', $detail['validFrom']);
        $this->validTo = \DateTime::createFromFormat('ymdHis\Z', $detail['validTo']);
        if (isset($detail['name'])) {
            $arrayName = explode("/", $detail["name"]);
            $arrayName = array_reverse($arrayName);
            $arrayName = array_filter($arrayName);
            $name = implode(",", $arrayName);
            $this->subjectNameValue = $name;
        }
    }

    /**
     * Verify signature
     * @link http://php.net/manual/en/function.openssl-verify.php
     * @param string $data
     * @param string $signature
     * @param int $algorithm [optional] For more information see the list of Signature Algorithms.
     * @return bool Returns true if the signature is correct, false if it is incorrect
     * @throws CertificadoException An error has occurred when verify signature
     */
    public function verify($data, $signature, $algorithm = OPENSSL_ALGO_SHA1)
    {
        $verified = openssl_verify($data, $signature, $this->rawKey, $algorithm);
        if ($verified === self::SIGNATURE_ERROR) {
            throw CertificadoException::signatureFailed();
        }
        return $verified === self::SIGNATURE_CORRECT;
    }

    /**
     * Check if is in valid date interval.
     * @return bool Returns true
     */
    public function isExpired()
    {
        return new \DateTime('now') > $this->validTo;
    }

    /**
     * Returns raw public key without markers and LF's
     * @return string
     */
    public function unFormated()
    {
        $ret = preg_replace('/-----.*[\n]?/', '', $this->rawKey);
        return preg_replace('/[\n\r]/', '', $ret);
    }

    /**
     * Returns raw public key
     * @return string
     */
    public function __toString()
    {
        return $this->rawKey;
    }

    /**
     * Extract CNPJ number by OID
     * @return string
     */
    public function cnpj()
    {
        return Asn1::getCNPJ($this->unFormated());
    }

    /**
     * Extract CPF number by OID
     * @return string
     */
    public function cpf()
    {
        return Asn1::getCPF($this->unFormated());
    }

    /**
     * @param string $string
     * @param string $start
     * @param array $end
     * @return string
     */
    protected function between(string $string, string $start, array $end = ['p7b', 'p7c']): string
    {
        $string = ' ' . $string;
        $final = null;
        foreach ($end as $fim) {
            if (strpos($string, $fim) !== false) {
                $final = $fim;
                break;
            }
        }
        if (empty($final)) {
            return '';
        }
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }
        $fim = strpos($string, $final) - 13;
        $path = substr($string, $ini, $fim);
        if (substr($path, -4) === $final) {
            return $path;
        }
        return '';
    }
}
