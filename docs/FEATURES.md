# What the system does

## Shipment file — 7 stages
1. **Request** — sales fills the RFQ form (or AI fills it from a WhatsApp chat / voice note). Same validation on screen and server.
2. **Pricing** — pricing sends RFQs to vendors (vendor email never contains the client's name or contact), records quotes (or AI reads rate emails), ranks them, builds the cost sheet, sends to pricing review.
3. **Quotation** — sales sends the quotation PDF; up to 3 negotiation rounds; price under the minimum margin needs management approval; client approval; payment terms decided by accounts/management.
4. **Booking** — supplier ready, booking, documents to broker, proforma; BL, ATD, ETA and carrier required; AI document check — "blocker" findings must be fixed or dismissed with a reason.
5. **Shipping & customs** — arrival notice, D/O, ACID/Nafeza, customs release.
6. **Delivery** — delivery, actual costs handed to accounts, final invoice.
7. **Closing** — collection, suppliers paid, after-sale call, feedback.

Every stage has ★ required checks; moving forward is blocked until they are done (management too). Moving back, marking lost (with reason) and reopening are logged. Each check has a default owner department.

## Departments
- **Sales:** own clients only (duplicate check on phone), activities and follow-ups, leads, quotations, performance vs target.
- **Pricing / pricing review:** request list, vendor quotes, cost sheets, review queue, reprice requests. No client contact details.
- **Operations / customs:** operations board, booking & shipping data, documents, stage moves.
- **Accounts:** invoices (VAT, Arabic/English PDF, amount in words), payments, collections, overdue alerts, payment-term decisions.
- **Management:** everything above, users & roles, settings, AI usage, outbox, approvals; dashboards and weekly/monthly reports.

## Alerts (automatic)
New lead not answered in time · client follow-ups due · overdue invoices · vendor payments due · files waiting too long in a stage · monthly performance check (15th) · weekly summary (Sunday) · monthly report (1st). Working days Sunday–Thursday, Cairo time.

## Languages
Arabic (RTL) and English per user; PDFs and emails in the recipient's language.
