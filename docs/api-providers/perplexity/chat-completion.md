# Chat Completion — `POST /v1/sonar`

Generate a chat completion response for the given conversation.

Used by `PerplexityApiService::chat()` as the primary endpoint for Tannus AI chat.

## Usage Notes

- **Model**: `sonar` (default). Alternatives: `sonar-pro`, `sonar-deep-research`, `sonar-reasoning-pro`
- **Auth**: Bearer token via `PERPLEXITY_API_KEY` environment variable
- **Conversation**: Pass the full `messages` array (system + user/assistant history) on every request
- **System prompt**: Prepended automatically by the service; do not include it in the `messages` array sent from the frontend

## OpenAPI Spec

```yaml
post /v1/sonar
openapi: 3.1.0
info:
  title: Perplexity AI API
  version: 1.0.0
servers:
  - url: https://api.perplexity.ai
paths:
  /v1/sonar:
    post:
      summary: Create Chat Completion
      description: Generate a chat completion response for the given conversation.
      operationId: chat_completions_chat_completions_post
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ApiChatCompletionsRequest'
      responses:
        '200':
          description: Successful Response
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CompletionResponse'
        '422':
          description: Validation Error
      security:
        - HTTPBearer: []
components:
  schemas:
    ApiChatCompletionsRequest:
      type: object
      required:
        - model
        - messages
      properties:
        model:
          type: string
          enum: [sonar, sonar-pro, sonar-deep-research, sonar-reasoning-pro]
        messages:
          type: array
          description: Array of messages forming the conversation history
          items:
            $ref: '#/components/schemas/ChatMessage'
        max_tokens:
          type: integer
          maximum: 128000
        temperature:
          type: number
          minimum: 0
          maximum: 2
        stream:
          type: boolean
          default: false
        disable_search:
          type: boolean
          description: When true, disables web search; model uses only training data
    ChatMessage:
      type: object
      required: [role, content]
      properties:
        role:
          type: string
          enum: [system, user, assistant]
        content:
          type: string
    CompletionResponse:
      type: object
      required: [id, model, created, choices]
      properties:
        id:
          type: string
        model:
          type: string
        created:
          type: integer
        choices:
          type: array
          items:
            type: object
            properties:
              index:
                type: integer
              message:
                $ref: '#/components/schemas/ChatMessage'
              finish_reason:
                type: string
                enum: [stop, length]
        citations:
          type: array
          items:
            type: string
          description: URLs of sources used to generate the response
  securitySchemes:
    HTTPBearer:
      type: http
      scheme: bearer
```
