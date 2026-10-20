let cashAdvances = [];
let nextCursorId = null;
let hasMoreRows = false;
let isLoadingRows = false;
let desktopPage = 1;
const PAGE_SIZE = 10;

const dom = {
	filterKeyword: null,
	filterDateRange: null,
	filterDateRangePicker: null,
	filterStatus: null,
	filterAmountRange: null,
	sumTotalGranted: null,
	sumPending: null,
	sumForLiquidation: null,
	sumApproved: null,
	cashAdvanceTbody: null,
	cashAdvanceMobileList: null,
	resultCount: null,
	resultCountMobile: null,
	btnReset: null,
	btnOpenNew: null,
	desktopPagination: null,
	btnLoadMoreMobile: null,
	cashAdvanceTable: null,
};

const formatPHP = (amount) => {
	const value = Number(amount || 0);
	return value.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
};

const formatApprovedPHP = (amount) => (amount === null || amount === undefined ? '—' : formatPHP(amount));

const normalizeDate = (value) => (value ? String(value) : '');

const escapeHtml = (value = '') =>
	String(value)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');

const getStatusBadge = (status) => {
	if (status === 'Pending Approval') {
		return '<span class="kna-badge kna-badge-pending">Pending Approval</span>';
	}
	if (status === 'For Liquidation') {
		return '<span class="kna-badge kna-badge-liquidation">For Liquidation</span>';
	}
	if (status === 'Approved') {
		return '<span class="kna-badge kna-badge-approved">Approved</span>';
	}
	if (status === 'Rejected') {
		return '<span class="kna-badge kna-badge-rejected">Rejected</span>';
	}
	if (status === 'Completed') {
		return '<span class="kna-badge kna-badge-completed">Completed</span>';
	}
	if (status === 'Cancelled') {
		return '<span class="kna-badge kna-badge-rejected">Cancelled</span>';
	}
	return `<span class="kna-badge kna-badge-pending">${escapeHtml(status || 'Pending')}</span>`;
};

const normalizeApiRows = (rows) =>
	(rows || []).map((row) => ({
		id: Number(row.id),
		refNo: row.cash_advance_id,
		amount: Number(row.amount || 0),
		approvedAmount: row.approved_amount !== null && row.approved_amount !== undefined ? Number(row.approved_amount) : null,
		requestedDate: String(row.created_date || '').slice(0, 10),
		neededDate: row.needed_date,
		status: row.status_name,
		purpose: row.description,
	}));

const updateLoadMoreButtons = () => {
	const show = hasMoreRows && !isLoadingRows;
	if (dom.btnLoadMoreMobile) {
		dom.btnLoadMoreMobile.style.display = show ? 'inline-block' : 'none';
		dom.btnLoadMoreMobile.disabled = isLoadingRows;
	}
};

const loadCashAdvances = (reset = false) => {
	if (isLoadingRows) {
		return null;
	}

	if (reset) {
		desktopPage = 1;
		nextCursorId = null;
		hasMoreRows = false;
		cashAdvances = [];
		refreshUI();
	}

	isLoadingRows = true;
	updateLoadMoreButtons();

	// Take: 0 asks the SP for the exact full result set (no artificial cap);
	// client-side handles filtering & paging from there.
	const postData = {
		Take: reset ? 0 : PAGE_SIZE,
	};

	if (nextCursorId !== null) {
		postData.CursorId = nextCursorId;
	}

	const request = ajax_loader('transactions/cash-advance/api/get', postData);

	request.done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res.status !== 'success') {
			return;
		}

		const rows = normalizeApiRows(res.data);
		cashAdvances = reset ? rows : cashAdvances.concat(rows);

		const pagination = res.pagination || {};
		hasMoreRows = Boolean(pagination.hasMore);
		nextCursorId = (pagination.nextCursorId !== undefined && pagination.nextCursorId !== null)
			? Number(pagination.nextCursorId)
			: (rows.length ? rows[rows.length - 1].id : nextCursorId);

		refreshUI();
	}).fail(() => {
		if (reset) {
			cashAdvances = [];
			refreshUI();
		}
		Swal.fire({
			icon: 'error',
			title: 'Load Failed',
			text: 'Could not load cash advance records.',
		});
	}).always(() => {
		isLoadingRows = false;
		updateLoadMoreButtons();
	});

	return request;
};

const renderDesktopPagination = (rows) => {
	if (!dom.desktopPagination) {
		return;
	}

	const totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
	if (desktopPage > totalPages) {
		desktopPage = totalPages;
	}

	const canPrev = desktopPage > 1;
	const canNext = desktopPage < totalPages || hasMoreRows;

	// Slide a fixed-size window of page numbers around the current
	// page instead of listing every page.
	const WINDOW_SIZE = 5;
	let windowStart = Math.max(1, desktopPage - Math.floor(WINDOW_SIZE / 2));
	let windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);
	windowStart = Math.max(1, windowEnd - WINDOW_SIZE + 1);

	let pageLinks = '';
	if (windowStart > 1) {
		pageLinks += `
			<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>
			<li class="page-item disabled"><span class="page-link">&hellip;</span></li>
		`;
	}
	for (let page = windowStart; page <= windowEnd; page += 1) {
		pageLinks += `
			<li class="page-item ${page === desktopPage ? 'active' : ''}">
				<a class="page-link" href="#" data-page="${page}">${page}</a>
			</li>
		`;
	}
	if (windowEnd < totalPages) {
		pageLinks += `
			<li class="page-item disabled"><span class="page-link">&hellip;</span></li>
			<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>
		`;
	}

	dom.desktopPagination.innerHTML = `
		<li class="page-item ${canPrev ? '' : 'disabled'}">
			<a class="page-link" href="#" data-action="prev">&lsaquo;</a>
		</li>
		${pageLinks}
		<li class="page-item ${canNext ? '' : 'disabled'}">
			<a class="page-link" href="#" data-action="next">&rsaquo;</a>
		</li>
	`;
};

const goToDesktopPage = (targetPage) => {
	if (targetPage < 1) {
		return;
	}

	const loadedRows = getFilteredRows();
	const loadedPages = Math.max(1, Math.ceil(loadedRows.length / PAGE_SIZE));

	if (targetPage <= loadedPages) {
		desktopPage = targetPage;
		refreshUI();
		return;
	}

	if (targetPage === loadedPages + 1 && hasMoreRows) {
		const request = loadCashAdvances(false);
		if (request) {
			request.done(() => {
				desktopPage = targetPage;
				refreshUI();
			});
		}
	}
};

const inAmountRange = (amount, range) => {
	if (!range) {
		return true;
	}

	const [min, max] = range.split('-').map(Number);
	return Number(amount) >= min && Number(amount) <= max;
};

const matchesFilters = (row) => {
	const status = dom.filterStatus.value;
	const amountRange = dom.filterAmountRange.value;
	const keyword = (dom.filterKeyword ? dom.filterKeyword.value : '').trim().toLowerCase();

	if (keyword) {
		const haystack = `${row.refNo} ${row.purpose}`.toLowerCase();
		if (haystack.indexOf(keyword) === -1) {
			return false;
		}
	}

	if (status && row.status !== status) {
		return false;
	}

	if (dom.filterDateRangePicker) {
		const selected = dom.filterDateRangePicker.selectedDates;
		if (selected.length === 2) {
			const from = selected[0].toISOString().slice(0, 10);
			const to = selected[1].toISOString().slice(0, 10);
			if (row.requestedDate < from || row.requestedDate > to) {
				return false;
			}
		}
	}

	if (!inAmountRange(row.amount, amountRange)) {
		return false;
	}

	return true;
};

const renderDesktopTable = (rows) => {
	dom.cashAdvanceTbody.innerHTML = '';

	if (!rows.length) {
		dom.cashAdvanceTbody.innerHTML =
			'<tr><td colspan="9" class="text-center text-muted">No records found</td></tr>';
		return;
	}

	rows.forEach((row) => {
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td style="white-space:nowrap;">${escapeHtml(row.refNo)}</td>
			<td class="text-right" style="white-space:nowrap;">${formatPHP(row.amount)}</td>
			<td class="text-right" style="white-space:nowrap;">${formatApprovedPHP(row.approvedAmount)}</td>
			<td style="white-space:normal;overflow-wrap:break-word;" title="${escapeHtml(row.purpose)}">${escapeHtml(row.purpose)}</td>
			<td style="white-space:nowrap;">${escapeHtml(row.neededDate)}</td>
			<td style="white-space:nowrap;">${escapeHtml(row.requestedDate)}</td>
			<td style="white-space:nowrap;">${getStatusBadge(row.status)}</td>
			<td class="text-center kna-actions">
				<button type="button" class="btn btn-sm btn-outline-primary" data-action="view" data-ref="${escapeHtml(row.refNo)}">
					View
				</button>
			</td>
		`;
		dom.cashAdvanceTbody.appendChild(tr);
	});
};

const renderMobileCards = (rows) => {
	dom.cashAdvanceMobileList.innerHTML = '';

	if (!rows.length) {
		dom.cashAdvanceMobileList.innerHTML =
			'<div class="kna-small text-center text-muted py-2">No records found</div>';
		return;
	}

	rows.forEach((row) => {
		const item = document.createElement('div');
		item.className = 'kna-item';
		item.innerHTML = `
			<div class="kna-row">
				<div class="kna-small font-weight-bold">Cash Advance No: ${escapeHtml(row.refNo)}</div>
				<div>${getStatusBadge(row.status)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Requested Amount</div>
				<div class="kna-small font-weight-bold">${formatPHP(row.amount)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Approved Amount</div>
				<div class="kna-small font-weight-bold">${formatApprovedPHP(row.approvedAmount)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Needed</div>
				<div class="kna-small">${escapeHtml(row.neededDate)}</div>
			</div>
			<div class="kna-small text-muted mt-1 mb-1 text-truncate">${escapeHtml(row.purpose)}</div>
			<button type="button" class="btn btn-outline-primary btn-sm kna-small w-100" data-action="view" data-ref="${escapeHtml(row.refNo)}">
				View Details
			</button>
		`;
		dom.cashAdvanceMobileList.appendChild(item);
	});
};

const renderSummary = (rows) => {
	const totalRequested = rows.reduce((sum, row) => sum + Number(row.amount || 0), 0);
	const pending = rows
		.filter((row) => row.status === 'Pending Approval')
		.reduce((sum, row) => sum + Number(row.amount || 0), 0);
	const forLiquidation = rows
		.filter((row) => row.status === 'For Liquidation')
		.reduce((sum, row) => sum + Number(row.amount || 0), 0);
	const approved = rows
		.filter((row) => row.status === 'Approved')
		.reduce((sum, row) => sum + Number(row.amount || 0), 0);

	dom.sumTotalGranted.textContent = formatPHP(totalRequested);
	dom.sumPending.textContent = formatPHP(pending);
	dom.sumForLiquidation.textContent = formatPHP(forLiquidation);
	dom.sumApproved.textContent = formatPHP(approved);
};

const getFilteredRows = () => cashAdvances.filter(matchesFilters);

const refreshUI = () => {
	const rows = getFilteredRows();
	const startIndex = (desktopPage - 1) * PAGE_SIZE;
	const desktopRows = rows.slice(startIndex, startIndex + PAGE_SIZE);
	renderSummary(rows);
	renderDesktopTable(desktopRows);
	renderMobileCards(rows);
	renderDesktopPagination(rows);
	dom.resultCount.textContent = `${rows.length} record(s)`;
	dom.resultCountMobile.textContent = `${rows.length} record(s)`;
	updateLoadMoreButtons();
};

const resetFilters = () => {
	desktopPage = 1;
	if (dom.filterKeyword) dom.filterKeyword.value = '';
	if (dom.filterDateRangePicker) {
		dom.filterDateRangePicker.clear();
	}
	dom.filterStatus.value = '';
	dom.filterAmountRange.value = '';
	refreshUI();
};

const cacheDom = () => {
	dom.filterKeyword = document.getElementById('filterKeyword');
	dom.filterDateRange = document.getElementById('filterDateRange');
	dom.filterStatus = document.getElementById('filterStatus');
	dom.filterAmountRange = document.getElementById('filterAmountRange');
	dom.sumTotalGranted = document.getElementById('sumTotalGranted');
	dom.sumPending = document.getElementById('sumPending');
	dom.sumForLiquidation = document.getElementById('sumForLiquidation');
	dom.sumApproved = document.getElementById('sumApproved');
	dom.cashAdvanceTbody = document.getElementById('cashAdvanceTbody');
	dom.cashAdvanceMobileList = document.getElementById('cashAdvanceMobileList');
	dom.resultCount = document.getElementById('resultCount');
	dom.resultCountMobile = document.getElementById('resultCountMobile');
	dom.btnReset = document.getElementById('btnReset');
	dom.btnOpenNew = document.getElementById('btnOpenNew');
	dom.desktopPagination = document.getElementById('desktopPagination');
	dom.btnLoadMoreMobile = document.getElementById('btnLoadMoreMobile');
	dom.cashAdvanceTable = document.getElementById('cashAdvanceTable');
};

const applyFilters = () => {
	desktopPage = 1;
	refreshUI();
};
const goToPath = (path) => {
	window.location.href = `${base_url}${path}`;
};


// ─── CHECK PENDING CA AND HANDLE NEW REQUEST ───
const handleNewRequestClick = () => {
	if (!dom.btnOpenNew) return;

	const hasPending = dom.btnOpenNew.getAttribute('data-has-pending') === '1';
	const pendingId = dom.btnOpenNew.getAttribute('data-pending-id') || '';
	const pendingStatus = dom.btnOpenNew.getAttribute('data-pending-status') || '';

	if (hasPending) {
		Swal.fire({
			icon: 'warning',
			title: 'Pending Cash Advance Found',
			html: `You have a pending cash advance <strong>${escapeHtml(pendingId)}</strong> that needs to be liquidated before creating a new request.`,
			confirmButtonText: 'Ok',
		});
		return;
	}

	goToPath('transactions/cash-advance/add');
};

const bindEvents = () => {
	if (dom.filterKeyword) dom.filterKeyword.addEventListener('input', applyFilters);
	dom.filterStatus.addEventListener('change', applyFilters);
	dom.filterAmountRange.addEventListener('change', applyFilters);
	dom.btnReset.addEventListener('click', resetFilters);

	if (dom.btnOpenNew) {
		dom.btnOpenNew.addEventListener('click', handleNewRequestClick);
	}

	if (dom.desktopPagination) {
		dom.desktopPagination.addEventListener('click', (event) => {
			const target = event.target.closest('a.page-link');
			if (!target) {
				return;
			}
			event.preventDefault();

			if (target.dataset.page) {
				goToDesktopPage(Number(target.dataset.page));
				return;
			}

			if (target.dataset.action === 'prev') {
				goToDesktopPage(desktopPage - 1);
				return;
			}

			if (target.dataset.action === 'next') {
				goToDesktopPage(desktopPage + 1);
			}
		});
	}
	if (dom.btnLoadMoreMobile) {
		dom.btnLoadMoreMobile.addEventListener('click', () => loadCashAdvances(false));
	}

	dom.cashAdvanceTable.addEventListener('click', (event) => {
		const btn = event.target.closest('button[data-action="view"]');
		if (!btn) {
			return;
		}
		goToPath(`transactions/cash-advance/view/${encodeURIComponent(btn.getAttribute('data-ref'))}`);
	});

	dom.cashAdvanceMobileList.addEventListener('click', (event) => {
		const btn = event.target.closest('button[data-action="view"]');
		if (!btn) {
			return;
		}
		goToPath(`transactions/cash-advance/view/${encodeURIComponent(btn.getAttribute('data-ref'))}`);
	});
};

const init = () => {
	cacheDom();
	if (dom.filterDateRange) {
		dom.filterDateRangePicker = flatpickr(dom.filterDateRange, {
			mode: 'range',
			dateFormat: 'Y-m-d',
			allowInput: false,
			onChange: (selectedDates) => {
				if (selectedDates.length === 0 || selectedDates.length === 2) {
					applyFilters();
				}
			},
		});
	}
	bindEvents();
	loadCashAdvances(true);
};

$(document).ready(() => {
	init();
});