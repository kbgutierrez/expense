let reimbursements = [];
let rmbNextCursorId = null;
let rmbHasMoreRows = false;
let rmbIsLoadingRows = false;
let rmbDesktopPage = 1;
const RMB_PAGE_SIZE = 10;

const normalizeApiRows = (rows) =>
	(rows || []).map((row) => ({
		id: Number(row.id),
		reimbursementNo: normalizeDate(row.reimbursement_id),
		description: normalizeDate(row.description || ''),
		totalAmount: Number(row.total_amount || 0),
		approvedAmount: row.approved_amount !== null && row.approved_amount !== undefined ? Number(row.approved_amount) : null,
		hasApproval: Number(row.has_approval || 0) === 1,
		submittedDate: normalizeDate(row.submitted_date || '').slice(0, 10),
		statusCode: normalizeDate(row.status_code),
		status: normalizeDate(row.status_name),
	}));

const loadReimbursements = (reset = false) => {
	if (rmbIsLoadingRows) {
		return;
	}
	if (reset) {
		reimbursements = [];
		rmbNextCursorId = null;
		rmbHasMoreRows = false;
		rmbDesktopPage = 1;
	}
	rmbIsLoadingRows = true;
	updateLoadMoreButton();

	const payload = { Take: 20 };
	if (rmbNextCursorId !== null) {
		payload.CursorId = rmbNextCursorId;
	}

	ajax_loader('transactions/reimbursement/api/list', payload).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res.status !== 'success') {
			rmbIsLoadingRows = false;
			updateLoadMoreButton();
			return;
		}

		const newRows = normalizeApiRows(res.data || []);
		reimbursements = reset ? newRows : reimbursements.concat(newRows);
		const pagination = res.pagination || {};
		rmbHasMoreRows = Boolean(pagination.hasMore);
		rmbNextCursorId = rmbHasMoreRows ? (pagination.nextCursorId || null) : null;

		rmbIsLoadingRows = false;
		updateLoadMoreButton();
		refreshUI();
	}).fail(() => {
		rmbIsLoadingRows = false;
		updateLoadMoreButton();
	});
};

const updateLoadMoreButton = () => {
	if (!domList.btnLoadMoreMobile) {
		return;
	}
	if (rmbIsLoadingRows) {
		domList.btnLoadMoreMobile.disabled = true;
		domList.btnLoadMoreMobile.textContent = 'Loading…';
		domList.btnLoadMoreMobile.classList.remove('d-none');
		return;
	}
	if (rmbHasMoreRows) {
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
	if (status === 'Paid') {
		return '<span class="kna-badge kna-badge-paid">Paid</span>';
	}
	return '<span class="kna-badge kna-badge-pending">Submitted</span>';
};

const inAmountRange = (amount, range) => {
	if (!range) {
		return true;
	}

	const [min, max] = range.split('-').map(Number);
	return Number(amount) >= min && Number(amount) <= max;
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
	sumTotalReimbursement: null,
	sumPendingReview: null,
	sumApprovedReimbursement: null,
	sumRejected: null,
	reimbursementTbody: null,
	reimbursementMobileList: null,
	resultCount: null,
	resultCountMobile: null,
	btnSearch: null,
	btnReset: null,
	btnOpenNewReimbursement: null,
	reimbursementTable: null,
	btnLoadMoreMobile: null,
	desktopPagination: null,
};

const matchesFilters = (row) => {
	const range = domList.filterDateRangePicker ? parseDateRange(domList.filterDateRangePicker.value) : { from: '', to: '' };
	const status = domList.filterStatus.value;
	const amountRange = domList.filterAmountRange.value;
	const keyword = (domList.filterKeyword ? domList.filterKeyword.value : '').trim().toLowerCase();

	if (keyword) {
		const haystack = `${row.reimbursementNo} ${row.description}`.toLowerCase();
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
	if (!inAmountRange(row.totalAmount, amountRange)) {
		return false;
	}

	return true;
};

const renderDesktopTable = (rows) => {
	domList.reimbursementTbody.innerHTML = '';

	if (!rows.length) {
		domList.reimbursementTbody.innerHTML =
			'<tr><td colspan="6" class="text-center text-muted">No records found</td></tr>';
		return;
	}

	rows.forEach((row) => {
		const isDraft = row.statusCode === 'RMB_DRAFT' || row.status === 'Draft';
		const actionButton = isDraft
			? `<button type="button" class="btn btn-sm btn-outline-secondary" data-action="edit" data-ref="${escapeHtml(row.reimbursementNo)}">Edit Draft</button>`
			: ((row.status === 'Submitted' || row.status === 'Pending Approval') && !row.hasApproval)
				? `<button type="button" class="btn btn-sm btn-outline-warning" data-action="edit-submitted" data-ref="${escapeHtml(row.reimbursementNo)}">Edit</button>
           <button type="button" class="btn btn-sm btn-outline-primary" data-action="view" data-ref="${escapeHtml(row.reimbursementNo)}">View</button>`
				: `<button type="button" class="btn btn-sm btn-outline-primary" data-action="view" data-ref="${escapeHtml(row.reimbursementNo)}">View</button>`;
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td style="white-space:nowrap;">${escapeHtml(row.reimbursementNo)}</td>
			<td class="text-right" style="white-space:nowrap;">${formatPHP(row.totalAmount)}</td>
			<td class="text-right" style="white-space:nowrap;">${formatApprovedPHP(row.approvedAmount)}</td>
			<td style="white-space:nowrap;">${escapeHtml(row.submittedDate)}</td>
			<td style="white-space:nowrap;">${getStatusBadge(row.status)}</td>
			<td class="text-center kna-actions">
				${actionButton}
			</td>
		`;
		domList.reimbursementTbody.appendChild(tr);
	});
};

const renderDesktopPagination = (rows) => {
	if (!domList.desktopPagination) {
		return;
	}

	const totalPages = Math.max(1, Math.ceil(rows.length / RMB_PAGE_SIZE));
	if (rmbDesktopPage > totalPages) {
		rmbDesktopPage = totalPages;
	}

	const canPrev = rmbDesktopPage > 1;
	const canNext = rmbDesktopPage < totalPages || rmbHasMoreRows;

	// Slide a fixed-size window of page numbers around the current
	// page instead of listing every page.
	const WINDOW_SIZE = 5;
	let windowStart = Math.max(1, rmbDesktopPage - Math.floor(WINDOW_SIZE / 2));
	let windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);
	windowStart = Math.max(1, windowEnd - WINDOW_SIZE + 1);

	let pageLinks = '';
	if (windowStart > 1) {
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-action="page" data-page="1">1</a></li>`;
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
	}
	for (let page = windowStart; page <= windowEnd; page += 1) {
		const active = page === rmbDesktopPage ? 'active' : '';
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
	const totalPages = Math.max(1, Math.ceil(rows.length / RMB_PAGE_SIZE));

	if (targetPage > totalPages) {
		return;
	}

	rmbDesktopPage = targetPage;
	refreshUI();
};

const renderMobileCards = (rows) => {
	domList.reimbursementMobileList.innerHTML = '';

	if (!rows.length) {
		domList.reimbursementMobileList.innerHTML =
			'<div class="kna-small text-center text-muted py-2">No records found</div>';
		return;
	}

	rows.forEach((row) => {
		const isDraft = row.statusCode === 'RMB_DRAFT' || row.status === 'Draft';
		const actionButton = isDraft
			? `<button type="button" class="btn btn-outline-secondary btn-sm kna-small w-100" data-action="edit" data-ref="${escapeHtml(row.reimbursementNo)}">Edit Draft</button>`
			: ((row.status === 'Submitted' || row.status === 'Pending Approval') && !row.hasApproval)
				? `<button type="button" class="btn btn-outline-warning btn-sm kna-small w-100 mb-1" data-action="edit-submitted" data-ref="${escapeHtml(row.reimbursementNo)}">Edit</button>
           <button type="button" class="btn btn-outline-primary btn-sm kna-small w-100" data-action="view" data-ref="${escapeHtml(row.reimbursementNo)}">View Details</button>`
				: `<button type="button" class="btn btn-outline-primary btn-sm kna-small w-100" data-action="view" data-ref="${escapeHtml(row.reimbursementNo)}">View Details</button>`;
		const item = document.createElement('div');
		item.className = 'kna-item';
		item.innerHTML = `
			<div class="kna-row">
				<div class="kna-small font-weight-bold">${escapeHtml(row.reimbursementNo)}</div>
				<div>${getStatusBadge(row.status)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Requested Amount</div>
				<div class="kna-small font-weight-bold">${formatPHP(row.totalAmount)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Approved Amount</div>
				<div class="kna-small font-weight-bold">${formatApprovedPHP(row.approvedAmount)}</div>
			</div>
			<div class="kna-row">
				<div class="kna-small text-muted">Submitted</div>
				<div class="kna-small">${escapeHtml(row.submittedDate)}</div>
			</div>
			${actionButton}
		`;
		domList.reimbursementMobileList.appendChild(item);
	});
};

const renderSummary = (rows) => {
	const totalReimbursement = rows.reduce((sum, row) => sum + Number(row.totalAmount || 0), 0);
	const pendingReview = rows
		.filter((row) => row.status === 'Submitted')
		.reduce((sum, row) => sum + Number(row.totalAmount || 0), 0);
	const approved = rows
		.filter((row) => row.status === 'Approved')
		.reduce((sum, row) => sum + Number(row.totalAmount || 0), 0);
	const rejected = rows
		.filter((row) => row.status === 'Rejected')
		.reduce((sum, row) => sum + Number(row.totalAmount || 0), 0);

	domList.sumTotalReimbursement.textContent = formatPHP(totalReimbursement);
	domList.sumPendingReview.textContent = formatPHP(pendingReview);
	domList.sumApprovedReimbursement.textContent = formatPHP(approved);
	domList.sumRejected.textContent = formatPHP(rejected);
};

const getFilteredRows = () => reimbursements.filter(matchesFilters);

const applyFilters = () => {
	rmbDesktopPage = 1;
	refreshUI();
};

const refreshUI = () => {
	const rows = getFilteredRows();
	const startIndex = (rmbDesktopPage - 1) * RMB_PAGE_SIZE;
	const desktopRows = rows.slice(startIndex, startIndex + RMB_PAGE_SIZE);
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
	domList.sumTotalReimbursement = document.getElementById('sumTotalReimbursement');
	domList.sumPendingReview = document.getElementById('sumPendingReview');
	domList.sumApprovedReimbursement = document.getElementById('sumApprovedReimbursement');
	domList.sumRejected = document.getElementById('sumRejected');
	domList.reimbursementTbody = document.getElementById('reimbursementTbody');
	domList.reimbursementMobileList = document.getElementById('reimbursementMobileList');
	domList.resultCount = document.getElementById('resultCount');
	domList.resultCountMobile = document.getElementById('resultCountMobile');
	domList.btnSearch = document.getElementById('btnSearch');
	domList.btnReset = document.getElementById('btnReset');
	domList.btnOpenNewReimbursement = document.getElementById('btnOpenNewReimbursement');
	domList.reimbursementTable = document.getElementById('reimbursementTable');
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
	domList.btnOpenNewReimbursement.addEventListener('click', () => {
		goToPath('transactions/reimbursement/add');
	});

	domList.reimbursementTable.addEventListener('click', (event) => {
		const btn = event.target.closest('button[data-action]');
		if (!btn) {
			return;
		}
		const action = btn.getAttribute('data-action');
		if (action === 'edit') {
			goToPath(`transactions/reimbursement/add/${btn.getAttribute('data-ref')}`);
			return;
		}
		if (action === 'edit-submitted') {
			goToPath(`transactions/reimbursement/edit/${btn.getAttribute('data-ref')}`);
			return;
		}
		goToPath(`transactions/reimbursement/view/${btn.getAttribute('data-ref')}`);
	});

	domList.reimbursementMobileList.addEventListener('click', (event) => {
		const btn = event.target.closest('button[data-action]');
		if (!btn) {
			return;
		}
		const action = btn.getAttribute('data-action');
		if (action === 'edit') {
			goToPath(`transactions/reimbursement/add/${btn.getAttribute('data-ref')}`);
			return;
		}
		if (action === 'edit-submitted') {
			goToPath(`transactions/reimbursement/edit/${btn.getAttribute('data-ref')}`);
			return;
		}
		goToPath(`transactions/reimbursement/view/${btn.getAttribute('data-ref')}`);
	});

	if (domList.btnLoadMoreMobile) {
		domList.btnLoadMoreMobile.addEventListener('click', () => loadReimbursements(false));
	}

	if (domList.desktopPagination) {
		domList.desktopPagination.addEventListener('click', (event) => {
			const btn = event.target.closest('a[data-action]');
			if (!btn) {
				return;
			}

			event.preventDefault();
			if (btn.getAttribute('data-action') === 'prev') {
				goToDesktopPage(rmbDesktopPage - 1);
				return;
			}
			if (btn.getAttribute('data-action') === 'next') {
				goToDesktopPage(rmbDesktopPage + 1);
				return;
			}
			const page = Number(btn.getAttribute('data-page'));
			if (page) {
				goToDesktopPage(page);
			}
		});
	}

	loadReimbursements(true);

	initTeamTab();
};

/* ============================================================
   MY TEAM TAB (only present in the DOM for users who currently
   hold an active revolving fund)
   ============================================================ */

const domTeam = {
	dateRange: null,
	dateRangePicker: null,
	btnSearch: null,
	btnExport: null,
	table: null,
	tbody: null,
	mobileList: null,
	resultCount: null,
	resultCountMobile: null,
};

let teamItemRows = []; // flat, one row per expense line — the source of truth (and export shape)
let teamGroups = []; // one entry per reimbursement, for the summary table + "View" modal
let teamLastRange = null;

const dateToIso = (date) => {
	const y = date.getFullYear();
	const m = `${date.getMonth() + 1}`.padStart(2, '0');
	const d = `${date.getDate()}`.padStart(2, '0');
	return `${y}-${m}-${d}`;
};

const getCostCenterDisplay = (row) => {
	const ccId = row.costCenterId || '';
	const ccName = row.costCenterName || '';
	if (ccId && ccName) return `${ccId} - ${ccName}`;
	return ccName || ccId || '-';
};

const getExpenseTypeDisplay = (row) => {
	const code = row.expenseCategory || '';
	const name = row.categoryName || '';
	if (code && name) return `${code} - ${name}`;
	return name || code || '-';
};

const normalizeTeamItemRows = (rows) => (rows || []).map((row) => ({
	reimbursementId: normalizeDate(row.reimbursement_id),
	salesman: normalizeDate(row.salesman),
	costCenterId: normalizeDate(row.cost_center_id),
	costCenterName: normalizeDate(row.cost_center_name),
	statusName: normalizeDate(row.status_name),
	createdDate: normalizeDate(row.created_date).slice(0, 10),
	headerTotalAmount: Number(row.header_total_amount || 0),
	expenseCategory: normalizeDate(row.expense_category),
	categoryName: normalizeDate(row.category_name),
	description: normalizeDate(row.description),
	invoiceReceiptNo: normalizeDate(row.invoice_receipt_no),
	documentDate: normalizeDate(row.document_date).slice(0, 10),
	actualAmount: Number(row.actual_amount || 0),
	approvedAmount: row.approved_amount !== null && row.approved_amount !== undefined ? Number(row.approved_amount) : null,
	vendorName: normalizeDate(row.vendor_name),
}));

const groupTeamItemRows = (rows) => {
	const groups = new Map();
	rows.forEach((row) => {
		if (!groups.has(row.reimbursementId)) {
			groups.set(row.reimbursementId, {
				reimbursementId: row.reimbursementId,
				salesman: row.salesman,
				costCenterId: row.costCenterId,
				costCenterName: row.costCenterName,
				statusName: row.statusName,
				createdDate: row.createdDate,
				totalAmount: row.headerTotalAmount,
				items: [],
			});
		}
		groups.get(row.reimbursementId).items.push(row);
	});
	return Array.from(groups.values());
};

const renderTeamDesktopTable = (groups) => {
	domTeam.tbody.innerHTML = '';
	if (!groups.length) {
		domTeam.tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No records found</td></tr>';
		return;
	}
	groups.forEach((group) => {
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td class="font-weight-bold" style="white-space:nowrap;">${escapeHtml(group.reimbursementId)}</td>
			<td style="white-space:normal;overflow-wrap:break-word;">${escapeHtml(group.salesman)}</td>
			<td class="text-right" style="white-space:nowrap;">${formatPHP(group.totalAmount)}</td>
			<td style="white-space:nowrap;">${group.items.length} item(s)</td>
			<td style="white-space:normal;overflow-wrap:break-word;">${escapeHtml(getCostCenterDisplay(group))}</td>
			<td style="white-space:nowrap;">${escapeHtml(group.statusName)}</td>
			<td style="white-space:nowrap;">${escapeHtml(group.createdDate)}</td>
			<td class="text-center kna-actions">
				<button type="button" class="btn btn-sm btn-outline-primary" data-action="view-team-details" data-reimbursement-id="${escapeHtml(group.reimbursementId)}">View</button>
			</td>
		`;
		domTeam.tbody.appendChild(tr);
	});
};

const renderTeamMobileCards = (groups) => {
	domTeam.mobileList.innerHTML = '';
	if (!groups.length) {
		domTeam.mobileList.innerHTML = '<div class="kna-small text-center text-muted py-2">No records found</div>';
		return;
	}
	groups.forEach((group) => {
		const item = document.createElement('div');
		item.className = 'kna-item';
		item.innerHTML = `
			<div class="kna-row">
				<div class="kna-small font-weight-bold">${escapeHtml(group.reimbursementId)}</div>
				<div class="kna-small">${escapeHtml(group.statusName)}</div>
			</div>
			<div class="kna-small font-weight-bold">${escapeHtml(group.salesman)}</div>
			<div class="kna-row"><div class="kna-small text-muted">Amount</div><div class="kna-small">${formatPHP(group.totalAmount)}</div></div>
			<div class="kna-row"><div class="kna-small text-muted">Filed</div><div class="kna-small">${escapeHtml(group.createdDate)}</div></div>
			<div class="kna-small text-muted mt-1">${group.items.length} item(s)</div>
			<button type="button" class="btn btn-outline-primary btn-sm kna-small w-100 mt-2" data-action="view-team-details" data-reimbursement-id="${escapeHtml(group.reimbursementId)}">View Details</button>
		`;
		domTeam.mobileList.appendChild(item);
	});
};

const openTeamReimbursementDetails = (reimbursementId) => {
	window.location.href = `${base_url}transactions/reimbursement/team/view/${encodeURIComponent(reimbursementId)}`;
};

const refreshTeamUI = () => {
	teamGroups = groupTeamItemRows(teamItemRows);
	renderTeamDesktopTable(teamGroups);
	renderTeamMobileCards(teamGroups);
	domTeam.resultCount.textContent = `${teamGroups.length} record(s)`;
	domTeam.resultCountMobile.textContent = `${teamGroups.length} record(s)`;
	domTeam.btnExport.disabled = teamItemRows.length === 0;
};

const teamSearch = () => {
	const range = domTeam.dateRangePicker ? domTeam.dateRangePicker.selectedDates : [];
	if (range.length !== 2) {
		Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Please select a date range.' });
		return;
	}

	const dateFrom = dateToIso(range[0]);
	const dateTo = dateToIso(range[1]);

	ajax_loader('transactions/reimbursement/api/get/team', { DateFrom: dateFrom, DateTo: dateTo }).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (!res || res.status !== 'success') {
			Swal.fire({ icon: 'error', title: 'Failed', text: (res && res.response) ? res.response : 'Failed to load report.' });
			return;
		}
		teamItemRows = normalizeTeamItemRows(res.data);
		teamLastRange = { dateFrom, dateTo };
		refreshTeamUI();
	}).fail(() => {
		Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Could not connect to the server.' });
	});
};

const teamExport = () => {
	if (!teamItemRows.length || typeof XLSX === 'undefined') return;

	const exportRows = teamItemRows.map((row) => ({
		'Reimbursement No': row.reimbursementId,
		'Salesman': row.salesman,
		'Expense Type Code': row.expenseCategory,
		'Expense Type': row.categoryName,
		'Cost Center Code': row.costCenterId,
		'Cost Center': row.costCenterName,
		'Description': row.description,
		'Amount': row.approvedAmount !== null ? row.approvedAmount : row.actualAmount,
		'Status': row.statusName,
		'Filed Date': row.createdDate,
	}));

	const ws = XLSX.utils.json_to_sheet(exportRows);
	const wb = XLSX.utils.book_new();
	XLSX.utils.book_append_sheet(wb, ws, 'Reimbursements');

	const range = teamLastRange || {};
	const fileName = `Team-Reimbursement-Report-${range.dateFrom || 'all'}-to-${range.dateTo || 'all'}.xlsx`;
	XLSX.writeFile(wb, fileName);
};

const initTeamTab = () => {
	domTeam.dateRange = document.getElementById('teamDateRange');
	if (!domTeam.dateRange) {
		return;
	}

	domTeam.btnSearch = document.getElementById('btnTeamSearch');
	domTeam.btnExport = document.getElementById('btnTeamExport');
	domTeam.table = document.getElementById('teamReportTable');
	domTeam.tbody = document.getElementById('teamReportTbody');
	domTeam.mobileList = document.getElementById('teamReportMobileList');
	domTeam.resultCount = document.getElementById('teamResultCount');
	domTeam.resultCountMobile = document.getElementById('teamResultCountMobile');

	if (typeof flatpickr !== 'undefined') {
		domTeam.dateRangePicker = flatpickr(domTeam.dateRange, { mode: 'range', dateFormat: 'Y-m-d', allowInput: true });
	}

	domTeam.btnSearch.addEventListener('click', teamSearch);
	domTeam.btnExport.addEventListener('click', teamExport);

	[domTeam.table, domTeam.mobileList].forEach((el) => {
		el.addEventListener('click', (event) => {
			const btn = event.target.closest('button[data-action="view-team-details"]');
			if (!btn) return;
			openTeamReimbursementDetails(btn.getAttribute('data-reimbursement-id'));
		});
	});
};

// Router: Initialize the correct page
const initModule = () => {
	if (document.getElementById('reimbursementTable')) {
		initListPage();
	} else if (document.getElementById('expenseItemsContainer')) {
		initAddPage();
	} else if (document.getElementById('editPageMarker')) {
		initEditPage();
	} else if (document.getElementById('reimbursementRef')) {
		initDetailPage();
	}
};

$(document).ready(() => {
	initModule();
});
