
<style>
	.kna-page {
		padding: 12px 14px;
	}

	.kna-card {
		border: 1px solid #d9e0e7 !important;
		border-radius: 6px;
		background: #ffffff;
		box-shadow: 0 1px 2px rgba(20, 30, 50, .05);
	}

	.kna-card .card-body {
		padding: .85rem;
	}

	.kna-title {
		font-size: 20px;
		font-weight: 600;
		margin: 0;
		line-height: 1.2;
	}

	.kna-small {
		font-size: 12px !important;
		line-height: 1.35;
	}

	.kna-kpi {
		font-size: 19px;
		line-height: 1.15;
		font-weight: 600;
		margin: 0;
		color: #1d2a3a;
	}

	.kna-kpi-caption {
		font-size: 11px;
		color: #6c757d;
		margin: 0;
	}

	.kna-form-label {
		margin-bottom: .3rem;
		font-weight: 600;
	}

	.kna-table td,
	.kna-table th {
		font-size: 12px !important;
		padding: .5rem .45rem;
		vertical-align: middle;
		white-space: nowrap;
	}

	.kna-actions .btn {
		padding: .3rem .55rem;
		font-size: 12px;
	}

	.kna-badge {
		padding: .2rem .4rem;
		border-radius: 3px;
		font-size: 11px;
		font-weight: 600;
		display: inline-block;
	}

	.kna-badge-pending {
		background: #fff5d9;
		color: #7a5b00;
	}

	.kna-badge-approved {
		background: #e8f7ee;
		color: #17663a;
	}

	.kna-badge-rejected {
		background: #fdeaea;
		color: #8a2121;
	}

	.kna-badge-draft {
		background: #eef2f7;
		color: #495869;
	}

	.kna-badge-paid {
		background: #e0e7ff;
		color: #3730a3;
	}

	.kna-mobile-list .kna-item {
		border: 1px solid #dde3eb;
		border-radius: 6px;
		padding: .65rem;
		margin-bottom: .5rem;
		background: #fff;
	}

	.kna-mobile-list .kna-item:last-child {
		margin-bottom: 0;
	}

	.kna-mobile-list .kna-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: .45rem;
		margin-bottom: .25rem;
	}

	.kna-mobile-list .kna-row:last-child {
		margin-bottom: 0;
	}

	.kna-filter-field {
		flex: 0 0 auto;
	}

	.kna-subcard {
		border: 1px solid #e5eaf0;
		border-radius: 6px;
		padding: .6rem;
		background: #fafcff;
	}

	@media (max-width: 991.98px) {
		.kna-page {
			padding: 10px;
		}

		.kna-title {
			font-size: 17px;
		}

		.kna-card .card-body {
			padding: .7rem;
		}

		.kna-stack-mobile {
			flex-direction: column;
			align-items: stretch !important;
			gap: .5rem;
		}

		.kna-mobile-cta {
			width: 100%;
		}
	}

	@media (max-width: 575.98px) {
		.kna-small {
			font-size: 11px !important;
		}

		.kna-filter-field {
			flex: 1 1 100%;
		}

		#filterDateRange,
		#filterStatus,
		#filterAmountRange {
			width: 100% !important;
		}
	}

	.kna-rmb-tabs { border-bottom: 1px solid #d9e0e7; margin-bottom: .85rem; flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
	.kna-rmb-tabs::-webkit-scrollbar { height: 4px; }
	.kna-rmb-tabs .nav-link { font-size: 13px; font-weight: 600; color: #4a5a6a; border: none; border-bottom: 2px solid transparent; padding: .55rem .9rem; white-space: nowrap; }
	.kna-rmb-tabs .nav-link.active { color: #2f6eb4; border-bottom-color: #2f6eb4; background: transparent; }
	.kna-rmb-tabs .nav-link i { margin-right: 5px; }

	.kna-kpi-row { display: flex; flex-wrap: wrap; gap: .65rem; margin-bottom: .85rem; }
	.kna-kpi-row > div { flex: 1 1 160px; min-width: 140px; }
	.kna-kpi-row .kna-kpi-fund { border-left: 3px solid #2f6eb4; }

	@media (max-width: 575.98px) {
		.kna-kpi-row { gap: .5rem; }
		.kna-kpi-row > div { flex: 1 1 calc(50% - .5rem); min-width: 0; }
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2 kna-stack-mobile">
		<div>
			<div class="kna-title">Reimbursement</div>
		</div>
		<button type="button" class="btn btn-primary btn-sm kna-small kna-mobile-cta" id="btnOpenNewReimbursement">
			New Reimbursement
		</button>
	</div>

	<ul class="nav kna-rmb-tabs" id="rmbTabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" id="rmb-tab-mine" data-toggle="tab" href="#rmb-pane-mine" role="tab" aria-controls="rmb-pane-mine" aria-selected="true">
				<i class="fas fa-receipt"></i>My Reimbursements
			</a>
		</li>
		<?php if (!empty($hasActiveFund)): ?>
		<li class="nav-item">
			<a class="nav-link" id="rmb-tab-team" data-toggle="tab" href="#rmb-pane-team" role="tab" aria-controls="rmb-pane-team" aria-selected="false">
				<i class="fas fa-users"></i>My Team
			</a>
		</li>
		<?php endif; ?>
	</ul>

	<div class="tab-content" id="rmbTabsContent">
	<div class="tab-pane fade show active" id="rmb-pane-mine" role="tabpanel" aria-labelledby="rmb-tab-mine">

	<div class="kna-kpi-row">
		<?php if (!empty($hasActiveFund)): ?>
		<div>
			<div class="card kna-card kna-kpi-fund h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Remaining Revolving Fund</p>
					<p class="kna-kpi" id="mineRevolvingFundBalance">₱<?= number_format((float) ($revolvingFundBalance ?? 0), 2) ?></p>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div>
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Total Reimbursement</p>
					<p class="kna-kpi" id="sumTotalReimbursement">—</p>
				</div>
			</div>
		</div>
		<div>
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Pending</p>
					<p class="kna-kpi" id="sumPendingReview">—</p>
				</div>
			</div>
		</div>
		<div>
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Approved</p>
					<p class="kna-kpi" id="sumApprovedReimbursement">—</p>
				</div>
			</div>
		</div>
		<div>
			<div class="card kna-card h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Rejected</p>
					<p class="kna-kpi" id="sumRejected">—</p>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body py-2">
			<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
				<div class="kna-filter-field">
					<label class="kna-small kna-form-label mb-1">Search</label>
					<input type="text" class="form-control form-control-sm kna-small" id="filterKeyword" placeholder="RMB no. or purpose" autocomplete="off" style="width:180px;">
				</div>
				<div class="kna-filter-field">
					<label class="kna-small kna-form-label mb-1">Date Range</label>
					<input type="text" class="form-control form-control-sm kna-small" id="filterDateRange" placeholder="Select range" autocomplete="off" readonly style="width:180px;">
				</div>
				<div class="kna-filter-field">
					<label class="kna-small kna-form-label mb-1">Status</label>
					<select class="form-control form-control-sm kna-small" id="filterStatus" style="width:150px;">
						<option value="">All Status</option>
						<option value="Draft">Draft</option>
						<option value="Submitted">Submitted</option>
						<option value="Approved">Approved</option>
						<option value="Rejected">Rejected</option>
						<option value="Paid">Paid</option>
					</select>
				</div>
				<div class="kna-filter-field">
					<label class="kna-small kna-form-label mb-1">Amount</label>
					<select class="form-control form-control-sm kna-small" id="filterAmountRange" style="width:160px;">
						<option value="">All</option>
						<option value="0-5000">PHP 0 - PHP 5,000</option>
						<option value="5001-10000">PHP 5,001 - PHP 10,000</option>
						<option value="10001-999999">Above PHP 10,000</option>
					</select>
				</div>
				<div class="kna-filter-field">
					<button type="button" class="btn btn-outline-secondary btn-sm" id="btnReset" title="Clear filters" style="height:31px;width:31px;padding:0;">
						<i class="fas fa-undo"></i>
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card d-none d-md-block">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Reimbursement History</div>
				<div class="kna-small text-muted" id="resultCount">—</div>
			</div>

			<div class="table-responsive">
				<table class="table table-sm kna-table" id="reimbursementTable" style="width:100%;">
					<thead>
						<tr>
							<th style="min-width:140px;">Reimbursement No</th>
							<th style="min-width:120px;" class="text-right">Requested Amount</th>
							<th style="min-width:120px;" class="text-right">Approved Amount</th>
							<th style="min-width:105px;">Submitted</th>
							<th style="min-width:130px;">Status</th>
							<th style="min-width:150px;" class="text-center">Actions</th>
						</tr>
					</thead>
					<tbody id="reimbursementTbody">
					</tbody>
				</table>
			</div>
			<div class="d-flex justify-content-end mt-2">
				<nav aria-label="Reimbursement desktop pagination">
					<ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
				</nav>
			</div>
		</div>
	</div>

	<div class="card kna-card d-md-none">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">Reimbursement History</div>
				<div class="kna-small text-muted" id="resultCountMobile">—</div>
			</div>
			<div class="kna-mobile-list" id="reimbursementMobileList"></div>
			<div class="text-center mt-2">
				<button type="button" class="btn btn-outline-secondary btn-sm kna-small d-none" id="btnLoadMoreMobile">Load More</button>
			</div>
		</div>
	</div>

	</div>

	<?php if (!empty($hasActiveFund)): ?>
	<div class="tab-pane fade" id="rmb-pane-team" role="tabpanel" aria-labelledby="rmb-tab-team">

		<div class="card kna-card mb-2">
			<div class="card-body py-2">
				<div class="kna-small text-muted mb-2">Download your team's approved reimbursements within a date range, to send to Accounting.</div>
				<div class="d-flex flex-wrap align-items-end justify-content-between kna-stack-mobile" style="gap:.5rem;">
					<div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
						<div>
							<label class="kna-small kna-form-label mb-1">Date Range</label>
							<input type="text" class="form-control form-control-sm kna-small" id="teamDateRange" placeholder="Select date range" style="width:220px;">
						</div>
						<div>
							<button type="button" class="btn btn-primary btn-sm kna-small" id="btnTeamSearch" style="height:31px;">
								<i class="fas fa-search mr-1"></i>Search
							</button>
						</div>
					</div>
					<button type="button" class="btn btn-outline-success btn-sm kna-small kna-mobile-cta" id="btnTeamExport" disabled>
						<i class="fas fa-file-excel mr-1"></i>Export to Excel
					</button>
				</div>
			</div>
		</div>

		<div class="card kna-card d-none d-md-block">
			<div class="card-body">
				<div class="d-flex align-items-center justify-content-between mb-2">
					<div class="kna-small text-muted">Team Reimbursements</div>
					<div class="kna-small text-muted" id="teamResultCount">0 record(s)</div>
				</div>
				<div class="table-responsive">
					<table class="table table-sm kna-table" id="teamReportTable" style="width:100%;">
						<thead>
							<tr>
								<th style="min-width:140px;">Reimbursement No</th>
								<th style="min-width:150px;">Salesman</th>
								<th style="min-width:110px;" class="text-right">Amount</th>
								<th style="min-width:140px;">Description</th>
								<th style="min-width:190px;">Cost Center</th>
								<th style="min-width:130px;">Status</th>
								<th style="min-width:105px;">Filed Date</th>
								<th style="min-width:100px;" class="text-center">Actions</th>
							</tr>
						</thead>
						<tbody id="teamReportTbody"></tbody>
					</table>
				</div>
			</div>
		</div>

		<div class="card kna-card d-md-none">
			<div class="card-body">
				<div class="d-flex align-items-center justify-content-between mb-2">
					<div class="kna-small text-muted">Team Reimbursements</div>
					<div class="kna-small text-muted" id="teamResultCountMobile">0 record(s)</div>
				</div>
				<div class="kna-mobile-list" id="teamReportMobileList"></div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	</div>
</div>
</div>
