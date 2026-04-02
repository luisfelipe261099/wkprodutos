(() => {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', () => {
            const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
            menuToggle.setAttribute('aria-expanded', String(!expanded));
            mainNav.classList.toggle('open');
        });
    }

    const revealItems = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && revealItems.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        revealItems.forEach((item, index) => {
            item.style.transitionDelay = `${Math.min(index * 35, 250)}ms`;
            observer.observe(item);
        });
    } else {
        revealItems.forEach((item) => item.classList.add('show'));
    }

    const filterButtons = document.querySelectorAll('[data-filter]');
    const products = document.querySelectorAll('[data-category]');

    if (filterButtons.length > 0 && products.length > 0) {
        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.getAttribute('data-filter') || 'all';

                filterButtons.forEach((btn) => btn.classList.remove('active'));
                button.classList.add('active');

                products.forEach((product) => {
                    const category = product.getAttribute('data-category');
                    const isVisible = filter === 'all' || filter === category;
                    product.style.display = isVisible ? 'block' : 'none';
                });
            });
        });
    }
})();
