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

	.btn-primary {
		background: var(--brand-700);
		color: #fff;
		border: none;
	}

	.btn-warning {
		background: #f59e0b;
		color: #fff;
		border: none;
	}

	/* Info grid rows */
	.kna-info-row { display: grid; gap: 8px; margin-bottom: 10px; }
	.kna-info-row-3 { grid-template-columns: repeat(3, 1fr); }
	.kna-desktop-info .kna-info-row {
		gap: 6px;
		margin-bottom: 6px;
	}
	.kna-desktop-info .kna-form-label {
		margin-bottom: .2rem;
	}
	.kna-compact-field {
		display: flex;
		flex-direction: column;
		gap: 4px;
	}
	.kna-compact-value {
		min-height: 30px;
		padding: 4px 8px;
		border-radius: 4px;
		border: 1px solid #e5e7eb;
		background: #fff;
		font-size: 11px;
		font-weight: 500;
		color: #1f2937;
		display: flex;
		align-items: center;
	}
	.kna-compact-value.is-muted {
		background: #f8fafc;
	}
	.kna-compact-value .kna-var-badge {
		font-size: 11px;
		padding: 2px 8px;
	}

	/* Financial summary cards */
	.kna-fin-card {
		background: #f8fafc;
		border: 1px solid #e5e7eb;
		border-left: 3px solid var(--brand-700);
		border-radius: 6px;
		padding: 10px 12px;
	}
	.kna-fin-card.ca  { border-left-color: #3b82f6; }
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

	/* Variance badges */
	.kna-var-badge     { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
	.kna-var-balanced  { background: #d1fae5; color: #065f46; }
	.kna-var-return    { background: #fef3c7; color: #92400e; }
	.kna-var-reimburse { background: #dbeafe; color: #1e40af; }

	/* ===== COMPACT EXPENSE ITEM ROWS ===== */
	.kna-exp-wrap { width: 100%; overflow-x: auto; }
	.kna-item-rows { display: flex; flex-direction: column; gap: 6px; }
	.kna-item-row-wrap { display: flex; flex-direction: column; gap: 0; }
	.kna-item-row {
		display: flex;
		align-items: flex-start;
		gap: 8px;
		background: #f8f9fc;
		border: 1px solid #e5e7eb;
		border-radius: 6px;
		padding: 8px;
	}
	.kna-item-row-index {
		flex: 0 0 auto;
		width: 20px;
		padding-top: 6px;
		font-size: 11px;
		font-weight: 700;
		color: #9ca3af;
		text-align: center;
	}
	.kna-item-row-fields {
		flex: 1 1 auto;
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
		min-width: 0;
	}
	.kna-item-field {
		display: flex;
		flex-direction: column;
		gap: 2px;
		flex: 1 1 100px;
		min-width: 90px;
	}
	.kna-item-field-label {
		font-size: 9px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: .2px;
		color: #9ca3af;
	}
	.kna-f-type { flex: 2 1 180px; }
	.kna-f-amount { flex: 1 1 90px; }
	.kna-f-vat { flex: 0 0 auto; min-width: 40px; }
	.kna-f-vendor { flex: 1 1 130px; }
	.kna-f-tin { flex: 1 1 100px; }
	.kna-f-attach { flex: 1 1 150px; }
	.kna-f-remarks { flex: 1 1 150px; }
	.kna-item-attach-inline {
		display: flex;
		align-items: center;
		gap: 6px;
		flex-wrap: wrap;
	}
	.kna-item-attach-inline .kna-attachment-cell { font-size: 11px; font-weight: 600; }
	.kna-item-attach-inline .btn {
		font-size: 11px;
		padding: 3px 8px;
		height: 24px;
		line-height: 1;
	}
	.kna-item-row-remove, .kna-item-row-lock {
		flex: 0 0 auto;
		margin-top: 4px;
		width: 26px; height: 26px;
		border-radius: 6px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 11px;
	}
	.kna-item-row-remove {
		border: 1px solid #fecaca;
		background: #fef2f2;
		color: #dc2626;
		cursor: pointer;
	}
	.kna-item-row-remove:hover { background: #fee2e2; border-color: #ef4444; }
	.kna-item-row-lock {
		border: 1px solid #e5e7eb;
		background: #f3f4f6;
		color: #9ca3af;
	}
	.kna-attachment-cell {
		font-size: 12px;
		font-weight: 600;
		white-space: normal;
		overflow: hidden;
		text-overflow: ellipsis;
		line-height: 1.25;
	}
	.kna-vat-wrap {
		display: inline-flex;
		align-items: center;
		height: 28px;
		margin: 0;
		cursor: pointer;
	}
	.kna-vat-input {
		width: 14px;
		height: 14px;
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
		position: relative;
	}
	.kna-thumb-wrap.removed {
		opacity: 0.4;
		filter: grayscale(1);
	}
	.kna-thumb-remove {
		position: absolute;
		top: -4px;
		right: -4px;
		background: #ef4444;
		color: #fff;
		border: none;
		border-radius: 50%;
		width: 16px;
		height: 16px;
		font-size: 9px;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		z-index: 2;
		padding: 0;
	}
	.kna-thumb {
		width: 58px;
		height: 50px;
		object-fit: cover;
		border-radius: 4px;
		border: 1px solid #e5e7eb;
		transition: transform .15s, border-color .15s;
	}
	.kna-thumb:hover { transform: scale(1.1); border-color: var(--brand-700); }
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
	.kna-file-wrap { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; margin-bottom: 3px; max-width: 140px; }
	.kna-file-wrap a { color: var(--brand-700); text-decoration: none; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100px; display: inline-block; vertical-align: middle; }
	.kna-file-wrap a:hover { text-decoration: underline; }
	.kna-file-wrap .kna-file-remove {
		color: #ef4444;
		cursor: pointer;
		font-size: 10px;
		margin-left: 2px;
	}
	.kna-file-wrap.removed {
		opacity: 0.4;
		text-decoration: line-through;
	}

	/* ===== REJECTED ITEM STYLING ===== */
	.kna-item-row.kna-row-rejected {
		background: #fef2f2;
		border-color: #fecaca;
	}
	.kna-rejection-ribbon {
		display: flex;
		align-items: center;
		gap: 8px;
		flex-wrap: wrap;
		padding: 6px 10px;
		background: #fff;
		border: 1px solid #fecaca;
		border-radius: 6px;
		border-left: 3px solid #ef4444;
		font-size: 11px;
		margin-top: 6px;
	}
	.kna-rejection-ribbon-label {
		font-weight: 700;
		color: #991b1b;
		text-transform: uppercase;
		letter-spacing: .3px;
		font-size: 10px;
		white-space: nowrap;
	}
	.kna-rejection-pill {
		background: #fee2e2;
		color: #991b1b;
		padding: 2px 8px;
		border-radius: 10px;
		font-size: 11px;
		font-weight: 600;
		white-space: nowrap;
	}
	.kna-rejection-pill i {
		margin-right: 3px;
		font-size: 10px;
	}

	/* Locked row styling */
	.kna-item-row.kna-row-locked {
		background: #f3f4f6;
		border-color: #e5e7eb;
	}
	.kna-lock-icon {
		color: #9ca3af;
		font-size: 11px;
	}

	/* ===== MOBILE COMPACT OVERVIEW (Hidden on desktop) ===== */
	.kna-mobile-overview { display: none; }

	/* Items total summary */
	.kna-mobile-summary {
		display: block;
		margin-top: 10px;
		padding: 10px 12px;
		border: 1px solid #d1fae5;
		border-radius: 8px;
		background: #f0fdf4;
	}
	.kna-mobile-summary .kna-fin-label {
		margin-bottom: 2px;
	}

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

	/* Edit-specific styles */
	.kna-edit-input {
		font-size: 12px;
		padding: 4px 8px;
		border-radius: 4px;
		border: 1px solid #d1d5db;
		width: 100%;
		min-width: 0;
	}
	.kna-edit-input:focus {
		outline: none;
		border-color: var(--brand-700);
		box-shadow: 0 0 0 2px rgba(var(--brand-rgb), 0.1);
	}
	.kna-edit-input:disabled {
		background: #f3f4f6;
		border-color: #e5e7eb;
		color: #6b7280;
		cursor: not-allowed;
	}
	.kna-edit-select {
		font-size: 12px;
		padding: 4px 8px;
		border-radius: 4px;
		border: 1px solid #d1d5db;
		width: 100%;
		min-width: 0;
		background: #fff;
	}
	.kna-edit-select:focus {
		outline: none;
		border-color: var(--brand-700);
		box-shadow: 0 0 0 2px rgba(var(--brand-rgb), 0.1);
	}
	.kna-edit-select:disabled {
		background: #f3f4f6;
		border-color: #e5e7eb;
		color: #6b7280;
		cursor: not-allowed;
	}
	.kna-edit-number {
		text-align: right;
	}
	.kna-edit-checkbox {
		width: 15px;
		height: 15px;
		margin: 0;
		accent-color: var(--brand-700);
	}
	.kna-edit-checkbox:disabled {
		accent-color: #9ca3af;
		cursor: not-allowed;
	}

	/* Warning banner for rejected items */
	.kna-rejected-banner {
		background: #fef2f2;
		border: 1px solid #fecaca;
		border-radius: 6px;
		padding: 10px 12px;
		margin-bottom: 12px;
		font-size: 12px;
		color: #991b1b;
	}
	.kna-rejected-banner i {
		margin-right: 6px;
	}

	/* Actions bar */
	.kna-edit-actions {
		display: flex;
		gap: 8px;
		justify-content: flex-end;
		margin-top: 12px;
	}

	/* Add item button matching add.php style */
	.kna-add-item-btn {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
		width: 100%;
		padding: 10px;
		border: 2px dashed #d1d5db;
		border-radius: 6px;
		background: #f9fafb;
		color: #6b7280;
		font-size: 12px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.15s;
		margin-top: 8px;
	}
	.kna-add-item-btn:hover {
		border-color: var(--brand-700);
		color: var(--brand-700);
		background: var(--brand-50);
	}

	/* Attachment remove undo */
	.kna-attach-undo {
		font-size: 10px;
		color: var(--brand-700);
		cursor: pointer;
		margin-left: 4px;
	}
	.kna-attach-undo:hover {
		text-decoration: underline;
	}

	/* Mobile sticky actions */
	.kna-mobile-sticky-actions {
		display: none;
	}

	@media (max-width: 767.98px) {
		.kna-desktop-info { display: none !important; }
		.kna-mobile-overview { display: block; }

		.kna-page { padding: 8px 8px 12px; }
		.kna-title { font-size: 18px; }
		.kna-info-row-3 { grid-template-columns: 1fr; }
		.kna-fin-value { font-size: 14px; }
		.kna-exp-wrap { overflow: visible; }
		.kna-item-field { flex: 1 1 45%; }
		.kna-thumb-wrap { max-width: 68px; margin-right: 4px; }
		.kna-thumb { width: 52px; height: 46px; }
		.kna-thumb-label { max-width: 68px; }
		.kna-edit-actions { display: none; }
		.kna-mobile-sticky-actions {
			display: flex;
			gap: 8px;
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			background: #fff;
			border-top: 1px solid #e5e7eb;
			padding: 10px 14px;
			box-shadow: 0 -4px 12px rgba(0,0,0,0.06);
			z-index: 1030;
		}
		.kna-mobile-sticky-actions .btn {
			flex: 1;
			font-size: 12px;
			padding: 10px 8px;
			min-height: 44px;
			border-radius: 8px;
		}
		.kna-mobile-sticky-actions .btn-outline-secondary {
			flex: 0 0 auto;
			padding: 10px 14px;
		}
		body { padding-bottom: 72px; }
		.form-control, .form-control-sm {
			min-height: 40px;
			font-size: 12px;
		}
		select.form-control {
			height: 40px;
		}

		/* Mobile Hero */
		.kna-mobile-hero {
			background: linear-gradient(135deg, var(--brand-700) 0%, var(--brand-500) 100%);
			border-radius: 12px;
			padding: 14px 16px;
			margin-bottom: 10px;
			color: #fff;
			box-shadow: 0 4px 12px rgba(var(--brand-rgb), 0.25);
		}
		.kna-mobile-hero-top {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			margin-bottom: 10px;
		}
		.kna-mobile-hero-id {
			font-size: 13px;
			font-weight: 700;
			opacity: 0.95;
			letter-spacing: 0.3px;
		}
		.kna-mobile-hero-id span {
			opacity: 0.7;
			font-weight: 500;
			font-size: 11px;
			display: block;
			margin-top: 2px;
		}
		.kna-mobile-hero-status {
			font-size: 10px;
			font-weight: 700;
			padding: 3px 10px;
			border-radius: 20px;
			background: rgba(255,255,255,0.2);
			backdrop-filter: blur(4px);
			white-space: nowrap;
		}
		.kna-mobile-hero-amounts {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 8px;
		}
		.kna-mobile-hero-amt {
			text-align: center;
			background: rgba(255,255,255,0.12);
			border-radius: 8px;
			padding: 8px 4px;
		}
		.kna-mobile-hero-amt-label {
			font-size: 9px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.4px;
			opacity: 0.75;
			margin-bottom: 2px;
		}
		.kna-mobile-hero-amt-value { font-size: 14px; font-weight: 700; }
		.kna-mobile-hero-amt-value.small { font-size: 12px; }

		/* Mobile Info Grid */
		.kna-info-section-mobile { display: block; margin-bottom: 10px; }
		.kna-info-grid-mobile {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 8px;
		}
		.kna-info-item-mobile {
			background: #fff;
			border-radius: 10px;
			padding: 10px 12px;
			border: 1px solid #e5e7eb;
			border-left: 3px solid var(--brand-700);
			box-shadow: 0 1px 3px rgba(0,0,0,0.04);
		}
		.kna-info-item-mobile.liq-no { border-left-color: var(--brand-700); }
		.kna-info-item-mobile.ca-ref { border-left-color: #3b82f6; }
		.kna-info-item-mobile.ca-amt { border-left-color: #0f766e; }
		.kna-info-item-mobile.ca-date { border-left-color: var(--brand-500); }
		.kna-info-item-mobile.range { border-left-color: #f59e0b; }
		.kna-info-item-mobile.total { border-left-color: #059669; }
		.kna-info-item-mobile.variance { border-left-color: #ef4444; }
		.kna-info-item-mobile.payable { border-left-color: #ec4899; }
		.kna-info-item-mobile.address { border-left-color: #14b8a6; }
		.kna-info-item-mobile.costcenter { border-left-color: #f97316; }
		.kna-info-item-mobile.status { border-left-color: var(--brand-700); }
		.kna-info-item-mobile.submitted { border-left-color: #64748b; }
		.kna-info-item-mobile.expense-period { border-left-color: var(--accent-600); }
		.kna-info-item-mobile.purpose { border-left-color: var(--brand-700); }
		.kna-info-label-mobile {
			font-size: 9px;
			font-weight: 700;
			color: #9ca3af;
			text-transform: uppercase;
			letter-spacing: 0.4px;
			margin-bottom: 3px;
		}
		.kna-info-value-mobile {
			font-size: 12px;
			font-weight: 600;
			color: #1f2937;
			line-height: 1.3;
			word-break: break-word;
		}
		.kna-info-value-mobile .kna-var-badge { font-size: 10px; padding: 1px 8px; }
		.kna-info-item-mobile.full-width { grid-column: 1 / -1; }
	}
	  /* ─── OCR Status Indicators ─── */
  .kna-ocr-status {
    font-size: 11px;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    line-height: 1.3;
  }
  .kna-ocr-scanning { color: #2563eb; }
  .kna-ocr-success  { color: #059669; }
  .kna-ocr-error    { color: #dc2626; }
  .kna-ocr-manual   { color: #6b7280; }
  .kna-ocr-status i { font-size: 12px; width: 14px; text-align: center; }

  .kna-ocr-manual-btn {
    background: none;
    border: none;
    color: var(--brand-700);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
    line-height: 1;
  }
  .kna-ocr-manual-btn:hover { color: var(--brand-800); }

	@media (max-width: 767.98px) {
		.kna-page > .d-flex.mb-2 { margin-bottom: 12px !important; }
		.kna-page > .d-flex.mb-2 .kna-small { display: none; }
		.kna-page > .d-flex.mb-2 .kna-title { font-size: 18px; font-weight: 700; color: #1f2937; }
		.kna-page > .d-flex.mb-2 .btn-outline-secondary {
			width: 38px; height: 38px; padding: 0; border-radius: 50%;
			display: inline-flex; align-items: center; justify-content: center;
			font-size: 0; border-color: #e5e7eb; background: #fff;
		}
		.kna-page > .d-flex.mb-2 .btn-outline-secondary i { font-size: 15px; margin: 0 !important; }
		.kna-page { padding-bottom: 96px !important; }
		.kna-card { background: transparent; border: 0; box-shadow: none; }
		.kna-card .card-body { padding: 0 !important; }
		.kna-card .card-body > hr,
		.kna-card .card-body > .d-flex.mb-2 { display: none !important; }
		.kna-rejected-banner { border-radius: 12px; padding: 12px 14px; font-size: 13px; line-height: 1.4; }
		.kna-mobile-sticky-actions { align-items: center; padding: 10px 14px calc(12px + env(safe-area-inset-bottom)) !important; }
		.kna-mobile-sticky-actions .lqm-btn-ghost { flex: 0 0 auto !important; background: transparent; color: #4b5563; font-weight: 600; font-size: 13px !important; padding: 10px 8px !important; }
		.kna-mobile-sticky-actions .lqm-btn-submit { flex: 0 0 auto !important; min-width: 120px; border-radius: 12px !important; font-size: 14px !important; font-weight: 600; }
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div>
			<div class="kna-title">Edit Liquidation</div>
			<div class="kna-small text-muted">Update rejected items and resubmit for approval</div>
		</div>
		<a href="<?=base_url('transactions/liquidation');?>" class="btn btn-outline-secondary">
			<i class="fas fa-arrow-left mr-1"></i> Back
		</a>
	</div>

	<!-- Rejected Items Banner -->
	<div id="rejectedBanner" class="kna-rejected-banner d-none">
		<i class="fas fa-exclamation-triangle"></i>
		<strong>Action Required:</strong> <span id="rejectedCount">0</span> item(s) were rejected. Please update the highlighted items below and resubmit.
	</div>

	<div class="card kna-card">
		<div class="card-body">
			<input type="hidden" id="liquidationRef" value="<?=html_escape($liquidation_no);?>">
			<input type="hidden" id="editPageMarker" value="1">
			<input type="hidden" id="cashAdvanceId" value="">

			<!-- Desktop: Full Liquidation Information -->
			<div class="kna-desktop-info">
				<div class="kna-section-title">
					<i class="fas fa-info-circle"></i>
					Liquidation Information
				</div>

				<!-- Row 1: Identifiers & Status -->
				<div class="kna-info-row kna-info-row-3">
					<div class="form-group">
						<label class="kna-form-label">Liquidation No</label>
						<div class="kna-readonly" id="editLiquidationNo">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Cash Advance Ref</label>
						<div class="kna-readonly" id="editCaRef">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Status</label>
						<div class="kna-readonly" id="editStatus" style="background:transparent;border-color:transparent;padding-left:0;">-</div>
					</div>
				</div>

				<!-- Row 2: Payable To, Address, Cost Center -->
				<div class="kna-info-row kna-info-row-3">
					<div class="form-group">
						<label class="kna-form-label">Payable To</label>
						<div class="kna-readonly" id="editPayableTo">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Address</label>
						<div class="kna-readonly" id="editAddress">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Cost Center</label>
						<div class="kna-readonly" id="editCostCenter">-</div>
					</div>
				</div>

				<!-- Row 3: Dates -->
				<div class="kna-info-row kna-info-row-3">
					<div class="form-group">
						<label class="kna-form-label">Submitted Date</label>
						<div class="kna-readonly" id="editSubmittedDate">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Expense Period</label>
						<div class="kna-readonly" id="editExpenseDate">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">CA Date</label>
						<div class="kna-readonly" id="editCaDate">-</div>
					</div>
				</div>

				<!-- Row 3: Financial summary -->
				<div class="kna-info-row kna-info-row-4" style="grid-template-columns: repeat(4, 1fr); margin-bottom:12px;">
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">CA Amount</label>
						<div class="kna-compact-value is-muted" id="editCaAmount">-</div>
					</div>
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">Approved Amount</label>
						<div class="kna-compact-value is-muted" id="editApprovedAmount">-</div>
					</div>
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">Total Liquidated</label>
						<div class="kna-compact-value is-muted" id="editLiquidatedAmount">-</div>
					</div>
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">Variance</label>
						<div class="kna-compact-value is-muted" id="editVariance">-</div>
					</div>
				</div>

				<!-- Notes -->
				<div class="form-group" style="margin-bottom:12px;">
					<label class="kna-form-label">Notes / Purpose</label>
					<div class="kna-readonly" id="editPurpose" style="min-height:48px;align-items:flex-start;padding-top:8px;"></div>
				</div>
			</div>

			<!-- Mobile: Compact Overview (hidden on desktop) -->
			<div class="kna-mobile-overview">
				<div class="lqm-ca">
					<div class="lqm-ca-top">
						<div class="lqm-ca-id">
							<div class="lqm-label">Liquidation</div>
							<div class="lqm-ca-ref" id="mobileLiquidationNo">-</div>
						</div>
						<span class="lqm-status" id="mobileStatus">-</span>
					</div>
					<div class="lqm-ca-purpose">CA <span id="mobileCaRef">-</span> &middot; <span id="mobilePurpose">-</span></div>
					<div class="lqm-ca-balance">
						<div class="lqm-ca-row">
							<span>Approved</span>
							<b id="mobileApprovedAmount">-</b>
						</div>
						<div class="lqm-progress"><i id="mobileProgressBar"></i></div>
						<div class="lqm-ca-row lqm-ca-row-muted">
							<span>Liquidated <span id="mobileTotal">-</span></span>
							<span id="mobileLeft"></span>
						</div>
						<span class="lqm-variance d-none" id="mobileVariance">-</span>
					</div>
					<details class="lqm-ca-details">
						<summary>Liquidation details</summary>
						<div class="lqm-ca-grid">
							<div><span class="lqm-label">CA Amount</span><span id="mobileCaAmount">-</span></div>
							<div><span class="lqm-label">CA Date</span><span id="mobileCaDate">-</span></div>
							<div><span class="lqm-label">Submitted</span><span id="mobileSubmittedDate">-</span></div>
							<div><span class="lqm-label">Expense Period</span><span id="mobileExpenseDate">-</span></div>
							<div class="lqm-full"><span class="lqm-label">Payable To</span><span id="mobilePayableTo">-</span></div>
							<div class="lqm-full"><span class="lqm-label">Cost Center</span><span id="mobileCostCenter">-</span></div>
							<div class="lqm-full"><span class="lqm-label">Address</span><span id="mobileAddress">-</span></div>
						</div>
					</details>
				</div>
			</div>

			<hr />

			<div class="d-flex align-items-center justify-content-between mb-2">
				<div class="kna-section-title" style="margin:0;border:none;padding:0;">
					<i class="fas fa-receipt"></i>
					Expense Items
				</div>
				<button type="button" class="btn btn-outline-secondary" id="btnAddNewItem" style="white-space: nowrap;">
					<i class="fas fa-plus mr-1"></i> Add Item
				</button>
			</div>

			<div id="editExpenseItems"></div>

			<hr />

			<div class="kna-edit-actions">
				<a href="<?=base_url('transactions/liquidation');?>" class="btn btn-outline-secondary">Cancel</a>
				<button type="button" class="btn btn-warning" id="btnSaveAsDraft">
					<i class="fas fa-save mr-1"></i> Save as Draft
				</button>
				<button type="button" class="btn btn-primary" id="btnSaveEdit">
					<i class="fas fa-check mr-1"></i> Save & Resubmit
				</button>
			</div>
		</div>
	</div>

	<!-- Mobile sticky actions -->
	<div class="kna-mobile-sticky-actions">
		<div class="lqm-sticky-total">
			<span>Total</span>
			<b id="mobileStickyTotal">₱0.00</b>
		</div>
		<button type="button" class="btn lqm-btn-ghost" id="btnSaveAsDraftMobile">Save draft</button>
		<button type="button" class="btn btn-primary lqm-btn-submit" id="btnSaveEditMobile">Resubmit</button>
	</div>
</div>

<!-- Lightbox -->
<div id="knaLightbox" class="kna-lightbox d-none">
	<button class="kna-lightbox-close" id="knaLightboxClose">&#x2715;</button>
	<img id="knaLightboxImg" class="kna-lightbox-img" src="" alt="Attachment">
</div>

<script>
window.currentUserId = <?=json_encode((int)$this->session->userdata('user_id'));?>;
</script>