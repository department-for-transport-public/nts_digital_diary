<?php

namespace App\Utility\Security;

class Url
{
    protected ?string $scheme;
    protected ?string $host;
    protected ?int $port;
    protected ?string $user;
    protected ?string $pass;
    protected ?string $path;
    protected ?string $fragment;

    /** @var array|string[] */
    protected array $queryParams;

    public function __construct($url)
    {
        $parts = parse_url($url);

        $this->scheme = $parts['scheme'] ?? null;
        $this->host = $parts['host'] ?? null;
        $this->port = $parts['port'] ?? null;
        $this->user = $parts['user'] ?? null;
        $this->pass = $parts['pass'] ?? null;
        $this->path = $parts['path'] ?? null;
        $this->fragment = $parts['fragment'] ?? null;

        $this->queryParams = [];

        $query = $parts['query'] ?? '';
        $queryParts = explode('&', $query);

        foreach ($queryParts as $queryPart) {
            if ($queryPart === '') {
                continue;
            }

            $pair = explode('=', $queryPart);
            $this->queryParams[$pair[0]] = urldecode($pair[1]);
        }
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function setScheme(?string $scheme): self
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): self
    {
        $this->host = $host;
        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): self
    {
        $this->port = $port;
        return $this;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function setPass(?string $pass): self
    {
        $this->pass = $pass;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    public function setFragment(?string $fragment): self
    {
        $this->fragment = $fragment;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function setQueryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;
        return $this;
    }

    // --------------------------------------------------------------------------------------------------------------

    public function getQueryParam(string $key): ?string {
        return $this->queryParams[$key] ?? null;
    }

    public function setQueryParam(string $key, string $value): self {
        $this->queryParams[$key] = $value;
        return $this;
    }

    public function removeQueryParam(string $key): self {
        unset($this->queryParams[$key]);
        return $this;
    }

    public function __toString(): string {
        $queryParts = [];

        foreach($this->queryParams as $k => $v) {
            $encodedValue = urlencode($v);
            $queryParts[] = "{$k}={$encodedValue}";
        }

        $query = implode('&', $queryParts);

        return
            ($this->scheme ? "{$this->scheme}://" : '').
            ($this->user ? "{$this->user}" : '').
            ($this->pass ? ":{$this->pass}" : '').
            (($this->user || $this->pass) ? "@" : '').
            ($this->host ? "{$this->host}" : '').
            ($this->port ? ":{$this->port}" : '').
            ($this->path ? "{$this->path}" : '').
            ($query ? "?{$query}" : '').
            ($this->fragment ? "#{$this->fragment}" : '');
    }
}