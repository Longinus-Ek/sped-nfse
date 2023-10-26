<?php

namespace NFSePHP\NFSe;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LucasArend\HttpFox\HttpFox;
use NFePHP\DA\Legacy\Dom;
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
     * @param stdClass $config
     * @param Certificado $certificate
     */
    public function __construct(\stdClass $config, $certificado, $passwordCertificado)
    {
        $this->config = $config;
        $this->version = $this->config->versao;
        $this->uf = $this->config->siglaUF;
        $this->cidade = $this->config->cidade;
        $this->certificate = $certificado;
        $this->password = $passwordCertificado;
        $this->tpAmb = $this->config->tpAmb;
        $this->padrao = $this->config->padrao;
    }

    public function gerarNFSe($signedXML, array $options)
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

    public function envioLoteRps($signedXML, array $options): string{
        $infoHeader = $this->getCabecalho('LOTE');
        $soapAction = $this->getSoapAction('LOTE');
        $xmlFormatedString = validXml::formatXML($signedXML, $infoHeader);
        $xmlAssinado = $this->signLoteRps($xmlFormatedString);
        $xmlEnvio = new DOMDocument('1.0', 'UTF-8');
        $xmlEnvio->loadXML($xmlAssinado);
        $xmlEnvio->formatOutput = true;

        $http = new HttpFox();
        $http->disableSSL();
        //$http->setProxy(); //Utilizado para depurar as requisições com o fiddler
        array_push($options, $soapAction);

        $http->setHeaders($options);

        $result = $http->sendPost($this->getUrlConnect(),$xmlEnvio->saveXML());

        return $result;
    }

    public function consultaLoteRps($cnpj, $protocolo, $inscricao, array $options): string
    {
        $infoHeader = $this->getCabecalho('CONSULTA');
        $soapAction = $this->getSoapAction('CONSULTA');
        $xml = '
        <nfse:Prestador>'.
        '<nfse:CpfCnpj>'.
        "<nfse:Cnpj>$cnpj</nfse:Cnpj>".
        '</nfse:CpfCnpj>'.
        "<nfse:InscricaoMunicipal>$inscricao</nfse:InscricaoMunicipal>".
        '</nfse:Prestador>'.
        "<nfse:Protocolo>$protocolo</nfse:Protocolo>";

        $xmlFormatedString = validXml::formatXML($xml, $infoHeader);

        $xmlEnvio = new DOMDocument('1.0', 'UTF-8');
        $xmlEnvio->loadXML(trim($xmlFormatedString));
        $xmlEnvio->formatOutput = true;

        $http = new HttpFox();
        $http->disableSSL();
        //$http->setProxy(); //Utilizado para depurar as requisições com o fiddler
        array_push($options, $soapAction);

        $http->setHeaders($options);

        $result = $http->sendPost($this->getUrlConnect(),$xmlEnvio->saveXML());

        return $result;
    }

    public function cancelaRps($xml, $numRPS, $numNFS, $cnpj, $codigoMunicipio, $inscricao, array $options): string
    {
        $infoHeader = $this->getCabecalho('CANCELAR');
        $soapAction = $this->getSoapAction('CANCELAR');

        $sing = SignerNfse::generateSign($this->certificate, $this->password, $xml);

        $xml = '
        <nfse:Pedido>
        <nfse:InfPedidoCancelamento Id="'.$numRPS.'">
            <nfse:IdentificacaoNfse>
                <nfse:Numero>'.$numNFS.'</nfse:Numero>
                <nfse:CpfCnpj>
                    <nfse:Cnpj>'.$cnpj.'</nfse:Cnpj>
                </nfse:CpfCnpj>
                <nfse:InscricaoMunicipal>'.$inscricao.'</nfse:InscricaoMunicipal>
                <nfse:CodigoMunicipio>'.$codigoMunicipio.'</nfse:CodigoMunicipio>
            </nfse:IdentificacaoNfse>
            <nfse:CodigoCancelamento>2</nfse:CodigoCancelamento>
        </nfse:InfPedidoCancelamento>
        </nfse:Pedido>'
        . $sing;
        $xml = validXml::formatXML($xml, $infoHeader);

        $xmlEnvio = new DOMDocument('1.0', 'UTF-8');
        $xmlEnvio->loadXML(trim($xml));
        $xmlEnvio->formatOutput = true;

        $http = new HttpFox();
        $http->disableSSL();
        //$http->setProxy(); //Utilizado para depurar as requisições com o fiddler
        array_push($options, $soapAction);

        $http->setHeaders($options);

        $result = $http->sendPost($this->getUrlConnect(),$xmlEnvio->saveXML());

        return $result;
    }

    protected function getUrlConnect(): string
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
        } else {
            $this->urlSoap = $dadosMunicipio->HOM[0];
        }

        return $this->urlSoap;
    }

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

    protected function getXmlUrlPath()
    {
        $file = __DIR__ . "/urlDanfse.xml";

        if (!file_exists($file)) {
            throw new \Exception('Caminho arquivo XML URL não encontrado!');
        }
        return file_get_contents($file);
    }

    protected function getValidateXMLSchema()
    {
        $file = __DIR__ . "/nfse_v2-03.xsd";

        if (!file_exists($file)) {
            throw new \Exception('Caminho arquivo XSD não encontrado!');
        }
        return $file;
    }

    public function getNumeroFromXML($xmlString) {
        $xml = simplexml_load_string($xmlString);
        $xml->registerXPathNamespace('n', 'http://www.abrasf.org.br/nfse.xsd');

        $numeroNode = $xml->xpath('//n:Numero');

        if (!empty($numeroNode)) {
            return (string) $numeroNode[0];
        }

        return null;
    }

    /**
     * Sign NFe or NFCe
     * @param  string  $xml NFe xml content
     * @return string signed NFe xml
     * @throws RuntimeException
     */
    public function signLoteRps(string $xml): string
    {
        $gerarAssinatura = SignerNfse::generateSign($this->certificate, $this->password, $xml);
        if(!$gerarAssinatura){
            throw new Exception('Falha na leitura do Certificado, senha incorreta!');
        }
        $loadSign = new DOMDocument('1.0', 'UTF-8');
        $loadSign->loadXML($gerarAssinatura);
        $loadXml = new DOMDocument('1.0', 'UTF-8');
        $loadXml->loadXML($xml);
        // Crie um objeto DOMXPath
        $xpath = new DOMXPath($loadXml);

        $xpath->registerNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xpath->registerNamespace('sis', 'http://www.sistema.com.br/Sistema.Ws.Nfse');
        $xpath->registerNamespace('nfse', 'http://www.abrasf.org.br/nfse.xsd');

        $rps = $xpath->query('//nfse:Rps')->item(0);
        $enviarLote = $xpath->query('//sis:EnviarLoteRpsEnvio')->item(0);
        if($rps && $enviarLote){
            $signatureRPS = $loadXml->importNode($loadSign->documentElement, true);
            $rps->appendChild($signatureRPS);
            $signatureEnviarLote = $loadXml->importNode($loadSign->documentElement, true);
            $enviarLote->appendChild($signatureEnviarLote);
            return $loadXml->saveXML();
        }else {
            throw new Exception('Falha ao assinar XML, biblioteca não identificou a estrutura do XML, contate o suporte!');
        }

    }
}
