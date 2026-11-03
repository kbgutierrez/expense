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

  .kna-form-label {
    margin-bottom: .3rem;
    font-weight: 600;
  }

  .kna-table td, .kna-table th {
    font-size: 12px !important;
    padding: .5rem .45rem;
    vertical-align: middle;
    white-space: nowrap;
  }

  .kna-actions .btn {
    padding: .3rem .55rem;
    font-size: 12px;
  }

  .kna-badge {
    padding: .2rem .4rem;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
  }

  .kna-badge-pending {
    background: #fff5d9;
    color: #7a5b00;
  }

  .kna-badge-approved {
    background: #e8f7ee;
    color: #17663a;
  }

  .kna-badge-rejected {
    background: #fdeaea;
    color: #8a2121;
  }

  .kna-badge-liquidation {
    background: #e9f3ff;
    color: #1b4f88;
  }
  .kna-badge-completed {
    background: #e8f7ee;
    color: #17663a;
  }

  .kna-mobile-list .kna-item {
    border: 1px solid #dde3eb;
    border-radius: 6px;
    padding: .65rem;
    margin-bottom: .5rem;
    background: #fff;
  }

  .kna-mobile-list .kna-item:last-child {
    margin-bottom: 0;
  }

  .kna-mobile-list .kna-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .45rem;
    margin-bottom: .25rem;
  }

  .kna-mobile-list .kna-row:last-child {
    margin-bottom: 0;
  }

  @media (max-width: 991.98px) {
    .kna-page {
      padding: 10px;
    }

    .kna-title {
      font-size: 17px;
    }

    .kna-card .card-body {
      padding: .7rem;
    }

    .kna-stack-mobile {
      flex-direction: column;
      align-items: stretch !important;
      gap: .5rem;
    }

    .kna-mobile-cta {
      width: 100%;
    }

  }

  @media (max-width: 575.98px) {
    .kna-small {
      font-size: 11px !important;
    }
  }
</style>

<div class="page-inner kna-page">
  <div class="d-flex align-items-center justify-content-between mb-2 kna-stack-mobile">
    <div>
      <div class="kna-title">Cash Advance</div>
    </div>
    <button type="button" class="btn btn-primary btn-sm kna-small kna-mobile-cta" id="btnOpenNew"
      data-has-pending="<?=!empty($has_pending_ca) ? '1' : '0';?>"
      data-pending-id="<?=html_escape($pending_ca_id ?? '');?>"
      data-pending-status="<?=html_escape($pending_ca_status ?? '');?>">
      New Request
    </button>
  </div>

  <!-- Summary Cards -->
  <div class="row mb-2">
    <div class="col-md-3 col-6 pr-md-2 mb-2 mb-md-0">
      <div class="card kna-card h-100">
        <div class="card-body">
          <p class="kna-kpi-caption">Total Requested</p>
          <p class="kna-kpi" id="sumTotalGranted">—</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6 px-md-2 mb-2 mb-md-0">
      <div class="card kna-card h-100">
        <div class="card-body">
          <p class="kna-kpi-caption">Pending</p>
          <p class="kna-kpi" id="sumPending">—</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6 px-md-2">
      <div class="card kna-card h-100">
        <div class="card-body">
          <p class="kna-kpi-caption">For Liquidation</p>
          <p class="kna-kpi" id="sumForLiquidation">—</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-6 pl-md-2">
      <div class="card kna-card h-100">
        <div class="card-body">
          <p class="kna-kpi-caption">Approved</p>
          <p class="kpi kna-kpi" id="sumApproved">—</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="card kna-card mb-2">
    <div class="card-body py-2">
      <div class="d-flex flex-wrap align-items-end" style="gap:.5rem;">
        <div class="kna-filter-field">
          <label class="kna-small kna-form-label mb-1">Search</label>
          <input type="text" class="form-control form-control-sm kna-small" id="filterKeyword" placeholder="CA no. or purpose" autocomplete="off" style="width:180px;">
        </div>
        <div class="kna-filter-field">
          <label class="kna-small kna-form-label mb-1">Date Range</label>
          <input type="text" class="form-control form-control-sm kna-small" id="filterDateRange" placeholder="Select range" autocomplete="off" readonly style="width:180px;">
        </div>
        <div class="kna-filter-field">
          <label class="kna-small kna-form-label mb-1">Status</label>
          <select class="form-control form-control-sm kna-small" id="filterStatus" style="width:150px;">
            <option value="">All Status</option>
            <option value="Pending Approval">Pending Approval</option>
            <option value="For Liquidation">For Liquidation</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="kna-filter-field">
          <label class="kna-small kna-form-label mb-1">Amount</label>
          <select class="form-control form-control-sm kna-small" id="filterAmountRange" style="width:160px;">
            <option value="">All Amounts</option>
            <option value="0-5000">Up to PHP 5,000</option>
            <option value="5001-10000">PHP 5,001 – 10,000</option>
            <option value="10001-999999">Above PHP 10,000</option>
          </select>
        </div>
        <div class="kna-filter-field">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnReset" title="Clear filters" style="height:31px;width:31px;padding:0;">
            <i class="fas fa-undo"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Desktop Table -->
  <div class="card kna-card d-none d-md-block">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="kna-small text-muted">Request History</div>
        <div class="kna-small text-muted" id="resultCount">—</div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm kna-table" id="cashAdvanceTable" style="width:100%;">
          <thead>
            <tr>
              <th style="min-width:130px;">Cash Advance No</th>
              <th style="min-width:110px;" class="text-right">Requested Amount</th>
              <th style="min-width:110px;" class="text-right">Approved Amount</th>
              <th style="min-width:260px;">Purpose</th>
              <th style="min-width:105px;">Needed On</th>
              <th style="min-width:105px;">Requested</th>
              <th style="min-width:130px;">Status</th>
              <th style="min-width:100px;" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="cashAdvanceTbody">
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-end mt-2">
        <nav aria-label="Cash advance desktop pagination">
          <ul class="pagination pagination-sm mb-0" id="desktopPagination"></ul>
        </nav>
      </div>
    </div>
  </div>

  <!-- Mobile Cards -->
  <div class="card kna-card d-md-none">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="kna-small text-muted">Request History</div>
        <div class="kna-small text-muted" id="resultCountMobile">—</div>
      </div>
      <div class="kna-mobile-list" id="cashAdvanceMobileList"></div>
      <div class="text-center mt-2">
        <button type="button" class="btn btn-outline-primary btn-sm kna-small" id="btnLoadMoreMobile" style="display:none;">
          Load More
        </button>
      </div>
    </div>
  </div>
</div>