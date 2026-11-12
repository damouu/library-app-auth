# Library App Authentication Service

Distributed Library System — Authentication Service

---

## Overview

The Authentication Service is the security foundation of the distributed library platform.

It is responsible for user registration, user authentication, JWT token generation, identity verification, and account lifecycle management.

The service provides a centralized authentication mechanism used by all protected services within the platform and publishes identity-related events through Kafka for downstream consumers.

---

## Service Boundaries

### Provides

- User registration
- User authentication
- JWT token generation
- Identity verification
- User profile retrieval
- Account deletion
- Authentication event publication
- Access control enforcement

### Does Not Handle

- Book catalog metadata management
- Borrow transaction processing
- Inventory management
- Recommendation generation
- Notification delivery
- Analytical aggregation
- Search indexing

---

## Badges

<!-- Code Quality & Tests -->

[![Tests](https://github.com/damouu/library-app-auth/actions/workflows/run-tests.yml/badge.svg?branch=test)](https://github.com/damouu/library-app-auth/actions/workflows/run-tests.yml)
[![Merge PR](https://github.com/damouu/library-app-auth/actions/workflows/merge-pr.yml/badge.svg)](https://github.com/damouu/library-app-auth/actions/workflows/merge-pr.yml)
[![Prepare](https://github.com/damouu/library-app-auth/actions/workflows/prepare.yml/badge.svg)](https://github.com/damouu/library-app-auth/actions/workflows/prepare.yml)
[![YouTrack-Staging](https://github.com/damouu/library-app-auth/actions/workflows/youtrack-staging.yml/badge.svg)](https://github.com/damouu/library-app-auth/actions/workflows/youtrack-staging.yml)
[![YouTrack Closed](https://github.com/damouu/library-app-auth/actions/workflows/youtrack-done.yml/badge.svg)](https://github.com/damouu/library-app-auth/actions/workflows/youtrack-done.yml)

<!-- Coverage -->

[![Codecov](https://codecov.io/gh/damouu/library-app-auth/branch/test/graph/badge.svg)](https://codecov.io/gh/damouu/library-app-auth)

<!-- Docker -->

[![Docker Build](https://github.com/damouu/library-app-auth/actions/workflows/build-and-publish.yml/badge.svg)](https://github.com/damouu/library-app-auth/actions/workflows/build-and-publish.yml)
[![Docker Image](https://img.shields.io/docker/v/damou/library-app-auth?label=docker&logo=docker)](https://hub.docker.com/r/damou/library-app-auth)
[![Docker Pulls](https://img.shields.io/docker/pulls/damou/library-app-auth?logo=docker)](https://hub.docker.com/r/damou/library-app-auth)

<!-- Git / Version -->

[![Git Tag](https://img.shields.io/github/v/tag/damouu/library-app-auth?logo=github)](https://github.com/damouu/library-app-auth/tags)

<!-- Observability / Monitoring -->

![OpenTelemetry](https://img.shields.io/badge/OpenTelemetry-instrumented-brightgreen)
![Kafka](https://img.shields.io/badge/Kafka-integrated-orange)
![Prometheus](https://img.shields.io/badge/Prometheus-monitored-blue)

---

## Responsibilities

- Register new users
- Authenticate users
- Generate JWT tokens
- Validate user identities
- Retrieve authenticated profiles
- Manage account lifecycle
- Publish authentication events
- Enforce access control policies

---

## Technology Stack

| Category          | Technology                 |
|-------------------|----------------------------|
| Runtime           | PHP 8.2                    |
| Framework         | Laravel 12                 |
| Messaging         | Kafka                      |
| Persistence       | Laravel Eloquent           |
| Database          | MongoDB                    |
| Cache             | Redis                      |
| API Documentation | OpenAPI                    |
| Validation        | Laravel Validation         |
| Security          | JWT Authentication         |
| Testing           | PHPUnit / Mockery          |
| Monitoring        | OpenTelemetry / Prometheus |
| Containerization  | Docker                     |
| CI/CD             | GitHub Actions             |

---

## API Endpoints

### Authentication Operations

#### Register User

```http
POST /auth/register
```

Creates a new user account.

---

#### Login

```http
POST /auth/login
```

Authenticates a user using Basic Authentication credentials and generates a JWT token.

---

#### Get User Profile

```http
GET /auth/profile
```

Returns information about the authenticated user.

---

#### Delete User

```http
DELETE /auth/user
```

Deletes the authenticated user's account.

---

## Event Processing

### Published Kafka Topics

| Topic                   | Description              |
|-------------------------|--------------------------|
| library.user.created.v1 | User registration events |
| library.user.deleted.v1 | User deletion events     |

---

## Processing Flow

Registration Request

↓

Validation Layer

↓

MongoDB Persistence

↓

Kafka Event Publication

↓

Downstream Service Consumption

---

## Authentication Lifecycle

Client Request

↓

Credential Validation

↓

Identity Verification

↓

JWT Generation

↓

Protected Resource Access

↓

Profile Retrieval

---

## API Documentation

`/swagger-ui/`

---

## Build Quality

The CI pipeline enforces:

- Automated test execution
- Coverage thresholds
- Pull request validation
- Docker image publication
- Branch protection workflows

---

Environment-driven configuration.

---

## Monitoring

`/health`

`/metrics`

---

## Architectural Role

The Authentication Service represents the security foundation of the distributed library system.

All protected services rely on the identity verification and JWT tokens issued by this service to enforce access control and secure communication across the platform.

---

## License

MIT
