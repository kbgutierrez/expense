
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

	.kna-form-label {
		margin-bottom: .3rem;
		font-weight: 600;
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

	.kna-rmb-link {
		cursor: pointer;
		color: var(--brand-700);
		text-decoration: none;
	}
	.kna-rmb-link:hover { text-decoration: underline; }

	.kna-modal-overlay {
		position: fixed;
		top: 0; left: 0; right: 0; bottom: 0;
		background: rgba(15, 23, 42, .5);
		z-index: 10000;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
	}
	.kna-modal-overlay.d-none { display: none !important; }
	.kna-modal {
		background: #fff;
		border-radius: 8px;
		width: 100%;
		max-width: 640px;
		max-height: 86vh;
		display: flex;
		flex-direction: column;
		box-shadow: 0 12px 48px rgba(0, 0, 0, .25);
	}
	.kna-modal-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 12px 16px;
		border-bottom: 1px solid #e5e7eb;
	}
	.kna-modal-title {
		font-size: 14px;
		font-weight: 700;
		color: #1a202c;
	}
	.kna-modal-close {
		background: none;
		border: none;
		font-size: 16px;
		color: #6b7280;
		cursor: pointer;
		line-height: 1;
		padding: 4px;
	}
	.kna-modal-close:hover { color: #1f2937; }
	.kna-modal-body { padding: 16px; overflow-y: auto; }

	.kna-info-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 10px; }
	.kna-form-label-sm { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 2px; }
	.kna-readonly-value { font-size: 12px; color: #1f2937; }

	.kna-item-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; margin-top: 6px; }
	.kna-item-table th {
		background: #f1f5f9;
		border: 1px solid #e2e8f0;
		padding: 6px 8px;
		font-weight: 700;
		color: #475569;
		font-size: 11px;
	}
	.kna-item-table td {
		border-bottom: 1px solid #f1f5f9;
		border-right: 1px solid #f1f5f9;
		padding: 6px 8px;
	}
	.kna-item-table td:first-child { border-left: 1px solid #f1f5f9; }
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div class="kna-title">New Replenishment</div>
		<a href="<?= base_url('transactions/replenishment') ?>" class="btn btn-outline-secondary btn-sm kna-small">
			<i class="fas fa-arrow-left mr-1"></i> Back
		</a>
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
				<div class="kna-small text-muted">Claim your team's paid reimbursements as proof of expense to replenish your fund.</div>
				<div class="kna-small text-muted" id="claimableCount">—</div>
			</div>

			<div class="table-responsive">
				<table class="table table-sm kna-table" id="claimableTable" style="width:100%">
					<thead>
						<tr>
							<th style="width:32px;"><input type="checkbox" id="claimAll"></th>
							<th>Reimbursement No <span class="kna-small text-muted font-weight-normal">(click to view)</span></th>
							<th>Filed By</th>
							<th>Description</th>
							<th class="text-right">Amount</th>
						</tr>
					</thead>
					<tbody id="claimableTbody"></tbody>
				</table>
			</div>

			<div class="mt-2">
				<label class="kna-small kna-form-label mb-1">Remarks</label>
				<textarea class="form-control form-control-sm kna-small" id="rplRemarks" rows="2" placeholder="Notes for the approver (optional)"></textarea>
			</div>

			<div class="d-flex align-items-center justify-content-between mt-2">
				<div class="kna-small">Total to replenish: <strong id="claimedTotal">₱0.00</strong></div>
				<div>
					<button type="button" class="btn btn-outline-secondary btn-sm kna-small" id="btnSaveDraft">Save Draft</button>
					<button type="button" class="btn btn-primary btn-sm kna-small" id="btnSubmitReplenishment">Submit for Approval</button>
				</div>
			</div>
		</div>
	</div>

	<?php endif; ?>
</div>

<!-- Reimbursement Detail Modal — shown in-place instead of navigating
     away, so claiming reimbursements never loses your in-progress
     selection here or piles up extra tabs. -->
<div id="rmbDetailModalOverlay" class="kna-modal-overlay d-none">
	<div class="kna-modal">
		<div class="kna-modal-header">
			<div class="kna-modal-title"><i class="fas fa-receipt mr-1"></i> Reimbursement Details</div>
			<button type="button" class="kna-modal-close" id="btnCloseRmbDetail">&#x2715;</button>
		</div>
		<div class="kna-modal-body" id="rmbDetailModalBody">
			<div class="text-center kna-small text-muted py-3">Loading...</div>
		</div>
	</div>
</div>
