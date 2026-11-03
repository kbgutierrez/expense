const PAGE_SIZE = 10;

const normalizeText = (value) => (value ? String(value) : '');

// Take: 0 asks the SP for the exact full result set (no artificial cap);
// client-side handles filtering & paging from there — same convention as expense-types.
const paginateRows = (rows, page) => {
	const totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
	const clampedPage = Math.min(Math.max(1, page), totalPages);
	const start = (clampedPage - 1) * PAGE_SIZE;
	return { pageRows: rows.slice(start, start + PAGE_SIZE), totalPages, clampedPage };
};

const renderPaginationNav = (navEl, totalPages, currentPage) => {
	if (!navEl) return;

	// Slide a fixed-size window of page numbers around the current
	// page instead of listing every page.
	const WINDOW_SIZE = 5;
	let windowStart = Math.max(1, currentPage - Math.floor(WINDOW_SIZE / 2));
	let windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);
	windowStart = Math.max(1, windowEnd - WINDOW_SIZE + 1);

	let pageLinks = '';
	if (windowStart > 1) {
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
	}
	for (let page = windowStart; page <= windowEnd; page += 1) {
		pageLinks += `<li class="page-item ${page === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${page}">${page}</a></li>`;
	}
	if (windowEnd < totalPages) {
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
	}

	navEl.innerHTML = `
		<li class="page-item ${currentPage > 1 ? '' : 'disabled'}"><a class="page-link" href="#" data-action="prev">&lsaquo;</a></li>
		${pageLinks}
		<li class="page-item ${currentPage < totalPages ? '' : 'disabled'}"><a class="page-link" href="#" data-action="next">&rsaquo;</a></li>
	`;
};

const bindPaginationNav = (navEl, getPage, setPage, refresh) => {
	if (!navEl) return;
	navEl.addEventListener('click', (event) => {
		const target = event.target.closest('a.page-link');
		if (!target) return;
		event.preventDefault();

		if (target.dataset.page) {
			setPage(Number(target.dataset.page));
		} else if (target.dataset.action === 'prev') {
			setPage(getPage() - 1);
		} else if (target.dataset.action === 'next') {
			setPage(getPage() + 1);
		}
		refresh();
	});
};

const escapeHtml = (value = '') => String(value)
	.replace(/&/g, '&amp;')
	.replace(/</g, '&lt;')
	.replace(/>/g, '&gt;')
	.replace(/"/g, '&quot;')
	.replace(/'/g, '&#39;');

const toIsoDate = (value) => normalizeText(value).slice(0, 10);

const formatPHP = (amount) => Number(amount || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });

let departmentOptions = [];
let userOptions = [];

const buildUserOptionLabel = (u) => `${normalizeText(u.firstname)} ${normalizeText(u.lastname)} - ${normalizeText(u.designation)} (${normalizeText(u.department)})`;

/* ============================================================
   FUND STATUS / TYPE BADGES
   ============================================================ */

const getFundStatusBadge = (statusCode, statusName) => {
	if (statusCode === 'RF_ACTIVE') return '<span class="kna-badge kna-badge-active">Active</span>';
	if (statusCode === 'RF_INACTIVE') return '<span class="kna-badge kna-badge-inactive">Inactive</span>';
	if (statusCode === 'RF_LOCKED') return '<span class="kna-badge kna-badge-locked">Locked</span>';
	return `<span class="kna-badge kna-badge-inactive">${escapeHtml(statusName || statusCode || 'Unknown')}</span>`;
};

const getScopeBadge = (scopeType) => {
	if (scopeType === 'PERSON') return '<span class="kna-badge kna-badge-person">Person</span>';
	if (scopeType === 'DEPARTMENT') return '<span class="kna-badge kna-badge-department">Department</span>';
	if (scopeType === 'COMPANY') return '<span class="kna-badge kna-badge-company">Company</span>';
	return `<span class="kna-badge kna-badge-inactive">${escapeHtml(scopeType || '-')}</span>`;
};

const getTrxTypeBadge = (trxType, trxTypeName) => {
	if (trxType === 'RF_TOPUP') return '<span class="kna-badge kna-badge-topup">Top-up</span>';
	if (trxType === 'RF_ADJUSTMENT') return '<span class="kna-badge kna-badge-adjustment">Adjustment</span>';
	return `<span class="kna-badge kna-badge-inactive">${escapeHtml(trxTypeName || trxType || '-')}</span>`;
};

/* ============================================================
   SHARED LOOKUPS (departments, users — for the "Holder" picker)
   ============================================================ */

const loadLookups = () => {
	ajax_loader('maintenance/revolving-fund/api/get/departments', {}).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res && res.status === 'success') {
			departmentOptions = res.data || [];
		}
	});

	ajax_loader('maintenance/revolving-fund/api/get/users', {}).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res && res.status === 'success') {
			userOptions = res.data || [];
		}
	});
};

/* ============================================================
   REVOLVING FUNDS TAB (fund + holder created/edited together)
   ============================================================ */

const rff = {
	rows: [],
	page: 1,
	dom: {},
};

const rffNormalizeRows = (rows) => (rows || []).map((row) => ({
	id: Number(row.id),
	fundCode: normalizeText(row.fund_code),
	scopeType: normalizeText(row.scope_type),
	scopeId: row.scope_id !== null && row.scope_id !== undefined ? Number(row.scope_id) : null,
	scopeName: normalizeText(row.scope_name),
	openingBalance: Number(row.opening_balance || 0),
	availableBalance: Number(row.available_balance || 0),
	allowNegativeBalance: Boolean(Number(row.allow_negative_balance || 0)),
	allowSelfCashIn: Boolean(Number(row.allow_self_cash_in || 0)),
	statusCode: normalizeText(row.status),
	statusName: normalizeText(row.status_name),
	remarks: normalizeText(row.remarks),
}));

const rffLoad = (reset = false) => {
	if (reset) {
		rff.page = 1;
	}

	const payload = { Take: 0 };
	ajax_loader('maintenance/revolving-fund/api/get/funds', payload).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (!res || res.status !== 'success') return;
		rff.rows = rffNormalizeRows(res.data);
		rffRefresh();
	}).fail(() => {
		Swal.fire({ icon: 'error', title: 'Load Failed', text: 'Could not load revolving funds.' });
	});
};

const rffMatchesFilters = (row) => {
	const keyword = normalizeText(rff.dom.filterKeyword.value).trim().toLowerCase();
	const status = normalizeText(rff.dom.filterStatus.value).trim();

	if (status && row.statusCode !== status) return false;
	if (keyword) {
		const haystack = `${row.fundCode} ${row.scopeName}`.toLowerCase();
		if (haystack.indexOf(keyword) === -1) return false;
	}
	return true;
};

const rffGetFilteredRows = () => rff.rows.filter(rffMatchesFilters);

const rffRenderSummary = (rows) => {
	const total = rows.length;
	const active = rows.filter((r) => r.statusCode === 'RF_ACTIVE').length;
	const inactive = total - active;
	const totalBalance = rows.filter((r) => r.statusCode === 'RF_ACTIVE').reduce((sum, r) => sum + r.availableBalance, 0);

	rff.dom.sumTotal.textContent = String(total);
	rff.dom.sumActive.textContent = String(active);
	rff.dom.sumInactive.textContent = String(inactive);
	rff.dom.sumBalance.textContent = formatPHP(totalBalance);
};

const rffRenderDesktopTable = (rows) => {
	rff.dom.tbody.innerHTML = '';
	if (!rows.length) {
		rff.dom.tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No records found</td></tr>';
		return;
	}
	rows.forEach((row) => {
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td class="font-weight-bold">${escapeHtml(row.fundCode)}</td>
			<td>${getScopeBadge(row.scopeType)} ${escapeHtml(row.scopeName)}</td>
			<td class="text-right">${formatPHP(row.availableBalance)}</td>
			<td>${getFundStatusBadge(row.statusCode, row.statusName)}</td>
			<td class="text-center kna-actions">
				<button type="button" class="btn btn-sm btn-outline-success" data-action="adjust-fund" data-id="${row.id}">Adjust Fund</button>
				<button type="button" class="btn btn-sm btn-outline-info" data-action="history" data-id="${row.id}">History</button>
				<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${row.id}">Edit</button>
			</td>
		`;
		rff.dom.tbody.appendChild(tr);
	});
};

const rffRenderMobileCards = (rows) => {
	rff.dom.mobileList.innerHTML = '';
	if (!rows.length) {
		rff.dom.mobileList.innerHTML = '<div class="kna-small text-center text-muted py-2">No records found</div>';
		return;
	}
	rows.forEach((row) => {
		const item = document.createElement('div');
		item.className = 'kna-item';
		item.innerHTML = `
			<div class="kna-row">
				<div class="kna-small font-weight-bold">${escapeHtml(row.fundCode)}</div>
				<div>${getFundStatusBadge(row.statusCode, row.statusName)}</div>
			</div>
			<div class="kna-small font-weight-bold">${getScopeBadge(row.scopeType)} ${escapeHtml(row.scopeName)}</div>
			<div class="kna-row"><div class="kna-small text-muted">Available Balance</div><div class="kna-small">${formatPHP(row.availableBalance)}</div></div>
			<div class="d-flex mt-2" style="gap:6px;">
				<button type="button" class="btn btn-outline-success btn-sm kna-small w-100" data-action="adjust-fund" data-id="${row.id}">Adjust Fund</button>
				<button type="button" class="btn btn-outline-info btn-sm kna-small w-100" data-action="history" data-id="${row.id}">History</button>
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small w-100" data-action="edit" data-id="${row.id}">Edit</button>
			</div>
		`;
		rff.dom.mobileList.appendChild(item);
	});
};

const rffRefresh = () => {
	const rows = rffGetFilteredRows();
	const { pageRows, totalPages, clampedPage } = paginateRows(rows, rff.page);
	rff.page = clampedPage;

	rffRenderSummary(rows);
	rffRenderDesktopTable(pageRows);
	rffRenderMobileCards(rows);
	renderPaginationNav(rff.dom.pagination, totalPages, clampedPage);
	rff.dom.resultCount.textContent = `${rows.length} record(s)`;
	rff.dom.resultCountMobile.textContent = `${rows.length} record(s)`;
};

const rffFindById = (id) => rff.rows.find((row) => Number(row.id) === Number(id));

const rffToggleScopeInput = (scopeType, presetValue) => {
	const isFreeText = scopeType === 'COMPANY';
	rff.dom.scopeText.classList.toggle('d-none', !isFreeText);

	if (isFreeText) {
		if ($.fn.select2 && $(rff.dom.scopeSelect).data('select2')) {
			$(rff.dom.scopeSelect).select2('destroy');
		}
		rff.dom.scopeSelect.classList.add('d-none');
		rff.dom.scopeText.value = presetValue || '';
		return;
	}

	rff.dom.scopeText.classList.add('d-none');
	rff.dom.scopeSelect.classList.remove('d-none');

	const options = scopeType === 'DEPARTMENT'
		? departmentOptions.map((d) => ({ id: d.department_id, text: normalizeText(d.department) }))
		: userOptions.map((u) => ({ id: u.id, text: buildUserOptionLabel(u) }));

	rff.dom.scopeSelect.innerHTML = '<option value=""></option>' + options.map((o) => `<option value="${escapeHtml(String(o.id))}">${escapeHtml(o.text)}</option>`).join('');

	if ($.fn.select2) {
		if ($(rff.dom.scopeSelect).data('select2')) {
			$(rff.dom.scopeSelect).select2('destroy');
		}
		$(rff.dom.scopeSelect).select2({ width: '100%', placeholder: 'Select ' + scopeType.toLowerCase(), dropdownParent: $('#modalRfFund') });
	}

	if (presetValue !== undefined && presetValue !== null) {
		$(rff.dom.scopeSelect).val(String(presetValue)).trigger('change');
	}
};

const rffSetAdvancedVisible = (visible) => {
	rff.dom.advancedSection.style.display = visible ? '' : 'none';
	rff.dom.toggleAdvanced.style.display = visible ? 'none' : '';
};

const rffOpenCreateModal = () => {
	rff.dom.mode.value = 'create';
	rff.dom.id.value = '';
	rff.dom.scopeType.value = 'PERSON';
	rff.dom.opening.value = '';
	rff.dom.allowNegative.checked = false;
	rff.dom.allowSelfCashIn.checked = false;
	rff.dom.status.value = 'RF_ACTIVE';
	rff.dom.remarks.value = '';
	rff.dom.opening.disabled = false;
	document.getElementById('modalRfFundTitleText').textContent = 'Add Fund';
	rffToggleScopeInput('PERSON', null);
	rffSetAdvancedVisible(false);
	$('#modalRfFund').modal('show');
};

const rffOpenEditModal = (id) => {
	const row = rffFindById(id);
	if (!row) return;

	rff.dom.mode.value = 'edit';
	rff.dom.id.value = String(row.id);
	rff.dom.scopeType.value = row.scopeType;
	rff.dom.opening.value = row.openingBalance;
	rff.dom.opening.disabled = true;
	rff.dom.allowNegative.checked = row.allowNegativeBalance;
	rff.dom.allowSelfCashIn.checked = row.allowSelfCashIn;
	rff.dom.status.value = row.statusCode;
	rff.dom.remarks.value = row.remarks;
	document.getElementById('modalRfFundTitleText').textContent = `Edit Fund — ${row.fundCode}`;

	rffToggleScopeInput(row.scopeType, row.scopeType === 'COMPANY' ? row.scopeName : row.scopeId);
	if (row.scopeType === 'COMPANY') {
		rff.dom.scopeText.value = row.scopeName;
	}
	rffSetAdvancedVisible(true);

	$('#modalRfFund').modal('show');
};

const rffSave = () => {
	const mode = normalizeText(rff.dom.mode.value);
	const scopeType = normalizeText(rff.dom.scopeType.value);
	const isFreeText = scopeType === 'COMPANY';

	let scopeId = null;
	let scopeName = '';

	if (isFreeText) {
		scopeName = normalizeText(rff.dom.scopeText.value).trim();
	} else {
		const selected = $(rff.dom.scopeSelect).select2('data')[0];
		scopeId = rff.dom.scopeSelect.value ? Number(rff.dom.scopeSelect.value) : null;
		scopeName = selected ? selected.text : '';
	}

	if (!scopeName) {
		Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Please select or enter who this fund is for.' });
		return;
	}

	const openingBalance = Number(rff.dom.opening.value || 0);
	if (mode !== 'edit' && openingBalance < 0) {
		Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Opening balance cannot be negative.' });
		return;
	}

	const payload = {
		ScopeType: scopeType,
		ScopeId: scopeId,
		ScopeName: scopeName,
		AllowNegativeBalance: rff.dom.allowNegative.checked ? 1 : 0,
		AllowSelfCashIn: rff.dom.allowSelfCashIn.checked ? 1 : 0,
		Status: rff.dom.status.value,
		Remarks: normalizeText(rff.dom.remarks.value).trim(),
	};

	if (mode === 'edit') {
		payload.Id = Number(rff.dom.id.value || 0);
	} else {
		payload.OpeningBalance = openingBalance;
	}

	const endpoint = mode === 'edit' ? 'maintenance/revolving-fund/api/update/fund' : 'maintenance/revolving-fund/api/save/fund';

	Swal.fire({
		icon: 'question',
		title: mode === 'edit' ? 'Confirm Update' : 'Confirm Save',
		text: mode === 'edit' ? 'Save changes to this fund?' : 'Create this revolving fund?',
		showCancelButton: true,
		confirmButtonText: 'Yes',
		cancelButtonText: 'No',
		reverseButtons: true,
	}).then((result) => {
		if (!result.isConfirmed) return;

		ajax_loader(endpoint, payload).done((response) => {
			const res = (typeof response === 'string') ? $.parseJSON(response) : response;
			if (res && res.status === 'success') {
				$('#modalRfFund').modal('hide');
				Swal.fire({ icon: 'success', title: 'Saved', text: res.response || 'Fund saved successfully.' });
				rffLoad(true);
				return;
			}
			Swal.fire({ icon: 'error', title: 'Failed', text: (res && res.response) ? res.response : 'Failed to save fund.' });
		}).fail(() => {
			Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Could not connect to the server.' });
		});
	});
};

/* ============================================================
   ADD MONEY MODAL (per fund row — no fund picker needed)
   ============================================================ */

const rfLedger = { dom: {}, currentFund: null };

const rfLedgerOpen = (id) => {
	const row = rffFindById(id);
	if (!row) return;

	rfLedger.currentFund = row;
	rfLedger.dom.fundId.value = String(row.id);
	rfLedger.dom.fundLabel.textContent = `${row.fundCode} — ${row.scopeName}`;
	rfLedger.dom.balanceHint.textContent = `Available balance: ${formatPHP(row.availableBalance)}`;
	rfLedger.dom.type.value = 'RF_TOPUP';
	rfLedger.dom.amount.value = '';
	rfLedger.dom.remarks.value = '';
	setFlatpickrDate(rfLedger.dom.date, todayIso());

	$('#modalRfLedger').modal('show');
};

const rfLedgerSave = () => {
	const fundId = Number(rfLedger.dom.fundId.value || 0);
	const trxDate = normalizeText(rfLedger.dom.date.value).trim();
	const trxType = rfLedger.dom.type.value;
	const amount = Number(rfLedger.dom.amount.value || 0);
	const remarks = normalizeText(rfLedger.dom.remarks.value).trim();

	if (!fundId) return;
	if (!trxDate) {
		Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Please provide a transaction date.' });
		return;
	}
	if (!amount) {
		Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Amount cannot be zero.' });
		return;
	}
	if (trxType === 'RF_TOPUP' && amount < 0) {
		Swal.fire({ icon: 'warning', title: 'Invalid Amount', text: 'Top-up amount must be positive.' });
		return;
	}
	if (!remarks) {
		Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Please provide remarks.' });
		return;
	}

	const payload = { FundId: fundId, TrxDate: trxDate, TrxType: trxType, Amount: amount, Remarks: remarks };

	Swal.fire({
		icon: 'question',
		title: 'Confirm Entry',
		text: 'Post this entry?',
		showCancelButton: true,
		confirmButtonText: 'Yes',
		cancelButtonText: 'No',
		reverseButtons: true,
	}).then((result) => {
		if (!result.isConfirmed) return;

		ajax_loader('maintenance/revolving-fund/api/save/ledger', payload).done((response) => {
			const res = (typeof response === 'string') ? $.parseJSON(response) : response;
			if (res && res.status === 'success') {
				$('#modalRfLedger').modal('hide');
				Swal.fire({ icon: 'success', title: 'Posted', text: res.response || 'Entry posted successfully.' });
				rffLoad(false);
				return;
			}
			Swal.fire({ icon: 'error', title: 'Failed', text: (res && res.response) ? res.response : 'Failed to post entry.' });
		}).fail(() => {
			Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Could not connect to the server.' });
		});
	});
};

/* ============================================================
   HISTORY MODAL (per fund row — read only)
   ============================================================ */

const rfHistory = { dom: {} };

const rfHistoryNormalizeRows = (rows) => (rows || []).map((row) => ({
	trxDate: toIsoDate(row.trx_date),
	trxType: normalizeText(row.trx_type),
	trxTypeName: normalizeText(row.trx_type_name),
	amount: Number(row.amount || 0),
	balanceAfter: Number(row.balance_after || 0),
	remarks: normalizeText(row.remarks),
	createdBy: normalizeText(row.created_by_name),
}));

const rfHistoryOpen = (id) => {
	const row = rffFindById(id);
	if (!row) return;

	document.getElementById('modalRfHistoryTitleText').textContent = `Fund History — ${row.fundCode}`;
	rfHistory.dom.list.innerHTML = '<div class="kna-small text-center text-muted py-2">Loading...</div>';
	$('#modalRfHistory').modal('show');

	ajax_loader('maintenance/revolving-fund/api/get/ledger', { FundId: row.id, Take: 0 }).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (!res || res.status !== 'success') {
			rfHistory.dom.list.innerHTML = '<div class="kna-small text-center text-muted py-2">Could not load history.</div>';
			return;
		}

		const rows = rfHistoryNormalizeRows(res.data);
		if (!rows.length) {
			rfHistory.dom.list.innerHTML = '<div class="kna-small text-center text-muted py-2">No entries yet.</div>';
			return;
		}

		rfHistory.dom.list.innerHTML = rows.map((r) => {
			const amountClass = r.amount < 0 ? 'text-amount-neg' : 'text-amount-pos';
			return `
				<div class="kna-item">
					<div class="kna-row">
						<div class="kna-small font-weight-bold">${escapeHtml(r.trxDate)}</div>
						<div>${getTrxTypeBadge(r.trxType, r.trxTypeName)}</div>
					</div>
					<div class="kna-row"><div class="kna-small text-muted">Amount</div><div class="kna-small ${amountClass}">${formatPHP(r.amount)}</div></div>
					<div class="kna-row"><div class="kna-small text-muted">Balance After</div><div class="kna-small">${formatPHP(r.balanceAfter)}</div></div>
					<div class="kna-small text-muted mt-1">${escapeHtml(r.remarks || '-')} ${r.createdBy ? '— ' + escapeHtml(r.createdBy) : ''}</div>
				</div>
			`;
		}).join('');
	}).fail(() => {
		rfHistory.dom.list.innerHTML = '<div class="kna-small text-center text-muted py-2">Could not load history.</div>';
	});
};

/* ============================================================
   SHARED DATE HELPERS
   ============================================================ */

const dateToIso = (date) => {
	const y = date.getFullYear();
	const m = `${date.getMonth() + 1}`.padStart(2, '0');
	const d = `${date.getDate()}`.padStart(2, '0');
	return `${y}-${m}-${d}`;
};

const todayIso = () => dateToIso(new Date());

const setFlatpickrDate = (input, isoValue) => {
	if (!input) return;
	if (input._flatpickr) {
		input._flatpickr.setDate(isoValue || null, false);
	} else {
		input.value = isoValue || '';
	}
};

const initFlatpickrInputs = () => {
	if (typeof flatpickr === 'undefined') return;
	flatpickr(rfLedger.dom.date, { dateFormat: 'Y-m-d', allowInput: true });
};

/* ============================================================
   DOM CACHING + EVENTS
   ============================================================ */

const cacheDom = () => {
	rff.dom = {
		filterKeyword: document.getElementById('rfFilterKeyword'),
		filterStatus: document.getElementById('rfFilterStatus'),
		btnReset: document.getElementById('rfBtnReset'),
		sumTotal: document.getElementById('rfSumTotal'),
		sumActive: document.getElementById('rfSumActive'),
		sumInactive: document.getElementById('rfSumInactive'),
		sumBalance: document.getElementById('rfSumBalance'),
		tbody: document.getElementById('rfFundTbody'),
		mobileList: document.getElementById('rfFundMobileList'),
		resultCount: document.getElementById('rfResultCount'),
		resultCountMobile: document.getElementById('rfResultCountMobile'),
		table: document.getElementById('rfFundTable'),
		pagination: document.getElementById('rfFundPagination'),
		btnOpenNew: document.getElementById('btnOpenNewFund'),
		btnSave: document.getElementById('btnSaveRfFund'),
		mode: document.getElementById('rfFundMode'),
		id: document.getElementById('rfFundId'),
		scopeType: document.getElementById('rfFundScopeType'),
		scopeSelect: document.getElementById('rfFundScopeSelect'),
		scopeText: document.getElementById('rfFundScopeText'),
		opening: document.getElementById('rfFundOpening'),
		allowNegative: document.getElementById('rfFundAllowNegative'),
		allowSelfCashIn: document.getElementById('rfFundAllowSelfCashIn'),
		status: document.getElementById('rfFundStatus'),
		remarks: document.getElementById('rfFundRemarks'),
		advancedSection: document.getElementById('rfFundAdvancedSection'),
		toggleAdvanced: document.getElementById('rfFundToggleAdvanced'),
	};

	rfLedger.dom = {
		fundId: document.getElementById('rfLedgerFundId'),
		fundLabel: document.getElementById('rfLedgerFundLabel'),
		balanceHint: document.getElementById('rfLedgerBalanceHint'),
		date: document.getElementById('rfLedgerDate'),
		type: document.getElementById('rfLedgerType'),
		amount: document.getElementById('rfLedgerAmount'),
		remarks: document.getElementById('rfLedgerRemarks'),
		btnSave: document.getElementById('btnSaveRfLedger'),
	};

	rfHistory.dom = {
		list: document.getElementById('rfHistoryList'),
	};
};

const resetPageAnd = (tabState, fn) => () => {
	tabState.page = 1;
	fn();
};

const bindEvents = () => {
	// Revolving Funds tab
	rff.dom.filterKeyword.addEventListener('input', resetPageAnd(rff, rffRefresh));
	rff.dom.filterStatus.addEventListener('change', resetPageAnd(rff, rffRefresh));
	rff.dom.btnReset.addEventListener('click', () => {
		rff.dom.filterKeyword.value = '';
		rff.dom.filterStatus.value = '';
		rff.page = 1;
		rffRefresh();
	});
	rff.dom.btnOpenNew.addEventListener('click', rffOpenCreateModal);
	rff.dom.btnSave.addEventListener('click', rffSave);
	rff.dom.scopeType.addEventListener('change', () => rffToggleScopeInput(rff.dom.scopeType.value, null));
	rff.dom.toggleAdvanced.addEventListener('click', (event) => {
		event.preventDefault();
		rffSetAdvancedVisible(true);
	});
	bindPaginationNav(rff.dom.pagination, () => rff.page, (p) => { rff.page = p; }, rffRefresh);

	[rff.dom.table, rff.dom.mobileList].forEach((el) => {
		el.addEventListener('click', (event) => {
			const btn = event.target.closest('button[data-action]');
			if (!btn) return;
			const id = Number(btn.getAttribute('data-id'));
			const action = btn.getAttribute('data-action');
			if (action === 'edit') rffOpenEditModal(id);
			if (action === 'adjust-fund') rfLedgerOpen(id);
			if (action === 'history') rfHistoryOpen(id);
		});
	});

	// Adjust Fund modal
	rfLedger.dom.btnSave.addEventListener('click', rfLedgerSave);
};

const init = () => {
	cacheDom();
	bindEvents();
	initFlatpickrInputs();
	loadLookups();
	rffLoad(true);
};

$(document).ready(() => {
	init();
});
