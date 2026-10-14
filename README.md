# Kdos-Graphie

## Description

Boutique en ligne e-commerce dédiée aux créations graphiques personnalisées. Le site propose un catalogue de produits personnalisables, un tunnel de commande avec paiement en ligne, un espace utilisateur, et une interface d'administration pour la gestion des commandes et du catalogue.

## Stack technique

- **Backend :** PHP 8.4, Symfony 8.0, Doctrine ORM 3, Doctrine Migrations 4
- **Frontend :** Twig, Tailwind CSS, AssetMapper + Importmap, Stimulus, Symfony UX Turbo
- **Paiement :** Stripe (PaymentIntent + Elements)
- **Sécurité :** Symfony Security + `symfonycasts/reset-password-bundle`
- **Asynchrone :** Symfony Messenger
- **Mailer :** Symfony Mailer (Brevo SMTP)
- **Outils :** Composer, Symfony Flex, Docker

## Prérequis

### Sans Docker
- PHP >= 8.4
- Composer >= 2
- MySQL 8.0
- Symfony CLI (recommandé)

### Avec Docker
- Docker + Docker Compose

## Installation

### Via Docker (recommandé en dev)

```bash
# 1. Cloner le dépôt
git clone git@github.com:buzzer93/kdos-graphie.fr.git
cd Kdos-Graphie.fr

# 2. Copier les variables d'environnement Docker
cp .env.docker .env.local
# Ajuster les valeurs si nécessaire

# 3. Démarrer les conteneurs
make up

# 4. Installer les dépendances et initialiser la base
make install
make db-reset
```

### Sans Docker

```bash
# 1. Cloner le dépôt
git clone git@github.com:buzzer93/kdos-graphie.fr.git
cd Kdos-Graphie.fr

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Renseigner DATABASE_URL, APP_SECRET, MAILER_DSN, STRIPE_*

# 4. Initialiser la base
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. (Optionnel) Charger les fixtures
php bin/console doctrine:fixtures:load

# 6. Lancer le serveur
symfony serve
```

## Commandes Make (Docker)

```bash
make up           # Démarrer les conteneurs
make down         # Arrêter les conteneurs
make shell        # Ouvrir un shell dans le conteneur PHP
make install      # Installer Composer + importmap
make db-migrate   # Lancer les migrations
make db-fixtures  # Charger les fixtures
make db-reset     # Migrations + fixtures
make test         # Lancer les tests PHPUnit
make lint         # Vérifier YAML, Twig, conteneur Symfony
```

## Fonctionnalités

### Côté boutique
- Page d'accueil
- Catalogue de produits personnalisables
- Formulaire de commande personnalisée
- Paiement en ligne via Stripe
- Formulaire de contact
- Inscription / connexion / récupération de mot de passe
- Emails transactionnels

### Côté admin
- Dashboard avec gestion des commandes
- Workflow de commande avec statuts dédiés
- CRUDs pour les produits et contenus du site

## Processus de commande personnalisée (cible métier)

### Parcours client (user stories)

**1. Découverte et configuration**
- Le visiteur parcourt le catalogue et choisit un produit.
- Il configure ses options (taille, couleur, finition, etc.) et renseigne une personnalisation : texte libre, téléchargement d'un fichier graphique (logo, photo), et/ou une demande spéciale.
- Il ajoute le produit au panier. Le panier est sauvegardé en session (pas besoin de compte).

**2. Validation de la commande**
- Le visiteur accède au panier, vérifie son contenu, puis clique sur "Commander".
- Il remplit le formulaire de commande : prénom, nom, email, téléphone, adresse de livraison.
- Il soumet le formulaire. **Aucun paiement n'est demandé à ce stade.**

**3. Deux chemins possibles selon le contenu du panier**

| Cas | Condition | Statut initial | Ce que le client reçoit |
|---|---|---|---|
| **Commande standard** | Aucun fichier ni demande spéciale | `a_confirmer` | Email "Demande reçue" |
| **Commande sur devis** | Au moins un fichier uploadé ou une demande spéciale | `en_attente_devis` | Email "Demande reçue" (avec mention qu'un devis suivra) |

**4. Attente de confirmation / devis**
- Le client attend que l'admin examine sa demande.
- L'admin peut lui envoyer un message pour demander des précisions (email "Informations complémentaires requises").

**5. Réception du lien de paiement**
- Pour une commande standard : l'admin accepte → le client reçoit un email "Commande confirmée" avec un lien de paiement Stripe.
- Pour une commande sur devis : l'admin envoie un devis (prix + description de la réalisation) → le client reçoit un email "Votre devis" avec le détail et un lien de paiement Stripe.

**6. Paiement en ligne**
- Le client clique sur le lien de paiement, arrive sur la page de paiement Stripe hébergée sur le site.
- Il entre ses coordonnées bancaires. Stripe gère la sécurité PCI-DSS, le site ne stocke jamais les données de carte.
- Une fois le paiement validé, le client reçoit un email "Paiement confirmé".

**7. Réalisation et expédition**
- L'artisane réalise la commande. La commande est visible en admin au statut `a_faire`.
- Quand le colis est expédié, l'admin clique "Terminer" → le client reçoit un email "Commande expédiée".

**8. Cas de refus ou annulation**
- Si la commande est refusée (demande irréalisable) : le client reçoit un email "Commande refusée" avec le motif.
- Si la commande est annulée (ex : client ne paie pas, problème d'approvisionnement) : le client reçoit un email "Commande annulée" avec le motif.

---

### Parcours admin (admin stories)

**Réception d'une nouvelle commande**
- L'admin reçoit un email `[Admin] Nouvelle commande : ORD-XXXXXXXX-XXXXXX` dans sa boîte Gmail.
- Elle se connecte à `/admin` et retrouve la commande dans la liste avec son statut.

**Actions disponibles par statut**

| Statut | Libellé affiché | Actions disponibles |
|---|---|---|
| `en_attente_devis` | En attente de devis | Envoyer un devis · Refuser · Demander des infos |
| `a_confirmer` | À confirmer | Accepter · Refuser · Demander des infos |
| `en_attente_paiement` | En attente de paiement | Relancer le client · Marquer payé manuellement · Annuler · Demander des infos |
| `a_faire` | À faire | Terminer (expédier) · Annuler · Demander des infos |
| `termine` | Terminé | Consultation seule · Suppression (archivage auto) |
| `refuse` | Refusé | Consultation seule · Suppression (archivage auto) |
| `annule` | Annulé | Consultation seule · Marquer remboursé · Suppression (archivage auto) |

**Détail des actions**

- **Envoyer un devis** : l'admin saisit un prix (en euros) et une description de ce qu'elle va réaliser. L'application crée automatiquement un PaymentIntent Stripe au montant saisi et envoie le devis par email avec le lien de paiement.
- **Accepter** : valable pour les commandes standard (`a_confirmer`). Crée le PaymentIntent Stripe et envoie le lien de paiement au client.
- **Refuser** : motif obligatoire. Envoie un email au client avec le motif. La commande passe à `refuse`.
- **Annuler** : motif obligatoire. Envoie un email au client avec le motif. La commande passe à `annule`.
- **Demander des infos** : envoie un email libre au client sans changer le statut. Utile pour préciser une personnalisation.
- **Relancer le paiement** : renvoie l'email avec le lien de paiement Stripe au client.
- **Marquer payé manuellement** : à n'utiliser qu'en cas exceptionnel (virement, règlement en direct). Normalement, c'est le webhook Stripe qui déclenche ce changement automatiquement.
- **Terminer** : marque la commande comme expédiée, envoie l'email "Commande expédiée" au client.
- **Supprimer** : uniquement pour les commandes `termine`, `refuse` ou `annule`. La suppression déclenche un archivage automatique avant effacement définitif.

---

### Cycle complet d'une commande

```
Client soumet sa demande
        │
        ├── Panier standard ──────────────────────► [a_confirmer]
        │                                                 │
        └── Panier avec fichier / demande spéciale ► [en_attente_devis]
                                                          │
                                              Admin envoie un devis
                                                          │
                                        ┌─────────────────┴─────────────────┐
                               [a_confirmer]                       [en_attente_devis]
                                     │                                       │
                               Admin accepte                     Admin envoie devis
                                     │                                       │
                                     └──────────────┬────────────────────────┘
                                                    │
                                        [en_attente_paiement]
                                                    │
                                   Client paie (Stripe webhook)
                                                    │
                                              [a_faire]
                                                    │
                                         Admin expédie le colis
                                                    │
                                              [termine]

        À tout moment (sauf termine) :
        ├── Admin refuse ──► [refuse]
        └── Admin annule ──► [annule]
```

**Emails envoyés à chaque étape**

| Étape | Email | Destinataire |
|---|---|---|
| Client soumet sa commande | "Demande reçue" | Client |
| Client soumet sa commande | "[Admin] Nouvelle commande" | Admin |
| Admin demande des infos | "Informations complémentaires requises" | Client |
| Admin envoie un devis | "Votre devis Kdos Graphie" | Client |
| Admin accepte (commande standard) | "Commande confirmée" + lien de paiement | Client |
| Admin relance le paiement | "Rappel de paiement" + lien de paiement | Client |
| Paiement Stripe validé (webhook) | "Paiement confirmé" | Client |
| Paiement Stripe validé (webhook) | "[Admin] Paiement reçu" | Admin |
| Admin termine / expédie | "Commande expédiée" | Client |
| Admin refuse | "Commande refusée" + motif | Client |
| Admin annule | "Commande annulée" + motif | Client |

> **Important :** La validation du paiement est confirmée exclusivement par Stripe via webhook (appel serveur → serveur). Le retour navigateur après paiement n'est jamais utilisé pour valider un paiement — c'est une protection contre les fraudes.

---

### Statuts et règles métier

Toute transition de statut passe par `OrderLifecycleService` — jamais directement via un formulaire ou une mise à jour manuelle en base.

| Règle | Détail |
|---|---|
| Refus | Motif obligatoire + email client automatique |
| Annulation | Motif obligatoire + email client automatique |
| Validation paiement | Webhook Stripe uniquement (jamais le retour navigateur) |
| Suppression | Uniquement sur `termine`, `refuse`, `annule` + archivage préalable obligatoire |
| Données commerciales | Verrouillées dès que la commande passe en `en_attente_paiement` (prix, produits) |

## Structure du projet

```
src/
├── Command/          # Commandes CLI Symfony
├── Controller/
│   ├── Admin/        # Routes /admin/*
│   └── ...           # Routes publiques
├── DataFixtures/
├── Entity/           # Entités Doctrine
├── Form/             # Types de formulaires
├── Handler/          # Actions complexes (paiement, commandes)
├── Message/          # Messages Messenger
├── MessageHandler/   # Handlers Messenger
├── Repository/       # Accès aux données
├── Security/         # Authenticator, voters
├── Service/          # Logique métier réutilisable
└── Twig/             # Extensions Twig

templates/
├── admin/
├── catalog/
├── checkout/
├── contact/
├── emails/
├── home/
├── reset_password/
└── security/
```

## Déploiement VPS

Le script `scripts/update-vps.sh` met à jour l'application en production :

```bash
bash scripts/update-vps.sh
```

Il exécute dans l'ordre :
1. `git pull --ff-only`
2. `composer install --no-dev --optimize-autoloader`
3. `doctrine:migrations:migrate`
4. `asset-map:compile`
5. `cache:clear`

## Configuration des emails (Brevo + ImprovMX)

L'envoi et la réception d'emails reposent sur deux services distincts et complémentaires. Aucun credential n'est documenté ici — les valeurs réelles vivent uniquement dans `.env.local` (jamais commité).

### Brevo — envoi (SMTP sortant)

Brevo sert de relais SMTP pour tous les emails envoyés par l'application (confirmation de commande, notifications admin, formulaire de contact, réinitialisation de mot de passe...).

- Variable : `MAILER_DSN` (format `smtp://user:password@smtp-relay.brevo.com:587`).
- Contrainte importante : Brevo n'accepte d'envoyer que **depuis une adresse "expéditeur" explicitement vérifiée** dans le dashboard Brevo (SPF/DKIM/DMARC configurés sur le domaine `kdos-graphie.fr`). Envoyer avec un `from` non vérifié (ex: une adresse Gmail) est rejeté par Brevo.
- Toutes les méthodes de `OrderMailer`, `ContactMailer` et `ResetPasswordMailer` utilisent donc `MAIL_CONTACT` (`contact@kdos-graphie.fr`, adresse vérifiée) comme `from`.
- Brevo est un service d'**envoi uniquement** : il n'héberge aucune boîte de réception. Rien n'est jamais "reçu" côté Brevo.
- Les emails sont envoyés de façon **synchrone** (pas de routage vers un transport Messenger asynchrone) : si un worker Messenger dédié n'est pas déployé, router les emails vers un transport async les ferait s'accumuler sans jamais partir.

### ImprovMX — réception (redirection vers une vraie boîte mail)

Comme Brevo n'héberge pas de boîte mail, il faut une solution séparée pour que les emails **envoyés vers** `contact@kdos-graphie.fr` (réponses de clients, notifications admin) arrivent réellement quelque part.

- ImprovMX fournit un service de redirection mail gratuit : des enregistrements DNS `MX` sont ajoutés chez le registrar du domaine (IONOS), pointant vers les serveurs ImprovMX.
- Une règle de redirection est configurée dans le dashboard ImprovMX : `contact@kdos-graphie.fr` → boîte Gmail de l'administrateur.
- Ce mécanisme est totalement indépendant de Brevo et du code de l'application — c'est une configuration DNS/infra, pas applicative.

### Variables d'environnement

| Variable | Rôle |
|---|---|
| `MAILER_DSN` | DSN SMTP Brevo utilisé pour l'envoi |
| `MAIL_CONTACT` | Adresse `from` (vérifiée Brevo) et destinataire des notifications admin — doit rester `contact@kdos-graphie.fr`, jamais une adresse Gmail directe |

### Schéma du flux email

```
Appli Symfony ──(SMTP)──► Brevo ──► destinataire (client)
                                          │
Client répond / appli notifie admin ──────┘
                                          │
                                    contact@kdos-graphie.fr
                                          │
                                   DNS MX → ImprovMX
                                          │
                                   Boîte Gmail admin
```

---

## Configuration des services tiers — Stripe & Brevo

Cette section explique le rôle de chaque service, pourquoi il est indispensable, et ce qu'il faut configurer. **Aucune clé ni credential ne figure ici** — ils vivent uniquement dans `.env.local` (jamais commité).

---

### Stripe — paiement en ligne

**Ce que c'est :** Stripe est le prestataire de paiement en ligne. C'est lui qui encaisse les cartes bancaires des clients, gère la sécurité (3D Secure, PCI-DSS), et transfère l'argent sur le compte bancaire de l'artisane. Le site ne stocke jamais les données de carte — tout passe par Stripe.

**Pourquoi c'est indispensable :** Sans Stripe, aucun paiement en ligne n'est possible. Le site ne peut pas encaisser.

**Ce qu'il faut créer :** Un compte Stripe (stripe.com). Compte gratuit à l'inscription, Stripe prélève une commission par transaction (~1,5 % + 0,25 € pour les cartes européennes).

**Les deux clés API à récupérer dans le dashboard Stripe :**

| Variable | Où la trouver | Rôle |
|---|---|---|
| `STRIPE_SECRET_KEY` | Stripe dashboard → Développeurs → Clés API → "Clé secrète" | Permet à l'application de créer des paiements côté serveur |
| `STRIPE_WEBHOOK_SECRET` | Stripe dashboard → Développeurs → Webhooks → "Secret de signature" | Permet de vérifier que les notifications de paiement viennent bien de Stripe |

> **Clé test vs clé live :** En développement, on utilise les clés test (`sk_test_...`) — les paiements ne sont pas réels. En production, on bascule sur les clés live (`sk_live_...`). Ne jamais utiliser les clés live en local.

**Ce qu'est le webhook Stripe (et pourquoi c'est critique) :**

Le webhook est une notification automatique que Stripe envoie au serveur après un paiement réussi. C'est cette notification — et elle seule — qui déclenche le passage de la commande au statut `a_faire` et l'envoi des emails de confirmation.

```
Client paie sur la page Stripe
        │
        └──► Stripe envoie un appel HTTP POST vers :
             https://kdos-graphie.fr/stripe/webhook
                        │
                        └──► L'application vérifie la signature
                             et passe la commande en "À faire"
                             + email "Paiement confirmé" (client)
                             + email "[Admin] Paiement reçu" (admin)
```

**Ce qui se casse si le webhook n'est pas configuré :** Le client paye, l'argent part de sa carte, mais la commande reste bloquée à `en_attente_paiement` pour toujours. L'artisane ne reçoit pas l'email de notification. Il faut alors marquer la commande comme payée manuellement depuis l'admin.

**Configuration du webhook en production :**
1. Aller dans le dashboard Stripe → Développeurs → Webhooks.
2. Ajouter un endpoint : `https://kdos-graphie.fr/stripe/webhook`.
3. Sélectionner l'événement : `payment_intent.succeeded`.
4. Copier le "Secret de signature" généré → le mettre dans `STRIPE_WEBHOOK_SECRET`.

---

### Brevo — envoi d'emails transactionnels

**Ce que c'est :** Brevo (ex-Sendinblue) est le service qui se charge d'envoyer tous les emails du site : confirmations de commande, notifications admin, devis, emails de réinitialisation de mot de passe, etc.

**Pourquoi un service dédié plutôt que Gmail ou le serveur mail du VPS :** Les emails envoyés depuis un serveur classique finissent souvent en spam. Brevo gère la déliverabilité (SPF, DKIM, DMARC), les statistiques d'envoi, et garantit que les emails arrivent en boîte principale.

**Pourquoi c'est indispensable :** Sans Brevo configuré, aucun email n'est envoyé. Ni les confirmations de commande, ni les notifications admin, ni les demandes de réinitialisation de mot de passe.

**Ce qu'il faut créer :** Un compte Brevo (brevo.com). Le plan gratuit permet 300 emails/jour, ce qui est largement suffisant pour une boutique artisanale.

**La clé à récupérer :**

| Variable | Où la trouver | Rôle |
|---|---|---|
| `MAILER_DSN` | Brevo dashboard → Paramètres → SMTP & API → SMTP | Adresse et mot de passe du serveur SMTP |

Le format exact : `smtp://LOGIN_BREVO:MOT_DE_PASSE_BREVO@smtp-relay.brevo.com:587`

**Contrainte importante — adresse expéditeur vérifiée :** Brevo n'autorise l'envoi que depuis des adresses email dont le domaine a été vérifié dans leur dashboard (enregistrements DNS SPF/DKIM/DMARC configurés chez IONOS). Tous les emails du site partent depuis `contact@kdos-graphie.fr` (déjà vérifié). Il ne faut pas changer cette adresse expéditeur.

---

### ImprovMX — réception des réponses clients

**Ce que c'est :** ImprovMX est un service de redirection d'emails. Il redirige les emails reçus sur `contact@kdos-graphie.fr` vers la boîte Gmail de l'artisane.

**Pourquoi il faut ça :** Brevo ne gère que l'envoi. Si un client répond à un email de commande, sa réponse va vers `contact@kdos-graphie.fr`. Sans ImprovMX, cette réponse disparaît dans le vide.

**Ce n'est pas du code :** ImprovMX est une configuration DNS pure. Rien à changer dans l'application. La configuration est faite côté IONOS (registrar du domaine) et dans le dashboard ImprovMX.

---

### Vue d'ensemble : comment les trois services s'articulent

```
┌─────────────────────────────────────────────────────────────┐
│                    Site kdos-graphie.fr                     │
│                    (Symfony / VPS)                          │
└──────┬──────────────────────────────────────┬───────────────┘
       │                                      │
       │ SMTP                                 │ API
       ▼                                      ▼
┌─────────────┐                    ┌─────────────────────┐
│    BREVO    │                    │       STRIPE        │
│  (envoi     │                    │  (paiement)         │
│  d'emails)  │                    │                     │
└──────┬──────┘                    └──────────┬──────────┘
       │ Email                                │ Webhook POST
       │ vers client                          │ vers /stripe/webhook
       │                                      │
       ▼                                      ▼
┌─────────────────┐             ┌─────────────────────────┐
│  Boîte email    │             │  Application valide     │
│  du client      │             │  le paiement et passe   │
│                 │             │  la commande en         │
│  Si réponse →   │             │  "À faire"              │
│  ImprovMX →     │             └─────────────────────────┘
│  Gmail admin    │
└─────────────────┘
```

**Résumé des comptes à avoir actifs en production**

| Service | Usage | Coût indicatif |
|---|---|---|
| Stripe | Encaissement des paiements | ~1,5 % + 0,25 € par transaction carte EU |
| Brevo | Envoi des emails transactionnels | Gratuit jusqu'à 300 emails/jour |
| ImprovMX | Réception des réponses clients | Gratuit (plan de base) |
