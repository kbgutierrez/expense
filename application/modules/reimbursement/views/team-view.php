<style>
	* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
	body { background: linear-gradient(135deg, #f0f4f8 0%, #f8f9fc 100%); }
	.kna-page { padding: 12px 14px; background: transparent; min-height: 100vh; }
	.kna-title { font-size: 20px; font-weight: 600; margin: 0 0 8px 0; line-height: 1.2; }
	.kna-small { font-size: 12px !important; line-height: 1.35; }
	.kna-card { border: 1px solid #d9e0e7 !important; border-radius: 6px; background: #ffffff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
	.kna-card .card-body { padding: .85rem; }
	.kna-form-label { margin-bottom: .3rem; font-weight: 600; font-size: 12px; }
	.form-control, .form-control-sm { font-size: 12px; border-radius: 4px; padding: 6px 10px; height: 32px; }
	.btn { border-radius: 4px; font-size: 12px; padding: 6px 14px; }
	.btn-primary { background: var(--brand-700); color: #fff; border: none; }
	.btn-outline-secondary { border: 1px solid #d1d5db; color: #6b7280; background: transparent; }
	.btn-outline-secondary:hover { background: #f3f4f6; border-color: #9ca3af; }
	.kna-section-title {
		font-size: 14px; font-weight: 700; color: #1a202c; margin-bottom: 10px; margin-top: 0;
		padding-bottom: 4px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 6px;
	}
	hr { border: 0; border-top: 1px solid #f3f4f6; margin: 12px 0; }

	.kna-table td, .kna-table th { font-size: 12px !important; padding: .5rem .45rem; vertical-align: middle; }

	.kna-badge { padding: .2rem .4rem; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; }
	.kna-badge-pending { background: #fff9db; color: #f59f00; }
	.kna-badge-approved { background: #e8f7ee; color: #17663a; }
	.kna-badge-rejected { background: #fff5f5; color: #e03131; }
	.kna-badge-proxy { background: var(--brand-100); color: var(--brand-800); }

	.kna-readonly-banner {
		background: #fff9db; border: 1px solid #f5d76e; color: #7a5b00; border-radius: 6px;
		padding: .6rem .85rem; font-size: 12px; margin-bottom: .75rem;
	}

	/* Info grid rows (mirrors add.php) */
	.kna-info-row { display: grid; gap: 8px; margin-bottom: 10px; }
	.kna-info-row-4 { grid-template-columns: repeat(4, 1fr); }

	/* Compact item-field design (mirrors add.php's expense item rows) */
	.kna-item-field { display: flex; flex-direction: column; gap: 2px; flex: 1 1 100px; min-width: 90px; }
	.kna-item-field-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .2px; color: #9ca3af; }
	.kna-item-field .form-control { font-size: 10px; padding: 4px 8px; height: 28px; }
	.kna-item-row-fields { display: flex; flex-wrap: wrap; gap: 6px; min-width: 0; }
	.kna-f-type { flex: 2 1 220px; }
	.kna-f-desc { flex: 2 1 220px; }
	.kna-f-amount { flex: 1 1 90px; }
	.kna-f-vat { flex: 0 0 auto; min-width: 110px; }
	.kna-f-vendor { flex: 1 1 130px; }
	.kna-f-tin { flex: 1 1 100px; }
	.kna-f-attach { flex: 1 1 220px; }

	.kna-vat-wrap { display: inline-flex; align-items: center; height: 28px; margin: 0; cursor: pointer; }
	.kna-vat-input { width: 14px; height: 14px; margin: 0; accent-color: var(--brand-700); }
	.kna-item-attach-inline { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
	.kna-item-attach-inline .kna-attachment-cell { font-size: 10px; font-weight: 600; display: inline-block; max-width: 130px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; vertical-align: middle; }
	.kna-item-attach-inline .btn { font-size: 10px; padding: 3px 8px; height: 24px; line-height: 1; }
	.kna-attachment-cell { font-size: 12px; font-weight: 600; white-space: normal; overflow: hidden; text-overflow: ellipsis; line-height: 1.25; }

	.kna-ocr-status { font-size: 10px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; line-height: 1.3; margin-top: 4px; }
	.kna-ocr-scanning { color: #2563eb; }
	.kna-ocr-success { color: #059669; }
	.kna-ocr-error { color: #dc2626; }
	.kna-ocr-status i { font-size: 10px; width: 12px; text-align: center; }
	.kna-ocr-manual-btn { background: none; border: none; color: var(--brand-700); font-size: 10px; font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline; line-height: 1; }
	.kna-ocr-manual-btn:hover { color: var(--brand-800); }

	/* Select2 sizing, matched to add.js's initExpenseTypeSelect2 (not the oversized default) */
	.select2-container--default .select2-selection--single { height: 28px; border: 1px solid #d1d5db; border-radius: 4px; background: #fff; }
	.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 26px; padding-left: 8px; padding-right: 20px; font-size: 10px; color: #374151; }
	.select2-container--default .select2-selection--single .select2-selection__arrow { height: 26px; width: 20px; }
	.select2-container--default .select2-selection--single .select2-selection__arrow b { border-width: 3px 3px 0 3px; margin-top: -2px; }

	@media (max-width: 767.98px) {
		.kna-page { padding: 8px 8px 12px; }
		.kna-info-row-4 { grid-template-columns: 1fr 1fr; }
		.kna-item-field { flex: 1 1 100%; }
	}
</style>

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
			'expense_code' => isset($et['expense_code']) ? $et['expense_code'] : '',
			'long_text' => isset($et['long_text']) ? $et['long_text'] : '',
			'category_name' => isset($et['category_name']) ? $et['category_name'] : '',
		);
	}
}
$payableToData = array();
if (!empty($payable_to_users) && is_array($payable_to_users)) {
	foreach ($payable_to_users as $u) {
		$payableToData[] = array(
			'id' => isset($u['id']) ? $u['id'] : '',
			'firstname' => isset($u['firstname']) ? $u['firstname'] : '',
			'lastname' => isset($u['lastname']) ? $u['lastname'] : '',
			'designation' => isset($u['designation']) ? $u['designation'] : '',
		);
	}
}
?>
<input type="hidden" id="costCentersData" value="<?= html_escape(json_encode($ccData)) ?>">
<input type="hidden" id="expenseTypesData" value="<?= html_escape(json_encode($expenseTypeData)) ?>">
<input type="hidden" id="payableToUsersData" value="<?= html_escape(json_encode($payableToData)) ?>">

<div class="page-inner kna-page" id="teamViewPage" data-reimbursement-id="<?= htmlspecialchars($reimbursement_no, ENT_QUOTES) ?>">
	<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap" style="gap:.5rem;">
		<div>
			<div class="kna-title">Team Reimbursement Details</div>
			<div class="kna-small text-muted" id="teamViewRefLabel"><?= htmlspecialchars($reimbursement_no, ENT_QUOTES) ?></div>
		</div>
		<a href="<?= base_url('transactions/reimbursement') ?>" class="btn btn-outline-secondary btn-sm">
			<i class="fas fa-arrow-left mr-1"></i> Back to My Team
		</a>
	</div>

	<div id="teamViewReadonlyBanner" class="kna-readonly-banner d-none"></div>

	<div class="card kna-card mb-2">
		<div class="card-body">
			<div class="kna-section-title"><i class="fas fa-info-circle"></i> Reimbursement Information</div>

			<div class="kna-info-row kna-info-row-4">
				<div>
					<div class="kna-small text-muted">Salesman</div>
					<div id="teamViewSalesman" class="font-weight-bold kna-small">—</div>
					<div id="teamViewProxyBadge" class="kna-badge kna-badge-proxy d-none mt-1"></div>
				</div>
				<div>
					<div class="kna-small text-muted">Status</div>
					<div id="teamViewStatus">—</div>
				</div>
				<div>
					<div class="kna-small text-muted">Total Amount</div>
					<div id="teamViewTotal" class="font-weight-bold kna-small">—</div>
				</div>
				<div>
					<div class="kna-small text-muted">Submitted</div>
					<div id="teamViewSubmitted" class="kna-small">—</div>
				</div>
			</div>

			<hr>

			<div class="kna-info-row kna-info-row-4">
				<div>
					<label class="kna-form-label">Cost Center</label>
					<select class="form-control form-control-sm" id="teamViewCostCenterId">
						<option value="">Select cost center</option>
					</select>
				</div>
				<div>
					<label class="kna-form-label">Payable To</label>
					<select class="form-control form-control-sm" id="teamViewPayableTo">
						<option value="">Loading...</option>
					</select>
				</div>
				<div>
					<label class="kna-form-label">Address</label>
					<input type="text" class="form-control form-control-sm" id="teamViewAddress">
				</div>
				<div>
					<label class="kna-form-label">IO Number</label>
					<input type="text" class="form-control form-control-sm" id="teamViewIo">
				</div>
			</div>
			<div class="text-right">
				<button type="button" class="btn btn-primary btn-sm" id="btnSaveTeamHeader">
					<i class="fas fa-save mr-1"></i> Save Header Changes
				</button>
			</div>
		</div>
	</div>

	<div class="card kna-card">
		<div class="card-body">
			<div class="kna-section-title"><i class="fas fa-receipt"></i> Line Items</div>
			<div class="table-responsive">
				<table class="table table-sm kna-table">
					<thead>
						<tr>
							<th>Expense Type</th>
							<th>Description</th>
							<th>Invoice/Receipt No.</th>
							<th>Document Date</th>
							<th class="text-right">Amount</th>
							<th>Vendor</th>
							<th>Attachment</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody id="teamViewItemsTbody">
						<tr><td colspan="8" class="text-center text-muted kna-small py-4">Loading...</td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modalEditTeamItem" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h6 class="modal-title kna-small font-weight-bold">Correct Line Item</h6>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="editItemDetailId">
				<div class="kna-item-row-fields">
					<div class="kna-item-field kna-f-type">
						<span class="kna-item-field-label">Expense Type</span>
						<select class="form-control form-control-sm" id="editItemExpenseCategory">
							<option value="">Select expense type</option>
						</select>
					</div>
					<div class="kna-item-field kna-f-attach">
						<span class="kna-item-field-label">Attachment</span>
						<div class="kna-item-attach-inline">
							<span class="kna-attachment-cell" id="editItemAttachmentSummary">No file</span>
							<button type="button" class="btn btn-outline-primary btn-sm" id="btnEditItemAttach">Attach</button>
							<input type="file" class="d-none" id="editItemAttachmentUpload" accept="image/*,application/pdf">
							<input type="file" class="d-none" id="editItemAttachmentCamera" accept="image/*" capture="environment">
						</div>
						<div id="editItemOcrStatus"></div>
					</div>
					<div class="kna-item-field">
						<span class="kna-item-field-label">Invoice/Receipt No.</span>
						<input type="text" class="form-control form-control-sm" id="editItemInvoiceReceiptNo">
					</div>
					<div class="kna-item-field">
						<span class="kna-item-field-label">Document Date</span>
						<input type="date" class="form-control form-control-sm" id="editItemDocumentDate">
					</div>
					<div class="kna-item-field kna-f-amount">
						<span class="kna-item-field-label">Actual Amount</span>
						<input type="number" step="0.01" class="form-control form-control-sm text-right" id="editItemActualAmount">
					</div>
					<div class="kna-item-field kna-f-vat">
						<span class="kna-item-field-label">VAT</span>
						<label class="kna-vat-wrap kna-small">
							<input type="checkbox" class="kna-vat-input mr-2" id="editItemIsVatable"> Applicable
						</label>
					</div>
					<div class="kna-item-field kna-f-amount">
						<span class="kna-item-field-label">Net Amount</span>
						<input type="number" step="0.01" class="form-control form-control-sm text-right" id="editItemNetAmount" readonly title="Auto-computed from Actual Amount">
					</div>
					<div class="kna-item-field kna-f-amount">
						<span class="kna-item-field-label">VAT Amount</span>
						<input type="number" step="0.01" class="form-control form-control-sm text-right" id="editItemVatAmount" readonly title="Auto-computed from Actual Amount">
					</div>
					<div class="kna-item-field kna-f-vendor">
						<span class="kna-item-field-label">Vendor Name</span>
						<input type="text" class="form-control form-control-sm" id="editItemVendorName">
					</div>
					<div class="kna-item-field kna-f-tin">
						<span class="kna-item-field-label">Vendor TIN</span>
						<input type="text" class="form-control form-control-sm" id="editItemVendorTin">
					</div>
					<div class="kna-item-field kna-f-desc">
						<span class="kna-item-field-label">Vendor Address</span>
						<input type="text" class="form-control form-control-sm" id="editItemVendorAddress">
					</div>
					<div class="kna-item-field kna-f-desc">
						<span class="kna-item-field-label">Description</span>
						<input type="text" class="form-control form-control-sm" id="editItemDescription">
					</div>
				</div>
				<div class="kna-small text-muted mt-2">Uploading a receipt photo will attempt to auto-fill the fields above (OCR). Review before saving.</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary btn-sm" id="btnSaveTeamItem">Save Correction</button>
			</div>
		</div>
	</div>
</div>
