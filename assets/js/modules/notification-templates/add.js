const addDom = {
	eventCode: null,
	transactionType: null,
	subject: null,
	btnSave: null,
	btnPreview: null,
	btnLoadDefault: null,
};

const updateEventInfoBox = () => {
	const select = addDom.eventCode;
	const box = document.getElementById('eventInfoBox');
	const firesWhenEl = document.getElementById('eventInfoFiresWhen');
	const sentToEl = document.getElementById('eventInfoSentTo');
	const composeToEl = document.getElementById('composeToPreview');
	if (!select || !box) return;

	const option = select.options[select.selectedIndex];
	if (!option) return;

	const sentTo = option.getAttribute('data-sent-to') || '';
	if (firesWhenEl) firesWhenEl.textContent = option.getAttribute('data-fires-when') || '';
	if (sentToEl) sentToEl.textContent = sentTo;
	if (composeToEl) composeToEl.textContent = sentTo;
	box.style.borderLeftColor = option.getAttribute('data-accent') || '#5b2bd6';
};

const loadDefaultTemplate = () => {
	const eventCode = addDom.eventCode ? addDom.eventCode.value : '';
	if (!eventCode) {
		Swal.fire({ icon: 'warning', title: 'Select an Event first', text: 'Choose an Event above, then generate a template for it.' });
		return;
	}

	const currentSubject = addDom.subject ? addDom.subject.value.trim() : '';
	const currentBody = $('#templateBody').summernote('code');
	const hasContent = currentSubject !== '' || (currentBody !== '' && currentBody !== '<p><br></p>');

	const transactionType = addDom.transactionType ? addDom.transactionType.value : '';

	const apply = () => {
		ajax_loader('maintenance/notification-templates/api/get/default', { event_code: eventCode, transaction_type: transactionType })
			.done((response) => {
				const res = typeof response === 'string' ? $.parseJSON(response) : response;
				if (res.status !== 'success') {
					Swal.fire({ icon: 'error', title: 'Error', text: res.response || 'Could not generate a template.' });
					return;
				}
				if (addDom.subject) addDom.subject.value = res.data.subject || '';
				$('#templateBody').summernote('code', res.data.body_html || '');
			})
			.fail(() => {
				Swal.fire({ icon: 'error', title: 'Connection Problem', text: "We couldn't reach the server." });
			});
	};

	if (hasContent) {
		Swal.fire({
			icon: 'warning', title: 'Replace current content?', text: 'This will overwrite the Subject and Body you\'ve already typed.',
			showCancelButton: true, confirmButtonText: 'Yes, replace it', cancelButtonText: 'Cancel', reverseButtons: true,
		}).then((result) => { if (result.isConfirmed) apply(); });
	} else {
		apply();
	}
};

const initSummernote = () => {
	$('#templateBody').summernote({
		height: 320,
		toolbar: [
			['style', ['style']],
			['font', ['bold', 'italic', 'underline', 'clear']],
			['fontname', ['fontname']],
			['color', ['color']],
			['para', ['ul', 'ol', 'paragraph']],
			['table', ['table']],
			['insert', ['link', 'picture', 'hr']],
			['view', ['fullscreen', 'codeview']],
		],
	});
};

const bindMergeFieldPalette = () => {
	document.querySelectorAll('#mergeFieldPalette [data-merge-field]').forEach((chip) => {
		chip.addEventListener('click', () => {
			const token = `{{${chip.getAttribute('data-merge-field')}}}`;
			$('#templateBody').summernote('editor.insertText', token);
		});
	});

	const toggle = document.getElementById('btnToggleMergeFields');
	const panel = document.getElementById('mergeFieldPalette');
	if (toggle && panel) {
		toggle.addEventListener('click', () => {
			const isOpen = !panel.classList.contains('d-none');
			panel.classList.toggle('d-none', isOpen);
			toggle.classList.toggle('is-open', !isOpen);
		});
	}
};

const getFormPayload = () => ({
	event_code: addDom.eventCode ? addDom.eventCode.value : '',
	transaction_type: addDom.transactionType ? addDom.transactionType.value : '',
	subject: addDom.subject ? addDom.subject.value.trim() : '',
	body_html: $('#templateBody').summernote('code'),
});

const previewTemplate = () => {
	const payload = getFormPayload();
	if (!payload.subject || !payload.body_html) {
		Swal.fire({ icon: 'warning', title: 'Nothing to preview', text: 'Fill in Subject and Body first.' });
		return;
	}

	ajax_loader('maintenance/notification-templates/api/preview', payload)
		.done((response) => {
			const res = typeof response === 'string' ? $.parseJSON(response) : response;
			if (res.status !== 'success') {
				Swal.fire({ icon: 'error', title: 'Preview failed', text: res.response || 'Unknown error.' });
				return;
			}
			const subjectEl = document.getElementById('previewSubject');
			const frame = document.getElementById('previewFrame');
			if (subjectEl) subjectEl.textContent = res.data.subject || '';
			if (frame) frame.srcdoc = res.data.body_html || '';
			$('#previewModal').modal('show');
		})
		.fail(() => {
			Swal.fire({ icon: 'error', title: 'Connection Problem', text: "We couldn't reach the server." });
		});
};

const saveTemplate = () => {
	const payload = getFormPayload();

	if (!payload.event_code) {
		Swal.fire({ icon: 'warning', title: 'Validation', text: 'Event is required.' });
		return;
	}
	if (!payload.subject) {
		Swal.fire({ icon: 'warning', title: 'Validation', text: 'Subject is required.' });
		return;
	}
	if (!payload.body_html || payload.body_html === '<p><br></p>') {
		Swal.fire({ icon: 'warning', title: 'Validation', text: 'Body is required.' });
		return;
	}

	Swal.fire({
		icon: 'question', title: 'Confirm Save', text: 'Save this notification template?',
		showCancelButton: true, confirmButtonText: 'Yes', cancelButtonText: 'No', reverseButtons: true,
	}).then((result) => {
		if (!result.isConfirmed) return;

		ajax_loader('maintenance/notification-templates/api/save', payload)
			.done((response) => {
				const res = typeof response === 'string' ? $.parseJSON(response) : response;
				if (res.status === 'success') {
					Swal.fire({ icon: 'success', title: 'Saved', text: 'Notification template created successfully.' })
						.then(() => { window.location.href = `${base_url}maintenance/notification-templates`; });
				} else {
					Swal.fire({ icon: 'warning', title: 'Unable to Save', text: res.response || 'Please review and try again.' });
				}
			})
			.fail(() => {
				Swal.fire({ icon: 'error', title: 'Connection Problem', text: "We couldn't reach the server." });
			});
	});
};

$(document).ready(() => {
	addDom.eventCode = document.getElementById('eventCode');
	addDom.transactionType = document.getElementById('transactionType');
	addDom.subject = document.getElementById('templateSubject');
	addDom.btnSave = document.getElementById('btnSaveTemplate');
	addDom.btnPreview = document.getElementById('btnPreviewTemplate');
	addDom.btnLoadDefault = document.getElementById('btnLoadDefaultTemplate');

	initSummernote();
	bindMergeFieldPalette();
	updateEventInfoBox();

	if (addDom.eventCode) addDom.eventCode.addEventListener('change', updateEventInfoBox);
	if (addDom.btnSave) addDom.btnSave.addEventListener('click', saveTemplate);
	if (addDom.btnPreview) addDom.btnPreview.addEventListener('click', previewTemplate);
	if (addDom.btnLoadDefault) addDom.btnLoadDefault.addEventListener('click', loadDefaultTemplate);
});
