<?php
include "class/mercadobitcoin.php";

$chave = "Aqui-Vai-A-Tua-Chave";
$codigoChave = "Aqui-Vai-O-Código-da-Chave";
$pin = "Pin-Numérico-De-4-Dígitos";


$mcb = new mercadoBitcoin($chave, $codigoChave, $pin);

$info = $mcb->getInfo();
echo "info";
print_r($info);

/*
     *  pair: (obrigatório) par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins
     *  e 'ltc_brl' para ordens de compra e venda de Litecoins.
     *  type: tipo da ordem: 'buy' para ordens de compra e 'sell' para ordens de venda.
     *  status: status da ordem: 'active' para ordens ainda ativas ou em aberto,
     *                           'canceled' para ordens que foram canceladas e
     *                           'completed' para ordens que foram completadas ou preenchidas.
     *  from_id: id inicial de ordem para ser listado.
     *  end_id: id final de ordem para ser listado.
     *  since: timestamp inicial de criação da ordem para ser listado.
     *  end: timestamp final de criação da ordem para ser listado.
 */
$orderList = $mcb->OrderList($pair = 'btc_brl', $type = 'buy', $status = '', $from_id = "", $end_id = "", $since = "", $end = "");
echo "orderList";
print_r($orderList);

/**
     *   pair: Obrigatório. Par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins
                                          'ltc_brl' para ordens de compra e venda de Litecoins.
     *   type: Obrigatório. Tipo da ordem: 'buy' para ordens de compra e
                                           'sell' para ordens de venda.
     *   volume: Obrigatório. Volume de criptomoeda para compra ou venda, seja Bitcoin ou Litecoin.
     *   price: preço unitário em Reais para compra ou venda.
     */
$trade = $mcb->Trade($pair = 'btc_brl', $type = 'buy', $volume = 0.01, $price= 0.0314);
echo "trade";
print_r($trade);


/**
     *   pair: Obrigatório. Par da ordem: 'btc_brl' para ordens de compra ou venda de Bitcoins
     *   e 'ltc_brl' para ordens de compra e venda de Litecoins.
     *   order_id: Obrigatório. id da ordem.
     */
$cancelOrder = $mcb->CancelOrder('btc_brl', 0);
echo "CancelOrder";
print_r($cancelOrder);


echo "\n\nApis Públicas\n\n";
$tickerBTC = $mcb->ticker();
echo "tickerBTC";
print_r($tickerBTC);

$orderbookBTC = $mcb->orderbook();
echo "orderbookBTC";
print_r($orderbookBTC);

$tradesBTC = $mcb->trades();
echo "tradesBTC";
print_r($tradesBTC);


$tickerLTC = $mcb->ticker_litecoin();
echo "tickerLTC";
print_r($tickerLTC);

$orderbookLTC = $mcb->orderbook_litecoin();
echo "orderbookLTC";
print_r($orderbookLTC);

$tradesLTC = $mcb->trades_litecoin();
echo "tradesLTC";
print_r($tradesLTC);
?>
