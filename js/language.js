// Translation initialization function
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,sq',
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
        autoDisplay: false
    }, 'google_translate_element');
    
    // Remove Google's branding
    const style = document.createElement('style');
    style.innerHTML = `
        .goog-te-banner-frame { display: none !important; }
        .goog-te-gadget-icon { display: none !important; }
        .goog-te-gadget-simple { background-color: transparent !important; border: none !important; }
        .goog-te-menu-value span { color: #000 !important; }
        body { top: 0 !important; }
    `;
    document.head.appendChild(style);
}

// Language selector functionality
document.addEventListener('DOMContentLoaded', function() {
    const languageOptions = document.querySelectorAll('.language-option');
    const currentLanguage = document.getElementById('currentLanguage');
    
    function setLanguage(languageCode) {
        // Update UI
        const selectedOption = document.querySelector(`[data-lang="${languageCode}"]`);
        if (selectedOption) {
            currentLanguage.innerHTML = selectedOption.innerHTML;
        }
        
        // Trigger Google Translate
        const checkInterval = setInterval(function() {
            const translateSelect = document.querySelector('.goog-te-combo');
            if (translateSelect) {
                clearInterval(checkInterval);
                translateSelect.value = languageCode;
                translateSelect.dispatchEvent(new Event('change'));
                
                // Force page refresh for better compatibility
                setTimeout(() => {
                    if (!document.querySelector('.goog-te-menu-frame')) {
                        window.location.reload();
                    }
                }, 1000);
            }
        }, 100);
        
        localStorage.setItem('autobuba_language', languageCode);
    }
    
    // Set click handlers
    languageOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const langCode = this.getAttribute('data-lang');
            setLanguage(langCode);
        });
    });
    
    // Apply saved language
    const savedLanguage = localStorage.getItem('autobuba_language');
    if (savedLanguage) {
        setLanguage(savedLanguage);
    }
});

// Load Google Translate script with delay to prevent conflicts
window.addEventListener('load', function() {
    // First check if Google Translate is already loaded
    if (typeof google === 'undefined' || !google.translate) {
        const script = document.createElement('script');
        script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.body.appendChild(script);
    }
    
    // Fallback in case translation doesn't work
    setTimeout(() => {
        if (!document.querySelector('.goog-te-combo')) {
            console.warn('Google Translate failed to load, implementing fallback');
            const savedLang = localStorage.getItem('autobuba_language') || 'en';
            document.querySelectorAll('[data-translate]').forEach(el => {
                const key = el.getAttribute('data-translate');
                if (savedLang === 'sq') {
                    // Simple Albanian translations - you should expand this
                    const translations = {
                        "Cars": "Makinat",
                        "Blog": "Blog",
                        "About": "Rreth Nesh",
                        "Contact": "Kontakt"
                        // Add more translations as needed
                    };
                    if (translations[key]) {
                        el.textContent = translations[key];
                    }
                }
            });
        }
    }, 3000);
});