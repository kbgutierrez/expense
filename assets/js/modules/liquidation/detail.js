const domDetail = {
	liquidationRef: null,
	viewLiquidationNo: null,
	viewCaRef: null,
	viewCaAmount: null,
	viewApprovedAmount: null,
	viewCaDate: null,
	viewExpenseDate: null,
	viewLiquidatedAmount: null,
	viewVariance: null,
	viewPurpose: null,
	viewPayableTo: null,
	viewAddress: null,
	viewCostCenter: null,
	viewStatus: null,
	viewSubmittedDate: null,
	viewExpenseItems: null,
	viewTimeline: null,
	btnEditLiquidation: null,
	mobileLiquidationNo: null,
	mobileStatus: null,
	mobileCaAmount: null,
	mobileApprovedAmount: null,
	mobileTotal: null,
	mobileVariance: null,
	mobileCaRef: null,
	mobileCaDate: null,
	mobileSubmittedDate: null,
	mobileExpenseDate: null,
	mobilePayableTo: null,
	mobileCostCenter: null,
	mobileAddress: null,
	mobilePurpose: null,
};

const IMG_EXTS = /\.(jpg|jpeg|png|gif|webp)$/i;

const getVariancePresentation = (refund, reimburse) => {
	if (refund > 0) {
		return {
			desktopHtml: `<span class="kna-var-badge kna-var-return">${formatPHP(refund)} to return</span>`,
			mobileText: `${formatPHP(refund)} return`,
		};
	}
	if (reimburse > 0) {
		return {
			desktopHtml: `<span class="kna-var-badge kna-var-reimburse">${formatPHP(reimburse)} to reimburse</span>`,
			mobileText: `${formatPHP(reimburse)} reimburse`,
		};
	}
	return {
		desktopHtml: '<span class="kna-var-badge kna-var-balanced">0.00</span>',
		mobileText: '0.00',
	};
};

const openLightbox = (url) => {
	const lb = document.getElementById('knaLightbox');
	const img = document.getElementById('knaLightboxImg');
	if (lb && img) {
		img.src = url;
		lb.classList.remove('d-none');
	}
};

const renderAttachment = (name) => {
	const url = `${base_url}assets/uploads/attachments/${encodeURIComponent(name)}`;
	if (IMG_EXTS.test(name)) {
		return `<span class="kna-thumb-wrap" data-lightbox="${escapeHtml(url)}">
			<img class="kna-thumb" src="${url}" alt="${escapeHtml(name)}" loading="lazy">
			<span class="kna-thumb-label">${escapeHtml(name)}</span>
		</span>`;
	}
	return `<span class="kna-file-wrap">
		<i class="fas fa-file-alt" style="color:#5b2bd6;font-size:11px;"></i>
		<a href="${url}" target="_blank" rel="noopener">${escapeHtml(name)}</a>
	</span>`;
};

const getExpenseTypeDisplayText = (expense) => {
	const code = normalizeDate(expense.expense_code || expense.expense_category_code || expense.category_code || '');
	const longText = normalizeDate(expense.long_text || expense.expense_category_name || expense.category_name || expense.description || '');

	if (code && longText) {
		return `${code} - ${longText}`;
	}
	return code || longText || 'Expense Item';
};

const getExpenseVendorHtml = (expense) => {
	const vendorName = normalizeDate(expense.vendor_name || '');
	const vendorAddress = normalizeDate(expense.vendor_address || '');
	const vendorTin = normalizeDate(expense.vendor_tin || '');

	if (!vendorName && !vendorAddress && !vendorTin) {
		return '<span class="text-muted" style="font-size:11px;">\u2014</span>';
	}

	return `
		<div class="kna-vendor-display">
			${vendorName ? `<div>${escapeHtml(vendorName)}</div>` : ''}
			${vendorAddress ? `<div class="kna-vendor-sub">${escapeHtml(vendorAddress)}</div>` : ''}
			${vendorTin ? `<div class="kna-vendor-sub">TIN: ${escapeHtml(vendorTin)}</div>` : ''}
		</div>
	`;
};

const getExpenseStatusBadge = (expense) => {
	const hasApproved = Boolean(Number(expense.has_approved || 0));
	const hasRejected = Boolean(Number(expense.has_rejected || 0));
	const rejectionReason = normalizeDate(expense.rejection_reason || '');
	const rejectedByName = normalizeDate(expense.rejected_by_name || '');

	if (hasApproved) {
		return {
			desktop: '<div class="kna-approved-badge" style="margin-top:4px;"><i class="fas fa-check"></i> Approved</div>',
			mobile: '<span class="kna-status-badge kna-status-approved"><i class="fas fa-check"></i> Approved</span>',
		};
	}

	if (hasRejected) {
		return {
			desktop: `<div class="kna-rejected-badge" style="margin-top:4px;"><i class="fas fa-times"></i> Rejected by ${escapeHtml(rejectedByName)}</div><div class="kna-rejected-reason" style="font-size:11px;color:#991b1b;font-style:italic;">"${escapeHtml(rejectionReason)}"</div>`,
			mobile: `<span class="kna-status-badge kna-status-rejected"><i class="fas fa-times"></i> Rejected by ${escapeHtml(rejectedByName)}</span>`,
		};
	}

	return { desktop: '', mobile: '' };
};

const getExpenseItemSummary = (expense) => ({
	docDate: normalizeDate(expense.document_date || '').slice(0, 10),
	typeText: getExpenseTypeDisplayText(expense),
	reference: normalizeDate(expense.invoice_receipt_no || '') || '\u2014',
	amount: Number(expense.actual_amount || 0),
	netAmt: Number(expense.net_amount || 0),
	vatAmt: Number(expense.vat_amount || 0),
	isVattable: Boolean(Number(expense.is_vatable)),
	description: normalizeDate(expense.description || ''),
	attachNames: normalizeDate(expense.attachment || '').split(',').map((s) => s.trim()).filter(Boolean),
	vendorName: normalizeDate(expense.vendor_name || ''),
	vendorAddress: normalizeDate(expense.vendor_address || ''),
	vendorTin: normalizeDate(expense.vendor_tin || ''),
	vendorHtml: getExpenseVendorHtml(expense),
	statusBadge: getExpenseStatusBadge(expense),
});

const renderExpenseRow = (summary, index) => {
	const vatBadge = summary.isVattable
		? '<input type="checkbox" class="kna-vat-check" checked disabled>'
		: '<input type="checkbox" class="kna-vat-check" disabled>';

	const amountHtml = summary.isVattable
		? `<div class="kna-amount-main">${formatPHP(summary.amount)}</div><div class="kna-amount-breakdown">Net ${formatPHP(summary.netAmt)}</div><div class="kna-amount-breakdown">VAT ${formatPHP(summary.vatAmt)}</div>`
		: `<div class="kna-amount-main">${formatPHP(summary.amount)}</div>`;

	return `
		<tr>
			<td class="text-center kna-rownum kna-cell-index" data-label="#">${index + 1}</td>
			<td data-label="Date">${escapeHtml(summary.docDate)}</td>
			<td data-label="Expense Type">${escapeHtml(summary.typeText)}</td>
			<td data-label="Reference">${escapeHtml(summary.reference)}</td>
			<td class="text-center kna-cell-vat" data-label="VAT">${vatBadge}</td>
			<td data-label="Vendor">${summary.vendorHtml}</td>
			<td class="kna-cell-attachment" data-label="Attachment">${summary.attachNames.length ? summary.attachNames.map(renderAttachment).join('') : '<span class="text-muted" style="font-size:11px;">\u2014</span>'}</td>
			<td data-label="Remarks">${escapeHtml(summary.description)}${summary.statusBadge.desktop}</td>
			<td class="text-right kna-cell-amount" data-label="Amount">${amountHtml}</td>
		</tr>
	`;
};

const renderExpenseCard = (summary, index) => {
	const amountHtml = summary.isVattable
		? `<div class="kna-amount-main">${formatPHP(summary.amount)}</div><div class="kna-amount-breakdown">Net ${formatPHP(summary.netAmt)}</div><div class="kna-amount-breakdown">VAT ${formatPHP(summary.vatAmt)}</div>`
		: `<div class="kna-amount-main">${formatPHP(summary.amount)}</div>`;

	return `
		<div class="kna-exp-card">
			<div class="kna-exp-card-head">
				<div class="kna-exp-card-head-left">
					<div class="kna-exp-card-badge">${index + 1}</div>
					<div class="kna-exp-card-title">${escapeHtml(summary.typeText || 'Expense Item')}</div>
					<div class="kna-exp-card-meta">${escapeHtml(summary.docDate)} \u2022 ${escapeHtml(summary.reference)}</div>
					${summary.statusBadge.mobile}
				</div>
				<div class="kna-exp-card-amount">${amountHtml}</div>
			</div>

			<div class="kna-exp-card-body">
				<div class="kna-exp-card-grid">
					<div class="kna-exp-card-field kna-exp-card-field-full">
						<span class="kna-exp-card-label">Vendor</span>
						<span class="kna-exp-card-value kna-vendor-display">${summary.vendorHtml}</span>
					</div>
					<div class="kna-exp-card-field">
						<span class="kna-exp-card-label">VAT</span>
						<span class="kna-exp-card-value">${summary.isVattable ? '<input type="checkbox" class="kna-vat-check" checked disabled>' : '<input type="checkbox" class="kna-vat-check" disabled>'}</span>
					</div>
					<div class="kna-exp-card-field kna-exp-card-field-full">
						<span class="kna-exp-card-label">Remarks</span>
						<span class="kna-exp-card-value">${escapeHtml(summary.description || '\u2014')}</span>
					</div>
				</div>

				<div class="kna-exp-card-field kna-exp-card-field-full">
					<span class="kna-exp-card-label">Attachment</span>
					<span class="kna-exp-card-value kna-exp-card-attach">${summary.attachNames.length ? summary.attachNames.map(renderAttachment).join('') : '<span class="text-muted">\u2014</span>'}</span>
				</div>
			</div>
		</div>
	`;
};

const mobileDetailQuery = window.matchMedia ? window.matchMedia('(max-width: 767.98px)') : null;
const isMobileDetail = () => Boolean(mobileDetailQuery && mobileDetailQuery.matches);
let detailExpensesCache = null;
let detailSheetIndex = null;

const formatDetailShortDate = (value) => {
	const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(normalizeDate(value));
	if (!m) return '';
	const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	return `${months[Number(m[2]) - 1]} ${Number(m[3])}, ${m[1]}`;
};

const getDetailStatus = (expense) => {
	if (Number(expense.has_approved || 0)) return 'approved';
	if (Number(expense.has_rejected || 0)) return 'rejected';
	return '';
};

const renderMobileDetailCard = (expense, index) => {
	const summary = getExpenseItemSummary(expense);
	const status = getDetailStatus(expense);
	const name = normalizeDate(expense.long_text || expense.expense_category_name || expense.category_name || '') || summary.typeText;
	const sub = [formatDetailShortDate(summary.docDate), summary.reference !== '—' ? `OR ${summary.reference}` : ''].filter(Boolean).join(' · ');
	const chip = status === 'approved'
		? '<span class="lqm-chip is-approved"><i class="fas fa-check"></i> Approved</span>'
		: (status === 'rejected' ? '<span class="lqm-chip is-rejected"><i class="fas fa-times-circle"></i> Rejected</span>' : '');
	const hasReceipt = summary.attachNames.length > 0;
	return `
		<button type="button" class="lqm-exp${status === 'rejected' ? ' is-rejected' : ''}" data-detail-action="mOpen" data-detail-index="${index}">
			<span class="lqm-exp-thumb${hasReceipt ? '' : ' is-missing'}"><i class="fas ${hasReceipt ? 'fa-receipt' : 'fa-exclamation'}"></i></span>
			<span class="lqm-exp-main">
				<span class="lqm-exp-name d-block">${escapeHtml(name)}</span>
				<span class="lqm-exp-sub d-block">${escapeHtml(sub || '—')}</span>
				${chip}
			</span>
			<span class="lqm-exp-right">
				<span class="lqm-exp-amt d-block">${escapeHtml(formatPHP(summary.amount))}</span>
				${hasReceipt ? '' : '<span class="lqm-exp-flag d-block">No receipt</span>'}
			</span>
		</button>`;
};

const renderMobileDetailSheet = (expense) => {
	const summary = getExpenseItemSummary(expense);
	const status = getDetailStatus(expense);
	let note = '';
	if (status === 'approved') note = '<div class="lqm-locked-note"><i class="fas fa-check-circle"></i> Approved</div>';
	if (status === 'rejected') {
		note = `<div class="lqm-reject-note"><b><i class="fas fa-exclamation-circle"></i> Rejected</b><div><strong>${escapeHtml(normalizeDate(expense.rejected_by_name || 'Approver'))}:</strong> ${escapeHtml(normalizeDate(expense.rejection_reason || 'No reason provided'))}</div></div>`;
	}
	const receipt = summary.attachNames.length
		? `<div class="lqm-receipt-has"><span class="kna-attachment-cell">${summary.attachNames.map(renderAttachment).join('')}</span></div>`
		: '<div class="lqm-receipt-has"><span class="text-muted" style="font-size:12px;">No receipt attached</span></div>';
	const kv = (label, value) => `<div class="lqm-kv"><span>${label}</span><b>${escapeHtml(value || '—')}</b></div>`;
	const vendor = [summary.vendorName, summary.vendorAddress, summary.vendorTin ? `TIN ${summary.vendorTin}` : ''].filter(Boolean);
	return `
		<div class="lqm-sheet-backdrop" data-detail-action="mClose"></div>
		<div class="lqm-sheet" role="dialog" aria-modal="true" aria-label="Expense">
			<div class="lqm-grab"></div>
			<div class="lqm-sheet-head">
				<b>Expense</b>
				<button type="button" class="lqm-icon-btn" data-detail-action="mClose" aria-label="Close"><i class="fas fa-times"></i></button>
			</div>
			<div class="lqm-sheet-body">
				${note}
				${receipt}
				<div class="lqm-amount-static">${escapeHtml(formatPHP(summary.amount))}</div>
				${summary.isVattable ? `<div class="lqm-amount-sub">Net ${escapeHtml(formatPHP(summary.netAmt))} · VAT ${escapeHtml(formatPHP(summary.vatAmt))}</div>` : ''}
				${kv('Date', formatDetailShortDate(summary.docDate))}
				${kv('OR / Invoice no.', summary.reference)}
				${kv('Expense type', summary.typeText)}
				${kv('Description', summary.description)}
				${kv('Vendor', vendor.join(' · '))}
				${kv('VAT-able', summary.isVattable ? 'Yes' : 'No')}
			</div>
			<div class="lqm-sheet-foot">
				<button type="button" class="lqm-btn lqm-btn-primary" data-detail-action="mClose">Close</button>
			</div>
		</div>`;
};

const renderMobileDetailItems = (expenses) => {
	const container = domDetail.viewExpenseItems;
	const list = expenses || [];
	const sheetExpense = detailSheetIndex !== null ? list[detailSheetIndex] : null;
	if (!sheetExpense) detailSheetIndex = null;
	document.documentElement.classList.toggle('lqm-sheet-open', Boolean(sheetExpense));
	container.innerHTML = `
		<div class="lqm-list">
			<div class="lqm-section"><b>Expenses</b><span>${list.length} item${list.length === 1 ? '' : 's'}</span></div>
			${list.length ? list.map(renderMobileDetailCard).join('') : '<div class="lqm-empty"><b>No expense items found</b></div>'}
		</div>
		<div class="lqm-sheet-wrap">${sheetExpense ? renderMobileDetailSheet(sheetExpense) : ''}</div>`;
};

const updateDetailMobileSummary = () => {
	const approved = Number(normalizeDate(domDetail.mobileApprovedAmount?.textContent).replace(/[^0-9.]/g, '') || 0);
	const total = Number(normalizeDate(domDetail.mobileTotal?.textContent).replace(/[^0-9.]/g, '') || 0);
	const bar = document.getElementById('mobileProgressBar');
	const left = document.getElementById('mobileLeft');
	if (bar) {
		bar.style.width = `${approved > 0 ? Math.min(100, (total / approved) * 100) : 0}%`;
		bar.classList.toggle('is-over', approved > 0 && total > approved);
	}
	if (left) {
		if (approved <= 0) left.textContent = '';
		else if (total <= approved) left.textContent = `${formatPHP(approved - total)} left`;
		else left.textContent = `${formatPHP(total - approved)} over`;
	}
};

const setDetailMobileVariance = (refund, reimburse) => {
	if (!domDetail.mobileVariance) return;
	domDetail.mobileVariance.textContent = refund > 0 ? `${formatPHP(refund)} to return` : (reimburse > 0 ? `${formatPHP(reimburse)} to reimburse` : 'Fully liquidated');
	domDetail.mobileVariance.className = 'lqm-variance' + (refund > 0 ? ' is-return' : (reimburse > 0 ? ' is-reimburse' : ' is-balanced'));
};

const renderDetailExpenseItems = (expenses) => {
	const container = domDetail.viewExpenseItems;
	if (!container) {
		return;
	}
	detailExpensesCache = expenses || [];

	if (isMobileDetail()) {
		if (expenses && expenses.length && domDetail.mobileTotal) {
			domDetail.mobileTotal.textContent = formatPHP(expenses.reduce((sum, e) => sum + Number(e.actual_amount || 0), 0));
		}
		renderMobileDetailItems(expenses);
		updateDetailMobileSummary();
		return;
	}
	document.documentElement.classList.remove('lqm-sheet-open');

	if (!expenses || !expenses.length) {
		container.innerHTML = '<div class="text-muted kna-small py-2">No expense items found.</div>';
		return;
	}

	const rowsHtml = expenses
		.map((expense, i) => {
			return renderExpenseRow(getExpenseItemSummary(expense), i);
		})
		.join('');

	const mobileCardsHtml = expenses
		.map((expense, i) => {
			return renderExpenseCard(getExpenseItemSummary(expense), i);
		})
		.join('');

	const total    = expenses.reduce((sum, e) => sum + Number(e.actual_amount || 0), 0);
	const totalNet = expenses.reduce((sum, e) => sum + Number(e.net_amount    || 0), 0);
	const totalVat = expenses.reduce((sum, e) => sum + Number(e.vat_amount    || 0), 0);
	if (domDetail.mobileTotal) {
		domDetail.mobileTotal.textContent = formatPHP(total);
	}

	container.innerHTML = `
		<div class="kna-exp-wrap">
			<div class="kna-exp-mobile">${mobileCardsHtml}</div>
			<table class="kna-exp-table">
				<thead>
					<tr>
						<th style="width:34px;">#</th>
						<th style="width:96px;">Date</th>
						<th style="width:120px;">Expense Type</th>
						<th style="width:110px;">Reference</th>
						<th style="width:72px;" class="text-center">VAT</th>
						<th style="width:340px;">Vendor</th>
						<th style="width:70px;">Attachment</th>
						<th>Remarks</th>
						<th style="width:130px;" class="text-right">Amount</th>
					</tr>
				</thead>
				<tbody>${rowsHtml}</tbody>
				<tfoot>
					<tr>
						<td colspan="8" class="text-right" style="font-size:12px;">Total</td>
						<td class="text-right">
							<div class="kna-amount-main">${formatPHP(total)}</div>
							<div class="kna-amount-breakdown">Net ${formatPHP(totalNet)}</div>
							<div class="kna-amount-breakdown">VAT ${formatPHP(totalVat)}</div>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
	`;
};

const formatTimelineDate = (dateStr) => {
	if (!dateStr) return '';
	const raw = normalizeDate(dateStr);
	if (!raw) return '';

	const date = new Date(raw.replace(' ', 'T'));
	if (Number.isNaN(date.getTime())) return raw;

	const yyyy = date.getFullYear();
	const mm = String(date.getMonth() + 1).padStart(2, '0');
	const dd = String(date.getDate()).padStart(2, '0');
	let hh = date.getHours();
	const ampm = hh >= 12 ? 'PM' : 'AM';
	hh = hh % 12;
	hh = hh ? hh : 12;
	const min = String(date.getMinutes()).padStart(2, '0');

	return `${yyyy}-${mm}-${dd} ${String(hh).padStart(2, '0')}:${min}${ampm}`;
};

const AUDIT_FIELD_LABELS = {
	description: 'Description', invoice_receipt_no: 'Invoice/Receipt No.', document_date: 'Document Date',
	actual_amount: 'Gross Amount', approved_amount: 'Gross Amount', expense_category: 'Expense Type',
	is_vatable: 'VAT Applicable', net_amount: 'Net Amount', vat_amount: 'VAT Amount',
	vendor_name: 'Vendor Name', vendor_address: 'Vendor Address', vendor_tin: 'Vendor TIN',
	amount: 'Amount', cost_center_id: 'Cost Center', payable_to: 'Payable To', address: 'Address', io: 'IO Number',
};
const AUDIT_CURRENCY_FIELDS = new Set(['actual_amount', 'approved_amount', 'net_amount', 'vat_amount', 'amount']);

const formatAuditFieldLabel = (field) => AUDIT_FIELD_LABELS[field] || field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const formatAuditValue = (field, value) => {
	const raw = normalizeDate(value);
	if (raw === '') return '<span class="text-muted">\u2014</span>';
	if (AUDIT_CURRENCY_FIELDS.has(field)) {
		const num = Number(raw);
		return Number.isFinite(num) ? escapeHtml(formatPHP(num)) : escapeHtml(raw);
	}
	if (field === 'is_vatable') return (raw === '1' || raw.toLowerCase() === 'true') ? 'Yes' : 'No';
	if (field === 'document_date') {
		const d = raw.slice(0, 10);
		return escapeHtml(d || raw);
	}
	return escapeHtml(raw);
};

const AUDIT_ACTION_VERBS = {
	SUBMITTED: 'submitted', SAVED_DRAFT: 'saved a draft of', CREATED: 'created', APPROVED: 'approved',
	REJECTED: 'rejected', UPDATED_DRAFT: 'updated', RESUBMITTED: 'resubmitted',
	ADDED_ITEM: 'added an item to', UPDATED_ITEM: 'edited',
	// Advise/Release log the raw new status code as the action — map
	// those specifically instead of falling through to the raw-code
	// fallback ("lq for release the liquidation").
	LQ_FOR_RELEASE: 'advised payment for', LQ_COMPLETED: 'released payment for',
};
const auditActionVerb = (action) => AUDIT_ACTION_VERBS[action] || action.toLowerCase().replace(/_/g, ' ');
const joinAuditVerbs = (actions) => {
	const verbs = [...new Set(actions.map(auditActionVerb))];
	if (verbs.length === 1) return verbs[0];
	if (verbs.length === 2) return `${verbs[0]} and ${verbs[1]}`;
	return `${verbs.slice(0, -1).join(', ')}, and ${verbs[verbs.length - 1]}`;
};

const groupAuditTrail = (auditTrail) => {
	if (!auditTrail || !auditTrail.length) return [];

	const sorted = [...auditTrail].sort((a, b) => {
		const da = new Date((a.created_date || '').replace(' ', 'T'));
		const db = new Date((b.created_date || '').replace(' ', 'T'));
		return da - db;
	});

	const entriesWithKey = sorted.map((entry) => {
		const action = normalizeDate(entry.action || '').toUpperCase();
		const changedByName = normalizeDate(entry.changed_by_name || 'Unknown User');
		const transactionId = normalizeDate(entry.transaction_id || '');
		const entityType = normalizeDate(entry.entity_type || '').toUpperCase();
		const entityId = normalizeDate(entry.entity_id || '');
		const fieldName = normalizeDate(entry.field_name || '');
		const description = normalizeDate(entry.description || '');
		const remarks = normalizeDate(entry.remarks || '');
		const dateStr = formatTimelineDate(entry.created_date);

		const rawDate = normalizeDate(entry.created_date || '');
		const timeBucket = rawDate.length >= 16 ? rawDate.substring(0, 16) : rawDate;

		const groupKey = `${changedByName}|${transactionId}|${timeBucket}`;

		return {
			...entry,
			_action: action,
			_entityType: entityType,
			_entityId: entityId,
			_fieldName: fieldName,
			_changedByName: changedByName,
			_dateStr: dateStr,
			_timeBucket: timeBucket,
			_groupKey: groupKey,
			_description: description,
			_remarks: remarks,
			_oldValue: entry.old_value,
			_newValue: entry.new_value,
		};
	});

	const groupMap = new Map();

	entriesWithKey.forEach((entry) => {
		const key = entry._groupKey;

		if (!groupMap.has(key)) {
			groupMap.set(key, {
				dateStr: entry._dateStr,
				changedByName: entry._changedByName,
				transactionType: normalizeDate(entry.transaction_type || ''),
				actions: [],
				headerRemarks: '',
				headerDescription: '',
				itemsByEntity: new Map(),
				hasHeader: false,
				hasItems: false,
			});
		}

		const group = groupMap.get(key);
		if (!group.actions.includes(entry._action)) group.actions.push(entry._action);

		if (entry._entityType === 'HEADER') {
			group.hasHeader = true;
			if (entry._remarks) group.headerRemarks = entry._remarks;
			if (entry._description) group.headerDescription = entry._description;
		} else if (entry._entityType === 'ITEM') {
			group.hasItems = true;
			const entityKey = entry._entityId || entry._description || 'item';
			if (!group.itemsByEntity.has(entityKey)) {
				group.itemsByEntity.set(entityKey, { description: entry._description, remarks: '', changes: [], actions: [] });
			}
			const itemGroup = group.itemsByEntity.get(entityKey);
			if (entry._description && !itemGroup.description) itemGroup.description = entry._description;
			if (entry._remarks) itemGroup.remarks = entry._remarks;
			if (!itemGroup.actions.includes(entry._action)) itemGroup.actions.push(entry._action);
			const isSkippedField = entry._fieldName === 'status' || entry._fieldName === 'net_amount' || entry._fieldName === 'vat_amount';
			if (entry._fieldName && !isSkippedField && normalizeDate(entry._oldValue) !== normalizeDate(entry._newValue)) {
				itemGroup.changes.push({ field: entry._fieldName, oldValue: entry._oldValue, newValue: entry._newValue });
			}
		} else {
			if (entry._description) group.headerDescription = entry._description;
			if (entry._remarks) group.headerRemarks = entry._remarks;
		}
	});

	const groups = Array.from(groupMap.values()).map((g) => ({ ...g, items: Array.from(g.itemsByEntity.values()) }));
	groups.sort((a, b) => {
		const da = new Date((a.dateStr || '').replace(' ', 'T'));
		const db = new Date((b.dateStr || '').replace(' ', 'T'));
		return da - db;
	});

	return groups;
};

const buildTimelineText = (group) => {
	const changedByName = escapeHtml(group.changedByName);
	const transactionType = group.transactionType.toLowerCase();
	const items = group.items;

	let entityDesc = '';
	if (transactionType === 'cash_advance') {
		entityDesc = 'the cash advance';
	} else if (transactionType === 'liquidation') {
		entityDesc = 'the liquidation';
	} else {
		entityDesc = 'the request';
	}

	const verbPhrase = joinAuditVerbs(group.actions);

	let mainLine = `<strong>${changedByName}</strong> ${verbPhrase} ${entityDesc}`;
	if (group.headerDescription) mainLine += ` &mdash; ${escapeHtml(group.headerDescription)}`;
	if (group.headerRemarks) mainLine += `: "${escapeHtml(group.headerRemarks)}"`;

	if (!group.hasItems || items.length === 0) return mainLine;

	const subLines = items.map((item) => {
		const label = item.description ? `"${escapeHtml(item.description)}"` : 'an item';
		const itemVerb = joinAuditVerbs(item.actions);
		const itemVerbLabel = itemVerb.charAt(0).toUpperCase() + itemVerb.slice(1);
		if (item.changes.length > 0) {
			const changeParts = item.changes.map((c) => `${escapeHtml(formatAuditFieldLabel(c.field))}: ${formatAuditValue(c.field, c.oldValue)} &rarr; ${formatAuditValue(c.field, c.newValue)}`).join(', ');
			let line = `&nbsp;&nbsp;&bull; Changed ${label} &mdash; ${changeParts}`;
			if (item.remarks) line += ` <em>("${escapeHtml(item.remarks)}")</em>`;
			return line;
		}
		if (item.remarks) return `&nbsp;&nbsp;&bull; ${itemVerbLabel} ${label}: "${escapeHtml(item.remarks)}"`;
		return `&nbsp;&nbsp;&bull; ${itemVerbLabel} ${label}`;
	});

	return [mainLine, ...subLines].join('<br>');
};

const renderHistoryTimeline = (auditTrail) => {
	const container = domDetail.viewTimeline;
	if (!container) return;

	if (!auditTrail || !auditTrail.length) {
		container.innerHTML = `
			<li class="kna-timeline-item is-pending">
				<div class="kna-timeline-item-top">
					<span class="kna-timeline-item-name">No history available</span>
				</div>
				<div class="kna-timeline-item-remarks">This request has no recorded history yet.</div>
			</li>
		`;
		return;
	}

	const groups = groupAuditTrail(auditTrail);

	const html = groups.map((group, index) => {
		const isLast = index === groups.length - 1;
		const statusClass = isLast ? 'is-current' : 'is-done';
		const text = buildTimelineText(group);

		return `
			<li class="kna-timeline-item ${statusClass}">
				<div class="kna-timeline-item-top">
					<span class="kna-timeline-item-name">${escapeHtml(group.dateStr)}</span>
				</div>
				<div class="kna-timeline-item-remarks">${text}</div>
			</li>
		`;
	}).join('');

	container.innerHTML = html;
};

const loadAuditTrail = () => {
	const ref = domDetail.liquidationRef ? domDetail.liquidationRef.value : '';
	if (!ref) return;

	ajax_loader('transactions/liquidation/api/get/timeline', { ReferenceNo: ref })
		.done((response) => {
			const res = (typeof response === 'string') ? $.parseJSON(response) : response;
			if (res.status !== 'success') {
				renderHistoryTimeline([]);
				return;
			}
			renderHistoryTimeline(res.data && res.data.audit_trail ? res.data.audit_trail : []);
		})
		.fail(() => {
			renderHistoryTimeline([]);
		});
};

const renderDetailTimeline = (timeline) => {
	loadAuditTrail();
};

/* ─── HISTORY MODAL TOGGLE ─── */
const initHistoryModal = (triggerIds = ['btnShowHistory']) => {
	const overlay = document.getElementById('historyModalOverlay');
	const closeBtn = document.getElementById('btnCloseHistory');
	if (!overlay) return;
	const open = () => overlay.classList.remove('d-none');
	const close = () => overlay.classList.add('d-none');
	triggerIds.forEach((id) => {
		const btn = document.getElementById(id);
		if (btn) btn.addEventListener('click', open);
	});
	if (closeBtn) closeBtn.addEventListener('click', close);
	overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
};

const cacheDetailDom = () => {
	domDetail.liquidationRef = document.getElementById('liquidationRef');
	domDetail.viewLiquidationNo = document.getElementById('viewLiquidationNo');
	domDetail.viewCaRef = document.getElementById('viewCaRef');
	domDetail.viewCaAmount = document.getElementById('viewCaAmount');
	domDetail.viewApprovedAmount = document.getElementById('viewApprovedAmount');
	domDetail.viewCaDate = document.getElementById('viewCaDate');
	domDetail.viewExpenseDate = document.getElementById('viewExpenseDate');
	domDetail.viewLiquidatedAmount = document.getElementById('viewLiquidatedAmount');
	domDetail.viewVariance = document.getElementById('viewVariance');
	domDetail.viewPurpose = document.getElementById('viewPurpose');
	domDetail.viewPayableTo = document.getElementById('viewPayableTo');
	domDetail.viewAddress = document.getElementById('viewAddress');
	domDetail.viewCostCenter = document.getElementById('viewCostCenter');
	domDetail.viewStatus = document.getElementById('viewStatus');
	domDetail.viewSubmittedDate = document.getElementById('viewSubmittedDate');
	domDetail.viewExpenseItems = document.getElementById('viewExpenseItems');
	domDetail.viewTimeline = document.getElementById('viewTimeline');
	domDetail.btnEditLiquidation = document.getElementById('btnEditLiquidation');
	domDetail.mobileLiquidationNo = document.getElementById('mobileLiquidationNo');
	domDetail.mobileStatus = document.getElementById('mobileStatus');
	domDetail.mobileCaAmount = document.getElementById('mobileCaAmount');
	domDetail.mobileApprovedAmount = document.getElementById('mobileApprovedAmount');
	domDetail.mobileTotal = document.getElementById('mobileTotal');
	domDetail.mobileVariance = document.getElementById('mobileVariance');
	domDetail.mobileCaRef = document.getElementById('mobileCaRef');
	domDetail.mobileCaDate = document.getElementById('mobileCaDate');
	domDetail.mobileSubmittedDate = document.getElementById('mobileSubmittedDate');
	domDetail.mobileExpenseDate = document.getElementById('mobileExpenseDate');
	domDetail.mobilePayableTo = document.getElementById('mobilePayableTo');
	domDetail.mobileCostCenter = document.getElementById('mobileCostCenter');
	domDetail.mobileAddress = document.getElementById('mobileAddress');
	domDetail.mobilePurpose = document.getElementById('mobilePurpose');

	if (domDetail.viewExpenseItems) {
		domDetail.viewExpenseItems.addEventListener('click', (e) => {
			const wrap = e.target.closest('[data-lightbox]');
			if (wrap) {
				openLightbox(wrap.getAttribute('data-lightbox'));
				return;
			}
			const btn = e.target.closest('[data-detail-action]');
			if (!btn || !detailExpensesCache) return;
			const action = btn.getAttribute('data-detail-action');
			if (action === 'mOpen') detailSheetIndex = Number(btn.getAttribute('data-detail-index'));
			if (action === 'mClose') detailSheetIndex = null;
			renderMobileDetailItems(detailExpensesCache);
		});
	}
	if (mobileDetailQuery) {
		const onBreakpoint = () => { detailSheetIndex = null; if (detailExpensesCache) renderDetailExpenseItems(detailExpensesCache); };
		if (mobileDetailQuery.addEventListener) mobileDetailQuery.addEventListener('change', onBreakpoint);
		else if (mobileDetailQuery.addListener) mobileDetailQuery.addListener(onBreakpoint);
	}
	const lbEl = document.getElementById('knaLightbox');
	if (lbEl) {
		lbEl.addEventListener('click', (e) => {
			if (e.target === lbEl || e.target.id === 'knaLightboxClose') {
				lbEl.classList.add('d-none');
				document.getElementById('knaLightboxImg').src = '';
			}
		});
	}

	initHistoryModal(['btnShowHistory', 'btnShowHistoryMobile']);
};

const initDetailPage = () => {
	cacheDetailDom();

	const ref = normalizeDate(domDetail.liquidationRef ? domDetail.liquidationRef.value : '');

	if (!ref) {
		if (domDetail.viewExpenseItems) {
			domDetail.viewExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">Record not found.</div>';
		}
		return;
	}

	if (domDetail.viewLiquidationNo) {
		domDetail.viewLiquidationNo.textContent = ref;
	}
	if (domDetail.mobileLiquidationNo) {
		domDetail.mobileLiquidationNo.textContent = ref;
	}

	// Use dedicated endpoint instead of loading all headers
	ajax_loader('transactions/liquidation/api/get/header_by_id', { LiquidationId: ref }).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res.status !== 'success' || !res.data) {
			return;
		}

		const record = res.data;

		if (domDetail.viewLiquidationNo) {
			domDetail.viewLiquidationNo.textContent = normalizeDate(record.liquidation_id);
		}
		if (domDetail.mobileLiquidationNo) {
			domDetail.mobileLiquidationNo.textContent = normalizeDate(record.liquidation_id) || '-';
		}
		if (domDetail.viewCaRef) {
			domDetail.viewCaRef.textContent = normalizeDate(record.cash_advance_id);
		}
		if (domDetail.mobileCaRef) {
			domDetail.mobileCaRef.textContent = normalizeDate(record.cash_advance_id) || '-';
		}
		if (domDetail.viewCaAmount) {
			domDetail.viewCaAmount.textContent = formatPHP(Number(record.ca_amount || 0));
		}
		if (domDetail.mobileCaAmount) {
			domDetail.mobileCaAmount.textContent = formatPHP(Number(record.ca_amount || 0));
		}
		if (domDetail.viewApprovedAmount) {
			domDetail.viewApprovedAmount.textContent = formatPHP(Number(record.approved_amount || 0));
		}
		if (domDetail.mobileApprovedAmount) {
			domDetail.mobileApprovedAmount.textContent = formatPHP(Number(record.approved_amount || 0));
		}
		const submittedDate = normalizeDate(record.submitted_date || '').slice(0, 10) || '-';
		const caDate = normalizeDate(record.ca_date || '').slice(0, 10) || '-';
		if (domDetail.viewCaDate) {
			domDetail.viewCaDate.textContent = caDate;
		}
		if (domDetail.mobileCaDate) {
			domDetail.mobileCaDate.textContent = caDate;
		}
		if (domDetail.viewLiquidatedAmount) {
			domDetail.viewLiquidatedAmount.textContent = formatPHP(Number(record.total_amount_spent || 0));
		}
		if (domDetail.mobileTotal) {
			domDetail.mobileTotal.textContent = formatPHP(Number(record.total_amount_spent || 0));
		}
		if (domDetail.viewStatus) {
			domDetail.viewStatus.innerHTML = getStatusBadge(normalizeDate(record.status_name));
		}
		if (domDetail.mobileStatus) {
			domDetail.mobileStatus.textContent = normalizeDate(record.status_name) || '-';
		}
		if (domDetail.viewSubmittedDate) {
			domDetail.viewSubmittedDate.textContent = submittedDate;
		}
		if (domDetail.mobileSubmittedDate) {
			domDetail.mobileSubmittedDate.textContent = submittedDate;
		}

		// New fields from SP
		if (domDetail.viewPayableTo) {
			domDetail.viewPayableTo.textContent = normalizeDate(record.payable_to || '') || '-';
		}
		if (domDetail.mobilePayableTo) {
			domDetail.mobilePayableTo.textContent = normalizeDate(record.payable_to || '') || '-';
		}
		if (domDetail.viewAddress) {
			domDetail.viewAddress.textContent = normalizeDate(record.address || '') || '-';
		}
		if (domDetail.mobileAddress) {
			domDetail.mobileAddress.textContent = normalizeDate(record.address || '') || '-';
		}
		if (domDetail.viewCostCenter) {
			const ccId = record.cost_center_id || '';
			const ccName = record.cost_center_name || '';
			const costCenterText = ccId && ccName ? `${ccId} - ${ccName}` : (normalizeDate(ccId || ccName) || '-');
			domDetail.viewCostCenter.textContent = costCenterText;
			if (domDetail.mobileCostCenter) {
				domDetail.mobileCostCenter.textContent = costCenterText;
			}
		}

		const refund = Number(record.refund_amount || 0);
		const reimburse = Number(record.reimburse_amount || 0);
		const variance = getVariancePresentation(refund, reimburse);
		if (domDetail.viewVariance) {
			domDetail.viewVariance.innerHTML = variance.desktopHtml;
		}
		if (domDetail.mobileVariance) {
			domDetail.mobileVariance.textContent = variance.mobileText;
		}
		setDetailMobileVariance(refund, reimburse);
		if (domDetail.viewPurpose) {
			domDetail.viewPurpose.textContent = normalizeDate(record.description || '') || '-';
		}
		if (domDetail.mobilePurpose) {
			domDetail.mobilePurpose.textContent = normalizeDate(record.description || '') || '-';
		}

		const statusCode = normalizeDate(record.status_code || '');
		const isSubmitted = statusCode === 'LQ_SUBMITTED' || statusCode === 'LQ_REJECTED';
		const currentUserId = Number(window.currentUserId || 0);
		const createdById = Number(record.created_by_id || 0);

		if (isSubmitted && createdById === currentUserId) {
			[domDetail.btnEditLiquidation, document.getElementById('btnEditLiquidationDesktop')].forEach((btn) => {
				if (!btn) return;
				btn.classList.remove('d-none');
				btn.href = `${base_url}transactions/liquidation/edit/${encodeURIComponent(ref)}`;
			});
		}
		updateDetailMobileSummary();
	}).fail(() => {
		// Silently fail - details will still try to load
	});

	ajax_loader('transactions/liquidation/api/get/details', { LiquidationId: ref }).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res.status !== 'success') {
			if (domDetail.viewExpenseItems) {
				domDetail.viewExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">Could not load expense items.</div>';
			}
			return;
		}

		const expenses = res.data || [];
		
		fetchApprovalStatusForItems(ref, expenses).then((expensesWithStatus) => {
			renderDetailExpenseItems(expensesWithStatus);

			if (domDetail.viewExpenseDate && expensesWithStatus.length) {
				const dates = expensesWithStatus
					.map((e) => normalizeDate(e.document_date || '').slice(0, 10))
					.filter(Boolean)
					.sort();
				const first = dates[0] || '-';
				const last = dates[dates.length - 1] || '-';
				const rangeText = first === last ? first : `${first} \u2013 ${last}`;
				domDetail.viewExpenseDate.textContent = rangeText;
				if (domDetail.mobileExpenseDate) {
					domDetail.mobileExpenseDate.textContent = rangeText;
				}
			}

			loadAuditTrail();
		});
	}).fail(() => {
		if (domDetail.viewExpenseItems) {
			domDetail.viewExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">Could not load expense items.</div>';
		}
	});
};

const fetchApprovalStatusForItems = (liquidationId, expenses) => {
	return new Promise((resolve) => {
		ajax_loader('transactions/liquidation/api/get/for_edit', { LiquidationId: liquidationId }).done((response) => {
			const res = (typeof response === 'string') ? $.parseJSON(response) : response;
			if (res.status !== 'success' || !res.data || !res.data.details) {
				resolve(expenses);
				return;
			}

			const detailsWithStatus = res.data.details || [];
			const statusMap = {};
			
			detailsWithStatus.forEach((detail) => {
				statusMap[Number(detail.id)] = {
					has_approved: detail.has_approved,
					has_rejected: detail.has_rejected,
					rejection_reason: detail.rejection_reason,
					rejected_by_name: detail.rejected_by_name,
				};
			});

			const merged = expenses.map((expense) => {
				const detailId = Number(expense.id || 0);
				const status = statusMap[detailId] || {};
				return {
					...expense,
					has_approved: status.has_approved || 0,
					has_rejected: status.has_rejected || 0,
					rejection_reason: status.rejection_reason || '',
					rejected_by_name: status.rejected_by_name || '',
				};
			});

			resolve(merged);
		}).fail(() => {
			resolve(expenses);
		});
	});
};