<?php
require_once __DIR__ . '/../config/auth.php';

$db = getDB();

// Load all visible layers with category info
$layers = $db->query("
    SELECT l.*, c.name as category_name, c.slug as category_slug
    FROM gis_layers l
    LEFT JOIN gis_categories c ON c.id = l.category_id
    WHERE l.is_visible = 1
    ORDER BY l.sort_order
")->fetchAll();

// Load all active markers grouped by layer as GeoJSON
$markersStmt = $db->query("
    SELECT m.id, m.layer_id, m.title, m.description, m.latitude, m.longitude,
           m.properties, m.status,
           GROUP_CONCAT(i.file_path ORDER BY i.sort_order SEPARATOR '|||') as images
    FROM gis_markers m
    LEFT JOIN gis_marker_images i ON i.marker_id = m.id
    WHERE m.status = 'active'
    GROUP BY m.id
    ORDER BY m.layer_id, m.id
");

$geojsonByLayer = [];
foreach ($markersStmt as $row) {
    $lid = $row['layer_id'];
    if (!isset($geojsonByLayer[$lid])) {
        $geojsonByLayer[$lid] = [
            'type' => 'FeatureCollection',
            'features' => []
        ];
    }
    $geojsonByLayer[$lid]['features'][] = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [(float)$row['longitude'], (float)$row['latitude']]
        ],
        'properties' => [
            'id'          => (int)$row['id'],
            'title'       => $row['title'],
            'description' => $row['description'],
            'status'      => $row['status'],
            'images'      => $row['images'] ? explode('|||', $row['images']) : [],
            'extra'       => json_decode($row['properties'], true) ?: []
        ]
    ];
}
?>
<?php include __DIR__ . "/../template-layout/header.php"; ?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.locatecontrol@0.81.1/dist/L.Control.Locate.min.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css" />

<style>
    /* Map container */
    #map { width: 100%; height: calc(100vh - 64px - 56px); border-radius: 12px; }

    /* Custom marker pin */
    .marker-pin {
        width: 28px; height: 28px;
        border-radius: 50% 50% 50% 0;
        position: absolute;
        transform: rotate(-45deg);
        left: 50%; top: 50%;
        margin: -14px 0 0 -14px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.35);
        border: 2px solid rgba(255,255,255,0.9);
    }
    .marker-pin i { transform: rotate(45deg); font-size: 12px; color: #fff; }

    /* Popup */
    .popup-content { min-width: 220px; max-width: 300px; }
    .popup-content h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 4px; line-height: 1.3; }
    .popup-content .desc { font-size: 13px; color: #64748b; margin-bottom: 8px; line-height: 1.5; }
    .popup-content .extra-info { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
    .popup-content .extra-tag { display: inline-block; padding: 2px 8px; background: #f1f5f9; border-radius: 4px; font-size: 11px; color: #475569; }
    .popup-content .popup-img { width: 100%; border-radius: 6px; margin-top: 6px; max-height: 180px; object-fit: cover; }
    .popup-content .nav-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 12px; background: #3b82f6; color: #fff; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; margin-top: 6px; }
    .popup-content .nav-btn:hover { background: #2563eb; }
    .popup-content .coords { font-size: 11px; color: #94a3b8; margin-top: 4px; font-family: monospace; }
    .popup-content .stream-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 12px; background: #ef4444; color: #fff; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; margin-top: 6px; cursor: pointer; border: none; margin-right: 4px; }
    .popup-content .stream-btn:hover { background: #dc2626; }

    /* Camera stream modal */
    .cam-modal-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
        z-index: 9999; display: none; align-items: center; justify-content: center; padding: 16px;
    }
    .cam-modal-overlay.show { display: flex; }
    .cam-modal {
        background: #fff; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        width: 100%; max-width: 720px; overflow: hidden; animation: camFadeIn 0.3s ease;
    }
    html.dark .cam-modal { background: #1e293b; }
    @keyframes camFadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    .cam-modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid #e2e8f0;
    }
    html.dark .cam-modal-header { border-color: #334155; }
    .cam-modal-header h3 { font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
    html.dark .cam-modal-header h3 { color: #f1f5f9; }
    .cam-modal-close {
        width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer;
        background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; transition: all 0.2s;
    }
    .cam-modal-close:hover { background: #e2e8f0; color: #1e293b; }
    html.dark .cam-modal-close { background: #334155; color: #94a3b8; }
    html.dark .cam-modal-close:hover { background: #475569; color: #f1f5f9; }
    .cam-stream-wrap { position: relative; background: #000; min-height: 400px; }
    .cam-stream-wrap img { width: 100%; height: 400px; display: block; object-fit: contain; background: #000; }
    .cam-loading {
        position: absolute; inset: 0; background: rgba(0,0,0,0.8);
        display: flex; align-items: center; justify-content: center; gap: 10px;
        color: #fff; font-size: 14px;
    }
    .cam-loading.hidden { display: none; }
    .cam-spinner { width: 32px; height: 32px; border: 3px solid rgba(255,255,255,0.2); border-top-color: #ef4444; border-radius: 50%; animation: camSpin 0.8s linear infinite; }
    @keyframes camSpin { to { transform: rotate(360deg); } }
    .cam-error { color: #ef4444; background: #fef2f2; padding: 10px 16px; border-radius: 8px; margin: 12px 16px; font-size: 13px; display: none; }
    html.dark .cam-error { background: rgba(239,68,68,0.15); }
    .cam-modal-footer {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 20px; border-top: 1px solid #e2e8f0; font-size: 13px;
    }
    html.dark .cam-modal-footer { border-color: #334155; color: #94a3b8; }
    .cam-status { display: flex; align-items: center; gap: 6px; }
    .cam-status.connecting { color: #f59e0b; }
    .cam-status.connected { color: #22c55e; }
    .cam-status.error { color: #ef4444; }
    .cam-reload-btn {
        padding: 6px 14px; background: #3b82f6; color: #fff; border: none; border-radius: 8px;
        font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;
    }
    .cam-reload-btn:hover { background: #2563eb; }

    /* CCTV marker pulse */
    @keyframes cctvPulse {
        0%, 100% { box-shadow: 0 2px 6px rgba(239,68,68,0.35), 0 0 0 0 rgba(239,68,68,0.3); }
        50% { box-shadow: 0 2px 6px rgba(239,68,68,0.35), 0 0 0 8px rgba(239,68,68,0); }
    }
    .marker-cctv .marker-pin { animation: cctvPulse 2s infinite; cursor: pointer; }

    /* Leaflet overrides */
    .leaflet-control-layers { border-radius: 10px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important; border: none !important; }
    .leaflet-control-layers-expanded { padding: 10px 14px !important; }

    /* Search on map */
    .map-search {
        position: relative; z-index: 800;
        margin-bottom: -48px; margin-left: 50px; margin-top: 8px;
        width: 320px; max-width: calc(100% - 70px);
    }
    .map-search input {
        width: 100%; padding: 10px 14px 10px 38px;
        border: none; border-radius: 10px;
        background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        font-size: 14px; font-family: inherit; outline: none;
    }
    .map-search input:focus { box-shadow: 0 4px 16px rgba(59,130,246,0.25); }
    .map-search .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
    .map-search-results {
        position: absolute; top: 100%; left: 0; right: 0;
        background: #fff; border-radius: 0 0 10px 10px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        max-height: 240px; overflow-y: auto; display: none;
    }
    .map-search-results.show { display: block; }
    .sr-item { padding: 8px 14px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
    .sr-item:hover { background: #f8fafc; }
    .sr-item:last-child { border-bottom: none; }
    .sr-item .sr-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .sr-item .sr-title { font-weight: 600; color: #1e293b; }
    .sr-item .sr-layer { font-size: 11px; color: #94a3b8; margin-left: auto; }

    /* Layer legend sidebar panel */
    .legend-panel { display: flex; flex-wrap: wrap; gap: 6px; }
    .legend-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px; border-radius: 8px;
        font-size: 12px; font-weight: 500; cursor: pointer;
        border: 1px solid #e2e8f0; background: #fff;
        transition: all 0.2s;
    }
    .legend-chip.active { border-color: currentColor; }
    .legend-chip.inactive { opacity: 0.4; }
    .legend-chip .lc-dot { width: 8px; height: 8px; border-radius: 50%; }
    .legend-chip .lc-count { font-weight: 700; }
</style>

<?php include __DIR__ . "/../template-layout/sidebar.php"; ?>

<div id="mainContent" class="main-expanded transition-all duration-300 min-h-screen flex flex-col">

    <?php include __DIR__ . "/../template-layout/navbar.php"; ?>

    <main class="flex-1 p-4 lg:p-6">

        <!-- Page Header -->
        <div class="mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Web GIS Map</h1>
                    <p class="text-sm text-gray-500 mt-0.5">แผนที่ข้อมูลเชิงพื้นที่ เทศบาลนครรังสิต</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="../admin/index.php" class="hover:text-gray-700">Home</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Web GIS Map</span>
                </nav>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <?php foreach ($layers as $layer): ?>
                <?php $count = count($geojsonByLayer[$layer['id']]['features'] ?? []); ?>
                <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                         style="background:<?= htmlspecialchars($layer['marker_color']) ?>15;">
                        <i class="fas <?= htmlspecialchars($layer['icon_class']) ?>"
                           style="color:<?= htmlspecialchars($layer['marker_color']) ?>;"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900"><?= $count ?></p>
                        <p class="text-xs text-gray-500 leading-tight"><?= htmlspecialchars($layer['layer_name']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="bg-white rounded-xl p-4 border border-gray-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-indigo-50">
                    <i class="fas fa-map-pin text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-gray-900">
                        <?= array_sum(array_map(fn($g) => count($g['features']), $geojsonByLayer)) ?>
                    </p>
                    <p class="text-xs text-gray-500 leading-tight">Total Markers</p>
                </div>
            </div>
        </div>

        <!-- Map Card -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Toolbar -->
            <div class="px-4 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="legend-panel" id="legendPanel">
                    <?php foreach ($layers as $layer): ?>
                        <?php $count = count($geojsonByLayer[$layer['id']]['features'] ?? []); ?>
                        <span class="legend-chip active" data-layer-id="<?= $layer['id'] ?>"
                              style="color:<?= htmlspecialchars($layer['marker_color']) ?>;">
                            <span class="lc-dot" style="background:<?= htmlspecialchars($layer['marker_color']) ?>"></span>
                            <?= htmlspecialchars($layer['layer_name']) ?>
                            <span class="lc-count"><?= $count ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="flex items-center gap-2">
                    <select id="baseMapSelect" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="osm">OpenStreetMap</option>
                        <option value="satellite">ESRI Satellite</option>
                        <option value="light">CartoDB Light</option>
                        <option value="dark">CartoDB Dark</option>
                    </select>
                </div>
            </div>

            <!-- Map Container -->
            <div class="relative">
                <!-- Search -->
                <div class="map-search">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="ค้นหาจุด..." autocomplete="off">
                    <div class="map-search-results" id="searchResults"></div>
                </div>

                <div id="map"></div>
            </div>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<!-- Camera Stream Modal -->
<div class="cam-modal-overlay" id="camModal">
    <div class="cam-modal">
        <div class="cam-modal-header">
            <h3>
                <i class="fas fa-video" style="color:#ef4444;"></i>
                <span id="camModalName">...</span>
            </h3>
            <button class="cam-modal-close" id="camModalClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="cam-stream-wrap">
            <div class="cam-loading" id="camLoading">
                <div class="cam-spinner"></div>
                <span>กำลังโหลดภาพจากกล้อง...</span>
            </div>
            <img id="camStreamImg" alt="Camera stream">
        </div>
        <div class="cam-error" id="camError">
            <i class="fas fa-exclamation-circle"></i> ไม่สามารถโหลดภาพจากกล้องได้ กรุณาลองใหม่อีกครั้ง
        </div>
        <div class="cam-modal-footer">
            <div class="cam-status connecting" id="camStatus">
                <i class="fas fa-circle-notch fa-spin"></i> กำลังเชื่อมต่อ...
            </div>
            <button class="cam-reload-btn" id="camReloadBtn"><i class="fas fa-sync-alt"></i> รีเฟรช</button>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.locatecontrol@0.81.1/dist/L.Control.Locate.min.js"></script>
<script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js"></script>

<script>
// ── Data from PHP ──
const layersConfig = <?= json_encode($layers, JSON_UNESCAPED_UNICODE) ?>;
const geojsonData  = <?= json_encode($geojsonByLayer, JSON_UNESCAPED_UNICODE) ?>;

// ── Map Init ──
const map = L.map('map', {
    center: [13.9870, 100.6100],
    zoom: 13,
    zoomControl: false,
    fullscreenControl: true,
    fullscreenControlOptions: { position: 'topleft' }
});

L.control.zoom({ position: 'topleft' }).addTo(map);

// ── Base Maps ──
const baseLayers = {
    osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
    }),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19, attribution: '&copy; Esri'
    }),
    light: L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19, attribution: '&copy; CartoDB'
    }),
    dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 19, attribution: '&copy; CartoDB'
    })
};

let currentBase = baseLayers.osm;
currentBase.addTo(map);

document.getElementById('baseMapSelect').addEventListener('change', function() {
    map.removeLayer(currentBase);
    currentBase = baseLayers[this.value];
    currentBase.addTo(map);
});

// ── Locate ──
L.control.locate({
    position: 'topleft',
    strings: { title: 'ตำแหน่งของฉัน' },
    flyTo: true, keepCurrentZoomLevel: false,
    locateOptions: { maxZoom: 16 }
}).addTo(map);

// ── Scale ──
L.control.scale({ imperial: false, position: 'bottomleft' }).addTo(map);

// ── Marker Icon ──
function createIcon(color, iconClass, isCctv) {
    return L.divIcon({
        className: isCctv ? 'custom-marker marker-cctv' : 'custom-marker',
        html: `<div class="marker-pin" style="background:${color}"><i class="fas ${iconClass}"></i></div>`,
        iconSize: [28, 36], iconAnchor: [14, 36], popupAnchor: [0, -36]
    });
}

// ── CCTV layer slug detection ──
const cctvLayerIds = layersConfig.filter(l => l.layer_slug === 'cctv').map(l => l.id);

// ── Popup ──
function buildPopup(props, layerColor, isCctv) {
    let html = `<div class="popup-content">`;
    html += `<h3 style="border-left:3px solid ${layerColor}; padding-left:8px;">${props.title}</h3>`;
    if (props.description) html += `<p class="desc">${props.description}</p>`;

    const extra = props.extra || {};
    // For CCTV, skip showing stream_url in extra tags
    const keys = Object.keys(extra).filter(k => k !== 'stream_url');
    if (keys.length) {
        html += `<div class="extra-info">`;
        keys.forEach(k => { html += `<span class="extra-tag"><b>${k}:</b> ${extra[k]}</span>`; });
        html += `</div>`;
    }
    if (props.images?.length) {
        props.images.forEach(img => { html += `<img src="${img}" class="popup-img" loading="lazy" />`; });
    }

    const c = props._coords;
    html += `<div class="coords"><i class="fas fa-crosshairs"></i> ${c[0].toFixed(6)}, ${c[1].toFixed(6)}</div>`;

    // CCTV: add "ดูสตรีมสด" button
    if (isCctv && extra.stream_url) {
        html += `<button class="stream-btn" onclick="openCamStream(${props.id}, '${props.title.replace(/'/g, "\\'")}')"><i class="fas fa-video"></i> ดูสตรีมสด</button>`;
    }
    html += `<a class="nav-btn" href="https://www.google.com/maps/dir/?api=1&destination=${c[0]},${c[1]}" target="_blank"><i class="fas fa-route"></i> นำทาง</a>`;
    html += `</div>`;
    return html;
}

// ── Load Layers ──
const clusterGroups = {};
const allMarkers = [];

layersConfig.forEach(cfg => {
    const lid = cfg.id;
    const data = geojsonData[lid];
    if (!data || !data.features.length) return;
    const isCctv = cctvLayerIds.includes(lid);

    const cluster = L.markerClusterGroup({
        maxClusterRadius: 50, spiderfyOnMaxZoom: true, showCoverageOnHover: false,
        iconCreateFunction: function(cl) {
            const n = cl.getChildCount();
            const sz = n >= 50 ? 44 : n >= 10 ? 38 : 32;
            return L.divIcon({
                html: `<div style="background:${cfg.marker_color};color:#fff;border-radius:50%;
                    width:100%;height:100%;display:flex;align-items:center;justify-content:center;
                    font-weight:700;font-size:13px;border:3px solid rgba(255,255,255,0.8);
                    box-shadow:0 2px 8px rgba(0,0,0,0.2);">${n}</div>`,
                className: 'custom-cluster', iconSize: L.point(sz, sz)
            });
        }
    });

    L.geoJSON(data, {
        pointToLayer: (f, ll) => L.marker(ll, { icon: createIcon(cfg.marker_color, cfg.icon_class, isCctv) }),
        onEachFeature: (f, layer) => {
            const p = f.properties;
            p._coords = [f.geometry.coordinates[1], f.geometry.coordinates[0]];

            if (isCctv && p.extra && p.extra.stream_url) {
                // CCTV: click opens camera stream modal directly
                layer.on('click', () => openCamStream(p.id, p.title));
            } else {
                layer.bindPopup(() => buildPopup(p, cfg.marker_color, isCctv), { maxWidth: 320 });
            }
            layer.bindTooltip(p.title, { direction: 'top', offset: [0, -30] });
            allMarkers.push({
                title: p.title, description: p.description || '',
                latlng: L.latLng(p._coords[0], p._coords[1]),
                color: cfg.marker_color, layerName: cfg.layer_name,
                leafletLayer: layer, isCctv: isCctv,
                markerId: p.id
            });
        }
    }).addTo(cluster);

    cluster.addTo(map);
    clusterGroups[lid] = cluster;
});

// ── Legend Chips Toggle ──
document.querySelectorAll('.legend-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        const lid = chip.dataset.layerId;
        const cluster = clusterGroups[lid];
        if (!cluster) return;

        if (map.hasLayer(cluster)) {
            map.removeLayer(cluster);
            chip.classList.remove('active');
            chip.classList.add('inactive');
        } else {
            map.addLayer(cluster);
            chip.classList.add('active');
            chip.classList.remove('inactive');
        }
    });
});

// ── Search ──
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');

searchInput.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    searchResults.innerHTML = '';

    if (q.length < 2) { searchResults.classList.remove('show'); return; }

    const matches = allMarkers.filter(m =>
        m.title.toLowerCase().includes(q) || m.description.toLowerCase().includes(q)
    ).slice(0, 15);

    if (!matches.length) {
        searchResults.innerHTML = '<div class="sr-item" style="color:#94a3b8;justify-content:center;">ไม่พบผลลัพธ์</div>';
        searchResults.classList.add('show');
        return;
    }

    matches.forEach(m => {
        const item = document.createElement('div');
        item.className = 'sr-item';
        item.innerHTML = `<span class="sr-dot" style="background:${m.color}"></span>
            <span class="sr-title">${m.title}</span>
            <span class="sr-layer">${m.layerName}</span>`;
        item.addEventListener('click', () => {
            map.flyTo(m.latlng, 17);
            if (m.isCctv && m.markerId) {
                openCamStream(m.markerId, m.title);
            } else {
                m.leafletLayer.openPopup();
            }
            searchResults.classList.remove('show');
            searchInput.value = m.title;
        });
        searchResults.appendChild(item);
    });
    searchResults.classList.add('show');
});

document.addEventListener('click', e => {
    if (!e.target.closest('.map-search')) searchResults.classList.remove('show');
});

// ── Invalidate map size on sidebar toggle ──
document.getElementById('sidebarToggle').addEventListener('click', () => {
    setTimeout(() => map.invalidateSize(), 350);
});

// ── Camera Stream Modal ──
const camModal = document.getElementById('camModal');
const camModalName = document.getElementById('camModalName');
const camStreamImg = document.getElementById('camStreamImg');
const camLoading = document.getElementById('camLoading');
const camError = document.getElementById('camError');
const camStatus = document.getElementById('camStatus');
let camAutoRefresh = null;

function loadCamStream(markerId) {
    camError.style.display = 'none';
    camLoading.classList.remove('hidden');
    camStatus.className = 'cam-status connecting';
    camStatus.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> กำลังเชื่อมต่อ...';
    const t = Date.now();
    camStreamImg.src = `proxy_camera_stream.php?id=${markerId}&t=${t}`;
}

camStreamImg.onload = function() {
    camLoading.classList.add('hidden');
    camStatus.className = 'cam-status connected';
    camStatus.innerHTML = '<i class="fas fa-check-circle"></i> เชื่อมต่อสำเร็จ';
};

camStreamImg.onerror = function() {
    camLoading.classList.add('hidden');
    camError.style.display = 'block';
    camStatus.className = 'cam-status error';
    camStatus.innerHTML = '<i class="fas fa-times-circle"></i> ไม่สามารถเชื่อมต่อได้';
};

function openCamStream(markerId, title) {
    camModal.dataset.markerId = markerId;
    camModalName.textContent = title;
    camModal.classList.add('show');
    loadCamStream(markerId);
    camAutoRefresh = setInterval(() => loadCamStream(markerId), 30000);
}

function closeCamModal() {
    camModal.classList.remove('show');
    clearInterval(camAutoRefresh);
    camStreamImg.src = '';
}

document.getElementById('camModalClose').addEventListener('click', closeCamModal);
document.getElementById('camReloadBtn').addEventListener('click', () => {
    const mid = camModal.dataset.markerId;
    if (mid) loadCamStream(mid);
});
camModal.addEventListener('click', e => { if (e.target === camModal) closeCamModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape' && camModal.classList.contains('show')) closeCamModal(); });
</script>
