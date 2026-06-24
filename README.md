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

Les commandes suivent un workflow strict avec des statuts dédiés :

```
a_confirmer → en_attente_paiement → a_faire → termine
                                  ↘ refuse
                    ↘ annule
```

Règles métier :
- Toute transition de statut passe par un service/handler dédié.
- Le refus requiert un motif obligatoire et une notification email client.
- La validation du paiement est confirmée côté serveur via webhook Stripe.
- La suppression en admin d'une commande `annule`, `refuse` ou `termine` nécessite un archivage préalable.

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
