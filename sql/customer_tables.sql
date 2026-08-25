-- Customers table: CRM-style store for Biver Royalty Homes customer data.
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    phone VARCHAR(40) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    customer_type VARCHAR(30) NOT NULL DEFAULT 'buyer',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    source VARCHAR(50) DEFAULT 'manual',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customers_status (status),
    INDEX idx_customers_type (customer_type),
    INDEX idx_customers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
