CREATE TABLE users (
    id INT SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);   

select * from users;

select * from storyboard_panels;

DELETE FROM users WHERE username = 'user1';
\timing

EXPLAIN ANALYZE 
SELECT shot_number, prompt, image_url 
FROM storyboard_panels 
WHERE project_id = 101 
ORDER BY shot_number ASC;


EXPLAIN ANALYZE 
SELECT shot_number, prompt, image_url x
FROM storyboard_panels 
WHERE project_id = 102 
ORDER BY shot_number ASC;

INSERT INTO storyboard_panels 
(project_id, user_id, shot_number, prompt, image_url)
VALUES (101, 1, 1, 'dafvdafv', 'adfvadfv')
ON CONFLICT (project_id, shot_number)
DO UPDATE SET prompt = EXCLUDED.prompt, image_url = EXCLUDED.image_url;


SELECT project_id, user_id, name 
FROM projects 
WHERE user_id = 2
ORDER BY project_id ASC