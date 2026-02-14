<?php
$B = defined('BASE_URL') ? BASE_URL : '/cdp';
$_page = basename($_SERVER['SCRIPT_NAME']);
function _nav($file, $icon, $label, $B, $_page) {
    $active = ($_page === $file);
    $cls = $active
        ? 'bg-indigo-50 text-indigo-600'
        : 'text-gray-700 hover:bg-gray-100';
    $iCls = $active ? '' : 'text-gray-500';
    $path = (str_contains($file, 'map')) ? "{$B}/public/{$file}" : "{$B}/admin/{$file}";
    echo "<a href=\"{$path}\" class=\"menu-item flex items-center px-3 py-2.5 rounded-lg {$cls} transition-colors relative group\">
        <i class=\"fas {$icon} text-base min-w-[20px] {$iCls}\"></i>
        <span class=\"menu-text ml-3 text-sm font-medium\">{$label}</span>
        <span class=\"tooltip absolute left-full ml-2 px-3 py-1.5 bg-gray-900 text-white text-xs rounded-md whitespace-nowrap shadow-lg z-50\">{$label}</span>
    </a>";
}
?>
<aside id="sidebar" class="sidebar-expanded fixed left-0 top-0 h-full bg-white border-r border-gray-200 transition-all duration-300 z-40 -translate-x-full md:translate-x-0">
    <div class="h-16 flex items-center justify-center border-b border-gray-200">
        <div class="flex items-center space-x-3 px-4">
            <img src="<?= $B ?>/asset/images/logo/rangsit-small-logo.png" alt="Rangsit Logo" class="h-9 w-auto flex-shrink-0">
            <span id="logoText" class="text-base font-semibold text-gray-900">Rangsit CDP</span>
        </div>
    </div>

    <div class="px-3 py-4 overflow-y-auto" style="max-height: calc(100vh - 64px);">
        <p id="menuLabel" class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Main</p>

        <nav class="space-y-1">
            <?php _nav('index.php', 'fa-th-large', 'Dashboard', $B, $_page); ?>
            <?php _nav('map.php', 'fa-map-marked-alt', 'Web GIS Map', $B, $_page); ?>
        </nav>

        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 mt-6">Data Management</p>

        <nav class="space-y-1">
            <?php _nav('layers.php', 'fa-layer-group', 'Layers', $B, $_page); ?>
            <?php _nav('categories.php', 'fa-folder-open', 'Categories', $B, $_page); ?>
            <?php _nav('markers.php', 'fa-map-pin', 'Markers', $B, $_page); ?>
            <?php _nav('marker-form.php', 'fa-map-marker-alt', 'Pin on Map', $B, $_page); ?>
        </nav>

        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 mt-6">Import / Export</p>

        <nav class="space-y-1">
            <?php _nav('import.php', 'fa-file-upload', 'Import Data', $B, $_page); ?>
            <?php _nav('export.php', 'fa-file-download', 'Export Data', $B, $_page); ?>
        </nav>

        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 mt-6">System</p>

        <nav class="space-y-1">
            <?php _nav('users.php', 'fa-users', 'Users', $B, $_page); ?>
            <?php _nav('roles.php', 'fa-user-shield', 'Roles & Permissions', $B, $_page); ?>
            <?php _nav('audit-log.php', 'fa-history', 'Audit Log', $B, $_page); ?>
            <?php _nav('settings.php', 'fa-cog', 'Settings', $B, $_page); ?>
        </nav>
    </div>
</aside>

<div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>
