-- KREIRAJ OVAJ FAJL: setup_database.sql

CREATE DATABASE IF NOT EXISTS bif_ppv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bif_ppv;

-- Events tabela
CREATE TABLE IF NOT EXISTS events (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date DATETIME NOT NULL,
    price INT NOT NULL COMMENT 'Cena u centima',
    currency VARCHAR(3) DEFAULT 'rsd',
    early_bird_price INT,
    early_bird_until DATETIME,
    stream_url TEXT,
    poster_image VARCHAR(500),
    status ENUM('upcoming', 'live', 'finished') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Purchases tabela
CREATE TABLE IF NOT EXISTS purchases (
    id VARCHAR(50) PRIMARY KEY,
    event_id VARCHAR(50) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    amount INT NOT NULL COMMENT 'Iznos u centima',
    currency VARCHAR(3) DEFAULT 'rsd',
    payment_intent_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    access_expires_at DATETIME,
    
    INDEX idx_customer_email (customer_email),
    INDEX idx_status (status),
    INDEX idx_event_id (event_id)
);

-- Access tokens tabela
CREATE TABLE IF NOT EXISTS access_tokens (
    token VARCHAR(64) PRIMARY KEY,
    purchase_id VARCHAR(50) NOT NULL,
    event_id VARCHAR(50) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_accessed TIMESTAMP NULL,
    access_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_customer_email (customer_email),
    INDEX idx_event_id (event_id),
    INDEX idx_expires_at (expires_at)
);

-- Insert default event
INSERT IGNORE INTO events (id, title, description, date, price, currency, stream_url, status) VALUES
('bif-1-new-rise', 'BIF 1: New Rise', 'Najveći influenser fight show na Balkanu', '2025-07-21 20:00:00', 199900, 'rsd', 'https://vimeo.com/1017406920?fl=pl&fe=sh', 'upcoming');