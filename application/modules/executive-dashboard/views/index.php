<style>
	:root {
		--kna-series-1: #2a78d6;
		--kna-series-2: #eb6834;
		--kna-series-3: #1baf7a;
		--kna-series-4: #4a3aa7;
		--kna-status-good: #0ca30c;
		--kna-status-warning: #fab219;
		--kna-status-serious: #ec835a;
		--kna-status-critical: #d03b3b;
		--kna-ink: #0b0b0b;
		--kna-ink-secondary: #52514e;
		--kna-ink-muted: #898781;
		--kna-grid: #e1e0d9;
	}
	.kna-page { padding: 12px 14px; }
	.kna-card { border: 1px solid #d9e0e7 !important; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
	.kna-card .card-body { padding: 1rem; }
	.kna-title { font-size: 22px; font-weight: 700; margin: 0; line-height: 1.2; color: var(--kna-ink); }
	.kna-subtitle { font-size: 13px; color: var(--kna-ink-secondary); margin: 2px 0 0; }
	.kna-small { font-size: 12px !important; line-height: 1.35; }
	.kna-form-label { margin-bottom: .3rem; font-weight: 600; color: #34495e; }

	.kna-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 14px; }
	.kna-kpi-tile { background: #fff; border: 1px solid #e1e0d9; border-radius: 10px; padding: 14px 16px; position: relative; overflow: hidden; }
	.kna-kpi-tile::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--kna-accent, #2a78d6); }
	.kna-kpi-label { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--kna-ink-muted); font-weight: 700; margin: 0 0 6px; }
	.kna-kpi-value { font-size: 22px; font-weight: 700; color: var(--kna-ink); margin: 0; line-height: 1.15; font-variant-numeric: tabular-nums; }
	.kna-kpi-sub { font-size: 12px; color: var(--kna-ink-secondary); margin: 4px 0 0; }

	.kna-chart-card { min-height: 340px; }
	.kna-chart-title { font-size: 14px; font-weight: 700; color: var(--kna-ink); margin: 0 0 2px; }
	.kna-chart-subtitle { font-size: 12px; color: var(--kna-ink-muted); margin: 0 0 12px; }
	.kna-chart-canvas-wrap { position: relative; height: 260px; }
	.kna-chart-canvas-wrap.kna-tall { height: 320px; }

	.kna-legend-note { font-size: 11px; color: var(--kna-ink-muted); margin-top: 8px; }

	.kna-badge { padding: .2rem .4rem; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; }
	.kna-badge-ca { background: var(--brand-100); color: var(--brand-800); }
	.kna-badge-rmb { background: #fff4e5; color: #a15c00; }
	.kna-badge-lq { background: var(--accent-100); color: var(--brand-900); }
	.kna-badge-completed { background: #e8f7ee; color: #17663a; }
	.kna-badge-pending { background: #fff5d9; color: #7a5b00; }
	.kna-table td, .kna-table th { font-size: 12px; vertical-align: middle; }

	@media (max-width: 1199.98px) {
		.kna-kpi-grid { grid-template-columns: repeat(2, 1fr); }
	}
	@media (max-width: 767.98px) {
		.kna-kpi-grid { grid-template-columns: 1fr; }
		.kna-title { font-size: 19px; }
		.kna-page { padding: 10px; }
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:.75rem;">
		<div>
			<div class="kna-title">Executive Dashboard</div>
			<p class="kna-subtitle">Company-wide expense &amp; approval overview</p>
		</div>
		<div class="d-flex align-items-end flex-wrap" style="gap:.5rem;">
			<div>
				<label class="kna-small kna-form-label mb-1">Date Range</label>
				<input type="text" class="form-control form-control-sm kna-small" id="filterDateRange" placeholder="All time" autocomplete="off" readonly style="width:200px;">
			</div>
			<button type="button" class="btn btn-outline-secondary btn-sm" id="btnReset" title="Reset to all time" style="height:31px;">
				<i class="fas fa-sync-alt mr-1"></i>Reset
			</button>
		</div>
	</div>

	<div class="kna-kpi-grid">
		<div class="kna-kpi-tile" style="--kna-accent:#2a78d6;">
			<p class="kna-kpi-label">Cash Advance</p>
			<p class="kna-kpi-value" id="kpiCaTotal">₱0.00</p>
			<p class="kna-kpi-sub" id="kpiCaCount">0 requests</p>
		</div>
		<div class="kna-kpi-tile" style="--kna-accent:#eb6834;">
			<p class="kna-kpi-label">Reimbursement</p>
			<p class="kna-kpi-value" id="kpiRmbTotal">₱0.00</p>
			<p class="kna-kpi-sub" id="kpiRmbCount">0 requests</p>
		</div>
		<div class="kna-kpi-tile" style="--kna-accent:#1baf7a;">
			<p class="kna-kpi-label">Liquidation</p>
			<p class="kna-kpi-value" id="kpiLqTotal">₱0.00</p>
			<p class="kna-kpi-sub" id="kpiLqCount">0 requests</p>
		</div>
		<div class="kna-kpi-tile" style="--kna-accent:#4a3aa7;">
			<p class="kna-kpi-label">Payments Released</p>
			<p class="kna-kpi-value" id="kpiPaymentTotal">₱0.00</p>
			<p class="kna-kpi-sub" id="kpiPaymentCount">0 releases</p>
		</div>
		<div class="kna-kpi-tile" style="--kna-accent:#fab219;">
			<p class="kna-kpi-label">Pending Approvals</p>
			<p class="kna-kpi-value" id="kpiPendingCount">0</p>
			<p class="kna-kpi-sub" id="kpiPendingAmount">₱0.00 awaiting decision</p>
		</div>
		<div class="kna-kpi-tile" style="--kna-accent:#d03b3b;">
			<p class="kna-kpi-label">Avg. Approval Turnaround</p>
			<p class="kna-kpi-value" id="kpiTurnaround">0.0 days</p>
			<p class="kna-kpi-sub">Submit &rarr; decision</p>
		</div>
		<div class="kna-kpi-tile" style="--kna-accent:#0ca30c;">
			<p class="kna-kpi-label">Revolving Fund Balance</p>
			<p class="kna-kpi-value" id="kpiRfBalance">₱0.00</p>
			<p class="kna-kpi-sub">Active funds, current total</p>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-8 mb-3">
			<div class="card kna-card kna-chart-card h-100">
				<div class="card-body">
					<div class="d-flex align-items-center justify-content-between">
						<div>
							<p class="kna-chart-title">Monthly Spend Trend</p>
							<p class="kna-chart-subtitle">Cash Advance vs Reimbursement vs Liquidation, by month</p>
						</div>
					</div>
					<div class="kna-chart-canvas-wrap kna-tall">
						<canvas id="chartMonthlyTrend"></canvas>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-4 mb-3">
			<div class="card kna-card kna-chart-card h-100">
				<div class="card-body">
					<p class="kna-chart-title">Pending Approvals Aging</p>
					<p class="kna-chart-subtitle">How long items have been waiting</p>
					<div class="kna-chart-canvas-wrap kna-tall">
						<canvas id="chartAgingBuckets"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6 mb-3">
			<div class="card kna-card kna-chart-card h-100">
				<div class="card-body">
					<p class="kna-chart-title">Spend by Department</p>
					<p class="kna-chart-subtitle">Cash Advance + Reimbursement, top departments</p>
					<div class="kna-chart-canvas-wrap">
						<canvas id="chartDepartmentBreakdown"></canvas>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 mb-3">
			<div class="card kna-card kna-chart-card h-100">
				<div class="card-body">
					<p class="kna-chart-title">Spend by GL Code</p>
					<p class="kna-chart-subtitle">Reimbursement + Liquidation line items, top codes</p>
					<div class="kna-chart-canvas-wrap">
						<canvas id="chartGlBreakdown"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card mb-3">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2 flex-wrap" style="gap:.5rem;">
				<div>
					<p class="kna-chart-title mb-0">Detail Data</p>
					<p class="kna-chart-subtitle mb-0">Every transaction behind the numbers above &mdash; tick rows to export just those, or leave none/all ticked to export everything in range. Export includes Detail, By Department, and By GL tabs.</p>
				</div>
				<div class="d-flex align-items-center" style="gap:.5rem;">
					<span class="kna-small text-muted" id="detailSelectedCount">0 selected</span>
					<button type="button" class="btn btn-success btn-sm kna-small" id="btnExportExcel">
						<i class="fas fa-file-excel mr-1"></i>Export to Excel
					</button>
				</div>
			</div>

			<div class="mb-2">
				<input type="text" class="form-control form-control-sm kna-small" id="detailFilterKeyword" placeholder="Search reference no., employee, department, description..." style="max-width:320px;">
			</div>

			<div class="table-responsive">
				<table class="table table-sm kna-table" id="detailTable" style="width:100%;">
					<thead>
						<tr>
							<th style="min-width:28px;"><input type="checkbox" id="detailSelectAll"></th>
							<th style="min-width:120px;">Type</th>
							<th style="min-width:130px;">Reference No.</th>
							<th style="min-width:160px;">Employee</th>
							<th style="min-width:170px;">Department</th>
							<th style="min-width:180px;">Cost Center</th>
							<th style="min-width:110px;" class="text-right">Amount</th>
							<th style="min-width:260px;">Description</th>
							<th style="min-width:130px;">Status</th>
							<th style="min-width:110px;">Date</th>
						</tr>
					</thead>
					<tbody id="detailTbody"></tbody>
				</table>
			</div>
			<div class="d-flex align-items-center justify-content-between mt-2">
				<div class="kna-small text-muted" id="detailResultCount">0 record(s)</div>
				<nav aria-label="Detail data pagination">
					<ul class="pagination pagination-sm mb-0" id="detailPagination"></ul>
				</nav>
			</div>
		</div>
	</div>
</div>

<form id="detailExportForm" method="POST" action="" style="display:none;" target="_blank"></form>
