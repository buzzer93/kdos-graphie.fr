# KDOS-GRAPHIE

Site vitrine + e-commerce de vente de produits graphiques personnalisables.
Stack : Symfony 8.0 · PHP 8.4 · MySQL · Tailwind CSS · AssetMapper · Stimulus.

## Objectifs de Claude

- Implémenter les fonctionnalités en respectant l'architecture `classic-symfony`.
- Garder les contrôleurs minces, la logique dans des services ou handlers nommés.
- Éviter les abstractions inutiles.
- Proposer ou mettre à jour les tests quand la logique métier change.
- Signaler les risques de sécurité avant toute modification sensible.

## Fichiers importants

- `.claude/project-profile.md` — stack, architecture, commandes, déploiement
- `.claude/rules/architecture.md` — structure `Controller / Service / Handler / Repository`
- `.claude/rules/security.md` — règles pour les zones sensibles
- `.claude/rules/doctrine.md` — entités, repositories, migrations
- `.claude/rules/frontend.md` — Tailwind, Stimulus, AssetMapper, Twig
- `.claude/rules/testing.md` — PHPUnit, stratégie de tests
- `DESIGN.md` — système de design actif (si présent, obligatoire pour le frontend)

## Conventions principales

- Architecture : `Controller → Service / Handler → Repository → Entity`
- `Service/` pour la logique réutilisable, `Handler/` pour les actions complexes ponctuelles.
- Nommer les classes par leur responsabilité métier : `StripePaymentHandler`, `ContactMailer`.
- Pas d'interface sans au moins trois implémentations ou besoin de découplage fort.
- Migrations : toujours créer une nouvelle migration, ne jamais modifier une ancienne.

## Zones sensibles

Expliquer le plan avant de modifier :
- paiement Stripe ;
- authentification et autorisation ;
- espace admin ;
- migrations de base de données ;
- déploiement.
