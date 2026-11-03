const domReview = {
  approvalRef: null, currentUserId: null, reviewMode: null, reviewTitle: null,
  reviewStatusBadge: null, reviewHeaderFields: null, viewApprovalItems: null,
  reviewTimeline: null, summaryReviewed: null, summaryApprovedAmount: null,
  summaryRejectedAmount: null, reviewerRemarks: null, btnSubmitDecision: null,
  decisionSummary: null, paymentActionBar: null, paymentActionStatus: null,
  paymentActionRemarks: null, paymentActionRemarksLabel: null, btnPaymentAction: null
};

const IMG_EXTS = /\.(jpg|jpeg|png|gif|webp)$/i;
const ATTACHMENTS_BASE = base_url + 'assets/uploads/attachments/';

let reviewState = {
  transactionType: null, referenceNo: null, items: [], header: null,
  decisions: {}, totalItems: 0, reviewedCount: 0, approvedAmount: 0, rejectedAmount: 0,
  isPastMode: false, paymentCapability: null
};

/* ─── Derive an overall Approved/Rejected badge from item outcomes, used
   in past (read-only) mode since the header status coming back from the
   SP for LQ/RMB is not reliable ("Pending Approval" is hardcoded at
   render time regardless of actual state). ─── */
const getPastOverallStatusBadge = (items) => {
  const hasRejected = (items || []).some(item => (item.item_status || '').toUpperCase() === 'REJECTED');
  return getStatusBadge(hasRejected ? 'Rejected' : 'Approved');
};

/* ─── AMOUNT TO WORDS (from add.js) ─── */
const amountToWords = (amount) => {
  const num = parseFloat(String(amount).replace(/,/g, ''));
  if (!num || num < 0) return '';
  const ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
  const tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
  const convertLessThanOneThousand = (n) => {
    let result = '';
    if (n >= 100) { result += ones[Math.floor(n / 100)] + ' hundred'; n %= 100; if (n > 0) result += ' '; }
    if (n < 20) { result += ones[n]; } else { result += tens[Math.floor(n / 10)]; if (n % 10 !== 0) result += '-' + ones[n % 10]; }
    return result.trim();
  };
  const convert = (n) => {
    if (n === 0) return 'zero';
    let result = '';
    const billion = Math.floor(n / 1000000000);
    const million = Math.floor((n % 1000000000) / 1000000);
    const thousand = Math.floor((n % 1000000) / 1000);
    const remainder = n % 1000;
    if (billion) result += convert(billion) + ' billion ';
    if (million) result += convert(million) + ' million ';
    if (thousand) result += convert(thousand) + ' thousand ';
    if (remainder) result += convertLessThanOneThousand(remainder);
    return result.trim();
  };
  const pesos = Math.floor(num);
  const centavos = Math.round((num - pesos) * 100);
  let words = convert(pesos) + ' pesos';
  if (centavos > 0) words += ' and ' + convert(centavos) + ' centavos';
  words += ' only';
  return words.replace(/\b\w/g, (l) => l.toUpperCase());
};

/* ─── UTILITIES ─── */
const formatPHP = n => {
  const num = Number(n) || 0;
  return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const normalizeDate = str => {
  if (str == null) return '';
  const clean = String(str).trim();
  return (!clean || clean === 'null' || clean === 'undefined') ? '' : clean;
};

const escapeHtml = str => str == null ? '' : String(str)
  .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const formatFileSize = bytes => {
  if (!bytes || bytes <= 0) return '';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const isImageFile = fileName => fileName ? /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(fileName) : false;

const getFileIconClass = fileName => {
  if (!fileName) return 'fa-file';
  const l = fileName.toLowerCase();
  if (/\.pdf$/i.test(l)) return 'fa-file-pdf';
  if (/\.(doc|docx|txt|rtf)$/i.test(l)) return 'fa-file-word';
  if (/\.(xls|xlsx|csv)$/i.test(l)) return 'fa-file-excel';
  if (/\.(zip|rar|7z|tar|gz)$/i.test(l)) return 'fa-file-archive';
  if (/\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(l)) return 'fa-file-image';
  return 'fa-file';
};

const getFileIconColor = fileName => {
  if (!fileName) return '#6b7280';
  const l = fileName.toLowerCase();
  if (/\.pdf$/i.test(l)) return '#dc2626';
  if (/\.(doc|docx|txt|rtf)$/i.test(l)) return '#2563eb';
  if (/\.(xls|xlsx|csv)$/i.test(l)) return '#16a34a';
  if (/\.(zip|rar|7z|tar|gz)$/i.test(l)) return '#7c3aed';
  if (/\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i.test(l)) return '#0891b2';
  return '#6b7280';
};

const calculateVat = (grossAmount, isVatable) => {
  if (!isVatable || !grossAmount) return { netAmount: grossAmount, vatAmount: 0, grossAmount };
  const net = Number(grossAmount) / 1.12, vat = Number(grossAmount) - net;
  return { netAmount: Math.round(net * 100) / 100, vatAmount: Math.round(vat * 100) / 100, grossAmount: Number(grossAmount) };
};

const syncEditableFieldInputs = (key, fieldClass, value, sourceEl) => {
  document.querySelectorAll(`.${fieldClass}[data-key="${key}"]`).forEach(el => {
    if (el === sourceEl) return;
    if (window.jQuery && $(el).hasClass('select2-hidden-accessible')) {
      $(el).val(value).trigger('change.select2');
      return;
    }
    el.value = value;
  });
};

const resolveApprovalPdfUrl = row => {
  if (!row || typeof row !== 'object') return '';
  const candidates = [
    normalizeDate(row.active_pdf_url), normalizeDate(row.final_pdf_url), normalizeDate(row.generated_pdf_url),
    normalizeDate(row.final_pdf_path), normalizeDate(row.generated_pdf_path)
  ].filter(Boolean);
  if (!candidates.length) return '';
  const first = candidates[0];
  if (/^https?:\/\//i.test(first)) return first;
  return first.indexOf('assets/') === 0 ? base_url + first : '';
};

const getStatusBadge = statusName => {
  const name = String(statusName || '').toLowerCase();
  if (name.includes('pending')) return '<span class="kna-badge kna-badge-pending">Pending</span>';
  if (name.includes('approved') || name.includes('approve')) return '<span class="kna-badge kna-badge-approved">Approved</span>';
  if (name.includes('rejected') || name.includes('reject')) return '<span class="kna-badge kna-badge-rejected">Rejected</span>';
  if (name.includes('partial')) return '<span class="kna-badge kna-badge-partial">Partially Approved</span>';
  return '<span class="kna-badge kna-badge-pending">' + escapeHtml(statusName || 'Pending') + '</span>';
};

const formatTimelineDate = dateStr => {
  if (!dateStr) return '';
  const raw = normalizeDate(dateStr);
  if (!raw) return '';
  const date = new Date(raw.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return raw;
  const yyyy = date.getFullYear(), mm = String(date.getMonth() + 1).padStart(2, '0'), dd = String(date.getDate()).padStart(2, '0');
  let hh = date.getHours(); const ampm = hh >= 12 ? 'PM' : 'AM'; hh = hh % 12 || 12;
  return `${yyyy}-${mm}-${dd} ${String(hh).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}${ampm}`;
};

/* ─── LIGHTBOX & VIEWER ─── */
const openLightbox = url => {
  const lb = document.getElementById('knaLightbox'), img = document.getElementById('knaLightboxImg');
  if (lb && img) { img.src = url; lb.classList.remove('d-none'); }
};

const bindViewerToggleEvents = () => {
  document.querySelectorAll('[data-toggle-doc-viewer]').forEach(btn => {
    if (btn.dataset.bound === '1') return;
    btn.dataset.bound = '1';
    btn.addEventListener('click', () => {
      const panel = document.getElementById(btn.getAttribute('data-target'));
      if (!panel) return;
      const iframe = panel.querySelector('iframe[data-src]'), isHidden = panel.classList.contains('d-none');
      if (isHidden) {
        if (iframe && iframe.getAttribute('src') === 'about:blank') {
          const s = iframe.getAttribute('data-src') || '';
          if (s) iframe.setAttribute('src', s);
        }
        panel.classList.remove('d-none');
        btn.setAttribute('aria-expanded', 'true'); btn.classList.add('is-active');
        btn.setAttribute('title', 'Hide document'); btn.setAttribute('aria-label', 'Hide document');
      } else {
        panel.classList.add('d-none');
        btn.setAttribute('aria-expanded', 'false'); btn.classList.remove('is-active');
        btn.setAttribute('title', 'Show document'); btn.setAttribute('aria-label', 'Show document');
      }
    });
  });
};

/* ─── ROW SYNC & BUTTON HELPERS ─── */
const syncRowHeights = () => {
  const mainTable = document.querySelector('.kna-review-table-main'), actionTable = document.querySelector('.kna-review-table-action');
  if (!mainTable || !actionTable) return;
  const mainRows = mainTable.querySelectorAll('tbody tr[data-item-key]'), actionRows = actionTable.querySelectorAll('tbody tr[data-item-key]');
  mainRows.forEach(r => r.style.height = ''); actionRows.forEach(r => r.style.height = '');
  const map = new Map(); actionRows.forEach(r => { const k = r.getAttribute('data-item-key'); if (k) map.set(k, r); });
  mainRows.forEach(mainRow => {
    const actionRow = map.get(mainRow.getAttribute('data-item-key'));
    if (!actionRow) return;
    const max = Math.max(mainRow.getBoundingClientRect().height, actionRow.getBoundingClientRect().height);
    mainRow.style.height = max + 'px'; actionRow.style.height = max + 'px';
  });
};

const setRejectButtonIcon = (btn, isConfirm) => {
  if (!btn) return;
  const icon = btn.querySelector('i'); if (!icon) return;
  icon.className = isConfirm ? 'fas fa-paper-plane' : 'fas fa-times';
  btn.title = isConfirm ? 'Click again to confirm rejection' : 'Reject';
};

const toggleCancelButton = (key, show) => {
  document.querySelectorAll(`[data-cancel-reject][data-key="${key}"]`).forEach(btn => btn.classList.toggle('d-none', !show));
};

const updateRowStyling = (key, decision) => {
  document.querySelectorAll(`[data-item-key="${key}"]`).forEach(row => {
    row.classList.remove('is-approved', 'is-rejected');
    if (decision === 'approve') row.classList.add('is-approved');
    if (decision === 'reject') row.classList.add('is-rejected');
  });
  requestAnimationFrame(syncRowHeights);
};

const refreshItemStatusBadge = (key, newStatus) => {
  const decision = reviewState.decisions[key];
  if (decision && decision.isReadOnly) return;
  const badgeClass = newStatus === 'APPROVED' ? 'kna-badge-approved' : 'kna-badge-rejected';
  const badgeHtml = newStatus && newStatus !== 'PENDING' ? `<div class="mb-1"><span class="kna-badge ${badgeClass}">${newStatus}</span></div>` : '';
  document.querySelectorAll(`.kna-review-table-action [data-item-key="${key}"], .kna-exp-mobile [data-item-key="${key}"]`).forEach(el => {
    const container = el.querySelector('.kna-item-decision'); if (!container) return;
    const old = container.querySelector('.kna-badge'); if (old) old.parentElement.remove();
    if (badgeHtml) container.insertAdjacentHTML('afterbegin', badgeHtml);
  });
  requestAnimationFrame(syncRowHeights);
};

/* ─── CANCEL & READ-ONLY HELPERS ─── */
const cancelRejectFlow = key => {
  const ds = reviewState.decisions[key];
  if (ds) ds.decision = null;
  document.querySelectorAll('.kna-item-decision').forEach(c => {
    const remark = c.querySelector(`.kna-item-remark[data-key="${key}"]`), rejectBtn = c.querySelector(`.kna-toggle-btn.is-reject[data-key="${key}"]`), approveBtn = c.querySelector(`.kna-toggle-btn.is-approve[data-key="${key}"]`);
    if (remark) { remark.classList.add('d-none'); remark.value = ''; }
    if (rejectBtn) { rejectBtn.classList.remove('is-active'); setRejectButtonIcon(rejectBtn, false); }
    if (approveBtn) approveBtn.classList.remove('is-active');
  });
  toggleCancelButton(key, false); updateRowStyling(key, null); refreshItemStatusBadge(key, 'PENDING'); updateSummary();
};

const headerDecisionLabel = (statusName) => {
  const s = String(statusName || '');
  return /reject|pending/i.test(s) ? s : 'Approved';
};

const renderReadOnlyAction = (itemStatus, decidedByName, itemRemarks) => {
  // itemStatus can be a raw code ('APPROVED') or a human status_name
  // (CA/LQ/RMB header display: "Completed", "Payment Advised - For
  // Release", etc.) — only actual rejection should render as the red
  // "x" state, everything else in this read-only (already-decided)
  // view represents a successful outcome.
  const isRejected = String(itemStatus || '').toUpperCase().includes('REJECT');
  const isApp = !isRejected, statusClass = isApp ? 'kna-badge-approved' : 'kna-badge-rejected';
  const statusIcon = isApp ? 'fa-check-circle' : 'fa-times-circle', statusColor = isApp ? '#17663a' : '#e03131';
  return `<div class="kna-item-decision"><div class="kna-readonly-status"><i class="fas ${statusIcon}" style="color:${statusColor};font-size:14px;"></i><span class="kna-badge ${statusClass}">${itemStatus}</span></div><div class="kna-readonly-by">by ${escapeHtml(decidedByName || 'another approver')}</div>${itemRemarks ? `<div class="kna-readonly-remark">${escapeHtml(itemRemarks)}</div>` : ''}</div>`;
};

/* ─── ATTACHMENTS ─── */
const renderCaAttachment = att => {
  const fileName = att.file_name || '', originalName = att.original_name || fileName, viewUrl = att.view_url || '', downloadUrl = att.download_url || viewUrl;
  const fileSize = formatFileSize(att.file_size), uploadedDate = normalizeDate(att.uploaded_date).slice(0, 10);
  const isImage = isImageFile(fileName), iconClass = getFileIconClass(fileName), iconColor = getFileIconColor(fileName);
  if (isImage && viewUrl) {
    return `<div class="kna-attachment-item"><div class="kna-attachment-thumb-wrap" data-lightbox="${escapeHtml(viewUrl)}" title="Click to preview"><img class="kna-attachment-thumb-img" src="${escapeHtml(viewUrl)}" alt="${escapeHtml(originalName)}" loading="lazy" onerror="this.parentElement.innerHTML='<i class=\'fas fa-image\' style=\'color:${iconColor};font-size:18px;\'></i>'"></div><div class="kna-attachment-info"><div class="kna-attachment-name" title="${escapeHtml(originalName)}">${escapeHtml(originalName)}</div><div class="kna-attachment-meta">${fileSize}${uploadedDate ? ' \u2022 ' + uploadedDate : ''}</div></div><div class="kna-attachment-actions"><a href="${escapeHtml(downloadUrl)}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" title="Download"><i class="fas fa-download"></i></a></div></div>`;
  }
  return `<div class="kna-attachment-item"><div class="kna-attachment-icon" style="background:${iconColor}15;color:${iconColor};"><i class="fas ${iconClass}"></i></div><div class="kna-attachment-info"><div class="kna-attachment-name" title="${escapeHtml(originalName)}">${escapeHtml(originalName)}</div><div class="kna-attachment-meta">${fileSize}${uploadedDate ? ' \u2022 ' + uploadedDate : ''}</div></div><div class="kna-attachment-actions"><a href="${escapeHtml(viewUrl)}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" title="View"><i class="fas fa-eye"></i></a><a href="${escapeHtml(downloadUrl)}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" title="Download"><i class="fas fa-download"></i></a></div></div>`;
};

const renderCaAttachments = attachments => !attachments || !attachments.length ? '<span class="text-muted kna-small">No attachments</span>' : `<div class="kna-attachment-list">${attachments.map(renderCaAttachment).join('')}</div>`;

const renderAttachment = name => {
  const url = ATTACHMENTS_BASE + encodeURIComponent(name);
  return IMG_EXTS.test(name)
    ? `<span class="kna-thumb-wrap" data-lightbox="${escapeHtml(url)}"><img class="kna-thumb" src="${url}" alt="${escapeHtml(name)}" loading="lazy"><span class="kna-thumb-label">${escapeHtml(name)}</span></span>`
    : `<span class="kna-file-wrap"><i class="fas fa-file-alt" style="color:#5b2bd6;font-size:11px;"></i><a href="${url}" target="_blank" rel="noopener">${escapeHtml(name)}</a></span>`;
};

/* ─── PAYABLE TO HELPERS ─── */
let reviewPayableToOptions = [];

// Scoped to the REQUESTER's department (not the logged-in approver's) —
// an approver in Finance reviewing an ICT employee's CA needs ICT names.
const loadPayableToPickerOptions = (requesterUserId, callback) => {
  if (!requesterUserId) { reviewPayableToOptions = []; if (callback) callback(); return; }
  ajax_loader('transactions/approvals/api/get/payable-to-options', { UserId: requesterUserId }).done((response) => {
    const res = typeof response === 'string' ? $.parseJSON(response) : response;
    reviewPayableToOptions = (res.status === 'success' && Array.isArray(res.data)) ? res.data : [];
    if (callback) callback();
  }).fail(() => { reviewPayableToOptions = []; if (callback) callback(); });
};

const getPayableToOptions = (selectedUserId, fallbackDisplayText) => {
  const selected = normalizeDate(selectedUserId);
  let opts = reviewPayableToOptions.map(u => {
    const value = escapeHtml(String(u.id));
    const label = `${u.firstname} ${u.lastname}${u.designation ? ' (' + u.designation + ')' : ''}`;
    const isSelected = value === selected ? ' selected' : '';
    return `<option value="${value}"${isSelected}>${escapeHtml(label)}</option>`;
  }).join('');

  // Legacy free-typed value has no matching id — show the resolved
  // text as a disabled placeholder instead of leaving the field
  // blank; saving requires picking a fresh option from the list.
  if (!selected && fallbackDisplayText) {
    opts = `<option value="" selected disabled>${escapeHtml(fallbackDisplayText)} (re-select to change)</option>${opts}`;
  }

  return `<option value="">Select payable to</option>${opts}`;
};

/* ─── COST CENTER HELPERS ─── */
const getCostCenterOptions = (selectedId) => {
  const dataEl = document.getElementById('costCentersData');
  if (!dataEl) return `<option value="">No options available</option>`;
  try {
    const centers = JSON.parse(dataEl.value);
    if (!Array.isArray(centers) || centers.length === 0) {
      return `<option value="">No options available</option>`;
    }
    return centers.map(cc => {
      const value = escapeHtml(cc.cost_center_code || '');
      const text = escapeHtml((cc.cost_center_code ? cc.cost_center_code + ' - ' : '') + (cc.cost_center_name || ''));
      const selected = value === selectedId ? ' selected' : '';
      return `<option value="${value}"${selected}>${text}</option>`;
    }).join('');
  } catch (e) {
    return `<option value="">Error loading options</option>`;
  }
};

const getExpenseTypeOptions = selectedCode => {
  const dataEl = document.getElementById('expenseTypesData');
  if (!dataEl) return '<option value="">No expense types available</option>';

  const selected = normalizeDate(selectedCode);
  try {
    const types = JSON.parse(dataEl.value);
    if (!Array.isArray(types) || types.length === 0) {
      return '<option value="">No expense types available</option>';
    }

    let hasSelected = false;
    const options = types.map(et => {
      const code = normalizeDate(et.expense_code || '');
      const longText = normalizeDate(et.long_text || '');
      if (!code) return '';
      const label = `${code} - ${longText || code}`;
      const isSelected = selected !== '' && code === selected;
      if (isSelected) hasSelected = true;
      return `<option value="${escapeHtml(code)}"${isSelected ? ' selected' : ''}>${escapeHtml(label)}</option>`;
    }).filter(Boolean);

    if (selected && !hasSelected) {
      options.unshift(`<option value="${escapeHtml(selected)}" selected>${escapeHtml(selected)}</option>`);
    }

    options.unshift('<option value="">Select expense type</option>');
    return options.join('');
  } catch (e) {
    return '<option value="">Error loading expense types</option>';
  }
};

const initLiquidationCategorySelect2 = () => {
  if (!(window.jQuery && $.fn.select2)) return;
  $('.kna-edit-category').filter(function () {
    return $(this).is(':visible') && $(this).closest('.d-none').length === 0;
  }).each(function () {
    const $el = $(this);
    if ($el.hasClass('select2-hidden-accessible')) return;
    const $dropdownParent = $el.closest('.page-inner').length ? $el.closest('.page-inner') : $(document.body);
    $el.select2({
      placeholder: 'Select expense type',
      allowClear: false,
      width: '100%',
      dropdownParent: $dropdownParent
    });
  });
};

/* ─── HEADER EDITOR (Cost Center + Payable To + Address + IO) — CA & Reimbursement ─── */
const initCaHeaderEditor = (referenceNo, options = {}) => {
  const endpoint = options.endpoint || 'transactions/approvals/api/update/ca-header';
  const confirmTitle = options.confirmTitle || 'Update Cash Advance Details?';
  const confirmText = options.confirmText || 'This will overwrite the issuer\'s original entries for Cost Center, Payable To, and Address. Continue?';
  const successText = options.successText || 'Cash advance details have been updated successfully.';
  const btn = document.getElementById('btnUpdateCaHeader');
  const badge = document.getElementById('caHeaderChangeBadge');
  const ccSelect = document.getElementById('reviewCostCenter');
  const payableInput = document.getElementById('reviewPayableTo');
  const addressInput = document.getElementById('reviewAddress');
  const ioInput = document.getElementById('reviewIo');

  if (reviewState.isPastMode) {
    [ccSelect, payableInput, addressInput, ioInput].forEach(el => { if (el) el.setAttribute('disabled', 'disabled'); });
    if (ccSelect && window.jQuery && $.fn.select2) $(ccSelect).prop('disabled', true);
    if (payableInput && window.jQuery && $.fn.select2) $(payableInput).prop('disabled', true);
    if (btn) btn.classList.add('d-none');
    return;
  }

  if (!btn) return;

  const checkDirty = () => {
    let isDirty = false;
    [ccSelect, payableInput, addressInput, ioInput].forEach(el => {
      if (!el) return;
      const original = el.getAttribute('data-original') || '';
      if (el.value !== original) isDirty = true;
    });

    // Hide when clean, show when dirty
    btn.classList.toggle('d-none', !isDirty);

    if (badge) {
      badge.classList.toggle('is-visible', isDirty);
      badge.style.opacity = isDirty ? '1' : '0';
    }
    return isDirty;
  };

  if (ccSelect) {
    // Select2 is a jQuery plugin — use jQuery change for reliability
    if (window.jQuery && $.fn.select2) {
      $(ccSelect).on('change', checkDirty);
    } else {
      ccSelect.addEventListener('change', checkDirty);
    }
  }
  if (payableInput) {
    if (window.jQuery && $.fn.select2) {
      $(payableInput).on('change', checkDirty);
    } else {
      payableInput.addEventListener('change', checkDirty);
    }
  }
  if (addressInput) addressInput.addEventListener('input', checkDirty);
  if (ioInput) ioInput.addEventListener('input', checkDirty);

  // Start hidden since nothing is modified yet
  btn.classList.add('d-none');

  btn.addEventListener('click', () => {
    if (!checkDirty()) return;

    Swal.fire({
      icon: 'question',
      title: confirmTitle,
      text: confirmText,
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#5b2bd6',
      cancelButtonColor: '#6b7280'
    }).then(result => {
      if (!result.isConfirmed) return;

      const originalHtml = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';

      const payload = {
        reference_no: referenceNo,
        cost_center_id: ccSelect ? ccSelect.value : '',
        address: addressInput ? addressInput.value.trim() : '',
        io: ioInput ? ioInput.value.trim() : ''
      };
      if (payableInput) payload.payable_to = payableInput.value.trim();

      $.ajax({
        url: base_url + endpoint,
        type: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify(payload),
        success: function (res) {
          if (res.status === 'success') {
            if (ccSelect) ccSelect.setAttribute('data-original', ccSelect.value);
            if (payableInput) payableInput.setAttribute('data-original', payableInput.value);
            if (addressInput) addressInput.setAttribute('data-original', addressInput.value);
            if (ioInput) ioInput.setAttribute('data-original', ioInput.value);

            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Saved';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            if (badge) {
              badge.textContent = 'Saved';
              badge.classList.add('is-visible');
              badge.style.opacity = '1';
            }

            setTimeout(() => {
              btn.innerHTML = originalHtml;
              btn.classList.remove('btn-success');
              btn.classList.add('btn-primary');
              checkDirty(); // hides button since values now match originals
              if (badge) {
                badge.textContent = 'Modified';
                badge.classList.remove('is-visible');
                badge.style.opacity = '0';
              }
            }, 2000);

            Swal.fire({
              icon: 'success',
              title: 'Updated',
              text: successText,
              timer: 2000,
              showConfirmButton: false,
              toast: true,
              position: 'top-end'
            });
          } else {
            throw new Error(res.response || 'Update failed.');
          }
        },
        error: function (xhr) {
          let msg = 'Failed to update details. Please try again.';
          try { const resp = JSON.parse(xhr.responseText); if (resp.response) msg = resp.response; } catch (e) { }
          btn.innerHTML = originalHtml;
          checkDirty(); // restores visibility based on current dirty state
          if (badge) {
            badge.textContent = 'Error';
            badge.classList.add('is-error');
            badge.classList.add('is-visible');
            badge.style.opacity = '1';
          }
          Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonColor: '#e03131' });
        }
      });
    });
  });
};

/* ─── RENDER: CASH ADVANCE ─── */
const renderCashAdvance = (data, attachments = []) => {
  const h = data[0], currentUserId = Number(domReview.currentUserId?.value || 0);
  reviewState.header = h; reviewState.items = []; reviewState.totalItems = 1; reviewState.decisions = {};
  if (domReview.reviewTitle) domReview.reviewTitle.textContent = 'Review Cash Advance';
  if (domReview.reviewStatusBadge) domReview.reviewStatusBadge.innerHTML = getStatusBadge(h.status_name);

  const approvalPdfUrl = resolveApprovalPdfUrl(h), hasApprovalPdf = Boolean(approvalPdfUrl), hasCaAttachments = attachments && attachments.length > 0;
  const currentCostCenterId = normalizeDate(h.cost_center_id || '');
  const currentIo = normalizeDate(h.io || h.io_number || h.io_no || '');
  const costCenterOptions = getCostCenterOptions(currentCostCenterId);

  const overviewHtml = `<div class="kna-overview-wrapper">
    <div class="kna-overview-compact">
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Reference No.</div>
        <div class="kna-overview-value">${escapeHtml(h.reference_no || '-')}</div>
      </div>
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Requester</div>
        <div class="kna-overview-value">${escapeHtml(h.user_name || '-')}</div>
      </div>
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Needed Date</div>
        <div class="kna-overview-value">${normalizeDate(h.needed_date).slice(0, 10) || '-'}</div>
      </div>
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Submission Date</div>
        <div class="kna-overview-value">${normalizeDate(h.created_date).slice(0, 10) || '-'}</div>
      </div>
    </div>
    <div class="kna-overview-fields">
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Requested Amount</div>
        <div class="kna-overview-value kna-amount">${formatPHP(h.amount || h.ca_amount || 0)}</div>
      </div>
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Approved Amount</div>
        <div class="kna-overview-value kna-amount-approved" id="caApprovedAmountDisplay">${formatPHP(h.approved_amount || h.amount || h.ca_amount || 0)}</div>
        ${(h.approved_amount && Number(h.approved_amount) !== Number(h.amount || h.ca_amount)) ? `<div class="kna-amount-words" id="caApprovedAmountWords">${escapeHtml(h.approved_amount_in_words || amountToWords(h.approved_amount))}</div>` : ''}
      </div>
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Payable To</div>
        <div class="kna-overview-value">
          <select class="kna-cost-center-select" id="reviewPayableTo" data-field="payable_to" data-original="${escapeHtml(normalizeDate(h.payable_to_user_id))}" style="min-width:140px;">
            ${getPayableToOptions(h.payable_to_user_id, h.payable_to)}
          </select>
        </div>
      </div>
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Cost Center</div>
        <div class="kna-overview-value">
          <select class="kna-cost-center-select" id="reviewCostCenter" data-field="cost_center_id" data-original="${escapeHtml(currentCostCenterId)}" style="min-width:140px;">
            ${costCenterOptions}
          </select>
        </div>
      </div>
    </div>
    <div class="kna-overview-fields">
      <div class="kna-overview-cell">
        <div class="kna-overview-label">Address</div>
        <div class="kna-overview-value">
          <input type="text" class="form-control form-control-sm kna-ca-field-input" id="reviewAddress" data-field="address" data-original="${escapeHtml(h.address || '')}" value="${escapeHtml(h.address || '')}" placeholder="Enter address..." style="min-width:140px;">
        </div>
      </div>
      <div class="kna-overview-cell">
        <div class="kna-overview-label">IO</div>
        <div class="kna-overview-value">
          <input type="text" class="form-control form-control-sm kna-ca-field-input" id="reviewIo" data-field="io" data-original="${escapeHtml(currentIo)}" value="${escapeHtml(currentIo)}" placeholder="Enter IO..." style="min-width:140px;">
        </div>
      </div>
      <div class="kna-overview-cell full" style="grid-column: span 2;">
        <div class="kna-overview-label">Purpose / Description</div>
        <div class="kna-overview-value kna-purpose">${escapeHtml(h.description || '-')}</div>
      </div>
    </div>
    <div class="kna-ca-update-bar">
      <button type="button" class="btn btn-primary btn-sm kna-small font-weight-bold" id="btnUpdateCaHeader"  style="min-width:110px;">
        <i class="fas fa-save mr-1"></i> Update
      </button>
    </div>
  </div>
  ${hasApprovalPdf ? `<div class="kna-doc-bar" data-toggle-doc-viewer data-target="ca-doc-viewer" aria-expanded="false">
    <div class="kna-doc-bar-left"><i class="fas fa-file-pdf"></i> Cash Advance Document</div>
    <div class="kna-doc-bar-right"><i class="fas fa-eye"></i> <span>View</span></div>
  </div>
  <div id="ca-doc-viewer" class="kna-doc-viewer-wrap d-none">
    <iframe class="kna-doc-viewer-frame" src="about:blank" data-src="${escapeHtml(approvalPdfUrl)}" title="Cash Advance Document Viewer"></iframe>
  </div>` : ''}
  ${hasCaAttachments ? `<div class="kna-attach-strip">
    <div class="kna-attach-strip-label"><i class="fas fa-paperclip"></i> Attachments</div>
    <div class="kna-attach-strip-list">
      ${attachments.map(att => {
    const isImg = isImageFile(att.file_name);
    const iconClass = isImg ? 'img' : (att.file_name.toLowerCase().endsWith('.pdf') ? 'pdf' : 'doc');
    const icon = isImg ? 'fa-image' : (att.file_name.toLowerCase().endsWith('.pdf') ? 'fa-file-pdf' : 'fa-file');
    return `<a href="${escapeHtml(att.view_url || '#')}" class="kna-attach-strip-item ${iconClass}" target="_blank" rel="noopener" title="${escapeHtml(att.original_name || att.file_name)}">
          <i class="fas ${icon}"></i> ${escapeHtml(att.original_name || att.file_name)}
        </a>`;
  }).join('')}
    </div>
  </div>` : ''}`;

  if (domReview.reviewHeaderFields) domReview.reviewHeaderFields.innerHTML = overviewHtml;

  // Initialize Select2 for cost center
  const ccSelect = document.getElementById('reviewCostCenter');
  if (ccSelect && window.jQuery && $.fn.select2) {
    $(ccSelect).select2({
      placeholder: 'Select Cost Center',
      allowClear: false,
      width: 'style'
    });
  }

  // Initialize Select2 for payable to
  const payableToSelect = document.getElementById('reviewPayableTo');
  if (payableToSelect && window.jQuery && $.fn.select2) {
    $(payableToSelect).select2({
      placeholder: 'Select Payable To',
      allowClear: false,
      width: 'style'
    });
  }

  // Bind header field editor
  initCaHeaderEditor(h.reference_no);

  // Bind document bar toggle
  const docBar = document.querySelector('.kna-doc-bar[data-toggle-doc-viewer]');
  if (docBar) {
    docBar.addEventListener('click', () => {
      const panel = document.getElementById(docBar.getAttribute('data-target'));
      if (!panel) return;
      const iframe = panel.querySelector('iframe[data-src]');
      const isHidden = panel.classList.contains('d-none');
      const label = docBar.querySelector('.kna-doc-bar-right span');
      const icon = docBar.querySelector('.kna-doc-bar-right i');
      if (isHidden) {
        if (iframe && iframe.getAttribute('src') === 'about:blank') {
          const s = iframe.getAttribute('data-src') || '';
          if (s) iframe.setAttribute('src', s);
        }
        panel.classList.remove('d-none');
        docBar.setAttribute('aria-expanded', 'true');
        docBar.classList.add('is-active');
        if (label) label.textContent = 'Hide';
        if (icon) icon.className = 'fas fa-eye-slash';
      } else {
        panel.classList.add('d-none');
        docBar.setAttribute('aria-expanded', 'false');
        docBar.classList.remove('is-active');
        if (label) label.textContent = 'View';
        if (icon) icon.className = 'fas fa-eye';
      }
    });
  }

  const caKey = 'ca_' + h.id, caAmount = Number(h.ca_amount) || 0;
  const caDescription = normalizeDate(h.description || '');
  const originalAmount = Number(h.amount || h.ca_amount || 0);
  const approvedAmount = Number(h.approved_amount || 0);
  const displayAmount = approvedAmount > 0 ? approvedAmount : originalAmount;
  const caIsReadOnly = reviewState.isPastMode;
  const caDisabledAttr = caIsReadOnly ? 'disabled' : '';

  reviewState.decisions[caKey] = {
    decision: null,
    remark: '',
    amount: displayAmount,
    originalAmount: originalAmount,
    approvedAmount: approvedAmount,
    actualAmount: displayAmount,
    description: caDescription,
    detail_id: null,
    approval_per_item_id: null,
    item_status: 'PENDING',
    isOwnDecision: true,
    isReadOnly: caIsReadOnly
  };

  const caActionCellHtml = caIsReadOnly
    ? renderReadOnlyAction(headerDecisionLabel(h.status_name), h.decided_by_name || '', h.rejection_reason || '')
    : `<div class="kna-item-decision"><div class="kna-toggle-group"><button type="button" class="kna-toggle-btn is-approve" data-decision="approve" data-key="${caKey}" title="Approve"><i class="fas fa-check"></i></button><button type="button" class="kna-toggle-btn is-reject" data-decision="reject" data-key="${caKey}" title="Reject"><i class="fas fa-times"></i></button></div><textarea class="kna-item-remark d-none" data-key="${caKey}" placeholder="Remarks are required for rejection..."></textarea><button type="button" class="kna-cancel-reject d-none" data-cancel-reject data-key="${caKey}" title="Cancel rejection"><i class="fas fa-undo"></i> Cancel</button></div>`;

  const desktopHtml = `<div class="kna-review-desktop kna-review-desktop-ca"><div class="kna-review-table-shell">
    <div class="kna-review-table-wrap-main"><table class="table table-sm kna-review-table-main"><thead><tr><th>Description</th><th class="text-right">Amount</th></tr></thead><tbody><tr data-item-key="${caKey}"><td><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-ca-description" data-key="${caKey}" value="${escapeHtml(caDescription)}" placeholder="Description" ${caDisabledAttr}></td><td>
  <div class="kna-amount-input-wrap">
    ${approvedAmount > 0 && approvedAmount !== originalAmount ? `<div class="kna-original-amount-strike">${formatPHP(originalAmount)}</div>` : ''}
    <input type="number" step="0.01" min="0" class="form-control form-control-sm kna-inline-edit-input kna-edit-ca-amount text-right" data-key="${caKey}" value="${escapeHtml(displayAmount)}" placeholder="0.00" ${caDisabledAttr}>
  </div>
</td>
</tr></tbody></table></div>
    <div class="kna-review-table-wrap-action"><table class="table table-sm kna-review-table-action"><thead><tr><th>Action</th></tr></thead><tbody><tr data-item-key="${caKey}"><td class="kna-col-action">${caActionCellHtml}</td></tr></tbody></table></div>
  </div><div class="kna-review-footer"><div class="kna-review-footer-main"><span class="kna-review-footer-label">Total Requested</span><div class="kna-review-footer-amount kna-amount-main">${formatPHP(h.ca_amount || 0)}</div></div></div></div>`;

  const caMobileDecisionHtml = caIsReadOnly
    ? `<div class="mt-2">${renderReadOnlyAction(headerDecisionLabel(h.status_name), h.decided_by_name || '', h.rejection_reason || '')}</div>`
    : `<div class="kna-item-decision"><div class="kna-toggle-group"><button type="button" class="kna-toggle-btn is-approve" data-decision="approve" data-key="${caKey}"><i class="fas fa-check"></i> Approve</button><button type="button" class="kna-toggle-btn is-reject" data-decision="reject" data-key="${caKey}"><i class="fas fa-times"></i> Reject</button></div><textarea class="kna-item-remark d-none" data-key="${caKey}" placeholder="Remarks are required for rejection..."></textarea><button type="button" class="kna-cancel-reject d-none" data-cancel-reject data-key="${caKey}"><i class="fas fa-undo"></i> Cancel</button></div>`;

  const mobileHtml = `<div class="kna-exp-mobile"><div class="kna-exp-card" data-item-key="${caKey}"><div class="kna-exp-card-head"><div><div class="kna-exp-card-title">${escapeHtml(h.description || 'Cash Advance')}</div><div class="kna-exp-card-meta">Ref: ${escapeHtml(h.reference_no || '-')}</div></div><div class="kna-exp-card-amount"><div class="kna-amount-main">${formatPHP(displayAmount)}</div>${approvedAmount > 0 && approvedAmount !== originalAmount ? `<div class="kna-amount-breakdown"><s>${formatPHP(originalAmount)}</s> original</div>` : ''}</div></div><div class="kna-exp-card-grid"><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Description</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-ca-description" data-key="${caKey}" value="${escapeHtml(caDescription)}" placeholder="Description" ${caDisabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Amount</span><span class="kna-exp-card-value"><input type="number" step="0.01" min="0" class="form-control form-control-sm kna-inline-edit-input kna-edit-ca-amount text-right" data-key="${caKey}" value="${escapeHtml(displayAmount)}" placeholder="0.00" ${caDisabledAttr}></span></div></div>${caMobileDecisionHtml}</div></div>`;

  if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = desktopHtml + mobileHtml;
  requestAnimationFrame(() => requestAnimationFrame(syncRowHeights));
};

const renderReplenishment = (data) => {
  const h = data[0];
  reviewState.header = h; reviewState.items = data; reviewState.totalItems = 1; reviewState.decisions = {};
  if (domReview.reviewTitle) domReview.reviewTitle.textContent = 'Review Replenishment';
  if (domReview.reviewStatusBadge) domReview.reviewStatusBadge.innerHTML = getStatusBadge(h.status_name);

  const total = data.reduce((sum, item) => sum + (Number(item.actual_amount) || 0), 0);
  const overviewHtml = `<div class="kna-overview-wrapper">
    <div class="kna-overview-compact">
      <div class="kna-overview-cell"><div class="kna-overview-label">Reference No.</div><div class="kna-overview-value">${escapeHtml(h.reference_no || '-')}</div></div>
      <div class="kna-overview-cell"><div class="kna-overview-label">Requester</div><div class="kna-overview-value">${escapeHtml(h.user_name || '-')}</div></div>
      <div class="kna-overview-cell"><div class="kna-overview-label">Revolving Fund</div><div class="kna-overview-value">${escapeHtml(h.cost_center_name || '-')}</div></div>
      <div class="kna-overview-cell"><div class="kna-overview-label">Submission Date</div><div class="kna-overview-value">${normalizeDate(h.created_date).slice(0, 10) || '-'}</div></div>
    </div>
    <div class="kna-overview-fields">
      <div class="kna-overview-cell"><div class="kna-overview-label">Claimed Reimbursements</div><div class="kna-overview-value">${data.length}</div></div>
      <div class="kna-overview-cell"><div class="kna-overview-label">Total Amount</div><div class="kna-overview-value kna-amount">${formatPHP(total)}</div></div>
    </div>
  </div>`;
  if (domReview.reviewHeaderFields) domReview.reviewHeaderFields.innerHTML = overviewHtml;

  const key = 'rpl_' + h.id;
  const isReadOnly = reviewState.isPastMode;
  reviewState.decisions[key] = {
    decision: null, remark: '', amount: total, originalAmount: total, approvedAmount: total, actualAmount: total,
    description: '', detail_id: null, approval_per_item_id: null, item_status: 'PENDING', isOwnDecision: true, isReadOnly
  };

  const actionHtml = (withLabels) => isReadOnly
    ? renderReadOnlyAction(headerDecisionLabel(h.status_name), h.decided_by_name || '', h.rejection_reason || '')
    : `<div class="kna-item-decision"><div class="kna-toggle-group"><button type="button" class="kna-toggle-btn is-approve" data-decision="approve" data-key="${key}" title="Approve"><i class="fas fa-check"></i>${withLabels ? ' Approve' : ''}</button><button type="button" class="kna-toggle-btn is-reject" data-decision="reject" data-key="${key}" title="Reject"><i class="fas fa-times"></i>${withLabels ? ' Reject' : ''}</button></div><textarea class="kna-item-remark d-none" data-key="${key}" placeholder="Remarks are required for rejection..."></textarea><button type="button" class="kna-cancel-reject d-none" data-cancel-reject data-key="${key}" title="Cancel rejection"><i class="fas fa-undo"></i> Cancel</button></div>`;

  const rows = data.map(item => `<tr><td>${escapeHtml(item.liquidation_id || '-')}</td><td>${escapeHtml(item.description || '-')}</td><td class="text-right">${formatPHP(item.actual_amount || 0)}</td></tr>`).join('');
  const desktopHtml = `<div class="kna-review-desktop kna-review-desktop-ca"><div class="kna-review-table-shell">
    <div class="kna-review-table-wrap-main"><table class="table table-sm kna-review-table-main"><thead><tr><th>Reimbursement No.</th><th>Description</th><th class="text-right">Amount</th></tr></thead><tbody>${rows}</tbody></table></div>
    <div class="kna-review-table-wrap-action"><table class="table table-sm kna-review-table-action"><thead><tr><th>Action</th></tr></thead><tbody><tr data-item-key="${key}"><td class="kna-col-action">${actionHtml(false)}</td></tr></tbody></table></div>
  </div><div class="kna-review-footer"><div class="kna-review-footer-main"><span class="kna-review-footer-label">Total Replenishment</span><div class="kna-review-footer-amount kna-amount-main">${formatPHP(total)}</div></div></div></div>`;

  const cards = data.map(item => `<div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">${escapeHtml(item.liquidation_id || '-')}</span><span class="kna-exp-card-value">${escapeHtml(item.description || '-')} &middot; ${formatPHP(item.actual_amount || 0)}</span></div>`).join('');
  const mobileHtml = `<div class="kna-exp-mobile"><div class="kna-exp-card" data-item-key="${key}"><div class="kna-exp-card-head"><div><div class="kna-exp-card-title">Replenishment</div><div class="kna-exp-card-meta">Ref: ${escapeHtml(h.reference_no || '-')}</div></div><div class="kna-exp-card-amount"><div class="kna-amount-main">${formatPHP(total)}</div></div></div><div class="kna-exp-card-grid">${cards}</div>${isReadOnly ? `<div class="mt-2">${actionHtml(true)}</div>` : actionHtml(true)}</div></div>`;

  if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = desktopHtml + mobileHtml;
  requestAnimationFrame(() => requestAnimationFrame(syncRowHeights));
};

/* ─── RENDER: LIQUIDATION ─── */
const renderLiquidation = (data, attachments = []) => {
  const currentUserId = Number(domReview.currentUserId?.value || 0);
  reviewState.header = data[0]; reviewState.items = data; reviewState.totalItems = data.length; reviewState.decisions = {};
  if (domReview.reviewTitle) domReview.reviewTitle.textContent = 'Review Liquidation';
  if (domReview.reviewStatusBadge) domReview.reviewStatusBadge.innerHTML = reviewState.isPastMode ? getPastOverallStatusBadge(data) : getStatusBadge('Pending Approval');

  const first = data[0], requesterName = first.user_name || '-', cashAdvanceAmount = Number(first.ca_amount) || 0;
  const totalLiquidated = data.reduce((s, item) => s + (Number(item.gross_amount ?? item.lq_amount) || 0), 0), varianceAmount = Number(first.variance) || 0;
  const hasCaAttachments = attachments && attachments.length > 0;
  const approvalPdfUrl = resolveApprovalPdfUrl(first), hasApprovalPdf = Boolean(approvalPdfUrl);

  const liquidationIo = normalizeDate(first.io || first.io_number || first.io_no || '');
  const overviewHtml = `<div class="kna-overview-compact">
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Requester</div>
      <div class="kna-overview-value">${escapeHtml(requesterName)}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Liquidation ID</div>
      <div class="kna-overview-value">${escapeHtml(first.liquidation_id || '-')}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">CA Reference</div>
      <div class="kna-overview-value">${escapeHtml(first.cash_advance_id || '-')}</div>
    </div>
  </div>
  <div class="kna-overview-fields">
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Total Items</div>
      <div class="kna-overview-value">${data.length}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">CA Amount</div>
      <div class="kna-overview-value kna-amount">${formatPHP(cashAdvanceAmount)}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Total Liquidated</div>
      <div class="kna-overview-value kna-amount">${formatPHP(totalLiquidated)}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Variance</div>
      <div class="kna-overview-value kna-amount">${formatPHP(varianceAmount)}</div>
    </div>
  </div>
  <div class="kna-overview-fields">
   <div class="kna-overview-cell">
      <div class="kna-overview-label">Payable To</div>
      <div class="kna-overview-value">${escapeHtml(first.payable_to || first.vendor_name || '-')}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Cost Center</div>
      <div class="kna-overview-value">${escapeHtml(first.cost_center_id)} - ${escapeHtml(first.cost_center_name || first.cost_center || '-')}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Address</div>
      <div class="kna-overview-value kna-address">${escapeHtml(first.address || '-')}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">IO</div>
      <div class="kna-overview-value">${escapeHtml(liquidationIo || '-')}</div>
    </div>
  </div>
  ${hasApprovalPdf ? `<div class="kna-doc-bar" data-toggle-doc-viewer data-target="ca-doc-viewer" aria-expanded="false">
    <div class="kna-doc-bar-left"><i class="fas fa-file-pdf"></i> Cash Advance Document</div>
    <div class="kna-doc-bar-right"><i class="fas fa-eye"></i> <span>View</span></div>
  </div>
  <div id="ca-doc-viewer" class="kna-doc-viewer-wrap d-none">
    <iframe class="kna-doc-viewer-frame" src="about:blank" data-src="${escapeHtml(approvalPdfUrl)}" title="Cash Advance Document Viewer"></iframe>
  </div>` : ''}
    ${hasCaAttachments ? `<div class="kna-attach-strip">
    <div class="kna-attach-strip-label"><i class="fas fa-paperclip"></i> Attachments</div>
    <div class="kna-attach-strip-list">
      ${attachments.map(att => {
    const isImg = isImageFile(att.file_name);
    const iconClass = isImg ? 'img' : (att.file_name.toLowerCase().endsWith('.pdf') ? 'pdf' : 'doc');
    const icon = isImg ? 'fa-image' : (att.file_name.toLowerCase().endsWith('.pdf') ? 'fa-file-pdf' : 'fa-file');
    return `<a href="${escapeHtml(att.view_url || '#')}" class="kna-attach-strip-item ${iconClass}" target="_blank" rel="noopener" title="${escapeHtml(att.original_name || att.file_name)}">
          <i class="fas ${icon}"></i> ${escapeHtml(att.original_name || att.file_name)}
        </a>`;
  }).join('')}
    </div>
  </div>` : ''}`;


  if (domReview.reviewHeaderFields) domReview.reviewHeaderFields.innerHTML = overviewHtml;

  // Bind document bar toggle for liquidation
  const docBar = document.querySelector('.kna-doc-bar[data-toggle-doc-viewer]');
  if (docBar) {
    docBar.addEventListener('click', () => {
      const panel = document.getElementById(docBar.getAttribute('data-target'));
      if (!panel) return;
      const iframe = panel.querySelector('iframe[data-src]');
      const isHidden = panel.classList.contains('d-none');
      const label = docBar.querySelector('.kna-doc-bar-right span');
      const icon = docBar.querySelector('.kna-doc-bar-right i');
      if (isHidden) {
        if (iframe && iframe.getAttribute('src') === 'about:blank') {
          const s = iframe.getAttribute('data-src') || '';
          if (s) iframe.setAttribute('src', s);
        }
        panel.classList.remove('d-none');
        docBar.setAttribute('aria-expanded', 'true');
        docBar.classList.add('is-active');
        if (label) label.textContent = 'Hide';
        if (icon) icon.className = 'fas fa-eye-slash';
      } else {
        panel.classList.add('d-none');
        docBar.setAttribute('aria-expanded', 'false');
        docBar.classList.remove('is-active');
        if (label) label.textContent = 'View';
        if (icon) icon.className = 'fas fa-eye';
      }
    });
  }

  let mainRows = '', actionRows = '', mobileCards = '';

  data.forEach((item, idx) => {
    const key = item.liquidation_id + '_' + idx;
       const originalAmount = Number(item.gross_amount ?? item.lq_amount) || 0;
    const approvedGross = Number(item.approved_gross_amount || item.approved_amount || 0);
    const displayAmount = approvedGross > 0 ? approvedGross : originalAmount;
    const originalIsVatable = Boolean(Number(item.is_vatable));
    const vatCalc = calculateVat(displayAmount, originalIsVatable);
    const detailId = item.id || 0;
    const approvalPerItemId = item.approval_per_item_id || 0;
    const itemStatus = item.item_status || 'PENDING';
    const decidedBy = item.item_decided_by || null;
    const decidedByName = item.decided_by_name || 'another approver';
    const isMyItem = Number(item.is_my_item || 0) === 1;
    const isOwnDecision = decidedBy ? Number(decidedBy) === currentUserId : false;
    const isAlreadyDecided = itemStatus === 'APPROVED' || itemStatus === 'REJECTED';
    const isReadOnly = reviewState.isPastMode || (isAlreadyDecided && !isOwnDecision) || (!isMyItem && isAlreadyDecided);

    const initialDescription = normalizeDate(item.description || '');
    const initialInvoice = normalizeDate(item.invoice_receipt_no || '');
    const initialDocDate = normalizeDate(item.document_date || '').slice(0, 10);
    const initialCategory = normalizeDate(item.expense_category || '');
    const categoryOptionsHtml = getExpenseTypeOptions(initialCategory);
    const initialVendorName = normalizeDate(item.vendor_name || '');
    const initialVendorAddress = normalizeDate(item.vendor_address || '');
    const initialVendorTin = normalizeDate(item.vendor_tin || '');

    reviewState.decisions[key] = {
      decision: null,
      remark: '',
      amount: displayAmount,
      isVatable: originalIsVatable,
      originalIsVatable,
      netAmount: vatCalc.netAmount,
      vatAmount: vatCalc.vatAmount,
      originalAmount: originalAmount,
      actualAmount: originalAmount,
      approvedAmount: displayAmount,
      liquidatedAmount: displayAmount,
      detail_id: detailId,
      approval_per_item_id: approvalPerItemId,
      item_status: itemStatus,
      isOwnDecision,
      isReadOnly,
      isMyItem,
      decidedBy,
      decidedByName,
      description: initialDescription,
      invoiceReceiptNo: initialInvoice,
      documentDate: initialDocDate,
      expenseCategory: initialCategory,
      vendorName: initialVendorName,
      vendorAddress: initialVendorAddress,
      vendorTin: initialVendorTin
    };

    const rowClass = itemStatus === 'APPROVED' ? 'is-approved' : (itemStatus === 'REJECTED' ? 'is-rejected' : '');
    const preselectedApprove = itemStatus === 'APPROVED' ? 'is-active' : '';
    const preselectedReject = itemStatus === 'REJECTED' ? 'is-active' : '';
    const remarkVisible = itemStatus === 'REJECTED' ? '' : 'd-none';
    const disabledAttr = isReadOnly ? 'disabled' : '';
    const readonlyAttr = isReadOnly ? 'readonly' : '';

    const hasAttachment = Boolean(item.attachment);
    const attachHtml = hasAttachment ? renderAttachment(item.attachment) : '<span class="text-muted kna-small">—</span>';
    const attachNames = (item.attachment || '').split(',').map(s => s.trim()).filter(Boolean);

    const descriptionInputHtml = `<input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-description" data-key="${key}" value="${escapeHtml(initialDescription)}" placeholder="Description" ${disabledAttr}>`;
    const categoryInputHtml = `<select class="form-control form-control-sm kna-inline-edit-input kna-edit-category" data-key="${key}" ${disabledAttr}>${categoryOptionsHtml}</select>`;
    const invoiceInputHtml = `<input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-invoice" data-key="${key}" value="${escapeHtml(initialInvoice)}" placeholder="Invoice/Receipt" ${disabledAttr}>`;
    const docDateInputHtml = `<input type="date" class="form-control form-control-sm kna-inline-edit-input kna-edit-docdate" data-key="${key}" value="${escapeHtml(initialDocDate)}" ${disabledAttr}>`;
    const grossInputHtml = `
      <div class="kna-amount-input-wrap">
        ${approvedGross > 0 && approvedGross !== originalAmount ? `<div class="kna-original-amount-strike">${formatPHP(originalAmount)}</div>` : ''}
        <input type="number" step="0.01" min="0" class="form-control form-control-sm kna-inline-edit-input kna-edit-gross text-right" data-key="${key}" value="${escapeHtml(displayAmount)}" ${disabledAttr}>
      </div>`;
    const vendorInputHtml = `<div class="kna-vendor-edit-wrap"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-name" data-key="${key}" value="${escapeHtml(initialVendorName)}" placeholder="Vendor name" ${disabledAttr}><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-address" data-key="${key}" value="${escapeHtml(initialVendorAddress)}" placeholder="Vendor address" ${disabledAttr}><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-tin" data-key="${key}" value="${escapeHtml(initialVendorTin)}" placeholder="TIN" ${disabledAttr}></div>`;

    mainRows += `<tr data-item-key="${key}" class="${rowClass}"><td class="text-center kna-rownum">${idx + 1}</td><td class="kna-col-description">${descriptionInputHtml}</td><td class="kna-col-category">${categoryInputHtml}</td><td>${attachHtml}</td><td>${invoiceInputHtml}</td><td>${docDateInputHtml}</td><td class="text-right kna-amount-main">${grossInputHtml}</td><td class="text-center kna-vat-cell"><label class="kna-vat-indicator"><input type="checkbox" class="kna-vat-check kna-vat-approver" data-key="${key}" ${originalIsVatable ? 'checked' : ''} ${disabledAttr}></label></td><td class="text-right kna-net-cell">${formatPHP(vatCalc.netAmount)}</td><td class="text-right kna-vat-amt-cell">${formatPHP(vatCalc.vatAmount)}</td><td class="kna-col-vendor">${vendorInputHtml}</td></tr>`;

    const actionCellHtml = isReadOnly
      ? renderReadOnlyAction(itemStatus, decidedByName, item.item_remarks)
      : `<div class="kna-item-decision"><div class="kna-toggle-group"><button type="button" class="kna-toggle-btn is-approve ${preselectedApprove}" data-decision="approve" data-key="${key}" ${disabledAttr}><i class="fas fa-check"></i></button><button type="button" class="kna-toggle-btn is-reject ${preselectedReject}" data-decision="reject" data-key="${key}" ${disabledAttr}><i class="fas fa-times"></i></button></div><textarea class="kna-item-remark ${remarkVisible}" data-key="${key}" ${readonlyAttr} placeholder="Remarks are required for rejection...">${escapeHtml(item.item_remarks || '')}</textarea><button type="button" class="kna-cancel-reject ${remarkVisible === '' ? '' : 'd-none'}" data-cancel-reject data-key="${key}" title="Cancel rejection"><i class="fas fa-undo"></i> Cancel</button></div>`;
    actionRows += `<tr data-item-key="${key}" class="${rowClass}"><td class="kna-col-action">${actionCellHtml}</td></tr>`;

    const mobileDecisionHtml = isReadOnly
      ? `<div class="kna-item-decision mt-2"><div class="kna-readonly-status"><i class="fas ${itemStatus === 'APPROVED' ? 'fa-check-circle' : 'fa-times-circle'}" style="color:${itemStatus === 'APPROVED' ? '#17663a' : '#e03131'};font-size:14px;"></i><span class="kna-badge ${itemStatus === 'APPROVED' ? 'kna-badge-approved' : 'kna-badge-rejected'}">${itemStatus}</span></div><div class="kna-readonly-by">by ${escapeHtml(decidedByName)}</div>${item.item_remarks ? `<div class="kna-readonly-remark">${escapeHtml(item.item_remarks)}</div>` : ''}</div>`
      : `<div class="kna-item-decision mt-2"><div class="kna-toggle-group"><button type="button" class="kna-toggle-btn is-approve ${preselectedApprove}" data-decision="approve" data-key="${key}" ${disabledAttr}><i class="fas fa-check"></i> Approve</button><button type="button" class="kna-toggle-btn is-reject ${preselectedReject}" data-decision="reject" data-key="${key}" ${disabledAttr}><i class="fas fa-times"></i> Reject</button></div><textarea class="kna-item-remark ${remarkVisible}" data-key="${key}" ${readonlyAttr} placeholder="Remarks are required for rejection...">${escapeHtml(item.item_remarks || '')}</textarea><button type="button" class="kna-cancel-reject ${remarkVisible === '' ? '' : 'd-none'}" data-cancel-reject data-key="${key}"><i class="fas fa-undo"></i> Cancel</button></div>`;

    mobileCards += `<div class="kna-exp-card ${rowClass}" data-item-key="${key}"><div class="kna-exp-card-head"><div><div class="kna-exp-card-title">${escapeHtml(initialDescription || '-')}</div><div class="kna-exp-card-sub">${escapeHtml(item.category_name || '-')}</div><div class="kna-exp-card-meta">Inv#: ${escapeHtml(initialInvoice || '-')} \u2022 ${escapeHtml(initialDocDate || '—')}</div></div><div class="kna-exp-card-amount"><div class="kna-amount-main">${formatPHP(displayAmount)}</div></div></div><div class="kna-exp-card-grid"><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Description</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-description" data-key="${key}" value="${escapeHtml(initialDescription)}" placeholder="Description" ${disabledAttr}></span></div><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Expense Type</span><span class="kna-exp-card-value"><select class="form-control form-control-sm kna-inline-edit-input kna-edit-category" data-key="${key}" ${disabledAttr}>${categoryOptionsHtml}</select></span></div><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Attachment</span><span class="kna-exp-card-value">${attachNames.length ? attachNames.map(renderAttachment).join('') : '<span class="text-muted">—</span>'}</span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Invoice/Receipt</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-invoice" data-key="${key}" value="${escapeHtml(initialInvoice)}" placeholder="Invoice/Receipt" ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Doc Date</span><span class="kna-exp-card-value"><input type="date" class="form-control form-control-sm kna-inline-edit-input kna-edit-docdate" data-key="${key}" value="${escapeHtml(initialDocDate)}" ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Gross</span><span class="kna-exp-card-value"><input type="number" step="0.01" min="0" class="form-control form-control-sm kna-inline-edit-input kna-edit-gross" data-key="${key}" value="${escapeHtml(displayAmount)}" ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">VAT</span><span class="kna-exp-card-value"><input type="checkbox" class="kna-vat-check kna-vat-approver" data-key="${key}" ${originalIsVatable ? 'checked' : ''} ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Net / VAT</span><span class="kna-exp-card-value"><span class="kna-net-value">${formatPHP(vatCalc.netAmount)}</span> / <span class="kna-vat-value">${formatPHP(vatCalc.vatAmount)}</span></span></div><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Vendor Name</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-name" data-key="${key}" value="${escapeHtml(initialVendorName)}" placeholder="Vendor name" ${disabledAttr}></span></div><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Vendor Address</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-address" data-key="${key}" value="${escapeHtml(initialVendorAddress)}" placeholder="Vendor address" ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Vendor TIN</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-tin" data-key="${key}" value="${escapeHtml(initialVendorTin)}" placeholder="TIN" ${disabledAttr}></span></div></div>${mobileDecisionHtml}</div>`;

    if (itemStatus === 'APPROVED') {
      reviewState.decisions[key].decision = 'approve';
    } else if (itemStatus === 'REJECTED') {
      reviewState.decisions[key].decision = 'reject';
      reviewState.decisions[key].remark = item.item_remarks || '';
    }
  });

  const desktopHtml = `<div class="kna-review-desktop kna-review-desktop-liquidation"><div class="kna-review-table-shell">
    <div class="kna-review-table-wrap-main"><table class="table table-sm kna-review-table-main"><thead><tr><th>#</th><th>Description</th><th>Expense Type</th><th>Attachment</th><th>Invoice/Receipt</th><th>Doc. Date</th><th class="text-right">Gross</th><th class="text-center">VAT</th><th class="text-right">Net</th><th class="text-right">VAT Amt</th><th>Vendor</th></tr></thead><tbody>${mainRows}</tbody></table></div>
    <div class="kna-review-table-wrap-action"><table class="table table-sm kna-review-table-action"><thead><tr><th>Action</th></tr></thead><tbody>${actionRows}</tbody></table></div>
  </div><div class="kna-review-footer"><div class="kna-review-footer-main"><span class="kna-review-footer-label">Total Liquidated Amount</span><div class="kna-review-footer-amount kna-amount-main">${formatPHP(totalLiquidated)}</div></div></div></div>`;

  const mobileHtml = `<div class="kna-exp-mobile">${mobileCards}</div>`;

  if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = desktopHtml + mobileHtml;
  initLiquidationCategorySelect2();
  requestAnimationFrame(() => requestAnimationFrame(syncRowHeights));
};

/* ─── RENDER: REIMBURSEMENT (mirrors LIQUIDATION, no cash-advance link) ─── */
const renderReimbursement = (data, attachments = []) => {
  const currentUserId = Number(domReview.currentUserId?.value || 0);
  reviewState.header = data[0]; reviewState.items = data; reviewState.totalItems = data.length; reviewState.decisions = {};
  if (domReview.reviewTitle) domReview.reviewTitle.textContent = 'Review Reimbursement';
  if (domReview.reviewStatusBadge) domReview.reviewStatusBadge.innerHTML = reviewState.isPastMode ? getPastOverallStatusBadge(data) : getStatusBadge('Pending Approval');

  const first = data[0], requesterName = first.user_name || '-';
  const totalRequested = data.reduce((s, item) => s + (Number(item.actual_amount ?? item.lq_amount) || 0), 0);
  const totalApproved = data.reduce((s, item) => s + (Number(item.approved_amount) || 0), 0);
  const hasCaAttachments = attachments && attachments.length > 0;
  const approvalPdfUrl = resolveApprovalPdfUrl(first), hasApprovalPdf = Boolean(approvalPdfUrl);

  const reimbursementIo = normalizeDate(first.io || first.io_number || first.io_no || '');
  const rmbCostCenterId = normalizeDate(first.cost_center_id || '');
  const rmbCostCenterOptions = getCostCenterOptions(rmbCostCenterId);
  const overviewHtml = `<div class="kna-overview-wrapper">
    <div class="kna-overview-compact">
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Requester</div>
      <div class="kna-overview-value">${escapeHtml(requesterName)}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Reimbursement ID</div>
      <div class="kna-overview-value">${escapeHtml(first.liquidation_id || first.reference_no || '-')}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Total Items</div>
      <div class="kna-overview-value">${data.length}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Requested Total</div>
      <div class="kna-overview-value kna-amount">${formatPHP(totalRequested)}</div>
    </div>
  </div>
  <div class="kna-overview-fields">
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Approved Total</div>
      <div class="kna-overview-value kna-amount">${formatPHP(totalApproved)}</div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Cost Center</div>
      <div class="kna-overview-value">
        <select class="kna-cost-center-select" id="reviewCostCenter" data-field="cost_center_id" data-original="${escapeHtml(rmbCostCenterId)}" style="min-width:140px;">
          ${rmbCostCenterOptions}
        </select>
      </div>
    </div>
    <div class="kna-overview-cell">
      <div class="kna-overview-label">Address</div>
      <div class="kna-overview-value">
        <input type="text" class="form-control form-control-sm kna-ca-field-input" id="reviewAddress" data-field="address" data-original="${escapeHtml(first.address || '')}" value="${escapeHtml(first.address || '')}" placeholder="Enter address..." style="min-width:140px;">
      </div>
    </div>
  </div>
  <div class="kna-overview-fields">
    <div class="kna-overview-cell">
      <div class="kna-overview-label">IO</div>
      <div class="kna-overview-value">
        <input type="text" class="form-control form-control-sm kna-ca-field-input" id="reviewIo" data-field="io" data-original="${escapeHtml(reimbursementIo)}" value="${escapeHtml(reimbursementIo)}" placeholder="Enter IO..." style="min-width:140px;">
      </div>
    </div>
  </div>
    <div class="kna-ca-update-bar">
      <button type="button" class="btn btn-primary btn-sm kna-small font-weight-bold" id="btnUpdateCaHeader" style="min-width:110px;">
        <i class="fas fa-save mr-1"></i> Update
      </button>
    </div>
  </div>
  ${hasApprovalPdf ? `<div class="kna-doc-bar" data-toggle-doc-viewer data-target="ca-doc-viewer" aria-expanded="false">
    <div class="kna-doc-bar-left"><i class="fas fa-file-pdf"></i> Supporting Document</div>
    <div class="kna-doc-bar-right"><i class="fas fa-eye"></i> <span>View</span></div>
  </div>
  <div id="ca-doc-viewer" class="kna-doc-viewer-wrap d-none">
    <iframe class="kna-doc-viewer-frame" src="about:blank" data-src="${escapeHtml(approvalPdfUrl)}" title="Reimbursement Document Viewer"></iframe>
  </div>` : ''}
    ${hasCaAttachments ? `<div class="kna-attach-strip">
    <div class="kna-attach-strip-label"><i class="fas fa-paperclip"></i> Attachments</div>
    <div class="kna-attach-strip-list">
      ${attachments.map(att => {
    const isImg = isImageFile(att.file_name);
    const iconClass = isImg ? 'img' : (att.file_name.toLowerCase().endsWith('.pdf') ? 'pdf' : 'doc');
    const icon = isImg ? 'fa-image' : (att.file_name.toLowerCase().endsWith('.pdf') ? 'fa-file-pdf' : 'fa-file');
    return `<a href="${escapeHtml(att.view_url || '#')}" class="kna-attach-strip-item ${iconClass}" target="_blank" rel="noopener" title="${escapeHtml(att.original_name || att.file_name)}">
          <i class="fas ${icon}"></i> ${escapeHtml(att.original_name || att.file_name)}
        </a>`;
  }).join('')}
    </div>
  </div>` : ''}`;


  if (domReview.reviewHeaderFields) domReview.reviewHeaderFields.innerHTML = overviewHtml;

  // Initialize Select2 for cost center
  const rmbCcSelect = document.getElementById('reviewCostCenter');
  if (rmbCcSelect && window.jQuery && $.fn.select2) {
    $(rmbCcSelect).select2({
      placeholder: 'Select Cost Center',
      allowClear: false,
      width: 'style'
    });
  }

  // Bind header field editor (same behavior as cash advance, reimbursement endpoint)
  initCaHeaderEditor(first.reference_no || first.liquidation_id, {
    endpoint: 'transactions/approvals/api/update/rmb-header',
    confirmTitle: 'Update Reimbursement Details?',
    confirmText: 'This will overwrite the requester\'s original entries for Cost Center, Payable To, Address, and IO. Continue?',
    successText: 'Reimbursement details have been updated successfully.'
  });

  // Bind document bar toggle for reimbursement
  const docBar = document.querySelector('.kna-doc-bar[data-toggle-doc-viewer]');
  if (docBar) {
    docBar.addEventListener('click', () => {
      const panel = document.getElementById(docBar.getAttribute('data-target'));
      if (!panel) return;
      const iframe = panel.querySelector('iframe[data-src]');
      const isHidden = panel.classList.contains('d-none');
      const label = docBar.querySelector('.kna-doc-bar-right span');
      const icon = docBar.querySelector('.kna-doc-bar-right i');
      if (isHidden) {
        if (iframe && iframe.getAttribute('src') === 'about:blank') {
          const s = iframe.getAttribute('data-src') || '';
          if (s) iframe.setAttribute('src', s);
        }
        panel.classList.remove('d-none');
        docBar.setAttribute('aria-expanded', 'true');
        docBar.classList.add('is-active');
        if (label) label.textContent = 'Hide';
        if (icon) icon.className = 'fas fa-eye-slash';
      } else {
        panel.classList.add('d-none');
        docBar.setAttribute('aria-expanded', 'false');
        docBar.classList.remove('is-active');
        if (label) label.textContent = 'View';
        if (icon) icon.className = 'fas fa-eye';
      }
    });
  }

  let mainRows = '', actionRows = '', mobileCards = '';

  data.forEach((item, idx) => {
    const key = item.liquidation_id + '_' + idx;
       const originalAmount = Number(item.gross_amount ?? item.lq_amount) || 0;
    const approvedGross = Number(item.approved_gross_amount || item.approved_amount || 0);
    const displayAmount = approvedGross > 0 ? approvedGross : originalAmount;
    const originalIsVatable = Boolean(Number(item.is_vatable));
    const vatCalc = calculateVat(displayAmount, originalIsVatable);
    const detailId = item.id || 0;
    const approvalPerItemId = item.approval_per_item_id || 0;
    const itemStatus = item.item_status || 'PENDING';
    const decidedBy = item.item_decided_by || null;
    const decidedByName = item.decided_by_name || 'another approver';
    const isMyItem = Number(item.is_my_item || 0) === 1;
    const isOwnDecision = decidedBy ? Number(decidedBy) === currentUserId : false;
    const isAlreadyDecided = itemStatus === 'APPROVED' || itemStatus === 'REJECTED';
    const isReadOnly = reviewState.isPastMode || (isAlreadyDecided && !isOwnDecision) || (!isMyItem && isAlreadyDecided);

    const initialDescription = normalizeDate(item.description || '');
    const initialInvoice = normalizeDate(item.invoice_receipt_no || '');
    const initialDocDate = normalizeDate(item.document_date || '').slice(0, 10);
    const initialCategory = normalizeDate(item.expense_category || '');
    const categoryOptionsHtml = getExpenseTypeOptions(initialCategory);
    const initialVendorName = normalizeDate(item.vendor_name || '');
    const initialVendorAddress = normalizeDate(item.vendor_address || '');
    const initialVendorTin = normalizeDate(item.vendor_tin || '');

    reviewState.decisions[key] = {
      decision: null,
      remark: '',
      amount: displayAmount,
      isVatable: originalIsVatable,
      originalIsVatable,
      netAmount: vatCalc.netAmount,
      vatAmount: vatCalc.vatAmount,
      originalAmount: originalAmount,
      actualAmount: originalAmount,
      approvedAmount: displayAmount,
      liquidatedAmount: displayAmount,
      detail_id: detailId,
      approval_per_item_id: approvalPerItemId,
      item_status: itemStatus,
      isOwnDecision,
      isReadOnly,
      isMyItem,
      decidedBy,
      decidedByName,
      description: initialDescription,
      invoiceReceiptNo: initialInvoice,
      documentDate: initialDocDate,
      expenseCategory: initialCategory,
      vendorName: initialVendorName,
      vendorAddress: initialVendorAddress,
      vendorTin: initialVendorTin
    };

    const rowClass = itemStatus === 'APPROVED' ? 'is-approved' : (itemStatus === 'REJECTED' ? 'is-rejected' : '');
    const preselectedApprove = itemStatus === 'APPROVED' ? 'is-active' : '';
    const preselectedReject = itemStatus === 'REJECTED' ? 'is-active' : '';
    const remarkVisible = itemStatus === 'REJECTED' ? '' : 'd-none';
    const disabledAttr = isReadOnly ? 'disabled' : '';
    const readonlyAttr = isReadOnly ? 'readonly' : '';

    const hasAttachment = Boolean(item.attachment);
    const attachHtml = hasAttachment ? renderAttachment(item.attachment) : '<span class="text-muted kna-small">—</span>';
    const attachNames = (item.attachment || '').split(',').map(s => s.trim()).filter(Boolean);

    const descriptionInputHtml = `<input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-description" data-key="${key}" value="${escapeHtml(initialDescription)}" placeholder="Description" ${disabledAttr}>`;
    const categoryInputHtml = `<select class="form-control form-control-sm kna-inline-edit-input kna-edit-category" data-key="${key}" ${disabledAttr}>${categoryOptionsHtml}</select>`;
    const invoiceInputHtml = `<input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-invoice" data-key="${key}" value="${escapeHtml(initialInvoice)}" placeholder="Invoice/Receipt" ${disabledAttr}>`;
    const docDateInputHtml = `<input type="date" class="form-control form-control-sm kna-inline-edit-input kna-edit-docdate" data-key="${key}" value="${escapeHtml(initialDocDate)}" ${disabledAttr}>`;
    const grossInputHtml = `
      <div class="kna-amount-input-wrap">
        ${approvedGross > 0 && approvedGross !== originalAmount ? `<div class="kna-original-amount-strike">${formatPHP(originalAmount)}</div>` : ''}
        <input type="number" step="0.01" min="0" class="form-control form-control-sm kna-inline-edit-input kna-edit-gross text-right" data-key="${key}" value="${escapeHtml(displayAmount)}" ${disabledAttr}>
      </div>`;
    const vendorInputHtml = `<div class="kna-vendor-edit-wrap"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-name" data-key="${key}" value="${escapeHtml(initialVendorName)}" placeholder="Vendor name" ${disabledAttr}><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-address" data-key="${key}" value="${escapeHtml(initialVendorAddress)}" placeholder="Vendor address" ${disabledAttr}><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-tin" data-key="${key}" value="${escapeHtml(initialVendorTin)}" placeholder="TIN" ${disabledAttr}></div>`;

    mainRows += `<tr data-item-key="${key}" class="${rowClass}"><td class="text-center kna-rownum">${idx + 1}</td><td class="kna-col-description">${descriptionInputHtml}</td><td class="kna-col-category">${categoryInputHtml}</td><td>${attachHtml}</td><td>${invoiceInputHtml}</td><td>${docDateInputHtml}</td><td class="text-right kna-amount-main">${grossInputHtml}</td><td class="text-center kna-vat-cell"><label class="kna-vat-indicator"><input type="checkbox" class="kna-vat-check kna-vat-approver" data-key="${key}" ${originalIsVatable ? 'checked' : ''} ${disabledAttr}></label></td><td class="text-right kna-net-cell">${formatPHP(vatCalc.netAmount)}</td><td class="text-right kna-vat-amt-cell">${formatPHP(vatCalc.vatAmount)}</td><td class="kna-col-vendor">${vendorInputHtml}</td></tr>`;

    const actionCellHtml = isReadOnly
      ? renderReadOnlyAction(itemStatus, decidedByName, item.item_remarks)
      : `<div class="kna-item-decision"><div class="kna-toggle-group"><button type="button" class="kna-toggle-btn is-approve ${preselectedApprove}" data-decision="approve" data-key="${key}" ${disabledAttr}><i class="fas fa-check"></i></button><button type="button" class="kna-toggle-btn is-reject ${preselectedReject}" data-decision="reject" data-key="${key}" ${disabledAttr}><i class="fas fa-times"></i></button></div><textarea class="kna-item-remark ${remarkVisible}" data-key="${key}" ${readonlyAttr} placeholder="Remarks are required for rejection...">${escapeHtml(item.item_remarks || '')}</textarea><button type="button" class="kna-cancel-reject ${remarkVisible === '' ? '' : 'd-none'}" data-cancel-reject data-key="${key}" title="Cancel rejection"><i class="fas fa-undo"></i> Cancel</button></div>`;
    actionRows += `<tr data-item-key="${key}" class="${rowClass}"><td class="kna-col-action">${actionCellHtml}</td></tr>`;

    const mobileDecisionHtml = isReadOnly
      ? `<div class="kna-item-decision mt-2"><div class="kna-readonly-status"><i class="fas ${itemStatus === 'APPROVED' ? 'fa-check-circle' : 'fa-times-circle'}" style="color:${itemStatus === 'APPROVED' ? '#17663a' : '#e03131'};font-size:14px;"></i><span class="kna-badge ${itemStatus === 'APPROVED' ? 'kna-badge-approved' : 'kna-badge-rejected'}">${itemStatus}</span></div><div class="kna-readonly-by">by ${escapeHtml(decidedByName)}</div>${item.item_remarks ? `<div class="kna-readonly-remark">${escapeHtml(item.item_remarks)}</div>` : ''}</div>`
      : `<div class="kna-item-decision mt-2"><div class="kna-toggle-group"><button type="button" class="kna-toggle-btn is-approve ${preselectedApprove}" data-decision="approve" data-key="${key}" ${disabledAttr}><i class="fas fa-check"></i> Approve</button><button type="button" class="kna-toggle-btn is-reject ${preselectedReject}" data-decision="reject" data-key="${key}" ${disabledAttr}><i class="fas fa-times"></i> Reject</button></div><textarea class="kna-item-remark ${remarkVisible}" data-key="${key}" ${readonlyAttr} placeholder="Remarks are required for rejection...">${escapeHtml(item.item_remarks || '')}</textarea><button type="button" class="kna-cancel-reject ${remarkVisible === '' ? '' : 'd-none'}" data-cancel-reject data-key="${key}"><i class="fas fa-undo"></i> Cancel</button></div>`;

    mobileCards += `<div class="kna-exp-card ${rowClass}" data-item-key="${key}"><div class="kna-exp-card-head"><div><div class="kna-exp-card-title">${escapeHtml(initialDescription || '-')}</div><div class="kna-exp-card-sub">${escapeHtml(item.category_name || '-')}</div><div class="kna-exp-card-meta">Inv#: ${escapeHtml(initialInvoice || '-')} • ${escapeHtml(initialDocDate || '—')}</div></div><div class="kna-exp-card-amount"><div class="kna-amount-main">${formatPHP(displayAmount)}</div></div></div><div class="kna-exp-card-grid"><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Description</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-description" data-key="${key}" value="${escapeHtml(initialDescription)}" placeholder="Description" ${disabledAttr}></span></div><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Expense Type</span><span class="kna-exp-card-value"><select class="form-control form-control-sm kna-inline-edit-input kna-edit-category" data-key="${key}" ${disabledAttr}>${categoryOptionsHtml}</select></span></div><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Attachment</span><span class="kna-exp-card-value">${attachNames.length ? attachNames.map(renderAttachment).join('') : '<span class="text-muted">—</span>'}</span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Invoice/Receipt</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-invoice" data-key="${key}" value="${escapeHtml(initialInvoice)}" placeholder="Invoice/Receipt" ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Doc Date</span><span class="kna-exp-card-value"><input type="date" class="form-control form-control-sm kna-inline-edit-input kna-edit-docdate" data-key="${key}" value="${escapeHtml(initialDocDate)}" ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Gross</span><span class="kna-exp-card-value"><input type="number" step="0.01" min="0" class="form-control form-control-sm kna-inline-edit-input kna-edit-gross" data-key="${key}" value="${escapeHtml(displayAmount)}" ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">VAT</span><span class="kna-exp-card-value"><input type="checkbox" class="kna-vat-check kna-vat-approver" data-key="${key}" ${originalIsVatable ? 'checked' : ''} ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Net / VAT</span><span class="kna-exp-card-value"><span class="kna-net-value">${formatPHP(vatCalc.netAmount)}</span> / <span class="kna-vat-value">${formatPHP(vatCalc.vatAmount)}</span></span></div><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Vendor Name</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-name" data-key="${key}" value="${escapeHtml(initialVendorName)}" placeholder="Vendor name" ${disabledAttr}></span></div><div class="kna-exp-card-field kna-exp-card-field-full"><span class="kna-exp-card-label">Vendor Address</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-address" data-key="${key}" value="${escapeHtml(initialVendorAddress)}" placeholder="Vendor address" ${disabledAttr}></span></div><div class="kna-exp-card-field"><span class="kna-exp-card-label">Vendor TIN</span><span class="kna-exp-card-value"><input type="text" class="form-control form-control-sm kna-inline-edit-input kna-edit-vendor-tin" data-key="${key}" value="${escapeHtml(initialVendorTin)}" placeholder="TIN" ${disabledAttr}></span></div></div>${mobileDecisionHtml}</div>`;

    if (itemStatus === 'APPROVED') {
      reviewState.decisions[key].decision = 'approve';
    } else if (itemStatus === 'REJECTED') {
      reviewState.decisions[key].decision = 'reject';
      reviewState.decisions[key].remark = item.item_remarks || '';
    }
  });

  const desktopHtml = `<div class="kna-review-desktop kna-review-desktop-liquidation kna-review-desktop-reimbursement"><div class="kna-review-table-shell">
    <div class="kna-review-table-wrap-main"><table class="table table-sm kna-review-table-main"><thead><tr><th>#</th><th>Description</th><th>Expense Type</th><th>Attachment</th><th>Invoice/Receipt</th><th>Doc. Date</th><th class="text-right">Gross</th><th class="text-center">VAT</th><th class="text-right">Net</th><th class="text-right">VAT Amt</th><th>Vendor</th></tr></thead><tbody>${mainRows}</tbody></table></div>
    <div class="kna-review-table-wrap-action"><table class="table table-sm kna-review-table-action"><thead><tr><th>Action</th></tr></thead><tbody>${actionRows}</tbody></table></div>
  </div><div class="kna-review-footer"><div class="kna-review-footer-main"><span class="kna-review-footer-label">Total Reimbursed Amount</span><div class="kna-review-footer-amount kna-amount-main">${formatPHP(totalRequested)}</div></div></div></div>`;

  const mobileHtml = `<div class="kna-exp-mobile">${mobileCards}</div>`;

  if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = desktopHtml + mobileHtml;
  initLiquidationCategorySelect2();
  requestAnimationFrame(() => requestAnimationFrame(syncRowHeights));
};

/* ─── VAT UPDATE ─── */
const updateVatDisplay = (key, isVatable) => {
  const decision = reviewState.decisions[key];
  if (!decision || decision.isReadOnly) return;
  // Compute from the current (possibly approver-edited) gross, not the original actualAmount
  const currentGross = Number(decision.amount ?? decision.approvedAmount ?? decision.actualAmount) || 0;
  const vatCalc = calculateVat(currentGross, isVatable);
  decision.isVatable = isVatable; decision.netAmount = vatCalc.netAmount; decision.vatAmount = vatCalc.vatAmount;
  document.querySelectorAll(`[data-item-key="${key}"]`).forEach(row => {
    const netCell = row.querySelector('.kna-net-cell'), vatCell = row.querySelector('.kna-vat-amt-cell');
    if (netCell) { netCell.textContent = formatPHP(vatCalc.netAmount); netCell.setAttribute('data-net', vatCalc.netAmount); }
    if (vatCell) { vatCell.textContent = formatPHP(vatCalc.vatAmount); vatCell.setAttribute('data-vat', vatCalc.vatAmount); }
    const netSpan = row.querySelector('.kna-net-value'), vatSpan = row.querySelector('.kna-vat-value');
    if (netSpan) netSpan.textContent = formatPHP(vatCalc.netAmount); if (vatSpan) vatSpan.textContent = formatPHP(vatCalc.vatAmount);
  });
  requestAnimationFrame(syncRowHeights);
};

/* ─── TIMELINE ─── */
const AUDIT_FIELD_LABELS = {
  description: 'Description', invoice_receipt_no: 'Invoice/Receipt No.', document_date: 'Document Date',
  actual_amount: 'Gross Amount', approved_amount: 'Gross Amount', expense_category: 'Expense Type',
  is_vatable: 'VAT Applicable', net_amount: 'Net Amount', vat_amount: 'VAT Amount',
  vendor_name: 'Vendor Name', vendor_address: 'Vendor Address', vendor_tin: 'Vendor TIN',
  amount: 'Amount', cost_center_id: 'Cost Center', payable_to: 'Payable To', address: 'Address', io: 'IO Number'
};
const AUDIT_CURRENCY_FIELDS = new Set(['actual_amount', 'approved_amount', 'net_amount', 'vat_amount', 'amount']);

const formatAuditFieldLabel = field => AUDIT_FIELD_LABELS[field] || field.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

const formatAuditValue = (field, value) => {
  const raw = normalizeDate(value);
  if (raw === '') return '<span class="text-muted">—</span>';
  if (AUDIT_CURRENCY_FIELDS.has(field)) { const num = Number(raw); return Number.isFinite(num) ? escapeHtml(formatPHP(num)) : escapeHtml(raw); }
  if (field === 'is_vatable') return (raw === '1' || raw.toLowerCase() === 'true') ? 'Yes' : 'No';
  if (field === 'document_date') { const d = raw.slice(0, 10); return escapeHtml(d || raw); }
  return escapeHtml(raw);
};

const AUDIT_ACTION_VERBS = {
  SUBMITTED: 'submitted', SAVED_DRAFT: 'saved a draft of', CREATED: 'created', APPROVED: 'approved',
  REJECTED: 'rejected', UPDATED_DRAFT: 'updated', RESUBMITTED: 'resubmitted',
  ADDED_ITEM: 'added an item to', UPDATED_ITEM: 'edited',
  // Advise/Release log the raw new status code as the action (see
  // Approvals::runAdvisePayment/runReleasePayment) — map those
  // specifically for every transaction type instead of falling
  // through to the raw-code fallback ("ca for release the cash advance").
  CA_FOR_RELEASE: 'advised payment for', CA_FOR_LIQUIDATION: 'released payment for', CA_COMPLETED: 'released payment for',
  LQ_FOR_RELEASE: 'advised payment for', LQ_COMPLETED: 'released payment for',
  RMB_FOR_RELEASE: 'advised payment for', RMB_PAID: 'released payment for',
  RPL_FOR_RELEASE: 'advised payment for', RPL_COMPLETED: 'released payment for',
};
const auditActionVerb = action => AUDIT_ACTION_VERBS[action] || action.toLowerCase().replace(/_/g, ' ');
const joinAuditVerbs = actions => {
  const verbs = [...new Set(actions.map(auditActionVerb))];
  if (verbs.length === 1) return verbs[0];
  if (verbs.length === 2) return `${verbs[0]} and ${verbs[1]}`;
  return `${verbs.slice(0, -1).join(', ')}, and ${verbs[verbs.length - 1]}`;
};

const groupAuditTrail = auditTrail => {
  if (!auditTrail || !auditTrail.length) return [];
  const sorted = [...auditTrail].sort((a, b) => new Date((a.created_date || '').replace(' ', 'T')) - new Date((b.created_date || '').replace(' ', 'T')));
  const entriesWithKey = sorted.map(entry => {
    const action = normalizeDate(entry.action || '').toUpperCase(), changedByName = normalizeDate(entry.changed_by_name || 'Unknown User');
    const transactionId = normalizeDate(entry.transaction_id || ''), entityType = normalizeDate(entry.entity_type || '').toUpperCase();
    const entityId = normalizeDate(entry.entity_id || ''), fieldName = normalizeDate(entry.field_name || '');
    const description = normalizeDate(entry.description || ''), remarks = normalizeDate(entry.remarks || '');
    const dateStr = formatTimelineDate(entry.created_date), rawDate = normalizeDate(entry.created_date || ''), timeBucket = rawDate.length >= 16 ? rawDate.substring(0, 16) : rawDate;
    return {
      ...entry, _action: action, _entityType: entityType, _entityId: entityId, _fieldName: fieldName,
      _changedByName: changedByName, _dateStr: dateStr, _timeBucket: timeBucket,
      _groupKey: `${changedByName}|${transactionId}|${timeBucket}`,
      _description: description, _remarks: remarks, _oldValue: entry.old_value, _newValue: entry.new_value
    };
  });
  const groupMap = new Map();
  entriesWithKey.forEach(entry => {
    const key = entry._groupKey;
    if (!groupMap.has(key)) groupMap.set(key, { dateStr: entry._dateStr, changedByName: entry._changedByName, transactionType: normalizeDate(entry.transaction_type || ''), actions: [], headerRemarks: '', headerDescription: '', itemsByEntity: new Map(), hasHeader: false, hasItems: false });
    const group = groupMap.get(key);
    if (!group.actions.includes(entry._action)) group.actions.push(entry._action);
    if (entry._entityType === 'HEADER') {
      group.hasHeader = true;
      if (entry._remarks) group.headerRemarks = entry._remarks;
      if (entry._description) group.headerDescription = entry._description;
    } else if (entry._entityType === 'ITEM') {
      group.hasItems = true;
      const entityKey = entry._entityId || entry._description || 'item';
      if (!group.itemsByEntity.has(entityKey)) group.itemsByEntity.set(entityKey, { description: entry._description, remarks: '', changes: [], actions: [] });
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
  const groups = Array.from(groupMap.values()).map(g => ({ ...g, items: Array.from(g.itemsByEntity.values()) }));
  groups.sort((a, b) => new Date((a.dateStr || '').replace(' ', 'T')) - new Date((b.dateStr || '').replace(' ', 'T')));
  return groups;
};

const buildTimelineText = group => {
  const changedByName = escapeHtml(group.changedByName), transactionType = group.transactionType.toLowerCase(), items = group.items;
  const entityDesc = transactionType === 'cash_advance' ? 'the cash advance' : (transactionType === 'liquidation' ? 'the liquidation' : (transactionType === 'reimbursement' ? 'the reimbursement' : 'the request'));
  const verbPhrase = joinAuditVerbs(group.actions);

  let mainLine = `<strong>${changedByName}</strong> ${verbPhrase} ${entityDesc}`;
  if (group.headerDescription) mainLine += ` &mdash; ${escapeHtml(group.headerDescription)}`;
  if (group.headerRemarks) mainLine += `: "${escapeHtml(group.headerRemarks)}"`;

  if (!group.hasItems || items.length === 0) return mainLine;

  const subLines = items.map(item => {
    const label = item.description ? `"${escapeHtml(item.description)}"` : 'an item';
    const itemVerb = joinAuditVerbs(item.actions);
    const itemVerbLabel = itemVerb.charAt(0).toUpperCase() + itemVerb.slice(1);
    if (item.changes.length > 0) {
      const changeParts = item.changes.map(c => `${escapeHtml(formatAuditFieldLabel(c.field))}: ${formatAuditValue(c.field, c.oldValue)} &rarr; ${formatAuditValue(c.field, c.newValue)}`).join(', ');
      let line = `&nbsp;&nbsp;&bull; Changed ${label} &mdash; ${changeParts}`;
      if (item.remarks) line += ` <em>("${escapeHtml(item.remarks)}")</em>`;
      return line;
    }
    if (item.remarks) return `&nbsp;&nbsp;&bull; ${itemVerbLabel} ${label}: "${escapeHtml(item.remarks)}"`;
    return `&nbsp;&nbsp;&bull; ${itemVerbLabel} ${label}`;
  });
  return [mainLine, ...subLines].join('<br>');
};

const renderHistoryTimeline = auditTrail => {
  const container = domReview.reviewTimeline; if (!container) return;
  if (!auditTrail || !auditTrail.length) { container.innerHTML = `<li class="kna-timeline-item is-pending"><div class="kna-timeline-item-top"><span class="kna-timeline-item-name">No history available</span></div><div class="kna-timeline-item-remarks">This request has no recorded history yet.</div></li>`; return; }
  const groups = groupAuditTrail(auditTrail);
  container.innerHTML = groups.map((group, index) => {
    const isLast = index === groups.length - 1, statusClass = isLast ? 'is-current' : 'is-done', text = buildTimelineText(group);
    return `<li class="kna-timeline-item ${statusClass}"><div class="kna-timeline-item-top"><span class="kna-timeline-item-name">${escapeHtml(group.dateStr)}</span></div><div class="kna-timeline-item-remarks">${text}</div></li>`;
  }).join('');
};

const loadAuditTrail = () => {
  const ref = domReview.approvalRef ? domReview.approvalRef.value : '';
  if (!ref) return;
  ajax_loader('transactions/approvals/api/get/timeline', { ReferenceNo: ref }).done(response => {
    const res = typeof response === 'string' ? $.parseJSON(response) : response;
    if (res.status !== 'success') { renderHistoryTimeline([]); return; }
    renderHistoryTimeline(res.data && res.data.audit_trail ? res.data.audit_trail : []);
  }).fail(() => renderHistoryTimeline([]));
};
const renderReviewTimeline = () => loadAuditTrail();

/* ─── SUMMARY & SUBMIT STATE ─── */
const updateSubmitButtonState = () => {
  const decisions = reviewState.decisions, keys = Object.keys(decisions), actionableItems = keys.filter(k => !decisions[k].isReadOnly);
  const totalActionable = actionableItems.length, reviewedCount = actionableItems.filter(k => decisions[k].decision).length, allDecided = totalActionable > 0 && reviewedCount === totalActionable;
  if (domReview.btnSubmitDecision) {
    domReview.btnSubmitDecision.disabled = !allDecided;
    domReview.btnSubmitDecision.classList.toggle('btn-light', !allDecided); domReview.btnSubmitDecision.classList.toggle('btn-success', allDecided);
    domReview.btnSubmitDecision.title = allDecided ? 'All items reviewed — ready to submit' : `Review all items first (${reviewedCount}/${totalActionable})`;
  }
};

const updateSummary = () => {
  const decisions = reviewState.decisions, keys = Object.keys(decisions), actionableItems = keys.filter(k => !decisions[k].isReadOnly), totalItems = actionableItems.length;
  let reviewedCount = 0, approvedAmount = 0, rejectedAmount = 0;
  keys.forEach(k => { const d = decisions[k]; if (d.decision) reviewedCount++; if (d.decision === 'approve') approvedAmount += d.amount; if (d.decision === 'reject') rejectedAmount += d.amount; });
  reviewState.reviewedCount = reviewedCount; reviewState.approvedAmount = approvedAmount; reviewState.rejectedAmount = rejectedAmount;
  if (domReview.summaryReviewed) domReview.summaryReviewed.textContent = `${reviewedCount} / ${totalItems}`;
  if (domReview.summaryApprovedAmount) domReview.summaryApprovedAmount.textContent = formatPHP(approvedAmount);
  if (domReview.summaryRejectedAmount) domReview.summaryRejectedAmount.textContent = formatPHP(rejectedAmount);
  updateSubmitButtonState();
};

const callPerItemDecision = (key, status, remarks, isNotify) => {
  const decision = reviewState.decisions[key];
  if (!decision || !decision.approval_per_item_id) return Promise.reject(new Error('Approval item ID not found for key: ' + key));
  return $.ajax({ url: base_url + 'transactions/approvals/api/per/item/decision', type: 'POST', contentType: 'application/json; charset=utf-8', dataType: 'json', data: JSON.stringify({ approval_per_item_id: decision.approval_per_item_id, status: status.toUpperCase(), remarks: remarks || '', is_notify: isNotify }) });
};

/* ─── EVENT BINDING ─── */
const bindDecisionEvents = () => {
  if (!domReview.viewApprovalItems) return;
  domReview.viewApprovalItems.addEventListener('click', e => {
    const cancelBtn = e.target.closest('[data-cancel-reject]');
    if (cancelBtn) { cancelRejectFlow(cancelBtn.getAttribute('data-key')); return; }
    const btn = e.target.closest('.kna-toggle-btn'); if (!btn) return;
    const key = btn.getAttribute('data-key'), decision = btn.getAttribute('data-decision'), parent = btn.closest('.kna-toggle-group'), container = btn.closest('.kna-item-decision'), decisionState = reviewState.decisions[key];
    if (!decisionState) return;
    if (decisionState.isReadOnly) { Swal.fire({ icon: 'info', title: 'Already Decided', text: `This item has already been ${decisionState.item_status.toLowerCase()} by ${decisionState.decidedByName || 'another approver'} and cannot be modified.`, confirmButtonText: 'OK', confirmButtonColor: '#5b2bd6' }); return; }
    const isAlreadyActive = btn.classList.contains('is-active'), remark = container.querySelector(`.kna-item-remark[data-key="${key}"]`);
    if (isAlreadyActive && decision === 'reject' && remark && !remark.classList.contains('d-none')) {
      const remarkText = remark.value.trim();
      if (!remarkText) { remark.classList.add('kna-remark-required'); remark.focus(); setTimeout(() => remark.classList.remove('kna-remark-required'), 600); Swal.fire({ icon: 'warning', title: 'Remarks Required', text: 'Please provide remarks for this rejection before confirming.', confirmButtonText: 'OK', confirmButtonColor: '#f59f00' }); return; }
      Swal.fire({ icon: 'warning', title: 'Reject Item', text: 'Notify the requester to correct this item?', showDenyButton: true, showCancelButton: true, confirmButtonText: 'Yes', denyButtonText: 'No', cancelButtonText: 'Cancel', confirmButtonColor: '#e03131', denyButtonColor: '#f59f00', cancelButtonColor: '#9ca3af' }).then(result => {
        if (result.isDismissed) return;
        const isNotify = result.isConfirmed ? 1 : 0;
        callPerItemDecision(key, 'REJECTED', remarkText, isNotify).then(response => {
          if (response.status !== 'success') throw new Error(response.response || 'Failed');
          decisionState.item_status = 'REJECTED'; decisionState.remark = remarkText; toggleCancelButton(key, false); setRejectButtonIcon(btn, false); updateRowStyling(key, 'reject'); refreshItemStatusBadge(key, 'REJECTED'); updateSummary();
          Swal.fire({ icon: 'success', title: 'Item Rejected', text: isNotify ? 'Requester has been notified to correct this item.' : 'Item has been rejected successfully.', timer: 2500, showConfirmButton: false, toast: true, position: 'top-end' });
        }).catch(err => {
          Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Failed to reject item.' }); toggleCancelButton(key, false); setRejectButtonIcon(btn, false); btn.classList.remove('is-active'); decisionState.decision = null;
          if (remark) { remark.classList.add('d-none'); remark.value = ''; } updateRowStyling(key, null); refreshItemStatusBadge(key, 'PENDING'); updateSummary();
        });
      }); return;
    }
    if (isAlreadyActive) {
      if (decisionState) decisionState.decision = null; if (remark) { remark.classList.add('d-none'); remark.value = ''; }
      toggleCancelButton(key, false); if (decision === 'reject') setRejectButtonIcon(btn, false); parent.querySelectorAll('.kna-toggle-btn').forEach(b => b.classList.remove('is-active'));
      updateRowStyling(key, null); refreshItemStatusBadge(key, 'PENDING'); updateSummary(); return;
    }
    parent.querySelectorAll('.kna-toggle-btn').forEach(b => { if (b.classList.contains('is-active') && b.getAttribute('data-decision') === 'reject') setRejectButtonIcon(b, false); b.classList.remove('is-active'); });
    btn.classList.add('is-active'); if (decisionState) decisionState.decision = decision;
    if (reviewState.transactionType === 'CASH_ADVANCE' || reviewState.transactionType === 'REPLENISHMENT') {
      if (remark) { if (decision === 'reject') { remark.classList.remove('d-none'); remark.focus(); toggleCancelButton(key, true); } else { remark.classList.add('d-none'); remark.value = ''; toggleCancelButton(key, false); } }
      updateRowStyling(key, decision); updateSummary(); return;
    }
    if (reviewState.transactionType === 'LIQUIDATION' || reviewState.transactionType === 'REIMBURSEMENT') {
      if (decision === 'approve') {
        if (remark) { remark.classList.add('d-none'); remark.value = ''; } toggleCancelButton(key, false);
        const rejectBtn = container.querySelector(`.kna-toggle-btn.is-reject[data-key="${key}"]`); if (rejectBtn) setRejectButtonIcon(rejectBtn, false);
        updateRowStyling(key, 'approve'); refreshItemStatusBadge(key, 'APPROVED'); updateSummary(); return;
      }
      if (decision === 'reject') { if (remark) { remark.classList.remove('d-none'); remark.focus(); } toggleCancelButton(key, true); setRejectButtonIcon(btn, true); return; }
    }
  });
  domReview.viewApprovalItems.addEventListener('input', e => {
    if (e.target.classList.contains('kna-item-remark')) {
      const key = e.target.getAttribute('data-key');
      if (reviewState.decisions[key]) reviewState.decisions[key].remark = e.target.value;
      e.target.classList.remove('kna-remark-required');
      return;
    }
    const key = e.target.getAttribute('data-key');
    if (!key || !reviewState.decisions[key]) return;
    if (e.target.classList.contains('kna-edit-ca-description')) {
      reviewState.decisions[key].description = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-ca-description', e.target.value, e.target);
      return;
    }
    if (e.target.classList.contains('kna-edit-ca-amount')) {
      const amount = Number(e.target.value);
      const normalizedAmount = Number.isFinite(amount) && amount >= 0 ? amount : 0;
      reviewState.decisions[key].amount = normalizedAmount;
      reviewState.decisions[key].actualAmount = normalizedAmount;
      reviewState.decisions[key].approvedAmount = normalizedAmount;
      // Update amount in words
      const words = amountToWords(normalizedAmount);
      reviewState.decisions[key].approvedAmountInWords = words;
      // Update display
      const wordsEl = document.getElementById('caApprovedAmountWords');
      if (wordsEl) wordsEl.textContent = words;
      const displayEl = document.getElementById('caApprovedAmountDisplay');
      if (displayEl) displayEl.textContent = formatPHP(normalizedAmount);
      const footerEl = document.getElementById('caFooterApprovedAmount');
      if (footerEl) footerEl.textContent = formatPHP(normalizedAmount);
      const mobileEl = document.getElementById('caMobileApprovedAmount');
      if (mobileEl) mobileEl.textContent = formatPHP(normalizedAmount);
      syncEditableFieldInputs(key, 'kna-edit-ca-amount', e.target.value, e.target);
      updateSummary();
      return;
    }
    if (e.target.classList.contains('kna-edit-category')) {
      reviewState.decisions[key].expenseCategory = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-category', e.target.value, e.target);
      return;
    }
    if (e.target.classList.contains('kna-edit-description')) {
      reviewState.decisions[key].description = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-description', e.target.value, e.target);
      return;
    }
    if (e.target.classList.contains('kna-edit-invoice')) {
      reviewState.decisions[key].invoiceReceiptNo = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-invoice', e.target.value, e.target);
      return;
    }
    if (e.target.classList.contains('kna-edit-docdate')) {
      reviewState.decisions[key].documentDate = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-docdate', e.target.value, e.target);
      return;
    }
    if (e.target.classList.contains('kna-edit-gross')) {
      const gross = Number(e.target.value);
      const normalizedGross = Number.isFinite(gross) && gross >= 0 ? gross : 0;
      reviewState.decisions[key].approvedAmount = normalizedGross;
      reviewState.decisions[key].amount = normalizedGross;
      reviewState.decisions[key].liquidatedAmount = normalizedGross;
      syncEditableFieldInputs(key, 'kna-edit-gross', String(e.target.value), e.target);
      updateVatDisplay(key, !!reviewState.decisions[key].isVatable);
      updateSummary();
      return;
    }
    if (e.target.classList.contains('kna-edit-vendor-name')) {
      reviewState.decisions[key].vendorName = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-vendor-name', e.target.value, e.target);
      return;
    }
    if (e.target.classList.contains('kna-edit-vendor-address')) {
      reviewState.decisions[key].vendorAddress = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-vendor-address', e.target.value, e.target);
      return;
    }
    if (e.target.classList.contains('kna-edit-vendor-tin')) {
      reviewState.decisions[key].vendorTin = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-vendor-tin', e.target.value, e.target);
    }
  });
  domReview.viewApprovalItems.addEventListener('change', e => {
    if (e.target.classList.contains('kna-vat-approver')) {
      updateVatDisplay(e.target.getAttribute('data-key'), e.target.checked);
      return;
    }
    if (e.target.classList.contains('kna-edit-category')) {
      const key = e.target.getAttribute('data-key');
      if (!key || !reviewState.decisions[key]) return;
      reviewState.decisions[key].expenseCategory = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-category', e.target.value, e.target);
      return;
    }
    if (e.target.classList.contains('kna-edit-docdate')) {
      const key = e.target.getAttribute('data-key');
      if (!key || !reviewState.decisions[key]) return;
      reviewState.decisions[key].documentDate = e.target.value;
      syncEditableFieldInputs(key, 'kna-edit-docdate', e.target.value, e.target);
    }
  });
  domReview.viewApprovalItems.addEventListener('click', e => { const wrap = e.target.closest('[data-lightbox]'); if (wrap) openLightbox(wrap.getAttribute('data-lightbox')); });

  /* ─── FIX: Lightbox for CA attachments in header/overview ─── */
  if (domReview.reviewHeaderFields) {
    domReview.reviewHeaderFields.addEventListener('click', e => {
      const wrap = e.target.closest('[data-lightbox]');
      if (wrap) { e.preventDefault(); openLightbox(wrap.getAttribute('data-lightbox')); }
    });
  }
};

/* ─── SUBMISSION ─── */
const submitDecisions = () => {
  const decisions = reviewState.decisions, keys = Object.keys(decisions), actionableKeys = keys.filter(k => !decisions[k].isReadOnly);
  const totalItems = actionableKeys.length, reviewedCount = actionableKeys.filter(k => decisions[k].decision).length;
  if (reviewedCount < totalItems) { Swal.fire({ icon: 'warning', title: 'Incomplete Review', text: `Please review all items before submitting. (${reviewedCount} of ${totalItems} decided)`, confirmButtonText: 'OK', confirmButtonColor: '#f59f00' }); return; }
  if (reviewState.transactionType === 'LIQUIDATION' || reviewState.transactionType === 'REIMBURSEMENT') {
    const hasRejections = actionableKeys.some(k => decisions[k].decision === 'reject');
    Swal.fire({ icon: hasRejections ? 'warning' : 'info', title: 'Confirm Final Submission', text: hasRejections ? 'Rejecting any item returns the whole request to the requester for correction and resubmission. Continue?' : 'Submit your final decision?', showCancelButton: true, confirmButtonText: 'Submit Final Decision', cancelButtonText: 'Cancel', confirmButtonColor: hasRejections ? '#e03131' : '#17663a', cancelButtonColor: '#6b7280' }).then(result => {
      if (!result.isConfirmed) return;
      const btn = domReview.btnSubmitDecision; if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...'; }
      const approvalPromises = [];
      actionableKeys.forEach(k => { const d = decisions[k]; if (d.decision === 'approve' && d.approval_per_item_id) approvalPromises.push(callPerItemDecision(k, 'APPROVED', '', 0).catch(err => { if (err.message && err.message.includes('already been decided')) return { status: 'success', skipped: true }; throw err; })); });
      Promise.all(approvalPromises).then(() => handleSubmit()).catch(err => { Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Failed to process approvals.', confirmButtonColor: '#e03131' }); if (btn) { btn.disabled = false; updateSubmitButtonState(); } });
    }); return;
  }
  const hasRejections = actionableKeys.some(k => decisions[k].decision === 'reject');
  const typeNoun = reviewState.transactionType === 'REPLENISHMENT' ? 'replenishment' : 'cash advance';
  Swal.fire({ icon: hasRejections ? 'warning' : 'info', title: 'Confirm Submission', text: hasRejections ? `This will reject the ${typeNoun}. Are you sure?` : `This will approve the ${typeNoun}. Submit?`, showCancelButton: true, confirmButtonText: 'Submit', cancelButtonText: 'Cancel', confirmButtonColor: hasRejections ? '#e03131' : '#17663a', cancelButtonColor: '#6b7280' }).then(result => { if (result.isConfirmed) handleSubmit(); });
};

const handleSubmit = () => {
  const decisions = reviewState.decisions, keys = Object.keys(decisions);
  const payload = {
    reference_no: reviewState.referenceNo, transaction_type: reviewState.transactionType,
    overall_remarks: domReview.reviewerRemarks ? domReview.reviewerRemarks.value : '',
    decisions: keys.map(k => { const d = decisions[k]; return { item_key: k, decision: d.decision, remark: d.remark, amount: d.amount, original_amount: d.originalAmount || d.amount, approved_amount: d.approvedAmount || d.amount, approved_amount_in_words: d.approvedAmountInWords || '', is_vatable: d.isVatable, net_amount: d.netAmount, vat_amount: d.vatAmount, actual_amount: d.actualAmount, detail_id: d.detail_id || null, description: d.description || '', invoice_receipt_no: d.invoiceReceiptNo || '', document_date: d.documentDate || '', expense_category: d.expenseCategory || '', vendor_name: d.vendorName || '', vendor_address: d.vendorAddress || '', vendor_tin: d.vendorTin || '' }; }).filter(d => d.decision !== null)
  };
  const btn = domReview.btnSubmitDecision;
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Submitting...'; }
  $('#modal-loading').show();
  $.ajax({
    url: base_url + 'transactions/approvals/api/submit_decisions', type: 'POST', contentType: 'application/json; charset=utf-8', dataType: 'json', data: JSON.stringify(payload),
    complete() { $('#modal-loading').hide(); },
    success(response) {
      if (response.status === 'success') { Swal.fire({ icon: 'success', title: 'Submitted!', text: 'Your decisions have been submitted successfully.', confirmButtonText: 'OK', confirmButtonColor: '#17663a' }).then(() => { window.location.href = base_url + 'transactions/approvals'; }); }
      else { Swal.fire({ icon: 'error', title: 'Error', text: response.response || 'Unknown error occurred.', confirmButtonText: 'OK', confirmButtonColor: '#e03131' }); if (btn) { btn.disabled = false; updateSubmitButtonState(); } }
    },
    error(xhr) {
      let msg = 'Server error during submission.';
      try { const resp = JSON.parse(xhr.responseText); if (resp.response) msg = resp.response; } catch (e) { }
      Swal.fire({ icon: 'error', title: 'Server Error', text: msg, confirmButtonText: 'OK', confirmButtonColor: '#e03131' });
      if (btn) { btn.disabled = false; updateSubmitButtonState(); }
    }
  });
};

/* ─── DATA LOADING ─── */
const loadReviewData = () => {
  const ref = domReview.approvalRef ? domReview.approvalRef.value : '';
  if (!ref) { if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = '<div class="alert alert-danger kna-small">No approval reference found.</div>'; return; }
  reviewState.referenceNo = ref;
  ajax_loader('transactions/approvals/api/get/details', { ReferenceNo: ref }).done(response => {
    const res = typeof response === 'string' ? $.parseJSON(response) : response;
    if (res.status !== 'success' || !res.data || !res.data.items || !Array.isArray(res.data.items)) { if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = '<div class="alert alert-danger kna-small">' + escapeHtml(res.response || 'Failed to load details.') + '</div>'; return; }
    const responseData = res.data, items = responseData.items || [], attachments = responseData.attachments || [];
    if (!items.length) { if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = '<div class="alert alert-danger kna-small">No data returned for this reference.</div>'; return; }

    const paymentCapability = responseData.payment_capability || null;
    const canAdvise = Boolean(paymentCapability && Number(paymentCapability.can_advise) === 1);
    const canRelease = Boolean(paymentCapability && Number(paymentCapability.can_release) === 1);
    reviewState.paymentCapability = paymentCapability;
    if (canAdvise || canRelease) {
      reviewState.isPastMode = true;
      if (domReview.decisionSummary) domReview.decisionSummary.classList.add('d-none');
    }

    reviewState.transactionType = items[0].transaction_type;
    if (['LIQUIDATION', 'CASH_ADVANCE', 'REIMBURSEMENT', 'REPLENISHMENT'].indexOf(reviewState.transactionType) === -1) {
      if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = '<div class="alert alert-danger kna-small">Unknown transaction type: ' + escapeHtml(reviewState.transactionType) + '</div>';
      return;
    }

    // Payable To options must be scoped to the REQUESTER's department
    // (items[0].user_id), only known now that details have loaded —
    // fetch before rendering so the picker's <option> list is ready.
    loadPayableToPickerOptions(items[0].user_id, () => {
      if (reviewState.transactionType === 'CASH_ADVANCE') renderCashAdvance(items, attachments);
      else if (reviewState.transactionType === 'LIQUIDATION') renderLiquidation(items, attachments);
      else if (reviewState.transactionType === 'REIMBURSEMENT') renderReimbursement(items, attachments);
      else if (reviewState.transactionType === 'REPLENISHMENT') renderReplenishment(items);
      renderReviewTimeline(); updateSummary();
      applyPaymentActionBar(canAdvise, canRelease, paymentCapability);
    });
  }).fail(() => { if (domReview.viewApprovalItems) domReview.viewApprovalItems.innerHTML = '<div class="alert alert-danger kna-small">Server error while fetching details.</div>'; });
};

/* ─── PAYMENT ADVISORY / RELEASE ─── */
const applyPaymentActionBar = (canAdvise, canRelease, paymentCapability) => {
  const bar = domReview.paymentActionBar;
  if (!bar) return;

  if (!canAdvise && !canRelease) {
    bar.classList.add('d-none');
    return;
  }

  bar.classList.remove('d-none');
  if (domReview.paymentActionStatus) domReview.paymentActionStatus.textContent = (paymentCapability && paymentCapability.status_name) || '—';

  const isRelease = canRelease;
  if (domReview.paymentActionRemarksLabel) domReview.paymentActionRemarksLabel.textContent = isRelease ? 'Release Remarks' : 'Advisory Remarks';
  if (domReview.paymentActionRemarks) domReview.paymentActionRemarks.placeholder = isRelease ? 'Optional notes about this payment release...' : 'Optional notes about this payment advisory...';
  if (domReview.btnPaymentAction) {
    domReview.btnPaymentAction.textContent = isRelease ? 'Release Payment' : 'Advise for Payment';
    domReview.btnPaymentAction.disabled = false;
  }
};

const submitPaymentAction = () => {
  const paymentCapability = reviewState.paymentCapability;
  if (!paymentCapability) return;

  const isRelease = Number(paymentCapability.can_release) === 1;
  const endpoint = isRelease ? 'transactions/approvals/api/payment/release' : 'transactions/approvals/api/payment/advise';
  const confirmVerb = isRelease ? 'release payment for' : 'advise payment for';

  Swal.fire({
    icon: 'question', title: 'Confirm', text: `Are you sure you want to ${confirmVerb} ${reviewState.referenceNo}?`,
    showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'No', reverseButtons: true
  }).then((result) => {
    if (!result.isConfirmed) return;

    const payload = {
      reference_no: reviewState.referenceNo,
      remarks: domReview.paymentActionRemarks ? domReview.paymentActionRemarks.value : ''
    };
    const btn = domReview.btnPaymentAction;
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...'; }
    $('#modal-loading').show();

    $.ajax({
      url: base_url + endpoint, type: 'POST', contentType: 'application/json; charset=utf-8', dataType: 'json', data: JSON.stringify(payload),
      complete() { $('#modal-loading').hide(); },
      success(response) {
        if (response.status === 'success') {
          Swal.fire({ icon: 'success', title: 'Done!', text: response.response || 'Payment action completed successfully.', confirmButtonText: 'OK', confirmButtonColor: '#17663a' })
            .then(() => { window.location.href = base_url + 'transactions/approvals'; });
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: response.response || 'Unknown error occurred.', confirmButtonText: 'OK', confirmButtonColor: '#e03131' });
          if (btn) { btn.disabled = false; applyPaymentActionBar(!isRelease && Boolean(paymentCapability.can_advise), isRelease, paymentCapability); }
        }
      },
      error(xhr) {
        let msg = 'Server error during submission.';
        try { const resp = JSON.parse(xhr.responseText); if (resp.response) msg = resp.response; } catch (e) { }
        Swal.fire({ icon: 'error', title: 'Server Error', text: msg, confirmButtonText: 'OK', confirmButtonColor: '#e03131' });
        if (btn) { btn.disabled = false; applyPaymentActionBar(!isRelease && Boolean(paymentCapability.can_advise), isRelease, paymentCapability); }
      }
    });
  });
};

const cacheReviewDom = () => {
  domReview.approvalRef = document.getElementById('approvalRef'); domReview.currentUserId = document.getElementById('currentUserId'); domReview.reviewMode = document.getElementById('reviewMode'); domReview.reviewTitle = document.getElementById('reviewTitle');
  domReview.reviewStatusBadge = document.getElementById('reviewStatusBadge'); domReview.reviewHeaderFields = document.getElementById('reviewHeaderFields'); domReview.viewApprovalItems = document.getElementById('viewApprovalItems');
  domReview.reviewTimeline = document.getElementById('reviewTimeline'); domReview.summaryReviewed = document.getElementById('summaryReviewed'); domReview.summaryApprovedAmount = document.getElementById('summaryApprovedAmount');
  domReview.summaryRejectedAmount = document.getElementById('summaryRejectedAmount'); domReview.reviewerRemarks = document.getElementById('reviewerRemarks'); domReview.btnSubmitDecision = document.getElementById('btnSubmitDecision');
  domReview.decisionSummary = document.getElementById('decisionSummary');
  domReview.paymentActionBar = document.getElementById('paymentActionBar'); domReview.paymentActionStatus = document.getElementById('paymentActionStatus');
  domReview.paymentActionRemarks = document.getElementById('paymentActionRemarks'); domReview.paymentActionRemarksLabel = document.getElementById('paymentActionRemarksLabel');
  domReview.btnPaymentAction = document.getElementById('btnPaymentAction');

  reviewState.isPastMode = (domReview.reviewMode ? domReview.reviewMode.value : '') === 'past';
  if (reviewState.isPastMode && domReview.decisionSummary) {
    domReview.decisionSummary.classList.add('d-none');
  }

  const lb = document.getElementById('knaLightbox');
  if (lb) { lb.addEventListener('click', e => { if (e.target === lb || e.target.id === 'knaLightboxClose') { lb.classList.add('d-none'); const img = document.getElementById('knaLightboxImg'); if (img) img.src = ''; } }); }
  if (domReview.btnSubmitDecision) domReview.btnSubmitDecision.addEventListener('click', submitDecisions);
  if (domReview.btnPaymentAction) domReview.btnPaymentAction.addEventListener('click', submitPaymentAction);
  initHistoryModal();
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

const handleReviewResize = () => {
  syncRowHeights();
  if (reviewState.transactionType === 'LIQUIDATION' || reviewState.transactionType === 'REIMBURSEMENT') initLiquidationCategorySelect2();
};

const initReviewPage = () => { cacheReviewDom(); loadReviewData(); bindDecisionEvents(); window.addEventListener('resize', handleReviewResize); };
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initReviewPage); else initReviewPage();