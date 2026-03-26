/* ═══════════════════════════════════════════════
   WPTrustRocket – Admin JS
   ═══════════════════════════════════════════════ */

(function () {
	'use strict';

	const api = (endpoint, method = 'GET', body = null) => {
		const opts = {
			method,
			headers: {
				'X-WP-Nonce': wptrAdmin.nonce,
				'Content-Type': 'application/json',
			},
		};
		if (body) opts.body = JSON.stringify(body);
		return fetch(wptrAdmin.restUrl + endpoint, opts).then(r => r.json());
	};

	/* ─── Helpers ─── */

	function setStatus(el, text, type) {
		el.textContent = '';
		var span = document.createElement('span');
		span.textContent = text;
		if (type === 'success') span.style.color = 'var(--wptr-success)';
		if (type === 'error') span.style.color = 'var(--wptr-danger)';
		el.appendChild(span);
	}

	function showSpinner(el) {
		el.textContent = '';
		var sp = document.createElement('span');
		sp.className = 'wptr-spinner';
		el.appendChild(sp);
	}

	/* ─── Toast ─── */

	function toast(msg, type) {
		type = type || 'success';
		var el = document.createElement('div');
		el.className = 'wptr-toast wptr-toast--' + type;
		el.textContent = msg;
		document.body.appendChild(el);
		setTimeout(function () { el.remove(); }, 3000);
	}

	/* ─── Sync Button ─── */

	document.querySelectorAll('#wptr-sync-btn').forEach(function (btn) {
		btn.addEventListener('click', async function () {
			btn.disabled = true;
			var origText = btn.textContent;
			btn.textContent = wptrAdmin.i18n.syncing;

			try {
				var res = await api('sync', 'POST');
				if (res.success) {
					toast(wptrAdmin.i18n.syncDone + ' (' + (res.data && res.data.synced || 0) + ' Reviews)');
					setTimeout(function () { location.reload(); }, 1200);
				} else {
					toast(res.message || wptrAdmin.i18n.syncError, 'error');
				}
			} catch (e) {
				toast(wptrAdmin.i18n.syncError, 'error');
			}

			btn.disabled = false;
			btn.textContent = origText;
		});
	});

	/* ─── Test Connection ─── */

	var testBtn = document.getElementById('wptr-test-connection');
	var testStatus = document.getElementById('wptr-connection-status');

	if (testBtn) {
		testBtn.addEventListener('click', async function () {
			testBtn.disabled = true;
			showSpinner(testStatus);

			var body = {};
			var idField = document.getElementById('wptr_client_id');
			var secretField = document.getElementById('wptr_client_secret');
			var tsidField = document.getElementById('wptr_tsid');
			if (idField) body.client_id = idField.value;
			if (secretField) body.client_secret = secretField.value;
			if (tsidField) body.tsid = tsidField.value;

			try {
				var res = await api('test-connection', 'POST', body);
				if (res.success) {
					setStatus(testStatus, wptrAdmin.i18n.testOk, 'success');
				} else {
					setStatus(testStatus, res.message || wptrAdmin.i18n.testFail, 'error');
				}
			} catch (e) {
				setStatus(testStatus, wptrAdmin.i18n.testFail, 'error');
			}

			testBtn.disabled = false;
		});
	}

	/* ─── Create Group ─── */

	var createBtn = document.getElementById('wptr-create-group-btn');
	var nameInput = document.getElementById('wptr-new-group-name');

	if (createBtn && nameInput) {
		var doCreate = async function () {
			var name = nameInput.value.trim();
			if (!name) return;

			createBtn.disabled = true;

			try {
				var res = await api('groups', 'POST', { name: name });
				if (res.success) {
					toast(wptrAdmin.i18n.saved);
					location.reload();
				} else {
					toast(res.message || wptrAdmin.i18n.error, 'error');
				}
			} catch (e) {
				toast(wptrAdmin.i18n.error, 'error');
			}

			createBtn.disabled = false;
		};

		createBtn.addEventListener('click', doCreate);
		nameInput.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				doCreate();
			}
		});
	}

	/* ─── Delete Group ─── */

	document.querySelectorAll('.wptr-group-delete').forEach(function (btn) {
		btn.addEventListener('click', async function (e) {
			e.preventDefault();
			if (!confirm(wptrAdmin.i18n.confirmDelete)) return;

			var id = btn.dataset.id;
			btn.disabled = true;

			try {
				await api('groups/' + id, 'DELETE');
				toast(wptrAdmin.i18n.saved);
				location.reload();
			} catch (e) {
				toast(wptrAdmin.i18n.error, 'error');
			}
		});
	});

	/* ─── Group Editor: Search ─── */

	var groupSearch = document.getElementById('wptr-group-search');
	if (groupSearch) {
		groupSearch.addEventListener('input', function () {
			var q = groupSearch.value.toLowerCase();
			document.querySelectorAll('.wptr-sel-review').forEach(function (el) {
				var text = el.dataset.search || '';
				el.style.display = text.indexOf(q) !== -1 ? '' : 'none';
			});
		});
	}

	/* ─── Group Editor: Select All / None ─── */

	var selectAll = document.getElementById('wptr-select-all');
	var selectNone = document.getElementById('wptr-select-none');
	var countEl = document.getElementById('wptr-selected-count');

	function updateCount() {
		if (!countEl) return;
		var checked = document.querySelectorAll('.wptr-review-checkbox:checked').length;
		countEl.textContent = checked;
	}

	function updateCardState(checkbox) {
		var card = checkbox.closest('.wptr-sel-review');
		if (!card) return;
		card.classList.toggle('wptr-sel-review--selected', checkbox.checked);
	}

	if (selectAll) {
		selectAll.addEventListener('click', function () {
			document.querySelectorAll('.wptr-sel-review:not([style*="display: none"]) .wptr-review-checkbox').forEach(function (cb) {
				cb.checked = true;
				updateCardState(cb);
			});
			updateCount();
		});
	}

	if (selectNone) {
		selectNone.addEventListener('click', function () {
			document.querySelectorAll('.wptr-review-checkbox').forEach(function (cb) {
				cb.checked = false;
				updateCardState(cb);
			});
			updateCount();
		});
	}

	document.querySelectorAll('.wptr-review-checkbox').forEach(function (cb) {
		cb.addEventListener('change', function () {
			updateCardState(cb);
			updateCount();
		});
	});

	/* ─── Group Editor: Save ─── */

	var saveBtn = document.getElementById('wptr-save-group');
	if (saveBtn) {
		saveBtn.addEventListener('click', async function () {
			var groupId = saveBtn.dataset.groupId;
			var ids = [];

			document.querySelectorAll('.wptr-review-checkbox:checked').forEach(function (cb) {
				ids.push(parseInt(cb.value, 10));
			});

			saveBtn.disabled = true;
			saveBtn.textContent = wptrAdmin.i18n.syncing;

			try {
				var res = await api('groups/' + groupId + '/reviews', 'PUT', { review_ids: ids });
				if (res.success) {
					toast(wptrAdmin.i18n.saved + ' (' + res.count + ')');
				} else {
					toast(wptrAdmin.i18n.error, 'error');
				}
			} catch (e) {
				toast(wptrAdmin.i18n.error, 'error');
			}

			saveBtn.disabled = false;
			saveBtn.textContent = 'Speichern';
		});
	}

})();
