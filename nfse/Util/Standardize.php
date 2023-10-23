<?php

namespace NFSePHP\NFSe\Util;

use stdClass;

class Standardize
{
    /**
     * @var string $resp
     */
    private $resp;

    public function __construct($resposta)
    {
        $this->resp = $resposta;
    }

    public function toArray(): array
    {
        $xmlObj = simplexml_load_string($this->resp);

        if ($xmlObj === false) {
            return false; // Falha no carregamento do XML
        }

        $responses = [];

        if ($xmlObj->children('s', true)->Body) {
            $body = $xmlObj->children('s', true)->Body;

            foreach ($body->children() as $result) {
                $response = [];
                $response['type'] = $result->getName();

                if ($result->count() === 0) {
                    $response['success'] = true;
                } else {
                    $response['success'] = false;
                    $messages = [];

                    if (property_exists($result, 'RecepcionarLoteRpsResult')) {
                        // RecepcionarLoteRpsResponse
                        $result = $result->RecepcionarLoteRpsResult;
                        $response['NumeroLote'] = (string) $result->NumeroLote;
                        $response['DataRecebimento'] = (string) $result->DataRecebimento;
                        $response['Protocolo'] = (string) $result->Protocolo;

                        if (isset($result->ListaMensagemRetorno->MensagemRetorno)) {
                            foreach ($result->ListaMensagemRetorno->MensagemRetorno as $message) {
                                $msg = [
                                    'Codigo' => (string) $message->Codigo,
                                    'Mensagem' => (string) $message->Mensagem,
                                    'Correcao' => (string) $message->Correcao
                                ];

                                $messages[] = $msg;
                            }
                            $response['messages'] = $messages;
                        }
                    } elseif (property_exists($result, 'ConsultarLoteRpsResult')) {
                        // ConsultarLoteRpsResponse
                        $result = $result->ConsultarLoteRpsResult;
                        $response['Situacao'] = (string) $result->Situacao;

                        if (isset($result->ListaMensagemRetorno->MensagemRetorno)) {
                            foreach ($result->ListaMensagemRetorno->MensagemRetorno as $message) {
                                $msg = [
                                    'Codigo' => (string) $message->Codigo,
                                    'Mensagem' => (string) $message->Mensagem,
                                    'Correcao' => (string) $message->Correcao
                                ];

                                $messages[] = $msg;
                            }
                            $response['messages'] = $messages;
                        }
                    }
                }

                $responses[] = $response;
            }
        }

        return $responses;
    }
}
