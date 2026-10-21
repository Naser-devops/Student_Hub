
<div align="center">
🎓 StudentHub
Learn. Explore. Grow.
A modern student learning platform built with PHP & MySQL and deployed on AWS with a private database architecture.

<p> <img src="https://img.shields.io/badge/PHP-Backend-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP" /> <img src="https://img.shields.io/badge/Docker-Containerized-2496ED?style=flat-square&logo=docker&logoColor=white" alt="Docker" /> <img src="https://img.shields.io/badge/AWS-Cloud-FF9900?style=flat-square&logo=amazonwebservices&logoColor=white" alt="AWS" /> <img src="https://img.shields.io/badge/RDS-MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="Amazon RDS MySQL" /> </p>
🌐 Live Website · 🏗️ Architecture · 📸 Screenshots

</div>
✨ About the Project
StudentHub is a responsive learning platform where students can discover courses, manage their learning, and track enrollments. An admin dashboard provides tools for managing course content.

Beyond the application itself, this project demonstrates a two-tier AWS deployment: a Dockerized PHP web application on Amazon EC2 connected to Amazon RDS MySQL within private subnets.

📸 Project Preview
<div align="center"> <img src="assets/images/01-landing.png" alt="StudentHub landing page" width="100%" /> <sub>Landing page</sub> </div>
<table> <tr> <td align="center" width="50%"><img src="assets/images/02-student-dashboard.png" alt="Student dashboard" width="100%" /><br /><b>Student Dashboard</b></td> <td align="center" width="50%"><img src="assets/images/03-course-catalog.png" alt="Course catalog" width="100%" /><br /><b>Course Catalog</b></td> </tr> <tr> <td align="center"><img src="assets/images/04-course-learning.png" alt="Course learning page" width="100%" /><br /><b>Learning Experience</b></td> <td align="center"><img src="assets/images/05-admin-panel.png" alt="Admin panel" width="100%" /><br /><b>Admin Panel</b></td> </tr> </table> <details> <summary><b>➕ View course management preview</b></summary> <br /> <img src="assets/images/06-add-course.png" alt="Add course screen" width="100%" /> </details>
🏗️ AWS Architecture


Request flow: Browser → DuckDNS → Elastic IP → Caddy (HTTPS) → EC2 / Docker / PHP → Amazon RDS MySQL

Amazon EC2: hosts the PHP/Apache web application in Docker.
Amazon VPC: separates the public web layer from private database subnets.
Amazon RDS MySQL: stores application data without a public database endpoint.
Security Groups: allow MySQL access on port 3306 from the web server's security group only.
AWS Systems Manager: provides server administration without opening inbound SSH.
Caddy: handles HTTPS and reverse-proxies requests to the local web container.
🛠️ Tech Stack
Application	Cloud & Deployment
PHP · MySQL · HTML · CSS · JavaScript	AWS EC2 · Amazon RDS · VPC
Apache · PDO	Docker Compose · Caddy · Systems Manager
Responsive UI	Git · GitHub · DuckDNS
🔐 Security Highlights
Database hosted in private subnets, with public access disabled.
MySQL access restricted by security-group-to-security-group rules.
Application uses a dedicated database user instead of the RDS administrator.
Credentials stored in an untracked .env file, never committed to Git.
RDS encryption at rest and automated backups enabled.
🚀 Deployment Notes
The live deployment uses Amazon RDS inside a private VPC. Running the current Docker Compose configuration requires access to that database and the corresponding local .env settings; it is not a one-command standalone local demo. The original Docker MySQL service is retained temporarily as a rollback option.

<div align="center">
Built and deployed by Naser Bader

StudentHub — a web application and hands-on AWS / DevOps portfolio project.

</div>
