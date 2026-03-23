document.addEventListener('DOMContentLoaded', function() {
    const user = localStorage.getItem('loggedInUser');
    const role = localStorage.getItem('userRole');
    
    if (!user) {
        window.location.href = 'index.html';
        return;
    }
    
    document.getElementById('welcomeUser').textContent = `Welcome, ${user} (${role})`;
    
    // Sidebar navigation
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            
            // Remove active from all sections and links
            document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
            document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
            
            // Add active to target
            document.getElementById(targetId).classList.add('active');
            this.classList.add('active');
        });
    });
    
    // Auto-click first menu item
    if (document.querySelector('.sidebar a')) {
        document.querySelector('.sidebar a').click();
    }
});

// Make logout global for HTML onclick
function logout() {
    localStorage.clear();
    window.location.href = 'index.html';
}
