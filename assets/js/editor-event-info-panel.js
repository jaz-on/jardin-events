/**
 * Gutenberg document panel for Jardin Events metadata.
 */
(function (wp) {
	'use strict';

	if (!wp || !wp.plugins || !wp.editPost || !wp.element || !wp.components || !wp.data || !wp.apiFetch || !wp.i18n) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var Fragment = wp.element.Fragment;
	var TextControl = wp.components.TextControl;
	var ComboboxControl = wp.components.ComboboxControl;
	var __ = wp.i18n.__;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var apiFetch = wp.apiFetch;

	function EventInfoPanel() {
		var postType = useSelect(function (select) {
			return select('core/editor').getCurrentPostType();
		}, []);

		if (postType !== 'event') {
			return null;
		}

		var meta = useSelect(function (select) {
			return select('core/editor').getEditedPostAttribute('meta') || {};
		}, []);

		var editPost = useDispatch('core/editor').editPost;

		function setMeta(key, value) {
			var next = {};
			next[key] = value;
			editPost({ meta: next });
		}

		var eventDate = meta.event_date || '';
		var eventEndDate = meta.event_date_end || '';
		var eventCity = meta.event_city || '';
		var eventCountry = meta.event_country || '';
		var eventMapUrl = meta.event_map_url || '';
		var eventLink = meta.event_link || '';
		var eventTicketUrl = meta.event_ticket_url || '';
		var eventArticle = meta.event_article || 0;
		var eventSlidesUrl = meta.event_slides_url || '';
		var eventVideoUrl = meta.event_video_url || '';

		var postSearchValueState = useState('');
		var postSearchValue = postSearchValueState[0];
		var setPostSearchValue = postSearchValueState[1];
		var postOptionsState = useState([]);
		var postOptions = postOptionsState[0];
		var setPostOptions = postOptionsState[1];

		useEffect(function () {
			var selectedId = parseInt(eventArticle || 0, 10);
			if (!selectedId) {
				return;
			}
			apiFetch({ path: '/wp/v2/posts/' + selectedId + '?_fields=id,title' })
				.then(function (post) {
					if (!post || !post.id) {
						return;
					}
					var title = post.title && post.title.rendered ? post.title.rendered : '#' + String(post.id);
					setPostOptions(function (prev) {
						var out = prev.slice();
						var found = false;
						out.forEach(function (opt) {
							if (String(opt.value) === String(post.id)) {
								opt.label = title;
								found = true;
							}
						});
						if (!found) {
							out.push({ value: String(post.id), label: title });
						}
						return out;
					});
				})
				.catch(function () {});
		}, [eventArticle]);

		useEffect(function () {
			var term = (postSearchValue || '').trim();
			if (term.length < 2) {
				return;
			}
			var path = '/wp/v2/search?type=post&subtype=post&search=' + encodeURIComponent(term) + '&per_page=10';
			apiFetch({ path: path })
				.then(function (items) {
					if (!Array.isArray(items)) {
						return;
					}
					var mapped = items.map(function (item) {
						return {
							value: String(item.id),
							label: item.title || ('#' + String(item.id))
						};
					});
					setPostOptions(mapped);
				})
				.catch(function () {});
		}, [postSearchValue]);

		useEffect(function () {
			function normalize(s) {
				return String(s || '')
					.normalize('NFD')
					.replace(/[\u0300-\u036f]/g, '')
					.trim()
					.toLowerCase();
			}

			function ensurePanelOrder() {
				var buttons = Array.prototype.slice.call(
					document.querySelectorAll('.components-panel__body-title .components-panel__body-toggle')
				);
				if (!buttons.length) {
					return;
				}

				var roleBtn = null;
				var infoBtn = null;
				buttons.forEach(function (btn) {
					var txt = normalize(btn.textContent);
					if (txt === 'roles') {
						roleBtn = btn;
					}
					if (txt === 'informations') {
						infoBtn = btn;
					}
				});

				if (!roleBtn || !infoBtn) {
					return;
				}

				var rolePanel = roleBtn.closest('.components-panel__body');
				var infoPanel = infoBtn.closest('.components-panel__body');
				if (!rolePanel || !infoPanel || rolePanel === infoPanel) {
					return;
				}

				var parent = rolePanel.parentElement;
				if (!parent || parent !== infoPanel.parentElement) {
					return;
				}

				if (rolePanel.nextElementSibling !== infoPanel) {
					parent.insertBefore(infoPanel, rolePanel.nextElementSibling);
				}
			}

			function ensurePanelsOpened() {
				var buttons = Array.prototype.slice.call(
					document.querySelectorAll('.components-panel__body-title .components-panel__body-toggle')
				);
				if (!buttons.length) {
					return;
				}

				buttons.forEach(function (btn) {
					var txt = normalize(btn.textContent);
					if (txt === 'roles' || txt === 'informations') {
						var expanded = btn.getAttribute('aria-expanded') === 'true';
						if (!expanded) {
							btn.click();
						}
					}
				});
			}

			ensurePanelOrder();
			ensurePanelsOpened();
			var observer = new MutationObserver(function () {
				ensurePanelOrder();
				ensurePanelsOpened();
			});
			observer.observe(document.body, { childList: true, subtree: true });
			return function () {
				observer.disconnect();
			};
		}, []);

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'jardin-events-informations',
				title: __('Informations', 'jardin-events')
			},
			el(TextControl, {
				label: __('Date de début', 'jardin-events'),
				type: 'date',
				value: eventDate,
				onChange: function (v) { setMeta('event_date', v || ''); }
			}),
			el(TextControl, {
				label: __('Date de fin (optionnelle)', 'jardin-events'),
				type: 'date',
				value: eventEndDate,
				onChange: function (v) { setMeta('event_date_end', v || ''); }
			}),
			el(TextControl, {
				label: __('Ville', 'jardin-events'),
				value: eventCity,
				onChange: function (v) { setMeta('event_city', v || ''); }
			}),
			el(TextControl, {
				label: __('Pays', 'jardin-events'),
				value: eventCountry,
				onChange: function (v) { setMeta('event_country', v || ''); }
			}),
			el(TextControl, {
				label: __('Lien carte (Google Maps/OSM)', 'jardin-events'),
				type: 'url',
				value: eventMapUrl,
				onChange: function (v) { setMeta('event_map_url', v || ''); }
			}),
			el(TextControl, {
				label: __('Page de l’événement', 'jardin-events'),
				type: 'url',
				value: eventLink,
				onChange: function (v) { setMeta('event_link', v || ''); }
			}),
			el(TextControl, {
				label: __('Billetterie (optionnel)', 'jardin-events'),
				type: 'url',
				value: eventTicketUrl,
				onChange: function (v) { setMeta('event_ticket_url', v || ''); }
			}),
			el(ComboboxControl, {
				label: __('Contenu lié (récap)', 'jardin-events'),
				value: eventArticle ? String(eventArticle) : '',
				options: postOptions,
				onFilterValueChange: function (v) { setPostSearchValue(v || ''); },
				onChange: function (v) { setMeta('event_article', v ? parseInt(v, 10) : 0); }
			}),
			el(TextControl, {
				label: __('URL des slides (optionnel)', 'jardin-events'),
				type: 'url',
				value: eventSlidesUrl,
				onChange: function (v) { setMeta('event_slides_url', v || ''); }
			}),
			el(TextControl, {
				label: __('URL vidéo (optionnel)', 'jardin-events'),
				type: 'url',
				value: eventVideoUrl,
				onChange: function (v) { setMeta('event_video_url', v || ''); }
			})
		);
	}

	registerPlugin('jardin-events-editor-info-panel', {
		render: function () {
			return el(Fragment, null, el(EventInfoPanel));
		}
	});
})(window.wp);
