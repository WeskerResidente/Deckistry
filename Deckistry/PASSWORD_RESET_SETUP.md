# Configuration du Système de Récupération de Mot de Passe

## ✅ Fonctionnalités Installées

Le système de récupération de mot de passe est maintenant complètement installé avec :

### 📋 Base de données
- ✅ Champs `reset_token` et `reset_token_expires_at` ajoutés à la table `users`
- ✅ Migration exécutée avec succès

### 🛣️ Routes disponibles
- `/mot-de-passe-oublie` (app_forgot_password) - Demande de réinitialisation
- `/reinitialiser-mot-de-passe/{token}` (app_reset_password) - Formulaire de nouveau mot de passe

### 📄 Pages créées
1. **Demande de réinitialisation** (`templates/password_reset/request.html.twig`)
   - Formulaire pour entrer l'email
   - Lien de retour vers la connexion

2. **Réinitialisation** (`templates/password_reset/reset.html.twig`)
   - Formulaire pour le nouveau mot de passe
   - Validation en temps réel
   - Vérification que les mots de passe correspondent

3. **Email de réinitialisation** (`templates/password_reset/email.html.twig`)
   - Email HTML professionnel
   - Bouton et lien de réinitialisation
   - Expiration après 1 heure

### 🔒 Sécurité
- Token unique généré avec `random_bytes(32)`
- Expiration automatique après 1 heure
- Token supprimé après utilisation
- Ne révèle pas si un email existe dans la base

---

## ⚙️ Configuration de l'Email (IMPORTANT)

Actuellement, le mailer est en mode "null" (aucun email n'est envoyé).

### Option 1 : Gmail (Développement)

1. Modifiez le fichier `.env` :
```env
MAILER_DSN=gmail://votre-email@gmail.com:votre-mot-de-passe-application@default
```

2. Créez un mot de passe d'application Gmail :
   - Allez sur https://myaccount.google.com/apppasswords
   - Générez un nouveau mot de passe pour "Symfony"
   - Utilisez ce mot de passe dans la DSN

### Option 2 : Mailtrap (Développement/Test)

1. Créez un compte sur https://mailtrap.io (gratuit)

2. Modifiez le fichier `.env` :
```env
MAILER_DSN=smtp://[username]:[password]@smtp.mailtrap.io:2525
```

### Option 3 : SMTP Générique

```env
MAILER_DSN=smtp://utilisateur:motdepasse@smtp.exemple.com:587
```

### Option 4 : Mode Test (Développement uniquement)

Pour tester sans envoyer d'emails :
```env
MAILER_DSN=null://null
```

Les emails seront disponibles dans le Symfony Profiler en bas de page.

---

## 🧪 Test du Système

### 1. Tester la demande de réinitialisation
- Allez sur http://127.0.0.1:8000/login
- Cliquez sur "Mot de passe oublié ?"
- Entrez un email valide
- Vérifiez l'email reçu (ou le Profiler si mode null)

### 2. Tester la réinitialisation
- Cliquez sur le lien dans l'email
- Entrez un nouveau mot de passe
- Confirmez le mot de passe
- Connectez-vous avec le nouveau mot de passe

---

## 📝 Flux Complet

1. **Utilisateur oublie son mot de passe**
   → Va sur `/login` → Clique "Mot de passe oublié ?"

2. **Demande de réinitialisation**
   → Entre son email sur `/mot-de-passe-oublie`
   → Reçoit un email avec un token unique

3. **Clic sur le lien**
   → Redirigé vers `/reinitialiser-mot-de-passe/{token}`
   → Vérifie que le token est valide et non expiré

4. **Nouveau mot de passe**
   → Entre et confirme le nouveau mot de passe
   → Mot de passe hashé et sauvegardé
   → Token supprimé
   → Redirigé vers `/login`

---

## 🔧 Personnalisation

### Modifier la durée de validité du token
Dans `PasswordResetController.php`, ligne 28 :
```php
$user->setResetTokenExpiresAt(new \DateTime('+1 hour')); // Changez '+1 hour'
```

### Modifier l'email expéditeur
Dans `PasswordResetController.php`, ligne 40 :
```php
->from('noreply@deckistry.com') // Changez cette adresse
```

### Modifier le design de l'email
Éditez `templates/password_reset/email.html.twig`

---

## ✨ Le lien est maintenant visible !

Le lien "Mot de passe oublié ?" a été ajouté sous le champ mot de passe dans la page de connexion.

