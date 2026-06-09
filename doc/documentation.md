# InkSync — Documentation

\---

## 1\. Project Overview



InkSync is a web-based AI storyboard generator developed as a student project at FH Salzburg. Users upload a reference image and write a scene description. the tool produces a storyboard panel in a sketchy/storyboard style. The application is built as a PHP/PostgreSQL CRUD web application and runs locally via XAMPP or on the university server. It is a functional prototype — not a production product.



\---

## 2\. Features

* Generate storyboard panels from a text prompt and a reference image
* ControlNet conditioning (edge canny + lineart) to preserve composition from the reference
* Custom LoRA fine-tuning for a consistent sketch/ink art style
* User registration, login, and session management
* Per-user daily generation limit (40 panels/day)
* Admin panel showing per-user generation counts
* Save and revisit projects via a personal projects page
* Export the storyboard grid as a PNG using html2canvas

\---

## 3\. Tech Stack

|***Layer***|***Technology***|
|-|-|
|Backend|PHP 8, PDO, PostgreSQL|
|Frontend|Vanilla JS, CSS (no framework)|
|Image generation|Replicate API — `sdxl-based/realvisxl-v3-multi-controlnet-lora`|
|Base model|Stability AI SDXL with RealVisXL v3 weights|
|Conditioning|ControlNet (edge canny + lineart)|
|Style|Custom LoRA weights|
|Export|html2canvas v1.4.1|
|Server (local)|XAMPP (Apache + PHP)|

\---

## 4\. Setup \& Installation

**Requirements:** XAMPP (or any Apache + PHP 8 stack), PostgreSQL, a Replicate API key.

1. Clone or copy the project into your XAMPP `htdocs` folder as `/inksync`.
2. Create a PostgreSQL database and import `inksync.sql` to set up the schema.
3. Open `backend/config.php` and fill in your database credentials and Replicate API key.
4. Make sure the `backend/.htaccess` file is in place — it blocks direct browser access to the backend folder.
5. Navigate to `http://localhost/inksync` to start the app.

For the university server (`users.ct.fh-salzburg.ac.at`), the config already contains the correct credentials and switches automatically based on `HTTP\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\_HOST`.

\---

## 5\. Usage

1. Register an account or log in.
2. On the main page, write a scene description in English in the **Scene Description** field. Describe the mood, camera angle, lighting, and action — avoid specifying shot type or style (those are handled by the model).
3. Upload a **reference image** — a sketch, photo, or 3D render works. The image is automatically cropped and resized to 4:3.
4. Click **Generate Storyboard Panel** and wait (\~30–90 seconds depending on server load).
5. The generated panel appears on the right. Repeat for additional panels to build a storyboard grid.
6. Use the **Export** button to download the full storyboard grid as a PNG.
7. Panels can be saved to a named project and revisited from the **Projects** page.

\---

## 6\. Project Structure

```



inksync/

│   .gitignore

│   .htaccess

│   index.php					- main app page + generation logic

│   inksync.sql					- database schema

│   inksync\\\\\\\\\\\\\\\_db.session.sql

│   warmup.php					- pings Replicate to reduce cold-start time

│

├───.vscode

│       settings.json

│

├───backend

│   │   .htaccess

│   │   api.php					- HTTP helper functions (post\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\_json, get\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\_json)

│   │   api\\\\\\\\\\\\\\\_new.php

│   │   bootstrap.php				- session start, DB connection, BASE\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\_URL

│   │   check\\\\\\\\\\\\\\\_login.php

│   │   config.php				- DB credentials + Replicate API key (not committed)

│   │   prompt.php				- Builds Replicate request params (ControlNet, LoRA)

│   │   save\\\\\\\\\\\\\\\_panels.php			- saves panel data to DB

│   │

│   └───security

│           10k\\\\\\\\\\\\\\\_common\\\\\\\\\\\\\\\_passwords.txt

│

├───doc

│       documentation.md

│

├───frontend

│       styles.css

│

├───pages

│       admin\\\\\\\\\\\\\\\_panel.php

│       login.php

│       logout.php

│       panel\\\\\\\\\\\\\\\_partial.php

│       projects.php

│       register.php

│

├───scripts						- client-side export library

\\\\\\\&#x09;(html2canvas)


```

\---

## 7\. Known Limitations

* **Blocking PHP thread:** Generation polls Replicate with `sleep(3)` in a loop, holding the PHP-FPM worker for the duration. This is acceptable for now but would not work for many users



* **Cold starts:** The Replicate model can take 30+ seconds on first use. `warmup.php` is called on page load to pre-warm the model, but this is not guaranteed.



* **Input sensitivity:** Faint sketches, transparent PNGs, NSFW images, or extreme aspect ratios will produce poor results. Prompts should be in English; abstract or non-visual concepts generate inconsistently.



* **No real-time feedback:** There is no progress bar during generation.



* **Generation limit:** Each user has a limited amount of generations per day.

\---



## 8\. Credits \& Licenses

|Resource|Author / Provider|License|
|-|-|-|
|[html2canvas](https://html2canvas.hertzen.com) v1.4.1|Niklas von Hertzen|MIT|
|base64-arraybuffer, css-line-break, text-segmentation, utrie|Niklas von Hertzen|MIT|
|[Replicate](https://replicate.com) API|Replicate Inc.|—|
|[RealVisXL v3 Multi-ControlNet LoRA](https://replicate.com/sdxl-based/realvisxl-v3-multi-controlnet-lora)|sdxl-based (Replicate)|—|
|SDXL base model|Stability AI|CreativeML Open RAIL++-M|
|LoRA weights|FH Salzburg / InkSync team|—|
|Fonts (Georgia, Courier New)|System fonts|—|

This is a non-commercial student project. All third-party libraries are used in accordance with their respective licenses.

