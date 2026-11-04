// x-editable (grid ->editable()) is not loaded with Bootstrap 5; it gets replaced in the grid phase
if ($.fn.editable) {
    $.fn.editable.defaults.params = function (params) {
        params._token = LA.token;
        params._editable = 1;
        params._method = 'PUT';
        return params;
    };

    $.fn.editable.defaults.error = function (data) {
        var msg = '';
        if (data.responseJSON.errors) {
            $.each(data.responseJSON.errors, function (k, v) {
                msg += v + "\n";
            });
        }
        return msg
    };
}

// native replacements for the iCheck calls of 1.x: set the state and fire 'change'
$.fn.laCheck = function (checked) {
    return this.each(function () {
        if (this.checked !== checked) {
            this.checked = checked;
            $(this).trigger('change');
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
            $submit_btn.button('loading')
        }
    }
    NProgress.start();
});

$(document).on('pjax:complete', function (xhr) {
    if (xhr.relatedTarget && xhr.relatedTarget.tagName && xhr.relatedTarget.tagName.toLowerCase() === 'form') {
        $submit_btn = $('form[pjax-container] :submit');
        if ($submit_btn) {
            $submit_btn.button('reset')
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

    $('[data-bs-toggle="popover"], [data-toggle="popover"]').popover();
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
