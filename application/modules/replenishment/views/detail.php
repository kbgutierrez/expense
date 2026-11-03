<style>
	.kna-page {
		padding: 12px 14px;
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

	.kna-card {
		border: 1px solid #d9e0e7 !important;
		border-radius: 6px;
		background: #ffffff;
		box-shadow: 0 1px 2px rgba(20, 30, 50, .05);
	}

	.kna-card .card-body {
		padding: .85rem;
	}

	.kna-form-label {
		margin-bottom: .3rem;
		font-weight: 600;
		font-size: 12px;
	}

	.kna-readonly {
		min-height: 32px;
		padding: 6px 10px;
		border-radius: 4px;
		border: 1px solid #e5e7eb;
		background: #f8fafc;
		font-size: 12px;
		color: #1f2937;
		display: flex;
		align-items: center;
	}

	.kna-section-title {
		font-size: 14px;
		font-weight: 700;
		color: #1a202c;
		margin-bottom: 10px;
		margin-top: 0;
		padding-bottom: 4px;
		border-bottom: 1px solid #f3f4f6;
		display: flex;
		align-items: center;
		gap: 6px;
	}

	.kna-section-title-row { justify-content: space-between; }
	.kna-section-title-label { display: flex; align-items: center; gap: 6px; }

	.kna-history-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 28px;
		height: 28px;
		border-radius: 6px;
		border: 1px solid #d9e0e7;
		background: #fff;
		color: #4b5563;
		cursor: pointer;
		font-size: 12px;
		flex: 0 0 auto;
		transition: background .15s, color .15s, border-color .15s;
	}
	.kna-history-btn:hover { background: var(--brand-100); border-color: var(--brand-700); color: var(--brand-700); }

	.kna-history-modal-overlay {
		position: fixed;
		top: 0; left: 0; right: 0; bottom: 0;
		background: rgba(15, 23, 42, .5);
		z-index: 10000;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
	}
	.kna-history-modal-overlay.d-none { display: none !important; }
	.kna-history-modal {
		background: #fff;
		border-radius: 8px;
		width: 100%;
		max-width: 480px;
		max-height: 82vh;
		display: flex;
		flex-direction: column;
		box-shadow: 0 12px 48px rgba(0, 0, 0, .25);
	}
	.kna-history-modal-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 12px 16px;
		border-bottom: 1px solid #e5e7eb;
	}
	.kna-history-modal-title {
		font-size: 14px;
		font-weight: 700;
		color: #1a202c;
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.kna-history-modal-close {
		background: none;
		border: none;
		font-size: 16px;
		color: #6b7280;
		cursor: pointer;
		line-height: 1;
		padding: 4px;
	}
	.kna-history-modal-close:hover { color: #1f2937; }
	.kna-history-modal-body { padding: 16px; overflow-y: auto; }

	.form-group { margin-bottom: 0; }

	.btn { border-radius: 4px; font-size: 12px; padding: 6px 14px; }
	.btn-outline-secondary { border: 1px solid #d1d5db; color: #6b7280; background: transparent; }

	.kna-info-row { display: grid; gap: 8px; margin-bottom: 10px; }
	.kna-info-row-3 { grid-template-columns: repeat(3, 1fr); }
	.kna-info-row-4 { grid-template-columns: repeat(4, 1fr); }

	.kna-fin-card {
		background: #f8fafc;
		border: 1px solid #e5e7eb;
		border-left: 3px solid var(--brand-700);
		border-radius: 6px;
		padding: 10px 12px;
	}
	.kna-fin-label {
		font-size: 11px;
		font-weight: 600;
		color: #6b7280;
		text-transform: uppercase;
		letter-spacing: .4px;
		margin-bottom: 4px;
	}
	.kna-fin-value {
		font-size: 15px;
		font-weight: 700;
		color: #1f2937;
		line-height: 1.3;
	}

	.kna-claim-table {
		width: 100%;
		border-collapse: separate;
		border-spacing: 0;
		font-size: 12px;
	}
	.kna-claim-table th {
		background: #f1f5f9;
		border: 1px solid #e2e8f0;
		padding: 8px 10px;
		font-weight: 700;
		color: #475569;
		text-transform: uppercase;
		font-size: 11px;
		letter-spacing: .3px;
		white-space: nowrap;
	}
	.kna-claim-table td {
		border-bottom: 1px solid #f1f5f9;
		border-right: 1px solid #f1f5f9;
		padding: 8px 10px;
		vertical-align: top;
		color: #1f2937;
	}
	.kna-claim-table td:first-child { border-left: 1px solid #f1f5f9; }
	.kna-claim-table tbody tr:nth-child(odd) td { background: #ffffff; }
	.kna-claim-table tbody tr:nth-child(even) td { background: #f8fafc; }
	.kna-claim-table tfoot td {
		background: #ecfdf5;
		border-top: 2px solid #6ee7b7;
		border-color: #a7f3d0;
		font-weight: 700;
		color: #065f46;
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

	.kna-timeline { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0; }
	.kna-timeline-item { position: relative; padding: 12px 0 12px 24px; border-left: 2px solid #e5e7eb; font-size: 12px; }
	.kna-timeline-item:last-child { padding-bottom: 0; }
	.kna-timeline-item::before {
		content: '';
		position: absolute;
		left: -7px;
		top: 14px;
		width: 12px;
		height: 12px;
		border-radius: 50%;
		border: 2px solid #fff;
		background: #d1d5db;
		box-shadow: 0 0 0 2px #e5e7eb;
	}
	.kna-timeline-item-top { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
	.kna-timeline-item-name { font-weight: 700; color: #1f2937; font-size: 12px; }
	.kna-timeline-item-remarks { color: #4b5563; font-size: 12px; line-height: 1.5; word-break: break-word; }

	@media (max-width: 767.98px) {
		.kna-page { padding: 8px 8px 12px; }
		.kna-title { font-size: 18px; }
		.kna-info-row-3 { grid-template-columns: 1fr; }
		.kna-info-row-4 { grid-template-columns: 1fr; }
		.kna-claim-table { display: none !important; }
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div class="kna-title">Replenishment Details</div>
		<a href="<?= base_url('transactions/replenishment'); ?>" class="btn btn-outline-secondary">
			<i class="fas fa-arrow-left mr-1"></i> Back
		</a>
	</div>

	<div class="card kna-card">
		<div class="card-body">
			<input type="hidden" id="replenishmentRef" value="<?= html_escape($replenishment_no); ?>">

			<div class="kna-section-title kna-section-title-row">
				<span class="kna-section-title-label"><i class="fas fa-info-circle"></i> Replenishment Information</span>
				<button type="button" class="kna-history-btn" id="btnShowHistory" title="View History">
					<i class="fas fa-history"></i>
				</button>
			</div>

			<div class="kna-info-row kna-info-row-3">
				<div class="form-group">
					<label class="kna-form-label">Replenishment No</label>
					<div class="kna-readonly" id="viewReplenishmentNo">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Status</label>
					<div class="kna-readonly" id="viewStatus" style="background:transparent;border-color:transparent;padding-left:0;">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Submitted Date</label>
					<div class="kna-readonly" id="viewSubmittedDate">-</div>
				</div>
			</div>

			<div class="kna-info-row kna-info-row-3">
				<div class="form-group">
					<label class="kna-form-label">Revolving Fund</label>
					<div class="kna-readonly" id="viewFundCode">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Total Amount</label>
					<div class="kna-readonly" id="viewTotalAmount" style="color:#16a34a;font-weight:600;">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Requested By</label>
					<div class="kna-readonly" id="viewRequestedBy">-</div>
				</div>
			</div>

			<div class="kna-info-row kna-info-row-3">
				<div class="form-group">
					<label class="kna-form-label">Remarks</label>
					<div class="kna-readonly" id="viewRemarks" style="min-height:44px;align-items:flex-start;padding-top:8px;">-</div>
				</div>
			</div>

			<hr />

			<div class="kna-section-title">
				<i class="fas fa-receipt"></i>
				Bundled Reimbursement Claims
			</div>

			<div id="viewClaims"></div>
		</div>
	</div>
</div>

<!-- History Modal -->
<div id="historyModalOverlay" class="kna-history-modal-overlay d-none">
	<div class="kna-history-modal">
		<div class="kna-history-modal-header">
			<div class="kna-history-modal-title"><i class="fas fa-history"></i> History</div>
			<button type="button" class="kna-history-modal-close" id="btnCloseHistory">&#x2715;</button>
		</div>
		<div class="kna-history-modal-body">
			<ul class="kna-timeline" id="viewTimeline"></ul>
		</div>
	</div>
</div>
