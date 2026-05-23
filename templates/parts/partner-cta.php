<?php
/**
 * Партнёрские CTA — структура 1:1 с similar-regions.php (которая работает на сайте).
 * <section> + <h2> + <ul> + <li> + <a>. Минимум inline-стилей.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$region_name_local = isset( $region_name ) && $region_name ? $region_name : '';
$headline = $region_name_local
    ? 'Платить за ЖКУ в ' . esc_html( $region_name_local ) . ' выгоднее'
    : 'Платить за ЖКУ выгоднее';
?>
<section class="ms-region-page__partners">
    <h2><?php echo $headline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
    <p>Карты с кешбэком возвращают часть платежа за коммуналку и любые покупки.</p>

    <ul class="similar-list partner-list">
        <li>
            <a href="https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=7249"
               target="_blank"
               rel="sponsored nofollow noopener noreferrer">
                <strong>Карта с кешбэком — без условий</strong>
                <span>Дебетовая навсегда, бесплатно</span>
            </a>
        </li>
        <li>
            <a href="https://go.leadgid.ru/aff_c?aff_id=139564&offer_id=6432&p=10249&erid=LjN8KDq59"
               target="_blank"
               rel="sponsored nofollow noopener noreferrer">
                <strong>Платить ЖКУ выгоднее</strong>
                <span>Т-Банк Black + 3 мес Pro в подарок</span>
            </a>
        </li>
    </ul>

    <p style="margin-top:12px;text-align:center;font-size:11px;color:#94A3B8;">
        Реклама. АО «ТБанк». ERID: LjN8KDq59
    </p>
</section>
