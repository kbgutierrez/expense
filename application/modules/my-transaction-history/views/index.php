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
	.kna-badge { padding: .2rem .4rem; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; }
	.kna-badge-ca { background: var(--brand-100); color: var(--brand-800); }
	.kna-badge-rpl { background: #fff4e5; color: #a15c00; }
	.kna-badge-rmb { background: #e8f7ee; color: #17663a; }
	.kna-badge-lq { background: var(--accent-100); color: var(--brand-900); }
	.kna-mobile-list .kna-item { border: 1px solid #dde3eb; border-radius: 6px; padding: .65rem; margin-bottom: .5rem; background: #fff; }
	.kna-mobile-list .kna-item:last-child { margin-bottom: 0; }
	.kna-mobile-list .kna-row { display: flex; align-items: center; justify-content: space-between; gap: .45rem; margin-bottom: .25rem; }
	.kna-mobile-list .kna-row:last-child { margin-bottom: 0; }

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
			<div class="kna-title">My Transaction History</div>
		</div>
		<button type="button" class="btn btn-success btn-sm kna-small kna-mobile-cta" id="btnDownloadExcel">
			<i class="fas fa-file-excel mr-1"></i>Download Excel
		</button>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body py-2">
			<div class="d-flex flex-wrap" style="gap:1.5rem;">
				<div>
					<div class="kna-small text-muted">Employee</div>
					<div class="kna-small font-weight-bold" id="profileEmployee">-</div>
				</div>
				<div>
					<div class="kna-small text-muted">Position</div>
					<div class="kna-small font-weight-bold" id="profilePosition">-</div>
				</div>
				<div>
					<div class="kna-small text-muted">Department</div>
					<div class="kna-small font-weight-bold" id="profileDepartment">-</div>
				</div>
				<div>
					<div class="kna-small text-muted">Company</div>
					<div class="kna-small font-weight-bold" id="profileCompany">-</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row mb-2">
		<div class="col-md-3 col-6 pr-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Total Transactions</p>
					<p class="kna-kpi" id="sumTotal">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 px-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Cash Advance</p>
					<p class="kna-kpi" id="sumCa">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 pr-md-2 pl-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Reimbursement</p>
					<p class="kna-kpi" id="sumRmb">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 pl-md-2">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Liquidation / Replenishment</p>
					<p class="kna-kpi" id="sumLqRpl">0</p>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body py-2">
			<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
				<div>
					<label class="kna-small kna-form-label mb-1">Date Range</label>
					<input type="text" class="form-control form-control-sm kna-small" id="filterDateRange" placeholder="All time" autocomplete="off" readonly style="width:180px;">
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Search</label>
					<input type="text" class="form-control form-control-sm kna-small" id="filterKeyword" placeholder="Reference no. or description" style="width:240px;">
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Type</label>
					<select class="form-control form-control-sm kna-small" id="filterType" style="width:170px;">
						<option value="">All Types</option>
						<option value="CASH_ADVANCE">Cash Advance</option>
						<option value="REPLENISHMENT">Replenishment</option>
						<option value="REIMBURSEMENT">Reimbursement</option>
						<option value="LIQUIDATION">Liquidation</option>
					</select>
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Status</label>
					<select class="form-control form-control-sm kna-small" id="filterStatus" style="width:170px;">
						<option value="">All Statuses</option>
						<option value="Submitted">Submitted</option>
						<option value="Approved">Approved</option>
						<option value="For Liquidation">For Liquidation</option>
						<option value="Paid">Paid</option>
						<option value="RMB_FOR_RELEASE">For Release</option>
						<option value="Completed">Completed</option>
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
				<div class="kna-small text-muted">Transaction List</div>
				<div class="kna-small text-muted" id="resultCount">0 record(s)</div>
			</div>
			<div class="table-responsive">
				<table class="table table-sm kna-table" id="historyTable" style="width:100%;">
					<thead>
						<tr>
							<th style="min-width:140px;">Type</th>
							<th style="min-width:130px;">Reference No.</th>
							<th style="min-width:260px;">Description</th>
							<th style="min-width:190px;">Cost Center</th>
							<th style="min-width:110px;" class="text-right">Amount</th>
							<th style="min-width:130px;">Status</th>
							<th style="min-width:105px;">Needed Date</th>
							<th style="min-width:105px;">Created Date</th>
							<th style="min-width:105px;">Updated Date</th>
						</tr>
					</thead>
					<tbody id="historyTbody"></tbody>
				</table>
			</div>
			<div class="d-flex justify-content-end mt-2">
				<nav aria-label="Transaction history desktop pagination">
					<ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
				</nav>
			</div>
		</div>
	</div>

	<div class="card kna-card d-md-none">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Transaction List</div>
				<div class="kna-small text-muted" id="resultCountMobile">0 record(s)</div>
			</div>
			<div class="kna-mobile-list" id="historyMobileList"></div>
		</div>
	</div>
</div>
