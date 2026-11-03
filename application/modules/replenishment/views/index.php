
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
		color: var(--brand-900);
	}

	.kna-kpi-caption {
		font-size: 11px;
		color: #6c757d;
		margin: 0;
	}

	.kna-kpi-row { display: flex; flex-wrap: wrap; gap: .65rem; margin-bottom: .85rem; }
	.kna-kpi-row > div { flex: 1 1 160px; min-width: 140px; }
	.kna-kpi-fund { border-left: 3px solid var(--brand-700); }

	.kna-table td,
	.kna-table th {
		font-size: 12px !important;
		padding: .5rem .45rem;
		vertical-align: middle;
	}

	.kna-badge {
		padding: .2rem .4rem;
		border-radius: 3px;
		font-size: 11px;
		font-weight: 600;
		display: inline-block;
	}

	.kna-badge-pending { background: #fff5d9; color: #7a5b00; }
	.kna-badge-approved { background: #e8f7ee; color: #17663a; }
	.kna-badge-rejected { background: #fdeaea; color: #8a2121; }
	.kna-badge-draft { background: #eef2f7; color: #495869; }
	.kna-badge-paid { background: #e0e7ff; color: #3730a3; }
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div class="kna-title">Revolving Fund Replenishment</div>
		<?php if (!empty($hasActiveFund)): ?>
		<button type="button" class="btn btn-primary btn-sm kna-small" id="btnOpenNewReplenishment">
			New Replenishment
		</button>
		<?php endif; ?>
	</div>

	<?php if (empty($hasActiveFund)): ?>
	<div class="card kna-card">
		<div class="card-body kna-small text-muted">
			You do not currently hold an active revolving fund, so there is nothing to replenish.
		</div>
	</div>
	<?php else: ?>

	<div class="kna-kpi-row">
		<div>
			<div class="card kna-card kna-kpi-fund h-100">
				<div class="card-body">
					<p class="kna-kpi-caption">Remaining Revolving Fund</p>
					<p class="kna-kpi" id="rplFundBalance">₱<?= number_format((float) ($revolvingFundBalance ?? 0), 2) ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="card kna-card">
		<div class="card-body">
			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-small text-muted">My Replenishment Requests</div>
				<div class="kna-small text-muted" id="resultCount">—</div>
			</div>
			<div class="table-responsive">
				<table class="table table-sm kna-table" id="replenishmentListTable" style="width:100%">
					<thead>
						<tr>
							<th>Replenishment No</th>
							<th class="text-right">Total Amount</th>
							<th>Submitted</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody id="replenishmentListTbody"></tbody>
				</table>
			</div>
		</div>
	</div>

	<?php endif; ?>
</div>
