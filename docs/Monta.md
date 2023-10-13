$nfse = new Nfse('2.02');

$std = new stdClass();
$std->Id = '222312312312asdasd';
$std->Numero = '3321';
$nfse->taginfNfse($std);
$std = new stdClass();
$std->bc = 120.00;
$std->aliqV = 1.00;
$std->vIss = 1.20;
$std->vLiq = 120.00;
$nfse->tagValoresNfse($std);

$std = new stdClass();
$std->cpf = '09620153936';
$std->logradouro = 'Rua Presidente Costa e Silva';
$std->numero = '273';
$std->complemento = 'Casa';
$std->bairro = 'Centro';
$std->codMP = '654654654';
$std->inscricaoMunicipal = '66666666';
$std->uf = 'SC';
$std->cep = '88240000';
$std->telefone = '48999402516';
$std->email = 'derickbass4@gmail.com';
$std->razaosocial = 'TESTE DESS PRR';
$std->nomefantasia = 'TESTE DESS PRR';
$nfse->tagPrestadorServico($std);

$std = new stdClass();
$std->mp = '654654654';
$std->uf = 'SC';
$nfse->tagOrgaoGerador($std);

$std = new stdClass();
$std->vServ = 120.00;
$std->vIss = 1.20;
$std->pAliq = 1.00;
$std->issRetido = 2;
$std->itemListaServico = "1.07";
$std->codigoCnae = "9511800";
$std->codigoTributacaoMunicipio = "1.07";
$std->discriminacao = "TESTE DE EMISSÃO XML DE NFS-e";
$std->codigoMunicipio = "4202404";
$std->exigibilidadeISS = "1";
$std->municipioIncidencia = "4202404";
$nfse->tagServico($std);

$std = new stdClass();
$std->cpf = '09620153936';
$std->inscricaoMunicipal = '66666666';
$nfse->tagPrestador($std);

$std = new stdClass();
$std->cpf = '59420065987';
$std->razaosocial = 'Rastatabacanavaravaisentandonavassoura';
$std->logradouro = 'Rua Presidente Costa e Silva';
$std->inscricaoMunicipal = '999999';
$std->numero = '273';
$std->complemento = 'Casa';
$std->bairro = 'Centro';
$std->codMP = '654654654';
$std->uf = 'SC';
$std->cep = '88240000';
$std->telefone = '48999402516';
$std->email = 'derickbass4@gmail.com';
$nfse->tagTomador($std);

$std = new stdClass();
$std->competencia = "2023-10-13";
$std->regimeEspecialTributacao = "1";
$std->optanteSimplesNacional = "2";
$std->incentivoFiscal = "2";
$nfse->tagDeclaracaoPrestacaoServico($std);

$xml = $nfse->monta();