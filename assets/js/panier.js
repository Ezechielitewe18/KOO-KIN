(function () {
    'use strict';

    const drawer = document.getElementById('drawer-panier');
    const overlayPanier = document.getElementById('overlay-panier');
    const contenu = document.querySelector('.js-panier-contenu');
    const totalEl = document.querySelector('.js-panier-total');
    const nbEls = document.querySelectorAll('.js-panier-nb');

    let token = null;

    const fetchToken = () => {
        return fetch(window.KOOKIN_URL + '/api/token.php')
            .then((r) => r.json())
            .then((d) => { token = d.token; return d.token; });
    };

    const req = (action, data) => {
        const body = new FormData();
        body.append('action', action);
        Object.keys(data).forEach((k) => body.append(k, data[k]));
        return fetch(window.KOOKIN_URL + '/api/panier.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': token },
            body,
        }).then((r) => r.json());
    };

    const formatPrix = (n) => Number(n).toLocaleString('fr-FR').replace(/\u202F/g, ' ') + ' CDF';

    const render = (state) => {
        if (totalEl) totalEl.textContent = formatPrix(state.total);
        nbEls.forEach((el) => { el.textContent = state.count; });

        if (!contenu) return;

        if (!state.items.length) {
            contenu.innerHTML = '<p style="text-align:center;color:#6f6454;padding:2rem 0">Votre panier est vide.<br><small>Ajoutez de bons plats de chez nous !</small></p>';
            return;
        }

        contenu.innerHTML = state.items.map((item) => {
            const photo = item.photo_url || (window.KOOKIN_URL + '/assets/img/placeholders/plat.svg');
            return `
                <div class="panier-item">
                    <div class="panier-item__media"><img src="${photo}" alt="${item.nom}"></div>
                    <div class="panier-item__corps">
                        <div class="panier-item__nom">${item.nom}</div>
                        <div class="panier-item__prix">${formatPrix(item.prix)}</div>
                        <div class="qte">
                            <button type="button" data-act="minus" data-id="${item.id}">−</button>
                            <span>${item.quantite}</span>
                            <button type="button" data-act="plus" data-id="${item.id}">+</button>
                        </div>
                        <button type="button" class="panier-item__sup" data-act="remove" data-id="${item.id}">Retirer</button>
                    </div>
                    <div class="panier-item__total" style="font-weight:600;font-size:.95rem">${formatPrix(item.prix * item.quantite)}</div>
                </div>`;
        }).join('');
    };

    const rafraichir = () => req('view', {})
        .then((d) => { if (d.items) render(d); })
        .catch(() => {});

    const ouvrir = () => {
        drawer.classList.add('ouvert');
        overlayPanier.classList.add('visible');
        document.body.style.overflow = 'hidden';
        rafraichir();
    };

    const fermer = () => {
        drawer.classList.remove('ouvert');
        overlayPanier.classList.remove('visible');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('.js-panier-ouvrir').forEach((b) => b.addEventListener('click', ouvrir));
    document.querySelectorAll('.js-panier-fermer').forEach((b) => b.addEventListener('click', fermer));
    if (overlayPanier) overlayPanier.addEventListener('click', fermer);

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fermer();
    });

    contenu.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-act]');
        if (!btn) return;
        const id = btn.dataset.id;
        const act = btn.dataset.act;
        if (act === 'remove') {
            req('remove', { id }).then((d) => { if (d.items) render(d); });
            return;
        }
        const qteEl = btn.closest('.qte').querySelector('span');
        const qte = parseInt(qteEl.textContent, 10);
        const nouvelle = act === 'plus' ? qte + 1 : Math.max(0, qte - 1);
        if (nouvelle === 0) {
            req('remove', { id }).then((d) => { if (d.items) render(d); });
            return;
        }
        req('update', { id, quantite: nouvelle }).then((d) => { if (d.items) render(d); });
    });

    const ajouter = (btn) => {
        const envoi = () => req('add', { id: btn.dataset.panier, quantite: btn.dataset.qte || 1 })
            .then((d) => { if (d.items) render(d); });
        if (!token) { fetchToken().then(envoi); } else { envoi(); }
    };

    document.querySelectorAll('[data-panier]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            btn.disabled = true;
            ajouter(btn).finally(() => { btn.disabled = false; });
        });
    });

    fetchToken().then(rafraichir);
})();