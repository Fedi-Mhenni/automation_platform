# Workflow Automation Platform

Moteur d'automatisation de workflows visuels construit avec **Laravel 13** et **Alpine.js**. Inspiré de n8n et Zapier, mais conçu pour être lisible, extensible et déployable sans infrastructure externe.

> Projet académique — Bachelor Web 3ème année

---

## Aperçu

Ce projet permet de composer des workflows sous forme de graphes orientés, de les déclencher via webhook, formulaire ou planificateur, et de suivre chaque exécution nœud par nœud.

```
Webhook entrant → Condition (montant > 100) ──true──→ Email "Commande VIP"
                                            ──false─→ Log "Commande standard"
```

---

## Fonctionnalités

- **Éditeur visuel** — canvas drag-and-drop pour composer le graphe, panneau de configuration par nœud généré depuis son JSON Schema
- **3 types de déclencheurs** — Webhook (HTTP entrant), Formulaire, Planificateur (fréquence visuelle : minutely / hourly / daily / weekly)
- **Conditions et branches** — routage `true`/`false` basé sur les variables du payload
- **Délai** — pause configurable entre deux nœuds
- **Actions** — envoi d'email (Resend), journalisation avec message
- **Templates `{{variable}}`** — interpolation des données du payload dans les payloads des nœuds aval
- **Logs d'exécution** — traçabilité complète, groupée par run (UUID d'exécution)
- **Dry-run planificateur** — aperçu du prochain déclenchement sans exécuter le workflow
- **Auth complète** — inscription, connexion, isolation des données par utilisateur (Sanctum SPA)

---

## Stack technique

| Couche          | Technologie                                                |
| --------------- | ---------------------------------------------------------- |
| Backend         | PHP 8.3 · Laravel 13 · Eloquent ORM                        |
| Auth API        | Laravel Sanctum (cookie SPA, httpOnly)                     |
| Frontend        | Alpine.js v3 · Tailwind CSS v3 · Vite                      |
| Email           | Resend via `resend/resend-laravel`                         |
| Scheduler       | `dragonmantank/cron-expression`                            |
| Base de données | SQLite (dev/tests) — compatible MySQL/PostgreSQL           |
| Tests           | PHPUnit 12 · SQLite in-memory                              |
| Outils dev      | Laravel Pail (logs temps réel) · Laravel Pint (code style) |

---

## Installation

### Prérequis

- PHP ≥ 8.3 + Composer
- Node.js ≥ 18 + npm

### Démarrage en une commande

```bash
git clone <url-du-repo>
cd automation-platform
composer run setup
```

Le script `setup` : installe les dépendances Composer et npm, génère la clé d'application, exécute les migrations et compile les assets frontend.

### Environnement de développement

```bash
composer run dev
```

Lance en parallèle : serveur PHP · queue worker · tail des logs (Pail) · Vite HMR.

Application disponible sur `http://localhost:8000`

**Compte de test :** `admin@test.com` / `password123`

### Variables d'environnement

Copier `.env.example` en `.env` et configurer :

```env
# Email (Resend — https://resend.com)
RESEND_API_KEY=re_xxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@votre-domaine.com

# Base de données (SQLite par défaut, rien à changer pour le dev)
DB_CONNECTION=sqlite
```

---

## Architecture

### Moteur d'exécution

Un workflow est un graphe orienté. `WorkflowRunner` le parcourt récursivement depuis `meta.startNodeId` :

```
WorkflowRunner::run($workflow, $payload)
  ├── GraphValidator::validate()        Structure · IDs uniques · arêtes · startNodeId = trigger
  ├── new ExecutionContext(input: $payload)
  └── processFromNode()  ←  RÉCURSIF
        ├── NodeRegistry::make($type)->handle($context)   Exécute le nœud
        ├── logExecution() → ExecutionLog::create()       Trace le résultat
        └── getNextNodes()                                Suit les arêtes (avec routing conditionnel)
```

`ExecutionContext` transporte l'état mutable du run. Chaque nœud lit et écrit dans `$context->state`. Les templates `{{variable}}` sont résolus par `TemplateEngine` via une recherche à trois niveaux : état plat → input → state récursif.

### Modèle de données — le graphe

Toute la logique d'un workflow est stockée dans une colonne JSON `nodes_structure` :

```json
{
    "nodes": [
        {
            "id": "node_1",
            "type": "trigger_webhook",
            "payload": { "expected_fields": "email,montant" }
        },
        {
            "id": "node_2",
            "type": "control_condition",
            "payload": {
                "variable": "montant",
                "operator": "greater_than",
                "value": "100"
            }
        },
        {
            "id": "node_3",
            "type": "action_email",
            "payload": { "to": "{{email}}", "subject": "Commande confirmée" }
        }
    ],
    "edges": [
        { "source": "node_1", "target": "node_2" },
        { "source": "node_2", "target": "node_3", "condition": "true" }
    ],
    "meta": { "startNodeId": "node_1" }
}
```

### Types de nœuds

| Type          | Identifiant         | Rôle                                          |
| ------------- | ------------------- | --------------------------------------------- |
| Webhook       | `trigger_webhook`   | Démarre le workflow sur un `POST` HTTP        |
| Formulaire    | `trigger_form`      | Déclencheur via formulaire                    |
| Planificateur | `trigger_scheduler` | Déclenchement périodique (cron)               |
| Condition     | `control_condition` | Branchement `true`/`false` selon une variable |
| Délai         | `control_delay`     | Pause entre deux nœuds                        |
| Email         | `action_email`      | Envoi d'email via Resend                      |
| Log           | `action_log`        | Journalisation avec interpolation             |

### Ajouter un nouveau type de nœud

1. Créer une classe implémentant `ActionInterface` dans `app/Services/Workflow/`
2. L'ajouter à `NodeRegistry::$nodes`

Le frontend récupère le schéma automatiquement via `GET /api/workflow-nodes/schema` et génère le formulaire de configuration sans aucune modification UI.

---

## API principale

| Méthode | Route                          | Auth    | Description                                |
| ------- | ------------------------------ | ------- | ------------------------------------------ |
| `POST`  | `/api/webhook/{token}`         | —       | Déclenche un workflow (public)             |
| `GET`   | `/api/workflows`               | Sanctum | Liste les workflows                        |
| `POST`  | `/api/workflows/{id}/save`     | Sanctum | Sauvegarde le graphe complet               |
| `POST`  | `/api/workflows/{id}/test`     | Sanctum | Exécution manuelle avec payload arbitraire |
| `GET`   | `/api/workflows/{id}/next-run` | Sanctum | Prochain déclenchement (sans exécuter)     |
| `GET`   | `/api/workflows/{id}/logs`     | Sanctum | Logs groupés par run                       |
| `GET`   | `/api/workflow-nodes/schema`   | Sanctum | Schémas JSON de tous les types de nœuds    |

---

## Tests

```bash
# Tous les tests
composer run test

# Un test précis
php artisan test --filter NomDuTest

# Un fichier
php artisan test tests/Feature/Auth/AuthenticationTest.php
```

Les tests utilisent SQLite in-memory, le driver mail `array` et les queues `sync` — aucun service externe requis.

---

## Commandes artisan

```bash
# Déclenche les workflows planifiés dont l'expression cron est due
php artisan workflow:run-scheduled
```

La commande est enregistrée dans `routes/console.php` et appelée toutes les minutes par le scheduler Laravel (`php artisan schedule:run` via cron OS).

---

## Structure du projet

```
app/
├── Console/Commands/
│   └── RunScheduledWorkflows.php
├── Http/Controllers/
│   ├── WorkflowController.php          # API CRUD + exécution + next-run
│   ├── WorkflowWebController.php       # Routes Blade (vues)
│   ├── WebhookController.php           # Endpoint public (sans auth)
│   └── ExecutionLogController.php
└── Services/Workflow/
    ├── WorkflowRunner.php              # Moteur principal (traversée récursive)
    ├── NodeRegistry.php                # Registre des types de nœuds
    ├── ExecutionContext.php            # État mutable partagé entre les nœuds
    ├── TemplateEngine.php              # Résolution des placeholders {{variable}}
    ├── NodeResult.php                  # Objet valeur success / failure
    ├── Contracts/ActionInterface.php   # Contrat de chaque type de nœud
    ├── Graph/Edge.php                  # Valeur objet — arête orientée et conditionnelle
    ├── Validation/GraphValidator.php   # Validation pré-exécution du graphe
    ├── Triggers/                       # WebhookTrigger · FormTrigger · SchedulerTrigger
    ├── Actions/                        # EmailAction · LogAction
    └── Control/                        # ConditionControl · DelayControl · ConditionResolver
```

---

## Workflow Git

```
main        ← production stable
dev         ← branche d'intégration
feature/*   ← développement par fonctionnalité (PR obligatoire vers dev)
```
