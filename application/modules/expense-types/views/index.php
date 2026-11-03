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
	.kna-badge-sd { background: var(--brand-100); color: var(--brand-800); }
	.kna-badge-ga { background: var(--accent-100); color: var(--brand-900); }
	.kna-mobile-list .kna-item { border: 1px solid #dde3eb; border-radius: 6px; padding: .65rem; margin-bottom: .5rem; background: #fff; }
	.kna-mobile-list .kna-item:last-child { margin-bottom: 0; }
	.kna-mobile-list .kna-row { display: flex; align-items: center; justify-content: space-between; gap: .45rem; margin-bottom: .25rem; }
	.kna-mobile-list .kna-row:last-child { margin-bottom: 0; }

	/* ---- Professional Modal Styles ---- */
	.kna-modal-header {
		background: #f8f9fa;
		border-bottom: 1px solid #e9ecef;
		padding: .75rem 1rem;
	}
	.kna-modal-header .modal-title {
		font-weight: 600;
		color: var(--brand-900);
		font-size: 14px;
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.kna-modal-header .close {
		padding: .5rem;
		margin: -.5rem -.5rem -.5rem auto;
	}

	.kna-form-section {
		margin-bottom: 1rem;
	}
	.kna-form-section-title {
		font-size: 11px;
		text-transform: uppercase;
		letter-spacing: .4px;
		font-weight: 700;
		color: #6c757d;
		margin-bottom: .6rem;
		padding-bottom: .35rem;
		border-bottom: 1px solid #eef2f7;
	}

	.form-control-sm, .custom-select-sm {
		font-size: 12px;
	}

	.kna-view-header {
		background: #f8f9fa;
		border: 1px solid #e9ecef;
		border-radius: 6px;
		padding: 1rem;
		margin-bottom: 1rem;
	}
	.kna-view-header .kna-view-code {
		font-size: 18px;
		font-weight: 700;
		color: var(--brand-900);
		margin-bottom: .25rem;
	}
	.kna-view-header .kna-view-meta {
		display: flex;
		align-items: center;
		gap: .5rem;
		flex-wrap: wrap;
	}

	.kna-view-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: .75rem;
		margin-bottom: 1rem;
	}
	.kna-view-item {
		background: #fff;
		border: 1px solid #eef2f7;
		border-radius: 5px;
		padding: .6rem .75rem;
	}
	.kna-view-item.full-width {
		grid-column: 1 / -1;
	}
	.kna-view-label {
		font-size: 11px;
		color: #6c757d;
		text-transform: uppercase;
		letter-spacing: .3px;
		margin-bottom: .2rem;
		font-weight: 600;
	}
	.kna-view-value {
		font-size: 13px;
		color: #1d2a3a;
		font-weight: 500;
		word-break: break-word;
	}
	.kna-view-value.text-muted {
		color: #6c757d;
		font-weight: 400;
	}

	@media (max-width: 767.98px) {
		.kna-view-grid {
			grid-template-columns: 1fr 1fr;
		}
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
		.kna-view-grid { grid-template-columns: 1fr; }
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2 kna-stack-mobile">
		<div>
			<div class="kna-title">Expense Types</div>
		</div>
		<button type="button" class="btn btn-primary btn-sm kna-small kna-mobile-cta" id="btnOpenNewExpenseType">
			<i class="fas fa-plus mr-1"></i>New Expense Type
		</button>
	</div>

	<div class="row mb-2">
		<div class="col-md-3 col-6 pr-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Total Categories</p>
					<p class="kna-kpi" id="sumTotal">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 px-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Active</p>
					<p class="kna-kpi" id="sumActive">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 pr-md-2 pl-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Inactive</p>
					<p class="kna-kpi" id="sumInactive">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 pl-md-2">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Categories</p>
					<p class="kna-kpi" id="sumCategory">0 / 0</p>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body py-2">
			<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
				<div>
					<label class="kna-small kna-form-label mb-1">Search</label>
					<input type="text" class="form-control form-control-sm kna-small" id="filterKeyword" placeholder="Code, name or description" style="width:220px;">
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Status</label>
					<select class="form-control form-control-sm kna-small" id="filterStatus" style="width:140px;">
						<option value="">All Status</option>
						<option value="CAT_ACTIVE">Active</option>
						<option value="CAT_INACTIVE">Inactive</option>
					</select>
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Category</label>
					<select class="form-control form-control-sm kna-small" id="filterCategory" style="width:120px;">
						<option value="">All</option>
						<option value="SD">SD</option>
						<option value="GA">GA</option>
					</select>
				</div>
				<div>
					<button type="button" class="btn btn-outline-secondary btn-sm" id="btnReset" title="Clear filters" style="height:31px;width:31px;padding:0;">
						<i class="fas fa-sync-alt"></i>
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card d-none d-md-block">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Expense Type List</div>
				<div class="kna-small text-muted" id="resultCount">0 record(s)</div>
			</div>
			<div class="table-responsive">
				<table class="table table-sm kna-table" id="expenseTypesTable" style="width:100%;">
					<thead>
						<tr>
							<th style="min-width:130px;">Expense Code</th>
							<th style="min-width:260px;">Description</th>
							<th style="min-width:260px;">Long Text</th>
							<th style="min-width:190px;">Short Text</th>
							<th style="min-width:120px;">Category</th>
							<th style="min-width:120px;">Status</th>
							<th style="min-width:150px;">Created By</th>
							<th style="min-width:105px;">Created Date</th>
							<th style="min-width:150px;">Updated By</th>
							<th style="min-width:105px;">Updated Date</th>
							<th style="min-width:120px;" class="text-center">Actions</th>
						</tr>
					</thead>
					<tbody id="expenseTypesTbody"></tbody>
				</table>
			</div>
			<div class="d-flex justify-content-end mt-2">
				<nav aria-label="Expense types desktop pagination">
					<ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
				</nav>
			</div>
		</div>
	</div>

	<div class="card kna-card d-md-none">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Expense Type List</div>
				<div class="kna-small text-muted" id="resultCountMobile">0 record(s)</div>
			</div>
			<div class="kna-mobile-list" id="expenseTypesMobileList"></div>
			<div class="text-center mt-2">
				<button type="button" class="btn btn-outline-primary btn-sm kna-small" id="btnLoadMoreMobile" style="display:none;">
					Load More
				</button>
			</div>
		</div>
	</div>
</div>

<!-- ==================== ADD / EDIT MODAL ==================== -->
<div class="modal fade" id="modalExpenseType" tabindex="-1" role="dialog" aria-labelledby="modalExpenseTypeLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<div class="modal-header kna-modal-header">
				<h5 class="modal-title kna-small" id="modalExpenseTypeLabel">
					<i class="fas fa-file-invoice-dollar text-primary"></i>
					<span id="modalExpenseTypeTitleText">Expense Type</span>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<form id="formExpenseType">
					<input type="hidden" id="expenseTypeMode" value="create">
					<input type="hidden" id="expenseTypeId" value="">

					<!-- Basic Information -->
					<div class="kna-form-section">
						<div class="kna-form-section-title">Basic Information</div>
						<div class="row">
							<div class="col-md-6 form-group">
								<label class="kna-small kna-form-label">Expense Code <span class="text-danger">*</span></label>
								<div class="input-group input-group-sm">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="fas fa-barcode"></i></span>
									</div>
									<input type="text" class="form-control" id="expenseTypeCode" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="e.g. 6000000101" required>
								</div>
							</div>
							<div class="col-md-6 form-group">
								<label class="kna-small kna-form-label">Category (SD / GA) <span class="text-danger">*</span></label>
								<div class="input-group input-group-sm">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="fas fa-building"></i></span>
									</div>
									<select class="form-control custom-select" id="expenseTypeCategory" required>
										<option value="SD">SD — Sales & Distribution</option>
										<option value="GA">GA — General & Administrative</option>
									</select>
								</div>
							</div>
						</div>

						<div class="form-group">
							<label class="kna-small kna-form-label">Description <span class="text-danger">*</span></label>
							<input type="text" class="form-control form-control-sm" id="expenseTypeCategoryName" maxlength="200" placeholder="Enter full description" required>
						</div>

						<div class="row">
							<div class="col-md-6 form-group">
								<label class="kna-small kna-form-label">Long Text</label>
								<input type="text" class="form-control form-control-sm" id="expenseTypeLongText" maxlength="300" placeholder="Extended description">
							</div>
							<div class="col-md-6 form-group">
								<label class="kna-small kna-form-label">Short Text</label>
								<input type="text" class="form-control form-control-sm" id="expenseTypeShortText" maxlength="100" placeholder="Abbreviated label">
							</div>
						</div>
					</div>

					<!-- Settings -->
					<div class="kna-form-section mb-0">
						<div class="kna-form-section-title">Settings</div>
						<div class="row">
							<div class="col-md-6 form-group mb-0">
								<label class="kna-small kna-form-label">Status</label>
								<div class="input-group input-group-sm">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
									</div>
									<select class="form-control custom-select" id="expenseTypeStatus">
										<option value="CAT_ACTIVE">Active</option>
										<option value="CAT_INACTIVE">Inactive</option>
									</select>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>

			<div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #e9ecef;">
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small" data-dismiss="modal">
					<i class="fas fa-times mr-1"></i>Cancel
				</button>
				<button type="button" class="btn btn-primary btn-sm kna-small" id="btnSaveExpenseType">
					<i class="fas fa-save mr-1"></i>Save
				</button>
			</div>
		</div>
	</div>
</div>

<!-- ==================== VIEW MODAL ==================== -->
<div class="modal fade" id="modalViewExpenseType" tabindex="-1" role="dialog" aria-labelledby="modalViewExpenseTypeLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<div class="modal-header kna-modal-header">
				<h5 class="modal-title kna-small" id="modalViewExpenseTypeLabel">
					<i class="fas fa-eye text-info mr-1"></i>Expense Type Details
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">
				<!-- Header Card -->
				<div class="kna-view-header">
					<div class="kna-view-code" id="viewExpenseTypeCode">-</div>
					<div class="kna-view-meta">
						<span id="viewExpenseTypeStatus">-</span>
						<span id="viewExpenseTypeCategory">-</span>
					</div>
				</div>

				<!-- Audit & IDs -->
				<div class="kna-view-grid">
					<div class="kna-view-item">
						<div class="kna-view-label">Record ID</div>
						<div class="kna-view-value" id="viewExpenseTypeId">-</div>
					</div>
					<div class="kna-view-item">
						<div class="kna-view-label">Created By</div>
						<div class="kna-view-value" id="viewExpenseTypeCreatedBy">-</div>
					</div>
					<div class="kna-view-item">
						<div class="kna-view-label">Created Date</div>
						<div class="kna-view-value" id="viewExpenseTypeCreatedDate">-</div>
					</div>
					<div class="kna-view-item">
						<div class="kna-view-label">Updated By</div>
						<div class="kna-view-value" id="viewExpenseTypeUpdatedBy">-</div>
					</div>
					<div class="kna-view-item">
						<div class="kna-view-label">Updated Date</div>
						<div class="kna-view-value" id="viewExpenseTypeUpdatedDate">-</div>
					</div>
				</div>

				<!-- Description -->
				<div class="kna-view-grid">
					<div class="kna-view-item full-width">
						<div class="kna-view-label">Description</div>
						<div class="kna-view-value" id="viewExpenseTypeCategoryName">-</div>
					</div>
				</div>

				<!-- Text Variants -->
				<div class="kna-view-grid">
					<div class="kna-view-item">
						<div class="kna-view-label">Long Text</div>
						<div class="kna-view-value text-muted" id="viewExpenseTypeLongText">-</div>
					</div>
					<div class="kna-view-item">
						<div class="kna-view-label">Short Text</div>
						<div class="kna-view-value text-muted" id="viewExpenseTypeShortText">-</div>
					</div>
				</div>
			</div>

			<div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #e9ecef;">
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small" data-dismiss="modal">
					<i class="fas fa-times mr-1"></i>Close
				</button>
			</div>
		</div>
	</div>
</div>