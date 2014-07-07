<?php
class mercadoBitcoin
  {
    private static $nonce;
    private static $chave;
    private static $codigoChave;
    private static $pin;
    private static $certificado;
    private static $apiPrivada;
    private static $apiPublica;
    private static $baseUrl;

    public function __construct($chave, $codigoChave, $pin, $temTeuServidorCertificado = false)
      {
        $this->chave = $chave;
        $this->pin = $pin;
        $this->codigoChave = $codigoChave;
        $this->certificado = (bool)$temTeuServidorCertificado;
        $this->baseUrl = "https://www.mercadobitcoin.com.br/";
        $this->version = "v. beta 0.2";
        $this->apiPrivada = Array("getInfo", "OrderList", "Trade", "CancelOrder");
        $this->apiPublica = Array("ticker", "orderbook", "trades", "ticker_litecoin", "orderbook_litecoin", "trades_litecoin");
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
        foreach($this->apiPrivada as $value)
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
                return $this->doRequest("POST", $params, $header);
              }
          }
        foreach($this->apiPublica as $value)
          {
            if($api == $value)
              { 
                return $this->doRequest("GET", Array("method" => $value));
              }
          }
        return false;
      }
    
    private function doRequest($metodo, $params, $header = Array())
      {
        foreach(array_keys($params) as $key)
          {
            $params[$key] = urlencode($params[$key]);
          }
        $postFields = http_build_query($params);
        $ch = curl_init();
        $options = Array(
                          CURLOPT_HEADER         => false,
                          CURLOPT_USERAGENT      => urlencode('Módulo de API Mercado Bitcoin em PHP ' . $this->version),
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_SSL_VERIFYPEER => $this->certificate, 
                          CURLOPT_SSL_VERIFYHOST => $this->certificate
                        );
        if($metodo == "POST")
          {
            $options[CURLOPT_URL]  = $this->baseUrl . "tapi/";
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_HTTPHEADER] = $header;
            $options[CURLOPT_POSTFIELDS] = $postFields;
          }
        else
          {
            $options[CURLOPT_URL] = $this->baseUrl . "api/" . $params["method"] . "/";
          }
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
            echo "\nHTTP Status: " . $status . "\n";
            exit(0);
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
    /*#########################APIs Privadas########################################*/
    /**
     * POST https://www.mercadobitcoin.com.br/tapi/
     * retorna as informações de saldo da conta do usuário.
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
     * retorna uma lista das ordens do usuário por moeda, tipo, data e status.
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
     * cria uma ordem de compra ou venda do par escolhido: BTC/BRL ou LTC/BRL.
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
     * cancela ordens em aberto do usuário.
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
      
    /*#########################APIs Públicas######################################*/ 
    /**
     * GET https://www.mercadobitcoin.com.br/api/ticker/
     * retorna o ticker de preço do Bitcoin.
     */
    public function ticker()
      { 
        return $this->callApi(__FUNCTION__);
      }
      
    /**
     * GET https://www.mercadobitcoin.com.br/api/orderbook/
     * retorna as ofertas de compra e venda de Bitcoin.
     */ 
    public function orderbook()
      {
        return $this->callApi(__FUNCTION__);
      }
      
    /**
     * GET https://www.mercadobitcoin.com.br/api/trades/
     * retorna as negociações ou operações realizadas de Bitcoin.
     */
    public function trades()
      {
        return $this->callApi(__FUNCTION__);
      }
      
    /**
     * GET https://www.mercadobitcoin.com.br/api/ticker_litecoin/
     * retorna o ticker de preço do Litecoin.
     */
    public function ticker_litecoin()
      {
        return $this->callApi(__FUNCTION__);
      }
    
    /**
     * GET https://www.mercadobitcoin.com.br/api/orderbook_litecoin/
     * retorna as ofertas de compra e venda de Litecoin.
     */
    public function orderbook_litecoin()
      {
        return $this->callApi(__FUNCTION__);
      }
    
    /**
     * GET https://www.mercadobitcoin.com.br/api/trades_litecoin/
     * retorna as negociações ou operações realizadas de Litecoin.
     */
    public function trades_litecoin()
      {
        return $this->callApi(__FUNCTION__);
      }
  }
?>
