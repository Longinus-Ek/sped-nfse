<?php

namespace NFSePHP\NFSe\Util;

use DOMDocument;

class Validador
{
    /**
     * Find erros in XML validate with schema XSD
     * @param string $xml XML content
     * @param string $xsd real path to scheme file
     * @return boolean
     * @throws ValidadorException
     */
    public static function isValid($xml, $xsd)
    {
        if (!self::isXML($xml)) {
            throw ValidadorException::isNotXml();
        }
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        libxml_clear_errors();
        if (! $dom->schemaValidate($xsd)) {
            $errors = [];
            foreach (libxml_get_errors() as $error) {
                $errors[] = $error->message;
            }
            throw ValidadorException::xmlErrors($errors);
        }
        return true;
    }

    /**
     * Check if string is a XML
     * @param string $content
     * @return boolean
     */
    public static function isXML($content)
    {
        if (empty($content)) {
            return false;
        }
        $content = trim($content);
        if (
            stripos($content, '<!DOCTYPE html>') !== false
            || stripos($content, '</html>') !== false
        ) {
            return false;
        }
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        simplexml_load_string($content);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        return empty($errors);
    }
}
