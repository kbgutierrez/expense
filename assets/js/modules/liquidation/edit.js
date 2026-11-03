let editExpenseItems = [];
let editExpenseItemCounter = 0;
let editExpenseTypeOptions = [];
let editLiquidationData = null;
let editReceiptOcr = null;

const domEdit = {};
const EDIT_IMG_EXTS = /\.(jpg|jpeg|png|gif|webp)$/i;
const MAX_ATTACHMENT_BYTES = 2 * 1024 * 1024;
const editObjectUrls = new Map();

// ─── Helpers ───
const qs = (sel, ctx = document) => ctx.querySelector(sel);
const normalizeDate = (value) => (value ? String(value) : '');
const formatPHP = (amount) => Number(amount || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
const escapeHtml = (value = '') =>
  String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
const parseRes = (r) => (typeof r === 'string' ? jQuery.parseJSON(r) : r);
const safeNum = (v) => Number(v || 0);
const goToPath = (path) => { window.location.href = `${base_url}${path}`; };

// ─── Object URL Management ───
const revokeEditObjectUrls = (editId) => {
  const urls = editObjectUrls.get(editId);
  if (urls) { urls.forEach((url) => { try { URL.revokeObjectURL(url); } catch (e) {} }); editObjectUrls.delete(editId); }
};
const storeEditObjectUrl = (editId, url) => {
  if (!editObjectUrls.has(editId)) editObjectUrls.set(editId, []);
  editObjectUrls.get(editId).push(url);
};

// ─── Attachment Helpers ───
const getEditItemAttachmentNames = (item) =>
  ((item.existingAttachments || []).map(normalizeDate).filter(Boolean))
    .concat((item.newAttachments || []).map((f) => f.name || f.fileName || '').filter(Boolean));

const getEditItemAttachmentNamesCsv = (item) => {
  const newFiles = (item.newAttachments || []).map((f) => f.name || f.fileName || '').filter(Boolean);
  if (newFiles.length) return newFiles.join(',');
  const kept = (item.existingAttachments || []).map(normalizeDate).filter(Boolean)
    .filter((name) => !(item.removedAttachments || []).includes(name));
  return kept.join(',');
};

const getEditExpenseTypeDisplayText = (option) => {
  const code = normalizeDate(option.expenseCode || option.expense_code || option.categoryName || option.category_code || '');
  const longText = normalizeDate(option.longText || option.long_text || option.description || option.categoryName || '');
  return code && longText ? `${code} - ${longText}` : (code || longText || 'Select type');
};

const getEditExpenseTypeByCode = (code) => editExpenseTypeOptions.find((item) => String(item.expenseCode || item.expense_code) === String(code));
const getEditExpenseTypeById = (idValue) => editExpenseTypeOptions.find((item) => String(item.id) === String(idValue));

const editExpenseTypeOptionsMarkup = (selectedValue) =>
  `<option value="">Select type</option>${editExpenseTypeOptions.map((option) => {
    const code = normalizeDate(option.expenseCode);
    const desc = normalizeDate(option.description || option.longText || option.long_text || '');
    return `<option value="${escapeHtml(code)}" title="${escapeHtml(desc)}" ${String(selectedValue) === String(code) ? 'selected' : ''}>${escapeHtml(getEditExpenseTypeDisplayText(option))}</option>`;
  }).join('')}`;

// ─── Status & Variance ───
const getStatusBadge = (status) => {
  const map = { Draft: 'draft', Approved: 'approved', Rejected: 'rejected' };
  const cls = map[status] || 'pending';
  return `<span class="kna-badge kna-badge-${cls}">${status || 'Submitted'}</span>`;
};

const getVariancePresentation = (refund, reimburse) => {
  if (refund > 0) return { desktopHtml: `<span class="kna-var-badge kna-var-return">${formatPHP(refund)} to return</span>`, mobileText: `${formatPHP(refund)} return` };
  if (reimburse > 0) return { desktopHtml: `<span class="kna-var-badge kna-var-reimburse">${formatPHP(reimburse)} to reimburse</span>`, mobileText: `${formatPHP(reimburse)} reimburse` };
  return { desktopHtml: '<span class="kna-var-badge kna-var-balanced">0.00</span>', mobileText: '0.00' };
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
  const et = getEditExpenseTypeByCode(item.expense_category || item.expense_category_id || '');
  const newAttachments = item.newAttachments || [];
  const ocrState = editReceiptOcr ? editReceiptOcr.getItemOcrState(item._editId) : { status: 'idle' };

  return {
    approval, isLocked, isRejected,
    rowClass: isLocked ? 'kna-row-locked' : (isRejected ? 'kna-row-rejected' : ''),
    disabledAttr: isLocked ? 'disabled' : '',
    lockIcon: isLocked ? '<i class="fas fa-lock kna-lock-icon" title="Approved — cannot edit"></i> ' : '',
    docDate: normalizeDate(item.document_date || '').slice(0, 10),
    category: normalizeDate(item.expense_category || item.expense_category_id || ''),
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

// ─── Attachment Cell Builder (shared between desktop & mobile) ───
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

// ─── Item Row Builder (compact, one row per item, all fields inline) ───
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

const mobileEditQuery = window.matchMedia ? window.matchMedia('(max-width: 767.98px)') : null;
const isMobileEdit = () => Boolean(mobileEditQuery && mobileEditQuery.matches);
let editSheetId = null;

const isBlankEditItem = (item) => Boolean(item && item.isNew) && !normalizeDate(item.document_date) && !normalizeDate(item.expense_category)
  && !normalizeDate(item.invoice_receipt_no) && Number(item.actual_amount || 0) <= 0 && !normalizeDate(item.description)
  && !normalizeDate(item.vendor_name) && !normalizeDate(item.vendor_address) && !normalizeDate(item.vendor_tin) && !(item.newAttachments || []).length;

const formatEditShortDate = (value) => {
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(normalizeDate(value));
  if (!m) return '';
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${months[Number(m[2]) - 1]} ${Number(m[3])}, ${m[1]}`;
};

const getEditStatusChip = (item, summary) => {
  if (summary.approval.status === 'approved') return '<span class="lqm-chip is-approved"><i class="fas fa-lock"></i> Approved</span>';
  if (summary.approval.status === 'partial') return '<span class="lqm-chip is-partial"><i class="fas fa-lock"></i> Partially approved</span>';
  if (summary.isRejected) return '<span class="lqm-chip is-rejected"><i class="fas fa-times-circle"></i> Rejected</span>';
  if (item.isNew) return '<span class="lqm-chip is-new">New</span>';
  return '';
};

const renderMobileEditCard = (item, summary) => {
  const et = getEditExpenseTypeByCode(summary.category);
  const name = normalizeDate((et && (et.longText || et.description || et.expenseCode)) || '') || normalizeDate(item.description) || 'Expense';
  const sub = [formatEditShortDate(item.document_date), normalizeDate(item.invoice_receipt_no) ? `OR ${normalizeDate(item.invoice_receipt_no)}` : ''].filter(Boolean).join(' · ') || 'Tap to complete details';
  const hasReceipt = summary.newAttachments.length > 0 || summary.existingAttachments.some((n) => !summary.removedAttachments.includes(n));
  const amount = summary.amount > 0 ? formatPHP(summary.amount) : '—';
  const cls = ['lqm-exp', summary.isRejected ? 'is-rejected' : '', summary.isLocked ? 'is-locked' : ''].filter(Boolean).join(' ');
  const icon = summary.isLocked ? 'fa-lock' : (hasReceipt ? 'fa-receipt' : 'fa-exclamation');
  const flag = summary.isOcrLoading
    ? '<span class="lqm-exp-flag is-ocr d-block"><i class="fas fa-spinner fa-spin"></i> Reading receipt</span>'
    : (!hasReceipt && !summary.isLocked ? '<span class="lqm-exp-flag d-block">Attach receipt</span>' : '');
  return `
    <button type="button" class="${cls}" data-edit-action="mOpen" data-edit-id="${item._editId}">
      <span class="lqm-exp-thumb${!hasReceipt && !summary.isLocked ? ' is-missing' : ''}"><i class="fas ${icon}"></i></span>
      <span class="lqm-exp-main">
        <span class="lqm-exp-name d-block">${escapeHtml(name)}</span>
        <span class="lqm-exp-sub d-block">${escapeHtml(sub)}</span>
        ${getEditStatusChip(item, summary)}
      </span>
      <span class="lqm-exp-right">
        <span class="lqm-exp-amt d-block">${escapeHtml(amount)}</span>
        ${flag}
      </span>
    </button>`;
};

const renderMobileEditSheet = (item, summary) => {
  const locked = summary.isLocked;
  const dis = locked ? 'disabled' : '';
  const att = buildAttachmentCell(item, summary);
  const hasAnyAttachment = summary.newAttachments.length > 0 || summary.existingAttachments.length > 0;
  let receipt;
  if (hasAnyAttachment) {
    receipt = `<div class="lqm-receipt-has${locked ? ' lqm-locked' : ''}"><span class="kna-attachment-cell">${att.attachHtml}${att.ocrLoadingIcon}${att.compressedIcon}</span>${!locked ? `<button type="button" data-edit-action="attach" data-edit-id="${item._editId}">Replace</button>` : ''}</div>`;
  } else if (!locked) {
    receipt = `<button type="button" class="lqm-receipt" data-edit-action="attach" data-edit-id="${item._editId}"><i class="fas fa-camera"></i>Add receipt photo<small>We'll fill in the details for you</small></button>`;
  } else {
    receipt = '<div class="lqm-receipt-has"><span class="text-muted" style="font-size:12px;">No receipt attached</span></div>';
  }
  const rejectNote = summary.isRejected && summary.approval.rejections.length
    ? `<div class="lqm-reject-note"><b><i class="fas fa-exclamation-circle"></i> Rejected — please fix and resubmit</b>${summary.approval.rejections.map((rej) => `<div><strong>${escapeHtml(normalizeDate(rej.approver_name || rej.approver || 'Approver'))}:</strong> ${escapeHtml(normalizeDate(rej.remarks || rej.rejection_reason || 'No reason provided'))}</div>`).join('')}</div>`
    : '';
  const lockedNote = locked ? '<div class="lqm-locked-note"><i class="fas fa-lock"></i> Approved — this expense can no longer be edited.</div>' : '';
  const vendorName = normalizeDate(item.vendor_name);
  const vendorSummary = vendorName || (summary.isVattable ? 'VAT' : 'Optional');
  const amountValue = Number(item.actual_amount || 0) > 0 ? normalizeDate(item.actual_amount) : '';
  const isNewBlank = isBlankEditItem(item);
  return `
    <div class="lqm-sheet-backdrop" data-edit-action="mClose" data-edit-id="${item._editId}"></div>
    <div class="lqm-sheet" role="dialog" aria-modal="true" aria-label="Expense">
      <div class="lqm-grab"></div>
      <div class="lqm-sheet-head">
        <b>${isNewBlank ? 'New expense' : 'Expense'}</b>
        <button type="button" class="lqm-icon-btn" data-edit-action="mClose" data-edit-id="${item._editId}" aria-label="Close"><i class="fas fa-times"></i></button>
      </div>
      <div class="lqm-sheet-body">
        ${lockedNote}${rejectNote}
        ${receipt}
        <input type="file" class="d-none" data-edit-file="upload" data-edit-id="${item._editId}" accept="image/*,application/pdf">
        <input type="file" class="d-none" data-edit-file="camera" data-edit-id="${item._editId}" accept="image/*" capture="environment">
        ${buildEditOcrStatusHtml(item._editId)}
        <div class="lqm-amount-wrap">
          <span class="lqm-label">Amount <span class="lqm-req">*</span></span>
          <div class="lqm-amount"><span>₱</span><input type="number" inputmode="decimal" min="0" step="0.01" placeholder="0.00" data-edit-field="amount" data-edit-id="${item._editId}" value="${escapeHtml(amountValue)}" ${dis}></div>
        </div>
        <div class="lqm-two">
          <div class="lqm-field"><span class="lqm-label">Date <span class="lqm-req">*</span></span><input type="date" class="lqm-input" data-edit-field="documentDate" data-edit-id="${item._editId}" value="${escapeHtml(summary.docDate)}" ${dis}></div>
          <div class="lqm-field"><span class="lqm-label">OR / Invoice no. <span class="lqm-req">*</span></span><input type="text" class="lqm-input" data-edit-field="reference" data-edit-id="${item._editId}" value="${escapeHtml(normalizeDate(item.invoice_receipt_no))}" placeholder="e.g. 004512" ${dis}></div>
        </div>
        <div class="lqm-field"><span class="lqm-label">Expense type <span class="lqm-req">*</span></span><select class="lqm-input" data-edit-field="expenseCategory" data-edit-id="${item._editId}" ${dis}>${editExpenseTypeOptionsMarkup(summary.category)}</select></div>
        <div class="lqm-field"><span class="lqm-label">Description <span class="lqm-req">*</span></span><input type="text" class="lqm-input" data-edit-field="description" data-edit-id="${item._editId}" value="${escapeHtml(normalizeDate(item.description))}" placeholder="What was this for?" ${dis}></div>
        <details class="lqm-more">
          <summary>Vendor &amp; VAT <span>${escapeHtml(vendorSummary)} <i class="fas fa-chevron-down"></i></span></summary>
          <div class="lqm-field"><span class="lqm-label">Vendor name</span><input type="text" class="lqm-input" data-edit-field="vendorName" data-edit-id="${item._editId}" value="${escapeHtml(vendorName)}" ${dis}></div>
          <div class="lqm-field"><span class="lqm-label">Vendor address</span><input type="text" class="lqm-input" data-edit-field="vendorAddress" data-edit-id="${item._editId}" value="${escapeHtml(normalizeDate(item.vendor_address))}" ${dis}></div>
          <div class="lqm-field"><span class="lqm-label">TIN</span><input type="text" class="lqm-input" data-edit-field="vendorTin" data-edit-id="${item._editId}" value="${escapeHtml(normalizeDate(item.vendor_tin))}" ${dis}></div>
          <label class="lqm-vat">VAT-able receipt <input type="checkbox" data-edit-field="isVattable" data-edit-id="${item._editId}" ${summary.isVattable ? 'checked' : ''} ${dis}></label>
        </details>
      </div>
      <div class="lqm-sheet-foot">
        ${!locked ? `<button type="button" class="lqm-icon-btn" data-edit-action="mDelete" data-edit-id="${item._editId}" aria-label="Delete expense"><i class="fas fa-trash"></i></button>` : ''}
        <button type="button" class="lqm-btn lqm-btn-primary" data-edit-action="mDone" data-edit-id="${item._editId}">${locked ? 'Close' : 'Save expense'}</button>
      </div>
    </div>`;
};

const buildMobileEditHtml = () => {
  const listed = editExpenseItems.filter((item) => !isBlankEditItem(item));
  const cards = listed.map((item) => renderMobileEditCard(item, getEditExpenseItemSummary(item))).join('');
  const count = listed.length;
  const sheetItem = editSheetId ? findEditItem(editSheetId) : null;
  if (!sheetItem) editSheetId = null;
  document.documentElement.classList.toggle('lqm-sheet-open', Boolean(sheetItem));
  const body = count
    ? cards
    : '<div class="lqm-empty"><div class="lqm-empty-icon"><i class="fas fa-receipt"></i></div><b>No expenses yet</b><p>Add an expense by scanning its receipt.</p></div>';
  return `
    <div class="lqm-list">
      <div class="lqm-section"><b>Expenses</b><span>${count} item${count === 1 ? '' : 's'}</span></div>
      ${body}
      <div class="lqm-actions" style="margin-top:10px;">
        <button type="button" class="lqm-btn lqm-btn-outline" data-edit-action="mScan" data-edit-id="-1"><i class="fas fa-camera"></i> Scan receipt</button>
        <button type="button" class="lqm-btn lqm-btn-outline" data-edit-action="mManual" data-edit-id="-1"><i class="fas fa-plus"></i> Add manually</button>
      </div>
    </div>
    <div class="lqm-sheet-wrap">${sheetItem ? renderMobileEditSheet(sheetItem, getEditExpenseItemSummary(sheetItem)) : ''}</div>`;
};

const openEditSheet = (editId) => {
  editSheetId = editId;
  renderEditExpenseItems();
};

const closeEditSheet = () => {
  const item = editSheetId ? findEditItem(editSheetId) : null;
  if (item && isBlankEditItem(item)) {
    if (editReceiptOcr) editReceiptOcr.cancelOcr(item._editId);
    editExpenseItems = editExpenseItems.filter((i) => i._editId !== item._editId);
  }
  editSheetId = null;
  renderEditExpenseItems();
};

const startMobileEditExpense = (scan) => {
  const blank = editExpenseItems.find((item) => isBlankEditItem(item));
  const item = blank || createNewEditItem();
  if (!blank) editExpenseItems.push(item);
  openEditSheet(item._editId);
  if (scan) promptEditAttachmentSource(item._editId);
};

const updateMobileEditProgress = () => {
  const approved = safeNum(normalizeDate(domEdit.editApprovedAmount?.textContent).replace(/[^0-9.]/g, ''));
  const total = editExpenseItems.reduce((sum, e) => sum + Number(e.actual_amount || e.amount || 0), 0);
  if (domEdit.mobileStickyTotal) domEdit.mobileStickyTotal.textContent = formatPHP(total);
  if (domEdit.mobileTotal) domEdit.mobileTotal.textContent = formatPHP(total);
  if (domEdit.mobileProgressBar) {
    domEdit.mobileProgressBar.style.width = `${approved > 0 ? Math.min(100, (total / approved) * 100) : 0}%`;
    domEdit.mobileProgressBar.classList.toggle('is-over', approved > 0 && total > approved);
  }
  if (domEdit.mobileLeft) {
    if (approved <= 0) domEdit.mobileLeft.textContent = '';
    else if (total <= approved) domEdit.mobileLeft.textContent = `${formatPHP(approved - total)} left`;
    else domEdit.mobileLeft.textContent = `${formatPHP(total - approved)} over`;
  }
  if (domEdit.mobileVariance) {
    const refund = approved > total ? approved - total : 0, reimburse = total > approved ? total - approved : 0;
    domEdit.mobileVariance.textContent = refund > 0 ? `${formatPHP(refund)} to return` : (reimburse > 0 ? `${formatPHP(reimburse)} to reimburse` : 'Fully liquidated');
    domEdit.mobileVariance.className = 'lqm-variance' + (total <= 0 || approved <= 0 ? ' d-none' : (refund > 0 ? ' is-return' : (reimburse > 0 ? ' is-reimburse' : ' is-balanced')));
  }
};

// ─── Render Items ───
const renderEditExpenseItems = () => {
  const container = domEdit.editExpenseItems;
  if (!container) return;

  if (!editExpenseItems?.length && isMobileEdit()) {
    container.innerHTML = buildMobileEditHtml();
    if (domEdit.editLiquidatedAmount) domEdit.editLiquidatedAmount.textContent = formatPHP(0);
    if (domEdit.editExpenseDate) domEdit.editExpenseDate.textContent = '-';
    if (domEdit.mobileExpenseDate) domEdit.mobileExpenseDate.textContent = '-';
    updateEditVariance();
    return;
  }

  if (!editExpenseItems?.length) {
    container.innerHTML = '<div class="text-muted kna-small py-2">No expense items found.</div>';
    if (domEdit.mobileTotal) domEdit.mobileTotal.textContent = formatPHP(0);
    if (domEdit.editLiquidatedAmount) domEdit.editLiquidatedAmount.textContent = formatPHP(0);
    if (domEdit.editExpenseDate) domEdit.editExpenseDate.textContent = '-';
    if (domEdit.mobileExpenseDate) domEdit.mobileExpenseDate.textContent = '-';
    updateEditVariance();
    return;
  }

  let rejectedCount = 0;
  const mobile = isMobileEdit();
  if (!mobile) document.documentElement.classList.remove('lqm-sheet-open');
  const rowsHtml = mobile ? '' : editExpenseItems.map((item, i) => {
    const summary = getEditExpenseItemSummary(item);
    if (summary.isRejected) rejectedCount++;
    return buildItemRow(item, summary, i);
  }).join('');
  if (mobile) rejectedCount = editExpenseItems.filter((item) => getEditExpenseItemSummary(item).isRejected).length;

  const total = editExpenseItems.reduce((sum, e) => sum + Number(e.actual_amount || e.amount || 0), 0);
  const totalNet = editExpenseItems.reduce((sum, e) => sum + Number(e.net_amount || 0), 0);
  const totalVat = editExpenseItems.reduce((sum, e) => sum + Number(e.vat_amount || 0), 0);

  container.innerHTML = mobile ? buildMobileEditHtml() : `
    <div class="kna-item-rows">${rowsHtml}</div>
    <div class="kna-mobile-summary">
      <div class="kna-fin-label">Total</div>
      <div class="kna-fin-value">${formatPHP(total)}</div>
      <div style="font-size:10px;color:#9ca3af;margin-top:2px;">Net ${formatPHP(totalNet)} • VAT ${formatPHP(totalVat)}</div>
    </div>`;

  // Update UI state
  if (domEdit.rejectedBanner && domEdit.rejectedCount) {
    domEdit.rejectedBanner.classList.toggle('d-none', rejectedCount === 0);
    if (rejectedCount > 0) domEdit.rejectedCount.textContent = rejectedCount;
  }
  if (domEdit.editItemCount) domEdit.editItemCount.textContent = `${editExpenseItems.length} item(s)`;
  if (domEdit.editLiquidatedAmount) domEdit.editLiquidatedAmount.textContent = formatPHP(total);
  if (domEdit.mobileTotal) domEdit.mobileTotal.textContent = formatPHP(total);

  const dates = editExpenseItems.map((e) => normalizeDate(e.document_date || '').slice(0, 10)).filter(Boolean).sort();
  const rangeText = dates.length ? (dates[0] === dates[dates.length - 1] ? dates[0] : `${dates[0]} – ${dates[dates.length - 1]}`) : '-';
  if (domEdit.editExpenseDate) domEdit.editExpenseDate.textContent = rangeText;
  if (domEdit.mobileExpenseDate) domEdit.mobileExpenseDate.textContent = rangeText;

  updateEditVariance();
};

// ─── Variance ───
const updateEditVariance = () => {
  if (!domEdit.editVariance || !domEdit.editApprovedAmount) return;
  const approvedAmount = safeNum(domEdit.editApprovedAmount.textContent.replace(/[^0-9.]/g, ''));
  const total = editExpenseItems.reduce((sum, e) => sum + Number(e.actual_amount || e.amount || 0), 0);
  const variance = getVariancePresentation(approvedAmount > total ? approvedAmount - total : 0, total > approvedAmount ? total - approvedAmount : 0);
  domEdit.editVariance.innerHTML = variance.desktopHtml;
  if (domEdit.mobileVariance) domEdit.mobileVariance.textContent = variance.mobileText;
  updateMobileEditProgress();
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
  jQuery.ajax({ url: base_url + 'transactions/liquidation/api/get/expense_types', type: 'POST', dataType: 'json', headers: { Authorization: 'Bearer 12345678' } })
    .done((response) => {
      const res = parseRes(response);
      if (res.status !== 'success') { editExpenseTypeOptions = []; if (callback) callback(); return; }
      editExpenseTypeOptions = (res.data || []).map((item) => ({
        id: Number(item.id || 0), expenseCode: normalizeDate(item.expense_code), longText: normalizeDate(item.long_text),
        categoryName: normalizeDate(item.category_name), description: normalizeDate(item.description),
      })).filter((item) => item.id && (item.expenseCode || item.categoryName));
      if (callback) callback();
      else renderEditExpenseItems();
    }).fail(() => { editExpenseTypeOptions = []; if (callback) callback(); });
};

const loadEditData = () => {
  const ref = normalizeDate(domEdit.liquidationRef?.value);
  if (!ref) {
    Swal.fire({ icon: 'error', title: 'Error', text: 'No liquidation reference provided.' }).then(() => goToPath('transactions/liquidation'));
    return;
  }

  // Load header
  ajax_loader('transactions/liquidation/api/get/header_by_id', { LiquidationId: ref }).done((response) => {
    const res = parseRes(response);
    if (res.status !== 'success' || !res.data) {
      Swal.fire({ icon: 'error', title: 'Not Found', text: 'Liquidation record not found.' }).then(() => goToPath('transactions/liquidation'));
      return;
    }

    const record = res.data;
    editLiquidationData = record;
    const currentUserId = Number(window.currentUserId || 0);
    const createdById = Number(record.created_by_id || record.created_by || 0);
    const statusCode = normalizeDate(record.status_code || '');

    if (createdById !== currentUserId) {
      Swal.fire({ icon: 'error', title: 'Access Denied', text: 'You can only edit your own liquidations.' }).then(() => goToPath('transactions/liquidation'));
      return;
    }
    if (statusCode !== 'LQ_SUBMITTED' && statusCode !== 'LQ_REJECTED') {
      Swal.fire({ icon: 'warning', title: 'Cannot Edit', text: 'Only submitted or rejected liquidations can be edited.' }).then(() => goToPath('transactions/liquidation'));
      return;
    }

    // Populate DOM
    const setText = (el, val) => { if (el) el.textContent = val; };
    const setHtml = (el, val) => { if (el) el.innerHTML = val; };
    const submittedDate = normalizeDate(record.submitted_date || '').slice(0, 10) || '-';
    const purposeText = normalizeDate(record.description || '') || '-';
    const payableToText = normalizeDate(record.payable_to || '') || '-';
    const addressText = normalizeDate(record.address || '') || '-';
    const ccId = record.cost_center_id || '', ccName = record.cost_center_name || '';
    const costCenterText = ccId && ccName ? `${ccId} - ${ccName}` : (normalizeDate(ccId || ccName) || '-');

    setText(domEdit.editLiquidationNo, normalizeDate(record.liquidation_id));
    setText(domEdit.mobileLiquidationNo, normalizeDate(record.liquidation_id) || '-');
    setText(domEdit.editCaRef, normalizeDate(record.cash_advance_id));
    setText(domEdit.mobileCaRef, normalizeDate(record.cash_advance_id) || '-');
    setText(domEdit.editCaAmount, formatPHP(Number(record.ca_amount || 0)));
    setText(domEdit.mobileCaAmount, formatPHP(Number(record.ca_amount || 0)));
    setText(domEdit.editApprovedAmount, formatPHP(Number(record.approved_amount || 0)));
    setText(domEdit.mobileApprovedAmount, formatPHP(Number(record.approved_amount || 0)));
    const caDate = normalizeDate(record.ca_date || '').slice(0, 10) || '-';
    setText(domEdit.editCaDate, caDate);
    setText(domEdit.mobileCaDate, caDate);
    setHtml(domEdit.editStatus, getStatusBadge(normalizeDate(record.status_name)));
    setText(domEdit.mobileStatus, normalizeDate(record.status_name) || '-');
    setText(domEdit.editSubmittedDate, submittedDate);
    setText(domEdit.mobileSubmittedDate, submittedDate);
    setText(domEdit.editPurpose, purposeText);
    setText(domEdit.mobilePurpose, purposeText);
    setText(domEdit.editPayableTo, payableToText);
    setText(domEdit.mobilePayableTo, payableToText);
    setText(domEdit.editAddress, addressText);
    setText(domEdit.mobileAddress, addressText);
    setText(domEdit.editCostCenter, costCenterText);
    setText(domEdit.mobileCostCenter, costCenterText);
    if (domEdit.cashAdvanceId) domEdit.cashAdvanceId.value = normalizeDate(record.cash_advance_id);

    const variance = getVariancePresentation(Number(record.refund_amount || 0), Number(record.reimburse_amount || 0));
    setHtml(domEdit.editVariance, variance.desktopHtml);
    setText(domEdit.mobileVariance, variance.mobileText);
    if (editExpenseItems.length) updateEditVariance(); else updateMobileEditProgress();
  }).fail(() => {
    Swal.fire({ icon: 'error', title: 'Load Failed', text: 'Could not load liquidation header.' }).then(() => goToPath('transactions/liquidation'));
  });

  // Load details
  ajax_loader('transactions/liquidation/api/get/for_edit', { LiquidationId: ref }).done((response) => {
    const res = parseRes(response);
    if (res.status !== 'success') {
      if (domEdit.editExpenseItems) domEdit.editExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">Could not load expense items.</div>';
      return;
    }

    editExpenseItems = (res.data?.details || []).map((item) => {
      return {
        ...item,
        expense_category: normalizeDate(item.expense_category || ''),
        _editId: ++editExpenseItemCounter,
        isNew: false,
        existingAttachments: normalizeDate(item.attachment || '').split(',').map((s) => s.trim()).filter(Boolean),
        newAttachments: [],
        removedAttachments: [],
      };
    });

    renderEditExpenseItems();

    if (domEdit.editExpenseDate && editExpenseItems.length) {
      const dates = editExpenseItems.map((e) => normalizeDate(e.document_date || '').slice(0, 10)).filter(Boolean).sort();
      const rangeText = dates.length ? (dates[0] === dates[dates.length - 1] ? dates[0] : `${dates[0]} – ${dates[dates.length - 1]}`) : '-';
      domEdit.editExpenseDate.textContent = rangeText;
      if (domEdit.mobileExpenseDate) domEdit.mobileExpenseDate.textContent = rangeText;
    }
  }).fail(() => {
    if (domEdit.editExpenseItems) domEdit.editExpenseItems.innerHTML = '<div class="text-muted kna-small py-2">Could not load expense items.</div>';
  });
};

// ─── Form State ───
const getEditFormPayload = () => {
  const caRef = normalizeDate(domEdit.editCaRef?.textContent);
  const totalAmount = editExpenseItems.reduce((sum, item) => sum + Number(item.actual_amount || item.amount || 0), 0);
  const caAmount = safeNum(domEdit.editCaAmount?.textContent.replace(/[^0-9.]/g, ''));
  const approvedAmount = safeNum(domEdit.editApprovedAmount?.textContent.replace(/[^0-9.]/g, ''));
  const validDates = editExpenseItems.map((item) => normalizeDate(item.document_date || '').slice(0, 10)).filter((d) => /^\d{4}-\d{2}-\d{2}$/.test(d)).sort();

  return {
    caRef, totalAmount, caAmount,
    refundAmount: approvedAmount > totalAmount ? approvedAmount - totalAmount : 0,
    reimburseAmount: totalAmount > approvedAmount ? totalAmount - approvedAmount : 0,
    dateFrom: validDates[0] || '', dateTo: validDates[validDates.length - 1] || '',
    purpose: normalizeDate(domEdit.editPurpose?.textContent),
  };
};

// ─── Validation ───
const validateEditBeforeSave = () => {
  const editableItems = editExpenseItems.filter((item) => getItemApprovalStatus(item).canEdit);
  const isRejected = normalizeDate(editLiquidationData?.status_code || '') === 'LQ_REJECTED';
  if (!editableItems.length && !isRejected) {
    Swal.fire({ icon: 'warning', title: 'Nothing to edit', text: 'All items are approved and locked. No changes can be made.' });
    return null;
  }
  const missing = editableItems.find((item) => !item.document_date || !item.expense_category || !item.invoice_receipt_no || Number(item.actual_amount || 0) <= 0 || !item.description);
  if (missing) {
    Swal.fire({ icon: 'warning', title: 'Incomplete item', text: 'Each editable item requires document date, expense type, reference, amount, and remarks.' })
      .then(() => { if (isMobileEdit() && missing._editId) openEditSheet(missing._editId); });
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
  if (isMobileEdit() && editExpenseItems.some(isBlankEditItem)) {
    editExpenseItems = editExpenseItems.filter((item) => !isBlankEditItem(item));
    editSheetId = null;
    renderEditExpenseItems();
  }
  const state = validateEditBeforeSave();
  if (!state) return;

  const ref = normalizeDate(domEdit.liquidationRef?.value);
  const caId = normalizeDate(domEdit.cashAdvanceId?.value);

  const doSave = () => {
    const expensePayload = editExpenseItems.map((item) => ({
      Id: item.isNew ? null : item.id, DocumentDate: item.document_date,
      ExpenseCategory: item.expense_category, InvoiceReceiptNo: item.invoice_receipt_no,
      ActualAmount: Number(item.actual_amount || 0), IsVatable: Boolean(Number(item.is_vatable || 0)),
      Description: item.description, Attachment: getEditItemAttachmentNamesCsv(item), IsNew: item.isNew || false,
      VendorName: item.vendor_name || '', VendorAddress: item.vendor_address || '', VendorTin: item.vendor_tin || '',
    }));

    const formData = new FormData();
    formData.append('LiquidationId', ref);
    formData.append('CashAdvanceId', caId);
    formData.append('CashAdvanceAmount', state.caAmount.toFixed(2));
    formData.append('TotalAmountSpent', state.totalAmount.toFixed(2));
    formData.append('RefundAmount', state.refundAmount.toFixed(2));
    formData.append('ReimburseAmount', state.reimburseAmount.toFixed(2));
    formData.append('StatusCode', statusCode);
    formData.append('Description', state.purpose);
    formData.append('ExpenseRangeFrom', state.dateFrom);
    formData.append('ExpenseRangeTo', state.dateTo);
    formData.append('Expenses', JSON.stringify(expensePayload));

    editExpenseItems.forEach((item, index) => {
      if (item.newAttachments?.length > 0) formData.append(`attachments[${index}][]`, item.newAttachments[0]);
    });

    ajax_loader_formdata_loading('transactions/liquidation/api/update', formData).done((response) => {
      const res = parseRes(response);
      if (res.status !== 'success') {
        Swal.fire({ icon: 'error', title: 'Failed', text: res.response || 'Failed to update liquidation.' });
        return;
      }
      const isDraft = statusCode === 'LQ_DRAFT';
      Swal.fire({
        icon: 'success', title: isDraft ? 'Draft Saved' : 'Resubmitted',
        text: isDraft ? 'Liquidation saved as draft successfully.' : 'Liquidation updated and resubmitted for approval.',
      }).then(() => goToPath('transactions/liquidation'));
    }).fail(() => Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Could not connect to the server.' }));
  };

  if (statusCode === 'LQ_DRAFT') {
    Swal.fire({ icon: 'question', title: 'Save as Draft?', text: 'This will save your changes as a draft. You can edit it later.', showCancelButton: true, confirmButtonText: 'Save', cancelButtonText: 'Cancel', reverseButtons: true, confirmButtonColor: '#f59e0b' })
      .then((result) => { if (result.isConfirmed) doSave(); });
    return;
  }
  Swal.fire({ icon: 'question', title: 'Resubmit for Approval?', text: 'Are you sure you want to resubmit this liquidation for approval?', showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'Cancel', reverseButtons: true, confirmButtonColor: '#5b2bd6' })
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
    'liquidationRef', 'cashAdvanceId', 'editLiquidationNo', 'editCaRef', 'editCaAmount', 'editApprovedAmount', 'editCaDate',
    'editExpenseDate', 'editLiquidatedAmount', 'editVariance', 'editPurpose', 'editPayableTo',
    'editAddress', 'editCostCenter', 'editStatus', 'editSubmittedDate', 'editExpenseItems',
    'editItemCount', 'rejectedBanner', 'rejectedCount', 'btnAddNewItem', 'btnSaveEdit',
    'btnSaveAsDraft', 'btnSaveEditMobile', 'btnSaveAsDraftMobile', 'mobileLiquidationNo',
    'mobileStatus', 'mobileCaAmount', 'mobileTotal', 'mobileVariance', 'mobileCaRef',
    'mobileCaDate', 'mobileSubmittedDate', 'mobileExpenseDate', 'mobileApprovedAmount', 'mobilePayableTo',
    'mobileCostCenter', 'mobileAddress', 'mobilePurpose', 'mobileProgressBar', 'mobileLeft', 'mobileStickyTotal',
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
  if (field === 'amount') updateEditVariance();
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

  // Single attachment: clear everything first
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

  if (action === 'mScan' || action === 'mManual') { startMobileEditExpense(action === 'mScan'); return; }
  if (action === 'mClose' || action === 'mDone') { closeEditSheet(); return; }

  const item = findEditItem(editId);
  if (!item) return;

  if (action === 'mOpen') { openEditSheet(editId); return; }
  if (action === 'mDelete') {
    if (!getItemApprovalStatus(item).canEdit) return;
    Swal.fire({ icon: 'warning', title: 'Remove Item?', text: 'This item will be removed from the liquidation.', showCancelButton: true, confirmButtonText: 'Remove', cancelButtonText: 'Cancel', reverseButtons: true, confirmButtonColor: '#e03131' })
      .then((result) => {
        if (!result.isConfirmed) return;
        if (editReceiptOcr) editReceiptOcr.cancelOcr(editId);
        editExpenseItems = editExpenseItems.filter((i) => i._editId !== editId);
        editSheetId = null;
        renderEditExpenseItems();
      });
    return;
  }

  switch (action) {
    case 'remove':
      if (editReceiptOcr) editReceiptOcr.cancelOcr(editId);
      if (!item.isNew) {
        Swal.fire({ icon: 'warning', title: 'Remove Item?', text: 'This item will be removed from the liquidation.', showCancelButton: true, confirmButtonText: 'Remove', cancelButtonText: 'Cancel', reverseButtons: true })
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
  document.documentElement.classList.add('lqm-has-sticky');

  editReceiptOcr = window.SharedReceiptOcr?.create({
    maxAttachmentBytes: MAX_ATTACHMENT_BYTES,
    getExpenseItem: (itemId) => { const item = findEditItem(itemId); if (item && !item.attachments) item.attachments = []; return item; },
    getExpenseTypeOptions: () => editExpenseTypeOptions,
    renderItems: renderEditExpenseItems,
    normalizeDate, escapeHtml, swal: Swal,
    ocrEndpoint: 'transactions/liquidation/api/ocr',
    baseUrl: base_url,
  });

  loadEditExpenseTypes(() => loadEditData());

  domEdit.btnAddNewItem?.addEventListener('click', () => { editExpenseItems.push(createNewEditItem()); renderEditExpenseItems(); });
  domEdit.btnSaveEdit?.addEventListener('click', () => sendEditUpdate('LQ_SUBMITTED'));
  domEdit.btnSaveAsDraft?.addEventListener('click', () => sendEditUpdate('LQ_DRAFT'));
  domEdit.btnSaveEditMobile?.addEventListener('click', () => sendEditUpdate('LQ_SUBMITTED'));
  domEdit.btnSaveAsDraftMobile?.addEventListener('click', () => sendEditUpdate('LQ_DRAFT'));

  if (domEdit.editExpenseItems) {
    domEdit.editExpenseItems.addEventListener('input', onEditInput);
    domEdit.editExpenseItems.addEventListener('change', onEditChange);
    domEdit.editExpenseItems.addEventListener('click', onEditClick);
  }

  if (mobileEditQuery) {
    const onBreakpoint = () => { editSheetId = null; renderEditExpenseItems(); };
    if (mobileEditQuery.addEventListener) mobileEditQuery.addEventListener('change', onBreakpoint);
    else if (mobileEditQuery.addListener) mobileEditQuery.addListener(onBreakpoint);
  }
};

initEditPage();