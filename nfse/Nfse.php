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
     * @var array of DOMElements
     */
    protected $nfse;
    /**
     * @var array of DOMElements
     */
    protected $infNfse;
    /**
     * @var array of DOMElements
     */
    protected $versao;
    /**
     * @var array of DOMElements
     */
    protected $valoresNfse;
    /**
     * @var array of DOMElements
     */
    protected $identificacaoPrestador;
    /**
     * @var array of DOMElements
     */
    protected $enderPrest;
    /**
     * @var array of DOMElements
     */
    protected $contatoPrestador;
    /**
     * @var array of DOMElements
     */
    protected $prestadorServico;
    /**
     * @var array of DOMElements
     */
    protected $orgaoGerador;
    /**
     * @var array of DOMElements
     */
    protected $prestador;
    /**
     * @var array of DOMElements
     */
    protected $servico;
    /**
     * @var array of DOMElements
     */
    protected $tomador;
    /**
     * @var array of DOMElements
     */
    protected $declaracaoPrestacaoServico;

    //Monta os dados necessários para emissão da NFS-e

    /**
     * Construtor recebe o objeto da nota fiscal com todas as informações e instancia um novo DOM Document
     * @param String $versao
     */

    public function __construct(String $versao)
    {
        $this->dom = new Dom($versao, 'UTF-8');
        $this->versao = $versao;
    }

    public function monta(): string
    {
        if (!empty($this->errors)) {
            $this->errors = array_merge($this->errors, $this->dom->errors);
        } else {
            $this->errors = $this->dom->errors;
        }
        //Cria a tag Nfse raiz do xml
        $this->buildNfse($this->versao);
        //Atribui id e versão infNfse

        $this->dom->appChild($this->infNfse, $this->valoresNfse, 'Falta tag "infNfse"');
        $this->dom->appChild($this->infNfse, $this->prestadorServico, 'Falta tag "infNfse"');
        $this->dom->appChild($this->infNfse, $this->orgaoGerador, 'Falta tag "infNfse"');
        $this->dom->appChild($this->infNfse, $this->declaracaoPrestacaoServico, 'Falta tag "infNfse"');
        $this->dom->appChild($this->nfse, $this->infNfse, 'Falta tag "Raiz"');
        $this->dom->appendChild($this->nfse);
        $this->xml = $this->dom->saveXML();

        return $this->xml;
    }

    private function buildNfse($versao) : \DOMElement
    {
        if(!$this->nfse){
            $this->nfse = $this->dom->createElement('Nfse');
            $this->nfse->setAttribute('versao', $versao);
        }

        return $this->nfse;
    }

    public function taginfNfse(stdClass $std) : \DOMElement
    {
        $this->infNfse = $this->dom->createElement('InfNfse');
        $this->infNfse->setAttribute('Id', $std->Id);
        $this->dom->addChild(
            $this->infNfse,
            'Numero',
            $std->Numero,
            true,
            'Número da Nota Fiscal de Serviço'
        );

        return $this->infNfse;
    }

    public function tagValoresNfse(stdClass $std) : \DOMElement
    {
        $this->valoresNfse = $this->dom->createElement('ValoresNfse');
        $this->dom->addChild(
            $this->valoresNfse,
            'BaseCalculo',
            $std->bc,
            true,
            'Base cálculo',
        );
        $this->dom->addChild(
            $this->valoresNfse,
            'Aliquota',
            $std->aliqV,
            true,
            'Alíquota Imposto ISS',
        );
        $this->dom->addChild(
            $this->valoresNfse,
            'ValorIss',
            $std->vIss,
            true,
            'Valor resultante imposto ISS',
        );
        $this->dom->addChild(
            $this->valoresNfse,
            'ValorLiquidoNfse',
            $std->vLiq,
            true,
            'Valor Líquido',
        );

        return $this->valoresNfse;
    }

    public function tagPrestadorServico(stdClass $std): \DOMElement
    {
        //Criação node PrestadorServico
        $this->prestadorServico = $this->dom->createElement('PrestadorServico');

        //Identificação do prestador
        $this->identificacaoPrestador = $this->dom->createElement('IdentificacaoPrestador');

        $cpfCnpj = $this->dom->createElement('CpfCnpj');
        if(isset($std->cnpj)){
            $this->dom->addChild(
                $cpfCnpj,
                'Cnpj',
                $std->cnpj,
                true,
                'CNPJ do prestador de serviço'
            );
        }elseif(isset($std->cpf)){
            $this->dom->addChild(
                $cpfCnpj,
                'Cpf',
                $std->cpf,
                true,
                'CPF do prestador de serviço'
            );
        }
        $this->dom->appChild($this->identificacaoPrestador, $cpfCnpj, 'Falta tag IdentificacaoPrestador');
        $this->dom->addChild(
            $this->identificacaoPrestador,
            'InscricaoMunicipal',
            $std->inscricaoMunicipal,
            true,
            'Inscrição municipal do Prestador de serviço'
        );
        //Adição node IdentificacaoPrestador ao node PrestadorServico
        $this->dom->appChild($this->prestadorServico, $this->identificacaoPrestador, 'Falta Tag PrestadorServiço');

        //Adição node Razão social
        $this->dom->addChild(
            $this->prestadorServico,
            'RazaoSocial',
            $std->razaosocial,
            true,
            'Razão social do Prestador de serviço'
        );

        //Adição node NomeFantasia
        $this->dom->addChild(
            $this->prestadorServico,
            'NomeFantasia',
            $std->nomefantasia,
            true,
            'Nome Fantasia do Prestador de serviço'
        );

        //Endereço do prestador
        $this->enderPrest = $this->dom->createElement('Endereco');
        $this->dom->addChild(
            $this->enderPrest,
            'Endereco',
            $std->logradouro,
            true,
            'Logradouro do prestador de serviço'
        );
        $this->dom->addChild(
            $this->enderPrest,
            'Numero',
            $std->numero,
            true,
            'Número do prestador de serviço'
        );
        $this->dom->addChild(
            $this->enderPrest,
            'Complemento',
            $std->complemento,
            true,
            'Complemento do prestador de serviço'
        );
        $this->dom->addChild(
            $this->enderPrest,
            'Bairro',
            $std->bairro,
            true,
            'Bairro do prestador de serviço'
        );
        $this->dom->addChild(
            $this->enderPrest,
            'CodigoMunicipio',
            $std->codMP,
            true,
            'Código do Município do prestador de serviço'
        );
        $this->dom->addChild(
            $this->enderPrest,
            'Uf',
            $std->uf,
            true,
            'UF do prestador de serviço'
        );
        $this->dom->addChild(
            $this->enderPrest,
            'Cep',
            $std->cep,
            true,
            'Cep do prestador de serviço'
        );
        //Adiciona tag Endereço
        $this->dom->appChild($this->prestadorServico, $this->enderPrest, 'Falta Tag PrestadorServiço');

        //Contato do prestador
        $this->contatoPrestador = $this->dom->createElement('Contato');
        $this->dom->addChild(
            $this->contatoPrestador,
            'Telefone',
            $std->telefone,
            true,
            'Telefone do prestador de serviço'
        );
        $this->dom->addChild(
            $this->contatoPrestador,
            'Email',
            $std->email,
            true,
            'E-mail do prestador de serviço'
        );
        //Adiciona tag Contato
        $this->dom->appChild($this->prestadorServico, $this->contatoPrestador, 'Falta Tag PrestadorServiço');

        return $this->prestadorServico;
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
        $this->servico = $this->dom->createElement('Servico');
        $valores = $this->dom->createElement('Valores');
        //Adiciona os nodes dentro do nove valores
        $this->dom->addChild(
            $valores,
            'ValorServicos',
            $std->vServ,
            true,
            'Valor total dos serviços'
        );
        $this->dom->addChild(
            $valores,
            'ValorIss',
            $std->vIss,
            true,
            'Valor Iss dos serviços'
        );
        $this->dom->addChild(
            $valores,
            'Aliquota',
            $std->pAliq,
            true,
            'Porcentagem Aliquota dos serviços'
        );
        //Adiciona node valores no node serviço
        $this->dom->appChild($this->servico, $valores, 'Falta tag Serviço');
        $this->dom->addChild(
            $this->servico,
            'IssRetido',
            $std->issRetido,
            true,
            'Choice Iss Retido, seguindo o padrão 1 Sim, 2 Nao'
        );
        if($std->issRetido === "1"){
            $this->dom->addChild(
                $this->servico,
                'ValorIssRetido',
                $std->vIssRetido,
                false,
                'Valor ISS Retido caso IssRetido seja 1'
            );
        }
        $this->dom->addChild(
            $this->servico,
            'ItemListaServico',
            $std->itemListaServico,
            true,
            'Código de item da lista de serviço'
        );
        $this->dom->addChild(
            $this->servico,
            'CodigoCnae',
            $std->codigoCnae,
            true,
            'Código CNAE'
        );
        $this->dom->addChild(
            $this->servico,
            'CodigoTributacaoMunicipio',
            $std->codigoTributacaoMunicipio,
            true,
            'Código de Tributação'
        );
        $this->dom->addChild(
            $this->servico,
            'Discriminacao',
            $std->discriminacao,
            true,
            'Discriminação do conteúdo da NFS-e'
        );
        $this->dom->addChild(
            $this->servico,
            'CodigoMunicipio',
            $std->codigoMunicipio,
            true,
            'Código de identificação do município conforme tabela do IBGE. Preencher com 5 noves para serviço prestado no exterior'
        );
        $this->dom->addChild(
            $this->servico,
            'ExigibilidadeISS',
            $std->exigibilidadeISS,
            true,
            '1 – Exigível, 2 – Não Incidência, 3 – Isenção, 4 – Exportação, 5 – Imunidade, 6 – Exigibilidade suspensa por decisão judicial, 7 – Exigibilidade suspensa por processo administrativo'
        );
        $this->dom->addChild(
            $this->servico,
            'MunicipioIncidencia',
            $std->municipioIncidencia,
            false,
            'Caso exigibilidade seja diferente de 2, 5, 6 e 7'
        );

        return $this->servico;
    }

    public function tagPrestador(stdClass $std): \DOMElement
    {
        $this->prestador = $this->dom->createElement('Prestador');

        $cpfCnpj = $this->dom->createElement('CpfCnpj');
        if(isset($std->cnpj)){
            $this->dom->addChild(
                $cpfCnpj,
                'Cnpj',
                $std->cnpj,
                true,
                'CNPJ do prestador de serviço'
            );
        }elseif(isset($std->cpf)){
            $this->dom->addChild(
                $cpfCnpj,
                'Cpf',
                $std->cpf,
                true,
                'CPF do prestador de serviço'
            );
        }
        $this->dom->appChild($this->prestador, $cpfCnpj, 'Falta tag Prestador');
        $this->dom->addChild(
            $this->prestador,
            'InscricaoMunicipal',
            $std->inscricaoMunicipal,
            true,
            'Inscrição municipal do Prestador de serviço'
        );

        return $this->prestador;
    }

    public function tagTomador(stdClass $std): \DOMElement
    {
        $this->tomador = $this->dom->createElement('Tomador');
        $this->identificacaoTomador = $this->dom->createElement('identificacaoTomador');
        $cpfCnpj = $this->dom->createElement('CpfCnpj');
        //Adição das informações node CpfCnpj
        if(isset($std->cnpj)){
            $this->dom->addChild(
                $cpfCnpj,
                'Cnpj',
                $std->cnpj,
                true,
                'Cnpj do tomador do serviço'
            );
        }elseif(isset($std->cpf)){
            $this->dom->addChild(
                $cpfCnpj,
                'Cpf',
                $std->cpf,
                true,
                'Cpf do tomador do serviço'
            );
        }
        $this->dom->appChild($this->identificacaoTomador, $cpfCnpj, 'Falta tag IdentificacaoTomador');
        $this->dom->addChild(
            $this->identificacaoTomador,
            'InscricaoMunicipal',
            $std->inscricaoMunicipal,
            true,
            'Inscrição municipal do tomador de serviço'
        );
        //Adição node CpfCnpj ao node IdentificacaoTomador
        //Adição node IdentificacaoTomador ao node Tomador
        $this->dom->appChild($this->tomador, $this->identificacaoTomador, 'Falta tag Tomador');
        $this->dom->addChild(
            $this->tomador,
            'RazaoSocial',
            $std->razaosocial,
            true,
            'Razão Social tomador de serviço'
        );
        //Geração node Endereço
        $this->enderTomador = $this->dom->createElement('Endereco');
        $this->dom->addChild(
            $this->enderTomador,
            'Endereco',
            $std->logradouro,
            true,
            'Logradouro do tomador de serviço'
        );
        $this->dom->addChild(
            $this->enderTomador,
            'Numero',
            $std->numero,
            true,
            'Número do tomador de serviço'
        );
        $this->dom->addChild(
            $this->enderTomador,
            'Complemento',
            $std->complemento,
            true,
            'Complemento do tomador de serviço'
        );
        $this->dom->addChild(
            $this->enderTomador,
            'Bairro',
            $std->bairro,
            true,
            'Bairro do tomador de serviço'
        );
        $this->dom->addChild(
            $this->enderTomador,
            'CodigoMunicipio',
            $std->codMP,
            true,
            'Código do Município do tomador de serviço'
        );
        $this->dom->addChild(
            $this->enderTomador,
            'Uf',
            $std->uf,
            true,
            'UF do tomador de serviço'
        );
        $this->dom->addChild(
            $this->enderTomador,
            'Cep',
            $std->cep,
            true,
            'Cep do tomador de serviço'
        );
        //Adição node Endereco ao node Tomador
        $this->dom->appChild($this->tomador, $this->enderTomador, 'Falta tag Tomador');

        //Geração Node Contato
        $this->contatoTomador = $this->dom->createElement('Contato');
        $this->dom->addChild(
            $this->contatoTomador,
            'Telefone',
            $std->telefone,
            true,
            'Telefone do tomador de serviço'
        );
        $this->dom->addChild(
            $this->contatoTomador,
            'Email',
            $std->email,
            true,
            'E-mail do tomador de serviço'
        );

        //Adição node Contato ao node Tomador
        $this->dom->appChild($this->tomador, $this->contatoTomador, 'Falta tag Tomador');

        return $this->tomador;
    }

    public function tagDeclaracaoPrestacaoServico(stdClass $std): \DOMElement
    {
        $this->infDeclaracaoPrestacaoServico = $this->dom->createElement('InfDeclaracaoPrestacaoServico');
        $this->infDeclaracaoPrestacaoServico->setAttribute('Id', "singtag");

        $this->dom->addChild(
            $this->infDeclaracaoPrestacaoServico,
            'Competencia',
            $std->competencia,
            true,
            'Data da Competência da prestação de serviço'
        );
        $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->servico, 'Falta Tag InfDeclaracaoPrestacaoServico');
        $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->prestador, 'Falta Tag InfDeclaracaoPrestacaoServico');
        $this->dom->appChild($this->infDeclaracaoPrestacaoServico, $this->tomador, 'Falta Tag InfDeclaracaoPrestacaoServico');
        $this->dom->addChild(
            $this->infDeclaracaoPrestacaoServico,
            'RegimeEspecialTributacao',
            $std->regimeEspecialTributacao,
            true,
            'Código de identificação do regime especial de tributação'
        );
        $this->dom->addChild(
            $this->infDeclaracaoPrestacaoServico,
            'OptanteSimplesNacional',
            $std->optanteSimplesNacional,
            true,
            'Código de identificação se é optante pelo SN 1 Sim, 2 Nao'
        );
        $this->dom->addChild(
            $this->infDeclaracaoPrestacaoServico,
            'IncentivoFiscal',
            $std->incentivoFiscal,
            true,
            'Código de identificação se opta pelo incentivo fiscal pelo SN 1 Sim, 2 Nao'
        );
        $this->declaracaoPrestacaoServico = $this->dom->createElement('DeclaracaoPrestacaoServico');
        $this->dom->appChild($this->declaracaoPrestacaoServico, $this->infDeclaracaoPrestacaoServico, 'Falta Tag DeclaracaoPrestacaoServico');

        return $this->declaracaoPrestacaoServico;
    }







}
