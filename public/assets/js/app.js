/**
 * Tools4Regnum — Frontend JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // Favorite toggle (AJAX)
    // ============================================================
    document.querySelectorAll('.favorite-btn').forEach(function (btn) {
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
        var fileContentArea = fileFilterInput.closest('.container');
        var fileGridSelector = '.row.g-3, .list-group';

        fileFilterInput.addEventListener('input', function () {
            var q = this.value.trim();
            var category = this.getAttribute('data-category');
            clearTimeout(fileFilterTimer);

            fileFilterTimer = setTimeout(function () {
                // Navigate with query parameter (simple approach — reloads content via server)
                var url = '/' + category + (q ? '?q=' + encodeURIComponent(q) : '');

                // Use fetch to get the page HTML and swap in the content
                var container = fileFilterInput.closest('.container');
                var gridArea = container.querySelector('.row.g-3') || container.querySelector('.list-group');
                var paginationArea = container.querySelector('nav[aria-label]'); // pagination nav if any
                var alertArea = container.querySelector('.alert.alert-info');

                // Add loading state
                if (gridArea) gridArea.classList.add('file-grid-loading');

                fetch(url)
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        // Parse the HTML and extract the content
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(html, 'text/html');
                        var newContainer = doc.querySelector('.container.py-4');
                        if (newContainer && container) {
                            // Preserve the header/breadcrumb area, replace from the items onward
                            var newStats = newContainer.querySelector('.badge.bg-secondary');
                            var oldStats = container.querySelector('.badge.bg-secondary');
                            if (newStats && oldStats) {
                                oldStats.textContent = newStats.textContent;
                            }

                            // Replace the grid/list + pagination area
                            var newGrid = newContainer.querySelector('.row.g-3') || newContainer.querySelector('.list-group');
                            var newPag = newContainer.querySelector('nav.mt-4');
                            var newAlert = newContainer.querySelector('.alert.alert-info');

                            // Remove old content after the filter form
                            var form = container.querySelector('form');
                            var toRemove = [];
                            var sibling = form.closest('.d-flex') ? form.closest('.d-flex').nextElementSibling : form.nextElementSibling;
                            while (sibling) {
                                toRemove.push(sibling);
                                sibling = sibling.nextElementSibling;
                            }
                            toRemove.forEach(function (el) { el.remove(); });

                            // Append new content
                            var insertAfter = form.closest('.d-flex') || form;
                            if (newAlert) insertAfter.parentNode.appendChild(newAlert);
                            if (newGrid) insertAfter.parentNode.appendChild(newGrid);
                            if (newPag) insertAfter.parentNode.appendChild(newPag);
                        }

                        // Update URL
                        history.replaceState(null, '', url);
                    })
                    .catch(function () {
                        if (gridArea) gridArea.classList.remove('file-grid-loading');
                    });
            }, 300);
        });
    }

});
