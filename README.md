# Mzansi Munch

## What it is
Mzansi Munch is a food delivery platform built for township-based users. It was made as a school project to connect local sellers with buyers in townships and provide delivery through delivery partners.

## User roles and what they can do
- **Buyer** - Browse meals from sellers, add items to cart, place orders, track order status, and manage their profile/settings.
- **Seller** - Add and manage meals, update stock, view incoming orders, manage orders, and check sales/income.
- **Delivery Partner** - View available/delivery orders, accept and update delivery status, view delivery info and income.

## Technologies used
- PHP
- MySQL
- HTML
- CSS
- JavaScript

## Live link
[https://mzansimunch.infinityfreeapp.com/](https://mzansimunch.infinityfreeapp.com/)

## How to run it locally
1. Install [XAMPP](https://www.apachefriends.org/).
2. Clone or copy this repo into your XAMPP htdocs folder.
3. Import the database. Look for a SQL dump in the repo (if present) into MySQL via phpMyAdmin, or create a database and match the details in includes/db_config.php.
4. Update includes/db_config.php if needed for your local MySQL credentials.
5. Start Apache and MySQL in XAMPP, then open the site in your browser (e.g. http://localhost/SummativeAssessment/).

## Short folder structure
- dmin/ - Admin dashboard files
- css/, js/, images/ - Styles, scripts, and assets
- includes/ - Shared includes (e.g. db_config.php)
- uploads/ - Uploaded meal images
- Root PHP files - Main pages for buyers, sellers, delivery partners, and auth

## Author
Mikaela Padayachie
