<script>
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('overlay');
    const menuTexts = document.querySelectorAll('.menu-text');
    const logoText = document.getElementById('logoText');
    const menuLabel = document.getElementById('menuLabel');
    const sectionLabels = document.querySelectorAll('#sidebar p.uppercase');

    let isMobile = window.innerWidth < 768;
    let sidebarCollapsed = false;

    function updateLayout() {
        if (isMobile) {
            if (sidebar.classList.contains('translate-x-0')) {
                overlay.classList.remove('hidden');
            } else {
                overlay.classList.add('hidden');
            }
        } else {
            overlay.classList.add('hidden');
            if (sidebarCollapsed) {
                sidebar.classList.remove('sidebar-expanded');
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.remove('main-expanded');
                mainContent.classList.add('main-collapsed');
                menuTexts.forEach(text => text.classList.add('hidden'));
                logoText.classList.add('hidden');
                menuLabel.classList.add('hidden');
                sectionLabels.forEach(label => label.classList.add('hidden'));
            } else {
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.classList.add('sidebar-expanded');
                mainContent.classList.remove('main-collapsed');
                mainContent.classList.add('main-expanded');
                menuTexts.forEach(text => text.classList.remove('hidden'));
                logoText.classList.remove('hidden');
                menuLabel.classList.remove('hidden');
                sectionLabels.forEach(label => label.classList.remove('hidden'));
            }
        }
    }

    sidebarToggle.addEventListener('click', () => {
        if (isMobile) {
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('translate-x-0');
            updateLayout();
        } else {
            sidebarCollapsed = !sidebarCollapsed;
            updateLayout();
        }
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        updateLayout();
    });

    window.addEventListener('resize', () => {
        const wasMobile = isMobile;
        isMobile = window.innerWidth < 768;

        if (wasMobile !== isMobile) {
            if (isMobile) {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                sidebarCollapsed = false;
            } else {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
            }
            updateLayout();
        }
    });

    // Initialize layout
    updateLayout();
</script>
</body>
</html>
