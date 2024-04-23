<?php

namespace NFSePHP\NFSe;

use DOMDocument;
use DOMXPath;
use LucasArend\HttpFox\HttpFox;
use NFSePHP\NFSe\Util\Certificado;
use NFSePHP\NFSe\Util\SignerNfse;
use NFSePHP\NFSe\Util\validXml;
use PHPUnit\Util\Exception;
use RuntimeException;
use stdClass;

class Tools
{
    /**
     * @var string $urlSoap
     */
    protected string $urlSoap;
    /**
     * @var string $tpAmb
     */
    protected string $tpAmb;
    /**
     * @var string $soapAction
     */
    protected string $soapAction;
    /**
     * @var stdClass $envelopeCabecalho;
     */
    protected stdClass $envelopeCabecalho;
    /**
     * Sign algorithm from OPENSSL
     * @var int $algorithm
     */
    protected int $algorithm = OPENSSL_ALGO_SHA1;
    /**
     * Canonical conversion options
     * @var array $canonical
     */
    protected array $canonical = [true,false,null,null];
    /**
     * @var array
     */
    protected array $padroes = [
        'BLUMENAU' => 'SIMPLISS',
        'GASPAR' => 'BETA11',
        'LAGES' => 'BETA2'
    ];
    /**
     * @var array|string[]
     */
    private array $cidadePrefixo = ['BLUMENAU'];
    /**
     * @var string $prefixoNfse
     */
    protected string $prefixoNfse;

    /**
     * @param stdClass $config
     * @param Certificado $certificate
     */
    public function __construct(\stdClass $config, $certificado, $passwordCertificado, $cidade)
    {
        $this->config = $config;
        $this->version = $this->config->versao;
        $this->uf = $this->config->siglaUF;
        $this->cidade = $this->config->cidade;
        $this->certificate = $certificado;
        $this->password = $passwordCertificado;
        $this->tpAmb = $this->config->tpAmb;
        $this->padrao = $this->padroes[$cidade];
        $this->prefixoNfse = in_array($cidade, $this->cidadePrefixo) ? 'nfse:' : '';
    }

    /**
     * @param $signedXML
     * @param array $options
     * @return bool|\Exception|string
     */
    public function gerarNFSe($signedXML, array $options): \Exception|bool|string
    {
        try {
            $infoHeader = $this->getCabecalho();
            $soapAction = $this->getSoapAction();
            $xmlFormatedString = validXml::formatXML($signedXML, $infoHeader);
            $xmlEnvio = new DOMDocument('1.0', 'UTF-8');
            $xmlEnvio->loadXML($xmlFormatedString);
            $xmlEnvio->formatOutput = true;

//            //Buscando XSD de validação
//            $xsdValidate = $this->getValidateXMLSchema(); // Contém o conteúdo do XSD
//
//
//            if(!$xmlEnvio->schemaValidate($xsdValidate)){
//                $erros = libxml_get_errors();
//                foreach ($erros as $erro) {
//                    throw new \Exception("Erro: " . $erro->message . " na linha " . $erro->line . "<br />");
//                }
//                libxml_clear_errors();
//            }

            $http = new HttpFox();
            $http->disableSSL();
            $http->setProxy();
            array_push($options, $soapAction);

            $http->setHeaders($options);

            $result = $http->sendPost($this->getUrlConnect(),$xmlEnvio->saveXML());

        } catch (\Exception $e) {
            $result = false;
        }
        if ($result !== false) {
            return $result;
        } else {
            return $e;
        }
    }

    /**
     * @param $signedXML
     * @return string
     * @throws \DOMException
     */
    public function envioLoteRps($signedXML): string
    {
        $options = [
            'Content-Type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Expect: 100-continue',
            'Connection: Keep-Alive',
        ];
        $infoHeader = $this->getCabecalho('LOTE');
        $soapAction = $this->getSoapAction('LOTE');
        $signedXML .= '<assinarLote/>';
        $xmlFormatedString = validXml::formatXML($signedXML, $infoHeader);
        $xmlFormatedString = '<?xml version="'.$this->version.'" encoding="UTF-8"?>'.$xmlFormatedString;
        $xmlAssinado = SignerNfse::signNFSe($this->certificate, $this->password, $xmlFormatedString);

        $http = new HttpFox();
        $http->disableSSL();
        //$http->setProxy(); //Utilizado para depurar as requisições com o fiddler
        array_push($options, $soapAction);
        $http->setHeaders($options);
        $result = $http->sendPost($this->getUrlConnect('LOTE'), $xmlAssinado);

        return $result;
    }

    /**
     * @param $cnpj
     * @param $protocolo
     * @param $inscricao
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function consultaLoteRps($cnpj, $protocolo, $inscricao, array $options): string
    {
        $infoHeader = $this->getCabecalho('CONSULTA');
        $soapAction = $this->getSoapAction('CONSULTA');
        $xml = '
        <'.$this->prefixoNfse.'Prestador>'.
        '<'.$this->prefixoNfse.'CpfCnpj>'.
        '<'.$this->prefixoNfse.'Cnpj>$cnpj</'.$this->prefixoNfse.'Cnpj>'.
        '</'.$this->prefixoNfse.'CpfCnpj>'.
        '<'.$this->prefixoNfse.'InscricaoMunicipal>$inscricao</'.$this->prefixoNfse.'InscricaoMunicipal>'.
        '</'.$this->prefixoNfse.'Prestador>'.
        '<'.$this->prefixoNfse.'Protocolo>$protocolo</'.$this->prefixoNfse.'Protocolo>';

        $xmlFormatedString = validXml::formatXML($xml, $infoHeader);
        $xmlFormatedString = '<?xml version="'.$this->version.'" encoding="UTF-8"?>'.$xmlFormatedString;

        $http = new HttpFox();
        $http->disableSSL();
        //$http->setProxy(); //Utilizado para depurar as requisições com o fiddler
        array_push($options, $soapAction);

        $http->setHeaders($options);

        $result = $http->sendPost($this->getUrlConnect(),$xmlFormatedString);

        return $result;
    }

    /**
     * @param $xml
     * @param $numRPS
     * @param $numNFS
     * @param $cnpj
     * @param $codigoMunicipio
     * @param $inscricao
     * @param array $options
     * @return string
     * @throws \Exception
     */
    public function cancelaRps($xml, $numRPS, $numNFS, $cnpj, $codigoMunicipio, $inscricao, array $options): string
    {
        $infoHeader = $this->getCabecalho('CANCELAR');
        $soapAction = $this->getSoapAction('CANCELAR');

        $sing = SignerNfse::generateSign($this->certificate, $this->password, $xml);

        $xml = '
        <'.$this->prefixoNfse.'Pedido>
        <'.$this->prefixoNfse.'InfPedidoCancelamento Id="'.$numRPS.'">
            <'.$this->prefixoNfse.'IdentificacaoNfse>
                <'.$this->prefixoNfse.'Numero>'.$numNFS.'</'.$this->prefixoNfse.'Numero>
                <'.$this->prefixoNfse.'CpfCnpj>
                    <'.$this->prefixoNfse.'Cnpj>'.$cnpj.'</'.$this->prefixoNfse.'Cnpj>
                </'.$this->prefixoNfse.'CpfCnpj>
                <'.$this->prefixoNfse.'InscricaoMunicipal>'.$inscricao.'</'.$this->prefixoNfse.'InscricaoMunicipal>
                <'.$this->prefixoNfse.'CodigoMunicipio>'.$codigoMunicipio.'</'.$this->prefixoNfse.'CodigoMunicipio>
            </'.$this->prefixoNfse.'IdentificacaoNfse>
            <'.$this->prefixoNfse.'CodigoCancelamento>2</'.$this->prefixoNfse.'CodigoCancelamento>
        </'.$this->prefixoNfse.'InfPedidoCancelamento>
        </'.$this->prefixoNfse.'Pedido>'
        . $sing;
        $xml = validXml::formatXML($xml, $infoHeader);
        $xml = '<?xml version="'.$this->version.'" encoding="UTF-8"?>'.$xml;

        $http = new HttpFox();
        $http->disableSSL();
        //$http->setProxy(); //Utilizado para depurar as requisições com o fiddler
        array_push($options, $soapAction);

        $http->setHeaders($options);

        $result = $http->sendPost($this->getUrlConnect(),$xml);

        return $result;
    }

    /**
     * @throws \Exception
     */
    protected function getUrlConnect($metodo = false): string
    {
        $fileContaisUrl = $this->getXmlUrlPath();
        $xmlUrl = simplexml_load_string($fileContaisUrl);
        $padrao = $this->padrao;
        $uf = $this->uf;
        $cidade = $this->cidade;
        $dadosPadrao = $xmlUrl->$padrao;
        $dadosEstado = $dadosPadrao->$uf;
        $dadosMunicipio = $dadosEstado->$cidade;
        if ($this->tpAmb == 1) {
            $this->urlSoap = $dadosMunicipio->PROD[0];
            if(!$this->urlSoap == 'false'){
                $this->urlSoap = $dadosMunicipio->$metodo->PROD[0];
            }
        } else {
            $this->urlSoap = $dadosMunicipio->HOM[0];
            if($this->urlSoap == 'false'){
                $this->urlSoap = $dadosMunicipio->$metodo->HOM[0];
            }
        }

        return $this->urlSoap;
    }

    /**
     * @param $action
     * @return stdClass
     * @throws \Exception
     */
    protected function getCabecalho($action): stdClass
    {
        $fileContaisUrl = $this->getXmlUrlPath();
        $xmlUrl = simplexml_load_string($fileContaisUrl);
        $padrao = $this->padrao;
        $uf = $this->uf;
        $cidade = $this->cidade;
        $dadosPadrao = $xmlUrl->$padrao;
        $dadosEstado = $dadosPadrao->$uf;
        $dadosMunicipio = $dadosEstado->$cidade;
        $lote = $dadosMunicipio->$action;
        $cabecalho = $lote->CABECALHO;
        $header = $cabecalho->HEADER;
        $footer = $cabecalho->FOOTER;
        $std = new stdClass();
        $std->header = $header;
        $std->footer = $footer;
        $this->envelopeCabecalho = $std;

        return $this->envelopeCabecalho;
    }

    /**
     * @param $action
     * @return string
     * @throws \Exception
     */
    protected function getSoapAction($action): string
    {
        $fileContaisUrl = $this->getXmlUrlPath();
        $xmlUrl = simplexml_load_string($fileContaisUrl);
        $padrao = $this->padrao;
        $uf = $this->uf;
        $cidade = $this->cidade;
        $dadosPadrao = $xmlUrl->$padrao;
        $dadosEstado = $dadosPadrao->$uf;
        $dadosMunicipio = $dadosEstado->$cidade;
        $tipo = $dadosMunicipio->$action;
        $this->soapAction = 'SOAPAction: "' .$tipo->soapAction[0]. '"';

        return $this->soapAction;
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    protected function getXmlUrlPath(): bool|string
    {
        $file = __DIR__ . "/urlDanfse.xml";

        if (!file_exists($file)) {
            throw new \Exception('Caminho arquivo XML URL não encontrado!');
        }
        return file_get_contents($file);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getValidateXMLSchema(): string
    {
        $file = __DIR__ . "/nfse_v2-03.xsd";

        if (!file_exists($file)) {
            throw new \Exception('Caminho arquivo XSD não encontrado!');
        }
        return $file;
    }

    /**
     * @param $xmlString
     * @return string|null
     */
    public function getNumeroFromXML($xmlString): ?string
    {
        $xml = simplexml_load_string($xmlString);
        $xml->registerXPathNamespace('n', 'http://www.abrasf.org.br/nfse.xsd');

        $numeroNode = $xml->xpath('//n:Numero');

        if (!empty($numeroNode)) {
            return (string) $numeroNode[0];
        }

        return null;
    }
}
