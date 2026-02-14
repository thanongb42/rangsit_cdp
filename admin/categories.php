<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$db = getDB();
$B = BASE_URL;

// Handle form actions
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $db->prepare("INSERT INTO gis_categories (name, slug, color, icon_class, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['slug'],
            $_POST['color'] ?: '#94a3b8',
            $_POST['icon_class'] ?: null,
            $_POST['description'] ?: null,
            (int)($_POST['sort_order'] ?? 0),
        ]);
        $msg = 'เพิ่มหมวดหมู่สำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'update') {
        $stmt = $db->prepare("UPDATE gis_categories SET name=?, slug=?, color=?, icon_class=?, description=?, sort_order=? WHERE id=?");
        $stmt->execute([
            $_POST['name'],
            $_POST['slug'],
            $_POST['color'] ?: '#94a3b8',
            $_POST['icon_class'] ?: null,
            $_POST['description'] ?: null,
            (int)($_POST['sort_order'] ?? 0),
            (int)$_POST['id'],
        ]);
        $msg = 'แก้ไขหมวดหมู่สำเร็จ';
        $msgType = 'success';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $layerCount = $db->prepare("SELECT COUNT(*) FROM gis_layers WHERE category_id=?");
        $layerCount->execute([$id]);
        $count = $layerCount->fetchColumn();
        if ($count > 0) {
            $msg = "ไม่สามารถลบได้ — หมวดหมู่นี้มี {$count} Layer อยู่";
            $msgType = 'error';
        } else {
            $db->prepare("DELETE FROM gis_categories WHERE id=?")->execute([$id]);
            $msg = 'ลบหมวดหมู่สำเร็จ';
            $msgType = 'success';
        }
    }
}

// Fetch categories with layer count
$categories = $db->query("
    SELECT c.*, COUNT(l.id) as layer_count
    FROM gis_categories c
    LEFT JOIN gis_layers l ON l.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order, c.id
")->fetchAll();
?>
<?php include __DIR__ . "/../template-layout/header.php"; ?>

<?php include __DIR__ . "/../template-layout/sidebar.php"; ?>

<div id="mainContent" class="main-expanded transition-all duration-300 min-h-screen flex flex-col">

    <?php include __DIR__ . "/../template-layout/navbar.php"; ?>

    <main class="flex-1 p-6 lg:p-8">

        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Categories</h1>
                    <p class="text-sm text-gray-500 mt-1">จัดการหมวดหมู่ชั้นข้อมูล GIS</p>
                </div>
                <nav class="flex items-center space-x-2 text-sm text-gray-500">
                    <a href="index.php" class="hover:text-gray-700">Home</a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-900 font-medium">Categories</span>
                </nav>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($msg): ?>
        <div class="mb-6 px-4 py-3 rounded-lg border <?= $msgType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?> flex items-center gap-2" id="alertMsg">
            <i class="fas <?= $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <span class="text-sm font-medium"><?= htmlspecialchars($msg) ?></span>
            <button onclick="document.getElementById('alertMsg').remove()" class="ml-auto text-lg leading-none">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="ค้นหาหมวดหมู่..."
                       class="pl-10 pr-4 py-2 w-72 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
            </div>
            <button onclick="openModal()" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> เพิ่มหมวดหมู่ใหม่
            </button>
        </div>

        <!-- Categories Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" id="categoriesGrid">
            <?php if (empty($categories)): ?>
            <div class="col-span-full text-center py-16 text-gray-400">
                <i class="fas fa-folder-open text-5xl mb-4"></i>
                <p class="text-sm">ยังไม่มีหมวดหมู่</p>
            </div>
            <?php endif; ?>

            <?php foreach ($categories as $cat): ?>
            <div class="cat-card bg-white rounded-xl border border-gray-200 hover:shadow-lg transition-all duration-200 overflow-hidden group">
                <!-- Color strip -->
                <div class="h-1.5" style="background:<?= htmlspecialchars($cat['color']) ?>;"></div>

                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                                 style="background:<?= htmlspecialchars($cat['color']) ?>15;">
                                <i class="fas <?= htmlspecialchars($cat['icon_class'] ?? 'fa-folder') ?> text-lg"
                                   style="color:<?= htmlspecialchars($cat['color']) ?>;"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 cat-name"><?= htmlspecialchars($cat['name']) ?></h3>
                                <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($cat['slug']) ?></p>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onclick='editCategory(<?= json_encode($cat, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                                    class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="แก้ไข">
                                <i class="fas fa-pen text-xs"></i>
                            </button>
                            <button onclick="confirmDelete(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', <?= $cat['layer_count'] ?>)"
                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="ลบ">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <?php if ($cat['description']): ?>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed line-clamp-2"><?= htmlspecialchars($cat['description']) ?></p>
                    <?php endif; ?>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-layer-group text-xs text-gray-400"></i>
                                <span class="text-xs font-semibold text-gray-600"><?= $cat['layer_count'] ?></span>
                                <span class="text-xs text-gray-400">Layers</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-sort text-xs text-gray-400"></i>
                                <span class="text-xs text-gray-400">ลำดับ <?= $cat['sort_order'] ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full border border-white shadow-sm" style="background:<?= htmlspecialchars($cat['color']) ?>;"></span>
                            <span class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($cat['color']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </main>

    <?php include __DIR__ . "/../template-layout/footer.php"; ?>

</div>

<!-- Modal: Create / Edit Category -->
<div id="catModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <form method="POST" id="catForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">เพิ่มหมวดหมู่ใหม่</h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อหมวดหมู่ <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="fName" required
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="เช่น ระบบสาธารณูปโภค">
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" id="fSlug" required
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                           placeholder="เช่น infrastructure">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                    <textarea name="description" id="fDescription" rows="2"
                              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                              placeholder="คำอธิบายหมวดหมู่"></textarea>
                </div>

                <!-- Color & Icon Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">สี</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color" id="fColor" value="#94a3b8"
                                   class="w-10 h-10 border border-gray-200 rounded-lg cursor-pointer p-0.5">
                            <input type="text" id="fColorText" value="#94a3b8"
                                   class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   maxlength="7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i id="iconPreview" class="fas fa-folder"></i></span>
                            <input type="text" name="icon_class" id="fIcon" value="fa-folder" readonly
                                   class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer bg-white"
                                   onclick="document.getElementById('iconPicker').classList.toggle('hidden')">
                        </div>
                    </div>
                </div>

                <!-- Icon Picker -->
                <div id="iconPicker" class="hidden border border-gray-200 rounded-lg p-3 bg-white">
                    <input type="text" id="iconSearch" placeholder="ค้นหา icon..."
                           class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs mb-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <div id="iconGrid" class="grid grid-cols-8 gap-1 max-h-40 overflow-y-auto"></div>
                </div>

                <!-- Sort Order -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับการแสดงผล</label>
                    <input type="number" name="sort_order" id="fSort" value="0" min="0"
                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Preview -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">Preview</p>
                    <div class="flex items-center gap-3">
                        <div id="previewIcon" class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:#94a3b815;">
                            <i class="fas fa-folder text-lg" style="color:#94a3b8;"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-700" id="previewName">ตัวอย่างหมวดหมู่</p>
                            <p class="text-xs text-gray-400" id="previewSlug">example-slug</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    ยกเลิก
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <i class="fas fa-save mr-1.5"></i> <span id="submitText">บันทึก</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4">
        <div class="p-6 text-center">
            <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-trash-alt text-red-500 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">ยืนยันการลบ</h3>
            <p class="text-sm text-gray-500" id="deleteMsg">คุณต้องการลบหมวดหมู่นี้หรือไม่?</p>
        </div>
        <div class="flex border-t border-gray-200">
            <button onclick="closeDeleteModal()" class="flex-1 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors rounded-bl-2xl">ยกเลิก</button>
            <form method="POST" id="deleteForm" class="flex-1">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="w-full py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors rounded-br-2xl border-l border-gray-200">ลบ</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../template-layout/scripts.php"; ?>

<script>
function openModal() {
    document.getElementById('catForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('modalTitle').textContent = 'เพิ่มหมวดหมู่ใหม่';
    document.getElementById('submitText').textContent = 'บันทึก';
    document.getElementById('fColor').value = '#94a3b8';
    document.getElementById('fColorText').value = '#94a3b8';
    document.getElementById('fIcon').value = 'fa-folder';
    updatePreview();
    document.getElementById('catModal').classList.remove('hidden');
}

function editCategory(data) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = data.id;
    document.getElementById('modalTitle').textContent = 'แก้ไขหมวดหมู่';
    document.getElementById('submitText').textContent = 'อัปเดต';
    document.getElementById('fName').value = data.name;
    document.getElementById('fSlug').value = data.slug;
    document.getElementById('fDescription').value = data.description || '';
    document.getElementById('fColor').value = data.color;
    document.getElementById('fColorText').value = data.color;
    document.getElementById('fIcon').value = data.icon_class || 'fa-folder';
    document.getElementById('fSort').value = data.sort_order;
    updatePreview();
    document.getElementById('catModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('catModal').classList.add('hidden');
}

function confirmDelete(id, name, layerCount) {
    document.getElementById('deleteId').value = id;
    if (layerCount > 0) {
        document.getElementById('deleteMsg').innerHTML = `หมวดหมู่ "<b>${name}</b>" มี <b>${layerCount}</b> Layer — ไม่สามารถลบได้จนกว่าจะย้าย Layer ทั้งหมดออกก่อน`;
    } else {
        document.getElementById('deleteMsg').innerHTML = `คุณต้องการลบหมวดหมู่ "<b>${name}</b>" หรือไม่?`;
    }
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Live preview
function updatePreview() {
    const color = document.getElementById('fColor').value;
    const icon = document.getElementById('fIcon').value || 'fa-folder';
    const name = document.getElementById('fName').value || 'ตัวอย่างหมวดหมู่';
    const slug = document.getElementById('fSlug').value || 'example-slug';
    document.getElementById('previewIcon').style.background = color + '15';
    document.getElementById('previewIcon').innerHTML = `<i class="fas ${icon} text-lg" style="color:${color};"></i>`;
    document.getElementById('previewName').textContent = name;
    document.getElementById('previewSlug').textContent = slug;
    document.getElementById('iconPreview').className = 'fas ' + icon;
}

document.getElementById('fColor').addEventListener('input', function() {
    document.getElementById('fColorText').value = this.value;
    updatePreview();
});
document.getElementById('fColorText').addEventListener('input', function() {
    if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
        document.getElementById('fColor').value = this.value;
    }
    updatePreview();
});
document.getElementById('fName').addEventListener('input', updatePreview);
document.getElementById('fSlug').addEventListener('input', updatePreview);

// Icon Picker
const catIcons = [
    // Map & Location
    'fa-map-marker-alt','fa-map-pin','fa-location-dot','fa-map','fa-map-location-dot','fa-compass','fa-earth-asia','fa-globe',
    // Infrastructure
    'fa-tower-broadcast','fa-satellite-dish','fa-wifi','fa-bolt','fa-plug','fa-lightbulb','fa-solar-panel','fa-charging-station',
    // Water & Environment
    'fa-droplet','fa-faucet-drip','fa-water','fa-leaf','fa-tree','fa-seedling','fa-recycle','fa-trash-can',
    // Buildings
    'fa-building','fa-house','fa-school','fa-hospital','fa-hotel','fa-church','fa-mosque','fa-store',
    // Public Services
    'fa-hand-holding-heart','fa-people-roof','fa-person-shelter','fa-kit-medical','fa-suitcase-medical','fa-stethoscope','fa-wheelchair','fa-baby',
    // Transport
    'fa-road','fa-bridge','fa-car','fa-bus','fa-train','fa-bicycle','fa-motorcycle','fa-gas-pump',
    // Safety & Security
    'fa-shield-halved','fa-camera','fa-video','fa-fire-extinguisher','fa-triangle-exclamation','fa-bell','fa-phone','fa-lock',
    // Government & Admin
    'fa-landmark','fa-building-columns','fa-flag','fa-gavel','fa-scale-balanced','fa-id-card','fa-user-tie','fa-users',
    // Tools & Work
    'fa-screwdriver-wrench','fa-wrench','fa-hammer','fa-gear','fa-toolbox','fa-hard-hat','fa-industry','fa-warehouse',
    // Sports & Recreation
    'fa-futbol','fa-basketball','fa-dumbbell','fa-umbrella-beach','fa-campground','fa-mountain','fa-person-running','fa-bicycle',
    // Education & Culture
    'fa-graduation-cap','fa-book','fa-chalkboard-teacher','fa-microscope','fa-palette','fa-music','fa-monument','fa-chess-rook',
    // Misc
    'fa-folder','fa-folder-open','fa-tags','fa-layer-group','fa-star','fa-heart','fa-circle-info','fa-cubes',
];

const iconGrid = document.getElementById('iconGrid');
const iconSearch = document.getElementById('iconSearch');

function renderIcons(filter = '') {
    iconGrid.innerHTML = '';
    const filtered = filter ? catIcons.filter(i => i.includes(filter)) : catIcons;
    filtered.forEach(icon => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w-9 h-9 flex items-center justify-center rounded-lg hover:bg-indigo-50 hover:text-indigo-600 text-gray-500 transition-colors';
        btn.innerHTML = `<i class="fas ${icon}"></i>`;
        btn.title = icon;
        btn.addEventListener('click', () => {
            document.getElementById('fIcon').value = icon;
            document.getElementById('iconPicker').classList.add('hidden');
            updatePreview();
        });
        iconGrid.appendChild(btn);
    });
}
renderIcons();
iconSearch.addEventListener('input', function() { renderIcons(this.value.toLowerCase()); });

// Auto-generate slug
document.getElementById('fName').addEventListener('input', function() {
    if (document.getElementById('formAction').value === 'create') {
        document.getElementById('fSlug').value = this.value
            .toLowerCase().trim()
            .replace(/[^\u0E00-\u0E7Fa-z0-9\s-]/g, '')
            .replace(/[\s]+/g, '-')
            .replace(/-+/g, '-');
        updatePreview();
    }
});

// Search filter
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.cat-card').forEach(card => {
        const name = card.querySelector('.cat-name').textContent.toLowerCase();
        card.style.display = name.includes(q) ? '' : 'none';
    });
});

// Close modals on backdrop click
document.getElementById('catModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.getElementById('deleteModal').addEventListener('click', function(e) { if (e.target === this) closeDeleteModal(); });
</script>
