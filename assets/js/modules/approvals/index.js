
const APPROVALS_ENDPOINTS = {
	pending: 'transactions/approvals/api/get/header',
	past: 'transactions/approvals/api/get/past-header',
	payment: 'transactions/approvals/api/get/payment-queue',
};

let selectedApprovalTab = 'pending';

// Per-tab cache: rows already loaded, next cursor, and whether more rows exist.
const approvalsTabState = {
	pending: { rows: [], nextCursorId: null, hasMoreRows: false, loaded: false },
	past: { rows: [], nextCursorId: null, hasMoreRows: false, loaded: false },
	payment: { rows: [], nextCursorId: null, hasMoreRows: false, loaded: false },
};

let approvals = [];
let approvalsNextCursorId = null;
let approvalsHasMoreRows = false;
let approvalsIsLoadingRows = false;

let selectedTransactionType = 'ALL';
let approvalsDesktopPage = 1;

let approvalsDateRangePicker = null;
const paymentSelectedRefs = new Set();

const APPROVALS_PAGE_SIZE = 10;

const formatPHP = (amount) => {
	const value = Number(amount || 0);
	return value.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
};

const normalizeDate = (value) => (value ? String(value) : '');

const escapeHtml = (value = '') => String(value)
	.replace(/&/g, '&amp;')
	.replace(/</g, '&lt;')
	.replace(/>/g, '&gt;')
	.replace(/"/g, '&quot;')
	.replace(/'/g, '&#39;');

const formatDisplayDate = (value) => {
	const raw = normalizeDate(value).slice(0, 10);
	if (!raw) {
		return '—';
	}
	const date = new Date(`${raw}T00:00:00`);
	if (Number.isNaN(date.getTime())) {
		return escapeHtml(raw);
	}
	return date.toLocaleDateString('en-PH', {
		year: 'numeric',
		month: 'short',
		day: '2-digit',
	});
};

const getTransactionTypeLabel = (type) => {
	if (type === 'CASH_ADVANCE') return 'Cash Advance';
	if (type === 'LIQUIDATION') return 'Liquidation';
	if (type === 'REIMBURSEMENT') return 'Reimbursement';
	return escapeHtml(normalizeDate(type));
};

const daysBetween = (fromIso, toIso) => {
	if (!fromIso || !toIso) {
		return null;
	}
	const from = new Date(`${fromIso}T00:00:00`);
	const to = new Date(`${toIso}T00:00:00`);
	if (Number.isNaN(from.getTime()) || Number.isNaN(to.getTime())) {
		return null;
	}
	return Math.round((to.getTime() - from.getTime()) / 86400000);
};

const todayIso = () => new Date().toISOString().slice(0, 10);

const normalizeApprovalRows = (rows) =>
	(rows || []).map((row) => {
		const submittedDate = normalizeDate(row.submitted_date).slice(0, 10);
		const decidedDate = normalizeDate(row.decided_date).slice(0, 10);
		return {
			approvalDetailId: Number(row.approval_detail_id || 0),
			referenceNo: normalizeDate(row.reference_no),
			transactionType: normalizeDate(row.transaction_type),
			userId: Number(row.user_id || 0),
			requestor: normalizeDate(row.requester_name),
			department: normalizeDate(row.department),
			amount: Number(row.ca_amount ?? row.lq_amount ?? 0),
			submittedDate,
			decisionStatus: normalizeDate(row.decision_status).toUpperCase(),
			decidedDate,
			paymentAction: normalizeDate(row.payment_action).toUpperCase(),
			agingDays: daysBetween(submittedDate, todayIso()),
			turnaroundDays: (() => {
				const days = daysBetween(submittedDate, decidedDate);
				return days === null ? null : Math.max(1, days);
			})(),
		};
	});

const loadApprovals = (reset = false) => {
	if (approvalsIsLoadingRows) {
		return;
	}

	const tabState = approvalsTabState[selectedApprovalTab];

	if (reset) {
		tabState.rows = [];
		tabState.nextCursorId = null;
		tabState.hasMoreRows = false;
		tabState.loaded = false;
		approvalsDesktopPage = 1;
	}

	approvalsIsLoadingRows = true;

	// Take: 0 asks the SP for the exact full result set (no artificial cap);
	// client-side handles filtering & paging from there.
	const payload = {
		Take: reset ? 0 : APPROVALS_PAGE_SIZE,
	};

	if (tabState.nextCursorId !== null) {
		payload.CursorId = tabState.nextCursorId;
	}

	const requestedTab = selectedApprovalTab;

	ajax_loader(APPROVALS_ENDPOINTS[requestedTab], payload)
		.done((response) => {
			const res = (typeof response === 'string')
				? $.parseJSON(response)
				: response;

			if (res.status !== 'success') {
				approvalsIsLoadingRows = false;
				return;
			}

			const newRows = normalizeApprovalRows(res.data || []);
			const state = approvalsTabState[requestedTab];

			state.rows = reset
				? newRows
				: state.rows.concat(newRows);

			const pagination = res.pagination || {};

			state.hasMoreRows = Boolean(pagination.hasMore);
			state.nextCursorId = state.hasMoreRows
				? (pagination.nextCursorId || null)
				: null;
			state.loaded = true;

			approvalsIsLoadingRows = false;

			if (requestedTab === selectedApprovalTab) {
				approvals = state.rows;
				refreshApprovalsList();
			}
		})
		.fail(() => {
			approvalsIsLoadingRows = false;
		});
};

const matchesDateRange = (row) => {
	const selected = approvalsDateRangePicker ? approvalsDateRangePicker.selectedDates : [];
	if (selected.length !== 2 || !row.submittedDate) return true;

	const toIso = (date) => {
		const y = date.getFullYear();
		const m = `${date.getMonth() + 1}`.padStart(2, '0');
		const d = `${date.getDate()}`.padStart(2, '0');
		return `${y}-${m}-${d}`;
	};

	const from = toIso(selected[0]);
	const to = toIso(selected[1]);
	return row.submittedDate >= from && row.submittedDate <= to;
};

const matchesApprovalKeyword = (row) => {
	const input = document.getElementById('filterKeyword');
	const keyword = (input ? input.value : '').trim().toLowerCase();
	if (!keyword) return true;
	const haystack = `${row.referenceNo} ${row.requestor} ${row.department}`.toLowerCase();
	return haystack.indexOf(keyword) !== -1;
};

const getFilteredApprovals = () => approvals
	.filter((row) => selectedTransactionType === 'ALL' || row.transactionType === selectedTransactionType)
	.filter(matchesDateRange)
	.filter(matchesApprovalKeyword);

const renderDesktopPagination = (rows) => {
	const desktopPagination = document.getElementById('desktopPagination');
	if (!desktopPagination) {
		return;
	}

	if (!rows.length) {
		desktopPagination.innerHTML = '';
		return;
	}

	const totalPages = Math.max(1, Math.ceil(rows.length / APPROVALS_PAGE_SIZE));
	if (approvalsDesktopPage > totalPages) {
		approvalsDesktopPage = totalPages;
	}

	const canPrev = approvalsDesktopPage > 1;
	const canNext = approvalsDesktopPage < totalPages;

	// Slide a fixed-size window of page numbers around the current
	// page instead of listing every page.
	const WINDOW_SIZE = 5;
	let windowStart = Math.max(1, approvalsDesktopPage - Math.floor(WINDOW_SIZE / 2));
	let windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);
	windowStart = Math.max(1, windowEnd - WINDOW_SIZE + 1);

	let pageLinks = '';
	if (windowStart > 1) {
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-action="page" data-page="1">1</a></li>`;
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
	}
	for (let page = windowStart; page <= windowEnd; page += 1) {
		const active = page === approvalsDesktopPage ? 'active' : '';
		pageLinks += `<li class="page-item ${active}"><a class="page-link" href="#" data-action="page" data-page="${page}">${page}</a></li>`;
	}
	if (windowEnd < totalPages) {
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-action="page" data-page="${totalPages}">${totalPages}</a></li>`;
	}

	desktopPagination.innerHTML = `
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
	const rows = getFilteredApprovals();
	const totalPages = Math.max(1, Math.ceil(rows.length / APPROVALS_PAGE_SIZE));
	if (targetPage < 1 || targetPage > totalPages) {
		return;
	}
	approvalsDesktopPage = targetPage;
	refreshApprovalsList();
};

const isPastTab = () => selectedApprovalTab === 'past';
const isPaymentTab = () => selectedApprovalTab === 'payment';
const isPendingTab = () => selectedApprovalTab === 'pending';
const hasPettyCashSlipButton = () => Boolean(document.getElementById('btnDownloadBatchPettyCashSlips'));
const isBatchSelectableTab = () => isPaymentTab() || isPastTab() || (isPendingTab() && hasPettyCashSlipButton());

const getAgingBadgeHtml = (days) => {
	if (days === null || days === undefined) {
		return '—';
	}
	let cls = 'kna-badge-approved';
	if (days >= 14) cls = 'kna-badge-rejected';
	else if (days >= 7) cls = 'kna-badge-pending';
	return `<span class="kna-badge ${cls}">${escapeHtml(String(days))}</span>`;
};

const getStatusBadgeHtml = (decisionStatus) => {
	if (decisionStatus === 'APPROVED') {
		return '<span class="kna-badge kna-badge-approved">Approved</span>';
	}
	if (decisionStatus === 'REJECTED') {
		return '<span class="kna-badge kna-badge-rejected">Rejected</span>';
	}
	return '—';
};

const getPaymentActionBadgeHtml = (paymentAction) => {
	if (paymentAction === 'ADVISE') {
		return '<span class="kna-badge kna-badge-pending">Needs Advisory</span>';
	}
	if (paymentAction === 'RELEASE') {
		return '<span class="kna-badge kna-badge-partial">Needs Release</span>';
	}
	return '—';
};

const getReviewUrl = (row) => {
	const base = `${base_url}transactions/approvals/review/${encodeURIComponent(row.referenceNo)}`;
	return isPastTab() ? `${base}?mode=past` : base;
};

const renderMobileCards = (pageRows) => {
	const mobileList = document.getElementById('approvalsMobileList');
	if (!mobileList) {
		return;
	}

	if (!pageRows.length) {
		const emptyLabel = isPastTab() ? 'Past' : (isPaymentTab() ? 'Payment' : 'Pending');
		mobileList.innerHTML = `<div class="text-center text-muted kna-small py-4">No ${emptyLabel} Approvals</div>`;
		return;
	}

	mobileList.innerHTML = pageRows.map((row) => `
		<div class="kna-mobile-card">
			<div class="d-flex justify-content-between align-items-start">
				<div>
					<div class="font-weight-bold">${escapeHtml(row.referenceNo)}</div>
					<div class="text-muted kna-small">${escapeHtml(getTransactionTypeLabel(row.transactionType))}</div>
				</div>
				${isPastTab() ? `<div>${getStatusBadgeHtml(row.decisionStatus)}</div>` : ''}
				${isPaymentTab() ? `<div>${getPaymentActionBadgeHtml(row.paymentAction)}</div>` : ''}
			</div>
			<div class="mt-2 kna-small">
				<div><strong>Requestor:</strong> ${escapeHtml(row.requestor)}</div>
				<div><strong>Department:</strong> ${escapeHtml(row.department)}</div>
				<div><strong>Amount:</strong> ${formatPHP(row.amount)}</div>
				<div><strong>Date:</strong> ${formatDisplayDate(row.submittedDate)}</div>
				${(!isPastTab() && !isPaymentTab()) ? `<div><strong>Aging (Days):</strong> ${getAgingBadgeHtml(row.agingDays)}</div>` : ''}
				${isPastTab() ? `<div><strong>Turnaround (Days):</strong> ${getAgingBadgeHtml(row.turnaroundDays)}</div>` : ''}
			</div>
			<div class="mt-2">
				<a
					class="btn btn-primary btn-sm btn-block"
					href="${getReviewUrl(row)}">
					${(isPastTab() || isPaymentTab()) ? 'View' : 'Review'}
				</a>
			</div>
		</div>
	`).join('');
};

const refreshApprovalsList = () => {
	const tbodyMain = document.getElementById('matrixTbodyMain');
	const tbodyAction = document.getElementById('matrixTbodyAction');
	const resultCount = document.getElementById('resultCount');
	if (!tbodyMain || !tbodyAction) {
		return;
	}

	const rows = getFilteredApprovals();
	const totalPages = Math.max(1, Math.ceil(rows.length / APPROVALS_PAGE_SIZE));
	if (approvalsDesktopPage > totalPages) {
		approvalsDesktopPage = totalPages;
	}

	const start = (approvalsDesktopPage - 1) * APPROVALS_PAGE_SIZE;
	const pageRows = rows.slice(start, start + APPROVALS_PAGE_SIZE);
	const colCount = isPastTab() ? 8 : 7;
	const checkboxColCount = isBatchSelectableTab() ? colCount + 1 : colCount;

	if (!pageRows.length) {
		const emptyLabel = isPastTab() ? 'Past' : (isPaymentTab() ? 'Payment' : 'Pending');
		tbodyMain.innerHTML = `
		<tr>
			<td colspan="${checkboxColCount}" class="text-center text-muted kna-small py-4">
				No ${emptyLabel} Approvals
			</td>
		</tr>
	`;

		tbodyAction.innerHTML = `
		<tr>
			<td></td>
		</tr>
	`;

		if (resultCount) {
			resultCount.textContent = '0 Records';
		}

		renderMobileCards([]);
		renderDesktopPagination([]);
		updatePaymentBatchBar();
		return;
	}

	tbodyMain.innerHTML = pageRows.map((row) => `
		<tr>
			${isBatchSelectableTab() ? `<td><input type="checkbox" class="payment-row-checkbox" data-ref="${escapeHtml(row.referenceNo)}" ${paymentSelectedRefs.has(row.referenceNo) ? 'checked' : ''}></td>` : ''}
			<td><strong>${escapeHtml(row.referenceNo)}</strong></td>
			<td>${escapeHtml(getTransactionTypeLabel(row.transactionType))}</td>
			<td>${escapeHtml(row.requestor)}</td>
			<td>${escapeHtml(row.department)}</td>
			<td>${formatPHP(row.amount)}</td>
			<td>${formatDisplayDate(row.submittedDate)}</td>
			${(!isPastTab() && !isPaymentTab()) ? `<td class="text-center">${getAgingBadgeHtml(row.agingDays)}</td>` : ''}
			${isPastTab() ? `<td>${getStatusBadgeHtml(row.decisionStatus)}</td>` : ''}
			${isPastTab() ? `<td class="text-center">${getAgingBadgeHtml(row.turnaroundDays)}</td>` : ''}
			${isPaymentTab() ? `<td>${getPaymentActionBadgeHtml(row.paymentAction)}</td>` : ''}
		</tr>
	`).join('');

	tbodyAction.innerHTML = pageRows.map((row) => `
		<tr>
			<td>
				<a
					class="btn btn-outline-primary btn-xs kna-small py-1 px-2"
					href="${getReviewUrl(row)}">
					${(isPastTab() || isPaymentTab()) ? 'View' : 'Review'}
				</a>
			</td>
		</tr>
	`).join('');

	if (resultCount) {
		resultCount.textContent = `${rows.length} Record${rows.length === 1 ? '' : 's'}`;
	}

	renderMobileCards(pageRows);
	renderDesktopPagination(rows);
	updatePaymentBatchBar();
};

const updateApprovalTabChrome = () => {
	const pageTitle = document.getElementById('approvalsPageTitle');
	const resultLabel = document.getElementById('resultLabel');
	const statusColumnHeader = document.getElementById('statusColumnHeader');
	const paymentActionColumnHeader = document.getElementById('paymentActionColumnHeader');
	const agingColumnHeader = document.getElementById('agingColumnHeader');
	const turnaroundColumnHeader = document.getElementById('turnaroundColumnHeader');

	if (pageTitle) {
		pageTitle.textContent = isPastTab() ? 'Past Approvals' : (isPaymentTab() ? 'For Payment' : 'Pending Approvals');
	}
	if (resultLabel) {
		resultLabel.textContent = isPastTab() ? 'Already Reviewed' : (isPaymentTab() ? 'Awaiting Payment Action' : 'Awaiting Your Action');
	}
	if (statusColumnHeader) {
		statusColumnHeader.classList.toggle('d-none', !isPastTab());
	}
	if (paymentActionColumnHeader) {
		paymentActionColumnHeader.classList.toggle('d-none', !isPaymentTab());
	}
	if (agingColumnHeader) {
		agingColumnHeader.classList.toggle('d-none', isPastTab() || isPaymentTab());
	}
	if (turnaroundColumnHeader) {
		turnaroundColumnHeader.classList.toggle('d-none', !isPastTab());
	}

	const paymentCheckboxColumnHeader = document.getElementById('paymentCheckboxColumnHeader');
	if (paymentCheckboxColumnHeader) {
		paymentCheckboxColumnHeader.classList.toggle('d-none', !isBatchSelectableTab());
	}
	const paymentBatchBar = document.getElementById('paymentBatchBar');
	if (paymentBatchBar) {
		paymentBatchBar.classList.toggle('d-none', !isBatchSelectableTab());
	}
	const paymentActionCheckboxes = document.getElementById('paymentActionCheckboxes');
	if (paymentActionCheckboxes) {
		if (isPaymentTab()) {
			paymentActionCheckboxes.classList.add('d-flex');
			paymentActionCheckboxes.style.removeProperty('display');
		} else {
			paymentActionCheckboxes.classList.remove('d-flex');
			paymentActionCheckboxes.style.display = 'none';
		}
	}
	const btnProcessBatchPaymentEl = document.getElementById('btnProcessBatchPayment');
	if (btnProcessBatchPaymentEl) {
		btnProcessBatchPaymentEl.classList.toggle('d-none', !isPaymentTab());
	}
	const btnDownloadBatchPettyCashSlipsEl = document.getElementById('btnDownloadBatchPettyCashSlips');
	if (btnDownloadBatchPettyCashSlipsEl) {
		btnDownloadBatchPettyCashSlipsEl.classList.toggle('d-none', !(isPastTab() || isPendingTab()));
	}
	if (!isBatchSelectableTab()) {
		paymentSelectedRefs.clear();
	}
	updatePaymentBatchBar();
};

const updatePaymentBatchBar = () => {
	const countEl = document.getElementById('paymentSelectedCount');
	const btn = document.getElementById('btnProcessBatchPayment');
	if (countEl) {
		countEl.textContent = String(paymentSelectedRefs.size);
	}
	if (btn) {
		btn.disabled = paymentSelectedRefs.size === 0;
	}
	const btnDownloadSlips = document.getElementById('btnDownloadBatchPettyCashSlips');
	if (btnDownloadSlips) {
		btnDownloadSlips.disabled = paymentSelectedRefs.size === 0;
	}
	const selectAll = document.getElementById('paymentSelectAll');
	if (selectAll) {
		const pageCheckboxes = Array.from(document.querySelectorAll('.payment-row-checkbox'));
		selectAll.checked = pageCheckboxes.length > 0 && pageCheckboxes.every((cb) => cb.checked);
	}
};

const switchApprovalTab = (tab) => {
	if (tab !== 'pending' && tab !== 'past' && tab !== 'payment') {
		return;
	}

	selectedApprovalTab = tab;
	approvalsDesktopPage = 1;
	paymentSelectedRefs.clear();
	updateApprovalTabChrome();

	const tabState = approvalsTabState[tab];
	if (tabState.loaded) {
		approvals = tabState.rows;
		refreshApprovalsList();
		return;
	}

	loadApprovals(true);
};

const initListPage = () => {
	const listPage = document.getElementById('approvalsListPage');
	if (!listPage) {
		return;
	}

	document.querySelectorAll('.kna-tab[data-approval-tab]').forEach((tab) => {
		tab.addEventListener('click', () => {
			document.querySelectorAll('.kna-tab[data-approval-tab]').forEach((el) => {
				el.classList.remove('is-active');
			});
			tab.classList.add('is-active');
			switchApprovalTab(tab.getAttribute('data-approval-tab') || 'pending');
		});
	});

	const filterTransactionType = document.getElementById('filterTransactionType');
	if (filterTransactionType) {
		filterTransactionType.addEventListener('change', () => {
			selectedTransactionType = filterTransactionType.value || 'ALL';
			approvalsDesktopPage = 1;
			paymentSelectedRefs.clear();
			refreshApprovalsList();
			updatePaymentBatchBar();
		});
	}

	const filterKeyword = document.getElementById('filterKeyword');
	if (filterKeyword) {
		filterKeyword.addEventListener('input', () => {
			approvalsDesktopPage = 1;
			paymentSelectedRefs.clear();
			refreshApprovalsList();
			updatePaymentBatchBar();
		});
	}

	const filterDateRange = document.getElementById('filterDateRange');
	if (filterDateRange && typeof flatpickr !== 'undefined') {
		approvalsDateRangePicker = flatpickr(filterDateRange, {
			mode: 'range',
			dateFormat: 'Y-m-d',
			allowInput: true,
			onChange: (selectedDates) => {
				if (selectedDates.length === 0 || selectedDates.length === 2) {
					approvalsDesktopPage = 1;
					paymentSelectedRefs.clear();
					refreshApprovalsList();
					updatePaymentBatchBar();
				}
			},
		});
	}

	const btnResetApprovalFilters = document.getElementById('btnResetApprovalFilters');
	if (btnResetApprovalFilters) {
		btnResetApprovalFilters.addEventListener('click', () => {
			if (approvalsDateRangePicker) approvalsDateRangePicker.clear();
			if (filterTransactionType) filterTransactionType.value = 'ALL';
			if (filterKeyword) filterKeyword.value = '';
			selectedTransactionType = 'ALL';
			approvalsDesktopPage = 1;
			paymentSelectedRefs.clear();
			refreshApprovalsList();
			updatePaymentBatchBar();
		});
	}

	const desktopPagination = document.getElementById('desktopPagination');
	if (desktopPagination) {
		desktopPagination.addEventListener('click', (event) => {
			const btn = event.target.closest('a[data-action]');
			if (!btn) {
				return;
			}
			event.preventDefault();
			if (btn.getAttribute('data-action') === 'prev') {
				goToDesktopPage(approvalsDesktopPage - 1);
				return;
			}
			if (btn.getAttribute('data-action') === 'next') {
				goToDesktopPage(approvalsDesktopPage + 1);
				return;
			}
			const page = Number(btn.getAttribute('data-page'));
			if (page) {
				goToDesktopPage(page);
			}
		});
	}

	document.addEventListener('change', (e) => {
		if (e.target.classList && e.target.classList.contains('payment-row-checkbox')) {
			const ref = e.target.getAttribute('data-ref');
			if (e.target.checked) {
				paymentSelectedRefs.add(ref);
			} else {
				paymentSelectedRefs.delete(ref);
			}
			updatePaymentBatchBar();
		}
	});

	const paymentSelectAll = document.getElementById('paymentSelectAll');
	if (paymentSelectAll) {
		paymentSelectAll.addEventListener('change', () => {
			const checked = paymentSelectAll.checked;
			document.querySelectorAll('.payment-row-checkbox').forEach((cb) => {
				cb.checked = checked;
				const ref = cb.getAttribute('data-ref');
				if (checked) {
					paymentSelectedRefs.add(ref);
				} else {
					paymentSelectedRefs.delete(ref);
				}
			});
			updatePaymentBatchBar();
		});
	}

	const btnProcessBatchPayment = document.getElementById('btnProcessBatchPayment');
	if (btnProcessBatchPayment) {
		btnProcessBatchPayment.addEventListener('click', () => {
			const paymentDoAdviseEl = document.getElementById('paymentDoAdvise');
			const paymentDoReleaseEl = document.getElementById('paymentDoRelease');
			const doAdvise = Boolean(paymentDoAdviseEl && paymentDoAdviseEl.checked);
			const doRelease = Boolean(paymentDoReleaseEl && paymentDoReleaseEl.checked);

			if (!doAdvise && !doRelease) {
				Swal.fire({ icon: 'warning', title: 'Select an action', text: 'Tick Payment Advisory and/or Payment Release before processing.' });
				return;
			}
			if (paymentSelectedRefs.size === 0) {
				return;
			}

			const actionLabel = doAdvise && doRelease ? 'advise and release' : (doAdvise ? 'advise' : 'release');
			Swal.fire({
				icon: 'question',
				title: 'Confirm Batch Action',
				text: `Are you sure you want to ${actionLabel} payment for ${paymentSelectedRefs.size} transaction(s)?`,
				showCancelButton: true,
				confirmButtonText: 'Yes',
				cancelButtonText: 'No',
				reverseButtons: true,
			}).then((result) => {
				if (!result.isConfirmed) return;

				btnProcessBatchPayment.disabled = true;
				ajax_loader_loading('transactions/approvals/api/payment/bulk-action', {
					reference_numbers: Array.from(paymentSelectedRefs),
					do_advise: doAdvise ? 1 : 0,
					do_release: doRelease ? 1 : 0,
				}).done((response) => {
					const res = (typeof response === 'string') ? $.parseJSON(response) : response;
					if (res.status !== 'success') {
						Swal.fire({ icon: 'error', title: 'Failed', text: res.response || 'Batch payment action failed.' });
						return;
					}

					const data = res.data || {};
					const advisedCount = (data.advised || []).length;
					const releasedCount = (data.released || []).length;
					const errors = data.errors || [];

					let summary = '';
					if (doAdvise) summary += `Advised: ${advisedCount}. `;
					if (doRelease) summary += `Released: ${releasedCount}. `;
					if (errors.length) summary += `${errors.length} failed.`;

					Swal.fire({
						icon: errors.length ? 'warning' : 'success',
						title: 'Batch Action Complete',
						html: summary + (errors.length ? '<br><br>' + errors.map((e) => `${escapeHtml(e.reference_no)} (${escapeHtml(e.action)}): ${escapeHtml(e.message)}`).join('<br>') : ''),
					});

					paymentSelectedRefs.clear();
					loadApprovals(true);
				}).fail(() => {
					Swal.fire({ icon: 'error', title: 'Error', text: 'Server error during batch payment action.' });
				}).always(() => {
					btnProcessBatchPayment.disabled = paymentSelectedRefs.size === 0;
				});
			});
		});
	}

	const openPettyCashSlipPreview = (referenceNumbers) => {
		const actionUrl = `${base_url}transactions/approvals/petty-cash-slips-batch`;
		if (typeof window.openPdfPreviewByForm === 'function') {
			window.openPdfPreviewByForm(actionUrl, { 'reference_numbers[]': referenceNumbers });
			return;
		}

		const form = document.createElement('form');
		form.method = 'POST';
		form.action = actionUrl;
		form.target = '_blank';

		referenceNumbers.forEach((ref) => {
			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'reference_numbers[]';
			input.value = ref;
			form.appendChild(input);
		});

		document.body.appendChild(form);
		form.submit();
		form.remove();
	};

	const btnDownloadBatchPettyCashSlips = document.getElementById('btnDownloadBatchPettyCashSlips');
	if (btnDownloadBatchPettyCashSlips) {
		btnDownloadBatchPettyCashSlips.addEventListener('click', () => {
			if (paymentSelectedRefs.size === 0) {
				return;
			}

			ajax_loader_loading('transactions/approvals/api/petty-cash-slips/eligibility', {
				reference_numbers: Array.from(paymentSelectedRefs),
			}).done((response) => {
				const res = (typeof response === 'string') ? $.parseJSON(response) : response;
				if (res.status !== 'success') {
					Swal.fire({ icon: 'error', title: 'Failed', text: res.response || 'Could not check petty cash slip eligibility.' });
					return;
				}

				const data = res.data || {};
				const allowed = data.allowed || [];
				const skipped = data.skipped || [];

				const refListHtml = (refs) => {
					const shown = refs.slice(0, 3).map(escapeHtml).join('<br>');
					const more = refs.length > 3 ? `<br>+${refs.length - 3} more` : '';
					return `<b>${shown}</b>${more}`;
				};

				if (allowed.length === 0) {
					Swal.fire({
						icon: 'warning',
						title: 'Not Allowed',
						html: `You can't generate petty cash slips for:<br>${refListHtml(skipped)}`,
					});
					return;
				}

				if (skipped.length > 0) {
					Swal.fire({
						icon: 'info',
						title: `${skipped.length} Will Be Skipped`,
						html: `Not allowed for:<br>${refListHtml(skipped)}<br><br>Generate for the other ${allowed.length}?`,
						showCancelButton: true,
						confirmButtonText: 'Continue',
						cancelButtonText: 'Cancel',
						reverseButtons: true,
					}).then((result) => {
						if (result.isConfirmed) openPettyCashSlipPreview(allowed);
					});
					return;
				}

				openPettyCashSlipPreview(allowed);
			}).fail(() => {
				Swal.fire({ icon: 'error', title: 'Error', text: 'Server error while checking petty cash slip eligibility.' });
			});
		});
	}

	updateApprovalTabChrome();
	loadApprovals(true);
};

const initModule = () => {
	if (document.getElementById('approvalsListPage')) {
		initListPage();
	} else if (document.getElementById('approvalRef') && typeof initReviewPage === 'function') {
		initReviewPage();
	}
};

$(document).ready(() => {
	initModule();
});
