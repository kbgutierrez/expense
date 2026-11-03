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
	.kna-badge-advised { background: #fff4e5; color: #a15c00; }
	.kna-badge-released { background: #e8f7ee; color: #17663a; }
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
			<div class="kna-title">Payment Advisory &amp; Release Register</div>
		</div>
		<button type="button" class="btn btn-success btn-sm kna-small kna-mobile-cta" id="btnDownloadExcel">
			<i class="fas fa-file-excel mr-1"></i>Download Excel
		</button>
	</div>

	<div class="row mb-2">
		<div class="col-md-3 col-6 pr-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Total Events</p>
					<p class="kna-kpi" id="sumTotal">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 px-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Advised</p>
					<p class="kna-kpi" id="sumAdvised">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 pr-md-2 pl-md-2 mb-2 mb-md-0">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Released</p>
					<p class="kna-kpi" id="sumReleased">0</p>
				</div>
			</div>
		</div>
		<div class="col-md-3 col-6 pl-md-2">
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Total Amount</p>
					<p class="kna-kpi" id="sumAmount">0.00</p>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body py-2">
			<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
				<div>
					<label class="kna-small kna-form-label mb-1">Search</label>
					<input type="text" class="form-control form-control-sm kna-small" id="filterKeyword" placeholder="Reference no., requester, actor" style="width:240px;">
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Event</label>
					<select class="form-control form-control-sm kna-small" id="filterEvent" style="width:140px;">
						<option value="">All Events</option>
						<option value="ADVISED">Advised</option>
						<option value="RELEASED">Released</option>
					</select>
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Type</label>
					<select class="form-control form-control-sm kna-small" id="filterType" style="width:170px;">
						<option value="">All Types</option>
						<option value="CASH_ADVANCE">Cash Advance</option>
						<option value="REIMBURSEMENT">Reimbursement</option>
						<option value="LIQUIDATION">Liquidation</option>
						<option value="REPLENISHMENT">Replenishment</option>
					</select>
				</div>
				<div>
					<label class="kna-small kna-form-label mb-1">Date Range</label>
					<input type="text" class="form-control form-control-sm kna-small" id="filterDateRange" placeholder="Select range" autocomplete="off" readonly style="width:180px;">
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
				<div class="kna-small text-muted">Payment Advisory &amp; Release Register</div>
				<div class="kna-small text-muted" id="resultCount">0 record(s)</div>
			</div>
			<div class="table-responsive">
				<table class="table table-sm kna-table" id="registerTable" style="width:100%;">
					<thead>
						<tr>
							<th style="min-width:130px;">Reference No.</th>
							<th style="min-width:130px;">Type</th>
							<th style="min-width:120px;">Event</th>
							<th style="min-width:160px;">Requester</th>
							<th style="min-width:160px;">Payable To</th>
							<th style="min-width:170px;">Department</th>
							<th style="min-width:180px;">Cost Center</th>
							<th style="min-width:110px;" class="text-right">Amount</th>
							<th style="min-width:150px;">Actioned By</th>
							<th style="min-width:140px;">Date/Time</th>
							<th style="min-width:260px;">Remarks</th>
						</tr>
					</thead>
					<tbody id="registerTbody"></tbody>
				</table>
			</div>
			<div class="d-flex justify-content-end mt-2">
				<nav aria-label="Payment register desktop pagination">
					<ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
				</nav>
			</div>
		</div>
	</div>

	<div class="card kna-card d-md-none">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Payment Advisory &amp; Release Register</div>
				<div class="kna-small text-muted" id="resultCountMobile">0 record(s)</div>
			</div>
			<div class="kna-mobile-list" id="registerMobileList"></div>
			<div class="text-center mt-2">
				<button type="button" class="btn btn-outline-primary btn-sm kna-small" id="btnLoadMoreMobile" style="display:none;">
					Load More
				</button>
			</div>
		</div>
	</div>
</div>
