<?php
/**
 * Партнёрские CTA-кнопки: кешбэк-карта и оплата ЖКУ через Т-Банк.
 * Используется на странице региона и на архиве /tarify-zhku/.
 *
 * Опциональные переменные:
 *   $region_name — название региона для подзаголовка. Если не передано, используется общий текст.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$region_name_local = isset( $region_name ) && $region_name ? $region_name : '';
$headline = $region_name_local
    ? 'Платить за ЖКУ в ' . esc_html( $region_name_local ) . ' выгоднее'
    : 'Платить за ЖКУ выгоднее';
?>
<section class="ms-partner-cta" aria-label="Партнёрские предложения для оплаты ЖКУ">
    <div class="ms-partner-cta__head">
        <h2 class="ms-partner-cta__title"><?php echo $headline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
        <p class="ms-partner-cta__sub">Карты с кешбэком возвращают часть платежа за коммуналку и любые покупки. Выбирайте под себя — мы подобрали два проверенных варианта.</p>
    </div>

    <div class="ms-partner-cta__grid">
        <a class="ms-partner-card"
           href="https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=7249"
           target="_blank"
           rel="sponsored nofollow noopener noreferrer"
           data-event="partner-click"
           data-offer="cashback-card">
            <div class="ms-partner-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="14" x="2" y="5" rx="2"/>
                    <line x1="2" x2="22" y1="10" y2="10"/>
                </svg>
            </div>
            <div class="ms-partner-card__body">
                <div class="ms-partner-card__title">Карта с кешбэком — без условий</div>
                <div class="ms-partner-card__sub">Дебетовая навсегда, бесплатно</div>
            </div>
            <div class="ms-partner-card__arrow" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </div>
        </a>

        <a class="ms-partner-card"
           href="https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=6432&p=10249&erid=LjN8KDq59"
           target="_blank"
           rel="sponsored nofollow noopener noreferrer"
           data-event="partner-click"
           data-offer="tbank-zhku">
            <div class="ms-partner-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <path d="M8 13h8"/><path d="M8 17h6"/>
                </svg>
            </div>
            <div class="ms-partner-card__body">
                <div class="ms-partner-card__title">Платить ЖКУ выгоднее</div>
                <div class="ms-partner-card__sub">Т-Банк Black + 3 мес Pro в подарок</div>
            </div>
            <div class="ms-partner-card__arrow" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>

    <div class="ms-partner-cta__erid">
        <span>Реклама. АО «ТБанк». ERID: LjN8KDq59</span>
    </div>
</section>
