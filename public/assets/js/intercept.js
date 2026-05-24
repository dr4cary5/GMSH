// این اسکریپت توسط Rewriter.php به انتهای <head> تزریق می‌شود
(function() {
    const PROXY_BASE = document.querySelector('script[data-proxy-base]')?.dataset.proxyBase || '';
    if (!PROXY_BASE) return;

    // ۱. رهگیری تغییرات آدرس (History API)
    const _pushState = history.pushState;
    const _replaceState = history.replaceState;
    
    function notifyParent() {
        window.parent.postMessage({ type: 'url-change', url: location.href }, '*');
    }

    history.pushState = function() {
        _pushState.apply(history, arguments);
        notifyParent();
    };
    history.replaceState = function() {
        _replaceState.apply(history, arguments);
        notifyParent();
    };

    // ۲. رهگیری fetch و XMLHttpRequest
    const _fetch = window.fetch;
    window.fetch = async function(...args) {
        let url = args[0];
        if (typeof url === 'string' && !url.startsWith('http')) {
            url = new URL(url, location.origin).href;
        }
        if (typeof url === 'string' && !url.startsWith(PROXY_BASE)) {
            args[0] = `${PROXY_BASE}/proxy.php?q=${btoa(url)}`;
        }
        return _fetch.apply(this, args);
    };

    // ۳. رهگیری کلیک‌ها و لینک‌های جدید
    document.addEventListener('click', e => {
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
            e.preventDefault();
            window.location.href = href.startsWith('http') ? href : new URL(href, location.href).href;
        }
    });

    window.addEventListener('load', notifyParent);
})();
