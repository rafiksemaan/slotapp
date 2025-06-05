-- Add new machine_types table
CREATE TABLE IF NOT EXISTS `machine_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default machine types
INSERT INTO `machine_types` (`name`, `description`) VALUES
('CASH', 'Cash-based slot machine'),
('COINS', 'Coin-operated slot machine'),
('GAMBEE', 'Gambee electronic gaming machine');

-- Modify machines table to use machine_types
ALTER TABLE `machines` 
  DROP COLUMN `type`,
  ADD COLUMN `type_id` int AFTER `model`,
  ADD KEY `type_id` (`type_id`);

-- Update existing machines to use new type_id
UPDATE `machines` m
JOIN `machine_types` mt ON m.type = mt.name;