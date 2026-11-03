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

  .kna-readonly {
    min-height: 32px;
    padding: 6px 10px;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    font-size: 12px;
    color: #1f2937;
    display: flex;
    align-items: center;
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

  .kna-section-title-row { justify-content: space-between; }
  .kna-section-title-label { display: flex; align-items: center; gap: 6px; }

  .kna-history-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid #d9e0e7;
    background: #fff;
    color: #4b5563;
    cursor: pointer;
    font-size: 12px;
    flex: 0 0 auto;
    transition: background .15s, color .15s, border-color .15s;
  }
  .kna-history-btn:hover { background: var(--brand-100); border-color: var(--brand-700); color: var(--brand-700); }

  .kna-history-modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, .5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .kna-history-modal-overlay.d-none { display: none !important; }
  .kna-history-modal {
    background: #fff;
    border-radius: 8px;
    width: 100%;
    max-width: 480px;
    max-height: 82vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 12px 48px rgba(0, 0, 0, .25);
  }
  .kna-history-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
  }
  .kna-history-modal-title {
    font-size: 14px;
    font-weight: 700;
    color: #1a202c;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .kna-history-modal-close {
    background: none;
    border: none;
    font-size: 16px;
    color: #6b7280;
    cursor: pointer;
    line-height: 1;
    padding: 4px;
  }
  .kna-history-modal-close:hover { color: #1f2937; }
  .kna-history-modal-body { padding: 16px; overflow-y: auto; }

  .form-group {
    margin-bottom: 0;
  }

  .btn {
    border-radius: 4px;
    font-size: 12px;
    padding: 6px 14px;
  }

  .btn-outline-secondary {
    border: 1px solid #d1d5db;
    color: #6b7280;
    background: transparent;
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

  .kna-fin-card.amt {
    border-left-color: #0f766e;
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

  hr {
    border: 0;
    border-top: 1px solid #f3f4f6;
    margin: 12px 0;
  }

  /* Timeline */
  .kna-timeline {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0;
  }

  .kna-timeline-item {
    position: relative;
    padding: 12px 0 12px 24px;
    border-left: 2px solid #e5e7eb;
    font-size: 12px;
  }

  .kna-timeline-item:last-child {
    padding-bottom: 0;
  }

  .kna-timeline-item::before {
    content: '';
    position: absolute;
    left: -7px;
    top: 14px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    background: #d1d5db;
    box-shadow: 0 0 0 2px #e5e7eb;
  }

  .kna-timeline-item.is-done {
    border-left-color: #22c55e;
  }

  .kna-timeline-item.is-done::before {
    background: #22c55e;
    box-shadow: 0 0 0 2px #dcfce7;
  }

  .kna-timeline-item.is-current {
    border-left-color: var(--accent-600);
  }

  .kna-timeline-item.is-current::before {
    background: var(--accent-600);
    box-shadow: 0 0 0 2px var(--accent-200);
  }

  .kna-timeline-item.is-pending {
    border-left-color: #d1d5db;
  }

  .kna-timeline-item.is-pending::before {
    background: #d1d5db;
    box-shadow: 0 0 0 2px #f3f4f6;
  }

  .kna-timeline-item-top {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
  }

  .kna-timeline-item-name {
    font-weight: 700;
    color: #1f2937;
    font-size: 12px;
  }

  .kna-timeline-item-remarks {
    color: #4b5563;
    font-size: 12px;
    line-height: 1.5;
    word-break: break-word;
  }

  .kna-doc-panel {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #f8fafc;
    padding: 10px;
    margin-bottom: 12px;
  }

  .kna-doc-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
  }

  .kna-doc-state {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #334155;
  }

  .kna-doc-frame {
    width: 100%;
    height: 600px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
  }

  .kna-doc-empty {
    font-size: 12px;
    color: #6b7280;
    padding: 12px;
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    background: #fff;
  }

  /* Attachments */
  .kna-attachment-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .kna-attachment-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    transition: background 0.15s ease, box-shadow 0.15s ease;
  }

  .kna-attachment-item:hover {
    background: #f8fafc;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  }

  /* Attachment thumbnail styles */
  .kna-attachment-thumb-wrap {
    width: 48px;
    height: 48px;
    border-radius: 6px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    flex-shrink: 0;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .kna-attachment-thumb-wrap:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    border-color: var(--brand-200);
  }

  .kna-attachment-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
  }

  .kna-attachment-icon {
    width: 48px;
    height: 48px;
    border-radius: 6px;
    background: var(--brand-100);
    color: var(--brand-700);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
  }

  .kna-attachment-icon.img {
    background: #f0fdf4;
    color: #16a34a;
  }

  .kna-attachment-icon.pdf {
    background: #fef2f2;
    color: #dc2626;
  }

  .kna-attachment-icon.doc {
    background: #eff6ff;
    color: #2563eb;
  }

  .kna-attachment-info {
    flex: 1;
    min-width: 0;
  }

  .kna-attachment-name {
    font-size: 12px;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .kna-attachment-meta {
    font-size: 11px;
    color: #6b7280;
  }

  .kna-attachment-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
  }

  .kna-attachment-actions .btn {
    padding: 4px 8px;
    font-size: 11px;
  }

  @media (max-width: 768px) {
    .kna-info-row-3 {
      grid-template-columns: 1fr;
    }

    .kna-doc-frame {
      height: 460px;
    }
  }

  /* Image Preview Modal */
  .kna-preview-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    padding: 20px;
  }

  .kna-preview-modal.active {
    opacity: 1;
    visibility: visible;
  }

  .kna-preview-modal-inner {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .kna-preview-modal-img {
    max-width: 100%;
    max-height: 80vh;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    object-fit: contain;
  }

  .kna-preview-modal-caption {
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    max-width: 80vw;
    word-break: break-word;
  }

  .kna-preview-modal-close {
    position: absolute;
    top: -44px;
    right: 0;
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s ease;
  }

  .kna-preview-modal-close:hover {
    background: rgba(255,255,255,0.3);
  }
  @media (max-width: 768px) {
  .kna-info-row-3,
  .kna-info-row-4 {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="page-inner kna-page">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <div class="kna-title">Cash Advance Details</div>
    </div>
    <div class="d-flex align-items-center" style="gap:.5rem;">
      <button type="button" class="btn btn-outline-danger d-none" id="btnCancelCashAdvance">
        <i class="fas fa-ban mr-1"></i> Cancel Request
      </button>
      <a href="<?=base_url('transactions/cash-advance');?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back
      </a>
    </div>
  </div>

  <div class="card kna-card">
    <div class="card-body">
      <input type="hidden" id="cashAdvanceRef" value="<?=html_escape($cash_advance_no);?>">
      <input type="hidden" id="currentUserId" value="<?=isset($current_user_id) ? (int) $current_user_id : 0;?>">
      <input type="hidden" id="serverKflowUrl" value="<?=html_escape(isset($resume_kflow_url) ? $resume_kflow_url : '');?>">

      <div class="kna-section-title kna-section-title-row">
        <span class="kna-section-title-label"><i class="fas fa-info-circle"></i> Cash Advance Information</span>
        <button type="button" class="kna-history-btn" id="btnShowHistory" title="View History">
          <i class="fas fa-history"></i>
        </button>
      </div>

  <!-- Row 1: Identifiers & Status -->
  <div class="kna-info-row" style="grid-template-columns: repeat(4, 1fr);">
    <div class="form-group">
      <label class="kna-form-label">Cash Advance No</label>
      <div class="kna-readonly" id="viewRefNo">-</div>
    </div>
    <div class="form-group">
      <label class="kna-form-label">Status</label>
      <div class="kna-readonly" id="viewStatus" style="background:transparent;border-color:transparent;padding-left:0;">-</div>
    </div>
    <div class="form-group">
      <label class="kna-form-label">Amount</label>
      <div class="kna-readonly" id="viewAmount">-</div>
    </div>
    <div class="form-group">
      <label class="kna-form-label">Approved Amount</label>
      <div class="kna-readonly" id="viewApprovedAmount">-</div>
    </div>
  </div>

      <!-- Row 2: Dates & Cost Center -->
      <div class="kna-info-row kna-info-row-3">
        <div class="form-group">
          <label class="kna-form-label">Requested Date</label>
          <div class="kna-readonly" id="viewRequestedDate">-</div>
        </div>
        <div class="form-group">
          <label class="kna-form-label">Date Needed</label>
          <div class="kna-readonly" id="viewNeededDate">-</div>
        </div>
        <div class="form-group">
          <label class="kna-form-label">Cost Center</label>
          <div class="kna-readonly" id="viewCostCenter">-</div>
        </div>
      </div>

      <!-- Payable To & Address -->
      <div class="kna-info-row kna-info-row-3">
        <div class="form-group">
          <label class="kna-form-label">Payable To</label>
          <div class="kna-readonly" id="viewPayableTo">-</div>
        </div>
        <div class="form-group">
          <label class="kna-form-label">Address</label>
          <div class="kna-readonly" id="viewAddress">-</div>
        </div>
        <div class="form-group">
          <label class="kna-form-label">IO Number</label>
          <div class="kna-readonly" id="viewIONumber">-</div>
        </div>
      </div>

      <!-- Notes -->
      <div class="form-group" style="margin-bottom:12px;">
        <label class="kna-form-label">Purpose / Description</label>
        <div class="kna-readonly" id="viewPurpose" style="min-height:48px;align-items:flex-start;padding-top:8px;">-</div>
      </div>

      <!-- Attachments -->
      <div id="viewAttachmentsSection">
        <div class="kna-section-title">
          <i class="fas fa-paperclip"></i>
          Attachments
        </div>
        <div id="viewAttachmentsList" class="kna-attachment-list">
          <div class="kna-doc-empty">Loading attachments...</div>
        </div>
      </div>

      <hr />

      <div id="viewPdfSection">
        <div class="kna-section-title">
          <i class="fas fa-file-pdf"></i>
          Cash Advance Document
        </div>

        <div class="kna-doc-panel">
          <div class="kna-doc-meta">
            <div class="kna-doc-state" id="viewPdfState">Preparing document...</div>
            <a href="#" id="viewPdfOpenNewTab" class="btn btn-sm btn-outline-secondary d-none" target="_blank" rel="noopener">Open in New Tab</a>
          </div>
          <iframe id="viewPdfIframe" class="kna-doc-frame d-none" title="Cash Advance PDF Preview"></iframe>
          <div id="viewPdfEmpty" class="kna-doc-empty">Document preview is not available yet.</div>
        </div>
      </div>

      <div id="viewWorkflowSection" class="d-none">
        <div class="kna-section-title">
          <i class="fas fa-route"></i>
          K-flow Workflow (Embedded)
        </div>
        <div class="kna-doc-panel">
          <div class="kna-doc-meta">
            <div class="kna-small text-muted">Approval is still in progress. You can monitor and complete K-Flow steps here without leaving <?= html_escape(config_item('app_name')); ?>.</div>
            <a href="#" id="viewWorkflowOpenNewTab" class="btn btn-sm btn-outline-secondary d-none" target="_blank" rel="noopener">Open Workflow in New Tab</a>
          </div>
          <iframe id="viewWorkflowIframe" class="kna-doc-frame" title="Embedded K-flow Workflow"></iframe>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- History Modal -->
<div id="historyModalOverlay" class="kna-history-modal-overlay d-none">
  <div class="kna-history-modal">
    <div class="kna-history-modal-header">
      <div class="kna-history-modal-title"><i class="fas fa-history"></i> History</div>
      <button type="button" class="kna-history-modal-close" id="btnCloseHistory">&#x2715;</button>
    </div>
    <div class="kna-history-modal-body">
      <ul class="kna-timeline" id="viewTimeline"></ul>
    </div>
  </div>
</div>

<!-- Image Preview Modal -->
<div id="attachmentPreviewModal" class="kna-preview-modal">
  <div class="kna-preview-modal-inner">
    <button type="button" class="kna-preview-modal-close" id="attachmentPreviewClose">
      <i class="fas fa-times"></i>
    </button>
    <img id="attachmentPreviewImg" src="" alt="Preview" class="kna-preview-modal-img">
    <div class="kna-preview-modal-caption" id="attachmentPreviewCaption"></div>
  </div>
</div>