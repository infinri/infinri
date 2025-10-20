<?php
declare(strict_types=1);

namespace Infinri\Core\Model;

use Infinri\Core\Model\ResourceModel\AbstractResource;

/**
 * Abstract Model
 * 
 * Base class for all models (Active Record pattern)
 */
abstract class AbstractModel
{
    /**
     * @var array<string, mixed> Model data
     */
    protected array $data = [];

    /**
     * @var array<string, mixed> Original data (for change detection)
     */
    protected array $origData = [];

    /**
     * @var bool Whether model has been deleted
     */
    protected bool $isDeleted = false;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->origData = $data;
    }

    /**
     * Get resource model
     *
     * @return AbstractResource
     */
    abstract protected function getResource(): AbstractResource;

    /**
     * Get ID field name
     *
     * @return string
     */
    protected function getIdFieldName(): string
    {
        return $this->getResource()->getIdFieldName();
    }

    /**
     * Get ID
     *
     * @return int|string|null
     */
    public function getId(): int|string|null
    {
        return $this->getData($this->getIdFieldName());
    }

    /**
     * Set ID
     *
     * @param int|string $id
     * @return $this
     */
    public function setId(int|string $id): self
    {
        return $this->setData($this->getIdFieldName(), $id);
    }

    /**
     * Load model by ID
     *
     * @param int|string $id
     * @return $this
     */
    public function load(int|string $id): self
    {
        $data = $this->getResource()->load($id);

        if ($data !== false) {
            $this->setData($data);
            $this->origData = $this->data;
        }

        return $this;
    }

    /**
     * Save model
     *
     * @return $this
     */
    public function save(): self
    {
        if ($this->isDeleted) {
            throw new \RuntimeException('Cannot save deleted model');
        }

        $id = $this->getResource()->save($this->data);

        if (!$this->getId()) {
            $this->setId($id);
        }

        $this->origData = $this->data;

        return $this;
    }

    /**
     * Delete model
     *
     * @return $this
     */
    public function delete(): self
    {
        if ($this->getId()) {
            $this->getResource()->delete($this->getId());
            $this->isDeleted = true;
        }

        return $this;
    }

    /**
     * Set data
     *
     * @param string|array<string, mixed> $key
     * @param mixed $value
     * @return $this
     */
    public function setData(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = $key;
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Get data
     *
     * @param string|null $key
     * @return mixed
     */
    public function getData(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    /**
     * Unset data
     *
     * @param string $key
     * @return $this
     */
    public function unsetData(string $key): self
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * Check if data has changed
     *
     * @param string|null $key
     * @return bool
     */
    public function hasDataChanged(?string $key = null): bool
    {
        if ($key === null) {
            return $this->data !== $this->origData;
        }

        return ($this->data[$key] ?? null) !== ($this->origData[$key] ?? null);
    }

    /**
     * Check if model exists in database
     *
     * @return bool
     */
    public function isObjectNew(): bool
    {
        return empty($this->origData);
    }

    /**
     * Check if model is deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Magic getter
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->getData($key);
    }

    /**
     * Magic setter
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setData($key, $value);
    }

    /**
     * Magic isset
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }
}
