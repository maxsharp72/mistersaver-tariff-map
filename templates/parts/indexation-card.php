<?php
/**
 * Карточка «Индексация тарифов».
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$idx_2024 = MS_Tariff_Map_ACF::get( $region_id, 'index_2024' );
$idx_2025 = MS_Tariff_Map_ACF::get( $region_id, 'index_2025' );
$idx_2026 = MS_Tariff_Map_ACF::get( $region_id, 'index_2026', 4.0 );
?>
<section class="ms-region-page__index">
    <h2>
        <svg class="section-title-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/>
        </svg>
        Индексация тарифов
    </h2>
    <table class="regions-table">
        <thead>
            <tr><th>Год</th><th class="num">Рост тарифов, %</th><th>Источник</th></tr>
        </thead>
        <tbody>
            <?php if ( $idx_2024 !== null ) : ?>
            <tr><td>2024</td><td class="num"><?php echo esc_html( number_format( (float) $idx_2024, 1, ',', '' ) ); ?>%</td><td>Постановление Правительства РФ</td></tr>
            <?php endif; ?>
            <?php if ( $idx_2025 !== null ) : ?>
            <tr><td>2025</td><td class="num"><?php echo esc_html( number_format( (float) $idx_2025, 1, ',', '' ) ); ?>%</td><td>с 1 июля 2025 года</td></tr>
            <?php endif; ?>
            <tr>
                <td><strong>2026</strong></td>
                <td class="num"><strong><?php echo esc_html( number_format( (float) $idx_2026, 1, ',', '' ) ); ?>%</strong></td>
                <td>Постановление Правительства РФ № 3147-р</td>
            </tr>
        </tbody>
    </table>
</section>
