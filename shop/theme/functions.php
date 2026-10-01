<?php
defined('ABSPATH') || exit;
add_action('after_setup_theme', function () { add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('woocommerce'); add_theme_support('wc-product-gallery-slider'); });
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('haya2', get_stylesheet_uri(), [], filemtime(get_stylesheet_directory().'/style.css'));
    if (is_product()) wp_enqueue_script('haya2-options',get_template_directory_uri().'/product-options.js',['jquery','wc-add-to-cart-variation'],filemtime(get_stylesheet_directory().'/product-options.js'),true);
});
add_action('woocommerce_before_variations_form',function () { echo '<p class="size-help">Choose a size below to check availability and add it to your bag.</p>'; });
add_filter('woocommerce_product_add_to_cart_text',function ($text,$product) { return $product->is_type('variable') ? 'Choose size' : $text; },10,2);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
add_filter('loop_shop_columns', fn () => 4);
add_filter('woocommerce_currency_symbol', function ($symbol, $currency) { return $currency === 'AED' ? 'AED ' : $symbol; }, 10, 2);
// WooCommerce treats an empty max_price parameter as zero.
add_action('wp_loaded', function () { foreach (['min_price','max_price'] as $key) if (isset($_GET[$key]) && trim((string)$_GET[$key])==='') unset($_GET[$key]); }, 1);
function haya2_filters() {
    $category = sanitize_title(wp_unslash($_GET['collection'] ?? ''));
    ?><form class="filter-form" method="get" action="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">
    <?php if (!get_option('permalink_structure')) { ?><input type="hidden" name="post_type" value="product"><?php } ?>
    <label>Collection<select name="collection"><option value="">All abayas</option><?php foreach (['everyday'=>'Everyday','occasion'=>'Occasion','signature'=>'Signature','light-tones'=>'Light tones'] as $slug=>$label) { ?><option value="<?php echo esc_attr($slug); ?>" <?php selected($category,$slug); ?>><?php echo esc_html($label); ?></option><?php } ?></select></label>
    <label>Minimum AED<input type="number" name="min_price" min="0" value="<?php echo esc_attr($_GET['min_price'] ?? ''); ?>"></label>
    <label>Maximum AED<input type="number" name="max_price" min="0" value="<?php echo esc_attr($_GET['max_price'] ?? ''); ?>"></label>
    <label>Size<select name="haya_size"><option value="">Any size</option><?php foreach (['S','M','L','XL'] as $size) { ?><option <?php selected(sanitize_text_field(wp_unslash($_GET['haya_size'] ?? '')),$size); ?>><?php echo esc_html($size); ?></option><?php } ?></select></label>
    <label>Colour<select name="haya_colour"><option value="">Any colour</option><?php foreach (['Black','Ivory','Lilac'] as $colour) { ?><option <?php selected(sanitize_text_field(wp_unslash($_GET['haya_colour'] ?? '')),$colour); ?>><?php echo esc_html($colour); ?></option><?php } ?></select></label>
    <label><input type="checkbox" name="available" value="1" <?php checked(isset($_GET['available'])); ?>> In stock</label><button class="button">Apply filters</button><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Clear</a></form><?php
}
add_action('woocommerce_before_shop_loop','haya2_filters',15);
add_action('woocommerce_no_products_found','haya2_filters',5);
add_action('woocommerce_product_query', function ($query) {
    $tax = (array)$query->get('tax_query');
    if (!empty($_GET['collection'])) $tax[] = ['taxonomy'=>'product_cat','field'=>'slug','terms'=>sanitize_title(wp_unslash($_GET['collection']))];
    if (!empty($_GET['haya_size'])) $tax[] = ['taxonomy'=>'pa_size','field'=>'slug','terms'=>sanitize_title(wp_unslash($_GET['haya_size']))];
    if (!empty($_GET['haya_colour'])) $tax[] = ['taxonomy'=>'pa_colour','field'=>'slug','terms'=>sanitize_title(wp_unslash($_GET['haya_colour']))];
    $query->set('tax_query',$tax);
    if (isset($_GET['available'])) { $meta = (array)$query->get('meta_query'); $meta[]=['key'=>'_stock_status','value'=>'instock']; $query->set('meta_query',$meta); }
    if (isset($_GET['available']) && !empty($_GET['haya_size'])) {
        $variations=get_posts(['post_type'=>'product_variation','post_status'=>'publish','numberposts'=>-1,'meta_query'=>[['key'=>'attribute_pa_size','value'=>sanitize_title(wp_unslash($_GET['haya_size']))],['key'=>'_stock_status','value'=>'instock']]]);
        $parents=array_unique(array_map(fn($variation)=>(int)$variation->post_parent,$variations));
        $existing=$query->get('post__in');
        $query->set('post__in',($existing ? array_values(array_intersect($existing,$parents)) : $parents) ?: [0]);
    }
});
add_action('woocommerce_before_checkout_form', function () { echo '<div class="demo-label">Local demo checkout · Sample products and shipping rates · No real payments</div>'; }, 5);
