(function () {
    'use strict';

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const burger = document.getElementById('js-burger');

    const ouvrirSidebar = () => {
        sidebar.classList.add('ouvert');
        overlay.classList.add('visible');
        document.body.style.overflow = 'hidden';
    };
    const fermerSidebar = () => {
        sidebar.classList.remove('ouvert');
        overlay.classList.remove('visible');
        document.body.style.overflow = '';
    };

    if (burger) {
        burger.addEventListener('click', ouvrirSidebar);
        overlay.addEventListener('click', fermerSidebar);
    }

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fermerSidebar();
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.animer').forEach((el) => observer.observe(el));

    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        const image = lightbox.querySelector('img');
        const fermerLightbox = () => {
            lightbox.classList.remove('ouvert');
            document.body.style.overflow = '';
        };
        document.querySelectorAll('[data-lightbox]').forEach((el) => {
            const ouvrir = () => {
                image.src = el.dataset.lightbox;
                lightbox.classList.add('ouvert');
                document.body.style.overflow = 'hidden';
            };
            el.addEventListener('click', ouvrir);
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); ouvrir(); }
            });
        });
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox || e.target.closest('#lightbox-fermer')) fermerLightbox();
        });
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') fermerLightbox();
        });
    }
})();