/* Liquidation Module - Shared Utilities & Routing */

let liquidations = [];
let liqNextCursorId = null;
let liqHasMoreRows = false;
let liqIsLoadingRows = false;
let liqDesktopPage = 1;
const LIQ_PAGE_SIZE = 10;

// Placeholder to satisfy detail.js references until real data loads
const mockLiquidations = [];

const normalizeApiRows = (rows) =>
	(rows || []).map((row) => ({
		id: Number(row.id),
		liquidationNo: normalizeDate(row.liquidation_id),
		cashAdvanceRef: normalizeDate(row.cash_advance_id),
		caAmount: Number(row.ca_amount || 0),
		description: normalizeDate(row.description || ''),
		liquidatedAmount: Number(row.total_amount_spent || 0),
		approvedAmount: row.approved_liquidated_amount !== null && row.approved_liquidated_amount !== undefined ? Number(row.approved_liquidated_amount) : null,
		hasApproval: Number(row.has_approval || 0) === 1,
		refundAmount: Number(row.refund_amount || 0),
		reimburseAmount: Number(row.reimburse_amount || 0),
		submittedDate: normalizeDate(row.submitted_date || '').slice(0, 10),
		statusCode: normalizeDate(row.status_code),
		status: normalizeDate(row.status_name),
	}));

const loadLiquidations = (reset = false) => {
	if (liqIsLoadingRows) {
		return;
	}
	if (reset) {
		liquidations = [];
		liqNextCursorId = null;
		liqHasMoreRows = false;
		liqDesktopPage = 1;
	}
	liqIsLoadingRows = true;
	updateLoadMoreButton();

	// Take: 0 asks the SP for the exact full result set (no artificial cap);
	// client-side handles filtering & paging from there.
	const payload = { Take: reset ? 0 : LIQ_PAGE_SIZE };
	if (liqNextCursorId !== null) {
		payload.CursorId = liqNextCursorId;
	}

	ajax_loader('transactions/liquidation/api/get/header', payload).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res.status !== 'success') {
			liqIsLoadingRows = false;
			updateLoadMoreButton();
			return;
		}

		const newRows = normalizeApiRows(res.data || []);
		liquidations = reset ? newRows : liquidations.concat(newRows);
		const pagination = res.pagination || {};
		liqHasMoreRows = Boolean(pagination.hasMore);
		liqNextCursorId = liqHasMoreRows ? (pagination.nextCursorId || null) : null;

		liqIsLoadingRows = false;
		updateLoadMoreButton();
		refreshUI();
	}).fail(() => {
		liqIsLoadingRows = false;
		updateLoadMoreButton();
	});
};

const updateLoadMoreButton = () => {
	if (!domList.btnLoadMoreMobile) {
		return;
	}
	if (liqIsLoadingRows) {
		domList.btnLoadMoreMobile.disabled = true;
		domList.btnLoadMoreMobile.textContent = 'Loading…';
		domList.btnLoadMoreMobile.classList.remove('d-none');
		return;
	}
	if (liqHasMoreRows) {
		domList.btnLoadMoreMobile.disabled = false;
		domList.btnLoadMoreMobile.textContent = 'Load More';
		domList.btnLoadMoreMobile.classList.remove('d-none');
	} else {
		domList.btnLoadMoreMobile.classList.add('d-none');
	}
};

// Shared utility functions
const formatPHP = (amount) => {
	const value = Number(amount || 0);
	return value.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
};

const formatApprovedPHP = (amount) => (amount === null || amount === undefined ? '—' : formatPHP(amount));

const normalizeDate = (value) => (value ? String(value) : '');

const parseDateRange = (rangeText) => {
	const value = normalizeDate(rangeText);
	if (!value) {
		return { from: '', to: '' };
	}

	const match = value.match(/^(\d{4}-\d{2}-\d{2})\s+to\s+(\d{4}-\d{2}-\d{2})$/i);
	if (!match) {
		const parts = value.split(' to ').map((part) => normalizeDate(part).trim()).filter(Boolean);
		if (parts.length === 2) {
			return { from: parts[0], to: parts[1] };
		}
		return { from: '', to: '' };
	}

	return { from: match[1], to: match[2] };
};

const escapeHtml = (value = '') =>
	String(value)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');

const getStatusBadge = (status) => {
	if (status === 'Draft') {
		return '<span class="kna-badge kna-badge-draft">Draft</span>';
	}
	if (status === 'Approved') {
		return '<span class="kna-badge kna-badge-approved">Approved</span>';
	}
	if (status === 'Rejected') {
		return '<span class="kna-badge kna-badge-rejected">Rejected</span>';
	}
	if (status === 'Completed'){
		return '<span class="kna-badge kna-badge-approved">Completed</span>'
	}
	return '<span class="kna-badge kna-badge-pending">Submitted</span>';
};

const varianceValue = (row) => Number(row.refundAmount || 0) - Number(row.reimburseAmount || 0);

const varianceLabel = (row) => {
	const refund = Number(row.refundAmount || 0);
	const reimburse = Number(row.reimburseAmount || 0);
	if (refund > 0) {
		return `${formatPHP(refund)} to return`;
	}
	if (reimburse > 0) {
		return `${formatPHP(reimburse)} to reimburse`;
	}
	return '0.00';
};

const settlementFromVariance = (value) => {
	if (value > 0) {
		return {
			action: 'Return to Cashier',
			amount: formatPHP(value),
		};
	}
	if (value < 0) {
		return {
			action: 'For Reimbursement',
			amount: formatPHP(Math.abs(value)),
		};
	}
	return {
		action: '0.00',
		amount: formatPHP(0),
	};
};

const inAmountRange = (amount, range) => {
	if (!range) {
		return true;
	}

	const [min, max] = range.split('-').map(Number);
	return Number(amount) >= min && Number(amount) <= max;
};

const makeFileKey = (file) => `${file.name}|${file.size}|${file.lastModified}`;

const receiptPreviewMarkup = (file) => {
	if ((file.type || '').startsWith('image/')) {
		const objectUrl = URL.createObjectURL(file);
		return `<img src="${objectUrl}" alt="${escapeHtml(file.name)}" style="width:100%;height:120px;object-fit:cover;">`;
	}
	return '<div class="kna-receipt-preview">DOCUMENT PREVIEW</div>';
};

const goToPath = (path) => {
	window.location.href = `${base_url}${path}`;
};

// List page DOM & functions
const domList = {
	filterDateRange: null,
	filterDateRangePicker: null,
	filterStatus: null,
	filterAmountRange: null,
	sumTotalLiquidated: null,
	sumPendingReview: null,
	sumApprovedLiquidation: null,
	sumRejected: null,
	liquidationTbody: null,
	liquidationMobileList: null,
	resultCount: null,
	resultCountMobile: null,
	btnSearch: null,
	btnReset: null,
	btnOpenNewLiquidation: null,
	liquidationTable: null,
	btnLoadMoreMobile: null,
	desktopPagination: null,
};

const matchesFilters = (row) => {
	const range = domList.filterDateRangePicker ? parseDateRange(domList.filterDateRangePicker.value) : { from: '', to: '' };
	const status = domList.filterStatus.value;
	const amountRange = domList.filterAmountRange.value;
	const keyword = (domList.filterKeyword ? domList.filterKeyword.value : '').trim().toLowerCase();

	if (keyword) {
		const haystack = `${row.liquidationNo} ${row.cashAdvanceRef} ${row.description}`.toLowerCase();
		if (haystack.indexOf(keyword) === -1) {
			return false;
		}
	}

	if (status && row.status !== status) {
		return false;
	}
	if (range.from && row.submittedDate < range.from) {
		return false;
	}
	if (range.to && row.submittedDate > range.to) {
		return false;
	}
	if (!inAmountRange(row.liquidatedAmount, amountRange)) {
		return false;
	}

	return true;
};

const renderDesktopTable = (rows) => {
	domList.liquidationTbody.innerHTML = '';

	if (!rows.length) {
		domList.liquidationTbody.innerHTML =
			'<tr><td colspan="9" class="text-center text-muted">No records found</td></tr>';
		return;
	}

	rows.forEach((row) => {
		const isDraft = row.statusCode === 'LQ_DRAFT' || row.status === 'Draft';
		const actionButton = isDraft
			? `<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit" data-ref="${escapeHtml(row.liquidationNo)}">Edit Draft</button>`
			: ((row.status === 'Submitted' || row.status === 'Pending Approval') && !row.hasApproval)
				? `<button type="button" class="btn btn-sm btn-outline-warning" data-action="edit-submitted" data-ref="${escapeHtml(row.liquidationNo)}">Edit</button>
           <button type="button" class="btn btn-sm btn-outline-primary" data-action="view" data-ref="${escapeHtml(row.liquidationNo)}">View</button>`
				: `<button type="button" class="btn btn-sm btn-outline-primary" data-action="view" data-ref="${escapeHtml(row.liquidationNo)}">View</button>`;
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td>${escapeHtml(row.liquidationNo)}</td>
			<td>${escapeHtml(row.cashAdvanceRef)}</td>
			<td class="text-right">${formatPHP(row.caAmount)}</td>
			<td class="text-right">${formatPHP(row.liquidatedAmount)}</td>
			<td class="text-right">${formatApprovedPHP(row.approvedAmount)}</td>
			<td class="text-right">${escapeHtml(varianceLabel(row))}</td>
			<td>${escapeHtml(row.submittedDate)}</td>
			<td>${getStatusBadge(row.status)}</td>
			<td class="text-center kna-actions">
				${actionButton}
			</td>
		`;
		domList.liquidationTbody.appendChild(tr);
	});
};

const renderDesktopPagination = (rows) => {
	if (!domList.desktopPagination) {
		return;
	}

	const totalPages = Math.max(1, Math.ceil(rows.length / LIQ_PAGE_SIZE));
	if (liqDesktopPage > totalPages) {
		liqDesktopPage = totalPages;
	}

	const canPrev = liqDesktopPage > 1;
	const canNext = liqDesktopPage < totalPages || liqHasMoreRows;

	// Slide a fixed-size window of page numbers around the current
	// page instead of listing every page.
	const WINDOW_SIZE = 5;
	let windowStart = Math.max(1, liqDesktopPage - Math.floor(WINDOW_SIZE / 2));
	let windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);
	windowStart = Math.max(1, windowEnd - WINDOW_SIZE + 1);

	let pageLinks = '';
	if (windowStart > 1) {
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-action="page" data-page="1">1</a></li>`;
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
	}
	for (let page = windowStart; page <= windowEnd; page += 1) {
		const active = page === liqDesktopPage ? 'active' : '';
		pageLinks += `<li class="page-item ${active}"><a class="page-link" href="#" data-action="page" data-page="${page}">${page}</a></li>`;
	}
	if (windowEnd < totalPages) {
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-action="page" data-page="${totalPages}">${totalPages}</a></li>`;
	}

	domList.desktopPagination.innerHTML = `
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

	const rows = getFilteredRows();
	const totalPages = Math.max(1, Math.ceil(rows.length / LIQ_PAGE_SIZE));

	if (targetPage > totalPages) {
		return;
	}

	liqDesktopPage = targetPage;
	refreshUI();
};

const renderMobileCards = (rows) => {
	domList.liquidationMobileList.innerHTML = '';

	if (!rows.length) {
		domList.liquidationMobileList.innerHTML =
			'<div class="kna-small text-center text-muted py-2">No records found</div>';
		return;
	}

	rows.forEach((row) => {
		const isDraft = row.statusCode === 'LQ_DRAFT' || row.status === 'Draft';
		const actionButton = isDraft
			? `<button type="button" class="btn btn-outline-secondary btn-sm kna-small w-100" data-action="edit" data-ref="${escapeHtml(row.liquidationNo)}">Edit Draft</button>`
			: ((row.status === 'Submitted' || row.status === 'Pending Approval') && !row.hasApproval)
				? `<button type="button" class="btn btn-outline-warning btn-sm kna-small w-100 mb-1" data-action="edit-submitted" data-ref="${escapeHtml(row.liquidationNo)}">Edit</button>
           <button type="button" class="btn btn-outline-primary btn-sm kna-small w-100" data-action="view" data-ref="${escapeHtml(row.liquidationNo)}">View Details</button>`
				: `<button type="button" class="btn btn-outline-primary btn-sm kna-small w-100" data-action="view" data-ref="${escapeHtml(row.liquidationNo)}">View Details</button>`;
		const item = document.createElement('div');
		item.className = 'kna-item';
		item.innerHTML = `
			<div class="kna-row">
				<div class="kna-small font-weight-bold">${escapeHtml(row.liquidationNo)}</div>
				<div>${getStatusBadge(row.status)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Cash Advance</div>
				<div class="kna-small">${escapeHtml(row.cashAdvanceRef)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">CA Amount</div>
				<div class="kna-small">${formatPHP(row.caAmount)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Liquidated (Requested)</div>
				<div class="kna-small font-weight-bold">${formatPHP(row.liquidatedAmount)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Liquidated (Approved)</div>
				<div class="kna-small font-weight-bold">${formatApprovedPHP(row.approvedAmount)}</div>
			</div>
			${actionButton}
		`;
		domList.liquidationMobileList.appendChild(item);
	});
};

const renderSummary = (rows) => {
	const totalLiquidated = rows.reduce((sum, row) => sum + Number(row.liquidatedAmount || 0), 0);
	const pendingReview = rows
		.filter((row) => row.status === 'Submitted')
		.reduce((sum, row) => sum + Number(row.liquidatedAmount || 0), 0);
	const approved = rows
		.filter((row) => row.status === 'Approved')
		.reduce((sum, row) => sum + Number(row.liquidatedAmount || 0), 0);
	const Rejected = rows
		.filter((row) => row.status === 'Rejected')
		.reduce((sum, row) => sum + Number(row.liquidatedAmount || 0), 0);

	domList.sumTotalLiquidated.textContent = formatPHP(totalLiquidated);
	domList.sumPendingReview.textContent = formatPHP(pendingReview);
	domList.sumApprovedLiquidation.textContent = formatPHP(approved);
	domList.sumRejected.textContent = formatPHP(Rejected);
};

const getFilteredRows = () => liquidations.filter(matchesFilters);

const applyFilters = () => {
	liqDesktopPage = 1;
	refreshUI();
};

const refreshUI = () => {
	const rows = getFilteredRows();
	const startIndex = (liqDesktopPage - 1) * LIQ_PAGE_SIZE;
	const desktopRows = rows.slice(startIndex, startIndex + LIQ_PAGE_SIZE);
	renderSummary(rows);
	renderDesktopTable(desktopRows);
	renderDesktopPagination(rows);
	renderMobileCards(rows);
	domList.resultCount.textContent = `${rows.length} record(s)`;
	domList.resultCountMobile.textContent = `${rows.length} record(s)`;
	updateLoadMoreButton();
};

const resetFilters = () => {
	if (domList.filterKeyword) domList.filterKeyword.value = '';
	if (domList.filterDateRangePicker) {
		domList.filterDateRangePicker.clear();
	}
	domList.filterStatus.value = '';
	domList.filterAmountRange.value = '';
	applyFilters();
};

const cacheListDom = () => {
	domList.filterKeyword = document.getElementById('filterKeyword');
	domList.filterDateRange = document.getElementById('filterDateRange');
	domList.filterDateRangePicker = document.getElementById('filterDateRange');
	domList.filterStatus = document.getElementById('filterStatus');
	domList.filterAmountRange = document.getElementById('filterAmountRange');
	domList.sumTotalLiquidated = document.getElementById('sumTotalLiquidated');
	domList.sumPendingReview = document.getElementById('sumPendingReview');
	domList.sumApprovedLiquidation = document.getElementById('sumApprovedLiquidation');
	domList.sumRejected = document.getElementById('sumRejected');
	domList.liquidationTbody = document.getElementById('liquidationTbody');
	domList.liquidationMobileList = document.getElementById('liquidationMobileList');
	domList.resultCount = document.getElementById('resultCount');
	domList.resultCountMobile = document.getElementById('resultCountMobile');
	domList.btnSearch = document.getElementById('btnSearch');
	domList.btnReset = document.getElementById('btnReset');
	domList.btnOpenNewLiquidation = document.getElementById('btnOpenNewLiquidation');
	domList.liquidationTable = document.getElementById('liquidationTable');
	domList.btnLoadMoreMobile = document.getElementById('btnLoadMoreMobile');
	domList.desktopPagination = document.getElementById('desktopPagination');
};

const initListPage = () => {
	cacheListDom();
	if (domList.filterDateRange) {
		domList.filterDateRangePicker = flatpickr(domList.filterDateRange, {
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
	if (domList.filterKeyword) domList.filterKeyword.addEventListener('input', applyFilters);
	domList.filterStatus.addEventListener('change', applyFilters);
	domList.filterAmountRange.addEventListener('change', applyFilters);
	domList.btnReset.addEventListener('click', resetFilters);
	domList.btnOpenNewLiquidation.addEventListener('click', () => {
		goToPath('transactions/liquidation/add');
	});

	domList.liquidationTable.addEventListener('click', (event) => {
		const btn = event.target.closest('button[data-action]');
		if (!btn) {
			return;
		}
		const action = btn.getAttribute('data-action');
		if (action === 'edit') {
			goToPath(`transactions/liquidation/add/${btn.getAttribute('data-ref')}`);
			return;
		}
		goToPath(`transactions/liquidation/view/${btn.getAttribute('data-ref')}`);

		if (action === 'edit-submitted') {
			goToPath(`transactions/liquidation/edit/${btn.getAttribute('data-ref')}`);
			return;
		}
	});


	domList.liquidationMobileList.addEventListener('click', (event) => {
		const btn = event.target.closest('button[data-action]');
		if (!btn) {
			return;
		}
		const action = btn.getAttribute('data-action');
		if (action === 'edit') {
			goToPath(`transactions/liquidation/add/${btn.getAttribute('data-ref')}`);
			return;
		}
		goToPath(`transactions/liquidation/view/${btn.getAttribute('data-ref')}`);
		if (action === 'edit-submitted') {
			goToPath(`transactions/liquidation/edit/${btn.getAttribute('data-ref')}`);
			return;
		}
	});

	if (domList.btnLoadMoreMobile) {
		domList.btnLoadMoreMobile.addEventListener('click', () => loadLiquidations(false));
	}

	if (domList.desktopPagination) {
		domList.desktopPagination.addEventListener('click', (event) => {
			const btn = event.target.closest('a[data-action]');
			if (!btn) {
				return;
			}

			event.preventDefault();
			if (btn.getAttribute('data-action') === 'prev') {
				goToDesktopPage(liqDesktopPage - 1);
				return;
			}
			if (btn.getAttribute('data-action') === 'next') {
				goToDesktopPage(liqDesktopPage + 1);
				return;
			}
			const page = Number(btn.getAttribute('data-page'));
			if (page) {
				goToDesktopPage(page);
			}
		});
	}

	loadLiquidations(true);
};

// Router: Initialize the correct page
const initModule = () => {
	// Check which page we're on and load the appropriate module
	if (document.getElementById('liquidationTable')) {
		initListPage();
	} else if (document.getElementById('expenseItemsContainer')) {
		initAddPage();
	} else if (document.getElementById('editPageMarker')) {
		initEditPage();
	} else if (document.getElementById('liquidationRef')) {
		initDetailPage();
	}
};

$(document).ready(() => {
	initModule();
});