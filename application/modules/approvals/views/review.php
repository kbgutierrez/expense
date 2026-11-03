<style>
    .kna-page { padding: 12px 14px; }
    .kna-card { border: 1px solid #d9e0e7 !important; border-radius: 6px; background: #fff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
    .kna-card .card-body { padding: .85rem; }
    .kna-title { font-size: 20px; font-weight: 600; margin: 0; line-height: 1.2; }
    .kna-small { font-size: 12px !important; line-height: 1.35; }
    .kna-form-label { margin-bottom: .3rem; font-weight: 600; font-size: 12px; }
    .kna-badge { padding: .2rem .4rem; border-radius: 3px; font-size: 11px; font-weight: 600; display: inline-block; }
    .kna-badge-pending { background: #fff9db; color: #f59f00; }
    .kna-badge-approved { background: #e8f7ee; color: #17663a; }
    .kna-badge-rejected { background: #fff5f5; color: #e03131; }
    .kna-badge-partial { background: #e9f3ff; color: #1b4f88; }

    .kna-history-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: 1px solid #d9e0e7;
        background: #fff;
        color: #4b5563;
        cursor: pointer;
        font-size: 12px;
        flex: 0 0 auto;
        transition: background .15s, color .15s, border-color .15s;
    }
    .kna-history-btn:hover { background: var(--brand-50); border-color: var(--brand-700); color: var(--brand-800); }

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

    /* ─── Compact Overview Grid ─── */
    .kna-overview-wrapper {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }
    .kna-overview-compact, .kna-overview-fields {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        border: none;
        border-radius: 0;
        overflow: hidden;
        background: #fff;
    }
    .kna-overview-fields {
        border-top: 1px solid #f1f5f9;
    }
    .kna-overview-cell {
        padding: 10px 12px;
        border-right: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
        min-height: 56px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: background-color 0.08s ease;
    }
    .kna-overview-cell:hover { background-color: #fafafa; }
    .kna-overview-cell:nth-child(4n) { border-right: none; }
    .kna-overview-cell:nth-last-child(-n+4) { border-bottom: none; }
    .kna-overview-cell.wide { grid-column: span 2; }
    .kna-overview-cell.full { grid-column: span 4; }
    
    .kna-overview-label {
        font-size: 10px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
        line-height: 1.2;
    }
    .kna-overview-value {
        font-size: 12px;
        font-weight: 600;
        color: #111827;
        line-height: 1.35;
        word-break: break-word;
    }
    .kna-overview-value.kna-amount {
        font-size: 14px;
        font-weight: 700;
        color: #059669;
        letter-spacing: -0.3px;
    }
    .kna-overview-value.kna-purpose {
        font-weight: 400;
        color: #374151;
        line-height: 1.5;
        white-space: pre-wrap;
    }
    .kna-overview-value.kna-address {
        font-weight: 400;
        color: #374151;
        line-height: 1.4;
    }

    /* Editable text inputs inside overview */
    .kna-ca-field-input {
        font-size: 12px;
        padding: 4px 8px;
        height: 30px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        color: #111827;
        min-width: 140px;
        width: 100%;
        background: #fff;
    }
    .kna-ca-field-input:focus {
        outline: none;
        border-color: var(--brand-700);
        box-shadow: 0 0 0 3px rgba(var(--brand-rgb), 0.08);
    }

    /* Cost Center in compact cell */
    .kna-overview-cell .select2-container {
        width: 100% !important;
    }
    .kna-overview-cell .select2-selection {
        height: 30px !important;
        border-color: #d1d5db !important;
        border-radius: 4px !important;
        font-size: 12px;
    }
    .kna-overview-cell .select2-selection__rendered {
        line-height: 28px !important;
        font-size: 12px;
        color: #111827;
        padding-left: 8px !important;
    }
    .kna-overview-cell .select2-selection__arrow {
        height: 28px !important;
        right: 4px !important;
    }
    .kna-cc-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .kna-field-hint-inline {
        font-size: 10px;
        color: #9ca3af;
        font-weight: 400;
        font-style: italic;
    }

    /* ─── Update Bar ─── */
    .kna-ca-update-bar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 10px 12px;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
    }

    /* ─── Collapsible Document Section ─── */
    .kna-doc-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        cursor: pointer;
        transition: background-color 0.1s ease;
    }
    .kna-doc-bar:hover { background: #f3f4f6; }
    .kna-doc-bar-left {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
    }
    .kna-doc-bar-left i { color: #dc2626; font-size: 14px; }
    .kna-doc-bar-right {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: #6b7280;
        font-weight: 600;
    }
    .kna-doc-viewer-wrap {
        border-top: 1px solid #e5e7eb;
        overflow: hidden;
        background: #fff;
    }
    .kna-doc-viewer-frame {
        width: 100%;
        height: 420px;
        border: 0;
        display: block;
    }

    /* ─── Attachments Strip ─── */
    .kna-attach-strip {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        flex-wrap: wrap;
    }
    .kna-attach-strip-label {
        font-size: 10px;
        font-weight: 700;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    .kna-attach-strip-list {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        flex: 1;
    }
    .kna-attach-strip-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        font-size: 11px;
        color: #374151;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.12s ease;
        text-decoration: none;
    }
    .kna-attach-strip-item:hover {
        background: #e5e7eb;
        border-color: #d1d5db;
    }
    .kna-attach-strip-item i { font-size: 12px; }
    .kna-attach-strip-item.img i { color: #0891b2; }
    .kna-attach-strip-item.pdf i { color: #dc2626; }
    .kna-attach-strip-item.doc i { color: var(--brand-700); }

    /* ─── Change Badge ─── */
    .kna-change-badge {
        font-size: 9px;
        font-weight: 700;
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        padding: 1px 6px;
        border-radius: 3px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        opacity: 0;
        transition: opacity 0.25s ease;
        pointer-events: none;
    }
    .kna-change-badge.is-visible { opacity: 1; }
    .kna-change-badge.is-saving {
        color: #6b7280;
        background: #f3f4f6;
        border-color: #d1d5db;
    }
    .kna-change-badge.is-error {
        color: #e03131;
        background: #fff5f5;
        border-color: #fecaca;
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
    .kna-timeline-item.is-done::before { background: #22c55e; box-shadow: 0 0 0 2px #dcfce7; }
    .kna-timeline-item.is-current { border-left-color: var(--brand-700); }
    .kna-timeline-item.is-current::before { background: var(--brand-700); box-shadow: 0 0 0 2px var(--brand-200); }
    .kna-timeline-item.is-pending { border-left-color: #d1d5db; }
    .kna-timeline-item.is-pending::before { background: #d1d5db; box-shadow: 0 0 0 2px #f3f4f6; }
    .kna-timeline-item-top { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .kna-timeline-item-name { font-weight: 700; color: #1f2937; font-size: 12px; }
    .kna-timeline-item-remarks { color: #4b5563; font-size: 12px; line-height: 1.5; word-break: break-word; }

    /* ─── SPLIT TABLE SHELL ─── */
    .kna-review-desktop {
        width: 100%;
        border: 1px solid #e5ecf3;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }
    .kna-review-table-shell { display: flex; width: 100%; border-bottom: 1px solid #e5ecf3; }
    .kna-review-table-wrap-main { flex: 1 1 auto; overflow-x: auto; overflow-y: hidden; }
    .kna-review-table-main {
        width: 100%; margin-bottom: 0; font-size: 12px;
        border-collapse: separate; border-spacing: 0;
    }
    .kna-review-desktop-liquidation .kna-review-table-main {
        min-width: 2040px;
    }
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(2), .kna-review-desktop-liquidation .kna-review-table-main td.kna-col-description {
        min-width: 340px;
        width: 340px;
    }
    .kna-review-table-main th, .kna-review-table-main td {
        padding: 5px 6px; vertical-align: middle; border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f1f5f9; color: #1f2937; box-sizing: border-box;
    }
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(3), .kna-review-desktop-liquidation .kna-review-table-main td.kna-col-category {
        min-width: 240px;
        width: 240px;
    }
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(4) {
        min-width: 170px;
        width: 170px;
    }
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(5) {
        min-width: 150px;
        width: 150px;
    }
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(6) {
        min-width: 150px;
        width: 150px;
    }
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(7),
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(8),
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(9),
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(10) {
        min-width: 130px;
        width: 130px;
    }
    .kna-review-desktop-liquidation .kna-review-table-main th:nth-child(11), .kna-review-desktop-liquidation .kna-review-table-main td.kna-col-vendor {
        min-width: 420px;
        width: 420px;
    }
    .kna-review-desktop-ca .kna-review-table-wrap-main {
        overflow-x: hidden;
    }
    .kna-review-desktop-ca .kna-review-table-main {
        min-width: 0;
        table-layout: fixed;
    }
    .kna-review-desktop-ca .kna-review-table-main th:nth-child(1),
    .kna-review-desktop-ca .kna-review-table-main td:nth-child(1) {
        width: auto;
    }
    .kna-review-desktop-ca .kna-review-table-main th:nth-child(2),
    .kna-review-desktop-ca .kna-review-table-main td:nth-child(2) {
        width: 220px;
        min-width: 220px;
    }
    .kna-review-table-main th {
        background: var(--brand-50); font-size: 11px; font-weight: 700; color: #475569;
        text-transform: uppercase; letter-spacing: .3px; white-space: nowrap;
    }
    .kna-review-table-main th.text-right {
        text-align: right !important;
    }
    .kna-review-table-main th.text-center {
        text-align: center !important;
    }
    .kna-review-table-main .kna-edit-gross {
        text-align: right !important;
    }
    .kna-review-table-main tbody tr:nth-child(odd) td { background: #ffffff; }
    .kna-review-table-main tbody tr:nth-child(even) td { background: #f8fafc; }
    .kna-review-table-main tbody tr:hover td { background: var(--brand-50); }
    .kna-review-table-main tbody tr.is-approved td { background: #f0fdf4 !important; }
    .kna-review-table-main tbody tr.is-rejected td { background: #fef2f2 !important; }

    .kna-review-table-wrap-action {
        width: 180px; flex: 0 0 180px; overflow: hidden;
    }
    .kna-review-table-action {
        width: 100%; margin-bottom: 0; font-size: 12px;
        border-collapse: separate; border-spacing: 0; border-left: 2px solid #e5ecf3;
    }
    .kna-review-table-action th, .kna-review-table-action td {
        padding: 6px; vertical-align: middle; border-bottom: 1px solid #f1f5f9;
        text-align: center; background: #fff; box-sizing: border-box;
    }
    .kna-review-table-action th {
        background: var(--brand-50); font-size: 11px; font-weight: 700; color: #475569;
        text-transform: uppercase; letter-spacing: .3px;
    }
    .kna-review-table-action tbody tr:nth-child(odd) td { background: #ffffff; }
    .kna-review-table-action tbody tr:nth-child(even) td { background: #f8fafc; }
    .kna-review-table-action tbody tr.is-approved td { background: #f0fdf4 !important; }
    .kna-review-table-action tbody tr.is-rejected td { background: #fef2f2 !important; }

    .kna-col-action {
        width: 180px; min-width: 180px; max-width: 180px;
        padding: 6px !important; text-align: center;
    }

    /* ─── Toggle Decision Buttons ─── */
    .kna-item-decision {
        display: flex; flex-direction: column; align-items: center; gap: 6px; width: 100%;
    }
    .kna-toggle-group {
        display: flex; width: 100%; border: 1.5px solid #d1d5db;
        border-radius: 6px; overflow: hidden; background: #fff;
    }
    .kna-toggle-btn {
        flex: 1; border: none; background: #fff; font-size: 11px; font-weight: 700;
        padding: 5px 4px; cursor: pointer; color: #6b7280; transition: all .15s ease;
        line-height: 1.2; display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    }
    .kna-toggle-btn:first-child { border-right: 1.5px solid #d1d5db; }
    .kna-toggle-btn:hover { background: #f9fafb; }
    .kna-toggle-btn.is-approve:hover { color: #17663a; }
    .kna-toggle-btn.is-reject:hover { color: #e03131; }
    .kna-toggle-btn.is-approve.is-active { background: #17663a; color: #fff; }
    .kna-toggle-btn.is-reject.is-active { background: #e03131; color: #fff; }
    .kna-toggle-btn.is-active { box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
    .kna-toggle-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .kna-toggle-btn:disabled:hover { background: #fff; color: #6b7280; }

    .kna-item-remark {
        font-size: 12px; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px;
        width: 100%; resize: vertical; min-height: 60px; line-height: 1.5; color: #374151; background: #fafafa;
    }
    .kna-item-remark:focus {
        outline: none; border-color: var(--brand-700); background: #fff;
        box-shadow: 0 0 0 3px rgba(var(--brand-rgb), 0.08);
    }
    .kna-item-remark::placeholder { color: #9ca3af; font-size: 11px; }
    .kna-item-remark.d-none { display: none !important; }
    .kna-item-remark[readonly] { background: #f3f4f6; color: #6b7280; cursor: default; }

    /* ─── Footer ─── */
    .kna-review-footer { display: flex; width: 100%; border-top: 2px solid #6ee7b7; background: #ecfdf5; }
    .kna-review-footer-main {
        flex: 1 1 auto; display: flex; align-items: center; justify-content: flex-end;
        gap: 12px; padding: 10px 12px; min-height: 46px;
    }
    .kna-review-footer-label {
        font-size: 12px; font-weight: 700; color: #065f46;
        text-transform: uppercase; letter-spacing: .3px;
    }
    .kna-review-footer-amount { text-align: right; }

    .kna-exp-mobile { display: none; }
    .kna-amount-main { font-weight: 700; color: #0f766e; }
    .kna-amount-breakdown { font-size: 10px; color: #9ca3af; margin-top: 2px; }
    .kna-rownum { color: #9ca3af; font-size: 11px; text-align: center; }

    .kna-vat-indicator {
        display: inline-flex; align-items: center; gap: 5px; font-size: 11px;
        font-weight: 600; color: #374151; cursor: pointer; margin: 0;
    }
    .kna-vat-check { width: 15px; height: 15px; margin: 0; accent-color: var(--brand-700); cursor: pointer; }
    .kna-vat-check:disabled { opacity: 0.5; cursor: not-allowed; }

    .kna-thumb-wrap {
        display: inline-flex; flex-direction: column; align-items: center; cursor: pointer;
        margin: 2px 6px 2px 0; max-width: 72px; vertical-align: top; text-align: center;
    }
    .kna-thumb { width: 58px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; transition: transform .15s, border-color .15s; }
    .kna-thumb:hover { transform: scale(1.08); border-color: var(--brand-700); }
    .kna-thumb-label {
        font-size: 10px; color: #6b7280; margin-top: 2px; max-width: 72px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block;
    }
    .kna-file-wrap { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; margin-bottom: 3px; }
    .kna-file-wrap a { color: var(--brand-700); text-decoration: none; font-weight: 600; }
    .kna-file-wrap a:hover { text-decoration: underline; }

    /* ─── Mobile Cards (native-app style: bigger touch targets, clear
       hierarchy - amount/title prominent, fields grouped in a 2-up grid
       instead of one long stacked list, full-width thumb-friendly
       Approve/Reject) ─── */
    .kna-exp-card {
        border: 1px solid #e5e7eb; border-radius: 14px; background: #fff;
        padding: 14px; margin-bottom: 12px; box-shadow: 0 2px 8px rgba(20, 30, 50, .06);
    }
    .kna-exp-card.is-approved { border-color: #86efac; background: #f0fdf4; }
    .kna-exp-card.is-rejected { border-color: #fca5a5; background: #fef2f2; }
    .kna-exp-card-head {
        display: flex; justify-content: space-between; gap: 10px; align-items: flex-start;
        padding-bottom: 12px; border-bottom: 1px solid #eef2f7; margin-bottom: 12px;
    }
    .kna-exp-card-title { font-size: 15px; font-weight: 700; color: #111827; line-height: 1.3; }
    .kna-exp-card-sub { font-size: 12px; font-weight: 600; color: #6b7280; margin-left: 4px; }
    .kna-exp-card-meta { font-size: 12px; color: #6b7280; margin-top: 4px; }
    .kna-exp-card-amount { text-align: right; flex: 0 0 auto; }
    .kna-exp-card-amount .kna-amount-main { font-size: 17px; }
    .kna-exp-card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 10px; }
    .kna-exp-card-field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .kna-exp-card-field-full { grid-column: 1 / -1; }
    .kna-exp-card-label {
        font-size: 10.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .3px; color: #6b7280;
    }
    .kna-exp-card-value { font-size: 13px; color: #1f2937; }
    .kna-exp-card-attach { display: flex; flex-wrap: wrap; gap: 6px; }

    /* Native-feeling form controls inside mobile cards specifically -
       bigger than the compact desktop table inputs, easier to tap. */
    .kna-exp-card-value input.form-control,
    .kna-exp-card-value select.form-control {
        font-size: 13px; padding: 9px 10px; height: 40px; border-radius: 8px;
        border: 1px solid #dbe2ea; background: #fafbfc;
    }
    .kna-exp-card-value input.form-control:focus,
    .kna-exp-card-value select.form-control:focus {
        outline: none; border-color: var(--brand-700); background: #fff;
        box-shadow: 0 0 0 3px rgba(var(--brand-rgb), 0.08);
    }
    .kna-exp-card-value .kna-vat-check { width: 20px; height: 20px; }
    .kna-exp-card-value .kna-net-value, .kna-exp-card-value .kna-vat-value { font-weight: 600; }

    /* ─── Submit Bar ─── */
    .kna-review-items-card .card-body { padding: .75rem .85rem 0; }
    .kna-review-items-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; flex-wrap: wrap; padding-bottom: .65rem;
        border-bottom: 1px solid #eef2f7; margin-bottom: .65rem;
    }
    .kna-review-items-body { min-height: 280px; padding-bottom: .5rem; }
    .kna-review-submit-bar {
        display: flex; align-items: flex-end; flex-wrap: wrap; gap: 12px;
        margin-bottom: .8rem; border: 1px solid #e5ecf3; border-radius: 8px;
        background: var(--brand-50); padding: 10px 12px;
    }
    .kna-review-submit-stats { display: flex; flex-wrap: wrap; gap: 8px; flex: 1 1 280px; }
    .kna-review-stat-chip {
        border: 1px solid #e5ecf3; border-radius: 6px; background: #fff;
        padding: 8px 12px; min-width: 120px;
    }
    .kna-review-stat-chip-label {
        font-size: 10px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 2px;
    }
    .kna-review-stat-chip-value { font-size: 13px; font-weight: 700; color: #1f2937; }
    .kna-review-stat-chip-value.is-approved { color: #17663a; }
    .kna-review-stat-chip-value.is-rejected { color: #e03131; }
    .kna-review-submit-remarks { flex: 2 1 320px; min-width: 220px; }
    .kna-review-submit-action { flex: 0 0 auto; min-width: 200px; }
    .kna-review-submit-action .btn { min-width: 200px; }
    .kna-review-context-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }

    /* ─── Lightbox ─── */
    .kna-lightbox {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, .88); z-index: 9999;
        display: flex; align-items: center; justify-content: center;
    }
    .kna-lightbox.d-none { display: none !important; }
    .kna-lightbox-img {
        max-width: 90vw; max-height: 88vh; border-radius: 6px;
        box-shadow: 0 8px 40px rgba(0, 0, 0, .6);
    }
    .kna-lightbox-close {
        position: fixed; top: 16px; right: 20px; background: none; border: none;
        color: #fff; font-size: 32px; cursor: pointer; line-height: 1; z-index: 10000;
    }

    /* ─── Responsive ─── */
    @media (max-width: 991.98px) {
        .kna-overview-compact, .kna-overview-fields { grid-template-columns: repeat(3, 1fr); }
        .kna-overview-cell:nth-child(4n) { border-right: 1px solid #f1f5f9; }
        .kna-overview-cell:nth-child(3n) { border-right: none; }
        .kna-overview-cell.wide { grid-column: span 2; }
        .kna-overview-cell.full { grid-column: span 3; }
        .kna-overview-cell:nth-last-child(-n+4) { border-bottom: 1px solid #f1f5f9; }
        .kna-overview-cell:nth-last-child(-n+3) { border-bottom: none; }
    }
    @media (max-width: 767.98px) {
        .kna-page { padding: 8px 8px 12px; }
        .kna-title { font-size: 18px; }

        /* Requester overview: ditch the bordered multi-column grid AND
           the one-row-per-field stacked list (both ate too much vertical
           space, pushing Line Items below the fold) for a compact native
           "settings row" pattern - label left, value right, same line.
           Nothing is hidden, it just takes half the height. Only the
           long-form Purpose/Description text stays stacked, since a
           paragraph can't sit next to its label on one line. */
        .kna-overview-wrapper { border: none; }
        .kna-overview-compact, .kna-overview-fields { display: block; }
        .kna-overview-cell {
            display: flex; flex-direction: row; flex-wrap: wrap; align-items: center; justify-content: space-between;
            gap: 4px 10px; grid-column: auto; min-height: 0;
            padding: 7px 0; border: none !important;
        }
        .kna-overview-label { margin-bottom: 0; flex: 0 0 auto; }
        .kna-overview-value { flex: 1 1 auto; text-align: right; }
        .kna-overview-value input, .kna-overview-value select { width: 100%; }
        .kna-overview-value.kna-amount { font-size: 14px; }
        .kna-amount-words { flex-basis: 100%; text-align: right; }

        .kna-overview-cell.wide { display: block; padding: 8px 0; }
        .kna-overview-cell.wide .kna-overview-label { margin-bottom: 4px; }
        .kna-overview-cell.wide .kna-overview-value { text-align: left; }

        .kna-doc-viewer-frame { height: 320px; }
        .kna-review-desktop { display: none !important; }
        .kna-exp-mobile { display: block; }
        .kna-review-items-body { min-height: auto; }
        .kna-review-submit-bar { margin-bottom: .65rem; }
        .kna-review-submit-action, .kna-review-submit-action .btn { width: 100%; min-width: 0; }
        .kna-thumb-wrap { max-width: 68px; margin-right: 4px; }
        .kna-thumb { width: 52px; height: 46px; }
        .kna-timeline-item { min-width: 0; flex-basis: 100%; }

        /* Slightly bigger tap targets for Approve/Reject inside mobile
           cards - not oversized, just enough to comfortably tap. */
        .kna-exp-card .kna-toggle-btn { padding: 9px 8px; font-size: 12.5px; min-height: 38px; }
        .kna-exp-card .kna-item-remark { font-size: 13px; padding: 9px 11px; min-height: 56px; }
        .kna-exp-card .kna-cancel-reject { padding: 7px 12px; font-size: 12px; }
    }

    /* ─── Remark required shake animation ─── */
    @keyframes kna-remark-shake {
        0%, 100% { transform: translateX(0); }
        20% { transform: translateX(-4px); }
        40% { transform: translateX(4px); }
        60% { transform: translateX(-3px); }
        80% { transform: translateX(3px); }
    }
    .kna-remark-required {
        border-color: #e03131 !important; background: #fff5f5 !important;
        animation: kna-remark-shake 0.4s ease-in-out;
    }

    .kna-vendor-display { font-size: 12px; line-height: 1.4; }
    .kna-vendor-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }

    .kna-inline-edit-input {
        font-size: 11px;
        line-height: 1.2;
        padding: 3px 7px !important;
        height: 26px !important;
        min-height: 26px !important;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        width: 100%;
        background: #fff;
        box-sizing: border-box;
    }
    .kna-review-items-body .form-control.form-control-sm.kna-inline-edit-input,
    .kna-review-items-body input.kna-inline-edit-input,
    .kna-review-items-body select.kna-inline-edit-input {
        height: 26px !important;
        min-height: 26px !important;
        padding: 3px 7px !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
    }
    .kna-inline-edit-input:focus {
        border-color: var(--brand-700);
        box-shadow: 0 0 0 2px rgba(var(--brand-rgb), 0.08);
    }
    .kna-vendor-edit-wrap {
        display: grid;
        grid-template-columns: 1fr;
        gap: 4px;
    }
    .kna-review-desktop-liquidation .kna-review-table-main td.kna-col-vendor .kna-vendor-edit-wrap {
        grid-template-columns: 1.1fr 1.3fr 0.8fr;
        align-items: center;
        gap: 6px;
    }

    .kna-review-items-body .select2-container {
        width: 100% !important;
    }
    .kna-review-items-body .select2-container .select2-selection--single {
        height: 26px !important;
        min-height: 26px !important;
        border-color: #d1d5db !important;
        border-radius: 4px !important;
        box-sizing: border-box;
    }
    .kna-review-items-body .select2-container .select2-selection__rendered {
        line-height: 24px !important;
        font-size: 11px;
        padding-left: 7px !important;
        padding-right: 24px !important;
    }
    .kna-review-items-body .select2-container .select2-selection__arrow {
        height: 24px !important;
    }
    .kna-review-items-body .select2-container--open,
    .kna-review-items-body .select2-dropdown {
        z-index: 2100;
    }

    .kna-review-table-main td input[type="date"].kna-inline-edit-input {
        padding-right: 26px;
    }
    .kna-review-items-body .select2-container--default .select2-selection--single:focus,
    .kna-review-items-body .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--brand-700) !important;
        box-shadow: 0 0 0 2px rgba(var(--brand-rgb), 0.08);
    }

    .kna-review-table-main td .kna-inline-edit-input,
    .kna-exp-card .kna-inline-edit-input {
        margin: 0;
    }

    .kna-cancel-reject {
        display: flex; align-items: center; justify-content: center; gap: 4px;
        width: 100%; padding: 5px 8px; margin-top: 6px; border: 1px solid #d1d5db;
        border-radius: 4px; background: #f9fafb; color: #6b7280;
        font-size: 11px; font-weight: 600; cursor: pointer; transition: all .15s ease;
    }
    .kna-cancel-reject:hover { background: #f3f4f6; color: #374151; border-color: #9ca3af; }
    .kna-cancel-reject i { font-size: 10px; }

    .kna-readonly-status { display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 4px; }
    .kna-readonly-by { font-size: 10px; color: #9ca3af; text-align: center; margin-bottom: 6px; }
    .kna-readonly-remark {
        font-size: 11px; color: #e03131; background: #fff5f5;
        border: 1px solid #fecaca; border-radius: 4px; padding: 6px 8px;
        text-align: left; line-height: 1.4; max-width: 160px; word-break: break-word;
    }
    /* ─── Amount Comparison Styles ─── */
.kna-amount-original { font-weight: 600; color: #6b7280; text-decoration: line-through; font-size: 12px; }
.kna-amount-approved { font-weight: 700; color: #059669; font-size: 14px; }
.kna-amount-input-wrap { display: flex; flex-direction: column; gap: 2px; }
.kna-original-amount-strike { font-size: 10px; color: #9ca3af; text-decoration: line-through; margin-bottom: 2px; }
.kna-amount-words { font-size: 10px; color: #6b7280; font-style: italic; margin-top: 2px; }
.kna-amount-breakdown { font-size: 10px; color: #9ca3af; margin-top: 2px; }
.kna-amount-breakdown s { color: #9ca3af; }

/* Footer dual amount display */
.kna-review-footer-main { display: flex; align-items: center; justify-content: flex-end; gap: 24px; }
.kna-review-footer-original, .kna-review-footer-approved { text-align: right; }
.kna-review-footer-original .kna-review-footer-amount { color: #6b7280; text-decoration: line-through; font-size: 13px; }
.kna-review-footer-approved .kna-review-footer-amount { color: #0f766e; font-size: 16px; }

/* Overview amount change notice */
.kna-amount-change-notice { background: #f0fdf4; }
.kna-amount-from { color: #6b7280; text-decoration: line-through; font-weight: 600; }
.kna-amount-to { color: #059669; font-weight: 700; }
.kna-amount-words { color: #6b7280; font-size: 11px; font-style: italic; margin-left: 8px; }
</style>

<div class="page-inner kna-page">
    <input type="hidden" id="approvalRef" value="<?=html_escape($approval_id);?>">
    <input type="hidden" id="currentUserId" value="<?=(int)$this->session->userdata('user_id');?>">
    <input type="hidden" id="reviewMode" value="<?=html_escape($review_mode);?>">
    <?php
    $ccData = array();
    if (!empty($cost_centers) && is_array($cost_centers)) {
        foreach ($cost_centers as $cc) {
            $ccData[] = array(
                'cost_center_code' => isset($cc['cost_center_code']) ? $cc['cost_center_code'] : '',
                'cost_center_name' => isset($cc['cost_center_name']) ? $cc['cost_center_name'] : '',
            );
        }
    }

    $expenseTypeData = array();
    if (!empty($expense_types) && is_array($expense_types)) {
        foreach ($expense_types as $et) {
            $expenseTypeData[] = array(
                'expense_code' => isset($et['expense_code']) ? $et['expense_code'] : '',
                'long_text' => isset($et['long_text']) ? $et['long_text'] : '',
            );
        }
    }
    ?>
    <input type="hidden" id="costCentersData" value="<?=html_escape(json_encode($ccData));?>">
    <input type="hidden" id="expenseTypesData" value="<?=html_escape(json_encode($expenseTypeData));?>">

    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap" style="gap: 8px;">
        <div>
            <div class="kna-title" id="reviewTitle">Review Request</div>
            <span class="kna-badge kna-badge-pending mt-1" id="reviewStatusBadge">Awaiting Your Action</span>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
            <button type="button" class="kna-history-btn" id="btnShowHistory" title="View History">
                <i class="fas fa-history"></i>
            </button>
            <a href="<?=base_url('transactions/approvals');?>" class="btn btn-outline-secondary btn-sm kna-small">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card kna-card mb-2">
        <div class="card-body py-2">
            <div class="kna-small font-weight-bold text-muted mb-2 text-uppercase">Request Overview</div>
            <div id="reviewHeaderFields"></div>
        </div>
    </div>

    <div class="card kna-card kna-review-items-card mb-2">
        <div class="card-body">
            <div class="kna-review-items-head">
                <div>
                    <div class="kna-small font-weight-bold text-muted text-uppercase">Line Items for Review</div>
                </div>
            </div>

            <div class="kna-review-submit-bar" id="decisionSummary">
                <div class="kna-review-submit-stats">
                    <div class="kna-review-stat-chip">
                        <div class="kna-review-stat-chip-label">Items Reviewed</div>
                        <div class="kna-review-stat-chip-value" id="summaryReviewed">0 / 0</div>
                    </div>
                    <div class="kna-review-stat-chip">
                        <div class="kna-review-stat-chip-label">Approved Amount</div>
                        <div class="kna-review-stat-chip-value is-approved" id="summaryApprovedAmount">₱0.00</div>
                    </div>
                    <div class="kna-review-stat-chip">
                        <div class="kna-review-stat-chip-label">Rejected Amount</div>
                        <div class="kna-review-stat-chip-value is-rejected" id="summaryRejectedAmount">₱0.00</div>
                    </div>
                </div>
                <div class="kna-review-submit-remarks">
                    <label class="kna-form-label kna-small font-weight-bold text-dark mb-1">Overall Reviewer Remarks</label>
                    <textarea class="form-control kna-small" id="reviewerRemarks" rows="2" placeholder="Optional notes for the entire request..."></textarea>
                </div>
                <div class="kna-review-submit-action">
                    <button type="button" class="btn btn-success btn-sm kna-small font-weight-bold" id="btnSubmitDecision">
                        Submit Decisions
                    </button>
                </div>
            </div>

            <div class="kna-review-submit-bar d-none" id="paymentActionBar">
                <div class="kna-review-submit-stats">
                    <div class="kna-review-stat-chip">
                        <div class="kna-review-stat-chip-label">Current Status</div>
                        <div class="kna-review-stat-chip-value" id="paymentActionStatus">—</div>
                    </div>
                </div>
                <div class="kna-review-submit-remarks">
                    <label class="kna-form-label kna-small font-weight-bold text-dark mb-1" id="paymentActionRemarksLabel">Remarks</label>
                    <textarea class="form-control kna-small" id="paymentActionRemarks" rows="2" placeholder="Optional notes..."></textarea>
                </div>
                <div class="kna-review-submit-action">
                    <button type="button" class="btn btn-success btn-sm kna-small font-weight-bold" id="btnPaymentAction">
                        Action
                    </button>
                </div>
            </div>

            <div class="kna-review-items-body" id="viewApprovalItems"></div>
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
            <ul class="kna-timeline" id="reviewTimeline"></ul>
        </div>
    </div>
</div>

<div id="knaLightbox" class="kna-lightbox d-none">
    <button class="kna-lightbox-close" id="knaLightboxClose">&#x2715;</button>
    <img id="knaLightboxImg" class="kna-lightbox-img" src="" alt="Attachment">
</div>