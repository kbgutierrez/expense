const domDetail = {
	reimbursementRef: null,
	viewReimbursementNo: null,
	viewDescription: null,
	viewExpenseDate: null,
	viewTotalAmount: null,
	viewStatus: null,
	viewSubmittedDate: null,
	viewPayableTo: null,
	viewAddress: null,
	viewCostCenter: null,
	viewIoNumber: null,
	viewExpenseItems: null,
	viewTimeline: null,
	btnEditReimbursement: null,
};

const IMG_EXTS = /\.(jpg|jpeg|png|gif|webp)$/i;

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

const renderDetailExpenseItems = (expenses) => {
	const container = domDetail.viewExpenseItems;
	if (!container) {
		return;
	}

	if (!expenses || !expenses.length) {
		container.innerHTML = '<div class="text-muted kna-small py-2">No expense items found.</div>';
		return;
	}

	const rowsHtml = expenses
		.map((expense, i) => {
			const docDate = normalizeDate(expense.document_date || '').slice(0, 10);
			const category = normalizeDate(expense.category_name || '');
			const reference = normalizeDate(expense.invoice_receipt_no || '') || '—';
			const amount = Number(expense.approved_amount || expense.actual_amount || 0);
			const netAmt = Number(expense.net_amount || 0);
			const vatAmt = Number(expense.vat_amount || 0);
			const isVattable = Boolean(Number(expense.is_vatable));
			const description = normalizeDate(expense.description || '');
			const vendorName = normalizeDate(expense.vendor_name || '');

			const attachNames = normalizeDate(expense.attachment || '')
				.split(',')
				.map((s) => s.trim())
				.filter(Boolean);
			const attachHtml = attachNames.length
				? attachNames.map(renderAttachment).join('')
				: '<span class="text-muted" style="font-size:11px;">—</span>';

			const vatBadge = isVattable
				? '<input type="checkbox" class="kna-vat-check" checked disabled>'
				: '<input type="checkbox" class="kna-vat-check" disabled>';

			const amountHtml = isVattable
				? `<div class="kna-amount-main">${formatPHP(amount)}</div><div class="kna-amount-breakdown">Net ${formatPHP(netAmt)}</div><div class="kna-amount-breakdown">VAT ${formatPHP(vatAmt)}</div>`
				: `<div class="kna-amount-main">${formatPHP(amount)}</div>`;

			const hasApproved = Boolean(Number(expense.has_approved || 0));
			const hasRejected = Boolean(Number(expense.has_rejected || 0));
			const rejectionReason = normalizeDate(expense.rejection_reason || '');
			const rejectedByName = normalizeDate(expense.rejected_by_name || '');

			let statusBadge = '';
			if (hasApproved) {
				statusBadge = `<div class="kna-approved-badge" style="margin-top:4px;"><i class="fas fa-check"></i> Approved</div>`;
			} else if (hasRejected) {
				statusBadge = `<div class="kna-rejected-badge" style="margin-top:4px;"><i class="fas fa-times"></i> Rejected by ${escapeHtml(rejectedByName)}</div>
					<div class="kna-rejected-reason" style="font-size:11px;color:#991b1b;font-style:italic;">"${escapeHtml(rejectionReason)}"</div>`;
			}

			return `
				<tr>
					<td class="text-center kna-rownum kna-cell-index" data-label="#">${i + 1}</td>
					<td data-label="Date">${escapeHtml(docDate)}</td>
					<td data-label="Category">${escapeHtml(category)}</td>
					<td data-label="Reference">${escapeHtml(reference)}</td>
					<td data-label="Vendor">${escapeHtml(vendorName || '—')}</td>
					<td class="text-center kna-cell-vat" data-label="VAT">${vatBadge}</td>
					<td class="kna-cell-attachment" data-label="Attachment">${attachHtml}</td>
					<td data-label="Remarks">${escapeHtml(description)}${statusBadge}</td>
					<td class="text-right kna-cell-amount" data-label="Amount">${amountHtml}</td>
				</tr>
			`;
		})
		.join('');

	const mobileCardsHtml = expenses
		.map((expense, i) => {
			const docDate = normalizeDate(expense.document_date || '').slice(0, 10);
			const category = normalizeDate(expense.category_name || '');
			const reference = normalizeDate(expense.invoice_receipt_no || '') || '—';
			const amount = Number(expense.approved_amount || expense.actual_amount || 0);
			const netAmt = Number(expense.net_amount || 0);
			const vatAmt = Number(expense.vat_amount || 0);
			const isVattable = Boolean(Number(expense.is_vatable));
			const description = normalizeDate(expense.description || '') || '—';
			const vendorName = normalizeDate(expense.vendor_name || '');

			const attachNames = normalizeDate(expense.attachment || '')
				.split(',')
				.map((s) => s.trim())
				.filter(Boolean);

			const amountHtml = isVattable
				? `<div class="kna-amount-main">${formatPHP(amount)}</div><div class="kna-amount-breakdown">Net ${formatPHP(netAmt)}</div><div class="kna-amount-breakdown">VAT ${formatPHP(vatAmt)}</div>`
				: `<div class="kna-amount-main">${formatPHP(amount)}</div>`;

			const hasApproved = Boolean(Number(expense.has_approved || 0));
			const hasRejected = Boolean(Number(expense.has_rejected || 0));
			const rejectionReason = normalizeDate(expense.rejection_reason || '');
			const rejectedByName = normalizeDate(expense.rejected_by_name || '');

			let statusBadge = '';
			if (hasApproved) {
				statusBadge = `<div class="kna-approved-badge" style="margin-top:6px;"><i class="fas fa-check"></i> Approved</div>`;
			} else if (hasRejected) {
				statusBadge = `<div class="kna-rejected-badge" style="margin-top:6px;"><i class="fas fa-times"></i> Rejected by ${escapeHtml(rejectedByName)}</div>
					<div class="kna-rejected-reason" style="font-size:11px;color:#991b1b;font-style:italic;">"${escapeHtml(rejectionReason)}"</div>`;
			}

			return `
				<div class="kna-exp-card">
					<div class="kna-exp-card-head">
						<div>
							<div class="kna-exp-card-title">${escapeHtml(category || 'Expense Item')} <span class="kna-exp-card-sub">#${i + 1}</span></div>
							<div class="kna-exp-card-meta">${escapeHtml(docDate)} • ${escapeHtml(reference)}</div>
							${statusBadge}
						</div>
						<div class="kna-exp-card-amount">${amountHtml}</div>
					</div>

					<div class="kna-exp-card-grid">
						<div class="kna-exp-card-field">
							<span class="kna-exp-card-label">VAT</span>
							<span class="kna-exp-card-value">
								<input type="checkbox" class="kna-vat-check" ${isVattable ? 'checked' : ''} disabled>
							</span>
						</div>
						<div class="kna-exp-card-field">
							<span class="kna-exp-card-label">Vendor</span>
							<span class="kna-exp-card-value">${escapeHtml(vendorName || '—')}</span>
						</div>
						<div class="kna-exp-card-field kna-exp-card-field-full">
							<span class="kna-exp-card-label">Remarks</span>
							<span class="kna-exp-card-value">${escapeHtml(description)}</span>
						</div>
					</div>

					<div class="kna-exp-card-field kna-exp-card-field-full">
						<span class="kna-exp-card-label">Attachment</span>
						<span class="kna-exp-card-value kna-exp-card-attach">
							${attachNames.length ? attachNames.map(renderAttachment).join('') : '<span class="text-muted">—</span>'}
						</span>
					</div>
				</div>
			`;
		})
		.join('');

	const total    = expenses.reduce((sum, e) => sum + Number(e.approved_amount || e.actual_amount || 0), 0);
	const totalNet = expenses.reduce((sum, e) => sum + Number(e.net_amount    || 0), 0);
	const totalVat = expenses.reduce((sum, e) => sum + Number(e.vat_amount    || 0), 0);

	container.innerHTML = `
		<div class="kna-exp-wrap">
			<div class="kna-exp-mobile">${mobileCardsHtml}</div>
			<table class="kna-exp-table">
				<thead>
					<tr>
						<th style="width:34px;">#</th>
						<th style="width:96px;">Date</th>
						<th style="width:120px;">Category</th>
						<th style="width:110px;">Reference</th>
						<th style="width:120px;">Vendor</th>
						<th style="width:72px;" class="text-center">VAT</th>
						<th style="min-width:110px;">Attachment</th>
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

/* ─── TIMELINE ─── */
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
	if (raw === '') return '<span class="text-muted">—</span>';
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
	// fallback ("rmb for release the request").
	RMB_FOR_RELEASE: 'advised payment for', RMB_PAID: 'released payment for',
};
const auditActionVerb = (action) => AUDIT_ACTION_VERBS[action] || action.toLowerCase().replace(/_/g, ' ');
const joinAuditVerbs = (actions) => {
	const verbs = [...new Set(actions.map(auditActionVerb))];
	if (verbs.length === 1) return verbs[0];
	if (verbs.length === 2) return `${verbs[0]} and ${verbs[1]}`;
	return `${verbs.slice(0, -1).join(', ')}, and ${verbs[verbs.length - 1]}`;
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

	let entityDesc = 'the reimbursement';
	if (transactionType === 'cash_advance') {
		entityDesc = 'the cash advance';
	} else if (transactionType === 'liquidation') {
		entityDesc = 'the liquidation';
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
	const ref = normalizeDate(domDetail.reimbursementRef ? domDetail.reimbursementRef.value : '');
	if (!ref) return;

	ajax_loader('transactions/reimbursement/api/timeline', { ReferenceNo: ref }).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res.status !== 'success') {
			renderHistoryTimeline([]);
			return;
		}
		renderHistoryTimeline(res.data && res.data.audit_trail ? res.data.audit_trail : []);
	}).fail(() => renderHistoryTimeline([]));
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

/* ─── DETAIL PAGE INIT ─── */
const cacheDetailDom = () => {
	domDetail.reimbursementRef = document.getElementById('reimbursementRef');
	domDetail.viewReimbursementNo = document.getElementById('viewReimbursementNo');
	domDetail.viewDescription = document.getElementById('viewDescription');
	domDetail.viewExpenseDate = document.getElementById('viewExpenseDate');
	domDetail.viewTotalAmount = document.getElementById('viewTotalAmount');
	domDetail.viewApprovedAmount = document.getElementById('viewApprovedAmount');
	domDetail.viewStatus = document.getElementById('viewStatus');
	domDetail.viewSubmittedDate = document.getElementById('viewSubmittedDate');
	domDetail.viewPayableTo = document.getElementById('viewPayableTo');
	domDetail.viewAddress = document.getElementById('viewAddress');
	domDetail.viewCostCenter = document.getElementById('viewCostCenter');
	domDetail.viewIoNumber = document.getElementById('viewIoNumber');
	domDetail.viewExpenseItems = document.getElementById('viewExpenseItems');
	domDetail.viewTimeline = document.getElementById('viewTimeline');
	domDetail.btnEditReimbursement = document.getElementById('btnEditReimbursement');

	const lbEl = document.getElementById('knaLightbox');
	if (lbEl) {
		lbEl.addEventListener('click', (e) => {
			if (e.target === lbEl || e.target.id === 'knaLightboxClose') {
				lbEl.classList.add('d-none');
				document.getElementById('knaLightboxImg').src = '';
			}
		});
	}

	if (domDetail.viewExpenseItems) {
		domDetail.viewExpenseItems.addEventListener('click', (e) => {
			const wrap = e.target.closest('[data-lightbox]');
			if (wrap) {
				openLightbox(wrap.getAttribute('data-lightbox'));
			}
		});
	}

	initHistoryModal();
};

const loadReimbursementDetail = () => {
	const ref = normalizeDate(domDetail.reimbursementRef ? domDetail.reimbursementRef.value : '');
	if (!ref) {
		if (domDetail.viewExpenseItems) {
			domDetail.viewExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">No reimbursement reference provided.</div>';
		}
		return;
	}

	ajax_loader('transactions/reimbursement/api/get', { ReimbursementId: ref }).done((response) => {
		const res = (typeof response === 'string') ? $.parseJSON(response) : response;
		if (res.status !== 'success' || !res.data || !res.data.header) {
			if (domDetail.viewExpenseItems) {
				domDetail.viewExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">Reimbursement record not found.</div>';
			}
			return;
		}

		const payload = res.data;
		const header = payload.header;
		const details = payload.details || [];

		if (domDetail.viewReimbursementNo) domDetail.viewReimbursementNo.textContent = normalizeDate(header.reimbursement_id);
		if (domDetail.viewStatus) domDetail.viewStatus.innerHTML = getStatusBadge(normalizeDate(header.status_name));
		if (domDetail.viewSubmittedDate) domDetail.viewSubmittedDate.textContent = normalizeDate(header.submitted_date || '').slice(0, 10) || '-';
		if (domDetail.viewDescription) domDetail.viewDescription.textContent = normalizeDate(header.description || '') || '-';
		if (domDetail.viewTotalAmount) domDetail.viewTotalAmount.textContent = formatPHP(Number(header.total_amount || 0));
		if (domDetail.viewApprovedAmount) {
			const approvedTotal = details.reduce((sum, e) => sum + Number(e.approved_amount || e.actual_amount || 0), 0);
			domDetail.viewApprovedAmount.textContent = formatPHP(approvedTotal);
		}
		if (domDetail.viewPayableTo) domDetail.viewPayableTo.textContent = normalizeDate(header.payable_to || '') || '-';
		if (domDetail.viewAddress) domDetail.viewAddress.textContent = normalizeDate(header.address || '') || '-';
		if (domDetail.viewIoNumber) domDetail.viewIoNumber.textContent = normalizeDate(header.io_number || '') || '-';
		if (domDetail.viewCostCenter) {
			const ccCode = normalizeDate(header.cost_center_id || '');
			const ccName = normalizeDate(header.cost_center_name || '');
			domDetail.viewCostCenter.textContent = ccCode ? `${ccCode}${ccName ? ' - ' + ccName : ''}` : '-';
		}

		renderDetailExpenseItems(details);

		if (domDetail.viewExpenseDate && details.length) {
			const dates = details
				.map((e) => normalizeDate(e.document_date || '').slice(0, 10))
				.filter(Boolean)
				.sort();
			const first = dates[0] || '-';
			const last = dates[dates.length - 1] || '-';
			domDetail.viewExpenseDate.textContent = first === last ? first : `${first} – ${last}`;
		}

		const currentUserId = Number(window.currentUserId || 0);
		const createdById = Number(header.created_by_id || header.created_by || 0);
		const statusCode = normalizeDate(header.status_code || '');
		if (domDetail.btnEditReimbursement) {
			const canEdit = createdById === currentUserId && (statusCode === 'RMB_SUBMITTED' || statusCode === 'RMB_REJECTED');
			if (canEdit) {
				domDetail.btnEditReimbursement.classList.remove('d-none');
				domDetail.btnEditReimbursement.href = `${base_url}transactions/reimbursement/edit/${encodeURIComponent(ref)}`;
			} else {
				domDetail.btnEditReimbursement.classList.add('d-none');
			}
		}
	}).fail(() => {
		if (domDetail.viewExpenseItems) {
			domDetail.viewExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">Could not load reimbursement details.</div>';
		}
	});
};

const initDetailPage = () => {
	cacheDetailDom();
	loadReimbursementDetail();
	loadAuditTrail();
};
