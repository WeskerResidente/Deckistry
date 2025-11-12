# 🔧 Debug du système de mot de passe oublié

## ✅ Corrections apportées
1. Correction du conflit de variable `$email` → `$emailAddress` et `$emailMessage`
2. Ajout de logs d'erreur détaillés
3. Création de routes de debug pour tester sans email

## 🧪 Routes de Test (Développement uniquement)

### 1. Tester l'envoi d'email
Accédez à cette URL dans votre navigateur :
```
http://127.0.0.1:8000/debug/test-email/normanbelaid@gmail.com
```

Cela va :
- Envoyer un email de test via Mailtrap
- Afficher un message de succès ou l'erreur exacte

### 2. Générer un lien de reset sans email
Accédez à cette URL :
```
http://127.0.0.1:8000/debug/reset-link/normanbelaid@gmail.com
```

Cela va :
- Créer un token de reset pour cet utilisateur
- Afficher directement le lien de réinitialisation
- Vous pouvez cliquer sur le lien pour changer le mot de passe

## 📧 Vérifier Mailtrap

1. Connectez-vous sur https://mailtrap.io/
2. Allez dans votre inbox
3. Vous devriez voir les emails de test arriver

## 🐛 Si l'email ne fonctionne toujours pas

### Vérification 1 : Credentials Mailtrap
Dans le fichier `.env`, vérifiez que vos identifiants Mailtrap sont corrects :
```env
MAILER_DSN=smtp://488d9ca9027f63:f464896b929d38@smtp.mailtrap.io:2525
```

### Vérification 2 : Cache
```bash
php bin/console cache:clear
```

### Vérification 3 : Logs Symfony
Regardez les logs :
```bash
tail -f var/log/dev.log
```

### Vérification 4 : Alternative - Utiliser null mailer
Si Mailtrap ne fonctionne pas, utilisez temporairement :
```env
MAILER_DSN=null://null
```

Puis allez sur http://127.0.0.1:8000/_profiler et cliquez sur la dernière requête pour voir l'email dans le profiler.

## 🔄 Flux de test complet

1. **Demander un reset** : http://127.0.0.1:8000/mot-de-passe-oublie
   - Entrer votre email : normanbelaid@gmail.com
   - Cliquer sur "Envoyer"

2. **Vérifier l'email** :
   - Option A : Mailtrap (https://mailtrap.io/)
   - Option B : Symfony Profiler si MAILER_DSN=null://null
   - Option C : Route debug (http://127.0.0.1:8000/debug/reset-link/normanbelaid@gmail.com)

3. **Réinitialiser le mot de passe** :
   - Cliquer sur le lien dans l'email
   - Entrer un nouveau mot de passe
   - Confirmer

4. **Se connecter** :
   - http://127.0.0.1:8000/login
   - Utiliser le nouveau mot de passe

## ⚠️ Important - Production

Avant de mettre en production, **SUPPRIMEZ** les routes de debug :
- `/debug/reset-link/{email}`
- `/debug/test-email/{email}`

Ces routes sont dans `PasswordResetController.php` et doivent être supprimées pour des raisons de sécurité.

