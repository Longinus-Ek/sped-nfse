# sped-nfse
Biblioteca criada para emissão de nota fiscal de serviço (em desenvolvimento)

## Utilização

```
$nfse = new Nfse('1.0', 'UTF-8', 'CIDADE');

            $std = new stdClass();
            $std->Id = 123 [int];
            $std->serie = '123' [string];
            $nfse->taginfNfse($std);
            foreach ($itensNF as $item) {
                $std = new stdClass();
                $std->vLiq = 0.00 [float];
                $std->vDed = 0.00 [float];
                $std->vPis = 0.00; [float]
                $std->vCofins = 0.00; [float]
                $std->vInss = 0.00; [float]
                $std->vIr = 0.00; [float]
                $std->vCsll = 0.00; [float]
                $std->vOutrasRetencoes = 0.00; [float]
                $std->vIss = 0.00; [float]
                $std->descIncondicionado = 0.00; [float]
                $std->descCondicionado = 0.00; [float]

                $std->aIss = 0.00; [float]
            }
            $std->issRetido = 2;  [1 ATIVO, 2 INATIVO]
            $std->responsavelRetencao = 1; [1 ATIVO, 2 INATIVO]

        
            foreach ($itensNF as $item) {
                $std->itemListaServico = '12' [string];
                $std->codigoTributacaoMunicipio = '123'; [string]
                $std->codigoCnae = '123'; [string]
                $std->discriminacao = '123'; [string]
             
                $std->codigoMunicipio = '123'; [string]
                $std->codigoPais = '123'; [string]
                $std->exigibilidadeISS = '123'; [string]
                $std->municipioIncidencia = '123'; [string]
                $std->cnpj = '123'; [string]
                $std->logradouro = '123'; [string]
                $std->numero = '123'; [string]
                $std->complemento = '123'; [string]
                $std->bairro = '123'; [string]
                $std->codMP = '123'; [string]
                $std->inscricaoMunicipal = '123'; [string]
                $std->uf = '123'; [string]
                $std->cep = '123'; [string]
                $std->telefone = '123'; [string]
                $std->email = '123'; [string]
                $std->razaosocial = '123'; [string]
                $std->nomefantasia = '123'; [string]
                $std->outrasInformacoes = '123'; [string]
            }
            $std->cidadePrestador = '123'; [string]
            $nfse->tagServico($std);

            $std = new stdClass();
            $std->cpfCnpj = '123'; [string]
            $std->inscricaoMunicipal = '123'; [string]
            $nfse->tagPrestador($std);

            $std = new stdClass();
            $std->cpfCnpj = '123'; [string]
            $std->razaosocial = '123'; [string]
            $std->logradouro = '123'; [string]
            $std->numero = '123'; [string]
            $std->complemento = '123'; [string]
            $std->bairro = '123'; [string]
            $std->codMP = '123'; [string]
            $std->uf = $'123'; [string]
            $std->codPais = '123'; [string]
            $std->cep = '123'; [string]
            $std->telefone = '123'; [string]
            $std->email = '123'; [string]
            $std->inscricaoMunicipal = '123'; [string]
            $nfse->tagTomador($std);

            $std = new stdClass();
            $std->competencia = date('Y-m-d'); [date]
            $std->regimeEspecialTributacao = '123'; [string]
            $std->optanteSimplesNacional = '123'; [string]
            $std->incentivoFiscal = '123'; [string]
            $nfse->tagDeclaracaoPrestacaoServico($std);

            $std = new stdClass();
            $std->numeroLote = 123; [int]
            $std->cnpj = '123'; [string]
            $std->inscricaoMunicipal = '123'; [string]
            $std->quantidadeRps = 1; [int]
            $nfse->tagLoteRps($std);
            
            //Cria o xml
            $xml = $nfse->monta();
            
            //Configurações para enviar a nota fiscal de serviço
            $configStd = new stdClass();
            $configStd->versao = "1.0";
            $configStd->siglaUF = $grafica->uf;
            $configStd->cidade = $cidadePrestador->cidadeUP;
            $configStd->tpAmb = $configGrafica->tpAmbiente;
            
            //Controlador Nota Fiscal de serviço
            $tools = new Tools($configStd, $certificado, $password, 'Cidade que vai ser enviada a nota em UPPERCASE');
            
            //Envio NFSE
            $resposta = $tools->envioLoteRps($xml);
            
            //Mensagem resposta
            $st = new Standardize($resposta);
            $arrayResponse = $st->toArray();
            $mensagens = isset($arrayResponse[0]['messages']) ? $arrayResponse[0]['messages'] : array();

            if(count($mensagens) > 0){
                foreach ($mensagens as $msg){
                    if($msg['Codigo'] === "E178"){
                        //SUCESSO
                    }else{
                        //REJEITADA
                    }
                    throw new \Exception('Codigo: ' . $msg['Codigo'] . ' Motivo: ' . $msg['Mensagem'] . ' Correção: ' . $msg['Correcao']);
                }
            }
            if(isset($arrayResponse[0]['Protocolo'])){
                $notaFiscal->protocolo = $arrayResponse[0]['Protocolo'];
                $notaFiscal->save();

                sleep(30);

                $ops = ['Content-Type: text/xml;charset="utf-8"',
                    'Accept: text/xml',
                    'Expect: 100-continue',
                    'Connection: Keep-Alive',
                ];

                $cnpj = $grafica->cnpj;
                $xmlConsultado = $tools->consultaLoteRps($cnpj, $notaFiscal->protocolo, $grafica->inscricaomp, $ops);
                $st2 = new Standardize($xmlConsultado);
                $arrayResponse2 = $st2->toArray();

                $mensagens2 = isset($arrayResponse2[0]['messages']) ? $arrayResponse2[0]['messages'] : array();
                if(count($mensagens2) > 0){
                    foreach ($mensagens2 as $msg){
                        $notaFiscal->status = 'REJEITADA';
                        $notaFiscal->save();
                        throw new \Exception('Codigo: ' . $msg['Codigo'] . ' Motivo: ' . $msg['Mensagem'] . ' Correção: ' . $msg['Correcao']);
                    }
                }
                $filenameXML = $_SERVER['HTTP_HOST'].'/NFSeXML/' . 'nfe_' . time() . '.xml';
                Storage::put($filenameXML, $xmlConsultado);
                $notaFiscal->pathXML = $filenameXML;
                $notaFiscal->numNFSE = $tools->getNumeroFromXML($xmlConsultado);
                $notaFiscal->save();
            }
```