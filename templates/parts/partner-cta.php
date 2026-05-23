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
<style>
/* Аварийные инлайн-стили партнёрского CTA — на случай, если map.css не подгружается на странице региона */
.ms-partner-cta{margin:32px 0;padding:24px;background:linear-gradient(135deg,#F0F9FF 0%,#FFF 50%,#FEF3C7 100%);border-radius:16px;border:1px solid rgba(226,232,240,.8)}
.ms-partner-cta__head{margin-bottom:20px}
.ms-partner-cta__title{font-size:clamp(20px,2.5vw,24px);font-weight:700;margin:0 0 8px;color:#0F172A;letter-spacing:-.01em}
.ms-partner-cta__sub{margin:0;color:#64748B;font-size:14px;line-height:1.5;max-width:640px}
.ms-partner-cta__grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:20px}
.ms-partner-card{display:flex !important;align-items:center;gap:14px;padding:16px 18px;background:#fff;border:1px solid rgba(226,232,240,.9);border-radius:12px;text-decoration:none !important;color:#0F172A !important;transition:transform .15s,box-shadow .15s,border-color .15s;box-shadow:0 1px 2px rgba(15,23,42,.04);min-height:76px;box-sizing:border-box}
.ms-partner-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(4,107,210,.12);border-color:#046BD2;text-decoration:none !important}
.ms-partner-card:hover .ms-partner-card__title,
.ms-partner-card:hover .ms-partner-card__sub{color:inherit}
.ms-partner-card__icon{flex-shrink:0;width:44px;height:44px;border-radius:10px;background:rgba(4,107,210,.08);color:#046BD2;display:flex;align-items:center;justify-content:center}
.ms-partner-card__icon svg{width:22px;height:22px}
.ms-partner-card__body{flex:1 1 auto;min-width:0}
.ms-partner-card__title{font-weight:700;font-size:15px;line-height:1.3;color:#0F172A;margin-bottom:4px}
.ms-partner-card__sub{font-size:13px;color:#64748B;line-height:1.4}
.ms-partner-card__arrow{flex-shrink:0;width:24px;height:24px;color:#94A3B8;transition:transform .15s,color .15s}
.ms-partner-card__arrow svg{width:100%;height:100%}
.ms-partner-card:hover .ms-partner-card__arrow{color:#046BD2;transform:translateX(3px)}
.ms-partner-cta__erid{margin-top:14px;text-align:center;font-size:11px;color:#94A3B8;letter-spacing:.02em}
@media (max-width:768px){.ms-partner-cta{padding:18px;margin:20px 0}.ms-partner-cta__grid{grid-template-columns:1fr;gap:10px}.ms-partner-card{padding:14px}}
</style>
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
