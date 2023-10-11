<?php

/**
 * Classe a construção do xml da NFSe
 * Esta classe basica está estruturada para montar XML da NFSe para o
 * layout versão 2.02, os demais modelos serão derivados deste
 *
 * @category  API
 * @package   NFePHP\NFe\
 * @author    Erick Dias
 * @link      www.mudar.com.br
 */

namespace yuon\nfse;

use DOMDocument;
use stdClass;

class Nfse extends DOMDocument
{
    private $stdOBJ;
    public $errors = [];
    public $dom;
    public $nfse;

    //Monta os dados necessários para emissão da NFS-e

    /**
     * Construtor recebe o objeto da nota fiscal com todas as informações e instancia um novo DOM Document
     * @param $stdOBJ
     */

    public function __construct($stdOBJ)
    {
        $this->stdOBJ = $stdOBJ;
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
        //Cria a tag Nfse no escopo do xml
        $this->buildNfse();

    }

    public function montaXML(): string
    {
        if (!empty($this->errors)) {
            $this->errors = array_merge($this->errors, $this->dom->errors);
        } else {
            $this->errors = $this->dom->errors;
        }
        $std = $this->stdOBJ;

        return 'aaaa';
    }

    private function buildNfse(stdClass $std) : \DOMElement{

        $this->nfse = $this->dom->createElement('Nfse');
        $this->nfse->setAttribute('versao', $std->versao);
        return $this->nfse;
    }
}
