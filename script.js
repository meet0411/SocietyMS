document.addEventListener('DOMContentLoaded', function() {
    const welcomeEl = document.getElementById('welcomeUser');
    if (welcomeEl && document.body && document.body.dataset) {
        const user = document.body.dataset.username || '';
        const role = document.body.dataset.role || '';
        welcomeEl.textContent = user ? `Welcome, ${user}${role ? ` (${role})` : ''}` : '';
    }
    
    // Sidebar navigation
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href') || '';
            if (!href.startsWith('#')) {
                return; // normal navigation link
            }

            e.preventDefault();
            const targetId = href.substring(1);
            const target = document.getElementById(targetId);
            if (!target) return;
            
            // Remove active from all sections and links
            document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
            document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
            
            // Add active to target
            target.classList.add('active');
            this.classList.add('active');
        });
    });
    
    // Auto-click first menu item
    const firstLink = document.querySelector('.sidebar a');
    if (firstLink) {
        const href = firstLink.getAttribute('href') || '';
        if (href.startsWith('#')) {
            firstLink.click();
        }
    }
});

// Make logout global for HTML onclick
function logout() {
    window.location.href = 'logout.php';
}
