$(function () {
    'use strict';

    var toast = window.ffToast;
    var $admin = $('.admin');

    /* Sidebar (mobile) */
    $('.side-toggle, .side-backdrop').on('click', function () {
        $admin.toggleClass('side-open');
    });

    /* Status helpers */
    var LABELS = {
        pending: ['st-pending', 'Pending'],
        approved: ['st-approved', 'Approved'],
        rejected: ['st-rejected', 'Rejected'],
        open: ['st-open', 'Open'],
        resolved: ['st-resolved', 'Resolved']
    };

    function matchesTab(tr, tab) {
        if (!tab || tab === 'all') return true;
        if (tab === 'featured') return tr.getAttribute('data-featured') === '1';
        return tr.getAttribute('data-status') === tab;
    }

    function activeTab(table) {
        return $(table).closest('.panel').find('.tabs-line .active').data('tab');
    }

    function tableFor($rows) {
        return $rows.closest('table').data('dt');
    }

    /* Status tabs act as a DataTables row filter */
    DataTable.ext.search.push(function (settings, data, index) {
        return matchesTab(settings.aoData[index].nTr, activeTab(settings.nTable));
    });

    /* Bulk selection (current page only) */
    function syncBulk() {
        $('.panel').has('.bulk-bar').each(function () {
            var $panel = $(this);
            var $checks = $panel.find('tbody .row-check');
            var n = $checks.filter(':checked').length;

            $panel.find('.bulk-bar').toggleClass('show', n > 0).find('.bulk-count').text(n);
            $panel.find('.check-all').prop('checked', n > 0 && n === $checks.length);
        });
    }

    function checkedRows() {
        return $('tbody .row-check:checked').closest('tr');
    }

    $('.check-all').on('change', function () {
        $(this).closest('table').find('tbody .row-check').prop('checked', this.checked);
        syncBulk();
    });

    $(document).on('change', '.row-check', syncBulk);

    /* DataTables */
    $('[data-table]').each(function () {
        var table = this;
        var $table = $(table);
        var $panel = $table.closest('.panel');
        var noun = $table.data('noun') || 'rows';
        var compact = $table.is('[data-compact]');
        var orderCol = $table.data('order-col');

        var dt = new DataTable(table, {
            order: orderCol !== undefined ? [[orderCol, 'desc']] : [],
            autoWidth: false,
            pageLength: 10,
            paging: !compact,
            info: !compact,
            columnDefs: [{ targets: 'no-sort', orderable: false }],
            layout: {
                topStart: null,
                topEnd: null,
                bottomStart: compact ? null : 'info',
                bottomEnd: compact ? null : { paging: { firstLast: false } }
            },
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ ' + noun,
                infoEmpty: 'No ' + noun + ' to show',
                infoFiltered: '',
                zeroRecords: $table.data('empty') || 'Nothing to show',
                emptyTable: $table.data('empty') || 'Nothing to show',
                paginate: {
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>'
                }
            }
        });

        $table.data('dt', dt);

        function updateCounts() {
            var nodes = dt.rows().nodes().toArray();
            $panel.find('.tabs-line button').each(function () {
                var tab = $(this).data('tab');
                $(this).find('.count').text(nodes.filter(function (tr) { return matchesTab(tr, tab); }).length);
            });
        }

        dt.on('draw', function () {
            $table.find('.row-check, .check-all').prop('checked', false);
            updateCounts();
            syncBulk();
        });

        updateCounts();

        $panel.find('.tabs-line').on('click', 'button', function () {
            $(this).addClass('active').attr('aria-selected', 'true')
                .siblings().removeClass('active').attr('aria-selected', 'false');
            dt.draw();
        });

        $panel.find('.table-search').on('input', function () {
            dt.search(this.value).draw();
        });

        var initialSearch = $panel.find('.table-search').val();
        if (initialSearch) {
            dt.search(initialSearch).draw();
        }

        $panel.find('.column-filter').on('change', function () {
            dt.column($(this).data('column')).search(this.value).draw();
        });

        $panel.find('.page-length').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });
    });

    function setStatus($rows, status, label) {
        var info = LABELS[status];

        $rows.each(function () {
            var $row = $(this);
            $row.attr('data-status', status);
            $row.find('.status').first().attr('class', 'status ' + info[0]).text(label || info[1]);
            $row.find('.act.approve').prop('disabled', status === 'approved');
            $row.find('.act.reject').prop('disabled', status === 'rejected');
        });

        tableFor($rows).rows($rows).invalidate('dom').draw(false);
    }

    function setFeatured($rows, on) {
        $rows.each(function () {
            var $row = $(this);
            $row.attr('data-featured', on ? '1' : '0');
            $row.find('.act.feature').toggleClass('on', on)
                .attr('aria-pressed', on)
                .find('i').attr('class', on ? 'bi bi-star-fill' : 'bi bi-star');
            $row.find('.feature-flag').remove();
            if (on) {
                $row.find('.status').first().after('<i class="bi bi-star-fill feature-flag" title="Featured" aria-label="Featured"></i>');
            }
        });

        tableFor($rows).rows($rows).invalidate('dom').draw(false);
    }

    /* Server-backed moderation (tables with data-moderate-url); other tables stay as design previews */
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    function sendJson(url, method, payload) {
        return $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            data: JSON.stringify(payload)
        }).fail(function (xhr) {
            var res = xhr.responseJSON || {};
            var firstError = res.errors ? Object.values(res.errors)[0][0] : null;
            toast(firstError || res.message || 'Something went wrong. Please try again.', true);
        });
    }

    function moderate($rows, action, extra) {
        var url = $rows.closest('table').data('moderate-url');

        if (!url) {
            return $.Deferred().resolve({}).promise();
        }

        var ids = $rows.map(function () { return $(this).data('id'); }).get();

        return sendJson(url, 'POST', $.extend({ action: action, ids: ids }, extra || {}))
            .done(function (res) {
                if (res.counts) {
                    var $badge = $('[data-pending-count]');
                    $badge.text(res.counts.pending).prop('hidden', res.counts.pending === 0);
                }
            });
    }

    function applyServerState($rows, posts) {
        posts.forEach(function (post) {
            var $row = $rows.filter('[data-id="' + post.id + '"]');
            setStatus($row, post.status);
            setFeatured($row, post.featured);
        });
    }

    function removeRows($rows) {
        var dt = tableFor($rows);
        $rows.fadeOut(250).promise().done(function () {
            dt.rows($rows).remove().draw(false);
        });
    }

    /* Modals */
    var $target = $();
    var rejectModal = document.getElementById('rejectModal');
    var deleteModal = document.getElementById('deleteModal');

    function openModal(el, $rows) {
        $target = $rows;
        var title = $rows.length === 1
            ? $rows.find('.cell-post strong').text()
            : $rows.length + ' selected posts';
        $(el).find('.modal-post-title').text(title);
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    $(rejectModal).on('show.bs.modal', function () {
        $('#rejectNote').val('');
        $('#rejectConfirm').prop('disabled', false);
    });

    $('#rejectConfirm').on('click', function () {
        var reason = $('#rejectReason').val();
        var note = String($('#rejectNote').val() || '').trim();
        var $rows = $target;
        var n = $rows.length;
        var $btn = $(this).prop('disabled', true);

        moderate($rows, 'reject', { reason: reason, note: note || null }).done(function (res) {
            bootstrap.Modal.getInstance(rejectModal).hide();
            if (res.posts) {
                applyServerState($rows, res.posts);
            } else {
                setStatus($rows, 'rejected');
                setFeatured($rows, false);
            }
            toast(res.message || (n > 1 ? n + ' posts' : 'Post') + ' rejected. Author notified: ' + reason + '.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $('#deleteConfirm').on('click', function () {
        var $rows = $target;
        var n = $rows.length;
        var mode = $(deleteModal).data('mode');

        if (mode === 'report') {
            bootstrap.Modal.getInstance(deleteModal).hide();
            $rows.find('.act.remove, .act.dismiss').prop('disabled', true);
            setStatus($rows, 'resolved', 'Post removed');
            toast('Post and media permanently deleted. Report resolved.');
            return;
        }

        var $btn = $(this).prop('disabled', true);

        moderate($rows, 'delete').done(function (res) {
            bootstrap.Modal.getInstance(deleteModal).hide();
            removeRows($rows);
            toast(res.message || (n > 1 ? n + ' posts' : 'Post') + ' and all media permanently deleted.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    /* Row actions: posts */
    $(document).on('click', '.act.approve', function () {
        var $row = $(this).closest('tr');

        moderate($row, 'approve').done(function (res) {
            if (res.posts) {
                applyServerState($row, res.posts);
            } else {
                setStatus($row, 'approved');
            }
            toast(res.message || 'Post approved and published to the feed.');
        });
    });

    $(document).on('click', '.act.reject', function () {
        openModal(rejectModal, $(this).closest('tr'));
    });

    $(document).on('click', '.act.feature', function () {
        var $row = $(this).closest('tr');
        var on = $row.attr('data-featured') !== '1';

        moderate($row, on ? 'feature' : 'unfeature').done(function (res) {
            if (res.posts) {
                applyServerState($row, res.posts);
            } else {
                if (on && $row.attr('data-status') !== 'approved') {
                    setStatus($row, 'approved');
                }
                setFeatured($row, on);
            }
            toast(res.message || (on ? 'Post featured at the top of the feed.' : 'Post removed from featured.'));
        });
    });

    $(document).on('click', '.act.delete', function () {
        $(deleteModal).data('mode', 'post');
        openModal(deleteModal, $(this).closest('tr'));
    });

    /* Row actions: reports */
    $(document).on('click', '.act.dismiss', function () {
        var $row = $(this).closest('tr');
        $row.find('.act.remove, .act.dismiss').prop('disabled', true);
        setStatus($row, 'resolved', 'Dismissed');
        toast('Report dismissed. The post stays live.');
    });

    $(document).on('click', '.act.remove', function () {
        $(deleteModal).data('mode', 'report');
        openModal(deleteModal, $(this).closest('tr'));
    });

    /* Bulk */
    $('[data-bulk]').on('click', function () {
        var $rows = checkedRows();
        var action = $(this).data('bulk');
        var n = $rows.length;

        if (!n) return;

        if (action === 'approve') {
            moderate($rows, 'approve').done(function (res) {
                if (res.posts) {
                    applyServerState($rows, res.posts);
                } else {
                    setStatus($rows, 'approved');
                }
                toast(res.message || n + ' posts approved.');
            });
        } else if (action === 'feature') {
            moderate($rows, 'feature').done(function (res) {
                if (res.posts) {
                    applyServerState($rows, res.posts);
                } else {
                    setStatus($rows, 'approved');
                    setFeatured($rows, true);
                }
                toast(res.message || n + ' posts featured.');
            });
        } else if (action === 'reject') {
            openModal(rejectModal, $rows);
        } else if (action === 'delete') {
            $(deleteModal).data('mode', 'post');
            openModal(deleteModal, $rows);
        } else if (action === 'dismiss') {
            $rows.find('.act.remove, .act.dismiss').prop('disabled', true);
            setStatus($rows, 'resolved', 'Dismissed');
            toast(n + ' reports dismissed.');
        }
    });

    /* Post approval setting */
    var APPROVAL_HINTS = {
        on: 'On: new posts wait in the Pending tab and only appear in the feed once you approve them.',
        off: 'Off: new posts go live straight away and you can still reject, feature or delete them at any time.'
    };

    $('[data-approval-switch]').on('change', function () {
        var input = this;
        var on = input.checked;
        var url = $(input).data('url');
        var $hint = $(input).closest('.setting-card').find('.setting-hint');

        if (!url) {
            $hint.text(on ? APPROVAL_HINTS.on : APPROVAL_HINTS.off);
            toast(on ? 'Approval turned on. New posts will wait for review.' : 'Approval turned off. New posts go live instantly.');
            return;
        }

        input.disabled = true;

        sendJson(url, 'PUT', { enabled: on }).done(function (res) {
            $hint.text(res.enabled ? APPROVAL_HINTS.on : APPROVAL_HINTS.off);
            toast(res.message);
        }).fail(function () {
            input.checked = !on;
        }).always(function () {
            input.disabled = false;
        });
    });

    /* Settings: only show SMTP fields when SMTP is selected */
    $('[data-smtp-toggle]').on('change', function () {
        $('.smtp-fields').prop('hidden', this.value !== 'smtp');
    });

    /* Manual cleanup trigger */
    $('[data-run-cleanup]').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>Running');
        setTimeout(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-play-fill"></i>Run now');
            $('.last-run').text('Just now');
            toast('Cleanup finished: 3 expired posts and 11 media files deleted.');
        }, 1400);
    });

    /* Support analytics: date filters, daily/weekly chart and live refresh */
    $('[data-support-analytics]').each(function () {
        var $section = $(this);
        var url = $section.data('url');
        var pollMs = Math.max(10, Number($section.data('poll')) || 15) * 1000;
        var $chart = $section.find('[data-support-chart]');
        var $custom = $section.find('[data-support-custom]');
        var $live = $section.find('[data-live-status]');
        var $pages = $section.find('[data-support-pages]');
        var number = new Intl.NumberFormat('en');
        var initial = JSON.parse($section.find('[data-support-initial]').text());
        var state = { range: initial.range.key, group: initial.group, from: null, to: null };
        var lastGood = $.extend({}, state);
        var request = null;
        var timer = null;
        var stopped = false;

        function clicks(n) {
            return number.format(n) + (n === 1 ? ' click' : ' clicks');
        }

        function formatDate(ymd) {
            var parts = ymd.split('-');
            return new Date(parts[0], parts[1] - 1, parts[2])
                .toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function setLive(mode, text) {
            $live.toggleClass('is-offline', mode === 'offline').toggleClass('is-stopped', mode === 'stopped');
            $live.find('[data-live-text]').text(text);
        }

        function syncControls() {
            $section.find('[data-range]').each(function () {
                var on = $(this).data('range') === state.range;
                $(this).toggleClass('active', on).attr('aria-pressed', on);
            });
            $section.find('[data-group]').each(function () {
                var on = $(this).data('group') === state.group;
                $(this).toggleClass('active', on).attr('aria-pressed', on);
            });
            $custom.prop('hidden', state.range !== 'custom');
        }

        function renderChart(data) {
            var bars = data.chart;
            var max = Math.max.apply(null, [1].concat(bars.map(function (bar) { return bar.clicks; })));
            var dense = bars.length > 14;
            var labelEvery = dense ? Math.ceil(bars.length / 10) : 1;
            var unit = data.group === 'week' ? 'week' : 'day';
            var peak = bars.reduce(function (best, bar) { return bar.clicks > best.clicks ? bar : best; }, bars[0]);

            $chart.toggleClass('is-dense', dense).empty();

            bars.forEach(function (bar, i) {
                var $fill = $('<span class="bar-fill">').css('height', Math.round(bar.clicks / max * 90) + '%');

                if (!dense) {
                    $fill.attr('data-value', bar.clicks);
                }

                $('<div class="bar">')
                    .attr('title', bar.title + ': ' + clicks(bar.clicks))
                    .append($('<div class="bar-track">').append($fill))
                    .append($('<small>').text(i % labelEvery === 0 ? bar.label : ''))
                    .appendTo($chart);
            });

            $chart.attr('aria-label', 'Bar chart of Support button clicks per ' + unit + ': ' + (dense
                ? clicks(data.range.clicks) + ' in total, busiest ' + unit + ' ' + peak.title + ' with ' + clicks(peak.clicks)
                : bars.map(function (bar) { return bar.title + ' ' + clicks(bar.clicks); }).join('; ')));

            $section.find('[data-support-empty]').prop('hidden', data.range.clicks > 0);
        }

        function renderPages(data) {
            $pages.empty();

            data.topPages.forEach(function (page) {
                var share = data.range.clicks ? Math.round(page.clicks / data.range.clicks * 100) : 0;

                $('<li>')
                    .append('<span class="stat-icon ic-orange"><i class="bi bi-link-45deg"></i></span>')
                    .append($('<div>')
                        .append($('<a target="_blank" rel="noopener">').attr('href', page.path)
                            .append($('<strong class="page-path">').text(page.path)))
                        .append($('<p>').text(clicks(page.clicks) + ' · ' + share + '% of this range')))
                    .appendTo($pages);
            });

            $section.find('[data-support-pages-empty]').prop('hidden', data.topPages.length > 0);
            $section.find('[data-support-pages-summary]').text('Top pages · ' + data.range.label);
        }

        function render(data) {
            var dates = data.range.from === data.range.to
                ? formatDate(data.range.from)
                : formatDate(data.range.from) + ' – ' + formatDate(data.range.to);

            $section.find('[data-support-stat]').each(function () {
                var text = number.format(data.stats[$(this).data('support-stat')]);

                if ($(this).text() !== text) {
                    $(this).text(text).removeClass('is-updated');
                    void this.offsetWidth; // restart the highlight animation
                    $(this).addClass('is-updated');
                }
            });

            $section.find('[data-support-summary]').text(data.range.label + ' · ' + dates + ' · ' + clicks(data.range.clicks));

            if (data.links) {
                data.links.platforms.forEach(function (platform) {
                    var $item = $section.find('[data-links-today-platform="' + platform.key + '"]');
                    $item.find('strong').text(number.format(platform.clicks));
                    $item.children('span').text(platform.clicks === 1 ? 'click' : 'clicks');
                });
                var $total = $section.find('[data-links-today-total]');
                $total.find('strong').text(number.format(data.links.total));
                $total.children('span').text(data.links.total === 1 ? 'support link click' : 'support link clicks');
            }
            renderChart(data);
            renderPages(data);
            setLive('live', 'Live · updated ' + new Date(data.updatedAt).toLocaleTimeString());
        }

        function schedule() {
            clearTimeout(timer);

            if (!stopped) {
                timer = setTimeout(load, pollMs);
            }
        }

        function load() {
            clearTimeout(timer);

            if (stopped || document.hidden) {
                return;
            }

            if (request) {
                request.abort();
            }

            var params = { range: state.range, group: state.group };

            if (state.range === 'custom') {
                params.from = state.from;
                params.to = state.to;
            }

            request = $.ajax({
                url: url,
                data: params,
                dataType: 'json',
                headers: { Accept: 'application/json' }
            }).done(function (data) {
                lastGood = $.extend({}, state);
                render(data);
            }).fail(function (xhr, status) {
                if (status === 'abort') return;

                if (xhr.status === 401 || xhr.status === 403 || xhr.status === 419) {
                    stopped = true;
                    setLive('stopped', 'Paused · reload the page to resume');
                    return;
                }

                if (xhr.status === 422) {
                    var res = xhr.responseJSON || {};
                    toast(res.errors ? Object.values(res.errors)[0][0] : (res.message || 'Please check the dates.'), true);
                    state = $.extend({}, lastGood);
                    syncControls();
                    return;
                }

                setLive('offline', 'Connection lost · retrying');
            }).always(function (a, status) {
                if (status !== 'abort') {
                    request = null;
                    schedule();
                }
            });
        }

        function applyCustomDates() {
            var from = $custom.find('[name="from"]').val();
            var to = $custom.find('[name="to"]').val();

            if (!from || !to) {
                toast('Choose both a start and an end date.', true);
                return false;
            }

            if (from > to) {
                toast('The end date must be on or after the start date.', true);
                return false;
            }

            state.from = from;
            state.to = to;
            return true;
        }

        $section.find('[data-range]').on('click', function () {
            var range = $(this).data('range');

            if (range === 'custom' && !applyCustomDates()) {
                return;
            }

            state.range = range;
            syncControls();
            load();
        });

        $section.find('[data-group]').on('click', function () {
            state.group = $(this).data('group');
            syncControls();
            load();
        });

        $custom.on('submit', function (e) {
            e.preventDefault();

            if (applyCustomDates()) {
                load();
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                load();
            }
        });

        render(initial);
        syncControls();
        schedule();
    });

    /* Support link analytics page: filters, charts, country report, activity log and CSV export */
    $('[data-support-links]').each(function () {
        var $page = $(this);
        var page = this;
        var dataUrl = $page.data('url');
        var activityUrl = $page.data('activity-url');
        var exportUrl = $page.data('export-url');
        var report = JSON.parse($page.find('[data-links-initial]').text());
        var platforms = report.platforms.map(function (p) { return { key: p.key, label: p.label, color: p.color }; });
        var number = new Intl.NumberFormat('en');
        var $custom = $page.find('[data-links-custom]');
        var $chart = $page.find('[data-links-chart]');
        var $countryTable = $page.find('[data-links-countries]');
        var $activity = $page.find('[data-links-activity]');
        var $tip = $('<div class="chart-tip" role="tooltip" hidden></div>').appendTo('body');
        var isCustom = report.range.key === 'custom';
        var state = { range: report.range.key, group: report.group, from: isCustom ? report.range.from : null, to: isCustom ? report.range.to : null };
        var lastGood = $.extend({}, state);
        var request = null;
        var activityRequest = null;
        var activityPage = 1;
        var activityLastPage = 1;
        var countryTable = null;
        var FLAG_URL = 'https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/flags/4x3/';

        function clicks(n) {
            return number.format(n) + (n === 1 ? ' click' : ' clicks');
        }

        function formatDate(ymd) {
            var parts = ymd.split('-');
            return new Date(parts[0], parts[1] - 1, parts[2])
                .toLocaleDateString('en', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function platformFor(key) {
            return platforms.filter(function (p) { return p.key === key; })[0] || { key: key, label: key, color: '#999' };
        }

        function params(extra) {
            var p = { range: state.range, group: state.group };

            if (state.range === 'custom') {
                p.from = state.from;
                p.to = state.to;
            }

            return $.extend(p, extra || {});
        }

        /* Flags come from the flag-icons set; a globe stands in for unknown countries */
        function flag(code) {
            if (!code) {
                return $('<span class="flag flag-unknown" aria-hidden="true"><i class="bi bi-globe2"></i></span>');
            }

            return $('<img class="flag" width="20" height="15" alt="" loading="lazy">').attr('src', FLAG_URL + code.toLowerCase() + '.svg');
        }

        page.addEventListener('error', function (e) {
            if (e.target.tagName === 'IMG' && e.target.classList.contains('flag')) {
                $(e.target).replaceWith(flag(null));
            }
        }, true);

        function countryCell(country) {
            return $('<span class="cell-country">').append(flag(country.code)).append($('<span>').text(country.name));
        }

        function dot(color) {
            return $('<span class="pf-dot">').css('--pf', color);
        }

        /* Change against the previous period of the same length */
        function delta($el, current, previous, noun) {
            var compare = 'vs ' + report.range.compareLabel;
            var title = number.format(previous) + ' ' + noun + ' in ' + report.range.compareLabel;

            if (!previous) {
                $el.attr('class', current ? 'trend up' : 'trend flat').text(current ? 'New' : 'No change');
            } else {
                var pct = Math.round((current - previous) / previous * 100);
                $el.attr('class', 'trend ' + (pct > 0 ? 'up' : (pct < 0 ? 'down' : 'flat')))
                    .text((pct > 0 ? '+' : (pct < 0 ? '−' : '')) + Math.abs(pct) + '%');
            }

            $el.attr({ title: title, 'aria-label': $el.text() + ' ' + compare });
        }

        /* Tooltips for chart bars and country rows */
        function tipContent(title, values, total) {
            var $content = $('<div>').append($('<strong>').text(title));

            platforms.forEach(function (p) {
                var value = values[p.key] || 0;
                var share = total ? Math.round(value / total * 100) : 0;
                $('<div class="tip-line">').append(dot(p.color)).append($('<span>').text(p.label))
                    .append($('<b>').text(number.format(value) + (total ? ' · ' + share + '%' : ''))).appendTo($content);
            });

            $('<div class="tip-line tip-total">').append($('<span>').text('Total')).append($('<b>').text(number.format(total))).appendTo($content);

            return $content;
        }

        function showTip(el) {
            var info = $(el).data('tip');
            if (!info) return;

            $tip.empty().append(tipContent(info.title, info.values, info.total)).prop('hidden', false);

            var rect = el.getBoundingClientRect();
            var left = rect.left + window.scrollX + rect.width / 2 - $tip.outerWidth() / 2;
            var maxLeft = window.scrollX + document.documentElement.clientWidth - $tip.outerWidth() - 8;

            $tip.css({
                left: Math.max(window.scrollX + 8, Math.min(left, maxLeft)),
                top: rect.top + window.scrollY - $tip.outerHeight() - 10
            });
        }

        $page.on('mouseenter focus', '.has-tip', function () { showTip(this); })
            .on('mouseleave blur', '.has-tip', function () { $tip.prop('hidden', true); });

        function renderCards(data) {
            $page.find('[data-stat="clicks"]').text(number.format(data.totals.clicks));
            delta($page.find('[data-delta="clicks"]'), data.totals.clicks, data.totals.previousClicks, 'clicks');
            $page.find('[data-stat="visitors"]').text(number.format(data.totals.visitors));
            delta($page.find('[data-delta="visitors"]'), data.totals.visitors, data.totals.previousVisitors, 'visitors');

            data.platforms.forEach(function (p) {
                $page.find('[data-platform-clicks="' + p.key + '"]').text(number.format(p.clicks));
                $page.find('[data-platform-share="' + p.key + '"]').text('· ' + p.share + '% of clicks');
                delta($page.find('[data-platform-delta="' + p.key + '"]'), p.clicks, p.previousClicks, 'clicks');
            });

            $.each(data.periods, function (key, period) {
                var $card = $page.find('[data-period="' + key + '"]');
                $card.find('[data-period-total]').text(number.format(period.total));
                $card.find('[data-period-since]').text(period.since);
                $.each(period.platforms, function (platform, value) {
                    $card.find('[data-period-platform="' + platform + '"]').text(number.format(value));
                });
            });

            $page.find('[data-filtered-total]').text(number.format(data.filtered.total));
            data.filtered.reasons.forEach(function (reason) {
                $page.find('[data-filtered-reason="' + reason.key + '"]').text(number.format(reason.clicks));
            });
        }

        function renderActivityChart(data) {
            var buckets = data.chart;
            var max = Math.max.apply(null, [1].concat(buckets.map(function (b) { return b.total; })));
            var dense = buckets.length > 14;
            var labelEvery = dense ? Math.ceil(buckets.length / 10) : 1;
            var unit = { day: 'day', week: 'week', month: 'month' }[data.group] || 'day';
            var peak = buckets.reduce(function (best, b) { return b.total > best.total ? b : best; }, buckets[0]);

            $chart.toggleClass('is-dense', dense).empty();

            buckets.forEach(function (bucket, i) {
                var $stack = $('<span class="bar-stack">').toggleClass('is-zero', bucket.total === 0)
                    .css('height', Math.round(bucket.total / max * 90) + '%');

                platforms.forEach(function (p) {
                    var value = bucket.platforms[p.key] || 0;
                    if (value) {
                        $('<span class="bar-seg">').css({ flexGrow: value, background: p.color }).appendTo($stack);
                    }
                });

                if (!dense && bucket.total) {
                    $stack.attr('data-value', bucket.total);
                }

                $('<div class="bar has-tip" tabindex="0">')
                    .data('tip', { title: bucket.title, values: bucket.platforms, total: bucket.total })
                    .attr('aria-label', bucket.title + ': ' + platforms.map(function (p) { return p.label + ' ' + (bucket.platforms[p.key] || 0); }).join(', ') + ', total ' + bucket.total)
                    .append($('<div class="bar-track">').append($stack))
                    .append($('<small>').text(i % labelEvery === 0 ? bucket.label : ''))
                    .appendTo($chart);
            });

            $chart.attr('aria-label', 'Stacked bar chart of support link clicks per ' + unit + ': ' + clicks(data.totals.clicks) + ' in total'
                + (peak && peak.total ? ', busiest ' + unit + ' ' + peak.title + ' with ' + clicks(peak.total) : ''));
            $page.find('[data-links-chart-empty]').prop('hidden', data.totals.clicks > 0);
            $page.find('[data-activity-summary]').text(clicks(data.totals.clicks) + ' per ' + unit + ', split by platform');
        }

        function renderComparison(data) {
            var total = data.totals.clicks;
            var acc = 0;
            var stops = [];
            var $list = $page.find('[data-links-compare]').empty();

            data.platforms.forEach(function (p) {
                if (total && p.clicks) {
                    var start = acc / total * 100;
                    acc += p.clicks;
                    stops.push(p.color + ' ' + start + '% ' + (acc / total * 100) + '%');
                }

                $('<li>')
                    .append($('<div class="compare-head">').append(dot(p.color)).append($('<span>').text(p.label))
                        .append($('<b>').text(clicks(p.clicks))).append($('<em>').text(p.share + '%')))
                    .append($('<div class="share-bar">').append($('<span>').css({ width: p.share + '%', background: p.color })))
                    .append($('<small>').text(number.format(p.visitors) + (p.visitors === 1 ? ' visitor' : ' visitors')))
                    .appendTo($list);
            });

            var leader = data.platforms.slice().sort(function (a, b) { return b.clicks - a.clicks; })[0];
            if (total && leader && data.platforms.filter(function (p) { return p.clicks === leader.clicks; }).length === 1) {
                $('<li class="compare-leader">').text(leader.label + ' gets the most clicks in this period.').appendTo($list);
            }

            $page.find('[data-links-donut]')
                .css('background', total ? 'conic-gradient(' + stops.join(', ') + ')' : '')
                .attr('aria-label', 'Platform share of clicks: ' + data.platforms.map(function (p) { return p.label + ' ' + p.share + '%'; }).join(', '))
                .find('[data-donut-total]').text(number.format(total));
        }

        function renderCountryChart(data) {
            var top = data.countries.slice(0, 8);
            var max = top.length ? top[0].total : 1;
            var $box = $page.find('[data-links-country-chart]').empty();

            top.forEach(function (country) {
                var $fill = $('<span class="country-fill">').css('width', Math.max(2, country.total / max * 100) + '%');

                platforms.forEach(function (p) {
                    var value = country.platforms[p.key] || 0;
                    if (value) {
                        $('<span>').css({ flexGrow: value, background: p.color }).appendTo($fill);
                    }
                });

                $('<div class="country-row has-tip" tabindex="0">')
                    .data('tip', { title: country.name, values: country.platforms, total: country.total })
                    .attr('aria-label', country.name + ': ' + platforms.map(function (p) { return p.label + ' ' + (country.platforms[p.key] || 0); }).join(', ') + ', total ' + country.total)
                    .append(countryCell(country))
                    .append($('<span class="country-track">').append($fill))
                    .append($('<b>').text(number.format(country.total)))
                    .appendTo($box);
            });

            $page.find('[data-links-country-empty]').prop('hidden', top.length > 0);
        }

        function renderCountryTable(data) {
            if (!countryTable) {
                var columns = [{
                    data: 'name',
                    render: function (name, type, row) {
                        return type === 'display' ? countryCell(row)[0].outerHTML : name;
                    }
                }];

                platforms.forEach(function (p) {
                    columns.push({ data: function (row) { return row.platforms[p.key] || 0; }, render: function (v, type) { return type === 'display' ? number.format(v) : v; } });
                });

                columns.push({ data: 'total', render: function (v, type) { return type === 'display' ? '<strong>' + number.format(v) + '</strong>' : v; } });
                columns.push({ data: 'share', render: function (v, type) { return type === 'display' ? v.toFixed(1) + '%' : v; } });

                countryTable = new DataTable($countryTable[0], {
                    data: data.countries,
                    columns: columns,
                    order: [[platforms.length + 1, 'desc']],
                    autoWidth: false,
                    pageLength: 10,
                    layout: { topStart: null, topEnd: null, bottomStart: 'info', bottomEnd: { paging: { firstLast: false } } },
                    language: {
                        info: 'Showing _START_ to _END_ of _TOTAL_ countries',
                        infoEmpty: 'No countries to show',
                        infoFiltered: '',
                        zeroRecords: 'No countries match.',
                        emptyTable: 'No support link clicks in this period.',
                        paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
                    }
                });
            } else {
                countryTable.clear().rows.add(data.countries).draw();
            }

            var known = data.countries.filter(function (c) { return c.code; }).length;
            $page.find('[data-country-summary]').text(known + (known === 1 ? ' country' : ' countries') + ' · sorted by total clicks');
        }

        $page.find('[data-country-search]').on('input', function () {
            if (countryTable) countryTable.search(this.value).draw();
        });

        function render(data) {
            report = data;
            $page.find('[data-links-summary]').text(data.range.label + ' · '
                + (data.range.from === data.range.to ? formatDate(data.range.from) : formatDate(data.range.from) + ' – ' + formatDate(data.range.to))
                + ' · times in ' + data.range.timezone);
            renderCards(data);
            renderActivityChart(data);
            renderComparison(data);
            renderCountryChart(data);
            renderCountryTable(data);
        }

        /* Recent activity (server-side pages) */
        function renderActivity(res) {
            var $body = $activity.find('tbody').empty();

            if (!res.data.length) {
                $('<tr><td colspan="6" class="dt-empty"></td></tr>').find('td').text('No support link clicks in this period.').end().appendTo($body);
            }

            res.data.forEach(function (row) {
                var p = platformFor(row.platform);
                var $clickedFrom = row.page
                    ? $('<a target="_blank" rel="noopener" class="cell-email">').attr('href', row.page).text(row.page)
                    : $('<span class="muted">').text('Unknown');

                $('<tr>')
                    .append($('<td class="nowrap muted">').text(row.time))
                    .append($('<td>').append(countryCell(row.country)))
                    .append($('<td>').append($('<span class="pf-badge">').append(dot(p.color)).append($('<span>').text(p.label))))
                    .append($('<td>').append($clickedFrom))
                    .append($('<td class="nowrap">').append($('<span>').text(row.visitorType)).append($('<small class="muted">').text(' · #' + row.visitor)))
                    .append($('<td>').append(row.filtered
                        ? [$('<span class="status st-rejected">').text('Not counted'), $('<small class="status-reason">').text(row.filtered)]
                        : $('<span class="status st-approved">').text('Counted')))
                    .appendTo($body);
            });

            activityPage = res.meta.page;
            activityLastPage = res.meta.lastPage;
            $page.find('[data-activity-info]').text(res.meta.total
                ? 'Showing ' + number.format(res.meta.from) + ' to ' + number.format(res.meta.to) + ' of ' + clicks(res.meta.total)
                : 'No clicks to show');
            $page.find('[data-activity-page-label]').text(activityPage + ' / ' + activityLastPage);
            $page.find('[data-activity-page="prev"]').prop('disabled', activityPage <= 1).parent().toggleClass('disabled', activityPage <= 1);
            $page.find('[data-activity-page="next"]').prop('disabled', activityPage >= activityLastPage).parent().toggleClass('disabled', activityPage >= activityLastPage);
        }

        function loadActivity(pageNumber) {
            if (activityRequest) activityRequest.abort();

            $activity.closest('.panel').addClass('is-loading');

            activityRequest = $.ajax({
                url: activityUrl,
                data: params({ page: pageNumber }),
                dataType: 'json',
                headers: { Accept: 'application/json' }
            }).done(renderActivity).fail(function (xhr, status) {
                if (status === 'abort') return;
                toast('Could not load the recent activity. Please try again.', true);
            }).always(function (a, status) {
                if (status !== 'abort') {
                    activityRequest = null;
                    $activity.closest('.panel').removeClass('is-loading');
                }
            });
        }

        $page.find('[data-activity-page]').on('click', function () {
            var next = $(this).data('activity-page') === 'next' ? activityPage + 1 : activityPage - 1;
            if (next >= 1 && next <= activityLastPage) loadActivity(next);
        });

        /* Filters */
        function syncControls() {
            $page.find('[data-range]').each(function () {
                var on = $(this).data('range') === state.range;
                $(this).toggleClass('active', on).attr('aria-pressed', on);
            });
            $page.find('[data-group]').each(function () {
                var on = $(this).data('group') === state.group;
                $(this).toggleClass('active', on).attr('aria-pressed', on);
            });
            $custom.prop('hidden', state.range !== 'custom');
        }

        function syncLinks() {
            $page.find('[data-export]').each(function () {
                this.href = exportUrl + '?' + $.param(params({ report: $(this).data('export') }));
            });
            window.history.replaceState(null, '', window.location.pathname + '?' + $.param(params()));
        }

        function load(withActivity) {
            if (request) request.abort();

            $page.addClass('is-loading');

            request = $.ajax({
                url: dataUrl,
                data: params(),
                dataType: 'json',
                headers: { Accept: 'application/json' }
            }).done(function (data) {
                lastGood = $.extend({}, state);
                render(data);
                syncLinks();
                if (withActivity) loadActivity(1);
            }).fail(function (xhr, status) {
                if (status === 'abort') return;

                var res = xhr.responseJSON || {};

                if (xhr.status === 422) {
                    toast(res.errors ? Object.values(res.errors)[0][0] : (res.message || 'Please check the dates.'), true);
                } else if (xhr.status === 401 || xhr.status === 403 || xhr.status === 419) {
                    toast('Your session has expired. Reload the page to continue.', true);
                } else {
                    toast('Could not load the analytics. Please try again.', true);
                }

                state = $.extend({}, lastGood);
                syncControls();
            }).always(function (a, status) {
                if (status !== 'abort') {
                    request = null;
                    $page.removeClass('is-loading');
                }
            });
        }

        function applyCustomDates() {
            var from = $custom.find('[name="from"]').val();
            var to = $custom.find('[name="to"]').val();

            if (!from || !to) {
                toast('Choose both a start and an end date.', true);
                return false;
            }

            if (from > to) {
                toast('The end date must be on or after the start date.', true);
                return false;
            }

            state.from = from;
            state.to = to;
            return true;
        }

        $page.find('[data-range]').on('click', function () {
            var range = $(this).data('range');

            if (range === 'custom' && !applyCustomDates()) return;

            state.range = range;
            syncControls();
            load(true);
        });

        $page.find('[data-group]').on('click', function () {
            state.group = $(this).data('group');
            syncControls();
            load(false);
        });

        $custom.on('submit', function (e) {
            e.preventDefault();
            if (applyCustomDates()) load(true);
        });

        render(report);
        syncControls();
        $page.find('[data-export]').each(function () {
            this.href = exportUrl + '?' + $.param(params({ report: $(this).data('export') }));
        });
        loadActivity(1);
    });
});
