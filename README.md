# Kdos-Graphie

## Description
Boutique en ligne e-commerce dédiée aux créations graphiques (cadeaux personnalisés, illustrations). Le site propose un catalogue de produits, un panier, un tunnel de commande complet, un espace utilisateur avec récupération de mot de passe, et une interface d'administration pour la gestion du catalogue.

## Stack technique
- **Backend :** PHP >= 8.4, Symfony 8.0, Doctrine ORM 3, Doctrine Migrations 4
- **Frontend :** Twig, AssetMapper + Importmap, Stimulus, Symfony UX Turbo
- **Sécurité :** Symfony Security + `symfonycasts/reset-password-bundle`
- **Asynchrone :** Symfony Messenger (Message / MessageHandler)
- **Mailer :** Symfony Mailer
- **Outils :** Composer, Symfony Flex

## Prérequis
- PHP >= 8.4
- Composer >= 2
- Symfony CLI (recommandé)
- Serveur MySQL (ou autre SGBD compatible Doctrine)

## Fonctionnalités

### Côté boutique
- Page d'accueil (`home/`)
- Catalogue produits (`catalog/`)
- Panier d'achat (`cart/`)
- Tunnel de commande / paiement (`checkout/`)
- Formulaire de contact (`contact/`)
- Inscription / connexion / récupération de mot de passe (`security/`, `reset_password/`)
- Emails transactionnels (`emails/`)

### Côté admin
- Espace d'administration (`admin/`) pour la gestion du catalogue, des commandes, etc.

## Installation

```bash
# 1. Cloner le dépôt et se placer dans l'application
git clone <url-du-depot>
cd Kdos-Graphie.fr

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Renseigner DATABASE_URL, APP_SECRET, MAILER_DSN

# 4. Initialiser la base
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. (Optionnel) Charger les fixtures
php bin/console doctrine:fixtures:load

# 6. Lancer le serveur
symfony serve
```

## Utilisation
- Boutique : [http://127.0.0.1:8000](http://127.0.0.1:8000)
- Administration : [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)

### Structure du projet
```
src/
├── Command/
├── Controller/
├── DataFixtures/
├── Entity/
├── Form/
├── Message/
├── MessageHandler/
├── Repository/
├── Security/
├── Service/
└── Twig/

templates/
├── admin/             # Espace admin
├── cart/              # Panier
├── catalog/           # Catalogue produits
├── checkout/          # Tunnel de commande
├── contact/
├── emails/
├── home/
├── reset_password/
└── security/
```
