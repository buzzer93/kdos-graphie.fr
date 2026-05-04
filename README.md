# KDOS-GRAPHIE

Etat du projet au 4 mai 2026.

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

## TODO backlog (a faire plus tard)

- [ ] Generer et appliquer la migration Doctrine pour Product/Category/Order/OrderItem.
- [ ] Ajouter contraintes de validation metier manquantes (unicite reference commande, garde-fous stock/prix).
- [ ] Ajouter formulaire de contact backend (validation + CSRF + reCAPTCHA).
- [ ] Ajouter flux mot de passe oublie admin (request, token, expiration, reset).
- [ ] Ajouter ecran admin de parametres globaux (master admin).
- [ ] Ajouter tests fonctionnels CRUD admin et securite des acces.
- [ ] Ajouter une section deployment complete (worker messenger, variables Stripe, checklist prod).

### Niveau 1 - Fondations

- [ ] Implementer le panier session complet (ajout/retrait, quantites, recalculs, controle stock).
- [ ] Ajouter un service de cycle de vie commande (acceptation, relance, refus, annulation, cloture).
- [ ] Ajouter envois email metier (client et admin) avec templates Twig dedies.
- [ ] Ajouter message/handler Messenger pour notification admin post-paiement.

### Niveau 2 - Fonctionnalites transverses

- [ ] Ajouter tests unitaires/integration sur services metier (lifecycle, mailer, messenger, webhook).

### Niveau 3 - Stripe (a faire en dernier)

- [ ] Implementer Stripe cote serveur (creation PaymentIntent) et interface paiement cote client.
- [ ] Ajouter routes de paiement: page paiement, creation intent, page confirmation.
- [ ] Ajouter webhook Stripe securise (verification signature + idempotence).
- [ ] Ajouter tests unitaires/integration sur services Stripe et webhook.

## Commandes utiles

- Tests: php bin/phpunit
- Lint container: php bin/console lint:container
- Lint Twig: php bin/console lint:twig templates
- Routes: php bin/console debug:router
