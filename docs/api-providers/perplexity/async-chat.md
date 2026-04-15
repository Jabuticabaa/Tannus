# Async Chat Completion — `POST /v1/async/sonar` / `GET /v1/async/sonar`

Submit and retrieve asynchronous chat completion requests.

> **Not used for real-time chat.** Async completions are intended for long-running tasks (e.g. `sonar-deep-research`). The Tannus chat uses synchronous `POST /v1/sonar` instead.

## OpenAPI Spec

```yaml
post /v1/async/sonar — submit async request
openapi: 3.1.0
info:
  title: Perplexity AI API
  version: 1.0.0
servers:
  - url: https://api.perplexity.ai
paths:
  /v1/async/sonar:
    post:
      summary: Create Async Chat Completion
      operationId: create_async_chat_completions
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [request]
              properties:
                request:
                  $ref: '#/components/schemas/ApiChatCompletionsRequest'
                idempotency_key:
                  type: string
      responses:
        '200':
          description: Accepted — returns task ID and status
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AsyncApiChatCompletionsResponse'
      security:
        - HTTPBearer: []
    get:
      summary: List Async Chat Completions
      operationId: list_async_chat_completions
      responses:
        '200':
          description: List of async requests for the authenticated user
          content:
            application/json:
              schema:
                type: object
                properties:
                  requests:
                    type: array
                  next_token:
                    type: string
      security:
        - HTTPBearer: []
components:
  schemas:
    AsyncApiChatCompletionsResponse:
      type: object
      required: [id, model, created_at, status]
      properties:
        id:
          type: string
        model:
          type: string
        created_at:
          type: integer
        started_at:
          type: integer
        completed_at:
          type: integer
        failed_at:
          type: integer
        status:
          type: string
          enum: [CREATED, IN_PROGRESS, COMPLETED, FAILED]
        response:
          description: CompletionResponse when status is COMPLETED
        error_message:
          type: string
  securitySchemes:
    HTTPBearer:
      type: http
      scheme: bearer
```
