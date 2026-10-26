$(function () {
    'use strict';

    var $body = $('body');

    $('.year').text(new Date().getFullYear());

    /* Toast */
    function toast(message, isError) {
        var $toast = $('.ff-toast');

        if (!$toast.length) {
            $toast = $('<div class="ff-toast" role="status" aria-live="polite"><i></i><span></span></div>').appendTo($body);
        }

        $toast.toggleClass('is-error', !!isError);
        $toast.find('i').attr('class', isError ? 'bi bi-exclamation-circle-fill' : 'bi bi-check-circle-fill');
        $toast.find('span').text(message);
        $toast.addClass('show');

        clearTimeout($toast.data('timer'));
        $toast.data('timer', setTimeout(function () {
            $toast.removeClass('show');
        }, 2800));
    }

    window.ffToast = toast;

    /* Mobile navigation */
    $('.nav-toggle').on('click', function () {
        var open = $('.site-header').toggleClass('nav-open').hasClass('nav-open');
        $(this).attr('aria-expanded', open).find('i').attr('class', open ? 'bi bi-x-lg' : 'bi bi-list');
    });

    /* Feed layout (list / grid) */
    var $feed = $('#feed');
    var $viewButtons = $('.view-toggle [data-view]');

    function setFeedView(view, fromUser) {
        var root = document.documentElement;

        if (view === 'grid') {
            root.setAttribute('data-feed-view', 'grid');
        } else {
            root.removeAttribute('data-feed-view');
        }

        $viewButtons.each(function () {
            var on = $(this).data('view') === view;
            $(this).toggleClass('active', on).attr('aria-pressed', on);
        });

        if (!fromUser) return;

        try {
            localStorage.setItem('ff-feed-view', view);
        } catch (e) {}

        $feed.removeClass('is-switching');
        void $feed[0].offsetWidth; // force reflow so the animation restarts
        $feed.addClass('is-switching');
        clearTimeout($feed.data('timer'));
        $feed.data('timer', setTimeout(function () {
            $feed.removeClass('is-switching');
        }, 400));
    }

    if ($feed.length) {
        setFeedView(document.documentElement.getAttribute('data-feed-view') === 'grid' ? 'grid' : 'list', false);

        $viewButtons.on('click', function () {
            if (!$(this).hasClass('active')) {
                setFeedView($(this).data('view'), true);
            }
        });
    }

    /* Community stats: only real numbers from the server are shown */
    var $siteStats = $('[data-site-stats]');

    if ($siteStats.length) {
        $.getJSON('/api/stats').done(function (data) {
            var format = new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 });
            var shown = 0;

            $siteStats.find('[data-stat]').each(function () {
                var value = data[$(this).data('stat')];

                if (typeof value === 'number' && value >= 0) {
                    $(this).text(format.format(value));
                    shown++;
                } else {
                    $(this).parent().remove();
                }
            });

            $siteStats.prop('hidden', shown === 0);
        });
    }

    /* Copy to clipboard */
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        var $tmp = $('<textarea readonly>').val(text).css({ position: 'fixed', opacity: 0 }).appendTo($body);
        $tmp[0].select();
        document.execCommand('copy');
        $tmp.remove();
        return Promise.resolve();
    }

    $(document).on('click', '[data-copy]', function (e) {
        e.preventDefault();
        var $btn = $(this);
        copyText(String($btn.data('copy'))).then(function () {
            toast($btn.data('copy-msg') || 'Copied to clipboard');
        });
    });

    /* Single post gallery */
    $('.gallery').each(function () {
        var $gallery = $(this);
        var $main = $gallery.find('.gallery-main img');
        var $thumbs = $gallery.find('.gallery-thumbs button');
        var $count = $gallery.find('.gallery-count');
        var index = 0;

        function show(i) {
            index = (i + $thumbs.length) % $thumbs.length;
            var $thumb = $thumbs.eq(index);

            $main.attr({ src: $thumb.data('full'), alt: $thumb.find('img').attr('alt') });
            $thumbs.removeClass('active').removeAttr('aria-current');
            $thumb.addClass('active').attr('aria-current', 'true');
            $count.text((index + 1) + ' / ' + $thumbs.length);
        }

        $thumbs.on('click', function () { show($thumbs.index(this)); });
        $gallery.find('.gallery-prev').on('click', function () { show(index - 1); });
        $gallery.find('.gallery-next').on('click', function () { show(index + 1); });
    });

    /* Report post */
    $('#reportForm').on('submit', function (e) {
        e.preventDefault();

        if (!$(this).find('input[name="reason"]:checked').length) {
            toast('Please choose a reason for your report.', true);
            return;
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('reportModal')).hide();
        this.reset();
        toast('Thanks. A moderator will review this post shortly.');
    });

    /* Character counters */
    $('[data-counter]').each(function () {
        var $field = $(this);
        var $out = $($field.data('counter'));
        var max = $field.attr('maxlength');

        function update() { $out.text($field.val().length + ' / ' + max); }

        $field.on('input', update);
        update();
    });

    /* Fields that must not contain links (pattern shared with the server-side NoUrls rule) */
    $('[data-no-urls]').each(function () {
        var $field = $(this);
        var pattern = new RegExp($field.data('no-urls'), 'i');
        var message = $field.data('no-urls-message');
        var $error = $('#' + $field.attr('aria-describedby'));

        function check() {
            var hasUrl = pattern.test($field.val());
            this.setCustomValidity(hasUrl ? message : '');
            $field.toggleClass('has-error', hasUrl);
            $error.text(hasUrl ? message : '').prop('hidden', !hasUrl);
        }

        $field.on('input blur', check);
    });

    /* Password visibility */
    $('.toggle-pass').on('click', function () {
        var $input = $(this).siblings('input');
        var show = $input.attr('type') === 'password';

        $input.attr('type', show ? 'text' : 'password');
        $(this).attr('aria-label', show ? 'Hide password' : 'Show password')
            .find('i').attr('class', show ? 'bi bi-eye-slash' : 'bi bi-eye');
    });

    /* Password strength */
    $('[data-strength]').on('input', function () {
        var value = this.value;
        var score = 0;
        var labels = ['Too short', 'Weak', 'Fair', 'Good', 'Strong'];

        if (value.length >= 8) {
            score = 1;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
            if (/\d/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;
        }

        var $meter = $($(this).data('strength'));
        $meter.attr('data-level', value ? Math.max(score, 1) : 0);
        $meter.next('.strength-text').text(value ? labels[score] : '');
    });

    $('[data-match]').on('input', function () {
        var original = $($(this).data('match')).val();
        this.setCustomValidity(this.value && this.value !== original ? 'Passwords do not match.' : '');
    });

    /* Log out links submit their surrounding POST form */
    $(document).on('click', '[data-logout]', function (e) {
        e.preventDefault();
        $(this).closest('form').trigger('submit');
    });

    /* Demo forms (static design only) */
    $('form[data-demo]').on('submit', function (e) {
        e.preventDefault();
        toast($(this).data('demo'));
        if ($(this).is('[data-reset]')) {
            this.reset();
            $(this).find('.preview-grid').empty();
            $(this).find('[data-counter]').trigger('input');
        }
    });

    /* Confirm destructive actions */
    $(document).on('submit', 'form[data-confirm]', function (e) {
        if (!window.confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });

    /* Missing media fallback */
    function showMediaFallback(el) {
        var $el = $(el);
        if ($el.data('fallback-shown')) return;
        $el.data('fallback-shown', true);

        var $box = $el.is('video') ? $el : $el.closest('.gallery-main, .gallery-thumbs button, .my-post-thumb');
        var message = $el.is('video') ? 'This video is no longer available.' : 'Image unavailable';
        $('<span class="media-missing" role="img">')
            .attr('aria-label', message)
            .append('<i class="bi bi-image-alt" aria-hidden="true"></i>')
            .append($('<span>').text(message))
            .insertAfter($el);
        $el.addClass('is-missing');
        $box.addClass('has-missing-media');
    }

    $('img[data-media-fallback]').each(function () {
        if (this.complete && this.naturalWidth === 0) showMediaFallback(this);
    }).on('error', function () { showMediaFallback(this); });

    $('video[data-media-fallback]').each(function () {
        var video = this;
        $(video).find('source').last().on('error', function () { showMediaFallback(video); });
    });

    /* Media uploads (limits come from the dropzone data attributes, set from config/media.php) */
    var LIMITS = {
        image: {
            types: ['image/jpeg', 'image/png', 'image/webp'],
            maxSize: 5 * 1024 * 1024,
            maxFiles: 10,
            label: 'JPG, PNG or WebP'
        },
        video: {
            types: ['video/mp4', 'video/webm'],
            maxSize: 50 * 1024 * 1024,
            maxFiles: 2,
            maxSeconds: 60,
            label: 'MP4 or WebM'
        }
    };

    /* Existing media on the edit form: toggle "remove when saved" */
    function syncRemoveToggle(input) {
        var removed = input.checked;
        var $label = $(input).closest('.preview-remove');

        $(input).closest('.existing-item').toggleClass('is-removed', removed);
        $label.attr('title', removed ? 'Keep this file' : 'Remove this file')
            .find('i').attr('class', removed ? 'bi bi-arrow-counterclockwise' : 'bi bi-x-lg');
    }

    $('[data-remove-toggle]').each(function () { syncRemoveToggle(this); })
        .on('change', function () { syncRemoveToggle(this); });

    function limitsFor($zone) {
        var base = LIMITS[$zone.data('kind')];
        var types = $zone.data('types');

        return {
            types: types ? String(types).split(',') : base.types,
            maxSize: Number($zone.data('max-size')) || base.maxSize,
            maxFiles: Number($zone.data('max-files')) || base.maxFiles,
            maxSeconds: Number($zone.data('max-seconds')) || base.maxSeconds,
            label: $zone.data('label') || base.label
        };
    }

    function formatSize(bytes) {
        return bytes > 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.round(bytes / 1024) + ' KB';
    }

    function formatDuration(seconds) {
        var s = Math.round(seconds);
        return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }

    $('.dropzone').each(function () {
        var $zone = $(this);
        var input = $zone.find('input[type="file"]')[0];
        var kind = $zone.data('kind');
        var rules = limitsFor($zone);
        var $grid = $($zone.data('preview'));
        var $error = $($zone.data('error'));
        var store = new DataTransfer();

        function sync() {
            input.files = store.files;
        }

        function showErrors(list) {
            $error.empty();
            list.forEach(function (msg) { $('<div>').text(msg).appendTo($error); });
        }

        function removeFile(file) {
            var next = new DataTransfer();
            Array.prototype.forEach.call(store.files, function (f) {
                if (f !== file) next.items.add(f);
            });
            store = next;
            sync();
        }

        function addPreview(file, extra) {
            var url = URL.createObjectURL(file);
            var $item = $('<div class="preview-item">');
            var $media = kind === 'image'
                ? $('<img>').attr({ src: url, alt: 'Preview of ' + file.name })
                : $('<video muted playsinline preload="metadata">').attr('src', url);

            $item.append($media);
            $('<span class="preview-meta">').text(extra || formatSize(file.size)).appendTo($item);
            $('<span class="preview-name">').text(file.name).attr('title', file.name).appendTo($item);
            $('<button type="button" class="preview-remove" aria-label="Remove file"><i class="bi bi-x-lg"></i></button>')
                .on('click', function () {
                    removeFile(file);
                    URL.revokeObjectURL(url);
                    $item.remove();
                })
                .appendTo($item);

            if (kind === 'image') {
                $('<input type="text" name="alt_text[]" maxlength="150" required>')
                    .attr({ placeholder: 'Describe this image (alt text)', 'aria-label': 'Alt text for ' + file.name })
                    .appendTo($item);
            }

            $grid.append($item);
        }

        function checkVideo(file, done) {
            var probe = document.createElement('video');
            var url = URL.createObjectURL(file);
            probe.preload = 'metadata';
            probe.onloadedmetadata = function () {
                URL.revokeObjectURL(url);
                done(probe.duration);
            };
            probe.onerror = function () {
                URL.revokeObjectURL(url);
                done(null);
            };
            probe.src = url;
        }

        $(input.form).on('reset', function () {
            store = new DataTransfer();
            $error.empty();
        });

        $zone.on('dragenter dragover', function () { $zone.addClass('is-drag'); })
            .on('dragleave drop', function () { $zone.removeClass('is-drag'); });

        $(input).on('change', function () {
            var incoming = Array.prototype.slice.call(this.files);
            var errors = [];

            sync();

            incoming.forEach(function (file) {
                if (rules.types.indexOf(file.type) === -1) {
                    errors.push(file.name + ': only ' + rules.label + ' files are allowed.');
                    return;
                }
                if (file.size > rules.maxSize) {
                    errors.push(file.name + ': file is larger than ' + formatSize(rules.maxSize) + '.');
                    return;
                }
                var kept = $($zone.data('existing')).find('.existing-item:not(.is-removed)').length;

                if (store.files.length + kept >= rules.maxFiles) {
                    errors.push('You can upload up to ' + rules.maxFiles + (kind === 'image' ? ' photos.' : (rules.maxFiles === 1 ? ' video.' : ' videos.')));
                    return;
                }

                if (kind === 'video') {
                    store.items.add(file);
                    checkVideo(file, function (duration) {
                        if (duration === null || duration > rules.maxSeconds) {
                            removeFile(file);
                            showErrors([file.name + (duration === null
                                ? ': this video could not be read.'
                                : ': videos must be ' + rules.maxSeconds + ' seconds or shorter.')]);
                            return;
                        }
                        addPreview(file, formatDuration(duration) + ' · ' + formatSize(file.size));
                    });
                } else {
                    store.items.add(file);
                    addPreview(file);
                }
            });

            sync();
            showErrors(errors);
        });
    });

    /* Floating support button (markup and links come from Admin > Settings) */
    var $support = $('.support-fab');

    if ($support.length) {
        var $supportBtn = $support.find('.support-fab-btn');

        $body.addClass('has-support-fab');

        function setSupportOpen(open) {
            $support.toggleClass('is-open', open);
            $supportBtn.attr('aria-expanded', open)
                .find('i').attr('class', open ? 'bi bi-x-lg' : 'bi bi-heart-fill');
        }

        $supportBtn.on('click', function () {
            setSupportOpen(!$support.hasClass('is-open'));
        });

        $(document).on('click', function (e) {
            if ($support.hasClass('is-open') && !$(e.target).closest('.support-fab').length) {
                setSupportOpen(false);
            }
        }).on('keydown', function (e) {
            if (e.key === 'Escape' && $support.hasClass('is-open')) {
                setSupportOpen(false);
                $supportBtn.trigger('focus');
            }
        });
    }

    /* Cash App amount chips */
    $('.amount-chips').on('click', 'button', function () {
        var $btn = $(this);
        var $link = $btn.closest('.pay-card').find('[data-pay-base]');

        $btn.addClass('active').attr('aria-pressed', 'true')
            .siblings().removeClass('active').attr('aria-pressed', 'false');
        $link.attr('href', $link.data('pay-base') + $btn.data('amount'));
    });
});
