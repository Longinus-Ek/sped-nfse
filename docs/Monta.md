$nfse = new Nfse('2.03', 'UTF-8');
$nfse->definePrefixo("nfse:");
```
$std = new stdClass();
$std->Id = '86';
$nfse->taginfNfse($std);
```
```
$std = new stdClass();
$std->vLiq = 120.00;
$std->vDed = 0.00;
$std->vPis = 0.00;
$std->vCofins = 0.00;
$std->vInss = 0.00;
$std->vIr = 0.00;
$std->vCsll = 0.00;
$std->vOutrasRetencoes = 0.00;
$std->valorTotaisTributos = 0.00;
$std->vIss = 0.00;
$std->aIss = 1.00;
$std->descIncondicionado = 0.00;
$std->descCondicionado = 0.00;
$std->issRetido = 2;
$std->vIssRetido = 0;
$std->itemListaServico = "1.07";
$std->codigoCnae = "9511800";
$std->codigoTributacaoMunicipio = "1.07";
$std->discriminacao = "TESTE DE EMISSÃO XML DE NFS-e";
$std->codigoMunicipio = "4202404";
$std->exigibilidadeISS = "1";
$std->municipioIncidencia = "4202404";
$std->cpf = '21728376000197';
$std->logradouro = 'Rua Presidente Costa e Silva';
$std->numero = '273';
$std->complemento = 'Casa';
$std->bairro = 'Centro';
$std->codMP = '654654654';
$std->inscricaoMunicipal = '110675';
$std->uf = 'SC';
$std->cep = '88240000';
$std->telefone = '48999402516';
$std->email = 'derickbass4@gmail.com';
$std->razaosocial = 'CSX SOLUCOES Ltda';
$std->nomefantasia = 'CSX SOLUCOES';
$std->outrasInformacoes = '';
$nfse->tagServico($std);
```
```
$std = new stdClass();
$std->cpf = '21728376000197';
$std->inscricaoMunicipal = '110675';
$nfse->tagPrestador($std);
```
```
$std = new stdClass();
$std->cpf = '59420065987';
$std->razaosocial = 'Rasta Tabaca na Vara';
$std->logradouro = 'Rua Presidente Costa e Silva';
$std->numero = '273';
$std->complemento = 'Casa';
$std->bairro = 'Centro';
$std->codMP = '4202404';
$std->uf = 'SC';
$std->codPais = '1058';
$std->cep = '88240000';
$std->telefone = '48999402516';
$std->email = 'derickbass4@gmail.com';
$nfse->tagTomador($std);
```
```
$std = new stdClass();
$std->competencia = "2023-10-20";
$std->regimeEspecialTributacao = "1";
$std->optanteSimplesNacional = "2";
$std->incentivoFiscal = "2";
$nfse->tagDeclaracaoPrestacaoServico($std);
```
```
$std = new stdClass();
$std->numeroLote = 86;
$std->cpf = '21728376000197';
$std->inscricaoMunicipal = '110675';
$std->quantidadeRps = 1;
$nfse->tagLoteRps($std);
```
```
$xml = $nfse->monta();
```
```
$configStd = new stdClass();
$configStd->versao = "1.00";
$configStd->siglaUF = "SC";
$configStd->cidade = "BLUMENAU";
$configStd->tpAmb = 2;
$configStd->padrao = 'SIMPLISS';
```
```
$ops = ['Content-Type: text/xml;charset="utf-8"',
'Accept: text/xml',
'Expect: 100-continue',
'Connection: Keep-Alive',
];
```
```
$tools = new Tools($configStd, $certificado, $password);
```
```
$resposta = $tools->envioLoteRps($xml, $ops);
$st = new Standardize($resposta);
$arrayResponse = $st->toArray();
$mensagens = isset($arrayResponse[0]['messages']) ? $arrayResponse[0]['messages'] : array();
```
```
if(count($mensagens) > 0){
    foreach ($mensagens as $msg){
        throw new \Exception('Codigo: ' . $msg['Codigo'] . ' Motivo: ' . $msg['Mensagem'] . ' Correção: ' . $msg['Correcao']);
    }
}
```
```
if($arrayResponse[0]['Protocolo']){
    $notaFiscal->protocolo = $arrayResponse[0]['Protocolo'];
    $notaFiscal->save();
    
    sleep(30);
    
    $cnpj = '21728376000197';//$grafica->cnpj;
    $xmlConsultado = $tools->consultaLoteRps($cnpj, $notaFiscal->protocolo, '110675', $ops);
    $st2 = new Standardize($xmlConsultado);
    $arrayResponse2 = $st2->toArray();
    
    $mensagens2 = isset($arrayResponse2[0]['messages']) ? $arrayResponse2[0]['messages'] : array();
    if(count($mensagens2) > 0){
        foreach ($mensagens2 as $msg){
            $notaFiscal->status = 'AGUARDANDO_RESPOSTA';
            $notaFiscal->save();
            throw new \Exception('Codigo: ' . $msg['Codigo'] . ' Motivo: ' . $msg['Mensagem'] . ' Correção: ' . $msg['Correcao']);
        }
    }
}