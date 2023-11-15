<?php

namespace NFSePHP\NFSe\Util;

use DOMDocument;
use DOMElement;
use DOMNode;

class SignerNfse
{
    public static function generateSign($certificado, $password, $dadosXML): bool|string
    {
        if (openssl_pkcs12_read($certificado, $certs, $password) && extension_loaded('openssl')) {

            // Obtém a chave privada
            $privateKey = openssl_pkey_get_private($certs['pkey']);

            // Obtém o certificado X.509
            $x509Cert = openssl_x509_read($certs['cert']);

            // Cria um novo documento XML
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->formatOutput = true;

            // Cria o elemento Signature
            $signature = $doc->createElement('Signature');
            $signature->setAttribute('xmlns', "http://www.w3.org/2000/09/xmldsig#");

            // Cria o elemento SignedInfo
            $signedInfo = $doc->createElement('SignedInfo');

            // Adiciona os elementos CanonicalizationMethod e SignatureMethod a SignedInfo
            $canonicalizationMethod = $doc->createElement('CanonicalizationMethod');
            $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
            $signedInfo->appendChild($canonicalizationMethod);

            $signatureMethod = $doc->createElement('SignatureMethod');
            $signatureMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');
            $signedInfo->appendChild($signatureMethod);

            // Cria o elemento Reference
            $reference = $doc->createElement('Reference');

            // Adiciona os elementos Transforms a Reference
            $transforms = $doc->createElement('Transforms');
            $reference->appendChild($transforms);

            $transform1 = $doc->createElement('Transform');
            $transform1->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
            $transforms->appendChild($transform1);

            $transform2 = $doc->createElement('Transform');
            $transform2->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
            $transforms->appendChild($transform2);

            // Calcule o DigestValue com base nos dados do XML
            $xmlDigestValue = base64_encode(sha1($dadosXML, true));
            $digestMethod = $doc->createElement('DigestMethod');
            $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
            $reference->appendChild($digestMethod);

            $digestValue = $doc->createElement('DigestValue', $xmlDigestValue);
            $reference->appendChild($digestValue);

            // Adiciona Reference a SignedInfo
            $signedInfo->appendChild($reference);

            // Adiciona SignedInfo a Signature
            $signature->appendChild($signedInfo);

            // Crie a assinatura usando a chave privada
            openssl_sign($signedInfo->C14N(), $signatureValue, $privateKey, OPENSSL_ALGO_SHA1);

            // Cria o elemento SignatureValue com a assinatura
            $signatureValueElement = $doc->createElement('SignatureValue', base64_encode($signatureValue));
            $signature->appendChild($signatureValueElement);

            // Adicione o elemento KeyInfo com o certificado X.509
            $keyInfo = $doc->createElement('KeyInfo');
            $x509Data = $doc->createElement('X509Data');

            // Adicione o certificado X.509
            $certData = openssl_x509_export($x509Cert, $output, false);
            if ($certData) {
                $x509Certificate = $doc->createElement('X509Certificate', base64_encode($output));
                $x509Data->appendChild($x509Certificate);
            }

            $keyInfo->appendChild($x509Data);
            $signature->appendChild($keyInfo);

            // Adicione o elemento Signature ao documento
            $doc->appendChild($signature);

            $docString = $doc->saveXML();

            $signatureLines = explode("\n", $docString);
            array_shift($signatureLines);
            $signatureString = implode("\n", $signatureLines);
            return $signatureString;
        } else {
            return false;
        }
    }

}
