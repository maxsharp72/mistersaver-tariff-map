<?php
/**
 * Таблица тарифов на странице региона.
 * Использует переменные из single-region_tariff.php: $region_id
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$rows = [
    [ 'Холодная вода',  'tariff_cold_water',        'руб/м³' ],
    [ 'Горячая вода',   'tariff_hot_water',         'руб/м³' ],
    [ 'Водоотведение',  'tariff_wastewater',        'руб/м³' ],
    [ 'Электроэнергия (день)', 'tariff_electricity_day', 'руб/кВт·ч' ],
    [ 'Электроэнергия (ночь)', 'tariff_electricity_night', 'руб/кВт·ч' ],
    [ 'Отопление',      'tariff_heat',              'руб/Гкал' ],
    [ 'Газ',            'tariff_gas',               'руб/м³' ],
    [ 'ТКО',            'tariff_garbage',           'руб/чел./мес.' ],
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
            [ $label, $field, $unit ] = $row;
            $value = MS_Tariff_Map_ACF::get( $region_id, $field );
            if ( $value === null || $value === '' ) continue;
        ?>
            <tr>
                <td><?php echo esc_html( $label ); ?></td>
                <td class="num"><strong><?php echo esc_html( number_format( (float) $value, 2, ',', ' ' ) ); ?></strong></td>
                <td><?php echo esc_html( $unit ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
