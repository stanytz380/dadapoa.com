// Language translations (kiingereza na kiswahili)
const translations = {
    en: {
        welcome: "Welcome to Dadapoa",
        providers: "Service Providers Near You",
        no_providers: "No service providers yet.",
        search_placeholder: "Search by region or name",
        contact_admin: "Contact Admin",
        join_now: "Join Now",
        chat_with: "Chat with"
    },
    sw: {
        welcome: "Karibu Dadapoa",
        providers: "Watoa Huduma Karibu Nawe",
        no_providers: "Hakuna watoa huduma bado.",
        search_placeholder: "Tafuta kwa mkoa au jina",
        contact_admin: "Wasiliana na Admin",
        join_now: "Jiunge Sasa",
        chat_with: "Ongea na"
    }
};

let currentLang = localStorage.getItem('dadapoa_lang') || 'sw';

function setLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('dadapoa_lang', lang);
    document.querySelectorAll('[data-lang-key]').forEach(el => {
        const key = el.getAttribute('data-lang-key');
        if (translations[lang][key]) {
            if (el.tagName === 'INPUT' && el.placeholder !== undefined) {
                el.placeholder = translations[lang][key];
            } else {
                el.innerText = translations[lang][key];
            }
        }
    });
    // Update special buttons like chat_with dynamically
    document.querySelectorAll('.chat-btn').forEach(btn => {
        const nickname = btn.getAttribute('data-nickname');
        if (nickname) btn.innerText = `${translations[lang].chat_with} ${nickname}`;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('lang-en')?.addEventListener('click', () => setLanguage('en'));
    document.getElementById('lang-sw')?.addEventListener('click', () => setLanguage('sw'));
    setLanguage(currentLang);

    // Disable right click (ikiwa haijafanywa kwenye html)
    document.addEventListener('contextmenu', (e) => e.preventDefault());
});
