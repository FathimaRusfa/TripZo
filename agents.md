You are working on my TripZo project inside the IDE.

First, read the entire codebase and follow any project instructions from AGENTS.md and any IDE/project rules before making changes.

Project context:
- Project name: TripZo
- Stack: PHP, MySQL/MariaDB, HTML, CSS, Bootstrap 5, minimal JavaScript
- Environment: XAMPP on Windows
- Goal: turn this academic tourist guide website into a highly polished, realistic, professional web application while staying aligned with the approved proposal and SRS
- Constraints:
  - keep the project compatible with XAMPP
  - do not introduce unnecessary frameworks
  - preserve existing working functionality unless a change is clearly justified
  - always make realistic improvements, not overengineered ones
  - when updating code, apply changes directly to files and save them
  - return full-file updates, not partial snippets
  - prefer Bootstrap-first solutions with clean custom CSS
  - prefer prepared statements for new or refactored database work
  - keep public UI and admin UI visually consistent

Your responsibilities:
1. Audit the current project structure, pages, database usage, styling, and UX.
2. Identify all weak points preventing the site from looking and behaving professionally.
3. If any libraries, assets, or dependencies are needed, connect and configure them properly.
   - If something requires installation, tell me exactly what must be installed and why.
   - If a CDN version is sufficient, use that instead of adding heavy local dependencies.
   - If an external service or API key is required, do not hardcode secrets; leave clear placeholders and explain where they go.
4. Implement the improvements directly in the codebase.
5. Keep all changes compatible with the current file structure unless a change is clearly beneficial.
6. After edits, run checks:
   - PHP syntax validation on changed PHP files
   - quick review for broken paths, broken includes, and inconsistent styling
7. At the end, provide:
   - a summary of what changed
   - the list of files changed
   - any manual steps I must do next
   - any dependencies or libraries added
   - any hosting or deployment implications

Priority implementation order:
A. Public-facing polish
- Improve homepage to feel like a professional tourism landing page
- Improve places page with search, category filtering, pagination, better cards, empty states
- Improve place details page with better layout, metadata, image handling, review section polish
- Improve planner page UX and visual structure
- Improve map page to look professional and function reliably
- Add consistent typography, spacing, button hierarchy, and section layout

B. Admin panel polish
- Make dashboard feel like a real control panel
- Improve manage attractions page
- Improve add/edit forms
- Improve review moderation UI
- Ensure consistent styling across all admin pages

C. Code quality and realism
- Replace insecure SQL usage with safer patterns where practical
- Improve validation and error messaging
- Prevent duplicate submissions where needed
- Remove dead code, temporary files, and weak UX points

D. Delivery readiness
- Make the project GitHub-ready
- Make the project hosting-ready for PHP/MySQL shared hosting
- Prepare a clean README if missing

Important working style:
- Do not stop after only suggesting changes
- Actually apply the changes to the codebase
- If there are multiple possible approaches, choose the one that is most practical for a student PHP/MySQL project
- If you need to split work into phases, start with the highest-value visible improvements first
- If any task depends on missing information, ask only the minimum necessary question

Start now by:
1. reading the whole project
2. identifying the most impactful improvements
3. applying the first professional polish pass across the public pages
4. reporting exactly what you changed