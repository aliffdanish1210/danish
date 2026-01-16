Event Registration System

📖 Project Description

This project is a secure web application built using the Laravel framework.
It focuses on robust user management, incorporating:

✅ Multi-role access control (Admin, User, etc.)

✅ Secure authentication using Laravel Passport

✅ Multi-Factor Authentication (MFA)

✅ Secure file handling and authorization policies

✅ Strong focus on data integrity, privacy, and access control

⚙️ Installation Steps

Follow the steps below to set up the project locally:

1️⃣ Clone the repository
git clone <your-repository-url>
cd danish

2️⃣ Install dependencies
composer install
npm install && npm run dev

3️⃣ Configure environment file

Copy .env.example to .env and update your database credentials:

cp .env.example .env


Then generate the application key:

php artisan key:generate

4️⃣ Initialize Laravel Passport (Authentication Security)
php artisan passport:install


This will generate encryption keys and clients for API authentication.

🛡️ Security Features Summary

🔐 Multi-Factor Authentication (MFA)
Adds an extra layer of security using Google Authenticator or SMS verification.

👥 Role-Based Access Control (RBAC)
Implemented using Spatie/Laravel-Permission to restrict access based on user roles such as:

Admin

User

🌐 Force HTTPS (SSL/TLS)
Enforced in AppServiceProvider to ensure all data transmission is encrypted.

🛂 Policy-Based Authorization
Laravel Policies are used to control access to critical actions such as:

Update

Delete

View sensitive data

▶️ How to Run the Application

Start the Laravel development server:

php artisan serve


If you change routes or configurations, clear cache using:

php artisan optimize:clear

📦 Dependencies

Framework: Laravel

Authentication: Laravel Passport

Authorization / Roles: Spatie Laravel-Permission

Frontend Build Tool: Laravel Mix / Webpack

🖼️ Screenshots
🔑 Login Page
(image.png)

🏠 Dashboard
(image-1.png)