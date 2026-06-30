-- Run this file only if you already imported the old database/schema.sql before adding the payment gateway files.
USE ecommerce_db;

ALTER TABLE orders
  MODIFY payment_method ENUM('Cash on Delivery','SSLCommerz','Bkash','Nagad','Card') NOT NULL DEFAULT 'Cash on Delivery';

CREATE TABLE IF NOT EXISTS payment_gateway_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  gateway_name VARCHAR(80) NOT NULL DEFAULT 'SSLCommerz Demo',
  transaction_id VARCHAR(120) DEFAULT NULL,
  event_type VARCHAR(50) NOT NULL,
  payload JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_gateway_logs_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_gateway_logs_order (order_id),
  INDEX idx_gateway_logs_event (event_type),
  INDEX idx_gateway_logs_created (created_at)
) ENGINE=InnoDB;

INSERT INTO site_settings (setting_key, setting_value) VALUES
('payment_gateway_mode', 'demo'),
('payment_gateway_name', 'SSLCommerz Demo')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
