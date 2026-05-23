<?php
/**
 * Партнёрские CTA-кнопки. Inline-style на каждом теге — гарантированно
 * работает в любой теме и переживает любые фильтры.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$region_name_local = isset( $region_name ) && $region_name ? $region_name : '';
$headline = $region_name_local
    ? 'Платить за ЖКУ в ' . esc_html( $region_name_local ) . ' выгоднее'
    : 'Платить за ЖКУ выгоднее';

// Базовые стили карточки одной строкой. !important повсюду — перебиваем тему Astra.
$card_style = 'display:flex !important;align-items:center !important;gap:14px !important;padding:16px 18px !important;background:#fff !important;border:1px solid #e2e8f0 !important;border-radius:12px !important;text-decoration:none !important;color:#0F172A !important;box-shadow:0 1px 2px rgba(15,23,42,.04) !important;min-height:76px !important;box-sizing:border-box !important;width:auto !important;visibility:visible !important;opacity:1 !important;';
$icon_box   = 'flex-shrink:0;width:44px;height:44px;border-radius:10px;background:rgba(4,107,210,.08);color:#046BD2;display:flex;align-items:center;justify-content:center;';
$icon_svg   = 'width:22px;height:22px;display:block;';
$body_style = 'flex:1 1 auto;min-width:0;';
$title_style = 'font-weight:700;font-size:15px;line-height:1.3;color:#0F172A;margin-bottom:4px;';
$sub_style  = 'font-size:13px;color:#64748B;line-height:1.4;';
$arrow_box  = 'flex-shrink:0;width:24px;height:24px;color:#94A3B8;display:flex;align-items:center;justify-content:center;';
?>
<section class="ms-partner-cta" aria-label="Партнёрские предложения для оплаты ЖКУ" style="margin:32px 0;padding:24px;background:linear-gradient(135deg,#F0F9FF 0%,#FFF 50%,#FEF3C7 100%);border-radius:16px;border:1px solid rgba(226,232,240,.8);">
    <div style="margin-bottom:20px;">
        <h2 class="ms-partner-cta__title" style="font-size:24px;font-weight:700;margin:0 0 8px;color:#0F172A;letter-spacing:-.01em;line-height:1.2;"><?php echo $headline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
        <p style="margin:0;color:#64748B;font-size:14px;line-height:1.5;max-width:640px;">Карты с кешбэком возвращают часть платежа за коммуналку и любые покупки. Выбирайте под себя — мы подобрали два проверенных варианта.</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-top:20px;">

        <a class="ms-partner-card"
           href="https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=7249"
           target="_blank"
           rel="sponsored nofollow noopener noreferrer"
           data-offer="cashback-card"
           style="<?php echo esc_attr( $card_style ); ?>">
            <span style="<?php echo esc_attr( $icon_box ); ?>" aria-hidden="true">
                <svg style="<?php echo esc_attr( $icon_svg ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
            </span>
            <span style="<?php echo esc_attr( $body_style ); ?>">
                <span style="<?php echo esc_attr( $title_style ); ?>;display:block;">Карта с кешбэком — без условий</span>
                <span style="<?php echo esc_attr( $sub_style ); ?>;display:block;">Дебетовая навсегда, бесплатно</span>
            </span>
            <span style="<?php echo esc_attr( $arrow_box ); ?>" aria-hidden="true">
                <svg style="width:100%;height:100%;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </span>
        </a>

        <a class="ms-partner-card"
           href="https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=6432&p=10249&erid=LjN8KDq59"
           target="_blank"
           rel="sponsored nofollow noopener noreferrer"
           data-offer="tbank-zhku"
           style="<?php echo esc_attr( $card_style ); ?>">
            <span style="<?php echo esc_attr( $icon_box ); ?>" aria-hidden="true">
                <svg style="<?php echo esc_attr( $icon_svg ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8"/><path d="M8 17h6"/></svg>
            </span>
            <span style="<?php echo esc_attr( $body_style ); ?>">
                <span style="<?php echo esc_attr( $title_style ); ?>;display:block;">Платить ЖКУ выгоднее</span>
                <span style="<?php echo esc_attr( $sub_style ); ?>;display:block;">Т-Банк Black + 3 мес Pro в подарок</span>
            </span>
            <span style="<?php echo esc_attr( $arrow_box ); ?>" aria-hidden="true">
                <svg style="width:100%;height:100%;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </span>
        </a>

    </div>

    <div style="margin-top:14px;text-align:center;font-size:11px;color:#94A3B8;letter-spacing:.02em;">
        Реклама. АО «ТБанк». ERID: LjN8KDq59
    </div>
</section>
