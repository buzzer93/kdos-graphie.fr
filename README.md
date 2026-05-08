# KDOS-GRAPHIE

Etat du projet au 4 mai 2026.

## Processus de commande personnalisee (cible metier)

Ce chapitre formalise le parcours commande souhaite pour les produits personnalisables.

### Etape 1 - Panier et personnalisation

- Le client ajoute des produits dans son panier.
- Chaque ligne de panier peut contenir:
  - une image de personnalisation;
  - un texte personnalise;
  - ou les deux selon les capacites du produit.

### Etape 2 - Validation du panier sans compte client

- Le client passe commande en mode invite (sans creation de compte).
- Il renseigne un formulaire de commande avec au minimum:
  - nom;
  - prenom;
  - email;
  - telephone;
  - adresse de livraison;
  - informations complementaires (optionnel).
- A validation du formulaire, la commande est creee avec le statut: `a_confirmer`.
- Un email automatique est envoye au client pour confirmer la bonne reception de sa demande.
- Cet email precise que l'atelier peut recontacter le client par email ou telephone si des precisions sont necessaires.

### Etape 3 - Traitement de la demande par le vendeur

- Le vendeur analyse la demande depuis l'interface admin.
- Il peut contacter le client par email ou telephone.
- Il peut:
  - refuser la commande avec un motif;
  - confirmer la commande pour lancement du paiement.
- Si la commande est refusee:
  - statut: `refuse`;
  - email client contenant le motif de refus.

### Etape 4 - Envoi du lien de paiement

- Si la commande est confirmee, un email est envoye au client avec un lien de paiement Stripe.
- La commande passe au statut: `en_attente_paiement`.

### Etape 5 - Paiement du client

- Le client paie via Stripe.
- Apres paiement valide:
  - le vendeur recoit une notification email;
  - le client recoit une confirmation de paiement;
  - la commande passe au statut: `a_faire`.

### Etape 6 - Preparation et expedition

- Le vendeur prepare puis expedie la commande.
- Il peut renseigner si necessaire:
  - numero de suivi;
  - societe de livraison.
- Une fois terminee, la commande passe au statut: `termine`.
- Le client recoit un email avec les informations de livraison et de suivi.

### Etape 7 - Archivage et suppression

- Les commandes `annule`, `refuse` et `termine` peuvent etre supprimees rapidement depuis l'admin.
- Avant suppression, les donnees importantes client/commande doivent etre sauvegardees dans une archive.
- La suppression definitive n'est autorisee qu'apres archivage reussi.

### Etats de commande cibles

- `a_confirmer`
- `en_attente_paiement`
- `a_faire`
- `termine`
- `refuse`
- `annule`

### Regles metier minimales

- Le motif est obligatoire pour un refus.
- Une commande payee ne peut pas revenir a un statut de pre-paiement sans action explicite et tracee.
- Les transitions de statut doivent passer par un service metier dedie (pas de changement libre en formulaire).
- Les actions sensibles (refus, confirmation, passage paye, cloture, suppression) doivent etre tracees.
- Le statut de paiement doit etre confirme par le flux serveur Stripe (webhook/verification), pas uniquement par le retour navigateur.

## Features actuellement presentes

- Authentification admin (login/logout) avec protection des routes /admin.
- CRUD admin complet:
  - Produits (liste, filtre, pagination, create/edit/show/delete, upload image)
  - Categories (liste, filtre, pagination, create/edit/show/delete)
  - Commandes (liste, filtre, pagination, create/edit/show/delete, edition des lignes)
- Catalogue public:
  - Liste des produits visibles avec filtre categorie/recherche + pagination
  - Detail produit visible
- Services techniques:
  - generation de slug unique (produit/categorie)
  - stockage image produit

## Comparaison avec l'ancien projet similaire

Par rapport aux features documentees dans l'ancien projet, il manque encore des briques importantes:

- Paiement Stripe complet (PaymentIntent + parcours de paiement client)
- Webhook Stripe securise (signature, mapping des evenements)
- Workflow metier de cycle de vie commande (pending -> awaiting_payment -> paid -> done/rejected)
- Envoi d'emails metier (lien de paiement, refus, cloture, notifications admin)
- Notification admin asynchrone apres paiement (Messenger message + handler)
- Formulaire contact serveur + anti-spam (reCAPTCHA)
- Mot de passe oublie admin (token, expiration, invalidation)
- Ecran de parametres admin (email master admin / regeneration mot de passe)
- Panier session avance (controle stock, recapitulatif fiable, coherence avec paiement)
- Tests metier (services, workflows, webhooks, fonctionnalites critiques)

## TODO backlog

### Point d'avancement (deja fait)

- [x] Generer et appliquer la migration Doctrine pour Product/Category/Order/OrderItem.
- [x] Mettre a jour l'entite Order et les transitions pour les statuts cibles (a_confirmer, en_attente_paiement, a_faire, termine, refuse, annule).
- [x] Ajouter les champs checkout invite (prenom, telephone, adresse livraison, informations complementaires).
- [x] Ajouter support personnalisation par ligne de commande (texte/image selon produit).
- [x] Ajouter archivage avant suppression des commandes annulees/refusees/terminees.
- [x] Ajouter envois email metier de base pour le cycle de vie de commande.
- [x] Ajouter des tests fonctionnels/unitaires sur les parcours admin et le cycle de vie commande.
- [x] Definir les regles d'annulation — admin uniquement, statuts a_confirmer / en_attente_paiement / a_faire annulables.
- [x] Definir la gestion des remboursements — tracabilite manuelle (refund_note + refunded_at), remboursement Stripe effectue manuellement.
- [x] Ajouter contraintes validation metier — suppression stock non utilise, prix recalcule depuis la base au checkout.
- [x] Ajouter formulaire contact backend — ContactController + ContactType + CSRF + reCAPTCHA v3 (RecaptchaService).
- [x] Ajouter flux mot de passe oublie admin — symfonycasts/reset-password-bundle, entity + migration + controller + mailer + templates.
- [x] Ajouter ecran admin parametres globaux — SiteSetting entity + migration + SettingsController + template.
- [x] Ajouter section deploiement complete — variables Stripe, worker Messenger, webhook, Caddy.

### Admin UX (inspire du projet similaire)

- [ ] Dashboard admin : cartes de synthese par statut de commande (compteurs, liens filtres, total CA).
- [ ] Composant `_status_badge.html.twig` reutilisable avec couleurs semantiques par statut.
- [ ] Service `OrderAdminActionAvailabilityResolver` : centralise les actions disponibles par statut (canAccept, canReject, canCancel, canComplete, canSendPaymentLink).
- [ ] Action admin "Demander des informations complementaires" — email automatique au client depuis la fiche commande.
- [ ] Action admin "Renvoyer le lien de paiement" — disponible en statut en_attente_paiement.
- [ ] Action admin "Purge" — suppression en masse des commandes terminees/refusees/annulees (avec archivage obligatoire).
- [ ] Parametres admin : coordonnees contact editables (telephone, email, adresse, reseaux sociaux).
- [ ] Parametres admin : changement identifiants master admin (email + mot de passe).
- [ ] Parametres admin : reordonnancement des categories par drag-and-drop.

### Cycle de vie commande

- [ ] Adapter OrderLifecycleService au processus metier cible complet (confirmation, paiement Stripe, passage a faire, cloture, refus/annulation).
- [ ] Completer les templates email (refus, remboursement, livraison avec numero de suivi).
- [ ] Ajouter message/handler Messenger pour notification admin post-paiement.

### Panier

- [ ] Implémenter le panier session complet (ajout/retrait, quantites, recalculs, controle stock).

### Tests

- [ ] Ajouter tests unitaires/integration sur services metier (lifecycle, mailer, messenger, webhook).

### Stripe (en dernier)

- [ ] Implementer Stripe cote serveur (creation PaymentIntent) et interface paiement cote client.
- [ ] Ajouter routes de paiement : page paiement, creation intent, page confirmation.
- [ ] Ajouter webhook Stripe securise (verification signature + idempotence).
- [ ] Ajouter tests unitaires/integration sur services Stripe et webhook.

## Déploiement production

### Variables d'environnement requises

Configurer dans `.env.local` (ne jamais committer les secrets) :

```dotenv
APP_ENV=prod
APP_SECRET=<valeur aléatoire forte>

# Base de données
DATABASE_URL="mysql://user:password@host:3306/kdos_graphie?serverVersion=8.0.36&charset=utf8mb4"

# Mailer
MAILER_DSN=smtp://user:password@host:587
MAILER_FROM_ADDRESS=noreply@kdos-graphie.fr
CONTACT_RECIPIENT_ADDRESS=contact@kdos-graphie.fr

# Stripe
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# reCAPTCHA v3 (https://www.google.com/recaptcha/admin)
RECAPTCHA_SITE_KEY=...
RECAPTCHA_SECRET_KEY=...
```

### Checklist déploiement initial

```bash
git clone git@github.com:<repo> /var/www/kdos-graphie
cd /var/www/kdos-graphie
composer install --no-dev --optimize-autoloader
cp .env .env.local          # puis renseigner les variables ci-dessus
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
chmod -R 775 var/
chown -R www-data:www-data var/ public/
```

### Checklist mise à jour

```bash
git pull
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
```

### Worker Messenger (tâches asynchrones)

Le transport Doctrine est configuré. En production, créer un service systemd ou supervisor.

**Exemple supervisor** (`/etc/supervisor/conf.d/kdos-messenger.conf`) :

```ini
[program:kdos-messenger]
command=php /var/www/kdos-graphie/bin/console messenger:consume async --time-limit=3600
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/supervisor/kdos-messenger.log
```

**Activer et démarrer** :
```bash
supervisorctl reread && supervisorctl update && supervisorctl start kdos-messenger
```

**Surveiller la file** :
```bash
php bin/console messenger:stats
php bin/console messenger:failed:show   # messages en erreur
```

### Webhook Stripe

Déclarer l'URL `https://kdos-graphie.fr/webhook/stripe` dans le Dashboard Stripe.
Événements requis : `payment_intent.succeeded`, `payment_intent.payment_failed`, `checkout.session.completed`.
La signature doit être vérifiée côté serveur avec `STRIPE_WEBHOOK_SECRET` — ne jamais faire confiance au seul retour navigateur.

### Caddy (exemple de configuration)

```caddy
kdos-graphie.fr {
    root * /var/www/kdos-graphie/public
    php_fastcgi unix//run/php/php8.4-fpm.sock
    encode gzip
    file_server
    try_files {path} /index.php
}
```

## Commandes utiles

- Tests: php bin/phpunit
- Lint container: php bin/console lint:container
- Lint Twig: php bin/console lint:twig templates
- Routes: php bin/console debug:router
