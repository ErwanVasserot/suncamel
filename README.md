# SunCamel

Site de location de vélos électriques à Raglan, développé avec Symfony. Il regroupe le catalogue, la sélection d’une période de location, un panier, le paiement Stripe et une administration EasyAdmin.

## Fonctionnalités

- Pages publiques composées de blocs Twig réutilisables.
- Catalogue administrable de vélos, collections et galeries d’images.
- Sélection des dates de retrait et de retour.
- Disponibilité calculée à partir du stock et des réservations existantes.
- Panier conservé dans la session Symfony.
- Tarification calculée selon le nombre de jours de location.
- Réservation temporaire du stock pendant le paiement.
- Paiement avec Stripe Checkout.
- Confirmation fiable des paiements par webhook Stripe.
- Inscription, connexion et rôles utilisateur/administrateur.
- Gestion des produits, images, FAQ, utilisateurs et réservations avec EasyAdmin.
- Contenu statique de secours lorsque certaines données ne sont pas disponibles en base.

## Technologies

- PHP 8.4 ou supérieur
- Symfony 8
- Doctrine ORM et Doctrine Migrations
- MariaDB/MySQL
- Twig
- Symfony AssetMapper
- Tailwind CSS 4 via SymfonyCasts Tailwind Bundle
- Stimulus et Symfony UX Turbo
- EasyAdmin 5
- Stripe Checkout via l’API HTTP
- PHPUnit 13

## Prérequis

- PHP avec les extensions requises par Symfony et Doctrine
- Composer
- MariaDB 10.11 ou une version MySQL compatible
- Node.js uniquement pour certains contrôles JavaScript facultatifs
- Un compte Stripe pour tester ou utiliser les paiements

> Les migrations actuelles utilisent la syntaxe MySQL/MariaDB. Le service PostgreSQL présent dans `compose.yaml` n’est donc pas compatible avec celles-ci sans adaptation préalable.

## Installation locale

Installer les dépendances :

```bash
composer install
```

Créer `.env.local` et configurer au minimum la base de données :

```dotenv
APP_SECRET=change-me
DATABASE_URL="mysql://app:password@127.0.0.1:3306/suncamel?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
```

Créer la base si nécessaire, puis appliquer les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### État actuel de la base locale

La connexion locale a été vérifiée avec succès sur la base `suncamel` :

- les 6 migrations disponibles sont exécutées ;
- aucune migration n’est en attente ;
- le mapping des entités Doctrine est valide ;
- le schéma SQL est synchronisé avec les entités ;
- les tables du catalogue et des réservations sont accessibles ;
- les 3 produits initiaux sont présents.

Pour reproduire ces vérifications :

```bash
php bin/console doctrine:migrations:status --no-interaction
php bin/console doctrine:schema:validate
php bin/console doctrine:query:dql "SELECT COUNT(p.id) AS product_count FROM App\\Entity\\Product p"
php bin/console doctrine:query:dql "SELECT COUNT(b.id) AS booking_count FROM App\\Entity\\Booking b"
```

La commande `doctrine:query:sql` n’est pas fournie par la version de Doctrine installée dans ce projet. Utiliser les requêtes DQL ci-dessus pour les contrôles simples.

Compiler les styles :

```bash
php bin/console tailwind:build
```

Pour suivre les changements CSS pendant le développement :

```bash
php bin/console tailwind:build --watch
```

Lancer ensuite l’application avec le serveur Symfony, FrankenPHP ou le serveur web PHP de votre choix. La racine publique du serveur doit pointer vers `public/`.

Exemple avec Symfony CLI :

```bash
symfony server:start
```

L’application est généralement accessible à l’adresse `https://localhost:8000`.

## Configuration Stripe

Ajouter les variables suivantes dans `.env.local` :

```dotenv
STRIPE_SECRET_KEY=sk_test_...
STRIPE_CURRENCY=nzd
STRIPE_WEBHOOK_SECRET=whsec_...
```

Le webhook Stripe doit cibler :

```text
POST https://votre-domaine.example/stripe/webhook
```

Événements à activer :

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `checkout.session.expired`

Pour tester localement avec Stripe CLI :

```bash
stripe listen --forward-to https://localhost:8000/stripe/webhook --skip-verify
```

Copier le secret `whsec_...` affiché par Stripe CLI dans `STRIPE_WEBHOOK_SECRET`.

### Cycle d’une réservation

1. Le client choisit une période de location.
2. Les dates sont transmises au catalogue puis à la fiche produit.
3. Le vélo est ajouté au panier conservé dans la session.
4. Le serveur vérifie à nouveau le stock pour la période choisie.
5. Au passage en caisse, une réservation `pending` bloque temporairement le stock.
6. Une session Stripe Checkout est créée avec la référence de réservation.
7. Le webhook Stripe passe la réservation à l’état `paid` après confirmation du paiement.
8. Une session Stripe annulée, expirée ou échouée libère le stock.

Les réservations temporaires expirent après environ 31 minutes. La disponibilité ignore automatiquement les réservations `pending` expirées.

## Calcul des disponibilités

Un vélo est indisponible lorsque le nombre de réservations qui se chevauchent atteint son `stockQuantity`.

Les réservations prises en compte sont :

- les réservations payées ;
- les réservations en attente dont la date d’expiration n’est pas dépassée.

Une transaction et un verrou pessimiste sur le produit protègent la création d’une réservation contre les paiements simultanés.

Le fuseau métier utilisé pour les dates de location est `Pacific/Auckland`.

## Administration

L’administration est disponible sur :

```text
/admin
```

Elle nécessite le rôle `ROLE_ADMIN` et permet de gérer :

- les produits et leur stock ;
- les images des produits ;
- les questions et réponses de la FAQ ;
- les utilisateurs et leurs rôles ;
- les réservations et leur statut.

Les migrations initiales créent des comptes de démonstration. Ils ne doivent pas être conservés tels quels en production : changez leurs mots de passe ou supprimez-les avant le déploiement.

## Structure du projet

```text
assets/
  controllers/          Contrôleurs Stimulus
  styles/               Styles Tailwind
config/                  Configuration Symfony
migrations/              Migrations Doctrine
public/                   Point d’entrée et images publiques
src/
  Controller/            Contrôleurs publics, panier, paiement et admin
  Entity/                Entités Doctrine
  Repository/            Requêtes métier et accès aux données
  Service/               Services applicatifs, dont le panier
templates/
  blocks/                Blocs de contenu réutilisables
  cart/                  Affichage du panier
  checkout/              Retours de paiement
  page/                  Composition des pages
  partials/              En-tête, pied de page et fil d’Ariane
tests/                    Infrastructure PHPUnit
```

## Principales entités

- `Product` : vélo, prix, stock, caractéristiques et collection.
- `ProductImage` : galerie associée à un produit.
- `Booking` : réservation, client, statut, montant et session Stripe.
- `BookingItem` : vélo, période, quantité et prix figé au moment de la commande.
- `User` : compte client ou administrateur.
- `FaqItem` : contenu administrable de la FAQ.
- `Page` : page composée de métadonnées et de blocs JSON.
- `Menu` et `MenuItem` : structure prévue pour des menus administrables.

## Routes métier principales

| Méthode | Route | Utilité |
|---|---|---|
| `GET` | `/` | Page d’accueil |
| `GET` | `/collections/all` | Catalogue et disponibilités |
| `GET` | `/products/{slug}` | Fiche d’un vélo |
| `GET` | `/cart` | Panier |
| `POST` | `/cart/add/{slug}` | Ajout d’un vélo au panier |
| `POST` | `/cart/remove/{key}` | Suppression d’un article |
| `POST` | `/checkout` | Création de la réservation et de la session Stripe |
| `GET` | `/checkout/success` | Retour après paiement |
| `GET` | `/checkout/cancel` | Retour après annulation |
| `POST` | `/stripe/webhook` | Confirmation serveur Stripe |
| `GET` | `/login` | Connexion |
| `GET/POST` | `/register` | Création d’un compte |
| `GET` | `/admin` | Administration |

## Contenu et pages de secours

`PageController` charge en priorité les pages publiées présentes en base. Lorsqu’aucune page correspondante n’existe, il construit une version de secours pour :

- l’accueil ;
- les collections ;
- les fiches produit ;
- la FAQ ;
- la page contact ;
- les conditions générales.

Les produits et éléments de FAQ suivent une logique similaire : les données administrées sont utilisées en priorité, puis un contenu de secours prend le relais.

## Commandes utiles

Lister les routes :

```bash
php bin/console debug:router
```

Valider le conteneur et les templates :

```bash
php bin/console lint:container
php bin/console lint:twig templates
```

Vérifier le modèle Doctrine :

```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
```

Vérifier que toutes les migrations sont appliquées :

```bash
php bin/console doctrine:migrations:up-to-date
```

Lancer les tests :

```bash
php bin/phpunit
```

Compiler les ressources pour la production :

```bash
php bin/console tailwind:build --minify
php bin/console asset-map:compile
```

Vider le cache :

```bash
php bin/console cache:clear
```

## État des tests

PHPUnit est configuré, mais le projet ne contient pas encore de tests applicatifs. Les priorités recommandées sont :

1. validation des périodes de location ;
2. détection des chevauchements de réservations ;
3. calcul du prix sur plusieurs jours ;
4. ajout et suppression d’articles du panier ;
5. signature et idempotence du webhook Stripe ;
6. permissions de l’administration.

## Préparation à la production

Avant un déploiement public :

- définir un `APP_SECRET` robuste ;
- utiliser exclusivement des secrets Stripe de production fournis par l’environnement ;
- enregistrer le webhook HTTPS dans Stripe ;
- supprimer ou sécuriser les comptes de démonstration ;
- vérifier le tarif journalier et les règles de caution ;
- faire relire les conditions générales ;
- configurer les sauvegardes de la base et des images téléversées ;
- activer les journaux et alertes sur les erreurs de paiement ;
- ajouter des tests automatisés ;
- désactiver le mode debug ;
- compiler les assets avant la mise en ligne.

## Déploiement Git sur SiteHost

Le projet possède son propre dépôt Git à la racine de `suncamel`. Cela évite d’utiliser par erreur le dépôt parent de `/Users/owner` et d’y inclure des fichiers personnels.

### 1. Créer le dépôt distant

Créer un dépôt privé vide sur GitHub, GitLab, Bitbucket ou le fournisseur Git utilisé pour le déploiement. Ne pas initialiser ce dépôt distant avec un autre README afin d’éviter un historique divergent.

Ajouter ensuite le dépôt distant :

```bash
git remote add origin git@github.com:organisation/suncamel.git
git push -u origin main
```

Remplacer l’URL d’exemple par l’URL SSH réelle du dépôt.

### 2. Configurer les variables SiteHost

Utiliser [.env.prod.example](.env.prod.example) comme liste de référence et configurer les vraies valeurs dans l’environnement SiteHost. Ne jamais envoyer `.env.local`, `.env.prod.local`, les clés Stripe ou les identifiants de base dans Git.

Les variables indispensables sont :

- `APP_ENV=prod`
- `APP_DEBUG=0`
- `APP_SECRET`
- `DEFAULT_URI`
- `DATABASE_URL`
- `STRIPE_SECRET_KEY`
- `STRIPE_CURRENCY`
- `STRIPE_WEBHOOK_SECRET`

### 3. Configurer le site web

Le document root du domaine doit pointer vers le dossier `public/` du projet et non vers la racine du dépôt. La version PHP doit être compatible avec la contrainte définie dans `composer.json`.

Vérifier également que le processus PHP peut écrire dans :

```text
var/cache/
var/log/
var/sessions/
public/images/suncamel/products/
```

Le dernier chemin est nécessaire aux images envoyées depuis EasyAdmin.

### 4. Déployer après un pull

Après avoir cloné le dépôt une première fois, chaque déploiement peut suivre ce flux :

```bash
cd /chemin/vers/suncamel
git pull --ff-only origin main
./bin/deploy-sitehost
```

Le script [bin/deploy-sitehost](bin/deploy-sitehost) :

1. installe les dépendances Composer de production ;
2. compile Tailwind et AssetMapper ;
3. applique les migrations Doctrine ;
4. reconstruit et préchauffe le cache Symfony.

Le script s’arrête dès qu’une commande échoue. Le déploiement ne doit être considéré comme terminé que si le message `SunCamel deployment completed successfully.` est affiché.

### 5. Configurer l’accès SSH au dépôt

Sur le serveur SiteHost, générer ou utiliser une clé SSH dédiée au déploiement. Ajouter sa clé publique comme deploy key en lecture seule dans le dépôt distant, puis vérifier l’accès avant le premier clone :

```bash
ssh -T git@github.com
```

Adapter l’hôte pour GitLab, Bitbucket ou un autre fournisseur. La clé privée doit rester uniquement sur le serveur et ne doit jamais être ajoutée au dépôt.

### 6. Webhook Stripe de production

Après mise en ligne, enregistrer l’URL suivante dans Stripe :

```text
https://votre-domaine.example/stripe/webhook
```

Reporter le secret de signature généré par Stripe dans `STRIPE_WEBHOOK_SECRET` sur SiteHost.

### Procédure de retour arrière

Conserver le hash du commit précédemment déployé. En cas de problème applicatif, revenir explicitement à ce commit puis relancer le script :

```bash
git checkout <commit-stable>
./bin/deploy-sitehost
```

Les migrations de base ne doivent pas être annulées automatiquement. Toute restauration de base doit être préparée et accompagnée d’une sauvegarde.

## Dépannage

### Les styles récemment modifiés ne sont pas visibles

Reconstruire Tailwind et vider le cache :

```bash
php bin/console tailwind:build --minify
php bin/console cache:clear
```

Si `public/assets/` a été compilé en mode développement, les ressources compilées peuvent prendre le dessus sur les fichiers source. Utiliser le mode watch ou nettoyer les ressources compilées selon le workflow local retenu.

### Le paiement est accepté mais la réservation reste `pending`

Vérifier :

- la valeur de `STRIPE_WEBHOOK_SECRET` ;
- l’URL publique du webhook ;
- les événements activés dans Stripe ;
- les tentatives et réponses HTTP dans le tableau de bord Stripe ;
- les journaux Symfony dans `var/log/`.

### En cas d’erreur de connexion à la base

La base locale `suncamel` est actuellement accessible et son schéma est synchronisé. Si une erreur de connexion apparaît sur une autre machine ou après un changement de configuration, contrôler `DATABASE_URL`, le port MariaDB/MySQL et l’existence de la base, puis exécuter :

```bash
php bin/console doctrine:migrations:status --no-interaction
php bin/console doctrine:schema:validate
```

Pour confirmer qu’une table peut être lue :

```bash
php bin/console doctrine:query:dql "SELECT COUNT(p.id) FROM App\\Entity\\Product p"
```

## Licence

Projet propriétaire. Tous droits réservés.
