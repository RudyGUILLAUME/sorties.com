# 🧭 sorties.com

**sorties.com** est une **plateforme web de gestion de sorties** entre amis, en famille ou dans un cadre professionnel.  
Elle permet d’organiser, rejoindre et gérer des événements facilement à travers une interface moderne et intuitive.

---

## 🚀 Technologies principales

- **Framework backend** : [Symfony 6.4 LTS (WebApp)](https://symfony.com/)
- **Template engine** : [Twig](https://twig.symfony.com/)
- **Base de données** : Doctrine ORM (MySQL ou PostgreSQL)
- **Front-end** : [Bootstrap 5](https://getbootstrap.com/)
- **Interface utilisateur** : Composants UI intégrés (modals, formulaires, alertes, etc.)
- **Environnement** : PHP 8.2+, Composer, Node.js, Symfony CLI

---

## ⚙️ Installation et configuration

### 1️⃣ Cloner le projet
```bash
git clone https://github.com/<ton-compte>/<ton-repo>.git
cd sorties.com
```

### 2️⃣ Installer les dépendances PHP
```bash
composer install
```

### 3️⃣ Installer les dépendances front-end
```bash
npm install
```

### 4️⃣ Configurer l’environnement
Copie le fichier `.env` en `.env.local` puis configure ta base de données :
```bash
DATABASE_URL="mysql://user:password@127.0.0.1:3306/sorties?serverVersion=8.0.32&charset=utf8mb4"
```

### 5️⃣ Créer la base de données
```bash
symfony console doctrine:database:create
```

### 6️⃣ Exécuter les migrations
```bash
symfony console doctrine:migrations:migrate
```

### 7️⃣ Lancer le serveur local
```bash
symfony serve -d
npm run dev
```

Le site est ensuite disponible à l’adresse :  
👉 http://127.0.0.1:8000

---

## 🧩 Structure du projet

```
sorties.com/
├── assets/             # Fichiers front (JS, SCSS)
├── config/             # Configuration Symfony
├── migrations/         # Fichiers de migration Doctrine
├── public/             # Racine publique du site
├── src/                # Code source PHP (controllers, entities, services)
├── templates/          # Templates Twig
├── translations/       # Fichiers de traduction
├── tests/              # Tests unitaires et fonctionnels
└── .env                # Configuration d’environnement
```

---

## 🧪 Commandes utiles pour les tests

### Lancer les tests unitaires et fonctionnels
```bash
php bin/phpunit
```

### Lancer les assets front-end en mode développement
```bash
npm run watch
```

### Compiler les assets en production
```bash
npm run build
```

---

## 🧱 Fonctionnalités principales (prévisionnelles)

- 🔐 Authentification & gestion des utilisateurs  
- 📅 Création et gestion de sorties  
- 🧭 Participation à des événements  
- 💬 Système de messages et notifications  
- 🌍 Interface responsive (mobile / desktop)  
- ⚙️ Administration via interface dédiée  

---

## 💡 Auteur

**Projet développé par :**  
🧑‍💻 *R. Guillaume*, *Lucas Robichon* & *Elowan Coquillau*
📅 *2025*  
📫 *Projet académique / personnel basé sur Symfony 6.4 LTS*

---

## 📜 Licence

Ce projet est distribué sous licence **ENI**.  
Vous êtes libres de l’utiliser, le modifier et le distribuer sous les mêmes conditions.

---
