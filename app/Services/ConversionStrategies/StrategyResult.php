<?php

namespace App\Services\ConversionStrategies;

class StrategyResult
{
    /**
     * @var string $result The result status of the strategy
     */
    private string $result;

    /**
     * @var string|null $details Additional details about the strategy result
     */
    private ?string $details;

    /**
     * @var string|null $view The view name to be returned after the strategy execution
     */
    private ?string $view;
    
    /**
     * @var string|null $route The name of route to be redirected after the strategy execution
     */
    private ?string $route;

    /**
     * @var array $with Data to be passed with the view
     */
    private array $with;

    /**
     * @var string|null $next The next step or action to be taken
     */
    private ?string $next;

    public const STATUSES = [
        'COMPLETED' => 'completed',
        'FAILED' => 'failed',
        'PROCESSING' => 'processing',
        'REDIRECT' => 'redirect',
    ];

    /**
     * StrategyResult constructor.
     *
     * @param string $result
     * @param string|null $details
     * @param string|null $next
     * @param string|null $view
     * @param string|null $route
     * @param array $with
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $result,
        string $details = null,
        ?string $next = null,
        ?string $view = null,
        ?string $route = null,
        array $with = [],
    ) {
        $this->setResult($result);
        $this->details = $details;
        $this->next = $next;
        $this->view = $view;
        $this->route = $route;
        $this->with = $with;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function getView(): ?string
    {
        return $this->view;
    }
    
    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function getWith(): array
    {
        return $this->with;
    }

    public function getNext(): ?string
    {
        return $this->next;
    }

    /**
     * Set the result status.
     *
     * @param string $result
     * @throws \InvalidArgumentException
     */
    public function setResult(string $result): void
    {
        if (!in_array($result, self::STATUSES)) {
            throw new \InvalidArgumentException("Invalid result status: $result");
        }

        $this->result = $result;
    }

    public function setDetails(string $details): void
    {
        $this->details = $details;
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }
    
    public function setRoute(string $route): void
    {
        $this->route = $route;
    }

    public function setWith(array $with): void
    {
        $this->with = $with;
    }

    public function setNext(string $next): void
    {
        $this->next = $next;
    }

    /**
     * Chech if result is processing
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->result === self::STATUSES['PROCESSING'];
    }

    /**
     * Chech if result is completed
     *
     * @param mixed $result
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->result === self::STATUSES['COMPLETED'];
    }

    /**
     * Chech if result with error
     * 
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->result === self::STATUSES['FAILED'];
    }
    
    /**
     * Chech if result is redirect
     * 
     * @return bool
     */
    public function isRedirect(): bool
    {
        return $this->result === self::STATUSES['REDIRECT'];
    }
}
