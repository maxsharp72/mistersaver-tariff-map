<?php
/**
 * FAQ-блок (4 вопроса). Дублируется в Schema.org через class-schema.php.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$r_in = $region_in ?? ( 'в ' . $region_name );
$r_of = $region_of ?? $region_name;

$faqs = [
    [
        'q' => "Когда повысятся тарифы ЖКХ {$r_in}?",
        'a' => ms_tariff_map_indexation_answer( $index_2026 ?? null ),
    ],
    [
        'q' => "Как рассчитать квитанцию ЖКХ {$r_in}?",
        'a' => 'Используйте <a href="' . esc_url( home_url( '/kalkulyatory/' ) ) . '">калькуляторы ЖКХ</a> на MisterSaver — введите площадь квартиры, число прописанных и потребление, калькулятор подставит тарифы региона.',
    ],
    [
        'q' => "Где посмотреть официальные тарифы {$r_of}?",
        'a' => $regulator_url
            ? 'Официальные тарифы публикуются на сайте регулятора: <a href="' . esc_url( $regulator_url ) . '" target="_blank" rel="noopener">' . esc_html( $regulator ?: $regulator_url ) . '</a>.'
            : 'Официальные тарифы публикуются на сайте регионального комитета по тарифам.',
    ],
    [
        'q' => "Как сэкономить на ЖКУ {$r_in}?",
        'a' => 'Установите счётчики, проведите утепление окон и дверей, замените лампы на LED, оплачивайте ЖКУ с кэшбэком через партнёрский сервис MisterSaver.',
    ],
];
?>
<section class="ms-region-page__faq">
    <h2>Часто задаваемые вопросы</h2>
    <?php foreach ( $faqs as $faq ) : ?>
    <details>
        <summary><?php echo esc_html( $faq['q'] ); ?></summary>
        <div><?php echo wp_kses_post( $faq['a'] ); ?></div>
    </details>
    <?php endforeach; ?>
</section>
