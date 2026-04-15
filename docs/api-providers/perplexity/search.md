# Search — `POST /search`

Search the web and retrieve relevant page contents.

> **Not used by Tannus chat.** The Tannus AI chat uses `POST /v1/sonar` which includes built-in web search via the `sonar` model. The standalone `/search` endpoint is available for direct search use cases.

## OpenAPI Spec

```yaml
post /search
openapi: 3.1.0
info:
  title: Perplexity AI API
  version: 1.0.0
servers:
  - url: https://api.perplexity.ai
paths:
  /search:
    post:
      summary: Search the Web
      operationId: search_search_post
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [query]
              properties:
                query:
                  description: Search query string or array of strings
                  oneOf:
                    - type: string
                    - type: array
                      items:
                        type: string
                max_results:
                  type: integer
                  default: 10
                  minimum: 1
                  maximum: 20
                max_tokens:
                  type: integer
                  default: 10000
                max_tokens_per_page:
                  type: integer
                  default: 4096
                search_domain_filter:
                  type: array
                  maxItems: 20
                  description: Limit results to specific domains
                search_language_filter:
                  type: array
                  items:
                    type: string
                  description: ISO 639-1 language codes (e.g. en, fr)
                search_recency_filter:
                  type: string
                  enum: [hour, day, week, month, year]
                search_after_date_filter:
                  type: string
                  description: MM/DD/YYYY
                search_before_date_filter:
                  type: string
                  description: MM/DD/YYYY
                country:
                  type: string
                  description: ISO 3166-1 alpha-2 country code
      responses:
        '200':
          description: Successful Response
          content:
            application/json:
              schema:
                type: object
                properties:
                  id:
                    type: string
                  results:
                    type: array
                    items:
                      type: object
                      properties:
                        title:
                          type: string
                        url:
                          type: string
                        snippet:
                          type: string
                        date:
                          type: string
                        last_updated:
                          type: string
      security:
        - HTTPBearer: []
components:
  securitySchemes:
    HTTPBearer:
      type: http
      scheme: bearer
```
