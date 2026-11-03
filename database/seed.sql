-- Placeholder content so every page looks real while you test.
-- Rows marked is_sample = 1 disappear from the public site as soon as you add a real item of the same kind.

INSERT INTO publications (title, authors, venue, year, type, doi, tags, is_featured, is_sample) VALUES
('Sample publication: variability of West African monsoon rainfall', 'A. Author, B. Author', 'Journal name', 2026, 'journal', '', 'monsoon, rainfall', 1, 1),
('Sample publication: aerosol-cloud interactions over the Gulf of Guinea', 'A. Author, C. Author', 'Journal name', 2025, 'journal', '', 'aerosols, clouds', 1, 1),
('Sample conference paper: boundary-layer processes in the Sahel', 'A. Author', 'Conference name', 2024, 'conference', '', 'boundary layer', 1, 1);

INSERT INTO awards (title, org, year, description, is_sample, sort_order) VALUES
('Sample award: Early Career Researcher Award', 'Professional body', 2023, '', 1, 10),
('Sample award: Best Paper Prize', 'Conference or society', 2021, '', 1, 20);

INSERT INTO talks (title, event, location, talk_date, kind, is_sample, sort_order) VALUES
('Sample talk: Why the sky behaves the way it does', 'University lecture series', 'Nigeria', '2026-03-12', 'talk', 1, 10),
('Sample interview: Understanding seasonal forecasts', 'Radio or TV programme', 'Lagos', '2025-11-02', 'interview', 1, 20);

INSERT INTO projects (title, summary, status, is_sample, sort_order) VALUES
('Sample research area: West African monsoon dynamics', 'A short description of the question you study, the data and methods you use, and why it matters for people in the region.', 'ongoing', 1, 10),
('Sample research area: Aerosols, clouds and climate', 'Replace this with your own project description. Add links, images and collaborators from the Admin area.', 'ongoing', 1, 20),
('Sample research area: Weather and climate education', 'Developing clear teaching resources for university physics and atmospheric science students.', 'ongoing', 1, 30);

INSERT INTO testimonials (name, role, quote, is_sample, sort_order) VALUES
('Sample student', '300L Physics student', 'Sample testimonial: replace this with a real comment from a student or colleague (with their permission).', 1, 10);

INSERT INTO timeline (year, title, detail, is_sample, sort_order) VALUES
('20XX', 'Sample: BSc Physics', 'University name', 1, 10),
('20XX', 'Sample: MSc Atmospheric Science', 'University name', 1, 20),
('20XX', 'Sample: PhD', 'University name', 1, 30),
('20XX–now', 'Sample: Lecturer / Researcher', 'Institution name', 1, 40);

INSERT INTO posts (title, slug, excerpt, body, tags, status, published_at, is_sample) VALUES
('Sample post: Welcome to ClearSky Academy', 'welcome-to-clearsky-academy', 'This is a sample article. Replace it with your first real post from the Admin area.',
'This is a **sample post** so you can see how articles look.\n\n## What you can do\n\n- Write in plain text with simple formatting\n- Add images, links and headings\n- Schedule posts for later\n\nReplace this post from **Admin → Blog posts**.', 'welcome, physics', 'published', '2026-10-01 09:00:00', 1);

INSERT INTO pages (slug, title, body) VALUES
('privacy', 'Privacy Policy', 'This is a starting template. Please review it and adapt it to how you actually use data.\n\n## What we collect\n\nWhen you use the contact form we collect your name, email address, optional phone number and your message. When you subscribe to the newsletter we collect your email address (and name if you give it).\n\n## Why we collect it\n\nWe use contact details only to reply to you, and newsletter details only to send the newsletter you asked for. We do not sell your data.\n\n## Your rights\n\nUnder the Nigeria Data Protection Act you may ask to see, correct or delete your data. Email us and we will respond. You can unsubscribe from the newsletter at any time using the link in every email.\n\n## Cookies\n\nThis site uses only essential cookies (to keep forms safe and to keep the administrator logged in). It does not use advertising or tracking cookies.'),
('terms', 'Terms of Use', 'This is a starting template. Please review it and adapt it.\n\n## Use of this website\n\nThe content on this site is provided for general information and education. We try to keep it accurate, but we make no guarantee that it is complete or error-free.\n\n## Intellectual property\n\nUnless stated otherwise, the text, images and course material on this site belong to the site owner. You may share links, and quote short extracts with credit, but may not copy or resell content without permission.\n\n## External links\n\nWe link to other websites for your convenience and are not responsible for their content.\n\n## Changes\n\nWe may update these terms from time to time.');
