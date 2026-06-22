# Gold Hoarder — Application Specification

## Overview

Gold Hoarder is a small PHP web widget that lets a user track their gold balance over time. It runs as a standalone app called from a shell application, displaying a ledger of gold snapshots — one entry per day — with full CRUD capabilities.

## Environment

- **Production:** LAMP stack (Linux, Apache, MySQL, PHP)
- **Local development:** Docker Desktop container replicating the LAMP environment

## Architecture

- **Backend:** PHP (no framework)
- **Database:** MySQL
- **Frontend:** Server-rendered HTML with minimal JavaScript for interactivity (sorting, pagination, filtering)
- **Integration:** Called from an external shell application which handles authentication. The contract between shell and widget will be defined separately.

## Data Model

### Table: `gold_entries`

| Column     | Type         | Constraints                          |
|------------|--------------|--------------------------------------|
| id         | INT          | Primary key, auto-increment          |
| user_id    | INT          | Not null, part of unique constraint  |
| entry_date | DATE         | Not null, part of unique constraint  |
| amount     | INT          | Not null (negative values allowed)   |
| comment    | TEXT         | Nullable                             |
| created_at | DATETIME     | Not null, default CURRENT_TIMESTAMP  |
| updated_at | DATETIME     | Not null, auto-updated on modify     |

- **Unique constraint:** (`user_id`, `entry_date`) — one entry per user per day.
- **Gold amount:** Represents the total gold balance at that point in time, not a delta. Negative values are permitted but expected to be rare.

## Features

### Main View — Entry List

- Displays all entries for the current user in a table.
- **Columns:** Date, Gold Amount, Comment.
- **Default sort:** Date descending (newest first).
- **Sortable** by any column.
- **Paginated** with configurable page size.
- **Filterable:**
  - By date range (from/to).
  - By amount range (min/max).
  - By comment text (substring search).

### Add Entry

- User provides: date, gold amount, comment (optional).
- Validation:
  - Date is required and must be a valid date.
  - Date must be unique for the user (no duplicate day).
  - Gold amount is required and must be an integer.
- On success: entry is added and the user returns to the list.

### Edit Entry

- User can modify: date, gold amount, comment.
- Same validation as Add.
- Editing the date must still respect the one-entry-per-day constraint.

### Delete Entry

- User can delete any entry.
- Deletion requires confirmation.
- No recalculation or adjustment of surrounding entries.

## Shell Integration Contract

### Session

- Session name: `BETTERMEE_SESSID`
- Cookie path: `/` (shared across all paths on the domain)
- Session variables set by the shell after authentication:
  - `$_SESSION['user_id']` — integer, identifies the user
  - `$_SESSION['user_email']` — string
  - `$_SESSION['user_role']` — `'admin'` or `'user'`

### Authentication

- The shell handles login entirely.
- The widget checks `$_SESSION['user_id']` to confirm the user is authenticated.
- If absent: the user is not logged in. The widget should deny access (no login form of its own).

### Database

- Shared MySQL database.
- Connection via environment variables: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- The widget may create its own tables but must not modify shell tables (`users`, `widgets`).

### Deployment

- The widget lives under its own path on the same domain (e.g., `/goldhoarder/`).
- This path is registered in the shell's widget registry.

### Isolation

- The widget must not depend on the shell's PHP code.
- It is a standalone application that shares the domain, session, and database with the shell.

## Authentication & Authorization

- Authentication is handled by the shell (see integration contract above).
- The widget reads `$_SESSION['user_id']` to identify the current user.
- The widget enforces that a user can only see and modify their own entries.
- The `user_id` column in `gold_entries` maps to the shell's user ID.

## Non-Functional Requirements

- No external PHP frameworks or heavy dependencies.
- Minimal JavaScript; no build toolchain required.
- The app must work in a single Docker container for local development.
- Responsive enough for desktop use; mobile optimization is not required.
