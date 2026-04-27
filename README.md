# CRM Project

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)

<img width="2559" height="1439" alt="image" src="https://github.com/user-attachments/assets/e0084363-7563-4714-bf5c-14c12a88238b" />

[Watch Demo](https://www.youtube.com/watch?v=w3XfOwjRJBs)

## Overview

This project is a **Customer Relationship Management (CRM) system** designed for small to medium businesses, integrating local AI capabilities, Gmail communication, and secure external access via Cloudflare.  

It is hosted locally on a machine with **Intel i7, 4th generation, GT620M GPU, and 6GB RAM**, using [Ollama Qwen2.5](https://ollama.com/) for AI-assisted email classification and ticket management.

**Core goals:**
- Centralize client data and communication.
- Automate email classification and responses with AI.
- Track support tickets and client interactions.
- Provide secure role-based access for admins, agents, and clients.

---

## Key Features

### CRM & Client Management
- **Client Profiles:** Add, edit, and manage detailed client information.
- **Contact History:** Maintain a timeline of client interactions, emails, and tickets.
- **Search & Filters:** Quickly locate clients or tickets by name, status, or category.

### Ticket & Support Management
- **Ticket Lifecycle:** Claim, reply, solve, and close support tickets.
- **Ticket Assignment:** Role-based assignment to agents or teams.
- **Priority & Status:** Set priority levels and track ticket progress.
- **AI Assistance:** AI-generated suggested replies and ticket categorization.

### Email Integration
- **Gmail API Integration:** OAuth2 authentication for sending and receiving emails.
- **Email Sync:** Automatically fetch and attach emails to client profiles.
- **AI Classification:** Emails are categorized and flagged for priority using Ollama Qwen2.5.

### Role-Based Access Control (RBAC)
- **Admin:** Full system control including users, roles, and configurations.
- **Agent:** Access to assigned clients and tickets.
- **Client:** Limited access to personal profile and ticket status.
- **Secure Login & Authentication:** PHP sessions with hashed passwords.

### AI & Automation
- **Local AI Server:** Ollama Qwen2.5 runs locally for privacy and fast inference.
- **Email Categorization:** AI detects email type and urgency.
- **Suggested Responses:** AI drafts ticket replies for faster response times.

### Security & Hosting
- **Cloudflare Tunnel:** Secure HTTPS access for remote connectivity.
- **Session Management:** Prevent unauthorized access.
- **Database Protection:** Parameterized queries and secure connections.

---

## Installation

### Requirements
- PHP 7.4+
- MySQL / MariaDB
- Composer (dependency management)
- Ollama Qwen2.5 installed locally
- Cloudflare tunnel configured for secure access

### Steps

1. **Clone the Repository**
```bash
git clone https://github.com/Maniseniler/CRMProject.git
cd your-repo
```
2. **Install PHP Dependencies**
```bash
composer install
```

3. **Configure Database**
- Edit /config/db.php with your MySQL credentials.
- Import the provided SQL schema into your database.

4. **Set up Gmail API**

- Create a project in Google Cloud Console.
  
- Enable Gmail API and generate OAuth2 credentials.
  
- Configure /gmail/auth.php and /gmail/callback.php with client ID and secret.

5. **Start Local AI Server**
   
- ollama serve qwen2.5
