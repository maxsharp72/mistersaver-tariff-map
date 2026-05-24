<?php
/**
 * Партнёрские CTA-кнопки. Ссылки через /go/{slug}/?ref={region} — обход AdBlock + трекинг.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$region_name_local = isset( $region_name ) && $region_name ? $region_name : '';
$region_slug_local = '';
if ( ! empty( $region_id ) ) {
    $region_slug_local = get_post_field( 'post_name', $region_id );
}

$headline = $region_name_local
    ? 'Платить за ЖКУ в ' . esc_html( $region_name_local ) . ' выгоднее'
    : 'Платить за ЖКУ выгоднее';

// Единый стиль синей кнопки. Высота и ширина фиксированы — обе кнопки одинаковые.
$btn_style = 'display:inline-flex !important;align-items:center !important;justify-content:center !important;gap:10px !important;padding:16px 24px !important;background:#046BD2 !important;color:#fff !important;font-weight:700 !important;font-size:15px !important;border-radius:10px !important;text-decoration:none !important;line-height:1.3 !important;box-shadow:0 2px 8px rgba(4,107,210,.22) !important;border:none !important;min-width:240px !important;text-align:center !important;';

$ico_style = 'width:20px;height:20px;flex-shrink:0;color:#fff;';
$sub_style = 'display:block;font-size:13px;color:#64748B;margin:8px 0 0 0;line-height:1.4;text-align:center;';
$erid_style = 'display:block;margin-top:4px;font-size:11px;color:#94A3B8;letter-spacing:.02em;text-align:center;';

$cashback_url = MS_Tariff_Map_Redirector::local_url( 'cashback', $region_slug_local );
$tbank_url    = MS_Tariff_Map_Redirector::local_url( 'tbank',    $region_slug_local );
?>
<section class="ms-region-page__partners" style="margin:32px 0;padding:28px 24px;background:linear-gradient(135deg,#F8FAFC 0%,#FFF 50%,#F1F5F9 100%);border-radius:16px;border:1px solid rgba(226,232,240,.8);">

    <h2 style="font-size:22px;font-weight:700;margin:0 0 8px 0;color:#0F172A;letter-spacing:-.01em;line-height:1.25;text-align:center;"><?php echo $headline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
    <p style="margin:0 auto 24px auto;color:#64748B;font-size:14px;line-height:1.5;max-width:560px;text-align:center;">Карты с кешбэком возвращают часть платежа за коммуналку и любые покупки. Выбирайте под себя.</p>

    <div style="display:flex;flex-wrap:wrap;gap:24px;justify-content:center;align-items:flex-start;">

        <div style="flex:0 1 260px;display:flex;flex-direction:column;align-items:stretch;">
            <a href="<?php echo esc_url( $cashback_url ); ?>"
               target="_blank"
               rel="sponsored nofollow noopener"
               data-offer="cashback-card"
               data-region="<?php echo esc_attr( $region_slug_local ); ?>"
               style="<?php echo esc_attr( $btn_style ); ?>">
                <svg style="<?php echo esc_attr( $ico_style ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                Твой кешбэк
            </a>
            <span style="<?php echo esc_attr( $sub_style ); ?>">Бесплатная дебетовая карта<br>навсегда и без условий</span>
        </div>

        <div style="flex:0 1 260px;display:flex;flex-direction:column;align-items:stretch;">
            <a href="<?php echo esc_url( $tbank_url ); ?>"
               target="_blank"
               rel="sponsored nofollow noopener"
               data-offer="tbank-zhku"
               data-region="<?php echo esc_attr( $region_slug_local ); ?>"
               style="<?php echo esc_attr( $btn_style ); ?>">
                <svg style="<?php echo esc_attr( $ico_style ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8"/><path d="M8 17h6"/></svg>
                Оплатить ЖКУ
            </a>
            <span style="<?php echo esc_attr( $sub_style ); ?>">Т-Банк Black + 3 месяца<br>Т-Банк Pro в подарок</span>
            <span style="<?php echo esc_attr( $erid_style ); ?>">Реклама. АО «ТБанк». ERID: LjN8KDq59</span>
        </div>

    </div>
</section>

<?php
// Трекер Яндекс.Метрики — выводим рядом с кнопками, чтобы не зависеть от wp_footer().
$_ms_opts = get_option( 'ms_tariff_map_settings', [] );
$_ms_ym_id = isset( $_ms_opts['yandex_metrika_id'] ) ? trim( (string) $_ms_opts['yandex_metrika_id'] ) : '';
if ( preg_match( '/^\d+$/', $_ms_ym_id ) ) :
    $_ms_ym_id = (int) $_ms_ym_id;
?>
<script id="ms-tariff-map-tracker">
(function() {
  if (window.__msTariffTrackerInit) return;
  window.__msTariffTrackerInit = true;
  var COUNTER_ID = <?php echo esc_js( (string) $_ms_ym_id ); ?>;
  document.addEventListener('click', function(e) {
    var link = e.target.closest('a[data-offer]');
    if (!link || typeof ym !== 'function') return;
    var offer  = link.getAttribute('data-offer');
    var region = link.getAttribute('data-region');
    ym(COUNTER_ID, 'reachGoal', 'partner_click');
    if (offer === 'cashback-card') {
      ym(COUNTER_ID, 'reachGoal', 'partner_click_cashback');
    } else if (offer === 'tbank-zhku') {
      ym(COUNTER_ID, 'reachGoal', 'partner_click_tbank');
    }
    ym(COUNTER_ID, 'params', { partner_offer: offer, partner_region: region || 'archive' });
  }, { capture: true });
})();
</script>
<?php endif; ?>
