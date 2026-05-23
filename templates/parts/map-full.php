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
                    <span class="search__icon" aria-hidden="true"><svg viewBox="0 0 16 16"><path d="M13.936 13.24L9.708 9.01a4.8 4.8 0 1 0-.69.69l4.228 4.228a.488.488 0 0 0 .69-.69zM6.002 9.8A3.8 3.8 0 1 1 8.69 8.686a3.778 3.778 0 0 1-2.687 1.112z"/></svg></span>
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

            <a class="filters__reset" id="reset-filters"><svg class="filters__reset-ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M1.282 7H.272A7.788 7.788 0 0 1 15 4.582V2h1v4h-4V5h2.093a6.788 6.788 0 0 0-12.81 2zM1 11.418A7.788 7.788 0 0 0 15.728 9h-1.01a6.788 6.788 0 0 1-12.811 2H4v-1H0v4h1z"/></svg> Сбросить все фильтры</a>
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
                <button class="map-toolbar__btn active" data-view="map"><svg class="map-toolbar__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M1 7h6V1H1zm1-5h4v4H2zm13-1H9v6h6zm-1 5h-4V2h4zM1 15h6V9H1zm1-5h4v4H2zm7 5h6V9H9zm1-5h4v4h-4z"/></svg><span>Карта</span></button>
                <button class="map-toolbar__btn" data-view="table"><svg class="map-toolbar__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M6 14h9v1H6zm-2 2H1v-3h3zm-1-2H2v1h1zM15 2H6v1h9zM6 9h9V8H6zM4 4H1V1h3zM3 2H2v1h1zm1 8H1V7h3zM3 8H2v1h1z"/></svg><span>Таблица</span></button>
                <div class="map-toolbar__sep"></div>
                <button class="map-toolbar__btn" id="btn-export"><svg class="map-toolbar__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 11v1h-.5a3.493 3.493 0 0 1-1.484-6.659 1.966 1.966 0 0 1 2.617-1.73 4.968 4.968 0 0 1 9.298 1.701A3.486 3.486 0 0 1 13 11.95v-1a2.495 2.495 0 0 0 .52-4.725l-.503-.227-.077-.548a3.968 3.968 0 0 0-7.43-1.357l-.403.734-.794-.266A.978.978 0 0 0 4 4.5a.989.989 0 0 0-.987.92L2.966 6l-.525.246A2.494 2.494 0 0 0 3.5 11zm6.62.675L9 13.295V7H8v6.26l-1.585-1.585-.707.707 2.81 2.81L9.708 14l1.618-1.618z"/></svg><span>Экспорт CSV</span></button>
                <button class="map-toolbar__btn" id="btn-share"><svg class="map-toolbar__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M1 1h8v1H2v12h12V9h1v6H1zm5.02 7.521V11H7V8.521A3.54 3.54 0 0 1 10.52 5h2.752l-1.626 1.646.707.707 2.81-2.809-2.81-2.809-.706.707 1.579 1.579H10.52a4.505 4.505 0 0 0-4.5 4.5z"/></svg><span>Поделиться</span></button>
                <div class="map-toolbar__count">Показано <b id="visible-count">—</b> из <b id="total-count">—</b> регионов</div>
            </div>

            <div class="map-stage" id="map-stage-<?php echo esc_attr( $id ); ?>">
                <div id="map"></div>

                <div class="map-controls">
                    <button class="map-btn" id="zoom-in" title="Приблизить" aria-label="Приблизить">
                        <svg class="map-btn__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M9 4v4h4v1H9v4H8V9H4V8h4V4z"/></svg>
                    </button>
                    <button class="map-btn" id="zoom-out" title="Отдалить" aria-label="Отдалить">
                        <svg class="map-btn__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M13 8v1H3V8z"/></svg>
                    </button>
                    <button class="map-btn" id="zoom-reset" title="Сброс вида" aria-label="Сброс вида">
                        <svg class="map-btn__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M6 14H2v-4H1v5h5zm4 1h5v-5h-1v4h-4zM6 1H1v5h1V2h4zm4 1h4v4h1V1h-5z"/></svg>
                    </button>
                    <button class="map-btn" id="fullscreen" title="Полноэкранно" aria-label="Полноэкранно">
                        <svg class="map-btn__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M0 1v14h16V1zm15 13H1V2h14zM9.644 6.65L12.246 4H11V3h3v3h-1V4.66l-2.644 2.69zM12.246 12L9.644 9.35l.712-.7L13 11.34V10h1v3h-3v-1zM3 6H2V3h3v1H3.753l2.603 2.65-.712.7L3 4.66zm2 7H2v-3h1v1.34l2.644-2.69.712.7L3.753 12H5z"/></svg>
                    </button>
                </div>

                <div class="opacity-control" id="opacity-control">
                    <button class="opacity-control__toggle" id="opacity-toggle" title="Прозрачность подсветки регионов" aria-label="Прозрачность подсветки">
                        <svg class="opacity-control__ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M10.8 7.5a2.3 2.3 0 1 1-2.3-2.3 2.302 2.302 0 0 1 2.3 2.3zm5.046.37c-.558.69-3.523 4.13-7.287 4.13-3.766 0-6.82-3.434-7.395-4.122a.581.581 0 0 1 .001-.758C1.738 6.434 4.793 3 8.56 3c3.764 0 6.73 3.44 7.286 4.13a.58.58 0 0 1 0 .74zm-.997-.37C14.07 6.61 11.532 4 8.559 4 5.58 4 2.969 6.605 2.16 7.5c.81.897 3.421 3.5 6.398 3.5 2.973 0 5.511-2.61 6.29-3.5z"/></svg>
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
                <th data-sort="bill" class="num sorted">Платёж, ₽/мес <svg class="th-sort-ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M9 2v10.295l1.62-1.62.706.707-2.808 2.81-2.81-2.81.707-.707L8 12.26V2z"/></svg></th>
                <th>Уровень</th>
            </tr>
        </thead>
        <tbody id="table-body"></tbody>
    </table>

    <div class="back-to-map-wrap">
        <button class="back-to-map-btn" id="back-to-map"><svg class="back-to-map-btn__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg> Вернуться к карте</button>
    </div>
</section>
