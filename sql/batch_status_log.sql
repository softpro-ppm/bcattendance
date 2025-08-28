-- =====================================================
-- Batch Status Log Table
-- Tracks all changes to batch statuses for audit purposes
-- =====================================================

CREATE TABLE IF NOT EXISTS `batch_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `old_status` enum('active','inactive','completed') NOT NULL,
  `new_status` enum('active','inactive','completed') NOT NULL,
  `changed_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who made the change',
  `change_reason` text DEFAULT NULL COMMENT 'Reason for status change',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `batch_id` (`batch_id`),
  KEY `changed_by` (`changed_by`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `batch_status_log_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `batch_status_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample log entry for demonstration
INSERT INTO `batch_status_log` (`batch_id`, `old_status`, `new_status`, `changed_by`, `change_reason`, `created_at`) VALUES
(15, 'completed', 'active', 1, 'Automatic status update after date modification', NOW());

-- Display confirmation
SELECT 'Batch Status Log Table Created Successfully!' as Status;
