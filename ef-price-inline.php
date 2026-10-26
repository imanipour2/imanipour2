<?php
/**
 * Plugin Name: Enerfoam Inline Price Cart
 * Description: Four-column RTL factory price tables with per-row pack quantity and real WooCommerce cart submission.
 * Version: 1.1.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * Author: Tak Eista South (Enerfoam)
 */
defined('ABSPATH') || exit;

function ef_ip_products(): array {
    return [
        'S' => [15 => [50 => 1317, 60 => 1318], 20 => [50 => 1321, 60 => 1322], 25 => [50 => 1325, 60 => 1326], 30 => [50 => 1329, 60 => 1330]],
        'L' => [15 => [50 => 1319, 60 => 1320], 20 => [50 => 1323, 60 => 1324], 25 => [50 => 1327, 60 => 1328], 30 => [50 => 1331, 60 => 1332]],
    ];
}

/* Rendered Elementor templates can change the current global post.
   Always submit to the actual price page rather than get_permalink(). */
add_action('wp_enqueue_scripts', static function (): void {
    if (function_exists('is_page') && is_page(765)) {
        wp_enqueue_style('ef-ip-price-cart', plugin_dir_url(__FILE__) . 'price-inline.css', [], '1.1.0');
    }
});

function ef_ip_render($atts): string {
    if (!function_exists('wc_get_product')) {
        return '<p>برای مشاهده و خرید، ووکامرس باید فعال باشد.</p>';
    }
    $opts = shortcode_atts(['edge' => 'S'], (array) $atts, 'enerfoam_inline_prices');
    $edge = strtoupper(sanitize_text_field((string) $opts['edge']));
    $matrix = ef_ip_products();
    if (!isset($matrix[$edge])) { return ''; }
    wp_enqueue_style('ef-ip-price-cart', plugin_dir_url(__FILE__) . 'price-inline.css', [], '1.1.0');
    $grades = [15 => 'کلاسیک', 20 => 'پلاس', 25 => 'پرو', 30 => 'مکس'];
    $edge_label = $edge === 'S' ? 'لبه ساده' : 'لبه پلکانی';
    $post_url = get_permalink(765);
    if (!$post_url) { $post_url = home_url('/price/'); }
    ob_start();
    ?>
    <section class="ef-ip" dir="rtl" aria-label="<?php echo esc_attr('قیمت و خرید انرفوم ' . $edge_label); ?>">
      <table class="ef-ip-table">
        <caption class="screen-reader-text"><?php echo esc_html('قیمت و افزودن به سبد خرید - ' . $edge_label); ?></caption>
        <colgroup><col class="ef-ip-name-col"><col class="ef-ip-pack-col"><col class="ef-ip-area-col"><col class="ef-ip-buy-col"></colgroup>
        <thead><tr>
          <th scope="col">محصول<br>ضخامت</th>
          <th scope="col">قیمت<br>بسته<br><small>تومان</small></th>
          <th scope="col">قیمت<br>مترمربع<br><small>تومان</small></th>
          <th scope="col">تعداد<br><svg class="ef-ip-heading-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h2l2.5 11h11l2-8H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg></th>
        </tr></thead>
        <tbody>
        <?php foreach ($matrix[$edge] as $grade => $sizes): foreach ($sizes as $mm => $id):
            $product = wc_get_product($id);
            if (!$product || !$product->is_visible()) { continue; }
            $pack_price = (float) wc_get_price_to_display($product);
            $area = $mm === 50 ? 4.32 : 3.60;
            $available = $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock();
            $title = $grades[$grade] . ' ' . $grade;
            $label = $title . '، ' . $mm . ' میلی‌متر، ' . $edge_label;
        ?>
        <tr data-product-id="<?php echo esc_attr((string) $id); ?>">
          <th scope="row" class="ef-ip-name"><strong><?php echo esc_html($title); ?></strong><small><?php echo esc_html((string) $mm); ?> میلی‌متر</small></th>
          <td class="ef-ip-price"><bdi><?php echo esc_html(number_format_i18n($pack_price, 0)); ?></bdi></td>
          <td class="ef-ip-price"><bdi><?php echo esc_html(number_format_i18n($pack_price / $area, 0)); ?></bdi></td>
          <td class="ef-ip-buy">
          <?php if ($available): ?>
            <form action="<?php echo esc_url($post_url); ?>" method="post" class="ef-ip-form">
              <label for="ef-ip-<?php echo esc_attr((string) $id); ?>" class="screen-reader-text"><?php echo esc_html('تعداد بسته ' . $label); ?></label>
              <input id="ef-ip-<?php echo esc_attr((string) $id); ?>" type="number" name="quantity" min="1" step="1" <?php if ($product->managing_stock() && !$product->backorders_allowed()) { echo 'max="' . esc_attr((string) max(1, (int) $product->get_stock_quantity())) . '"'; } ?> value="1" required inputmode="numeric">
              <button type="submit" name="add-to-cart" value="<?php echo esc_attr((string) $id); ?>" aria-label="<?php echo esc_attr('افزودن ' . $label . ' به سبد خرید'); ?>" title="<?php echo esc_attr('افزودن ' . $label . ' به سبد خرید'); ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M2 3h2l2.8 12h12.4l2-9H5"/></svg><span>افزودن</span>
              </button>
            </form>
          <?php else: ?><span class="ef-ip-unavailable">ناموجود</span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endforeach; ?>
        </tbody>
      </table>
      <p class="ef-ip-date">اعتبار قیمت‌های اعلام‌شده: تا پایان ۲ آذر ۱۴۰۵</p>
      <p class="ef-ip-foot">قیمت‌ها به تومان و درب کارخانه‌اند. خرید بر حسب بسته است؛ بسته ۵۰ میلی‌متری شامل ۶ تخته و بسته ۶۰ میلی‌متری شامل ۵ تخته است. هزینه حمل جداگانه محاسبه می‌شود.</p>
    </section>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('enerfoam_inline_prices', 'ef_ip_render');
