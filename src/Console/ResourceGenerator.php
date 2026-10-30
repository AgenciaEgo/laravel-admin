<?php

namespace Encore\Admin\Console;

use Illuminate\Database\Eloquent\Model;

class ResourceGenerator
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $formats = [
        'form_field'  => "\$form->%s('%s', __('%s'))",
        'show_field'  => "\$show->field('%s', __('%s'))",
        'grid_column' => "\$grid->column('%s', __('%s'))",
    ];

    /**
     * @var array
     */
    private $doctrineTypeMapping = [
        'string' => [
            'enum', 'geometry', 'geometrycollection', 'linestring',
            'polygon', 'multilinestring', 'multipoint', 'multipolygon',
            'point',
        ],
    ];

    /**
     * @var array
     */
    protected $fieldTypeMapping = [
        'ip'       => 'ip',
        'email'    => 'email|mail',
        'password' => 'password|pwd',
        'url'      => 'url|link|src|href',
        'mobile'   => 'mobile|phone',
        'color'    => 'color|rgb',
        'image'    => 'image|img|avatar|pic|picture|cover',
        'file'     => 'file|attachment',
    ];

    /**
     * ResourceGenerator constructor.
     *
     * @param mixed $model
     */
    public function __construct($model)
    {
        $this->model = $this->getModel($model);
    }

    /**
     * @param mixed $model
     *
     * @return mixed
     */
    protected function getModel($model)
    {
        if ($model instanceof Model) {
            return $model;
        }

        if (!class_exists($model) || !is_string($model) || !is_subclass_of($model, Model::class)) {
            throw new \InvalidArgumentException("Invalid model [$model] !");
        }

        return new $model();
    }

    /**
     * @return string
     */
    public function generateForm()
    {
        $reservedColumns = $this->getReservedColumns();

        $output = '';

        foreach ($this->getTableColumns() as $column) {
            $name = $column['name'];
            if (in_array($name, $reservedColumns)) {
                continue;
            }
            $type = $column['type'];
            $default = $column['default'];

            $defaultValue = '';

            // set column fieldType and defaultValue
            switch ($type) {
                case 'boolean':
                case 'bool':
                    $fieldType = 'switch';
                    break;
                case 'json':
                case 'array':
                case 'object':
                    $fieldType = 'text';
                    break;
                case 'string':
                    $fieldType = 'text';
                    foreach ($this->fieldTypeMapping as $type => $regex) {
                        if (preg_match("/^($regex)$/i", $name) !== 0) {
                            $fieldType = $type;
                            break;
                        }
                    }
                    $defaultValue = "'{$default}'";
                    break;
                case 'integer':
                case 'bigint':
                case 'smallint':
                case 'timestamp':
                    $fieldType = 'number';
                    break;
                case 'decimal':
                case 'float':
                case 'real':
                    $fieldType = 'decimal';
                    break;
                case 'datetime':
                    $fieldType = 'datetime';
                    $defaultValue = "date('Y-m-d H:i:s')";
                    break;
                case 'date':
                    $fieldType = 'date';
                    $defaultValue = "date('Y-m-d')";
                    break;
                case 'time':
                    $fieldType = 'time';
                    $defaultValue = "date('H:i:s')";
                    break;
                case 'text':
                case 'blob':
                    $fieldType = 'textarea';
                    break;
                default:
                    $fieldType = 'text';
                    $defaultValue = "'{$default}'";
            }

            $defaultValue = $defaultValue ?: $default;

            $label = $this->formatLabel($name);

            $output .= sprintf($this->formats['form_field'], $fieldType, $name, $label);

            if (trim($defaultValue, "'\"")) {
                $output .= "->default({$defaultValue})";
            }

            $output .= ";\r\n";
        }

        return $output;
    }

    public function generateShow()
    {
        $output = '';

        foreach ($this->getTableColumns() as $column) {
            $name = $column['name'];

            // set column label
            $label = $this->formatLabel($name);

            $output .= sprintf($this->formats['show_field'], $name, $label);

            $output .= ";\r\n";
        }

        return $output;
    }

    public function generateGrid()
    {
        $output = '';

        foreach ($this->getTableColumns() as $column) {
            $name = $column['name'];
            $label = $this->formatLabel($name);

            $output .= sprintf($this->formats['grid_column'], $name, $label);
            $output .= ";\r\n";
        }

        return $output;
    }

    protected function getReservedColumns()
    {
        return [
            $this->model->getKeyName(),
            $this->model->getCreatedAtColumn(),
            $this->model->getUpdatedAtColumn(),
            'deleted_at',
        ];
    }

    /**
     * Get columns of a giving model.
     *
     * @throws \Exception
     *
     * @return array[] each item has `name`, `type` (doctrine-style type name) and `default`
     */
    protected function getTableColumns()
    {
        $connection = $this->model->getConnection();
        $schemaBuilder = $connection->getSchemaBuilder();

        // Laravel >= 10.x ships native schema introspection, doctrine/dbal was dropped in Laravel 11.
        if (method_exists($schemaBuilder, 'getColumns')) {
            return array_map(function ($column) {
                return [
                    'name'    => $column['name'],
                    'type'    => $this->normalizeNativeType($column['type_name'], $column['type']),
                    'default' => $this->normalizeNativeDefault($column['default']),
                ];
            }, $schemaBuilder->getColumns($this->model->getTable()));
        }

        return $this->getDoctrineTableColumns();
    }

    /**
     * Get columns through doctrine/dbal (Laravel 9 and early 10.x).
     *
     * @throws \Exception
     *
     * @return array[]
     */
    protected function getDoctrineTableColumns()
    {
        $connection = $this->model->getConnection();

        if (!method_exists($connection, 'isDoctrineAvailable') || !$connection->isDoctrineAvailable()) {
            throw new \Exception(
                'You need to require doctrine/dbal: ^3.0 in your own composer.json to get database columns. '
            );
        }

        $table = $connection->getTablePrefix().$this->model->getTable();
        /** @var \Doctrine\DBAL\Schema\MySqlSchemaManager $schema */
        $schema = $connection->getDoctrineSchemaManager($table);

        // custom mapping the types that doctrine/dbal does not support
        $databasePlatform = $schema->getDatabasePlatform();

        foreach ($this->doctrineTypeMapping as $doctrineType => $dbTypes) {
            foreach ($dbTypes as $dbType) {
                $databasePlatform->registerDoctrineTypeMapping($dbType, $doctrineType);
            }
        }

        $database = null;
        if (strpos($table, '.')) {
            list($database, $table) = explode('.', $table);
        }

        return array_map(function ($column) {
            return [
                'name'    => $column->getName(),
                'type'    => $column->getType()->getName(),
                'default' => $column->getDefault(),
            ];
        }, array_values($schema->listTableColumns($table, $database)));
    }

    /**
     * Map a native database type to the doctrine-style type names used by the generators.
     *
     * @param string $typeName e.g. `varchar`, `tinyint`
     * @param string $type     full type, e.g. `tinyint(1)`
     *
     * @return string
     */
    protected function normalizeNativeType($typeName, $type)
    {
        $typeName = strtolower($typeName);

        if ($typeName === 'tinyint' && preg_match('/^tinyint\(1\)/i', $type)) {
            return 'boolean';
        }

        $map = [
            'boolean'   => ['bool', 'boolean'],
            'integer'   => ['int', 'integer', 'mediumint', 'tinyint', 'int4', 'int2'],
            'bigint'    => ['bigint', 'int8'],
            'smallint'  => ['smallint'],
            'decimal'   => ['decimal', 'numeric'],
            'float'     => ['float', 'double', 'real', 'double precision', 'float4', 'float8'],
            'datetime'  => ['datetime', 'datetime2', 'timestamp without time zone', 'timestamptz', 'timestamp with time zone'],
            'timestamp' => ['timestamp'],
            'date'      => ['date'],
            'time'      => ['time', 'time without time zone'],
            'json'      => ['json', 'jsonb'],
            'text'      => ['text', 'tinytext', 'mediumtext', 'longtext'],
            'blob'      => ['blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary', 'bytea'],
        ];

        foreach ($map as $normalized => $types) {
            if (in_array($typeName, $types, true)) {
                return $normalized;
            }
        }

        return 'string';
    }

    /**
     * Strip the quotes some drivers keep around string defaults (e.g. sqlite).
     *
     * @param mixed $default
     *
     * @return mixed
     */
    protected function normalizeNativeDefault($default)
    {
        if (is_string($default) && preg_match("/^'(.*)'$/s", $default, $matches)) {
            return str_replace("''", "'", $matches[1]);
        }

        return $default;
    }

    /**
     * Format label.
     *
     * @param string $value
     *
     * @return string
     */
    protected function formatLabel($value)
    {
        return ucfirst(str_replace(['-', '_'], ' ', $value));
    }
}
