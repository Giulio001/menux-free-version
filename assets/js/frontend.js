/* MenuX — Frontend JS. Dynamic config is injected via wp_add_inline_script (see includes/assets.php). */
document.addEventListener("DOMContentLoaded", function() {
    var _d             = window.menuxFrontendData || {};
    var supportedCodes = _d.supportedCodes || [];
    var defaultCode    = _d.defaultCode    || '';
    var currentUrl     = window.location.href.split(/[?#]/)[0].replace(/\/$/, "");
    var ajaxUrl        = _d.ajaxUrl        || '';
    var trackNonce     = _d.trackNonce     || '';
    var isLoggedIn     = _d.isLoggedIn     || 0;
    var isSticky       = _d.isSticky       || 0;

    function findBestCode(langAttr, codes) {
        var lang = (langAttr || defaultCode).toLowerCase().replace('_', '-');
        for (var i = 0; i < codes.length; i++) { if (codes[i].toLowerCase() === lang) return codes[i]; }
        var prefix = lang.substring(0, 2);
        for (var i = 0; i < codes.length; i++) { if (codes[i].toLowerCase().startsWith(prefix)) return codes[i]; }
        return codes[0] || defaultCode;
    }

    function applyLanguage() {
        var langAttr = document.documentElement.getAttribute("lang") || defaultCode;
        var bestCode = findBestCode(langAttr, supportedCodes);
        var dataAttr = 'data-lang-' + bestCode.toLowerCase().replace('-', '_');
        document.querySelectorAll(".menux-container").forEach(function(nav) {
            nav.querySelectorAll(".menux-label").forEach(function(span) {
                span.textContent = span.getAttribute(dataAttr) || span.getAttribute('data-default') || '';
            });
        });
    }
    applyLanguage();

    // Active link
    document.querySelectorAll(".menux-container").forEach(function(nav) {
        nav.querySelectorAll("a.menux-link").forEach(function(a) {
            var href = (a.getAttribute("href") || "").replace(/\/$/, "");
            if (currentUrl === href) a.classList.add("active");
        });

        var hamburger  = nav.querySelector(".menux-hamburger");
        var ul         = nav.querySelector(".menux-list");
        var overlay    = document.getElementById('menux-overlay');
        var closeBtn   = document.getElementById('menux-close-btn');
        var openStyle  = nav.getAttribute('data-mobile-open-style') || 'dropdown';
        var bpMode     = nav.getAttribute('data-mobile-bp-mode') || 'manual';
        var bpPx       = parseInt(nav.getAttribute('data-mobile-bp') || '768', 10);

        function bmOpenMenu() {
            if (!ul) return;
            ul.classList.add('show');
            hamburger && hamburger.classList.add('open');
            hamburger && hamburger.setAttribute('aria-expanded', 'true');
            if (overlay && openStyle !== 'dropdown') {
                overlay.style.display = 'block';
                overlay.getBoundingClientRect();
                overlay.classList.add('visible');
            }
            if (closeBtn && openStyle !== 'dropdown') {
                closeBtn.classList.add('visible');
            }
            if (openStyle !== 'dropdown') {
                document.body.style.overflow = 'hidden';
            }
        }

        function bmCloseMenu() {
            if (!ul) return;
            ul.classList.remove('show');
            hamburger && hamburger.classList.remove('open');
            hamburger && hamburger.setAttribute('aria-expanded', 'false');
            ul.querySelectorAll('.menux-submenu.mobile-open').forEach(function(sm) {
                sm.classList.remove('mobile-open');
            });
            if (overlay) {
                overlay.classList.remove('visible');
                setTimeout(function(){ if (!overlay.classList.contains('visible')) overlay.style.display = 'none'; }, 320);
            }
            if (closeBtn) {
                closeBtn.classList.remove('visible');
            }
            document.body.style.overflow = '';
        }

        function bmToggleMenu() {
            ul && ul.classList.contains('show') ? bmCloseMenu() : bmOpenMenu();
        }

        if (hamburger && ul) {
            hamburger.addEventListener('click', function(e) {
                e.preventDefault();
                bmToggleMenu();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', bmCloseMenu);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', bmCloseMenu);
        }

        document.addEventListener('keydown', function(e) {
            if ((e.key === 'Escape' || e.keyCode === 27) && ul && ul.classList.contains('show')) {
                bmCloseMenu();
                hamburger && hamburger.focus();
            }
        });

        nav.querySelectorAll(".menux-has-children > a.menux-link").forEach(function(a) {
            a.addEventListener("click", function(e) {
                var isMobile = bpMode === 'auto'
                    ? nav.classList.contains('menux-is-mobile')
                    : window.innerWidth <= bpPx;
                if (isMobile) {
                    e.preventDefault();
                    var subMenu = a.parentElement.querySelector('.menux-submenu');
                    if (subMenu) {
                        subMenu.classList.toggle('mobile-open');
                        a.classList.toggle('mobile-sm-open');
                    }
                }
            });
        });

        if (bpMode === 'auto') {
            function bmCheckFit() {
                var list = nav.querySelector('.menux-list');
                if (!list) return;
                var wasMobile = nav.classList.contains('menux-is-mobile');
                nav.classList.remove('menux-is-mobile');
                nav.classList.add('menux-is-desktop');
                list.style.visibility = 'hidden';
                list.style.display = 'flex';
                list.style.flexDirection = 'row';

                var containerW = nav.getBoundingClientRect().width;
                var listScrollW = list.scrollWidth;
                var logoW = 0;
                var logo = nav.querySelector('.menux-logo');
                if (logo) logoW = logo.getBoundingClientRect().width + 16;

                var fits = (listScrollW + logoW) <= containerW;

                list.style.visibility = '';
                list.style.display = '';
                list.style.flexDirection = '';

                if (fits) {
                    nav.classList.remove('menux-is-mobile');
                    nav.classList.add('menux-is-desktop');
                    if (wasMobile && ul && ul.classList.contains('show')) bmCloseMenu();
                } else {
                    nav.classList.remove('menux-is-desktop');
                    nav.classList.add('menux-is-mobile');
                }
            }

            if (window.ResizeObserver) {
                new ResizeObserver(function() { bmCheckFit(); }).observe(nav);
            } else {
                window.addEventListener('resize', bmCheckFit, { passive: true });
            }
            bmCheckFit();

        } else {
            window.addEventListener('resize', function() {
                if (window.innerWidth > bpPx && ul && ul.classList.contains('show')) {
                    bmCloseMenu();
                }
            }, { passive: true });
        }
    });

    // Click tracking
    var bmCountryCode = '';
    try {
        fetch('https://ipapi.co/json/', {mode:'cors'}).then(function(r){return r.json()}).then(function(d){
            if (d && d.country_code) bmCountryCode = d.country_code;
        }).catch(function(){});
    } catch(e){}

    document.querySelectorAll("a.menux-link[data-item-key]").forEach(function(a) {
        a.addEventListener("click", function() {
            var key   = a.getAttribute('data-item-key')   || '';
            var label = a.getAttribute('data-item-label') || '';
            var url   = a.getAttribute('data-item-url')   || a.getAttribute('href') || '';
            var lang  = document.documentElement.getAttribute('lang') || defaultCode;
            var params = { action:'menux_track_click', nonce:trackNonce, item_key:key, item_label:label, item_url:url, user_lang:lang, is_logged_in:isLoggedIn, country:bmCountryCode };
            navigator.sendBeacon
                ? navigator.sendBeacon(ajaxUrl, new URLSearchParams(params))
                : fetch(ajaxUrl, { method:'POST', body: new URLSearchParams(params) });
        });
    });

    // Sticky
    if (isSticky) {
        var nav = document.getElementById('menux-nav-main');
        var spacer = document.getElementById('menux-spacer');
        if (nav) {
            var navTop = nav.getBoundingClientRect().top + window.scrollY;
            var navH   = nav.offsetHeight;

            var lastScrollY = window.scrollY;
            var scrollThreshold = 5;
            nav.classList.add('menux-autohide');

            function checkSticky() {
                var currentY = window.scrollY;
                if (currentY >= navTop) {
                    nav.classList.add('menux-sticky-fixed');
                    if (spacer) { spacer.style.display = 'block'; spacer.style.height = navH + 'px'; }

                    if (currentY > lastScrollY && (currentY - lastScrollY) > scrollThreshold) {
                        nav.classList.add('menux-hidden');
                    } else if (currentY < lastScrollY && (lastScrollY - currentY) > scrollThreshold) {
                        nav.classList.remove('menux-hidden');
                    }
                } else {
                    nav.classList.remove('menux-sticky-fixed');
                    nav.classList.remove('menux-hidden');
                    if (spacer) spacer.style.display = 'none';
                }
                lastScrollY = currentY;
            }
            window.addEventListener('scroll', checkSticky, { passive: true });
            checkSticky();
        }
    }

    // Scroll progress bar
    var progressBar = document.getElementById('menux-progress-bar');
    if (progressBar) {
        function menuxUpdateProgress() {
            var docH = document.documentElement.scrollHeight - window.innerHeight;
            var pct  = docH > 0 ? (window.scrollY / docH) * 100 : 0;
            progressBar.style.width = Math.min(100, pct).toFixed(1) + '%';
        }
        window.addEventListener('scroll', menuxUpdateProgress, { passive: true });
        menuxUpdateProgress();
    }

    // Language observer
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) { if (m.attributeName === 'lang') applyLanguage(); });
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });

    // Search modal
    var searchEnabled = _d.searchEnabled || false;
    if (searchEnabled) {
        var _bmSM = {
            modal: document.getElementById('menux-search-modal'),
            input: document.getElementById('menux-search-input-modal'),
            results: document.getElementById('menux-search-results-modal'),
            countEl: document.getElementById('menux-search-count'),
            openBtn: document.getElementById('menux-search-open'),
            closeBtn: document.getElementById('menux-search-close'),
            backdrop: document.getElementById('menux-search-backdrop'),
            navBar: document.getElementById('bm-search-nav-bar'),
            navLabel: document.getElementById('bm-snb-label'),
            navPrev: document.getElementById('bm-snb-prev'),
            navNext: document.getElementById('bm-snb-next'),
            navClear: document.getElementById('bm-snb-clear'),
            currentTab: 'menu',
            focusIdx: -1,
            pageMatches: [],
            pageMatchIdx: -1,
            debounceTimer: null
        };

        function bmSmOpen() {
            if (!_bmSM.modal) return;
            _bmSM.modal.classList.add('bm-sm-open');
            document.body.style.overflow = 'hidden';
            setTimeout(function() { if (_bmSM.input) _bmSM.input.focus(); }, 50);
            bmSmRenderEmpty();
        }
        function bmSmClose() {
            if (!_bmSM.modal) return;
            _bmSM.modal.classList.remove('bm-sm-open');
            document.body.style.overflow = '';
            if (_bmSM.input) _bmSM.input.value = '';
            _bmSM.focusIdx = -1;
            bmSmRenderEmpty();
        }

        if (_bmSM.openBtn) _bmSM.openBtn.addEventListener('click', bmSmOpen);
        if (_bmSM.closeBtn) _bmSM.closeBtn.addEventListener('click', bmSmClose);
        if (_bmSM.backdrop) _bmSM.backdrop.addEventListener('click', bmSmClose);

        document.querySelectorAll('#menux-search-box .bm-sm-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('#menux-search-box .bm-sm-tab').forEach(function(t){ t.classList.remove('active'); });
                tab.classList.add('active');
                _bmSM.currentTab = tab.getAttribute('data-tab');
                _bmSM.focusIdx = -1;
                var q = _bmSM.input ? _bmSM.input.value.trim() : '';
                if (q.length >= 2) bmSmSearch(q);
                else bmSmRenderEmpty();
            });
        });

        function bmSmHL(text, q) {
            if (!q) return text;
            var safe = q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
            return text.replace(new RegExp('(' + safe + ')', 'gi'), '<mark>$1</mark>');
        }

        function bmSmRenderEmpty() {
            if (!_bmSM.results) return;
            _bmSM.results.innerHTML = '<div class="bm-sm-empty">'
                + '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
                + '<div style="font-size:15px;font-weight:600;color:#475569;margin-bottom:6px;">Start typing to search</div>'
                + '<div style="font-size:12px;">Tab <strong>Menu</strong>: search menu links · Tab <strong>In page</strong>: find text on the current page</div>'
                + '</div>';
            if (_bmSM.countEl) _bmSM.countEl.textContent = '';
        }

        function bmSmRenderNoResults(q) {
            if (!_bmSM.results) return;
            _bmSM.results.innerHTML = '<div class="bm-sm-empty">'
                + '<svg viewBox="0 0 24 24"><path d="M10 10l4 4M14 10l-4 4"/><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
                + '<div style="font-size:15px;font-weight:600;color:#475569;margin-bottom:6px;">No results for "<em style=\'color:#6366f1;font-style:normal;\'>' + q + '</em>"</div>'
                + '</div>';
            if (_bmSM.countEl) _bmSM.countEl.textContent = '0 results';
        }

        function bmSmSearch(q) {
            if (_bmSM.currentTab === 'menu') bmSmSearchMenu(q);
            else bmSmSearchPage(q);
        }

        function bmSmSearchMenu(q) {
            var items = [];
            document.querySelectorAll('#menux-nav-main a.menux-link').forEach(function(a) {
                var labelEl = a.querySelector('.menux-label');
                var label = labelEl ? labelEl.textContent.trim() : a.textContent.trim();
                var href  = a.getAttribute('href') || '';
                var iconEl = a.querySelector('i[class]');
                var iconClass = iconEl ? iconEl.className : '';
                if (label && href && href !== '#') items.push({ label: label, href: href, icon: iconClass });
            });
            var matches = items.filter(function(it) { return it.label.toLowerCase().indexOf(q.toLowerCase()) !== -1; });
            if (!matches.length) { bmSmRenderNoResults(q); return; }

            var html = '';
            matches.forEach(function(it, i) {
                var iconHtml = it.icon
                    ? '<div class="bm-sm-result-icon"><i class="' + it.icon + '" aria-hidden="true"></i></div>'
                    : '<div class="bm-sm-result-icon">🔗</div>';
                html += '<a href="' + it.href + '" class="bm-sm-result" data-idx="' + i + '" onclick="bmSmClose()">'
                    + iconHtml
                    + '<div><div class="bm-sm-result-title">' + bmSmHL(it.label, q) + '</div>'
                    + '<div class="bm-sm-result-sub">' + it.href + '</div></div>'
                    + '</a>';
            });
            if (_bmSM.results) _bmSM.results.innerHTML = html;
            if (_bmSM.countEl) _bmSM.countEl.textContent = matches.length + ' result' + (matches.length===1?'':'s');
            _bmSM.focusIdx = -1;
        }

        function bmSmSearchPage(q) {
            bmSmClearPageHighlights();

            var bodyEl = document.body;
            var walker = document.createTreeWalker(bodyEl, NodeFilter.SHOW_TEXT, {
                acceptNode: function(node) {
                    var p = node.parentNode;
                    while (p) {
                        var tn = (p.tagName || '').toLowerCase();
                        if (tn === 'script' || tn === 'style' || tn === 'noscript') return NodeFilter.FILTER_REJECT;
                        if (p.id === 'menux-search-modal' || p.id === 'bm-search-nav-bar') return NodeFilter.FILTER_REJECT;
                        if (p.classList && (p.classList.contains('menux-container') || p.classList.contains('menux-sticky-spacer'))) return NodeFilter.FILTER_REJECT;
                        p = p.parentNode;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            }, false);

            var re = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'), 'gi');
            var hits = [];
            var node;
            while ((node = walker.nextNode())) {
                var text = node.nodeValue;
                var m;
                var lastIdx = 0;
                var frag = null;
                re.lastIndex = 0;
                while ((m = re.exec(text)) !== null) {
                    if (!frag) frag = document.createDocumentFragment();
                    if (m.index > lastIdx) frag.appendChild(document.createTextNode(text.slice(lastIdx, m.index)));
                    var mark = document.createElement('mark');
                    mark.className = 'menux-highlight';
                    mark.textContent = m[0];
                    frag.appendChild(mark);
                    hits.push(mark);
                    lastIdx = m.index + m[0].length;
                }
                if (frag) {
                    if (lastIdx < text.length) frag.appendChild(document.createTextNode(text.slice(lastIdx)));
                    node.parentNode.replaceChild(frag, node);
                }
            }

            _bmSM.pageMatches = hits;
            _bmSM.pageMatchIdx = -1;

            if (!hits.length) { bmSmRenderNoResults(q); return; }

            var html = '';
            hits.forEach(function(mark, i) {
                var ctx = '';
                var par = mark.parentNode;
                while (par && !ctx.trim()) {
                    ctx = par.textContent || '';
                    par = par.parentNode;
                }
                ctx = ctx.replace(/\s+/g,' ').trim().slice(0, 120);
                html += '<div class="bm-sm-page-hit" data-hit-idx="' + i + '">'
                    + '<div class="bm-sm-result-title">' + bmSmHL(mark.textContent, q) + '</div>'
                    + '<div class="bm-sm-ph-ctx">' + bmSmHL(ctx, q) + '</div>'
                    + '<div class="bm-sm-ph-pos">Match ' + (i+1) + ' of ' + hits.length + '</div>'
                    + '</div>';
            });
            if (_bmSM.results) _bmSM.results.innerHTML = html;
            if (_bmSM.countEl) _bmSM.countEl.textContent = hits.length + ' occurrence' + (hits.length===1?'':'s');

            _bmSM.results.querySelectorAll('.bm-sm-page-hit').forEach(function(el) {
                el.addEventListener('click', function() {
                    var idx = parseInt(el.getAttribute('data-hit-idx'));
                    bmSmClose();
                    bmSmGoToHit(idx);
                    bmSmShowNavBar(q);
                });
            });

            if (hits.length > 0) bmSmShowNavBar(q);
        }

        function bmSmGoToHit(idx) {
            if (!_bmSM.pageMatches.length) return;
            idx = ((idx % _bmSM.pageMatches.length) + _bmSM.pageMatches.length) % _bmSM.pageMatches.length;
            _bmSM.pageMatchIdx = idx;
            _bmSM.pageMatches.forEach(function(m) { m.className = 'menux-highlight'; });
            var cur = _bmSM.pageMatches[idx];
            cur.className = 'menux-highlight menux-highlight-current';
            cur.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (_bmSM.navLabel) _bmSM.navLabel.textContent = (idx+1) + ' / ' + _bmSM.pageMatches.length;
        }

        function bmSmShowNavBar(q) {
            if (!_bmSM.navBar || !_bmSM.pageMatches.length) return;
            _bmSM.navBar.classList.add('show');
            if (_bmSM.navLabel) _bmSM.navLabel.textContent = _bmSM.pageMatchIdx >= 0 ? (_bmSM.pageMatchIdx+1) + ' / ' + _bmSM.pageMatches.length : _bmSM.pageMatches.length + ' found';
            bmSmGoToHit(0);
        }

        function bmSmClearPageHighlights() {
            document.querySelectorAll('mark.menux-highlight,mark.menux-highlight-current').forEach(function(mark) {
                var txt = document.createTextNode(mark.textContent);
                mark.parentNode.replaceChild(txt, mark);
            });
            _bmSM.pageMatches = [];
            _bmSM.pageMatchIdx = -1;
            if (_bmSM.navBar) _bmSM.navBar.classList.remove('show');
        }

        if (_bmSM.navPrev) _bmSM.navPrev.addEventListener('click', function() { bmSmGoToHit(_bmSM.pageMatchIdx - 1); });
        if (_bmSM.navNext) _bmSM.navNext.addEventListener('click', function() { bmSmGoToHit(_bmSM.pageMatchIdx + 1); });
        if (_bmSM.navClear) _bmSM.navClear.addEventListener('click', function() { bmSmClearPageHighlights(); });

        if (_bmSM.input) {
            _bmSM.input.addEventListener('input', function() {
                clearTimeout(_bmSM.debounceTimer);
                var q = _bmSM.input.value.trim();
                if (q.length < 2) { bmSmRenderEmpty(); return; }
                _bmSM.debounceTimer = setTimeout(function() { bmSmSearch(q); }, 220);
            });

            _bmSM.input.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') { bmSmClose(); return; }
                var items = _bmSM.results ? _bmSM.results.querySelectorAll('.bm-sm-result,.bm-sm-page-hit') : [];
                if (!items.length) return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    _bmSM.focusIdx = (_bmSM.focusIdx + 1) % items.length;
                    items.forEach(function(el,i){ el.classList.toggle('bm-sm-focused', i === _bmSM.focusIdx); });
                    items[_bmSM.focusIdx].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    _bmSM.focusIdx = (_bmSM.focusIdx - 1 + items.length) % items.length;
                    items.forEach(function(el,i){ el.classList.toggle('bm-sm-focused', i === _bmSM.focusIdx); });
                    items[_bmSM.focusIdx].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter' && _bmSM.focusIdx >= 0) {
                    e.preventDefault();
                    items[_bmSM.focusIdx].click();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && _bmSM.modal && _bmSM.modal.classList.contains('bm-sm-open')) { bmSmClose(); }
        });
    }
});
