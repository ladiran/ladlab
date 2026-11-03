<?php
declare(strict_types=1);

/** Phase 8 seed: the "Copyright and takedown policy" page (a starting template: edit it in Admin → Legal pages and have it reviewed). */
return function (PDO $pdo): void {
    if ($pdo->query("SELECT 1 FROM pages WHERE slug = 'copyright'")->fetchColumn()) {
        return;
    }
    $email = '{{contact_email}}'; // replaced with your contact email (Admin → Contact & social) when the page is shown
    $body = <<<MD
We respect the work of authors, publishers, photographers, teachers and software developers, and we expect everyone who uses this site to do the same.

## What we publish

* **Our own material**: notes, lessons, articles and downloads written by us, which we have the right to share.
* **Openly licensed or public-domain material**, shown with its licence (for example Creative Commons) and its author. Where a licence requires credit, we give it.
* **Links to other websites** (the Free Tools page). We link only to the owner's own page or to legitimate sources: open-access, openly licensed or freely given resources. We do not host those files and do not control those sites.

We do **not** knowingly publish or link to pirated books, cracked software or other unauthorised copies. Please do not send us any.

## If you believe something here infringes your copyright

Tell us at **{$email}** (or use the "Report a problem" link beside any free resource). Please include:

1. **What the work is** (title, author, and where it is lawfully available, if you can say).
2. **Where it appears on our site**: the web address of the page, resource or link.
3. **Who you are**: your name, your role (owner or authorised agent) and how to reach you.
4. **A statement** that you believe in good faith that the use is not authorised by the copyright owner, its agent or the law.
5. **A statement** that the information you give is accurate and that you are the owner or authorised to act for the owner.

## What we do next

* We acknowledge your message, normally within **2 working days**.
* If the claim looks valid we **remove or disable access promptly**, and we tell the person who added the item.
* If the person who added it believes it was removed by mistake, they may send us a short explanation and the proof of their right to share it. We will review it fairly and may restore the item, or ask you to confirm your claim.
* We may **end the access of anyone who repeatedly shares material they have no right to share.**
* We keep a record of every notice and what we did about it.

## If you want to share material with us

Only offer material that is **yours**, **openly licensed**, **in the public domain**, or that you have **written permission** to share. Every free resource on this site shows its licence and author so that readers know what they may do with it. Suggestions for new links are checked before they are listed.

## Using what you find here

Free to read does not always mean free to copy. Check the licence shown on each resource and follow it.

*This policy is provided in good faith to explain how we work. It is not legal advice.*
MD;
    $pdo->prepare("INSERT INTO pages (slug, title, body) VALUES ('copyright', 'Copyright and takedown policy', ?)")->execute([$body]);
};
