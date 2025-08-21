$(document).ready(function() {
            // Initialize owl carousel without autoplay
            $(".car__item__pic__slider").owlCarousel({
                loop: true,
                margin: 0,
                items: 1,
                dots: true,
                nav: true,
                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                smartSpeed: 1200,
                autoHeight: false,
                autoplay: false // Removed autoplay
            });
            
            // Set initial language
            let currentLanguage = localStorage.getItem('language') || 'en';
            applyLanguage(currentLanguage);
            
            // Language selector functionality
            $('.language-option').click(function(e) {
                e.preventDefault();
                const lang = $(this).data('lang');
                currentLanguage = lang;
                localStorage.setItem('language', lang);
                applyLanguage(lang);
                
                // Update active state
                $('.language-option').removeClass('active');
                $(this).addClass('active');
                
                // Update flag icon
                $('#languageDropdown').html($(this).html());
            });
            
            // Apply language translations
            function applyLanguage(lang) {
                $('[data-translate]').each(function() {
                    const key = $(this).data('translate');
                    if (translations[lang] && translations[lang][key]) {
                        $(this).text(translations[lang][key]);
                    }
                });
            }
        });