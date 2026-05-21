<?php
/**
 * FAQ-блок (4 вопроса). Дублируется в Schema.org через class-schema.php.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

$faqs = [
    [
        'q' => "Когда повысятся тарифы ЖКХ в {$region_name}?",
        'a' => 'Плановая индексация на 2026 год составит около 4,0% (постановление Правительства РФ № 3147-р). Тарифы изменятся с 1 июля 2026 года.',
    ],
    [
        'q' => "Как рассчитать квитанцию ЖКХ в {$region_name}?",
        'a' => 'Используйте <a href="' . esc_url( home_url( '/kalkulyatory/' ) ) . '">калькуляторы ЖКХ</a> на MisterSaver — введите площадь квартиры, число прописанных и потребление, калькулятор подставит тарифы региона.',
    ],
    [
        'q' => "Где посмотреть официальные тарифы {$region_name}?",
        'a' => $regulator_url
            ? 'Официальные тарифы публикуются на сайте регулятора: <a href="' . esc_url( $regulator_url ) . '" target="_blank" rel="noopener">' . esc_html( $regulator ?: $regulator_url ) . '</a>.'
            : 'Официальные тарифы публикуются на сайте регионального комитета по тарифам.',
    ],
    [
        'q' => "Как сэкономить на ЖКУ в {$region_name}?",
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
