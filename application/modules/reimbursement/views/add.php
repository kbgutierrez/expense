<style>
	.kna-image-overlay {
		display: none;
		position: fixed;
		z-index: 9999;
		left: 0;
		top: 0;
		width: 100vw;
		height: 100vh;
		background: rgba(0,0,0,0.55);
		align-items: center;
		justify-content: center;
		cursor: zoom-out;
	}
	.kna-image-overlay.active { display: flex; }
	.kna-image-overlay img {
		max-width: 90vw;
		max-height: 90vh;
		border-radius: 8px;
		box-shadow: 0 8px 32px rgba(0,0,0,0.25);
		background: #fff;
		padding: 8px;
	}

	* { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
	body { background: linear-gradient(135deg, #f0f4f8 0%, #f8f9fc 100%); }
	.kna-page { padding: 12px 14px; background: transparent; min-height: 100vh; }
	.kna-title { font-size: 20px; font-weight: 600; margin: 0 0 8px 0; line-height: 1.2; }
	.kna-small { font-size: 12px !important; line-height: 1.35; }
	.kna-card { border: 1px solid #d9e0e7 !important; border-radius: 6px; background: #ffffff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
	.kna-card .card-body { padding: .85rem; }
	.kna-form-label { margin-bottom: .3rem; font-weight: 600; font-size: 12px; }
	.form-control, .form-control-sm { font-size: 12px; border-radius: 4px; padding: 6px 10px; height: 32px; }
	textarea.form-control { min-height: 48px; font-size: 12px; padding: 6px 10px; }
	.btn { border-radius: 4px; font-size: 12px; padding: 6px 14px; }
	.btn-primary { background: var(--brand-700); color: #fff; border: none; }
	.btn-outline-secondary { border: 1px solid #d1d5db; color: #6b7280; background: transparent; }
	.btn-outline-secondary:hover { background: #f3f4f6; border-color: #9ca3af; }
	.kna-section-title {
		font-size: 14px; font-weight: 700; color: #1a202c; margin-bottom: 10px; margin-top: 0;
		padding-bottom: 4px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 6px;
	}
	.form-group { margin-bottom: 0; }
	.kna-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 12px; }
	hr { border: 0; border-top: 1px solid #f3f4f6; margin: 12px 0; }
	.mb-4 { margin-bottom: 12px; }
	.mr-1 { margin-right: 4px; }
	.d-none { display: none; }
	.d-flex { display: flex; }
	.gap-2 { gap: 6px; }
	.ml-2 { margin-left: 6px; }

	/* Info grid rows */
	.kna-info-row { display: grid; gap: 8px; margin-bottom: 10px; }
	.kna-info-row-3 { grid-template-columns: repeat(3, 1fr); }
	.kna-compact-field { display: flex; flex-direction: column; gap: 4px; }
	.kna-compact-value {
		min-height: 30px; padding: 4px 8px; border-radius: 4px; border: 1px solid #e5e7eb;
		background: #f8fafc; font-size: 11px; font-weight: 500; color: #1f2937; display: flex; align-items: center;
	}

	/* ===== COMPACT EXPENSE ITEM ROWS (mirrors liquidation) ===== */
	.kna-item-rows { display: flex; flex-direction: column; gap: 6px; }
	.kna-item-row {
		display: flex; align-items: flex-start; gap: 8px; background: #f8f9fc;
		border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px;
	}
	.kna-item-row-index {
		flex: 0 0 auto; width: 18px; padding-top: 6px; font-size: 10px; font-weight: 700; color: #9ca3af; text-align: center;
	}
	.kna-item-row-fields { flex: 1 1 auto; display: flex; flex-wrap: wrap; gap: 6px; min-width: 0; }
	.kna-item-field { display: flex; flex-direction: column; gap: 2px; flex: 1 1 100px; min-width: 90px; }
	.kna-item-field-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .2px; color: #9ca3af; }
	.kna-item-field .form-control { font-size: 10px; padding: 4px 8px; height: 28px; }
	.kna-f-type { flex: 2 1 180px; }
	.kna-f-amount { flex: 1 1 90px; }
	.kna-f-vat { flex: 0 0 auto; min-width: 40px; }
	.kna-f-vendor { flex: 1 1 130px; }
	.kna-f-tin { flex: 1 1 100px; }
	.kna-f-attach { flex: 1 1 150px; }
	.kna-f-remarks { flex: 1 1 150px; }
	.kna-vat-wrap { display: inline-flex; align-items: center; height: 28px; margin: 0; cursor: pointer; }
	.kna-vat-input { width: 14px; height: 14px; margin: 0; accent-color: var(--brand-700); }
	.kna-item-attach-inline { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
	.kna-item-attach-inline .kna-attachment-cell { font-size: 10px; font-weight: 600; display: inline-block; max-width: 130px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; vertical-align: middle; }
	.kna-item-attach-inline .btn { font-size: 10px; padding: 3px 8px; height: 24px; line-height: 1; }
	.kna-item-row-remove {
		flex: 0 0 auto; margin-top: 4px; width: 26px; height: 26px; border-radius: 6px;
		border: 1px solid #fecaca; background: #fef2f2; color: #dc2626;
		display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 11px;
	}
	.kna-item-row-remove:hover { background: #fee2e2; border-color: #ef4444; }
	.kna-attachment-cell { font-size: 12px; font-weight: 600; white-space: normal; overflow: hidden; text-overflow: ellipsis; line-height: 1.25; }

	.kna-ocr-status { font-size: 10px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; line-height: 1.3; }
	.kna-ocr-scanning { color: #2563eb; }
	.kna-ocr-success { color: #059669; }
	.kna-ocr-error { color: #dc2626; }
	.kna-ocr-manual { color: #6b7280; }
	.kna-ocr-status i { font-size: 10px; width: 12px; text-align: center; }
	.kna-ocr-manual-btn { background: none; border: none; color: var(--brand-700); font-size: 10px; font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline; line-height: 1; }
	.kna-ocr-manual-btn:hover { color: var(--brand-800); }

	@media (max-width: 768px) {
		.kna-page { padding: 8px 4px; }
		.kna-title { font-size: 15px; gap: 4px; }
		.kna-card .card-body { padding: 8px 4px; }
		.kna-actions { flex-direction: column; gap: 4px; }
		.kna-actions .btn { width: 100%; }
		.kna-info-row-3 { grid-template-columns: 1fr; }
		.kna-item-field { flex: 1 1 45%; }
	}

	@media (max-width: 480px) {
		.kna-title { font-size: 12px; }
		.kna-section-title { font-size: 10px; }
		.kna-card .card-body { padding: 4px 2px; }
		.kna-item-field { flex: 1 1 100%; }
	}
</style>

<div class="page-inner kna-page">
	<input type="hidden" id="reimbursementRef" value="<?=isset($reimbursement_no) ? htmlspecialchars($reimbursement_no, ENT_QUOTES, 'UTF-8') : '';?>">
	<input type="hidden" id="draftEditWindowDays" value="<?=isset($draft_edit_window_days) ? (int)$draft_edit_window_days : 7;?>">
	<input type="hidden" id="isEditMode" value="<?=!empty($is_edit_mode) ? '1' : '0';?>">
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
	<input type="hidden" id="teamMembersData" value="<?=html_escape(json_encode(!empty($team_members) ? $team_members : array()));?>">
	<?php
	$payableToData = array();
	if (!empty($payable_to_users) && is_array($payable_to_users)) {
		foreach ($payable_to_users as $u) {
			$payableToData[] = array(
				'id' => isset($u['id']) ? (int) $u['id'] : 0,
				'firstname' => isset($u['firstname']) ? $u['firstname'] : '',
				'lastname' => isset($u['lastname']) ? $u['lastname'] : '',
				'designation' => isset($u['designation']) ? $u['designation'] : '',
			);
		}
	}
	?>
	<input type="hidden" id="payableToUsersData" value="<?=html_escape(json_encode($payableToData));?>">
	<input type="hidden" id="currentUserId" value="<?=isset($current_user_id) ? (int) $current_user_id : 0;?>">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div>
			<div class="kna-title"><?=!empty($is_edit_mode) ? 'Edit Draft Reimbursement' : 'New Reimbursement';?></div>
			<div class="kna-small text-muted"><?=!empty($is_edit_mode) ? 'Update your draft and submit when ready' : 'Submit expense details with receipt documentation';?></div>
		</div>
		<a href="<?=base_url('transactions/reimbursement'); ?>" class="btn btn-outline-secondary">
			<i class="fas fa-arrow-left mr-1"></i> Back
		</a>
	</div>
	<div class="card kna-card">
		<div class="card-body">
			<form id="formNewReimbursement" autocomplete="off">
				<div class="kna-section-title">
					<i class="fas fa-info-circle"></i>
					Reimbursement Information
				</div>
				<?php if (!empty($team_members)): ?>
				<div class="kna-info-row kna-info-row-3" id="fileForRow">
					<div class="form-group">
						<label class="kna-form-label">File For</label>
						<select class="form-control form-control-sm kna-small" id="newFileFor">
							<option value="">Myself</option>
						</select>
					</div>
				</div>
				<?php endif; ?>
				<div class="kna-info-row kna-info-row-3">
					<div class="form-group">
						<label class="kna-form-label">IO Number</label>
						<input type="text" class="form-control form-control-sm kna-small" id="newIoNumber" placeholder="IO Number (optional)">
					</div>
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">Total Amount</label>
						<div class="kna-compact-value" id="newTotalAmount">0.00</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Expense Date Range</label>
						<input type="text" class="form-control form-control-sm kna-small" id="newDateRange" placeholder="Auto based on document dates" readonly style="background:#f0f4f8;">
					</div>
				</div>
				<div class="kna-info-row kna-info-row-3">
					<div class="form-group">
						<label class="kna-form-label">Address</label>
						<input type="text" class="form-control form-control-sm kna-small" id="newAddress" placeholder="Address">
					</div>
					<div class="form-group">
						<label class="kna-form-label">Cost Center</label>
						<select class="form-control form-control-sm kna-small" id="newCostCenter">
							<option value="">Select cost center</option>
						</select>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Payable To</label>
						<select class="form-control form-control-sm kna-small" id="newPayableTo">
							<option value="">Select payable to</option>
						</select>
					</div>
				</div>
				<div class="form-group" style="margin-bottom: 12px;">
					<label class="kna-form-label">Description / Notes</label>
					<textarea class="form-control form-control-sm kna-small" id="newDescription" placeholder="Purpose of reimbursement" style="min-height: 48px;" required></textarea>
				</div>

				<hr />
				<div class="d-flex align-items-center justify-content-between mb-2">
					<div class="kna-section-title" style="margin: 0; border: none; padding: 0;">
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
					<a href="<?=base_url('transactions/reimbursement'); ?>" class="btn btn-outline-secondary">Cancel</a>
					<button type="button" class="btn btn-outline-primary" id="btnSaveDraftReimbursement">
						<i class="fas fa-save mr-1"></i> Save Draft
					</button>
					<button type="button" class="btn btn-primary" id="btnSaveNewReimbursement">
						<i class="fas fa-check mr-1"></i> <?=!empty($is_edit_mode) ? 'Update & Submit' : 'Submit Reimbursement';?>
					</button>
				</div>
			</form>
		</div>
	</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
	const overlay = document.getElementById('knaImageOverlay');
	if (overlay) {
		overlay.addEventListener('click', function() {
			overlay.classList.remove('active');
			overlay.querySelector('img').src = '';
		});
	}
	document.body.addEventListener('click', function(e) {
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
