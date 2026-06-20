document.addEventListener('DOMContentLoaded', function() {
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar')
        const overlay = document.getElementById('sidebar-overlay')
        sidebar.classList.toggle('-translate-x-full')
        overlay.classList.toggle('hidden')
    }
})