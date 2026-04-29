/**
 * Event metabox admin helpers:
 * - Search posts for recap article picker.
 * - Improve end-date picker by following the selected start date.
 */
(function () {
	function initArticleSearch() {
		const searchInput = document.getElementById('jardin-event-article-search');
		const idInput = document.getElementById('jardin-event-article-id');
		const list = document.getElementById('jardin-event-article-suggest');
		const cfg = typeof jardinEventsAdmin === 'undefined' ? null : jardinEventsAdmin;

		if (!searchInput || !idInput || !list || !cfg || !cfg.ajaxUrl || !cfg.nonce) {
			return;
		}

		let timer;

		function hideList() {
			list.hidden = true;
			list.innerHTML = '';
		}

		function render(items) {
			list.innerHTML = '';
			if (!items.length) {
				hideList();
				return;
			}
			items.forEach(function (item) {
				const li = document.createElement('li');
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.textContent = item.title + ' (#' + item.id + ')';
				btn.addEventListener('click', function () {
					idInput.value = String(item.id);
					searchInput.value = '';
					hideList();
				});
				li.appendChild(btn);
				list.appendChild(li);
			});
			list.hidden = false;
		}

		searchInput.addEventListener('input', function () {
			clearTimeout(timer);
			const term = searchInput.value.trim();
			if (term.length < 2) {
				hideList();
				return;
			}
			timer = setTimeout(function () {
				const url =
					cfg.ajaxUrl +
					'?action=jardin_events_search_posts&nonce=' +
					encodeURIComponent(cfg.nonce) +
					'&search=' +
					encodeURIComponent(term);
				fetch(url, { credentials: 'same-origin' })
					.then(function (r) {
						return r.json();
					})
					.then(function (body) {
						if (body.success && Array.isArray(body.data)) {
							render(body.data);
						} else {
							hideList();
						}
					})
					.catch(function () {
						hideList();
					});
			}, 300);
		});

		document.addEventListener('click', function (e) {
			if (!list.contains(e.target) && e.target !== searchInput) {
				hideList();
			}
		});
	}

	function initDatePickerSync() {
		const startInput = document.getElementById('jardin-event-date');
		const endInput = document.getElementById('jardin-event-end-date');
		if (!startInput || !endInput) {
			return;
		}

		function syncEndFromStart() {
			const startDate = startInput.value;
			if (!startDate) {
				return;
			}

			// Pre-fill empty end date to reduce repetitive input.
			if (!endInput.value) {
				endInput.value = startDate;
			}

			// Force native date picker to open around the start date.
			endInput.min = startDate;
		}

		startInput.addEventListener('change', syncEndFromStart);
		endInput.addEventListener('focus', syncEndFromStart);
		endInput.addEventListener('click', syncEndFromStart);
	}

	initArticleSearch();
	initDatePickerSync();
})();
