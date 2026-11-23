(function () {
    'use strict';

    const API_URL = './main/api.php';

    const state = {
        participants: [],
        expandedId: null,
        modalName: { mode: 'add', id: null },
        modalScore: { id: null },
        pendingConfirm: null,
        deltaChoice: {}, // { [participantId]: { kind: 'chip'|'custom', value: number|string } }
    };

    const els = {
        list: document.getElementById('participants-list'),
        empty: document.getElementById('participants-empty'),
        addBtn: document.getElementById('btn-add-participant'),
        resetBtn: document.getElementById('btn-reset-all'),

        modalName: document.getElementById('modal-name'),
        inputName: document.getElementById('input-name'),
        errorName: document.getElementById('error-name'),
        saveName: document.getElementById('btn-name-save'),

        modalScore: document.getElementById('modal-score'),
        inputScore: document.getElementById('input-score'),
        errorScore: document.getElementById('error-score'),
        saveScore: document.getElementById('btn-score-save'),

        modalConfirm: document.getElementById('modal-confirm'),
        confirmText: document.getElementById('confirm-text'),
        confirmOk: document.getElementById('btn-confirm-ok'),

        toast: document.getElementById('toast'),
    };

    const CHIPS = [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000];

    function api(action, payload) {
        const body = Object.assign({ action: action }, payload || {});
        return fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        }).then(function (res) {
            return res.json().catch(function () {
                return { ok: false, error: 'Некорректный ответ сервера.' };
            });
        });
    }

    function showToast(message, kind) {
        if (!els.toast) return;
        els.toast.textContent = message;
        els.toast.classList.remove('is-hidden', 'toast--error', 'toast--success');
        els.toast.classList.add(kind === 'error' ? 'toast--error' : 'toast--success');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(function () {
            els.toast.classList.add('is-hidden');
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

    function openModal(el) { if (el) el.classList.remove('is-hidden'); }
    function closeModal(el) { if (el) el.classList.add('is-hidden'); }
    function closeAllModals() {
        closeModal(els.modalName);
        closeModal(els.modalScore);
        closeModal(els.modalConfirm);
    }

    function getChoice(id) {
        return state.deltaChoice[id] || { kind: 'chip', value: null };
    }

    function render() {
        if (!els.list) return;
        if (!state.participants.length) {
            els.empty.classList.remove('is-hidden');
            els.list.innerHTML = '';
            return;
        }
        els.empty.classList.add('is-hidden');

        els.list.innerHTML = state.participants.map(function (p) {
            const expanded = state.expandedId === p.id;
            const choice = getChoice(p.id);
            const chips = CHIPS.map(function (v) {
                const active = choice.kind === 'chip' && choice.value === v;
                return '<button type="button" class="chip' + (active ? ' chip--active' : '') +
                    '" data-action="pick-chip" data-value="' + v + '">' + v + '</button>';
            }).join('');
            const customValue = choice.kind === 'custom' ? escapeHtml(String(choice.value || '')) : '';

            return '' +
                '<li class="participant' + (expanded ? ' participant--expanded' : '') + '" data-id="' + escapeHtml(p.id) + '">' +
                    '<div class="participant-row">' +
                        '<button type="button" class="participant-name" data-action="rename" title="Изменить имя">' + escapeHtml(p.name) + '</button>' +
                        '<button type="button" class="participant-score" data-action="score" title="Изменить очки">' + Number(p.score) + '</button>' +
                        '<div class="participant-controls">' +
                            '<button type="button" class="btn btn-small btn-ghost" data-action="toggle">' + (expanded ? 'Свернуть' : 'Развернуть') + '</button>' +
                            '<button type="button" class="btn btn-small btn-danger" data-action="delete">Удалить</button>' +
                        '</div>' +
                    '</div>' +
                    (expanded ? renderDeltaPanel(p, chips, customValue) : '') +
                '</li>';
        }).join('');
    }

    function renderDeltaPanel(p, chips, customValue) {
        return '' +
            '<div class="delta-panel">' +
                '<div class="delta-section">' +
                    '<div class="delta-label">Быстрый выбор</div>' +
                    '<div class="chips">' + chips + '</div>' +
                '</div>' +
                '<div class="delta-section">' +
                    '<label class="field field--inline">' +
                        '<span class="field-label">Или своё значение</span>' +
                        '<input type="text" inputmode="numeric" class="field-input delta-input" placeholder="целое > 0" autocomplete="off" value="' + customValue + '">' +
                    '</label>' +
                '</div>' +
                '<div class="delta-actions">' +
                    '<button type="button" class="btn btn-primary" data-action="apply-add">Добавить</button>' +
                    '<button type="button" class="btn btn-secondary" data-action="apply-sub">Отнять</button>' +
                '</div>' +
            '</div>';
    }

    function loadParticipants() {
        return api('list').then(function (res) {
            if (!res.ok) {
                showToast(res.error || 'Не удалось загрузить список.', 'error');
                return;
            }
            state.participants = res.data.participants || [];
            render();
        });
    }

    function findParticipant(id) {
        return state.participants.find(function (p) { return p.id === id; });
    }

    function openNameModal(mode, id, initial) {
        state.modalName = { mode: mode, id: id || null };
        els.errorName.classList.add('is-hidden');
        els.inputName.value = initial || '';
        openModal(els.modalName);
        setTimeout(function () { els.inputName.focus(); }, 0);
    }

    function saveName() {
        const name = els.inputName.value.trim();
        if (!name) {
            els.errorName.textContent = 'Имя не может быть пустым.';
            els.errorName.classList.remove('is-hidden');
            return;
        }
        const req = state.modalName.mode === 'add'
            ? api('add', { name: name })
            : api('rename', { id: state.modalName.id, name: name });

        req.then(function (res) {
            if (!res.ok) {
                els.errorName.textContent = res.error || 'Ошибка сохранения.';
                els.errorName.classList.remove('is-hidden');
                return;
            }
            closeModal(els.modalName);
            showToast('Сохранено.', 'success');
            loadParticipants();
        });
    }

    function openScoreModal(id, initial) {
        state.modalScore = { id: id };
        els.errorScore.classList.add('is-hidden');
        els.inputScore.value = String(initial);
        openModal(els.modalScore);
        setTimeout(function () { els.inputScore.focus(); }, 0);
    }

    function saveScore() {
        const raw = els.inputScore.value.trim();
        if (!/^-?\d+$/.test(raw)) {
            els.errorScore.textContent = 'Введите целое число (можно отрицательное).';
            els.errorScore.classList.remove('is-hidden');
            return;
        }
        api('setScore', { id: state.modalScore.id, score: parseInt(raw, 10) }).then(function (res) {
            if (!res.ok) {
                els.errorScore.textContent = res.error || 'Ошибка сохранения.';
                els.errorScore.classList.remove('is-hidden');
                return;
            }
            closeModal(els.modalScore);
            showToast('Очки обновлены.', 'success');
            loadParticipants();
        });
    }

    function askConfirm(text, onOk) {
        state.pendingConfirm = onOk;
        els.confirmText.textContent = text;
        openModal(els.modalConfirm);
    }

    // --- логика начисления/списания ---
    function readAndApplyDelta(li, mode) {
        const id = li.getAttribute('data-id');
        const choice = getChoice(id);
        let delta = null;

        if (choice.kind === 'custom') {
            const raw = String(choice.value || '').trim();
            if (!/^\d+$/.test(raw)) {
                showToast('Введите целое положительное число.', 'error');
                return;
            }
            delta = parseInt(raw, 10);
        } else if (choice.kind === 'chip' && choice.value) {
            delta = parseInt(choice.value, 10);
        }

        if (!delta || delta <= 0) {
            showToast('Выберите значение или введите число больше нуля.', 'error');
            return;
        }

        api('applyDelta', { id: id, delta: delta, mode: mode }).then(function (res) {
            if (!res.ok) {
                showToast(res.error || 'Ошибка.', 'error');
                return;
            }
            // Автосворачивание + сброс выбора
            state.expandedId = null;
            delete state.deltaChoice[id];
            showToast(mode === 'add' ? 'Очки добавлены.' : 'Очки списаны.', 'success');
            loadParticipants();
        });
    }

    // --- делегирование ---
    if (els.list) {
        els.list.addEventListener('click', function (ev) {
            const btn = ev.target.closest('[data-action]');
            if (!btn) return;
            const li = btn.closest('.participant');
            if (!li) return;
            const id = li.getAttribute('data-id');
            const p = findParticipant(id);
            const action = btn.getAttribute('data-action');

            if (action === 'rename') {
                openNameModal('edit', id, p ? p.name : '');
            } else if (action === 'score') {
                openScoreModal(id, p ? p.score : 0);
            } else if (action === 'toggle') {
                state.expandedId = state.expandedId === id ? null : id;
                render();
            } else if (action === 'delete') {
                askConfirm('Удалить участника «' + (p ? p.name : '') + '»?', function () {
                    api('delete', { id: id }).then(function (res) {
                        if (!res.ok) { showToast(res.error || 'Ошибка.', 'error'); return; }
                        delete state.deltaChoice[id];
                        showToast('Удалено.', 'success');
                        loadParticipants();
                    });
                });
            } else if (action === 'pick-chip') {
                const value = parseInt(btn.getAttribute('data-value'), 10);
                const current = getChoice(id);
                if (current.kind === 'chip' && current.value === value) {
                    state.deltaChoice[id] = { kind: 'chip', value: null };
                } else {
                    state.deltaChoice[id] = { kind: 'chip', value: value };
                }
                render();
            } else if (action === 'apply-add') {
                readAndApplyDelta(li, 'add');
            } else if (action === 'apply-sub') {
                readAndApplyDelta(li, 'sub');
            }
        });

        els.list.addEventListener('input', function (ev) {
            const input = ev.target.closest('.delta-input');
            if (!input) return;
            const li = input.closest('.participant');
            if (!li) return;
            const id = li.getAttribute('data-id');
            const value = input.value.replace(/[^\d]/g, '');
            if (input.value !== value) input.value = value;
            if (value === '') {
                state.deltaChoice[id] = { kind: 'chip', value: getChoice(id).value || null };
            } else {
                state.deltaChoice[id] = { kind: 'custom', value: value };
            }
        });
    }

    if (els.addBtn) {
        els.addBtn.addEventListener('click', function () {
            openNameModal('add', null, '');
        });
    }
    if (els.resetBtn) {
        els.resetBtn.addEventListener('click', function () {
            askConfirm('Сбросить всё: удалить всех участников и их очки?', function () {
                api('reset').then(function (res) {
                    if (!res.ok) { showToast(res.error || 'Ошибка.', 'error'); return; }
                    showToast('Все данные сброшены.', 'success');
                    state.expandedId = null;
                    state.deltaChoice = {};
                    loadParticipants();
                });
            });
        });
    }

    if (els.saveName) els.saveName.addEventListener('click', saveName);
    if (els.saveScore) els.saveScore.addEventListener('click', saveScore);
    if (els.inputName) {
        els.inputName.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); saveName(); }
        });
    }
    if (els.inputScore) {
        els.inputScore.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); saveScore(); }
        });
    }

    if (els.confirmOk) {
        els.confirmOk.addEventListener('click', function () {
            const fn = state.pendingConfirm;
            state.pendingConfirm = null;
            closeModal(els.modalConfirm);
            if (typeof fn === 'function') fn();
        });
    }

    document.addEventListener('click', function (ev) {
        const closer = ev.target.closest('[data-modal-close]');
        if (!closer) return;
        const name = closer.getAttribute('data-modal-close');
        if (name === 'name') closeModal(els.modalName);
        if (name === 'score') closeModal(els.modalScore);
        if (name === 'confirm') closeModal(els.modalConfirm);
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') closeAllModals();
    });

    loadParticipants();
})();