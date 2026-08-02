DROP TABLE IF EXISTS image_generations CASCADE;
DROP TABLE IF EXISTS storyboard_panels CASCADE;
DROP TABLE IF EXISTS projects CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Privacy policy / terms-of-use consent (see backend/consent.php).
    -- NULL until the user accepts; consent_version lets us re-prompt
    -- everyone if the policy text changes materially later.
    consent_accepted_at TIMESTAMP,
    consent_version     VARCHAR(20)
);

CREATE TABLE projects(
    project_id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
    name VARCHAR(50) NOT NULL,
    hero_image_url TEXT,
    CONSTRAINT projects_user_name_unique UNIQUE (user_id, name)
);

CREATE TABLE storyboard_panels (
    panel_id SERIAL PRIMARY KEY,
    project_id INT REFERENCES projects(project_id) ON DELETE CASCADE,
    user_id INT REFERENCES users(user_id) ON DELETE CASCADE,
    shot_number INT NOT NULL,
    prompt TEXT NOT NULL,
    image_url TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE image_generations (
    gen_id       SERIAL PRIMARY KEY,
    user_id      INT REFERENCES users(user_id) ON DELETE CASCADE,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Login brute-force lockout tracking (5 failed attempts -> 15 min lockout).
-- Keyed by username rather than user_id since a lockout must apply even
-- when the username doesn't exist (otherwise an attacker could probe for
-- valid usernames by checking which ones never lock out).
CREATE TABLE login_attempts (
    username     VARCHAR(50) PRIMARY KEY,
    failed_count INT NOT NULL DEFAULT 0,
    locked_until TIMESTAMP
);

-- says that no storyboard_panel can have identical project_id and shot_number
ALTER TABLE storyboard_panels 
ADD CONSTRAINT unique_project_shot UNIQUE (project_id, shot_number);

-- CREATE INDEX idx_project_panels_sequence ON storyboard_panels(project_id, shot_number);

INSERT INTO users ( username, password, role) VALUES 
('admin1', '$2y$12$ysRXJhQ61So/Z5OJLokR.eRfpWHroL3sKfRE7pWq0Rm3M2BFbH/JW', 'admin'),
('user1', '$2y$12$ysRXJhQ61So/Z5OJLokR.eRfpWHroL3sKfRE7pWq0Rm3M2BFbH/JW', 'user');

INSERT INTO projects (user_id, name) 
VALUES (2, 'Cyberpunk Chase Scene');

INSERT INTO storyboard_panels (project_id, user_id, shot_number, prompt, image_url) VALUES
(1, 2, 1,  '(extreme wide shot:1.0), neon city skyline, nighttime, (rim-light:0.7), sketchy lora style', 'https://storage.inksync.local/panels/p1_1.png'),
(1, 2, 2,  '(established shot:0.9), crowded street market, rain reflections, (low-angle:0.8), hand-drawn', 'https://storage.inksync.local/panels/p1_2.png'),
(1, 2, 3,  '(medium shot:1.0), main character looking up at flying vehicle, (dutch-angle:0.95), sharp ink lines', 'https://storage.inksync.local/panels/p1_3.png'),
(1, 2, 4,  '(close-up:1.0), character eyes narrow, glowing neon text reflection, (cinematic-lighting:0.8)', 'https://storage.inksync.local/panels/p1_4.png'),
(1, 2, 5,  '(tracking shot:0.85), character runs down narrow alleyway, depth midas geometry, ink wash sketch', 'https://storage.inksync.local/panels/p1_5.png'),
(1, 2, 6,  '(over-the-shoulder shot:0.9), drone tracking target from above rooftops, (high-angle:0.7)', 'https://storage.inksync.local/panels/p1_6.png'),
(1, 2, 7,  '(medium close-up:1.0), character jumping over market stall, dynamic pose, controlnet canny lock', 'https://storage.inksync.local/panels/p1_7.png'),
(1, 2, 8,  '(action shot:1.1), smoke grenade exploding, heavy shadows, high-contrast monochrome ink style', 'https://storage.inksync.local/panels/p1_8.png'),
(1, 2, 9,  '(two-shot:0.95), antagonist blocks the exit, holding cyber-blade, (rim-light:0.95), dramatic pencil', 'https://storage.inksync.local/panels/p1_9.png'),
(1, 2, 10, '(close-up:1.0), weapon clashing, sparks flying, abstract background lines, fast-paced layout', 'https://storage.inksync.local/panels/p1_10.png'),
(1, 2, 11, '(low-angle shot:1.0), protagonist slips past under opponent, cinematic movement blur, storyboard lora', 'https://storage.inksync.local/panels/p1_11.png'),
(1, 2, 12, '(wide shot:0.85), protagonist escapes onto a speeding mag-train, pulling away into the distance', 'https://storage.inksync.local/panels/p1_12.png');

-- ==========================================================
-- PROJECT 2: HAUNTED MANSION EXPLORATION (Horror/Noir Theme)
-- ==========================================================

-- Insert Project 2 for user_id: 2
INSERT INTO projects (user_id, name) 
VALUES (2, 'Haunted Mansion Exploration');

-- Insert 12 consecutive panels for Project 102
INSERT INTO storyboard_panels (project_id, user_id, shot_number, prompt, image_url) VALUES
(2, 2, 1,  '(extreme wide shot:1.0), gothic mansion gates, heavy fog swirling, (moonlight:0.8), sketchy lora style', 'https://storage.inksync.local/panels/p2_1.png'),
(2, 2, 2,  '(medium shot:0.95), flashlight beam cutting through dark dust, long hallway, creepy shadows, hand-drawn', 'https://storage.inksync.local/panels/p2_2.png'),
(2, 2, 3,  '(close-up:1.0), character face showing pure terror, wide eyes, (low-key lighting:0.9), sharp ink details', 'https://storage.inksync.local/panels/p2_3.png'),
(2, 2, 4,  '(dutch-angle:1.1), ancient grandfather clock casting a distorted shadow, (depth_midas:0.6), sketch lora', 'https://storage.inksync.local/panels/p2_4.png'),
(2, 2, 5,  '(over-the-shoulder:0.85), shadowy phantom figure appearing at the corridor end, eerie aura, pencil shading', 'https://storage.inksync.local/panels/p2_5.png'),
(2, 2, 6,  '(extreme close-up:1.0), rusty brass doorknob slowly turning by itself, high-contrast linework', 'https://storage.inksync.local/panels/p2_6.png'),
(2, 2, 7,  '(tracking shot:0.9), character backing away rapidly into a corner, controlnet canny lock, ink wash', 'https://storage.inksync.local/panels/p2_7.png'),
(2, 2, 8,  '(high-angle:0.8), rotten wooden floorboards collapsing into a dark abyss, dramatic charcoal style', 'https://storage.inksync.local/panels/p2_8.png'),
(2, 2, 9,  '(medium shot:1.0), swarm of startled bats bursting from an old portrait, dynamic movement, storyboard lora', 'https://storage.inksync.local/panels/p2_9.png'),
(2, 2, 10, '(two-shot:0.9), demonic ghost reflection appearing in a cracked vanity mirror, (rim-light:0.7), fine ink lines', 'https://storage.inksync.local/panels/p2_10.png'),
(2, 2, 11, '(low-angle shot:1.0), heavy crystal chandelier swinging violently overhead, falling dust, architectural layout', 'https://storage.inksync.local/panels/p2_11.png'),
(2, 2, 12, '(wide shot:1.1), character sprinting frantically out of the front double doors, mansion looming menacingly', 'https://storage.inksync.local/panels/p2_12.png');


-- ==========================================================
-- PROJECT 3: DESERT MECH SHOWDOWN (Sci-Fi/Action Theme)
-- ==========================================================

-- Insert Project 3 for user_id: 2
INSERT INTO projects (user_id, name) 
VALUES (2, 'Desert Mech Showdown');

-- Insert 12 consecutive panels for Project 103
INSERT INTO storyboard_panels (project_id, user_id, shot_number, prompt, image_url) VALUES
(3, 2, 1,  '(extreme wide shot:1.2), vast barren desert dunes, twin suns blazing, (lens-flare:0.7), sketchy lora style', 'https://storage.inksync.local/panels/p3_1.png'),
(3, 2, 2,  '(established shot:1.0), abandoned industrial outpost buried in sand, rusty textures, pencil sketch', 'https://storage.inksync.local/panels/p3_2.png'),
(3, 2, 3,  '(low-angle shot:1.1), colossal bipedal mech standing guard, heavy iron plating, sharp ink linework', 'https://storage.inksync.local/panels/p3_3.png'),
(3, 2, 4,  '(close-up:0.9), stressed pilot inside the cockpit, holographic displays reflecting on visor, cinematic lighting', 'https://storage.inksync.local/panels/p3_4.png'),
(3, 2, 5,  '(medium shot:1.0), massive sandstorm wall approaching on the horizon, wind distortion lines, storyboard lora', 'https://storage.inksync.local/panels/p3_5.png'),
(3, 2, 6,  '(dutch-angle:0.85), rival enemy mech crashing through a dune, kicking up sand clouds, dynamic action lines', 'https://storage.inksync.local/panels/p3_6.png'),
(3, 2, 7,  '(tracking shot:1.0), rocket salvos launching from shoulder pods, thick smoke trails, controlnet canny edge', 'https://storage.inksync.local/panels/p3_7.png'),
(3, 2, 8,  '(extreme close-up:1.1), mechanical gears grinding and snapping under stress, high-contrast monochrome ink', 'https://storage.inksync.local/panels/p3_8.png'),
(3, 2, 9,  '(over-the-shoulder:0.95), heavy laser beam slicing through defensive walls, bright sparks exploding', 'https://storage.inksync.local/panels/p3_9.png'),
(3, 2, 10, '(two-shot:1.0), both mechs locked in close quarters melee combat, tearing metal, (rim-light:0.95), dramatic pencil', 'https://storage.inksync.local/panels/p3_10.png'),
(3, 2, 11, '(high-angle:0.85), enemy mech losing balance, falling backward into deep sand, smoke pouring from engine grid', 'https://storage.inksync.local/panels/p3_11.png'),
(3, 2, 12, '(wide shot:1.05), victorious mech standing alone as the severe sandstorm clears, cinematic composition', 'https://storage.inksync.local/panels/p3_12.png');

SELECT setval('users_user_id_seq',          (SELECT MAX(user_id)    FROM users)); --because i inserted users manually, and postgresql doesnt know that it, it tries to add new user with user_id: 1
SELECT setval('projects_project_id_seq',     (SELECT MAX(project_id) FROM projects));
SELECT setval('storyboard_panels_panel_id_seq', (SELECT MAX(panel_id) FROM storyboard_panels));