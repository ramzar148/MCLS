-- Create call_comments table for maintenance call updates and comments
CREATE TABLE IF NOT EXISTS call_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_call_id (call_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (call_id) REFERENCES maintenance_calls(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
