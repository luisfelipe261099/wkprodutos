// Viewport Height Fix para Mobile - Karla Wollinger
(function() {
    'use strict';
    
    // Fix para altura da viewport em mobile
    function setVH() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    // Definir no carregamento
    setVH();

    // Redefinir no resize (orientação, teclado virtual)
    window.addEventListener('resize', setVH);
    window.addEventListener('orientationchange', function() {
        setTimeout(setVH, 100);
    });

    // Melhorar performance de touch
    document.addEventListener('touchstart', function() {}, {passive: true});
    document.addEventListener('touchmove', function() {}, {passive: true});
    
    // Debug de toque (apenas em desenvolvimento)
    if (window.location.search.includes('debug=1')) {
        document.addEventListener('touchstart', function(e) {
            console.log('Touch start:', e.touches[0].clientX, e.touches[0].clientY);
        });
    }
})();
