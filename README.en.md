# PS Smart Business

[Deutsch](README.md) | **English**

[![Version](https://img.shields.io/badge/Version-1.1.5-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.5%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-up%20to%207.1.4-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

**Your customers. Your invoices. Your processes. Your system.**

PS Smart Business is a modular business suite for **WordPress and ClassicPress**.

CRM, customer management, tasks, quotes, invoices, and accounting are brought together in one central location -- directly within your own WordPress system.

No external SaaS platform. No monthly subscription. No data stored on a third-party platform.

---

## What is PS Smart Business?

PS Smart Business is evolving from a traditional CRM into a comprehensive business suite.

Manage your customers, organize tasks and appointments, prepare quotes, issue invoices, and document business activities.

The system remains modular:

> **You decide which features you need.**

Its extensible architecture allows additional features, integrations, and project-specific modules to be added.

---

## CRM and customer management

Keep track of your customers and ongoing activities.

- Customer management
- Dedicated **CRM agent role**
- Tasks and appointments
- Customer-specific notes and activities
- To-do and appointment statuses
- Email and dashboard notifications
- Notifications for individual users or WordPress roles
- Custom notification rules
- Multi-stage notifications for long-running activities
- CSV customer import

The management interfaces are designed for efficient workflows and provide filtering, sorting, and grouping features.

---

## Quotes and invoices

Create your business documents directly from the CRM.

### Quotes

- Multiple line items per quote
- Custom text and comments
- Touch-enabled signature
- PDF generation
- PDF storage
- Custom numbering

### Invoices

- Any number of invoice line items
- Automatic total calculations
- Configurable payment terms
- PDF generation
- Invoice registration
- Custom number range
- Payment statuses
- Due-date notifications

### Your documents, your design

Adapt generated PDFs to match your business:

- Your own logo
- Configurable header areas
- Custom alignment of header elements
- Configurable document information

---

## Accounting

PS Smart Business provides basic accounting functionality directly in the CRM.

Record business income and expenses, and prepare relevant data for further accounting.

The accounting module is not intended as a rigid all-in-one system.

Instead, extensions and integrations can connect it with other plugins.

---

## Notifications

Do not miss important activities.

PS Smart Business can trigger notifications according to your own rules.

For example:

- Tasks
- Appointments
- Due payments
- Expiring services
- Internal activities
- Specific users or roles

Multi-stage notification workflows are also supported.

---

## Extensible instead of restrictive

PS Smart Business is deliberately designed as a modular system.

This allows the CRM to be extended without rebuilding the entire core system.

Possible extensions include:

- Integrations
- API modules
- Frontend modules
- Project-specific extensions
- Custom business workflows

---

## WordPress or ClassicPress

PS Smart Business works with:

- **WordPress**
- **ClassicPress**
- **PHP 8.5 or later**

Run the CRM in your own installation and decide where your data is stored.

## Developing from source

E-invoice support uses Composer dependencies. After cloning, install the production dependencies:

```bash
composer install --no-dev --prefer-dist
```

Published plugin packages must include the generated `vendor/` directory.

E-invoices are not a separate document type: for B2B customers, the additional XML output is enabled by default in the regular invoice form and can be disabled for an individual invoice. The PDF and XML use the same stored invoice, line-item, and tax data.

No Java runtime is required to issue e-invoices on standard WordPress hosting. The generated XML file is validated within the plugin for consistency and against the UBL schema.
