window.toggleNotif = function() {
    const dropdown = document.getElementById('notif-dropdown');
    dropdown.classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('notif-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('notif-dropdown').classList.add('hidden');
    }
});