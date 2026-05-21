/* =========================================================
   MisterSaver — Карта тарифов ЖКУ
   Интерактивный прототип с OpenLayers 9.x
   ========================================================= */

const TIER_COLORS = ['#16A34A', '#65A30D', '#F59E0B', '#DC2626', '#7C2D12'];
const NA_COLOR = '#94A3B8';   // серый для регионов без данных
const NA_STRIPE = '#CBD5E1';
const DISTRICTS = ['ЦФО','СЗФО','ПФО','УФО','СФО','ДФО','ЮФО','СКФО'];
const DISTRICT_FULL = {
  'ЦФО':'Центральный','СЗФО':'Северо-Западный','ПФО':'Приволжский',
  'УФО':'Уральский','СФО':'Сибирский','ДФО':'Дальневосточный',
  'ЮФО':'Южный','СКФО':'Северо-Кавказский'
};

const LAYERS = {
  bill:        { title: 'Средний платёж, ₽/мес', unit: '₽/мес', format: v => v.toLocaleString('ru-RU') + ' ₽' },
  electricity: { title: 'Электричество, ₽/кВт·ч', unit: '₽/кВт·ч', format: v => v.toFixed(2) + ' ₽' },
  water:       { title: 'Холодная вода, ₽/м³', unit: '₽/м³', format: v => v.toFixed(2) + ' ₽' },
  hot_water:   { title: 'Горячая вода, ₽/м³', unit: '₽/м³', format: v => v.toFixed(2) + ' ₽' },
  gas:         { title: 'Газ, ₽/м³', unit: '₽/м³', format: v => v != null ? v.toFixed(2) + ' ₽' : '—' }
};

// Состояние
const state = {
  regions: [],
  geojson: null,
  layer: 'bill',
  view: 'map',
  filters: {
    districts: new Set(DISTRICTS),
    tiers: new Set([1,2,3,4,5]),
    billMin: 0,
    billMax: 99999,
    search: ''
  },
  sortKey: 'bill',
  sortDir: 'desc',
  toplistMode: 'expensive',
  selectedSlug: null,
  map: null,
  vectorSource: null,
  vectorLayer: null,
  regionsOpacity: 0.5  // По умолчанию 50%
};

// Кэш для canvas-pattern «диагональная штриховка» (для N/A и «нет газа»)
let _hatchPattern = null;
function getHatchPattern(opacity) {
  // Пересоздаём при смене opacity
  const cacheKey = 'hatch_' + Math.round(opacity * 100);
  if (_hatchPattern && _hatchPattern.key === cacheKey) return _hatchPattern.pattern;
  const c = document.createElement('canvas');
  c.width = 8; c.height = 8;
  const ctx = c.getContext('2d');
  ctx.fillStyle = `rgba(148, 163, 184, ${opacity * 0.5})`;  // светлый серый фон
  ctx.fillRect(0, 0, 8, 8);
  ctx.strokeStyle = `rgba(100, 116, 139, ${opacity})`;  // тёмная штриховка
  ctx.lineWidth = 1.5;
  ctx.beginPath();
  ctx.moveTo(-2, 6); ctx.lineTo(6, -2);
  ctx.moveTo(2, 10); ctx.lineTo(10, 2);
  ctx.stroke();
  const pattern = ctx.createPattern(c, 'repeat');
  _hatchPattern = { key: cacheKey, pattern };
  return pattern;
}

// Цветовая шкала по слою — динамически делим на 5 тиров через quantile
function computeBreaks(values) {
  const sorted = [...values].sort((a, b) => a - b);
  const n = sorted.length;
  return [
    sorted[Math.floor(n * 0.2) - 1] || sorted[0],
    sorted[Math.floor(n * 0.4) - 1] || sorted[0],
    sorted[Math.floor(n * 0.6) - 1] || sorted[0],
    sorted[Math.floor(n * 0.8) - 1] || sorted[0],
    sorted[n - 1]
  ];
}

function getTierForValue(value, layer) {
  if (value == null) return 0;
  const breaks = state.breaks[layer];
  for (let i = 0; i < 5; i++) {
    if (value <= breaks[i]) return i + 1;
  }
  return 5;
}

/* =========================================================
   Загрузка данных из WordPress REST API
   ========================================================= */
async function loadData() {
  // Конфиг приходит из PHP-шорткода через wp_add_inline_script.
  const cfg = window.__MS_TARIFF_MAP_CONFIG__ || {};
  if (!cfg.restUrl) {
    throw new Error('__MS_TARIFF_MAP_CONFIG__ не определён. Проверьте шорткод [ms_tariff_map].');
  }
  // Параллельная загрузка обоих эндпоинтов.
  const [regions, geojson] = await Promise.all([
    fetch(cfg.restUrl).then(r => r.json()),
    fetch(cfg.geojsonUrl).then(r => r.json())
  ]);
  state.regions = regions;
  state.geojson = geojson;
  state.config = cfg;
  // Сохраняем Яндекс-ключ в ту же глобальную переменную что и раньше.
  window.__MS_YANDEX_KEY__ = cfg.yandexKey || '';
  // URL-префикс для перехода на страницы регионов.
  state.urlPrefix = cfg.urlPrefix || '/tarify-zhku/';

  // Считаем границы для каждого слоя
  state.breaks = {};
  for (const key of Object.keys(LAYERS)) {
    const values = regions.map(r => r[key]).filter(v => v != null && !isNaN(v));
    state.breaks[key] = computeBreaks(values);
  }

  // Подсчёт счётчиков
  state.districtCounts = {};
  DISTRICTS.forEach(d => state.districtCounts[d] = 0);
  regions.forEach(r => { state.districtCounts[r.district] = (state.districtCounts[r.district] || 0) + 1; });

  // Min/Max bill для слайдера
  const bills = regions.map(r => r.bill);
  state.minBill = Math.min(...bills);
  state.maxBill = Math.max(...bills);
  state.filters.billMin = state.minBill;
  state.filters.billMax = state.maxBill;
}

/* =========================================================
   Инициализация карты OpenLayers
   ========================================================= */
function initMap() {
  const { Map, View, Feature } = ol;
  const { Tile: TileLayer, Vector: VectorLayer } = ol.layer;
  const { OSM, Vector: VectorSource } = ol.source;
  const { GeoJSON } = ol.format;
  const { Style, Fill, Stroke } = ol.style;
  const { fromLonLat } = ol.proj;

  // Подложка: Яндекс Tiles API. Attribution отключаем всё — отрисуем свою в HTML.
  const { XYZ } = ol.source;
  const YANDEX_KEY = (window.__MS_YANDEX_KEY__ || '').trim();
  let baseLayer;
  if (YANDEX_KEY) {
    baseLayer = new TileLayer({
      source: new XYZ({
        url: `https://tiles.api-maps.yandex.ru/v1/tiles/?apikey=${YANDEX_KEY}&lang=ru_RU&x={x}&y={y}&z={z}&l=map`,
        attributions: [],
        maxZoom: 19,
        crossOrigin: 'anonymous'
      })
    });
  } else {
    baseLayer = new TileLayer({
      source: new OSM({
        attributions: []
      })
    });
  }

  // Векторный слой регионов
  state.vectorSource = new VectorSource({
    features: new GeoJSON().readFeatures(state.geojson, {
      featureProjection: 'EPSG:3857',
      dataProjection: 'EPSG:4326'
    })
  });

  state.vectorLayer = new VectorLayer({
    source: state.vectorSource,
    style: styleForFeature
  });

  // Адаптивный начальный вид: центр и зум подобраны вручную для РФ (чтобы Калининград и Чукотка влезали)
  const w = window.innerWidth;
  // На узком экране сильно уменьшаем зум, иначе Калининград/Чукотка выходят за вьюпорт
  const initialZoom = w <= 480 ? 1.7 : w <= 768 ? 2.0 : w <= 1100 ? 2.6 : 3.0;
  state.initialZoom = initialZoom;
  state.initialCenter = fromLonLat([100, 65]);
  // Никаких дефолтных контролов OL — всё своё (zoom в верхнем-правом, attribution в HTML).
  state.map = new Map({
    target: 'map',
    layers: [baseLayer, state.vectorLayer],
    controls: new ol.Collection(),
    view: new View({
      center: state.initialCenter,
      zoom: initialZoom,
      minZoom: 1.5,
      maxZoom: 8
    })
  });

  // Отрисуем HTML-атрибуцию в правом нижнем углу .map-stage (если её ещё нет).
  const mapEl0 = document.getElementById('map');
  const stage0 = mapEl0 ? mapEl0.closest('.map-stage') : null;
  if (stage0 && !stage0.querySelector('.ms-attrib')) {
    const a = document.createElement('div');
    a.className = 'ms-attrib';
    a.textContent = YANDEX_KEY
      ? '© Яндекс Карты · Тарифы: ФАС / Росстат'
      : '© OpenStreetMap · Тарифы: ФАС / Росстат';
    stage0.appendChild(a);
  }

  // Hover события
  let lastHover = null;
  state.map.on('pointermove', (evt) => {
    if (evt.dragging) return;
    const feature = state.map.forEachFeatureAtPixel(evt.pixel, f => f);
    const target = state.map.getTargetElement();
    target.style.cursor = feature ? 'pointer' : '';

    if (feature !== lastHover) {
      if (lastHover) lastHover.set('hovered', false);
      if (feature) feature.set('hovered', true);
      lastHover = feature;
      state.vectorSource.changed();
    }

    if (feature) {
      showTooltip(feature, evt.pixel);
    } else {
      hideTooltip();
    }
  });

  // Клик
  state.map.on('click', (evt) => {
    const feature = state.map.forEachFeatureAtPixel(evt.pixel, f => f);
    if (feature) {
      const props = feature.getProperties();
      if (window.innerWidth <= 768) {
        // На мобайле — показываем bottom-sheet с CTA
        showMobileSheet(props);
      } else {
        // На десктопе — переход на страницу региона
        openRegionPage(props.slug);
      }
    } else {
      hideMobileSheet();
    }
  });

  // Controls
  document.getElementById('zoom-in').onclick = () => {
    state.map.getView().animate({ zoom: state.map.getView().getZoom() + 1, duration: 200 });
  };
  document.getElementById('zoom-out').onclick = () => {
    state.map.getView().animate({ zoom: state.map.getView().getZoom() - 1, duration: 200 });
  };
  document.getElementById('zoom-reset').onclick = () => {
    state.selectedSlug = null;
    state.map.getView().animate({
      center: state.initialCenter,
      zoom: state.initialZoom,
      duration: 400
    });
    state.vectorSource.changed();
  };
  document.getElementById('fullscreen').onclick = () => {
    // Контейнер map-stage имеет уникальный id (map-stage-<instance>); ищем по классу внутри родителя.
    const mapEl = document.getElementById('map');
    const el = mapEl ? mapEl.closest('.map-stage') : null;
    if (document.fullscreenElement) document.exitFullscreen();
    else if (el) el.requestFullscreen?.();
  };
}

/* =========================================================
   Мини-карта для страницы региона
   ========================================================= */
// Резолвер ol.control.defaults() — в OL 9.x это namespace с вложенной функцией defaults().
function resolveDefaultControls(opts) {
  const cd = ol.control.defaults;
  if (typeof cd === 'function') return cd(opts);
  if (cd && typeof cd.defaults === 'function') return cd.defaults(opts);
  // Крайний fallback — пустой Collection
  return new ol.Collection();
}

function initMiniMap(container) {
  const { Map, View } = ol;
  const { Tile: TileLayer, Vector: VectorLayer } = ol.layer;
  const { OSM, Vector: VectorSource, XYZ } = ol.source;
  const { GeoJSON } = ol.format;
  const { Style, Fill, Stroke } = ol.style;
  const { fromLonLat } = ol.proj;

  const slug = container.dataset.region;
  const innerMap = container.querySelector('[id^="map-mini-"]');
  if (!innerMap || !slug) return;

  // Найдём фичу нужного региона
  const features = new GeoJSON().readFeatures(state.geojson, {
    featureProjection: 'EPSG:3857',
    dataProjection: 'EPSG:4326'
  });

  const vectorSource = new VectorSource({ features });
  const vectorLayer = new VectorLayer({
    source: vectorSource,
    style: (feature) => {
      const props = feature.getProperties();
      const isTarget = props.slug === slug;
      if (!isTarget) {
        return new Style({
          fill: new Fill({ color: 'rgba(148,163,184,0.15)' }),
          stroke: new Stroke({ color: '#CBD5E1', width: 0.5 })
        });
      }
      const tierColor = TIER_COLORS[(props.tier || 1) - 1] || '#046BD2';
      const [r, g, b] = hexToRgb(tierColor);
      return new Style({
        fill: new Fill({ color: `rgba(${r},${g},${b},0.55)` }),
        stroke: new Stroke({ color: '#046BD2', width: 2 })
      });
    }
  });

  const YANDEX_KEY = (window.__MS_YANDEX_KEY__ || '').trim();
  const baseLayer = YANDEX_KEY
    ? new TileLayer({
        source: new XYZ({
          url: `https://tiles.api-maps.yandex.ru/v1/tiles/?apikey=${YANDEX_KEY}&lang=ru_RU&x={x}&y={y}&z={z}&l=map`,
          attributions: [],
          maxZoom: 19,
          crossOrigin: 'anonymous'
        })
      })
    : new TileLayer({ source: new OSM({ attributions: [] }) });

  // Никаких интеракций, никаких контролов OL в мини-карте.
  const map = new Map({
    target: innerMap.id,
    layers: [baseLayer, vectorLayer],
    controls: new ol.Collection(),
    interactions: new ol.Collection(),
    view: new View({
      center: fromLonLat([100, 65]),
      zoom: 2,
      minZoom: 1,
      maxZoom: 8
    })
  });

  // HTML-атрибуция в правом нижнем углу контейнера мини-карты.
  if (!container.querySelector('.ms-attrib')) {
    const a = document.createElement('div');
    a.className = 'ms-attrib ms-attrib--mini';
    a.textContent = YANDEX_KEY ? '© Яндекс Карты' : '© OpenStreetMap';
    container.style.position = 'relative';
    container.appendChild(a);
  }

  // Зум на регион
  const target = features.find(f => f.get('slug') === slug);
  if (target) {
    const ext = target.getGeometry().getExtent();
    map.getView().fit(ext, { padding: [24, 24, 24, 24], maxZoom: 6 });
  }
}

function styleForFeature(feature) {
  const { Style, Fill, Stroke } = ol.style;
  const props = feature.getProperties();
  const value = props[state.layer];
  const isFiltered = passesFilters(props);
  const isHover = props.hovered;
  const isSelected = props.slug === state.selectedSlug;

  // Базовая прозрачность из слайдера. Отфильтрованные в 5 раз прозрачнее.
  // Hover поднимает до мин 80%.
  let opacity;
  if (!isFiltered) {
    opacity = state.regionsOpacity * 0.2;  // 10% при ползунке 50%
  } else if (isHover) {
    opacity = Math.max(state.regionsOpacity, 0.8);
  } else {
    opacity = state.regionsOpacity;
  }

  // Определяем как красить
  let fillStyle;
  const noData = !props.has_data;
  const noGasOnGasLayer = state.layer === 'gas' && value == null && props.has_data;

  if (noData || noGasOnGasLayer) {
    // Диагональная штриховка на сером фоне
    fillStyle = new Fill({ color: getHatchPattern(opacity) });
  } else if (value == null) {
    // Для неизвестных значений (но регион имеет данные) — просто серый
    const [r, g, b] = hexToRgb(NA_COLOR);
    fillStyle = new Fill({ color: `rgba(${r},${g},${b},${opacity})` });
  } else {
    const tier = getTierForValue(value, state.layer);
    const color = TIER_COLORS[tier - 1] || NA_COLOR;
    const [r, g, b] = hexToRgb(color);
    fillStyle = new Fill({ color: `rgba(${r},${g},${b},${opacity})` });
  }

  return new Style({
    fill: fillStyle,
    stroke: new Stroke({
      color: isHover || isSelected ? '#046BD2' : '#fff',
      width: isHover || isSelected ? 2 : 0.8
    })
  });
}

function hexToRgb(hex) {
  const h = hex.replace('#', '');
  return [
    parseInt(h.substr(0, 2), 16),
    parseInt(h.substr(2, 2), 16),
    parseInt(h.substr(4, 2), 16)
  ];
}

function passesFilters(props) {
  if (!state.filters.districts.has(props.district)) return false;
  if (!state.filters.tiers.has(props.tier)) return false;
  if (props.bill < state.filters.billMin) return false;
  if (props.bill > state.filters.billMax) return false;
  if (state.filters.search) {
    const q = state.filters.search.toLowerCase();
    const hit = props.name.toLowerCase().includes(q) ||
                props.short_name.toLowerCase().includes(q) ||
                (props.center_city || '').toLowerCase().includes(q);
    if (!hit) return false;
  }
  return true;
}

/* =========================================================
   Tooltip
   ========================================================= */
function showTooltip(feature, pixel) {
  const tt = document.getElementById('tooltip');
  const props = feature.getProperties();
  const layer = state.layer;
  const value = props[layer];
  const layerCfg = LAYERS[layer];

  const fmt = (v, unit) => v != null ? layerCfg.format(v) : '—';
  const safeNum = (v, fmtFn) => v != null ? fmtFn(v) : '—';

  const isEstimate = props.bill_estimated && (layer === 'bill');
  const estimateMark = isEstimate ? ' <span style="font-size:11px; color:var(--warn); font-weight:600;" title="Оценочное значение">· оценка</span>' : '';
  const headerValue = props.has_data
    ? `${value != null ? layerCfg.format(value) : '<span style="color:var(--text-muted)">—</span>'} <small>${value != null ? layerCfg.unit : ''}</small>${estimateMark}`
    : `<span style="color:var(--text-muted); font-size:16px;">Данные в сборе</span>`;

  tt.innerHTML = `
    <div class="map-tooltip__t">
      <span class="tier-dot t${props.tier || 0}"></span>
      ${props.short_name}
    </div>
    <div class="map-tooltip__s">${DISTRICT_FULL[props.district] || props.district} ФО · ${props.center_city}</div>
    <div class="map-tooltip__big">${headerValue}</div>
    <div class="map-tooltip__row"><span>Средний платёж</span><b>${safeNum(props.bill, v => v.toLocaleString('ru-RU') + ' ₽/мес')}</b></div>
    <div class="map-tooltip__row"><span>Электричество</span><b>${safeNum(props.electricity, v => v.toFixed(2) + ' ₽/кВт·ч')}</b></div>
    <div class="map-tooltip__row"><span>Холодная вода</span><b>${safeNum(props.water, v => v.toFixed(2) + ' ₽/м³')}</b></div>
    <div class="map-tooltip__row"><span>Газ</span><b>${props.gas != null ? props.gas.toFixed(2) + ' ₽/м³' : '—'}</b></div>
    <div class="map-tooltip__cta">Кликните, чтобы открыть страницу региона →</div>
  `;
  tt.classList.add('show');
  // Позиционирование: pixel приходит от OL и относится к контейнеру #map.
  // Tooltip лежит внутри .map-stage (sibling #map), а .map-stage = position:relative.
  // Поскольку #map = position:absolute; inset:0 — координаты совпадают.
  const mapEl = document.getElementById('map');
  const stage = mapEl ? mapEl.closest('.map-stage') : null;
  if (!stage) return;
  const w = tt.offsetWidth;
  const h = tt.offsetHeight;
  let x = pixel[0] + 16;
  let y = pixel[1] + 16;
  if (x + w > stage.clientWidth - 8) x = pixel[0] - w - 16;
  if (y + h > stage.clientHeight - 8) y = pixel[1] - h - 16;
  if (x < 8) x = 8;
  if (y < 8) y = 8;
  tt.style.left = x + 'px';
  tt.style.top = y + 'px';
}

function hideTooltip() {
  document.getElementById('tooltip').classList.remove('show');
}

/* =========================================================
   Mobile bottom-sheet (на тапе)
   ========================================================= */
function showMobileSheet(props) {
  const tt = document.getElementById('tooltip');
  const safeNum = (v, fmtFn) => v != null ? fmtFn(v) : '—';
  const headerValue = props.bill != null
    ? `${props.bill.toLocaleString('ru-RU')} ₽ <small>/мес</small>`
    : `<span style="color:var(--text-muted); font-size:18px;">Данные в сборе</span>`;
  tt.innerHTML = `
    <div class="map-tooltip__t">
      <span class="tier-dot t${props.tier || 0}"></span>
      ${props.short_name}
    </div>
    <div class="map-tooltip__s">${DISTRICT_FULL[props.district] || props.district} ФО · ${props.center_city}</div>
    <div class="map-tooltip__big">${headerValue}</div>
    <div class="map-tooltip__row"><span>Электричество</span><b>${safeNum(props.electricity, v => v.toFixed(2) + ' ₽/кВт·ч')}</b></div>
    <div class="map-tooltip__row"><span>Холодная вода</span><b>${safeNum(props.water, v => v.toFixed(2) + ' ₽/м³')}</b></div>
    <div class="map-tooltip__row"><span>Горячая вода</span><b>${safeNum(props.hot_water, v => v.toFixed(2) + ' ₽/м³')}</b></div>
    <div class="map-tooltip__row"><span>Газ</span><b>${safeNum(props.gas, v => v.toFixed(2) + ' ₽/м³')}</b></div>
    <div class="map-tooltip__cta" style="text-align:center; padding-top:14px;">
      <a href="/tarify-zhku/${props.slug}/" style="display:block; background:var(--grad-brand); color:white; padding:12px; border-radius:8px; font-weight:700; font-size:14px;">Открыть страницу региона →</a>
    </div>
  `;
  tt.classList.add('show', 'bottom-sheet');
  tt.style.left = '';
  tt.style.top = '';
}
function hideMobileSheet() {
  const tt = document.getElementById('tooltip');
  tt.classList.remove('bottom-sheet', 'show');
}

function openRegionPage(slug) {
  const region = state.regions.find(r => r.slug === slug);
  if (!region) return;
  // Если с сервера пришёл полный URL — используем его; иначе собираем из prefix + slug.
  const url = region.url || (state.urlPrefix + slug + '/');
  window.location.href = url;
}

/* =========================================================
   Фильтры — рендер
   ========================================================= */
function renderFilters() {
  // Округа
  const d = document.getElementById('filter-districts');
  d.innerHTML = DISTRICTS.map(code => {
    const cnt = state.districtCounts[code] || 0;
    return `<label class="filters__check">
      <input type="checkbox" data-district="${code}" checked>
      ${code}
      <span class="count">${cnt}</span>
    </label>`;
  }).join('');
  d.querySelectorAll('input').forEach(inp => {
    inp.onchange = () => {
      const code = inp.dataset.district;
      if (inp.checked) state.filters.districts.add(code);
      else state.filters.districts.delete(code);
      applyFilters();
    };
  });

  // Тиры
  const t = document.getElementById('filter-tiers');
  const tierLabels = ['Очень дёшево','Дёшево','Средне','Дорого','Очень дорого'];
  const tierCounts = [0,0,0,0,0];
  state.regions.forEach(r => tierCounts[r.tier - 1]++);
  t.innerHTML = tierLabels.map((lab, i) => {
    return `<label class="filters__check">
      <input type="checkbox" data-tier="${i+1}" checked>
      <span class="tier-dot t${i+1}"></span>
      ${lab}
      <span class="count">${tierCounts[i]}</span>
    </label>`;
  }).join('');
  t.querySelectorAll('input').forEach(inp => {
    inp.onchange = () => {
      const tier = parseInt(inp.dataset.tier);
      if (inp.checked) state.filters.tiers.add(tier);
      else state.filters.tiers.delete(tier);
      applyFilters();
    };
  });

  // Range slider
  initRangeSlider();

  // Кнопка «Применить» убрана — фильтры применяются на лету в обработчиках выше
  document.getElementById('reset-filters').onclick = (e) => {
    e.preventDefault();
    resetFilters();
  };

  // Search
  initSearch();
}

function initRangeSlider() {
  const bar = document.querySelector('.range__bar');
  const h1 = document.getElementById('range-h1');
  const h2 = document.getElementById('range-h2');
  const fill = document.getElementById('range-fill');
  const labMin = document.getElementById('range-min');
  const labMax = document.getElementById('range-max');

  function update() {
    const range = state.maxBill - state.minBill;
    const left = ((state.filters.billMin - state.minBill) / range) * 100;
    const right = ((state.filters.billMax - state.minBill) / range) * 100;
    h1.style.left = left + '%';
    h2.style.left = right + '%';
    fill.style.left = left + '%';
    fill.style.right = (100 - right) + '%';
    labMin.textContent = state.filters.billMin.toLocaleString('ru-RU') + ' ₽';
    labMax.textContent = state.filters.billMax.toLocaleString('ru-RU') + ' ₽';
  }
  state.updateRange = update;
  update();

  function startDrag(handle, isMin) {
    const onMove = (e) => {
      const rect = bar.getBoundingClientRect();
      const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
      const pct = Math.max(0, Math.min(100, (x / rect.width) * 100));
      const value = Math.round(state.minBill + (pct / 100) * (state.maxBill - state.minBill));
      if (isMin) state.filters.billMin = Math.min(value, state.filters.billMax - 100);
      else state.filters.billMax = Math.max(value, state.filters.billMin + 100);
      update();
      applyFilters();
    };
    const onEnd = () => {
      document.removeEventListener('mousemove', onMove);
      document.removeEventListener('mouseup', onEnd);
      document.removeEventListener('touchmove', onMove);
      document.removeEventListener('touchend', onEnd);
    };
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onEnd);
    document.addEventListener('touchmove', onMove);
    document.addEventListener('touchend', onEnd);
  }
  h1.addEventListener('mousedown', () => startDrag(h1, true));
  h2.addEventListener('mousedown', () => startDrag(h2, false));
  h1.addEventListener('touchstart', () => startDrag(h1, true));
  h2.addEventListener('touchstart', () => startDrag(h2, false));
}

function resetFilters() {
  state.filters.districts = new Set(DISTRICTS);
  state.filters.tiers = new Set([1,2,3,4,5]);
  state.filters.billMin = state.minBill;
  state.filters.billMax = state.maxBill;
  state.filters.search = '';
  document.getElementById('search').value = '';
  document.querySelectorAll('.filters input[type=checkbox]').forEach(c => c.checked = true);
  state.updateRange();
  applyFilters();
}

function applyFilters() {
  // Перерисовать карту
  state.vectorSource.changed();
  // Перерисовать таблицу (синхронизирована с картой)
  renderTable();
  // Обновить счётчики (динамически из данных)
  const total = state.regions.length;
  const visible = state.regions.filter(passesFilters).length;
  document.getElementById('visible-count').textContent = visible;
  document.getElementById('total-count').textContent = total;
  document.getElementById('table-count').textContent = `${visible} из ${total}`;
  // Бейдж «Применены фильтры» в заголовке таблицы
  const filtersActive = visible < total;
  const note = document.getElementById('table-filter-note');
  if (note) note.hidden = !filtersActive;
}

/* =========================================================
   Поиск с подсказками
   ========================================================= */
function initSearch() {
  const input = document.getElementById('search');
  const results = document.getElementById('search-results');

  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    state.filters.search = '';  // search-фильтр работает только при клике/Enter
    if (!q) { results.classList.remove('open'); return; }
    const matches = state.regions
      .filter(r => r.name.toLowerCase().includes(q) ||
                   r.short_name.toLowerCase().includes(q) ||
                   (r.center_city || '').toLowerCase().includes(q))
      .slice(0, 8);
    if (matches.length === 0) { results.classList.remove('open'); return; }
    results.innerHTML = matches.map(r => `
      <div class="search__row" data-slug="${r.slug}">
        <span class="tier-dot t${r.tier}"></span>
        <span class="name">${r.short_name}</span>
        <span class="val">${r.bill.toLocaleString('ru-RU')} ₽</span>
      </div>
    `).join('');
    results.classList.add('open');
    results.querySelectorAll('.search__row').forEach(row => {
      row.onclick = () => {
        const slug = row.dataset.slug;
        const feat = state.vectorSource.getFeatures().find(f => f.get('slug') === slug);
        if (feat) {
          state.selectedSlug = slug;
          const ext = feat.getGeometry().getExtent();
          state.map.getView().fit(ext, { duration: 400, padding: [60,60,60,60], maxZoom: 6 });
          state.vectorSource.changed();
        }
        input.value = state.regions.find(r => r.slug === slug).short_name;
        results.classList.remove('open');
      };
    });
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.search')) results.classList.remove('open');
  });
}

/* =========================================================
   Tabs: переключение слоя карты
   ========================================================= */
function initLayerTabs() {
  document.querySelectorAll('.map-tab').forEach(tab => {
    tab.onclick = () => {
      document.querySelectorAll('.map-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      state.layer = tab.dataset.layer;

      // Обновим тиры по новому слою
      state.regions.forEach(r => {
        r.tier = getTierForValue(r[state.layer], state.layer);
      });
      // Обновим фичи
      state.vectorSource.getFeatures().forEach(f => {
        f.set('tier', getTierForValue(f.get(state.layer), state.layer));
      });

      // Легенда
      renderLegend();
      // Перерисовка карты
      state.vectorSource.changed();
      // Таблица
      renderTable();
    };
  });
}

/* =========================================================
   Легенда (динамическая по выбранному слою)
   ========================================================= */
function renderLegend() {
  const layerCfg = LAYERS[state.layer];
  document.getElementById('legend-title').textContent = layerCfg.title;
  const breaks = state.breaks[state.layer];
  const counts = [0,0,0,0,0];
  state.regions.forEach(r => {
    const t = getTierForValue(r[state.layer], state.layer);
    if (t >= 1 && t <= 5) counts[t-1]++;
  });

  const validValues = state.regions.map(r => r[state.layer]).filter(v => v != null);
  const ranges = [];
  let prev = validValues.length ? Math.min(...validValues) : 0;
  for (let i = 0; i < 5; i++) {
    const cur = breaks[i];
    ranges.push({ from: prev, to: cur, tier: i+1, count: counts[i] });
    prev = cur;
  }

  let html = ranges.map(r => `
    <div class="legend__row">
      <div class="legend__swatch" style="background:${TIER_COLORS[r.tier-1]}"></div>
      <span class="legend__label">${formatLegendRange(r.from, r.to)}</span>
      <span class="legend__count">${r.count}</span>
    </div>
  `).join('');

  // Доп строка: «Нет сетевого газа» для слоя Газ
  if (state.layer === 'gas') {
    const noGasCount = state.regions.filter(r => r.has_data && r.gas == null).length;
    if (noGasCount > 0) {
      html += `<div class="legend__row legend__row--na">
        <div class="legend__swatch"></div>
        <span class="legend__label">Нет сетевого газа</span>
        <span class="legend__count">${noGasCount}</span>
      </div>`;
    }
  }
  // Доп строка: «Данные в сборе» всегда, если есть N/A регионы
  const naCount = state.regions.filter(r => !r.has_data).length;
  if (naCount > 0) {
    html += `<div class="legend__row legend__row--na">
      <div class="legend__swatch"></div>
      <span class="legend__label">Данные в сборе</span>
      <span class="legend__count">${naCount}</span>
    </div>`;
  }

  document.getElementById('legend-rows').innerHTML = html;
}

function formatLegendRange(from, to) {
  const layerCfg = LAYERS[state.layer];
  if (state.layer === 'bill') {
    return `${Math.round(from).toLocaleString('ru-RU')} – ${Math.round(to).toLocaleString('ru-RU')}`;
  }
  return `${from.toFixed(2)} – ${to.toFixed(2)}`;
}

/* =========================================================
   Аналитика
   ========================================================= */
function renderDistribution() {
  const d = document.getElementById('distribution');
  const tierLabels = ['Дёшево','Эконом','Средне','Дорого','Дорого+'];
  const counts = [0,0,0,0,0];
  state.regions.forEach(r => counts[r.tier - 1]++);
  const maxCount = Math.max(...counts);

  d.innerHTML = counts.map((cnt, i) => `
    <div class="bar-row">
      <span class="lab"><span class="tier-dot t${i+1}"></span>${tierLabels[i]}</span>
      <div class="bar-track">
        <div class="bar-fill" style="background:${TIER_COLORS[i]}; width:${(cnt/maxCount)*100}%"></div>
      </div>
      <span class="cnt">${cnt} (${Math.round(cnt/state.regions.length*100)}%)</span>
    </div>
  `).join('');
}

function renderToplist() {
  const t = document.getElementById('toplist');
  const sorted = [...state.regions].sort((a, b) =>
    state.toplistMode === 'expensive' ? b.bill - a.bill : a.bill - b.bill
  ).slice(0, 5);
  const avg = 5500;

  t.innerHTML = sorted.map((r, i) => {
    const diff = Math.round((r.bill - avg) / avg * 100);
    const diffCls = diff > 0 ? 'warn' : 'good';
    const diffStr = (diff > 0 ? '+' : '') + diff + '% к среднему';
    return `<div class="toplist__row" data-slug="${r.slug}">
      <div class="toplist__rank">${i+1}</div>
      <div class="toplist__name">
        <strong>${r.short_name}</strong>
        <span>${r.district} · ${r.center_city}</span>
      </div>
      <div class="toplist__val">
        <b>${r.bill.toLocaleString('ru-RU')} ₽</b>
        <span class="${diffCls}">${diffStr}</span>
      </div>
    </div>`;
  }).join('');

  t.querySelectorAll('.toplist__row').forEach(row => {
    row.onclick = () => openRegionPage(row.dataset.slug);
  });

  document.getElementById('toplist-title').innerHTML = state.toplistMode === 'expensive'
    ? `Топ-5 самых дорогих регионов <a href="#" id="toggle-top">Самые дешёвые →</a>`
    : `Топ-5 самых дешёвых регионов <a href="#" id="toggle-top">Самые дорогие →</a>`;

  document.getElementById('toggle-top').onclick = (e) => {
    e.preventDefault();
    state.toplistMode = state.toplistMode === 'expensive' ? 'cheap' : 'expensive';
    renderToplist();
  };
}

/* =========================================================
   Таблица регионов
   ========================================================= */
function renderTable() {
  const tb = document.getElementById('table-body');
  const tierLabels = ['Очень дёшево','Дёшево','Средне','Дорого','Очень дорого'];
  let rows = state.regions.filter(passesFilters);

  // Сортировка
  const dir = state.sortDir === 'asc' ? 1 : -1;
  rows.sort((a, b) => {
    const va = a[state.sortKey];
    const vb = b[state.sortKey];
    if (typeof va === 'string') return va.localeCompare(vb, 'ru') * dir;
    return ((va || 0) - (vb || 0)) * dir;
  });

  if (rows.length === 0) {
    tb.innerHTML = '<tr><td colspan="8" class="empty">Нет регионов, соответствующих фильтрам</td></tr>';
    return;
  }

  const fmt = v => (v == null ? '—' : v.toFixed(2));
  const fmtBill = (v, isEst) => {
    if (v == null) return '<span style="color:var(--text-muted); font-style:italic;">N/A</span>';
    const mark = isEst ? ' <span style="font-size:10px; color:var(--warn); font-weight:600;" title="Оценочное значение">~</span>' : '';
    return `<b>${v.toLocaleString('ru-RU')} ₽</b>${mark}`;
  };
  const fmtTier = (tier, hasData) => {
    if (!hasData) return `<span class="tier-badge" style="background:#94A3B8;">Данные в сборе</span>`;
    return `<span class="tier-badge t${tier}">${tierLabels[tier - 1] || '—'}</span>`;
  };
  tb.innerHTML = rows.map((r, i) => `
    <tr data-slug="${r.slug}" ${!r.has_data ? 'style="opacity:.7;"' : ''}>
      <td style="color:var(--text-muted);">${i+1}</td>
      <td><span class="region-name">${r.short_name}</span></td>
      <td style="color:var(--text-muted);">${r.district}</td>
      <td class="num">${fmt(r.electricity)}</td>
      <td class="num">${fmt(r.water)}</td>
      <td class="num">${fmt(r.gas)}</td>
      <td class="num">${fmtBill(r.bill, r.bill_estimated)}</td>
      <td>${fmtTier(r.tier, r.has_data)}</td>
    </tr>
  `).join('');

  tb.querySelectorAll('tr').forEach(tr => {
    tr.onclick = () => openRegionPage(tr.dataset.slug);
  });
}

function initTableSorting() {
  document.querySelectorAll('.regions-table th[data-sort]').forEach(th => {
    th.onclick = () => {
      const key = th.dataset.sort;
      if (key === 'rank') return;
      if (state.sortKey === key) {
        state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        state.sortKey = key;
        state.sortDir = 'desc';
      }
      document.querySelectorAll('.regions-table th').forEach(t => {
        t.classList.remove('sorted');
        t.textContent = t.textContent.replace(/[↑↓]/g, '').trim();
      });
      th.classList.add('sorted');
      const arrow = state.sortDir === 'asc' ? ' ↑' : ' ↓';
      th.textContent = th.textContent + arrow;
      renderTable();
    };
  });
}

/* =========================================================
   View toggle: карта/таблица (для мобильного)
   ========================================================= */
function initViewToggle() {
  document.querySelectorAll('.map-toolbar__btn[data-view]').forEach(b => {
    b.onclick = () => {
      document.querySelectorAll('.map-toolbar__btn[data-view]').forEach(x => x.classList.remove('active'));
      b.classList.add('active');
      const v = b.dataset.view;
      if (v === 'table') {
        document.getElementById('table').scrollIntoView({ behavior: 'smooth' });
      }
    };
  });

  document.getElementById('btn-export').onclick = () => {
    // Экспорт отфильтрованного среза
    const rows = state.regions.filter(passesFilters);
    const csv = [
      ['Регион','Округ','Эл-во ₽/кВт·ч','ХВС ₽/м³','Газ ₽/м³','Платёж ₽/мес','Уровень'],
      ...rows.map(r => [r.name, r.district, r.electricity, r.water, r.gas ?? '', r.bill, r.tier])
    ].map(r => r.join(';')).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'mistersaver-tarify-zhku.csv';
    a.click();
  };

  document.getElementById('btn-share').onclick = () => {
    if (navigator.share) {
      navigator.share({
        title: 'Карта тарифов ЖКУ по регионам России',
        text: 'Сравните тарифы ЖКУ по 85 регионам России',
        url: location.href
      });
    } else {
      navigator.clipboard.writeText(location.href);
      alert('Ссылка скопирована');
    }
  };
}

/* =========================================================
   Контрольные элементы: слайдер прозрачности, кнопка «К карте»
   ========================================================= */
function initOpacityControl() {
  const root = document.getElementById('opacity-control');
  const toggle = document.getElementById('opacity-toggle');
  const slider = document.getElementById('opacity-slider');
  const val = document.getElementById('opacity-val');
  if (!root || !slider) return;

  toggle.onclick = () => root.classList.toggle('open');
  // Клик вне — закрываем
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#opacity-control')) root.classList.remove('open');
  });

  const toggleVal = document.getElementById('opacity-toggle-val');
  const update = (pct) => {
    state.regionsOpacity = pct / 100;
    val.textContent = pct + '%';
    if (toggleVal) toggleVal.textContent = pct + '%';
    _hatchPattern = null;  // сброс кэша штриховки
    if (state.vectorSource) state.vectorSource.changed();
  };
  slider.addEventListener('input', () => update(parseInt(slider.value, 10)));
  update(parseInt(slider.value, 10));
}

function initBackToMap() {
  const btn = document.getElementById('back-to-map');
  if (!btn) return;
  btn.onclick = () => {
    document.querySelector('.ms-mapcalc').scrollIntoView({ behavior: 'smooth', block: 'start' });
  };
}

/* =========================================================
   Старт
   ========================================================= */
(async function main() {
  // Mini-mode: страница региона. Только мини-карта, без UI таблицы/фильтров.
  // Full-mode определяем по наличию #map (из map-full.php), mini — по .ms-tariff-map--mini.
  const fullContainer = document.getElementById('map');
  const miniContainer = document.querySelector('.ms-tariff-map--mini');

  if (!fullContainer && miniContainer) {
    try {
      await loadData();
      initMiniMap(miniContainer);
    } catch (e) {
      console.error('[ms-tariff-map] mini init failed:', e);
    }
    return;
  }

  if (!fullContainer) return;
  await loadData();
  // Пересчитаем тиры по выбранному слою
  state.regions.forEach(r => {
    r.tier = getTierForValue(r[state.layer], state.layer);
  });

  // Динамические KPI
  const total = state.regions.length;
  const withData = state.regions.filter(r => r.has_data).length;
  document.getElementById('kpi-total').textContent = total;
  document.getElementById('kpi-total-d').textContent =
    withData === total ? 'в датасете' : `из них ${withData} с данными`;

  // Мин/макс по bill
  const withBill = state.regions.filter(r => r.bill != null);
  if (withBill.length) {
    const minR = withBill.reduce((a, b) => a.bill < b.bill ? a : b);
    const maxR = withBill.reduce((a, b) => a.bill > b.bill ? a : b);
    document.getElementById('kpi-min').textContent = minR.bill.toLocaleString('ru-RU') + ' ₽';
    document.getElementById('kpi-min-d').textContent = minR.short_name;
    document.getElementById('kpi-max').textContent = maxR.bill.toLocaleString('ru-RU') + ' ₽';
    document.getElementById('kpi-max-d').textContent = maxR.short_name;
  }

  renderFilters();
  initMap();
  initLayerTabs();
  initTableSorting();
  initViewToggle();
  initOpacityControl();
  initBackToMap();
  renderLegend();
  renderDistribution();
  renderToplist();
  renderTable();
  applyFilters();  // для проставления правильных счётчиков и бейджей при старте
})();
