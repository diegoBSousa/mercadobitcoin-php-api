<?php
class mercadoBitcoin
  {
    private static $nonce;
    private static $chave;
    private static $codigoChave;
    private static $pin;
    private static $certificado;
    private static $apiList;
    private static $baseUrl;

    public function __construct($chave, $codigoChave, $pin, $temTeuServidorCertificado = false)
      {
        $this->chave = $chave;
        $this->pin = $pin;
        $this->codigoChave = $codigoChave;
        $this->certificado = (bool)$temTeuServidorCertificado;
        $this->baseUrl = "https://www.mercadobitcoin.com.br/tapi/";
        $this->version = "v. beta 0.1";
        $this->apiList = Array("getInfo", "OrderList", "Trade", "CancelOrder");
        
        /*
        getInfo:     retorna as informações de saldo da conta do usuário.
        OrderList:   retorna uma lista das ordens do usuário por moeda, tipo, data e status.
        Trade:       cria uma ordem de compra ou venda do par escolhido: BTC/BRL ou LTC/BRL.
        CancelOrder: cancela ordens em aberto do usuário.
    */
      }

    private function nonce()
      {
        list($usec, $sec) = explode(" ", microtime());
        return (int)((float)$usec + (float)$sec);
      }
      
    private function signMessage($message)
      {
        $signedMessage = hash_hmac('sha512', $message, $this->codigoChave);
        return $signedMessage;
      }
     
    private function callApi($api, $params = Array())
      {
        foreach($this->apiList as $value)
          {
            if($api == $value)
              {
                $this->nonce = $this->nonce();
                $header = Array();
                $message = $value . ":" . $this->pin . ":" . $this->nonce;
                $header[] = "Sign: " . $this->signMessage($message);
                $header[] = "Key: "  . $this->chave;
                $params["method"] = $value;
                $params["tonce"]  = $this->nonce;
                return $this->doRequest($header, $params);
              }
          }
        return false;
      }
    
    private function doRequest($header, $params)
      {
        foreach(array_keys($params) as $key)
          {
            $params[$key] = urlencode($params[$key]);
          }
        $postFields = http_build_query($params);
        $ch = curl_init();
        $options = Array(
                          CURLOPT_URL            => $this->baseUrl,
                          CURLOPT_POST           => true,
                          CURLOPT_HEADER         => false,
                          CURLOPT_HTTPHEADER     => $header,
                          CURLOPT_USERAGENT      => urlencode('Módulo de API Mercado Bitcoin em PHP ' . $this->version),
                          CURLOPT_POSTFIELDS     => $postFields,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_SSL_VERIFYPEER => $this->certificate, 
                          CURLOPT_SSL_VERIFYHOST => $this->certificate
                        );
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status == 200)
          {
            return json_decode($response, true);
          }
        else
          {
            /* Debug */
            var_dump($response);
            var_dump($status);
          }
        return false;
      }
    
    private function removeVoid($params = Array())
      {
        $return = Array();
        foreach($params as $key => $value)
          {
            if($value != "")
              {
                $return[$key] = $value;
              }
          }
        return $return;
      }
    
    /**
     * POST https://www.mercadobitcoin.com.br/tapi/
     * @return array JSON results
     *  success: indicador de sucesso da requisição. Retorna 1 se requisição bem sucedida.
     *  return: vetor de dados de retorno deste método.
     *    funds: lista de valores do saldo da conta: btc = Bitcoin, ltc = Litecoin e brl = Reais.
     *    server_time: timestamp do servidor.
     *    open_orders: soma do número de ordens em aberto, incluindo compra e venda de todos os pares.
     */
    public function getInfo()
      { 
        return $this->callApi(__FUNCTION__);
      }
      
    /**
     * POST https://www.mercadobitcoin.com.br/tapi/
     * @param string $pair
     *  pair: (obrigatório) par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins
     *  e 'ltc_brl' para ordens de compra e venda de Litecoins.
     * @param string $type
     *  type: tipo da ordem: 'buy' para ordens de compra e 'sell' para ordens de venda.
     * @param string $status
     *  status: status da ordem: 'active' para ordens ainda ativas ou em aberto,
     *  'canceled' para ordens que foram canceladas e 'completed' para ordens que foram completadas ou preenchidas.
     * @param int $from_id
     *  from_id: id inicial de ordem para ser listado.
     * @param int $end_id
     *  end_id: id final de ordem para ser listado.
     * @param string $since
     *   since: timestamp inicial de criação da ordem para ser listado.
     * @param string $end
     *   end: timestamp final de criação da ordem para ser listado.
     * @return array JSON results    
        success: indicador de sucesso da requisição. Retorna 1 se requisição bem sucedida.
        return: dados de retorno deste método: retorna as ordens identificadas pelo id.
          status: status da ordem: 'active' para ordens ainda ativas ou em aberto, 'canceled' para ordens que foram canceladas e 'completed' para ordens que foram completadas ou preenchidas.
          created: timestamp da criação da ordem.
          price: preço unitário em Reais de compra ou venda da ordem.
          volume: volume de compra ou venda de criptomoeda da ordem, seja Bitcoin ou Litecoin.
          pair: par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins e 'ltc_brl' para ordens de compra e venda de Litecoins.
          type: tipo da ordem: 'buy' para ordens de compra e 'sell' para ordens de venda.
          operations: lista das operações que a ordem sofreu pelo id da operação. Acontecerá com ordens que sofreram alguma execução:
            volume: volume de criptomoeda executado nesta operação, seja Bitcoin ou Litecoin.
            price: preço unitário em Reais executado nesta operação.
            rate: taxa em percentual aplicada nesta operação.
            created: timestamp operaçao.
     */
    public function OrderList($pair, $type = "", $status = "", $from_id = "", $end_id = "", $since = "", $end = "")
      {
        return $this->callApi(
                               __FUNCTION__,
                               $this->removeVoid(
                                                  Array(
                                                         "pair"    => $pair,
                                                         "type"    => $type,
                                                         "status"  => $status,
                                                         "from_id" => $from_id,
                                                         "end_id"  => $end_id,
                                                         "since"   => $since,
                                                         "end"     => $end
                                                       )
                                                )
                             );
      }
      
    /**
     * POST https://www.mercadobitcoin.com.br/tapi/
     * @param string $pair
     *   pair: Obrigatório. Par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins
     *   e 'ltc_brl' para ordens de compra e venda de Litecoins.
     * @param string $type
     *   type: Obrigatório. Tipo da ordem: 'buy' para ordens de compra e 'sell' para ordens de venda.
     * @param float $volume
     *   volume: Obrigatório. Volume de criptomoeda para compra ou venda, seja Bitcoin ou Litecoin.
     * @param float $price
     *   price: preço unitário em Reais para compra ou venda.
     * @return array JSON results
         success: indicador de sucesso da requisição. Retorna 1 se requisição bem sucedida.
         return: dados de retorno deste método: retorna o id e os dados da ordem cancelada.
           status: status da ordem: 'active' para ordens ainda ativas ou em aberto, 'canceled' para ordens que foram canceladas e 'completed' para ordens que foram completadas ou preenchidas.
           created: timestamp da criação da ordem.
           price: preço unitário em Reais de compra ou venda da ordem.
           volume: volume de compra ou venda de criptomoeda da ordem, seja Bitcoin ou Litecoin.
           pair: par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins e 'ltc_brl' para ordens de compra e venda de Litecoins.
           type: tipo da ordem: 'buy' para ordens de compra e 'sell' para ordens de venda.
           operations: lista das operações que a ordem sofreu pelo id da operação. Acontecerá com ordens que sofreram alguma execução já em sua criação:
             volume: volume de criptomoeda executado nesta operação, seja Bitcoin ou Litecoin.
             price: preço unitário em Reais executado nesta operação.
             rate: taxa em percentual aplicada nesta operação.
             created: timestamp operaçao.
     */
    public function Trade($pair, $type, $volume, $price)
      {
        return $this->callApi(
                               __FUNCTION__,
                               Array(
                                      "pair"   => $pair,
                                      "type"   => $type,
                                      "volume" => (float)$volume,
                                      "price"  => (float)$price
                                    )
                             );
      }
    
    /**
     * POST https://www.mercadobitcoin.com.br/tapi/
     * @param string $pair
     *   pair: Obrigatório. Par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins
     *   e 'ltc_brl' para ordens de compra e venda de Litecoins.
     * @param int $order_id
     *   order_id: Obrigatório. id da ordem.
     * @return array JSON results
         success: indicador de sucesso da requisição. Retorna 1 se requisição bem sucedida.
         return: dados de retorno deste método: retorna o id e os dados da ordem criada.
           status: status da ordem: 'active' para ordens ainda ativas ou em aberto, 'canceled' para ordens que foram canceladas e 'completed' para ordens que foram completadas ou preenchidas.
           created: timestamp da criação da ordem.
           price: preço unitário em Reais de compra ou venda da ordem.
           volume: volume de compra ou venda de criptomoeda da ordem, seja Bitcoin ou Litecoin.
           pair: par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins e 'ltc_brl' para ordens de compra e venda de Litecoins.
           type: tipo da ordem: 'buy' para ordens de compra e 'sell' para ordens de venda.
           operations: lista das operações que a ordem sofreu pelo id da operação. Acontecerá com ordens que sofreram alguma execução já em sua criação:
             volume: volume de criptomoeda executado nesta operação, seja Bitcoin ou Litecoin.
             price: preço unitário em Reais executado nesta operação.
             rate: taxa em percentual aplicada nesta operação.
             created: timestamp operaçao.
     */
    public function CancelOrder($pair, $order_id)
      {
        return $this->callApi(
                               __FUNCTION__,
                               Array(
                                      "pair"     => $pair,
                                      "order_id" => (int)$order_id
                                    )
                             );
      }
  }     
?>
