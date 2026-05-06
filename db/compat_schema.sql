-- Compatibility schema for runtime code in this project
-- Targets database: gym

CREATE TABLE IF NOT EXISTS gym_goers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fitness_goal VARCHAR(255) DEFAULT NULL,
    membership_plan INT DEFAULT NULL,
    preferred_trainer VARCHAR(100) DEFAULT NULL,
    gym_username VARCHAR(100) DEFAULT NULL,
    meal_plan INT DEFAULT NULL,
    workout_plan INT DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gym_goers_username (username),
    INDEX idx_gym_goers_gym (gym_username)
);

CREATE TABLE IF NOT EXISTS trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    specialization VARCHAR(255) DEFAULT NULL,
    experience VARCHAR(100) DEFAULT NULL,
    certifications TEXT DEFAULT NULL,
    gym_username VARCHAR(100) DEFAULT NULL,
    fee DECIMAL(10,2) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trainers_username (username),
    INDEX idx_trainers_gym (gym_username)
);

CREATE TABLE IF NOT EXISTS gym_owners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gym_owners_username (username)
);

CREATE TABLE IF NOT EXISTS sellers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sellers_username (username)
);

CREATE TABLE IF NOT EXISTS gyms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gym_username VARCHAR(100) NOT NULL UNIQUE,
    owner_username VARCHAR(100) NOT NULL,
    gym_name VARCHAR(255) NOT NULL,
    phone_num VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    weekdays_hours VARCHAR(100) DEFAULT NULL,
    weekends_hours VARCHAR(100) DEFAULT NULL,
    profile_pic VARCHAR(255) DEFAULT NULL,
    RegistrationFee DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gyms_owner (owner_username)
);

CREATE TABLE IF NOT EXISTS membership_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    gym_username VARCHAR(100) NOT NULL,
    plan_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    features TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_membership_plans_gym (gym_username)
);

CREATE TABLE IF NOT EXISTS workout_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_username VARCHAR(100) NOT NULL,
    plan_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    duration_weeks INT DEFAULT NULL,
    sessions_per_week INT DEFAULT NULL,
    difficulty_level VARCHAR(50) DEFAULT NULL,
    exercises TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_workout_plans_trainer (trainer_username)
);

CREATE TABLE IF NOT EXISTS meal_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_username VARCHAR(100) NOT NULL,
    plan_name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    daily_calories INT DEFAULT NULL,
    meals_per_day INT DEFAULT NULL,
    protein_percentage INT DEFAULT NULL,
    carbs_percentage INT DEFAULT NULL,
    fats_percentage INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_meal_plans_trainer (trainer_username)
);

CREATE TABLE IF NOT EXISTS inventory (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_username VARCHAR(100) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory_seller (seller_username)
);

CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_user_product (user_id, product_id)
);

CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist_user_product (user_id, product_id)
);

CREATE TABLE IF NOT EXISTS gym_visits (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    gym_username VARCHAR(100) NOT NULL,
    visit_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_visit_unique (username, gym_username, visit_date)
);

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    seller_username VARCHAR(100) DEFAULT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_status VARCHAR(50) DEFAULT 'pending',
    shipping_address TEXT DEFAULT NULL,
    billing_address TEXT DEFAULT NULL,
    payment_id INT DEFAULT NULL,
    customer_name VARCHAR(255) DEFAULT NULL,
    customer_email VARCHAR(255) DEFAULT NULL,
    customer_phone VARCHAR(30) DEFAULT NULL,
    tracking_number VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_seller (seller_username)
);
