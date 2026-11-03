<style>
	* {
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
	}

	body {
		background: linear-gradient(135deg, #f0f4f8 0%, #f8f9fc 100%);
	}

	.kna-page {
		padding: 12px 14px;
		background: transparent;
		min-height: 100vh;
	}

	.kna-title {
		font-size: 20px;
		font-weight: 600;
		margin: 0 0 8px 0;
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

	.form-group {
		margin-bottom: 0;
	}

	.btn {
		border-radius: 4px;
		font-size: 12px;
		padding: 6px 14px;
	}

	.btn-outline-secondary {
		border: 1px solid #d1d5db;
		color: #6b7280;
		background: transparent;
	}

	/* Info grid rows */
	.kna-info-row { display: grid; gap: 8px; margin-bottom: 10px; }
	.kna-info-row-3 { grid-template-columns: repeat(3, 1fr); }
	.kna-info-row-4 { grid-template-columns: repeat(4, 1fr); }

	/* Financial summary cards */
	.kna-fin-card {
		background: #f8fafc;
		border: 1px solid #e5e7eb;
		border-left: 3px solid var(--brand-700);
		border-radius: 6px;
		padding: 10px 12px;
	}
	.kna-fin-card.liq { border-left-color: #0f766e; }
	.kna-fin-card.var { border-left-color: #f59e0b; }
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

	/* Expense table */
	.kna-exp-wrap {
		width: 100%;
		overflow-x: auto;
	}
	.kna-exp-mobile { display: none; }
	.kna-exp-table {
		min-width: 860px;
		width: 100%;
		border-collapse: separate;
		border-spacing: 0;
		font-size: 12px;
	}
	.kna-exp-table th {
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
	.kna-exp-table td {
		border-bottom: 1px solid #f1f5f9;
		border-right: 1px solid #f1f5f9;
		padding: 8px 10px;
		vertical-align: top;
		color: #1f2937;
	}
	.kna-exp-table td:first-child { border-left: 1px solid #f1f5f9; }
	.kna-exp-table tbody tr:nth-child(odd) td { background: #ffffff; }
	.kna-exp-table tbody tr:nth-child(even) td { background: #f8fafc; }
	.kna-exp-table tbody tr:hover td { background: #f0f9ff; }
	.kna-exp-table tfoot td {
		background: #ecfdf5;
		border-top: 2px solid #6ee7b7;
		border-color: #a7f3d0;
		font-weight: 700;
		color: #065f46;
		vertical-align: middle;
	}
	.kna-amount-main {
		font-weight: 700;
		color: #0f766e;
	}
	.kna-amount-breakdown {
		font-size: 10px;
		color: #9ca3af;
		margin-top: 2px;
	}
	.kna-rownum {
		color: #9ca3af;
		font-size: 11px;
		text-align: center;
	}
	.kna-vat-check {
		width: 15px;
		height: 15px;
		margin: 0;
		accent-color: var(--brand-700);
	}

	/* Attachment thumbnails */
	.kna-thumb-wrap {
		display: inline-flex;
		flex-direction: column;
		align-items: center;
		cursor: pointer;
		margin: 2px 6px 2px 0;
		max-width: 72px;
		vertical-align: top;
		text-align: center;
	}
	.kna-thumb {
		width: 58px;
		height: 50px;
		object-fit: cover;
		border-radius: 4px;
		border: 1px solid #e5e7eb;
		transition: transform .15s, border-color .15s;
	}
	.kna-thumb:hover {
		transform: scale(1.1);
		border-color: var(--brand-700);
	}
	.kna-thumb-label {
		font-size: 10px;
		color: #6b7280;
		margin-top: 2px;
		max-width: 72px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		display: block;
	}
	.kna-file-wrap {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		font-size: 11px;
		margin-bottom: 3px;
	}
	.kna-file-wrap a {
		color: var(--brand-700);
		text-decoration: none;
		font-weight: 600;
	}
	.kna-file-wrap a:hover { text-decoration: underline; }

	/* Mobile cards */
	.kna-exp-card {
		border: 1px solid #e5e7eb;
		border-radius: 10px;
		background: #fff;
		padding: 12px;
		margin-bottom: 10px;
		box-shadow: 0 1px 2px rgba(20, 30, 50, .04);
	}
	.kna-exp-card-head {
		display: flex;
		justify-content: space-between;
		gap: 10px;
		align-items: flex-start;
		padding-bottom: 10px;
		border-bottom: 1px solid #eef2f7;
		margin-bottom: 10px;
	}
	.kna-exp-card-title {
		font-size: 13px;
		font-weight: 700;
		color: #111827;
		line-height: 1.3;
	}
	.kna-exp-card-sub {
		font-size: 11px;
		font-weight: 600;
		color: #6b7280;
		margin-left: 4px;
	}
	.kna-exp-card-meta {
		font-size: 11px;
		color: #6b7280;
		margin-top: 3px;
	}
	.kna-exp-card-amount {
		text-align: right;
		flex: 0 0 auto;
	}
	.kna-exp-card-grid {
		display: grid;
		grid-template-columns: 1fr 2fr;
		gap: 8px;
		margin-bottom: 10px;
	}
	.kna-exp-card-field {
		display: flex;
		flex-direction: column;
		gap: 3px;
	}
	.kna-exp-card-field-full { grid-column: 1 / -1; }
	.kna-exp-card-label {
		font-size: 10px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: .3px;
		color: #6b7280;
	}
	.kna-exp-card-value { font-size: 12px; color: #1f2937; }
	.kna-exp-card-attach { display: flex; flex-wrap: wrap; gap: 6px; }
	.kna-mobile-summary {
		display: none;
		margin-top: 10px;
		padding: 10px 12px;
		border: 1px solid #d1fae5;
		border-radius: 8px;
		background: #f0fdf4;
	}
	.kna-mobile-summary .kna-fin-label { margin-bottom: 2px; }

	/* Lightbox */
	.kna-lightbox {
		position: fixed;
		top: 0; left: 0; right: 0; bottom: 0;
		background: rgba(0, 0, 0, .88);
		z-index: 9999;
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.kna-lightbox.d-none { display: none !important; }
	.kna-lightbox-img {
		max-width: 90vw;
		max-height: 88vh;
		border-radius: 6px;
		box-shadow: 0 8px 40px rgba(0, 0, 0, .6);
	}
	.kna-lightbox-close {
		position: fixed;
		top: 16px;
		right: 20px;
		background: none;
		border: none;
		color: #fff;
		font-size: 32px;
		cursor: pointer;
		line-height: 1;
		z-index: 10000;
	}

	/* Approved/Rejected badges */
	.kna-approved-badge {
		background: #d1fae5;
		color: #065f46;
		padding: 2px 8px;
		border-radius: 10px;
		font-size: 11px;
		font-weight: 700;
		display: inline-flex;
		align-items: center;
		gap: 4px;
	}
	.kna-rejected-badge {
		background: #fee2e2;
		color: #991b1b;
		padding: 2px 8px;
		border-radius: 10px;
		font-size: 11px;
		font-weight: 700;
		display: inline-flex;
		align-items: center;
		gap: 4px;
	}
	.kna-rejected-reason {
		font-size: 11px;
		color: #991b1b;
		font-style: italic;
		margin-top: 4px;
	}

	@media (max-width: 767.98px) {
		.kna-page { padding: 8px 8px 12px; }
		.kna-title { font-size: 18px; }
		.kna-info-row-3 { grid-template-columns: 1fr; }
		.kna-info-row-4 { grid-template-columns: 1fr; }
		.kna-fin-value { font-size: 14px; }
		.kna-exp-wrap { overflow: visible; }
		.kna-exp-mobile { display: block; }
		.kna-exp-table { display: none !important; }
		.kna-thumb-wrap { max-width: 68px; margin-right: 4px; }
		.kna-thumb { width: 52px; height: 46px; }
		.kna-thumb-label { max-width: 68px; }
		.kna-mobile-summary { display: block; }
	}

	/* Timeline */
	.kna-timeline {
		list-style: none;
		margin: 0;
		padding: 0;
		display: flex;
		flex-direction: column;
		gap: 0;
	}
	.kna-timeline-item {
		position: relative;
		padding: 12px 0 12px 24px;
		border-left: 2px solid #e5e7eb;
		font-size: 12px;
	}
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
	.kna-timeline-item.is-done { border-left-color: #22c55e; }
	.kna-timeline-item.is-done::before {
		background: #22c55e;
		box-shadow: 0 0 0 2px #dcfce7;
	}
	.kna-timeline-item.is-current { border-left-color: var(--accent-600); }
	.kna-timeline-item.is-current::before {
		background: var(--accent-600);
		box-shadow: 0 0 0 2px var(--accent-200);
	}
	.kna-timeline-item.is-pending { border-left-color: #d1d5db; }
	.kna-timeline-item.is-pending::before {
		background: #d1d5db;
		box-shadow: 0 0 0 2px #f3f4f6;
	}
	.kna-timeline-item-top {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 4px;
	}
	.kna-timeline-item-name {
		font-weight: 700;
		color: #1f2937;
		font-size: 12px;
	}
	.kna-timeline-item-remarks {
		color: #4b5563;
		font-size: 12px;
		line-height: 1.5;
		word-break: break-word;
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div>
			<div class="kna-title">Reimbursement Details</div>
		</div>
		<div class="d-flex gap-2">
			<a id="btnEditReimbursement" class="btn btn-outline-warning d-none" href="#">
				<i class="fas fa-edit mr-1"></i> Edit
			</a>
			<a href="<?= base_url('transactions/reimbursement'); ?>" class="btn btn-outline-secondary">
				<i class="fas fa-arrow-left mr-1"></i> Back
			</a>
		</div>
	</div>

	<div class="card kna-card">
		<div class="card-body">
			<input type="hidden" id="reimbursementRef" value="<?= html_escape($reimbursement_no); ?>">

			<div class="kna-section-title kna-section-title-row">
				<span class="kna-section-title-label"><i class="fas fa-info-circle"></i> Reimbursement Information</span>
				<button type="button" class="kna-history-btn" id="btnShowHistory" title="View History">
					<i class="fas fa-history"></i>
				</button>
			</div>

			<div class="kna-info-row kna-info-row-3">
				<div class="form-group">
					<label class="kna-form-label">Reimbursement No</label>
					<div class="kna-readonly" id="viewReimbursementNo">-</div>
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

			<div class="kna-info-row kna-info-row-4">
				<div class="form-group">
					<label class="kna-form-label">Expense Period</label>
					<div class="kna-readonly" id="viewExpenseDate">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Requested Total</label>
					<div class="kna-readonly" id="viewTotalAmount">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Approved Total</label>
					<div class="kna-readonly" id="viewApprovedAmount" style="color:#16a34a;font-weight:600;">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Description</label>
					<div class="kna-readonly" id="viewDescription" style="min-height:48px;align-items:flex-start;padding-top:8px;">-</div>
				</div>
			</div>

			<div class="kna-info-row kna-info-row-3">
				<div class="form-group">
					<label class="kna-form-label">Payable To</label>
					<div class="kna-readonly" id="viewPayableTo">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Address</label>
					<div class="kna-readonly" id="viewAddress">-</div>
				</div>
				<div class="form-group">
					<label class="kna-form-label">Cost Center</label>
					<div class="kna-readonly" id="viewCostCenter">-</div>
				</div>
			</div>
			<div class="kna-info-row kna-info-row-3">
				<div class="form-group">
					<label class="kna-form-label">IO Number</label>
					<div class="kna-readonly" id="viewIoNumber">-</div>
				</div>
			</div>

			<hr />

			<div class="kna-section-title">
				<i class="fas fa-receipt"></i>
				Expense Items
			</div>

			<div id="viewExpenseItems"></div>
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

<!-- Lightbox -->
<div id="knaLightbox" class="kna-lightbox d-none">
	<button class="kna-lightbox-close" id="knaLightboxClose">&#x2715;</button>
	<img id="knaLightboxImg" class="kna-lightbox-img" src="" alt="Attachment">
</div>
<script>
	window.currentUserId = <?= json_encode((int) $this->session->userdata('user_id')); ?>;
</script>
