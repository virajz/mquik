import './echo';
import sort from '@alpinejs/sort';
import JsBarcode from 'jsbarcode';
import QRCode from 'qrcode';

// Global helper called by x-init on barcode elements.
// <svg data-barcode="CODE128" data-value="MQ0000042X" data-height="50"></svg>
// <canvas data-barcode="QR" data-value="MQ0000042X" data-size="120"></canvas>
window.renderBarcode = function (el) {
    const value = el.dataset.value;
    const type  = (el.dataset.barcode || 'CODE128').toUpperCase();
    if (!value) return;

    if (type === 'QR') {
        QRCode.toCanvas(el, value, {
            width:  parseInt(el.dataset.size  || '120'),
            margin: 1,
            color: { dark: '#000000', light: '#ffffff' },
        }).catch(() => {});
    } else {
        try {
            JsBarcode(el, value, {
                format:       type === 'EAN13' ? 'EAN13' : 'CODE128',
                width:        1.5,
                height:       parseInt(el.dataset.height || '50'),
                displayValue: true,
                fontSize:     11,
                margin:       4,
            });
        } catch (_) { /* invalid value — render nothing */ }
    }
};

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(sort);
});

// Opens a dedicated iframe for label printing — no popup blocked, no race conditions,
// no full-page takeover. The iframe is invisible, loads content, then auto-prints.
window.printLabel = function (containerId, labelSize, copies) {
    const el = document.getElementById(containerId);
    if (!el) return;

    const parts = (labelSize || '50x25').split('x');
    const w = parseInt(parts[0]) || 50;
    const h = parseInt(parts[1]) || 25;

    // Serialize the SVG properly so it survives srcdoc
    let innerHtml = '';
    Array.from(el.children).forEach(function (child) {
        if (child.tagName === 'CANVAS') {
            const img = document.createElement('img');
            img.src = child.toDataURL('image/png');
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            innerHtml += img.outerHTML;
        } else if (child.tagName === 'SVG' || child.tagName === 'svg') {
            // Inline SVG — serialize with namespace
            innerHtml += new XMLSerializer().serializeToString(child);
        } else {
            innerHtml += child.outerHTML;
        }
    });

    const css = [
        '@page { size: ' + w + 'mm ' + h + 'mm; margin: 2mm; }',
        'html, body { margin: 0; padding: 0; width: ' + w + 'mm; background: white; }',
        'body { display: flex; flex-direction: column; align-items: center;',
        '       justify-content: center; font-family: sans-serif; font-size: 8pt; gap: 1mm; }',
        'svg { width: 100%; height: auto; display: block; }',
        'img { width: 100%; height: auto; display: block; }',
    ].join(' ');

    const srcdoc = '<!DOCTYPE html><html><head>'
        + '<style>' + css + '</style>'
        + '</head><body>' + innerHtml
        + '<script>window.onload = function() { window.focus(); window.print(); }<\/script>'
        + '</body></html>';

    // Remove any stale iframe
    const old = document.getElementById('print-iframe');
    if (old) old.remove();

    const iframe = document.createElement('iframe');
    iframe.id = 'print-iframe';
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;';
    iframe.srcdoc = srcdoc;
    document.body.appendChild(iframe);

    // Clean up after a delay (print dialog keeps iframe alive)
    iframe.onload = function () {
        setTimeout(function () { iframe.remove(); }, 30000);
    };
};
