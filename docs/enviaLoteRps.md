***
Homologado na prefeitura de blumenau
* Dev-Autor: Git -> https://github.com/Longinus-Ek
***

*Instância classe para montar o xml da NFSe
```
$nfse = new Nfse('2.03', 'UTF-8');
$nfse->definePrefixo("nfse:");
```
```
$std = new stdClass();
$std->Id = FiltroNumeros::convertNumeros($notaFiscal->numeroNF);
$std->serie = $notaFiscal->serie;
$nfse->taginfNfse($std);
foreach ($itensNF as $item) {
    $std = new stdClass();
    $std->vLiq = FiltroNumeros::convertNumeros($item['valorLiquido']);
    $std->vDed = 0.00;
    $std->vPis = UtilNumeros::convertStringNumberBRtoEUANumber($item['tributacao']['valorPIS']);
    $std->vCofins = UtilNumeros::convertStringNumberBRtoEUANumber($item['tributacao']['valorCOFINS']);
    $std->vInss = 0.00;
    $std->vIr = 0.00;
    $std->vCsll = UtilNumeros::convertStringNumberBRtoEUANumber($item['tributacao']['valorCSLL']);
    $std->vOutrasRetencoes = UtilNumeros::convertStringNumberBRtoEUANumber($item['valorOutros']);
    $std->vIss = UtilNumeros::convertStringNumberBRtoEUANumber($item['tributacao']['valorISS']);
    $std->descIncondicionado = 0.00;
    $std->descCondicionado = 0.00;

    $std->aIss = FiltroNumeros::convertNumeros($item['tributacao']['aliquotaISS']);
}
$std->issRetido = 2; //-->
$std->responsavelRetencao = 1; //-->
$cidadePrestador = Estados::where(function($query) use ($grafica){
    $query->where('cidade','=',$grafica->cidade)
        ->orWhere('cidadeUP','=',$grafica->cidade);
})->first();
$cidadeTomador = Estados::where(function($query) use ($notaFiscal){
    $query->where('cidade','=',$notaFiscal->cidade)
        ->orWhere('cidadeUP','=',$notaFiscal->cidade);
})->first();
foreach ($itensNF as $item) {
    $std->itemListaServico = $item['codigoServico'];
    $std->codigoTributacaoMunicipio = $item['codigoServico'];
    $std->codigoCnae = preg_replace('/[^\w\s]/', '', $item['codigoCNAE']);
    $std->discriminacao = $item['discriminacao'];
    if(!isset($cidadePrestador->IBGE)){
        return response()->json(['error' => true, 'message' => 'Cidade do Prestador não identificada!']);
    }
    $std->codigoMunicipio = $cidadePrestador->IBGE;
    $std->codigoPais = "1058";
    $std->exigibilidadeISS = $item['tributacao']['stISS'];
    $std->municipioIncidencia = $cidadePrestador->IBGE;
    $std->cnpj = $grafica->cnpj;
    $std->logradouro = $grafica->logradouro;
    $std->numero = $grafica->numero;
    $std->complemento = $grafica->complemento;
    $std->bairro = $grafica->bairro;
    $std->codMP = $cidadePrestador->IBGE;
    $std->inscricaoMunicipal = $grafica->inscricaomp;
    $std->uf = $grafica->uf;
    $std->cep = preg_replace('/[^\w\s]/', '', $grafica->cep);
    $std->telefone = preg_replace('/[^\w\s]/', '', $grafica->telefone);
    $std->email = $grafica->email;
    $std->razaosocial = $grafica->razaosocial;
    $std->nomefantasia = $grafica->nomefantasia;
    $std->outrasInformacoes = '';
}
$nfse->tagServico($std);
```
```
$std = new stdClass();
$std->cpfCnpj = $grafica->cnpj;
$std->inscricaoMunicipal = $grafica->inscricaomp;
$nfse->tagPrestador($std);

$pessoaTomador = PessoaJuridica::where('graficaID','=',$notaFiscal->graficaID)->where('ref','=',intval($notaFiscal->codigoPessoa))->first();
if(is_null($pessoaTomador)){
    return response()->json(['error' => true, 'message' => 'Pessoa Juridica Tomador não identificada!']);
}
$std = new stdClass();
$std->cpfCnpj = $notaFiscal->cpfCnpj;
$std->razaosocial = $notaFiscal->nome;
$std->logradouro = $notaFiscal->logradouro;
$std->numero = $notaFiscal->numero;
$std->complemento = $notaFiscal->complemento;
$std->bairro = $notaFiscal->bairro;
if(!isset($cidadeTomador->IBGE)){
    return response()->json(['error' => true, 'message' => 'Cidade do Tomador não identificada!']);
    }
$std->codMP = $cidadeTomador->IBGE;
$std->uf = $notaFiscal->uf;
$std->codPais = '1058';
$std->cep = $notaFiscal->cep;
$std->telefone = preg_replace('/[^\w\s]/', '', $notaFiscal->fone);
$std->email = $notaFiscal->email;
if(strlen($pessoaTomador) <= 0){
    return response()->json(['error' => true, 'message' => 'Inscrição municipal não configurada, efetue a configuração na tela Pessoa Jurídica!']);
}
$std->inscricaoMunicipal = $pessoaTomador->inscricaomp;
$nfse->tagTomador($std);
```
```
$std = new stdClass();
$std->competencia = date('Y-m-d');
$std->regimeEspecialTributacao = $notaFiscal->regimeEspecialTributacao;
$std->optanteSimplesNacional = $notaFiscal->simplesNacional;
$std->incentivoFiscal = $notaFiscal->IncentivoFiscal;
$nfse->tagDeclaracaoPrestacaoServico($std);
```
```
$std = new stdClass();
$std->numeroLote = FiltroNumeros::convertNumeros($notaFiscal->numeroNF);;
$std->cnpj = $grafica->cnpj;
$std->inscricaoMunicipal = $grafica->inscricaomp;
$std->quantidadeRps = 1;
$nfse->tagLoteRps($std);
```

```
$xml = $nfse->monta();
```
*Configuração do SOAP Client
```
$configStd = new stdClass();
$configStd->versao = "1.00";
$configStd->siglaUF = $grafica->uf;
$configStd->cidade = $cidadePrestador->cidadeUP;
$configStd->tpAmb = $configGrafica->tpAmbiente;
$listaPadraoCidade = [
'BLUMENAU' => 'SIMPLISS'
];
$configStd->padrao = $listaPadraoCidade[$cidadePrestador->cidadeUP];

$ops = ['Content-Type: text/xml;charset="utf-8"',
'Accept: text/xml',
'Expect: 100-continue',
'Connection: Keep-Alive',
];

$tools = new Tools($configStd, $certificado, $password);

$resposta = $tools->envioLoteRps($xml, $ops);

$notaFiscal->status = 'AUTORIZADA';
```
*Tratamento da resposta do webservice
```
$st = new Standardize($resposta);
$arrayResponse = $st->toArray();
$mensagens = isset($arrayResponse[0]['messages']) ? $arrayResponse[0]['messages'] : array();

if(count($mensagens) > 0){
    foreach ($mensagens as $msg){
        $notaFiscal->status = 'REJEITADA';
        throw new \Exception('Codigo: ' . $msg['Codigo'] . ' Motivo: ' . $msg['Mensagem'] . ' Correção: ' . $msg['Correcao']);
    }
}