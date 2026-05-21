<?php
/**
 * Похожие регионы — 5 соседних по федеральному округу.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;
if ( ! $district ) return;

$similar = get_posts( [
    'post_type'      => MS_Tariff_Map_CPT::POST_TYPE,
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'post__not_in'   => [ $region_id ],
    'meta_query'     => [
        [ 'key' => 'federal_district', 'value' => $district, 'compare' => '=' ],
    ],
    'orderby'        => 'rand',
] );

if ( empty( $similar ) ) return;
?>
<section class="ms-region-page__similar">
    <h2>Соседние регионы <?php echo esc_html( $district ); ?></h2>
    <ul class="similar-list">
        <?php foreach ( $similar as $p ) :
            $b = MS_Tariff_Map_ACF::get( $p->ID, 'avg_monthly_bill' );
            $sn = MS_Tariff_Map_ACF::get( $p->ID, 'region_name_short' ) ?: get_the_title( $p );
        ?>
        <li>
            <a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
                <strong><?php echo esc_html( $sn ); ?></strong>
                <?php if ( $b ) : ?>
                    <span><?php echo esc_html( number_format( (float) $b, 0, ',', ' ' ) ); ?> ₽/мес</span>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <p><a href="<?php echo esc_url( get_post_type_archive_link( MS_Tariff_Map_CPT::POST_TYPE ) ); ?>">← Все регионы на интерактивной карте</a></p>
</section>
