/*
  # Add Game Field to Machines Table

  1. Database Changes
    - Add `game` column to `machines` table
    - Set appropriate data type and constraints

  2. Notes
    - Game field will store the name of the game on the machine
    - Field is optional (can be NULL)
    - Uses VARCHAR(100) to accommodate various game names
*/

-- Add game column to machines table
ALTER TABLE machines 
ADD COLUMN game VARCHAR(100) NULL 
AFTER model;

-- Add index for better performance when filtering by game
CREATE INDEX idx_machines_game ON machines(game);