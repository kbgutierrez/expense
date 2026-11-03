<style>
    .kna-page { padding: 12px 14px; }
    .kna-card { border: 1px solid #d9e0e7 !important; border-radius: 6px; background: #fff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
    .kna-card .card-body { padding: .85rem; }
    .kna-title { font-size: 20px; font-weight: 600; margin: 0; line-height: 1.2; }
    .kna-small { font-size: 12px !important; line-height: 1.35; }
    .kna-form-label { margin-bottom: .3rem; font-weight: 600; font-size: 12px; }

    .kna-pivot-wrap {
        overflow-x: auto;
        border: 1px solid #e5ecf3;
        border-radius: 6px;
    }

    .kna-pivot-table {
        border-collapse: collapse;
        width: 100%;
        min-width: 720px;
        font-size: 12px;
        white-space: nowrap;
    }

    .kna-pivot-table th,
    .kna-pivot-table td {
        border: 1px solid #e5ecf3;
        padding: .45rem .55rem;
        vertical-align: middle;
    }

    .kna-pivot-table thead th {
        background: var(--brand-50);
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .kna-pivot-table td.kna-pivot-rowhead,
    .kna-pivot-table th.kna-pivot-rowhead {
        position: sticky;
        left: 0;
        background: var(--brand-50);
        z-index: 2;
        text-align: left;
        min-width: 220px;
    }

    .kna-pivot-table td.kna-pivot-amount {
        text-align: right;
    }

    .kna-pivot-table tfoot td,
    .kna-pivot-table tfoot th {
        background: var(--brand-100);
        font-weight: 700;
    }

    .kna-pivot-col-name {
        font-weight: 700;
        display: block;
    }

    .kna-pivot-col-meta {
        color: #6b7280;
        font-weight: 400;
    }

    .kna-badge-proxy {
        background: var(--brand-100);
        color: var(--brand-800);
        padding: .1rem .35rem;
        border-radius: 3px;
        font-size: 10px;
        font-weight: 600;
        display: inline-block;
        margin-top: 2px;
    }

    @media (max-width: 767.98px) {
        .kna-page { padding: 8px 8px 12px; }
    }
</style>

<div class="page-inner kna-page" id="consolidationPage">
    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap" style="gap:.5rem;">
        <div>
            <div class="kna-title">Batch Approval</div>
            <div class="kna-small text-muted">Summary of reimbursements currently awaiting your approval, grouped by expense type and team member.</div>
        </div>
        <a href="<?= base_url('transactions/approvals') ?>" class="btn btn-outline-secondary btn-sm kna-small">
            <i class="fas fa-arrow-left"></i> Back to Approvals
        </a>
    </div>

    <div class="card kna-card mb-2">
        <div class="card-body py-2 d-flex align-items-end flex-wrap" style="gap:.6rem;">
            <div>
                <label class="kna-form-label mb-1">Period <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm kna-small" id="filterConsolidationDateRange" placeholder="Select date range" style="width:220px;">
            </div>
            <div>
                <label class="kna-form-label mb-1">Sales Office</label>
                <select class="form-control form-control-sm kna-small" id="filterSalesOffice" style="width:220px;">
                    <option value="">All Sales Offices</option>
                </select>
            </div>
            <div>
                <label class="kna-form-label mb-1">Sales District</label>
                <select class="form-control form-control-sm kna-small" id="filterSalesDistrict" style="width:220px;" disabled>
                    <option value="">All Sales Districts</option>
                </select>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm kna-small" id="btnLoadConsolidation">
                    <i class="fas fa-search"></i> Load
                </button>
            </div>
            <div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetConsolidationFilters" title="Clear filters" style="height:31px;width:31px;padding:0;">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card kna-card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap" style="gap:.5rem;">
                <div class="kna-small text-muted" id="consolidationSummary">Select a period and click Load.</div>
                <button type="button" class="btn btn-success btn-sm kna-small" id="btnApproveSelected" disabled>
                    <i class="fas fa-check"></i> Approve Selected
                </button>
            </div>
            <div class="kna-pivot-wrap">
                <table class="kna-pivot-table" id="pivotTable">
                    <thead id="pivotThead"></thead>
                    <tbody id="pivotTbody">
                        <tr>
                            <td class="text-center text-muted kna-small py-4">No data loaded yet.</td>
                        </tr>
                    </tbody>
                    <tfoot id="pivotTfoot"></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
