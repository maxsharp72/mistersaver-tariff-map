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
    <!-- MS-DEBUG-ARCHIVE: partner-cta include file_exists=<?php echo file_exists( MS_TARIFF_MAP_DIR . 'templates/parts/partner-cta.php' ) ? 'YES' : 'NO'; ?> ts=<?php echo date( 'H:i:s' ); ?> -->
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/partner-cta.php'; ?>
    <!-- MS-DEBUG-ARCHIVE: partner-cta after -->
</main>

<?php get_footer();
