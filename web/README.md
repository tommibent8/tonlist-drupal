<img alt="Drupal Logo" src="https://www.drupal.org/files/Wordmark_blue_RGB.png" height="60px">

# Drupal Course Project – Open Web Software Development

This repository contains our course project for **Þróun opins vefhugbúnaðar**.
The project is built using **Drupal** (open source CMS) and demonstrates both
content modeling and custom module development.

The project includes:
- A full Drupal site with structured content
- Custom content types, views, roles and permissions
- A custom Drupal module implementing a search form
- Version control using Git
- Local development using DDEV

---

## User Guide (Notendahandbók)

### Logging in
- Log in as administrator to access all features.
- The administrator user is used to manage content, views, and modules.

### Content Management
- Navigate to **Content → Add content**
- Available content types include:
  - Artists
  - Albums
- Content can be edited, deleted, and viewed using standard Drupal tools.

### Views
- Preconfigured views are used to list content.
- Views demonstrate:
  - Filtering
  - Sorting
  - Display modes

### Music Search
- Navigate to `/music-search`
- Enter a search term (artist or album)
- Click **Search**
- Results are displayed on the same page
- The search currently returns mock data, but the architecture supports future API integration

### Media
- Media entities are used for images
- Uploaded files are stored in `/web/sites/default/files`

---

## Project Overview

### Assignment 1
- Set up a Drupal site locally using **DDEV**
- Model content using:
  - Custom content types
  - Fields
  - Media
  - Views
- Configure:
  - Menus
  - Blocks
  - Roles and permissions
- Use Git correctly with `.gitignore`
- Export database and files for submission

### Assignment 2
- Create a **custom Drupal module**
- Implement:
  - Routing
  - Drupal Form API
  - A custom service
  - Dependency Injection
- Prepare the architecture for future integration with external APIs
  (e.g. Spotify / Discogs)

---

## Technology Stack

- **Drupal** (core)
- **PHP**
- **Composer**
- **Drush**
- **DDEV** (local development environment)
- **Git**

---

## Local Setup Instructions

This project is intended to be run locally using DDEV.
The repository includes all necessary configuration files to start the project without manual Drupal installation.

### Prerequisites
Make sure you have installed:
- Docker
- DDEV
- Git

## Limitations and Future Work

- The music search currently uses mock data
- External APIs (Spotify, Discogs) are not yet integrated
- The architecture is prepared for service-based API integration

### Setup steps

```bash
# Clone the repository
git clone <your-repository-url>
cd <project-folder>

# Start the local environment
ddev start

# Import database
ddev import-db --file=pgsql

# Launch site
ddev launch

