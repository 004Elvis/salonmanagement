/* assets/js/script.js */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Dark Mode Logic
    const toggleBtn = document.getElementById('theme-toggle');
    const body = document.body;
    
    // Check local storage for theme preference
    if(localStorage.getItem('theme') === 'dark') {
        body.setAttribute('data-theme', 'dark');
        if(toggleBtn) toggleBtn.textContent = '☀️ Light Mode';
    }

    if(toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            if (body.getAttribute('data-theme') === 'dark') {
                body.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                toggleBtn.textContent = '🌙 Dark Mode';
            } else {
                body.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                toggleBtn.textContent = '☀️ Light Mode';
            }
        });
    }

    // 2. Password Toggle Logic (Eye Icon)
    const toggleIcons = document.querySelectorAll('.toggle-password');

    toggleIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            // Get the input field. 
            // We assume HTML is: <input> followed immediately by <i class="toggle-password">
            const input = this.previousElementSibling;
            
            if (input && input.tagName === 'INPUT') {
                if (input.type === "password") {
                    input.type = "text";
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash'); // Switch to "crossed out eye"
                } else {
                    input.type = "password";
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye'); // Switch back to "normal eye"
                }
            } else {
                console.error("Password toggle error: Input field not found before icon.");
            }
        });
    });
});