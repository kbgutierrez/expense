let editExpenseItems = [];
let editExpenseItemCounter = 0;
let editExpenseTypeOptions = [];
let editPayableToOptions = [];
let editReimbursementData = null;
let editReceiptOcr = null;

const domEdit = {};
const EDIT_IMG_EXTS = /\.(jpg|jpeg|png|gif|webp)$/i;
const MAX_ATTACHMENT_BYTES = 2 * 1024 * 1024;
const editObjectUrls = new Map();

// ─── Helpers (page is loaded standalone, without index.js) ───
const qs = (sel, ctx = document) => ctx.querySelector(sel);
const normalizeDate = (value) => (value ? String(value) : '');
const formatPHP = (amount) => Number(amount || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
const escapeHtml = (value = '') =>
	String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
const parseRes = (r) => (typeof r === 'string' ? jQuery.parseJSON(r) : r);
const safeNum = (v) => Number(v || 0);
const goToPath = (path) => { window.location.href = `${base_url}${path}`; };
const getStatusBadge = (status) => {
	const map = { Draft: 'draft', Approved: 'approved', Rejected: 'rejected' };
	const cls = map[status] || 'pending';
	return `<span class="kna-badge kna-badge-${cls}">${status || 'Submitted'}</span>`;
};

// ─── Object URL Management ───
const storeEditObjectUrl = (editId, url) => {
	if (!editObjectUrls.has(editId)) editObjectUrls.set(editId, []);
	editObjectUrls.get(editId).push(url);
};

// ─── Attachment Helpers ───
const getEditItemAttachmentNamesCsv = (item) => {
	const newFiles = (item.newAttachments || []).map((f) => f.name || f.fileName || '').filter(Boolean);
	if (newFiles.length) return newFiles.join(',');
	const kept = (item.existingAttachments || []).map(normalizeDate).filter(Boolean)
		.filter((name) => !(item.removedAttachments || []).includes(name));
	return kept.join(',');
};

const getEditExpenseTypeDisplayText = (option) => {
	const code = normalizeDate(option.expenseCode || option.expense_code || '');
	const longText = normalizeDate(option.longText || option.long_text || option.description || '');
	return code && longText ? `${code} - ${longText}` : (code || longText || 'Select type');
};

const getEditExpenseTypeByCode = (code) => editExpenseTypeOptions.find((item) => String(item.expenseCode) === String(code));

const editExpenseTypeOptionsMarkup = (selectedValue) =>
	`<option value="">Select type</option>${editExpenseTypeOptions.map((option) => {
		const code = normalizeDate(option.expenseCode);
		const desc = normalizeDate(option.description);
		return `<option value="${escapeHtml(code)}" title="${escapeHtml(desc)}" ${String(selectedValue) === String(code) ? 'selected' : ''}>${escapeHtml(getEditExpenseTypeDisplayText(option))}</option>`;
	}).join('')}`;

const initEditCostCenterSelect2 = () => {
	const el = document.getElementById('editCostCenter');
	if (!el || typeof jQuery.fn?.select2 === 'undefined') return;
	const $s = jQuery(el);
	if ($s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
	const $dropdownParent = $s.closest('.page-inner').length ? $s.closest('.page-inner') : jQuery(document.body);
	$s.select2({ placeholder: 'Select cost center', allowClear: false, width: '100%', dropdownAutoWidth: false, minimumResultsForSearch: 5, dropdownParent: $dropdownParent });
	const $c = $s.next('.select2-container');
	$c.find('.select2-selection--single').css({ height: '32px', border: '1px solid #ced4da', borderRadius: '4px', background: '#fff', fontSize: '12px' });
	$c.find('.select2-selection__rendered').css({ lineHeight: '30px', paddingLeft: '10px', paddingRight: '20px', color: '#495057' });
	$c.find('.select2-selection__arrow').css({ height: '30px', width: '20px' });
	$c.find('.select2-selection__arrow b').css({ borderWidth: '3px 3px 0 3px', marginTop: '-2px' });
};

const initEditPayableToSelect2 = () => {
	const el = document.getElementById('editPayableTo');
	if (!el || typeof jQuery.fn?.select2 === 'undefined') return;
	const $s = jQuery(el);
	if ($s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
	const $dropdownParent = $s.closest('.page-inner').length ? $s.closest('.page-inner') : jQuery(document.body);
	$s.select2({ placeholder: 'Select payable to', allowClear: true, width: '100%', dropdownAutoWidth: false, dropdownParent: $dropdownParent });
	const $c = $s.next('.select2-container');
	$c.find('.select2-selection--single').css({ height: '32px', border: '1px solid #ced4da', borderRadius: '4px', background: '#fff', fontSize: '12px' });
	$c.find('.select2-selection__rendered').css({ lineHeight: '30px', paddingLeft: '10px', paddingRight: '20px', color: '#495057' });
	$c.find('.select2-selection__arrow').css({ height: '30px', width: '20px' });
	$c.find('.select2-selection__arrow b').css({ borderWidth: '3px 3px 0 3px', marginTop: '-2px' });
};

const getEditPayableToOptionsMarkup = (selectedUserId, fallbackDisplayText) => {
	const selected = normalizeDate(selectedUserId);
	let opts = editPayableToOptions.map((u) => {
		const value = escapeHtml(String(u.id));
		const text = escapeHtml(`${u.firstname} ${u.lastname}${u.designation ? ' (' + u.designation + ')' : ''}`);
		const isSelected = value === selected ? ' selected' : '';
		return `<option value="${value}"${isSelected}>${text}</option>`;
	}).join('');

	// Legacy free-typed rows (or a picked user outside this department)
	// have no matching id in the list — show the resolved text as a
	// disabled placeholder option instead of leaving the field blank,
	// but any real save requires picking a fresh option from the list.
	if (!selected && fallbackDisplayText) {
		opts = `<option value="" selected disabled>${escapeHtml(fallbackDisplayText)} (re-select to change)</option>${opts}`;
	}

	return `<option value="">Select payable to</option>${opts}`;
};

const loadEditPayableToOptions = (callback) => {
	ajax_loader('transactions/reimbursement/api/get/payable-to-options', {}).done((response) => {
		const res = parseRes(response);
		editPayableToOptions = (res.status === 'success' && Array.isArray(res.data)) ? res.data : [];
		if (callback) callback();
	}).fail(() => { editPayableToOptions = []; if (callback) callback(); });
};

const getEditCostCenterOptions = (selectedValue) => {
	const dataEl = document.getElementById('costCentersData');
	if (!dataEl) return '<option value="">No options available</option>';
	try {
		const centers = JSON.parse(dataEl.value);
		if (!Array.isArray(centers) || centers.length === 0) return '<option value="">No options available</option>';
		const opts = centers.map((cc) => {
			const value = escapeHtml(cc.cost_center_code || '');
			const text = escapeHtml((cc.cost_center_code ? cc.cost_center_code + ' - ' : '') + (cc.cost_center_name || ''));
			const selected = value === normalizeDate(selectedValue) ? ' selected' : '';
			return `<option value="${value}"${selected}>${text}</option>`;
		}).join('');
		return `<option value="">Select cost center</option>${opts}`;
	} catch (e) {
		return '<option value="">Error loading options</option>';
	}
};

// ─── Lightbox ───
const openEditLightbox = (url) => {
	const lb = document.getElementById('knaLightbox');
	const img = document.getElementById('knaLightboxImg');
	if (lb && img) { img.src = url; lb.classList.remove('d-none'); }
};

// ─── Attachment Rendering ───
const renderEditAttachment = (name, item, isRemoved = false) => {
	const url = `${base_url}assets/uploads/attachments/${encodeURIComponent(name)}`;
	const removeBtn = !isRemoved ? `<button type="button" class="kna-thumb-remove" data-edit-action="removeAttachment" data-edit-id="${item._editId}" data-filename="${escapeHtml(name)}" title="Remove attachment">&#x2715;</button>` : '';
	const undoLink = isRemoved ? `<span class="kna-attach-undo" data-edit-action="undoRemoveAttachment" data-edit-id="${item._editId}" data-filename="${escapeHtml(name)}">Undo</span>` : '';

	if (EDIT_IMG_EXTS.test(name)) {
		return `<span class="kna-thumb-wrap ${isRemoved ? 'removed' : ''}" data-lightbox="${escapeHtml(url)}" data-filename="${escapeHtml(name)}" data-edit-id="${item._editId}">
			${removeBtn}<img class="kna-thumb" src="${url}" alt="${escapeHtml(name)}" loading="lazy"><span class="kna-thumb-label">${escapeHtml(name)}</span>${undoLink}
		</span>`;
	}
	const fileRemove = !isRemoved ? `<i class="fas fa-times kna-file-remove" data-edit-action="removeAttachment" data-edit-id="${item._editId}" data-filename="${escapeHtml(name)}" title="Remove attachment"></i>` : '';
	return `<span class="kna-file-wrap ${isRemoved ? 'removed' : ''}" data-filename="${escapeHtml(name)}" data-edit-id="${item._editId}">
		<i class="fas fa-file-alt" style="color:#5b2bd6;font-size:11px;"></i>
		<a href="${url}" target="_blank" rel="noopener" title="${escapeHtml(name)}">${escapeHtml(name)}</a>${fileRemove}${undoLink}
	</span>`;
};

// ─── Approval Status ───
const getItemApprovalStatus = (item) => {
	const approvals = item.approvals || [];
	if (!approvals.length) return { status: 'pending', canEdit: true, rejections: [] };
	const rejections = approvals.filter((a) => normalizeDate(a.status) === 'REJECTED');
	const hasApproved = approvals.some((a) => normalizeDate(a.status) === 'APPROVED');
	const allApproved = approvals.every((a) => normalizeDate(a.status) === 'APPROVED');
	if (allApproved) return { status: 'approved', canEdit: false, rejections: [] };
	if (rejections.length) return { status: 'rejected', canEdit: true, rejections };
	if (hasApproved) return { status: 'partial', canEdit: false, rejections: [] };
	return { status: 'pending', canEdit: true, rejections: [] };
};

const buildRejectionRibbon = (rejections) => {
	if (!rejections?.length) return '';
	const pills = rejections.map((rej) => {
		const approver = escapeHtml(normalizeDate(rej.approver_name || rej.approver || 'Approver'));
		const reason = escapeHtml(normalizeDate(rej.remarks || rej.rejection_reason || 'No reason provided'));
		return `<span class="kna-rejection-pill"><i class="fas fa-times-circle"></i> <strong>${approver}:</strong> "${reason}"</span>`;
	}).join('');
	return `<div class="kna-rejection-ribbon"><span class="kna-rejection-ribbon-label"><i class="fas fa-exclamation-circle"></i> Rejected</span>${pills}</div>`;
};

// ─── Item Summary ───
const getEditExpenseItemSummary = (item) => {
	const approval = getItemApprovalStatus(item);
	const isLocked = !approval.canEdit;
	const isRejected = approval.status === 'rejected';
	const et = getEditExpenseTypeByCode(item.expense_category || '');
	const newAttachments = item.newAttachments || [];
	const ocrState = editReceiptOcr ? editReceiptOcr.getItemOcrState(item._editId) : { status: 'idle' };

	return {
		approval, isLocked, isRejected,
		rowClass: isLocked ? 'kna-row-locked' : (isRejected ? 'kna-row-rejected' : ''),
		disabledAttr: isLocked ? 'disabled' : '',
		lockIcon: isLocked ? '<i class="fas fa-lock kna-lock-icon" title="Approved — cannot edit"></i> ' : '',
		docDate: normalizeDate(item.document_date || '').slice(0, 10),
		category: normalizeDate(item.expense_category || ''),
		selectedExpenseTypeText: getEditExpenseTypeDisplayText(et || {}),
		reference: normalizeDate(item.invoice_receipt_no || '') || '—',
		amount: Number(item.actual_amount || 0),
		isVattable: Boolean(Number(item.is_vatable || 0)),
		description: normalizeDate(item.description || '') || '—',
		existingAttachments: item.existingAttachments || [],
		removedAttachments: item.removedAttachments || [],
		newAttachments,
		ocrState,
		isOcrLoading: ocrState.status === 'scanning',
		hasCompressed: newAttachments.some((f) => f._wasCompressed),
	};
};

// ─── OCR Status ───
const buildEditOcrStatusHtml = (editId) => {
	if (!editReceiptOcr) return '';
	const s = editReceiptOcr.getItemOcrState(editId);
	if (!s || s.status === 'idle') return '';
	const manualBtn = `<button type="button" class="kna-ocr-manual-btn" data-edit-action="ocrManual" data-edit-id="${editId}">Enter manually</button>`;
	const map = {
		scanning: `<div class="kna-ocr-status kna-ocr-scanning"><i class="fas fa-spinner fa-spin"></i> <span>Reading receipt…</span>${manualBtn}</div>`,
		success: (() => {
			const fields = (s.appliedFields || []).map((f) => ({ date: 'Date', category: 'Category', ref: 'Ref', amount: 'Amount', vat: 'VAT', desc: 'Desc' }[f] || f)).join(', ');
			return `<div class="kna-ocr-status kna-ocr-success"><i class="fas fa-check-circle"></i> <span>${escapeHtml(fields ? `Auto-filled: ${fields}` : 'Receipt read')}</span></div>`;
		})(),
		timeout: `<div class="kna-ocr-status kna-ocr-error"><i class="fas fa-exclamation-triangle"></i> <span>${escapeHtml(s.error)}</span>${manualBtn}</div>`,
		error: `<div class="kna-ocr-status kna-ocr-error"><i class="fas fa-exclamation-triangle"></i> <span>${escapeHtml(s.error)}</span>${manualBtn}</div>`,
		manual: `<div class="kna-ocr-status kna-ocr-manual"><i class="fas fa-hand-pointer"></i> <span>Manual entry</span></div>`,
	};
	return map[s.status] || '';
};

// ─── Attachment Cell Builder ───
const buildAttachmentCell = (item, summary) => {
	let attachHtml = '<span class="text-muted" style="font-size:11px;">—</span>';
	let hasAttachment = false;

	if (summary.newAttachments.length > 0) {
		const file = summary.newAttachments[0];
		const name = file.name || file.fileName || 'New file';
		let objectUrl = file._objectUrl;
		if (!objectUrl) { objectUrl = URL.createObjectURL(file); file._objectUrl = objectUrl; storeEditObjectUrl(item._editId, objectUrl); }
		attachHtml = `<span class="kna-thumb-wrap" data-new-file="true" data-edit-id="${item._editId}">
			<img class="kna-thumb" src="${objectUrl}" alt="${escapeHtml(name)}" loading="lazy"><span class="kna-thumb-label">${escapeHtml(name)}</span>
			<button type="button" class="kna-thumb-remove" data-edit-action="removeNewAttachment" data-edit-id="${item._editId}" data-file-index="0" title="Remove">&#x2715;</button>
		</span>`;
		hasAttachment = true;
	} else if (summary.existingAttachments.length > 0) {
		const name = summary.existingAttachments[0];
		const isRemoved = summary.removedAttachments.includes(name);
		attachHtml = renderEditAttachment(name, item, isRemoved);
		hasAttachment = !isRemoved;
	}

	const ocrLoadingIcon = summary.isOcrLoading ? ' <i class="fas fa-spinner fa-spin" title="OCR in progress"></i>' : '';
	const compressedIcon = summary.hasCompressed ? ' <i class="fas fa-compress-alt" title="Compressed"></i>' : '';
	return { attachHtml, hasAttachment, ocrLoadingIcon, compressedIcon };
};

// ─── Item Row Builder ───
const editItemField = (label, cls, inputHtml) => `<div class="kna-item-field ${cls}"><span class="kna-item-field-label">${label}</span>${inputHtml}</div>`;

const buildItemRow = (item, summary, index) => {
	const att = buildAttachmentCell(item, summary);
	const rejectionRibbon = summary.isRejected && summary.approval.rejections.length ? buildRejectionRibbon(summary.approval.rejections) : '';
	const attachBtn = !summary.isLocked ? `<button type="button" class="btn btn-outline-primary btn-sm kna-small" data-edit-action="attach" data-edit-id="${item._editId}">${att.hasAttachment ? 'Replace' : 'Attach'}</button>` : '';
	const rowClasses = ['kna-item-row', summary.rowClass].filter(Boolean).join(' ');

	return `
		<div class="kna-item-row-wrap">
		<div class="${rowClasses}" data-edit-item-id="${item._editId}">
			<div class="kna-item-row-index">${index + 1}${summary.isLocked ? ` ${summary.lockIcon}` : ''}</div>
			<div class="kna-item-row-fields">
				${editItemField('Doc Date', 'kna-f-date', `<input type="date" class="kna-edit-input" data-edit-field="documentDate" data-edit-id="${item._editId}" value="${escapeHtml(summary.docDate)}" ${summary.disabledAttr}>`)}
				${editItemField('Expense Type', 'kna-f-type', `<select class="kna-edit-select" data-edit-field="expenseCategory" data-edit-id="${item._editId}" title="${escapeHtml(summary.selectedExpenseTypeText)}" ${summary.disabledAttr}>${editExpenseTypeOptionsMarkup(summary.category)}</select>`)}
				<div class="kna-item-field kna-f-attach">
					<span class="kna-item-field-label">Attachment</span>
					<div class="kna-item-attach-inline">
						<span class="kna-attachment-cell">${att.attachHtml}${att.ocrLoadingIcon}${att.compressedIcon}</span>
						${attachBtn}
						<input type="file" class="d-none" data-edit-file="upload" data-edit-id="${item._editId}" accept="image/*,application/pdf">
						<input type="file" class="d-none" data-edit-file="camera" data-edit-id="${item._editId}" accept="image/*" capture="environment">
					</div>
					${buildEditOcrStatusHtml(item._editId)}
				</div>
				${editItemField('Reference', 'kna-f-ref', `<input type="text" class="kna-edit-input" data-edit-field="reference" data-edit-id="${item._editId}" value="${escapeHtml(summary.reference)}" placeholder="Invoice / OR no." ${summary.disabledAttr}>`)}
				${editItemField('Amount', 'kna-f-amount', `<input type="number" min="0" step="0.01" class="kna-edit-input kna-edit-number" data-edit-field="amount" data-edit-id="${item._editId}" value="${escapeHtml(summary.amount)}" placeholder="0.00" ${summary.disabledAttr}>`)}
				${editItemField('VAT', 'kna-f-vat', `<label class="kna-vat-wrap"><input type="checkbox" class="kna-edit-checkbox" data-edit-field="isVattable" data-edit-id="${item._editId}" ${summary.isVattable ? 'checked' : ''} ${summary.disabledAttr}></label>`)}
				${editItemField('Vendor Name', 'kna-f-vendor', `<input type="text" class="kna-edit-input" data-edit-field="vendorName" data-edit-id="${item._editId}" value="${escapeHtml(item.vendor_name || '')}" placeholder="Vendor name" ${summary.disabledAttr}>`)}
				${editItemField('Vendor Address', 'kna-f-vendor', `<input type="text" class="kna-edit-input" data-edit-field="vendorAddress" data-edit-id="${item._editId}" value="${escapeHtml(item.vendor_address || '')}" placeholder="Address" ${summary.disabledAttr}>`)}
				${editItemField('TIN', 'kna-f-tin', `<input type="text" class="kna-edit-input" data-edit-field="vendorTin" data-edit-id="${item._editId}" value="${escapeHtml(item.vendor_tin || '')}" placeholder="TIN" ${summary.disabledAttr}>`)}
				${editItemField('Remarks', 'kna-f-remarks', `<input type="text" class="kna-edit-input" data-edit-field="description" data-edit-id="${item._editId}" value="${escapeHtml(summary.description)}" placeholder="Remarks" ${summary.disabledAttr}>`)}
			</div>
			${!summary.isLocked
				? `<button type="button" class="kna-item-row-remove" data-edit-action="remove" data-edit-id="${item._editId}" title="Remove item"><i class="fas fa-trash"></i></button>`
				: '<span class="kna-item-row-lock" title="Approved — cannot edit"><i class="fas fa-lock"></i></span>'}
		</div>
		${rejectionRibbon}
		</div>`;
};

// ─── Render Items ───
const renderEditExpenseItems = () => {
	const container = domEdit.editExpenseItems;
	if (!container) return;

	if (!editExpenseItems?.length) {
		container.innerHTML = '<div class="text-muted kna-small py-2">No expense items found.</div>';
		if (domEdit.editTotalAmount) domEdit.editTotalAmount.textContent = formatPHP(0);
		if (domEdit.editExpenseDate) domEdit.editExpenseDate.textContent = '-';
		return;
	}

	let rejectedCount = 0;
	const rowsHtml = editExpenseItems.map((item, i) => {
		const summary = getEditExpenseItemSummary(item);
		if (summary.isRejected) rejectedCount++;
		return buildItemRow(item, summary, i);
	}).join('');

	const total = editExpenseItems.reduce((sum, e) => sum + Number(e.actual_amount || e.amount || 0), 0);
	const totalNet = editExpenseItems.reduce((sum, e) => sum + Number(e.net_amount || 0), 0);
	const totalVat = editExpenseItems.reduce((sum, e) => sum + Number(e.vat_amount || 0), 0);

	container.innerHTML = `
		<div class="kna-item-rows">${rowsHtml}</div>
		<div class="kna-mobile-summary">
			<div class="kna-fin-label">Total</div>
			<div class="kna-fin-value">${formatPHP(total)}</div>
			<div style="font-size:10px;color:#9ca3af;margin-top:2px;">Net ${formatPHP(totalNet)} • VAT ${formatPHP(totalVat)}</div>
		</div>`;

	if (domEdit.rejectedBanner && domEdit.rejectedCount) {
		domEdit.rejectedBanner.classList.toggle('d-none', rejectedCount === 0);
		if (rejectedCount > 0) domEdit.rejectedCount.textContent = rejectedCount;
	}
	if (domEdit.editTotalAmount) domEdit.editTotalAmount.textContent = formatPHP(total);

	const dates = editExpenseItems.map((e) => normalizeDate(e.document_date || '').slice(0, 10)).filter(Boolean).sort();
	const rangeText = dates.length ? (dates[0] === dates[dates.length - 1] ? dates[0] : `${dates[0]} – ${dates[dates.length - 1]}`) : '-';
	if (domEdit.editExpenseDate) domEdit.editExpenseDate.textContent = rangeText;
};

// ─── Item Factory & Finder ───
const createNewEditItem = () => ({
	_editId: ++editExpenseItemCounter, id: null, document_date: '', expense_category: '',
	invoice_receipt_no: '', actual_amount: '', is_vatable: false, attachment: '',
	existingAttachments: [], newAttachments: [], removedAttachments: [],
	description: '', net_amount: 0, vat_amount: 0, approvals: [], isNew: true,
	vendor_name: '', vendor_address: '', vendor_tin: '',
});

const findEditItem = (editId) => editExpenseItems.find((item) => item._editId === editId);

// ─── Load Data ───
const loadEditExpenseTypes = (callback) => {
	jQuery.ajax({ url: base_url + 'transactions/reimbursement/api/get/expense_types', type: 'POST', dataType: 'json', headers: { Authorization: 'Bearer 12345678' } })
		.done((response) => {
			const res = parseRes(response);
			if (res.status !== 'success') { editExpenseTypeOptions = []; if (callback) callback(); return; }
			editExpenseTypeOptions = (res.data || []).map((item) => ({
				id: Number(item.id || 0), expenseCode: normalizeDate(item.expense_code), longText: normalizeDate(item.long_text),
				categoryName: normalizeDate(item.category_name), description: normalizeDate(item.description),
			})).filter((item) => item.expenseCode);
			if (callback) callback();
			else renderEditExpenseItems();
		}).fail(() => { editExpenseTypeOptions = []; if (callback) callback(); });
};

const loadEditData = () => {
	const ref = normalizeDate(domEdit.reimbursementRef?.value);
	if (!ref) {
		Swal.fire({ icon: 'error', title: 'Error', text: 'No reimbursement reference provided.' }).then(() => goToPath('transactions/reimbursement'));
		return;
	}

	ajax_loader('transactions/reimbursement/api/get', { ReimbursementId: ref }).done((response) => {
		const res = parseRes(response);
		if (res.status !== 'success' || !res.data || !res.data.header) {
			Swal.fire({ icon: 'error', title: 'Not Found', text: 'Reimbursement record not found.' }).then(() => goToPath('transactions/reimbursement'));
			return;
		}

		const payload = res.data;
		const header = payload.header;
		const details = payload.details || [];
		editReimbursementData = header;

		const currentUserId = Number(window.currentUserId || 0);
		const createdById = Number(header.created_by_id || header.created_by || 0);
		const statusCode = normalizeDate(header.status_code || '');

		if (createdById !== currentUserId) {
			Swal.fire({ icon: 'error', title: 'Access Denied', text: 'You can only edit your own reimbursements.' }).then(() => goToPath('transactions/reimbursement'));
			return;
		}
		if (statusCode !== 'RMB_SUBMITTED' && statusCode !== 'RMB_REJECTED') {
			Swal.fire({ icon: 'warning', title: 'Cannot Edit', text: 'Only submitted or rejected reimbursements can be edited.' }).then(() => goToPath('transactions/reimbursement'));
			return;
		}

		if (domEdit.editReimbursementNo) domEdit.editReimbursementNo.textContent = normalizeDate(header.reimbursement_id);
		if (domEdit.editStatus) domEdit.editStatus.innerHTML = getStatusBadge(normalizeDate(header.status_name));
		if (domEdit.editSubmittedDate) domEdit.editSubmittedDate.textContent = normalizeDate(header.submitted_date || '').slice(0, 10) || '-';
		if (domEdit.editDescription) domEdit.editDescription.value = normalizeDate(header.description || '');
		if (domEdit.editTotalAmount) domEdit.editTotalAmount.textContent = formatPHP(Number(header.total_amount || 0));
		if (domEdit.editPayableTo) { domEdit.editPayableTo.innerHTML = getEditPayableToOptionsMarkup(header.payable_to_user_id, header.payable_to); initEditPayableToSelect2(); }
		if (domEdit.editAddress) domEdit.editAddress.value = normalizeDate(header.address || '');
		if (domEdit.editIoNumber) domEdit.editIoNumber.value = normalizeDate(header.io_number || '');
		if (domEdit.editCostCenter) { domEdit.editCostCenter.innerHTML = getEditCostCenterOptions(header.cost_center_id); initEditCostCenterSelect2(); }

		editExpenseItems = details.map((item) => ({
			...item,
			expense_category: normalizeDate(item.expense_category || ''),
			_editId: ++editExpenseItemCounter,
			isNew: false,
			existingAttachments: normalizeDate(item.attachment || '').split(',').map((s) => s.trim()).filter(Boolean),
			newAttachments: [],
			removedAttachments: [],
		}));

		renderEditExpenseItems();

		if (domEdit.editExpenseDate && editExpenseItems.length) {
			const dates = editExpenseItems.map((e) => normalizeDate(e.document_date || '').slice(0, 10)).filter(Boolean).sort();
			const rangeText = dates.length ? (dates[0] === dates[dates.length - 1] ? dates[0] : `${dates[0]} – ${dates[dates.length - 1]}`) : '-';
			domEdit.editExpenseDate.textContent = rangeText;
		}
	}).fail(() => {
		if (domEdit.editExpenseItems) domEdit.editExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">Could not load expense items.</div>';
	});
};

// ─── Form State ───
const getEditFormPayload = () => {
	const totalAmount = editExpenseItems.reduce((sum, item) => sum + Number(item.actual_amount || item.amount || 0), 0);
	const validDates = editExpenseItems.map((item) => normalizeDate(item.document_date || '').slice(0, 10)).filter((d) => /^\d{4}-\d{2}-\d{2}$/.test(d)).sort();

	return {
		totalAmount,
		description: normalizeDate(domEdit.editDescription?.value),
		dateFrom: validDates[0] || '', dateTo: validDates[validDates.length - 1] || '',
		payableTo: normalizeDate(domEdit.editPayableTo?.value || ''),
		address: normalizeDate(domEdit.editAddress?.value || ''),
		costCenterId: normalizeDate(domEdit.editCostCenter?.value || ''),
		ioNumber: normalizeDate(domEdit.editIoNumber?.value || ''),
	};
};

// ─── Validation ───
const validateEditBeforeSave = () => {
	const editableItems = editExpenseItems.filter((item) => getItemApprovalStatus(item).canEdit);
	const isRejected = normalizeDate(editReimbursementData?.status_code || '') === 'RMB_REJECTED';
	if (!editableItems.length && !isRejected) {
		Swal.fire({ icon: 'warning', title: 'Nothing to edit', text: 'All items are approved and locked. No changes can be made.' });
		return null;
	}
	const missing = editableItems.find((item) => !item.document_date || !item.expense_category || !item.invoice_receipt_no || Number(item.actual_amount || 0) <= 0 || !item.description);
	if (missing) {
		Swal.fire({ icon: 'warning', title: 'Incomplete item', text: 'Each editable item requires document date, expense type, reference, amount, and remarks.' });
		return null;
	}
	const state = getEditFormPayload();
	if (state.totalAmount <= 0) {
		Swal.fire({ icon: 'warning', title: 'Invalid total', text: 'Total must be greater than 0.' });
		return null;
	}
	return state;
};

// ─── Save / Submit ───
const sendEditUpdate = (statusCode) => {
	const state = validateEditBeforeSave();
	if (!state) return;

	const ref = normalizeDate(domEdit.reimbursementRef?.value);

	const doSave = () => {
		const expensePayload = editExpenseItems.map((item) => ({
			Id: item.isNew ? null : item.id, DocumentDate: item.document_date,
			ExpenseCategory: item.expense_category, InvoiceReceiptNo: item.invoice_receipt_no,
			ActualAmount: Number(item.actual_amount || 0), IsVatable: Boolean(Number(item.is_vatable || 0)),
			Description: item.description, Attachment: getEditItemAttachmentNamesCsv(item), IsNew: item.isNew || false,
			VendorName: item.vendor_name || '', VendorAddress: item.vendor_address || '', VendorTin: item.vendor_tin || '',
		}));

		const formData = new FormData();
		formData.append('ReimbursementId', ref);
		formData.append('TotalAmount', state.totalAmount.toFixed(2));
		formData.append('StatusCode', statusCode);
		formData.append('Description', state.description);
		formData.append('CostCenterId', state.costCenterId);
		formData.append('PayableTo', state.payableTo);
		formData.append('Address', state.address);
		formData.append('IoNumber', state.ioNumber);
		formData.append('Expenses', JSON.stringify(expensePayload));

		editExpenseItems.forEach((item, index) => {
			if (item.newAttachments?.length > 0) formData.append(`attachments[${index}][]`, item.newAttachments[0]);
		});

		ajax_loader_formdata_loading('transactions/reimbursement/api/update', formData).done((response) => {
			const res = parseRes(response);
			if (res.status !== 'success') {
				Swal.fire({ icon: 'error', title: 'Failed', text: res.response || 'Failed to update reimbursement.' });
				return;
			}
			const isDraft = statusCode === 'RMB_DRAFT';
			Swal.fire({
				icon: 'success', title: isDraft ? 'Draft Saved' : 'Resubmitted',
				text: isDraft ? 'Reimbursement saved as draft successfully.' : 'Reimbursement updated and resubmitted for approval.',
			}).then(() => goToPath('transactions/reimbursement'));
		}).fail(() => Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Could not connect to the server.' }));
	};

	if (statusCode === 'RMB_DRAFT') {
		Swal.fire({ icon: 'question', title: 'Save as Draft?', text: 'This will save your changes as a draft. You can edit it later.', showCancelButton: true, confirmButtonText: 'Save', cancelButtonText: 'Cancel', reverseButtons: true, confirmButtonColor: '#f59e0b' })
			.then((result) => { if (result.isConfirmed) doSave(); });
		return;
	}
	Swal.fire({ icon: 'question', title: 'Resubmit for Approval?', text: 'Are you sure you want to resubmit this reimbursement for approval?', showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'Cancel', reverseButtons: true, confirmButtonColor: '#5b2bd6' })
		.then((result) => { if (result.isConfirmed) doSave(); });
};

// ─── Attachment Source Prompt ───
const promptEditAttachmentSource = (editId) => {
	if (!editReceiptOcr) return;
	editReceiptOcr.promptAttachmentSource(editId, {
		onGallery: (targetId) => { const el = qs(`[data-edit-file="upload"][data-edit-id="${targetId}"]`, domEdit.editExpenseItems); if (el) el.click(); },
		onCamera: async (targetId) => { const el = qs(`[data-edit-file="camera"][data-edit-id="${targetId}"]`, domEdit.editExpenseItems); if (el && await editReceiptOcr.ensureCameraPermission()) el.click(); },
	});
};

// ─── DOM Cache ───
const cacheEditDom = () => {
	const ids = [
		'reimbursementRef', 'editReimbursementNo', 'editDescription', 'editExpenseDate', 'editTotalAmount',
		'editStatus', 'editSubmittedDate', 'editPayableTo', 'editAddress', 'editCostCenter', 'editIoNumber',
		'editExpenseItems', 'rejectedBanner', 'rejectedCount', 'btnAddNewItem', 'btnSaveEdit', 'btnSaveAsDraft',
	];
	ids.forEach((id) => { domEdit[id] = document.getElementById(id); });

	const lbEl = document.getElementById('knaLightbox');
	if (lbEl) {
		lbEl.addEventListener('click', (e) => {
			if (e.target === lbEl || e.target.id === 'knaLightboxClose') {
				lbEl.classList.add('d-none');
				document.getElementById('knaLightboxImg').src = '';
			}
		});
	}

	if (domEdit.editExpenseItems) {
		domEdit.editExpenseItems.addEventListener('click', (e) => {
			const wrap = e.target.closest('[data-lightbox]');
			if (wrap) openEditLightbox(wrap.getAttribute('data-lightbox'));
		});
	}
};

// ─── Event Handlers ───
const onEditInput = (e) => {
	const t = e.target;
	const editId = Number(t.getAttribute('data-edit-id'));
	const field = t.getAttribute('data-edit-field');
	if (!editId || !field) return;

	const item = findEditItem(editId);
	if (!item) return;

	if (field === 'isVattable') { item.is_vatable = t.checked ? 1 : 0; return; }

	const fieldMap = { documentDate: 'document_date', expenseCategory: 'expense_category', reference: 'invoice_receipt_no', amount: 'actual_amount', description: 'description', vendorName: 'vendor_name', vendorAddress: 'vendor_address', vendorTin: 'vendor_tin' };
	const dataField = fieldMap[field] || field;
	item[dataField] = t.value;
	if (field === 'amount' && domEdit.editTotalAmount) {
		const total = editExpenseItems.reduce((sum, e) => sum + Number(e.actual_amount || e.amount || 0), 0);
		domEdit.editTotalAmount.textContent = formatPHP(total);
	}
};

const onEditChange = async (e) => {
	const t = e.target;
	const editId = Number(t.getAttribute('data-edit-id'));
	const fileMode = t.getAttribute('data-edit-file');
	if (!editId || !fileMode) return;

	const item = findEditItem(editId);
	if (!item) return;

	const files = Array.from(t.files || []);
	if (!files.length) return;

	if (item.existingAttachments?.length) {
		if (!item.removedAttachments) item.removedAttachments = [];
		item.existingAttachments.forEach((name) => { if (!item.removedAttachments.includes(name)) item.removedAttachments.push(name); });
	}
	if (item.newAttachments?.length) {
		item.newAttachments.forEach((f) => { if (f._objectUrl) { try { URL.revokeObjectURL(f._objectUrl); } catch (e) {} } });
	}
	item.newAttachments = [];
	item.attachments = [];
	if (editReceiptOcr) editReceiptOcr.cancelOcr(editId);

	const acceptedFiles = await editReceiptOcr.addItemAttachments(editId, files);
	if (acceptedFiles?.length) {
		item.newAttachments = [acceptedFiles[0]];
		editReceiptOcr.runOcrAutofillForItem(editId, acceptedFiles[0]);
	}
	item.attachments = [];
	renderEditExpenseItems();
	t.value = '';
};

const onEditClick = (e) => {
	const btn = e.target.closest('[data-edit-action]');
	if (!btn) return;
	const editId = Number(btn.getAttribute('data-edit-id'));
	const action = btn.getAttribute('data-edit-action');
	if (!editId || !action) return;

	const item = findEditItem(editId);
	if (!item) return;

	switch (action) {
		case 'remove':
			if (editReceiptOcr) editReceiptOcr.cancelOcr(editId);
			if (!item.isNew) {
				Swal.fire({ icon: 'warning', title: 'Remove Item?', text: 'This item will be removed from the reimbursement.', showCancelButton: true, confirmButtonText: 'Remove', cancelButtonText: 'Cancel', reverseButtons: true })
					.then((result) => { if (result.isConfirmed) { editExpenseItems = editExpenseItems.filter((i) => i._editId !== editId); renderEditExpenseItems(); } });
			} else {
				editExpenseItems = editExpenseItems.filter((i) => i._editId !== editId);
				renderEditExpenseItems();
			}
			break;
		case 'attach': promptEditAttachmentSource(editId); break;
		case 'ocrManual': if (editReceiptOcr) editReceiptOcr.markManual(editId); break;
		case 'removeAttachment': {
			const filename = btn.getAttribute('data-filename');
			if (filename) {
				if (!item.removedAttachments) item.removedAttachments = [];
				if (!item.removedAttachments.includes(filename)) item.removedAttachments.push(filename);
				renderEditExpenseItems();
			}
			break;
		}
		case 'undoRemoveAttachment': {
			const filename = btn.getAttribute('data-filename');
			if (filename && item.removedAttachments) {
				item.removedAttachments = item.removedAttachments.filter((f) => f !== filename);
				renderEditExpenseItems();
			}
			break;
		}
		case 'removeNewAttachment': {
			if (item.newAttachments?.length) {
				item.newAttachments.forEach((f) => { if (f._objectUrl) { try { URL.revokeObjectURL(f._objectUrl); } catch (e) {} } });
			}
			item.newAttachments = [];
			renderEditExpenseItems();
			break;
		}
	}
};

// ─── Init ───
const initEditPage = () => {
	cacheEditDom();
	if (domEdit.editCostCenter) { domEdit.editCostCenter.innerHTML = getEditCostCenterOptions(''); initEditCostCenterSelect2(); }

	editReceiptOcr = window.SharedReceiptOcr?.create({
		maxAttachmentBytes: MAX_ATTACHMENT_BYTES,
		getExpenseItem: (itemId) => { const item = findEditItem(itemId); if (item && !item.attachments) item.attachments = []; return item; },
		getExpenseTypeOptions: () => editExpenseTypeOptions,
		renderItems: renderEditExpenseItems,
		normalizeDate, escapeHtml, swal: Swal,
		ocrEndpoint: 'transactions/reimbursement/api/ocr',
		baseUrl: base_url,
	});

	loadEditPayableToOptions(() => loadEditExpenseTypes(() => loadEditData()));

	domEdit.btnAddNewItem?.addEventListener('click', () => { editExpenseItems.push(createNewEditItem()); renderEditExpenseItems(); });
	domEdit.btnSaveEdit?.addEventListener('click', () => sendEditUpdate('RMB_SUBMITTED'));
	domEdit.btnSaveAsDraft?.addEventListener('click', () => sendEditUpdate('RMB_DRAFT'));

	if (domEdit.editExpenseItems) {
		domEdit.editExpenseItems.addEventListener('input', onEditInput);
		domEdit.editExpenseItems.addEventListener('change', onEditChange);
		domEdit.editExpenseItems.addEventListener('click', onEditClick);
	}
};

initEditPage();
