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
	var TextControl = wp.components.TextControl;
	var FormTokenField = wp.components.FormTokenField;
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
		var rawEventArticle = meta.event_article;
		var eventArticleIds = [];
		if (Array.isArray(rawEventArticle)) {
			eventArticleIds = rawEventArticle
				.map(function (id) { return parseInt(id, 10) || 0; })
				.filter(function (id) { return id > 0; });
		} else {
			var singleRelatedId = parseInt(rawEventArticle || 0, 10);
			if (singleRelatedId > 0) {
				eventArticleIds = [singleRelatedId];
			}
		}
		var eventSlidesUrl = meta.event_slides_url || '';
		var eventVideoUrl = meta.event_video_url || '';

		var postSearchValueState = useState('');
		var postSearchValue = postSearchValueState[0];
		var setPostSearchValue = postSearchValueState[1];
		var postOptionsState = useState([]);
		var postOptions = postOptionsState[0];
		var setPostOptions = postOptionsState[1];
		var postTokenMapState = useState({});
		var postTokenMap = postTokenMapState[0];
		var setPostTokenMap = postTokenMapState[1];
		var postIdTitleMapState = useState({});
		var postIdTitleMap = postIdTitleMapState[0];
		var setPostIdTitleMap = postIdTitleMapState[1];

		function tokenFor(id, title) {
			var cleanTitle = String(title || '').trim();
			if (!cleanTitle) {
				return '#' + String(id);
			}
			return cleanTitle + ' (#' + String(id) + ')';
		}

		useEffect(function () {
			if (!eventArticleIds.length) {
				return;
			}
			var pending = eventArticleIds.map(function (id) {
				return apiFetch({ path: '/wp/v2/search?type=post&include=' + String(id) + '&per_page=1' })
					.then(function (items) {
						var item = Array.isArray(items) && items.length ? items[0] : null;
						if (!item || !item.id) {
							return null;
						}
						return { id: parseInt(item.id, 10), title: item.title || '' };
					})
					.catch(function () { return null; });
			});
			Promise.all(pending).then(function (results) {
				var nextTokenMap = {};
				var nextIdTitleMap = {};
				results.forEach(function (entry) {
					if (!entry || !entry.id) {
						return;
					}
					var token = tokenFor(entry.id, entry.title);
					nextTokenMap[token] = entry.id;
					nextIdTitleMap[String(entry.id)] = entry.title || '';
				});
				if (Object.keys(nextTokenMap).length) {
					setPostTokenMap(function (prev) {
						return Object.assign({}, prev, nextTokenMap);
					});
				}
				if (Object.keys(nextIdTitleMap).length) {
					setPostIdTitleMap(function (prev) {
						return Object.assign({}, prev, nextIdTitleMap);
					});
				}
			});
		}, [eventArticleIds.join(',')]);

		useEffect(function () {
			var term = (postSearchValue || '').trim();
			if (term.length < 2) {
				return;
			}
			var path = '/wp/v2/search?type=post&search=' + encodeURIComponent(term) + '&per_page=20';
			apiFetch({ path: path })
				.then(function (items) {
					if (!Array.isArray(items)) {
						return;
					}
					var mapped = [];
					var nextTokenMap = {};
					var nextIdTitleMap = {};
					items.forEach(function (item) {
						if (!item || !item.id) {
							return;
						}
						var numericId = parseInt(item.id, 10);
						if (!numericId) {
							return;
						}
						var token = tokenFor(numericId, item.title || '');
						mapped.push(token);
						nextTokenMap[token] = numericId;
						nextIdTitleMap[String(numericId)] = item.title || '';
					});
					setPostOptions(mapped);
					setPostTokenMap(function (prev) {
						return Object.assign({}, prev, nextTokenMap);
					});
					setPostIdTitleMap(function (prev) {
						return Object.assign({}, prev, nextIdTitleMap);
					});
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

			var timer = setTimeout(function () {
				var buttons = Array.prototype.slice.call(
					document.querySelectorAll('.components-panel__body-title .components-panel__body-toggle')
				);
				if (!buttons.length) {
					return;
				}

				var roleBtn = null;
				var infoBtn = null;
				var yoastBtn = null;

				buttons.forEach(function (btn) {
					var txt = normalize(btn.textContent);
					if (txt === 'roles') {
						roleBtn = btn;
					} else if (txt === 'informations') {
						infoBtn = btn;
					} else if (txt.indexOf('yoast') !== -1) {
						yoastBtn = btn;
					}

					if (txt === 'roles' || txt === 'informations') {
						if (btn.getAttribute('aria-expanded') !== 'true') {
							btn.click();
						}
					}
				});

				var rolePanel = roleBtn ? roleBtn.closest('.components-panel__body') : null;
				var infoPanel = infoBtn ? infoBtn.closest('.components-panel__body') : null;
				var yoastPanel = yoastBtn ? yoastBtn.closest('.components-panel__body') : null;

				if (rolePanel && infoPanel && rolePanel.parentElement === infoPanel.parentElement) {
					var parent = rolePanel.parentElement;
					if (rolePanel.nextElementSibling !== infoPanel) {
						parent.insertBefore(infoPanel, rolePanel.nextElementSibling);
					}
					if (yoastPanel && yoastPanel.parentElement === parent && infoPanel.nextElementSibling !== yoastPanel) {
						parent.insertBefore(yoastPanel, infoPanel.nextElementSibling);
					}
				}
			}, 300);

			return function () {
				clearTimeout(timer);
			};
		}, []);

		var selectedPostTokens = eventArticleIds.map(function (id) {
			return tokenFor(id, postIdTitleMap[String(id)] || '');
		});

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'jardin-events-informations',
				title: __('Informations', 'jardin-events'),
				className: 'jardin-events-info-panel'
			},
			el(TextControl, {
				label: __('Date de début', 'jardin-events'),
				type: 'date',
				value: eventDate,
				onChange: function (v) { setMeta('event_date', v || ''); }
			}),
			el(TextControl, {
				label: __('Date de fin', 'jardin-events'),
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
				label: __('Lien carte', 'jardin-events'),
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
				label: __('Billetterie', 'jardin-events'),
				type: 'url',
				value: eventTicketUrl,
				onChange: function (v) { setMeta('event_ticket_url', v || ''); }
			}),
			el(FormTokenField, {
				label: __('Contenu lié (récap)', 'jardin-events'),
				value: selectedPostTokens,
				suggestions: postOptions,
				__experimentalExpandOnFocus: true,
				__experimentalShowHowTo: false,
				onInputChange: function (v) { setPostSearchValue(v || ''); },
				onChange: function (tokens) {
					var ids = [];
					(tokens || []).forEach(function (token) {
						var raw = String(token || '').trim();
						if (!raw) {
							return;
						}
						var fromMap = postTokenMap[raw];
						if (fromMap) {
							ids.push(fromMap);
							return;
						}
						var match = raw.match(/#(\d+)\)?$/);
						if (match && match[1]) {
							ids.push(parseInt(match[1], 10));
						}
					});
					var uniqueIds = [];
					ids.forEach(function (id) {
						if (id > 0 && uniqueIds.indexOf(id) === -1) {
							uniqueIds.push(id);
						}
					});
					setMeta('event_article', uniqueIds);
				}
			}),
			el(TextControl, {
				label: __('URL slides', 'jardin-events'),
				type: 'url',
				value: eventSlidesUrl,
				onChange: function (v) { setMeta('event_slides_url', v || ''); }
			}),
			el(TextControl, {
				label: __('URL vidéo', 'jardin-events'),
				type: 'url',
				value: eventVideoUrl,
				onChange: function (v) { setMeta('event_video_url', v || ''); }
			})
		);
	}

	registerPlugin('jardin-events-editor-info-panel', {
		render: function () {
			return el(EventInfoPanel);
		}
	});
})(window.wp);
