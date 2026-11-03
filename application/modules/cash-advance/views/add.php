<style>
  * {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }

  body {
    background: linear-gradient(135deg, #f0f4f8 0%, #f8f9fc 100%);
  }

  .kna-page {
    padding: 12px 14px;
    background: transparent;
    min-height: 100vh;
  }

  .kna-title {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 8px 0;
    line-height: 1.2;
  }

  .kna-small {
    font-size: 12px !important;
    line-height: 1.35;
  }

  .kna-card {
    border: 1px solid #d9e0e7 !important;
    border-radius: 6px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgba(20, 30, 50, .05);
  }

  .kna-card .card-body {
    padding: .85rem;
  }

  .kna-form-label {
    margin-bottom: .3rem;
    font-weight: 600;
    font-size: 12px;
  }

  .form-control,
  .form-control-sm {
    font-size: 12px;
    border-radius: 4px;
    padding: 6px 10px;
    height: 32px;
  }

  textarea.form-control {
    min-height: 48px;
    font-size: 12px;
    padding: 6px 10px;
  }

  .btn {
    border-radius: 4px;
    font-size: 12px;
    padding: 6px 14px;
  }

  .btn-primary {
    background: var(--brand-700);
    color: #fff;
    border: none;
  }

  .btn-outline-secondary {
    border: 1px solid #d1d5db;
    color: #6b7280;
    background: transparent;
  }

  .kna-section-title {
    font-size: 14px;
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

  .kna-info-row {
    display: grid;
    gap: 8px;
    margin-bottom: 10px;
  }

  .kna-info-row-3 {
    grid-template-columns: repeat(3, 1fr);
  }

  .kna-fin-card {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-left: 3px solid var(--brand-700);
    border-radius: 6px;
    padding: 10px 12px;
  }

  .kna-fin-card.ca {
    border-left-color: #3b82f6;
  }

  .kna-fin-label {
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 4px;
  }

  .kna-fin-value {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.3;
  }

  .kna-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
  }

  hr {
    border: 0;
    border-top: 1px solid #f3f4f6;
    margin: 12px 0;
  }

  @media (max-width: 768px) {
    .kna-info-row-3 {
      grid-template-columns: 1fr;
    }
  }
  .select2-container--default .select2-selection--single {
    height:32px;
    border-radius:4px;
    border:1px solid #ced4da;
}

.select2-container--default 
.select2-selection--single 
.select2-selection__rendered {
    line-height:30px;
    font-size:12px;
}

.select2-container--default 
.select2-selection--single 
.select2-selection__arrow {
    height:30px;
}
</style>

<div class="page-inner kna-page">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="kna-title">New Cash Advance</div>
      <div class="kna-small text-muted">Submit a new cash advance request</div>
    </div>
    <a href="<?= base_url('transactions/cash-advance'); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-arrow-left mr-1"></i> Back
    </a>
  </div>

  <div class="card kna-card">
    <div class="card-body">
      <form id="formNewCashAdvance" autocomplete="off" enctype="multipart/form-data">
        <div class="kna-section-title">
          <i class="fas fa-info-circle"></i>
          Cash Advance Information
        </div>

        <!-- Row 1: Payable To + Date + Cost Center -->
        <div class="kna-info-row kna-info-row-3">
          <div class="form-group">
            <label class="kna-form-label">Payable To <span class="text-danger">*</span></label>
            <select class="form-control form-control-sm select2" id="newPayableTo" required>
              <option value="">Loading...</option>
            </select>
          </div>
          <div class="form-group">
            <label class="kna-form-label">Date</label>
            <input type="date" class="form-control form-control-sm" id="newDate" readonly>
          </div>
          <div class="form-group">
            <label class="kna-form-label">Cost Center <span class="text-danger">*</span></label>
            <select class="form-control form-control-sm select2" id="newCostCenter" required>
              <option value="">Select</option>
              <?php foreach ($cost_centers as $cc): ?>
                <option value="<?= $cc['cost_center_code']; ?>"><?= html_escape($cc['cost_center_code'] . ' - ' . $cc['cost_center_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Row 2: Address -->
        <div class="form-group" style="margin-bottom: 10px;">
          <label class="kna-form-label">Address <span class="text-danger">*</span></label>
          <textarea class="form-control form-control-sm" id="newAddress" rows="2" placeholder="Street, City, Province"
            required></textarea>
        </div>

        <!-- Row 3: Amount + Amount in Words + Date Needed -->
        <div class="kna-info-row kna-info-row-3">
          <div class="form-group">
            <label class="kna-form-label">Amount (PHP) <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-sm" id="newAmount" min="1" step="0.01"
              placeholder="0.00" required>
          </div>
          <div class="form-group">
            <label class="kna-form-label">Amount in Words</label>
            <input type="text" class="form-control form-control-sm" id="newAmountWords" readonly
              placeholder="Auto-generated">
          </div>
          <div class="form-group">
            <label class="kna-form-label">Date Needed <span class="text-danger">*</span></label>
            <input type="date" class="form-control form-control-sm" id="newNeededDate" required>
          </div>
        </div>

        <!-- IO Number -->
        <div class="form-group col-4" style="margin-bottom: 10px;">
          <label class="kna-form-label">IO Number</label>
          <input type="text" class="form-control form-control-sm" id="newIONumber" placeholder="Optional">
        </div>

        <!-- Row 4: Purpose -->
        <div class="form-group" style="margin-bottom: 12px;">
          <label class="kna-form-label">Purpose / Justification <span class="text-danger">*</span></label>
          <textarea class="form-control form-control-sm" id="newPurpose" rows="3"
            placeholder="Provide the reason for this cash advance request." required></textarea>
        </div>

        <!-- Row 5: Attachments -->
        <div class="form-group" style="margin-bottom: 12px;">
          <label class="kna-form-label">Attachments</label>
          <input type="file" class="form-control form-control-sm" id="newAttachments" multiple
            accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx">
          <div id="attachmentList" class="kna-small text-muted mt-1"></div>
        </div>

        <hr />

        <div class="kna-actions">
          <a href="<?= base_url('transactions/cash-advance'); ?>" class="btn btn-outline-secondary">Cancel</a>
          <button type="button" class="btn btn-primary" id="btnSaveNewCashAdvance">
            <i class="fas fa-check mr-1"></i> Submit Request
          </button>
        </div>
      </form>
    </div>
  </div>
</div>