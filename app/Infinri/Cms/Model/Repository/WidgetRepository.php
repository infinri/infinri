<?php
declare(strict_types=1);

namespace Infinri\Cms\Model\Repository;

use Infinri\Cms\Api\WidgetRepositoryInterface;
use Infinri\Cms\Model\Widget;
use Infinri\Cms\Model\ResourceModel\Widget as WidgetResource;
use PDO;

/**
 * CRUD operations for CMS widgets
 */
class WidgetRepository implements WidgetRepositoryInterface
{
    /**
     * @var PDO
     */
    private PDO $connection;

    /**
     * @var WidgetResource
     */
    private WidgetResource $widgetResource;

    /**
     * Constructor
     *
     * @param PDO $connection
     * @param WidgetResource $widgetResource
     */
    public function __construct(
        PDO            $connection,
        WidgetResource $widgetResource
    ) {
        $this->connection = $connection;
        $this->widgetResource = $widgetResource;
    }

    /**
     * Create new empty widget instance
     *
     * @return Widget
     */
    public function create(): Widget
    {
        return new Widget($this->widgetResource);
    }

    /**
     * Get widget by ID
     *
     * @param int $widgetId
     * @return Widget
     * @throws \RuntimeException if widget not found
     */
    public function getById(int $widgetId): Widget
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM cms_page_widget WHERE widget_id = :widget_id'
        );
        $stmt->execute(['widget_id' => $widgetId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new \RuntimeException(
                sprintf('Widget with ID %d not found', $widgetId)
            );
        }

        return new Widget($this->widgetResource, $data);
    }

    /**
     * Get all widgets
     *
     * @param bool $activeOnly Include only active widgets
     * @return Widget[]
     */
    public function getAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_page_widget';

        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }

        $sql .= ' ORDER BY page_id ASC, sort_order ASC, widget_id ASC';

        $stmt = $this->connection->query($sql);

        $widgets = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $widgets[] = new Widget($this->widgetResource, $data);
        }

        return $widgets;
    }

    /**
     * Get all widgets for a page (sorted by sort_order)
     *
     * @param int $pageId
     * @param bool $activeOnly Include only active widgets
     * @return Widget[]
     */
    public function getByPageId(int $pageId, bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM cms_page_widget WHERE page_id = :page_id';

        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, widget_id ASC';

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['page_id' => $pageId]);

        $widgets = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $widgets[] = new Widget($this->widgetResource, $data);
        }

        return $widgets;
    }

    /**
     * Save widget
     *
     * @param Widget $widget
     * @return Widget
     * @throws \InvalidArgumentException if validation fails
     */
    public function save(Widget $widget): Widget
    {
        // Validate before saving
        $widget->validate();

        $widgetId = $widget->getWidgetId();

        if ($widgetId) {
            // Update existing widget
            $this->update($widget);
        } else {
            // Insert new widget
            $this->insert($widget);
        }

        return $widget;
    }

    /**
     * Insert new widget
     *
     * @param Widget $widget
     * @return void
     */
    private function insert(Widget $widget): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO cms_page_widget 
            (page_id, widget_type, widget_data, sort_order, is_active, created_at, updated_at)
            VALUES 
            (:page_id, :widget_type, :widget_data, :sort_order, :is_active, NOW(), NOW())'
        );

        $stmt->execute([
            'page_id' => $widget->getPageId(),
            'widget_type' => $widget->getWidgetType(),
            'widget_data' => json_encode($widget->getWidgetData()),
            'sort_order' => $widget->getSortOrder(),
            'is_active' => $widget->getIsActive() ? 1 : 0,
        ]);

        $widget->setWidgetId((int)$this->connection->lastInsertId());
    }

    /**
     * Update existing widget
     *
     * @param Widget $widget
     * @return void
     */
    private function update(Widget $widget): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE cms_page_widget 
            SET page_id = :page_id,
                widget_type = :widget_type,
                widget_data = :widget_data,
                sort_order = :sort_order,
                is_active = :is_active,
                updated_at = NOW()
            WHERE widget_id = :widget_id'
        );

        $stmt->execute([
            'widget_id' => $widget->getWidgetId(),
            'page_id' => $widget->getPageId(),
            'widget_type' => $widget->getWidgetType(),
            'widget_data' => json_encode($widget->getWidgetData()),
            'sort_order' => $widget->getSortOrder(),
            'is_active' => $widget->getIsActive() ? 1 : 0,
        ]);
    }

    /**
     * Delete widget
     *
     * @param int $widgetId
     * @return bool
     * @throws \RuntimeException if widget not found
     */
    public function delete(int $widgetId): bool
    {
        // Verify widget exists
        $this->getById($widgetId);

        $stmt = $this->connection->prepare(
            'DELETE FROM cms_page_widget WHERE widget_id = :widget_id'
        );

        return $stmt->execute(['widget_id' => $widgetId]);
    }

    /**
     * Reorder widgets for a page
     *
     * @param int $pageId
     * @param array $widgetIds Array of widget IDs in desired order
     * @return bool
     * @throws \RuntimeException if reorder fails
     */
    public function reorder(int $pageId, array $widgetIds): bool
    {
        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare(
                'UPDATE cms_page_widget 
                SET sort_order = :sort_order 
                WHERE widget_id = :widget_id AND page_id = :page_id'
            );

            $sortOrder = 0;
            foreach ($widgetIds as $widgetId) {
                $stmt->execute([
                    'widget_id' => $widgetId,
                    'page_id' => $pageId,
                    'sort_order' => $sortOrder,
                ]);
                $sortOrder++;
            }

            $this->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw new \RuntimeException(
                sprintf('Failed to reorder widgets: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
