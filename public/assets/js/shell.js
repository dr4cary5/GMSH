document.addEventListener('DOMContentLoaded', () => {
    const iframe = document.getElementById('content-frame');
    const urlInput = document.getElementById('address-bar');
    const form = document.getElementById('url-form');
    const cookieList = document.getElementById('cookie-list');

    // ناوبری با آدرس‌بار
    form.addEventListener('submit', e => {
        e.preventDefault();
        let url = urlInput.value.trim();
        if (!url.startsWith('http')) url = 'https://' + url;
        iframe.src = `/proxy.php?q=${btoa(url)}`;
    });

    // دکمه‌های ناوبری
    document.getElementById('btn-back').onclick = () => iframe.contentWindow.history.back();
    document.getElementById('btn-forward').onclick = () => iframe.contentWindow.history.forward();
    document.getElementById('btn-reload').onclick = () => iframe.src = iframe.src;

    // دریافت کوکی‌ها از بک‌اند
    async function loadCookies() {
        try {
            const res = await fetch('/proxy.php?action=cookies');
            const data = await res.json();
            cookieList.textContent = JSON.stringify(data, null, 2);
        } catch { cookieList.textContent = 'Failed to load'; }
    }
    loadCookies();
    document.getElementById('btn-clear-cookies').onclick = () => {
        fetch('/proxy.php?action=clear-cookies', { method: 'POST' });
        loadCookies();
    };

    // همگام‌سازی آدرس‌بار با تغییرات داخل فریم (postMessage)
    window.addEventListener('message', e => {
        if (e.data?.type === 'url-change' && e.data.url) {
            urlInput.value = e.data.url;
        }
    });
});
