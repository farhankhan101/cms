document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const appContainer = document.querySelector('.app-container');
    
    // Create overlay for mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                // Mobile toggle
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            } else {
                // Desktop toggle
                appContainer.classList.toggle('sidebar-collapsed');
            }
        });
    }

    // Close sidebar when clicking overlay on mobile
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    });

    // Notification Dropdown Toggle
    const notifBtn = document.getElementById('notif-btn');
    const notifMenu = document.getElementById('notif-menu');

    if (notifBtn && notifMenu) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notifMenu.style.display = notifMenu.style.display === 'none' ? 'block' : 'none';
        });

        document.addEventListener('click', function(e) {
            if (!notifBtn.contains(e.target) && !notifMenu.contains(e.target)) {
                notifMenu.style.display = 'none';
            }
        });
    }
});
