# Authentication — API Tokens

Perplexity AI uses **Bearer token** authentication on all endpoints.

## How to Obtain a Token

1. Sign in at [https://www.perplexity.ai](https://www.perplexity.ai)
2. Navigate to **Settings → API** to generate an API key
3. Copy the key — it will not be shown again

## How to Configure It

Store the key as an environment variable / Replit Secret:

```
PERPLEXITY_API_KEY=pplx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

The `PerplexityApiService` reads it automatically from `$_SERVER['PERPLEXITY_API_KEY']` or `getenv('PERPLEXITY_API_KEY')`.

## Token Lifecycle

| Operation       | Endpoint                   | Description                          |
|-----------------|----------------------------|--------------------------------------|
| Create token    | Perplexity dashboard       | Generate new API key via web UI      |
| Revoke token    | Perplexity dashboard       | Revoke via web UI settings           |

> There is no programmatic token revocation endpoint in the public API at this time. All token management is performed through the Perplexity web dashboard.

## Security

- Never commit the token to version control
- Use Replit Secrets (`PERPLEXITY_API_KEY`) in production
- Rotate the key if it is accidentally exposed

## OpenAPI Security Scheme

```yaml
securitySchemes:
  HTTPBearer:
    type: http
    scheme: bearer
```

Applied globally:

```yaml
security:
  - HTTPBearer: []
```
