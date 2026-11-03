
<style>
	* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
	body { background: linear-gradient(135deg, #f0f4f8 0%, #f8f9fc 100%); }
	.kna-page { padding: 12px 14px; background: transparent; min-height: 100vh; }
	.kna-title { font-size: 20px; font-weight: 600; margin: 0 0 8px 0; line-height: 1.2; }
	.kna-small { font-size: 12px !important; line-height: 1.35; }
	.kna-card { border: 1px solid #d9e0e7 !important; border-radius: 6px; background: #ffffff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
	.kna-card .card-body { padding: .85rem; }
	.kna-form-label { margin-bottom: .3rem; font-weight: 600; font-size: 12px; }
	.kna-readonly {
		min-height: 32px; padding: 6px 10px; border-radius: 4px; border: 1px solid #e5e7eb;
		background: #f8fafc; font-size: 12px; color: #1f2937; display: flex; align-items: center;
	}
	.kna-section-title {
		font-size: 14px; font-weight: 700; color: #1a202c; margin-bottom: 10px; margin-top: 0;
		padding-bottom: 4px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 6px;
	}
	.form-group { margin-bottom: 0; }
	.btn { border-radius: 4px; font-size: 12px; padding: 6px 14px; }
	.btn-outline-secondary { border: 1px solid #d1d5db; color: #6b7280; background: transparent; }
	.btn-primary { background: var(--brand-700); color: #fff; border: none; }
	.btn-warning { background: #f59e0b; color: #fff; border: none; }

	/* Info grid rows */
	.kna-info-row { display: grid; gap: 8px; margin-bottom: 10px; }
	.kna-info-row-3 { grid-template-columns: repeat(3, 1fr); }
	.kna-compact-field { display: flex; flex-direction: column; gap: 4px; }
	.kna-compact-value {
		min-height: 30px; padding: 4px 8px; border-radius: 4px; border: 1px solid #e5e7eb;
		background: #fff; font-size: 11px; font-weight: 500; color: #1f2937; display: flex; align-items: center;
	}
	.kna-compact-value.is-muted { background: #f8fafc; }

	/* Financial summary card */
	.kna-fin-card { background: #f8fafc; border: 1px solid #e5e7eb; border-left: 3px solid var(--brand-700); border-radius: 6px; padding: 10px 12px; }
	.kna-fin-card.liq { border-left-color: #0f766e; }
	.kna-fin-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
	.kna-fin-value { font-size: 15px; font-weight: 700; color: #1f2937; line-height: 1.3; }

	/* ===== COMPACT EXPENSE ITEM ROWS (mirrors liquidation) ===== */
	.kna-exp-wrap { width: 100%; overflow-x: auto; }
	.kna-item-rows { display: flex; flex-direction: column; gap: 6px; }
	.kna-item-row-wrap { display: flex; flex-direction: column; gap: 0; }
	.kna-item-row {
		display: flex; align-items: flex-start; gap: 8px; background: #f8f9fc;
		border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px;
	}
	.kna-item-row-index { flex: 0 0 auto; width: 20px; padding-top: 6px; font-size: 11px; font-weight: 700; color: #9ca3af; text-align: center; }
	.kna-item-row-fields { flex: 1 1 auto; display: flex; flex-wrap: wrap; gap: 6px; min-width: 0; }
	.kna-item-field { display: flex; flex-direction: column; gap: 2px; flex: 1 1 100px; min-width: 90px; }
	.kna-item-field-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .2px; color: #9ca3af; }
	.kna-f-type { flex: 2 1 180px; }
	.kna-f-amount { flex: 1 1 90px; }
	.kna-f-vat { flex: 0 0 auto; min-width: 40px; }
	.kna-f-vendor { flex: 1 1 130px; }
	.kna-f-tin { flex: 1 1 100px; }
	.kna-f-attach { flex: 1 1 150px; }
	.kna-f-remarks { flex: 1 1 150px; }
	.kna-item-attach-inline { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
	.kna-item-attach-inline .kna-attachment-cell { font-size: 11px; font-weight: 600; }
	.kna-item-attach-inline .btn { font-size: 11px; padding: 3px 8px; height: 24px; line-height: 1; }
	.kna-item-row-remove, .kna-item-row-lock {
		flex: 0 0 auto; margin-top: 4px; width: 26px; height: 26px; border-radius: 6px;
		display: flex; align-items: center; justify-content: center; font-size: 11px;
	}
	.kna-item-row-remove { border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; cursor: pointer; }
	.kna-item-row-remove:hover { background: #fee2e2; border-color: #ef4444; }
	.kna-item-row-lock { border: 1px solid #e5e7eb; background: #f3f4f6; color: #9ca3af; }
	.kna-attachment-cell { font-size: 12px; font-weight: 600; white-space: normal; overflow: hidden; text-overflow: ellipsis; line-height: 1.25; }
	.kna-vat-wrap { display: inline-flex; align-items: center; height: 28px; margin: 0; cursor: pointer; }
	.kna-vat-input { width: 14px; height: 14px; margin: 0; accent-color: var(--brand-700); }

	/* Attachment thumbnails */
	.kna-thumb-wrap { display: inline-flex; flex-direction: column; align-items: center; cursor: pointer; margin: 2px 6px 2px 0; max-width: 72px; vertical-align: top; text-align: center; position: relative; }
	.kna-thumb-wrap.removed { opacity: 0.4; filter: grayscale(1); }
	.kna-thumb-remove {
		position: absolute; top: -4px; right: -4px; background: #ef4444; color: #fff; border: none; border-radius: 50%;
		width: 16px; height: 16px; font-size: 9px; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 2; padding: 0;
	}
	.kna-thumb { width: 58px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; transition: transform .15s, border-color .15s; }
	.kna-thumb:hover { transform: scale(1.1); border-color: var(--brand-700); }
	.kna-thumb-label { font-size: 10px; color: #6b7280; margin-top: 2px; max-width: 72px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
	.kna-file-wrap { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; margin-bottom: 3px; max-width: 140px; }
	.kna-file-wrap a { color: var(--brand-700); text-decoration: none; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100px; display: inline-block; vertical-align: middle; }
	.kna-file-wrap a:hover { text-decoration: underline; }
	.kna-file-wrap .kna-file-remove { color: #ef4444; cursor: pointer; font-size: 10px; margin-left: 2px; }
	.kna-file-wrap.removed { opacity: 0.4; text-decoration: line-through; }

	/* Rejected / locked item styling */
	.kna-item-row.kna-row-rejected { background: #fef2f2; border-color: #fecaca; }
	.kna-rejection-ribbon {
		display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 6px 10px; background: #fff;
		border: 1px solid #fecaca; border-radius: 6px; border-left: 3px solid #ef4444; font-size: 11px; margin-top: 6px;
	}
	.kna-rejection-ribbon-label { font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: .3px; font-size: 10px; white-space: nowrap; }
	.kna-rejection-pill { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; white-space: nowrap; }
	.kna-rejection-pill i { margin-right: 3px; font-size: 10px; }
	.kna-item-row.kna-row-locked { background: #f3f4f6; border-color: #e5e7eb; }
	.kna-lock-icon { color: #9ca3af; font-size: 11px; }

	.kna-mobile-summary { display: block; margin-top: 10px; padding: 10px 12px; border: 1px solid #d1fae5; border-radius: 8px; background: #f0fdf4; }
	.kna-mobile-summary .kna-fin-label { margin-bottom: 2px; }

	/* Lightbox */
	.kna-lightbox { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, .88); z-index: 9999; display: flex; align-items: center; justify-content: center; }
	.kna-lightbox.d-none { display: none !important; }
	.kna-lightbox-img { max-width: 90vw; max-height: 88vh; border-radius: 6px; box-shadow: 0 8px 40px rgba(0, 0, 0, .6); }
	.kna-lightbox-close { position: fixed; top: 16px; right: 20px; background: none; border: none; color: #fff; font-size: 32px; cursor: pointer; line-height: 1; z-index: 10000; }

	/* Edit-specific styles */
	.kna-edit-input { font-size: 12px; padding: 4px 8px; border-radius: 4px; border: 1px solid #d1d5db; width: 100%; min-width: 0; }
	.kna-edit-input:focus { outline: none; border-color: var(--brand-700); box-shadow: 0 0 0 2px rgba(var(--brand-rgb), 0.1); }
	.kna-edit-input:disabled { background: #f3f4f6; border-color: #e5e7eb; color: #6b7280; cursor: not-allowed; }
	.kna-edit-select { font-size: 12px; padding: 4px 8px; border-radius: 4px; border: 1px solid #d1d5db; width: 100%; min-width: 0; background: #fff; }
	.kna-edit-select:focus { outline: none; border-color: var(--brand-700); box-shadow: 0 0 0 2px rgba(var(--brand-rgb), 0.1); }
	.kna-edit-select:disabled { background: #f3f4f6; border-color: #e5e7eb; color: #6b7280; cursor: not-allowed; }
	.kna-edit-number { text-align: right; }
	.kna-edit-checkbox { width: 15px; height: 15px; margin: 0; accent-color: var(--brand-700); }
	.kna-edit-checkbox:disabled { accent-color: #9ca3af; cursor: not-allowed; }

	.kna-rejected-banner { background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; font-size: 12px; color: #991b1b; }
	.kna-rejected-banner i { margin-right: 6px; }
	.kna-edit-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 12px; }
	.kna-attach-undo { font-size: 10px; color: var(--brand-700); cursor: pointer; margin-left: 4px; }
	.kna-attach-undo:hover { text-decoration: underline; }

	.kna-ocr-status { font-size: 11px; margin-top: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; line-height: 1.3; }
	.kna-ocr-scanning { color: #2563eb; }
	.kna-ocr-success { color: #059669; }
	.kna-ocr-error { color: #dc2626; }
	.kna-ocr-manual { color: #6b7280; }
	.kna-ocr-status i { font-size: 12px; width: 14px; text-align: center; }
	.kna-ocr-manual-btn { background: none; border: none; color: var(--brand-700); font-size: 11px; font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline; line-height: 1; }
	.kna-ocr-manual-btn:hover { color: var(--brand-800); }

	@media (max-width: 767.98px) {
		.kna-page { padding: 8px 8px 12px; }
		.kna-title { font-size: 18px; }
		.kna-info-row-3 { grid-template-columns: 1fr; }
		.kna-fin-value { font-size: 14px; }
		.kna-exp-wrap { overflow: visible; }
		.kna-item-field { flex: 1 1 45%; }
		.kna-thumb-wrap { max-width: 68px; margin-right: 4px; }
		.kna-thumb { width: 52px; height: 46px; }
		.kna-thumb-label { max-width: 68px; }
		.kna-edit-actions { flex-direction: column; }
		.kna-edit-actions .btn { width: 100%; }
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div>
			<div class="kna-title">Edit Reimbursement</div>
			<div class="kna-small text-muted">Update rejected items and resubmit for approval</div>
		</div>
		<a href="<?=base_url('transactions/reimbursement');?>" class="btn btn-outline-secondary">
			<i class="fas fa-arrow-left mr-1"></i> Back
		</a>
	</div>

	<!-- Rejected Items Banner -->
	<div id="rejectedBanner" class="kna-rejected-banner d-none">
		<i class="fas fa-exclamation-triangle"></i>
		<strong>Action Required:</strong> <span id="rejectedCount">0</span> item(s) were rejected. Please update the highlighted items below and resubmit.
	</div>

	<div class="card kna-card">
		<div class="card-body">
			<input type="hidden" id="reimbursementRef" value="<?=html_escape($reimbursement_no);?>">
			<input type="hidden" id="editPageMarker" value="1">
			<?php
			$ccData = array();
			if (!empty($cost_centers) && is_array($cost_centers)) {
				foreach ($cost_centers as $cc) {
					$ccData[] = array(
						'cost_center_code' => isset($cc['cost_center_code']) ? $cc['cost_center_code'] : '',
						'cost_center_name' => isset($cc['cost_center_name']) ? $cc['cost_center_name'] : '',
					);
				}
			}
			$expenseTypeData = array();
			if (!empty($expense_types) && is_array($expense_types)) {
				foreach ($expense_types as $et) {
					$expenseTypeData[] = array(
						'id' => isset($et['id']) ? $et['id'] : '',
						'expense_code' => isset($et['expense_code']) ? $et['expense_code'] : '',
						'long_text' => isset($et['long_text']) ? $et['long_text'] : '',
						'category_name' => isset($et['category_name']) ? $et['category_name'] : '',
						'description' => isset($et['description']) ? $et['description'] : '',
					);
				}
			}
			?>
			<input type="hidden" id="costCentersData" value="<?=html_escape(json_encode($ccData));?>">
			<input type="hidden" id="expenseTypesData" value="<?=html_escape(json_encode($expenseTypeData));?>">

			<div class="kna-section-title">
				<i class="fas fa-info-circle"></i>
				Reimbursement Information
			</div>

			<div class="kna-info-row kna-info-row-3">
				<div class="form-group">
					<label class="kna-form-label">Reimbursement No</label>
					<div class="kna-readonly" id="editReimbursementNo">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Status</label>
					<div class="kna-readonly" id="editStatus" style="background:transparent;border-color:transparent;padding-left:0;">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Submitted Date</label>
					<div class="kna-readonly" id="editSubmittedDate">-</div>
				</div>
			</div>

			<div class="kna-info-row kna-info-row-3">
				<div class="form-group">
					<label class="kna-form-label">IO Number</label>
					<input type="text" class="form-control form-control-sm kna-small" id="editIoNumber" placeholder="IO Number (optional)">
				</div>
				<div class="form-group kna-compact-field">
					<label class="kna-form-label">Expense Period</label>
					<div class="kna-readonly" id="editExpenseDate">-</div>
				</div>
				<div class="form-group kna-compact-field">
					<label class="kna-form-label">Total Amount</label>
					<div class="kna-compact-value is-muted" id="editTotalAmount">-</div>
				</div>
			</div>

			<div class="kna-info-row kna-info-row-3" style="margin-bottom:12px;">
				<div class="form-group">
					<label class="kna-form-label">Payable To</label>
					<select class="form-control form-control-sm kna-small" id="editPayableTo">
						<option value="">Loading...</option>
					</select>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Address</label>
					<input type="text" class="form-control form-control-sm kna-small" id="editAddress" placeholder="Address">
				</div>
				<div class="form-group">
					<label class="kna-form-label">Cost Center</label>
					<select class="form-control form-control-sm kna-small" id="editCostCenter">
						<option value="">Select cost center</option>
					</select>
				</div>
			</div>

			<div class="form-group" style="margin-bottom:12px;">
				<label class="kna-form-label">Description</label>
				<textarea class="form-control form-control-sm kna-small" id="editDescription" style="min-height:48px;"></textarea>
			</div>

			<hr />

			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-section-title" style="margin:0;border:none;padding:0;">
					<i class="fas fa-receipt"></i>
					Expense Items
				</div>
				<button type="button" class="btn btn-outline-secondary" id="btnAddNewItem" style="white-space: nowrap;">
					<i class="fas fa-plus mr-1"></i> Add Item
				</button>
			</div>

			<div id="editExpenseItems"></div>

			<hr />

			<div class="kna-edit-actions">
				<a href="<?=base_url('transactions/reimbursement');?>" class="btn btn-outline-secondary">Cancel</a>
				<button type="button" class="btn btn-warning" id="btnSaveAsDraft">
					<i class="fas fa-save mr-1"></i> Save as Draft
				</button>
				<button type="button" class="btn btn-primary" id="btnSaveEdit">
					<i class="fas fa-check mr-1"></i> Save & Resubmit
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Lightbox -->
<div id="knaLightbox" class="kna-lightbox d-none">
	<button class="kna-lightbox-close" id="knaLightboxClose">&#x2715;</button>
	<img id="knaLightboxImg" class="kna-lightbox-img" src="" alt="Attachment">
</div>

<script>
window.currentUserId = <?=json_encode((int)$this->session->userdata('user_id'));?>;
</script>
