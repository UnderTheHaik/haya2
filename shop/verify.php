<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../work/shop-runtime/wordpress/wordpress/wp-load.php';
$failures=[];
function verify($condition,$message) { global $failures; if (!$condition) $failures[]=$message; }
verify(get_option('woocommerce_currency')==='AED','AED currency');
verify(count(wc_get_products(['limit'=>-1,'status'=>'publish']))===8,'8 abaya products');
foreach (wc_get_products(['limit'=>-1,'type'=>'variable','status'=>'publish']) as $product) { echo $product->get_sku().' stock='.$product->get_stock_status().' purchasable='.(int)$product->is_purchasable().' children='.count($product->get_children())."\n"; verify($product->is_in_stock(),'Variable parent stock: '.$product->get_sku()); verify(count($product->get_children())===4,'Four sizes: '.$product->get_sku()); verify(str_starts_with($product->get_sku(),'HAYA2-AB-'),'Abayas only'); }
$orders=wc_get_orders(['limit'=>-1,'status'=>'processing']);
foreach ($orders as $order) if ($order->get_payment_method()==='haya2_demo') { echo 'Demo order '.$order->get_id().' total='.$order->get_total()."\n"; foreach ($order->get_items() as $item) { $p=$item->get_product(); echo $p->get_sku().' stock='.$p->get_stock_quantity()."\n"; } }
if (isset($argv[1])) { $order=wc_get_order((int)$argv[1]); verify($order && $order->is_paid(),'Test order paid'); if ($order) foreach ($order->get_items() as $item) verify($item->get_product()->get_stock_quantity()===7,'Test variation stock reduced to 7'); }
verify((new WC_Coupon('HAYA10'))->get_amount()==10,'Coupon 10 percent');
if ($failures) { echo implode("\n",$failures)."\n"; exit(1); } echo "Verification passed.\n";
