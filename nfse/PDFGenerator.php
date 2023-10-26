<?php

namespace NFSePHP\NFSe;

use DOMDocument;
use DOMXPath;
use Mpdf\Mpdf;

class PDFGenerator extends Mpdf
{
    /**
     * @var $pathPdf
     */
    private $pathPdf;
    /**
     * @var $pathLogo
     */
    private string $pathLogo;

    public function __construct($pathPdf, $pathLogo)
    {
        $this->pathPdf = $pathPdf;
        $this->pathLogo = $pathLogo;
    }

    public function Danfse()
    {
        $xml = $this->pathPdf;
        $logo = $this->pathLogo;

        //Retirando Prefixo
        $xml = preg_replace('/<nfse:/', '<', $xml);
        $xml = preg_replace('/<\/nfse:/', '</', $xml);

        //Geração do PDF
        // Inicialize o objeto mPDF
        $mpdf = new Mpdf([
            'margin_left' => 5,   // Margem esquerda em px
            'margin_right' => 5,  // Margem direita em px
            'margin_top' => 8,    // Margem superior em px
            'margin_bottom' => 20, // Margem inferior em px
            'setAutoTopMargin' => 'stretch', // Defina a margem superior automaticamente para evitar quebras indesejadas
            'autoPageBreak' => 'auto', // Ative a quebra de página automática
        ]);

        // Inicialize o objeto DOMDocument para processar o XML com namespaces
        $dom = new DOMDocument();
        $dom->load($xml);

        // Use XPath para extrair os dados da NFSe com namespaces
        $xpath = new DOMXPath($dom);

        // Agora você pode usar XPath para obter os elementos da NFSe
        $numeroLote = $xpath->query('//NumeroLote')->item(0)->nodeValue;
        $dataEmissao = $xpath->query('//DataEmissao')->item(0)->nodeValue;

        // Crie o conteúdo do PDF
        $pdfContent = '';

        $pdfContent .= "<h1>Nota Fiscal de Serviços Eletrônicos</h1>";
        $pdfContent .= "Número da Nota: " . $numeroLote . "<br>";
        $pdfContent .= "Data de Emissão: " . $dataEmissao . "<br>";

        // Adicione outros dados da NFSe ao PDF conforme necessário

        // Adicione o conteúdo ao PDF
        $mpdf->WriteHTML($pdfContent);
    }
}
