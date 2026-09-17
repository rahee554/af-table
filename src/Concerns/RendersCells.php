<?php

namespace ArtflowStudio\Table\Concerns;

/**
 * Extracts a value out of a JSON column using a dot-notation path.
 *
 * Consolidated in 1.6.0 from: HasTemplateRendering, HasJsonSupport.
 * The template-rendering half of those traits (a hand-rolled {{ var }}
 * parser) was removed — real cell rendering goes through
 * AftableComponent::renderRawHtml(), which compiles actual Blade.
 */
trait RendersCells
{
    /**
     * Extract value from JSON column
     */
    protected function extractJsonValue($record, $columnKey, $jsonPath)
    {
        if (! isset($this->columns[$columnKey]['key'])) {
            return null;
        }

        $columnName = $this->columns[$columnKey]['key'];
        $jsonData = $record->$columnName;

        if (is_null($jsonData)) {
            return null;
        }

        // If it's already an array, use it directly
        if (is_array($jsonData)) {
            return $this->getNestedValue($jsonData, $jsonPath);
        }

        // Try to decode JSON string
        if (is_string($jsonData)) {
            $decodedData = json_decode($jsonData, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->getNestedValue($decodedData, $jsonPath);
            }
        }

        return null;
    }

    /**
     * Get nested value from array using dot notation
     */
    protected function getNestedValue($data, $path)
    {
        if (! is_array($data)) {
            return null;
        }

        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }
}
