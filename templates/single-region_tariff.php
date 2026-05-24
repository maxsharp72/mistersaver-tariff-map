<?php
/**
 * Single-template для страницы региона /tarify-zhku/{slug}/.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    $region_id = get_the_ID();
    $region_name      = MS_Tariff_Map_ACF::get( $region_id, 'region_name_short' ) ?: get_the_title();
    $region_full      = get_the_title();
    $district         = MS_Tariff_Map_ACF::get( $region_id, 'federal_district' );
    $center_city      = MS_Tariff_Map_ACF::get( $region_id, 'center_city' );
    $bill             = MS_Tariff_Map_ACF::get( $region_id, 'avg_monthly_bill' );
    $bill_estimated   = MS_Tariff_Map_ACF::get( $region_id, 'bill_estimated' );
    $index_2026       = MS_Tariff_Map_ACF::get( $region_id, 'index_2026', 4.0 );
    $regulator        = MS_Tariff_Map_ACF::get( $region_id, 'regulatory_body' );
    $regulator_url    = MS_Tariff_Map_ACF::get( $region_id, 'regulatory_url' );
    $tariff_date      = MS_Tariff_Map_ACF::get( $region_id, 'tariff_date' );
    $slug             = get_post_field( 'post_name', $region_id );
?>

<main class="ms-region-page">

    <?php // [Крошки] RankMath если включён, иначе — ручные ?>
    <nav class="ms-region-page__breadcrumbs" aria-label="Хлебные крошки">
        <?php if ( function_exists( 'rank_math_the_breadcrumbs' ) ) : ?>
            <?php rank_math_the_breadcrumbs(); ?>
        <?php else : ?>
            <ol class="ms-breadcrumbs ms-breadcrumbs--fallback" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span itemprop="name">Главная</span></a>
                    <meta itemprop="position" content="1" />
                </li>
                <li class="sep" aria-hidden="true">›</li>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="<?php echo esc_url( get_post_type_archive_link( 'region_tariff' ) ); ?>"><span itemprop="name">Карта тарифов ЖКУ</span></a>
                    <meta itemprop="position" content="2" />
                </li>
                <li class="sep" aria-hidden="true">›</li>
                <li class="current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name"><?php echo esc_html( $region_full ); ?></span>
                    <meta itemprop="position" content="3" />
                </li>
            </ol>
        <?php endif; ?>
    </nav>

    <header class="ms-region-page__head">
        <h1><?php echo esc_html( $region_full ); ?></h1>
        <p class="ms-region-page__meta">
            <?php if ( $district ) : ?>
                <span><?php echo esc_html( $district ); ?></span>
            <?php endif; ?>
            <?php if ( $center_city ) : ?>
                <span> · Центр: <?php echo esc_html( $center_city ); ?></span>
            <?php endif; ?>
            <?php if ( $tariff_date ) : ?>
                <span> · Данные актуальны с <?php echo esc_html( date_i18n( 'd F Y', strtotime( $tariff_date ) ) ); ?></span>
            <?php endif; ?>
        </p>
    </header>

    <?php // [Блок 1] Таблица тарифов ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/tariff-table.php'; ?>

    <?php // [Блок 2] Мини-карта региона ?>
    <section class="ms-region-page__map">
        <h2>Положение на карте России</h2>
        <?php echo do_shortcode( '[ms_tariff_map mode="mini" region="' . esc_attr( $slug ) . '" height="400"]' ); ?>
    </section>

    <?php // [Блок 3] Средний платёж ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/avg-payment-card.php'; ?>

    <?php // [Блок 3.5] Партнёрские CTA — сразу после суммы платежа ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/partner-cta.php'; ?>

    <?php // [Блок 4] Индексация ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/indexation-card.php'; ?>

    <?php // [Блок 5] Авто-сгенерированный текст ?>
    <section class="ms-region-page__content">
        <?php the_content(); ?>
    </section>

    <?php // [Блок 6] FAQ — выводится в Schema.org, тут можем дублировать визуально ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/faq.php'; ?>

    <?php // [Блок 7] Старая синяя плашка cta-partner.php — убрана в 0.2.9 (заменена на partner-cta.php в [Блок 3.5]) ?>

    <?php // [Блок 8] Похожие регионы ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/similar-regions.php'; ?>

    <?php // [Блок 9] Нашли ошибку? ?>
    <?php include MS_TARIFF_MAP_DIR . 'templates/parts/report-error.php'; ?>

</main>

<?php
endwhile;
get_footer();
