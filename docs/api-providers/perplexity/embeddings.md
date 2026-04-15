# Embeddings — `POST /embeddings`

Generate vector embeddings for input text.

> **Not used by Tannus chat.** Embeddings are relevant for semantic search, RAG pipelines, and similarity scoring — not for the real-time chat use case.

## OpenAPI Spec

```yaml
post /embeddings
openapi: 3.1.0
info:
  title: Perplexity AI API
  version: 1.0.0
servers:
  - url: https://api.perplexity.ai
paths:
  /embeddings:
    post:
      summary: Create Embeddings
      operationId: createEmbeddings
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [model, input]
              properties:
                model:
                  type: string
                  description: Embedding model identifier
                input:
                  description: Text input(s) to embed
                  oneOf:
                    - type: string
                    - type: array
                      items:
                        type: string
                encoding_format:
                  type: string
                  enum: [float, base64]
                  default: float
      responses:
        '200':
          description: Successful Response
          content:
            application/json:
              schema:
                type: object
                properties:
                  object:
                    type: string
                    enum: [list]
                  data:
                    type: array
                    items:
                      type: object
                      properties:
                        object:
                          type: string
                          enum: [embedding]
                        index:
                          type: integer
                        embedding:
                          type: array
                          items:
                            type: number
                  model:
                    type: string
                  usage:
                    type: object
                    properties:
                      prompt_tokens:
                        type: integer
                      total_tokens:
                        type: integer
      security:
        - HTTPBearer: []
components:
  securitySchemes:
    HTTPBearer:
      type: http
      scheme: bearer
```
