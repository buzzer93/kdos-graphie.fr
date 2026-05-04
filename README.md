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

## TODO backlog (a faire plus tard)

### Point d'avancement (deja fait)

- [x] Generer et appliquer la migration Doctrine pour Product/Category/Order/OrderItem.
- [x] Mettre a jour l'entite Order et les transitions pour les statuts cibles (a_confirmer, en_attente_paiement, a_faire, termine, refuse, annule).
- [x] Ajouter les champs checkout invite (prenom, telephone, adresse livraison, informations complementaires).
- [x] Ajouter support personnalisation par ligne de commande (texte/image selon produit).
- [x] Ajouter archivage avant suppression des commandes annulees/refusees/terminees.
- [x] Ajouter envois email metier de base pour le cycle de vie de commande.
- [x] Ajouter des tests fonctionnels/unitaires sur les parcours admin et le cycle de vie commande.

### Reste a faire

- [ ] Definir precisement a quel moment une commande peut etre annulee (avant paiement, apres paiement, en production) et qui a le droit d'annuler.
- [ ] Definir la gestion des remboursements quand une commande deja payee est annulee (partiel/complet, delai, tracabilite, notifications client).
- [ ] Ajouter contraintes de validation metier manquantes (unicite reference commande, garde-fous stock/prix).
- [ ] Ajouter formulaire de contact backend (validation + CSRF + reCAPTCHA).
- [ ] Ajouter flux mot de passe oublie admin (request, token, expiration, reset).
- [ ] Ajouter ecran admin de parametres globaux (master admin).
- [ ] Ajouter une section deployment complete (worker messenger, variables Stripe, checklist prod).

### Niveau 1 - Fondations

- [ ] Implementer le panier session complet (ajout/retrait, quantites, recalculs, controle stock).
- [ ] Adapter le service de cycle de vie commande au processus metier cible (confirmation, paiement Stripe, passage a faire, cloture, refus/annulation).
- [ ] Completer les envois email metier (templates Twig definitifs, cas de remboursement et relances avancees).
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
