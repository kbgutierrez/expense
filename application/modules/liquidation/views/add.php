<style>
  /* Overlay for full image preview */
  .kna-image-overlay {
    display: none;
    position: fixed;
    z-index: 9999;
    left:0; top: 0;
    width: 100vw; height: 100vh;
    background: rgba(0, 0, 0, 0.55);
    align-items: center;
    justify-content: center;
    cursor: zoom-out;
  }
  .kna-image-overlay.active { display: flex; }
  .kna-image-overlay img {
    max-width: 90vw; max-height: 90vh;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
    background: #fff; padding: 8px;
  }

  /* ===== COMPACT EXPENSE ITEM ROWS ===== */
  .kna-item-rows { display: flex; flex-direction: column; gap: 6px; }
  .kna-item-row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    background: #f8f9fc;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 8px;
  }
  .kna-item-row-index {
    flex: 0 0 auto;
    width: 18px;
    padding-top: 6px;
    font-size: 10px;
    font-weight: 700;
    color: #9ca3af;
    text-align: center;
  }
  .kna-item-row-fields {
    flex: 1 1 auto;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-width: 0;
  }
  .kna-item-field {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1 1 100px;
    min-width: 90px;
  }
  .kna-item-field-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .2px;
    color: #9ca3af;
  }
  .kna-item-field .form-control { font-size: 10px; padding: 4px 8px; height: 28px; }
  .kna-f-type { flex: 2 1 180px; }
  .kna-f-amount { flex: 1 1 90px; }
  .kna-f-vat { flex: 0 0 auto; min-width: 40px; }
  .kna-f-vendor { flex: 1 1 130px; }
  .kna-f-tin { flex: 1 1 100px; }
  .kna-f-attach { flex: 1 1 150px; }
  .kna-f-remarks { flex: 1 1 150px; }
  .kna-vat-wrap {
    display: inline-flex;
    align-items: center;
    height: 28px;
    margin: 0;
    cursor: pointer;
  }
  .kna-vat-input { width: 14px; height: 14px; margin: 0; accent-color: var(--brand-700); }
  .kna-item-attach-inline {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
  }
  .kna-item-attach-inline .kna-attachment-cell { font-size: 10px; font-weight: 600; display: inline-block; max-width: 130px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; vertical-align: middle; }
  .kna-item-attach-inline .btn {
    font-size: 10px;
    padding: 3px 8px;
    height: 24px;
    line-height: 1;
  }
  .kna-item-row-remove {
    flex: 0 0 auto;
    margin-top: 4px;
    width: 26px; height: 26px;
    border-radius: 6px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 11px;
  }
  .kna-item-row-remove:hover { background: #fee2e2; border-color: #ef4444; }

  .kna-ocr-status {
    font-size: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    line-height: 1.3;
  }
  .kna-ocr-scanning { color: #2563eb; }
  .kna-ocr-success { color: #059669; }
  .kna-ocr-error { color: #dc2626; }
  .kna-ocr-manual { color: #6b7280; }
  .kna-ocr-status i { font-size: 10px; width: 12px; text-align: center; }
  .kna-ocr-manual-btn {
    background: none; border: none;
    color: var(--brand-700);
    font-size: 10px; font-weight: 600;
    cursor: pointer; padding: 0;
    text-decoration: underline; line-height: 1;
  }
  .kna-ocr-manual-btn:hover { color: var(--brand-800); }

  /* ===== GLOBAL ===== */
  * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
  body { background: linear-gradient(135deg, #f0f4f8 0%, #f8f9fc 100%); }
  .kna-page {
    padding: 12px 14px;
    background: transparent;
    min-height: 100vh;
  }
  .kna-header {
    background: linear-gradient(135deg, var(--brand-700) 0%, var(--brand-500) 100%);
    color: white;
    padding: 22px 14px;
    box-shadow: 0 2px 10px rgba(var(--brand-rgb), .10);
  }
  .kna-header-content { max-width: 1100px; margin: 0 auto; }
  .kna-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px 0;
    line-height: 1.2;
  }
  .kna-title i { font-size: 18px; opacity: 0.95; }
  .kna-subtitle {
    font-size: 10px;
    opacity: 0.85;
    margin-top: 4px;
    font-weight: 400;
  }
  .kna-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 16px 8px;
  }
  .kna-card {
    border: 1px solid #d9e0e7 !important;
    border-radius: 6px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(20, 30, 50, .05);
  }
  .kna-card .card-body { padding: .85rem; }
  .kna-small { font-size: 10px !important; line-height: 1.35; }
  .kna-form-label {
    margin-bottom: .3rem;
    font-weight: 600;
    font-size: 10px;
  }
  .form-control, .form-control-sm {
    font-size: 10px;
    border-radius: 4px;
    padding: 6px 10px;
    height: 32px;
  }
  textarea.form-control { min-height: 48px; font-size: 10px; padding: 6px 10px; }
  .btn {
    border-radius: 4px;
    font-size: 10px;
    padding: 6px 14px;
  }
  .btn-primary { background: var(--brand-700); color: #fff; border: none; }
  .btn-outline-secondary {
    border: 1px solid #d1d5db;
    color: #6b7280;
    background: transparent;
  }
  .btn-outline-secondary:hover { background: #f3f4f6; border-color: #9ca3af; }
  .kna-section-title {
    font-size: 12px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 10px;
    margin-top: 0;
    padding-bottom: 4px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .kna-expense-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 8px 0;
    border-top: 2px solid #e5e7eb;
    border-bottom: 2px solid #e5e7eb;
  }
  .kna-expense-section-title {
    font-size: 13px;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
    border: none;
  }
  .form-row {
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 8px;
  }
  .form-group { margin-bottom: 0; }
  .kna-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
  }
  hr { border: 0; border-top: 1px solid #f3f4f6; margin: 12px 0; }
  .kna-header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
  }
  .kna-header-actions .btn { font-size: 10px; padding: 4px 10px; border-radius: 4px; }
  .mb-4 { margin-bottom: 12px; }
  .mr-1 { margin-right: 4px; }
  .d-none { display: none; }
  .d-flex { display: flex; }
  .gap-2 { gap: 6px; }
  .ml-2 { margin-left: 6px; }
  .kna-info-row {
    display: grid;
    gap: 8px;
    margin-bottom: 10px;
  }
  .kna-info-row-3 { grid-template-columns: repeat(3, 1fr); }
  .kna-desktop-info .kna-info-row {
    gap: 6px;
    margin-bottom: 6px;
  }
  .kna-desktop-info .kna-form-label {
    margin-bottom: .2rem;
  }
  .kna-compact-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .kna-compact-value {
    min-height: 30px;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    background: #fff;
    font-size: 11px;
    font-weight: 500;
    color: #1f2937;
    display: flex;
    align-items: center;
  }
  .kna-compact-value.is-muted {
    background: #f8fafc;
  }
  .kna-compact-value .kna-var-badge {
    font-size: 11px;
    padding: 2px 8px;
  }
  .kna-fin-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-left: 3px solid var(--brand-700);
    border-radius: 6px;
    padding: 10px 12px;
  }
  .kna-fin-card.ca { border-left-color: #3b82f6; }
  .kna-fin-card.liq { border-left-color: #0f766e; }
  .kna-fin-card.var { border-left-color: #f59e0b; }
  .kna-fin-label {
    font-size: 10px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 4px;
  }
  .kna-fin-value {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.3;
  }
  .kna-var-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
  }
  .kna-var-balanced { background: #d1fae5; color: #065f46; }
  .kna-var-return { background: #fef3c7; color: #92400e; }
  .kna-var-reimburse { background: #dbeafe; color: #1e40af; }
  .kna-summary-note { display: none; margin-top: 10px; padding: 10px 12px; border: 1px solid #d1fae5; border-radius: 8px; background: #f0fdf4; }
  .kna-summary-note .kna-fin-label { margin-bottom: 2px; }

  /* ===== MOBILE COMPACT OVERVIEW (Hidden on desktop) ===== */
  .kna-mobile-overview { display: none; }

  .kna-mobile-add-item { display: none; width: 100%; margin-top: 10px; }

  /* ===== MOBILE VENDOR SECTION ===== */
  .kna-mobile-vendor-section {
    margin-top: 10px;
  }
  .kna-mobile-vendor-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    padding: 0;
    background: transparent;
  }

  /* ===== MOBILE COMPACT OVERVIEW ===== */
  @media (max-width: 768px) {
    .kna-desktop-info { display: none !important; }
    .kna-mobile-overview { display: block; }

    .kna-mobile-hero {
      background: linear-gradient(135deg, var(--brand-700) 0%, var(--brand-500) 100%);
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 10px;
      color: #fff;
      box-shadow: 0 4px 12px rgba(var(--brand-rgb), 0.25);
    }
    .kna-mobile-hero-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 10px;
    }
    .kna-mobile-hero-id {
      font-size: 13px;
      font-weight: 700;
      opacity: 0.95;
      letter-spacing: 0.3px;
    }
    .kna-mobile-hero-id span {
      opacity: 0.7;
      font-weight: 500;
      font-size: 11px;
      display: block;
      margin-top: 2px;
    }
    .kna-mobile-hero-status {
      font-size: 10px;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(4px);
      white-space: nowrap;
    }
    .kna-mobile-hero-amounts {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 8px;
    }
    .kna-mobile-hero-amt {
      text-align: center;
      background: rgba(255,255,255,0.12);
      border-radius: 8px;
      padding: 8px 4px;
    }
    .kna-mobile-hero-amt-label {
      font-size: 9px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      opacity: 0.75;
      margin-bottom: 2px;
    }
    .kna-mobile-hero-amt-value { font-size: 14px; font-weight: 700; }
    .kna-mobile-hero-amt-value.small { font-size: 12px; }

    .kna-info-section-mobile { display: block; margin-bottom: 10px; }
    .kna-info-grid-mobile {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 8px;
    }
    .kna-info-item-mobile {
      background: #fff;
      border-radius: 10px;
      padding: 10px 12px;
      border: 1px solid #e5e7eb;
      border-left: 3px solid var(--brand-700);
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .kna-info-item-mobile.ca-ref { border-left-color: #3b82f6; }
    .kna-info-item-mobile.ca-amt { border-left-color: #0f766e; }
    .kna-info-item-mobile.ca-date { border-left-color: var(--brand-500); }
    .kna-info-item-mobile.range { border-left-color: #f59e0b; }
    .kna-info-item-mobile.total { border-left-color: #059669; }
    .kna-info-item-mobile.variance { border-left-color: #ef4444; }
    .kna-info-item-mobile.payable { border-left-color: #ec4899; }
    .kna-info-item-mobile.address { border-left-color: #14b8a6; }
    .kna-info-item-mobile.costcenter { border-left-color: #f97316; }
    .kna-info-item-mobile.status { border-left-color: var(--brand-700); }
    .kna-info-item-mobile.submitted { border-left-color: #64748b; }
    .kna-info-item-mobile.expense-period { border-left-color: var(--accent-600); }
    .kna-info-item-mobile.purpose { border-left-color: var(--brand-700); }
    .kna-info-label-mobile {
      font-size: 9px;
      font-weight: 700;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-bottom: 3px;
    }
    .kna-info-value-mobile {
      font-size: 12px;
      font-weight: 600;
      color: #1f2937;
      line-height: 1.3;
      word-break: break-word;
    }
    .kna-info-value-mobile .kna-var-badge { font-size: 10px; padding: 1px 8px; }
    .kna-info-item-mobile.full-width { grid-column: 1 / -1; }
    .kna-info-purpose-mobile {
      grid-column: 1 / -1;
      background: #fff;
      border-radius: 10px;
      padding: 10px 12px;
      border: 1px solid #e5e7eb;
      border-left: 3px solid var(--brand-700);
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .kna-info-purpose-mobile .kna-info-value-mobile {
      font-size: 12px;
      color: #374151;
      font-weight: 500;
      line-height: 1.4;
    }
    .kna-section-title { font-size: 13px; margin-bottom: 10px; padding-bottom: 6px; }
    .kna-expense-section-header {
      margin-top: 8px;
      padding: 8px 0;
      border-top-width: 1px;
      border-bottom-width: 1px;
    }
    .kna-expense-section-title { font-size: 13px; }

    .kna-header { padding: 14px 6px; }
    .kna-container { padding: 8px 4px; }
    .kna-title { font-size: 14px; gap: 4px; }
    .kna-title i { font-size: 14px; }
    .kna-card .card-body { padding: 8px 4px; }
    .kna-line-card { padding: 6px 4px; }
    .form-row { grid-template-columns: repeat(2, 1fr); gap: 4px; }
    .kna-actions { display: none; }
    .kna-actions .btn { width: 100%; }
    .kna-info-row-3 { grid-template-columns: 1fr; }
    .kna-summary-note { display: block; }
    .kna-item-field { flex: 1 1 45%; }
    .kna-mobile-add-item { display: block; }
    .kna-mobile-sticky-actions {
      display: flex;
      gap: 8px;
      position: fixed;
      bottom: 0; left: 0; right: 0;
      background: #fff;
      border-top: 1px solid #e5e7eb;
      padding: 10px 14px;
      box-shadow: 0 -4px 12px rgba(0,0,0,0.06);
      z-index: 1030;
    }
    .kna-mobile-sticky-actions .btn {
      flex: 1;
      font-size: 12px;
      padding: 10px 8px;
      min-height: 44px;
      border-radius: 8px;
    }
    .kna-mobile-sticky-actions .btn-outline-secondary {
      flex: 0 0 auto;
      padding: 10px 14px;
    }
    body { padding-bottom: 72px; }
    .kna-info-section-mobile {
      background: transparent;
      border-radius: 0;
      padding: 0;
      margin-bottom: 10px;
    }
    .form-control, .form-control-sm {
      min-height: 40px;
      font-size: 12px;
    }
    select.form-control { height: 40px; }
  }

  @media (max-width: 480px) {
    .kna-title { font-size: 12px; }
    .kna-section-title { font-size: 10px; }
    .form-row { grid-template-columns: 1fr; }
    .kna-card .card-body { padding: 4px 2px; }
    .kna-info-grid-mobile { grid-template-columns: 1fr; gap: 6px; }
    .kna-info-item-mobile { padding: 8px 10px; }
    .kna-info-value-mobile { font-size: 12px; }
    .kna-item-field { flex: 1 1 100%; }
    .kna-mobile-hero-amounts { grid-template-columns: 1fr 1fr; }
    .kna-mobile-hero-amt:nth-child(3) { grid-column: 1 / -1; }
  }
  @media (max-width: 768px) {
    .kna-page > .d-flex.mb-2 { margin-bottom: 12px !important; }
    .kna-page > .d-flex.mb-2 .kna-small { display: none; }
    .kna-page > .d-flex.mb-2 .kna-title { font-size: 18px; font-weight: 700; color: #1f2937; }
    .kna-page > .d-flex.mb-2 .btn-outline-secondary {
      width: 38px; height: 38px; padding: 0; border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 0; border-color: #e5e7eb; background: #fff;
    }
    .kna-page > .d-flex.mb-2 .btn-outline-secondary i { font-size: 15px; margin: 0 !important; }
    .kna-card { background: transparent; border: 0; box-shadow: none; }
    .kna-card .card-body { padding: 0 !important; }
    #formNewLiquidation > hr,
    .kna-expense-section-header { display: none !important; }
    .lqm-receipt-has .kna-attachment-cell { min-width: 0; overflow: hidden; }
    .lqm-sheet-body .kna-ocr-status { margin-top: 8px; font-size: 12px; }
    .kna-mobile-sticky-actions,
    .kna-mobile-sticky-actions.d-none { display: flex !important; align-items: center; padding: 10px 14px calc(12px + env(safe-area-inset-bottom)) !important; }
    .kna-page { padding-bottom: 96px; }
    .lqm-sticky-total { flex: 1 1 auto; line-height: 1.2; }
    .lqm-sticky-total span { display: block; font-size: 11px; color: #6b7280; }
    .lqm-sticky-total b { font-size: 16px; color: #1f2937; white-space: nowrap; }
    .kna-mobile-sticky-actions .lqm-btn-ghost { flex: 0 0 auto !important; background: transparent; color: #4b5563; font-weight: 600; font-size: 13px !important; padding: 10px 8px !important; }
    .kna-mobile-sticky-actions .lqm-btn-submit { flex: 0 0 auto !important; min-width: 120px; border-radius: 12px !important; font-size: 14px !important; font-weight: 600; }
  }

    @media (max-width: 768px) {
  .kna-info-row-3,
  .kna-info-row-4 {
    grid-template-columns: 1fr;
  }
}
</style>
<div class="page-inner kna-page">
  <input type="hidden" id="liquidationRef"
    value="<?= isset($liquidation_no) ? htmlspecialchars($liquidation_no, ENT_QUOTES, 'UTF-8') : ''; ?>">
  <input type="hidden" id="draftEditWindowDays"
    value="<?= isset($draft_edit_window_days) ? (int) $draft_edit_window_days : 7; ?>">
  <input type="hidden" id="isEditMode" value="<?= !empty($is_edit_mode) ? '1' : '0'; ?>">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="kna-title"><?= !empty($is_edit_mode) ? 'Edit Draft Liquidation' : 'New Liquidation'; ?></div>
      <div class="kna-small text-muted">
        <?= !empty($is_edit_mode) ? 'Update your draft and submit when ready' : 'Submit expense details with receipt documentation'; ?>
      </div>
    </div>
    <a href="<?= base_url('transactions/liquidation'); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
  </div>
  <div class="card kna-card">
    <div class="card-body">
      <form id="formNewLiquidation" autocomplete="off">
        <!-- Desktop: Full form rows -->
        <div class="kna-desktop-info">
          <div class="kna-section-title">
            <i class="fas fa-info-circle"></i>
            Liquidation Information
          </div>
          <!-- Row 1: Identifiers & Dates -->
          <div class="kna-info-row kna-info-row-3">
            <div class="form-group">
              <label class="kna-form-label">Reference</label>
              <select class="form-control form-control-sm kna-small" id="newCaRef" required>
                <option value="">Select cash advance</option>
              </select>
            </div>
            <div class="form-group">
              <label class="kna-form-label">CA Date</label>
              <input type="text" class="form-control form-control-sm kna-small" id="newCaDate" readonly
                style="background: #f0f4f8;" placeholder="-">
            </div>
            <div class="form-group kna-compact-field">
              <label class="kna-form-label">Expense Range</label>
              <input type="text" class="form-control form-control-sm kna-small" id="newDateRange"
                placeholder="Auto based on document dates" required readonly style="background: #f0f4f8;">
            </div>
          </div>
          <!-- Row 2: Payable To, Address, Cost Center -->
          <div class="kna-info-row kna-info-row-3">
            <div class="form-group">
              <label class="kna-form-label">Payable To</label>
              <input type="text" class="form-control form-control-sm kna-small" id="newPayableTo" readonly
                style="background: #f0f4f8;" placeholder="-">
            </div>
            <div class="form-group">
              <label class="kna-form-label">Address</label>
              <input type="text" class="form-control form-control-sm kna-small" id="newAddress" readonly
                style="background: #f0f4f8;" placeholder="-">
            </div>
            <div class="form-group">
              <label class="kna-form-label">Cost Center</label>
              <input type="text" class="form-control form-control-sm kna-small" id="newCostCenter" readonly
                style="background: #f0f4f8;" placeholder="-">
            </div>
          </div>
          <!-- Row 3: Financial summary -->
          <div class="kna-info-row kna-info-row-4" style="grid-template-columns: repeat(4, 1fr);">
            <div class="form-group">
              <label class="kna-form-label">CA Amount</label>
              <input type="number" class="form-control form-control-sm kna-small" id="newCaAmount" min="0" step="0.01"
                value="0" readonly style="background: #f0f4f8;">
            </div>
            <div class="form-group">
              <label class="kna-form-label">Approved Amount</label>
              <input type="number" class="form-control form-control-sm kna-small" id="newApprovedAmount" min="0"
                step="0.01" value="0" readonly style="background: #f0f4f8;">
            </div>
            <div class="form-group kna-compact-field">
              <label class="kna-form-label">Total Amount</label>
              <div class="kna-compact-value is-muted" id="newLiquidatedAmount">0.00</div>
            </div>
            <div class="form-group kna-compact-field">
              <label class="kna-form-label">Variance</label>
              <div class="kna-compact-value is-muted" id="newVariance">-</div>
            </div>
          </div>
          <div class="form-group" style="margin-bottom: 12px;">
            <label class="kna-form-label">Purpose / Notes</label>
            <textarea class="form-control form-control-sm kna-small" id="newPurpose" placeholder="" readonly
              style="background: #f0f4f8; min-height: 48px;"></textarea>
          </div>
        </div>

        <!-- Mobile: Compact Hero + Grid (hidden on desktop) -->
        <div class="kna-mobile-overview">
          <div class="lqm-ca">
            <div class="lqm-ca-top">
              <div class="lqm-ca-id">
                <div class="lqm-label">Cash Advance</div>
                <div class="lqm-ca-ref" id="mobileCaRef">-</div>
              </div>
              <label class="lqm-ca-change d-none" id="mobileCaChange">
                Change
                <select id="mobileCaSelect" aria-label="Select cash advance"></select>
              </label>
            </div>
            <div class="lqm-ca-purpose" id="mobilePurpose">-</div>
            <div class="lqm-ca-balance">
              <div class="lqm-ca-row">
                <span>Approved</span>
                <b id="mobileApprovedAmount">-</b>
              </div>
              <div class="lqm-progress"><i id="mobileProgressBar"></i></div>
              <div class="lqm-ca-row lqm-ca-row-muted">
                <span>Liquidated <span id="mobileTotal">-</span></span>
                <span id="mobileLeft"></span>
              </div>
              <span class="lqm-variance d-none" id="mobileVariance">-</span>
            </div>
            <details class="lqm-ca-details">
              <summary>Cash advance details</summary>
              <div class="lqm-ca-grid">
                <div><span class="lqm-label">CA Amount</span><span id="mobileCaAmount">-</span></div>
                <div><span class="lqm-label">CA Date</span><span id="mobileCaDate">-</span></div>
                <div><span class="lqm-label">Expense Range</span><span id="mobileDateRange">-</span></div>
                <div><span class="lqm-label">Payable To</span><span id="mobilePayableTo">-</span></div>
                <div class="lqm-full"><span class="lqm-label">Cost Center</span><span id="mobileCostCenter">-</span></div>
                <div class="lqm-full"><span class="lqm-label">Address</span><span id="mobileAddress">-</span></div>
              </div>
            </details>
          </div>
        </div>

        <hr />
        <div class="kna-expense-section-header">
          <div class="kna-expense-section-title">
            <i class="fas fa-receipt"></i>
            Expense Items
          </div>
          <button type="button" class="btn btn-outline-secondary" id="btnAddExpenseItem" style="white-space: nowrap;">
            <i class="fas fa-plus mr-1"></i> Add Item
          </button>
        </div>
        <div id="expenseItemsContainer"></div>
        <div id="knaImageOverlay" class="kna-image-overlay"><img src="" alt="Full Preview" /></div>
        <hr />
        <div class="kna-actions">
          <a href="<?= base_url('transactions/liquidation'); ?>" class="btn btn-outline-secondary">Cancel</a>
          <button type="button" class="btn btn-outline-primary" id="btnSaveDraftLiquidation">
            <i class="fas fa-save mr-1"></i> Save Draft
          </button>
          <button type="button" class="btn btn-primary" id="btnSaveNewLiquidation">
            <i class="fas fa-check mr-1"></i> <?= !empty($is_edit_mode) ? 'Update & Submit' : 'Submit Liquidation'; ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Mobile sticky actions -->
  <div class="kna-mobile-sticky-actions d-none">
    <div class="lqm-sticky-total">
      <span>Total</span>
      <b id="mobileStickyTotal">₱0.00</b>
    </div>
    <button type="button" class="btn lqm-btn-ghost" id="btnSaveDraftLiquidationMobile">Save draft</button>
    <button type="button" class="btn btn-primary lqm-btn-submit" id="btnSaveNewLiquidationMobile">
      <?= !empty($is_edit_mode) ? 'Update &amp; Submit' : 'Submit'; ?>
    </button>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const overlay = document.getElementById('knaImageOverlay');
      if (overlay) {
        overlay.addEventListener('click', function () {
          overlay.classList.remove('active');
          overlay.querySelector('img').src = '';
        });
      }
      document.body.addEventListener('click', function (e) {
        const preview = e.target.closest('.kna-receipt-preview[data-img-url]');
        if (preview) {
          const url = preview.getAttribute('data-img-url');
          if (overlay && url) {
            overlay.querySelector('img').src = url;
            overlay.classList.add('active');
          }
          e.preventDefault();
        }
      });
    });
  </script>
</div>