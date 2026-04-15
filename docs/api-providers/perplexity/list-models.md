# List Models — `GET /v1/models`

List models available for the Agent API.

> **Not used by Tannus chat.** This endpoint is informational — it lists models available for `POST /v1/agent`, not for the sonar chat completion. Tannus chat uses a fixed model configured in `PerplexityApiService::DEFAULT_MODEL`.

## OpenAPI Spec

```yaml
get /v1/models
openapi: 3.1.0
info:
  title: Perplexity AI API
  version: 1.0.0
servers:
  - url: https://api.perplexity.ai
paths:
  /v1/models:
    get:
      summary: List Models
      description: >-
        List the models available for the Agent API. Returns model identifiers
        that can be used with the POST /v1/agent endpoint. The response follows
        the OpenAI List Models format for compatibility with third-party tools.
      operationId: listModels
      responses:
        '200':
          description: Successful response with the list of available models.
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ListModelsResponse'
              example:
                object: list
                data:
                  - id: perplexity/sonar
                    object: model
                    created: 0
                    owned_by: perplexity
                  - id: openai/gpt-5.4
                    object: model
                    created: 0
                    owned_by: openai
                  - id: anthropic/claude-sonnet-4-6
                    object: model
                    created: 0
                    owned_by: anthropic
      security: []
components:
  schemas:
    ListModelsResponse:
      type: object
      required: [object, data]
      properties:
        object:
          type: string
          enum: [list]
          description: Always "list"
        data:
          type: array
          description: List of available model objects
          items:
            $ref: '#/components/schemas/Model'
    Model:
      type: object
      required: [id, object, created, owned_by]
      properties:
        id:
          type: string
          description: Model identifier in provider/model-name format
          example: perplexity/sonar
        object:
          type: string
          enum: [model]
          description: Always "model"
        created:
          type: integer
          description: Unix timestamp when the model was created
          example: 0
        owned_by:
          type: string
          description: Provider that owns the model (openai, anthropic, perplexity, xai, etc.)
          example: perplexity
```
