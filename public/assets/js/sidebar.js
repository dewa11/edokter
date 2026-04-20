(function () {
    var wrapper = document.getElementById('dashboardWrapper');
    var toggleButton = document.getElementById('sidebarToggle');
    var storageKey = 'edokter.sidebar.collapsed';

    if (!wrapper || !toggleButton) {
        return;
    }

    var collapsed = localStorage.getItem(storageKey) === '1';
    if (collapsed) {
        wrapper.classList.add('sidebar-collapsed');
    }

    toggleButton.addEventListener('click', function () {
        wrapper.classList.toggle('sidebar-collapsed');
        localStorage.setItem(storageKey, wrapper.classList.contains('sidebar-collapsed') ? '1' : '0');
    });
})();
