# DownOrUp 🌍

A simple real-time website status checker that tells if a website is UP or DOWN, with global trending outage tracking.

## 🚀 Features

- Website uptime checker
- Response time measurement
- Global history tracking (MySQL)
- Trending down websites
- AJAX-based checking (no reload)
- Last 24-hour activity log

## 🧠 Tech Stack

- PHP (Backend)
- MySQL (Database)
- JavaScript (AJAX)
- HTML/CSS

## 📊 How Trending Works

Trending is based on how many times a website is reported as DOWN within the last hour.

## ⚙️ Setup

1. Import database table:

```sql
CREATE TABLE checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(255),
    status VARCHAR(10),
    http_code INT,
    response_time INT,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```
2. Configure database in db.php
3. Run on local server or hosting

Built by Lorence (Renz)
