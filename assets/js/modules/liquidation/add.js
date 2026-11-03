let expenseItems = [], expenseItemCounter = 0, expenseTypeOptions = [];
let liquidationReceiptOcr = null, currentLiquidationId = '', draftCanEdit = true;
let draftEditWindowDays = 7, draftAgeDays = 0;
const MAX_ATTACHMENT_BYTES = 2 * 1024 * 1024;

const domAdd = {};

// ─── Helpers ───
const qs = (sel, ctx = document) => ctx.querySelector(sel);
const getExpenseTypeByCode = (code) => expenseTypeOptions.find((it) => String(it.expense_code) === String(code));
const getExpenseTypeById = (id) => expenseTypeOptions.find((it) => String(it.id) === String(id));
const getExpenseTypeDisplayText = (opt) => opt ? `${normalizeDate(opt.expense_code)} - ${normalizeDate(opt.long_text)}`.replace(/^ - | - $/g, '') : '';
const findExpenseItem = (id) => expenseItems.find((it) => it.id === id);
const getItemAmount = (it) => Number(it.amount || 0);
const getItemFieldElements = (itemId, field) => Array.from((domAdd.expenseItemsContainer || document).querySelectorAll(`[data-item-field="${field}"][data-item-id="${itemId}"]`));
const getLiveItemTextField = (itemId, field) => {
  const els = getItemFieldElements(itemId, field);
  if (!els.length) return '';
  const values = els.map((el) => normalizeDate(el.value)).filter((v) => v !== '');
  return values.length ? values[0] : normalizeDate(els[0].value);
};
const getLiveItemCheckboxField = (itemId, field) => getItemFieldElements(itemId, field).some((el) => Boolean(el.checked));
const syncExpenseItemFromDom = (item) => {
  if (!item || !item.id) return;

  const liveDocumentDate = getLiveItemTextField(item.id, 'documentDate');
  const liveExpenseType = getLiveItemTextField(item.id, 'expenseType');
  const liveReference = getLiveItemTextField(item.id, 'reference');
  const liveAmount = getLiveItemTextField(item.id, 'amount');
  const liveRemarks = getLiveItemTextField(item.id, 'remarks');

  if (liveDocumentDate !== '' || !item.documentDate) item.documentDate = liveDocumentDate;
  if (liveExpenseType !== '' || !item.expenseType) item.expenseType = liveExpenseType;
  if (liveReference !== '' || !item.reference) item.reference = liveReference;
  if (liveAmount !== '' || !item.amount) item.amount = liveAmount;
  if (liveRemarks !== '' || !item.remarks) item.remarks = liveRemarks;
  if (getItemFieldElements(item.id, 'isVattable').length) item.isVattable = getLiveItemCheckboxField(item.id, 'isVattable');
};
const getAllAttachments = (it) => (it.existingAttachments || []).map((n) => ({ name: n })).concat(it.attachments || []);
const getAttachmentNamesCsv = (it) => (it.existingAttachments || []).map(normalizeDate).filter(Boolean).join(',');
const hasVendorData = (it) => !!(it.vendorName || it.vendorAddress || it.vendorTin);
const createExpenseItem = () => ({ id: ++expenseItemCounter, documentDate: '', expenseType: '', reference: '', amount: '', isVattable: false, existingAttachments: [], attachments: [], remarks: '', vendorName: '', vendorAddress: '', vendorTin: '', _vendorExpanded: false });
const itemField = (label, cls, inputHtml) => `<div class="kna-item-field ${cls}"><span class="kna-item-field-label">${label}</span>${inputHtml}</div>`;

const swal = (icon, title, text, opts = {}) => Swal.fire({ icon, title, text, ...opts });
const parseRes = (r) => (typeof r === 'string' ? jQuery.parseJSON(r) : r);
const safeNum = (v) => Number(v || 0);

// ─── Expense Type Options ───
const expenseTypeOptionsMarkup = (selected) => {
  const opts = expenseTypeOptions.map((o) => {
    const code = normalizeDate(o.expense_code), txt = getExpenseTypeDisplayText(o), desc = normalizeDate(o.description);
    return `<option value="${escapeHtml(code)}" title="${escapeHtml(desc)}" ${String(selected) === String(code) ? 'selected' : ''}>${escapeHtml(txt)}</option>`;
  }).join('');
  return `<option value="">Select type</option>${opts}`;
};

// jQuery's .trigger('change') on a <select> (select2's own mechanism) never reaches
// native addEventListener('change', ...) listeners, so the expense type field must be
// synced to item state via a jQuery-bound handler on the element itself, not the
// delegated native `onChange` on the container (which only ever sees real native events).
const applyExpenseTypeSelection = (selectEl) => {
  const itemId = Number(selectEl.getAttribute('data-item-id'));
  const item = findExpenseItem(itemId);
  if (!item) return;
  item.expenseType = selectEl.value;
  selectEl.title = normalizeDate((getExpenseTypeByCode(selectEl.value) || {}).description);
};

const initExpenseTypeSelect2 = () => {
  if (!domAdd.expenseItemsContainer || typeof jQuery.fn?.select2 === 'undefined') return;
  jQuery(domAdd.expenseItemsContainer).find('select[data-item-field="expenseType"]').each(function () {
    const $s = jQuery(this);
    if ($s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
    $s.select2({ placeholder: 'Select type', allowClear: false, width: '100%', dropdownAutoWidth: false, minimumResultsForSearch: 5 });
    const $c = $s.next('.select2-container');
    $c.find('.select2-selection--single').css({ height: '30px', border: '1px solid #d1d5db', borderRadius: '4px', background: '#fff', fontSize: '10px' });
    $c.find('.select2-selection__rendered').css({ lineHeight: '28px', paddingLeft: '8px', paddingRight: '20px', color: '#374151' });
    $c.find('.select2-selection__arrow').css({ height: '28px', width: '20px' });
    $c.find('.select2-selection__arrow b').css({ borderWidth: '3px 3px 0 3px', marginTop: '-2px' });
    $s.on('change', function () { applyExpenseTypeSelection(this); });
    const cv = $s.attr('data-current-value') || $s.val();
    if (cv) $s.val(cv).trigger('change');
  });
};

// ─── Totals & Variance ───
const getTotal = () => expenseItems.reduce((s, it) => s + getItemAmount(it), 0);

const updateTotal = () => {
  const total = getTotal();
  if (domAdd.newLiquidatedAmount) domAdd.newLiquidatedAmount.textContent = formatPHP(total);
  if (domAdd.mobileTotal) domAdd.mobileTotal.textContent = formatPHP(total);
  updateVariance();
  updateMobileProgress();
};

const updateVariance = () => {
  if (!domAdd.newVariance) return;
  const ca = safeNum(domAdd.newApprovedAmount?.value), total = getTotal();
  const refund = ca > total ? ca - total : 0, reimburse = total > ca ? total - ca : 0;
  let html = '<span class="text-muted">-</span>', mob = '-';
  if (total > 0 && refund > 0) {
    html = `<span class="kna-var-badge kna-var-return">${formatPHP(refund)} to return</span>`;
    mob = `${formatPHP(refund)} return`;
  } else if (total > 0 && reimburse > 0) {
    html = `<span class="kna-var-badge kna-var-reimburse">${formatPHP(reimburse)} to reimburse</span>`;
    mob = `${formatPHP(reimburse)} reimburse`;
  } else if (total > 0) {
    html = '<span class="kna-var-badge kna-var-balanced">0.00</span>';
    mob = '0.00';
  }
  domAdd.newVariance.innerHTML = html;
  if (domAdd.mobileVariance) {
    domAdd.mobileVariance.textContent = refund > 0 && total > 0 ? `${formatPHP(refund)} to return` : (reimburse > 0 ? `${formatPHP(reimburse)} to reimburse` : (total > 0 ? 'Fully liquidated' : mob));
    domAdd.mobileVariance.className = 'lqm-variance' + (total <= 0 ? ' d-none' : (refund > 0 ? ' is-return' : (reimburse > 0 ? ' is-reimburse' : ' is-balanced')));
  }
};

// ─── Date Range ───
const getDateRange = () => {
  const dates = expenseItems.map((it) => normalizeDate(it.documentDate)).filter((v) => /^\d{4}-\d{2}-\d{2}$/.test(v)).sort();
  return dates.length ? { from: dates[0], to: dates[dates.length - 1] } : { from: '', to: '' };
};

const syncDateRange = () => {
  const { from, to } = getDateRange();
  const txt = from && to ? `${from} to ${to}` : '';
  if (domAdd.newDateRange) domAdd.newDateRange.value = txt;
  if (domAdd.mobileDateRange) domAdd.mobileDateRange.textContent = txt || '-';
};

// ─── DOM Cache ───
const cacheAddDom = () => {
  const ids = ['liquidationRef','draftEditWindowDays','isEditMode','newCaRef','newCaAmount','newApprovedAmount','newCaDate','newDateRange','newLiquidatedAmount','newVariance','newPurpose','newPayableTo','newAddress','newCostCenter','btnAddExpenseItem','expenseItemsContainer','btnSaveDraftLiquidation','btnSaveNewLiquidation','btnSaveDraftLiquidationMobile','btnSaveNewLiquidationMobile','mobileCaRef','mobileCaAmount','mobileApprovedAmount','mobileTotal','mobileVariance','mobileCaDate','mobileDateRange','mobilePayableTo','mobileCostCenter','mobileAddress','mobilePurpose','mobileCaSelect','mobileCaChange','mobileProgressBar','mobileLeft','mobileStickyTotal'];
  ids.forEach((id) => { domAdd[id] = document.getElementById(id); });
};

// ─── Cash Advance Sync ───
const resetCADetails = () => {
  domAdd.newCaAmount.value = '0.00';
  domAdd.newApprovedAmount.value = '0.00';
  ['newCaDate','newPurpose','newPayableTo','newAddress','newCostCenter'].forEach((k) => { if (domAdd[k]) domAdd[k].value = ''; });
  ['mobileCaAmount','mobileApprovedAmount','mobileCaDate','mobilePayableTo','mobileCostCenter','mobileAddress','mobilePurpose','mobileCaRef','mobileDateRange','mobileTotal','mobileVariance'].forEach((k) => { if (domAdd[k]) domAdd[k].textContent = '-'; });
  updateVariance();
};

const syncCADetails = () => {
  const ref = normalizeDate(domAdd.newCaRef.value);
  if (!ref) return resetCADetails();
  ajax_loader('transactions/liquidation/api/get/ca_details', { CashAdvanceId: ref })
    .done((r) => {
      const res = parseRes(r);
      if (res.status !== 'success' || !res.data) { resetCADetails(); return swal('error', 'Load Failed', res.response || 'Could not load cash advance details.'); }
      const d = res.data;
      domAdd.newCaAmount.value = safeNum(d.amount).toFixed(2);
      domAdd.newApprovedAmount.value = safeNum(d.approved_amount).toFixed(2);
      domAdd.newCaDate.value = normalizeDate(d.created_date).slice(0, 10);
      domAdd.newPurpose.value = normalizeDate(d.description);
      if (domAdd.newPayableTo) domAdd.newPayableTo.value = normalizeDate(d.payable_to || '');
      if (domAdd.newAddress) domAdd.newAddress.value = normalizeDate(d.address || '');
      if (domAdd.newCostCenter) {
        const ccId = d.cost_center_id || '', ccName = d.cost_center_name || '';
        domAdd.newCostCenter.value = ccId && ccName ? `${ccId} - ${ccName}` : normalizeDate(ccId || ccName);
      }
      if (domAdd.mobileCaRef) domAdd.mobileCaRef.textContent = ref || '-';
      if (domAdd.mobileCaAmount) domAdd.mobileCaAmount.textContent = formatPHP(safeNum(d.amount));
      if (domAdd.mobileApprovedAmount) domAdd.mobileApprovedAmount.textContent = formatPHP(safeNum(d.approved_amount));
      if (domAdd.mobileCaDate) domAdd.mobileCaDate.textContent = normalizeDate(d.created_date).slice(0, 10) || '-';
      if (domAdd.mobilePayableTo) domAdd.mobilePayableTo.textContent = normalizeDate(d.payable_to || '') || '-';
      if (domAdd.mobileAddress) domAdd.mobileAddress.textContent = normalizeDate(d.address || '') || '-';
      if (domAdd.mobileCostCenter) {
        const ccId = d.cost_center_id || '', ccName = d.cost_center_name || '';
        domAdd.mobileCostCenter.textContent = ccId && ccName ? `${ccId} - ${ccName}` : (normalizeDate(ccId || ccName) || '-');
      }
      if (domAdd.mobilePurpose) domAdd.mobilePurpose.textContent = normalizeDate(d.description) || '-';
      updateVariance();
    })
    .fail(() => { resetCADetails(); swal('error', 'Load Failed', 'Could not load cash advance details.'); });
};

const loadPendingCA = () => {
  ajax_loader('transactions/liquidation/api/get/pending/ca_no', {})
    .done((r) => {
      const res = parseRes(r);
      if (res.status !== 'success') { domAdd.newCaRef.innerHTML = '<option value="">No pending cash advance</option>'; resetCADetails(); return swal('error', 'Load Failed', res.response || 'Unable to load pending cash advances.'); }
      const opts = (res.data || []).map((it) => ({
        id: normalizeDate(it.cash_advance_id),
        status: normalizeDate(it.status_name),
        amount: Number(it.approved_amount || 0),
      })).filter((it) => it.id);
      const optLabel = (it) => {
        const parts = [it.id];
        if (it.status) parts.push(it.status);
        if (it.amount > 0) parts.push(`₱${it.amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        return parts.join(' — ');
      };
      const optHtml = (it, selected) => `<option value="${escapeHtml(it.id)}"${selected ? ' selected' : ''}>${escapeHtml(optLabel(it))}</option>`;
      if (opts.length === 1) { domAdd.newCaRef.innerHTML = optHtml(opts[0], true); syncCADetails(); }
      else if (opts.length > 1) { domAdd.newCaRef.innerHTML = '<option value="">Select cash advance</option>' + opts.map((it) => optHtml(it, false)).join(''); resetCADetails(); }
      else { domAdd.newCaRef.innerHTML = '<option value="">No pending cash advance</option>'; resetCADetails(); }
    })
    .fail(() => { domAdd.newCaRef.innerHTML = '<option value="">No pending cash advance</option>'; resetCADetails(); swal('error', 'Load Failed', 'Could not load pending cash advance numbers.'); });
};

// ─── Expense Types ───
const loadExpenseTypes = () => {
  jQuery.ajax({ url: base_url + 'transactions/liquidation/api/get/expense_types', type: 'POST', dataType: 'json', headers: { Authorization: 'Bearer 12345678' } })
    .done((r) => {
      const res = parseRes(r);
      if (res.status !== 'success') { expenseTypeOptions = []; renderExpenseItems(); return; }
      expenseTypeOptions = (res.data || []).map((it) => ({ id: Number(it.id || 0), expense_code: normalizeDate(it.expense_code), long_text: normalizeDate(it.long_text), description: normalizeDate(it.description), categoryName: normalizeDate(it.category_name), short_text: normalizeDate(it.short_text) })).filter((it) => it.id && it.expense_code);
      if (!expenseTypeOptions.length) { expenseTypeOptions = []; renderExpenseItems(); return; }
      renderExpenseItems();
    })
    .fail(() => { expenseTypeOptions = []; renderExpenseItems(); });
};

// ─── OCR Helpers ───
const ocr = {
  label: (atts, id) => liquidationReceiptOcr ? liquidationReceiptOcr.attachmentsLabel(atts, id) : '<span class="kna-attachment-cell"><span class="text-muted">No file</span></span>',
  add: (id, files) => liquidationReceiptOcr ? liquidationReceiptOcr.addItemAttachments(id, files) : Promise.resolve([]),
  run: (id, file) => liquidationReceiptOcr?.runOcrAutofillForItem(id, file),
  camPerm: () => liquidationReceiptOcr?.ensureCameraPermission(),
  prompt: (id) => {
    if (!liquidationReceiptOcr) return;
    liquidationReceiptOcr.promptAttachmentSource(id, {
      onGallery: (tid) => { const el = qs(`[data-item-file="upload"][data-item-id="${tid}"]`, domAdd.expenseItemsContainer); if (el) el.click(); },
      onCamera: async (tid) => { const el = qs(`[data-item-file="camera"][data-item-id="${tid}"]`, domAdd.expenseItemsContainer); if (el && await ocr.camPerm()) el.click(); }
    });
  },
  statusHtml: (id) => {
    if (!liquidationReceiptOcr) return '';
    const s = liquidationReceiptOcr.getItemOcrState(id);
    if (!s || s.status === 'idle') return '';
    const manualBtn = `<button type="button" class="kna-ocr-manual-btn" data-item-action="ocrManual" data-item-id="${id}">Enter manually</button>`;
    const map = {
      scanning: `<div class="kna-ocr-status kna-ocr-scanning"><i class="fas fa-spinner fa-spin"></i> <span>Reading…</span>${manualBtn}</div>`,
      success: `<div class="kna-ocr-status kna-ocr-success"><i class="fas fa-check"></i></div>`,
      timeout: `<div class="kna-ocr-status kna-ocr-error"><i class="fas fa-exclamation-triangle"></i> <span>${escapeHtml(s.error)}</span>${manualBtn}</div>`,
      error: `<div class="kna-ocr-status kna-ocr-error"><i class="fas fa-exclamation-triangle"></i> <span>${escapeHtml(s.error)}</span>${manualBtn}</div>`,
      manual: ''
    };
    return map[s.status] || '';
  }
};

// ─── Vendor ───
const vendorBadge = (it) => `<span class="kna-vendor-toggle-badge">${escapeHtml(getVendorStatus(it))}</span>`;

const mobileLqQuery = window.matchMedia ? window.matchMedia('(max-width: 768px)') : null;
const isMobileLq = () => Boolean(mobileLqQuery && mobileLqQuery.matches);
let mobileSheetItemId = null;

const isBlankExpenseItem = (it) => !normalizeDate(it.documentDate) && !normalizeDate(it.expenseType) && !normalizeDate(it.reference)
  && getItemAmount(it) <= 0 && !normalizeDate(it.remarks) && !hasVendorData(it) && !getAllAttachments(it).length;

const formatShortDate = (ymd) => {
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(normalizeDate(ymd));
  if (!m) return '';
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return `${months[Number(m[2]) - 1]} ${Number(m[3])}, ${m[1]}`;
};

const getExpenseTypeShortName = (code) => {
  const et = getExpenseTypeByCode(code);
  if (!et) return '';
  return normalizeDate(et.long_text || et.description || et.short_text || et.expense_code);
};

const renderMobileCard = (it) => {
  const atts = getAllAttachments(it);
  const ocrState = liquidationReceiptOcr ? liquidationReceiptOcr.getItemOcrState(it.id) : { status: 'idle' };
  const name = getExpenseTypeShortName(it.expenseType) || normalizeDate(it.remarks) || 'Expense';
  const sub = [formatShortDate(it.documentDate), it.reference ? `OR ${normalizeDate(it.reference)}` : ''].filter(Boolean).join(' · ') || 'Tap to complete details';
  let flag = '';
  if (ocrState.status === 'scanning') flag = '<div class="lqm-exp-flag is-ocr"><i class="fas fa-spinner fa-spin"></i> Reading receipt</div>';
  else if (!atts.length) flag = '<div class="lqm-exp-flag">Attach receipt</div>';
  const amount = getItemAmount(it) > 0 ? formatPHP(getItemAmount(it)) : '—';
  return `
    <button type="button" class="lqm-exp" data-item-action="mOpen" data-item-id="${it.id}">
      <span class="lqm-exp-thumb${atts.length ? '' : ' is-missing'}"><i class="fas ${atts.length ? 'fa-receipt' : 'fa-exclamation'}"></i></span>
      <span class="lqm-exp-main">
        <span class="lqm-exp-name d-block">${escapeHtml(name)}</span>
        <span class="lqm-exp-sub d-block">${escapeHtml(sub)}</span>
      </span>
      <span class="lqm-exp-right">
        <span class="lqm-exp-amt d-block">${escapeHtml(amount)}</span>
        ${flag}
      </span>
    </button>`;
};

const renderMobileSheet = (it) => {
  const dis = draftCanEdit ? '' : 'disabled';
  const atts = getAllAttachments(it);
  const receipt = atts.length
    ? `<div class="lqm-receipt-has">${ocr.label(atts, it.id)}${draftCanEdit ? `<button type="button" data-item-action="attach" data-item-id="${it.id}">Replace</button>` : ''}</div>`
    : `<button type="button" class="lqm-receipt" data-item-action="attach" data-item-id="${it.id}" ${dis}><i class="fas fa-camera"></i>Add receipt photo<small>We'll fill in the details for you</small></button>`;
  const vendorSummary = normalizeDate(it.vendorName) || (it.isVattable ? 'VAT' : 'Optional');
  const isNew = isBlankExpenseItem(it);
  return `
    <div class="lqm-sheet-backdrop" data-item-action="mClose" data-item-id="${it.id}"></div>
    <div class="lqm-sheet" role="dialog" aria-modal="true" aria-label="Expense">
      <div class="lqm-grab"></div>
      <div class="lqm-sheet-head">
        <b>${isNew ? 'New expense' : 'Expense'}</b>
        <button type="button" class="lqm-icon-btn" data-item-action="mClose" data-item-id="${it.id}" aria-label="Close"><i class="fas fa-times"></i></button>
      </div>
      <div class="lqm-sheet-body">
        ${receipt}
        <input type="file" class="d-none" data-item-file="upload" data-item-id="${it.id}" accept="image/*,application/pdf" multiple>
        <input type="file" class="d-none" data-item-file="camera" data-item-id="${it.id}" accept="image/*" capture="environment">
        ${ocr.statusHtml(it.id)}
        <div class="lqm-amount-wrap">
          <span class="lqm-label">Amount <span class="lqm-req">*</span></span>
          <div class="lqm-amount"><span>₱</span><input type="number" inputmode="decimal" min="0" step="0.01" placeholder="0.00" data-item-field="amount" data-item-id="${it.id}" value="${escapeHtml(it.amount)}" ${dis}></div>
        </div>
        <div class="lqm-two">
          <div class="lqm-field"><span class="lqm-label">Date <span class="lqm-req">*</span></span><input type="date" class="lqm-input" data-item-field="documentDate" data-item-id="${it.id}" value="${escapeHtml(it.documentDate)}" ${dis}></div>
          <div class="lqm-field"><span class="lqm-label">OR / Invoice no. <span class="lqm-req">*</span></span><input type="text" class="lqm-input" data-item-field="reference" data-item-id="${it.id}" value="${escapeHtml(it.reference)}" placeholder="e.g. 004512" ${dis}></div>
        </div>
        <div class="lqm-field"><span class="lqm-label">Expense type <span class="lqm-req">*</span></span><select class="lqm-input" data-item-field="expenseType" data-item-id="${it.id}" ${dis}>${expenseTypeOptionsMarkup(it.expenseType)}</select></div>
        <div class="lqm-field"><span class="lqm-label">Description <span class="lqm-req">*</span></span><input type="text" class="lqm-input" data-item-field="remarks" data-item-id="${it.id}" value="${escapeHtml(it.remarks)}" placeholder="What was this for?" ${dis}></div>
        <details class="lqm-more">
          <summary>Vendor &amp; VAT <span>${escapeHtml(vendorSummary)} <i class="fas fa-chevron-down"></i></span></summary>
          <div class="lqm-field"><span class="lqm-label">Vendor name</span><input type="text" class="lqm-input" data-item-field="vendorName" data-item-id="${it.id}" value="${escapeHtml(it.vendorName)}" ${dis}></div>
          <div class="lqm-field"><span class="lqm-label">Vendor address</span><input type="text" class="lqm-input" data-item-field="vendorAddress" data-item-id="${it.id}" value="${escapeHtml(it.vendorAddress)}" ${dis}></div>
          <div class="lqm-field"><span class="lqm-label">TIN</span><input type="text" class="lqm-input" data-item-field="vendorTin" data-item-id="${it.id}" value="${escapeHtml(it.vendorTin)}" ${dis}></div>
          <label class="lqm-vat">VAT-able receipt <input type="checkbox" data-item-field="isVattable" data-item-id="${it.id}" ${it.isVattable ? 'checked' : ''} ${dis}></label>
        </details>
      </div>
      <div class="lqm-sheet-foot">
        ${draftCanEdit ? `<button type="button" class="lqm-icon-btn" data-item-action="mDelete" data-item-id="${it.id}" aria-label="Delete expense"><i class="fas fa-trash"></i></button>` : ''}
        <button type="button" class="lqm-btn lqm-btn-primary" data-item-action="mDone" data-item-id="${it.id}">${draftCanEdit ? 'Save expense' : 'Close'}</button>
      </div>
    </div>`;
};

const renderMobileExpenseItems = () => {
  const visible = expenseItems.filter((it) => !isBlankExpenseItem(it) || it.id === mobileSheetItemId);
  const listed = visible.filter((it) => it.id !== mobileSheetItemId || !isBlankExpenseItem(it));
  const count = listed.length;
  const dis = draftCanEdit ? '' : 'disabled';
  let body;
  if (!count) {
    body = `
      <div class="lqm-empty">
        <div class="lqm-empty-icon"><i class="fas fa-camera"></i></div>
        <b>Scan your first receipt</b>
        <p>Take a photo and we'll fill in the amount, date and vendor for you.</p>
        <button type="button" class="lqm-btn lqm-btn-primary w-100" data-item-action="mScan" data-item-id="-1" ${dis}><i class="fas fa-camera"></i> Scan receipt</button>
        <button type="button" class="lqm-btn lqm-btn-link" data-item-action="mManual" data-item-id="-1" ${dis}>or add manually</button>
      </div>`;
  } else {
    body = listed.map(renderMobileCard).join('') + (draftCanEdit ? `
      <div class="lqm-actions">
        <button type="button" class="lqm-btn lqm-btn-outline" data-item-action="mScan" data-item-id="-1"><i class="fas fa-camera"></i> Scan receipt</button>
        <button type="button" class="lqm-btn lqm-btn-outline" data-item-action="mManual" data-item-id="-1"><i class="fas fa-plus"></i> Add manually</button>
      </div>` : '');
  }
  const sheetItem = mobileSheetItemId ? findExpenseItem(mobileSheetItemId) : null;
  if (!sheetItem) mobileSheetItemId = null;
  document.documentElement.classList.toggle('lqm-sheet-open', Boolean(sheetItem));

  domAdd.expenseItemsContainer.innerHTML = `
    <div class="lqm-list">
      <div class="lqm-section"><b>Expenses</b><span>${count} item${count === 1 ? '' : 's'}</span></div>
      ${body}
    </div>
    <div class="lqm-sheet-wrap">${sheetItem ? renderMobileSheet(sheetItem) : ''}</div>`;

  updateTotal();
  syncDateRange();
};

const openMobileSheet = (itemId) => {
  mobileSheetItemId = itemId;
  renderExpenseItems();
};

const closeMobileSheet = () => {
  const it = mobileSheetItemId ? findExpenseItem(mobileSheetItemId) : null;
  if (it && isBlankExpenseItem(it) && expenseItems.length > 1) {
    if (liquidationReceiptOcr) liquidationReceiptOcr.cancelOcr(it.id);
    expenseItems = expenseItems.filter((x) => x.id !== it.id);
  }
  mobileSheetItemId = null;
  renderExpenseItems();
};

const startMobileExpense = (scan) => {
  const blank = expenseItems.find((it) => isBlankExpenseItem(it));
  const item = blank || createExpenseItem();
  if (!blank) expenseItems.push(item);
  openMobileSheet(item.id);
  if (scan) ocr.prompt(item.id);
};

const syncMobileCaSelect = () => {
  const src = domAdd.newCaRef, dst = domAdd.mobileCaSelect, wrap = domAdd.mobileCaChange;
  if (!src || !dst || !wrap) return;
  dst.innerHTML = src.innerHTML;
  dst.value = src.value;
  dst.disabled = src.disabled;
  const realOptions = Array.from(src.options).filter((o) => o.value);
  wrap.classList.toggle('d-none', src.disabled || realOptions.length < 2);
  if (!src.value && domAdd.mobileCaRef) domAdd.mobileCaRef.textContent = realOptions.length ? 'Select cash advance' : 'No pending cash advance';
};

const updateMobileProgress = () => {
  const approved = safeNum(domAdd.newApprovedAmount?.value), total = getTotal();
  if (domAdd.mobileStickyTotal) domAdd.mobileStickyTotal.textContent = formatPHP(total);
  if (domAdd.mobileProgressBar) {
    const pct = approved > 0 ? Math.min(100, (total / approved) * 100) : 0;
    domAdd.mobileProgressBar.style.width = `${pct}%`;
    domAdd.mobileProgressBar.classList.toggle('is-over', approved > 0 && total > approved);
  }
  if (domAdd.mobileLeft) {
    if (approved <= 0) domAdd.mobileLeft.textContent = '';
    else if (total <= approved) domAdd.mobileLeft.textContent = `${formatPHP(approved - total)} left`;
    else domAdd.mobileLeft.textContent = `${formatPHP(total - approved)} over`;
  }
};

// ─── Render ───
const renderExpenseItems = () => {
  if (!domAdd.expenseItemsContainer) return;
  if (!expenseItems.length) expenseItems = [createExpenseItem()];
  if (isMobileLq()) return renderMobileExpenseItems();
  document.documentElement.classList.remove('lqm-sheet-open');

  const rows = expenseItems.map((it, i) => {
    const et = getExpenseTypeByCode(it.expenseType), desc = normalizeDate((et || {}).description);
    const atts = getAllAttachments(it), summary = ocr.label(atts, it.id), hasAtt = atts.length > 0;
    const btnLabel = hasAtt ? 'Replace' : 'Attach';
    const attTitle = hasAtt ? escapeHtml(atts.map((f) => f.name || f.fileName || '').join(', ')) : '';
    return `
      <div class="kna-item-row" data-item-id="${it.id}">
        <div class="kna-item-row-index">${i + 1}</div>
        <div class="kna-item-row-fields">
          ${itemField('Doc Date', 'kna-f-date', `<input type="date" class="form-control form-control-sm" data-item-field="documentDate" data-item-id="${it.id}" value="${escapeHtml(it.documentDate)}">`)}
          ${itemField('Expense Type', 'kna-f-type', `<select class="form-control form-control-sm kna-expense-type-select" data-item-field="expenseType" data-item-id="${it.id}" data-current-value="${escapeHtml(it.expenseType)}" title="${escapeHtml(desc)}">${expenseTypeOptionsMarkup(it.expenseType)}</select>`)}
          <div class="kna-item-field kna-f-attach">
            <span class="kna-item-field-label">Attachment</span>
            <div class="kna-item-attach-inline">
              <span class="kna-attachment-cell" title="${attTitle}">${summary}</span>
              <button type="button" class="btn btn-outline-primary btn-sm kna-small" data-item-action="attach" data-item-id="${it.id}">${btnLabel}</button>
              <input type="file" class="d-none" data-item-file="upload" data-item-id="${it.id}" accept="image/*,application/pdf" multiple>
              <input type="file" class="d-none" data-item-file="camera" data-item-id="${it.id}" accept="image/*" capture="environment">
            </div>
            ${ocr.statusHtml(it.id)}
          </div>
          ${itemField('Reference', 'kna-f-ref', `<input type="text" class="form-control form-control-sm" data-item-field="reference" data-item-id="${it.id}" value="${escapeHtml(it.reference)}" placeholder="Invoice / OR no.">`)}
          ${itemField('Amount', 'kna-f-amount', `<input type="number" min="0" step="0.01" class="form-control form-control-sm text-right" data-item-field="amount" data-item-id="${it.id}" value="${escapeHtml(it.amount)}" placeholder="0.00">`)}
          ${itemField('VAT', 'kna-f-vat', `<label class="kna-vat-wrap"><input type="checkbox" class="kna-vat-input" data-item-field="isVattable" data-item-id="${it.id}" ${it.isVattable ? 'checked' : ''}></label>`)}
          ${itemField('Vendor Name', 'kna-f-vendor', `<input type="text" class="form-control form-control-sm" data-item-field="vendorName" data-item-id="${it.id}" value="${escapeHtml(it.vendorName)}" placeholder="Vendor name">`)}
          ${itemField('Vendor Address', 'kna-f-vendor', `<input type="text" class="form-control form-control-sm" data-item-field="vendorAddress" data-item-id="${it.id}" value="${escapeHtml(it.vendorAddress)}" placeholder="Address">`)}
          ${itemField('TIN', 'kna-f-tin', `<input type="text" class="form-control form-control-sm" data-item-field="vendorTin" data-item-id="${it.id}" value="${escapeHtml(it.vendorTin)}" placeholder="TIN">`)}
          ${itemField('Remarks', 'kna-f-remarks', `<input type="text" class="form-control form-control-sm" data-item-field="remarks" data-item-id="${it.id}" value="${escapeHtml(it.remarks)}" placeholder="Remarks">`)}
        </div>
        <button type="button" class="kna-item-row-remove" data-item-action="remove" data-item-id="${it.id}" title="Remove item"><i class="fas fa-trash"></i></button>
      </div>`;
  }).join('');

  domAdd.expenseItemsContainer.innerHTML = `<div class="kna-item-rows">${rows}</div>`;

  initExpenseTypeSelect2();
  updateTotal();
  syncDateRange();
};

// ─── Form State & Validation ───
const getFormState = () => {
  const caRef = normalizeDate(domAdd.newCaRef.value);
  const totalAmount = Number(normalizeDate(domAdd.newLiquidatedAmount?.textContent || '0').replace(/[^0-9.\-]/g, '') || 0);
  const { from: dateFrom, to: dateTo } = getDateRange();
  return { caRef, totalAmount, dateFrom, dateTo, purpose: normalizeDate(domAdd.newPurpose.value) };
};

const validateBeforeSave = (statusCode) => {
  expenseItems.forEach(syncExpenseItemFromDom);
  const state = getFormState();
  const missingIndex = expenseItems.findIndex((it) => !normalizeDate(it.documentDate) || !normalizeDate(it.expenseType) || !normalizeDate(it.reference) || getItemAmount(it) <= 0 || !normalizeDate(it.remarks));

  if (!state.caRef || !state.dateFrom || !state.dateTo || !state.purpose)
    return swal('warning', 'Missing fields', 'Cash advance, document dates, and notes are required.'), null;
  if (state.dateFrom > state.dateTo)
    return swal('warning', 'Invalid expense range', 'Expense range start must not be later than end.'), null;
  if (!expenseItems.length)
    return swal('warning', 'Item required', 'Please add at least one expense item.'), null;
  if (missingIndex >= 0) {
    const item = expenseItems[missingIndex] || {};
    const missingFields = [];
    if (!normalizeDate(item.documentDate)) missingFields.push('Document Date');
    if (!normalizeDate(item.expenseType)) missingFields.push('Expense Type');
    if (!normalizeDate(item.reference)) missingFields.push('Reference');
    if (getItemAmount(item) <= 0) missingFields.push('Amount (> 0)');
    if (!normalizeDate(item.remarks)) missingFields.push('Remarks');

    return Swal.fire({
      icon: 'warning',
      title: 'Incomplete item',
      html: `${isMobileLq() ? 'Expense' : 'Row'} <strong>${missingIndex + 1}</strong> is missing: <strong>${escapeHtml(missingFields.join(', '))}</strong>.`,
    }).then(() => { if (isMobileLq() && item.id) openMobileSheet(item.id); }), null;
  }
  if (state.totalAmount <= 0)
    return swal('warning', 'Invalid total', 'Total must be greater than 0.'), null;
  if (statusCode === 'LQ_SUBMITTED' && !draftCanEdit)
    return swal('warning', 'Draft locked', `This draft is already ${draftAgeDays} day(s) old and can no longer be edited.`), null;

  return state;
};

// ─── Save / Submit ───
const sendLiquidation = (statusCode) => {
  if (isMobileLq()) {
    const filled = expenseItems.filter((it) => !isBlankExpenseItem(it));
    if (filled.length !== expenseItems.length) {
      expenseItems = filled.length ? filled : [createExpenseItem()];
      mobileSheetItemId = null;
      renderExpenseItems();
    }
  }
  const state = validateBeforeSave(statusCode);
  if (!state) return;

  const noAtt = expenseItems.find((it) => !getAllAttachments(it).length);
  const caAmt = safeNum(domAdd.newCaAmount?.value);
  const approvedAmt = safeNum(domAdd.newApprovedAmount?.value);
  const refund = approvedAmt > state.totalAmount ? approvedAmt - state.totalAmount : 0;
  const reimburse = state.totalAmount > approvedAmt ? state.totalAmount - approvedAmt : 0;

  const post = () => {
    const fd = new FormData();
    if (currentLiquidationId) fd.append('LiquidationId', currentLiquidationId);
    fd.append('CashAdvanceId', state.caRef);
    fd.append('CashAdvanceAmount', caAmt.toFixed(2));
    fd.append('TotalAmountSpent', state.totalAmount.toFixed(2));
    fd.append('RefundAmount', refund.toFixed(2));
    fd.append('ReimburseAmount', reimburse.toFixed(2));
    fd.append('StatusCode', statusCode);
    fd.append('Description', state.purpose);
    fd.append('ExpenseRangeFrom', state.dateFrom);
    fd.append('ExpenseRangeTo', state.dateTo);
    fd.append('Expenses', JSON.stringify(expenseItems.map((it) => ({
      DocumentDate: it.documentDate, ExpenseCategory: it.expenseType, InvoiceReceiptNo: it.reference,
      ActualAmount: getItemAmount(it), IsVatable: Boolean(it.isVattable), Description: it.remarks,
      Attachment: getAttachmentNamesCsv(it), VendorName: it.vendorName || '', VendorAddress: it.vendorAddress || '', VendorTin: it.vendorTin || ''
    }))));
    expenseItems.forEach((it, i) => { (it.attachments || []).forEach((f) => fd.append(`attachments[${i}][]`, f)); });

    ajax_loader_formdata_loading('transactions/liquidation/api/save', fd)
      .done((r) => {
        const res = parseRes(r);
        if (res.status !== 'success') return swal('error', 'Failed', res.response || 'Failed to save liquidation.');
        const gid = res.data && res.data.id ? normalizeDate(res.data.id) : '';
        if (gid) { currentLiquidationId = gid; if (domAdd.liquidationRef) domAdd.liquidationRef.value = gid; }
        const isDraft = statusCode === 'LQ_DRAFT';
        const displayId = normalizeDate(gid || currentLiquidationId || '');
        const actionText = isDraft ? 'Draft saved' : 'Liquidation submitted';

        Swal.fire({
          icon: 'success',
          title: isDraft ? 'Draft Saved' : 'Submitted',
          html: `${escapeHtml(actionText)} successfully.<br><strong>${escapeHtml(displayId || 'ID not available')}</strong>`,
        }).then(() => goToPath('transactions/liquidation'));
      })
      .fail(() => swal('error', 'Request Failed', 'Could not connect to the server.'));
  };

  if (statusCode === 'LQ_SUBMITTED' && noAtt) {
    swal('warning', 'Missing attachment', 'Some expense items do not have attachments. Please confirm if you want to continue.', { showCancelButton: true, confirmButtonText: 'Continue', cancelButtonText: 'Review items', reverseButtons: true })
      .then((r) => { if (r.isConfirmed) post(); });
    return;
  }
  if (statusCode === 'LQ_SUBMITTED') {
    swal('question', 'Confirm Submission', 'Are you sure you want to proceed?', { showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'No', reverseButtons: true })
      .then((r) => { if (r.isConfirmed) post(); });
    return;
  }
  post();
};

// ─── Editability & Draft ───
const setEditability = (editable) => {
  draftCanEdit = editable;
  const d = !editable;
  if (domAdd.newCaRef) domAdd.newCaRef.disabled = d || Boolean(currentLiquidationId);
  if (domAdd.btnAddExpenseItem) domAdd.btnAddExpenseItem.disabled = d;
  if (domAdd.btnSaveNewLiquidation) domAdd.btnSaveNewLiquidation.disabled = d;
  if (domAdd.btnSaveDraftLiquidation) domAdd.btnSaveDraftLiquidation.disabled = d;
};

const loadDraftForEdit = () => {
  const draftRef = normalizeDate(domAdd.liquidationRef?.value || '');
  if (!draftRef) return jQuery.Deferred().resolve().promise();
  currentLiquidationId = draftRef;

  return ajax_loader('transactions/liquidation/api/get/draft', { LiquidationId: draftRef })
    .done((r) => {
      const res = parseRes(r);
      if (res.status !== 'success' || !res.data || !res.data.header) {
        swal('error', 'Unable to open draft', res.response || 'Draft liquidation is not available.').then(() => goToPath('transactions/liquidation'));
        return;
      }
      const payload = res.data, header = payload.header, details = payload.details || [];
      const draftCaRef = normalizeDate(header.cash_advance_id);
      draftAgeDays = safeNum(payload.draftAgeDays);
      draftEditWindowDays = safeNum(payload.draftEditWindowDays) || draftEditWindowDays;
      setEditability(Boolean(payload.canEdit));

      if (draftCaRef && !domAdd.newCaRef.querySelector(`option[value="${draftCaRef}"]`)) {
        const opt = document.createElement('option'); opt.value = draftCaRef; opt.textContent = draftCaRef; domAdd.newCaRef.appendChild(opt);
      }
      domAdd.newCaRef.value = draftCaRef;
      domAdd.newCaAmount.value = safeNum(header.ca_amount).toFixed(2);
      domAdd.newCaDate.value = normalizeDate(header.created_date).slice(0, 10);
      domAdd.newPurpose.value = normalizeDate(header.description);
      if (domAdd.newPayableTo) domAdd.newPayableTo.value = normalizeDate(header.payable_to || '');
      if (domAdd.newAddress) domAdd.newAddress.value = normalizeDate(header.address || '');
      if (domAdd.newCostCenter) {
        const ccId = header.cost_center_id || '', ccName = header.cost_center_name || '';
        domAdd.newCostCenter.value = ccId && ccName ? `${ccId} - ${ccName}` : normalizeDate(ccId || ccName);
      }
      if (domAdd.mobileCaRef) domAdd.mobileCaRef.textContent = draftCaRef || '-';
      if (domAdd.mobileCaAmount) domAdd.mobileCaAmount.textContent = formatPHP(safeNum(header.ca_amount));
      if (domAdd.mobileApprovedAmount) domAdd.mobileApprovedAmount.textContent = formatPHP(safeNum(header.approved_amount));
      if (domAdd.mobileCaDate) domAdd.mobileCaDate.textContent = normalizeDate(header.created_date).slice(0, 10) || '-';
      if (domAdd.mobilePayableTo) domAdd.mobilePayableTo.textContent = normalizeDate(header.payable_to || '') || '-';
      if (domAdd.mobileAddress) domAdd.mobileAddress.textContent = normalizeDate(header.address || '') || '-';
      if (domAdd.mobileCostCenter) {
        const ccId = header.cost_center_id || '', ccName = header.cost_center_name || '';
        domAdd.mobileCostCenter.textContent = ccId && ccName ? `${ccId} - ${ccName}` : (normalizeDate(ccId || ccName) || '-');
      }
      if (domAdd.mobilePurpose) domAdd.mobilePurpose.textContent = normalizeDate(header.description) || '-';

      expenseItems = details.map((d) => ({
        id: ++expenseItemCounter, documentDate: normalizeDate(d.document_date).slice(0, 10),
        expenseType: normalizeDate(d.expense_code || d.expense_category || d.expense_category_id || ''),
        reference: normalizeDate(d.invoice_receipt_no), amount: normalizeDate(d.actual_amount),
        isVattable: Boolean(Number(d.is_vatable || 0)),
        existingAttachments: normalizeDate(d.attachment).split(',').map((n) => n.trim()).filter(Boolean),
        attachments: [], remarks: normalizeDate(d.description),
        vendorName: normalizeDate(d.vendor_name || ''), vendorAddress: normalizeDate(d.vendor_address || ''), vendorTin: normalizeDate(d.vendor_tin || ''), _vendorExpanded: false
      }));
      if (!expenseItems.length) expenseItems = [createExpenseItem()];
      renderExpenseItems();
      if (!payload.canEdit) swal('info', 'Draft Locked', `This draft is ${draftAgeDays} day(s) old. Drafts can only be edited within ${draftEditWindowDays} day(s).`);
    })
    .fail(() => swal('error', 'Unable to open draft', 'Could not load draft liquidation.').then(() => goToPath('transactions/liquidation')));
};

// ─── Event Handlers ───
const onInput = (e) => {
  const t = e.target, itemId = Number(t.getAttribute('data-item-id')), field = t.getAttribute('data-item-field');
  if (!itemId || !field) return;
  const item = findExpenseItem(itemId);
  if (!item) return;
  if (field === 'isVattable') { item.isVattable = Boolean(t.checked); return; }
  item[field] = t.value;
  if (field === 'expenseType') t.title = normalizeDate((getExpenseTypeByCode(t.value) || {}).description);
  if (field === 'amount') { updateTotal(); return; }
  if (field === 'documentDate') syncDateRange();
};

const onChange = async (e) => {
  const t = e.target, itemId = Number(t.getAttribute('data-item-id')), fileMode = t.getAttribute('data-item-file'), field = t.getAttribute('data-item-field');
  if (!itemId) return;
  const item = findExpenseItem(itemId);
  if (!item) return;

  // Select2 updates expense type via "change", so persist non-file field changes here.
  if (field && !fileMode) {
    if (field === 'isVattable') { item.isVattable = Boolean(t.checked); return; }
    item[field] = t.value;
    if (field === 'expenseType') t.title = normalizeDate((getExpenseTypeByCode(t.value) || {}).description);
    if (field === 'amount') { updateTotal(); return; }
    if (field === 'documentDate') syncDateRange();
    return;
  }

  if (!fileMode) return;
  item.attachments = []; item.existingAttachments = [];
  if (liquidationReceiptOcr) liquidationReceiptOcr.cancelOcr(itemId);
  const accepted = await ocr.add(itemId, Array.from(t.files || []));
  if (accepted.length) { item.attachments = [accepted[0]]; ocr.run(itemId, accepted[0]); }
  t.value = '';
  renderExpenseItems();
};

const onClick = (e) => {
  const btn = e.target.closest('[data-item-action]');
  if (!btn) return;
  const itemId = Number(btn.getAttribute('data-item-id')), action = btn.getAttribute('data-item-action');
  if (!itemId || !action) return;

  if (action === 'mScan' || action === 'mManual') {
    if (draftCanEdit) startMobileExpense(action === 'mScan');
    return;
  }
  if (action === 'mClose' || action === 'mDone') {
    closeMobileSheet();
    return;
  }

  const item = findExpenseItem(itemId);
  if (!item) return;

  if (action === 'mOpen') {
    openMobileSheet(itemId);
    return;
  }
  if (action === 'mDelete') {
    swal('warning', 'Remove expense?', 'This expense and its receipt will be removed.', { showCancelButton: true, confirmButtonText: 'Remove', cancelButtonText: 'Cancel', reverseButtons: true, confirmButtonColor: '#e03131' })
      .then((r) => {
        if (!r.isConfirmed) return;
        if (liquidationReceiptOcr) liquidationReceiptOcr.cancelOcr(itemId);
        expenseItems = expenseItems.filter((it) => it.id !== itemId);
        mobileSheetItemId = null;
        renderExpenseItems();
      });
    return;
  }

  switch (action) {
    case 'remove':
      if (liquidationReceiptOcr) liquidationReceiptOcr.cancelOcr(itemId);
      expenseItems = expenseItems.filter((it) => it.id !== itemId);
      renderExpenseItems();
      break;
    case 'attach': ocr.prompt(itemId); break;
    case 'ocrManual': if (liquidationReceiptOcr) liquidationReceiptOcr.markManual(itemId); break;
  }
};

// ─── Init ───
const initAddPage = () => {
  cacheAddDom();
  document.documentElement.classList.add('lqm-has-sticky');
  draftEditWindowDays = safeNum(domAdd.draftEditWindowDays?.value) || 7;
  currentLiquidationId = normalizeDate(domAdd.liquidationRef?.value || '');
  liquidationReceiptOcr = window.SharedReceiptOcr && window.SharedReceiptOcr.create({
    maxAttachmentBytes: MAX_ATTACHMENT_BYTES, getExpenseItem: findExpenseItem, getExpenseTypeOptions: () => expenseTypeOptions,
    renderItems: renderExpenseItems, normalizeDate, escapeHtml, swal: Swal, ocrEndpoint: 'transactions/liquidation/api/ocr', baseUrl: base_url
  });
  if (domAdd.newDateRange) { domAdd.newDateRange.setAttribute('readonly', 'readonly'); domAdd.newDateRange.setAttribute('placeholder', 'Auto based on document dates'); }
  expenseItems = [createExpenseItem()];
  renderExpenseItems();
  resetCADetails();
  loadExpenseTypes();
  if (currentLiquidationId) loadDraftForEdit(); else loadPendingCA();

  domAdd.btnSaveNewLiquidation?.addEventListener('click', () => sendLiquidation('LQ_SUBMITTED'));
  domAdd.btnSaveDraftLiquidation?.addEventListener('click', () => sendLiquidation('LQ_DRAFT'));
  domAdd.btnSaveNewLiquidationMobile?.addEventListener('click', () => sendLiquidation('LQ_SUBMITTED'));
  domAdd.btnSaveDraftLiquidationMobile?.addEventListener('click', () => sendLiquidation('LQ_DRAFT'));
  domAdd.btnAddExpenseItem?.addEventListener('click', () => { expenseItems.push(createExpenseItem()); renderExpenseItems(); });
  domAdd.newCaRef?.addEventListener('change', syncCADetails);
  if (domAdd.newCaRef && window.MutationObserver) {
    new MutationObserver(syncMobileCaSelect).observe(domAdd.newCaRef, { childList: true, attributes: true, attributeFilter: ['disabled'] });
  }
  domAdd.newCaRef?.addEventListener('change', syncMobileCaSelect);
  domAdd.mobileCaSelect?.addEventListener('change', () => {
    domAdd.newCaRef.value = domAdd.mobileCaSelect.value;
    domAdd.newCaRef.dispatchEvent(new Event('change'));
  });
  syncMobileCaSelect();
  if (mobileLqQuery) {
    const onBreakpoint = () => { mobileSheetItemId = null; expenseItems.forEach(syncExpenseItemFromDom); renderExpenseItems(); };
    if (mobileLqQuery.addEventListener) mobileLqQuery.addEventListener('change', onBreakpoint);
    else if (mobileLqQuery.addListener) mobileLqQuery.addListener(onBreakpoint);
  }
  domAdd.expenseItemsContainer?.addEventListener('input', onInput);
  domAdd.expenseItemsContainer?.addEventListener('change', onChange);
  domAdd.expenseItemsContainer?.addEventListener('click', onClick);
};