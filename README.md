# Belhache Galettes

Premiere base du site prive de gestion des galettes en `PHP + MySQL + HTML + CSS`.

## Environnements cibles

Ce projet est totalement independant de `menu.belhache.net` et `dev.menu.belhache.net`.

- production cible : `galette.belhache.net`
- developpement cible : `dev.galette.belhache.net`

Repere hebergement fourni :

- prod files : `galette.belhache.net`
- dev files : `dev.galette.belhache.net`

## Couvre deja

- inscription et connexion
- roles `admin` et `utilisateur`
- catalogue prive de formules
- demande de devis multi-formules avec nombre de convives par formule
- suivi des demandes et messagerie liee au dossier
- back-office admin initial pour utilisateurs, recettes, formules et demandes
- schema MySQL couvrant aussi les briques suivantes a brancher ensuite :
  - devis finaux
  - frais fixes
  - pieces jointes admin
  - PDF
  - envoi email

## Installation

1. Creer une base MySQL.
2. Importer [db/schema.sql](/home/adrien/dev/galettes-privees/db/schema.sql).
3. Definir les variables d environnement :

```bash
export APP_ENV=dev
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=galettes_privees
export DB_USER=root
export DB_PASSWORD=''
```

4. Lancer localement :

```bash
php -S 127.0.0.1:8080 -t /home/adrien/dev/galettes-privees
```

5. Charger les donnees de demonstration et creer l admin :

```bash
php /home/adrien/dev/galettes-privees/scripts/seed_demo.php
```

Le seeder cree :

- l admin `enligne@belhache.net`
- un utilisateur de demonstration
- des ingredients
- plusieurs recettes
- plusieurs formules
- une demande de devis exemple avec messagerie

## Ou trouver les acces MySQL sur o2switch

Dans le cPanel o2switch :

1. ouvre `Bases de donnees MySQL`
2. repere le `nom de la base`
3. repere le `nom de l utilisateur MySQL`
4. si besoin, redefinis le `mot de passe` de cet utilisateur
5. verifie que l utilisateur est bien `associe a la base` avec tous les privileges

Tu peux aussi les retrouver via :

- `phpMyAdmin` pour voir le nom exact de la base
- `MultiPHP INI Editor` n est pas utile ici
- `Gestionnaire de fichiers` seulement pour deposer les fichiers, pas pour les acces SQL

Les informations a me donner ou a renseigner dans le projet sont :

```bash
DB_HOST=localhost
DB_PORT=3306
DB_NAME=nom_de_ta_base
DB_USER=nom_de_ton_utilisateur_sql
DB_PASSWORD=mot_de_passe_sql
```

## Suite prevue

- mot de passe oublie et changement de mot de passe
- edition/suppression completes
- calcul consolide du cout interne par formule et liste de courses admin
- generation PDF et envoi email
- gestion des devis finaux et des frais fixes depuis l interface
