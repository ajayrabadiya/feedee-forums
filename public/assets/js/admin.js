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

    /* Manual cleanup trigger */
    $('[data-run-cleanup]').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>Running');
        setTimeout(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-play-fill"></i>Run now');
            $('.last-run').text('Just now');
            toast('Cleanup finished: 3 expired posts and 11 media files deleted.');
        }, 1400);
    });
});
