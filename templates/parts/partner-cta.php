<?php
/**
 * Партнёрские CTA — максимально простые текстовые ссылки.
 * Никаких вложенных блоков внутри <a>, никаких flex/grid — только текст и стиль.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$region_name_local = isset( $region_name ) && $region_name ? $region_name : '';
$headline = $region_name_local
    ? 'Платить за ЖКУ в ' . esc_html( $region_name_local ) . ' выгоднее'
    : 'Платить за ЖКУ выгоднее';

// Стили для ссылки-кнопки (применяются inline). !important — побеждает CSS темы.
$btn_style = 'display:inline-block !important;padding:14px 22px !important;background:#046BD2 !important;color:#fff !important;font-weight:700 !important;font-size:16px !important;border-radius:10px !important;text-decoration:none !important;margin:6px 8px 6px 0 !important;line-height:1.3 !important;box-shadow:0 2px 6px rgba(4,107,210,.18) !important;border:none !important;';

$btn_secondary = 'display:inline-block !important;padding:14px 22px !important;background:#fff !important;color:#046BD2 !important;font-weight:700 !important;font-size:16px !important;border-radius:10px !important;text-decoration:none !important;margin:6px 8px 6px 0 !important;line-height:1.3 !important;box-shadow:0 2px 6px rgba(15,23,42,.06) !important;border:1.5px solid #046BD2 !important;';

$sub_style = 'display:block;font-size:13px;color:#64748B;margin:4px 0 14px 0;line-height:1.4;';
?>
<section class="ms-partner-cta" aria-label="Партнёрские предложения для оплаты ЖКУ" style="margin:32px 0;padding:24px;background:linear-gradient(135deg,#F0F9FF 0%,#FFF 50%,#FEF3C7 100%);border-radius:16px;border:1px solid rgba(226,232,240,.8);">

    <h2 style="font-size:22px;font-weight:700;margin:0 0 8px 0;color:#0F172A;letter-spacing:-.01em;line-height:1.25;"><?php echo $headline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
    <p style="margin:0 0 18px 0;color:#64748B;font-size:14px;line-height:1.5;max-width:640px;">Карты с кешбэком возвращают часть платежа за коммуналку и любые покупки. Выбирайте под себя.</p>

    <p style="margin:0 0 6px 0;">
        <a href="https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=7249"
           target="_blank"
           rel="sponsored nofollow noopener noreferrer"
           data-offer="cashback-card"
           style="<?php echo esc_attr( $btn_secondary ); ?>">Карта с кешбэком — без условий →</a>
    </p>
    <span style="<?php echo esc_attr( $sub_style ); ?>">Дебетовая навсегда, бесплатно</span>

    <p style="margin:0 0 6px 0;">
        <a href="https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=6432&p=10249&erid=LjN8KDq59"
           target="_blank"
           rel="sponsored nofollow noopener noreferrer"
           data-offer="tbank-zhku"
           style="<?php echo esc_attr( $btn_style ); ?>">Платить ЖКУ выгоднее →</a>
    </p>
    <span style="<?php echo esc_attr( $sub_style ); ?>">Т-Банк Black + 3 мес Pro в подарок</span>

    <p style="margin:18px 0 0 0;text-align:center;font-size:11px;color:#94A3B8;letter-spacing:.02em;">
        Реклама. АО «ТБанк». ERID: LjN8KDq59
    </p>
</section>
