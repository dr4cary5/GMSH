document.addEventListener('DOMContentLoaded', () => {
    const iframe = document.getElementById('content-frame');
    const urlInput = document.getElementById('address-bar');
    const form = document.getElementById('url-form');

    // تابع ایمن ناوبری
    function navigate(url) {
        url = url.trim();
        if (!url) return;
        if (!url.match(/^https?:\/\//i)) url = 'https://' + url;
        
        try {
            // کدگذاری ایمن برای یونیکد و کاراکترهای خاص
            const encoded = btoa(unescape(encodeURIComponent(url)));
            iframe.src = `/proxy.php?q=${encoded}`;
            urlInput.value = url; // همگام‌سازی ظاهری
        } catch (e) {
            console.error('Navigation failed:', e);
            alert('URL نامعتبر یا غیرقابل کدگذاری');
        }
    }

    // ۱. ارسال فرم (Enter یا کلیک دکمه)
    form.addEventListener('submit', e => {
        e.preventDefault();
        navigate(urlInput.value);
    });

    // ۲. رهگیری مستقیم کلید Enter (پشتیبان قطعی)
    urlInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            navigate(urlInput.value);
        }
    });

    // ۳. دکمه‌های ناوبری
    document.getElementById('btn-back').onclick = () => iframe.contentWindow.history.back();
    document.getElementById('btn-forward').onclick = () => iframe.contentWindow.history.forward();
    document.getElementById('btn-reload').onclick = () => iframe.src = iframe.src;

    // ۴. مدیریت کوکی‌ها
    async function loadCookies() {
        try {
            const res = await fetch('/proxy.php?action=cookies');
            const data = await res.json();
            document.getElementById('cookie-list').textContent = JSON.stringify(data, null, 2);
        } catch { /* ignore */ }
    }
    loadCookies();
    
    document.getElementById('btn-clear-cookies').onclick = () => {
        fetch('/proxy.php?action=clear-cookies', { method: 'POST' }).then(loadCookies);
    };

    // ۵. همگام‌سازی آدرس‌بار با تغییرات داخل iframe
    window.addEventListener('message', e => {
        if (e.data?.type === 'url-change' && e.data.url) {
            urlInput.value = e.data.url;
        }
    });
});
