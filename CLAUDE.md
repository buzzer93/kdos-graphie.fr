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

### Secrets et fichiers sensibles

- Ne jamais lire `.env.local`, `.env.prod`, `.env.*.local`, `secrets/**`, cles privees, ni dumps de production.
- Ne jamais copier, journaliser, ni re-afficher une valeur sensible.

Procedure obligatoire quand une configuration est necessaire :

1. Expliquer la variable requise sans demander le secret complet.
2. Demander a l'utilisateur de renseigner la valeur lui-meme dans son fichier local.
3. Continuer uniquement avec une valeur fournie explicitement dans le chat ou un placeholder neutre.

## Contrat agent - Processus commande

Source de vérité détaillée : [README - Processus de commande personnalisée](README.md#processus-de-commande-personnalisee-cible-metier).

Règles strictes à respecter par défaut :
- Statuts cibles : `a_confirmer`, `en_attente_paiement`, `a_faire`, `termine`, `refuse`, `annule`.
- Toute transition de statut passe par un service métier dédié (pas de changement libre en formulaire admin).
- Refus : motif obligatoire + notification email client.
- Paiement : statut validé côté serveur Stripe (webhook/verification), jamais uniquement via retour navigateur.
- Suppression admin d'une commande `annule`, `refuse` ou `termine` : archivage obligatoire avant suppression définitive.
