<style>
	.kna-page { padding: 12px 14px; }
	.kna-card { border: 1px solid #d9e0e7 !important; border-radius: 6px; background: #fff; box-shadow: 0 1px 2px rgba(20, 30, 50, .05); }
	.kna-card .card-body { padding: .85rem; }
	.kna-title { font-size: 20px; font-weight: 600; margin: 0; line-height: 1.2; }
	.kna-small { font-size: 12px !important; line-height: 1.35; }
	.kna-form-label { margin-bottom: .3rem; font-weight: 600; }

	.kna-step-head { display: flex; align-items: center; gap: 8px; margin-bottom: .75rem; }
	.kna-step-num {
		flex: 0 0 auto; width: 22px; height: 22px; border-radius: 50%; background: #5b2bd6; color: #fff;
		font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center;
	}
	.kna-step-title { font-size: 13px; font-weight: 700; color: #1f2937; }
	.kna-step-hint { font-size: 11px; color: #6b7280; margin-top: 1px; }

	.kna-event-info {
		margin-top: .6rem; padding: 10px 12px; border-radius: 6px; border-left: 4px solid #5b2bd6;
		background: #f6f3ff; font-size: 12px; line-height: 1.5;
	}
	.kna-event-info .kna-event-info-row { margin-bottom: 3px; }
	.kna-event-info .kna-event-info-row:last-child { margin-bottom: 0; }
	.kna-event-info b { color: #1f2937; }

	/* ─── Compose window (Gmail/Outlook-style) ─── */
	.kna-mail-compose {
		border: 1px solid #dadce0; border-radius: 8px; overflow: hidden;
		box-shadow: 0 1px 6px rgba(0,0,0,.08); background: #fff;
	}
	.kna-mail-compose-titlebar {
		background: #f1f3f4; padding: 8px 14px; font-size: 12px; font-weight: 700;
		color: #3c4043; border-bottom: 1px solid #dadce0; display: flex; align-items: center; gap: 6px;
	}
	.kna-mail-field {
		display: flex; align-items: center; padding: 7px 14px; border-bottom: 1px solid #f1f3f4; font-size: 12.5px;
	}
	.kna-mail-field-label { flex: 0 0 46px; color: #5f6368; font-weight: 600; }
	.kna-mail-field-value { color: #202124; }
	.kna-mail-subject-field { padding: 0; }
	.kna-mail-subject-input {
		border: none; outline: none; width: 100%; padding: 9px 14px; font-size: 13px; color: #202124;
	}
	.kna-mail-subject-input:focus { box-shadow: inset 0 -2px 0 #5b2bd6; }
	.kna-mail-body-wrap .note-editor.note-frame { border: none; border-radius: 0; }
	.kna-mail-body-wrap .note-toolbar { background: #fafbfc; border-bottom: 1px solid #f1f3f4; }

	.kna-insert-field-toggle {
		display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600;
		color: #5b2bd6; cursor: pointer; padding: 4px 0; user-select: none;
	}
	.kna-insert-field-toggle:hover { text-decoration: underline; }
	.kna-insert-field-toggle i.fa-chevron-down { transition: transform .15s ease; font-size: 10px; }
	.kna-insert-field-toggle.is-open i.fa-chevron-down { transform: rotate(180deg); }

	.kna-merge-field-list { border: 1px solid #e5ecf3; border-radius: 6px; overflow: hidden; margin: 6px 0 10px; }
	.kna-merge-field-list.d-none { display: none !important; }
	.kna-merge-field-row {
		display: flex; align-items: center; gap: 10px; padding: 6px 10px; font-size: 12px;
		border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .1s ease;
	}
	.kna-merge-field-row:last-child { border-bottom: none; }
	.kna-merge-field-row:hover { background: #f6f3ff; }
	.kna-merge-field-token {
		flex: 0 0 auto; font-family: monospace; font-size: 11px; background: #ede9fe; color: #3b1a8a;
		border: 1px solid #ddd6fe; border-radius: 4px; padding: 2px 8px; white-space: nowrap;
	}
	.kna-merge-field-desc { color: #4b5563; }
	.kna-merge-field-hint { font-size: 10px; color: #9ca3af; margin-left: auto; flex: 0 0 auto; white-space: nowrap; }

	.kna-preview-frame { width: 100%; height: 60vh; border: 1px solid #e5ecf3; border-radius: 6px; background: #fff; }
	@media (max-width: 991.98px) { .kna-page { padding: 10px; } .kna-title { font-size: 17px; } .kna-merge-field-hint { display: none; } }
</style>

<div class="page-inner kna-page">
	<div class="d-flex align-items-center justify-content-between mb-2">
		<div>
			<div class="kna-title">New Notification Template</div>
			<div class="kna-small text-muted">Compose the email that gets sent automatically for a specific approval event.</div>
		</div>
		<div class="d-flex" style="gap:.45rem;">
			<a class="btn btn-outline-secondary btn-sm kna-small" href="<?=base_url('maintenance/notification-templates');?>">Back to List</a>
			<button type="button" class="btn btn-outline-primary btn-sm kna-small" id="btnPreviewTemplate"><i class="fas fa-eye mr-1"></i> Preview</button>
			<button type="button" class="btn btn-primary btn-sm kna-small" id="btnSaveTemplate"><i class="fas fa-save mr-1"></i> Save Template</button>
		</div>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body">
			<div class="kna-step-head">
				<div class="kna-step-num">1</div>
				<div>
					<div class="kna-step-title">When should this email be sent?</div>
					<div class="kna-step-hint">Pick the event this template applies to. Each event fires automatically and notifies specific people.</div>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-4 mb-1">
					<label class="kna-form-label kna-small">Event</label>
					<select class="form-control form-control-sm kna-small" id="eventCode">
						<?php foreach ($event_codes as $code => $meta): ?>
							<option
								value="<?=html_escape($code);?>"
								data-fires-when="<?=html_escape($meta['fires_when']);?>"
								data-sent-to="<?=html_escape($meta['sent_to']);?>"
								data-accent="<?=html_escape($meta['accent']);?>"
							><?=html_escape($meta['label']);?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group col-md-4 mb-1">
					<label class="kna-form-label kna-small">Applies To <span class="kna-small text-muted">(optional)</span></label>
					<select class="form-control form-control-sm kna-small" id="transactionType">
						<option value="">All Transaction Types</option>
						<option value="CASH_ADVANCE">Cash Advance</option>
						<option value="LIQUIDATION">Liquidation</option>
						<option value="REIMBURSEMENT">Reimbursement</option>
					</select>
					<div class="kna-step-hint mt-1">Leave as "All" unless you want a different email just for one transaction type — a specific one always wins over "All" for the same event.</div>
				</div>
			</div>

			<div class="kna-event-info" id="eventInfoBox">
				<div class="kna-event-info-row"><b>Fires when:</b> <span id="eventInfoFiresWhen"></span></div>
				<div class="kna-event-info-row"><b>Sent to:</b> <span id="eventInfoSentTo"></span></div>
			</div>
		</div>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body">
			<div class="kna-step-head">
				<div class="kna-step-num">2</div>
				<div>
					<div class="kna-step-title">Write the email</div>
					<div class="kna-step-hint">Looks and works like a normal email — type it as you would in Gmail or Outlook. Use "Insert a dynamic field" for real transaction data.</div>
				</div>
			</div>

			<div class="kna-mail-compose">
				<div class="kna-mail-compose-titlebar"><i class="fas fa-envelope"></i> New Message</div>
				<div class="kna-mail-field">
					<span class="kna-mail-field-label">From</span>
					<span class="kna-mail-field-value"><?= html_escape(config_item('app_name') . ' - ' . config_item('app_long_name')); ?></span>
				</div>
				<div class="kna-mail-field">
					<span class="kna-mail-field-label">To</span>
					<span class="kna-mail-field-value" id="composeToPreview">—</span>
				</div>
				<div class="kna-mail-field kna-mail-subject-field">
					<input type="text" class="kna-mail-subject-input" id="templateSubject" placeholder="Subject — e.g. Approval Required - {{reference_no}}">
				</div>
				<div class="kna-mail-body-wrap">
					<textarea id="templateBody"></textarea>
				</div>
			</div>

			<div class="mt-2">
				<span class="kna-insert-field-toggle" id="btnToggleMergeFields">
					<i class="fas fa-plus-circle"></i> Insert a dynamic field <i class="fas fa-chevron-down"></i>
				</span>
				<div class="kna-merge-field-list d-none" id="mergeFieldPalette">
					<?php foreach ($merge_fields as $field => $description): ?>
						<div class="kna-merge-field-row" data-merge-field="<?=html_escape($field);?>">
							<span class="kna-merge-field-token">{{<?=html_escape($field);?>}}</span>
							<span class="kna-merge-field-desc"><?=html_escape($description);?></span>
							<span class="kna-merge-field-hint"><i class="fas fa-plus-circle mr-1"></i>Click to insert</span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="d-flex align-items-center justify-content-end mt-1">
				<button type="button" class="btn btn-outline-primary btn-sm kna-small" id="btnLoadDefaultTemplate">
					<i class="fas fa-magic mr-1"></i> Generate Template for Me
				</button>
			</div>
		</div>
	</div>

	<div class="card kna-card mb-2">
		<div class="card-body">
			<div class="kna-step-head mb-0">
				<div class="kna-step-num">3</div>
				<div>
					<div class="kna-step-title">Check it, then save</div>
					<div class="kna-step-hint">Use Preview to see the email with sample data filled in before saving.</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title kna-small font-weight-bold">Email Preview (sample data)</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="kna-small font-weight-bold mb-2" id="previewSubject"></div>
				<iframe class="kna-preview-frame" id="previewFrame" sandbox=""></iframe>
			</div>
		</div>
	</div>
</div>
