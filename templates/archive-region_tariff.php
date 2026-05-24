<?php
/**
 * Архив CPT region_tariff = главная страница /tarify-zhku/.
 * Использует шорткод [ms_tariff_map mode="full"].
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

get_header(); ?>

<main class="ms-tariff-archive">
    <?php echo do_shortcode( '[ms_tariff_map mode="full"]' ); ?>

    <?php // Сквозной партнёрский CTA под картой ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/partner-cta.php'; ?>

    <?php // «Нашли ошибку?» ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/report-error.php'; ?>
</main>

<?php get_footer();
