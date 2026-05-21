<?php
/**
 * Полная карта (для архива и /tarify-zhku/).
 * Переменные из class-shortcode.php:
 *   $id   — уникальный ID контейнера
 *   $atts — атрибуты шорткода
 *
 * @package MisterSaver\TariffMap
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="page-hero">
    <h1>Карта тарифов ЖКУ по регионам России на 2026 год</h1>
    <p class="lead">Интерактивная карта стоимости коммунальных услуг по&nbsp;всем субъектам Российской Федерации. Кликните на регион, чтобы посмотреть тарифы на&nbsp;электричество, воду, газ и средний платёж семьи.</p>
    <div class="meta"><span class="dot"></span> Данные актуальны с 1 июля 2025 года</div>
</section>

<section class="ms-mapcalc">
    <div class="ms-mapcalc__h">
        <div class="ms-mapcalc__title">
            <h2>Найдите свой регион на карте</h2>
            <p>Цвет региона показывает уровень среднего платежа: от зелёного (дёшево) к&nbsp;коричневому (очень дорого)</p>
        </div>
    </div>

    <!-- KPI лента -->
    <div class="kpi-strip">
        <div class="kpi kpi--accent">
            <div class="kpi__l">Регионов</div>
            <div class="kpi__v" id="kpi-total">—</div>
            <div class="kpi__d" id="kpi-total-d">в датасете</div>
        </div>
        <div class="kpi">
            <div class="kpi__l">Средне по РФ</div>
            <div class="kpi__v">5 500 ₽</div>
            <div class="kpi__d">семья 3 чел./мес</div>
        </div>
        <div class="kpi">
            <div class="kpi__l">Индексация 2026</div>
            <div class="kpi__v">+4,0%</div>
            <div class="kpi__d">с 1 июля 2026</div>
        </div>
        <div class="kpi">
            <div class="kpi__l">Минимум</div>
            <div class="kpi__v" id="kpi-min">—</div>
            <div class="kpi__d" id="kpi-min-d">—</div>
        </div>
        <div class="kpi">
            <div class="kpi__l">Максимум</div>
            <div class="kpi__v" id="kpi-max">—</div>
            <div class="kpi__d warn" id="kpi-max-d">—</div>
        </div>
    </div>

    <div class="map-grid">

        <aside class="filters">
            <div class="filters__group">
                <h4>Поиск</h4>
                <div class="search">
                    <span class="search__icon">🔍</span>
                    <input id="search" placeholder="Регион..." autocomplete="off">
                    <div class="search__results" id="search-results"></div>
                </div>
            </div>

            <div class="filters__group">
                <h4>Федеральный округ</h4>
                <div id="filter-districts"></div>
            </div>

            <div class="filters__group">
                <h4>Средний платёж, ₽/мес</h4>
                <div class="range">
                    <div class="range__bar">
                        <div class="range__fill" id="range-fill"></div>
                        <div class="range__handle" id="range-h1" style="left:0%;"></div>
                        <div class="range__handle" id="range-h2" style="left:100%;"></div>
                    </div>
                    <div class="range__labels">
                        <span id="range-min">—</span>
                        <span id="range-max">—</span>
                    </div>
                </div>
            </div>

            <div class="filters__group">
                <h4>Уровень тарифа</h4>
                <div id="filter-tiers"></div>
            </div>

            <a class="filters__reset" id="reset-filters">↺ Сбросить все фильтры</a>
        </aside>

        <div class="map-area">
            <div class="map-tabs" id="layer-tabs">
                <div class="map-tab active" data-layer="bill">Общий платёж <span class="unit">₽/мес</span></div>
                <div class="map-tab" data-layer="electricity">Электричество <span class="unit">₽/кВт·ч</span></div>
                <div class="map-tab" data-layer="water">Холодная вода <span class="unit">₽/м³</span></div>
                <div class="map-tab" data-layer="hot_water">Горячая вода <span class="unit">₽/м³</span></div>
                <div class="map-tab" data-layer="gas">Газ <span class="unit">₽/м³</span></div>
            </div>

            <div class="map-toolbar">
                <button class="map-toolbar__btn active" data-view="map">⊞ Карта</button>
                <button class="map-toolbar__btn" data-view="table">≡ Таблица</button>
                <div class="map-toolbar__sep"></div>
                <button class="map-toolbar__btn" id="btn-export">⇣ Экспорт CSV</button>
                <button class="map-toolbar__btn" id="btn-share">⤴ Поделиться</button>
                <div class="map-toolbar__count">Показано <b id="visible-count">—</b> из <b id="total-count">—</b> регионов</div>
            </div>

            <div class="map-stage" id="map-stage-<?php echo esc_attr( $id ); ?>">
                <div id="map"></div>

                <div class="map-controls">
                    <button class="map-btn" id="zoom-in" title="Приблизить">+</button>
                    <button class="map-btn" id="zoom-out" title="Отдалить">−</button>
                    <button class="map-btn" id="zoom-reset" title="Сброс вида">⟲</button>
                    <button class="map-btn" id="fullscreen" title="Полноэкранно">⛶</button>
                </div>

                <div class="opacity-control" id="opacity-control">
                    <button class="opacity-control__toggle" id="opacity-toggle" title="Прозрачность подсветки регионов">
                        <span class="opacity-control__eye">👁</span>
                        <span class="opacity-control__toggle-val" id="opacity-toggle-val">50%</span>
                    </button>
                    <div class="opacity-control__panel" id="opacity-panel">
                        <div class="opacity-control__head">
                            <span>Подсветка регионов</span>
                            <span class="opacity-control__val" id="opacity-val">50%</span>
                        </div>
                        <input type="range" min="0" max="100" value="50" step="5" id="opacity-slider" class="opacity-control__slider">
                        <div class="opacity-control__legend">0% — только Яндекс Карты · 100% — сплошной цвет</div>
                    </div>
                </div>

                <div class="legend" id="legend">
                    <h5 id="legend-title">Средний платёж, ₽/мес</h5>
                    <div id="legend-rows"></div>
                </div>

                <div class="map-tooltip" id="tooltip"></div>
            </div>
        </div>
    </div>
</section>

<section class="analytics">
    <div class="panel">
        <h3>Распределение регионов по уровню тарифа <a href="#table">Все регионы →</a></h3>
        <div id="distribution"></div>
        <div class="insight">
            💡 <b>Инсайт:</b> 4 из 5 самых дорогих регионов — на Крайнем Севере (Чукотка, НАО, ЯНАО, Камчатка). «Северный завоз» поднимает тариф в&nbsp;2–4 раза от среднего по&nbsp;РФ. Москва замыкает топ-5 — высокий тариф на отопление и воду.
        </div>
    </div>

    <div class="panel">
        <h3 id="toplist-title">Топ-5 самых дорогих регионов <a href="#" id="toggle-top">Самые дешёвые →</a></h3>
        <div id="toplist"></div>
    </div>
</section>

<section class="regions-table-wrap" id="table">
    <h3>
        <span>Таблица регионов <span class="pill" id="table-count">—</span></span>
        <span id="table-filter-note" class="table-filter-note" hidden>
            <span class="dot"></span> Применены фильтры карты
        </span>
    </h3>
    <table class="regions-table">
        <thead>
            <tr>
                <th data-sort="rank">#</th>
                <th data-sort="name">Регион</th>
                <th data-sort="district">Округ</th>
                <th data-sort="electricity" class="num">Свет, ₽/кВт·ч</th>
                <th data-sort="water" class="num">ХВС, ₽/м³</th>
                <th data-sort="gas" class="num">Газ, ₽/м³</th>
                <th data-sort="bill" class="num sorted">Платёж, ₽/мес ↓</th>
                <th>Уровень</th>
            </tr>
        </thead>
        <tbody id="table-body"></tbody>
    </table>

    <div class="back-to-map-wrap">
        <button class="back-to-map-btn" id="back-to-map">↑ Вернуться к карте</button>
    </div>
</section>
