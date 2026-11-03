(function (window) {
	const defaultMaxBytes = 2 * 1024 * 1024;

	const normalizeDate = (value) => (value ? String(value) : '');

	const escapeHtml = (value = '') =>
		String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');

	const formatPHP = (amount) => {
		const value = Number(amount || 0);
		return value.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' });
	};

	const makeFileKey = (file) => `${file.name}|${file.size}|${file.lastModified}`;

	const toTokenId = (text) => {
		let hash = 0;
		for (let i = 0; i < text.length; i += 1) {
			hash = ((hash << 5) - hash) + text.charCodeAt(i);
			hash |= 0;
		}
		return Math.abs(hash) % 50000;
	};

	const readFileAsDataUrl = (file) => new Promise((resolve, reject) => {
		const reader = new FileReader();
		reader.onload = () => resolve(reader.result);
		reader.onerror = () => reject(new Error('Failed to read file.'));
		reader.readAsDataURL(file);
	});

	const loadImageElement = (src) => new Promise((resolve, reject) => {
		const img = new Image();
		img.onload = () => resolve(img);
		img.onerror = () => reject(new Error('Failed to load image.'));
		img.src = src;
	});

	const canvasToBlob = (canvas, mimeType, quality) =>
		new Promise((resolve) => canvas.toBlob((blob) => resolve(blob), mimeType, quality));

	const normalizeCategoryName = (value) =>
		normalizeDate(value)
			.toLowerCase()
			.replace(/[^a-z0-9 ]+/g, ' ')
			.replace(/\s+/g, ' ')
			.trim();

	const getExpenseTypeCode = (opt) => normalizeDate(opt.expense_code || opt.expenseCode || '');

	const mapOcrCategoryToExpenseTypeId = (categoryName, expenseTypeOptions) => {
		const incoming = normalizeCategoryName(categoryName);
		if (!incoming) {
			return '';
		}

		const exact = expenseTypeOptions.find((opt) => normalizeCategoryName(opt.categoryName) === incoming);
		if (exact) {
			return getExpenseTypeCode(exact);
		}

		const partial = expenseTypeOptions.find((opt) => {
			const local = normalizeCategoryName(opt.categoryName);
			return local !== '' && (local.includes(incoming) || incoming.includes(local));
		});

		return partial ? getExpenseTypeCode(partial) : '';
	};

	const normalizeOcrDateToYmd = (value) => {
		const raw = normalizeDate(value);
		if (!raw) {
			return '';
		}

		if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
			return raw;
		}

		const parsed = new Date(raw);
		if (!Number.isNaN(parsed.getTime())) {
			return parsed.toISOString().slice(0, 10);
		}

		const mdY = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
		if (mdY) {
			const month = Number(mdY[1]);
			const day = Number(mdY[2]);
			let year = Number(mdY[3]);
			if (year < 100) {
				year += 2000;
			}
			if (month >= 1 && month <= 12 && day >= 1 && day <= 31) {
				const yyyy = String(year);
				const mm = String(month).padStart(2, '0');
				const dd = String(day).padStart(2, '0');
				return `${yyyy}-${mm}-${dd}`;
			}
		}

		return '';
	};

	const create = (options = {}) => {
		const state = {
			ocrByItem: {},
		};

		const swal = options.swal || window.Swal;
		const renderItems = typeof options.renderItems === 'function' ? options.renderItems : () => {};
		const getExpenseItem = typeof options.getExpenseItem === 'function' ? options.getExpenseItem : () => null;
		const getExpenseTypeOptions = typeof options.getExpenseTypeOptions === 'function' ? options.getExpenseTypeOptions : () => [];
		const maxAttachmentBytes = Number(options.maxAttachmentBytes || defaultMaxBytes);
		const ocrEndpoint = options.ocrEndpoint || 'transactions/liquidation/api/ocr';
		const ocrTimeoutMs = Number(options.ocrTimeoutMs || 8000);
		const baseUrl = options.baseUrl || window.base_url || '';
		const normalizeDateFn = typeof options.normalizeDate === 'function' ? options.normalizeDate : normalizeDate;
		const escapeHtmlFn = typeof options.escapeHtml === 'function' ? options.escapeHtml : escapeHtml;

		/* ─── OCR State Management ─── */
		const getItemOcrState = (itemId) => state.ocrByItem[itemId] || { status: 'idle' };

		const setOcrState = (itemId, patch) => {
			const existing = state.ocrByItem[itemId] || {};
			state.ocrByItem[itemId] = { ...existing, ...patch };
			renderItems();
		};

		const cancelOcr = (itemId) => {
			const st = state.ocrByItem[itemId];
			if (st && st.jqXHR) {
				st.jqXHR.abort();
			}
			if (st && st.timeoutId) {
				clearTimeout(st.timeoutId);
			}
			delete state.ocrByItem[itemId];
			renderItems();
		};

		const markManual = (itemId) => {
			cancelOcr(itemId);
			state.ocrByItem[itemId] = { status: 'manual' };
			renderItems();
		};

		const isItemOcrLoading = (itemId) => {
			const s = state.ocrByItem[itemId];
			return s && s.status === 'scanning';
		};

		/* ─── Attachments ─── */
		const attachmentsLabel = (attachments, itemId) => {
			const ocr = state.ocrByItem[itemId];
			const isOcrLoading = ocr && ocr.status === 'scanning';

			if (!attachments.length) {
				return isOcrLoading
					? '<span class="text-muted">Scanning receipt…</span>'
					: '<span class="text-muted">No file</span>';
			}
			const compressedIcon = attachments.some((file) => Boolean(file && file._wasCompressed))
				? ' <i class="fas fa-compress-alt" title="Compressed"></i>'
				: '';
			const ocrLoadingIcon = isOcrLoading ? ' <i class="fas fa-spinner fa-spin" title="OCR in progress"></i>' : '';
			if (attachments.length === 1) {
				return `${escapeHtmlFn(attachments[0].name)}${compressedIcon}${ocrLoadingIcon}`;
			}
			return `${attachments.length} files attached${compressedIcon}${ocrLoadingIcon}`;
		};

		const compressImageToLimit = async (file) => {
			if (!file || !file.type || !file.type.startsWith('image/')) {
				return null;
			}

			if (file.size <= maxAttachmentBytes) {
				return file;
			}

			const dataUrl = await readFileAsDataUrl(file);
			const image = await loadImageElement(dataUrl);
			const canvas = document.createElement('canvas');
			const ctx = canvas.getContext('2d');

			if (!ctx) {
				return null;
			}

			let width = image.naturalWidth || image.width;
			let height = image.naturalHeight || image.height;
			let quality = 0.9;
			let scale = 1;
			let bestBlob = null;

			for (let attempt = 0; attempt < 8; attempt += 1) {
				canvas.width = Math.max(1, Math.floor(width * scale));
				canvas.height = Math.max(1, Math.floor(height * scale));
				ctx.clearRect(0, 0, canvas.width, canvas.height);
				ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

				const blob = await canvasToBlob(canvas, 'image/jpeg', quality);
				if (!blob) {
					break;
				}

				bestBlob = blob;
				if (blob.size <= maxAttachmentBytes) {
					break;
				}

				quality = Math.max(0.4, quality - 0.12);
				scale = Math.max(0.45, scale - 0.08);
			}

			if (!bestBlob || bestBlob.size > maxAttachmentBytes) {
				return null;
			}

			const baseName = (file.name || 'attachment').replace(/\.[^.]+$/, '');
			return new File([bestBlob], `${baseName}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
		};

		const PDF_RENDER_SCALE = 2;

		const ensurePdfJsWorker = () => {
			if (!window.pdfjsLib) {
				throw new Error('PDF renderer failed to load.');
			}
			if (!window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
				window.pdfjsLib.GlobalWorkerOptions.workerSrc = `${baseUrl}assets/js/modules/shared/pdfjs/pdf.worker.min.js`;
			}
			return window.pdfjsLib;
		};

		const renderPdfFirstPageToJpegFile = async (file) => {
			const pdfjsLib = ensurePdfJsWorker();
			const arrayBuffer = await file.arrayBuffer();
			const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
			const page = await pdf.getPage(1);
			const viewport = page.getViewport({ scale: PDF_RENDER_SCALE });
			const canvas = document.createElement('canvas');
			canvas.width = viewport.width;
			canvas.height = viewport.height;
			const ctx = canvas.getContext('2d');
			if (!ctx) {
				throw new Error('Canvas unavailable for PDF rendering.');
			}
			await page.render({ canvasContext: ctx, viewport }).promise;
			const blob = await canvasToBlob(canvas, 'image/jpeg', 0.92);
			if (!blob) {
				throw new Error('Failed to convert rendered PDF page to JPEG.');
			}
			const baseName = (file.name || 'receipt').replace(/\.[^.]+$/, '');
			return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
		};

		const normalizeIncomingAttachments = async (files) => {
			const acceptedFiles = [];
			const rejectedFiles = [];

			for (const file of files) {
				const isImage = file && file.type && file.type.startsWith('image/');
				const isPdf = file && file.type === 'application/pdf';

				if (!file || (!isImage && !isPdf)) {
					rejectedFiles.push(`${file ? file.name : 'Unknown file'} (only images or PDF allowed)`);
					continue;
				}

				if (file.size <= maxAttachmentBytes) {
					file._wasCompressed = false;
					acceptedFiles.push(file);
					continue;
				}

				if (isPdf) {
					rejectedFiles.push(`${file.name} (PDF exceeds ${(maxAttachmentBytes / (1024 * 1024)).toFixed(1)}MB limit)`);
					continue;
				}

				const compressed = await compressImageToLimit(file);
				if (compressed) {
					compressed._wasCompressed = true;
					acceptedFiles.push(compressed);
				} else {
					rejectedFiles.push(`${file.name} (cannot compress to 2MB)`);
				}
			}

			return { acceptedFiles, rejectedFiles };
		};

		const addItemAttachments = async (itemId, incomingFiles) => {
			const item = getExpenseItem(itemId);
			if (!item) {
				return [];
			}

			const normalized = await normalizeIncomingAttachments(incomingFiles);
			if (normalized.rejectedFiles.length) {
				swal.fire({
					icon: 'warning',
					title: 'Some files were skipped',
					text: normalized.rejectedFiles.join(', '),
				});
			}

			if (!normalized.acceptedFiles.length) {
				return [];
			}

			const existing = new Set((item.attachments || []).map((file) => makeFileKey(file)));
			normalized.acceptedFiles.forEach((file) => {
				const key = makeFileKey(file);
				if (!existing.has(key)) {
					existing.add(key);
					item.attachments.push(file);
				}
			});

			return normalized.acceptedFiles;
		};

		const ensureCameraPermission = async () => {
			if (!window.isSecureContext) {
				swal.fire({
					icon: 'warning',
					title: 'Camera unavailable',
					text: 'Camera access requires HTTPS (or localhost). Please use a secure connection.',
				});
				return false;
			}

			if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
				swal.fire({
					icon: 'warning',
					title: 'Camera not supported',
					text: 'This browser does not support direct camera access. Try Gallery upload instead.',
				});
				return false;
			}

			try {
				const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
				stream.getTracks().forEach((track) => track.stop());
				return true;
			} catch (error) {
				let message = 'Please allow camera permission in browser settings and try again.';
				if (error && error.name === 'NotFoundError') {
					message = 'No camera was found on this device.';
				} else if (error && error.name === 'NotReadableError') {
					message = 'Camera is currently being used by another app.';
				}

				swal.fire({
					icon: 'warning',
					title: 'Camera permission needed',
					text: message,
				});
				return false;
			}
		};

		const promptAttachmentSource = (itemId, callbacks = {}) => {
			swal.fire({
				title: 'Choose attachment source',
				text: 'Select where to get the receipt image/file.',
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: 'Gallery',
				confirmButtonColor: '#5b2bd6',
				showDenyButton: true,
				denyButtonText: 'Camera',
				denyButtonColor: '#8b5cf6',
				cancelButtonText: 'Cancel',
			}).then((result) => {
				if (result.isConfirmed && typeof callbacks.onGallery === 'function') {
					callbacks.onGallery(itemId);
					return;
				}

				if (result.isDenied && typeof callbacks.onCamera === 'function') {
					callbacks.onCamera(itemId);
				}
			});
		};

		/* ─── Non-blocking OCR with timeout & manual override ─── */
		const runOcrAutofillForItem = async (itemId, file) => {
			const isImage = file && file.type && file.type.startsWith('image/');
			const isPdf = file && file.type === 'application/pdf';
			if (!file || (!isImage && !isPdf)) {
				return;
			}

			const item = getExpenseItem(itemId);
			if (!item) {
				return;
			}

			// Cancel any in-flight OCR for this item
			cancelOcr(itemId);

			setOcrState(itemId, { status: 'scanning', result: null, error: null, startedAt: Date.now() });

			let ocrFile = file;
			if (isPdf) {
				try {
					ocrFile = await renderPdfFirstPageToJpegFile(file);
				} catch (err) {
					const current = state.ocrByItem[itemId];
					if (current && current.status === 'scanning') {
						setOcrState(itemId, { status: 'error', error: 'Could not read PDF for OCR - enter details manually' });
					}
					return;
				}
			}

			const formData = new FormData();
			formData.append('image', ocrFile);

			// Hard timeout via setTimeout in case jQuery timeout is unreliable
			const timeoutId = setTimeout(() => {
				const st = state.ocrByItem[itemId];
				if (st && st.jqXHR) {
					st.jqXHR.abort();
				}
			}, ocrTimeoutMs);

			const jqXHR = $.ajax({
				url: baseUrl + ocrEndpoint,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json',
				timeout: ocrTimeoutMs + 2000,
			});

			setOcrState(itemId, { jqXHR, timeoutId });

			try {
				const response = await jqXHR;
				clearTimeout(timeoutId);

				// If user cancelled or switched to manual while we were away, respect that
				const current = state.ocrByItem[itemId];
				if (!current || current.status !== 'scanning') {
					return;
				}

				if (!response || response.status !== 'success' || !response.data) {
					throw new Error(response?.response || 'OCR returned no usable data');
				}

				const ocr = response.data;

				const tokenSource = [
					normalizeDateFn(ocr.document_date),
					normalizeDateFn(ocr.invoice_receipt_no),
					normalizeDateFn(ocr.actual_amount),
					normalizeDateFn(ocr.description),
					normalizeDateFn(ocr.expense_category_name),
					(typeof ocr.is_vatable === 'boolean' ? String(ocr.is_vatable) : ''),
				].filter(Boolean).join(' ');

				const normalizedTokenSource = String(tokenSource || '').replace(/\s+/g, ' ').trim();
				const tokenArray = normalizedTokenSource
					? ((normalizedTokenSource.match(/[\p{L}\p{N}]+(?:[./:-][\p{L}\p{N}]+)*/gu) || []).filter((part) => part !== ''))
					: [];
				const tokenDetails = tokenArray.map((text) => ({
					text,
					token_id: toTokenId(text),
				}));

				console.log('Tokens');
				console.log(tokenArray.length);
				console.log('Total Token Used');
				console.log(tokenArray.length);
				console.log('Why');
				console.log('Total token used is the number of text chunks found after splitting OCR text by spaces and separators. Each row in Token Details is counted as 1 token.');
				console.log('Characters');
				console.log(normalizedTokenSource.length);
				console.log('Token Details');
				console.table(tokenDetails);

				const documentDate = normalizeOcrDateToYmd(ocr.document_date);
				const categoryId = mapOcrCategoryToExpenseTypeId(ocr.expense_category_name, getExpenseTypeOptions());

				const appliedFields = [];

				if (documentDate) {
					item.documentDate = documentDate;
					appliedFields.push('date');
				}
				if (categoryId) {
					item.expenseType = categoryId;
					appliedFields.push('category');
				}
				if (ocr.invoice_receipt_no) {
					item.reference = normalizeDateFn(ocr.invoice_receipt_no);
					appliedFields.push('ref');
				}
				if (Number(ocr.actual_amount) > 0) {
					item.amount = Number(ocr.actual_amount).toFixed(2);
					appliedFields.push('amount');
				}
				if (typeof ocr.is_vatable === 'boolean') {
					item.isVattable = ocr.is_vatable;
					appliedFields.push('vat');
				}
				if (ocr.description) {
					item.remarks = normalizeDateFn(ocr.description);
					appliedFields.push('desc');
				}

				if (ocr.vendor_name) {
					item.vendorName = normalizeDateFn(ocr.vendor_name);
					appliedFields.push('vendor');
				}
				if (ocr.vendor_address) {
					item.vendorAddress = normalizeDateFn(ocr.vendor_address);
				}
				if (ocr.vendor_tin) {
					item.vendorTin = normalizeDateFn(ocr.vendor_tin);
				}

				setOcrState(itemId, { status: 'success', result: ocr, appliedFields });
			} catch (err) {
				clearTimeout(timeoutId);

				const current = state.ocrByItem[itemId];
				if (!current) {
					return; // already cleaned up by cancel
				}

				const isAbort = err.statusText === 'abort' || (err.readyState === 0 && err.status === 0);
				const isTimeout = err.statusText === 'timeout';

				if (isAbort) {
					// If still scanning, it was a hard timeout
					if (current.status === 'scanning') {
						setOcrState(itemId, { status: 'timeout', error: 'OCR stopped — enter details manually' });
					}
				} else if (isTimeout) {
					setOcrState(itemId, { status: 'timeout', error: 'OCR timed out — please enter details manually' });
				} else {
					setOcrState(itemId, {
						status: 'error',
						error: err.responseJSON?.response || err.statusText || 'OCR failed — please enter details manually',
					});
				}
			}
		};

		return {
			attachmentsLabel,
			addItemAttachments,
			ensureCameraPermission,
			isItemOcrLoading,
			promptAttachmentSource,
			runOcrAutofillForItem,
			getItemOcrState,
			cancelOcr,
			markManual,
		};
	};

	window.SharedReceiptOcr = {
		create,
	};
})(window);