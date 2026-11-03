<style>
	.kna-page { padding: 12px 14px; }
	.kna-card { border: 1px solid #d9e0e7 !important; border-radius: 6px; background: #ffffff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
	.kna-card .card-body { padding: .85rem; }
	.kna-title { font-size: 20px; font-weight: 600; margin: 0; line-height: 1.2; }
	.kna-small { font-size: 12px !important; line-height: 1.35; }
	.kna-kpi { font-size: 19px; line-height: 1.15; font-weight: 600; margin: 0; color: var(--brand-900); }
	.kna-kpi-caption { font-size: 11px; color: #6c757d; margin: 0; }
	.kna-form-label { margin-bottom: .3rem; font-weight: 600; color: #34495e; }
	.kna-table td, .kna-table th { font-size: 12px !important; padding: .5rem .45rem; vertical-align: middle; white-space: nowrap; }
	.kna-actions .btn { padding: .3rem .55rem; font-size: 12px; }
	.kna-badge { padding: .2rem .4rem; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; }
	.kna-badge-active { background: #e8f7ee; color: #17663a; }
	.kna-badge-inactive { background: #eef2f7; color: #495869; }
	.kna-badge-locked { background: #fdeeee; color: #a12626; }
	.kna-badge-person { background: #e6f4ff; color: #0056b3; }
	.kna-badge-department { background: #fff4e0; color: #8a5a00; }
	.kna-badge-company { background: var(--brand-100); color: var(--brand-800); }
	.kna-badge-topup { background: #e8f7ee; color: #17663a; }
	.kna-badge-adjustment { background: #fff4e0; color: #8a5a00; }
	.kna-mobile-list .kna-item { border: 1px solid #dde3eb; border-radius: 6px; padding: .65rem; margin-bottom: .5rem; background: #fff; }
	.kna-mobile-list .kna-item:last-child { margin-bottom: 0; }
	.kna-mobile-list .kna-row { display: flex; align-items: center; justify-content: space-between; gap: .45rem; margin-bottom: .25rem; }
	.kna-mobile-list .kna-row:last-child { margin-bottom: 0; }
	.text-amount-neg { color: #a12626; }
	.text-amount-pos { color: #17663a; }

	/* ---- Tabs ---- */
	.kna-rf-tabs { border-bottom: 1px solid #d9e0e7; margin-bottom: .85rem; flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
	.kna-rf-tabs::-webkit-scrollbar { height: 4px; }
	.kna-rf-tabs .nav-link { font-size: 13px; font-weight: 600; color: #4a5a6a; border: none; border-bottom: 2px solid transparent; padding: .55rem .9rem; white-space: nowrap; }
	.kna-rf-tabs .nav-link.active { color: var(--brand-700); border-bottom-color: var(--brand-700); background: transparent; }
	.kna-rf-tabs .nav-link i { margin-right: 5px; }

	/* ---- Modal styling (reused expense-types pattern) ---- */
	.kna-modal-header { background: #f8f9fa; border-bottom: 1px solid #e9ecef; padding: .75rem 1rem; }
	.kna-modal-header .modal-title { font-weight: 600; color: var(--brand-900); font-size: 14px; display: flex; align-items: center; gap: 6px; }
	.kna-modal-header .close { padding: .5rem; margin: -.5rem -.5rem -.5rem auto; }
	.kna-form-section { margin-bottom: 1rem; }
	.kna-form-section-title { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; font-weight: 700; color: #6c757d; margin-bottom: .6rem; padding-bottom: .35rem; border-bottom: 1px solid #eef2f7; }
	.form-control-sm, .custom-select-sm { font-size: 12px; }
	.kna-balance-hint { font-size: 11px; color: #6c757d; margin-top: .25rem; }
	.kna-history-list .kna-item { border-bottom: 1px solid #eef2f7; padding: .5rem 0; }
	.kna-history-list .kna-item:last-child { border-bottom: none; }

	@media (max-width: 991.98px) {
		.kna-page { padding: 10px; }
		.kna-title { font-size: 17px; }
		.kna-card .card-body { padding: .7rem; }
		.kna-stack-mobile { flex-direction: column; align-items: stretch !important; gap: .5rem; }
		.kna-mobile-cta { width: 100%; }
	}
	@media (max-width: 767.98px) {
		.kna-rf-tabs .nav-link { font-size: 12px; padding: .5rem .65rem; }
	}
	@media (max-width: 575.98px) {
		.kna-small { font-size: 11px !important; }
		.kna-kpi { font-size: 16px; }
	}

	/* Match select2's rendered size to the surrounding form-control-sm fields */
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
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2 kna-stack-mobile">
		<div>
			<div class="kna-title">Revolving Fund</div>
		</div>
	</div>

	<ul class="nav kna-rf-tabs" id="rfTabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" id="rf-tab-funds" data-toggle="tab" href="#rf-pane-funds" role="tab" aria-controls="rf-pane-funds" aria-selected="true">
				<i class="fas fa-wallet"></i>Revolving Funds
			</a>
		</li>
	</ul>

	<div class="tab-content" id="rfTabsContent">

		<!-- ==================== REVOLVING FUNDS TAB (fund + holder together) ==================== -->
		<div class="tab-pane fade show active" id="rf-pane-funds" role="tabpanel" aria-labelledby="rf-tab-funds">

			<div class="row mb-2">
				<div class="col-md-3 col-6 pr-md-2 mb-2 mb-md-0">
					<div class="card kna-card h-100">
						<div class="card-body">
							<p class="kna-kpi-caption">Total Funds</p>
							<p class="kna-kpi" id="rfSumTotal">0</p>
						</div>
					</div>
				</div>
				<div class="col-md-3 col-6 px-md-2 mb-2 mb-md-0">
					<div class="card kna-card h-100">
						<div class="card-body">
							<p class="kna-kpi-caption">Active</p>
							<p class="kna-kpi" id="rfSumActive">0</p>
						</div>
					</div>
				</div>
				<div class="col-md-3 col-6 pr-md-2 pl-md-2 mb-2 mb-md-0">
					<div class="card kna-card h-100">
						<div class="card-body">
							<p class="kna-kpi-caption">Inactive / Locked</p>
							<p class="kna-kpi" id="rfSumInactive">0</p>
						</div>
					</div>
				</div>
				<div class="col-md-3 col-6 pl-md-2">
					<div class="card kna-card h-100">
						<div class="card-body">
							<p class="kna-kpi-caption">Total Available Balance</p>
							<p class="kna-kpi" id="rfSumBalance">₱0.00</p>
						</div>
					</div>
				</div>
			</div>

			<div class="card kna-card mb-2">
				<div class="card-body py-2">
					<div class="d-flex flex-wrap align-items-end justify-content-between kna-stack-mobile" style="gap:.5rem;">
						<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
							<div>
								<label class="kna-small kna-form-label mb-1">Search</label>
								<input type="text" class="form-control form-control-sm kna-small" id="rfFilterKeyword" placeholder="Search fund code or holder name" style="width:220px;">
							</div>
							<div>
								<label class="kna-small kna-form-label mb-1">Status</label>
								<select class="form-control form-control-sm kna-small" id="rfFilterStatus" style="width:130px;">
									<option value="">All Status</option>
									<option value="RF_ACTIVE">Active</option>
									<option value="RF_INACTIVE">Inactive</option>
									<option value="RF_LOCKED">Locked</option>
								</select>
							</div>
							<div>
								<button type="button" class="btn btn-outline-secondary btn-sm" id="rfBtnReset" title="Clear filters" style="height:31px;width:31px;padding:0;">
									<i class="fas fa-sync-alt"></i>
								</button>
							</div>
						</div>
						<button type="button" class="btn btn-primary btn-sm kna-small kna-mobile-cta" id="btnOpenNewFund">
							<i class="fas fa-plus mr-1"></i>Add Fund
						</button>
					</div>
				</div>
			</div>

			<div class="card kna-card d-none d-md-block">
				<div class="card-body">
					<div class="d-flex align-items-center justify-content-between mb-2">
						<div class="kna-small text-muted">Revolving Fund List</div>
						<div class="kna-small text-muted" id="rfResultCount">0 record(s)</div>
					</div>
					<div class="table-responsive">
						<table class="table table-sm kna-table" id="rfFundTable" style="width:100%">
							<thead>
								<tr>
									<th>Fund Code</th>
									<th>Holder</th>
									<th class="text-right">Available Balance</th>
									<th>Status</th>
									<th class="text-center">Actions</th>
								</tr>
							</thead>
							<tbody id="rfFundTbody"></tbody>
						</table>
					</div>
					<div class="d-flex justify-content-end mt-2">
						<nav aria-label="Fund desktop pagination">
							<ul class="pagination pagination-sm mb-0" id="rfFundPagination"></ul>
						</nav>
					</div>
				</div>
			</div>

			<div class="card kna-card d-md-none">
				<div class="card-body">
					<div class="d-flex align-items-center justify-content-between mb-2">
						<div class="kna-small text-muted">Revolving Fund List</div>
						<div class="kna-small text-muted" id="rfResultCountMobile">0 record(s)</div>
					</div>
					<div class="kna-mobile-list" id="rfFundMobileList"></div>
				</div>
			</div>
		</div>

	</div>
</div>

<!-- ==================== ADD / EDIT FUND MODAL ==================== -->
<div class="modal fade" id="modalRfFund" tabindex="-1" role="dialog" aria-labelledby="modalRfFundLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<div class="modal-header kna-modal-header">
				<h5 class="modal-title kna-small" id="modalRfFundLabel">
					<i class="fas fa-wallet text-primary"></i>
					<span id="modalRfFundTitleText">Add Fund</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<form id="formRfFund">
					<input type="hidden" id="rfFundMode" value="create">
					<input type="hidden" id="rfFundId" value="">

					<div class="kna-form-section">
						<div class="kna-form-section-title">Who is this fund for?</div>
						<div class="row">
							<div class="col-md-4 form-group">
								<label class="kna-small kna-form-label">Type <span class="text-danger">*</span></label>
								<select class="form-control form-control-sm custom-select" id="rfFundScopeType">
									<option value="PERSON">Person</option>
									<option value="DEPARTMENT">Department</option>
									<option value="COMPANY">Company</option>
								</select>
							</div>
							<div class="col-md-8 form-group">
								<label class="kna-small kna-form-label">Holder <span class="text-danger">*</span></label>
								<select class="form-control form-control-sm kna-small" id="rfFundScopeSelect" style="width:100%;"></select>
								<input type="text" class="form-control form-control-sm kna-small d-none" id="rfFundScopeText" maxlength="200" placeholder="e.g. Main Office">
							</div>
						</div>
					</div>

					<div class="kna-form-section" id="rfFundOpeningSection">
						<div class="kna-form-section-title">Starting Amount</div>
						<div class="form-group mb-2">
							<label class="kna-small kna-form-label">Opening Balance <span class="text-danger">*</span></label>
							<input type="number" class="form-control form-control-sm" id="rfFundOpening" min="0" step="0.01" placeholder="0.00">
						</div>
						<a href="#" class="kna-small" id="rfFundToggleAdvanced">+ More options (status, remarks, allow negative balance)</a>
					</div>

					<div class="kna-form-section mb-0" id="rfFundAdvancedSection" style="display:none;">
						<div class="kna-form-section-title">Settings</div>
						<div class="row">
							<div class="col-md-6 form-group">
								<label class="kna-small kna-form-label">Status</label>
								<select class="form-control form-control-sm custom-select" id="rfFundStatus">
									<option value="RF_ACTIVE">Active</option>
									<option value="RF_INACTIVE">Inactive</option>
									<option value="RF_LOCKED">Locked</option>
								</select>
							</div>
							<div class="col-md-6 form-group d-flex flex-column justify-content-end">
								<div class="custom-control custom-checkbox">
									<input type="checkbox" class="custom-control-input" id="rfFundAllowNegative">
									<label class="custom-control-label kna-small" for="rfFundAllowNegative">Unlimited fund (allow negative balance)</label>
								</div>
								<div class="custom-control custom-checkbox mb-2">
									<input type="checkbox" class="custom-control-input" id="rfFundAllowSelfCashIn">
									<label class="custom-control-label kna-small" for="rfFundAllowSelfCashIn">Allow holder self cash in</label>
								</div>
							</div>
						</div>
						<div class="form-group mb-0">
							<label class="kna-small kna-form-label">Remarks</label>
							<input type="text" class="form-control form-control-sm" id="rfFundRemarks" maxlength="500" placeholder="Optional notes">
						</div>
					</div>
				</form>
			</div>

			<div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #e9ecef;">
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small" data-dismiss="modal">
					<i class="fas fa-times mr-1"></i>Cancel
				</button>
				<button type="button" class="btn btn-primary btn-sm kna-small" id="btnSaveRfFund">
					<i class="fas fa-save mr-1"></i>Save
				</button>
			</div>
		</div>
	</div>
</div>

<!-- ==================== ADD MONEY MODAL ==================== -->
<div class="modal fade" id="modalRfLedger" tabindex="-1" role="dialog" aria-labelledby="modalRfLedgerLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<div class="modal-header kna-modal-header">
				<h5 class="modal-title kna-small" id="modalRfLedgerLabel">
					<i class="fas fa-exchange-alt text-primary"></i>
					<span>Adjust Fund</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<form id="formRfLedger">
					<input type="hidden" id="rfLedgerFundId" value="">
					<div class="kna-form-section">
						<div class="kna-form-section-title">Fund</div>
						<div class="form-group mb-0">
							<div class="kna-small font-weight-bold" id="rfLedgerFundLabel">-</div>
							<div class="kna-balance-hint" id="rfLedgerBalanceHint">Available balance: —</div>
						</div>
					</div>

					<div class="kna-form-section mb-0">
						<div class="kna-form-section-title">Transaction</div>
						<div class="row">
							<div class="col-md-4 form-group">
								<label class="kna-small kna-form-label">Date <span class="text-danger">*</span></label>
								<input type="text" class="form-control form-control-sm kna-small" id="rfLedgerDate" placeholder="YYYY-MM-DD">
							</div>
							<div class="col-md-4 form-group">
								<label class="kna-small kna-form-label">Type <span class="text-danger">*</span></label>
								<select class="form-control form-control-sm custom-select" id="rfLedgerType">
									<option value="RF_TOPUP">Top-up</option>
									<option value="RF_ADJUSTMENT">Adjustment</option>
								</select>
							</div>
							<div class="col-md-4 form-group">
								<label class="kna-small kna-form-label">Amount <span class="text-danger">*</span></label>
								<input type="number" class="form-control form-control-sm" id="rfLedgerAmount" step="0.01" placeholder="0.00">
							</div>
						</div>
						<div class="form-group mb-0">
							<label class="kna-small kna-form-label">Remarks <span class="text-danger">*</span></label>
							<input type="text" class="form-control form-control-sm" id="rfLedgerRemarks" maxlength="500" placeholder="Reason or note">
						</div>
					</div>
				</form>
			</div>

			<div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #e9ecef;">
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small" data-dismiss="modal">
					<i class="fas fa-times mr-1"></i>Cancel
				</button>
				<button type="button" class="btn btn-primary btn-sm kna-small" id="btnSaveRfLedger">
					<i class="fas fa-save mr-1"></i>Save
				</button>
			</div>
		</div>
	</div>
</div>

<!-- ==================== HISTORY MODAL ==================== -->
<div class="modal fade" id="modalRfHistory" tabindex="-1" role="dialog" aria-labelledby="modalRfHistoryLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<div class="modal-header kna-modal-header">
				<h5 class="modal-title kna-small" id="modalRfHistoryLabel">
					<i class="fas fa-history text-primary"></i>
					<span id="modalRfHistoryTitleText">Fund History</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<div class="kna-history-list" id="rfHistoryList"></div>
			</div>

			<div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #e9ecef;">
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small" data-dismiss="modal">
					<i class="fas fa-times mr-1"></i>Close
				</button>
			</div>
		</div>
	</div>
</div>
