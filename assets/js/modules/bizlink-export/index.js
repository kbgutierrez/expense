
const BIZLINK_EXPORT_ENDPOINT = 'transactions/bizlink-export/api/get/header';
const BIZLINK_EXPORT_PAGE_SIZE = 10;

let bizlinkExportRows = [];
let bizlinkExportNextCursorId = null;
let bizlinkExportIsLoadingRows = false;
let bizlinkExportLoaded = false;

let selectedTransactionType = 'ALL';
let bizlinkExportDesktopPage = 1;
let bizlinkExportDateRangePicker = null;
let bizlinkSortKey = '';
let bizlinkSortDir = 'asc';
const paymentSelectedRefs = new Set();

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

const normalizeBizlinkExportRows = (rows) =>
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
			amount: Number(row.approved_amount ?? row.ca_amount ?? row.lq_amount ?? 0),
			submittedDate,
			decisionStatus: normalizeDate(row.decision_status).toUpperCase(),
			decidedDate,
			turnaroundDays: (() => {
				const days = daysBetween(submittedDate, decidedDate);
				return days === null ? null : Math.max(1, days);
			})(),
			isBizlinkExported: Number(row.is_bizlink_exported || 0) === 1,
		};
	});

const loadBizlinkExportRows = (reset = false) => {
	if (bizlinkExportIsLoadingRows) {
		return;
	}

	if (reset) {
		bizlinkExportRows = [];
		bizlinkExportNextCursorId = null;
		bizlinkExportLoaded = false;
		bizlinkExportDesktopPage = 1;
	}

	bizlinkExportIsLoadingRows = true;

	const payload = {
		Take: reset ? 0 : BIZLINK_EXPORT_PAGE_SIZE,
	};

	if (bizlinkExportNextCursorId !== null) {
		payload.CursorId = bizlinkExportNextCursorId;
	}

	ajax_loader(BIZLINK_EXPORT_ENDPOINT, payload)
		.done((response) => {
			const res = (typeof response === 'string') ? $.parseJSON(response) : response;

			if (res.status !== 'success') {
				bizlinkExportIsLoadingRows = false;
				return;
			}

			const newRows = normalizeBizlinkExportRows(res.data || []);
			bizlinkExportRows = reset ? newRows : bizlinkExportRows.concat(newRows);

			const pagination = res.pagination || {};
			bizlinkExportNextCursorId = pagination.hasMore ? (pagination.nextCursorId || null) : null;
			bizlinkExportLoaded = true;
			bizlinkExportIsLoadingRows = false;

			refreshBizlinkExportList();
		})
		.fail(() => {
			bizlinkExportIsLoadingRows = false;
		});
};

const matchesDateRange = (row) => {
	const selected = bizlinkExportDateRangePicker ? bizlinkExportDateRangePicker.selectedDates : [];
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

const matchesKeyword = (row) => {
	const input = document.getElementById('filterKeyword');
	const keyword = (input ? input.value : '').trim().toLowerCase();
	if (!keyword) return true;
	const haystack = `${row.referenceNo} ${row.requestor} ${row.department}`.toLowerCase();
	return haystack.indexOf(keyword) !== -1;
};

const getFilteredBizlinkExportRows = () => bizlinkExportRows
	.filter((row) => selectedTransactionType === 'ALL' || row.transactionType === selectedTransactionType)
	.filter(matchesDateRange)
	.filter(matchesKeyword);

const getBizlinkSortValue = (row, key) => {
	if (key === 'transactionType') {
		return getTransactionTypeLabel(row.transactionType);
	}
	const value = row[key];
	return value === undefined ? null : value;
};

const sortBizlinkExportRows = (rows) => {
	if (!bizlinkSortKey) {
		return rows;
	}
	const direction = bizlinkSortDir === 'desc' ? -1 : 1;
	return rows.slice().sort((a, b) => {
		const left = getBizlinkSortValue(a, bizlinkSortKey);
		const right = getBizlinkSortValue(b, bizlinkSortKey);
		if ((left === null || left === '') && (right === null || right === '')) return 0;
		if (left === null || left === '') return 1;
		if (right === null || right === '') return -1;
		if (typeof left === 'number' && typeof right === 'number') return (left - right) * direction;
		return String(left).localeCompare(String(right), undefined, { numeric: true, sensitivity: 'base' }) * direction;
	});
};

const updateBizlinkSortIndicators = () => {
	document.querySelectorAll('.kna-table-main thead th[data-sort-key]').forEach((th) => {
		const active = th.getAttribute('data-sort-key') === bizlinkSortKey;
		th.classList.toggle('is-sorted-asc', active && bizlinkSortDir === 'asc');
		th.classList.toggle('is-sorted-desc', active && bizlinkSortDir === 'desc');
		th.setAttribute('aria-sort', active ? (bizlinkSortDir === 'asc' ? 'ascending' : 'descending') : 'none');
	});
};

const renderDesktopPagination = (rows) => {
	const desktopPagination = document.getElementById('desktopPagination');
	if (!desktopPagination) {
		return;
	}

	if (!rows.length) {
		desktopPagination.innerHTML = '';
		return;
	}

	const totalPages = Math.max(1, Math.ceil(rows.length / BIZLINK_EXPORT_PAGE_SIZE));
	if (bizlinkExportDesktopPage > totalPages) {
		bizlinkExportDesktopPage = totalPages;
	}

	const canPrev = bizlinkExportDesktopPage > 1;
	const canNext = bizlinkExportDesktopPage < totalPages;

	const WINDOW_SIZE = 5;
	let windowStart = Math.max(1, bizlinkExportDesktopPage - Math.floor(WINDOW_SIZE / 2));
	let windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);
	windowStart = Math.max(1, windowEnd - WINDOW_SIZE + 1);

	let pageLinks = '';
	if (windowStart > 1) {
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-action="page" data-page="1">1</a></li>`;
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
	}
	for (let page = windowStart; page <= windowEnd; page += 1) {
		const active = page === bizlinkExportDesktopPage ? 'active' : '';
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
	const rows = sortBizlinkExportRows(getFilteredBizlinkExportRows());
	const totalPages = Math.max(1, Math.ceil(rows.length / BIZLINK_EXPORT_PAGE_SIZE));
	if (targetPage < 1 || targetPage > totalPages) {
		return;
	}
	bizlinkExportDesktopPage = targetPage;
	refreshBizlinkExportList();
};

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

const getReviewUrl = (row) => `${base_url}transactions/approvals/review/${encodeURIComponent(row.referenceNo)}?mode=past`;

const exportedBadgeHtml = (row) => (row.isBizlinkExported
	? ' <sup><span class="badge badge-info" title="Already included in a BizLink batch" style="font-size:8px;vertical-align:top;">Exported</span></sup>'
	: '');

const renderMobileCards = (pageRows) => {
	const mobileList = document.getElementById('approvalsMobileList');
	if (!mobileList) {
		return;
	}

	if (!pageRows.length) {
		mobileList.innerHTML = '<div class="text-center text-muted kna-small py-4">No Records</div>';
		return;
	}

	mobileList.innerHTML = pageRows.map((row) => `
		<div class="kna-mobile-card">
			<div class="d-flex justify-content-between align-items-start">
				<div>
					<div class="font-weight-bold">${escapeHtml(row.referenceNo)}${exportedBadgeHtml(row)}</div>
					<div class="text-muted kna-small">${escapeHtml(getTransactionTypeLabel(row.transactionType))}</div>
				</div>
				<div>${getStatusBadgeHtml(row.decisionStatus)}</div>
			</div>
			<div class="mt-2 kna-small">
				<div><strong>Requestor:</strong> ${escapeHtml(row.requestor)}</div>
				<div><strong>Department:</strong> ${escapeHtml(row.department)}</div>
				<div><strong>Amount:</strong> ${formatPHP(row.amount)}</div>
				<div><strong>Date:</strong> ${formatDisplayDate(row.submittedDate)}</div>
				<div><strong>Turnaround (Days):</strong> ${getAgingBadgeHtml(row.turnaroundDays)}</div>
			</div>
			<div class="mt-2">
				<a
					class="btn btn-primary btn-sm btn-block"
					href="${getReviewUrl(row)}">
					View
				</a>
			</div>
		</div>
	`).join('');
};

const updateBizlinkExportBar = () => {
	const countEl = document.getElementById('paymentSelectedCount');
	if (countEl) {
		countEl.textContent = String(paymentSelectedRefs.size);
	}
	const btnDownloadBizlink = document.getElementById('btnDownloadBizlinkExport');
	if (btnDownloadBizlink) {
		btnDownloadBizlink.disabled = paymentSelectedRefs.size === 0;
	}
	const selectAll = document.getElementById('paymentSelectAll');
	if (selectAll) {
		const pageCheckboxes = Array.from(document.querySelectorAll('.payment-row-checkbox'));
		selectAll.checked = pageCheckboxes.length > 0 && pageCheckboxes.every((cb) => cb.checked);
	}
};

const refreshBizlinkExportList = () => {
	const tbodyMain = document.getElementById('matrixTbodyMain');
	const tbodyAction = document.getElementById('matrixTbodyAction');
	const resultCount = document.getElementById('resultCount');
	if (!tbodyMain || !tbodyAction) {
		return;
	}

	const rows = sortBizlinkExportRows(getFilteredBizlinkExportRows());
	const totalPages = Math.max(1, Math.ceil(rows.length / BIZLINK_EXPORT_PAGE_SIZE));
	if (bizlinkExportDesktopPage > totalPages) {
		bizlinkExportDesktopPage = totalPages;
	}

	const start = (bizlinkExportDesktopPage - 1) * BIZLINK_EXPORT_PAGE_SIZE;
	const pageRows = rows.slice(start, start + BIZLINK_EXPORT_PAGE_SIZE);
	const colCount = 9;

	if (!pageRows.length) {
		tbodyMain.innerHTML = `
		<tr>
			<td colspan="${colCount}" class="text-center text-muted kna-small py-4">
				No Records
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
		updateBizlinkExportBar();
		return;
	}

	tbodyMain.innerHTML = pageRows.map((row) => `
		<tr>
			<td><input type="checkbox" class="payment-row-checkbox" data-ref="${escapeHtml(row.referenceNo)}" ${paymentSelectedRefs.has(row.referenceNo) ? 'checked' : ''}></td>
			<td><strong>${escapeHtml(row.referenceNo)}</strong>${exportedBadgeHtml(row)}</td>
			<td>${escapeHtml(getTransactionTypeLabel(row.transactionType))}</td>
			<td>${escapeHtml(row.requestor)}</td>
			<td>${escapeHtml(row.department)}</td>
			<td>${formatPHP(row.amount)}</td>
			<td>${formatDisplayDate(row.submittedDate)}</td>
			<td>${getStatusBadgeHtml(row.decisionStatus)}</td>
			<td class="text-center">${getAgingBadgeHtml(row.turnaroundDays)}</td>
		</tr>
	`).join('');

	tbodyAction.innerHTML = pageRows.map((row) => `
		<tr>
			<td>
				<a
					class="btn btn-outline-primary btn-xs kna-small py-1 px-2"
					href="${getReviewUrl(row)}">
					View
				</a>
			</td>
		</tr>
	`).join('');

	if (resultCount) {
		resultCount.textContent = `${rows.length} Record${rows.length === 1 ? '' : 's'}`;
	}

	renderMobileCards(pageRows);
	renderDesktopPagination(rows);
	updateBizlinkExportBar();
};

const downloadBizlinkExportBatch = (referenceNumbers, payrollDate, batchNumber) => {
	const formData = new FormData();
	referenceNumbers.forEach((ref) => formData.append('reference_numbers[]', ref));
	formData.append('PayrollDate', payrollDate);
	formData.append('BatchNumber', batchNumber);

	$('#modal-loading').show();
	fetch(`${base_url}transactions/bizlink-export/generate-batch`, {
		method: 'POST',
		body: formData,
		credentials: 'same-origin',
	}).then(async (response) => {
		const contentType = response.headers.get('Content-Type') || '';
		if (contentType.indexOf('application/json') !== -1) {
			const res = await response.json();
			Swal.fire({ icon: 'error', title: 'Failed', text: res.response || 'Failed to generate the text file.' });
			return;
		}

		const disposition = response.headers.get('Content-Disposition') || '';
		const match = disposition.match(/filename="?([^"]+)"?/);
		const filename = match ? match[1] : 'BizLink_export.txt';

		const blob = await response.blob();
		const url = URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = filename;
		document.body.appendChild(link);
		link.click();
		link.remove();
		URL.revokeObjectURL(url);

		Swal.fire({ icon: 'success', title: 'Generated', text: `${filename} downloaded.`, timer: 1500, showConfirmButton: false });

		paymentSelectedRefs.clear();
		loadBizlinkExportRows(true);
		if ($('#modalBizlinkBatchHistory').hasClass('show')) {
			loadBizlinkBatchHistory();
		}
	}).catch(() => {
		Swal.fire({ icon: 'error', title: 'Error', text: 'Server error while generating the text file.' });
	}).finally(() => {
		$('#modal-loading').hide();
	});
};

const promptBizlinkExportBatch = (referenceNumbers) => {
	const today = new Date().toISOString().slice(0, 10);
	Swal.fire({
		title: 'Generate BizLink Text File',
		html: `
			<div class="text-left kna-small">
				<label class="kna-form-label d-block">Payroll Date</label>
				<input type="date" id="swalBizlinkPayrollDate" class="swal2-input" style="width:100%;margin:0 0 12px;" value="${today}">
				<label class="kna-form-label d-block">Batch Number</label>
				<input type="number" id="swalBizlinkBatchNumber" class="swal2-input" style="width:100%;margin:0;" min="1" max="99" value="1">
			</div>
		`,
		showCancelButton: true,
		confirmButtonText: 'Generate',
		cancelButtonText: 'Cancel',
		reverseButtons: true,
		focusConfirm: false,
		preConfirm: () => {
			const payrollDate = document.getElementById('swalBizlinkPayrollDate').value;
			const batchNumber = parseInt(document.getElementById('swalBizlinkBatchNumber').value, 10);
			if (!payrollDate) {
				Swal.showValidationMessage('Payroll Date is required.');
				return false;
			}
			if (!batchNumber || batchNumber < 1 || batchNumber > 99) {
				Swal.showValidationMessage('Batch Number must be between 1 and 99.');
				return false;
			}
			return { payrollDate, batchNumber: String(batchNumber).padStart(2, '0') };
		},
	}).then((result) => {
		if (result.isConfirmed) {
			downloadBizlinkExportBatch(referenceNumbers, result.value.payrollDate, result.value.batchNumber);
		}
	});
};

const bizlinkBatchStatusBadge = (row) => (row.is_void
	? '<span class="badge badge-secondary">Voided</span>'
	: '<span class="badge badge-success">Active</span>');

const loadBizlinkBatchHistory = () => {
	const tbody = document.getElementById('bizlinkBatchHistoryTbody');
	if (!tbody) return;
	tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted kna-small py-3">Loading...</td></tr>';

	ajax_loader_loading('transactions/bizlink-export/api/batches', {}).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res.status !== 'success') {
			tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted kna-small py-3">${escapeHtml(res.response || 'Failed to load batch history.')}</td></tr>`;
			return;
		}

		const rows = res.data || [];
		if (rows.length === 0) {
			tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted kna-small py-3">No batches generated yet.</td></tr>';
			return;
		}

		tbody.innerHTML = rows.map((row) => {
			const canVoid = !!row.can_void;
			return `
				<tr>
					<td>${escapeHtml(formatDisplayDate(row.payroll_date))}</td>
					<td>${escapeHtml(row.batch_number)}</td>
					<td>${escapeHtml(String(row.record_count))}</td>
					<td>${formatPHP(row.total_debit_amount)}</td>
					<td>${escapeHtml(row.generated_by_name || '')}</td>
					<td>${bizlinkBatchStatusBadge(row)}</td>
					<td>${row.is_void ? escapeHtml(row.void_reason || 'No reason given') : ''}</td>
					<td class="text-center">
						${canVoid ? `<button type="button" class="btn btn-outline-danger btn-xs kna-small py-1 px-2 btnVoidBizlinkBatch" data-id="${row.id}"><i class="fas fa-ban mr-1"></i>Void</button>` : ''}
					</td>
				</tr>
			`;
		}).join('');

		tbody.querySelectorAll('.btnVoidBizlinkBatch').forEach((btn) => {
			btn.addEventListener('click', () => {
				const batchId = btn.getAttribute('data-id');
				$(document).off('focusin.bs.modal');
				Swal.fire({
					title: 'Void this batch?',
					html: 'This lets you regenerate the same Payroll Date and Batch Number, and frees up its transactions for a new export. Please state why:',
					input: 'text',
					inputPlaceholder: 'Reason for voiding',
					showCancelButton: true,
					confirmButtonText: 'Void Batch',
					cancelButtonText: 'Cancel',
					reverseButtons: true,
					inputValidator: (value) => (!value ? 'A reason is required.' : undefined),
				}).then((result) => {
					if (!result.isConfirmed) return;
					ajax_loader_loading('transactions/bizlink-export/api/void', {
						BatchId: batchId,
						Reason: result.value,
					}).done((voidResponse) => {
						const voidRes = (typeof voidResponse === 'string') ? $.parseJSON(voidResponse) : voidResponse;
						if (voidRes.status !== 'success') {
							Swal.fire({ icon: 'error', title: 'Failed', text: voidRes.response || 'Failed to void batch.' });
							return;
						}
						Swal.fire({ icon: 'success', title: 'Voided', timer: 1200, showConfirmButton: false });
						loadBizlinkBatchHistory();
						loadBizlinkExportRows(true);
					}).fail(() => {
						Swal.fire({ icon: 'error', title: 'Error', text: 'Server error while voiding batch.' });
					});
				});
			});
		});
	}).fail(() => {
		tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted kna-small py-3">Server error while loading batch history.</td></tr>';
	});
};

const initBizlinkExportPage = () => {
	const listPage = document.getElementById('bizlinkExportListPage');
	if (!listPage) {
		return;
	}

	document.querySelectorAll('.kna-table-main thead th[data-sort-key]').forEach((th) => {
		th.addEventListener('click', () => {
			const key = th.getAttribute('data-sort-key');
			if (bizlinkSortKey === key) {
				if (bizlinkSortDir === 'asc') {
					bizlinkSortDir = 'desc';
				} else {
					bizlinkSortKey = '';
					bizlinkSortDir = 'asc';
				}
			} else {
				bizlinkSortKey = key;
				bizlinkSortDir = 'asc';
			}
			bizlinkExportDesktopPage = 1;
			updateBizlinkSortIndicators();
			refreshBizlinkExportList();
		});
	});

	const filterTransactionType = document.getElementById('filterTransactionType');
	if (filterTransactionType) {
		filterTransactionType.addEventListener('change', () => {
			selectedTransactionType = filterTransactionType.value || 'ALL';
			bizlinkExportDesktopPage = 1;
			paymentSelectedRefs.clear();
			refreshBizlinkExportList();
		});
	}

	const filterKeyword = document.getElementById('filterKeyword');
	if (filterKeyword) {
		filterKeyword.addEventListener('input', () => {
			bizlinkExportDesktopPage = 1;
			paymentSelectedRefs.clear();
			refreshBizlinkExportList();
		});
	}

	const filterDateRange = document.getElementById('filterDateRange');
	if (filterDateRange && typeof flatpickr !== 'undefined') {
		bizlinkExportDateRangePicker = flatpickr(filterDateRange, {
			mode: 'range',
			dateFormat: 'Y-m-d',
			allowInput: true,
			onChange: (selectedDates) => {
				if (selectedDates.length === 0 || selectedDates.length === 2) {
					bizlinkExportDesktopPage = 1;
					paymentSelectedRefs.clear();
					refreshBizlinkExportList();
				}
			},
		});
	}

	const btnResetApprovalFilters = document.getElementById('btnResetApprovalFilters');
	if (btnResetApprovalFilters) {
		btnResetApprovalFilters.addEventListener('click', () => {
			if (bizlinkExportDateRangePicker) bizlinkExportDateRangePicker.clear();
			if (filterTransactionType) filterTransactionType.value = 'ALL';
			if (filterKeyword) filterKeyword.value = '';
			selectedTransactionType = 'ALL';
			bizlinkExportDesktopPage = 1;
			paymentSelectedRefs.clear();
			refreshBizlinkExportList();
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
				goToDesktopPage(bizlinkExportDesktopPage - 1);
				return;
			}
			if (btn.getAttribute('data-action') === 'next') {
				goToDesktopPage(bizlinkExportDesktopPage + 1);
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
			updateBizlinkExportBar();
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
			updateBizlinkExportBar();
		});
	}

	const btnDownloadBizlinkExport = document.getElementById('btnDownloadBizlinkExport');
	if (btnDownloadBizlinkExport) {
		btnDownloadBizlinkExport.addEventListener('click', () => {
			if (paymentSelectedRefs.size === 0) {
				return;
			}

			ajax_loader_loading('transactions/bizlink-export/api/eligibility', {
				reference_numbers: Array.from(paymentSelectedRefs),
			}).done((response) => {
				const res = (typeof response === 'string') ? $.parseJSON(response) : response;
				if (res.status !== 'success') {
					Swal.fire({ icon: 'error', title: 'Failed', text: res.response || 'Could not check text file export eligibility.' });
					return;
				}

				const data = res.data || {};
				const allowed = data.allowed || [];
				const skipped = data.skipped || [];
				const alreadyExported = data.already_exported || [];

				const refListHtml = (refs) => {
					const shown = refs.slice(0, 3).map(escapeHtml).join('<br>');
					const more = refs.length > 3 ? `<br>+${refs.length - 3} more` : '';
					return `<b>${shown}</b>${more}`;
				};

				const reasonBlocks = [];
				if (skipped.length > 0) {
					reasonBlocks.push(`Not allowed (no BizLink Export permission for these):<br>${refListHtml(skipped)}`);
				}
				if (alreadyExported.length > 0) {
					reasonBlocks.push(`Already included in a previous batch (void that batch first to redo):<br>${refListHtml(alreadyExported)}`);
				}

				if (allowed.length === 0) {
					Swal.fire({
						icon: 'warning',
						title: 'Not Allowed',
						html: `You can't generate the text file for the selected transaction(s).<br><br>${reasonBlocks.join('<br><br>')}`,
					});
					return;
				}

				if (reasonBlocks.length > 0) {
					Swal.fire({
						icon: 'info',
						title: `${skipped.length + alreadyExported.length} Will Be Skipped`,
						html: `${reasonBlocks.join('<br><br>')}<br><br>Generate for the other ${allowed.length}?`,
						showCancelButton: true,
						confirmButtonText: 'Continue',
						cancelButtonText: 'Cancel',
						reverseButtons: true,
					}).then((result) => {
						if (result.isConfirmed) promptBizlinkExportBatch(allowed);
					});
					return;
				}

				promptBizlinkExportBatch(allowed);
			}).fail(() => {
				Swal.fire({ icon: 'error', title: 'Error', text: 'Server error while checking text file export eligibility.' });
			});
		});
	}

	const btnOpenBizlinkBatchHistory = document.getElementById('btnOpenBizlinkBatchHistory');
	if (btnOpenBizlinkBatchHistory) {
		btnOpenBizlinkBatchHistory.addEventListener('click', () => {
			loadBizlinkBatchHistory();
			$('#modalBizlinkBatchHistory').modal('show');
		});
	}

	loadBizlinkExportRows(true);
};

$(document).ready(() => {
	initBizlinkExportPage();
});
