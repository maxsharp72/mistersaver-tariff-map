<?php
/**
 * Партнёрский CTA-блок (платите ЖКУ с кэшбэком).
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$settings = get_option( 'ms_tariff_map_settings', [] );
// Приоритет: индивидуальная ссылка региона → глобальная.
$partner_url = MS_Tariff_Map_ACF::get( $region_id, 'partner_link_payment' )
    ?: ( $settings['partner_payment_url'] ?? '' );
$cashback_url = MS_Tariff_Map_ACF::get( $region_id, 'partner_link_cashback' )
    ?: ( $settings['partner_cashback_url'] ?? '' );

if ( ! $partner_url && ! $cashback_url ) return;
?>
<section class="cta-block">
    <div class="cta-block__text">
        <h3>Платите ЖКУ без комиссии и с кэшбэком</h3>
        <p>Подключите кэшбэк-карту партнёра MisterSaver — возвращайте до&nbsp;5% от стоимости коммунальных платежей в <?php echo esc_html( $region_name ); ?>.</p>
    </div>
    <?php $url = $cashback_url ?: $partner_url; ?>
    <a href="<?php echo esc_url( $url ); ?>" class="cta-block__btn" target="_blank" rel="nofollow sponsored noopener">Подключить кэшбэк →</a>
</section>
