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

	.kna-history-btn-mobile {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 30px;
		height: 30px;
		border-radius: 6px;
		border: 1px solid rgba(255, 255, 255, .35);
		background: rgba(255, 255, 255, .12);
		color: #fff;
		cursor: pointer;
		font-size: 13px;
		flex: 0 0 auto;
	}

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

	.kna-line-card {
		border: 1px solid #e5e7eb;
		border-radius: 6px;
		padding: 10px;
		background: #fff;
	}

	.kna-line-head {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 8px;
	}

	.kna-line-title {
		font-size: 13px;
		font-weight: 700;
		color: #1f2937;
	}

	.kna-item-table-wrap {
		width: 100%;
		display: flex;
		flex-direction: column;
		gap: 6px;
		overflow-x: auto;
	}

	.kna-item-table {
		display: grid;
		grid-template-columns: 130px 140px 140px 110px 90px 170px 1fr;
		gap: 8px;
		align-items: center;
		background: #f8f9fc;
		border: 1px solid #e5e7eb;
		border-radius: 6px;
		padding: 6px;
		min-width: 900px;
	}

	.kna-item-table-row .form-control {
		min-width: 0;
		font-size: 12px;
	}

	.kna-attachment-cell {
		font-size: 12px;
		font-weight: 600;
		margin-bottom: 2px;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.kna-item-table-head {
		background: #e5e7eb;
		border-color: #d1d5db;
		font-size: 12px;
		font-weight: 700;
		color: #374151;
	}

	.kna-receipt-filename,
	.kna-receipt-cell {
		min-width: 0;
		max-width: 100%;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		font-size: 12px;
		font-weight: 600;
		color: #1f2937;
	}

	.kna-receipt-amount-cell {
		font-size: 12px;
		font-weight: 700;
		color: #0f766e;
		text-align: right;
	}

	.kna-vat-indicator {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		font-size: 12px;
		font-weight: 600;
		color: #374151;
	}

	.kna-vat-indicator input {
		width: 14px;
		height: 14px;
		margin: 0;
	}

	.kna-expense-divider {
		border-top: 1px dashed #d1d5db;
		margin: 10px 0 12px;
	}

	hr {
		border: 0;
		border-top: 1px solid #f3f4f6;
		margin: 12px 0;
	}

	/* Info grid rows */
	.kna-info-row {
		display: grid;
		gap: 8px;
		margin-bottom: 10px;
	}

	.kna-info-row-3 {
		grid-template-columns: repeat(3, 1fr);
	}
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

	.kna-fin-card.ca {
		border-left-color: #3b82f6;
	}

	.kna-fin-card.liq {
		border-left-color: #0f766e;
	}

	.kna-fin-card.var {
		border-left-color: #f59e0b;
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

	/* Variance badges */
	.kna-var-badge {
		display: inline-block;
		padding: 2px 10px;
		border-radius: 20px;
		font-size: 12px;
		font-weight: 700;
	}

	.kna-var-balanced {
		background: #d1fae5;
		color: #065f46;
	}

	.kna-var-return {
		background: #fef3c7;
		color: #92400e;
	}

	.kna-var-reimburse {
		background: #dbeafe;
		color: #1e40af;
	}

	/* Expense table */
	.kna-exp-wrap {
		width: 100%;
		overflow-x: auto;
	}

	.kna-exp-mobile {
		display: none;
	}

	.kna-exp-table {
		min-width: 780px;
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

	.kna-exp-table td:first-child {
		border-left: 1px solid #f1f5f9;
	}

	.kna-exp-table tbody tr:nth-child(odd) td {
		background: #ffffff;
	}

	.kna-exp-table tbody tr:nth-child(even) td {
		background: #f8fafc;
	}

	.kna-exp-table tbody tr:hover td {
		background: #f0f9ff;
	}

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

	.kna-vat-yes {
		background: #d1fae5;
		color: #065f46;
		padding: 2px 7px;
		border-radius: 10px;
		font-size: 11px;
		font-weight: 700;
		white-space: nowrap;
	}

	.kna-vat-no {
		background: #f3f4f6;
		color: #9ca3af;
		padding: 2px 7px;
		border-radius: 10px;
		font-size: 11px;
		font-weight: 700;
	}

	/* Vendor display */
	.kna-vendor-display {
		font-size: 12px;
		line-height: 1.4;
	}
	.kna-vendor-sub {
		font-size: 11px;
		color: #6b7280;
		margin-top: 2px;
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

	.kna-file-wrap a:hover {
		text-decoration: underline;
	}

	/* ===== MOBILE COMPACT OVERVIEW (Hidden on desktop) ===== */
	.kna-mobile-overview { display: none; }

	/* ===== MOBILE CARDS ===== */
	.kna-exp-card {
		border: 1px solid #e5e7eb;
		border-radius: 12px;
		background: #fff;
		padding: 0;
		box-shadow: 0 2px 8px rgba(20, 30, 50, .06);
		margin-bottom: 12px;
		overflow: hidden;
	}

	.kna-exp-card-head {
		display: flex;
		justify-content: space-between;
		gap: 10px;
		align-items: flex-start;
		padding: 10px 12px;
		border-bottom: 1px solid #eef2f7;
		background: #fafbfc;
	}

	.kna-exp-card-head-left {
		display: flex;
		flex-direction: column;
		gap: 2px;
		min-width: 0;
		flex: 1;
	}

	.kna-exp-card-badge {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 22px;
		height: 22px;
		border-radius: 50%;
		background: var(--brand-700);
		color: #fff;
		font-size: 10px;
		font-weight: 700;
		margin-bottom: 2px;
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

	.kna-exp-card-body {
		padding: 10px 12px;
	}

	.kna-exp-card-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 10px;
		margin-bottom: 10px;
	}

	.kna-exp-card-field {
		display: flex;
		flex-direction: column;
		gap: 3px;
	}

	.kna-exp-card-field-full {
		grid-column: 1 / -1;
	}

	.kna-exp-card-label {
		font-size: 10px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: .3px;
		color: #6b7280;
	}

	.kna-exp-card-value {
		font-size: 12px;
		color: #1f2937;
	}

	.kna-exp-card-attach {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
	}

	.kna-mobile-summary {
		display: none;
		margin-top: 10px;
		padding: 10px 12px;
		border: 1px solid #d1fae5;
		border-radius: 8px;
		background: #f0fdf4;
	}

	.kna-mobile-summary .kna-fin-label {
		margin-bottom: 2px;
	}

	/* Status badges on mobile */
	.kna-status-badge {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		padding: 3px 10px;
		border-radius: 20px;
		font-size: 10px;
		font-weight: 700;
		margin-top: 4px;
	}

	.kna-status-approved {
		background: #d1fae5;
		color: #065f46;
	}

	.kna-status-rejected {
		background: #fee2e2;
		color: #991b1b;
	}

	/* Lightbox */
	.kna-lightbox {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, .88);
		z-index: 9999;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.kna-lightbox.d-none {
		display: none !important;
	}

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

	@media (max-width: 767.98px) {
		.kna-desktop-info { display: none !important; }
		.kna-mobile-overview { display: block; }

		.kna-page {
			padding: 8px 8px 12px;
		}

		.kna-title {
			font-size: 18px;
		}

		.kna-info-row-3 {
			grid-template-columns: 1fr;
		}

		.kna-fin-value {
			font-size: 14px;
		}

		.kna-exp-wrap {
			overflow: visible;
		}

		.kna-exp-mobile {
			display: block;
		}

		.kna-exp-table {
			display: none !important;
		}

		.kna-thumb-wrap {
			max-width: 68px;
			margin-right: 4px;
		}

		.kna-thumb {
			width: 52px;
			height: 46px;
		}

		.kna-thumb-label {
			max-width: 68px;
		}

		.kna-mobile-summary {
			display: block;
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

	/* ─── Timeline ─── */
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

	.kna-timeline-item:last-child {
		padding-bottom: 0;
	}

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

	.kna-timeline-item.is-done {
		border-left-color: #22c55e;
	}

	.kna-timeline-item.is-done::before {
		background: #22c55e;
		box-shadow: 0 0 0 2px #dcfce7;
	}

	.kna-timeline-item.is-current {
		border-left-color: var(--accent-600);
	}

	.kna-timeline-item.is-current::before {
		background: var(--accent-600);
		box-shadow: 0 0 0 2px var(--accent-200);
	}

	.kna-timeline-item.is-pending {
		border-left-color: #d1d5db;
	}

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

	@media (max-width: 480px) {
		.kna-timeline-item {
			padding: 10px 0 10px 20px;
			font-size: 11px;
		}
		.kna-timeline-item-name {
			font-size: 11px;
		}
		.kna-timeline-item-remarks {
			font-size: 11px;
		}
	}

	@media (max-width: 767.98px) {
		.kna-page > .d-flex.mb-2 { margin-bottom: 12px !important; }
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
		.kna-card .card-body > .kna-section-title { display: none !important; }
		.lqm-ca-tools { display: flex; align-items: center; gap: 8px; }
		.lqm-ca-tools .lqm-icon-btn { width: 34px; height: 34px; font-size: 14px; color: var(--brand-700); background: var(--brand-100); }
	}
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div>
			<div class="kna-title">Liquidation Details</div>
		</div>
		<div class="d-flex gap-2">
			<a id="btnEditLiquidationDesktop" class="btn btn-outline-warning d-none mr-2" href="#">
				<i class="fas fa-edit mr-1"></i> Edit
			</a>
			<a href="<?= base_url('transactions/liquidation'); ?>" class="btn btn-outline-secondary">
				<i class="fas fa-arrow-left mr-1"></i> Back
			</a>
		</div>
	</div>

	<div class="card kna-card">
		<div class="card-body">
			<input type="hidden" id="liquidationRef" value="<?= html_escape($liquidation_no); ?>">

			<!-- Desktop: Full Liquidation Information -->
			<div class="kna-desktop-info">
				<div class="kna-section-title kna-section-title-row">
					<span class="kna-section-title-label"><i class="fas fa-info-circle"></i> Liquidation Information</span>
					<button type="button" class="kna-history-btn" id="btnShowHistory" title="View History">
						<i class="fas fa-history"></i>
					</button>
				</div>

				<!-- Row 1: Identifiers & Status -->
				<div class="kna-info-row kna-info-row-3">
					<div class="form-group">
						<label class="kna-form-label">Liquidation No</label>
						<div class="kna-readonly" id="viewLiquidationNo">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Cash Advance Ref</label>
						<div class="kna-readonly" id="viewCaRef">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Status</label>
						<div class="kna-readonly" id="viewStatus"
							style="background:transparent;border-color:transparent;padding-left:0;">-</div>
					</div>
				</div>

				<!-- Row 2: Payable To, Address, Cost Center -->
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

				<!-- Row 3: Dates -->
				<div class="kna-info-row kna-info-row-3">
					<div class="form-group">
						<label class="kna-form-label">Submitted Date</label>
						<div class="kna-readonly" id="viewSubmittedDate">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">Expense Period</label>
						<div class="kna-readonly" id="viewExpenseDate">-</div>
					</div>
					<div class="form-group">
						<label class="kna-form-label">CA Date</label>
						<div class="kna-readonly" id="viewCaDate">-</div>
					</div>
				</div>

				<!-- Row 3: Financial summary -->
				<div class="kna-info-row kna-info-row-4" style="grid-template-columns: repeat(4, 1fr); margin-bottom:12px;">
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">CA Amount</label>
						<div class="kna-compact-value is-muted" id="viewCaAmount">-</div>
					</div>
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">Approved Amount</label>
						<div class="kna-compact-value is-muted" id="viewApprovedAmount">-</div>
					</div>
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">Total Liquidated</label>
						<div class="kna-compact-value is-muted" id="viewLiquidatedAmount">-</div>
					</div>
					<div class="form-group kna-compact-field">
						<label class="kna-form-label">Variance</label>
						<div class="kna-compact-value is-muted" id="viewVariance">-</div>
					</div>
				</div>

				<!-- Notes -->
				<div class="form-group" style="margin-bottom:12px;">
					<label class="kna-form-label">Notes / Purpose</label>
					<div class="kna-readonly" id="viewPurpose"
						style="min-height:48px;align-items:flex-start;padding-top:8px;"></div>
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
						<div class="lqm-ca-tools">
							<span class="lqm-status" id="mobileStatus">-</span>
							<button type="button" class="lqm-icon-btn" id="btnShowHistoryMobile" title="View History" aria-label="View history"><i class="fas fa-history"></i></button>
						</div>
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
					<a href="#" class="lqm-btn lqm-btn-outline w-100 d-none" id="btnEditLiquidation" style="margin-top:12px;text-decoration:none;"><i class="fas fa-pen"></i> Edit liquidation</a>
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