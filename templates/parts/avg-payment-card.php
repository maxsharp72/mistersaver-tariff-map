<?php
/**
 * Карточка «Средний платёж семьи».
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;
if ( ! $bill ) return;

$avg_rf = 5500;
$diff_pct = round( ( $bill - $avg_rf ) / $avg_rf * 100 );
$diff_text = $diff_pct > 0
    ? "на {$diff_pct}% дороже среднего по России"
    : ( $diff_pct < 0 ? "на " . abs( $diff_pct ) . "% дешевле среднего по России" : "примерно как среднее по России" );
$diff_class = $diff_pct > 0 ? 'warn' : 'good';
?>
<section class="ms-region-page__bill">
    <div class="bill-card">
        <div class="bill-card__label">Средний платёж семьи из 3 человек</div>
        <div class="bill-card__value">
            <?php echo esc_html( number_format( (float) $bill, 0, ',', ' ' ) ); ?> ₽<small>/мес</small>
            <?php if ( $bill_estimated ) : ?>
                <span class="bill-card__estimate" title="Оценочное значение">оценка</span>
            <?php endif; ?>
        </div>
        <div class="bill-card__diff <?php echo esc_attr( $diff_class ); ?>">
            <?php echo esc_html( $diff_text ); ?> (5 500 ₽)
        </div>
    </div>
</section>
