--Add currency column if it doesn't exist
ALTER TABLE users ADD COLUMN currency VARCHAR(10) DEFAULT 'PHP';

-- Add timezone column
ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT 'Asia/Manila';

-- Add theme column
ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT 'light';

-- Add notifications columns
ALTER TABLE users ADD COLUMN notify_budget_alerts TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN notify_transaction_reminders TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN notify_monthly_reports TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN full_name VARCHAR(250) DEFAULT '';