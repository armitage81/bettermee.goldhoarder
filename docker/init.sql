USE goldhoarder;

CREATE TABLE IF NOT EXISTS gold_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entry_date DATE NOT NULL,
    amount INT NOT NULL,
    comment TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_date (user_id, entry_date),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO gold_entries (user_id, entry_date, amount, comment) VALUES
(1, '2026-06-20', 100, 'Starting gold'),
(1, '2026-06-21', 250, 'Quest reward'),
(1, '2026-06-22', 300, NULL),
(1, '2026-06-23', 301, NULL),
(1, '2026-06-24', 291, 'Spent 10 gold for a reward.');
