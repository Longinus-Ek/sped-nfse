<?php

namespace NFSePHP\NFSe\Traits;

trait Getter
{
    public function GetMunicipioName($codigo)
    {

        $csvFile = base_path('packages/sped-nfse/nfse/Traits/estados.csv');

        $file = fopen($csvFile, 'r');

        if ($file !== false) {

            $dados = array();
            //Transformando os dados
            while (($row = fgetcsv($file, 1000)) !== false) {
                $dadosLimpos = array();

                foreach ($row as $value) {
                    $dadosLimpos[] = $value;
                }
                $dados[] = $dadosLimpos;
            }
            unset($dados[0]);
            try {
                foreach ($dados as $key => $dado) {
                    if($dado[0] === $codigo){
                        return $dado[1];
                    }
                }
            } catch (\Exception $e) {
                return $e;
            }

            fclose($file);
        }
        else{
            return false;
        }
    }
}
