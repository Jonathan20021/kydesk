<?php
namespace App\Controllers\Api;

class SpecController extends BaseApiController
{
    /**
     * Generate OpenAPI 3.1 spec for the Kydesk API.
     */
    public function openapi(): void
    {
        $base = rtrim($this->app->config['app']['url'], '/');
        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Kydesk Helpdesk API',
                'version' => 'v1',
                'description' => "API REST de Kydesk Helpdesk. Construye integraciones, automatizaciones y aplicaciones sobre tu workspace de helpdesk.\n\n## Autenticación\n\nTodas las llamadas (excepto `/health`) requieren un Bearer token en el header `Authorization`. Genera tokens en tu panel de developer (`/developers/apps/{id}`) o en el panel de tenant (`/t/{slug}/api-docs`).\n\n```\nAuthorization: Bearer kyd_xxxxxxxxxxxxxxxxxxxxxxxx\n```\n\n## Cuotas y rate limit\n\nLos tokens de developer aplican cuotas del plan suscrito. Cada respuesta incluye headers `X-RateLimit-Limit`, `X-Quota-Used`, `X-Quota-Limit` y `X-Quota-Pct`.\n\n## Idempotencia\n\nLos endpoints `POST` aceptan el header `Idempotency-Key`. Si lo envías, llamadas duplicadas en 24h devuelven la misma respuesta sin crear recursos duplicados.\n\n## Paginación\n\nUsa `?page=N&per_page=K` o `?offset=M&limit=K`. La respuesta incluye `meta.total`, `meta.has_more` y un objeto `links` con URLs de navegación.\n\n## Expansión de relaciones\n\nUsa `?expand=` para incluir relaciones en línea: `?expand=company,assignee,comments`.\n\n## Selección de campos\n\nUsa `?fields=id,subject,status` para limitar las propiedades devueltas.\n\n## Errores\n\nFormato consistente:\n```json\n{\n  \"error\": {\n    \"type\": \"validation_error\",\n    \"message\": \"...\",\n    \"status\": 422,\n    \"request_id\": \"abc123\"\n  }\n}\n```",
                'contact' => [
                    'name' => 'Kydesk Developer Support',
                    'email' => 'developers@kyrosrd.com',
                    'url' => $base . '/developers',
                ],
                'license' => ['name' => 'Proprietary', 'url' => $base . '/terms'],
            ],
            'servers' => [
                ['url' => $base . '/api/v1', 'description' => 'Production'],
            ],
            'security' => [['bearerAuth' => []]],
            'tags' => [
                ['name' => 'Meta', 'description' => 'Endpoints informativos: identidad, salud, estadísticas'],
                ['name' => 'Tickets', 'description' => 'CRUD de tickets, comentarios y escalamientos'],
                ['name' => 'Companies', 'description' => 'Empresas (clientes B2B)'],
                ['name' => 'Categories', 'description' => 'Categorías de tickets'],
                ['name' => 'Users', 'description' => 'Usuarios del workspace (técnicos / agentes)'],
                ['name' => 'KB', 'description' => 'Base de conocimiento'],
                ['name' => 'SLA', 'description' => 'Políticas de SLA'],
                ['name' => 'Automations', 'description' => 'Automatizaciones tipo IFTTT'],
                ['name' => 'Assets', 'description' => 'Activos / inventario'],
                ['name' => 'Search', 'description' => 'Búsqueda global'],
            ],
            'paths' => $this->buildPaths(),
            'components' => $this->buildComponents(),
        ];
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Generate Postman 2.1 collection.
     */
    public function postman(): void
    {
        $base = rtrim($this->app->config['app']['url'], '/') . '/api/v1';
        $resources = [
            'Meta' => [
                ['GET', '/me', 'Identidad del token'],
                ['GET', '/health', 'Health check'],
                ['GET', '/stats', 'Estadísticas del workspace'],
                ['GET', '/search?q=ejemplo', 'Búsqueda global'],
            ],
            'Tickets' => [
                ['GET', '/tickets?per_page=25', 'Listar tickets'],
                ['POST', '/tickets', 'Crear ticket'],
                ['GET', '/tickets/{{ticket_id}}', 'Detalle'],
                ['PATCH', '/tickets/{{ticket_id}}', 'Actualizar'],
                ['DELETE', '/tickets/{{ticket_id}}', 'Eliminar'],
                ['POST', '/tickets/{{ticket_id}}/comments', 'Añadir comentario'],
                ['GET', '/tickets/{{ticket_id}}/comments', 'Listar comentarios'],
                ['POST', '/tickets/{{ticket_id}}/escalate', 'Escalar ticket'],
                ['POST', '/tickets/{{ticket_id}}/assign', 'Asignar técnico'],
                ['POST', '/tickets/batch', 'Operación batch'],
            ],
            'Companies' => [
                ['GET', '/companies', 'Listar'],
                ['POST', '/companies', 'Crear'],
                ['GET', '/companies/{{company_id}}', 'Detalle'],
                ['PATCH', '/companies/{{company_id}}', 'Actualizar'],
                ['DELETE', '/companies/{{company_id}}', 'Eliminar'],
            ],
            'Categories' => [
                ['GET', '/categories', 'Listar'],
                ['POST', '/categories', 'Crear'],
                ['PATCH', '/categories/{{cat_id}}', 'Actualizar'],
                ['DELETE', '/categories/{{cat_id}}', 'Eliminar'],
            ],
            'Users' => [
                ['GET', '/users', 'Listar'],
                ['POST', '/users', 'Crear'],
                ['GET', '/users/{{user_id}}', 'Detalle'],
                ['PATCH', '/users/{{user_id}}', 'Actualizar'],
                ['DELETE', '/users/{{user_id}}', 'Eliminar'],
            ],
            'KB' => [
                ['GET', '/kb/articles', 'Listar artículos'],
                ['POST', '/kb/articles', 'Crear artículo'],
                ['GET', '/kb/articles/{{article_id}}', 'Detalle'],
                ['PATCH', '/kb/articles/{{article_id}}', 'Actualizar'],
                ['DELETE', '/kb/articles/{{article_id}}', 'Eliminar'],
                ['GET', '/kb/categories', 'Listar categorías KB'],
            ],
            'SLA' => [
                ['GET', '/sla', 'Listar políticas'],
                ['POST', '/sla', 'Crear política'],
                ['PATCH', '/sla/{{sla_id}}', 'Actualizar'],
                ['DELETE', '/sla/{{sla_id}}', 'Eliminar'],
            ],
            'Automations' => [
                ['GET', '/automations', 'Listar'],
                ['POST', '/automations', 'Crear'],
                ['PATCH', '/automations/{{auto_id}}', 'Actualizar'],
                ['DELETE', '/automations/{{auto_id}}', 'Eliminar'],
            ],
            'Assets' => [
                ['GET', '/assets', 'Listar'],
                ['POST', '/assets', 'Crear'],
                ['PATCH', '/assets/{{asset_id}}', 'Actualizar'],
                ['DELETE', '/assets/{{asset_id}}', 'Eliminar'],
            ],
        ];

        $items = [];
        foreach ($resources as $folder => $list) {
            $folderItems = [];
            foreach ($list as [$method, $path, $name]) {
                $folderItems[] = [
                    'name' => "$method $path",
                    'request' => [
                        'method' => $method,
                        'header' => [
                            ['key' => 'Authorization', 'value' => 'Bearer {{api_token}}', 'type' => 'text'],
                            ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
                        ],
                        'url' => [
                            'raw' => '{{base_url}}' . $path,
                            'host' => ['{{base_url}}'],
                            'path' => array_values(array_filter(explode('/', explode('?', $path)[0]))),
                        ],
                        'description' => $name,
                    ],
                ];
            }
            $items[] = ['name' => $folder, 'item' => $folderItems];
        }

        $coll = [
            'info' => [
                '_postman_id' => bin2hex(random_bytes(8)),
                'name' => 'Kydesk Helpdesk API',
                'description' => 'Colección oficial de la API de Kydesk. Configura las variables `base_url` y `api_token` en el entorno.',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $items,
            'variable' => [
                ['key' => 'base_url', 'value' => $base, 'type' => 'string'],
                ['key' => 'api_token', 'value' => 'kyd_xxxxxxxxxxxxxxxx', 'type' => 'string'],
            ],
        ];
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Content-Disposition: attachment; filename="kydesk-postman-collection.json"');
        echo json_encode($coll, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────

    protected function buildPaths(): array
    {
        $resp200 = ['$ref' => '#/components/responses/Success'];
        $resp201 = ['$ref' => '#/components/responses/Created'];
        $resp401 = ['$ref' => '#/components/responses/Unauthorized'];
        $resp404 = ['$ref' => '#/components/responses/NotFound'];
        $resp422 = ['$ref' => '#/components/responses/ValidationError'];
        $resp429 = ['$ref' => '#/components/responses/TooManyRequests'];

        $idParam = ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']];
        $pagParams = [
            ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1]],
            ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]],
            ['name' => 'sort', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Campo de ordenamiento. Prefijo `-` para descendente: `-created_at`'],
            ['name' => 'expand', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Relaciones a expandir, separadas por coma'],
            ['name' => 'fields', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Lista de campos a devolver'],
        ];

        return [
            '/me' => ['get' => ['tags' => ['Meta'], 'summary' => 'Identidad del token', 'responses' => ['200' => $resp200, '401' => $resp401]]],
            '/health' => ['get' => ['tags' => ['Meta'], 'summary' => 'Health check (no requiere auth)', 'security' => [], 'responses' => ['200' => $resp200]]],
            '/stats' => ['get' => ['tags' => ['Meta'], 'summary' => 'Estadísticas del workspace', 'responses' => ['200' => $resp200, '401' => $resp401]]],
            '/search' => [
                'get' => [
                    'tags' => ['Search'],
                    'summary' => 'Búsqueda global',
                    'parameters' => [
                        ['name' => 'q', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'minLength' => 2]],
                        ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'maximum' => 20]],
                    ],
                    'responses' => ['200' => $resp200, '422' => $resp422],
                ],
            ],
            '/tickets' => [
                'get' => [
                    'tags' => ['Tickets'], 'summary' => 'Listar tickets',
                    'parameters' => array_merge($pagParams, [
                        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['open','in_progress','on_hold','resolved','closed']]],
                        ['name' => 'priority', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['low','medium','high','urgent']]],
                        ['name' => 'assigned_to', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ['name' => 'q', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Búsqueda en subject/code/description'],
                    ]),
                    'responses' => ['200' => $resp200, '401' => $resp401, '429' => $resp429],
                ],
                'post' => [
                    'tags' => ['Tickets'], 'summary' => 'Crear ticket',
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/TicketInput']]]],
                    'parameters' => [['name' => 'Idempotency-Key', 'in' => 'header', 'schema' => ['type' => 'string']]],
                    'responses' => ['201' => $resp201, '422' => $resp422, '401' => $resp401],
                ],
            ],
            '/tickets/{id}' => [
                'parameters' => [$idParam],
                'get' => ['tags' => ['Tickets'], 'summary' => 'Detalle de ticket', 'responses' => ['200' => $resp200, '404' => $resp404]],
                'patch' => [
                    'tags' => ['Tickets'], 'summary' => 'Actualizar ticket',
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/TicketInput']]]],
                    'responses' => ['200' => $resp200, '404' => $resp404, '422' => $resp422],
                ],
                'delete' => ['tags' => ['Tickets'], 'summary' => 'Eliminar ticket', 'responses' => ['200' => $resp200, '404' => $resp404]],
            ],
            '/tickets/{id}/comments' => [
                'parameters' => [$idParam],
                'get' => ['tags' => ['Tickets'], 'summary' => 'Listar comentarios', 'responses' => ['200' => $resp200]],
                'post' => [
                    'tags' => ['Tickets'], 'summary' => 'Añadir comentario',
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['body'], 'properties' => ['body' => ['type' => 'string'], 'is_internal' => ['type' => 'boolean']]]]]],
                    'responses' => ['201' => $resp201, '404' => $resp404],
                ],
            ],
            '/tickets/{id}/escalate' => [
                'parameters' => [$idParam],
                'post' => ['tags' => ['Tickets'], 'summary' => 'Escalar ticket', 'responses' => ['200' => $resp200]],
            ],
            '/tickets/{id}/assign' => [
                'parameters' => [$idParam],
                'post' => [
                    'tags' => ['Tickets'], 'summary' => 'Asignar técnico',
                    'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ['type' => 'object', 'required' => ['user_id'], 'properties' => ['user_id' => ['type' => 'integer']]]]]],
                    'responses' => ['200' => $resp200],
                ],
            ],
            '/tickets/batch' => [
                'post' => ['tags' => ['Tickets'], 'summary' => 'Operaciones batch (hasta 50)', 'responses' => ['200' => $resp200]],
            ],
            '/companies' => [
                'get' => ['tags' => ['Companies'], 'summary' => 'Listar empresas', 'parameters' => $pagParams, 'responses' => ['200' => $resp200]],
                'post' => ['tags' => ['Companies'], 'summary' => 'Crear empresa', 'responses' => ['201' => $resp201]],
            ],
            '/companies/{id}' => [
                'parameters' => [$idParam],
                'get' => ['tags' => ['Companies'], 'responses' => ['200' => $resp200, '404' => $resp404]],
                'patch' => ['tags' => ['Companies'], 'responses' => ['200' => $resp200]],
                'delete' => ['tags' => ['Companies'], 'responses' => ['200' => $resp200]],
            ],
            '/categories' => [
                'get' => ['tags' => ['Categories'], 'responses' => ['200' => $resp200]],
                'post' => ['tags' => ['Categories'], 'responses' => ['201' => $resp201]],
            ],
            '/categories/{id}' => [
                'parameters' => [$idParam],
                'get' => ['tags' => ['Categories'], 'responses' => ['200' => $resp200]],
                'patch' => ['tags' => ['Categories'], 'responses' => ['200' => $resp200]],
                'delete' => ['tags' => ['Categories'], 'responses' => ['200' => $resp200]],
            ],
            '/users' => [
                'get' => ['tags' => ['Users'], 'parameters' => $pagParams, 'responses' => ['200' => $resp200]],
                'post' => ['tags' => ['Users'], 'responses' => ['201' => $resp201]],
            ],
            '/users/{id}' => [
                'parameters' => [$idParam],
                'get' => ['tags' => ['Users'], 'responses' => ['200' => $resp200]],
                'patch' => ['tags' => ['Users'], 'responses' => ['200' => $resp200]],
                'delete' => ['tags' => ['Users'], 'responses' => ['200' => $resp200]],
            ],
            '/kb/articles' => [
                'get' => ['tags' => ['KB'], 'parameters' => $pagParams, 'responses' => ['200' => $resp200]],
                'post' => ['tags' => ['KB'], 'responses' => ['201' => $resp201]],
            ],
            '/kb/articles/{id}' => [
                'parameters' => [$idParam],
                'get' => ['tags' => ['KB'], 'responses' => ['200' => $resp200]],
                'patch' => ['tags' => ['KB'], 'responses' => ['200' => $resp200]],
                'delete' => ['tags' => ['KB'], 'responses' => ['200' => $resp200]],
            ],
            '/kb/categories' => ['get' => ['tags' => ['KB'], 'summary' => 'Listar categorías KB', 'responses' => ['200' => $resp200]]],
            '/sla' => [
                'get' => ['tags' => ['SLA'], 'responses' => ['200' => $resp200]],
                'post' => ['tags' => ['SLA'], 'responses' => ['201' => $resp201]],
            ],
            '/sla/{id}' => [
                'parameters' => [$idParam],
                'patch' => ['tags' => ['SLA'], 'responses' => ['200' => $resp200]],
                'delete' => ['tags' => ['SLA'], 'responses' => ['200' => $resp200]],
            ],
            '/automations' => [
                'get' => ['tags' => ['Automations'], 'responses' => ['200' => $resp200]],
                'post' => ['tags' => ['Automations'], 'responses' => ['201' => $resp201]],
            ],
            '/automations/{id}' => [
                'parameters' => [$idParam],
                'patch' => ['tags' => ['Automations'], 'responses' => ['200' => $resp200]],
                'delete' => ['tags' => ['Automations'], 'responses' => ['200' => $resp200]],
            ],
            '/assets' => [
                'get' => ['tags' => ['Assets'], 'parameters' => $pagParams, 'responses' => ['200' => $resp200]],
                'post' => ['tags' => ['Assets'], 'responses' => ['201' => $resp201]],
            ],
            '/assets/{id}' => [
                'parameters' => [$idParam],
                'get' => ['tags' => ['Assets'], 'responses' => ['200' => $resp200]],
                'patch' => ['tags' => ['Assets'], 'responses' => ['200' => $resp200]],
                'delete' => ['tags' => ['Assets'], 'responses' => ['200' => $resp200]],
            ],
        ];
    }

    protected function buildComponents(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'kyd_*'],
            ],
            'schemas' => [
                'Ticket' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'code' => ['type' => 'string', 'example' => 'TK-01-00042'],
                        'subject' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => ['open','in_progress','on_hold','resolved','closed']],
                        'priority' => ['type' => 'string', 'enum' => ['low','medium','high','urgent']],
                        'channel' => ['type' => 'string', 'enum' => ['portal','email','phone','chat','internal']],
                        'requester_email' => ['type' => 'string', 'format' => 'email'],
                        'company_id' => ['type' => ['integer','null']],
                        'category_id' => ['type' => ['integer','null']],
                        'assigned_to' => ['type' => ['integer','null']],
                        'sla_due_at' => ['type' => ['string','null'], 'format' => 'date-time'],
                        'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ],
                'TicketInput' => [
                    'type' => 'object',
                    'required' => ['subject'],
                    'properties' => [
                        'subject' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => ['open','in_progress','on_hold','resolved','closed']],
                        'priority' => ['type' => 'string', 'enum' => ['low','medium','high','urgent']],
                        'channel' => ['type' => 'string', 'enum' => ['portal','email','phone','chat','internal']],
                        'requester_name' => ['type' => 'string'],
                        'requester_email' => ['type' => 'string', 'format' => 'email'],
                        'company_id' => ['type' => 'integer'],
                        'category_id' => ['type' => 'integer'],
                        'assigned_to' => ['type' => 'integer'],
                        'tags' => ['type' => 'string', 'description' => 'Lista separada por comas'],
                    ],
                ],
                'Error' => [
                    'type' => 'object',
                    'properties' => [
                        'error' => [
                            'type' => 'object',
                            'properties' => [
                                'type' => ['type' => 'string'],
                                'message' => ['type' => 'string'],
                                'status' => ['type' => 'integer'],
                                'request_id' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'responses' => [
                'Success' => [
                    'description' => 'OK',
                    'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => ['oneOf' => [['type' => 'object'], ['type' => 'array']]], 'meta' => ['type' => 'object']]]]],
                ],
                'Created' => ['description' => 'Recurso creado', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['data' => ['type' => 'object']]]]]],
                'Unauthorized' => ['description' => 'Token inválido o ausente', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
                'NotFound' => ['description' => 'Recurso no encontrado', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
                'ValidationError' => ['description' => 'Validación fallida', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
                'TooManyRequests' => ['description' => 'Rate limit / cuota excedida', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Error']]]],
            ],
        ];
    }
}
