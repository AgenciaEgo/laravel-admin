// native replacements for the iCheck calls of 1.x: set the state and fire 'change'
$.fn.laCheck = function (checked) {
    return this.each(function () {
        if (this.checked !== checked) {
            this.checked = checked;
            $(this).trigger('change');
        }
    });
};

// replaces Bootstrap 3's $.fn.button('loading' | 'reset'): disable the button and show its data-loading-text
$.fn.laLoading = function (loading) {
    return this.each(function () {
        var $btn = $(this);
        if (loading) {
            if ($btn.data('la-html') === undefined) {
                $btn.data('la-html', $btn.html());
            }
            $btn.prop('disabled', true);
            if ($btn.data('loading-text')) {
                $btn.html($btn.data('loading-text'));
            }
        } else {
            $btn.prop('disabled', false);
            if ($btn.data('la-html') !== undefined) {
                $btn.html($btn.data('la-html'));
                $btn.removeData('la-html');
            }
        }
    });
};

$.fn.laToggle = function () {
    return this.each(function () {
        $(this).laCheck(this.type === 'radio' ? true : !this.checked);
    });
};

toastr.options = {
    closeButton: true,
    progressBar: true,
    showMethod: 'slideDown',
    timeOut: 4000
};

$.pjax.defaults.timeout = 5000;
$.pjax.defaults.maxCacheLength = 0;
$(document).pjax('a:not(a[target="_blank"])', {
    container: '#pjax-container'
});

NProgress.configure({parent: '#app'});

$(document).on('pjax:timeout', function (event) {
    event.preventDefault();
})

$(document).on('submit', 'form[pjax-container]', function (event) {
    $.pjax.submit(event, '#pjax-container')
});

$(document).on("pjax:popstate", function () {

    $(document).one("pjax:end", function (event) {
        $(event.target).find("script[data-exec-on-popstate]").each(function () {
            $.globalEval(this.text || this.textContent || this.innerHTML || '');
        });
    });
});

$(document).on('pjax:send', function (xhr) {
    if (xhr.relatedTarget && xhr.relatedTarget.tagName && xhr.relatedTarget.tagName.toLowerCase() === 'form') {
        $submit_btn = $('form[pjax-container] :submit');
        if ($submit_btn) {
            $submit_btn.laLoading(true)
        }
    }
    NProgress.start();
});

$(document).on('pjax:complete', function (xhr) {
    if (xhr.relatedTarget && xhr.relatedTarget.tagName && xhr.relatedTarget.tagName.toLowerCase() === 'form') {
        $submit_btn = $('form[pjax-container] :submit');
        if ($submit_btn) {
            $submit_btn.laLoading(false)
        }
    }
    NProgress.done();
    $.admin.grid.selects = {};
});

$(function () {
    var $menu = $('.sidebar-menu');

    // highlight the clicked entry (pjax keeps the sidebar, so do it by hand)
    $menu.on('click', '.nav-item > a.nav-link:not([href="#"])', function () {
        $menu.find('.nav-link.active').removeClass('active');
        $(this).addClass('active').parents('.nav-treeview').siblings('a.nav-link').addClass('active');
    });

    // open the entry of the current page on full loads
    var $current = $menu.find('a.nav-link[href$="' + (location.pathname + location.search + location.hash) + '"]').first();
    $current.addClass('active');
    $current.parents('.nav-treeview').each(function () {
        $(this).css('display', 'block').closest('.nav-item').addClass('menu-open').children('a.nav-link').addClass('active');
    });

    $('[data-bs-toggle="popover"]').popover();
});

$(window).scroll(function() {
    if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
        $('#totop').fadeIn(500);
    } else {
        $('#totop').fadeOut(500);
    }
});

$('#totop').on('click', function (e) {
    e.preventDefault();
    $('html,body').animate({scrollTop: 0}, 500);
});

(function ($) {

    var Grid = function () {
        this.selects = {};
    };

    Grid.prototype.select = function (id) {
        this.selects[id] = id;
    };

    Grid.prototype.unselect = function (id) {
        delete this.selects[id];
    };

    Grid.prototype.selected = function () {
        var rows = [];
        $.each(this.selects, function (key, val) {
            rows.push(key);
        });

        return rows;
    };

    $.fn.admin = LA;
    $.admin = LA;
    $.admin.swal = swal;
    $.admin.toastr = toastr;
    $.admin.grid = new Grid();

    $.admin.reload = function () {
        $.pjax.reload('#pjax-container');
        $.admin.grid = new Grid();
    };

    $.admin.redirect = function (url) {
        $.pjax({container:'#pjax-container', url: url });
        $.admin.grid = new Grid();
    };

    $.admin.getToken = function () {
        return $('meta[name="csrf-token"]').attr('content');
    };

    $.admin.loadedScripts = [];

    $.admin.loadScripts = function(arr) {
        var _arr = $.map(arr, function(src) {

            if ($.inArray(src, $.admin.loadedScripts)) {
                return;
            }

            $.admin.loadedScripts.push(src);

            return $.getScript(src);
        });

        _arr.push($.Deferred(function(deferred){
            $(deferred.resolve);
        }));

        return $.when.apply($, _arr);
    }

})(jQuery);

/**
 * Date/time pickers (2.0): flatpickr behind the options of the eonasdan
 * bootstrap-datetimepicker that 1.x used, so fields keep taking moment formats
 * (->format('DD/MM/YYYY')) and its main options. Options flatpickr knows are
 * passed through as they are.
 *
 *   $.admin.datetimepicker(target, options)          one picker per input in target
 *   $.admin.datetimeRange(start, end, options)        two pickers limiting each other
 */
(function ($) {

    // moment token => flatpickr token (longest tokens first)
    var TOKENS = [
        ['YYYY', 'Y'], ['YY', 'y'],
        ['MMMM', 'F'], ['MMM', 'M'], ['MM', 'm'], ['M', 'n'],
        ['dddd', 'l'], ['ddd', 'D'], ['Do', 'J'], ['DD', 'd'], ['D', 'j'],
        ['HH', 'H'], ['H', 'H'], ['hh', 'G'], ['h', 'h'],
        ['mm', 'i'], ['m', 'i'], ['ss', 'S'], ['s', 's'],
        ['A', 'K'], ['a', 'K'], ['X', 'U']
    ];

    // eonasdan-only options with no flatpickr equivalent
    var DROPPED = ['format', 'locale', 'useCurrent', 'allowInputToggle', 'sideBySide', 'viewMode', 'keepOpen',
        'widgetPositioning', 'widgetParent', 'icons', 'toolbarPlacement', 'showClear', 'showClose',
        'showTodayButton', 'collapse', 'calendarWeeks', 'stepping', 'daysOfWeekDisabled', 'disabledDates',
        'enabledDates', 'date', 'useStrict', 'keepInvalid', 'focusOnShow', 'ignoreReadonly', 'debug', 'tooltips'];

    function convertFormat(format) {
        var out = '', i = 0;

        while (i < format.length) {
            if (format[i] === '[') { // [literal text]
                var end = format.indexOf(']', i);
                var literal = end < 0 ? format.slice(i + 1) : format.slice(i + 1, end);
                out += literal.replace(/[a-zA-Z]/g, '\\$&');
                i = end < 0 ? format.length : end + 1;
                continue;
            }

            var token = null;
            for (var t = 0; t < TOKENS.length; t++) {
                if (format.substr(i, TOKENS[t][0].length) === TOKENS[t][0]) {
                    token = TOKENS[t];
                    break;
                }
            }

            if (token) {
                out += token[1];
                i += token[0].length;
            } else {
                out += /[a-zA-Z]/.test(format[i]) ? '\\' + format[i] : format[i];
                i++;
            }
        }

        return out;
    }

    // 'es', 'pt-BR', 'zh_CN'... => a key of flatpickr.l10ns
    function locale(name) {
        var l10ns = (window.flatpickr && flatpickr.l10ns) || {};
        var key = String(name || '').toLowerCase().replace('-', '_');

        if (!key || key === 'en' || key.indexOf('en_') === 0) {
            return 'default';
        }
        if (l10ns[key]) {
            return key;
        }
        if (key === 'zh_tw' || key === 'zh_hk') {
            return l10ns.zh_tw ? 'zh_tw' : 'zh';
        }
        key = key.split('_')[0];

        return l10ns[key] ? key : 'default';
    }

    function config(input, options) {
        options = $.extend({}, options);

        var format = options.format || 'YYYY-MM-DD HH:mm:ss';
        var plain = format.replace(/\[[^\]]*\]/g, '');
        var hasDate = /[YMDd]/.test(plain);
        var hasDay = /[Dd]/.test(plain);
        var hasTime = /[Hhms]/.test(plain);
        var dateFormat = convertFormat(format);

        var cfg = {
            dateFormat: dateFormat,
            allowInput: true,
            disableMobile: true,
            locale: locale(options.locale),
            time_24hr: !/[Aa]/.test(plain),
            enableTime: hasTime,
            enableSeconds: /s/.test(plain),
            noCalendar: !hasDate
        };

        // year and/or month only (e.g. 'YYYY', 'YYYY-MM', 'MM'): month grid instead of days
        if (hasDate && !hasDay && !hasTime && window.monthSelectPlugin) {
            cfg.plugins = [new monthSelectPlugin({shorthand: true, dateFormat: dateFormat, altFormat: dateFormat})];
        }

        if (options.stepping) {
            cfg.minuteIncrement = options.stepping;
        }

        var disable = [];
        if ($.isArray(options.daysOfWeekDisabled) && options.daysOfWeekDisabled.length) {
            var days = options.daysOfWeekDisabled.map(Number);
            disable.push(function (date) { return days.indexOf(date.getDay()) !== -1; });
        }
        if ($.isArray(options.disabledDates)) {
            disable = disable.concat(options.disabledDates);
        }
        if (disable.length) {
            cfg.disable = disable;
        }
        if ($.isArray(options.enabledDates)) {
            cfg.enable = options.enabledDates;
        }
        if (options.minDate === false) {
            delete options.minDate;
        }
        if (options.maxDate === false) {
            delete options.maxDate;
        }

        // inside a dropdown, popover or modal keep the calendar next to the input, so clicking
        // it does not close the container
        if ($(input).closest('.dropdown-menu, .popover, .modal').length && !options.inline) {
            cfg['static'] = true;
        }

        $.each(DROPPED, function (i, key) { delete options[key]; });

        return $.extend(cfg, options);
    }

    function inputsOf(target) {
        return $(target).map(function () {
            return $(this).is('input') ? this : $(this).find('input:not([type="hidden"])').get();
        });
    }

    $.admin = $.admin || {};

    $.admin.flatpickrLocale = locale;

    $.admin.datetimepicker = function (target, options) {
        if (typeof flatpickr === 'undefined') {
            return [];
        }

        return inputsOf(target).map(function () {
            if (this._flatpickr) {
                this._flatpickr.destroy();
            }
            return flatpickr(this, config(this, options));
        }).get();
    };

    $.admin.datetimeRange = function (start, end, options) {
        var from = $.admin.datetimepicker(start, options);
        var to = $.admin.datetimepicker(end, options);

        $.each(from, function (i, fp) {
            fp.config.onChange.push(function (dates) {
                $.each(to, function (j, other) { other.set('minDate', dates[0] || null); });
            });
        });
        $.each(to, function (i, fp) {
            fp.config.onChange.push(function (dates) {
                $.each(from, function (j, other) { other.set('maxDate', dates[0] || null); });
            });
        });
    };

})(jQuery);

/**
 * Color picker (2.0): Coloris behind the formats of the bootstrap-colorpicker used in 1.x.
 *
 *   $.admin.colorpicker(selector, {format: 'hex' | 'rgb' | 'rgba'})
 *
 * Without a format the value is hex while opaque and rgba with transparency (the
 * "auto" of bootstrap-colorpicker). The swatch is the .la-color-swatch in the input group.
 */
(function ($) {

    var bound = {};

    function swatch(input) {
        $(input).closest('.input-group').find('.la-color-swatch').css('background-color', input.value || 'transparent');
    }

    $.admin.colorpicker = function (selector, options) {
        if (typeof Coloris === 'undefined') {
            return;
        }

        var format = (options || {}).format;
        var config = {format: 'mixed', alpha: true};

        if (format === 'hex') {
            config = {format: 'hex', alpha: false};
        } else if (format === 'rgb') {
            config = {format: 'rgb', alpha: false};
        } else if (format === 'rgba') {
            config = {format: 'rgb', alpha: true, forceAlpha: true};
        }

        // Coloris listens on the document per selector: bind each selector once (pjax reloads)
        if (!bound[selector]) {
            bound[selector] = true;
            Coloris({el: selector, wrap: false});
            $(document).on('input change', selector, function () {
                swatch(this);
            });
        }

        Coloris.setInstance(selector, config);

        $(selector).each(function () {
            swatch(this);
        });
    };

})(jQuery);

/**
 * Grid inline edit (2.0): the popovers of ->input(), ->select(), ->datetime(), ->editable()...
 * on the Bootstrap 5 Popover. The content is built from the field's <template> and holds
 * form controls, so the sanitizer is off (the template is server-rendered).
 */
(function ($) {

    function placement(tip, source) {
        var position = $(source).position();
        if (position.left < 100) return 'right';
        if (position.top < 110) return 'bottom';
        if ($(window).height() - $(source).offset().top < 370) return 'top';
        return 'bottom';
    }

    $.admin.inlineEdit = {
        popover: function (trigger, fill) {
            if (bootstrap.Popover.getInstance(trigger)) {
                return;
            }
            new bootstrap.Popover(trigger, {
                html: true,
                sanitize: false,
                container: 'body',
                trigger: 'manual',
                placement: placement,
                content: function () {
                    var $trigger = $(trigger);
                    var $template = $($('template#' + $trigger.data('target')).html());
                    fill($trigger, $template);
                    return $template.prop('outerHTML');
                }
            });
        },

        hide: function (except) {
            $('.la-ie-trigger').each(function () {
                var popover = bootstrap.Popover.getInstance(this);
                if (popover && this !== except) {
                    popover.hide();
                }
            });
        },

        toggle: function (trigger) {
            $.admin.inlineEdit.hide(trigger);
            var popover = bootstrap.Popover.getInstance(trigger);
            if (popover) {
                popover.toggle();
            }
        }
    };

})(jQuery);

/*
 * Grid filter drawer (filter/button.blade.php, filter/container.blade.php).
 */
(function ($) {
    // Bootstrap traps the focus inside an offcanvas with a backdrop, but select2 and the date
    // pickers put their search box / month and year inputs in <body>: release the trap
    $(document).on('shown.bs.offcanvas', '.filter-offcanvas', function () {
        var offcanvas = bootstrap.Offcanvas.getInstance(this);
        if (offcanvas && offcanvas._focustrap) {
            offcanvas._focustrap.deactivate();
        }
    });

    // searching or resetting reloads the grid with pjax: close the drawer first, so its
    // backdrop (in <body>) and the scroll lock do not outlive it
    $(document).on('pjax:send', function () {
        $('.filter-offcanvas.show, .filter-offcanvas.showing').each(function () {
            var offcanvas = bootstrap.Offcanvas.getInstance(this);
            if (offcanvas) {
                offcanvas.hide();
            }
        });
    });
})(jQuery);
