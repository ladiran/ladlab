-- Phase 9d: remember whether the email notification of an enquiry was delivered.

ALTER TABLE enquiries ADD COLUMN mail_status VARCHAR(8) NOT NULL DEFAULT '';
