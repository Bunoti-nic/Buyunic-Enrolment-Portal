# Composer Setup Instructions for BUYUNIC Enrollment Portal

## What is Composer?
Composer is a dependency manager for PHP that helps manage external libraries and packages. The BUYUNIC Enrollment Portal uses PHPMailer for advanced email functionality.

## Installation Steps

### Step 1: Install Composer (if not already installed)

#### Windows (XAMPP/WAMP):
1. Download Composer from: https://getcomposer.org/download/
2. Run the installer and follow the instructions
3. Restart your command prompt/terminal

#### Linux/Mac:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Install Project Dependencies

1. Open command prompt/terminal
2. Navigate to your project directory:
   ```bash
   cd C:\xampp\htdocs\your-project-folder
   # or
   cd /path/to/your/project
   ```

3. Run Composer install:
   ```bash
   composer install
   ```

### Step 3: Verify Installation

After running `composer install`, you should see:
- A `vendor/` folder in your project root
- The file `vendor/autoload.php` should exist

## If Composer Installation Fails

Don't worry! The BUYUNIC Portal includes a fallback email system that works without PHPMailer.

### Fallback Features:
- ✅ Email verification still works
- ✅ Password reset emails still work  
- ✅ OTP emails for admin login still work
- ✅ Notification emails still work
- ✅ Uses PHP's built-in `mail()` function
- ✅ Beautiful HTML email templates included

### Configure PHP Mail (if using fallback):

#### For XAMPP (Windows):
1. Edit `php.ini` file (usually in `C:\xampp\php\php.ini`)
2. Find and configure these settings:
   ```ini
   [mail function]
   SMTP = smtp.gmail.com
   smtp_port = 587
   sendmail_from = your-email@gmail.com
   sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"
   ```

3. Edit `sendmail.ini` (usually in `C:\xampp\sendmail\sendmail.ini`):
   ```ini
   [sendmail]
   smtp_server=smtp.gmail.com
   smtp_port=587
   error_logfile=error.log
   debug_logfile=debug.log
   auth_username=your-email@gmail.com
   auth_password=your-app-password
   force_sender=your-email@gmail.com
   ```

4. Restart Apache

#### For Linux/Mac:
1. Install sendmail or postfix:
   ```bash
   # Ubuntu/Debian
   sudo apt-get install sendmail
   
   # CentOS/RHEL
   sudo yum install sendmail
   ```

2. Configure your mail server settings in `php.ini`

## Recommended: Use Composer for Best Results

While the fallback works, we recommend installing Composer for:
- ✅ Better email delivery rates
- ✅ Advanced email features (attachments, encryption)
- ✅ Better error handling
- ✅ SMTP authentication support
- ✅ Future feature compatibility

## Troubleshooting

### Error: "composer: command not found"
- Composer is not installed or not in your PATH
- Follow Step 1 above to install Composer

### Error: "vendor/autoload.php not found"
- Run `composer install` in your project directory
- Make sure you're in the correct folder

### Emails not sending (fallback mode):
- Check your PHP mail configuration
- Verify SMTP settings in `config/database.php`
- Check server mail logs for errors

### Permission Errors:
```bash
# On Linux/Mac, if you get permission errors:
sudo chown -R www-data:www-data vendor/
sudo chmod -R 755 vendor/
```

## Getting Help

If you're still having issues:
1. Check the error logs in your web server
2. Ensure your hosting provider supports mail() function
3. Contact BUYUNIC support: info@buyunic.ug
4. WhatsApp: +256 207 901 434

## Status Check

You can verify your email system status by:
1. Trying to register a new account
2. Checking if verification emails are received
3. Looking at server error logs for any mail-related errors

The system will automatically use the best available email method!