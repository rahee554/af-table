<?php

namespace ArtflowStudio\Table\Traits\Core;

/**
 * HasTemplateRendering - Unified template rendering functionality
 * 
 * This trait consolidates rendering methods from:
 * - HasTemplateEngine (template processing)
 * - HasCustomRendering (custom cell rendering)
 * - HasBladeComponents (Blade component integration)
 * 
 * Purpose: Standardize template syntax and eliminate rendering conflicts
 */
trait HasTemplateRendering
{
    // *----------- Template Properties -----------*//
    
    protected $templateCache = [];
    protected $customRenderers = [];
    protected $renderingContext = [];
    
    // *----------- Core Template Methods -----------*//
    
    /**
     * Render cell value with template processing
     * Consolidated from HasTemplateEngine and HasCustomRendering
     */
    public function renderCellValue($column, $value, $record = null)
    {
        $columnConfig = $this->getColumnConfig($column);
        
        // Check for custom renderer first
        if (isset($columnConfig['renderer'])) {
            return $this->applyCustomRenderer($column, $value, $record, $columnConfig['renderer']);
        }
        
        // Check for template
        if (isset($columnConfig['template'])) {
            return $this->processTemplate($columnConfig['template'], $value, $record, $column);
        }
        
        // Apply default formatting
        return $this->applyDefaultFormatting($column, $value, $columnConfig);
    }
    
    /**
     * Process template with context
     * Consolidated from HasTemplateEngine
     */
    public function processTemplate($template, $value, $record = null, $column = null)
    {
        // Standardize template syntax to {{}} only
        $template = $this->standardizeTemplateSyntax($template);
        
        // Build template context
        $context = $this->buildTemplateContext($value, $record, $column);
        
        // Process template with caching
        $cacheKey = md5($template . serialize($context));
        
        if (isset($this->templateCache[$cacheKey])) {
            return $this->templateCache[$cacheKey];
        }
        
        $rendered = $this->renderTemplate($template, $context);
        $this->templateCache[$cacheKey] = $rendered;
        
        return $rendered;
    }
    
    /**
     * Standardize template syntax to {{}} only
     * Fixes issues with {[]} and @{{}} patterns
     */
    protected function standardizeTemplateSyntax($template): string
    {
        if (!is_string($template)) {
            return $template;
        }
        
        // Remove problematic {[]} patterns
        $template = preg_replace('/\{\[\s*([^}]+)\s*\]\}/', '{{ $1 }}', $template);
        
        // Remove problematic @{{}} patterns and convert to standard {{}}
        $template = preg_replace('/@\{\{\s*([^}]+)\s*\}\}/', '{{ $1 }}', $template);
        
        // Ensure proper spacing in {{}} patterns
        $template = preg_replace('/\{\{\s*([^}]+)\s*\}\}/', '{{ $1 }}', $template);
        
        return $template;
    }
    
    /**
     * Render template with context variables
     * From HasTemplateEngine
     */
    protected function renderTemplate($template, $context): string
    {
        // Simple template rendering - replace {{variable}} with context values
        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) use ($context) {
            $variable = trim($matches[1]);
            
            // Handle nested properties like record.name or value.status
            if (strpos($variable, '.') !== false) {
                return $this->getNestedValue($variable, $context);
            }
            
            // Handle method calls like format_date(value) or uppercase(record.name)
            if (preg_match('/(\w+)\(([^)]+)\)/', $variable, $methodMatches)) {
                $method = $methodMatches[1];
                $parameter = trim($methodMatches[2]);
                $paramValue = $this->getContextValue($parameter, $context);
                
                return $this->applyTemplateMethod($method, $paramValue, $context);
            }
            
            // Simple variable replacement
            return $this->getContextValue($variable, $context);
        }, $template);
    }
    
    /**
     * Build template context from available data
     * From HasTemplateEngine
     */
    protected function buildTemplateContext($value, $record, $column): array
    {
        $context = [
            'value' => $value,
            'record' => $record,
            'column' => $column,
            'component' => $this
        ];
        
        // Add custom rendering context
        if (!empty($this->renderingContext)) {
            $context = array_merge($context, $this->renderingContext);
        }
        
        // Add helper methods context
        $context['helpers'] = $this->getTemplateHelpers();
        
        return $context;
    }
    
    /**
     * Get value from context by key (supports dot notation)
     * From HasTemplateEngine
     */
    protected function getContextValue($key, $context)
    {
        if (strpos($key, '.') !== false) {
            return $this->getNestedValue($key, $context);
        }
        
        return $context[$key] ?? $key; // Return key as fallback if not found
    }
    
    /**
     * Get nested value using dot notation
     * From HasTemplateEngine
     */
    protected function getNestedValue($path, $context)
    {
        $keys = explode('.', $path);
        $value = $context;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } elseif (is_object($value) && isset($value->$key)) {
                $value = $value->$key;
            } else {
                return $path; // Return original path if not found
            }
        }
        
        return $value;
    }
    
    // *----------- Custom Rendering Methods -----------*//
    
    /**
     * Apply custom renderer to value
     * Consolidated from HasCustomRendering
     */
    protected function applyCustomRenderer($column, $value, $record, $renderer)
    {
        // Check if renderer is a method name
        if (is_string($renderer) && method_exists($this, $renderer)) {
            return $this->$renderer($value, $record, $column);
        }
        
        // Check if renderer is a callable
        if (is_callable($renderer)) {
            return call_user_func($renderer, $value, $record, $column, $this);
        }
        
        // Check if renderer is a registered custom renderer
        if (is_string($renderer) && isset($this->customRenderers[$renderer])) {
            return $this->applyCustomRenderer($column, $value, $record, $this->customRenderers[$renderer]);
        }
        
        // Default to template processing if renderer is a string
        if (is_string($renderer)) {
            return $this->processTemplate($renderer, $value, $record, $column);
        }
        
        // Fallback to original value
        return $value;
    }
    
    /**
     * Register custom renderer
     * From HasCustomRendering
     */
    public function registerCustomRenderer($name, $renderer)
    {
        $this->customRenderers[$name] = $renderer;
    }
    
    /**
     * Apply default formatting based on column type
     * From HasCustomRendering
     */
    protected function applyDefaultFormatting($column, $value, $columnConfig)
    {
        $type = $columnConfig['type'] ?? 'text';
        
        switch ($type) {
            case 'date':
                return $this->formatDate($value, $columnConfig['format'] ?? 'Y-m-d');
                
            case 'datetime':
                return $this->formatDate($value, $columnConfig['format'] ?? 'Y-m-d H:i:s');
                
            case 'currency':
                return $this->formatCurrency($value, $columnConfig['currency'] ?? 'USD');
                
            case 'number':
                return $this->formatNumber($value, $columnConfig['decimals'] ?? 2);
                
            case 'percentage':
                return $this->formatPercentage($value, $columnConfig['decimals'] ?? 1);
                
            case 'boolean':
                return $this->formatBoolean($value, $columnConfig);
                
            case 'enum':
                return $this->formatEnum($value, $columnConfig['options'] ?? []);
                
            case 'badge':
                return $this->renderBadge($value, $columnConfig);
                
            case 'link':
                return $this->renderLink($value, $columnConfig, $column);
                
            case 'image':
                return $this->renderImage($value, $columnConfig);
                
            default:
                return $this->formatText($value, $columnConfig);
        }
    }
    
    // *----------- Template Method Handlers -----------*//
    
    /**
     * Apply template method to value
     * From HasTemplateEngine
     */
    protected function applyTemplateMethod($method, $value, $context)
    {
        switch ($method) {
            case 'uppercase':
            case 'upper':
                return strtoupper($value);
                
            case 'lowercase':
            case 'lower':
                return strtolower($value);
                
            case 'capitalize':
                return ucwords($value);
                
            case 'title':
                return ucfirst($value);
                
            case 'truncate':
                return $this->truncateText($value, 50);
                
            case 'format_date':
                return $this->formatDate($value);
                
            case 'format_currency':
                return $this->formatCurrency($value);
                
            case 'format_number':
                return $this->formatNumber($value);
                
            case 'strip_tags':
                return strip_tags($value);
                
            case 'nl2br':
                return nl2br($value);
                
            case 'escape':
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                
            default:
                // Check if method exists on component
                if (method_exists($this, $method)) {
                    return $this->$method($value, $context);
                }
                
                return $value; // Return unchanged if method not found
        }
    }
    
    /**
     * Get available template helpers
     * From HasTemplateEngine
     */
    protected function getTemplateHelpers(): array
    {
        return [
            'format_date' => 'formatDate',
            'format_currency' => 'formatCurrency',
            'format_number' => 'formatNumber',
            'truncate' => 'truncateText',
            'upper' => 'strtoupper',
            'lower' => 'strtolower',
            'capitalize' => 'ucwords'
        ];
    }
    
    // *----------- Formatting Methods -----------*//
    
    /**
     * Format date value
     */
    protected function formatDate($value, $format = 'Y-m-d')
    {
        if (empty($value)) {
            return '';
        }
        
        try {
            if ($value instanceof \DateTime) {
                return $value->format($format);
            }
            
            return date($format, strtotime($value));
        } catch (\Exception $e) {
            return $value; // Return original value if formatting fails
        }
    }
    
    /**
     * Format currency value
     */
    protected function formatCurrency($value, $currency = 'USD')
    {
        if (!is_numeric($value)) {
            return $value;
        }
        
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥'
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($value, 2);
    }
    
    /**
     * Format number value
     */
    protected function formatNumber($value, $decimals = 2)
    {
        if (!is_numeric($value)) {
            return $value;
        }
        
        return number_format($value, $decimals);
    }
    
    /**
     * Format percentage value
     */
    protected function formatPercentage($value, $decimals = 1)
    {
        if (!is_numeric($value)) {
            return $value;
        }
        
        return number_format($value * 100, $decimals) . '%';
    }
    
    /**
     * Format boolean value
     */
    protected function formatBoolean($value, $config = [])
    {
        $trueLabel = $config['true_label'] ?? 'Yes';
        $falseLabel = $config['false_label'] ?? 'No';
        
        return $value ? $trueLabel : $falseLabel;
    }
    
    /**
     * Format enum value
     */
    protected function formatEnum($value, $options = [])
    {
        return $options[$value] ?? $value;
    }
    
    /**
     * Format text with basic options
     */
    protected function formatText($value, $config = [])
    {
        if (!is_string($value)) {
            return $value;
        }
        
        // Apply text transformations
        if (isset($config['transform'])) {
            switch ($config['transform']) {
                case 'uppercase':
                    $value = strtoupper($value);
                    break;
                case 'lowercase':
                    $value = strtolower($value);
                    break;
                case 'capitalize':
                    $value = ucwords($value);
                    break;
            }
        }
        
        // Apply truncation
        if (isset($config['truncate'])) {
            $value = $this->truncateText($value, $config['truncate']);
        }
        
        return $value;
    }
    
    /**
     * Truncate text to specified length
     */
    protected function truncateText($text, $length = 50, $suffix = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }
    
    // *----------- Render Component Methods -----------*//
    
    /**
     * Render badge component
     */
    protected function renderBadge($value, $config = [])
    {
        $class = $config['class'] ?? 'badge-primary';
        $colorMap = $config['color_map'] ?? [];
        
        if (isset($colorMap[$value])) {
            $class = $colorMap[$value];
        }
        
        return "<span class=\"badge {$class}\">" . htmlspecialchars($value) . "</span>";
    }
    
    /**
     * Render link component
     */
    protected function renderLink($value, $config, $column)
    {
        $url = $config['url'] ?? '#';
        $target = $config['target'] ?? '_self';
        $class = $config['class'] ?? '';
        
        // Process URL template
        if (strpos($url, '{{') !== false) {
            $url = $this->processTemplate($url, $value, null, $column);
        }
        
        return "<a href=\"{$url}\" target=\"{$target}\" class=\"{$class}\">" . htmlspecialchars($value) . "</a>";
    }
    
    /**
     * Render image component
     */
    protected function renderImage($value, $config = [])
    {
        $width = $config['width'] ?? 'auto';
        $height = $config['height'] ?? 'auto';
        $class = $config['class'] ?? '';
        $alt = $config['alt'] ?? '';
        
        return "<img src=\"{$value}\" alt=\"{$alt}\" class=\"{$class}\" style=\"width: {$width}; height: {$height};\">";
    }
    
    // *----------- Cache and Context Management -----------*//
    
    /**
     * Clear template cache
     */
    public function clearTemplateCache()
    {
        $this->templateCache = [];
    }
    
    /**
     * Set rendering context
     */
    public function setRenderingContext($context)
    {
        $this->renderingContext = $context;
    }
    
    /**
     * Add to rendering context
     */
    public function addToRenderingContext($key, $value)
    {
        $this->renderingContext[$key] = $value;
    }
    
    /**
     * Get template cache statistics
     */
    public function getTemplateCacheStats(): array
    {
        return [
            'cached_templates' => count($this->templateCache),
            'custom_renderers' => count($this->customRenderers),
            'context_variables' => count($this->renderingContext)
        ];
    }
}
