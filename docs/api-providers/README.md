# API Providers Documentation

This folder contains OpenAPI specifications and usage notes for each external AI/search provider integrated with Tannus IA.

## Structure

```
docs/api-providers/
├── README.md              ← this file
├── perplexity/            ← Perplexity AI API
│   ├── chat-completion.md
│   ├── list-models.md
│   ├── async-chat.md
│   ├── agent.md
│   ├── search.md
│   ├── embeddings.md
│   ├── contextualized-embeddings.md
│   └── auth-tokens.md
└── <provider>/            ← future providers go here (e.g. openrouter/)
    └── ...
```

## Adding a New Provider

1. Create a subfolder named after the provider (lowercase, kebab-case): `docs/api-providers/<provider>/`
2. Add one markdown file per endpoint group. Each file should include:
   - A short description of the endpoint
   - The full OpenAPI YAML spec block
   - Usage notes specific to this integration
3. Reference the folder from the relevant service class (`src/CoreBundle/Service/<Provider>ApiService.php`)

## Providers

| Provider    | Folder         | Service Class              | Used For                    |
|-------------|----------------|----------------------------|-----------------------------|
| Perplexity  | `perplexity/`  | `PerplexityApiService`     | Primary chat (sonar model)  |
| OpenRouter  | *(planned)*    | `OpenRouterApiService`     | Fallback chat               |
