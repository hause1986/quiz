(function () {
    'use strict';

    const API_URL = '../main/api.php';
    const REFRESH_MS = 2500;

    const listEl = document.getElementById('total-list');
    const emptyEl = document.getElementById('total-empty');
    const toastEl = document.getElementById('toast');

    let lastSignature = '';

    function showToast(message) {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.classList.remove('is-hidden', 'toast--error');
        toastEl.classList.add('toast--success');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(function () {
            toastEl.classList.add('is-hidden');
        }, 2500);
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function fetchList() {
        return fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'list' }),
        })
            .then(function (r) { return r.json(); })
            .catch(function () { return { ok: false }; });
    }

    function render(participants) {
        const signature = JSON.stringify(participants.map(function (p) {
            return [p.id, p.name, p.score];
        }));
        if (signature === lastSignature) return;
        lastSignature = signature;

        if (!participants.length) {
            emptyEl.classList.remove('is-hidden');
            listEl.innerHTML = '';
            return;
        }
        emptyEl.classList.add('is-hidden');

        listEl.innerHTML = participants.map(function (p, idx) {
            return '' +
                '<li class="participant participant--total">' +
                    '<span class="participant-place">' + (idx + 1) + '</span>' +
                    '<span class="participant-name participant-name--static">' + escapeHtml(p.name) + '</span>' +
                    '<span class="participant-score participant-score--static">' + Number(p.score) + '</span>' +
                '</li>';
        }).join('');
    }

    function tick() {
        fetchList().then(function (res) {
            if (!res || !res.ok) {
                return;
            }
            const participants = (res.data && res.data.participants) || [];
            render(participants);
        });
    }

    tick();
    setInterval(tick, REFRESH_MS);
})();