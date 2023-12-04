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

        $xmlTeste = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://server.nfse.thema.inf.br">
    <SOAP-ENV:Body>
        <ns1:recepcionarLoteRpsLimitado>
            <ns1:xml><![CDATA[<?xml version="1.0" encoding="ISO-8859-1"?><EnviarLoteRpsEnvio xmlns="http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd"><LoteRps Id="L230612182323829"><NumeroLote>230612182323829</NumeroLote><Cnpj>32559216000188</Cnpj><InscricaoMunicipal>30324</InscricaoMunicipal><QuantidadeRps>1</QuantidadeRps><ListaRps><Rps><InfRps Id="R230612182323830"><IdentificacaoRps><Numero>230612182323830</Numero><Serie>NFOF</Serie><Tipo>2</Tipo></IdentificacaoRps><DataEmissao>2023-06-12T18:22:00</DataEmissao><NaturezaOperacao>59</NaturezaOperacao><RegimeEspecialTributacao>5</RegimeEspecialTributacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>204</ValorServicos><ValorDeducoes>0</ValorDeducoes><ValorPis>0</ValorPis><ValorCofins>0</ValorCofins><ValorInss>0</ValorInss><ValorIr>0</ValorIr><ValorCsll>0</ValorCsll><IssRetido>2</IssRetido><ValorIss>4.08</ValorIss><ValorIssRetido>0</ValorIssRetido><OutrasRetencoes>0</OutrasRetencoes><BaseCalculo>204</BaseCalculo><Aliquota>0.02</Aliquota><ValorLiquidoNfse>204</ValorLiquidoNfse><DescontoIncondicionado>0</DescontoIncondicionado><DescontoCondicionado>0</DescontoCondicionado></Valores><ItemListaServico>107</ItemListaServico><CodigoCnae>6201501</CodigoCnae><CodigoTributacaoMunicipio>0</CodigoTributacaoMunicipio><Discriminacao>10 Ips de Proxy
3 Ren de IP</Discriminacao><CodigoMunicipio>4205902</CodigoMunicipio></Servico><Prestador><Cnpj>32559216000188</Cnpj><InscricaoMunicipal>30324</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><CpfCnpj><Cnpj>82923160000177</Cnpj></CpfCnpj></IdentificacaoTomador><RazaoSocial>SANTA CATARINA INFORMATICA LTDA</RazaoSocial><Endereco><Endereco>89110000</Endereco><Numero>799</Numero><Bairro>BOM RETIRO</Bairro><CodigoMunicipio>4202404</CodigoMunicipio><Uf>SC</Uf><Cep>89110000</Cep></Endereco></Tomador></InfRps><Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" /><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" /><Reference URI=""><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" /><Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" /></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" /><DigestValue>AQy+tE2aqiOG9N5ZSpWIdXmNb9I=</DigestValue></Reference></SignedInfo><SignatureValue>Gd+vnRQ1ky75LH3lOUHTB+7Wq1zR4xfkjVbAAJUwQn05/kHKp4vP4fFaUNUdweRKNTgPbcsSgeALBGTBB+QwZgzLEpiu/z4W/xNIoOwg+zyn+BM7sBLkFbFf+0h3er0NfDozxTTzBHqIOmk/CXuUBvui8pR/beiKXFKPw7Q9QFJnS4BMJAjocUt7eEi70ZT+enjiQR6zErVtWxo/02TEuEzNbitJLqgYKdcK14y8v8HxBADE4t8Qqe5SjtW8cxwW0fjbbWMl+YL5oUcO9NsmNLp7N7hUdHZN/L8Z5cCOjzUpEgQYrDpbW4IGtOJJfq7/rVjfV/TOtApaK3Ke2jmMFw==</SignatureValue><KeyInfo><X509Data><X509Certificate>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</X509Certificate></X509Data></KeyInfo></Signature></Rps></ListaRps></LoteRps><Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" /><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" /><Reference URI="#L230612182323829"><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" /><Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" /></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" /><DigestValue>+eVm5cfF/fXYAE4nS8FDkXtj5UU=</DigestValue></Reference></SignedInfo><SignatureValue>Of1uVM7PLvz0FhL4RfT1D/SVHxbOKbxwPi/S//+r4NJMnLkt4X6qmMyo8SoaKqft2VUQqNM3AKHTi1sPhWmMTSHCDmTmY+0g7ZcYBpHQ2aj/y18O60XQ7qnJhW9B+yShiOqnKaFZTFC7KieJa9dDYAs9Rej2Rwgtf5JJoNQ1yNVSMwDiI2b7jXEurtQ8osgBVbsbIYrLXseZRJd1tzGeIn1GXZ79CD0A0nD45ZydblGiKX7QdnotaYEZAz5eP1J5aCL5TICfitUhE3PZJG+Ine8ZP27eLXUBlxMBLuhJZp2H05u9neNgcDKYDc2VJ1x0ttAteQKCNHQh1um+maEIHA==</SignatureValue><KeyInfo><X509Data><X509Certificate>MIIHUjCCBTqgAwIBAgIIVR0iERFaQXowDQYJKoZIhvcNAQELBQAwWTELMAkGA1UEBhMCQlIxEzARBgNVBAoTCklDUC1CcmFzaWwxFTATBgNVBAsTDEFDIFNPTFVUSSB2NTEeMBwGA1UEAxMVQUMgQ0VSVElGSUNBIE1JTkFTIHY1MB4XDTIyMTExNjIwMzYwMFoXDTIzMTExNjIwMzYwMFowgf0xCzAJBgNVBAYTAkJSMRMwEQYDVQQKEwpJQ1AtQnJhc2lsMQswCQYDVQQIEwJTQzEbMBkGA1UEBxMSQmFsbmVhcmlvIFBpY2FycmFzMR4wHAYDVQQLExVBQyBDRVJUSUZJQ0EgTUlOQVMgdjUxFzAVBgNVBAsTDjE5MDQ2MjUxMDAwMTM1MRkwFwYDVQQLExBWaWRlb2NvbmZlcmVuY2lhMRowGAYDVQQLExFDZXJ0aWZpY2FkbyBQSiBBMTE/MD0GA1UEAxM2TFVDQVMgQVJFTkQgREVTRU5WT0xWSU1FTlRPIERFIFNPRlRXQVJFOjMyNTU5MjE2MDAwMTg4MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlngs0jPbyoOd050HFIdn0oOlhKviZpoAhDigNYp59joakW/el7oKVGjvO/8dKud0NNOw8jxBPGQ+ydBQlsT1QYCy373wrOxXhXJqdtKaNjWK9HCQDTHM8GLH7LVtosII9jmwAKtxbYGluQ/S5Y/EgTgg8gioN7qINkXx7SXPLC7vRA2NUNglR8v8kwG4S4FlGiCNyH6DM+g0Ip6JSghM0Pjz19tSA/cGweAWcs8UrIPntcRHVqZixLK6f9QKGFDJ6XYcVg6RH7ZU5xQUwBx2QyjpPG7Xr0xWjCrBr3ChU7JAcb8OgoFQxqIWycmMfAIBrJ59wwRoGRfqDMT2xOSZ1QIDAQABo4ICdzCCAnMwHwYDVR0jBBgwFoAUP9NcqRlN14gWLZgMrwre4U8kFrAwWQYIKwYBBQUHAQEETTBLMEkGCCsGAQUFBzAChj1odHRwOi8vY2NkLmFjc29sdXRpLmNvbS5ici9sY3IvYWMtY2VydGlmaWNhbWluYXMtc21pbWUtdjUucDdiMIGpBgNVHREEgaEwgZ6BFmx1Y2FzYXJlbmQ5OUBnbWFpbC5jb22gFgYFYEwBAwKgDRMLTFVDQVMgQVJFTkSgGQYFYEwBAwOgEBMOMzI1NTkyMTYwMDAxODigOAYFYEwBAwSgLxMtMTYwNTE5OTkwOTM0OTYyNDk5MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwoBcGBWBMAQMHoA4TDDAwMDAwMDAwMDAwMDBiBgNVHSAEWzBZMFcGBmBMAQIBYDBNMEsGCCsGAQUFBwIBFj9odHRwOi8vY2NkLmFjc29sdXRpLmNvbS5ici9kb2NzL2RwYy1hYy1jZXJ0aWZpY2FtaW5hcy1zbWltZS5wZGYwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUFBwMEMIGWBgNVHR8EgY4wgYswQ6BBoD+GPWh0dHA6Ly9jY2QuYWNzb2x1dGkuY29tLmJyL2xjci9hYy1jZXJ0aWZpY2FtaW5hcy1zbWltZS12NS5jcmwwRKBCoECGPmh0dHA6Ly9jY2QyLmFjc29sdXRpLmNvbS5ici9sY3IvYWMtY2VydGlmaWNhbWluYXMtc21pbWUtdjUuY3JsMB0GA1UdDgQWBBSfD8WZpG4dqgQcNeLkpauC+Ua6kTAOBgNVHQ8BAf8EBAMCBeAwDQYJKoZIhvcNAQELBQADggIBAGuV9y6lCVdfcfn+UCTVGzIJbB+AQk5TyC80RASWG4y44Ww0oRDv4F8yjki7mRl8t3dHG/L06lZmFExBQdbap5GIlivaT2fanDs6M3hZfLfUpWEWw0n0a8Y8D9pA+iZZvFGxcPDcKGSRkBzIX97Lxh7PtM5YLuqIBOQUBvKPfHqWwXfhigtrkYHJHxwij6GMs5GqyAuAbPTPijZAgb56DEwMplYBtu2ZBRVST6v7xwUMtFxbktt3Qb/To2x304Gj4LK+5LYe8ql6B/Eg1n5xSSh98bErcNMb14oYdapffAQT1F7UPxr236YZ+DJMevNlSyJzE/KaV6AZrpECVxSeOD4qCGeO+H8YBQPbVrlWT7hmpxNamzqua49vSQUJ0B6l/m3ujb9qUQOD5utLYc/TbaxOZLzwNqIFp0MYGVBIY93xtohPR3J7k6M5cMHz32jI9B/bJN0LydGbcpBQn+h2J0Nuub5k2k305tL/I3kKMPrhyBQD3VpdTIYbQjjoUqnM2IxjSRZJbcFVHkXKtCOp2CVpvm+b9jPotzsLDliuq1sdy2pC0ILLnOrZQeeRRNhKnU+/uotBRTlQK8aA8flG7eelI+I3KPM7tvfax7D0INKoQjnyyYCYwaCywLMm8Ux0QuKYeK6ExRBm0yNvdopVMGxZwCBF+wt02OluD4nTgl6W</X509Certificate></X509Data></KeyInfo></Signature></EnviarLoteRpsEnvio>]]></ns1:xml>
        </ns1:recepcionarLoteRpsLimitado>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

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
