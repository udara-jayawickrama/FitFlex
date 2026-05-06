# FitFlex Gym Management Application

FitFlex is a PHP and MySQL based gym management system with separate areas for gym-goers, trainers, gym owners, and sellers.

## Features

- User login and role-based dashboards
- Gym-goer workout, meal plan, wishlist, checkout, and order history pages
- Trainer tools for managing gym-goers, workout plans, and meal plans
- Gym owner management pages for gyms, customers, and membership plans
- Seller inventory and order management pages
- Frontend assets for styling and client-side behavior

## Project Structure

- `index.php` - main login page
- `register.php` - registration page
- `assets/` - CSS, JavaScript, and images
- `db/` - database configuration and schema
- `gym-goer/` - gym-goer portal
- `gym-owner/` - gym owner portal
- `seller/` - seller portal
- `trainer/` - trainer portal
- `uploads/` - uploaded gym images

## Setup

1. Copy the project into your local web server directory, such as WAMP `www`.
2. Create the database in MySQL and import `db/schema.sql`.
3. Update database credentials in `db/db_config.php` if needed.
4. Start Apache and MySQL in WAMP.
5. Open the project in your browser using the local server URL.

## Notes

- The project uses PHP session-based login flow.
- Uploaded images in `uploads/` are part of the current project state.
- Some database table names and schema details may need alignment with the application logic before production use.