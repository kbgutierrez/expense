<style>
	.kna-page { padding: 12px 14px; }
	.kna-card { border: 1px solid #d9e0e7 !important; border-radius: 6px; background: #ffffff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
	.kna-card .card-body { padding: .85rem; }
	.kna-title { font-size: 20px; font-weight: 600; margin: 0; line-height: 1.2; }
	.kna-small { font-size: 12px !important; line-height: 1.35; }
	.kna-form-label { margin-bottom: .3rem; font-weight: 600; color: #34495e; }
	.kna-table td, .kna-table th { font-size: 12px !important; padding: .5rem .45rem; vertical-align: middle; white-space: nowrap; }
	.kna-actions .btn { padding: .3rem .55rem; font-size: 12px; }
	.kna-badge { padding: .2rem .4rem; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; }
	.kna-badge-active { background: #e8f7ee; color: #17663a; }
	.kna-badge-inactive { background: #eef2f7; color: #495869; }
	.kna-mobile-list .kna-item { border: 1px solid #dde3eb; border-radius: 6px; padding: .65rem; margin-bottom: .5rem; background: #fff; }
	.kna-mobile-list .kna-item:last-child { margin-bottom: 0; }
	.kna-mobile-list .kna-row { display: flex; align-items: center; justify-content: space-between; gap: .45rem; margin-bottom: .25rem; }
	.kna-mobile-list .kna-row:last-child { margin-bottom: 0; }

	.kna-acct-masked { font-family: ui-monospace, "SF Mono", Consolas, monospace; letter-spacing: .5px; }
	.kna-btn-reveal { border: none; background: transparent; color: #6c7c8c; padding: 2px 4px; }
	.kna-btn-reveal:hover { color: var(--brand-700); }

	.kna-modal-header { background: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: .75rem 1rem; }
	.kna-modal-header .modal-title { font-weight: 600; color: var(--brand-900); font-size: 14px; display: flex; align-items: center; gap: 6px; }
	.kna-modal-header .close { padding: .5rem; margin: -.5rem -.5rem -.5rem auto; }
	.kna-form-section { margin-bottom: 1rem; }
	.kna-form-section-title { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; font-weight: 700; color: #6c757d; margin-bottom: .6rem; padding-bottom: .35rem; border-bottom: 1px solid #eef2f7; }
	.form-control-sm, .custom-select-sm { font-size: 12px; }

	#bamTabCompany .kna-card .card-body { padding: 1.5rem; }
	.kna-bizlink-section-title { font-size: 13px; text-transform: uppercase; letter-spacing: .4px; font-weight: 700; color: #495869; padding-bottom: .6rem; border-bottom: 1px solid #eef2f7; }
	#bamTabCompany .form-group { margin-bottom: 1.25rem; }
	#bamTabCompany .form-control { font-size: 13px; }

	.kna-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem 1.5rem; }
	.kna-detail-cell-span2 { grid-column: span 2; }
	.kna-detail-label { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #898781; font-weight: 700; margin-bottom: 4px; }
	.kna-detail-value { font-size: 14px; font-weight: 600; color: #0b0b0b; }
	@media (max-width: 575.98px) {
		.kna-detail-grid { grid-template-columns: 1fr; }
		.kna-detail-cell-span2 { grid-column: span 1; }
	}

	.kna-upload-drop { border: 2px dashed #d6dee7; border-radius: 8px; padding: 24px 16px; text-align: center; color: #6c7c8c; font-size: 12px; }
	.kna-upload-summary { font-size: 12px; }
	.kna-upload-summary .skip-item { padding: 4px 0; border-bottom: 1px dashed #eef2f7; color: #a12626; }
	.kna-upload-summary .skip-item:last-child { border-bottom: none; }

	.select2-container--default .select2-selection--single {
		height: 31px;
		border-radius: 4px;
		border: 1px solid #ced4da;
	}
	.select2-container--default .select2-selection--single .select2-selection__rendered {
		line-height: 29px;
		font-size: 12px;
	}
	.select2-container--default .select2-selection--single .select2-selection__arrow {
		height: 29px;
	}

	@media (max-width: 991.98px) {
		.kna-page { padding: 10px; }
		.kna-title { font-size: 17px; }
		.kna-card .card-body { padding: .7rem; }
		.kna-stack-mobile { flex-direction: column; align-items: stretch !important; gap: .5rem; }
		.kna-mobile-cta { width: 100%; }
	}
	@media (max-width: 575.98px) {
		.kna-small { font-size: 11px !important; }
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2 kna-stack-mobile">
		<div>
			<div class="kna-title">Bank Account Masterlist</div>
		</div>
	</div>

	<ul class="nav nav-tabs mb-2" id="bamTabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link active kna-small" id="bamTabEmployeesLink" data-toggle="tab" href="#bamTabEmployees" role="tab">Employee Accounts</a>
		</li>
		<li class="nav-item">
			<a class="nav-link kna-small" id="bamTabCompanyLink" data-toggle="tab" href="#bamTabCompany" role="tab">Company / BizLink Settings</a>
		</li>
	</ul>

	<div class="tab-content">
	<div class="tab-pane fade show active" id="bamTabEmployees" role="tabpanel">

	<div class="card kna-card mb-2">
		<div class="card-body py-2">
			<div class="d-flex flex-wrap align-items-end justify-content-between kna-stack-mobile" style="gap:.5rem;">
				<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
					<div>
						<label class="kna-small kna-form-label mb-1">Search</label>
						<input type="text" class="form-control form-control-sm kna-small" id="filterKeyword" placeholder="Employee name, code, or bank" style="width:240px;">
					</div>
					<div>
						<button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetFilters" title="Clear filters" style="height:31px;width:31px;padding:0;">
							<i class="fas fa-sync-alt"></i>
						</button>
					</div>
				</div>
				<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
					<button type="button" class="btn btn-outline-secondary btn-sm kna-small kna-mobile-cta" id="btnOpenMassUpload">
						<i class="fas fa-file-upload mr-1"></i>Mass Upload
					</button>
					<button type="button" class="btn btn-primary btn-sm kna-small kna-mobile-cta" id="btnOpenNewRecord">
						<i class="fas fa-plus mr-1"></i>Add Bank Account
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card d-none d-md-block">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Bank Account List</div>
				<div class="kna-small text-muted" id="resultCount">0 record(s)</div>
			</div>
			<div class="table-responsive">
				<table class="table table-sm kna-table" id="bankAccountTable" style="width:100%">
					<thead>
						<tr>
							<th>Employee Code</th>
							<th>Employee Name</th>
							<th>Bank Account</th>
							<th>Account No.</th>
							<th>Status</th>
							<th class="text-center">Actions</th>
						</tr>
					</thead>
					<tbody id="bankAccountTbody"></tbody>
				</table>
			</div>
			<div class="d-flex justify-content-end mt-2">
				<nav aria-label="Bank account desktop pagination">
					<ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
				</nav>
			</div>
		</div>
	</div>

	<div class="card kna-card d-md-none">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Bank Account List</div>
				<div class="kna-small text-muted" id="resultCountMobile">0 record(s)</div>
			</div>
			<div class="kna-mobile-list" id="bankAccountMobileList"></div>
			<div class="d-flex justify-content-center mt-2">
				<nav aria-label="Bank account mobile pagination">
					<ul class="pagination pagination-sm mb-0" id="mobilePagination"></ul>
				</nav>
			</div>
		</div>
	</div>

	</div>

	<div class="tab-pane fade" id="bamTabCompany" role="tabpanel">
		<div class="row">
			<div class="col-lg-5 mb-3">
				<div class="card kna-card h-100">
					<div class="card-body p-4">
						<div class="d-flex align-items-center justify-content-between mb-3">
							<div class="kna-bizlink-section-title mb-0">Current Settings</div>
							<span class="kna-small text-muted" id="companySettingsUpdatedLabel"></span>
						</div>
						<div class="kna-detail-grid" id="companySettingsView">
							<div class="kna-detail-cell">
								<div class="kna-detail-label">Company Code</div>
								<div class="kna-detail-value" id="viewCompanyCode">—</div>
							</div>
							<div class="kna-detail-cell">
								<div class="kna-detail-label">Presenting Office Code</div>
								<div class="kna-detail-value" id="viewPresentingOfficeCode">—</div>
							</div>
							<div class="kna-detail-cell kna-detail-cell-span2">
								<div class="kna-detail-label">Company Account Number</div>
								<div class="kna-detail-value">
									<span class="kna-acct-masked" id="companyAccountMasked">Not set</span>
									<button type="button" class="kna-btn-reveal" id="btnRevealCompanyAccount" title="Reveal"><i class="fas fa-eye"></i></button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-lg-7 mb-3">
				<div class="card kna-card h-100">
					<div class="card-body p-4">
						<div class="kna-bizlink-section-title mb-3">Edit Settings</div>

						<form id="formCompanySettings">
							<div class="form-group">
								<label class="kna-form-label">Company Code <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="companyCode" maxlength="5" placeholder="e.g. 05972">
							</div>
							<div class="form-group">
								<label class="kna-form-label">Company Account Number (to be debited)</label>
								<input type="text" class="form-control" id="companyAccountNumber" maxlength="50" placeholder="Leave blank to keep the current account number" autocomplete="off">
								<small class="text-muted kna-small d-block mt-1">Stored encrypted, same as employee accounts. Leave blank when saving to keep the existing account number.</small>
							</div>
							<div class="form-group mb-0">
								<label class="kna-form-label">Presenting Office Code (receiving BPI branch code) <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="presentingOfficeCode" maxlength="3" placeholder="e.g. 463">
							</div>
						</form>
					</div>
					<div class="card-footer text-right py-2" style="background:#f8f9fa;">
						<button type="button" class="btn btn-primary btn-sm kna-small" id="btnSaveCompanySettings">
							<i class="fas fa-save mr-1"></i>Save Changes
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>
</div>

<!-- ==================== ADD / EDIT MODAL ==================== -->
<div class="modal fade" id="modalBankAccount" tabindex="-1" role="dialog" aria-labelledby="modalBankAccountLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header kna-modal-header">
				<h5 class="modal-title kna-small" id="modalBankAccountLabel">
					<i class="fas fa-university text-primary"></i>
					<span id="modalBankAccountTitleText">Add Bank Account</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<form id="formBankAccount">
					<div class="kna-form-section">
						<div class="kna-form-section-title">Employee</div>
						<div class="form-group mb-0">
							<label class="kna-small kna-form-label">Name <span class="text-danger">*</span></label>
							<select class="form-control form-control-sm kna-small" id="bankAccountEmployeeSelect" style="width:100%;"></select>
						</div>
					</div>

					<div class="kna-form-section mb-0">
						<div class="kna-form-section-title">Bank Details</div>
						<div class="form-group">
							<label class="kna-small kna-form-label">Bank <span class="text-danger">*</span></label>
							<input type="text" class="form-control form-control-sm kna-small" id="bankAccountBankName" maxlength="200" placeholder="e.g. Bank Of The Philippine Islands">
						</div>
						<div class="form-group mb-0">
							<label class="kna-small kna-form-label">Account Number <span class="text-danger">*</span></label>
							<input type="text" class="form-control form-control-sm kna-small" id="bankAccountNumber" maxlength="50" placeholder="e.g. 4639219262" autocomplete="off">
							<small class="text-muted kna-small">Stored encrypted. Only the last 4 digits are shown afterward.</small>
						</div>
					</div>

					<div class="kna-form-section mb-0 d-none" id="bankAccountStatusSection">
						<div class="kna-form-section-title">Status</div>
						<div class="form-group mb-0">
							<select class="form-control form-control-sm custom-select" id="bankAccountStatus">
								<option value="1">Active</option>
								<option value="0">Inactive</option>
							</select>
						</div>
					</div>
				</form>
			</div>

			<div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #e9ecef;">
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small" data-dismiss="modal">
					<i class="fas fa-times mr-1"></i>Cancel
				</button>
				<button type="button" class="btn btn-primary btn-sm kna-small" id="btnSaveBankAccount">
					<i class="fas fa-save mr-1"></i>Save
				</button>
			</div>
		</div>
	</div>
</div>

<!-- ==================== MASS UPLOAD MODAL ==================== -->
<div class="modal fade" id="modalMassUpload" tabindex="-1" role="dialog" aria-labelledby="modalMassUploadLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header kna-modal-header">
				<h5 class="modal-title kna-small" id="modalMassUploadLabel">
					<i class="fas fa-file-upload text-primary"></i>
					<span>Mass Upload Bank Accounts</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<div class="d-flex align-items-center justify-content-between mb-2">
					<div class="kna-small text-muted">File must include Employee Code, Bank Account, and Account No. columns.</div>
					<a href="<?= base_url('maintenance/bank-account-masterlist/api/download-template') ?>" class="kna-small">
						<i class="fas fa-download mr-1"></i>Download Template
					</a>
				</div>
				<div class="kna-upload-drop mb-2">
					<i class="fas fa-file-excel fa-2x mb-2" style="color:#c7d6e6;"></i>
					<div class="mb-2">Choose an .xlsx, .xls, or .csv file</div>
					<input type="file" class="form-control form-control-sm" id="massUploadFile" accept=".xlsx,.xls,.csv">
				</div>
				<div class="d-none" id="massUploadSummary">
					<div class="kna-small font-weight-bold mb-1" id="massUploadSummaryHeadline"></div>
					<div class="kna-upload-summary" id="massUploadSkippedList"></div>
				</div>
			</div>

			<div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #e9ecef;">
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small" data-dismiss="modal">
					<i class="fas fa-times mr-1"></i>Close
				</button>
				<button type="button" class="btn btn-primary btn-sm kna-small" id="btnSubmitMassUpload">
					<i class="fas fa-upload mr-1"></i>Upload
				</button>
			</div>
		</div>
	</div>
</div>
