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
- Créneaux demi-journée (08:00–12:00 ou 12:00–16:00), journée et séjours de plusieurs jours.
- Tarifs demi-journée, journée et paliers dégressifs administrables par produit.
- Inscription, connexion, changement et réinitialisation du mot de passe.
- Rôles utilisateur/administrateur.
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

- toutes les migrations disponibles au moment de la vérification sont exécutées ;
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

## Comptes et mots de passe

La création d’un compte est facultative pour commander. Le panier demande toujours une adresse e-mail, enregistrée directement sur la réservation et utilisée pour la confirmation. Un visiteur peut ensuite :

- payer sans compte ;
- cocher **I want an account**, créer son compte avec l’adresse préremplie, puis revenir automatiquement au panier en étant connecté.

Lors de la création du compte, les anciennes réservations invitées portant exactement la même adresse e-mail sont automatiquement rattachées à ce compte. Les nouvelles réservations effectuées en étant connecté apparaissent dans **My account → My orders**.

Un utilisateur connecté peut modifier son mot de passe depuis le menu du site ou directement sur :

```text
/account/password
```

Le formulaire exige le mot de passe actuel, puis le nouveau mot de passe et sa confirmation. Le nouveau mot de passe doit contenir au moins huit caractères.

Un utilisateur qui ne peut plus se connecter peut demander une réinitialisation depuis le lien **Forgot your password?** de la page de connexion :

```text
/forgot-password
```

Pour éviter de révéler si une adresse est inscrite, la réponse affichée est identique pour toutes les adresses. Lorsqu’un compte existe, l’application génère un jeton aléatoire valable une heure et envoie un lien de réinitialisation. Seul le hash du jeton est conservé en base. Le jeton est invalidé dès que le mot de passe est modifié.

### Configuration des e-mails

Symfony Mailer utilise la variable `MAILER_DSN`. La valeur de développement suivante accepte les messages sans les distribuer :

```dotenv
MAILER_DSN=null://null
```

Pour que les messages soient réellement envoyés en préproduction ou en production, remplacer cette valeur par le DSN SMTP du fournisseur utilisé.

#### Utiliser Gmail SMTP

Le compte Google doit avoir la validation en deux étapes activée. Créer ensuite un **mot de passe d'application** Google dédié à SunCamel ; le mot de passe habituel du compte Gmail ne doit pas être utilisé.

Configurer `MAILER_DSN` dans le fichier d'environnement privé du serveur :

```dotenv
MAILER_DSN=smtps://guilhem.camel%40gmail.com:MOT_DE_PASSE_APPLICATION@smtp.gmail.com:465
```

Supprimer les espaces affichés dans le mot de passe d'application avant de l'insérer dans le DSN. Les caractères spéciaux de l’identifiant et du mot de passe doivent être encodés comme dans une URL (`@` devient par exemple `%40`). Ne jamais committer le véritable DSN ou le mot de passe d'application.

Les messages utilisent actuellement `no-reply@suncamel.co.nz` comme expéditeur. Cette adresse doit être ajoutée et validée dans le compte Gmail via la fonction **Envoyer des e-mails en tant que**. Sans cela, Gmail peut remplacer l'expéditeur par `guilhem.camel@gmail.com` ou refuser l'envoi. Pour une utilisation plus soutenue en production, préférer un fournisseur d'e-mails transactionnels avec SPF et DKIM configurés pour `suncamel.co.nz`.

Après une modification de `.env.preprod`, recréer l'application et le worker afin de charger la nouvelle valeur :

```bash
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml up -d --force-recreate app worker
```

Les e-mails sont placés dans la file Messenger `async`. En préproduction, le service Docker `worker` la consomme en permanence et redémarre automatiquement. Son état et ses journaux se contrôlent avec :

```bash
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml ps worker
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml logs --tail=100 worker
```

Après confirmation du paiement par le webhook Stripe, le client reçoit le récapitulatif de sa réservation (vélos, quantités, dates et heures, total et lien vers les Terms & Conditions). Une copie opérationnelle contenant aussi l’adresse du client est envoyée à `guilhem.camel@gmail.com`.

## Configuration Stripe

Le développement local et la préproduction utilisent des configurations séparées :

- `.env.local`, privé et exclu de Git/Docker, contient uniquement les identifiants du sandbox Stripe local ;
- `.env.preprod`, privé sur le serveur, reste injecté par `compose.preprod.yaml` et n'est jamais remplacé par la configuration locale.

Compléter les variables déjà présentes dans `.env.local` avec les valeurs du mode test Stripe :

```dotenv
STRIPE_SECRET_KEY=sk_test_...
STRIPE_CURRENCY=nzd
STRIPE_WEBHOOK_SECRET=whsec_...
```

L'application refuse volontairement une clé `sk_live_` lorsque `APP_ENV=dev`.

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
Redémarrer ensuite le serveur Symfony ou vider son cache si les anciennes variables restent chargées :

```bash
php bin/console cache:clear
```

### Cycle d’une réservation

1. Le client choisit une période de location.
2. Les dates sont transmises au catalogue puis à la fiche produit.
3. Le vélo est ajouté au panier conservé dans la session.
4. Le serveur vérifie à nouveau le stock pour la période choisie.
5. Le client renseigne son adresse e-mail et choisit éventuellement de créer un compte.
6. Au passage en caisse, une réservation `pending` bloque temporairement le stock.
7. Une session Stripe Checkout est créée avec la référence de réservation et l’adresse e-mail.
8. Le webhook Stripe passe la réservation à l’état `paid` après confirmation du paiement.
9. Deux e-mails sont placés dans la file Messenger : la confirmation client et la notification administrative.
10. Une session Stripe annulée, expirée ou échouée libère le stock.

Les réservations temporaires expirent après environ 31 minutes. La disponibilité ignore automatiquement les réservations `pending` expirées.

## Calcul des disponibilités

Un vélo est indisponible lorsque le nombre de réservations qui se chevauchent atteint son `stockQuantity`.

Les réservations prises en compte sont :

- les réservations payées ;
- les réservations en attente dont la date d’expiration n’est pas dépassée.

Une transaction et un verrou pessimiste sur le produit protègent la création d’une réservation contre les paiements simultanés.

Le fuseau métier utilisé pour les dates de location est `Pacific/Auckland`. Les horaires sont convertis et stockés en UTC dans MariaDB, puis reconvertis vers `Pacific/Auckland` pour le panier, le compte client, l’administration, Stripe et les e-mails. Cette règle évite les décalages et prend en charge automatiquement l’heure d’été néo-zélandaise.

## Tarification des locations

Chaque produit possède un prix demi-journée et un prix journée. Sur une seule date, la demi-journée commence à 08:00 ou 12:00 et le retour est calculé quatre heures plus tard ; la journée complète couvre 08:00–16:00. Pour plusieurs dates, le départ peut être fixé à 08:00, 12:00 ou 16:00 et le retour à 08:00, 12:00 ou 16:00, sans modifier le prix calculé sur le nombre de journées inclusives.

Les paliers dégressifs se configurent dans **Administration > Tarifs dégressifs**. Un palier associe un nombre minimum de jours à un prix journalier. Pour une durée donnée, le palier le plus élevé atteint s’applique à l’ensemble des jours. Sans palier applicable, le prix journée du produit est utilisé.

## Administration

L’administration est disponible sur :

```text
/admin
```

Elle nécessite le rôle `ROLE_ADMIN` et permet de gérer :

- les produits et leur stock ;
- les prix demi-journée, journée et les paliers dégressifs ;
- les images des produits ;
- les questions et réponses de la FAQ ;
- les Terms & Conditions avec un éditeur HTML dédié ;
- les utilisateurs et leurs rôles ;
- les réservations et leur statut.
- les périodes pendant lesquelles la location est fermée et le message affiché aux clients.

Les migrations initiales créent des comptes de démonstration. Ils ne doivent pas être conservés tels quels en production : changez leurs mots de passe ou supprimez-les avant le déploiement.

### Fermetures des locations

Le menu **Administration > Fermetures des locations** permet de créer une période inclusive avec une date de début, une date de fin et un message. Lorsque le message est vide, `Rental is closed` est utilisé.

Les jours concernés sont barrés et non sélectionnables dans le calendrier public. Le message est affiché au survol ou au focus. Une plage commençant avant la fermeture et se terminant après celle-ci est également refusée. Le serveur applique la même règle lors de l’ajout au panier et du paiement afin d’empêcher le contournement du calendrier.

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
  security/              Connexion, inscription et gestion des mots de passe
tests/                    Infrastructure PHPUnit
```

## Principales entités

- `Product` : vélo, prix, stock, caractéristiques et collection.
- `ProductImage` : galerie associée à un produit.
- `Booking` : réservation, client, statut, montant et session Stripe.
- `BookingItem` : vélo, période, quantité et prix figé au moment de la commande.
- `User` : compte client ou administrateur et données temporaires de réinitialisation du mot de passe.
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
| `GET/POST` | `/account/password` | Changement du mot de passe d’un utilisateur connecté |
| `GET` | `/account/orders` | Commandes à venir et historique de l’utilisateur connecté |
| `GET/POST` | `/forgot-password` | Demande d’un lien de réinitialisation |
| `GET/POST` | `/reset-password/{token}` | Choix d’un nouveau mot de passe avec un jeton temporaire |
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

PHPUnit couvre actuellement les principales règles des entités et services de réservation. Les priorités complémentaires recommandées sont :

1. validation des périodes de location ;
2. détection des chevauchements de réservations ;
3. calcul du prix sur plusieurs jours ;
4. ajout et suppression d’articles du panier ;
5. signature et idempotence du webhook Stripe ;
6. permissions de l’administration.
7. changement et réinitialisation du mot de passe.

## Préparation à la production

Avant un déploiement public :

- définir un `APP_SECRET` robuste ;
- configurer un `MAILER_DSN` réel et vérifier la délivrabilité des e-mails de réinitialisation ;
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
git remote add origin git@github.com:ErwanVasserot/suncamel.git
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
- `MAILER_DSN`
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

## Préproduction Docker temporaire

La préproduction utilise `compose.preprod.yaml` et reste isolée des versions de PHP et de MariaDB installées sur l’hôte. Le service HTTP écoute uniquement sur `127.0.0.1:18081` afin d’être publié par le reverse proxy Nginx du VPS.

Créer la configuration locale au déploiement :

```bash
cp .env.preprod.example .env.preprod
```

Remplacer toutes les valeurs d’exemple, puis démarrer la préproduction :

```bash
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml up -d --build
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml ps
```

La commande démarre trois services :

- `app`, l’application web Apache/PHP ;
- `database`, MariaDB ;
- `worker`, le consommateur Messenger chargé notamment des e-mails.

Les migrations sont appliquées automatiquement au démarrage de `app`. Vérifier ensuite le worker et les migrations :

```bash
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml ps worker
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml logs --tail=100 worker
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml exec app php bin/console doctrine:migrations:status --env=prod --no-debug
```

La base MariaDB, les images produits et les données d’exécution sont conservées dans des volumes dédiés. Un arrêt simple préserve ces données :

```bash
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml down
```

La suppression définitive de la préproduction, données comprises, est explicite :

```bash
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml down --volumes --remove-orphans
```

Cette dernière commande supprime la base et les images téléversées de préproduction. Elle ne doit être exécutée qu’après avoir confirmé qu’aucune donnée ne doit être conservée.

## Dépannage

### Les e-mails restent en attente

Vérifier que le worker est démarré et consulter la file :

```bash
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml ps worker
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml logs --tail=100 worker
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml exec app php bin/console messenger:stats --env=prod
```

Le transport `null://null` accepte les messages mais ne les distribue pas. En préproduction, vérifier sans exposer le secret que `MAILER_DSN` utilise bien `smtps` et `smtp.gmail.com` :

```bash
sudo docker compose --env-file .env.preprod -f compose.preprod.yaml exec app php -r '
$parts = parse_url(getenv("MAILER_DSN") ?: "");
echo "scheme: ".($parts["scheme"] ?? "absent").PHP_EOL;
echo "host: ".($parts["host"] ?? "absent").PHP_EOL;
'
```

### Les styles ou templates récemment modifiés ne sont pas visibles

Si `public/assets/` existe, Symfony sert cette compilation en priorité et ignore les changements apportés aux fichiers source. Après une modification de Tailwind, d’un template ou d’un contrôleur Stimulus, actualiser toute la compilation locale :

```bash
php bin/console tailwind:build --minify
php bin/console asset-map:compile
php bin/console cache:clear
```

Effectuer ensuite un rechargement forcé du navigateur (`Cmd + Shift + R` sur macOS, `Ctrl + Shift + R` sur Windows ou Linux). La commande `asset-map:compile` est destinée principalement à la production ; pour un développement continu, ne pas conserver une ancienne compilation dans `public/assets/` et utiliser Tailwind en mode watch :

```bash
php bin/console tailwind:build --watch
```

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
