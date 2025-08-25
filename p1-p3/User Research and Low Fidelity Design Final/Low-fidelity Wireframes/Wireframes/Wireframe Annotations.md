Browse Skills.png

Design Choices: Grid layout of student skill cards for quick scanning. Filters placed on left sidebar (keywords, skill level, availability) for easy refinement. Search bar on top for direct queries.

Functionality: Clicking a skill card opens the provider’s profile. Sorting toggle (“New” vs “Rating”) allows prioritising results.

Content Placeholders: Grey boxes = profile images; text labels = skill names; cards = dynamic database output.

Backend Interaction: Queries MySQL database for skills based on filters; PHP backend handles search requests, paginates results, and fetches profile links.

-------------------------------------

Landing:
Login Page.png

Design Choices: Hero section with headline slogan + CTA buttons (“Register Now”, “Login Here”). Key benefits listed as bullet points. Testimonial at the bottom builds trust.

Functionality: Register → registration form workflow. Login → session-based authentication.

Content Placeholders: Hero image box = stock/illustrative graphic. Testimonial area = rotating student feedback (pulled from reviews).

Backend Interaction: Registration links to PHP user creation + validation (Flinders email). Login connects to session management, hashed passwords, and redirects to dashboard.

-------------------------------------

Profile.png

Design Choices: Large profile image left, details right (name, study area, year level, keywords). Availability calendar displayed clearly at bottom.

Functionality: Students can edit own profile; others can view details before requesting service.

Content Placeholders: Profile image, name, bio, and tags are database-driven placeholders. Availability = coloured blocks rendered from stored schedule.

Backend Interaction: Pulls from user profile + skills tables. Availability saved in separate schedule table; updating triggers backend CRUD operations.

-------------------------------------

Register Form.png

Design Choices: Simple form fields (Name, Surname, Email, Message). Minimal distractions for easy onboarding.

Functionality: Validates Flinders email pattern (@flinders.edu.au). Submits details to database and triggers confirmation.

Content Placeholders: Labels (“Name”, “Surname”, etc.) as form inputs; no styling yet beyond placeholders.

Backend Interaction: PHP validates and hashes password (not shown in this low-fi). MySQL creates new user record. Session begins upon successful validation.

-------------------------------------

Request Form.png

Design Choices: Compact form beside the student’s profile page. Includes calendar picker for scheduling, text input for help description, and message field.

Functionality: Submitting sends a request to provider’s Requests page. System checks FUSSCredit balance before submission.

Content Placeholders: Date picker, text fields, and submit button. Availability calendar beneath shows provider’s schedule.

Backend Interaction: PHP inserts new service request into DB. Validates credit balance and prevents negatives. Sends notification to provider.

-------------------------------------

Requests.png

Design Choices: Split layout — left shows incoming/outgoing requests (list view), right shows monthly calendar for scheduling clarity.

Functionality: Buttons for “Accept” / “Decline” update request status. Calendar prevents overlaps/double-bookings.

Content Placeholders: Request cards are placeholders for real-time data (student name, time, request message). Calendar blocks = confirmed sessions.

Backend Interaction: PHP updates request table (status change). Credit check occurs if accepted. Notifications sent to requester.

-------------------------------------

Review Prompt.png

Design Choices: Modal overlay on dashboard. Star-rating widget + text box for review. Keeps UX simple and immediate after task completion.

Functionality: Star input and text review submitted once service is confirmed.

Content Placeholders: Stars are placeholders for interactive rating widget. Text box placeholder for review.

Backend Interaction: PHP validates review (no empty fields, prevents self-review). MySQL stores review + recalculates provider’s average rating dynamically.

-------------------------------------

Student Dashboard.png

Design Choices: Centralised hub. Left panel = notifications (requests). Right panel = credits, requests sent, pending requests + pie chart summary. Bottom = availability calendar.

Functionality: Clicking a notification opens request details. Calendar visualises commitments. Pie chart summarises current balance + request distribution.

Content Placeholders: Notifications as grey cards. Pie chart placeholder. Calendar blocks = active sessions.

Backend Interaction: Pulls live data from DB (credits, requests, availability). PHP generates chart data dynamically.
