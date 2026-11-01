// resources/js/todo-index.js

function updateStatus(id, currentStatus, btnElement) {
    var next = currentStatus === 'pending' ? 'active' :
        currentStatus === 'active' ? 'completed' :
        'pending';

    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) {
        console.error('CSRF token tidak ditemukan!');
        return;
    }

    var btn = btnElement || document.querySelector('[data-todo-id="' + id + '"]') || (typeof event !== 'undefined' && event && event.target ? event.target.closest('button') : null);

    fetch('/todo/status/' + id, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token.content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: next })
        })
        .then(function(res) {
            if (!res.ok) {
                return res.text().then(function(text) {
                    console.error('Server error:', res.status, text);
                    throw new Error('Server error ' + res.status);
                });
            }
            return res.json();
        })
        .then(function(data) {
            if (data.success) {
                if (btn) {
                    btn.setAttribute('onclick', "updateStatus(" + id + ", '" + next + "', this)");
                    btn.setAttribute('title', next.charAt(0).toUpperCase() + next.slice(1));

                    if (next === 'completed') {
                        btn.className = btn.className.replace(/border-gray-300|border-blue-400|bg-white|bg-blue-400/g, '').trim();
                        btn.classList.add('border-green-500', 'bg-green-500');
                        btn.innerHTML = '<svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>';
                    } else if (next === 'active') {
                        btn.className = btn.className.replace(/border-gray-300|border-green-500|bg-white|bg-green-500/g, '').trim();
                        btn.classList.add('border-blue-400', 'bg-blue-400');
                        btn.innerHTML = '<span class="w-2 h-2 bg-white rounded-full"></span>';
                    } else {
                        btn.className = btn.className.replace(/border-green-500|border-blue-400|bg-green-500|bg-blue-400/g, '').trim();
                        btn.classList.add('border-gray-300', 'bg-white');
                        btn.innerHTML = '';
                    }

                    var row = btn.closest('.todo-item-row') || btn.closest('.flex');
                    if (row) {
                        var titleEl = row.querySelector('.todo-title') || row.querySelector('a') || row.querySelector('p');
                        if (titleEl) {
                            if (next === 'completed') {
                                titleEl.classList.add('line-through', 'text-gray-400');
                            } else {
                                titleEl.classList.remove('line-through', 'text-gray-400');
                            }
                        }
                        var statusBadge = row.querySelector('.todo-status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = next.charAt(0).toUpperCase() + next.slice(1);
                            if (next === 'completed') {
                                statusBadge.className = 'todo-status-badge px-2 py-0.5 rounded-lg text-xs font-medium bg-green-50 text-green-600';
                            } else if (next === 'active') {
                                statusBadge.className = 'todo-status-badge px-2 py-0.5 rounded-lg text-xs font-medium bg-blue-50 text-blue-600';
                            } else {
                                statusBadge.className = 'todo-status-badge px-2 py-0.5 rounded-lg text-xs font-medium bg-yellow-50 text-yellow-600';
                            }
                        }
                    }
                } else {
                    location.reload();
                }
            } else {
                console.error('Gagal update status:', data);
            }
        })
        .catch(function(err) {
            console.error('Error:', err);
            alert('Gagal update status, coba lagi.');
        });
}

function toggleTodoStatus(id, btnElement) {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!token) return;

    var currentStatus = btnElement.getAttribute('data-status') || 'pending';
    var next = currentStatus === 'completed' ? 'pending' : 'completed';

    fetch('/todo/status/' + id, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token.content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: next })
    })
    .then(function(res) {
        if (!res.ok) throw new Error('Error ' + res.status);
        return res.json();
    })
    .then(function(data) {
        if (data.success) {
            btnElement.setAttribute('data-status', next);
            var svg = btnElement.querySelector('svg');
            var row = btnElement.closest('.todo-item-row');
            var title = row ? row.querySelector('.todo-title') : null;

            if (next === 'completed') {
                btnElement.classList.remove('border-gray-300', 'bg-white');
                btnElement.classList.add('border-emerald-500', 'bg-emerald-500', 'text-white');
                if (svg) svg.classList.remove('hidden');
                if (title) title.classList.add('line-through', 'text-gray-400');
            } else {
                btnElement.classList.remove('border-emerald-500', 'bg-emerald-500', 'text-white');
                btnElement.classList.add('border-gray-300', 'bg-white');
                if (svg) svg.classList.add('hidden');
                if (title) title.classList.remove('line-through', 'text-gray-400');
            }
        }
    })
    .catch(function(err) {
        console.error(err);
    });
}

// Modal delete
function openDeleteModal(action, name) {
    document.getElementById('deleteForm').action = action;
    document.getElementById('modal-item-name').textContent = '"' + name + '"';
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Tambah di paling bawah file
window.updateStatus = updateStatus;
window.toggleTodoStatus = toggleTodoStatus;
window.openDeleteModal = openDeleteModal;
window.closeDeleteModal = closeDeleteModal;