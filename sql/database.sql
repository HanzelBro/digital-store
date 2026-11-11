-- ==============================================================================
-- HanzelStore Database Schema & Initial Data
-- ==============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- --------------------------------------------------------
-- 1. Tabel: admins
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Akun admin default:
-- Username: admin
-- Password: admin123 (Sangat disarankan langsung diubah setelah login pertama kali)
INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$12$nzmuOuKLovCvEpTVG6wJV.T6prGfggyQENZimds3GITDz1rvlJLF.')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- --------------------------------------------------------
-- 2. Tabel: products
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `short_desc` VARCHAR(255),
  `category` VARCHAR(100) NOT NULL,
  `badge` VARCHAR(50) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT '📦',
  `image` VARCHAR(255) DEFAULT NULL,
  `features` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabel: product_durations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_durations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `label` VARCHAR(100) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `original_price` DECIMAL(12,2) DEFAULT NULL,
  `sort_order` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`product_id`),
  CONSTRAINT `fk_product_durations_product` FOREIGN KEY (`product_id`) 
    REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tabel: orders
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(50) NOT NULL UNIQUE,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(50) DEFAULT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_status` ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`payment_status`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Tabel: order_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `duration_label` VARCHAR(100) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  INDEX (`order_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) 
    REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Tabel: payment_confirmations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_confirmations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `order_code` VARCHAR(50) NOT NULL,
  `sender_name` VARCHAR(150) NOT NULL,
  `bank_name` VARCHAR(100) NOT NULL,
  `transfer_amount` DECIMAL(12,2) NOT NULL,
  `transfer_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`order_id`),
  INDEX (`order_code`),
  CONSTRAINT `fk_payment_confirmations_order` FOREIGN KEY (`order_id`) 
    REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Sample Data Produk Awal (Opsional)
-- --------------------------------------------------------
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_desc`, `category`, `badge`, `icon`, `image`, `features`) VALUES
(1, 'Canva Pro Edu / Lifetime', 'canva-pro-lifetime', 'Dapatkan akses penuh ke seluruh template premium, jutaan elemen grafis, resize otomatis, dan background remover beresolusi tinggi.', 'Akses desain grafis premium tanpa batas.', 'Desain Grafis', 'BESTSELLER', '🎨', 'canva-pro-1784281214.png', 'Akses Semua Template Premium|Background Remover Otomatis|Jutaan Foto & Elemen HD|Brand Kit Support'),
(2, 'CapCut Pro Premium', 'capcut-pro-premium', 'Akses semua fitur edit video eksklusif CapCut: auto-captions, efek transisi premium, export 4K 60fps tanpa watermark.', 'Fitur editing video terlengkap tanpa batas.', 'Video Editing', 'HOT', '🎬', 'capcut-pro-1784306551.png', 'Export 4K 60fps Tanpa Watermark|Auto Captions Multi Bahasa|Ratusan Efek & Transisi VIP|Cloud Storage 100GB'),
(3, 'Netflix Premium 4K UHD', 'netflix-premium-4k', 'Akun streaming film dan serial TV kualitas 4K Ultra HD. Profil privat dengan proteksi PIN dan garansi full durasi.', 'Nonton film & series favorit kualitas 4K UHD.', 'Streaming', 'NEW', '🍿', 'netflix-1784307678.png', 'Resolusi 4K Ultra HD|Profil Privat dengan PIN|Download Offline di Semua Device|Garansi Full Durasi')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `product_durations` (`product_id`, `label`, `price`, `original_price`, `sort_order`) VALUES
(1, '1 Bulan', 15000.00, 30000.00, 1),
(1, '1 Tahun', 45000.00, 95000.00, 2),
(2, '1 Bulan', 25000.00, 50000.00, 1),
(2, '3 Bulan', 65000.00, 130000.00, 2),
(3, '1 Bulan', 35000.00, 54000.00, 1)
ON DUPLICATE KEY UPDATE `id`=`id`;

COMMIT;
