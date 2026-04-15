# Agent API — `POST /v1/agent`

Generate a response using the Perplexity Agent API with optional web search, reasoning, and tool use.

> **Not used by Tannus chat.** The agent endpoint targets multi-step research workflows and model chaining. Tannus uses the simpler `POST /v1/sonar` chat completion endpoint.

## OpenAPI Spec

```yaml
post /v1/agent
openapi: 3.1.0
info:
  title: Perplexity AI API
  version: 1.0.0
servers:
  - url: https://api.perplexity.ai
paths:
  /v1/agent:
    post:
      summary: Create Agent Response
      description: Generate a response with optional web search and reasoning.
      operationId: createAgent
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [input]
              properties:
                input:
                  description: Input string or array of input items
                  oneOf:
                    - type: string
                    - type: array
                model:
                  type: string
                  description: Model in provider/model format (e.g. "xai/grok-4-1")
                models:
                  type: array
                  maxItems: 5
                  description: Fallback chain of models tried in order
                preset:
                  type: string
                  description: Pre-configured preset (fast-search, pro-search, deep-research)
                instructions:
                  type: string
                  description: System instructions for the model
                max_output_tokens:
                  type: integer
                max_steps:
                  type: integer
                  minimum: 1
                  maximum: 10
                stream:
                  type: boolean
                reasoning:
                  type: object
                  properties:
                    effort:
                      type: string
                      enum: [low, medium, high]
                tools:
                  type: array
      responses:
        '200':
          description: Successful response (JSON or SSE depending on stream)
      security:
        - HTTPBearer: []
components:
  securitySchemes:
    HTTPBearer:
      type: http
      scheme: bearer
```

## Available Models (via `GET /v1/models`)

Use `GET /v1/models` to list models available for the Agent API. Response follows OpenAI List Models format.

```yaml
get /v1/models
paths:
  /v1/models:
    get:
      summary: List Models
      operationId: listModels
      security: []
      responses:
        '200':
          content:
            application/json:
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
```
