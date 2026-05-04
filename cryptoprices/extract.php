<?
    function get_payload ($_crypto, $_fiat) {
        return (['currency' => $_fiat, 'code' => $_crypto, 'meta' => false]);
    }

    function extract_rate_from_api_old ($_api_endpoint, $_context, $_crypto, $_fiat) {
        
        if ($_context!= false) {
            stream_context_set_option($_context, 'http', 'content', json_encode(get_payload($_crypto,$_fiat)) );
            $response = (json_decode(@file_get_contents($_api_endpoint, false, $_context), true)['rate']);
        }
        else $response = (json_decode(@file_get_contents($_api_endpoint.$_crypto), true)['quotes'][$_fiat]['price']);
        
        return (float)$response;
    }
     
    function extract_rate_from_api ($_api_company, $_api_endpoint, $_api_context, $_crypto, $_fiat) {

        switch ($_api_company) {
            case 'coinmarketcap' :
                $response = json_decode(@file_get_contents($_api_endpoint.'?symbol='.$_crypto.'&convert='.$_fiat, false ,$_api_context), true)['data'][$_crypto]['quote'][$_fiat]['price'];
            case 'livecoinwatch': 
                $response = json_decode(@file_get_contents($_api_endpoint.$_crypto), true)['quotes'][$_fiat]['price'];
            case 'coinpaprika' :
                $response = json_decode(@file_get_contents($_api_endpoint.$_crypto."?quotes={$_fiat}"), true)['quotes'][$_fiat]['price'];
            default:
        }

        return (float)$response;
    }    

?>
