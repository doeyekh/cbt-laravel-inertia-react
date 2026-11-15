-- Step 1: Disable foreign keys
PRAGMA foreign_keys=OFF;

-- Step 2: Drop the columns that are causing issues
ALTER TABLE exam_schedules DROP COLUMN session_id;
ALTER TABLE exam_schedules DROP COLUMN room_id;

-- Step 3: Re-enable foreign keys
PRAGMA foreign_keys=ON;
-- Step 4: Verify the columns are gone
PRAGMA table_info(exam_schedules);
