# Contextualized Embeddings — `POST /contextualized-embeddings`

Generate embeddings that incorporate contextual information for improved relevance.

> **Not used by Tannus chat.** Contextualized embeddings are suited for document retrieval and RAG workflows, not for the real-time chat integration.

## OpenAPI Spec

```yaml
post /contextualized-embeddings
openapi: 3.1.0
info:
  title: Perplexity AI API
  version: 1.0.0
servers:
  - url: https://api.perplexity.ai
paths:
  /contextualized-embeddings:
    post:
      summary: Create Contextualized Embeddings
      operationId: createContextualizedEmbeddings
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [model, query, documents]
              properties:
                model:
                  type: string
                  description: Embedding model identifier
                query:
                  type: string
                  description: The query used to contextualize embeddings
                documents:
                  type: array
                  description: List of documents to embed with context
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
