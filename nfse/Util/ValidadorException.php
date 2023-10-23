<?php

namespace NFSePHP\NFSe\Util;

use NFSePHP\NFSe\Interfaces\ExceptionInterface;

class ValidadorException extends \RuntimeException implements ExceptionInterface
{
    public static function xmlErrors(array $errors)
    {
        $msg = '';
        foreach ($errors as $error) {
            $msg .= $error . "\n";
        }
        return new static('Este XML não é válido. ' . $msg);
    }

    public static function isNotXml()
    {
        return new static('A string passada não é um XML');
    }
}
