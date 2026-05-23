<?php
/**
 * Таблица тарифов на странице региона.
 * Использует переменные из single-region_tariff.php: $region_id
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

// SVG-иконки Lucide (MIT) — inline, без внешних зависимостей.
$icon_droplet  = '<svg class="tariff-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>';
$icon_droplets = '<svg class="tariff-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.3c-.29 1.45-1.14 2.84-2.29 3.76S3 11.1 3 12.25c0 2.22 1.8 4.05 4 4.05z"/><path d="M12.56 6.6A10.97 10.97 0 0 0 14 3.02c.5 2.5 2 4.9 4 6.5s3 3.5 3 5.5a6.98 6.98 0 0 1-11.91 4.97"/></svg>';
$icon_zap      = '<svg class="tariff-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>';
$icon_flame    = '<svg class="tariff-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/></svg>';
$icon_trash    = '<svg class="tariff-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';

$rows = [
    [ 'Холодная вода',  'tariff_cold_water',        'руб/м³',     $icon_droplet,  'water-cold' ],
    [ 'Горячая вода',   'tariff_hot_water',         'руб/м³',     $icon_droplets, 'water-hot' ],
    [ 'Водоотведение',  'tariff_wastewater',        'руб/м³',     $icon_droplet,  'wastewater' ],
    [ 'Электроэнергия (день)', 'tariff_electricity_day',   'руб/кВт·ч', $icon_zap, 'electricity-day' ],
    [ 'Электроэнергия (ночь)', 'tariff_electricity_night', 'руб/кВт·ч', $icon_zap, 'electricity-night' ],
    [ 'Отопление',      'tariff_heat',              'руб/Гкал',   $icon_flame,    'heat' ],
    [ 'Газ',            'tariff_gas',               'руб/м³',     $icon_flame,    'gas' ],
    [ 'ТКО',            'tariff_garbage',           'руб/чел./мес.', $icon_trash, 'garbage' ],
];
?>
<section class="ms-region-page__tariffs">
    <h2>Тарифы ЖКХ в <?php echo esc_html( $region_name ); ?> на 2026 год</h2>
    <table class="regions-table">
        <thead>
            <tr>
                <th>Услуга</th>
                <th class="num">Тариф</th>
                <th>Ед. измерения</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $rows as $row ) :
            [ $label, $field, $unit, $icon_svg, $icon_kind ] = $row;
            $value = MS_Tariff_Map_ACF::get( $region_id, $field );
            if ( $value === null || $value === '' ) continue;
        ?>
            <tr>
                <td class="tariff-cell">
                    <span class="tariff-ico-wrap tariff-ico--<?php echo esc_attr( $icon_kind ); ?>"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <span><?php echo esc_html( $label ); ?></span>
                </td>
                <td class="num"><strong><?php echo esc_html( number_format( (float) $value, 2, ',', ' ' ) ); ?></strong></td>
                <td><?php echo esc_html( $unit ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
