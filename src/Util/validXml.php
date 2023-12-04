<?php

namespace NFSePHP\NFSe\Util;

use stdClass;

class validXml
{
    /**
     * @param string $xml
     * @param stdClass $infoCabecalho
     * @return string
     */
    public static function formatXML(string $xml, stdClass $infoCabecalho): string
    {
        //Retirando cabeçalho xml nfse montada
        $xmlLinhas = explode("\n", $xml);
        array_shift($xmlLinhas);
        $xmlSemCabecalho = implode("\n", $xmlLinhas);
        $xmlSemCabecalho = $infoCabecalho->header.$xmlSemCabecalho.$infoCabecalho->footer;
        $xmlSig = preg_replace('/>\s+</', '><', $xmlSemCabecalho);
        $xmlSig = trim($xmlSig);

        return $xmlSig;
    }


}
