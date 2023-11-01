***
Homologado na prefeitura de blumenau

* Dev-Autor: Git -> https://github.com/Longinus-Ek

***
* Configuração SOAP Client
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

$ops = [
    'Content-Type: text/xml;charset="utf-8"',
    'Accept: text/xml',
    'Expect: 100-continue',
    'Connection: Keep-Alive',
];

$tools = new Tools($configStd, $certificado, $password);
```
* Consulta Rps
```
$cnpj = $grafica->cnpj;
$xmlConsultado = $tools->consultaLoteRps($cnpj, $notaFiscal->protocolo, $grafica->inscricaomp, $ops);
$st2 = new Standardize($xmlConsultado);
$arrayResponse2 = $st2->toArray();

$mensagens2 = isset($arrayResponse2[0]['messages']) ? $arrayResponse2[0]['messages'] : array();
if(count($mensagens2) > 0){
    foreach ($mensagens2 as $msg){
        $notaFiscal->status = 'AGUARDANDO_RESPOSTA';
        $notaFiscal->save();
        throw new \Exception('Codigo: ' . $msg['Codigo'] . '<br>Motivo: ' . $msg['Mensagem'] . '<br>Correção: ' . $msg['Correcao']);
    }
}
```

