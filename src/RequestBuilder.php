<?php

namespace TONYLABS\PowerSchool\Api;

use Illuminate\Support\Str;
use stdClass;

class RequestBuilder {

    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';

    protected Request $request;
    protected ?string $endpoint;
    protected ?string $method;
    protected array $options = [];
    protected ?array $data = null;
    protected ?string $table = null;
    protected array $queryString = [];
    protected string|int|null $id = null;
    protected bool $includeProjection = false;
    protected bool $asJsonResponse = false;
    protected string $pageKey = 'record';
    protected Paginator $paginator;

    public function __construct(string $serverAddress, string $clientId, string $clientSecret, ?string $token = NULL)
    {
        $this->request = new Request($serverAddress, $clientId, $clientSecret, $token);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Cleans all the variables for the next request
     */
    public function freshen(): static
    {
        $this->endpoint = null;
        $this->method = null;
        $this->options = [];
        $this->data = null;
        $this->table = null;
        $this->queryString = [];
        $this->id = null;
        $this->includeProjection = false;
        $this->asJsonResponse = false;
        unset($this->paginator);
        $this->pageKey = 'record';
        return $this;
    }

    /**
     * Sets the table for a request against a custom table
     */
    public function setTable(string $table): static
    {
        $this->table = $table;
        $this->endpoint = Str::startsWith($table, '/') ? $table : '/ws/schema/table/' . $table;
        $this->includeProjection = true;
        $this->pageKey = 'record';
        return $this;
    }

    /**
     * @see setTable
     */
    public function table(string $table): static
    {
        return $this->setTable($table);
    }

    /**
     * @see setTable
     */
    public function forTable(string $table): static
    {
        return $this->setTable($table);
    }

    /**
     * @see setTable
     */
    public function againstTable(string $table): static
    {
        return $this->setTable($table);
    }

    /**
     * Sets the id of the resource we're interacting with
     */
    public function setId(string|int $id): static
    {
        $this->endpoint .= '/' . $id;
        $this->id = $id;
        return $this;
    }

    /**
     * @see setId
     */
    public function id(string|int $id): static
    {
        return $this->setId($id);
    }

    /**
     * @see setId
     */
    public function forId(string|int $id): static
    {
        return $this->setId($id);
    }

    /**
     * Configures the request to be a core resource with optional method and data that
     * will send the request automatically.
     */
    public function resource(string $endpoint, string $method = null, array $data = []): null|stdClass|static
    {
        $this->endpoint = $endpoint;
        $this->includeProjection = false;

        if (!is_null($method)) {
            $this->method = $method;
        }

        if (!empty($data)) {
            $this->setData($data);
        }

        // If the method and data are set, automatically send the request
        if (!is_null($this->method) && !empty($this->data)) {
            return $this->send();
        }

        return $this;
    }

    /**
     * Does not force a projection parameter for GET requests
     */
    public function excludeProjection(): static
    {
        $this->includeProjection = false;
        return $this;
    }

    /**
     * @see excludeProjection
     */
    public function withoutProjection(): static
    {
        return $this->excludeProjection();
    }

    /**
     * Sets the endpoint for the request
     */
    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;
        $this->pageKey = Str::afterLast($endpoint, '/');
        return $this->excludeProjection();
    }

    /**
     * @see setEndpoint
     */
    public function toEndpoint(string $endpoint): static
    {
        return $this->setEndpoint($endpoint);
    }

    /**
     * @see setEndpoint
     */
    public function to(string $endpoint): static
    {
        return $this->setEndpoint($endpoint);
    }

    /**
     * @see setEndpoint
     */
    public function endpoint(string $endpoint): static
    {
        return $this->setEndpoint($endpoint);
    }

    /**
     * Sets the endpoint to the named query
     *
     * @param string $query The named query name (com.organization.product.area.name)
     * @param array $data
     * @return RequestBuilder|mixed
     */
    public function setNamedQuery(string $query, array $data = [])
    {
        $this->endpoint = Str::startsWith($query, '/') ? $query : '/ws/schema/query/' . $query;
        $this->pageKey = 'record';
        if (!empty($data)) {
            return $this->withData($data)->post();
        }
        $this->includeProjection = false;
        return $this->setMethod(static::POST);
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query The named query name (com.organization.product.area.name)
     * @param array $data
     * @return mixed
     */
    public function namedQuery(string $query, array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query The named query name (com.organization.product.area.name)
     * @param array $data
     * @return mixed
     */
    public function powerQuery(string $query, array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Alias for setNamedQuery()
     *
     * @param string $query The named query name (com.organization.product.area.name)
     * @param array $data
     * @return mixed
     */
    public function pq(string $query, array $data = [])
    {
        return $this->setNamedQuery($query, $data);
    }

    /**
     * Sets the data for the post/put/patch requests
     * Also performs basic sanitation for PS, such
     * as bool translation
     */
    public function setData(array $data): static
    {
        $this->data = $this->castToValuesString($data);
        return $this;
    }

    /**
     * Alias for setData()
     */
    public function withData(array $data): static
    {
        return $this->setData($data);
    }

    /**
     * Alias for setData()
     */
    public function with(array $data): static
    {
        return $this->setData($data);
    }

    /**
     * Sets an item to be included in the post request
     */
    public function setDataItem(string $key, $value): static
    {
        $this->data[$key] = $this->castToValuesString($value);
        return $this;
    }

    /**
     * Sets the query string for get requests
     */
    public function withQueryString(string|array $queryString): static
    {
        if (is_array($queryString)) {
            $this->queryString = $queryString;
        } else {
            parse_str($queryString, $this->queryString);
        }

        $this->queryString = $queryString;
        return $this;
    }

    /**
     * Alias of withQueryString()
     */
    public function query(string|array $queryString): static
    {
        return $this->withQueryString($queryString);
    }

    /**
     * Adds a variable to the query string
     */
    public function addQueryVar(string $key, $value): static
    {
        $this->queryString[$key] = $value;
        return $this;
    }

    /**
     * Checks to see if a query variable has been set
     */
    public function hasQueryVar(string $key): bool
    {
        return isset($this->queryString[$key]) && !empty($this->queryString[$key]);
    }

    /**
     * Syntactic sugar for the q query string var
     */
    public function q(string $query): static
    {
        return $this->addQueryVar('q', $query);
    }

    /**
     * Sugar for q()
     */
    public function queryExpression(string $expression): static
    {
        return $this->q($expression);
    }

    /**
     * Adds an ad-hoc filter expression, meant to be used for PowerQueries
     */
    public function adHocFilter(string $expression): static
    {
        return $this->addQueryVar('$q', $expression);
    }

    /**
     * Sugar for adHocFilter()
     */
    public function filter(string $expression): static
    {
        return $this->adHocFilter($expression);
    }

    /**
     * Syntactic sugar for the projection query string var
     */
    public function projection(string|array $projection): static
    {
        if (is_array($projection)) {
            $projection = implode(',', $projection);
        }
        $this->includeProjection = true;

        return $this->addQueryVar('projection', $projection);
    }

    /**
     * Syntactic sugar for the `pagesize` query string var
     */
    public function pageSize(int $pageSize): static
    {
        return $this->addQueryVar('pagesize', $pageSize);
    }

    /**
     * Sets the page query variable
     */
    public function page(int $page): static
    {
        return $this->addQueryVar('page', $page);
    }

    /**
     * Sets the sorting columns and direction for the request
     */
    public function sort(string|array $columns, bool $descending = false): static
    {
        $sort = is_array($columns) ? implode(',', $columns) : $columns;
        $this->addQueryVar('sort', $sort);
        $this->addQueryVar('sortdescending', $descending ? 'true' : 'false');
        return $this;
    }

    /**
     * Adds an order query string variable
     */
    public function adHocOrder(string $expression): static
    {
        return $this->addQueryVar('order', $expression);
    }

    /**
     * @see adHocOrder()
     */
    public function order(string $expression): static
    {
        return $this->adHocOrder($expression);
    }

    /**
     * Adds the count query variable for PowerQueries
     */
    public function includeCount(): static
    {
        return $this->addQueryVar('count', 'true');
    }

    /**
     * Configures the data version for the PowerQuery
     */
    public function dataVersion(int $version, string $applicationName): static
    {
        return $this->setDataItem('$dataversion', $version)->setDataItem('$dataversion_applicationname', $applicationName);
    }

    /**
     * Alias of dataVersion()
     */
    public function withDataVersion(int $version, string $applicationName): static
    {
        return $this->dataVersion($version, $applicationName);
    }

    /**
     * Adds `expansions` query variable
     */
    public function expansions(string|array $expansions): static
    {
        $expansions = is_array($expansions) ? implode(',', $expansions) : $expansions;
        $this->addQueryVar('expansions', $expansions);
        return $this;
    }

    /**
     * Alias of expansions()
     */
    public function withExpansions(string|array $expansions): static
    {
        return $this->expansions($expansions);
    }

    /**
     * Alias of expansions()
     */
    public function withExpansion(string $expansion): static
    {
        return $this->expansions($expansion);
    }

    /**
     * Adds `expansions` query variable
     */
    public function extensions(string|array $extensions): static
    {
        $extensions = is_array($extensions) ? implode(',', $extensions) : $extensions;
        $this->addQueryVar('extensions', $extensions);
        return $this;
    }

    /**
     * Alias of expansions()
     */
    public function withExtensions(string|array $extensions): static
    {
        return $this->extensions($extensions);
    }

    /**
     * Alias of extensions()
     */
    public function withExtension(string $extension): static
    {
        return $this->extensions($extension);
    }

    /**
     * Gets the data changes based on the data version subscription
     */
    public function getDataSubscriptionChanges(string $applicationName, int $version): Response
    {
        return $this->endpoint("/ws/dataversion/{$applicationName}/{$version}")->get();
    }

    /**
     * Sends a count request to the table api
     */
    public function count(): Response
    {
        $this->endpoint .= '/count';
        $this->includeProjection = false;
        return $this->get();
    }

    /**
     * Sets a flag to return as a decoded json rather than an Illuminate\Response
     */
    public function raw(): static
    {
        $this->asJsonResponse = false;
        return $this;
    }

    /**
     * Sets the flag to return a response
     */
    public function asJsonResponse(): static
    {
        $this->asJsonResponse = true;
        return $this;
    }

    /**
     * Casts all the values recursively as a string
     */
    protected function castToValuesString(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            // Convert all keys to lowercase
            $lowercaseKey = strtolower($key);
            
            // Recursively set the nested array values
            if (is_array($value)) {
                $result[$lowercaseKey] = $this->castToValuesString($value);
                continue;
            }

            // If it's null set the value to an empty string
            if (is_null($value)) {
                $value = '';
            }

            // If the type is a bool, set it to the
            // integer type that PS uses, 1 or 0
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            // Cast everything as a string, otherwise PS
            // with throw a typecast error or something
            $result[$lowercaseKey] = (string) $value;
        }
        return $result;
    }

    /**
     * Builds the dumb request structure for PowerSchool
     */
    public function buildRequestJson(): static
    {
        if ($this->method === static::GET || $this->method === 'delete') {
            return $this;
        }

        // Reset the json object from previous requests
        $this->options['json'] = [];
        
        // Use more concise conditional assignments
        if ($this->table) {
            $this->options['json']['tables'] = [$this->table => $this->data];
        } elseif ($this->data) {
            $this->options['json'] = $this->data;
        }

        if ($this->id) {
            $this->options['json']['id'] = $this->id;
            $this->options['json']['name'] = $this->table;
        }

        // Remove the json option if there is nothing there
        if (empty($this->options['json'])) {
            unset($this->options['json']);
        }

        return $this;
    }

    /**
     * Builds the query string for the request
     */
    public function buildRequestQuery(): static
    {
        // Build the query by hand
        if ($this->method !== static::GET && $this->method !== static::POST) {
            return $this;
        }

        $this->options['query'] = '';
        $this->options['query'] = '';
        $qs = [];

        // Build the query string
        foreach ($this->queryString as $var => $val) {
            $qs[] = $var . '=' . $val;
        }

        // Get requests are required to have a projection parameter
        if (
            !$this->hasQueryVar('projection') &&
            $this->includeProjection
        ) {
            $qs[] = 'projection=*';
        }

        if (!empty($qs)) {
            $this->options['query'] = implode('&', $qs);
        }

        return $this;
    }

    /**
     * Sets the request method
     */
    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Alias for setMethod()
     */
    public function method(string $method): static
    {
        return $this->setMethod($method);
    }

    /**
     * Sets method to get, sugar around setMethod(), then sends the request
     */
    public function get(string $endpoint = null): Response
    {
        if ($endpoint) {
            $this->setEndpoint($endpoint);
        }

        return $this->setMethod(static::GET)->send();
    }

    /**
     * Sets method to post, sugar around setMethod(), then sends the request
     */
    public function post(): Response
    {
        return $this->setMethod(static::POST)->send();
    }

    /**
     * Sets method to put, sugar around setMethod(), then sends the request
     */
    public function put(): Response
    {
        return $this->setMethod(static::PUT)->send();
    }

    /**
     * Sets method to patch, sugar around setMethod(), then sends the request
     */
    public function patch(): Response
    {
        return $this->setMethod(static::PATCH)->send();
    }

    /**
     * Sets method to delete, sugar around setMethod(), then sends the request
     */
    public function delete(): Response
    {
        return $this->setMethod(static::DELETE)->send();
    }

    /**
     * Sends the request to PowerSchool
     */
    public function send(bool $reset = true): Response
    {
        $this->buildRequestJson()->buildRequestQuery();
        $responseData = $this->getRequest()->makeRequest($this->method, $this->endpoint, $this->options, $this->asJsonResponse);
        $response = new Response($responseData, $this->pageKey);
        if ($reset) $this->freshen();
        return $response;
    }

    /**
     * This will return a chunk of data from PowerSchool
     */
    public function paginate(int $pageSize = 100): ?Response
    {
        if (!isset($this->paginator)) {
            $this->paginator = new Paginator($this, $pageSize);
        }
        $results = $this->paginator->page();
        if ($results === null) {
            $this->freshen();
            return null;
        }
        return $results;
    }
}
