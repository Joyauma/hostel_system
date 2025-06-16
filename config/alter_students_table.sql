USE hostel_system;

-- First remove the unique constraint from student_id
ALTER TABLE students
    DROP INDEX student_id;

-- Add new columns and rename student_id to roll
ALTER TABLE students
    ADD COLUMN dob DATE NULL AFTER gender,
    ADD COLUMN course VARCHAR(100) NULL AFTER address,
    ADD COLUMN year_of_study INT NULL AFTER course,
    ADD COLUMN guardian_address TEXT NULL AFTER guardian_phone,
    ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER guardian_address;

-- Rename the column and add unique constraint
ALTER TABLE students
    CHANGE COLUMN student_id roll VARCHAR(20) NOT NULL,
    ADD UNIQUE INDEX roll_unique (roll);

-- Update existing columns to be NOT NULL after adding them
ALTER TABLE students
    MODIFY COLUMN dob DATE NOT NULL,
    MODIFY COLUMN course VARCHAR(100) NOT NULL,
    MODIFY COLUMN year_of_study INT NOT NULL,
    MODIFY COLUMN guardian_address TEXT NOT NULL;
