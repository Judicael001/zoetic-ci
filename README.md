# 🌐 ZoeTIC — Plateforme Web & Système de Gestion

<div align="center">
  <img src="favicon.svg" alt="ZoeTIC Logo" width="100" height="100">
  <p><strong>Excellence en Formation Numérique & Prestations Techniques sur le Terrain</strong></p>
  <p>Abidjan, Côte d'Ivoire 🇨🇮</p>

  [![PHP Version](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
  [![Database](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
  [![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/fr/docs/Web/JavaScript)
</div>

---

## 📌 Présentation du Projet

**ZoeTIC** est une plateforme web moderne et complète conçue pour un centre de formation professionnelle aux métiers de la tech et une entreprise de prestations de services informatiques en Côte d'Ivoire.

La plateforme permet à la fois de présenter le catalogue pédagogique, de collecter les inscriptions en ligne, de valoriser les interventions techniques sur le terrain (vidéosurveillance, réseaux, sécurité) avec preuves vidéo et retours clients oraux, et de piloter l'ensemble de l'activité depuis un tableau de bord d'administration robuste.

---

## ✨ Fonctionnalités Clés

### 🎓 1. Catalogue de Formations & Inscriptions
- Filtrage dynamique par pôle (Cybersécurité, Développement, Bureautique, Méthodes Agiles).
- Fiches de cours détaillées (durée, niveau, tarif, formateur référent).
- Tunnel de candidature et d'inscription fluide avec notification.

### 🛠️ 2. Espace Réalisations & Confessions Clients (Nouveau)
- Vitrine des interventions techniques sur le terrain (Vidéosurveillance IP 4K, Câblage réseau & Baies, Sécurité électronique).
- **Lecteur vidéo intégré** montrant les équipes de techniciens en plein chantier.
- **Confessions clients authentiques** : intégration de vidéos filmées sur place et de notes vocales (lecteur audio style dictaphone) recueillies auprès des clients satisfaits.
- Modale interactive détaillée sans rechargement de page.

### 🤖 3. Chatbot Intelligent & Support
- Moteur conversationnel interactif avec classification automatique d'intentions (*intent detection* : formations, tarifs, interventions sur le terrain, inscriptions, localisation).
- Historique de session, limitation de débit (*rate limiting* anti-abus) et système de notation de pertinence (*feedback*).
- Compatible avec l'API Google Gemini pour l'assistance générative avancée.

### 📰 4. Blog & Actualités Technologiques
- Publication d'articles avec éditeur riche, catégories et pagination dynamique.
- Espace commentaires modérable depuis l'espace d'administration.

### ⚙️ 5. Panneau d'Administration Complet (`/admin`)
- **Tableau de bord & KPIs** : Statistiques en temps réel sur les étudiants, inscriptions, formations et chantiers.
- **Gestion des réalisations** : Formulaire multimédia complet avec téléversement sécurisé de photos multiples, vidéos et enregistrements audios.
- **Exports comptables et de suivi** (fichiers CSV / Excel des inscriptions).
- **Sécurité & Traçabilité** : Rôles d'accès (`super_admin`, `admin`, `editor`), gestion des administrateurs et journal d'audit des actions (`admin_logs`).
- **Personnalisation dynamique** : Gestion du logo du site avec génération automatique des favicons haute visibilité.

### 🎨 6. Design & Ergonomie
- Interface 100 % responsive (Mobile First) avec Tailwind CSS.
- Système bilingue Français / Anglais (`i18n`).
- Mode Nuit / Thème sombre avec bascule manuelle et programmation automatique (18h - 06h).
- Favicon vectoriel circulaire optimisé pour tous les écrans et onglets.

---

## 🛡️ Sécurité & Bonnes Pratiques

- **Protection CSRF** : Génération et vérification systématique de jetons sur toutes les requêtes d'action sensibles.
- **Requêtes préparées PDO** : Immunité totale contre les injections SQL.
- **Validation stricte des uploads** : Vérification réelle des types MIME (`finfo`) et limitation de taille côté serveur.
- **Variables d'environnement** : Isolation des identifiants et clés sensibles via `.env` (non versionné).
- **Hachage sécurisé** des mots de passe administrateurs via `password_hash()` (Bcrypt).

---

## 🚀 Installation & Développement Local

### Prérequis
- **PHP 8.2 ou supérieur** (avec extensions `pdo_mysql`, `gd`, `fileinfo`)
- **MySQL 8.0+** ou **MariaDB**
- Serveur local tel que **Laragon** (recommandé), XAMPP ou Docker

### Instructions d'installation

1. **Cloner le dépôt :**
   ```bash
   git clone https://github.com/Judicael001/zoetic-ci.git
   cd zoetic-ci
   ```

2. **Configurer l'environnement :**
   Copiez le fichier d'exemple et renseignez vos paramètres de base de données :
   ```bash
   cp .env.example .env
   ```

3. **Base de données :**
   - Créez une base de données MySQL nommée `zoeticci_db`.
   - Les tables et migrations s'initialisent automatiquement au premier lancement via les helpers `ensure_system_tables()` et `ensure_realisations_tables()`.

4. **Lancer le projet :**
   - Placez le dossier dans votre répertoire web (ex: `C:/laragon/www/zoetic-ci`).
   - Accédez au site via votre navigateur : `http://zoetic-ci.test` ou `http://localhost/zoetic-ci`.

---

## 👨‍💻 Auteur

Développé avec passion par **[Judicaël](https://github.com/Judicael001)**.  
N'hésitez pas à visiter mon profil GitHub pour découvrir d'autres projets !

---

<div align="center">
  <sub>© 2026 ZoeTIC Côte d'Ivoire. Tous droits réservés.</sub>
</div>
