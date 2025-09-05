-- init.sql for switcher-api

CREATE TABLE IF NOT EXISTS switchers (
    id SERIAL PRIMARY KEY,
    device_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    status BOOLEAN NOT NULL DEFAULT FALSE, -- false: off, true: on
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create an index on user_id for faster lookups
CREATE INDEX IF NOT EXISTS idx_switchers_user_id ON switchers(user_id);
