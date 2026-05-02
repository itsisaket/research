<?php
/**
 * Smart Search assets v2 — CSS + JS
 *
 * ฟีเจอร์
 *  - Quick search debounce auto-submit (text)
 *  - Auto-submit ทันทีเมื่อเปลี่ยน select
 *  - ปุ่ม clear ใน input
 *  - Autocomplete dropdown (เมื่อมี data-suggest-url)
 *  - Recent searches (localStorage, key เฉพาะ pjaxId)
 *  - Filter count badge (อัปเดตอัตโนมัติ)
 *  - Empty state ที่สวยงาม (ตรวจ table/card view ที่ว่าง)
 *
 * @var string $pjaxId
 */

$pjaxId = $pjaxId ?? '';
?>

<style>
.smart-search { position: relative; }
.smart-search .ss-quick-input { padding-left: 2.4rem; padding-right: 2.4rem; }
.smart-search .ss-quick-icon {
    position: absolute; left: .8rem; top: 50%; transform: translateY(-50%);
    color: #6c757d; pointer-events: none; z-index: 2;
}
.smart-search .ss-quick-clear {
    position: absolute; right: .6rem; top: 50%; transform: translateY(-50%);
    border: 0; background: transparent; color: #6c757d; cursor: pointer;
    padding: .25rem .5rem; line-height: 1; display: none;
    font-size: 1.25rem; z-index: 2;
}
.smart-search .ss-quick-clear:hover { color: #dc3545; }
.smart-search .ss-quick-input:not(:placeholder-shown) ~ .ss-quick-clear { display: block; }

/* ===== Preset chips (filter ลัด) ===== */
.ss-presets { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
.ss-presets .ss-preset-label {
    color: #6c757d; font-size: .8rem; margin-right: .25rem;
}
.ss-presets a {
    text-decoration: none; padding: .3rem .75rem; border-radius: 999px;
    background: #f8f9fa; color: #495057; font-size: .85rem;
    border: 1px solid #dee2e6; transition: all .15s;
    white-space: nowrap;
}
.ss-presets a:hover { background: #e7f1ff; border-color: #cfe2ff; color: #0d6efd; }
.ss-presets a.active {
    background: #0d6efd; color: #fff; border-color: #0d6efd;
    font-weight: 500;
}
.ss-presets a.active:hover { background: #0b5ed7; }

/* ===== Active filter chips ===== */
.smart-search .ss-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem; border-radius: 999px;
    background: #e7f1ff; color: #0d6efd; font-size: .85rem;
    border: 1px solid #cfe2ff;
}
.smart-search .ss-chip a { color: inherit; text-decoration: none; opacity: .7; }
.smart-search .ss-chip a:hover { opacity: 1; color: #dc3545; }

/* ===== Filter count badge ===== */
.ss-filter-badge {
    display: inline-block; min-width: 1.25rem; padding: .15rem .4rem;
    border-radius: 999px; background: #0d6efd; color: #fff;
    font-size: .7rem; font-weight: 600; line-height: 1;
    margin-left: .35rem;
}

/* ===== Sort dropdown ===== */
.ss-sort .dropdown-menu { font-size: .9rem; }
.ss-sort .dropdown-item.active,
.ss-sort .dropdown-item:active { background-color: #0d6efd; }
.ss-sort .dropdown-item i { width: 1.2rem; color: #6c757d; }
.ss-sort .dropdown-item.active i { color: #fff; }

/* ===== Loading + grid wrapper ===== */
.smart-search .ss-loading {
    display: none; align-items: center; gap: .4rem; color: #6c757d;
}
.pjax-loading .smart-search .ss-loading { display: inline-flex; }
.pjax-loading .ss-grid-wrap { opacity: .55; pointer-events: none; transition: opacity .15s; }

/* ===== Autocomplete dropdown + Recent searches ===== */
.ss-ac-wrap { position: relative; }
.ss-ac-dropdown {
    position: absolute; top: 100%; left: 0; right: 0;
    background: #fff; border: 1px solid #dee2e6; border-radius: .375rem;
    margin-top: .25rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.08);
    z-index: 1080; max-height: 380px; overflow-y: auto;
    display: none;
}
.ss-ac-dropdown.show { display: block; }
.ss-ac-section-header {
    padding: .4rem .9rem; background: #f8f9fa; font-size: .75rem;
    color: #6c757d; font-weight: 600; text-transform: uppercase;
    border-bottom: 1px solid #dee2e6;
    display: flex; justify-content: space-between; align-items: center;
}
.ss-ac-section-header a { font-size: .7rem; color: #dc3545; text-decoration: none; }
.ss-ac-item {
    display: block; padding: .6rem .9rem; color: inherit; text-decoration: none;
    border-bottom: 1px solid #f1f3f5; cursor: pointer;
}
.ss-ac-item:last-child { border-bottom: 0; }
.ss-ac-item:hover, .ss-ac-item.active {
    background: #e7f1ff; color: #0d6efd;
}
.ss-ac-item .ss-ac-title {
    font-weight: 500; display: block; line-height: 1.3;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ss-ac-item .ss-ac-subtitle {
    font-size: .8rem; color: #6c757d; margin-top: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ss-ac-item.active .ss-ac-subtitle { color: rgba(13,110,253,.75); }
.ss-ac-item mark {
    background: #fff3cd; color: inherit; padding: 0; font-weight: 600;
}
.ss-ac-recent { display: flex; align-items: center; gap: .5rem; }
.ss-ac-recent .ss-ac-recent-icon { color: #adb5bd; }
.ss-ac-empty, .ss-ac-loading {
    padding: .8rem; text-align: center; color: #6c757d; font-size: .85rem;
}
.ss-ac-footer {
    padding: .5rem .9rem; background: #f8f9fa; font-size: .8rem;
    color: #6c757d; border-top: 1px solid #dee2e6;
    display: flex; justify-content: space-between;
}
.ss-ac-footer kbd {
    background: #fff; border: 1px solid #ced4da; border-radius: 3px;
    padding: 1px 5px; font-size: .75rem;
}

/* ===== Empty state ===== */
.ss-empty-state {
    text-align: center; padding: 3rem 1rem; color: #6c757d;
}
.ss-empty-state .ss-empty-icon {
    font-size: 3rem; color: #ced4da; margin-bottom: 1rem;
}
.ss-empty-state .ss-empty-title {
    font-size: 1.1rem; color: #495057; margin-bottom: .5rem;
}

/* ===== Mobile ===== */
@media (max-width: 575.98px) {
    .ss-presets { font-size: .85rem; }
    .ss-presets a { padding: .25rem .55rem; }
    .smart-search .ss-quick-input { font-size: .95rem; }
}
</style>

<script>
(function () {
    'use strict';
    if (window.__smartSearchInited) {
        // อัปเดต badge ใหม่ทุก Pjax reload
        if (typeof window.ssUpdateFilterBadges === 'function') {
            window.ssUpdateFilterBadges();
        }
        return;
    }
    window.__smartSearchInited = true;

    const DEBOUNCE_MS  = 400;
    const SUGGEST_MS   = 250;
    const MIN_QUERY    = 2;
    const RECENT_MAX   = 5;

    /* ================= Helpers ================= */

    function debounce(fn, ms) {
        let t;
        return function () {
            const ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function highlight(text, q) {
        text = escapeHtml(text);
        if (!q) return text;
        const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return text.replace(re, '<mark>$1</mark>');
    }

    function submitForm($form) {
        if (!$form || !$form.length) return;
        if (window.jQuery && window.jQuery.pjax && $form.closest('[data-pjax-container]').length) {
            const containerId = $form.closest('[data-pjax-container]').attr('id');
            window.jQuery.pjax.submit(
                jQuery.Event('submit', {currentTarget: $form[0]}),
                containerId ? '#' + containerId : 'body'
            );
        } else {
            $form[0].submit();
        }
    }

    /* ================= Recent searches (localStorage) ================= */

    function recentKey($input) {
        const $form = $input.closest('form');
        const containerId = $form.closest('[data-pjax-container]').attr('id') || 'global';
        return 'ss_recent_' + containerId;
    }

    function getRecent($input) {
        try {
            return JSON.parse(localStorage.getItem(recentKey($input)) || '[]');
        } catch (e) { return []; }
    }

    function pushRecent($input, q) {
        if (!q || q.length < MIN_QUERY) return;
        let arr = getRecent($input);
        arr = arr.filter(function (s) { return s !== q; });
        arr.unshift(q);
        if (arr.length > RECENT_MAX) arr.length = RECENT_MAX;
        try { localStorage.setItem(recentKey($input), JSON.stringify(arr)); } catch (e) {}
    }

    function clearRecent($input) {
        try { localStorage.removeItem(recentKey($input)); } catch (e) {}
    }

    /* ================= Filter count badge ================= */

    window.ssUpdateFilterBadges = function () {
        jQuery('.smart-search').each(function () {
            const $form = jQuery(this).find('form').first();
            if (!$form.length) return;

            // นับเฉพาะ field ใน Advanced panel (collapse)
            const $adv = $form.find('.collapse').first();
            if (!$adv.length) return;

            let count = 0;
            $adv.find('input, select').each(function () {
                const v = jQuery(this).val();
                if (v && v.length && v !== '0') count++;
            });

            const $btn = $form.find('[data-bs-toggle="collapse"]').first();
            $btn.find('.ss-filter-badge').remove();
            if (count > 0) {
                $btn.append('<span class="ss-filter-badge">' + count + '</span>');
            }
        });
    };

    /* ================= debounce auto-submit ================= */
    jQuery(document).on('input.smartSearch', '.smart-search .ss-quick-input', debounce(function () {
        const $dd = jQuery(this).siblings('.ss-ac-dropdown');
        if ($dd.hasClass('show') && $dd.find('.ss-ac-item').length > 0) return;
        submitForm(jQuery(this).closest('form'));
    }, DEBOUNCE_MS));

    /* ================= บันทึก recent เมื่อ submit form ================= */
    jQuery(document).on('submit.smartSearch', '.smart-search form', function () {
        const $input = jQuery(this).find('.ss-quick-input').first();
        if ($input.length) pushRecent($input, ($input.val() || '').trim());
    });

    /* ================= auto-submit เมื่อเปลี่ยน select ================= */
    jQuery(document).on('change.smartSearch', '.smart-search select, .smart-search input[type="hidden"][data-auto-submit="1"]', function () {
        submitForm(jQuery(this).closest('form'));
    });

    /* ================= ปุ่ม clear ================= */
    jQuery(document).on('click.smartSearch', '.smart-search .ss-quick-clear', function (e) {
        e.preventDefault();
        const $input = jQuery(this).siblings('.ss-quick-input');
        $input.val('').trigger('input').focus();
        hideDropdown($input);
    });

    /* ================================================================
     *  Autocomplete + Recent searches
     * ================================================================ */

    function getDropdown($input) {
        let $dd = $input.siblings('.ss-ac-dropdown');
        if (!$dd.length) {
            $dd = jQuery('<div class="ss-ac-dropdown" role="listbox"></div>');
            $input.after($dd);
        }
        return $dd;
    }

    function hideDropdown($input) {
        const $dd = $input.siblings('.ss-ac-dropdown');
        $dd.removeClass('show').empty();
    }

    function renderRecent($input) {
        const recent = getRecent($input);
        const $dd = getDropdown($input);
        if (!recent || recent.length === 0) {
            $dd.removeClass('show').empty();
            return;
        }
        let html = '<div class="ss-ac-section-header">'
                 + '<span><i class="fas fa-history me-1"></i> ค้นหาล่าสุด</span>'
                 + '<a href="#" class="ss-ac-clear-recent">ล้างประวัติ</a>'
                 + '</div>';
        recent.forEach(function (q, i) {
            html += '<a href="#" class="ss-ac-item ss-ac-recent" data-recent="1" data-q="' + escapeHtml(q) + '" data-index="' + i + '">'
                  + '<span class="ss-ac-recent-icon"><i class="fas fa-clock-rotate-left"></i></span>'
                  + '<span class="ss-ac-title">' + escapeHtml(q) + '</span>'
                  + '</a>';
        });
        $dd.html(html).addClass('show');
    }

    function renderSuggest($input, items, q) {
        const $dd = getDropdown($input);
        if (!items || items.length === 0) {
            $dd.html('<div class="ss-ac-empty">ไม่พบรายการที่ตรงกับ "' + escapeHtml(q) + '"</div>'
                + '<div class="ss-ac-footer"><span>กด <kbd>Enter</kbd> เพื่อค้นหาแบบเต็ม</span></div>');
            $dd.addClass('show');
            return;
        }
        let html = '<div class="ss-ac-section-header"><span><i class="fas fa-magnifying-glass me-1"></i> รายการที่ตรงกัน</span></div>';
        items.forEach(function (it, i) {
            html += '<a href="' + escapeHtml(it.url || '#') + '" class="ss-ac-item" data-pjax="0" data-index="' + i + '">'
                  + '<span class="ss-ac-title">' + highlight(it.title || '', q) + '</span>';
            if (it.subtitle) {
                html += '<span class="ss-ac-subtitle">' + escapeHtml(it.subtitle) + '</span>';
            }
            html += '</a>';
        });
        html += '<div class="ss-ac-footer">'
              + '<span>เลือก <kbd>↑</kbd><kbd>↓</kbd> ยืนยัน <kbd>Enter</kbd> ปิด <kbd>Esc</kbd></span>'
              + '<span>' + items.length + ' รายการ</span>'
              + '</div>';
        $dd.html(html).addClass('show');
    }

    function setActive($dd, idx) {
        const $items = $dd.find('.ss-ac-item');
        $items.removeClass('active');
        if (idx >= 0 && idx < $items.length) {
            const $it = $items.eq(idx);
            $it.addClass('active');
            const top = $it.position().top;
            const ddTop = $dd.scrollTop();
            const itemH = $it.outerHeight();
            const ddH = $dd.height();
            if (top < 0) $dd.scrollTop(ddTop + top);
            else if (top + itemH > ddH) $dd.scrollTop(ddTop + top + itemH - ddH);
        }
    }

    function fetchSuggest($input, q) {
        const url = $input.data('suggest-url');
        if (!url) return;
        const $dd = getDropdown($input);
        $dd.html('<div class="ss-ac-loading"><span class="spinner-border spinner-border-sm me-1"></span> กำลังค้นหา...</div>')
           .addClass('show');

        const prev = $input.data('ac-xhr');
        if (prev && prev.abort) prev.abort();

        const xhr = jQuery.getJSON(url, {q: q})
            .done(function (data) {
                renderSuggest($input, (data && data.items) ? data.items : [], q);
            })
            .fail(function (jqXHR, textStatus) {
                if (textStatus === 'abort') return;
                $dd.html('<div class="ss-ac-empty text-danger">เกิดข้อผิดพลาดในการค้นหา</div>');
            });
        $input.data('ac-xhr', xhr);
    }

    const debouncedFetch = debounce(fetchSuggest, SUGGEST_MS);

    /* === Input event === */
    jQuery(document).on('input.ssAc', '.smart-search .ss-quick-input', function () {
        const $input = jQuery(this);
        const q = $input.val().trim();
        if (q.length < MIN_QUERY) {
            // ค่าน้อยกว่า min — แสดง recent searches ถ้ามี
            if ($input.is(':focus')) renderRecent($input);
            else hideDropdown($input);
            return;
        }
        // มี suggest URL → ลอง fetch, ไม่งั้นซ่อน
        if ($input.data('suggest-url')) {
            debouncedFetch($input, q);
        } else {
            hideDropdown($input);
        }
    });

    /* === Focus → ถ้าว่างให้แสดง recent, ถ้ามีคำให้ลอง suggest === */
    jQuery(document).on('focus.ssAc', '.smart-search .ss-quick-input', function () {
        const $input = jQuery(this);
        const q = $input.val().trim();
        if (q.length === 0) {
            renderRecent($input);
        } else if (q.length >= MIN_QUERY && $input.data('suggest-url')) {
            debouncedFetch($input, q);
        }
    });

    /* === Click recent → เติมในช่อง + submit === */
    jQuery(document).on('mousedown.ssAc', '.smart-search .ss-ac-item[data-recent="1"]', function (e) {
        e.preventDefault();
        const $input = jQuery(this).closest('.ss-ac-wrap').find('.ss-quick-input');
        const q = jQuery(this).data('q');
        $input.val(q);
        hideDropdown($input);
        submitForm($input.closest('form'));
    });

    /* === Click ล้างประวัติ === */
    jQuery(document).on('click.ssAc', '.ss-ac-clear-recent', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const $input = jQuery(this).closest('.ss-ac-wrap').find('.ss-quick-input');
        clearRecent($input);
        hideDropdown($input);
        $input.focus();
    });

    /* === Click suggest item → redirect === */
    jQuery(document).on('mousedown.ssAc', '.smart-search .ss-ac-item:not([data-recent])', function (e) {
        e.preventDefault();
        const href = jQuery(this).attr('href');
        if (href && href !== '#') window.location.href = href;
    });

    /* === Keyboard navigation === */
    jQuery(document).on('keydown.ssAc', '.smart-search .ss-quick-input', function (e) {
        const $input = jQuery(this);
        const $dd = $input.siblings('.ss-ac-dropdown');
        if (!$dd.hasClass('show')) return;

        const $items = $dd.find('.ss-ac-item');
        const total = $items.length;
        if (total === 0) return;

        let idx = $items.filter('.active').data('index');
        idx = (typeof idx === 'number') ? idx : -1;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive($dd, (idx + 1) % total);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive($dd, idx <= 0 ? total - 1 : idx - 1);
        } else if (e.key === 'Enter') {
            const $active = $items.filter('.active');
            if ($active.length) {
                e.preventDefault();
                if ($active.data('recent')) {
                    const q = $active.data('q');
                    $input.val(q);
                    hideDropdown($input);
                    submitForm($input.closest('form'));
                } else {
                    const href = $active.attr('href');
                    if (href && href !== '#') window.location.href = href;
                }
            } else {
                hideDropdown($input);
            }
        } else if (e.key === 'Escape') {
            hideDropdown($input);
        }
    });

    /* === ปิดเมื่อคลิกข้างนอก / blur === */
    jQuery(document).on('click.ssAc', function (e) {
        if (!jQuery(e.target).closest('.smart-search').length) {
            jQuery('.ss-ac-dropdown').removeClass('show').empty();
        }
    });
    jQuery(document).on('blur.ssAc', '.smart-search .ss-quick-input', function () {
        const $input = jQuery(this);
        setTimeout(function () { hideDropdown($input); }, 200);
    });

    /* === Initial: update badges === */
    jQuery(function () {
        if (typeof window.ssUpdateFilterBadges === 'function') {
            window.ssUpdateFilterBadges();
        }
    });

    /* === Pjax callback: update badges after content reload === */
    jQuery(document).on('pjax:end', function () {
        if (typeof window.ssUpdateFilterBadges === 'function') {
            window.ssUpdateFilterBadges();
        }
    });
})();
</script>
