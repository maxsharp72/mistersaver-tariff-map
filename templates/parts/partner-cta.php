<?php
/**
 * Партнёрские CTA-кнопки. Ссылки через свой домен /go/{slug}/ для обхода AdBlock.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$region_name_local = isset( $region_name ) && $region_name ? $region_name : '';
$headline = $region_name_local
    ? 'Платить за ЖКУ в ' . esc_html( $region_name_local ) . ' выгоднее'
    : 'Платить за ЖКУ выгоднее';

// Единый стиль синей кнопки.
$btn_style = 'display:inline-flex !important;align-items:center !important;gap:10px !important;padding:14px 22px !important;background:#046BD2 !important;color:#fff !important;font-weight:700 !important;font-size:16px !important;border-radius:10px !important;text-decoration:none !important;line-height:1.3 !important;box-shadow:0 2px 8px rgba(4,107,210,.22) !important;border:none !important;transition:transform .15s,box-shadow .15s,background .15s !important;';

$ico_style = 'width:20px;height:20px;flex-shrink:0;color:#fff;';
$sub_style = 'display:block;font-size:13px;color:#64748B;margin:6px 0 16px 0;line-height:1.4;';

$cashback_url = MS_Tariff_Map_Redirector::local_url( 'cashback' );
$tbank_url    = MS_Tariff_Map_Redirector::local_url( 'tbank' );
?>
<section class="ms-region-page__partners" style="margin:32px 0;padding:24px;background:linear-gradient(135deg,#F0F9FF 0%,#FFF 50%,#FEF3C7 100%);border-radius:16px;border:1px solid rgba(226,232,240,.8);">

    <h2 style="font-size:22px;font-weight:700;margin:0 0 8px 0;color:#0F172A;letter-spacing:-.01em;line-height:1.25;"><?php echo $headline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
    <p style="margin:0 0 22px 0;color:#64748B;font-size:14px;line-height:1.5;max-width:640px;">Карты с кешбэком возвращают часть платежа за коммуналку и любые покупки. Выбирайте под себя.</p>

    <p style="margin:0;">
        <a href="<?php echo esc_url( $cashback_url ); ?>"
           target="_blank"
           rel="sponsored nofollow noopener noreferrer"
           data-offer="cashback-card"
           style="<?php echo esc_attr( $btn_style ); ?>">
            <svg style="<?php echo esc_attr( $ico_style ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            Карта с кешбэком — без условий
        </a>
    </p>
    <span style="<?php echo esc_attr( $sub_style ); ?>">Дебетовая навсегда, бесплатно</span>

    <p style="margin:0;">
        <a href="<?php echo esc_url( $tbank_url ); ?>"
           target="_blank"
           rel="sponsored nofollow noopener noreferrer"
           data-offer="tbank-zhku"
           style="<?php echo esc_attr( $btn_style ); ?>">
            <svg style="<?php echo esc_attr( $ico_style ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8"/><path d="M8 17h6"/></svg>
            Платить ЖКУ выгоднее
        </a>
    </p>
    <span style="<?php echo esc_attr( $sub_style ); ?>">Т-Банк Black + 3 месяца Pro в подарок</span>

    <p style="margin:18px 0 0 0;text-align:center;font-size:11px;color:#94A3B8;letter-spacing:.02em;">
        Реклама. АО «ТБанк». ERID: LjN8KDq59
    </p>
</section>
