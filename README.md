# Thesis Monitoring System (TMS)

A comprehensive web application for managing the complete lifecycle of thesis projects, from topic selection to final defence.

## Features
- **Role-Based Access**: Specialized dashboards for Students, Supervisors, and Coordinators.
- **Milestone Management**: Track progress with versioned submissions and feedback loops.
- **Messaging System**: Built-in communication channel for each thesis project.
- **Audit Logging**: Tracks all critical system actions for accountability.
- **Notifications**: Real-time alerts for deadlines and activity.

## Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- PostgreSQL (or SQLite for local dev)

### Installation
1.  **Clone the repository**:
    ```bash
    git clone <repository-url>
    cd thesis-monitoring-system
    ```
2.  **Install PHP dependencies**:
    ```bash
    composer install
    ```
3.  **Install Node dependencies**:
    ```bash
    npm install
    ```
4.  **Environment Setup**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Configure your database credentials in `.env` if not using SQLite.*

5.  **Database Migration & Seeding**:
    This command resets the database and populates it with demo data.
    ```bash
    php artisan migrate:fresh --seed
    ```

6.  **Run Application**:
    Start the Laravel server and Vite development server.
    ```bash
    php artisan serve
    npm run dev
    ```

## Demo Credentials
Use these accounts to explore the system:

| Role | Email | Password |
|---|---|---|
| **Student** | `student@example.com` | `password` |
| **Supervisor** | `supervisor@example.com` | `password` |
| **Coordinator** | `coordinator@example.com` | `password` |

## User Guide

### Student
- **Dashboard**: View your current thesis status and upcoming milestones.
- **Submit Work**: Navigate to a milestone, upload files (or provide links), and add descriptions.
- **Messages**: Use the chat interface to discuss progress with your supervisor.

### Supervisor
- **Dashboard**: View all assigned students and their current status.
- **Review**: Open a student's submission, provide feedback (remarks), and choose a decision (Approve, Request Changes, Reject).

### Coordinator (Admin)
- **Assign Supervisors**: Link supervisors to student projects.
- **Audit Logs**: Access `/admin/audit-logs` to view a detailed history of system activities.

## Development
- **Testing**: Run the automated test suite.
    ```bash
    php artisan test
    ```
