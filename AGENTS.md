# AGENTS.md

Ce fichier definit le contexte commun obligatoire pour tous les agents utilises dans ce depot.
Objectif: garantir le meme niveau d'alignement que Claude, en s'appuyant sur `CLAUDE.md` et `.claude/*`.

## 1) Contexte de base (obligatoire)

Chaque agent doit charger et respecter, dans cet ordre:

1. `CLAUDE.md`
2. `.claude/project-profile.md`
3. `.claude/local-instructions.md`
4. Tous les fichiers de `.claude/rules/*`

Aucune execution ne doit ignorer ces sources.
Si des consignes se contredisent, priorite a:

1. `CLAUDE.md`
2. `.claude/project-profile.md`
3. `.claude/rules/*`
4. `.claude/local-instructions.md`

## 2) Regles transverses a appliquer systematiquement

- Respecter l'architecture `classic-symfony`: Controller -> Service/Handler -> Repository -> Entity.
- Garder les controllers minces.
- Mettre la logique metier reutilisable dans des services nommes metier.
- Utiliser les handlers pour les actions applicatives complexes et ponctuelles.
- Eviter la sur-ingenierie (pas d'abstraction inutile).
- Ne pas creer d'interface sans besoin reel (au moins trois implementations ou frontiere architecturale claire).
- Toujours creer une nouvelle migration; ne jamais modifier une migration deja executee.
- Ajouter ou mettre a jour des tests quand la logique metier change.
- Respecter Symfony natif en priorite, puis conventions du projet.

## 3) Chargement des regles `.claude/rules/*`

Tous les agents doivent appliquer toutes les regles suivantes:

- `.claude/rules/anti-overengineering.md`
- `.claude/rules/api-platform.md`
- `.claude/rules/architecture.md`
- `.claude/rules/code-style.md`
- `.claude/rules/deployment.md`
- `.claude/rules/doctrine.md`
- `.claude/rules/frontend.md`
- `.claude/rules/quality.md`
- `.claude/rules/security.md`
- `.claude/rules/symfony.md`
- `.claude/rules/testing.md`
- `.claude/rules/twig.md`

## 4) Catalogue des agents specialises

Agents disponibles sous `.claude/agents/`:

- `bug-hunter`: investigation et correction de bugs Symfony/PHP.
- `code-reviewer`: revue qualite, architecture, regressions et tests.
- `deployment-advisor`: preparation/revue de deploiement Symfony.
- `doctrine-specialist`: entites, repositories, migrations, performances SQL.
- `frontend-ui-designer`: Twig, Tailwind, Stimulus, AssetMapper.
- `security-auditor`: audit auth, CSRF, XSS, paiements, donnees sensibles.
- `test-writer`: creation et mise a jour de tests PHPUnit.

Quand une tache correspond fortement a l'un de ces domaines, l'agent specialise doit etre privilegie.

## 5) Usage des skills `.claude/skills/*`

Les skills du dossier `.claude/skills/` sont des guides operationnels reutilisables.
Tout agent doit:

1. Identifier le skill pertinent avant implementation.
2. Lire le `SKILL.md` correspondant.
3. Appliquer son workflow si compatible avec la demande utilisateur.

Exemples frequents:

- `search-first` avant d'ajouter une nouvelle logique.
- `implement-feature` pour une fonctionnalite complete.
- `test-implementation` quand la logique metier evolue.
- `review-changes` pour un audit de modifications.
- `symfony-ci-check` avant validation finale.

## 6) Checklist securite obligatoire

Pour les zones sensibles, l'agent doit d'abord expliquer un plan avant edition:

- Paiement Stripe
- Authentification
- Autorisation et acces admin
- Migrations de base de donnees
- Deploiement
- Uploads de fichiers
- Secrets et variables d'environnement

Rappels de securite:

- Ne pas lire `.env.local`, `.env.prod`, `secrets/**`, cles privees, dumps prod.
- Ne pas faire confiance a la validation front uniquement.
- Verifier CSRF, controle d'acces, ownership et exposition de donnees.

### Garde-fou obligatoire avant lecture de fichier

Avant tout appel de lecture de fichier, l'agent doit verifier le chemin cible.

Si le chemin correspond a l'un des motifs suivants, l'agent doit interrompre la lecture et demander a l'utilisateur une valeur explicite ou un extrait non sensible:

- `.env.local`
- `.env.prod`
- `.env.*.local`
- `secrets/**`
- cles privees
- dumps de production

Procedure obligatoire en cas de besoin de configuration:

1. Expliquer la variable necessaire sans demander de secret complet.
2. Demander a l'utilisateur de renseigner la valeur lui-meme dans son fichier local.
3. Continuer uniquement avec une valeur fournie explicitement dans le chat ou un placeholder neutre.
4. Ne jamais copier, journaliser, ni re-afficher une valeur sensible.

## 7) Definition de termine

Une tache n'est pas terminee tant que:

- Les regles de ce fichier ne sont pas respectees.
- Les impacts regressifs n'ont pas ete verifies.
- Les tests necessaires n'ont pas ete executes ou explicitement justifies.
- Les risques securite n'ont pas ete mentionnes si zone sensible.

## 8) Maintenance

A chaque mise a jour de `CLAUDE.md` ou de `.claude/*`, ce fichier doit etre verifie et ajuste pour conserver un contexte unifie entre tous les agents.

