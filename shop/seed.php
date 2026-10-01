<?php
// CLI-only local setup. Existing stock and orders survive reruns.
if (PHP_SAPI !== 'cli') exit;
define('WP_INSTALLING', !file_exists(__DIR__.'/../work/shop-runtime/admin-credentials.txt'));
require __DIR__.'/../work/shop-runtime/wordpress/wordpress/wp-load.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
require_once ABSPATH.'wp-admin/includes/upgrade.php';
if (!is_blog_installed()) {
    $password=bin2hex(random_bytes(12));
    wp_install('Haya2','haya2_admin','hello@example.test',false,'',$password);
    file_put_contents(__DIR__.'/../work/shop-runtime/admin-credentials.txt',"Username: haya2_admin\nPassword: $password\nURL: http://127.0.0.1:8088/wp-admin/\n");
}
wp_installing(false);
foreach (['woocommerce/woocommerce.php','haya2-demo/haya2-demo.php','woocommerce-gateway-stripe/woocommerce-gateway-stripe.php'] as $plugin) {
    $error=activate_plugin($plugin); if (is_wp_error($error)) throw new RuntimeException($error->get_error_message());
}
if (!class_exists('WooCommerce')) { require WP_PLUGIN_DIR.'/woocommerce/woocommerce.php'; WooCommerce::instance(); }
if (!WC()->product_factory) WC()->init();
if (!get_option('woocommerce_version')) WC_Install::install();
WC_Post_Types::register_taxonomies(); WC_Post_Types::register_post_types();
switch_theme('haya2');
$options=['woocommerce_currency'=>'AED','woocommerce_default_country'=>'AE','woocommerce_price_num_decimals'=>2,'woocommerce_calc_taxes'=>'no','woocommerce_enable_guest_checkout'=>'yes','woocommerce_enable_signup_and_login_from_checkout'=>'yes','woocommerce_manage_stock'=>'yes','woocommerce_hold_stock_minutes'=>30,'woocommerce_ship_to_countries'=>'specific','woocommerce_specific_ship_to_countries'=>['AE'],'woocommerce_allowed_countries'=>'specific','woocommerce_specific_allowed_countries'=>['AE'],'woocommerce_coming_soon'=>'no','woocommerce_store_pages_only'=>'no','woocommerce_demo_store'=>'no','blog_public'=>0,'woocommerce_store_address'=>'Local demonstration store','woocommerce_store_city'=>'Dubai','woocommerce_store_postcode'=>'00000','timezone_string'=>'Asia/Dubai'];
foreach ($options as $key=>$value) update_option($key,$value);
update_option('woocommerce_shipping_hide_rates_when_free','yes');
$stripe=get_option('woocommerce_stripe_settings',[]); $stripe['enabled']='no'; $stripe['testmode']='yes'; update_option('woocommerce_stripe_settings',$stripe);
function haya_page($slug,$title,$content) {
    $page=get_page_by_path($slug);
    if ($page) { wp_update_post(['ID'=>$page->ID,'post_title'=>$title,'post_content'=>$content,'post_status'=>'publish']); return $page->ID; }
    return wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>$content]);
}
$home=haya_page('home','Haya2',''); update_option('show_on_front','page'); update_option('page_on_front',$home);
foreach (['shop'=>['Abayas',''],'cart'=>['Your bag','[woocommerce_cart]'],'checkout'=>['Checkout','[woocommerce_checkout]'],'myaccount'=>['My account','[woocommerce_my_account]']] as $slug=>[$title,$content]) update_option('woocommerce_'.$slug.'_page_id',haya_page($slug,$title,$content));
haya_page('delivery-returns','Delivery & returns','<p>Local demonstration policy. UAE delivery: AED 25, or free delivery from AED 300 after discounts. Sample delivery estimate: 2–4 business days. No real orders are dispatched.</p><p>Sample return window: 14 days for unworn items. Rates and policies are placeholders for this local boutique.</p>');
update_option('wp_page_for_privacy_policy',haya_page('local-privacy','Local demo privacy','<p>This local Haya2 demo stores checkout details, orders, account information, and cart cookies on this computer. Use fictitious information when testing. Order emails are suppressed. The simulated payment method sends no payment data to a provider.</p>'));
haya_page('size-guide','Find your fit','<p>The demo collection offers S, M, L and XL. Choose your size on the product page to see available stock.</p><p>These are sample variations. Final garment measurements, lengths and a verified size chart must be supplied for real products.</p>');
$credits=json_decode(file_get_contents(__DIR__.'/images.json'),true); $html='<p>Photographs supplied for the Haya2 local concept boutique. Sample product names and prices are illustrative.</p><ul>';
foreach ($credits as $credit) $html.='<li><a href="'.esc_url($credit['source']).'">'.esc_html($credit['name'].' — '.$credit['author'].' ('.$credit['license'].')').'</a></li>';
haya_page('image-credits','Photo credits',$html.'</ul>');
function haya_term($name,$taxonomy,$slug,$parent=0) {
    $term=get_term_by('slug',$slug,$taxonomy); if ($term) return $term->term_id;
    $term=wp_insert_term($name,$taxonomy,['slug'=>$slug,'parent'=>$parent]);
    if (is_wp_error($term)) throw new RuntimeException($term->get_error_message()); return (int)$term['term_id'];
}
$root=haya_term('Abayas','product_cat','abayas'); $cats=[];
foreach (['everyday'=>'Everyday','occasion'=>'Occasion','signature'=>'Signature','light-tones'=>'Light tones'] as $slug=>$label) $cats[$slug]=haya_term($label,'product_cat',$slug,$root);
foreach (['size'=>'Size','colour'=>'Colour'] as $slug=>$label) {
    if (!wc_attribute_taxonomy_id_by_name($slug)) { wc_create_attribute(['name'=>$label,'slug'=>$slug,'type'=>'select','order_by'=>'menu_order','has_archives'=>false]); delete_transient('wc_attribute_taxonomies'); WC_Cache_Helper::invalidate_cache_group('woocommerce-attributes'); }
    if (!taxonomy_exists('pa_'.$slug)) register_taxonomy('pa_'.$slug,['product'],['hierarchical'=>false,'label'=>$label,'public'=>false]);
}
$sizes=['S','M','L','XL']; $size_terms=[]; foreach ($sizes as $size) $size_terms[]=haya_term($size,'pa_size',sanitize_title($size));
// Retire previous samples to Trash; their order history stays recoverable.
foreach (wc_get_products(['limit'=>-1,'status'=>'publish']) as $old) if (!str_starts_with($old->get_sku(),'HAYA2-AB-')) wp_trash_post($old->get_id());
$catalog=[
 ['Noir botanical abaya','signature','Black',395,'A flowing black silhouette with a tonal botanical pattern.'],
 ['Midnight bloom abaya','occasion','Black',425,'Statement floral detailing for evenings worth remembering.'],
 ['Silver line abaya','signature','Black',345,'Graphic lines, generous movement, and a quietly striking finish.'],
 ['The embellished abaya','occasion','Black',450,'A full-length silhouette framed by delicate embellished detailing.'],
 ['Lace edge abaya','everyday','Black',295,'A timeless black layer finished with a delicate lace edge.'],
 ['The flowing abaya','everyday','Black',275,'An effortless, fluid silhouette for your everyday wardrobe.'],
 ['Ivory botanical abaya','light-tones','Ivory',395,'Soft ivory tones and a subtle botanical pattern.'],
 ['Lilac daydream abaya','light-tones','Lilac',365,'A light, patterned silhouette with a gentle lilac palette.']
];
require_once ABSPATH.'wp-admin/includes/image.php';
foreach ($catalog as $index=>[$name,$cat,$colour,$price,$description]) {
    $sku='HAYA2-AB-'.str_pad((string)($index+1),3,'0',STR_PAD_LEFT); $existing=wc_get_product_id_by_sku($sku);
    $product=$existing ? wc_get_product($existing) : new WC_Product_Variable();
    $product->set_name($name); $product->set_sku($sku); $product->set_status('publish'); $product->set_category_ids([$root,$cats[$cat]]); $product->set_menu_order($index); $product->set_description('<p>'.esc_html($description).'</p><p>Sample product for the local Haya2 boutique. Photography is illustrative; prices, sizing and stock are for testing.</p>'); $product->set_short_description($description); $product->set_weight('0.5');
    $size_attr=new WC_Product_Attribute(); $size_attr->set_id(wc_attribute_taxonomy_id_by_name('size')); $size_attr->set_name('pa_size'); $size_attr->set_options($size_terms); $size_attr->set_visible(true); $size_attr->set_variation(true);
    $colour_attr=new WC_Product_Attribute(); $colour_attr->set_id(wc_attribute_taxonomy_id_by_name('colour')); $colour_attr->set_name('pa_colour'); $colour_attr->set_options([haya_term($colour,'pa_colour',sanitize_title($colour))]); $colour_attr->set_visible(true);
    $product->set_attributes([$size_attr,$colour_attr]); $id=$product->save();
    if (!$product->get_image_id()) {
        $image='abaya-'.($index+1).'.jpg'; $upload=wp_upload_bits($image,null,file_get_contents(__DIR__.'/theme/images/abayas/'.$image));
        if ($upload['error']) throw new RuntimeException($upload['error']);
        $attachment=wp_insert_attachment(['post_mime_type'=>'image/jpeg','post_title'=>$name,'post_status'=>'inherit'],$upload['file'],$id);
        wp_update_attachment_metadata($attachment,wp_generate_attachment_metadata($attachment,$upload['file'])); update_post_meta($attachment,'_wp_attachment_image_alt',$name);
        $product->set_image_id($attachment); $product->save();
    }
    if (!$existing) foreach ($sizes as $i=>$size) {
        $variation=new WC_Product_Variation(); $variation->set_parent_id($id); $variation->set_attributes(['pa_size'=>sanitize_title($size)]); $variation->set_regular_price($price); $variation->set_manage_stock(true); $variation->set_stock_quantity($index===1 && $i===3 ? 0 : 8); $variation->set_stock_status($index===1 && $i===3 ? 'outofstock':'instock'); $variation->set_sku($sku.'-'.$size); $variation->save();
    }
    wc_delete_product_transients($id); clean_post_cache($id); WC_Product_Variable::sync($id);
}
if (!get_option('haya2_shipping_seeded')) {
    $zone=new WC_Shipping_Zone(); $zone->set_zone_name('United Arab Emirates'); $zone->add_location('AE','country'); $zone->save();
    $flat=$zone->add_shipping_method('flat_rate'); update_option('woocommerce_flat_rate_'.$flat.'_settings',['enabled'=>'yes','title'=>'UAE delivery','tax_status'=>'none','cost'=>'25']);
    $free=$zone->add_shipping_method('free_shipping'); update_option('woocommerce_free_shipping_'.$free.'_settings',['enabled'=>'yes','title'=>'Free UAE delivery','requires'=>'min_amount','min_amount'=>'300','ignore_discounts'=>'no']); update_option('haya2_shipping_seeded',true);
}
if (!wc_get_coupon_id_by_code('HAYA10')) { $coupon=new WC_Coupon(); $coupon->set_code('HAYA10'); $coupon->set_discount_type('percent'); $coupon->set_amount(10); $coupon->set_individual_use(true); $coupon->set_usage_limit(100); $coupon->set_minimum_amount(50); $coupon->save(); }
flush_rewrite_rules(); echo "Haya2 abaya boutique seeded successfully.\n";
