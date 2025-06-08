/*
  # Create machine groups system

  1. New Tables
    - `machine_groups`
      - `id` (int, primary key, auto increment)
      - `name` (varchar, unique, not null)
      - `description` (text, nullable)
      - `created_at` (timestamp, default current_timestamp)
      - `updated_at` (timestamp, default current_timestamp on update)
    - `machine_group_members`
      - `id` (int, primary key, auto increment)
      - `group_id` (int, foreign key to machine_groups)
      - `machine_id` (int, foreign key to machines)
      - `created_at` (timestamp, default current_timestamp)
      - Unique constraint on (group_id, machine_id)

  2. Security
    - These tables don't need RLS as they're admin-only features
    - Access control handled at application level

  3. Constraints
    - Group names must be unique
    - Each machine can belong to multiple groups
    - Groups must have at least 2 machines (enforced at application level)
*/

-- Create machine_groups table
CREATE TABLE IF NOT EXISTS machine_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create machine_group_members table
CREATE TABLE IF NOT EXISTS machine_group_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  machine_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES machine_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE,
  UNIQUE KEY unique_group_machine (group_id, machine_id)
);

-- Create indexes for better performance
CREATE INDEX idx_machine_groups_name ON machine_groups(name);
CREATE INDEX idx_machine_group_members_group_id ON machine_group_members(group_id);
CREATE INDEX idx_machine_group_members_machine_id ON machine_group_members(machine_id);