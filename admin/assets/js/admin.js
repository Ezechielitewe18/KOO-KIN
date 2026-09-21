(function () {
    'use strict';

    const sidebar = document.getElementById('adm-sidebar');
    const overlay = document.getElementById('adm-overlay');
    const burger = document.getElementById('adm-burger');

    const fermer = () => {
        if (sidebar) sidebar.classList.remove('ouvert');
        if (overlay) overlay.classList.remove('visible');
        document.body.style.overflow = '';
    };

    if (burger && sidebar && overlay) {
        burger.addEventListener('click', () => {
            sidebar.classList.add('ouvert');
            overlay.classList.add('visible');
            document.body.style.overflow = 'hidden';
        });
        overlay.addEventListener('click', fermer);
        window.addEventListener('keydown', (e) => { if (e.key === 'Escape') fermer(); });
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    document.querySelectorAll('[data-aprecu]').forEach((input) => {
        const cible = document.getElementById(input.dataset.aprecu);
        if (!cible) return;
        input.addEventListener('change', () => {
            const fichier = input.files && input.files[0];
            if (fichier) cible.src = URL.createObjectURL(fichier);
        });
    });

    document.querySelectorAll('[data-basculer]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const cible = document.getElementById(btn.dataset.basculer);
            if (cible) {
                cible.hidden = !cible.hidden;
                if (!cible.hidden) {
                    const premier = cible.querySelector('input, select, textarea');
                    if (premier) premier.focus();
                }
            }
        });
    });
})();
