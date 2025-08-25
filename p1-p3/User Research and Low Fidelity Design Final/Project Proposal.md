# Project Proposal – Flinders Uni Skill Share (FUSS)

## Website Concept

Flinders Uni Skill Share (FUSS) is a web application for Flinders University students and administrators to exchange skills, services, and time without spending money. Students earn FUSSCredits for providing services—such as tutoring, essay proofreading, coding assistance, moving help, or language practice—which can be spent to receive services from other students.

FUSS addresses the fragmented and informal skill-sharing methods on campus including social media and word-of-mouth by providing a structured, secure, and incentivised platform. It tracks service hours, ensures accountability through ratings and reviews, and builds a reciprocal peer-support network that encourages skill development, collaboration, and meaningful skill exchange.

The platform’s design has been informed by surveys and interviews across different faculties, identifying student pain points and motivations, as well as competitor analysis of platforms like Facebook Marketplace, Gumtree, Fiverr, and uni peer-support services. This ensures features are relevant, usable, and tailored to student needs, such as helping a student leverage one skill to access another, while maintaining trust and fairness.

## Target Audience Profile

### Students
- Undergraduate and postgraduate, aged 18–30.
- Tech-comfortable, frequently use laptops and smartphones.
- Motivated by saving money, building networks, and sharing expertise.
- Need flexible ways to find and exchange academic and practical support.

Scenario:  
A second-year IT student tutors someone in coding, earns FUSSCredits, and spends them on essay proofreading by an Arts student.

### Administrators
- Staff managing FUSS operations, aged 30–40.
- Ensure system integrity, monitor transactions, moderate disputes, and manage user accounts.
- High technical proficiency, primarily desktop users with occasional mobile access.

Scenario:  
An admin identifies suspicious FUSSCredit transfers, investigates via the dashboard, freezes fraudulent transactions, and adjusts balances as needed. Administrators also benefit from analytics tools to track popular skills, usage patterns, and dispute trends.

## Scope Statement

### Student Accounts & Profile Management
- Registration/login with secure Flinders email validation and password hashing
- Personal profile: Name, degree, college, academic year, bio, profile picture (editable by students and admins as needed)
- Skills offered/requested with descriptions and topics
- FUSSCredit balance and transaction history
- Calendar-based availability: interactive selection of specific time slots and conflict detection

### Skill Exchange
- Search and browse students or skills with filters for category, topic, academic year, and availability
- Request a service specifying type, date/time, estimated hours, and message
- Confirm service completion to trigger FUSSCredit transfers and prevent negative balances
- Recommendation engine: suggest skills or students based on past requests and services
- Proximity matching: group users by campus zones for in-person services
- Weighted search and filtering: rank results by skill match, provider reputation, availability alignment, and recency

### Peer Reviews & Trust
- Ratings (1–5 stars) and optional feedback
- Trust score incorporating completion rate, disputes, response time, and reciprocity
- Basic dispute resolution workflow with admin oversight

### Messaging & Scheduling
- Internal inbox/outbox for messages and service coordination
- Availability management with general settings
- In-app notifications for new requests, confirmations, messages, or reviews

### Administrator Features
- Dashboard: monitor users, services, FUSSCredit distribution, and popular skills
- Manage users: view, edit, suspend, delete accounts
- Adjust FUSSCredits for seeding, rewards, or dispute resolution
- Moderate content to remove inappropriate material

### Backend Functions
- Database for storing profiles, skills, requests, messages, transactions, reviews, trust scores, notifications, and dispute history
- Secure authentication and session management
- Logging and audit trail for admin actions to ensure accountability and transparency
