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

namespace NFSePHP\NFSe;

use DOMDocument;
use Matrix\Exception;
use stdClass;
use NFSePHP\NFSe\DOMtools as Dom;

class Nfse extends DOMDocument
{
    /**
     * @var array
     */
    public $errors = [];
    /**
     * @var DOMtools
     */
    public $dom;
    /**
     * @var \DOMElement
     */
    protected $nfse;
    /**
     * @var \DOMElement
     */
    protected $nfseId;
    /**
     * @var \DOMElement
     */
    protected $infNfse;
    /**
     * @var \DOMElement
     */
    protected $versao;
    /**
     * @var \DOMElement
     */
    protected $orgaoGerador;
    /**
     * @var \DOMElement
     */
    protected $prestador;
    /**
     * @var \DOMElement
     */
    protected $servico;
    /**
     * @var \DOMElement
     */
    protected $tomador;
    /**
     * @var \DOMElement
     */
    protected $loteRps;
    /**
     * @var string $Id
     */
    protected $Id;
    /**
     * @var string $serie ;
     */
    protected $serie;
    /**
     * @var string $prefixoNfse
     */
    protected string $prefixoNfse;

    protected \DOMElement $identificacaoRps;

    /**
     * @var array
     */
    protected array $padroes = [
        'BLUMENAU' => 'SIMPLISS',
        'GASPAR' => 'BETA11',
        'LAGES' => 'BETA2'
    ];
    /**
     * @var string
     */
    protected string $cidadePrestador;
    /**
     * @var string
     */
    private string $padraoEscolha;

    private array $cidadePrefixo = ['BLUMENAU'];

    /**
     * Construtor recebe o objeto da nota fiscal com todas as informações e instancia um novo DOM Document
     * @param String $versao
     * @param String $charset
     * @throws \Exception
     */
    public function __construct(string $versao, string $charset, string $cidadePrestador)
    {
        $this->dom = new Dom('1.0', $charset);
        $this->versao = $versao;
        $this->cidadePrestador = $cidadePrestador;
        $this->definePrefixo();
        $this->definePadrao();
    }

    public function monta(): string
    {
        if (!empty($this->errors)) {
            $this->errors = array_merge($this->errors, $this->dom->errors);
        } else {
            $this->errors = $this->dom->errors;
        }
        //Cria a tag Rps sem Id
        $this->buildRps(false);
        //Adiciona node infDeclaracaoPrestacaoServico com id ao node Rps sem Id
        $this->dom->appChild($this->nfse, $this->infDeclaracaoPrestacaoServico, 'Falta tag "infDeclaracaoPrestacaoServico"');
        $assinar = $this->dom->createElement('assinar');
        //Adiciona tag location pra assinatura
        $this->dom->appChild($this->nfse, $assinar, 'Falta tag "infDeclaracaoPrestacaoServico"');
        //Cria Node Lista Rps
        $ListaRps = $this->dom->createElement($this->prefixoNfse . 'ListaRps');
        //Adiciona Node Rps sem id ao node ListaRps
        $this->dom->appChild($ListaRps, $this->nfse, 'Falta tag "ListaRps"');
        //Adiciona node ListaRps ao node LoteRps
        $this->dom->appChild($this->loteRps, $ListaRps, 'Falta tag "loteRps"');

        $this->dom->appendChild($this->loteRps);
        $this->dom->formatOutput = true;
        $this->xml = $this->dom->saveXML();

        return $this->xml;
    }

    private function buildRps($flagID): \DOMElement
    {
        if ($flagID) {
            if (!$this->nfseId) {
                $this->nfseId = $this->dom->createElement($this->prefixoNfse . 'Rps');
                $this->nfseId->setAttribute('Id', $this->Id);
                return $this->nfseId;
            }
        }
        if (!$this->nfse) {
            $this->nfse = $this->dom->createElement($this->prefixoNfse . 'Rps');
        }
        return $this->nfse;
    }

    /**
     * @throws \DOMException
     */
    private function buildIdentificacaoRps()
    {
        $this->identificacaoRps = $this->dom->createElement($this->prefixoNfse . 'IdentificacaoRps');
        $this->dom->addChild(
            $this->identificacaoRps,
            $this->prefixoNfse . 'Numero',
            $this->Id,
            true,
            'Numero da NFSe'
        );
        $this->dom->addChild(
            $this->identificacaoRps,
            $this->prefixoNfse . 'Serie',
            $this->serie,
            true,
            'Serie da NFSe'
        );
        $this->dom->addChild(
            $this->identificacaoRps,
            $this->prefixoNfse . 'Tipo',
            1,
            true,
            'Numero da NFSe'
        );
    }

    private function definePrefixo(): void
    {
        if(in_array($this->cidadePrestador, $this->cidadePrefixo)){
            $this->prefixoNfse = "nfse:";
        }else {
            $this->prefixoNfse = "";
        }
    }

    /**
     * @throws \Exception
     */
    private function definePadrao(): void
    {
        if (array_key_exists($this->cidadePrestador, $this->padroes)) {
            $this->padraoEscolha = $this->padroes[$this->cidadePrestador];
        } else {
            throw new \Exception('Escolha pelo menos um padrão para montagem do xml');
        }
    }

    public function taginfNfse(stdClass $std): \DOMElement
    {
        $this->infNfse = $this->dom->createElement($this->prefixoNfse . 'LoteRps');
        $this->infNfse->setAttribute('Id', $std->Id);
        $this->infNfse->setAttribute('versao', $this->versao);
        $this->Id = $std->Id;
        $this->serie = $std->serie;

        return $this->infNfse;
    }

    public function tagOrgaoGerador(stdClass $std): \DOMElement
    {
        $this->orgaoGerador = $this->dom->createElement('OrgaoGerador');
        $this->dom->addChild(
            $this->orgaoGerador,
            'CodigoMunicipio',
            $std->mp,
            true,
            'Codigo do Municipio que está sendo gerada a NFSe'
        );
        $this->dom->addChild(
            $this->orgaoGerador,
            'Uf',
            $std->uf,
            true,
            'Municipio que está sendo gerada a NFSe'
        );

        return $this->orgaoGerador;
    }

    public function tagServico(stdClass $std): \DOMElement
    {
        switch ($this->padraoEscolha){
            case "SIMPLISS":
                $this->servico = $this->dom->createElement($this->prefixoNfse . 'Servico');
                $valores = $this->dom->createElement($this->prefixoNfse . 'Valores');
                $this->cidadePrestador = $std->cidadePrestador;
                //Adiciona os nodes dentro do nove valores
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorServicos',
                    $std->vLiq,
                    true,
                    'Valor Líquido',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorDeducoes',
                    $std->vDed,
                    true,
                    'Valor Deduções',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorPis',
                    $std->vPis,
                    true,
                    'Valor Pis',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorCofins',
                    $std->vCofins,
                    true,
                    'Valor Cofins',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorInss',
                    $std->vInss,
                    true,
                    'Valor Valor Inss',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorIr',
                    $std->vIr,
                    true,
                    'Valor Imposto de renda',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorCsll',
                    $std->vCsll,
                    true,
                    'Valor Csll',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'OutrasRetencoes',
                    $std->vOutrasRetencoes,
                    true,
                    'Valor Outras retenções',
                );
                $valorTotalTributos = $std->vDed +
                    $std->vPis +
                    $std->vCofins +
                    $std->vInss +
                    $std->vIr +
                    $std->vCsll +
                    $std->vOutrasRetencoes +
                    $std->vIss;
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValTotTributos',
                    $valorTotalTributos,
                    true,
                    'Valor Total dos tributos',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorIss',
                    $std->vIss,
                    true,
                    'Valor resultante imposto ISS',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'Aliquota',
                    $std->aIss,
                    true,
                    'Alíquota Imposto ISS',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'DescontoIncondicionado',
                    $std->descIncondicionado,
                    true,
                    'Desconto incondicionado',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'DescontoCondicionado',
                    $std->descCondicionado,
                    true,
                    'Desconto condicionado',
                );

                //Adiciona node valores no node serviço
                $this->dom->appChild($this->servico, $valores, 'Falta tag Serviço');
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'IssRetido',
                    $std->issRetido,
                    true,
                    'Choice Iss Retido, seguindo o padrão 1 Sim, 2 Nao'
                );
                if ($std->issRetido == "1") {
                    $this->dom->addChild(
                        $this->servico,
                        $this->prefixoNfse . 'ValorIssRetido',
                        $std->vIss,
                        false,
                        'Valor ISS Retido caso IssRetido seja 1'
                    );
                    $this->dom->addChild(
                        $this->servico,
                        $this->prefixoNfse . 'ResponsavelRetencao',
                        $std->responsavelRetencao,
                        false,
                        'Responsável Retenção 1 tomador 2 intermediario'
                    );
                }
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'ItemListaServico',
                    $std->itemListaServico,
                    true,
                    'Código de item da lista de serviço'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoCnae',
                    $std->codigoCnae,
                    true,
                    'Código CNAE'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoTributacaoMunicipio',
                    $std->codigoTributacaoMunicipio,
                    true,
                    'Código de Tributação'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'Discriminacao',
                    $std->discriminacao,
                    true,
                    'Discriminação do conteúdo da NFS-e'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoMunicipio',
                    $std->codigoMunicipio,
                    true,
                    'Código de identificação do município conforme tabela do IBGE. Preencher com 5 noves para serviço prestado no exterior'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoPais',
                    $std->codigoPais,
                    true,
                    'Código de identificação do município conforme tabela do IBGE. Preencher com 5 noves para serviço prestado no exterior'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'ExigibilidadeISS',
                    $std->exigibilidadeISS,
                    true,
                    '1 – Exigível, 2 – Não Incidência, 3 – Isenção, 4 – Exportação, 5 – Imunidade, 6 – Exigibilidade suspensa por decisão judicial, 7 – Exigibilidade suspensa por processo administrativo'
                );
                /*$this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse.'OutrasInformacoes',
                    $std->outrasInformacoes,
                    true,
                    'Outras informações do Serviço'
                );*/
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'MunicipioIncidencia',
                    $std->municipioIncidencia,
                    false,
                    'Caso exigibilidade seja diferente de 2, 5, 6 e 7'
                );
            case 'BETA11':
                $this->servico = $this->dom->createElement($this->prefixoNfse . 'Servico');
                $valores = $this->dom->createElement($this->prefixoNfse . 'Valores');
                $this->cidadePrestador = $std->cidadePrestador;
                //Adiciona os nodes dentro do nove valores
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorServicos',
                    $std->vLiq,
                    true,
                    'Valor Líquido',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorDeducoes',
                    $std->vDed,
                    true,
                    'Valor Deduções',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorPis',
                    $std->vPis,
                    true,
                    'Valor Pis',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorCofins',
                    $std->vCofins,
                    true,
                    'Valor Cofins',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorInss',
                    $std->vInss,
                    true,
                    'Valor Valor Inss',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorIr',
                    $std->vIr,
                    true,
                    'Valor Imposto de renda',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorCsll',
                    $std->vCsll,
                    true,
                    'Valor Csll',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'IssRetido',
                    $std->issRetido,
                    true,
                    'Choice Iss Retido, seguindo o padrão 1 Sim, 2 Nao',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorIss',
                    $std->vIss,
                    true,
                    'Valor resultante imposto ISS',
                );
                $this->dom->addChild(
                $valores,
                $this->prefixoNfse . 'ValorIssRetido',
                0,
                true,
                'Valor resultante imposto ISS retido',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'OutrasRetencoes',
                    $std->vOutrasRetencoes,
                    true,
                    'Valor Outras retenções',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'BaseCalculo',
                    $std->vLiq,
                    true,
                    'Base de Calculo',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'Aliquota',
                    $std->aIss/100,
                    true,
                    'Alíquota Imposto ISS',
                );
                $valorTotalTributos = $std->vDed +
                    $std->vPis +
                    $std->vCofins +
                    $std->vInss +
                    $std->vIr +
                    $std->vCsll +
                    $std->vOutrasRetencoes +
                    $std->vIss;
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorLiquidoNfse',
                    $valorTotalTributos,
                    true,
                    'Valor Total dos tributos',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'DescontoIncondicionado',
                    $std->descIncondicionado,
                    true,
                    'Desconto incondicionado',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'DescontoCondicionado',
                    $std->descCondicionado,
                    true,
                    'Desconto condicionado',
                );

                //Adiciona node valores no node serviço
                $this->dom->appChild($this->servico, $valores, 'Falta tag Serviço');

                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'ItemListaServico',
                    str_replace('.', '', $std->itemListaServico),
                    true,
                    'Código de item da lista de serviço'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoCnae',
                    $std->codigoCnae,
                    true,
                    'Código CNAE'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoTributacaoMunicipio',
                    0,
                    true,
                    'Código de Tributação'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'Discriminacao',
                    $std->discriminacao,
                    true,
                    'Discriminação do conteúdo da NFS-e'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoMunicipio',
                    $std->codigoMunicipio,
                    true,
                    'Código de identificação do município conforme tabela do IBGE. Preencher com 5 noves para serviço prestado no exterior'
                );
            case "BETA2":
                $this->servico = $this->dom->createElement($this->prefixoNfse . 'Servico');
                $valores = $this->dom->createElement($this->prefixoNfse . 'Valores');
                $this->cidadePrestador = $std->cidadePrestador;
                //Adiciona os nodes dentro do nove valores
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorServicos',
                    $std->vLiq,
                    true,
                    'Valor Líquido',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorDeducoes',
                    $std->vDed,
                    true,
                    'Valor Deduções',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorPis',
                    $std->vPis,
                    true,
                    'Valor Pis',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorCofins',
                    $std->vCofins,
                    true,
                    'Valor Cofins',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorInss',
                    $std->vInss,
                    true,
                    'Valor Valor Inss',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorIr',
                    $std->vIr,
                    true,
                    'Valor Imposto de renda',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorCsll',
                    $std->vCsll,
                    true,
                    'Valor Csll',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'OutrasRetencoes',
                    $std->vOutrasRetencoes,
                    true,
                    'Valor Outras retenções',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'ValorIss',
                    $std->vIss,
                    true,
                    'Valor resultante imposto ISS',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'Aliquota',
                    $std->aIss,
                    true,
                    'Alíquota Imposto ISS',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'DescontoIncondicionado',
                    $std->descIncondicionado,
                    true,
                    'Desconto incondicionado',
                );
                $this->dom->addChild(
                    $valores,
                    $this->prefixoNfse . 'DescontoCondicionado',
                    $std->descCondicionado,
                    true,
                    'Desconto condicionado',
                );

                //Adiciona node valores no node serviço
                $this->dom->appChild($this->servico, $valores, 'Falta tag Serviço');
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'IssRetido',
                    $std->issRetido,
                    true,
                    'Choice Iss Retido, seguindo o padrão 1 Sim, 2 Nao'
                );
                if ($std->issRetido == "1") {
                    $this->dom->addChild(
                        $this->servico,
                        $this->prefixoNfse . 'ValorIssRetido',
                        $std->vIss,
                        false,
                        'Valor ISS Retido caso IssRetido seja 1'
                    );
                    $this->dom->addChild(
                        $this->servico,
                        $this->prefixoNfse . 'ResponsavelRetencao',
                        $std->responsavelRetencao,
                        false,
                        'Responsável Retenção 1 tomador 2 intermediario'
                    );
                }
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'ItemListaServico',
                    $std->itemListaServico,
                    true,
                    'Código de item da lista de serviço'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoCnae',
                    $std->codigoCnae,
                    true,
                    'Código CNAE'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoTributacaoMunicipio',
                    $std->codigoTributacaoMunicipio,
                    true,
                    'Código de Tributação'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'Discriminacao',
                    $std->discriminacao,
                    true,
                    'Discriminação do conteúdo da NFS-e'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoMunicipio',
                    $std->codigoMunicipio,
                    true,
                    'Código de identificação do município conforme tabela do IBGE. Preencher com 5 noves para serviço prestado no exterior'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'CodigoPais',
                    $std->codigoPais,
                    true,
                    'Código de identificação do município conforme tabela do IBGE. Preencher com 5 noves para serviço prestado no exterior'
                );
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'ExigibilidadeISS',
                    $std->exigibilidadeISS,
                    true,
                    '1 – Exigível, 2 – Não Incidência, 3 – Isenção, 4 – Exportação, 5 – Imunidade, 6 – Exigibilidade suspensa por decisão judicial, 7 – Exigibilidade suspensa por processo administrativo'
                );
                /*$this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse.'OutrasInformacoes',
                    $std->outrasInformacoes,
                    true,
                    'Outras informações do Serviço'
                );*/
                $this->dom->addChild(
                    $this->servico,
                    $this->prefixoNfse . 'MunicipioIncidencia',
                    $std->municipioIncidencia,
                    false,
                    'Caso exigibilidade seja diferente de 2, 5, 6 e 7'
                );
        }
        return $this->servico;
    }

    public function tagPrestador(stdClass $std): \DOMElement
    {
        $this->prestador = $this->dom->createElement($this->prefixoNfse . 'Prestador');
        switch ($this->padraoEscolha){
            case 'SIMPLISS':
                $cpfCnpj = $this->dom->createElement($this->prefixoNfse . 'CpfCnpj');
                if (strlen($std->cpfCnpj) == 14) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cpfCnpj,
                        true,
                        'Cnpj do prestador do serviço'
                    );
                } elseif (strlen($std->cpf) == 11) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'Cpf do prestador do serviço'
                    );
                } else {
                    throw new \Exception('Informe um CPF/CNPJ válido para o Tomador');
                }
                $this->dom->appChild($this->prestador, $cpfCnpj, 'Falta tag Prestador');
                $this->dom->addChild(
                    $this->prestador,
                    $this->prefixoNfse . 'InscricaoMunicipal',
                    $std->inscricaoMunicipal,
                    true,
                    'Inscrição municipal do Prestador de serviço'
                );
            case 'BETA11':
                if (strlen($std->cpfCnpj) == 14) {
                    $this->dom->addChild(
                        $this->prestador,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cpfCnpj,
                        true,
                        'Cnpj do prestador do serviço'
                    );
                } elseif (strlen($std->cpf) == 11) {
                    $this->dom->addChild(
                        $this->prestador,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'Cpf do prestador do serviço'
                    );
                } else {
                    throw new \Exception('Informe um CPF/CNPJ válido para o Tomador');
                }
                $this->dom->addChild(
                    $this->prestador,
                    $this->prefixoNfse . 'InscricaoMunicipal',
                    $std->inscricaoMunicipal,
                    true,
                    'Inscrição municipal do Prestador de serviço'
                );
            case 'BETA2':
                $cpfCnpj = $this->dom->createElement($this->prefixoNfse . 'CpfCnpj');
                if (strlen($std->cpfCnpj) == 14) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cpfCnpj,
                        true,
                        'Cnpj do prestador do serviço'
                    );
                } elseif (strlen($std->cpf) == 11) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'Cpf do prestador do serviço'
                    );
                } else {
                    throw new \Exception('Informe um CPF/CNPJ válido para o Tomador');
                }
                $this->dom->appChild($this->prestador, $cpfCnpj, 'Falta tag Prestador');
                $this->dom->addChild(
                    $this->prestador,
                    $this->prefixoNfse . 'InscricaoMunicipal',
                    $std->inscricaoMunicipal,
                    true,
                    'Inscrição municipal do Prestador de serviço'
                );
        }



        return $this->prestador;
    }

    public function tagTomador(stdClass $std): \DOMElement
    {
        switch ($this->padraoEscolha){
            case 'SIMPLISS':
                $this->tomador = $this->dom->createElement($this->prefixoNfse . 'Tomador');
                $identificacaoTomador = $this->dom->createElement($this->prefixoNfse . 'IdentificacaoTomador');
                $cpfCnpj = $this->dom->createElement($this->prefixoNfse . 'CpfCnpj');
                //Adição das informações node CpfCnpj
                if (strlen($std->cpfCnpj) > 11) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cpfCnpj,
                        true,
                        'Cnpj do tomador do serviço'
                    );
                } elseif (strlen($std->cpf) == 11) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'Cpf do tomador do serviço'
                    );
                } else {
                    throw new \Exception('Informe um CPF/CNPJ válido para o Tomador');
                }

                //Adição node CpfCnpj ao node IdentificacaoTomador
                $this->dom->appChild($identificacaoTomador, $cpfCnpj, 'Falta Tag IdentificacaoTomador');

                $this->dom->addChild(
                    $identificacaoTomador,
                    $this->prefixoNfse . 'InscricaoMunicipal',
                    $std->inscricaoMunicipal,
                    true,
                    'Inscrição municipal do Tomador de serviço'
                );

                //Adição node IdentificacaoTomador ao node Tomador
                $this->dom->appChild($this->tomador, $identificacaoTomador, 'Falta tag Tomador');

                $this->dom->addChild(
                    $this->tomador,
                    $this->prefixoNfse . 'RazaoSocial',
                    htmlspecialchars($std->razaosocial),
                    true,
                    'Razão Social tomador de serviço'
                );
                //Geração node Endereço
                $this->enderTomador = $this->dom->createElement($this->prefixoNfse . 'Endereco');
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Endereco',
                    $std->logradouro,
                    true,
                    'Logradouro do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Numero',
                    $std->numero,
                    true,
                    'Número do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Complemento',
                    $std->complemento,
                    true,
                    'Complemento do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Bairro',
                    $std->bairro,
                    true,
                    'Bairro do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'CodigoMunicipio',
                    $std->codMP,
                    true,
                    'Código do Município do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Uf',
                    $std->uf,
                    true,
                    'UF do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'CodigoPais',
                    $std->codPais,
                    true,
                    'Código do Pais do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Cep',
                    $std->cep,
                    true,
                    'Cep do tomador de serviço'
                );
                //Adição node Endereco ao node Tomador
                $this->dom->appChild($this->tomador, $this->enderTomador, 'Falta tag Tomador');

                //Geração Node Contato
                $this->contatoTomador = $this->dom->createElement($this->prefixoNfse . 'Contato');
                $this->dom->addChild(
                    $this->contatoTomador,
                    $this->prefixoNfse . 'Telefone',
                    $std->telefone,
                    true,
                    'Telefone do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->contatoTomador,
                    $this->prefixoNfse . 'Email',
                    $std->email,
                    true,
                    'E-mail do tomador de serviço'
                );

                //Adição node Contato ao node Tomador
                $this->dom->appChild($this->tomador, $this->contatoTomador, 'Falta tag Tomador');
            case 'BETA11':
                $this->tomador = $this->dom->createElement($this->prefixoNfse . 'Tomador');
                $identificacaoTomador = $this->dom->createElement($this->prefixoNfse . 'IdentificacaoTomador');
                $cpfCnpj = $this->dom->createElement($this->prefixoNfse . 'CpfCnpj');
                //Adição das informações node CpfCnpj
                if (strlen($std->cpfCnpj) > 11) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cpfCnpj,
                        true,
                        'Cnpj do tomador do serviço'
                    );
                } elseif (strlen($std->cpf) == 11) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'Cpf do tomador do serviço'
                    );
                } else {
                    throw new \Exception('Informe um CPF/CNPJ válido para o Tomador');
                }

                //Adição node CpfCnpj ao node IdentificacaoTomador
                $this->dom->appChild($identificacaoTomador, $cpfCnpj, 'Falta Tag IdentificacaoTomador');

                //Adição node IdentificacaoTomador ao node Tomador
                $this->dom->appChild($this->tomador, $identificacaoTomador, 'Falta tag Tomador');

                $this->dom->addChild(
                    $this->tomador,
                    $this->prefixoNfse . 'RazaoSocial',
                    htmlspecialchars($std->razaosocial),
                    true,
                    'Razão Social tomador de serviço'
                );
                //Geração node Endereço
                $this->enderTomador = $this->dom->createElement($this->prefixoNfse . 'Endereco');
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Endereco',
                    $std->logradouro,
                    true,
                    'Logradouro do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Numero',
                    $std->numero,
                    true,
                    'Número do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Bairro',
                    $std->bairro,
                    true,
                    'Bairro do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'CodigoMunicipio',
                    $std->codMP,
                    true,
                    'Código do Município do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Uf',
                    $std->uf,
                    true,
                    'UF do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Cep',
                    $std->cep,
                    true,
                    'Cep do tomador de serviço'
                );
                //Adição node Endereco ao node Tomador
                $this->dom->appChild($this->tomador, $this->enderTomador, 'Falta tag Tomador');
            case 'BETA2':
                $this->tomador = $this->dom->createElement($this->prefixoNfse . 'Tomador');
                $identificacaoTomador = $this->dom->createElement($this->prefixoNfse . 'IdentificacaoTomador');
                $cpfCnpj = $this->dom->createElement($this->prefixoNfse . 'CpfCnpj');
                //Adição das informações node CpfCnpj
                if (strlen($std->cpfCnpj) > 11) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cpfCnpj,
                        true,
                        'Cnpj do tomador do serviço'
                    );
                } elseif (strlen($std->cpf) == 11) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'Cpf do tomador do serviço'
                    );
                } else {
                    throw new \Exception('Informe um CPF/CNPJ válido para o Tomador');
                }

                //Adição node CpfCnpj ao node IdentificacaoTomador
                $this->dom->appChild($identificacaoTomador, $cpfCnpj, 'Falta Tag IdentificacaoTomador');

                $this->dom->addChild(
                    $identificacaoTomador,
                    $this->prefixoNfse . 'InscricaoMunicipal',
                    $std->inscricaoMunicipal,
                    true,
                    'Inscrição municipal do Tomador de serviço'
                );

                //Adição node IdentificacaoTomador ao node Tomador
                $this->dom->appChild($this->tomador, $identificacaoTomador, 'Falta tag Tomador');

                $this->dom->addChild(
                    $this->tomador,
                    $this->prefixoNfse . 'RazaoSocial',
                    htmlspecialchars($std->razaosocial),
                    true,
                    'Razão Social tomador de serviço'
                );
                //Geração node Endereço
                $this->enderTomador = $this->dom->createElement($this->prefixoNfse . 'Endereco');
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Endereco',
                    $std->logradouro,
                    true,
                    'Logradouro do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Numero',
                    $std->numero,
                    true,
                    'Número do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Complemento',
                    $std->complemento,
                    true,
                    'Complemento do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Bairro',
                    $std->bairro,
                    true,
                    'Bairro do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'CodigoMunicipio',
                    $std->codMP,
                    true,
                    'Código do Município do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Uf',
                    $std->uf,
                    true,
                    'UF do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'CodigoPais',
                    $std->codPais,
                    true,
                    'Código do Pais do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->enderTomador,
                    $this->prefixoNfse . 'Cep',
                    $std->cep,
                    true,
                    'Cep do tomador de serviço'
                );
                //Adição node Endereco ao node Tomador
                $this->dom->appChild($this->tomador, $this->enderTomador, 'Falta tag Tomador');

                //Geração Node Contato
                $this->contatoTomador = $this->dom->createElement($this->prefixoNfse . 'Contato');
                $this->dom->addChild(
                    $this->contatoTomador,
                    $this->prefixoNfse . 'Telefone',
                    $std->telefone,
                    true,
                    'Telefone do tomador de serviço'
                );
                $this->dom->addChild(
                    $this->contatoTomador,
                    $this->prefixoNfse . 'Email',
                    $std->email,
                    true,
                    'E-mail do tomador de serviço'
                );

                //Adição node Contato ao node Tomador
                $this->dom->appChild($this->tomador, $this->contatoTomador, 'Falta tag Tomador');
        }

        return $this->tomador;
    }

    public function tagDeclaracaoPrestacaoServico(stdClass $std): \DOMElement
    {
        switch($this->padraoEscolha){
            case "SIMPLISS":
                $this->infDeclaracaoPrestacaoServico = $this->dom->createElement($this->prefixoNfse . 'InfDeclaracaoPrestacaoServico');
                //Cria node Rps com ID
                $this->buildRps(true);
                $this->buildIdentificacaoRps();
                $this->dom->appChild($this->nfseId, $this->identificacaoRps, 'Falta tag "Rps com ID"');
                $this->dom->addChild(
                    $this->nfseId,
                    $this->prefixoNfse . 'DataEmissao',
                    date('Y-m-d'),
                    true,
                    'Data de emissão da NFSe'
                );
                $this->dom->addChild(
                    $this->nfseId,
                    $this->prefixoNfse . 'Status',
                    1,
                    true,
                    'Status da NFSe'
                );
                //Adiciona o node Rps com id ao node InfDeclaracaoPrestacaoServico
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->nfseId, 'Falta tag "infDeclaracaoPrestacaoServico"');
                //Adiciona Competencia ao node InfDeclaracaoPrestacaoServico
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'Competencia',
                    $std->competencia,
                    true,
                    'Data da Competência da prestação de serviço'
                );
                $this->infDeclaracaoPrestacaoServico->setAttribute('Id', $this->Id);
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->servico, 'Falta Tag InfDeclaracaoPrestacaoServico');
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->prestador, 'Falta Tag InfDeclaracaoPrestacaoServico');
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->tomador, 'Falta Tag InfDeclaracaoPrestacaoServico');
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'RegimeEspecialTributacao',
                    $std->regimeEspecialTributacao,
                    true,
                    'Código de identificação do regime especial de tributação'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'OptanteSimplesNacional',
                    $std->optanteSimplesNacional,
                    true,
                    'Código de identificação se é optante pelo SN 1 Sim, 2 Nao'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'IncentivoFiscal',
                    $std->incentivoFiscal,
                    true,
                    'Código de identificação se opta pelo incentivo fiscal pelo SN 1 Sim, 2 Nao'
                );
                break;
            case "BETA11":
                $this->infDeclaracaoPrestacaoServico = $this->dom->createElement($this->prefixoNfse . 'InfRps');
                $this->buildIdentificacaoRps();
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->identificacaoRps, 'Falta tag "infDeclaracaoPrestacaoServico"');
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'DataEmissao',
                    date('Y-m-d\TH:i:s'),
                    true,
                    'Data de emissão da NFSe'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'NaturezaOperacao',
                    59,
                    true,
                    'Código de identificação do regime especial de tributação'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'RegimeEspecialTributacao',
                    $std->regimeEspecialTributacao,
                    true,
                    'Código de identificação do regime especial de tributação'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'OptanteSimplesNacional',
                    $std->optanteSimplesNacional,
                    true,
                    'Código de identificação se é optante pelo SN 1 Sim, 2 Nao'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'IncentivadorCultural',
                    $std->incentivoFiscal,
                    true,
                    'Código de identificação se opta pelo incentivo fiscal pelo SN 1 Sim, 2 Nao'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'Status',
                    1,
                    true,
                    'Status da NFSe'
                );
                $this->infDeclaracaoPrestacaoServico->setAttribute('Id', 'R'.$this->Id);
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->servico, 'Falta Tag InfDeclaracaoPrestacaoServico');
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->prestador, 'Falta Tag InfDeclaracaoPrestacaoServico');
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->tomador, 'Falta Tag InfDeclaracaoPrestacaoServico');
                break;
            case "BETA2":
                $this->infDeclaracaoPrestacaoServico = $this->dom->createElement($this->prefixoNfse . 'InfDeclaracaoPrestacaoServico');
                //Cria node Rps com ID
                $this->buildRps(true);
                $this->buildIdentificacaoRps();
                $this->dom->appChild($this->nfseId, $this->identificacaoRps, 'Falta tag "Rps com ID"');
                $this->dom->addChild(
                    $this->nfseId,
                    $this->prefixoNfse . 'DataEmissao',
                    date('Y-m-d'),
                    true,
                    'Data de emissão da NFSe'
                );
                $this->dom->addChild(
                    $this->nfseId,
                    $this->prefixoNfse . 'Status',
                    1,
                    true,
                    'Status da NFSe'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'RegimeEspecialTributacao',
                    $std->regimeEspecialTributacao,
                    true,
                    'Código de identificação do regime especial de tributação'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'OptanteSimplesNacional',
                    $std->optanteSimplesNacional,
                    true,
                    'Código de identificação se é optante pelo SN 1 Sim, 2 Nao'
                );
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'IncentivoFiscal',
                    $std->incentivoFiscal,
                    true,
                    'Código de identificação se opta pelo incentivo fiscal pelo SN 1 Sim, 2 Nao'
                );
                //Adiciona o node Rps com id ao node InfDeclaracaoPrestacaoServico
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->nfseId, 'Falta tag "infDeclaracaoPrestacaoServico"');
                //Adiciona Competencia ao node InfDeclaracaoPrestacaoServico
                $this->dom->addChild(
                    $this->infDeclaracaoPrestacaoServico,
                    $this->prefixoNfse . 'Competencia',
                    $std->competencia,
                    true,
                    'Data da Competência da prestação de serviço'
                );
                $this->infDeclaracaoPrestacaoServico->setAttribute('Id', $this->Id);
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->servico, 'Falta Tag InfDeclaracaoPrestacaoServico');
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->prestador, 'Falta Tag InfDeclaracaoPrestacaoServico');
                $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->tomador, 'Falta Tag InfDeclaracaoPrestacaoServico');
                break;
        }
        return $this->infDeclaracaoPrestacaoServico;
    }

    public function tagLoteRps(stdClass $std): \DOMElement
    {
        $this->loteRps = $this->dom->createElement($this->prefixoNfse . 'LoteRps');
        $this->dom->addChild(
            $this->loteRps,
            $this->prefixoNfse . 'NumeroLote',
            $std->numeroLote,
            true,
            'Número do lote da NFSe'
        );
        switch($this->padraoEscolha){
            case "SIMPLISS":
                $this->loteRps->setAttribute('Id', $std->numeroLote);
                $this->loteRps->setAttribute('versao', $this->versao);
                $cpfCnpj = $this->dom->createElement($this->prefixoNfse . 'CpfCnpj');
                if (isset($std->cnpj)) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cnpj,
                        true,
                        'CNPJ do prestador de serviço'
                    );
                } elseif (isset($std->cpf)) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'CPF do prestador de serviço'
                    );
                }
                $this->dom->appChild($this->loteRps, $cpfCnpj, 'Falta tag Lote Rps');
                break;
            case "BETA11":
                $this->loteRps->setAttribute('Id', 'L'.$std->numeroLote);
                if (isset($std->cnpj)) {
                    $this->dom->addChild(
                        $this->loteRps,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cnpj,
                        true,
                        'CNPJ do prestador de serviço'
                    );
                } elseif (isset($std->cpf)) {
                    $this->dom->addChild(
                        $this->loteRps,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'CPF do prestador de serviço'
                    );
                }
                break;
            case "BETA2":
                $this->loteRps->setAttribute('Id', $std->numeroLote);
                $this->loteRps->setAttribute('versao', $this->versao);
                $cpfCnpj = $this->dom->createElement($this->prefixoNfse . 'CpfCnpj');
                if (isset($std->cnpj)) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cnpj',
                        $std->cnpj,
                        true,
                        'CNPJ do prestador de serviço'
                    );
                } elseif (isset($std->cpf)) {
                    $this->dom->addChild(
                        $cpfCnpj,
                        $this->prefixoNfse . 'Cpf',
                        $std->cpf,
                        true,
                        'CPF do prestador de serviço'
                    );
                }
                $this->dom->appChild($this->loteRps, $cpfCnpj, 'Falta tag Lote Rps');
                break;
        }

        $this->dom->addChild(
            $this->loteRps,
            $this->prefixoNfse . 'InscricaoMunicipal',
            $std->inscricaoMunicipal,
            true,
            'Inscrição municipal do Prestador de serviço'
        );
        $this->dom->addChild(
            $this->loteRps,
            $this->prefixoNfse . 'QuantidadeRps',
            $std->quantidadeRps,
            true,
            'Quantidade de notas do lote'
        );
        return $this->loteRps;
    }
}
