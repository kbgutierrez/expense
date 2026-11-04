# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

An internal finance workflow app, currently branded **ExReLiq** (Expense Reimbursement & Liquidation Portal; formerly ERLP, before that K-Net). It handles cash advance, liquidation, reimbursement, replenishment, revolving fund, approvals, payment advisory/release and BPI BizLink export. It is built on CodeIgniter 3 with HMVC (`application/third_party/MX`), runs on PHP 7.2 **32-bit** and uses SQL Server through the `sqlsrv` driver. `base_url` is built from the folder name in `application/config/config.php`.

### Environments

The same code runs from two folders. Each folder has its own `.env`, which holds everything that differs per environment (see `.env.example`):

| Folder / URL | Role | DB (`DB_HOST`, `DB_NAME`) | Portal (`PORTAL_URL`, `PORTAL_ID`) | K-Flow (`KFLOW_BASE_URL`) |
|---|---|---|---|---|
| `Z:\htdocs\expense-test` → `/expense-test/` | test | `192.168.1.229`, `BigEExpense` | `testportal`, portal id 47 (route `expense-test`) | `k-flow-test` |
| `Z:\htdocs\expense` → `/expense/` | prod (set up 2026-10-26, not live yet) | `192.168.0.250`, `BigEExpense` | `portal`, portal id 58 (route `expense`) | live `k-flow` (integration not built there yet) |

Both folders are one git repo (GitHub `kbgutierrez/expense`): `expense` is the `main` branch (prod) and `expense-test` is a git worktree on the `dev` branch (shared `.git` in `expense/.git`). Do day-to-day work, commits and testing in `expense-test` (`dev`); nothing is copied between folders. Code reaches prod only by merging `dev` into `main` in `expense`, and only when the owner asks. Run git with `git -c safe.directory=*` if the network-share ownership error appears; `gh` in this shell has read-only access, so the owner pushes (`git push origin main`, `git push origin dev`). Do not add Co-Authored-By lines to commits. `notes/`, `.claude/` and `.env` stay out of git (`notes/` is gitignored; `.env` is per folder, never copy it between the two). `config_item('portal_url')` and `config_item('portal_id')` replace the old hard-coded testportal URL and portal id 47. The portal session cookie is shared across `lsbizportal.lemonsquare.com.ph`, so a testportal login also opens `/expense/`; prod access is controlled by the role and module rows of portal 58 in the prod `BigEUsers`.

### Branding vs. technical names

The technical identity is generic and must not change on a rebrand: folder and URL `expense` (`expense-test` on test), portal route `expense` (`expense-test` on test), database `BigEExpense`, K-Flow callback key `expense_portal_callback_url` (in k-flow-test `application/config/config.php`, pointing to `/expense-test/`). The brand lives in one place:

- `application/config/branding.php` (autoloaded): `app_name`, `app_long_name`, `app_logo_header`, `app_logo_email`. Read it with `config_item('app_name')`; never hard-code the brand in PHP, JS or views.
- Logos have fixed file names: `assets/img/brand/logo-header.png` and `assets/img/brand/logo-email.png` (white on transparent; the email band is navy `#16153d`). A rebrand swaps the files.
- Email templates use `{{app_name}}` and `{{app_long_name}}`; `notify_event()` fills them from the config. The SMTP `from_name` is empty so it falls back to the brand.

A rebrand is therefore: edit `branding.php`, replace the two logo files, and change the `name` of the portal row (47 on test, 58 on prod). Older internal names stay as they are: `KNET_` batch ids, `Source='KNET'`, `knet_*` columns in `BigEKFlow`, `Knet_finalizer`, the `dbknet` DB group, `Knet_views` / `Knet_functions`, knet CSS/JS file names and the `erlp-*` CSS classes. Do not rename them. `Z:\htdocs\erlp` and `Z:\htdocs\k-net` only 301-redirect to `/expense/` and are kept as backups.

Because PHP is 32-bit, `(int)` saturates at 2,147,483,647. Use float plus `sprintf` for hashes, bank account numbers and large ids.

There is no build step, linter or unit test suite. To verify a change, load the page in the browser, query the DB or run the parity tool (see below).

## Project notes (Obsidian vault)

Project knowledge lives in `notes/` (an Obsidian vault, in `expense-test`). **Read the relevant note before scanning code**; it is cheaper than re-reading the codebase. Start from `notes/SIMULAN DITO.md` if unsure.

| Need | Read |
|---|---|
| What the app is, roles, stack, environments | `notes/Project Overview.md` |
| Folder map, request flow, data access, approval domain, UI | `notes/Architecture.md` |
| DB overview, cross-DB gotchas, status codes | `notes/Database Schema.md` |
| A table's columns, keys and which models/SPs use it | `notes/Database/Tables/<table>.md` |
| An SP's params (positional order), tables, R/W, callers | grep `notes/Database/Stored Procedures.md` |
| A plain model's methods, tables and callers | grep `notes/Database/Models.md` |
| URL prefixes and routing | `notes/Routes.md` |
| A module: files, endpoints, models, SP, tables, gotchas | `notes/Modules/<Title Case>.md` (frontmatter `module:` = folder, e.g. `ca_reports` → `CA Reports.md`) |
| Things NOT to change | `notes/Decisions/*.md`: always respect these |
| A task to implement | `notes/Tasks/<Task>.md` |

- The blocks between `%% gen:start %%` and `%% gen:end %%` are generated by `sh tools/php32.sh tools/gen_notes.php` (reads the test DB and the code, spends no tokens). Never edit inside them; rerun the script after changing endpoints, models, SPs or tables. Everything outside the markers is hand-written.
- Follow the templates in `notes/Templates/` (Taglish is fine). Link with wikilinks (`[[Cash Advance]]`), not markdown links. Keep frontmatter (`tags`, `created`, `status`, `module: "[[Module]]"`); bump `updated:` when editing a module note.
- Status values: tasks `todo` / `in-progress` / `done`; bugs `open` / `fixed`. Daily logs: `notes/Daily/YYYY-MM-DD.md`; bug write-ups: `notes/Debug-Logs/YYYY-MM-DD <title>.md`.
- If a note is stale or contradicts the code, trust the code and fix the note.
- Don't read `system/`, `vendor/`, `node_modules/`, `application/logs/`, `application/cache/`, `uploads/`, `.env`. Slash commands: `/init-notes`, `/task`, `/bug`, `/bug-hard`, `/log`, `/sync-notes` (`.claude/commands/`).

## Data access: plain CI models first, stored procedures only when needed

The database is `BigEExpense` (renamed from `BigEERLP` on test on 2026-10-26). `application/config/database.php` reads the host, name and credentials from `.env`, and the `default` and `dbknet` groups both point to it. The prod copy was built from test by `tmp/gen_create_bigeexpense_prod.php` (schema plus the setup tables `tbl_status`, `tbl_expense_category`, `tbl_notification_templates`, `tbl_cost_center` and `tbl_bizlink_company_settings`). `sp_cancel_cash_advance` is not on prod yet because prod `BigEKFlow` has no `knet_batch_id`. Prod `BigEHRIS.dbo.department.category` was added for this app (`tmp/alter_bigehris_department_add_category.sql`). Department ids differ between test and prod, so match departments by `department_acro`, not by id. The procedures and models join across sibling databases: `BigEUsers.dbo.users`, `BigEHRIS.dbo.department` and `BigEMasterData.dbo.tbl_SOffc` / `tbl_SDst`.

### Plain CI models (the default)

Simple reads, lookups, lists and single-table CRUD live in plain CodeIgniter models, `application/models/*_model.php`, which extend `application/core/MY_Model.php`. Do not add a registry or adapter layer. Load the model and call it directly:

```php
$this->load->model('Cash_advance_model');
$rows = $this->Cash_advance_model->list_by_user($userId, $cursorId, $take);
```

- `MY_Model` helpers: `rows()` / `row()` (after query-builder calls), `query_rows($sql, $binds)` / `query_row()`, `write($callable)`, `insert_id()`, `transaction($callable)` and `fail($message, $code)`. DB errors are thrown as `Exception` with the SQL Server prefix stripped (`MY_Model::clean_message`). Controllers catch it and pass `getMessage()` to `respondError()`.
- SQL that used to live in views is in `application/libraries/Knet_views.php` (`Knet_views::sub('vw_x', 'V')` returns it as a derived table). Ports of scalar SQL functions are in `Knet_functions.php`.
- Validation the procedure did with `THROW` is done in the model with `fail()`, keeping the same message.

### Stored procedures (only where they belong)

Keep these as SQL Server procedures, called through `SPModel`: reference-id generation (`CA`/`LQ`/`RMB`/`RPL`/`RF` codes; never generate ids in PHP), multi-step atomic writes (approval decisions, payment advise/release, inserts that create approval rows, K-Flow reject/cancel), cross-database writes and heavy reports, dashboards and search.

```php
$this->load->model('SPModel', 'sp');
$this->sp->setDatabase('dbknet');
$params = array('UserId' => $userId, 'CursorId' => $cursorId, 'Take' => $take);
$rows = $this->sp->readData(build_sp('sp_fetch_x', count($params)), $params, 'result');
```

- `build_sp()` (`application/helpers/sp_helper.php`) adds `?` placeholders. Parameters bind **by position**, so the array order must match the procedure's parameter order. The keys are only labels.
- `build_sp($name, 0)` still emits one `?`. For a procedure with no parameters, pass the raw string `'EXEC sp_name'` instead.
- `SPModel` also has `createData` and `createReturnId`. It turns SQL errors raised with `THROW` into exceptions or error results.

Procedure source is **not in the repo**. To read one, query the DB:

```
sqlcmd -S <host> -U <user> -P <pass> -d BigEExpense -y 0 -Q "SET NOCOUNT ON; SELECT OBJECT_DEFINITION(OBJECT_ID('sp_name'))"
```

`-W` and `-y` can't be used together. SQL Server is 2014, so `STRING_AGG`, `TRIM`, `STRING_SPLIT` and `CREATE OR ALTER` are not available.

Save any DDL you write (ALTER PROCEDURE/VIEW, CREATE TABLE) as a script in `tmp/`, named like `alter_<object>_<change>.sql`, so the change can be reviewed and applied to other environments.

### Parity tool

`tools/parity.php` runs each converted model method and the original procedure with the same arguments and compares the rows. Cases are in `tools/parity_cases/<domain>.php`, each an entry like `array('sp' => 'sp_fetch_x', 'params' => array(...), 'model' => 'X_model', 'method' => 'get_x', 'returns' => 'row')` (`returns` defaults to rows; `row`, `scalar` and `none` are also allowed). Run it with the 32-bit PHP and its extensions:

```
sh tools/php32.sh tools/parity.php            # all domains
sh tools/php32.sh tools/parity.php @lookup    # one case file
```

Add a case whenever you convert or change a model method that replaced a procedure. It only works while the original procedure still exists in the DB. The tool uses the DB of the folder it runs from, and some cases write, so run it only from `Z:\htdocs\expense-test`, never from the prod folder.

## Request / module structure

- Each feature is a module in `application/modules/<module>/` with `controllers/`, `views/` and `config/routes.php`. Controllers extend `MY_Controller` (`application/core/MY_Controller.php`).
- **Routing:** URLs are prefixed, for example `maintenance/approval-matrix/...`. `MY_Router::locate()` resolves `<prefix>/<module>/...` using the target module's own `config/routes.php`. Add new endpoints there, not in `application/config/routes.php`.
- **Page render:** every page loads the single layout `application/views/main.php` and passes `main_view => '../modules/<module>/views/<view>'`, `module_group`, `module` (the sidebar data, which `MY_Controller` loads through `Portal_model`) and `scripts => ['index.js']`.
- **Per-page JS:** `main.php` loads each entry in `scripts` from `assets/js/modules/<router module name>/<script>`, with a `?v=` cache-buster based on file time. Shared JS helpers are in `assets/js/helpers/`: `ajax_loader` (jQuery POST to `base_url + url`), swal, select2, datatable, export and transaction viewer.
- **API endpoints** are controller methods named `api_*`. They return JSON through `respondSuccess($msg, $data)` / `respondError($msg)` using the shape `{status, response, data}`. Some list endpoints return `{status, data, pagination}` instead.
- **Cursor pagination:** use `resolvePaginationTake()` together with `buildPaginationResult()`. The query receives the cursor id and take and must return `Take + 1` rows, ordered by `id DESC`, so the helper can work out `hasMore`.
- **Audit trail:** `logAuditTrail()` writes through `Audit_trail_model`.
- **Auth:** there is no login page in this app. The session (`user_id`, `user_info` with `department_id`, `company` and so on) is set by the external portal (`PORTAL_URL`: `testportal` on test, `portal` on prod). The tile is hard-coded per route in the portal's `application/views/dashboard_index.php`: testportal handles `expense` and `expense-test`, and the live portal has no tile yet. `MainController` and `main.php` redirect there when there is no session.
- **K-Flow:** document signing runs in K-Flow (`Z:\htdocs\k-flow-test` on test). It calls back to `/expense-test/transactions/cash-advance/api/kflow_callback`, read from `expense_portal_callback_url` in K-Flow's config. The live `k-flow` has none of this integration, and it must not be changed without the owner's explicit go-ahead because many people use it.
- Autoloaded helpers: `sp_helper`, `misc_helper`, `tableau_helper`, `notification_helper`, `groq_ocr_helper` (receipt OCR) and `encryption_helper` (used for bank account numbers). PDFs are generated with mPDF, TCPDF, FPDF/FPDI or wkhtmltopdf via knp-snappy (see `application/helpers/*_pdf_helper.php`), and Excel files with PhpSpreadsheet or Spout.

## UI and mobile styles

- Styles are in `assets/css/main.css`. Use the `--brand-*` palette tokens defined there instead of hard-coded colors.
- The mobile (native-app style) screens use the `lqm-*` classes in `main.css`, inside `@media (max-width: 768px)`: cards (`lqm-ca`, `lqm-list`, `lqm-exp`), form fields (`lqm-field`, `lqm-input`, `lqm-label`), bottom sheets (`lqm-sheet-wrap`, `lqm-sheet`, `lqm-sheet-backdrop`, with `lqm-sheet-open` on `<html>`) and a sticky action bar (`lqm-sticky-total`, `lqm-actions`, with `lqm-has-sticky` on `<html>`). They are used by Liquidation New/Edit/Details (`modules/liquidation/views/add.php`, `edit.php`, `detail.php` and `assets/js/modules/liquidation/add.js` / `detail.js`). Reuse them when redesigning other modules for mobile, and keep the desktop layout working.

## Approval domain model

- `tbl_approval_matrix_header` defines a rule by `transaction_type` (`CASH_ADVANCE`, `LIQUIDATION`, `REIMBURSEMENT`, `REPLENISHMENT`), `department_id`, optional `sales_office_code` / `sales_district_code`, a `min_amount`–`max_amount` bracket and `is_active`. `tbl_approval_matrix_details` (FK `matrix_header_id`) lists the approvers in order, with approval type and flags: `is_payment_advisory`, `is_payment_release`, `is_petty_cash_slip`, `is_bizlink_export`.
- When a transaction is submitted (for example `sp_insert_ca_v2`), the procedure picks the active matrix whose bracket contains the amount for that type and department. It then creates `tbl_approval_header` (`reference_id`, `approval_matrix_id`) and `tbl_approval_details`. If no bracket matches, it throws `No approval matrix found.`
- Transaction reference prefixes are `CA`, `LQ`, `RMB` and `RPL`. Status codes follow `<PREFIX>_APPROVED`, `<PREFIX>_FOR_RELEASE` and so on. Payment and BizLink capabilities are read from the matrix details saved on the transaction's approval header, not from the rules that are active now.
- `vw_approvals_header` (also `Knet_views::vw_approvals_header()`) drives the approvals lists and turnaround calculations.

## Conventions

- **Do not write code comments**: no docblocks and no inline explanations in PHP or JS. This is the owner's standing preference.
- Match the existing style: `array()` syntax in PHP, `try/catch` around each API method, and snake_case names for procedures and tables (`sp_fetch_*`, `sp_insert_*`, `sp_update_*`, `tbl_*`, `vw_*`). Models are named `<Domain>_model` with snake_case methods.
- Work on the test environment (`expense-test`, `k-flow-test`, DB `192.168.1.229`) by default. Do not touch the prod folder `expense`, the live `k-flow` folder or the prod DB `192.168.0.250` unless explicitly asked.
- `.env` is loaded through phpdotenv in `application/config/constants.php` and holds the DB credentials and per-environment values. It is git-ignored. Never commit credentials.
