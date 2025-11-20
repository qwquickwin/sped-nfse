<?php

namespace NFePHP\NFSe\Models\Prodam;

/**
 * Classe para a renderização dos RPS em XML para a Cidade de São Paulo
 * conforme o modelo Prodam
 *
 * @category  NFePHP
 * @package   NFePHP\NFSe\Models\Prodam\RenderRPS
 * @copyright NFePHP Copyright (c) 2016
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Roberto L. Machado <linux.rlm at gmail dot com>
 * @link      http://github.com/nfephp-org/sped-nfse for the canonical source repository
 */

use NFePHP\Common\DOMImproved as Dom;
use NFePHP\Common\Certificate;
use NFePHP\NFSe\Models\Prodam\Rps;

class RenderRPS
{
    protected static $dom;
    protected static $certificate;
    protected static $algorithm;

    public static function toXml($data, Certificate $certificate, $algorithm = OPENSSL_ALGO_SHA1, $versao)
    {
        self::$certificate = $certificate;
        self::$algorithm = $algorithm;
        $xml = '';
        if (is_object($data)) {
            return self::render($data, $versao);
        } elseif (is_array($data)) {
            foreach ($data as $rps) {
                $xml .= self::render($rps, $versao);
            }
        }

        return $xml;
    }
    
    /**
     * Monta o xml com base no objeto Rps
     * @param Rps $rps
     * @return string
     */
    private static function render(Rps $rps, $versao)
    {
        self::$dom = new Dom('1.0', 'utf-8');
        $root = self::$dom->createElement('RPS');
        $xmlnsAttribute = self::$dom->createAttribute('xmlns');
        $xmlnsAttribute->value = '';
        $root->appendChild($xmlnsAttribute);
        //tag Assinatura
        self::$dom->addChild(
            $root,
            'Assinatura',
            self::signstr($rps, $versao),
            true,
            'Tag assinatura do RPS vazia',
            true
        );
        //tag ChaveRPS
        $chaveRps = self::$dom->createElement('ChaveRPS');
        self::$dom->addChild(
            $chaveRps,
            'InscricaoPrestador',
            $rps->prestadorIM,
            true,
            "IM do prestador",
            true
        );
        self::$dom->addChild(
            $chaveRps,
            'SerieRPS',
            $rps->serieRPS,
            true,
            'Serie do RPS',
            false
        );
        self::$dom->addChild(
            $chaveRps,
            'NumeroRPS',
            $rps->numeroRPS,
            true,
            "Numero do RPS",
            true
        );
        self::$dom->appChild($root, $chaveRps, 'Adicionando tag ChaveRPS');
        //outras tags
        self::$dom->addChild(
            $root,
            'TipoRPS',
            $rps->tipoRPS,
            true,
            'Tipo de RPS',
            false
        );
        self::$dom->addChild(
            $root,
            'DataEmissao',
            $rps->dtEmiRPS,
            true,
            'Data de emissão',
            false
        );
        self::$dom->addChild(
            $root,
            'StatusRPS',
            $rps->statusRPS,
            true,
            'Status do RPS',
            false
        );
        self::$dom->addChild(
            $root,
            'TributacaoRPS',
            $rps->tributacaoRPS,
            true,
            'Tributação do RPS',
            false
        );

        if($versao == 1)
        {
            self::$dom->addChild(
                $root,
                'ValorServicos',
                $rps->valorServicosRPS,
                true,
                'Valor dos serviços',
                false
            );
        }
        
        self::$dom->addChild(
            $root,
            'ValorDeducoes',
            $rps->valorDeducoesRPS,
            true,
            'Valor das Deduções',
            true
        );
        self::$dom->addChild(
            $root,
            $versao > 1 ? 'ValorPIS' : 'ValorPis' ,
            $rps->valorPISRPS,
            true,
            'Valor do PIS',
            false
        );
        self::$dom->addChild(
            $root,
            'ValorCOFINS',
            $rps->valorCOFINSRPS,
            $versao > 1 ? true : false,
            'Valor do COFINS',
            false
        );
        self::$dom->addChild(
            $root,
            'ValorINSS',
            $rps->valorINSSRPS,
            $versao > 1 ? true : false,
            'Valor do INSS',
            false
        );
        self::$dom->addChild(
            $root,
            'ValorIR',
            $rps->valorIRRPS,
            $versao > 1 ? true : false,
            'Valor do IR',
            false
        );
        self::$dom->addChild(
            $root,
            'ValorCSLL',
            $rps->valorCSLLRPS,
            $versao > 1 ? true : false,
            'Valor do CSLL',
            false
        );

        self::$dom->addChild(
            $root,
            'CodigoServico',
            $rps->codigoServicoRPS,
            true,
            'Código do serviço',
            false
        );
        self::$dom->addChild(
            $root,
            'AliquotaServicos',
            $rps->aliquotaServicosRPS,
            true,
            'Aliquota do serviço',
            false
        );
        $issRet = 'false';
        if ($rps->issRetidoRPS) {
            $issRet = 'true';
        }
        self::$dom->addChild(
            $root,
            'ISSRetido',
            $issRet,
            true,
            'ISS Retido',
            false
        );
        //tag CPFCNPJTomador
        if ($rps->tomadorTipoDoc != '3') {
            $tomador = self::$dom->createElement('CPFCNPJTomador');
            if ($rps->tomadorTipoDoc == '2') {
                self::$dom->addChild(
                    $tomador,
                    'CNPJ',
                    $rps->tomadorCNPJCPF,
                    true,
                    "CNPJ do tomador",
                    false
                );
            } elseif ($rps->tomadorTipoDoc == '1') {
                self::$dom->addChild(
                    $tomador,
                    'CPF',
                    $rps->tomadorCNPJCPF,
                    true,
                    "CPF do tomador",
                    false
                );
            }
            self::$dom->appChild($root, $tomador, 'Adicionando tag CPFCNPJTomador');
        }
        //outras tags
        self::$dom->addChild(
            $root,
            'RazaoSocialTomador',
            $rps->tomadorRazao,
            true,
            'Razão Social do tomador',
            false
        );
        //tag EnderecoTomador
        $endtomador = self::$dom->createElement('EnderecoTomador');
        self::$dom->addChild(
            $endtomador,
            'TipoLogradouro',
            $rps->tomadorTipoLogradouro,
            true,
            'Tipo de logradouro do tomador',
            false
        );
        self::$dom->addChild(
            $endtomador,
            'Logradouro',
            $rps->tomadorLogradouro,
            true,
            'Logradouro do tomador',
            false
        );
        self::$dom->addChild(
            $endtomador,
            'NumeroEndereco',
            $rps->tomadorNumeroEndereco,
            true,
            'Numero do Logradouro do tomador',
            false
        );
        self::$dom->addChild(
            $endtomador,
            'ComplementoEndereco',
            $rps->tomadorComplementoEndereco,
            true,
            'Complemento endereço do tomador',
            false
        );
        self::$dom->addChild(
            $endtomador,
            'Bairro',
            $rps->tomadorBairro,
            true,
            'Bairro endereço do tomador',
            false
        );
        self::$dom->addChild(
            $endtomador,
            'Cidade',
            $rps->tomadorCodCidade,
            true,
            'Cidade endereço do tomador',
            false
        );
        self::$dom->addChild(
            $endtomador,
            'UF',
            $rps->tomadorSiglaUF,
            true,
            'UF endereço do tomador',
            false
        );
        self::$dom->addChild(
            $endtomador,
            'CEP',
            $rps->tomadorCEP,
            true,
            'CEP endereço do tomador',
            false
        );
        self::$dom->appChild($root, $endtomador, 'Adicionando tag EnderecoTomador');
        //outras tags
        self::$dom->addChild(
            $root,
            'EmailTomador',
            $rps->tomadorEmail,
            false,
            'Email do tomador',
            false
        );
        //tag intermediario
        //se existir incluir dados do intermediário
        if ($rps->intermediarioCNPJCPF) {
            $intermediario = self::$dom->createElement('CPFCNPJIntermediario');
            self::$dom->addChild(
                $intermediario,
                'CNPJ',
                $rps->intermediarioCNPJCPF,
                true,
                "CNPJ do intermediario",
                false
            );
            self::$dom->appChild($root, $intermediario, 'Adicionando tag CPFCNPJIntermediario');
            self::$dom->addChild(
                $root,
                'InscricaoMunicipalIntermediario',
                $rps->intermediarioIM,
                false,
                'IM do intermediario',
                false
            );
            self::$dom->addChild(
                $root,
                'EmailIntermediario',
                $rps->intermediarioEmail,
                false,
                'email do intermediario',
                false
            );
        }
        self::$dom->addChild(
            $root,
            'Discriminacao',
            $rps->discriminacaoRPS,
            true,
            'Discriminação do serviço',
            false
        );

        if($versao > 1)
        {
            self::$dom->addChild(
                $root,
                'ValorFinalCobrado',
                $rps->valorServicosRPS,
                true,
                'Valor dos serviços',
                false
            );

            self::$dom->addChild(
                $root,
                'ValorMulta',
                $rps->valorMulta,
                false,
                'Valor da multa',
                false
            );

            self::$dom->addChild(
                $root,
                'ValorJuros',
                $rps->valorJuros,
                false,
                'Valor dos juros',
                false
            );

            self::$dom->addChild(
                $root,
                'ValorIPI',
                $rps->valorIPI,
                false,
                'Valor do IPI',
                false
            );

            self::$dom->addChild(
                $root,
                'ExigibilidadeSuspensa',
                $rps->exigibilidadeSuspensa,
                true,
                'Exigibilidade Suspensa 0-nao | 1-sim',
                false
            );

            self::$dom->addChild(
                $root,
                'PagamentoParceladoAntecipado',
                $rps->pagamentoParceladoAntecipado,
                true,
                'Informe a nota fiscal de pagamento parcelado antecipado (realizado antes do fornecimento). 0-nao | 1-sim',
                false
            );

            self::$dom->addChild(
                $root,
                'NBS',
                $rps->NBS,
                true,
                'Nomenclatura Brasileira de Serviços',
                false
            );

            // escolha um dos dois elementos conforme os dados do RPS
            if (!empty($rps->cLocPrestacao)) {
                self::$dom->addChild(
                    $root,
                    'cLocPrestacao',
                    $rps->cLocPrestacao,
                    true,
                    'Código da cidade de prestação',
                    false
                );
            } elseif (!empty($rps->cPaisPrestacao)) {
                self::$dom->addChild(
                    $root,
                    'cPaisPrestacao',
                    $rps->cPaisPrestacao,
                    true,
                    'Código do país de prestação',
                    false
                );
            }

            // cria o elemento IBSCBS
            $IBSCBS = self::$dom->createElement('IBSCBS');

            // adiciona os filhos de IBSCBS diretamente
            self::$dom->addChild(
                $IBSCBS,
                'finNFSe',
                $rps->finNFSe,
                true,
                'Indicador da finalidade da emissão de NFS-e. 0 = NFS-e regular.',
                false
            );

            self::$dom->addChild(
                $IBSCBS,
                'indFinal',
                $rps->indFinal,
                true,
                'Indica operação de uso ou consumo pessoal. (0-Não ou 1-Sim).',
                false
            );

            self::$dom->addChild(
                $IBSCBS,
                'cIndOp',
                $rps->cIndOp,
                true,
                'Código indicador da operação de fornecimento, conforme tabela "código indicador de operação". Referente à tabela de indicador da operação publicada no ANEXO AnexoVII-IndOp_IBSCBS_V1.00.00-.xlsx',
                false
            );

            self::$dom->addChild(
                $IBSCBS,
                'indDest',
                $rps->indDest,
                true,
                'Indica o Destinatário dos serviços. 0 - O destinatário é o próprio tomador/adquirente identificado na NFS-e (tomador = adquirente = destinatário) 1 - O destinatário não é o próprio adquirente, podendo ser outra pessoa, física ou jurídica (ou equiparada), ou um estabelecimento diferente do indicado como tomador (tomador = adquirente ≠ destinatário).',
                false
            );

            $valores = self::$dom->createElement('valores');
            self::$dom->appChild($IBSCBS, $valores, 'Adicionando tag valores');

            $trib = self::$dom->createElement('trib');
            self::$dom->appChild($valores, $trib, 'Adicionando tag trib');

            $gIBSCBS = self::$dom->createElement('gIBSCBS');
            self::$dom->appChild($trib, $gIBSCBS, 'Adicionando tag gIBSCBS');

            self::$dom->addChild(
                $gIBSCBS,
                'cClassTrib',
                $rps->cClassTrib ?? '',
                false,
                'Código de classificação Tributária do IBS e da CBS.',
                false
            );

            self::$dom->appChild($root, $IBSCBS, 'Adicionando tag IBSCBS');

        }

        //finaliza
        self::$dom->appendChild($root);
        $xml = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', self::$dom->saveXML());
        return $xml;
    }
    
    /**
     * Cria o valor da assinatura do RPS
     * @param Rps $rps
     * @return string
     */
    private static function signstr(Rps $rps, $versao=1)
    {
        if($versao > 1)
        {
            $content = str_pad($rps->prestadorIM, 12, '0', STR_PAD_LEFT);
            $content .= str_pad($rps->serieRPS, 5, ' ', STR_PAD_RIGHT);
            $content .= str_pad($rps->numeroRPS, 12, '0', STR_PAD_LEFT);
            $content .= str_replace("-", "", $rps->dtEmiRPS);
            $content .= $rps->tributacaoRPS;
            $content .= $rps->statusRPS;
            $content .= ($rps->issRetidoRPS) ? 'S' : 'N';
            $content .= str_pad(
                str_replace(['.', ','], '', number_format($rps->valorServicosRPS, 2)),
                15,
                '0',
                STR_PAD_LEFT
            );
            $content .= str_pad(
                str_replace(['.', ','], '', number_format($rps->valorDeducoesRPS, 2)),
                15,
                '0',
                STR_PAD_LEFT
            );
            $content .= str_pad($rps->codigoServicoRPS, 5, '0', STR_PAD_LEFT);
            $content .= $rps->tomadorTipoDoc;
            $content .= str_pad($rps->tomadorCNPJCPF, 14, '0', STR_PAD_LEFT);
            if ($rps->intermediarioTipoDoc != '3' && $rps->intermediarioCNPJCPF != '') {
                $content .= $rps->intermediarioTipoDoc;
                $content .= str_pad($rps->intermediarioCNPJCPF, 14, '0', STR_PAD_LEFT);
                $content .= $rps->intermediarioISSRetido;
            }
        }
        else
        {
            $content = str_pad($rps->prestadorIM, 8, '0', STR_PAD_LEFT);
            $content .= str_pad($rps->serieRPS, 5, ' ', STR_PAD_RIGHT);
            $content .= str_pad($rps->numeroRPS, 12, '0', STR_PAD_LEFT);
            $content .= str_replace("-", "", $rps->dtEmiRPS);
            $content .= $rps->tributacaoRPS;
            $content .= $rps->statusRPS;
            $content .= ($rps->issRetidoRPS) ? 'S' : 'N';
            $content .= str_pad(
                str_replace(['.', ','], '', number_format($rps->valorServicosRPS, 2)),
                15,
                '0',
                STR_PAD_LEFT
            );
            $content .= str_pad(
                str_replace(['.', ','], '', number_format($rps->valorDeducoesRPS, 2)),
                15,
                '0',
                STR_PAD_LEFT
            );
            $content .= str_pad($rps->codigoServicoRPS, 5, '0', STR_PAD_LEFT);
            $content .= $rps->tomadorTipoDoc;
            $content .= str_pad($rps->tomadorCNPJCPF, 14, '0', STR_PAD_LEFT);
            if ($rps->intermediarioTipoDoc != '3' && $rps->intermediarioCNPJCPF != '') {
                $content .= $rps->intermediarioTipoDoc;
                $content .= str_pad($rps->intermediarioCNPJCPF, 14, '0', STR_PAD_LEFT);
                $content .= $rps->intermediarioISSRetido;
            }
        }

        //$contentBytes = self::getBytes($content);
        $signature = base64_encode(self::$certificate->sign($content, self::$algorithm));
        return $signature;
    }
}
