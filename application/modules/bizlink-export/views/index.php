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

    .kna-table th.kna-sortable {
        cursor: pointer;
        user-select: none;
    }

    .kna-table th.kna-sortable:hover {
        background: #eef3f8;
    }

    .kna-table th.kna-sortable::after {
        content: "\2195";
        margin-left: .35rem;
        opacity: .35;
    }

    .kna-table th.kna-sortable.is-sorted-asc::after {
        content: "\25B2";
        opacity: 1;
    }

    .kna-table th.kna-sortable.is-sorted-desc::after {
        content: "\25BC";
        opacity: 1;
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
        background: #f8fbff;
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

<div class="page-inner kna-page" id="bizlinkExportListPage">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <div class="kna-title">BizLink Export</div>
        </div>
    </div>

    <div class="card kna-card mb-2">
        <div class="card-body py-2 d-flex align-items-end flex-wrap" style="gap:.5rem;">
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

    <div class="card kna-card mb-2">
        <div class="card-body py-2 d-flex align-items-center flex-wrap" style="gap:.85rem;">
            <div class="kna-small font-weight-bold"><span id="paymentSelectedCount">0</span> selected</div>
            <button type="button" class="btn btn-outline-secondary btn-sm kna-small" id="btnDownloadBizlinkExport" disabled>
                <i class="fas fa-university mr-1"></i> Generate Text File
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm kna-small" id="btnOpenBizlinkBatchHistory">
                <i class="fas fa-history mr-1"></i> Batch History
            </button>
        </div>
    </div>

    <div class="card kna-card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="kna-small text-muted">Already Reviewed</div>
                <div class="kna-small text-muted" id="resultCount">0 Records</div>
            </div>
            <div class="kna-table-shell">
                <div class="kna-table-wrap-main">
                    <table class="table table-sm kna-table kna-table-main" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:28px;"><input type="checkbox" id="paymentSelectAll"></th>
                                <th class="kna-sortable" data-sort-key="referenceNo" aria-sort="none">Transaction No.</th>
                                <th class="kna-sortable" data-sort-key="transactionType" aria-sort="none">Type</th>
                                <th class="kna-sortable" data-sort-key="requestor" aria-sort="none">Requestor</th>
                                <th class="kna-sortable" data-sort-key="department" aria-sort="none">Department</th>
                                <th class="kna-sortable" data-sort-key="amount" aria-sort="none">Approved Amount</th>
                                <th class="kna-sortable" data-sort-key="submittedDate" aria-sort="none">Submission Date</th>
                                <th class="kna-sortable" data-sort-key="decisionStatus" aria-sort="none">Status</th>
                                <th class="kna-sortable" data-sort-key="turnaroundDays" aria-sort="none">Turnaround (Days)</th>
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
                <div class="text-center text-muted kna-small py-4">No Records</div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <nav aria-label="BizLink Export pagination">
                    <ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
                </nav>
            </div>
        </div>

    </div>

</div>

<div class="modal fade" id="modalBizlinkBatchHistory" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:95vw;width:95vw;">
        <div class="modal-content">
            <div class="modal-header py-2" style="background:#f8f9fa;border-bottom:1px solid #e9ecef;">
                <h5 class="modal-title kna-small" style="font-weight:600;">
                    <i class="fas fa-history text-primary"></i> BizLink Batch History
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm kna-table">
                        <thead>
                            <tr>
                                <th>Payroll Date</th>
                                <th>Batch #</th>
                                <th>Records</th>
                                <th>Total Debit</th>
                                <th>Generated By</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="bizlinkBatchHistoryTbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2" style="background:#f8f9fa;border-top:1px solid #e9ecef;">
                <button type="button" class="btn btn-outline-secondary btn-sm kna-small" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
