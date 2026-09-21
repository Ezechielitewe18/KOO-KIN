(function () {
    'use strict';

    const bouton = document.getElementById('chat-bouton');
    const fenetre = document.getElementById('chat-fenetre');
    const corps = document.getElementById('chat-corps');
    const choix = document.getElementById('chat-choix');

    const URL = window.KOOKIN_URL || '';

    const reponses = {
        menu: {
            texte: 'Voici notre menu : spécialités congolaises authentiques, avec prix en CDF.',
            lien: { txt: 'Voir le menu', href: URL + '/menu.php' },
            choix: ['Voir le menu'],
        },
        commander: {
            texte: 'Commandez en quelques secondes : choisissez vos plats, puis votre mode (sur place, à emporter ou livraison).',
            lien: { txt: 'Commander maintenant', href: URL + '/commander.php' },
            choix: ['Commander'],
        },
        ou: {
            texte: 'Nous sommes au 38, Avenue Bandundu, Q/Vélodrome, C/Kintambo — Kinshasa. Croisement des avenues Komoriko et Bandundu.',
            lien: { txt: 'Itinéraire', href: (KOOKIN_PARAMS && KOOKIN_PARAMS.map_lien) || 'https://maps.google.com/?q=38+Avenue+Bandundu+Kintambo+Kinshasa' },
            choix: ['Où êtes-vous ?'],
        },
        horaires: {
            texte: 'Nos horaires : Lundi — Dimanche. Nous livrons à partir de 11h.',
            lien: { txt: 'Plus d\'infos', href: URL + '/contact.php' },
            choix: ['Horaires'],
        },
        reserver: {
            texte: 'Réservez votre table en 1 minute : formulaire simple, confirmation rapide.',
            lien: { txt: 'Réserver une table', href: URL + '/reservation.php' },
            choix: ['Réserver'],
        },
        parler: {
            texte: 'Un conseiller KOO-KIN vous répond sous peu. Vous pouvez aussi nous contacter directement sur WhatsApp.',
            lien: { txt: 'WhatsApp', href: 'https://wa.me/' + ((KOOKIN_PARAMS && KOOKIN_PARAMS.whatsapp) || '243994266536') },
            choix: ['Parler à quelqu\'un'],
        },
        defaut: {
            texte: 'Merci ! Un membre de l\'équipe KOO-KIN va vous répondre très vite. En attendant, consultez le menu.',
            lien: { txt: 'Voir le menu', href: URL + '/menu.php' },
            choix: ['Voir le menu', 'Commander', 'Où êtes-vous ?', 'Horaires', 'Réserver', 'Parler à quelqu\'un'],
        },
    };

    const afficherMsg = (texte, qui) => {
        const div = document.createElement('div');
        div.className = 'chat__msg chat__msg--' + qui;
        div.textContent = texte;
        corps.appendChild(div);
        corps.scrollTop = corps.scrollHeight;
    };

    const afficherChoix = (liste) => {
        choix.innerHTML = '';
        liste.forEach((c) => {
            const b = document.createElement('button');
            b.textContent = c;
            b.addEventListener('click', () => traiter(c));
            choix.appendChild(b);
        });
    };

    const traiter = (message) => {
        afficherMsg(message, 'user');
        let cle = 'defaut';
        if (/menu|plat|manger/i.test(message)) cle = 'menu';
        else if (/command|livraison|livrer/i.test(message)) cle = 'commander';
        else if (/où|ou est|localis|adresse/i.test(message)) cle = 'ou';
        else if (/horaire|heure|ouvert/i.test(message)) cle = 'horaires';
        else if (/réserv|table/i.test(message)) cle = 'reserver';
        else if (/parler|aide|quelqu|humain/i.test(message)) cle = 'parler';

        const rep = reponses[cle];
        setTimeout(() => {
            afficherMsg(rep.texte, 'bot');
            if (rep.lien) {
                const a = document.createElement('a');
                a.className = 'btn btn--or btn--sm';
                a.href = rep.lien.href;
                a.target = rep.lien.href.startsWith('http') ? '_blank' : '_self';
                a.textContent = rep.lien.txt;
                a.style.margin = '0 0 0 .2rem';
                corps.appendChild(a);
                corps.scrollTop = corps.scrollHeight;
            }
            afficherChoix(rep.choix);
        }, 450);
    };

    let ouvert = false;
    bouton.addEventListener('click', () => {
        ouvert = !ouvert;
        fenetre.classList.toggle('ouvert', ouvert);
        if (ouvert && corps.children.length === 0) {
            afficherMsg('Bonjour', 'bot');
            afficherMsg('Comment pouvons-nous vous aider ?', 'bot');
            afficherChoix(reponses.defaut.choix);
        }
    });
})();