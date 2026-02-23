/**
 * Tools4Regnum — Frontend JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // Favorite toggle (AJAX)
    // ============================================================
    function bindFavoriteButtons(root) {
        (root || document).querySelectorAll('.favorite-btn:not([data-bound])').forEach(function (btn) {
            btn.setAttribute('data-bound', '1');
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!IS_LOGGED_IN) {
                    window.location.href = '/login';
                    return;
                }

                var entryId = this.getAttribute('data-entry-id');
                var icon = this.querySelector('i');
                var button = this;

                button.disabled = true;

                var formData = new FormData();
                formData.append('entry_id', entryId);

                fetch('/api/favorite/toggle', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: formData
                })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.success) {
                        if (data.is_favorited) {
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill');
                        } else {
                            icon.classList.remove('bi-heart-fill');
                            icon.classList.add('bi-heart');

                            var card = button.closest('.col-md-6, .col-lg-4');
                            if (card && window.location.pathname === '/favorites') {
                                card.style.transition = 'opacity 0.3s';
                                card.style.opacity = '0';
                                setTimeout(function () { card.remove(); }, 300);
                            }
                        }
                    }
                })
                .catch(function (err) {
                    console.error('Favorite toggle failed:', err);
                })
                .finally(function () {
                    button.disabled = false;
                });
            });
        });
    }

    // Bind existing favorite buttons
    bindFavoriteButtons();

    // ============================================================
    // Auto-generate slug from German title (admin form)
    // ============================================================
    var titleDe = document.querySelector('input[name="title_de"]');
    var slugField = document.querySelector('input[name="slug"]');

    if (titleDe && slugField && !slugField.value) {
        titleDe.addEventListener('input', function () {
            var text = this.value.toLowerCase()
                .replace(/[äÄ]/g, 'ae')
                .replace(/[öÖ]/g, 'oe')
                .replace(/[üÜ]/g, 'ue')
                .replace(/ß/g, 'ss')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
            slugField.value = text;
        });
    }

    // ============================================================
    // Bootstrap tooltips initialization
    // ============================================================
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ============================================================
    // Live Search — dropdown for navbar + hero inputs
    // ============================================================
    var liveSearchInputs = document.querySelectorAll('.live-search-input');
    var activeDropdown = null;
    var activeIndex = -1;
    var searchDebounceTimer = null;

    function debounce(fn, delay) {
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function () {
                fn.apply(ctx, args);
            }, delay);
        };
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function buildDropdownHtml(results, total, query) {
        if (!results || results.length === 0) {
            return '<div class="search-item" style="justify-content:center;color:#6c757d;"><em>No results</em></div>';
        }

        var html = '';
        results.forEach(function (r) {
            var thumbHtml;
            if (r.thumb) {
                thumbHtml = '<div class="search-item-thumb"><img src="' + escapeHtml(r.thumb) + '" alt="" loading="lazy" onerror="this.parentElement.innerHTML=\'<i class=\\\'bi ' + escapeHtml(r.icon) + ' text-secondary\\\'></i>\'"></div>';
            } else {
                thumbHtml = '<div class="search-item-thumb"><i class="bi ' + escapeHtml(r.icon) + ' text-secondary"></i></div>';
            }
            html += '<a href="' + escapeHtml(r.url) + '" class="search-item">'
                + thumbHtml
                + '<div class="search-item-info">'
                + '<div class="search-item-title">' + escapeHtml(r.title) + '</div>'
                + '<div class="search-item-meta">' + escapeHtml(r.category) + (r.summary ? ' &middot; ' + escapeHtml(r.summary) : '') + '</div>'
                + '</div>'
                + '</a>';
        });

        if (total > results.length) {
            html += '<div class="search-footer"><a href="/search?q=' + encodeURIComponent(query) + '">Show all ' + total + ' results &rarr;</a></div>';
        }

        return html;
    }

    function performLiveSearch(input) {
        var query = input.value.trim();
        var wrapper = input.closest('.live-search-wrapper');
        var dropdown = wrapper ? wrapper.querySelector('.live-search-dropdown') : null;
        if (!dropdown) return;

        if (query.length < 2) {
            dropdown.classList.remove('show');
            dropdown.innerHTML = '';
            activeDropdown = null;
            activeIndex = -1;
            return;
        }

        dropdown.innerHTML = '<div class="live-search-spinner"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
        dropdown.classList.add('show');
        activeDropdown = dropdown;
        activeIndex = -1;

        fetch('/api/search?q=' + encodeURIComponent(query) + '&limit=12')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                dropdown.innerHTML = buildDropdownHtml(data.results, data.total, query);
            })
            .catch(function () {
                dropdown.innerHTML = '<div class="search-item" style="justify-content:center;color:#dc3545;"><em>Search error</em></div>';
            });
    }

    var debouncedSearch = debounce(function (input) {
        performLiveSearch(input);
    }, 250);

    liveSearchInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            debouncedSearch(this);
        });

        input.addEventListener('focus', function () {
            if (this.value.trim().length >= 2) {
                performLiveSearch(this);
            }
        });

        // Keyboard navigation
        input.addEventListener('keydown', function (e) {
            if (!activeDropdown || !activeDropdown.classList.contains('show')) return;
            var items = activeDropdown.querySelectorAll('.search-item[href]');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                updateActiveItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, -1);
                updateActiveItem(items);
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                items[activeIndex].click();
            } else if (e.key === 'Escape') {
                activeDropdown.classList.remove('show');
                activeDropdown = null;
                activeIndex = -1;
            }
        });
    });

    function updateActiveItem(items) {
        items.forEach(function (el, i) {
            el.classList.toggle('active', i === activeIndex);
        });
        if (activeIndex >= 0 && items[activeIndex]) {
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.live-search-dropdown.show').forEach(function (dd) {
            if (!dd.closest('.live-search-wrapper').contains(e.target)) {
                dd.classList.remove('show');
            }
        });
    });

    // ============================================================
    // Live Search — search results page (replaces page content)
    // ============================================================
    var searchPageInput = document.getElementById('searchPageInput');
    var searchPageResults = document.getElementById('searchPageResults');

    if (searchPageInput && searchPageResults) {
        var pageSearchTimer = null;

        searchPageInput.addEventListener('input', function () {
            var q = this.value.trim();
            clearTimeout(pageSearchTimer);

            if (q.length < 2) {
                return; // don't clear existing server-rendered results
            }

            pageSearchTimer = setTimeout(function () {
                searchPageResults.style.opacity = '0.5';

                fetch('/api/search?q=' + encodeURIComponent(q) + '&limit=40')
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        searchPageResults.style.opacity = '1';
                        renderPageSearchResults(data.results, data.total, q);
                    })
                    .catch(function () {
                        searchPageResults.style.opacity = '1';
                    });
            }, 300);
        });
    }

    function renderPageSearchResults(results, total, query) {
        if (!searchPageResults) return;

        if (!results.length) {
            searchPageResults.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No results found for "' + escapeHtml(query) + '".</div>';
            return;
        }

        // Group by type
        var entries = results.filter(function (r) { return r.type === 'entry'; });
        var files = results.filter(function (r) { return r.type === 'file'; });

        var html = '';

        if (entries.length) {
            html += '<h5 class="mb-3"><i class="bi bi-journal-text me-2"></i>Wiki Entries <span class="badge bg-secondary">' + entries.length + '</span></h5>';
            html += '<div class="list-group mb-4">';
            entries.forEach(function (r) {
                html += '<a href="' + escapeHtml(r.url) + '" class="list-group-item list-group-item-action">'
                    + '<div class="d-flex justify-content-between align-items-start">'
                    + '<div><h6 class="mb-1">' + escapeHtml(r.title) + '</h6>'
                    + (r.summary ? '<p class="mb-1 small text-muted">' + escapeHtml(r.summary) + '</p>' : '')
                    + '</div><span class="badge bg-primary">' + escapeHtml(r.category) + '</span></div></a>';
            });
            html += '</div>';
        }

        if (files.length) {
            html += '<h5 class="mb-3"><i class="bi bi-file-earmark me-2"></i>File Resources <span class="badge bg-secondary">' + files.length + '</span></h5>';
            html += '<div class="row g-3">';
            files.forEach(function (r) {
                var thumbContent;
                if (r.thumb) {
                    thumbContent = '<img src="' + escapeHtml(r.thumb) + '" alt="" style="max-width:100%;max-height:100px;object-fit:contain;" loading="lazy" onerror="this.style.display=\'none\';this.parentElement.innerHTML=\'<i class=\\\'bi ' + escapeHtml(r.icon) + ' text-secondary display-6\\\'></i>\'">';
                } else {
                    thumbContent = '<i class="bi ' + escapeHtml(r.icon) + ' text-secondary display-6"></i>';
                }
                html += '<div class="col-6 col-sm-4 col-md-3 col-lg-2">'
                    + '<a href="' + escapeHtml(r.url) + '" class="text-decoration-none">'
                    + '<div class="card h-100 shadow-sm border-0 entry-card text-center">'
                    + '<div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height:100px;overflow:hidden;">'
                    + thumbContent + '</div>'
                    + '<div class="card-body p-2"><small class="d-block text-truncate">' + escapeHtml(r.title) + '</small>'
                    + '<small class="text-muted">' + escapeHtml(r.category) + '</small></div></div></a></div>';
            });
            html += '</div>';
        }

        if (total > results.length) {
            html += '<div class="alert alert-info mt-3"><i class="bi bi-info-circle me-2"></i>' + (total - results.length) + ' more results. Refine your search.</div>';
        }

        // Update the URL without reload
        var newUrl = '/search?q=' + encodeURIComponent(query);
        history.replaceState(null, '', newUrl);

        searchPageResults.innerHTML = html;
    }

    // ============================================================
    // Live Filter — file category pages (textures, sounds, music)
    // ============================================================
    var fileFilterInput = document.querySelector('.file-filter-input');

    if (fileFilterInput) {
        var fileFilterTimer = null;

        fileFilterInput.addEventListener('input', function () {
            var q = this.value.trim();
            var category = this.getAttribute('data-category');
            clearTimeout(fileFilterTimer);

            fileFilterTimer = setTimeout(function () {
                var url = '/' + category + (q ? '?q=' + encodeURIComponent(q) : '');

                // Fetch the full page and replace content
                var container = fileFilterInput.closest('.container');
                var gridArea = container.querySelector('.row.g-3') || container.querySelector('.list-group');
                if (gridArea) gridArea.classList.add('file-grid-loading');

                fetch(url)
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(html, 'text/html');
                        var newContainer = doc.querySelector('.container.py-4');
                        if (newContainer && container) {
                            var newStats = newContainer.querySelector('.badge.bg-secondary');
                            var oldStats = container.querySelector('.badge.bg-secondary');
                            if (newStats && oldStats) {
                                oldStats.textContent = newStats.textContent;
                            }

                            var newGrid = newContainer.querySelector('.row.g-3') || newContainer.querySelector('.list-group');
                            var newAlert = newContainer.querySelector('.alert.alert-info');

                            var form = container.querySelector('form');
                            var toRemove = [];
                            var sibling = form.closest('.d-flex') ? form.closest('.d-flex').nextElementSibling : form.nextElementSibling;
                            while (sibling) {
                                toRemove.push(sibling);
                                sibling = sibling.nextElementSibling;
                            }
                            toRemove.forEach(function (el) { el.remove(); });

                            var insertAfter = form.closest('.d-flex') || form;
                            if (newAlert) insertAfter.parentNode.appendChild(newAlert);
                            if (newGrid) insertAfter.parentNode.appendChild(newGrid);

                            // Re-append sentinel and loader for infinite scroll
                            var sentinel = document.createElement('div');
                            sentinel.className = 'infinite-scroll-sentinel';
                            insertAfter.parentNode.appendChild(sentinel);

                            var loader = document.createElement('div');
                            loader.className = 'infinite-scroll-loader text-center py-4';
                            loader.style.display = 'none';
                            loader.innerHTML = '<div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div>';
                            insertAfter.parentNode.appendChild(loader);

                            var endEl = document.createElement('div');
                            endEl.className = 'infinite-scroll-end text-center py-3';
                            endEl.style.display = 'none';
                            endEl.innerHTML = '<small class="text-muted"><i class="bi bi-check-circle me-1"></i>All items loaded</small>';
                            insertAfter.parentNode.appendChild(endEl);

                            // Also re-append pagination from new content
                            var newPag = newContainer.querySelector('.pagination-nav');
                            if (newPag) insertAfter.parentNode.appendChild(newPag);

                            // Re-init infinite scroll on the new container
                            if (newGrid && scrollMode === 'infinite') {
                                initInfiniteScroll(newGrid);
                            }

                            // Re-apply scroll mode visibility
                            applyScrollMode();
                        }

                        history.replaceState(null, '', url);
                    })
                    .catch(function () {
                        if (gridArea) gridArea.classList.remove('file-grid-loading');
                    });
            }, 300);
        });
    }

    // ============================================================
    // Scroll Mode Toggle (infinite scroll vs pagination)
    // ============================================================
    var SCROLL_MODE_KEY = 'tools4regnum_scroll_mode';

    function getScrollMode() {
        return localStorage.getItem(SCROLL_MODE_KEY) || 'infinite';
    }

    function setScrollMode(mode) {
        localStorage.setItem(SCROLL_MODE_KEY, mode);
    }

    var scrollMode = getScrollMode();
    var scrollToggleBtn = document.getElementById('scrollModeToggle');
    var scrollToggleIcon = document.getElementById('scrollModeIcon');

    var scrollToggleLabel = document.getElementById('scrollModeLabel');

    function updateToggleIcon() {
        if (!scrollToggleIcon) return;
        if (scrollMode === 'infinite') {
            scrollToggleIcon.className = 'bi bi-arrow-repeat';
            if (scrollToggleLabel) scrollToggleLabel.textContent = scrollToggleLabel.dataset.on || 'Infinite Scrolling: On';
            if (scrollToggleBtn) scrollToggleBtn.classList.add('active');
        } else {
            scrollToggleIcon.className = 'bi bi-123';
            if (scrollToggleLabel) scrollToggleLabel.textContent = scrollToggleLabel.dataset.off || 'Infinite Scrolling: Off';
            if (scrollToggleBtn) scrollToggleBtn.classList.remove('active');
        }
    }

    updateToggleIcon();

    if (scrollToggleBtn) {
        scrollToggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            scrollMode = scrollMode === 'infinite' ? 'pagination' : 'infinite';
            setScrollMode(scrollMode);
            // Reload page to apply the new mode cleanly
            window.location.reload();
        });
    }

    // Apply scroll mode: show/hide infinite scroll elements vs pagination
    function applyScrollMode() {
        var paginationNavs = document.querySelectorAll('.pagination-nav');
        var sentinels = document.querySelectorAll('.infinite-scroll-sentinel');
        var loaders = document.querySelectorAll('.infinite-scroll-loader');
        var endMsgs = document.querySelectorAll('.infinite-scroll-end');

        if (scrollMode === 'pagination') {
            // Show pagination, hide infinite scroll elements
            paginationNavs.forEach(function (el) { el.style.display = ''; });
            sentinels.forEach(function (el) { el.style.display = 'none'; });
            loaders.forEach(function (el) { el.style.display = 'none'; });
            endMsgs.forEach(function (el) { el.style.display = 'none'; });
        } else {
            // Hide pagination, infinite scroll elements managed by initInfiniteScroll
            paginationNavs.forEach(function (el) { el.style.display = 'none'; });
        }
    }

    applyScrollMode();

    // ============================================================
    // Infinite Scroll — using IntersectionObserver
    // ============================================================
    var FILE_BASE_URL = 'https://cor-forum.de/regnum/datengrab/res/';
    var FILE_URL_DIR = { textures: 'TEXTURE', sounds: 'SOUND', music: 'MUSIC' };

    function buildFileUrl(catSlug, filename) {
        var dir = FILE_URL_DIR[catSlug] || 'TEXTURE';
        return FILE_BASE_URL + dir + '/' + encodeURIComponent(filename);
    }

    function renderEntryCard(item) {
        var thumbHtml;
        if (item.thumb) {
            thumbHtml = '<img src="' + escapeHtml(item.thumb) + '" class="card-img-top" alt="' + escapeHtml(item.title) + '" style="height:180px;object-fit:cover;" loading="lazy">';
        } else {
            thumbHtml = '<div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height:180px;">'
                + '<i class="bi ' + escapeHtml(item.category_icon) + ' text-white display-5"></i></div>';
        }

        var favHtml = '';
        if (item._showFav) {
            var favIcon = item.is_favorited ? 'bi-heart-fill' : 'bi-heart';
            favHtml = '<button class="btn btn-sm btn-link text-danger p-0 favorite-btn" data-entry-id="' + item.id + '">'
                + '<i class="bi ' + favIcon + '"></i></button>';
        }

        var summaryHtml = '';
        if (item.summary) {
            var short = item.summary.length > 120 ? item.summary.substring(0, 120) + '…' : item.summary;
            summaryHtml = '<p class="card-text small text-muted">' + escapeHtml(short) + '</p>';
        }

        var col = document.createElement('div');
        col.className = 'col-md-6 col-lg-4';
        col.innerHTML = '<div class="card h-100 shadow-sm border-0 entry-card">'
            + thumbHtml
            + '<div class="card-body">'
            + '<div class="d-flex justify-content-between align-items-start">'
            + '<h5 class="card-title mb-1">' + escapeHtml(item.title) + '</h5>'
            + favHtml
            + '</div>'
            + summaryHtml
            + '</div>'
            + '<div class="card-footer bg-transparent border-0">'
            + '<a href="' + escapeHtml(item.url) + '" class="btn btn-sm btn-outline-primary">More &rarr;</a>'
            + '</div></div>';

        return col;
    }

    function renderTextureCard(item) {
        var col = document.createElement('div');
        col.className = 'col-6 col-sm-4 col-md-3 col-lg-2';
        col.innerHTML = '<a href="/' + escapeHtml(item.category_slug) + '/' + escapeHtml(item.slug) + '" class="text-decoration-none">'
            + '<div class="card h-100 shadow-sm border-0 entry-card text-center">'
            + '<div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height:120px;overflow:hidden;">'
            + '<img src="' + escapeHtml(item.url) + '" alt="' + escapeHtml(item.name) + '" style="max-width:100%;max-height:120px;object-fit:contain;" loading="lazy" onerror="this.style.display=\'none\';this.parentElement.innerHTML=\'<i class=\\\'bi bi-image text-secondary display-6\\\'></i>\'">'
            + '</div>'
            + '<div class="card-body p-2">'
            + '<small class="text-body-emphasis d-block text-truncate" title="' + escapeHtml(item.name) + '">' + escapeHtml(item.name) + '</small>'
            + '<small class="text-muted">#' + item.file_id + '</small>'
            + '</div></div></a>';
        return col;
    }

    function renderAudioItem(item, iconName) {
        var el = document.createElement('div');
        el.className = 'list-group-item d-flex align-items-center gap-3';
        el.innerHTML = '<a href="/' + escapeHtml(item.category_slug) + '/' + escapeHtml(item.slug) + '" class="text-decoration-none text-primary flex-shrink-0">'
            + '<i class="bi bi-' + escapeHtml(iconName) + ' fs-4"></i></a>'
            + '<div class="flex-grow-1 min-w-0">'
            + '<a href="/' + escapeHtml(item.category_slug) + '/' + escapeHtml(item.slug) + '" class="text-decoration-none">'
            + '<strong class="d-block text-truncate">' + escapeHtml(item.name) + '</strong></a>'
            + '<small class="text-muted">#' + item.file_id + ' &middot; ' + escapeHtml(item.extension) + '</small></div>'
            + '<audio controls preload="none" class="flex-shrink-0" style="max-width:250px;height:32px;">'
            + '<source src="' + escapeHtml(item.url) + '" type="audio/ogg"></audio>';
        return el;
    }

    function initInfiniteScroll(container) {
        if (!container) return;

        var url = container.getAttribute('data-infinite-url');
        var currentPage = parseInt(container.getAttribute('data-infinite-page') || '1', 10);
        var totalPages = parseInt(container.getAttribute('data-infinite-total-pages') || '1', 10);
        var type = container.getAttribute('data-infinite-type');
        var query = container.getAttribute('data-infinite-query') || '';
        var catSlug = container.getAttribute('data-category-slug') || '';
        var catIcon = container.getAttribute('data-category-icon') || 'bi-file-text';
        var isLoggedIn = container.getAttribute('data-is-logged-in') === '1';
        var audioIcon = container.getAttribute('data-audio-icon') || 'volume-up';

        if (!url || currentPage >= totalPages) {
            // Already on last page — show end message
            var endEl = container.parentElement.querySelector('.infinite-scroll-end');
            if (endEl && totalPages > 1) endEl.style.display = 'block';
            return;
        }

        var sentinel = container.parentElement.querySelector('.infinite-scroll-sentinel');
        var loader = container.parentElement.querySelector('.infinite-scroll-loader');
        var endMsg = container.parentElement.querySelector('.infinite-scroll-end');

        if (!sentinel) return;

        var loading = false;
        var destroyed = false;

        var observer = new IntersectionObserver(function (entries) {
            if (destroyed) return;
            entries.forEach(function (entry) {
                if (entry.isIntersecting && !loading && currentPage < totalPages) {
                    loadNextPage();
                }
            });
        }, {
            rootMargin: '400px' // Start loading before sentinel is visible
        });

        observer.observe(sentinel);

        function loadNextPage() {
            loading = true;
            if (loader) loader.style.display = 'block';

            var nextPage = currentPage + 1;
            var fetchUrl = url + '?page=' + nextPage;
            if (query) fetchUrl += '&q=' + encodeURIComponent(query);

            fetch(fetchUrl)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.items || data.items.length === 0) {
                        finish();
                        return;
                    }

                    var fragment = document.createDocumentFragment();

                    data.items.forEach(function (item) {
                        var el;
                        if (type === 'entries') {
                            item._showFav = isLoggedIn;
                            item.category_icon = catIcon;
                            el = renderEntryCard(item);
                        } else if (type === 'textures') {
                            el = renderTextureCard(item);
                        } else if (type === 'audio') {
                            el = renderAudioItem(item, audioIcon);
                        }
                        if (el) fragment.appendChild(el);
                    });

                    container.appendChild(fragment);

                    // Bind favorite buttons on new elements
                    if (type === 'entries') {
                        bindFavoriteButtons(container);
                    }

                    currentPage = data.page;
                    container.setAttribute('data-infinite-page', currentPage);

                    if (currentPage >= data.totalPages) {
                        finish();
                    } else {
                        loading = false;
                        if (loader) loader.style.display = 'none';
                    }
                })
                .catch(function (err) {
                    console.error('Infinite scroll load failed:', err);
                    loading = false;
                    if (loader) loader.style.display = 'none';
                });
        }

        function finish() {
            loading = true; // prevent further loads
            destroyed = true;
            observer.disconnect();
            if (loader) loader.style.display = 'none';
            if (endMsg) endMsg.style.display = 'block';
        }
    }

    // Init infinite scroll on all containers (only if in infinite mode)
    if (scrollMode === 'infinite') {
        document.querySelectorAll('.infinite-scroll-container').forEach(function (container) {
            initInfiniteScroll(container);
        });
    }

});
