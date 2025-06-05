-- Update machines table structure
ALTER TABLE machines 
CHANGE COLUMN `type` `type_id` INT,
ADD FOREIGN KEY (type_id) REFERENCES machine_types(id);