<?php
/**
 * Smart Search assets (CSS + JS)
 * - debounce auto-submit สำหรับช่อง quick search (text)
 * - auto-submit ทันทีเมื่อเปลี่ยน select
 * - แสดง loading indicator ใน Pjax
 * - ปุ่ม clear ใน input
 *
 * วิธีใช้:
 *   <?= $this->render('@app/views/_shared/_smart_search_assets', ['pjaxId' => 'pjax-xxx']) ?>
 *
 * @var string $pjaxId  id ของ Pjax container ที่ครอบ search+grid
 */

$pjaxId = $pjaxId ?? '';
?>

<style>
.smart-search .ss-quick-input { padding-left: 2.4rem; }
.smart-search .ss-quick-icon {
    position: absolute; left: .8rem; top: 50%; transform: translateY(-50%);
    color: #6c757d; pointer-events: none;
}
.smart-search .ss-quick-clear {
    position: absolute; right: .6rem; top: 50%; transform: translateY(-50%);
    border: 0; background: transparent; color: #6c757d; cursor: pointer;
    padding: .25rem .5rem; line-height: 1; display: none;
}
.smart-search .ss-quick-clear:hover { color: #dc3545; }
.smart-search .ss-quick-input:not(:placeholder-shown) ~ .ss-quick-clear { display: block; }

.smart-search .ss-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem; border-radius: 999px;
    background: #e7f1ff; color: #0d6efd; font-size: .85rem;
    border: 1px solid #cfe2ff;
}
.smart-search .ss-chip a { color: inherit; text-decoration: none; opacity: .7; }
.smart-search .ss-chip a:hover { opacity: 1; color: #dc3545; }

.smart-search .ss-loading {
    display: none; align-items: center; gap: .4rem; color: #6c757d;
}
.pjax-loading .smart-search .ss-loading { display: inline-flex; }
.pjax-loading .ss-grid-wrap { opacity: .55; pointer-events: none; transition: opacity .15s; }
</style>

<script>
(function () {
    'use strict';
    // กันการผูก event ซ้ำหลาย Pjax reload
    if (window.__smartSearchInited) {
        return;
    }
    window.__smartSearchInited = true;

    const DEBOUNCE_MS = 400;

    function debounce(fn, ms) {
        let t;
        return function () {
            const ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    function submitForm($form) {
        if (!$form || !$form.length) return;
        // Pjax submit ถ้ามี $.pjax และ form อยู่ใน Pjax container
        if (window.jQuery && window.jQuery.pjax && $form.closest('[data-pjax-container]').length) {
            window.jQuery.pjax.submit(
                jQuery.Event('submit', {currentTarget: $form[0]}),
                $form.closest('[data-pjax-container]').attr('id') ?
                    '#' + $form.closest('[data-pjax-container]').attr('id') : 'body'
            );
        } else {
            $form[0].submit();
        }
    }

    // delegate event เพื่อให้ใช้ได้กับ form ที่ถูก reload โดย Pjax
    jQuery(document).on('input.smartSearch', '.smart-search .ss-quick-input', debounce(function () {
        submitForm(jQuery(this).closest('form'));
    }, DEBOUNCE_MS));

    // auto-submit ทันทีเมื่อเปลี่ยน select / select2
    jQuery(document).on('change.smartSearch', '.smart-search select, .smart-search input[type="hidden"][data-auto-submit="1"]', function () {
        submitForm(jQuery(this).closest('form'));
    });

    // ปุ่ม clear ในช่อง quick search
    jQuery(document).on('click.smartSearch', '.smart-search .ss-quick-clear', function (e) {
        e.preventDefault();
        const $input = jQuery(this).siblings('.ss-quick-input');
        $input.val('').trigger('input').focus();
    });

    // ครั้งแรกที่โหลดหน้า autofocus quick search ถ้าว่าง
    jQuery(function () {
        const $first = jQuery('.smart-search .ss-quick-input').first();
        if ($first.length && !$first.val()) {
            // อย่า autofocus ถ้าหน้าใช้ scroll position อื่น
            // $first.focus();
        }
    });
})();
</script>
