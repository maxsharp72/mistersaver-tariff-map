<?php
/**
 * Мини-карта для встраивания на страницу одного региона.
 * Использует те же стили и JS, но в режиме mini.
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="ms-tariff-map ms-tariff-map--mini"
     id="<?php echo esc_attr( $id ); ?>"
     data-mode="mini"
     data-region="<?php echo esc_attr( $atts['region'] ); ?>"
     data-layer="<?php echo esc_attr( $atts['layer'] ); ?>"
     style="height:<?php echo intval( $atts['height'] ); ?>px;">
    <div id="map-mini-<?php echo esc_attr( $id ); ?>" style="width:100%;height:100%;"></div>
</div>
