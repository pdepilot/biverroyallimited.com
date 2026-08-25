-- Transactions table: records of property purchases, rentals and sales used to
-- generate official receipts and certificates.
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(40) NOT NULL UNIQUE,
    customer_id INT DEFAULT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(190) DEFAULT NULL,
    customer_phone VARCHAR(40) DEFAULT NULL,
    customer_address VARCHAR(255) DEFAULT NULL,
    property_id INT DEFAULT NULL,
    property_title VARCHAR(200) DEFAULT NULL,
    property_location VARCHAR(200) DEFAULT NULL,
    transaction_type VARCHAR(20) NOT NULL DEFAULT 'purchase',
    amount BIGINT NOT NULL DEFAULT 0,
    amount_paid BIGINT NOT NULL DEFAULT 0,
    currency VARCHAR(5) NOT NULL DEFAULT 'NGN',
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'paid',
    transaction_date DATE NOT NULL,
    description TEXT DEFAULT NULL,
    issued_by VARCHAR(150) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tx_type (transaction_type),
    INDEX idx_tx_status (payment_status),
    INDEX idx_tx_customer (customer_id),
    INDEX idx_tx_date (transaction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
