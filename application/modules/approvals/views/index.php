<style>
    .kna-page {
        padding: 12px 14px;
    }

    .kna-card {
        border: 1px solid #d9e0e7 !important;
        border-radius: 6px;
        background: #fff;
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

    .kna-table td,
    .kna-table th {
        font-size: 12px !important;
        padding: .5rem .45rem;
        vertical-align: middle;
        white-space: nowrap;
    }

    .kna-table-shell {
        display: flex;
        width: 100%;
        border: 1px solid #e5ecf3;
        border-radius: 6px;
        overflow: hidden;
    }

    .kna-table-wrap-main {
        flex: 1 1 auto;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .kna-table-main {
        min-width: 1120px;
        margin-bottom: 0;
    }

    .kna-table-action {
        width: 100px;
        margin-bottom: 0;
        border-left: 1px solid #e5ecf3;
    }

    .kna-table-action th,
    .kna-table-action td {
        text-align: center;
        background: #fff;
    }

    .kna-table-main th,
    .kna-table-action th {
        background: var(--brand-50);
    }

    .kna-tab {
        border: 1px solid #d9e0e7;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 600;
        background: #fff;
        color: #32475b;
        cursor: pointer;
    }

    .kna-tab.is-active {
        background: var(--brand-700);
        border-color: var(--brand-700);
        color: #fff;
    }

    .kna-form-label {
        margin-bottom: .3rem;
        font-weight: 600;
    }

    .kna-badge {
        padding: .2rem .4rem;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }

    .kna-badge-pending {
        background: #fff9db;
        color: #f59f00;
    }

    .kna-badge-approved {
        background: #e8f7ee;
        color: #17663a;
    }

    .kna-badge-partial {
        background: #e9f3ff;
        color: #1b4f88;
    }

    .kna-badge-rejected {
        background: #fff5f5;
        color: #e03131;
    }

    .kna-timeline {
        position: relative;
        padding-left: 24px;
        list-style: none;
        margin: 0;
    }

    .kna-timeline::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 5px;
        bottom: 5px;
        width: 2px;
        background: #e9ecef;
    }

    .kna-timeline-item {
        position: relative;
        margin-bottom: 12px;
        font-size: 12px;
    }

    .kna-timeline-item::before {
        content: '';
        position: absolute;
        left: -21px;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
        background: #dee2e6;
        box-shadow: 0 0 0 2px #dee2e6;
    }

    .kna-timeline-item.is-current::before {
        background: var(--brand-700);
        box-shadow: 0 0 0 2px var(--brand-700);
    }

    .kna-timeline-item.is-done::before {
        background: #17663a;
        box-shadow: 0 0 0 2px #17663a;
    }

    .kna-timeline-item.is-error::before {
        background: #e03131;
        box-shadow: 0 0 0 2px #e03131;
    }

    @media (max-width: 767.98px) {
        .kna-page {
            padding: 8px 8px 12px;
        }

        .kna-table-shell {
            flex-direction: column;
        }

        .kna-table-action {
            width: 100%;
            border-left: none;
            border-top: 1px solid #e5ecf3;
        }
    }
    @media (max-width:767.98px){

    .kna-table-shell{
        display:none;
    }

    .kna-mobile-card{
        border:1px solid #e5ecf3;
        border-radius:8px;
        padding:12px;
        margin-bottom:10px;
        background:#fff;
    }

}
</style>

<div class="page-inner kna-page" id="approvalsListPage">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <div class="kna-title" id="approvalsPageTitle">Pending Approvals</div>
        </div>
    </div>

    <div class="card kna-card mb-2">
        <div class="card-body py-2 d-flex align-items-end flex-wrap" style="gap:.5rem;">
            <div class="d-flex align-items-center flex-wrap" style="gap:.5rem;">
                <button class="kna-tab is-active" data-approval-tab="pending">Pending Approvals</button>
                <button class="kna-tab" data-approval-tab="past">Past Approvals</button>
                <?php if (!empty($hasPaymentCapability)): ?>
                <button class="kna-tab" data-approval-tab="payment">For Payment</button>
                <?php endif; ?>
            </div>
            <div>
                <label class="kna-small kna-form-label mb-1">Search</label>
                <input type="text" class="form-control form-control-sm kna-small" id="filterKeyword" placeholder="Ref no. or requestor" autocomplete="off" style="width:180px;">
            </div>
            <div>
                <label class="kna-small kna-form-label mb-1">Type</label>
                <select class="form-control form-control-sm kna-small" id="filterTransactionType" style="width:160px;">
                    <option value="ALL">All Items</option>
                    <option value="CASH_ADVANCE">Cash Advance</option>
                    <option value="LIQUIDATION">Liquidation</option>
                    <option value="REIMBURSEMENT">Reimbursement</option>
                    <option value="REPLENISHMENT">Replenishment</option>
                </select>
            </div>
            <div>
                <label class="kna-small kna-form-label mb-1">Date Range</label>
                <input type="text" class="form-control form-control-sm kna-small" id="filterDateRange" placeholder="Select date range" style="width:200px;">
            </div>
            <div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetApprovalFilters" title="Clear filters" style="height:31px;width:31px;padding:0;">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card kna-card mb-2 d-none" id="paymentBatchBar">
        <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:.85rem;">
            <div class="kna-small font-weight-bold"><span id="paymentSelectedCount">0</span> selected</div>
            <div id="paymentActionCheckboxes" class="d-flex align-items-center flex-wrap" style="gap:.85rem;">
                <?php if (!empty($hasAdvisoryCapability)): ?>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="paymentDoAdvise">
                    <label class="custom-control-label kna-small" for="paymentDoAdvise">Payment Advisory</label>
                </div>
                <?php endif; ?>
                <?php if (!empty($hasReleaseCapability)): ?>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="paymentDoRelease">
                    <label class="custom-control-label kna-small" for="paymentDoRelease">Payment Release</label>
                </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-primary btn-sm kna-small" id="btnProcessBatchPayment" disabled>
                Process Selected
            </button>
            <?php if (!empty($hasPettyCashSlipCapability)): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm kna-small" id="btnDownloadBatchPettyCashSlips" disabled>
                <i class="fas fa-file-download mr-1"></i> Generate Petty Cash Slips
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card kna-card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="kna-small text-muted" id="resultLabel">Awaiting Your Action</div>
                <div class="kna-small text-muted" id="resultCount">0 Records</div>
            </div>
            <div class="kna-table-shell">
                <div class="kna-table-wrap-main">
                    <table class="table table-sm kna-table kna-table-main" style="width:100%">
                        <thead>
                            <tr>
                                <th class="d-none" id="paymentCheckboxColumnHeader" style="width:28px;"><input type="checkbox" id="paymentSelectAll"></th>
                                <th>Transaction No.</th>
                                <th>Type</th>
                                <th>Requestor</th>
                                <th>Department</th>
                                <th>Amount</th>
                                <th>Submission Date</th>
                                <th class="d-none text-center" id="agingColumnHeader">Aging (Days)</th>
                                <th class="d-none" id="statusColumnHeader">Status</th>
                                <th class="d-none" id="turnaroundColumnHeader">Turnaround (Days)</th>
                                <th class="d-none" id="paymentActionColumnHeader">Payment Step</th>
                            </tr>
                        </thead>
                        <tbody id="matrixTbodyMain"></tbody>
                    </table>
                </div>
                <div>
                    <table class="table table-sm kna-table kna-table-action">
                        <thead>
                            <tr>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="matrixTbodyAction"></tbody>
                    </table>
                </div>
            </div>
                    <div class="kna-mobile-list d-md-none" id="approvalsMobileList">
            <div class="text-center text-muted kna-small py-4">No Pending Approvals</div>
        </div>
            <div class="d-flex justify-content-end mt-2">
                <nav aria-label="Approvals desktop pagination">
                    <ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
                </nav>
            </div>
        </div>

    </div>

</div>