(function () {
	if (!window.borobillStatsTracking || !borobillStatsTracking.ajaxUrl) {
		return;
	}

	var startAt = Date.now();
	var staySent = false;
	var readSent = false;
	var minSeconds = 3;
	var maxSeconds = 1800;
	var maxDepth = 0;
	var trackRead = !!Number(borobillStatsTracking.trackRead || 0);
	var postId = Number(borobillStatsTracking.postId || 0);
	var ASIDE_FIXED_TOP = 30;

	function postPayload(action, fields) {
		var body = new URLSearchParams();
		body.set('action', action);
		body.set('nonce', borobillStatsTracking.nonce || '');
		Object.keys(fields).forEach(function (key) {
			body.set(key, String(fields[key]));
		});

		var payload = body.toString();

		if (navigator.sendBeacon) {
			try {
				var blob = new Blob([payload], { type: 'application/x-www-form-urlencoded; charset=UTF-8' });
				if (navigator.sendBeacon(borobillStatsTracking.ajaxUrl, blob)) {
					return;
				}
			} catch (e) {
				// fall through
			}
		}

		if (window.fetch) {
			fetch(borobillStatsTracking.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: payload,
				keepalive: true,
				credentials: 'same-origin',
			}).catch(function () {});
			return;
		}

		try {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', borobillStatsTracking.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
			xhr.send(payload);
		} catch (err) {
			// ignore
		}
	}

	function getReadDepthPercent() {
		var article = document.querySelector('.single-post');
		if (!article) {
			return 0;
		}

		var scrollY = window.scrollY || window.pageYOffset || 0;
		var viewportH = window.innerHeight || document.documentElement.clientHeight || 0;
		var aside = document.querySelector('.single-aside.sticky-group');
		var articleTop = article.getBoundingClientRect().top + scrollY;
		var endEl = document.querySelector('.post-tags') || document.querySelector('.post-footer') || document.querySelector('.post-content');
		var contentEndDoc = 0;

		if (endEl) {
			var endRect = endEl.getBoundingClientRect();
			contentEndDoc = endRect.bottom + scrollY;
		} else {
			contentEndDoc = articleTop + article.offsetHeight;
		}

		var rangeStart = 0;
		var asideHeight = aside ? aside.offsetHeight : 320;
		var rangeEnd = contentEndDoc - ASIDE_FIXED_TOP - asideHeight;
		var minRange = Math.max(viewportH, (contentEndDoc - articleTop) * 0.5);
		rangeEnd = Math.max(rangeEnd, rangeStart + minRange);
		var range = rangeEnd - rangeStart;
		if (range <= 0) {
			return 0;
		}

		var raw = (scrollY - rangeStart) / range;
		return Math.round(Math.max(0, Math.min(100, raw * 100)));
	}

	function updateMaxDepth() {
		if (!trackRead) {
			return;
		}
		maxDepth = Math.max(maxDepth, getReadDepthPercent());
	}

	function sendStay() {
		if (staySent) {
			return;
		}

		var seconds = Math.round((Date.now() - startAt) / 1000);
		if (seconds < minSeconds) {
			return;
		}
		if (seconds > maxSeconds) {
			seconds = maxSeconds;
		}

		staySent = true;
		postPayload('borobill_track_stay', { seconds: seconds });
	}

	function sendRead() {
		if (!trackRead || readSent) {
			return;
		}

		updateMaxDepth();
		var seconds = Math.round((Date.now() - startAt) / 1000);
		if (seconds > maxSeconds) {
			seconds = maxSeconds;
		}
		if (seconds < 1 && maxDepth <= 0) {
			return;
		}

		readSent = true;
		postPayload('borobill_track_read', {
			depth: maxDepth,
			seconds: Math.max(1, seconds),
			post_id: postId > 0 ? postId : 0,
		});
	}

	function onLeave() {
		sendStay();
		sendRead();
	}

	if (trackRead) {
		updateMaxDepth();
		window.addEventListener('scroll', updateMaxDepth, { passive: true });
		window.addEventListener('resize', updateMaxDepth);
	}

	document.addEventListener('visibilitychange', function () {
		if (document.visibilityState === 'hidden') {
			onLeave();
		}
	});

	window.addEventListener('pagehide', onLeave);
	window.addEventListener('beforeunload', onLeave);
})();
