const dom = {
	filterDateRange: null,
	filterDateRangePicker: null,
	btnReset: null,
	detailFilterKeyword: null,
	detailSelectAll: null,
	detailTbody: null,
	detailPagination: null,
	detailResultCount: null,
	detailSelectedCount: null,
	btnExportExcel: null,
	detailExportForm: null,
};

const charts = {
	monthlyTrend: null,
	agingBuckets: null,
	departmentBreakdown: null,
	glBreakdown: null,
};

const KNA_SERIES = {
	blue: '#5b2bd6',
	orange: '#3b8bff',
	aqua: '#c026d3',
	violet: '#3b1a8a',
};

const KNA_STATUS = {
	good: '#0ca30c',
	warning: '#fab219',
	serious: '#ec835a',
	critical: '#d03b3b',
};

const KNA_INK_SECONDARY = '#52514e';
const KNA_GRID = '#e1e0d9';

let detailRows = [];
const selectedRefs = new Set();
let detailPage = 1;
const DETAIL_PAGE_SIZE = 15;

const normalizeText = (value) => (value || value === 0 ? String(value) : '');

const escapeHtml = (value = '') =>
	String(value)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');

const formatPHP = (value) => {
	const num = Number(value) || 0;
	return num.toLocaleString('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 });
};

const formatPHPFull = (value) => {
	const num = Number(value) || 0;
	return num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatNumber = (value) => Number(value || 0).toLocaleString('en-PH');

const parseDateRange = () => {
	if (!dom.filterDateRangePicker || !Array.isArray(dom.filterDateRangePicker.selectedDates) || dom.filterDateRangePicker.selectedDates.length !== 2) {
		return { from: '', to: '' };
	}
	return {
		from: dom.filterDateRangePicker.selectedDates[0].toISOString().slice(0, 10),
		to: dom.filterDateRangePicker.selectedDates[1].toISOString().slice(0, 10),
	};
};

const goToReference = (referenceNo) => {
	if (!referenceNo || typeof window.knetResolveTransactionRoute !== 'function') {
		return;
	}
	window.open(`${base_url}${window.knetResolveTransactionRoute(referenceNo, false, false)}`, '_blank');
};

// ---------- Charts (Chart.js v4 API — main.php loads v4 AFTER the bundled
// v2 core, so window.Chart ends up being v4; legend/tooltip live under
// options.plugins, scales are singular x/y objects, and horizontal bars are
// type:'bar' + indexAxis:'y', not the removed 'horizontalBar' type). ----------

const chartTooltipBase = (formatter) => ({
	mode: 'index',
	intersect: false,
	callbacks: {
		label: (ctx) => {
			const label = ctx.dataset.label || '';
			const raw = ctx.parsed.y !== undefined && ctx.parsed.y !== null ? ctx.parsed.y : ctx.parsed.x;
			return `${label}: ${formatter(raw)}`;
		},
	},
});

const renderMonthlyTrend = (rows) => {
	const canvas = document.getElementById('chartMonthlyTrend');
	if (!canvas) return;

	const months = Array.from(new Set(rows.map((r) => r.year_month))).sort();
	const seriesDefs = [
		{ key: 'CASH_ADVANCE', label: 'Cash Advance', color: KNA_SERIES.blue },
		{ key: 'REIMBURSEMENT', label: 'Reimbursement', color: KNA_SERIES.orange },
		{ key: 'LIQUIDATION', label: 'Liquidation', color: KNA_SERIES.aqua },
	];

	const datasets = seriesDefs.map((def) => ({
		label: def.label,
		borderColor: def.color,
		backgroundColor: def.color,
		fill: false,
		borderWidth: 2,
		pointRadius: 3,
		pointHoverRadius: 5,
		tension: 0.15,
		data: months.map((m) => {
			const match = rows.find((r) => r.year_month === m && r.transaction_type === def.key);
			return match ? Number(match.total_amount) : 0;
		}),
	}));

	if (charts.monthlyTrend) {
		charts.monthlyTrend.data.labels = months;
		charts.monthlyTrend.data.datasets = datasets;
		charts.monthlyTrend.update();
		return;
	}

	charts.monthlyTrend = new Chart(canvas.getContext('2d'), {
		type: 'line',
		data: { labels: months, datasets },
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: true, position: 'bottom', labels: { boxWidth: 12, color: KNA_INK_SECONDARY, font: { size: 11 } } },
				tooltip: chartTooltipBase(formatPHP),
			},
			scales: {
				x: { grid: { display: false }, ticks: { color: KNA_INK_SECONDARY } },
				y: {
					beginAtZero: true,
					grid: { color: KNA_GRID, drawTicks: false },
					ticks: { color: KNA_INK_SECONDARY, callback: (value) => formatPHP(value) },
				},
			},
		},
	});
};

const renderAgingBuckets = (rows) => {
	const canvas = document.getElementById('chartAgingBuckets');
	if (!canvas) return;

	const bucketColors = {
		'0-3 days': KNA_STATUS.good,
		'4-7 days': KNA_STATUS.warning,
		'8-14 days': KNA_STATUS.serious,
		'15+ days': KNA_STATUS.critical,
	};
	const order = ['0-3 days', '4-7 days', '8-14 days', '15+ days'];
	const byBucket = new Map(rows.map((r) => [r.bucket, Number(r.item_count)]));
	const labels = order;
	const data = order.map((b) => byBucket.get(b) || 0);
	const colors = order.map((b) => bucketColors[b]);

	if (charts.agingBuckets) {
		charts.agingBuckets.data.labels = labels;
		charts.agingBuckets.data.datasets[0].data = data;
		charts.agingBuckets.data.datasets[0].backgroundColor = colors;
		charts.agingBuckets.update();
		return;
	}

	charts.agingBuckets = new Chart(canvas.getContext('2d'), {
		type: 'bar',
		data: {
			labels,
			datasets: [{
				label: 'Pending items',
				data,
				backgroundColor: colors,
				borderWidth: 0,
			}],
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: false },
				tooltip: {
					callbacks: {
						label: (ctx) => `${formatNumber(ctx.parsed.y)} item(s)`,
					},
				},
			},
			scales: {
				x: { grid: { display: false }, ticks: { color: KNA_INK_SECONDARY } },
				y: {
					beginAtZero: true,
					ticks: { color: KNA_INK_SECONDARY, precision: 0 },
					grid: { color: KNA_GRID, drawTicks: false },
				},
			},
		},
	});
};

const renderHorizontalBar = (chartKey, canvasId, rows, labelField, valueField, color) => {
	const canvas = document.getElementById(canvasId);
	if (!canvas) return;

	const top = rows.slice(0, 8);
	const labels = top.map((r) => normalizeText(r[labelField]));
	const data = top.map((r) => Number(r[valueField]) || 0);

	if (charts[chartKey]) {
		charts[chartKey].data.labels = labels;
		charts[chartKey].data.datasets[0].data = data;
		charts[chartKey].update();
		return;
	}

	charts[chartKey] = new Chart(canvas.getContext('2d'), {
		type: 'bar',
		data: {
			labels,
			datasets: [{
				label: 'Amount',
				data,
				backgroundColor: color,
				borderWidth: 0,
				barThickness: 16,
			}],
		},
		options: {
			indexAxis: 'y',
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: false },
				tooltip: {
					callbacks: {
						label: (ctx) => formatPHP(ctx.parsed.x),
					},
				},
			},
			scales: {
				x: {
					beginAtZero: true,
					grid: { color: KNA_GRID, drawTicks: false },
					ticks: { color: KNA_INK_SECONDARY, callback: (v) => formatPHP(v) },
				},
				y: { grid: { display: false }, ticks: { color: KNA_INK_SECONDARY } },
			},
		},
	});
};

const renderKpis = (kpis) => {
	document.getElementById('kpiCaTotal').textContent = formatPHP(kpis.ca_total);
	document.getElementById('kpiCaCount').textContent = `${formatNumber(kpis.ca_count)} request(s)`;

	document.getElementById('kpiRmbTotal').textContent = formatPHP(kpis.rmb_total);
	document.getElementById('kpiRmbCount').textContent = `${formatNumber(kpis.rmb_count)} request(s)`;

	document.getElementById('kpiLqTotal').textContent = formatPHP(kpis.lq_total);
	document.getElementById('kpiLqCount').textContent = `${formatNumber(kpis.lq_count)} request(s)`;

	document.getElementById('kpiPaymentTotal').textContent = formatPHP(kpis.payment_released_total);
	document.getElementById('kpiPaymentCount').textContent = `${formatNumber(kpis.payment_released_count)} release(s)`;

	document.getElementById('kpiPendingCount').textContent = formatNumber(kpis.pending_count);
	document.getElementById('kpiPendingAmount').textContent = `${formatPHP(kpis.pending_amount)} awaiting decision`;

	const turnaround = Number(kpis.avg_turnaround_days) || 0;
	document.getElementById('kpiTurnaround').textContent = `${turnaround.toFixed(1)} days`;

	document.getElementById('kpiRfBalance').textContent = formatPHP(kpis.rf_balance);
};

// ---------- Detail data table (checkboxes + selective/full Excel export) ----------

const getTypeBadge = (type) => {
	const map = {
		CASH_ADVANCE: ['kna-badge-ca', 'Cash Advance'],
		REIMBURSEMENT: ['kna-badge-rmb', 'Reimbursement'],
		LIQUIDATION: ['kna-badge-lq', 'Liquidation'],
	};
	const entry = map[type] || ['kna-badge-lq', type || '-'];
	return `<span class="kna-badge ${entry[0]}">${escapeHtml(entry[1])}</span>`;
};

const normalizeDetailRows = (rows) =>
	(rows || []).map((row) => ({
		id: Number(row.id),
		transactionType: normalizeText(row.transaction_type),
		referenceNo: normalizeText(row.reference_no),
		employeeName: normalizeText(row.employee_name),
		companyName: normalizeText(row.company_name),
		departmentName: normalizeText(row.department_name),
		costCenterId: normalizeText(row.cost_center_id),
		costCenterName: normalizeText(row.cost_center_name),
		amount: Number(row.amount) || 0,
		description: normalizeText(row.description),
		statusName: normalizeText(row.status_name),
		createdDate: normalizeText(row.created_date).slice(0, 10),
	}));

const getFilteredDetailRows = () => {
	const keyword = normalizeText(dom.detailFilterKeyword.value).trim().toLowerCase();
	if (!keyword) return detailRows;
	return detailRows.filter((row) => {
		const haystack = `${row.referenceNo} ${row.employeeName} ${row.departmentName} ${row.description}`.toLowerCase();
		return haystack.indexOf(keyword) !== -1;
	});
};

const updateSelectionUi = () => {
	dom.detailSelectedCount.textContent = `${selectedRefs.size} selected`;

	const rows = getFilteredDetailRows();
	const allSelected = rows.length > 0 && rows.every((row) => selectedRefs.has(row.referenceNo));
	dom.detailSelectAll.checked = allSelected;
	dom.detailSelectAll.indeterminate = !allSelected && rows.some((row) => selectedRefs.has(row.referenceNo));
};

const renderDetailPagination = (totalRows) => {
	const totalPages = Math.max(1, Math.ceil(totalRows / DETAIL_PAGE_SIZE));
	if (detailPage > totalPages) detailPage = totalPages;

	const canPrev = detailPage > 1;
	const canNext = detailPage < totalPages;

	const WINDOW_SIZE = 5;
	let windowStart = Math.max(1, detailPage - Math.floor(WINDOW_SIZE / 2));
	let windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);
	windowStart = Math.max(1, windowEnd - WINDOW_SIZE + 1);

	let pageLinks = '';
	if (windowStart > 1) {
		pageLinks += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li><li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
	}
	for (let page = windowStart; page <= windowEnd; page += 1) {
		pageLinks += `<li class="page-item ${page === detailPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${page}">${page}</a></li>`;
	}
	if (windowEnd < totalPages) {
		pageLinks += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li><li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
	}

	dom.detailPagination.innerHTML = `
		<li class="page-item ${canPrev ? '' : 'disabled'}"><a class="page-link" href="#" data-action="prev">&lsaquo;</a></li>
		${pageLinks}
		<li class="page-item ${canNext ? '' : 'disabled'}"><a class="page-link" href="#" data-action="next">&rsaquo;</a></li>
	`;
};

const refreshDetailTable = () => {
	const rows = getFilteredDetailRows();
	const start = (detailPage - 1) * DETAIL_PAGE_SIZE;
	const pageRows = rows.slice(start, start + DETAIL_PAGE_SIZE);

	dom.detailResultCount.textContent = `${rows.length} record(s)`;
	renderDetailPagination(rows.length);

	if (!pageRows.length) {
		dom.detailTbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted">No records found</td></tr>';
		updateSelectionUi();
		return;
	}

	dom.detailTbody.innerHTML = pageRows.map((row) => {
		const costCenterDisplay = window.knetFormatCodeName(row.costCenterId, row.costCenterName);
		const checked = selectedRefs.has(row.referenceNo) ? 'checked' : '';
		return `
			<tr>
				<td><input type="checkbox" class="detail-row-checkbox" data-ref="${escapeHtml(row.referenceNo)}" ${checked}></td>
				<td style="white-space:nowrap;">${getTypeBadge(row.transactionType)}</td>
				<td class="font-weight-bold" style="white-space:nowrap;"><a href="#" class="kna-row-link" data-ref="${escapeHtml(row.referenceNo)}">${escapeHtml(row.referenceNo || '-')}</a></td>
				<td style="white-space:normal;overflow-wrap:break-word;" title="${escapeHtml(row.employeeName)}">${escapeHtml(row.employeeName || '-')}</td>
				<td style="white-space:normal;overflow-wrap:break-word;" title="${escapeHtml(row.departmentName)}">${escapeHtml(row.departmentName || '-')}</td>
				<td style="white-space:normal;overflow-wrap:break-word;" title="${escapeHtml(costCenterDisplay)}">${escapeHtml(costCenterDisplay)}</td>
				<td class="text-right" style="white-space:nowrap;">${formatPHPFull(row.amount)}</td>
				<td style="white-space:normal;overflow-wrap:break-word;" title="${escapeHtml(row.description)}">${escapeHtml(row.description || '-')}</td>
				<td style="white-space:nowrap;">${escapeHtml(row.statusName || '-')}</td>
				<td style="white-space:nowrap;">${escapeHtml(row.createdDate || '-')}</td>
			</tr>
		`;
	}).join('');

	updateSelectionUi();
};

const loadDetail = () => {
	const range = parseDateRange();
	const payload = {};
	if (range.from) payload.DateFrom = range.from;
	if (range.to) payload.DateTo = range.to;

	const request = ajax_loader('reports/executive-dashboard/api/get/detail', payload);

	request.done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (!res || res.status !== 'success') {
			return;
		}
		detailRows = normalizeDetailRows(res.data);
		detailPage = 1;
		refreshDetailTable();
	});
};

const submitExportForm = (references) => {
	const range = parseDateRange();
	dom.detailExportForm.innerHTML = '';
	dom.detailExportForm.action = `${base_url}reports/executive-dashboard/download/excel`;

	const addField = (name, value) => {
		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		dom.detailExportForm.appendChild(input);
	};

	if (range.from) addField('DateFrom', range.from);
	if (range.to) addField('DateTo', range.to);
	(references || []).forEach((ref) => addField('References[]', ref));

	dom.detailExportForm.submit();
};

// ---------- Filters & wiring ----------

const loadDashboard = () => {
	const range = parseDateRange();
	const payload = {};
	if (range.from) payload.DateFrom = range.from;
	if (range.to) payload.DateTo = range.to;

	const request = ajax_loader('reports/executive-dashboard/api/get', payload);

	request.done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (!res || res.status !== 'success') {
			Swal.fire({ icon: 'error', title: 'Load Failed', text: (res && res.response) ? res.response : 'Could not load dashboard data.' });
			return;
		}

		const payloadData = res.data || {};
		renderKpis(payloadData.kpis || {});
		renderMonthlyTrend(payloadData.monthlyTrend || []);
		renderAgingBuckets(payloadData.agingBuckets || []);
		renderHorizontalBar('departmentBreakdown', 'chartDepartmentBreakdown', payloadData.departmentBreakdown || [], 'department_name', 'total_amount', KNA_SERIES.blue);
		renderHorizontalBar('glBreakdown', 'chartGlBreakdown', payloadData.glBreakdown || [], 'gl_name', 'total_amount', KNA_SERIES.violet);
	}).fail(() => {
		Swal.fire({ icon: 'error', title: 'Load Failed', text: 'Could not load dashboard data.' });
	});

	selectedRefs.clear();
	loadDetail();
};

const resetFilters = () => {
	if (dom.filterDateRangePicker) dom.filterDateRangePicker.clear();
	loadDashboard();
};

const cacheDom = () => {
	dom.filterDateRange = document.getElementById('filterDateRange');
	dom.btnReset = document.getElementById('btnReset');
	dom.detailFilterKeyword = document.getElementById('detailFilterKeyword');
	dom.detailSelectAll = document.getElementById('detailSelectAll');
	dom.detailTbody = document.getElementById('detailTbody');
	dom.detailPagination = document.getElementById('detailPagination');
	dom.detailResultCount = document.getElementById('detailResultCount');
	dom.detailSelectedCount = document.getElementById('detailSelectedCount');
	dom.btnExportExcel = document.getElementById('btnExportExcel');
	dom.detailExportForm = document.getElementById('detailExportForm');
};

const bindEvents = () => {
	dom.btnReset.addEventListener('click', resetFilters);

	dom.detailFilterKeyword.addEventListener('input', () => {
		detailPage = 1;
		refreshDetailTable();
	});

	dom.detailSelectAll.addEventListener('change', () => {
		const rows = getFilteredDetailRows();
		if (dom.detailSelectAll.checked) {
			rows.forEach((row) => selectedRefs.add(row.referenceNo));
		} else {
			rows.forEach((row) => selectedRefs.delete(row.referenceNo));
		}
		refreshDetailTable();
	});

	dom.detailTbody.addEventListener('click', (event) => {
		const checkbox = event.target.closest('.detail-row-checkbox');
		if (checkbox) {
			const ref = checkbox.dataset.ref;
			if (checkbox.checked) {
				selectedRefs.add(ref);
			} else {
				selectedRefs.delete(ref);
			}
			updateSelectionUi();
			return;
		}

		const link = event.target.closest('a.kna-row-link');
		if (link) {
			event.preventDefault();
			goToReference(link.dataset.ref);
		}
	});

	dom.detailPagination.addEventListener('click', (event) => {
		const target = event.target.closest('a.page-link');
		if (!target) return;
		event.preventDefault();

		const rows = getFilteredDetailRows();
		const totalPages = Math.max(1, Math.ceil(rows.length / DETAIL_PAGE_SIZE));

		if (target.dataset.page) {
			detailPage = Math.min(totalPages, Math.max(1, Number(target.dataset.page)));
		} else if (target.dataset.action === 'prev' && detailPage > 1) {
			detailPage -= 1;
		} else if (target.dataset.action === 'next' && detailPage < totalPages) {
			detailPage += 1;
		}
		refreshDetailTable();
	});

	dom.btnExportExcel.addEventListener('click', () => {
		submitExportForm(Array.from(selectedRefs));
	});
};

const init = () => {
	cacheDom();

	if (dom.filterDateRange && typeof flatpickr !== 'undefined') {
		dom.filterDateRangePicker = flatpickr(dom.filterDateRange, {
			mode: 'range',
			dateFormat: 'Y-m-d',
			allowInput: false,
			onChange: (selectedDates) => {
				if (selectedDates.length === 0 || selectedDates.length === 2) {
					loadDashboard();
				}
			},
		});
	}

	bindEvents();
	loadDashboard();
};

$(document).ready(() => {
	init();
});
