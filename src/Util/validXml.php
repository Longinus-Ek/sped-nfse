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

        $xml =  $infoCabecalho->header.$xmlSemCabecalho.$infoCabecalho->footer;

        return $xml;
    }

}
