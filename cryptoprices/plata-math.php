<?php

// Adam Soares 26/03/26

function deploy_plata_rates($_MATICUSD, $_POLEUR, $_POLBRL, $_qtd_plata_pool__0x0E1_671a6, $_qtd_wmatic_pool__0x0E1_671a6, $_PLT_circulating_supply): array
{

    $_qtd_plata_pool__0x0E1_671a6 = (float)str_replace(',', '', $_qtd_plata_pool__0x0E1_671a6);
    $_qtd_wmatic_pool__0x0E1_671a6 = (float)str_replace(',', '', $_qtd_wmatic_pool__0x0E1_671a6);

    $USDPLT = ((float)$_qtd_plata_pool__0x0E1_671a6 / ((float)$_qtd_wmatic_pool__0x0E1_671a6 * $_MATICUSD) );
    $PLTUSD = round(1 / $USDPLT, 8);
    
    $PLTWMATIC = (float)$_qtd_wmatic_pool__0x0E1_671a6 / (float)$_qtd_plata_pool__0x0E1_671a6;
    $WMATICPLT = round(1 / $PLTWMATIC, 8);
    
    $PLTMATIC = $PLTWMATIC;
    $MATICPLT = $WMATICPLT;
    $PLTEUR = $PLTWMATIC * $_POLEUR;
    $PLTBRL = $PLTWMATIC * $_POLBRL;
    
    $EURPLT = round(1 / $PLTEUR, 8);
    $BRLPLT = round(1 / $PLTBRL, 8);
    
    $PLTmarketcapUSD = round($_PLT_circulating_supply * $PLTUSD, 4);
    $PLTmarketcapEUR = round($_PLT_circulating_supply * $PLTEUR, 4);
    $PLTmarketcapBRL = round($_PLT_circulating_supply * $PLTBRL, 4);

    return [
        'plt_prices' => [
            'PLTWMATIC' => $PLTWMATIC,
            'PLTMATIC' => $PLTMATIC,
            'PLTUSD' => $PLTUSD,
            'PLTBRL' => $PLTBRL,
            'PLTEUR' => $PLTEUR,
            'WMATICPLT' => $WMATICPLT,
            'MATICPLT' => $MATICPLT,
            'USDPLT' => $USDPLT,
            'EURPLT' => $EURPLT,
            'BRLPLT' => $BRLPLT
        ],
        'plt_marketcap' => [
            'USD' => $PLTmarketcapUSD,
            'BRL' => $PLTmarketcapBRL,
            'EUR' => $PLTmarketcapEUR
        ]
    ];
}

?>