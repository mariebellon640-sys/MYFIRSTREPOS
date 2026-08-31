# POISSON VIP

Plateforme web de commande et de livraison de poisson frais et de produits de la mer a Antananarivo.
Le projet met en relation les clients, les fournisseurs (mareyeurs, cooperatives, poissonneries), les
livreurs et l'equipe support, avec une tracabilite des lots de peche, un indice de fraicheur calcule et
le paiement Mobile Money.

## Sommaire

- [Fonctionnalites](#fonctionnalites)
- [Pile technique](#pile-technique)
- [Installation](#installation)
- [Comptes de demonstration](#comptes-de-demonstration)
- [Commandes utiles](#commandes-utiles)
- [API](#api)
- [Regles de gestion](#regles-de-gestion)
- [Organisation du code](#organisation-du-code)
- [Tests](#tests)
- [Limites connues](#limites-connues)

## Fonctionnalites

**Visiteur** : accueil, catalogue filtrable (categorie, fournisseur, prix, fraicheur, stock), fiche
produit, page de tracabilite d'un lot de peche, mentions legales.

**Client** : inscription e-mail/telephone, verification par OTP, reinitialisation de mot de passe,
adresses multiples, panier avec choix de preparation (entier, darne, filet), creneau de livraison,
code promo, paiement Mobile Money (MVola, Orange Money, Airtel Money) ou especes a la livraison,
historique et re-commande, facture, annulation avant preparation, suivi de livraison cartographie,
avis apres livraison, litiges, notifications, points de fidelite et parrainage, recommandations.

**Fournisseur** : validation prealable par l'administrateur, gestion du catalogue et des stocks,
declaration des lots de peche (lieu, date/heure de capture), traitement des commandes recues,
promotions, statistiques de vente et alertes de rupture.

**Livreur** : liste des courses, disponibilite, prise en charge, remontee de position GPS,
cloture livree/echouee, encaissement des paiements en especes.

**Administration et support** : pilotage des utilisateurs, validation des fournisseurs, supervision
des commandes, paiements et livraisons, affectation manuelle des livreurs, zones et frais de
livraison, codes promo, statistiques (chiffre d'affaires, panier moyen, produits les plus vendus,
zones actives), traitement des litiges et moderation des avis.

## Pile technique

| Composant | Choix |
|---|---|
| Langage / framework | PHP 8.3, Symfony 7 |
| Base de donnees | MySQL 8, Doctrine ORM |
| Rendu | Twig, Bootstrap 5, Leaflet (carte de suivi) |
| API | API Platform (REST / JSON-LD), JWT (LexikJWTAuthenticationBundle) |
| Asynchrone | Symfony Messenger (transport Doctrine) |
| Notifications | Symfony Mailer / Notifier |
| Tests | PHPUnit |

## Installation

Prerequis : PHP 8.3 (extensions `intl`, `pdo_mysql`, `mbstring`, `xml`), Composer 2 et MySQL 8.

```bash
composer install
cp .env .env.local            # ajuster DATABASE_URL, MAILER_DSN, APP_SECRET

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
php bin/console lexik:jwt:generate-keypair --skip-if-exists

php -S 127.0.0.1:8000 -t public      # ou : symfony serve
```

L'application est disponible sur <http://127.0.0.1:8000>.

Dans un second terminal, le worker traite les messages asynchrones (e-mails, affectation des
livraisons) :

```bash
php bin/console messenger:consume async -vv
```

## Comptes de demonstration

Les fixtures creent un jeu de donnees complet (zones, categories, produits, lots, commandes, avis).
Mot de passe commun : `Poisson2024!`.

| Role | Identifiant |
|---|---|
| Administrateur | `admin@poissonvip.mg` |
| Support | `support@poissonvip.mg` |
| Fournisseur valide | `fanja@mareyeur-tana.mg` |
| Fournisseur en attente | `naina@peche-toamasina.mg` |
| Livreur | `tiana@poissonvip.mg` |
| Client | `hasina@example.mg` |

## Commandes utiles

```bash
php bin/console poissonvip:fraicheur:actualiser   # recalcule les indices, retire les lots hors seuil
php bin/console poissonvip:livraisons:affecter    # rattrape les livraisons sans livreur
php bin/console doctrine:schema:validate
php bin/phpunit
```

Les deux premieres commandes sont concues pour une planification cron (par exemple toutes les
heures).

## API

API Platform expose le catalogue public en lecture, filtre pour ne montrer que les produits
commandables (fournisseur valide, produit actif, stock disponible) :

- `GET /api/produits` — recherche (`nom`, `espece`, `categorie.nom`, `fournisseur.nomCommercial`) et
  tri (`order[prixUnitaire]`, `order[nom]`, `order[dateMaj]`)
- `GET /api/produits/{id}`
- `GET /api/categories`, `GET /api/categories/{id}`
- `POST /api/login` — `{"identifiant": "...", "motDePasse": "..."}` renvoie un jeton JWT
- Documentation interactive : `/api/docs`

## Regles de gestion

1. Un produit n'est commandable que si son stock est suffisant au moment de la commande.
2. L'indice de fraicheur est calcule depuis l'heure de capture declaree ; sous le seuil configure
   (`FRAICHEUR_SEUIL_RETRAIT`), le lot est retire du catalogue.
3. Le client ne peut annuler sa commande que tant qu'elle n'est pas en preparation.
4. Un livreur ne recoit pas de nouvelle course tant qu'une livraison est en cours.
5. Un paiement en ligne doit etre confirme avant le passage de la commande en « validee » ; le
   paiement en especes est confirme par le livreur a la remise.
6. Un fournisseur doit etre valide par l'administrateur avant de publier des produits.
7. Un avis n'est possible qu'apres livraison et passe par la moderation du support.

## Organisation du code

```
src/
├── Api/             extension Doctrine restreignant le catalogue expose par l'API
├── Command/         taches planifiables (fraicheur, affectation des livraisons)
├── Controller/      espaces public, client, fournisseur, livreur, admin et support
├── DataFixtures/    jeu de donnees de demonstration
├── Entity/          modele de donnees Doctrine
├── Enum/            statuts et types metier
├── Form/            formulaires Symfony
├── Message/         messages Messenger
├── MessageHandler/  traitements asynchrones
├── Repository/      requetes DQL (catalogue, statistiques, disponibilites)
├── Security/        authentification, OTP, voters
└── Service/         logique metier (panier, commande, paiement, fraicheur, livraison, stats)
```

## Tests

```bash
php bin/phpunit
```

La suite couvre le calcul de fraicheur, les regles de gestion, les parcours public, commande et
back-office, ainsi que l'API (catalogue, filtres, authentification JWT).

Prealable : base de test initialisee.

```bash
APP_ENV=test php bin/console doctrine:schema:create
APP_ENV=test php bin/console doctrine:fixtures:load --no-interaction
```

## Limites connues

- Les passerelles Mobile Money fonctionnent en mode simulation (`MOBILE_MONEY_MODE=simulation`)
  tant que les contrats API operateurs ne sont pas signes ; le service est concu pour brancher les
  URL reelles via `MVOLA_API_URL`, `ORANGE_MONEY_API_URL` et `AIRTEL_MONEY_API_URL`.
- La traduction malagasy couvre la navigation et les elements transverses ; les pages metier restent
  a traduire.
- Les notifications SMS et push necessitent la configuration d'un transport Notifier ; seul l'e-mail
  est branche par defaut.
