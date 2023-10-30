<?php

namespace NFSePHP\NFSe;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DOMDocument;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use NFSePHP\NFSe\Traits\Getter;
use stdClass;

class Danfse extends Mpdf
{
    use Getter;
    /**
     * @var $xml
     */
    private $xml;
    /**
     * @var $logo
     */
    private $logo;
    /**
     * @var $logoname
     */
    private $logoname;

    /**
     * @var string $path
     */
    private string $path;

    /**
     * Função organiza os dados instanciados para realizar todas as ações
     * @param $xml
     * @param $logo
     */
    public function __construct($xml, $logo, $logoname, string $path)
    {
        $this->xml = $xml;
        $this->logo = $logo;
        $this->logoname = $logoname;
        $this->path = $path;
    }

    /**
     * @throws MpdfException
     */
    public function generatePDF()
    {
        $xmlElement = new DOMDocument();
        $xmlElement->loadXML($this->xml);
        $xmlObj = $this->xmlStringToObj($xmlElement->documentElement);
        $nameBody = 's:Body';
        $body = $xmlObj->$nameBody;

        $infNfse = $body->ConsultarLoteRpsResponse->ConsultarLoteRpsResult->ListaNfse->CompNfse->Nfse->InfNfse;
        $numero = $infNfse->Numero;
        $codigoVerificacao = $infNfse->CodigoVerificacao;
        $dataEmissao = $infNfse->DataEmissao;
        $ValoresNfse = $infNfse->ValoresNfse;
        $prestador = $infNfse->Prestador;
        $orgaoGerador = $infNfse->OrgaoGerador;

        $declaracaoServico = $infNfse->DeclaracaoPrestacaoServico->InfDeclaracaoPrestacaoServico;
        $rps = $declaracaoServico->Rps;
        $competencia = $declaracaoServico->Competencia;
        $servico = $declaracaoServico->Servico;
        $tomador = $declaracaoServico->Tomador;
        $regimeEspecialTributacao = $declaracaoServico->RegimeEspecialTributacao;
        $optanteSimplesNacional = $declaracaoServico->OptanteSimplesNacional;
        $incentivoFiscal = $declaracaoServico->IncentivoFiscal;

        $objXml = new stdClass();
        $objXml->numero = $numero;
        $objXml->codigoVerificacao = $codigoVerificacao;
        $objXml->dataEmissao = $dataEmissao;
        $objXml->valoresNfse = $ValoresNfse;
        $objXml->prestador = $prestador;
        $objXml->orgaoGerador = $orgaoGerador;
        $objXml->rps = $rps;
        $objXml->competencia = $competencia;
        $objXml->servico = $servico;
        $objXml->tomador = $tomador;
        $objXml->regimeEspecialTributacao = $regimeEspecialTributacao;
        $objXml->optanteSimplesNacional = $optanteSimplesNacional;
        $objXml->incentivoFiscal = $incentivoFiscal;


        $cidade = $this->GetMunicipioName($objXml->prestador->Endereco->CodigoMunicipio);
        $cidade = mb_strtoupper($cidade);

        $listaPadraoCidade = [
            'BLUMENAU' => 'SIMPLISS'
        ];
        $padrao = $listaPadraoCidade[$cidade];

        $file = __DIR__ . "/urlDanfse.xml";

        if (!file_exists($file)) {
            throw new \Exception('Caminho arquivo XML URL não encontrado!');
        }
        $xmlConfig = file_get_contents($file);
        $xmlUrl = simplexml_load_string($xmlConfig);
        $uf = $objXml->prestador->Endereco->Uf;
        $dadosPadrao = $xmlUrl->$padrao;
        $dadosEstado = $dadosPadrao->$uf;
        $dadosMunicipio = $dadosEstado->$cidade;
        $linkGeneratePdf = $dadosMunicipio->linkPDF[0];
        $linkGeneratePdf = preg_replace('/CNPJEMITENTE/', $objXml->prestador->IdentificacaoPrestador->CpfCnpj->Cnpj, $linkGeneratePdf);
        $linkGeneratePdf = preg_replace('/SERIENFSE/', $objXml->rps->IdentificacaoRps->Serie, $linkGeneratePdf);
        $linkGeneratePdf = preg_replace('/NUMNFSE/', $objXml->numero, $linkGeneratePdf);
        $linkGeneratePdf = preg_replace('/CODVERIFYNFSE/', $objXml->codigoVerificacao, $linkGeneratePdf);

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($linkGeneratePdf);
        $base64qr = base64_encode($qrCode);

        $imagemPath = base_path('packages/sped-nfse/nfse/img/'.mb_strtolower($cidade).'.png');
        $base64 = base64_encode(file_get_contents($imagemPath));
        $base64Logo = base64_encode($this->logo);

        $infoImage = getimagesize($this->logoname);
        $contentMime = 'image/png';
        if($infoImage !== false){
            $contentMime = $infoImage['mime'];
        }

        $content = '<!DOCTYPE>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pt" lang="pt">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>rel_nfse_v23</title>
    <style type="text/css"> * {
        margin: 0;
        padding: 0;
        text-indent: 0;
    }

    .s1 {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: bold;
        text-decoration: none;
        font-size: 8pt;
        line-height: 6pt;
    }

    .s2 {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: normal;
        text-decoration: none;
        font-size: 8pt;
        line-height: 6pt;
    }

    .s3 {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: bold;
        text-decoration: none;
        font-size: 8pt;
        line-height: 6pt;
    }

    .s4 {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: bold;
        text-decoration: none;
        font-size: 8.5pt;
        line-height: 6pt;
    }

    .s4-sm {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: bold;
        text-decoration: none;
        font-size: 7pt;
        line-height: 6pt;
    }

    .s5 {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: normal;
        text-decoration: none;
        font-size: 8.5pt;
        line-height: 6pt;
    }

    .s5-sm {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: normal;
        text-decoration: none;
        font-size: 7.5pt;
        line-height: 6pt;
    }

    .s7 {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: normal;
        text-decoration: none;
        font-size: 8pt;
        line-height: 6pt;
    }

    .s8 {
        color: black;
        font-family: Arial, sans-serif;
        font-style: normal;
        font-weight: bold;
        text-decoration: none;
        font-size: 8pt;
        line-height: 6pt;
    }

    table, tbody {
        vertical-align: top;
        overflow: visible;
    }
    </style>
</head>
<body><p style="text-indent: 0pt;text-align: right;"><br/></p>
<table style="border-collapse:collapse">
    <tr style="height:11pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-right-style:solid;border-right-width:1pt;border-left-style:solid;border-left-width:1pt;text-align: start;" colspan="4">
            <table>
                <tr>
                    <td>
                        <img src="data:image/png;base64,' . $base64 . '" alt="Brasão do Município" style="width: 80px; height: 80px;">
                    </td>
                    <td style="text-align: center">
                        <p class="s1">MUNICÍPIO DE BLUMENAU</p>
                        <p class="s1">SECRETARIA MUNICIPAL DA FAZENDA</p>
                        <p class="s1">DIRETORIA GERAL</p>
                        <p class="s1">DIRETORIA DE RECEITA</p>
                        <p><br></br></p>
                        <p><br></br></p>
                        <p class="s1">NOTA FISCAL DE SERVIÇOS ELETRÔNICA - NFS-E</p>
                    </td>
                </tr>
            </table>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;text-align: center;vertical-align: top" rowspan="7" >
            <table style="width: 100%">
                <tr>
                    <td>
                        <img src="data:image/png;base64,' . $base64qr . '" alt="QRCode Link PdfGenerator" style="width: 120px; height: 120px;">
                    </td>
                </tr>
            </table>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <table style="border-collapse: collapse; width: 100%;">
                <tbody>
                <tr>
                    <td colspan="2" style="border-bottom: solid 1pt black;text-align: center;">
                        <p class="s2">Número da Nota Fiscal</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border-bottom: solid 1pt black;text-align: center;">
                        <p class="s3">845</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border-bottom: solid 1pt black;text-align: center;">
                        <p class="s2">Série: <b>E</b></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border-bottom: solid 1pt black;text-align: center;">
                        <p class="s2">Data Emissão: <b>' . date('d/m/Y') . '</b></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border-bottom: solid 1pt black;text-align: center;">
                        <p class="s2">Certificação:</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <p class="s4">AE5047425</p>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr style="height:12pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;" colspan="4">
            <p class="s1">DADOS DO PRESTADOR</p>
        </td>
        <td style="border-right-style:solid;border-right-width:1pt;border-left-style:solid;border-left-width:1pt"></td>
    </tr>
    <tr style="height:65pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt" colspan="6">
            <table  style="width: 100%">
                <tr>
                    <td style="text-align: left; width: 100px">
                        <img src="data:' . $contentMime . ';base64,' . $base64Logo . '" alt="Logo da Empresa" style="width: 100px; height: 95px">
                    </td>
                    <td style="text-align: left">
                        <p class="s5">Nome/Razão Social: <b>CSX SOLUÇÕES LTDA</b></p>
                        <p class="s5">Nome Fantasia: <b>CSX SOLUÇÕES LTDA</b></p>
                        <p class="s5">CNPJ/CPF: <b>21.728.376/0001-97</b></p>
                        <p class="s5">Endereço: <b>BOTUVERA</b></p>
                        <p class="s5">Bairro: <b>ITOUPAVAZINHA</b></p>
                        <p class="s5">Municipio: <b>BLUMENAU </b> | País: <b>BRASIL</b></p>
                        <p class="s5">Insc. Municipal: <b>110675</b> | Insc. Estadual: <b>110675</b></p>
                    </td>
                    <td style="text-align: right">
                        <p class="s5">N°: <b>508</b></p>
                        <p class="s5">Complemento: <b>SALA 101</b></p>
                        <p class="s5">UF: <b>SC </b> | CEP: <b>89066-360</b></p>
                        <p class="s5">Telefone: <b>4733084515</b></p>
                        <p class="s5">E-mail: <b>nfe@csxsolucoes.com.br</b></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr style="height:12pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;" colspan="6">
            <p class="s1" style="padding-left: 193pt;padding-right: 188pt;text-indent: 0pt;line-height: 6pt;">DADOS DO TOMADOR</p>
        </td>
    </tr>
    <tr style="height:65pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt" colspan="6">
            <table style="width: 100%">
                <tr>
                    <td style="text-align: left">
                        <p class="s5">Nome/Razão Social: <b>CSX SOLUÇÕES LTDA</b></p>
                        <p class="s5">Nome Fantasia: <b>CSX SOLUÇÕES LTDA</b></p>
                        <p class="s5">CNPJ/CPF: <b>21.728.376/0001-97</b></p>
                        <p class="s5">Endereço: <b>BOTUVERA</b></p>
                        <p class="s5">Bairro: <b>ITOUPAVAZINHA</b></p>
                        <p class="s5">Municipio: <b>BLUMENAU </b> | País: <b>BRASIL</b></p>
                        <p class="s5">Insc. Municipal: <b>110675</b> | Insc. Estadual: <b>110675</b></p>
                    </td>
                    <td style="text-align: right">
                        <p class="s5">N°: <b>508</b></p>
                        <p class="s5">Complemento: <b>SALA 101</b></p>
                        <p class="s5">UF: <b>SC </b> | CEP: <b>89066-360</b></p>
                        <p class="s5">Telefone: <b>4733084515</b></p>
                        <p class="s5">E-mail: <b>nfe@csxsolucoes.com.br</b></p>
                    </td>
                </tr>
            </table>
    </tr>
    <tr style="height:12pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;" colspan="6">
            <p class="s1">DISCRIMINAÇÃO DO SERVIÇO</p>
        </td>
    </tr>
    <tr style="height:182pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt" colspan="6">
            <p class="s7">DESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00aDESENVOLVIMENTO DE SISTEMA - OS001224 - R$ 350,00a</p>
        </td>
    </tr>
    <tr style="height:20pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: right;" colspan="5">
            <p class="s4-sm">VALOR BRUTO DA NOTA</p>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: right;">
            <p class="s4">R$ 350,00</p>
        </td>
    </tr>
    <tr style="height:22pt">
        <td style="width: 125px;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">Valor Total das Deduções:</p>
            <p class="s1">R$ 0,00</p>
        </td>
        <td style="width: 125px;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">Desconto Incondicionado:</p>
            <p class="s1">R$ 0,00</p>
        </td>
        <td style="width: 125px;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">Desconto Condicionado:</p>
            <p class="s1">R$ 0,00</p>
        </td>
        <td style="width: 125px;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">Base de Cálculo:</p>
            <p class="s1">R$ 350,00</p>
        </td>
        <td style="width: 125px;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">Alíquota:</p>
            <p class="s1">2,0000%</p>
        </td>
        <td style="width: 125px;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">Valor do ISS:</p>
            <p class="s1">R$ 7,00</p>
        </td>
    </tr>
    <tr style="height:22pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">PIS: 0,000%</p>
            <p class="s1">R$ 0,00</p>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">COFINS: 0,000%</p>
            <p class="s1">R$ 0,00</p>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">INSS: 0,000%</p>
            <p class="s1">R$ 0,00</p>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">IR: 0,000%</p>
            <p class="s1">R$ 0,00</p>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">CSLL: 0,000%</p>
            <p class="s1">R$ 0,00</p>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;">
            <p class="s5">Outras Retenções:</p>
            <p class="s1">R$ 0,00</p>
        </td>
    </tr>
    <tr style="height:14pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;" colspan="4" >
            <p class="s8">Valor Aproximado dos tributos R$ 0,00</p>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: right;">
            <p class="s4-sm">VALOR LÍQUIDO DA NOTA</p>
        </td>
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: right;">
            <p class="s4">R$ 350,00</p>
        </td>
    </tr>
    <tr style="height:9pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;" colspan="6">
            <p class="s1">ENQUADRAMENTO DO SERVIÇO</p>
        </td>
    </tr>
    <tr style="height:38pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;" colspan="6">
            <p><br/></p>
            <p class="s5-sm">Atividade: 1.07 - Suporte técnico em informática, inclusive instalação, configuração e manutenção de programas de computação e bancos de dados.</p>
        </td>
    </tr>
    <tr style="height:12pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;" colspan="6">
            <p class="s1">OUTRAS INFORMAÇÕES</p>
        </td>
    </tr>
    <tr style="height:93pt">
        <td style="border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: left;" colspan="6">
            <table style="width: 100%">
                <tr>
                    <td>
                        <p class="s5">Mês de Competência: <b>05/2023 </b></p>
                        <p class="s5">Recolhimento: <b>Sem Retenção </b></p>
                        <p class="s5">CNAE: <b>9511800 Empresa Optante do Simples Nacional</b></p>
                    </td>
                    <td>
                        <p class="s5">Local do Recolhimento: <b>BLUMENAU/SC</b></p>
                        <p class="s5">Tributação: <b>Microempresário e Empresade Pequeno Porte (ME EPP)</b></p>
                    </td>
                    <td>
                        <p class="s5">Data Geração: <b>26/05/2023 15:03:46</b></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p style="text-indent: 0pt;text-align: left;"><br/></p>
                        <p style="text-indent: 0pt;text-align: left;"><br/></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p class="s5-sm">Impresso: ' . date('d/m/Y') . ' às ' . date('H:i:s') . ' por: ' . strtoupper(env('APP_NAME')) . '</p>
                    </td>
                    <td colspan="2" style="text-align: right; vertical-align: bottom">
                        <p class="s7">O conteúdo deste documento fiscal é de inteira responsabilidade do emissor.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table style="border-collapse:collapse; margin-top:10pt">
    <tr style="height:93pt">
        <td style="width: 500px;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt" colspan="6">
            <p class="s5">Recebi(emos) de: CSX SOLUÇÕES LTDA</p>
            <p class="s5">Os serviços constantes nesta Nota Fiscal de Serviços Eletrônica.</p>
            <p><br/></p>
            <table style="width: 100%">
                <tr>
                    <td style="text-align: center">
                        <p class="s5">________/_____/________</p>
                        <p class="s5">Data:</p>
                    </td>
                    <td style="text-align: center">
                        <p class="s5">____________________________________________________</p>
                        <p class="s5">Assinatura do Recebedor:</p>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 250px;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt;border-bottom-style:solid;border-bottom-width:1pt;border-right-style:solid;border-right-width:1pt;text-align: center;vertical-align: middle" colspan="6">
            <p class="s5">NOTA FISCAL DE SERVIÇOS ELETRÔNICA</p>
            <p class="s5">Número: 845</p>
            <p class="s5">Certificação</p>
            <p class="s5">AE5047425</p>
        </td>
    </tr>
</table>
</body>
</html>
';


        // Crie uma instância da classe Mpdf
        $mpdf = new Mpdf([
            'margin_left' => 5,   // Margem esquerda em px
            'margin_right' => 5,  // Margem direita em px
            'margin_top' => 5,    // Margem superior em px
            'margin_bottom' => 5, // Margem inferior em px
        ]);

        $mpdf->autoPageBreak = false;
        // Adicione o conteúdo ao PDF
        $mpdf->WriteHTML($content);

        return $mpdf->Output($this->path, 'F');
    }


    private function xmlStringToObj($element)
    {
        $obj = new stdClass();

        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attribute) {
                $obj->{$attribute->name} = $attribute->value;
            }
        }

        if ($element->hasChildNodes()) {
            $textValue = '';

            foreach ($element->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $textValue = trim($child->nodeValue);
                } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                    $childName = $child->nodeName;
                    $childValue = $this->xmlStringToObj($child);

                    if (property_exists($obj, $childName)) {
                        if (!is_array($obj->{$childName})) {
                            $existingValue = $obj->{$childName};
                            $obj->{$childName} = [$existingValue];
                        }
                        $obj->{$childName}[] = $childValue;
                    } else {
                        $obj->{$childName} = $childValue;
                    }
                }
            }

            if ($textValue !== '') {
                $obj = $textValue;
            }
        }

        return $obj;
    }

}
