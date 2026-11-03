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
	.kna-badge-ok { background: #e8f7ee; color: #17663a; }
	.kna-badge-warn { background: #fff4e6; color: #b35c00; }
	.kna-badge-danger { background: #fdeaea; color: #b02a2a; }
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
			<div class="kna-title">CA vs Liquidation Variance Report</div>
		</div>
		<button type="button" class="btn btn-success btn-sm kna-small kna-mobile-cta" id="btnExportExcel">
			<i class="fas fa-file-excel mr-1"></i>Export to Excel
		</button>
	</div>

	<div class="row mb-2">
		<div class="col-md-3 col-6 pr-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Total CA Amount</p>
					<p class="kna-kpi" id="sumCA">0.00</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 px-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Total Liquidated</p>
					<p class="kna-kpi" id="sumLiquidated">0.00</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 pr-md-2 pl-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Total Outstanding Variance</p>
					<p class="kna-kpi" id="sumVariance">0.00</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 pl-md-2">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Aging &gt; 30 days</p>
					<p class="kna-kpi" id="sumAging">0</p>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body py-2">
			<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
				<div>
					<label class="kna-small kna-form-label mb-1">Date Range</label>
					<input type="text" class="form-control form-control-sm kna-small" id="filterDateRange" placeholder="Select range" autocomplete="off" readonly style="width:180px;">
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Cost Center</label>
					<select class="form-control form-control-sm kna-small" id="filterCostCenter" style="width:200px;">
						<option value="">All Cost Centers</option>
					</select>
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Outstanding Only</label>
					<select class="form-control form-control-sm kna-small" id="filterOutstanding" style="width:150px;">
						<option value="">All Records</option>
						<option value="1">Outstanding Only</option>
					</select>
				</div>
				<div>
					<button type="button" class="btn btn-primary btn-sm kna-small" id="btnApply" style="height:31px;">Apply</button>
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
				<div class="kna-small text-muted">Cash Advances</div>
				<div class="kna-small text-muted" id="resultCount">0 record(s)</div>
			</div>
			<div class="table-responsive">
				<table class="table table-sm kna-table" id="varianceTable" style="width:100%;">
					<thead>
						<tr>
							<th style="min-width:130px;">CA No.</th>
							<th style="min-width:150px;">Employee</th>
							<th style="min-width:180px;">Cost Center</th>
							<th style="min-width:110px;" class="text-right">CA Amount</th>
							<th style="min-width:110px;" class="text-right">Liquidated</th>
							<th style="min-width:110px;" class="text-right">Variance</th>
							<th style="min-width:130px;">Status</th>
							<th style="min-width:100px;" class="text-center">Age (days)</th>
						</tr>
					</thead>
					<tbody id="varianceTbody"></tbody>
				</table>
			</div>
			<div class="d-flex justify-content-end mt-2">
				<nav aria-label="Variance desktop pagination">
					<ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
				</nav>
			</div>
		</div>
	</div>

	<div class="card kna-card d-md-none">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Cash Advances</div>
				<div class="kna-small text-muted" id="resultCountMobile">0 record(s)</div>
			</div>
			<div class="kna-mobile-list" id="varianceMobileList"></div>
			<div class="text-center mt-2">
				<button type="button" class="btn btn-outline-primary btn-sm kna-small" id="btnLoadMoreMobile" style="display:none;">
					Load More
				</button>
			</div>
		</div>
	</div>
</div>
