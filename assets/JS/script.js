/* assets/js/script.js */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Dark Mode Logic
    const toggleBtn = document.getElementById('theme-toggle');
    const body = document.body;
    
    // Function to apply theme
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            body.setAttribute('data-theme', 'dark');
            body.classList.add('dark-mode');
            if(toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        } else {
            body.setAttribute('data-theme', 'light');
            body.classList.remove('dark-mode');
            if(toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
        }
    };

    // Check local storage (Matches 'theme' key used in dash)
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    if(toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            localStorage.setItem('theme', newTheme);
            // Also sync to customer/admin keys just in case
            localStorage.setItem('customer-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            
            applyTheme(newTheme);
        });
    }

    // 2. Password Toggle Logic (Eye Icon)
    const toggleIcons = document.querySelectorAll('.toggle-password');

    toggleIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            // Find the input within the same wrapper
            const input = this.closest('.password-wrapper').querySelector('input');
            
            if (input) {
                if (input.type === "password") {
                    input.type = "text";
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    input.type = "password";
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            }
        });
    });
});