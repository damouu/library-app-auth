# Library App Authentication Service / 認証サービス

Distributed Library System — Authentication Service

分散型図書館システム — 認証サービス

---

## Overview / 概要

The Authentication Service is the security foundation of the distributed library platform.

It is responsible for user registration, user authentication, JWT token generation, identity verification, and account
lifecycle management.

The service provides a centralized authentication mechanism used by all protected services within the platform and
publishes identity-related events through Kafka for downstream consumers.

---

Authentication Service は分散型図書館システムにおける認証基盤サービスです。

ユーザー登録、認証、JWT 発行、本人確認、およびアカウントライフサイクル管理を担当します。

本サービスはシステム全体の認証基盤として機能し、Kafka を通じて認証関連イベントを配信します。

---

## Service Boundaries / サービス境界

### Provides

* User registration
* User authentication
* JWT token generation
* Identity verification
* User profile retrieval
* Account deletion
* Authentication event publication
* Access control enforcement

### Does Not Handle

* Book catalog metadata management
* Borrow transaction processing
* Inventory management
* Recommendation generation
* Notification delivery
* Analytical aggregation
* Search indexing

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
[![Docker Image](https://img.shields.io/docker/v/damou/library-app-auth?label=docker\&logo=docker)](https://hub.docker.com/r/damou/library-app-auth)
[![Docker Pulls](https://img.shields.io/docker/pulls/damou/library-app-auth?logo=docker)](https://hub.docker.com/r/damou/library-app-auth)

<!-- Git / Version -->

[![Git Tag](https://img.shields.io/github/v/tag/damouu/library-app-auth?logo=github)](https://github.com/damouu/library-app-auth/tags)

<!-- Observability / Monitoring -->

![OpenTelemetry](https://img.shields.io/badge/OpenTelemetry-instrumented-brightgreen)
![Kafka](https://img.shields.io/badge/Kafka-integrated-orange)
![Prometheus](https://img.shields.io/badge/Prometheus-monitored-blue)

---

## Responsibilities / 責務

### English

* Register new users
* Authenticate users
* Generate JWT tokens
* Validate user identities
* Retrieve authenticated profiles
* Manage account lifecycle
* Publish authentication events
* Enforce access control policies

### 日本語

* ユーザー登録
* ユーザー認証
* JWT 発行
* 本人確認
* ユーザープロファイル取得
* アカウントライフサイクル管理
* 認証イベント配信
* アクセス制御管理

---

## Technology Stack / 技術スタック

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

## API Endpoints / API エンドポイント

### Authentication Operations / 認証操作

#### Register User

```http
POST /auth/register
```

Creates a new user account.

新しいユーザーアカウントを登録します。

---

#### Login

```http
POST /auth/login
```

Authenticates a user using Basic Authentication credentials and generates a JWT token.

Basic 認証情報を利用してユーザー認証を行い、JWT を発行します。

---

#### Get User Profile

```http
GET /auth/profile
```

Returns information about the authenticated user.

認証済みユーザー情報を返却します。

---

#### Delete User

```http
DELETE /auth/user
```

Deletes the authenticated user's account.

認証済みユーザーアカウントを削除します。

---

## Event Processing / イベント処理

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

## Authentication Lifecycle / 認証ライフサイクル

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

## API Documentation / API ドキュメント

/swagger-ui/

---

## Local Development / ローカル開発

### Requirements

* PHP 8.2
* Composer
* Docker
* MongoDB
* Redis
* Kafka

---

### Run

```bash
docker compose up --build
```

---

## Testing / テスト

```bash
composer test
```

### Includes

* Unit tests
* Integration tests
* Controller tests
* JWT validation tests
* Kafka event validation
* Coverage verification
* Complexity verification

---

### 日本語

含まれるテスト:

* ユニットテスト
* 統合テスト
* コントローラテスト
* JWT 検証テスト
* Kafka イベント検証
* カバレッジ検証
* 複雑度検証

---

## Build Quality / 品質保証

The CI pipeline enforces:

* Automated test execution
* Coverage thresholds
* Pull request validation
* Docker image publication
* Branch protection workflows

---

### 日本語

CI パイプラインでは以下を保証します:

* 自動テスト実行
* カバレッジ閾値管理
* Pull Request 検証
* Docker イメージ配布
* ブランチ保護ワークフロー

---

Environment-driven configuration.

環境変数ベースで構成されています。

---

## Monitoring / モニタリング

/health

/metrics

---

## Architectural Role / アーキテクチャ上の役割

The Authentication Service represents the security foundation of the distributed library system.

All protected services rely on the identity verification and JWT tokens issued by this service to enforce access control
and secure communication across the platform.

---

Authentication Service は分散型図書館システム全体の認証基盤サービスです。

各サービスは本サービスが発行する JWT を利用して認可処理を行い、システム全体の安全性を維持します。

---

## License / ライセンス

MIT
